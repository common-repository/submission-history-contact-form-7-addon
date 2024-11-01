<?php
defined('ABSPATH') || exit;

echo '<div class="wrap"><h1>' . __('Submission History',"okd_cf7sh") . '</h1>';

$forms = get_posts(array(
    'post_status' => 'any',
    'posts_per_page' => -1,
    'offset' => 0,
    'orderby' => apply_filters('cf7-db-forms-orderby', 'ID'),
    'order' => apply_filters('cf7-db-forms-order', 'ASC'),
    'post_type' => 'wpcf7_contact_form'
));

$first_form_id = 0;
$fid = ((isset($_GET['fid'])) ? (int) sanitize_text_field($_GET['fid']) : '');
$currentPage = ((isset($_GET['page'])) ? (string)sanitize_text_field($_GET['page']) : '');
echo '<form action="'.admin_url('admin.php').'" method="GET">';
echo '<input type="hidden" name="page" value="'.OKD_CF7SH_MENU_SH.'" />';
echo '<label class="SubHead">'.__('Current Form:',"okd_cf7sh").' </label><select name="fid" onchange="this.form.submit();">';
echo '<option value="" '.(( count($forms) == 0 ) ? 'selected="selected"' : '').' >'.__('-- Select Form --',"okd_cf7sh").'</option>';
$i = 0;
foreach ($forms as $k => $v) {
    if ($first_form_id == 0 && $i == 0) {
        $first_form_id = $v->ID;
        
        if (empty($fid) && ($first_form_id > 0)) {
            $fid = $first_form_id;
        }
    }
    echo '<option value="'.$v->ID.'" '.(( !empty($fid) && ($fid == $v->ID)) ? 'selected="selected"' : '').'>'.$v->post_title.'</option>';
}
echo '</select>';
echo '</form>';

if (!empty($fid)) {
    $obj = $common->cf7sh_get_rows($fid);    
    $fields = $obj['fields'];
    unset($fields['submit_user_id']);
    $data_sorted = $obj['data_sorted'];
    $total = $obj['total'];
    $items_per_page = $obj['items_per_page'];
    $page = $obj['page'];
    
    $entry_actions = array(
        'delete' => __('Delete',"okd_cf7sh")
    );
    $entry_actions = apply_filters('cf7sh_entry_actions', $entry_actions);
    ?>
            <form action="" method="GET" id="cf7sh-admin-action-frm">
                <input type="hidden" name="page" value="<?php echo OKD_CF7SH_MENU_SH;?>">
                <input type="hidden" name="fid" value="<?php echo esc_attr($fid); ?>">
                <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('cf7sh-nonce'); ?>">
                <div class="tablenav top">
                	<div class="alignleft">
                		<?php do_action('cf7sh_operation_control', $fid,$currentPage); ?>   
                	</div>                       	       	                	
                    <div class="alignright actions bulkactions">                    	
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e('Select bulk action',"okd_cf7sh");?></label>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk Actions',"okd_cf7sh"); ?></option>
                            <?php echo $common->cf7sh_arr_to_option($entry_actions); ?>
                        </select>    
                          <input id="doaction" onclick="var action=jQuery('#bulk-action-selector-top option:selected').val();  if(action=='delete'){if(!confirm('<?php echo __('Are you sure you want to delete?',"okd_cf7sh"); ?>')){return false;}}else{alert('<?php echo __('Please select an action.',"okd_cf7sh"); ?>'); return false;}" name="btn_apply" class="button action" value="<?php _e('Apply',"okd_cf7sh"); ?>" type="submit" />                      
                     </div>
                     <div class="alignright selectall">                           
                           <label class="SubHead" for="cb-select-all-item"><?php echo __('Select All: ',"okd_cf7sh"); ?></label> <input type="checkbox" id="cb-select-all-item" />                                       
            		</div>  
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo (($total == 1) ?
                        '1 ' . __('item') :
                        $total . ' ' . __('items')) ?></span>
                        <span class="pagination-links">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('cpage', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => ceil($total / $items_per_page),
                                'current' => $page
                            ));
                            ?>
                        </span>
                    </div>
                    <br class="clear">
                </div>
                <div class="cf7sh_container">              
                        <?php
                        $i=0;
                        foreach ($data_sorted as $k => $v) {
                            if($i%2==0){
                                echo '<div class="cf7sh_field_flex">';
                            }
                            echo '<div class="cf7sh_data">';
                            echo '<input id="cb-select-'.$k.'" class="check-item" type="checkbox" name="del_id[]" value="'.$k.'" />';
                            echo '<div class="cf7sh_data_fields">';                            
                            foreach ($fields as $k2 => $v2) {                                
                                $_value = ((isset($v[$k2])) ? $v[$k2] : '&nbsp;');
                                $col_class = apply_filters('cf7sh_ad_tbl_col_class', array(), $k2, $_value, $fid);
                                if (!filter_var($_value, FILTER_VALIDATE_URL) === false) {
                                    $_value = sprintf('<a href="%1$s" target="_blank">%2$s</a>', $_value, $_value);
                                }
                                echo '<div class="cf7sh_field '.implode(' ', $col_class).'" >';
                                echo '<div class="cf7sh_field_item cf7sh_field_title">'.$common->cf7sh_admin_get_field_name($v2).'</div>';
                                echo '<div class="cf7sh_field_item cf7sh_field_value">'.htmlspecialchars(stripslashes($_value)).'</div>';                                                                
                                echo '</div>';                                
                            }
                            echo '</div>';
                            $row_id = $k;
                            do_action('cf7sh_admin_after_body_field', $fid, $row_id, $v);
                            echo '</div>';
                            
                            if($i%2==1){
                                echo '</div>';
                            }
                            $i++;
                        }
                        if(count($data_sorted)%2==1){
                            echo '<div class="cf7sh_data"></div>';
                            echo '</div>';
                        }
                        ?>
                        <div class="clear"></div>
                </div>         
            </form>
            <?php do_action('cf7sh_after_admin_form', $fid); ?>        
            <?php
        } else {
            if ($first_form_id > 0 && !(isset($_GET['action'])) && !(isset($_GET['action2']))) {
                ?>
                <script>
                location.replace('<?php echo admin_url("admin.php?page=".OKD_CF7SH_MENU_SH."&fid=" . $first_form_id); ?>');
                </script>
                <?php
            }
        }
        echo '</div>';
?>
      
<script>        
    jQuery(function($) {
    	$('.datepicker').datepicker({"dateFormat":"yy-mm-dd"});                      
    });
</script>
