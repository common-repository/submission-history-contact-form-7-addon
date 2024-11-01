<?php
namespace OKD_CF7SH\Functions;

use WPCF7_Submission;
use OKD_CF7SH\BLL\AnalyticsBLL as AnalyticsBLL;
use OKD_CF7SH\Modal\AnalyticsInfo as AnalyticsInfo;

defined('ABSPATH') || exit();

/**
 * Common functions
 */
class Common
{
    
    protected static $instance = null;
    
    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    private $pageId = null;
    
    private function __construct()
    {}
    
    public function Add_analytics_data($addtype,$formId){
        $submission_count=0;
        $view_count=0;
        if($addtype=="submit")
        {
            $submission_count=1;
        }else{
            $view_count=1;
        }
        //current datetime
        $time = date('Y-m-d H:i:s');
        $date=date('Y-m-d');
        
        //is exist or not
        $ainfo=AnalyticsBLL::getInstance()->GetByDate($date,$formId);
        if(empty($ainfo)){
            $ainfo=new AnalyticsInfo();
            $ainfo->cf7_id=$formId;
            $ainfo->report_date=$time;
            $ainfo->submission_count=$submission_count;
            $ainfo->view_count=$view_count;
            AnalyticsBLL::getInstance()->Add($ainfo);
        }else{           
            if($addtype=="submit")
            {
                $ainfo->submission_count+=1;
            }else{
                $ainfo->view_count+=1;
            }
            AnalyticsBLL::getInstance()->Update($ainfo);
        }
    }
    
    public function cf7sh_form_action_callback()
    {
        global $wpdb;
        if ($current_action = $this->cf7sh_current_action()) {
            if ($current_action == 'delete') {
                if (isset($_GET['del_id'])) {
                    $fid = ((isset($_GET['fid'])) ? (int)sanitize_text_field($_GET['fid']) : '');
                    if (apply_filters('cf7sh-alllowed-to-delete', true)) {
                        $nonce = $this->cf7sh_sanitize_arr($_REQUEST['_wpnonce']);
                        if (!wp_verify_nonce($nonce, 'cf7sh-nonce')) {
                            die('Security check');
                        }
                        
                        $del_id = implode(',', array_map('intval', $_GET['del_id']));
                        $del_id=sanitize_text_field($del_id);
                        $wpdb->query("DELETE FROM {$wpdb->prefix}cf7sh_okd_submission_history_entry WHERE data_id IN($del_id)");
                        $wpdb->query("DELETE FROM {$wpdb->prefix}cf7sh_okd_submission_history WHERE id IN($del_id)");
                    }
                    wp_safe_redirect(admin_url('admin.php?page='.OKD_CF7SH_MENU_SH.'&fid=' . $fid));
                    exit();
                }
            }
            do_action('cf7sh_entry_action', $current_action);
        }
        do_action('cf7sh_main_post');
    }
    
