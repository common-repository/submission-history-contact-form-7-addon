<?php
namespace OKD_CF7SH\API;

use OKD_CF7SH\Functions\Common;

defined('ABSPATH') || exit();

/**
 * Folder Controller
 */
class API
{
    protected static $instance = null;
    private static $common;

    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
            self::$common= Common::getInstance();
            self::$instance->addHooks();
        }
        return self::$instance;
    }

    public function __construct()
    {}

    private function addHooks()
    {
        add_action('rest_api_init', array(
            $this,
            'registerRestFields'
        ));
    }

    public function registerRestFields()
    {       
        // 获取根目录文件列表
        register_rest_route(OKD_CF7SH_REST_URL, 'getrows', array(
            'methods' => 'POST',
            'callback' => array(
                $this,
                'getRows'
            ),
            'permission_callback' => array(
                $this,
                'resPermissionsCheck'
            )
        ));
     
        // 删除文件
        register_rest_route(OKD_CF7SH_REST_URL, 'deletefiles', array(
            'methods' => 'POST',
            'callback' => array(
                $this,
                'deleteFiles'
            ),
            'permission_callback' => array(
                $this,
                'resPermissionsCheck'
            )
        ));
    }

    public function resPermissionsCheck()
    {
        return current_user_can('manage_options');
        // return true;
    }

    // 获取某个文件夹的文件列表
    public function getRows()
    {
        $formid = isset($_POST['formid']) ? (int) sanitize_text_field($_POST['formid']) : '';
        $searchkey = isset($_POST['searchkey']) ? (int) sanitize_text_field($_POST['searchkey']) : '';
        if ($formid != '') {
          $formlist=  self::$common->cf7sh_get_rows($formid,10);
          wp_send_json_success($formlist);
        }
        wp_send_json_error(array(
            'msg' => __('Fields is missing.', 'quickfilemanager')
        ));
    }

    // 删除文件
    public function deleteFiles()
    {
        $filelist = isset($_POST['filelist']) ? sanitize_text_field($_POST['filelist']) : '';
        if ($filelist != '') {
            //wp_send_json_success(FolderDLL::deleteFiles($filelist));
        }
        wp_send_json_error(array(
            'msg' => __('Required fields are missing.', 'quickfilemanager')
        ));
    }
}