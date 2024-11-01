<?php
namespace OKD_CF7SH;
use OKD_CF7SH\Functions\Tools;
use OKD_CF7SH\Functions\Common as Common;

defined('ABSPATH') || exit;

/**
 * Add all hooks
 */
class Hooks {
  protected static $instance = null;
  public static $common;
  
  public static function getInstance() {
    if (null == self::$instance) {      
      self::$instance = new self;
      self::$instance->addHooks();
    }

    return self::$instance;
  }
  
  private function addHooks(){
      //load form
      add_filter('wpcf7_form_response_output', array($this,'contact_form_load_hook'),10,5);
      //save data to database hook
      add_action('wpcf7_before_send_mail', array($this,'before_send_email_hook'));
      //change upload file path hook
      add_filter('okd_cf7sh_modify_form_before_insert_data', array($this,'modify_form_before_insert_data_hook'));

      //edit value
      add_action('cf7sh_admin_after_heading_field', array($this,'cf7sh_admin_after_heading_edit_field_func'));
      add_action('cf7sh_admin_after_body_field',array($this, 'cf7sh_admin_after_body_edit_field_func'), 10, 2);
      add_action('cf7sh_after_admin_form',array($this, 'cf7sh_after_admin_form_edit_value_func'));
      add_action('wp_ajax_cf7sh_edit_value', array($this,'cf7sh_edit_value_ajax_func'));
      add_action('cf7sh_main_post',array($this, 'cf7sh_submit_changed_values_cb'));
      
      //search      
      add_filter('cf7sh_get_entries_custom_where', array($this,'cf7sh_get_entries_custom_where_datetime_hook'), 10);
      add_action('cf7sh_operation_control', array($this,'cf7sh_operation_control_search_hook'), 10,2);
      
      //unique-id
      add_filter('cf7sh_before_printing_data', array($this,'cf7sh_before_printing_data_filter'), 10, 2);
      add_filter('cf7sh_admin_fields', array($this,'filter_cf7sh_admin_fields'), 10, 2);
      
      //export
      add_action('cf7sh_operation_control', array($this,'cf7sh_operation_control_export_hook'), 20);
      add_action('cf7sh_main_post', array($this,'cf7sh_export_action_cb'));
      
      //analytics
      add_action('cf7sh_after_insert_data', array($this,'add_analytics_data_hook'),10,3);
      //search controls
      add_action('cf7sh_operation_control_analytics', array($this,'cf7sh_operation_control_search_hook'),10,2);                  
  }

  public function __construct() {
      self::$common= Common::getInstance();
  }
  
  function contact_form_load_hook($output, $class, $content, $contactform, $status ){
      $formid=$contactform->id;
      self::$common->Add_analytics_data("pageload",$formid);
      return $output;
  }
  
  function cf7sh_operation_control_search_hook($formid,$currentPage)
  {
      $url = admin_url('admin.php?page='.$currentPage);
      $startDateDefault=Tools::AddDay(date('Y-m-d'),"-6");
      $endDateDefault=date('Y-m-d');
      ?>
      <?php 
        if($currentPage==OKD_CF7SH_MENU_SH){ 
            $startDateDefault='';
            $endDateDefault='';
        ?>
        <?php 
        }                      
        ?>
         <input value="<?php echo ((isset($_GET['startdate'])) ? esc_attr(wp_unslash($_GET['startdate'])) :$startDateDefault ); ?>" type="text" class="datepicker" autocomplete="off" name="startdate" id="cf7sh-startdate-q" placeholder="<?php echo _e('Start date',"okd_cf7sh"); ?>" />
         <input value="<?php echo ((isset($_GET['enddate'])) ? esc_attr(wp_unslash($_GET['enddate'])) : $endDateDefault); ?>" type="text" class="datepicker" autocomplete="off"  name="enddate" id="cf7sh-enddate-q" placeholder="<?php echo _e('End date',"okd_cf7sh"); ?>" />
        
        <?php 
        if($currentPage==OKD_CF7SH_MENU_SH){            
        ?>
        <input value="<?php echo ((isset($_GET['search'])) ? esc_attr(wp_unslash($_GET['search'])) : ''); ?>" type="text" class="" id="cf7sh-search-q"  name="search" placeholder="<?php echo __('Enter search key',"okd_cf7sh"); ?>" />
        <?php 
        }
        ?>
        <button data-url="<?php echo esc_url($url); ?>" class="button" type="button" id="cf7sh-search-btn"><?php _e('Search'); ?></button>
        <?php
  }  
  
