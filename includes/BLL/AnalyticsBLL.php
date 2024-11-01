<?php
namespace OKD_CF7SH\BLL;

defined('ABSPATH') || exit();

use OKD_CF7SH\DAL\AnalyticsDAL as AnalyticsDAL;
use OKD_CF7SH\Modal\AnalyticsInfo as AnalyticsInfo;

class AnalyticsBLL
{
    protected static $instance = null;
    private $dal;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->dal= new AnalyticsDAL();
    }
    
    //Advanced Methods
    
    public function GetByDate($date,$cf7_id){
        return  $this->dal->GetByDate($date,$cf7_id);
    }
    
    public function GetsByDate($cf7_id,$startDate,$endDate){
        return  $this->dal->GetsByDate($cf7_id,$startDate,$endDate);
    }
         
    //Basic Methods
    
    public function Add($model){
      return  $this->dal->Add($model);
    }
    
    public function Update($model){
        return  $this->dal->Update($model);
    }
    
    public function Delete($id){
        return $this->dal->Delete($id);        
    }
    
    public function Get($id){
        return $this->dal->Get($id);
    }
    
    public function Gets($cf7_id){
        return $this->dal->Gets($cf7_id);
    }   
}