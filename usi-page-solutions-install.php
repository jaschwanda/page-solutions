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

defined('ABSPATH') or die('Accesss not allowed.');

require_once('usi-page-solutions-settings.php');

final class USI_Page_Solutions_Install {

   const VERSION = '1.5.2 (2020-02-06)';

   private function __construct() {
   } // __construct();

   static function init() {
      $file = str_replace('-install', '', __FILE__);
      register_activation_hook($file, array(__CLASS__, 'hook_activation'));
      register_deactivation_hook($file, array(__CLASS__, 'hook_deactivation'));
   } // init();

   static function hook_activation() {

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

      if (USI_Page_Solutions::$options['preferences']['enable-cache']) {
         USI_Page_Solutions_Settings::index_file_restore();
         USI_Page_Solutions_Settings::cache_file_generate();
      }

   } // hook_deactivation();

} // Class USI_Page_Solutions_Install;

USI_Page_Solutions_Install::init();

// --------------------------------------------------------------------------------------------------------------------------- // ?>