  function cf7sh_get_entries_custom_where_datetime_hook()
  {
      global  $wpdb;
      $startdate = ((isset($_GET['startdate'])) ? sanitize_text_field(addslashes($_GET['startdate'])) : '');      
      $startdate=$startdate==''?'1900-01-01':$startdate;
      $enddate = ((isset($_GET['enddate'])) ? sanitize_text_field(addslashes($_GET['enddate'])) : '');
      $enddate=$enddate==''?'2100-12-31':$enddate;
      return array(" and data_id in (select data_id from ".$wpdb->prefix."cf7sh_okd_submission_history_entry where `name` = 'submit_time' AND `value` between '".$startdate." 00:00:00' and '".$enddate." 23:59:59')");
  }
    
  function cf7sh_export_action_cb()
  {
      if (isset($_GET['cf7sh-export']) && isset($_GET['btn_export'])) {
          add_filter('cf7sh_get_current_action', false);
          $fid = (int)$_GET['fid'];
          
          $ids_export = ((isset($_GET['del_id'])) ? implode(',', array_map('intval', $_GET['del_id'])) : '');
          
          $type = self::$common->cf7sh_sanitize_arr($_GET['cf7sh-export']);
          switch ($type) {
              case 'csv':
                  self::$common->cf7sh_export_to_csv($fid, $ids_export);
                  break;
              case '-1':
                  return;
                  break;
              default:
                  return;
                  break;
          }
      }
  }
  
  function cf7sh_operation_control_export_hook($fid)
  {
      ?>
        <select id="cf7sh-export" name="cf7sh-export" data-fid="<?php echo intval($fid); ?>">
            <option value="-1"><?php _e('Export to...',"okd_cf7sh"); ?></option>
            <option value="csv"><?php _e('CSV',"okd_cf7sh"); ?></option>
        </select>
        <button class="button action" type="submit" name="btn_export"><?php _e('Export',"okd_cf7sh"); ?></button>
        <?php
    }
  
  function filter_cf7sh_admin_fields($fields, $fid) {
      return array('data_id' => 'ID') + $fields;
  }
  
  function cf7sh_before_printing_data_filter($data, $fid) {
      foreach($data as $k => $v) {
          $data[$k]['data_id'] = $k;
      }
      return $data;
  }
  
