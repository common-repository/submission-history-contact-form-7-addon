<?php
namespace OKD_CF7SH\Functions;
class Tools{

   // var $dal;
    //构造函数
    function __construct() {
        //$par1, $par2
        //         $this->url = $par1;
        //         $this->title = $par2;
        //$this->dal= new DataDAL();
    }
    
    //析构函数
    function __destruct() {
        //   print "销毁 " . $this->name . "\n";
    }
    
//     function Delete($dataid){
//         try{
//             $this->dal->Delete($dataid);
//         }
//         catch(Exception $e)
//         {
//             echo $e->getMessage();
//         }
//     }
    
    //加减日期
    public static function AddDay($dateStr,$day){
        $dateArray= getdate(strtotime($dateStr." ".$day." day"));
        $date= date_create($dateArray["year"]."-".$dateArray["mon"]."-".$dateArray["mday"]);        
        return date_format($date,"Y-m-d");
    }
    
    //格式化日期
    public static function FormatDate($dateStr,$format){
        $dateArray= getdate(strtotime($dateStr));
        $date= date_create($dateArray["year"]."-".$dateArray["mon"]."-".$dateArray["mday"]);
        return date_format($date,$format);
    }
    
    /**
     * 获取包含协议头的域名
     *
     * @since 1.0.0
     
     * @return example: http://www.onekeydone.com:8080.
     */
    public static function GetDomain(){
        $http_type=self::GetHttpType();
        $http_domain=(string)$_SERVER['HTTP_HOST'];
        $http_host=$http_type.$http_domain;
        return $http_host;
    }
    
    /**
     * 获取协议头
     *
     * @since 1.0.0

     * @return http:// or https://
     */
    public static function GetHttpType()
    {
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type;
    }
}

?>