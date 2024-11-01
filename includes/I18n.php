<?php
namespace OKD_CF7SH;

defined('ABSPATH') || exit;
/**
 * I18n Logic
 */
class I18n {
  protected static $instance = null;

  public static function getInstance() {
    if (null == self::$instance) {
      self::$instance = new self;
			self::$instance->doHooks();
    }

    return self::$instance;
  }

  private function __construct() {
  }

  private function doHooks(){
    add_action('plugins_loaded', array($this, 'loadPluginTextdomain'));
  }

  public static function loadPluginTextdomain() {
    if (function_exists('determine_locale')) {
      $locale = determine_locale();
    } else {
      $locale = is_admin() ? get_user_locale() : get_locale();
    }
   // $locale=substr($locale,0,2);
    
    unload_textdomain('okd_cf7sh');
    load_textdomain('okd_cf7sh', OKD_CF7SH_PATH . '/i18n/languages/' . $locale . '.mo');
    load_plugin_textdomain('okd_cf7sh', false, OKD_CF7SH_PATH . '/i18n/languages/');
  }

}