  function cf7sh_submit_changed_values_cb()
  {
      global $wpdb;
      if (isset($_POST['cf7sh_save_value_field'])) {
          $fid = (int)sanitize_text_field($_POST['fid']);
          $rid = (int)sanitize_text_field($_POST['rid']);
          $field=$_POST['field'];
          foreach ($field as $key => $value) {
              $wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."cf7sh_okd_submission_history_entry SET `value` = %s WHERE `name` = %s AND `data_id` = %d", self::$common->cf7sh_sanitize_arr($value), self::$common->cf7sh_sanitize_arr($key), $rid));
          }
      }
  }
  
  function cf7sh_edit_value_ajax_func()
  {
      global $wpdb;
      $rid = ((isset($_POST['rid'])) ? (int)sanitize_text_field($_POST['rid']) : '');
      if (!empty($rid)) {
          $sql = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."cf7sh_okd_submission_history_entry WHERE `data_id` = %d", $rid);
          $rows = $wpdb->get_results($sql);
          $return = array();
          foreach ($rows as $k => $v) {
              $return[$v->name] = stripslashes($v->value);
          }
          exit(json_encode($return));
      }
  }
  
  function cf7sh_after_admin_form_edit_value_func($form_id)
  {
      $fields = self::$common->cf7sh_get_db_fields($form_id, false);
      ?>
        <div class="cf7sh-modal" id="cf7sh-modal-edit-value" style="display:none;">
            <form action="" class="cf7sh-modal-form loading" id="cf7sh-modal-form-edit-value" method="POST">
                <input type="hidden" name="fid" value="<?php echo esc_attr($form_id); ?>" />
                <input type="hidden" name="rid" value="" />
                <ul id="cf7sh-list-field-for-edit">
                    <?php
                        foreach ($fields as $k => $v) {
                            $label = $v;
                            $loading = __('Loading...');
                            echo sprintf("<li class=\"li-field-%s\" style='display:none'><span class=\"label\">%s</span> <input class=\"field-%s\" type=\"text\" name=\"field[%s]\" value=\"%s\" /></li>", esc_attr($k), esc_html($label), esc_attr($k), esc_attr($k), esc_attr($loading));
                        }
                    ?>
                </ul>
                <div class="cf7sh-modal-footer">
                    <input type="submit" name="cf7sh_save_value_field" value="<?php echo esc_attr(__('Save Changes')) ?>" class="button button-primary button-large" />
                </div>
            </form>
        </div>
        <?php
  }
  
  function cf7sh_admin_after_body_edit_field_func($form_id, $row_id)
  {
      ?>
        <td><a data-rid="<?php echo esc_attr($row_id); ?>" href="#TB_inline?width=500&height=550&inlineId=cf7sh-modal-edit-value" class="thickbox cf7sh-edit-value dashicons dashicons-edit-large" title="<?php _e('Edit'); ?>"></a></td>
        <?php
    }
  
  function cf7sh_admin_after_heading_edit_field_func()
  {
      ?>
        <th style="width: 80px;" class="manage-column"><?php _e('Edit'); ?></th>
        <?php
    }
    
  function modify_form_before_insert_data_hook($cf7)
  {
      //if it has at least 1 file uploaded
      if (count($cf7->uploaded_files) > 0) {
          $upload_dir = wp_upload_dir();
          $cf7sh_upload_folder = $this->common->cf7sh_upload_folder();
          $dir_upload = $upload_dir['basedir'] . '/' . $cf7sh_upload_folder;
          wp_mkdir_p($dir_upload);
          foreach ($cf7->uploaded_files as $k => $v) {
              $file_name = basename($v);
              $file_name = wp_unique_filename($dir_upload, $file_name);
              $dst_file = $dir_upload . '/' . $file_name;
              if (@copy($v, $dst_file)) {
                  $cf7->posted_data[$k] = $upload_dir['baseurl'] . '/' . $cf7sh_upload_folder . '/' . $file_name;
              }
          }
      }
      return $cf7;
  }

  //save data to db
  public function before_send_email_hook($contact_form)
  {          
      global $wpdb;
      do_action('cf7sh_before_insert_db', $contact_form);
      
      $cf7_id = $contact_form->id();
      $contact_form = self::$common->cf7sh_get_posted_data($contact_form);
      
      //for database installion
      $contact_form = self::$common->cf7sh_add_more_fields($contact_form);
      
      //Modify $contact_form
      $contact_form = apply_filters('cf7sh_modify_form_before_insert_data', $contact_form);
      //Type's $contact_form->posted_data is array
      $contact_form->posted_data = apply_filters('cf7sh_posted_data', $contact_form->posted_data);
      $time = date('Y-m-d H:i:s');
      $wpdb->query($wpdb->prepare('INSERT INTO '.$wpdb->prefix.'cf7sh_okd_submission_history(`created`) VALUES (%s)', $time));
      $data_id = $wpdb->insert_id;
      //install to database
      $cf7sh_no_save_fields = self::$common->cf7sh_no_save_fields();
      foreach ($contact_form->posted_data as $k => $v) {
          if (in_array($k, $cf7sh_no_save_fields)) {
              continue;
          } else {
              if (is_array($v)) {
                  $v = implode("\n", $v);
              }
              $wpdb->query($wpdb->prepare('INSERT INTO '.$wpdb->prefix.'cf7sh_okd_submission_history_entry(`cf7_id`, `data_id`, `name`, `value`) VALUES (%d,%d,%s,%s)', $cf7_id, $data_id, $k, $v));
          }
      }
      do_action('cf7sh_after_insert_data', $contact_form, $cf7_id, $data_id);          
  }  
                
  function add_analytics_data_hook($contact_form, $cf7_id, $data_id){
      self::$common->Add_analytics_data("submit",$cf7_id);      
  }
}
