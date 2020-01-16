<?php // ------------------------------------------------------------------------------------------------------------------------ //

/*
Page-Solutions is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
License as published by the Free Software Foundation, either version 3 of the License, or any later version.
 
Page-Solutions is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License along with Page-Solutions. If not, see 
https://github.com/jaschwanda/Page-solutions/blob/master/LICENSE.md

Copyright (c) 2020 by Jim Schwanda.
*/

require_once(plugin_dir_path(__DIR__) . 'usi-wordpress-solutions/usi-wordpress-solutions-uninstall.php');

require_once('usi-page-solutions.php');

final class USI_Page_Solutions_Uninstall {

   const VERSION = '1.5.0 (2020-01-12)';

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