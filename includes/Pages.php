<?php
namespace OKD_CF7SH;
use OKD_CF7SH\Functions\Common as Common;
//use QFM\DLL\FolderDLL as FolderDLL;
defined('ABSPATH') || exit();

/**
 * Pages Page
 */
class Pages
{
    protected static $instance = null;
    private static $common;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
            self::$common= Common::getInstance();
            self::$instance->addPages();
        }

        return self::$instance;
    }

    private $pageId = null;

    private function __construct()
    {}

    private function addPages()
    {
        //add menu
        add_action('admin_menu',array($this,'create_menus_hook'));
        //add js, css
        add_action('admin_enqueue_scripts', array($this,'cf7sh_load_files_hook'),10,1);
    }
    
    function cf7sh_load_files_hook($hook_suffix)
    {        
        //can't load on other pages
        $is_analytics_page=strpos($hook_suffix,"page_okd_cf7sh_analytics");
        $is_history_page=strpos($hook_suffix,"page_okd_cf7sh_history");
        if($is_analytics_page||$is_history_page)
        {
            //popup box script
            add_thickbox();
                        
            wp_register_style(OKD_CF7SH_PREFIX.'_datepicker_css', OKD_CF7SH_PLUGIN_URL . '/assets/plugin/css/jquery-ui.css');
            wp_enqueue_style(OKD_CF7SH_PREFIX.'_datepicker_css');
            wp_enqueue_script('jquery-ui-datepicker');
            
            wp_register_script(OKD_CF7SH_PREFIX.'_js', OKD_CF7SH_PLUGIN_URL . '/assets/plugin/js/plugin.js', array('jquery'));
            wp_enqueue_script(OKD_CF7SH_PREFIX.'_js');
                    
            if($is_analytics_page)
            {
                wp_register_script('echarts', OKD_CF7SH_PLUGIN_URL . '/assets/plugin/js/echarts.min.js');
                wp_enqueue_script('echarts');
            }
            
            wp_register_style(OKD_CF7SH_PREFIX.'_css', OKD_CF7SH_PLUGIN_URL . '/assets/plugin/css/plugin.css');
            wp_enqueue_style(OKD_CF7SH_PREFIX.'_css');      
        }
    }

    public function create_menus_hook()
    {
        //add submission history page
        $menu= add_submenu_page("wpcf7", __('Submission History',"okd_cf7sh"), __('Submission History',"okd_cf7sh"), 'manage_options', OKD_CF7SH_MENU_SH,
            array(
            $this,
            'submission_history_page'
        ));
        
        //add analytics page
        add_submenu_page("wpcf7", __('Analytics',"okd_cf7sh"), __('Analytics',"okd_cf7sh"), 'manage_options', OKD_CF7SH_MENU_AN,
            array(
                $this,
                'analytics_page'
            ));
        
        add_action('load-' . $menu, array($this,'cf7sh_form_action_callback_hook'));
    }        

    public function cf7sh_form_action_callback_hook(){
        self::$common->cf7sh_form_action_callback();
    }

    public function submission_history_page()
    {
        $common = Common::getInstance();
        $viewPath = OKD_CF7SH_PLUGIN_DIR . '/views/pages/html-submission-history.php';
        include_once $viewPath;
    }
    
    public function analytics_page()
    {
        $common = Common::getInstance();
        $edition="";
        if(OKD_CF7SH_EDITION=="lite"){
            $edition="-lite";
        }
        $viewPath = OKD_CF7SH_PLUGIN_DIR . '/views/pages/html-analytics'.$edition.'.php';
        include_once $viewPath;
    }

}
