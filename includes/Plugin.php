<?php
namespace OKD_CF7SH;
use OKD_CF7SH\Functions\Common as Common;

defined('ABSPATH') || exit;

/**
 * Plugin activate/deactivate logic
 */
class Plugin {
  protected static $instance = null;

  
  public static function getInstance() {
    if (null == self::$instance) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  private function __construct() {
  }

  /** Plugin activated hook */
  public static function activate() {      
      global $wpdb;
      $charset_collate = $wpdb->get_charset_collate();
      
      //history tables
      $cf7sh_table = $wpdb->prefix.'cf7sh_okd_submission_history';
      if ($wpdb->get_var("show tables like '$cf7sh_table'") != $cf7sh_table) {
          $sql = 'CREATE TABLE '.$cf7sh_table.' (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `created` timestamp NOT NULL,
            UNIQUE KEY id (id)
            ) '.$charset_collate.';';
          require_once ABSPATH.'wp-admin/includes/upgrade.php';
          dbDelta($sql);
      }
      
      $cf7sh_table_entry = $wpdb->prefix.'cf7sh_okd_submission_history_entry';
      if ($wpdb->get_var("show tables like '$cf7sh_table_entry'") != $cf7sh_table_entry) {
          $sql = 'CREATE TABLE '.$cf7sh_table_entry.' (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cf7_id` int(11) NOT NULL,
            `data_id` int(11) NOT NULL,
            `name` varchar(250),
            `value` text,
            UNIQUE KEY id (id)
            ) '.$charset_collate.';';
          require_once ABSPATH.'wp-admin/includes/upgrade.php';
          dbDelta($sql);
      } else {
          require_once ABSPATH.'wp-admin/includes/upgrade.php';
          
          maybe_convert_table_to_utf8mb4($cf7sh_table_entry);
          $sql = "ALTER TABLE ".$cf7sh_table_entry." change name name VARCHAR(250) character set utf8, change value value text character set utf8;";
          $wpdb->query($sql);
      }
      
      //analytics tables
      $cf7sh_table = $wpdb->prefix.'cf7_okd_analytics';
      if ($wpdb->get_var("show tables like '$cf7sh_table'") != $cf7sh_table) {
          $sql = 'CREATE TABLE '.$cf7sh_table.' (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cf7_id` int(11) NOT NULL,
            `submission_count` int(11) NOT NULL default 0,
            `view_count` int(11) NOT NULL default 0,
            `report_date` timestamp NOT NULL,
            UNIQUE KEY id (id)
            ) '.$charset_collate.';';
          require_once ABSPATH.'wp-admin/includes/upgrade.php';
          dbDelta($sql);
      }
      
      //update version
      $current_version = get_option(OKD_CF7SH_PREFIX.'_version');
      if ( version_compare(OKD_CF7SH_VERSION, $current_version, '>') ) {
          update_option(OKD_CF7SH_PREFIX.'_version', OKD_CF7SH_VERSION);
      }
  }

  /** Plugin deactivate hook */
  public static function deactivate() {
   
  }
  
}
