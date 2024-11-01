<?php
namespace OKD_CF7SH\DAL;

defined('ABSPATH') || exit;


class AnalyticsDAL {
    private static $analytics_table = 'cf7_okd_analytics';    
    
    //Advanced Methods
    public function GetByDate($date,$cf7_id)
    {
        global $wpdb;
        $query = $wpdb->prepare('SELECT * FROM %1$s WHERE `report_date` BETWEEN "%2$s 00:00:00" AND "%2$s 23:59:59" AND `cf7_id` = "%3$d"', self::getTable(self::$analytics_table), $date,$cf7_id);
        return $wpdb->get_row($query);
    }
    
    public function GetsByDate($cf7_id,$startDate,$endDate)
    {
        global $wpdb;
        $query = $wpdb->prepare('SELECT *,ifnull(CONVERT(CONVERT(submission_count,decimal(18, 4)) * 100 / CONVERT(view_count,decimal(18, 4)),decimal(18, 2)),0.00) conversion_rate FROM %1$s WHERE `report_date` BETWEEN "%2$s 00:00:00" AND "%3$s 23:59:59" AND `cf7_id` = "%4$d"', self::getTable(self::$analytics_table),$startDate,$endDate,$cf7_id);
        return $wpdb->get_results($query);
    }
    
    //Basic Methods    
    public function Add($model)
    {
        global $wpdb;
        $wpdb->insert(self::getTable(self::$analytics_table), array(
            'cf7_id' => (int) $model->cf7_id,
            'submission_count' => (int) $model->submission_count,
            'view_count' => (int) $model->view_count,
            'report_date' => (string) $model->report_date
        ), array(
            '%d',
            '%d',
            '%d',
            '%s'                
        ));
    }
    
    public function Update($model)
    {
         global $wpdb;
         return   $wpdb->update(
             self::getTable(self::$analytics_table),
            array(
                'cf7_id' => (int) $model->cf7_id,
                'submission_count' => (int) $model->submission_count,
                'view_count' => (int) $model->view_count,
                'report_date' => (string) $model->report_date
            ),
             array('id' => $model->id),
            array(
                '%d',
                '%d',
                '%d',
                '%s' 
                
            ),
            array('%d')
            );        
    }

    public function Delete($id)
    {
            global $wpdb;
            return  $wpdb->delete(self::getTable(self::$analytics_table), array(
                'id' => (int) $id
            ), array(
                '%d'
            ));
    }

    public function Get($id)
    {
        global $wpdb;
        $query = $wpdb->prepare('SELECT * FROM %1$s WHERE `id` = "%2$d"', self::getTable(self::$analytics_table), $id);
        return $wpdb->get_row($query);
    }       
    
    public function Gets($cf7_id)
    {
        global $wpdb;        
        $query = $wpdb->prepare('SELECT * FROM %1$s WHERE `cf7_id` = %2$d', self::getTable(self::$analytics_table), $cf7_id);
        return $wpdb->get_results($query);
    }        
    
    // 获取表名
    private static function getTable($table)
    {
        global $wpdb;
        return $wpdb->prefix . $table;
    }
}