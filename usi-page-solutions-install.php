<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

require_once('usi-page-solutions-settings.php');

final class USI_Page_Solutions_Install {

   const VERSION = '1.1.0 (2018-01-13)';

   private function __construct() {
   } // __construct();

   static function init() {
      $file = str_replace('-install', '', __FILE__);
      register_activation_hook($file, array(__CLASS__, 'hook_activation'));
      register_deactivation_hook($file, array(__CLASS__, 'hook_deactivation'));
   } // init();

   static function hook_activation() {

      global $wpdb;

      if (!current_user_can('activate_plugins')) return;

      check_admin_referer('activate-plugin_' . (isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : ''));

      $role = get_role('administrator');
      foreach (USI_Page_Solutions::$capabilities as $capability => $description) {
         $role->add_cap(USI_Page_Solutions::NAME . '-' . $capability);
      }

   } // hook_activation();

   static function hook_deactivation() {

      if (!current_user_can('activate_plugins')) return;

      check_admin_referer('deactivate-plugin_' . (isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : ''));

      if (USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-cache']) {
         USI_Page_Solutions_Settings::index_file_restore();
         USI_Page_Solutions_Settings::cache_file_generate();
      }

   } // hook_deactivation();

} // Class USI_Page_Solutions_Install;

USI_Page_Solutions_Install::init();

// --------------------------------------------------------------------------------------------------------------------------- // ?>
