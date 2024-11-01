<?php
/*
Plugin Name: Submission History - Contact Form 7 Addon 
Author: OneKeyDone
Author URI: http://www.onekeydone.com
Version:  1.0
Description: This plugin is an extension of the Contact Form 7 plugin, used to store, view, delete, search, and export data. Support Responsive. No matter how many fields you have, the interface won’t look messy.  
Plugin URI: http://onekeydone.com/contact-form-7-submission-history/
Text Domain: okd_cf7sh
Domain Path: /i18n/languages/
*/

namespace OKD_CF7SH;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('OKD_CF7SH_EDITION',"lite"); //lite pro enterprise
define('OKD_CF7SH_VERSION',"1.0");
define('OKD_CF7SH_PREFIX',"okd_cf7sh");
//menu slug
define('OKD_CF7SH_MENU_SH',OKD_CF7SH_PREFIX."_history");
define('OKD_CF7SH_MENU_AN',OKD_CF7SH_PREFIX."_analytics");

define('OKD_CF7SH_FILE', __FILE__);
define('OKD_CF7SH_PATH', plugin_dir_path( __FILE__ ));
define('OKD_CF7SH_PLUGIN_DIR', dirname(__FILE__));
define('OKD_CF7SH_PLUGIN_URL', plugins_url('', __FILE__));
define( 'OKD_CF7SH_REST_URL', 'cf7sh/v1' );

//auto load php file
spl_autoload_register(
    function ( $class ) {
        $prefix   = __NAMESPACE__;
        $base_dir = __DIR__ . '/includes';
        
        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }
        
        $relative_class_name = substr( $class, $len );
        $file = $base_dir . str_replace( '\\', '/', $relative_class_name ) . '.php';
        
        if ( file_exists( $file ) ) {
            //echo "include: ".$file."<br/>";
            require $file;
        }
    }
);

function init() {
    Plugin::getInstance();
    Plugin::activate();    
    Pages::getInstance();
    Hooks::getInstance();
    //API\API::getInstance();
    I18n::loadPluginTextdomain();   //translate    
}

add_action( 'plugins_loaded', 'OKD_CF7SH\\init' );

register_activation_hook( __FILE__, array( 'OKD_CF7SH\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'OKD_CF7SH\\Plugin', 'deactivate' ) );

