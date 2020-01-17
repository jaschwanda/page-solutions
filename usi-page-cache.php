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

require_once('usi-page-dbs-mysqli.php');

final class USI_Page_Cache {

   const VERSION = '1.5.0 (2020-01-12)';

   const DATE_ALPHA = '0000-00-00 00:00:00';
   const DATE_OMEGA = '9999-12-31 23:59:59';

   const POST_META = '_usi-page-solutions';
   const TEST_DATA = 'usi-page-solutions=database-fail';

   private function __construct() {
   } // __construct();

   public static function validate($cache) {
      return(array('allow-clear' => false, 'clear-every-publish' => false, 'inherit-parent' => false, 
         'mode' => 'disable', 'period' => 86400, 'schedule' => array('00:00:00'), 'size' => 0, 'updated' => self::DATE_ALPHA, 
         'valid_until' => self::DATE_ALPHA, 'dynamics' => false, 'html' => ''));
   } // validate();

} // Class USI_Page_Cache;

// --------------------------------------------------------------------------------------------------------------------------- // ?>