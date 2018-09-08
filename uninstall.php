<?php // ------------------------------------------------------------------------------------------------------------------------ //

require_once('usi-library/usi-debug-enable.php');
require_once('usi-library/usi-dbs-mysqli.php');
require_once('usi-settings/usi-settings-uninstall.php');
require_once('usi-page-solutions.php');

final class USI_Page_Solutions_Uninstall {

   const VERSION = '1.2.0 (2018-09-04)';

   private function __construct() {
   } // __construct();

   static function uninstall() {

      if (!defined('WP_UNINSTALL_PLUGIN')) exit;

      USI_Settings_Uninstall::uninstall(
         USI_Page_Solutions::NAME, 
         USI_Page_Solutions::PREFIX, 
         USI_Page_Solutions::$capabilities
      );

      try {

         global $wpdb;

         $dbs = new USI_Dbs(array('hash' => DB_PASSWORD, 'host' => DB_HOST, 'name' => DB_NAME, 'user' => DB_USER));

         $key = USI_Page_Cache::POST_META . '%';

         $query = $dbs->prepare_x(
            'DELETE FROM `' . $wpdb->prefix . 'postmeta` WHERE (`meta_key` LIKE ?)', // SQL;
            array('s', & $key), // Input parameters;
            null, // Output parameters;
            true, // Execute flag;
            false // Store results flag;
         );

      } catch(USI_Dbs_Exception $e) {

         USI_Debug::exception($e);

      }

      delete_metadata('user', null, $wpdb->prefix . USI_Page_Solutions::PREFIX . '-options-mru-page', null, true);
      delete_metadata('user', null, $wpdb->prefix . USI_Page_Solutions::PREFIX . '-options-mru-post', null, true);

   } // uninstall();

} // Class USI_Page_Solutions_Uninstall;

USI_Page_Solutions_Uninstall::uninstall();

// --------------------------------------------------------------------------------------------------------------------------- // ?>