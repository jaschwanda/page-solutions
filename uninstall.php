<?php // ------------------------------------------------------------------------------------------------------------------------ //

require_once(plugin_dir_path(__DIR__) . 'usi-wordpress-solutions/usi-wordpress-solutions-uninstall.php');

require_once('usi-page-solutions.php');

final class USI_Page_Solutions_Uninstall {

   const VERSION = '1.4.0 (2019-11-27)';

   private function __construct() {
   } // __construct();

   static function uninstall() {

      global $wpdb;

      if (!defined('WP_UNINSTALL_PLUGIN')) exit;

      $results = $wpdb->get_results('SELECT option_name FROM ' . $wpdb->prefix . 
         'options WHERE (option_name LIKE "' . USI_Page_Solutions::PREFIX . '-solutions-layout-options%")');
      foreach ($results as $result) {
         delete_option($result->option_name);
      }

      $wpdb->query('DELETE FROM `' . $wpdb->prefix . "postmeta` WHERE (`meta_key` LIKE '" . USI_Page_Cache::POST_META . "%')");

   } // uninstall();

} // Class USI_Page_Solutions_Uninstall;

USI_WordPress_Solutions_Uninstall::uninstall(USI_Page_Solutions::PREFIX);

USI_Page_Solutions_Uninstall::uninstall();

// --------------------------------------------------------------------------------------------------------------------------- // ?>