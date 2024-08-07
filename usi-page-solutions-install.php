<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

final class USI_Page_Solutions_Install {

   const VERSION = '1.7.0 (2022-08-09)';

   private function __construct() {
   } // __construct();

   static function init() {
      $file = str_replace('-install', '', __FILE__);
      register_activation_hook($file, [__CLASS__, 'hook_activation']);
      register_deactivation_hook($file, [__CLASS__, 'hook_deactivation']);
   } // init();

   static function hook_activation() {

      if (!current_user_can('activate_plugins')) return;

      check_admin_referer('activate-plugin_' . (isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : ''));

      $role = get_role('administrator');
      foreach (USI_Page_Solutions::$capabilities as $capability => $description) {
         $role->add_cap(USI_WordPress_Solutions_Capabilities::capability_slug(USI_Page_Solutions::PREFIX, $capability));
      }

   } // hook_activation();

   static function hook_deactivation() {

      if (!current_user_can('activate_plugins')) return;

      check_admin_referer('deactivate-plugin_' . (isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : ''));

      if (USI_Page_Solutions::$options['preferences']['enable-cache']) {
         USI_Page_Solutions_Settings::index_file_restore();
         USI_Page_Solutions_Settings::cache_file_generate();
      }

   } // hook_deactivation();

} // Class USI_Page_Solutions_Install;

// --------------------------------------------------------------------------------------------------------------------------- // ?>