    public function cf7sh_current_action()
    {
        $current_action = false;
        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'] && isset($_GET['btn_apply'])) {
            $current_action = $this->cf7sh_sanitize_arr($_REQUEST['action']);
            return apply_filters('cf7sh_get_current_action', $current_action);
        }
        
        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'] && isset($_GET['btn_apply2'])) {
            $current_action = $this->cf7sh_sanitize_arr($_REQUEST['action2']);
            return apply_filters('cf7sh_get_current_action', $current_action);
        }
        $current_action = apply_filters('cf7sh_get_current_action', $current_action);
        return false;
    }
    
    //submission history page get rows
    public function cf7sh_get_rows($fid, $per_page = 10)
    {
        global $wpdb;
        
        $cf7sh_entry_order_by = apply_filters('cf7sh_entry_order_by', '`data_id` DESC');
        $cf7sh_entry_order_by = trim($cf7sh_entry_order_by);
        
        $items_per_page = apply_filters('cf7sh_entry_per_page', $per_page);
        $page = isset($_GET['cpage']) ? abs((int)sanitize_text_field($_GET['cpage'])) : 1;
        
        $search = ((isset($_GET['search'])) ? addslashes(sanitize_text_field($_GET['search'])) : '');
                       
        $custom_where = apply_filters('cf7sh_get_entries_custom_where', array());
        $custom_where = implode(' ', $custom_where);
        
        $limit_query = '';
        if (is_numeric($per_page)) {
            $offset = ($page * $items_per_page) - $items_per_page;
            $limit_query = "LIMIT $offset, $items_per_page";
        }
        
        $query = sprintf("SELECT * FROM `".$wpdb->prefix."cf7sh_okd_submission_history_entry` WHERE `cf7_id` = %d AND data_id IN(SELECT * FROM (SELECT data_id FROM `".$wpdb->prefix."cf7sh_okd_submission_history_entry` WHERE 1 = 1 AND `cf7_id` = ".$fid." ".((!empty($search)) ? "AND `value` LIKE '%%".$search."%%'" : "")." ".$custom_where." GROUP BY `data_id` ORDER BY ".$cf7sh_entry_order_by." %s) temp_table) ORDER BY " . $cf7sh_entry_order_by, $fid, $limit_query);
        $data = $wpdb->get_results($query);
        $data_sorted = apply_filters('cf7sh_before_printing_data', $this->cf7sh_sortdata($data), $fid);
        $fields = $this->cf7sh_get_db_fields($fid);
        
        $sql="SELECT * FROM `".$wpdb->prefix."cf7sh_okd_submission_history_entry` WHERE `cf7_id` = " . (int)$fid . " ".((!empty($search)) ? "AND `value` LIKE '%%".$search."%%'" : "")." ".$custom_where." GROUP BY `data_id`";
        $total = $wpdb->get_results($sql);
        $total = count($total);
        
        return array(
            'fields' => $fields,
            'data_sorted' => $data_sorted,
            'total' => $total,
            'items_per_page' => $items_per_page,
            'page' => $page,
        );
    }
    
    public function FilterStartDateAndEndDate($data){
        //search between startdate and enddate
        $newArray=array();
        $startdate = ((isset($_GET['startdate'])) ? addslashes(sanitize_text_field($_GET['startdate'])) : '');
        $enddate = ((isset($_GET['enddate'])) ? addslashes(sanitize_text_field($_GET['enddate'])) : '');
        $searchKey = ((isset($_GET['search'])) ? addslashes(sanitize_text_field($_GET['search'])) : '');
        foreach($data as $k => $v) {
            if($this->IsBetwweenDates($startdate,$enddate,$data[$k]['submit_time']))
            {
                if($this->SearchFields($v,$searchKey)){
                    array_push($newArray,$v);
                }                
            }
        }
        return $newArray;
    }
    
    //is the row field contain search key
    private function SearchFields($rowArray,$searchKey){
        if($searchKey==''){
            return true;
        }
        foreach($rowArray as $colIndex => $fieldValue) {
            $searchIndex=strpos($fieldValue,$searchKey);
            //return false  !is_bool is contain the key
            if(!is_bool($searchIndex)){
                return true;
            }
        }        
        return false;
    }
    
    //is in the date range
    private function IsBetwweenDates($startDateStr,$endDateStr,$targetDateStr){
        $targetDate=strtotime($targetDateStr);
        if($startDateStr!=''&&$endDateStr!=''){
            $startDate=strtotime($startDateStr);
            $endDate=strtotime($endDateStr);
            if($targetDate>$startDate&&$targetDate<=$endDate)
            {
                return true;
            }else{
                return false;
            }
        }else if($startDateStr!=''&&$endDateStr==''){
            $startDate=strtotime($startDateStr);
            if($targetDate>$startDate)
            {
                return true;
            }else{
                return false;
            }
        }else if($startDateStr==''&&$endDateStr!=''){
            $endDate=strtotime($endDateStr);
            if($targetDate<=$endDate)
            {
                return true;
            }else{
                return false;
            }
        }else{
            return true;
        }
        
    }
   
    /*
     * Support functions
     */
    
        
    public function cf7sh_get_posted_data($cf7)
        {
            if (!isset($cf7->posted_data) && class_exists('WPCF7_Submission')) {
                // Contact Form 7 version 3.9 removed $cf7->posted_data and now
                // we have to retrieve it from an API
                $submission = WPCF7_Submission::get_instance();
                if ($submission) {
                    $data = array();
                    $data['title'] = $cf7->title();
                    $data['posted_data'] = $submission->get_posted_data();
                    $data['uploaded_files'] = $submission->uploaded_files();
                    $data['WPCF7_ContactForm'] = $cf7;
                    $cf7 = (object) $data;
                }
            }
            return $cf7;
        }

        //This method is used to filter the data to be saved, and specify which fields do not need to be saved.
        public  function cf7sh_no_save_fields()
        {
            $cf7sh_no_save_fields = array('_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_is_ajax_call');
            return apply_filters('cf7sh_no_save_fields', $cf7sh_no_save_fields);
        }
    

        public  function cf7sh_add_more_fields($cf7)
        {
            $submission = WPCF7_Submission::get_instance();
            
            $uploaded_files = $submission->uploaded_files();            
            foreach ( (array) $uploaded_files as $name => $paths ) {               
                $attachments = $paths;                
            }
            
            //time
            //$cf7->posted_data['submit_time'] = date_i18n('Y-m-d H:i:s');
          //  $cf7->posted_data['submit_time'] = date_i18n('Y-m-d H:i:s', $submission->get_meta('timestamp'));
          
            $datetime = date_create( '@' . $submission->get_meta('timestamp'));
            $datetime->setTimezone( wp_timezone() );            
            $cf7->posted_data['submit_time'] =$datetime->format( 'Y-m-d H:i:s' );
            
            //ip
            $cf7->posted_data['submit_ip'] = (isset($_SERVER['X_FORWARDED_FOR'])) ? $_SERVER['X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            //user id
            // $cf7->posted_data['submit_user_id'] = 0;
            // if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            //     $current_user = wp_get_current_user(); // WP_User
            //     $cf7->posted_data['submit_user_id'] = $current_user->ID;
            // }
            return $cf7;
        }
    

        public  function cf7sh_arr_to_option($arr)
        {
            $html = '';
            foreach ($arr as $k => $v) {
                $html .= '<option value="'.$k.'">'.$v.'</option>';
            }
            return $html;
        }
    
    /*
     * $data: rows from database
     * $fid: form id
     */

        public  function cf7sh_sortdata($data)
        {
            $data_sorted = array();
            foreach ($data as $k => $v) {
                if (!isset($data_sorted[$v->data_id])) {
                    $data_sorted[$v->data_id] = array();
                }
                $data_sorted[$v->data_id][$v->name] = apply_filters('cf7sh_entry_value', $v->value, $v->name);
                $data_sorted[$v->data_id]["data_id"] = apply_filters('cf7sh_entry_value', $v->data_id, "data_id");
            }
            
            return $data_sorted;
        }
    

        public function cf7sh_get_db_fields($fid, $filter = true)
        {
            global $wpdb;
            $sql = sprintf("SELECT `name` FROM `".$wpdb->prefix."cf7sh_okd_submission_history_entry` WHERE cf7_id = %d GROUP BY `name`", $fid);
            $data = $wpdb->get_results($sql);
            
            $fields = array();
            foreach ($data as $k => $v) {
                $fields[$v->name] = $v->name;
            }
            if ($filter) {
                $fields = apply_filters('cf7sh_admin_fields', $fields, $fid);
            }
            return $fields;
        }
    

        public  function cf7sh_get_entrys($fid, $entry_ids = '', $cf7sh_entry_order_by = '')
        {
            global $wpdb;
            if (empty($cf7sh_entry_order_by)) {
                $cf7sh_entry_order_by = '`data_id` DESC';
            }
            $query = sprintf("SELECT * FROM `".$wpdb->prefix."cf7sh_okd_submission_history_entry` WHERE `cf7_id` = %d AND data_id IN(SELECT * FROM (SELECT data_id FROM `".$wpdb->prefix."cf7sh_okd_submission_history_entry` WHERE 1 = 1 ".((!empty($entry_ids)) ? "AND `data_id` IN (".$entry_ids.")" : "")." GROUP BY `data_id` ORDER BY " . $cf7sh_entry_order_by . ") temp_table) ORDER BY " . $cf7sh_entry_order_by, $fid);
            $data = $wpdb->get_results($query);
            return $data;
        }
    

        public  function cf7sh_upload_folder()
        {
            return apply_filters('cf7sh_upload_folder', 'cf7-submission-history');
        }
    

        public  function cf7sh_admin_get_field_name($field)
        {
            return $field;
        }
    

        public  function cf7sh_sanitize_arr($arr)
        {
            return is_array($arr) ? array_map('cf7sh_sanitize_arr', $arr) : sanitize_text_field($arr);
        }
        
        public  function cf7sh_export_to_csv($fid, $ids_export = '')
        {            
            $delimiter = apply_filters('cf7_db_export_delimiter', ',');
            $fields = $this->cf7sh_get_db_fields($fid);
            
            $data = $this->cf7sh_get_entrys($fid, $ids_export, 'data_id desc');
            $data_sorted = $this->cf7sh_sortdata($data);
            $data_sorted =$this->FilterStartDateAndEndDate($data_sorted);
            $file_name = 'cf7-submission-history';
            $forms = get_posts(array(
                'post_status' => 'any',
                'posts_per_page' => -1,
                'offset' => 0,
                'orderby' => apply_filters('cf7-db-forms-orderby', 'ID'),
                'order' => apply_filters('cf7-db-forms-order', 'ASC'),
                'post_type' => 'wpcf7_contact_form'
            ));
            foreach ($forms as $k => $form) {
                if ($form->ID == (int)$fid) {
                    $file_name = $file_name . '-'  . $form->post_title;
                    break;
                }
            }

            $file_name = sanitize_title($file_name);
            
            header("Content-type: text/x-csv");
            header("Content-Disposition: attachment; filename=".$file_name.".csv");
            $fp = fopen('php://output', 'w');
            fputs($fp, "\xEF\xBB\xBF");
            fputcsv($fp, array_values($fields), $delimiter);
            foreach ($data_sorted as $k => $v) {
                $temp_value = array();
                foreach ($fields as $k2 => $v2) {
                    $temp_value[] = ((isset($v[$k2])) ? stripcslashes($v[$k2]) : '');
                }
                fputcsv($fp, $temp_value, $delimiter);
            }
            
            fclose($fp);
            exit();
        }
    
}
