<?php // --- file generated from usi-page-cache-template.php -------------------------------------------------------------------- //

/*
Page-Solutions is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
License as published by the Free Software Foundation, either version 3 of the License, or any later version.
 
Page-Solutions is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License along with Page-Solutions. If not, see 
https://github.com/jaschwanda/Page-solutions/blob/master/LICENSE.md

Copyright (c) 2020 by Jim Schwanda.
*/
/* USI-PAGE-SOLUTIONS-1 external-config-location or null; */
require_once('usi-page-dbs-mysqli.php');

final class USI_Page_Cache {

   const VERSION = '1.6.0 (2021-06-12)';

   const DATE_ALPHA = '0000-00-00 00:00:00';
   const DATE_OMEGA = '9999-12-31 23:59:59';
   const DATE_WRITE = '/* USI-PAGE-SOLUTIONS-2 */';

   const DEBUG_DEFAULTS   = 0x01;
   const DEBUG_META_DATA  = 0x02;
   const DEBUG_SQL        = 0x04;

   const POST_META = '_usi-page-solutions';
   const TEST_DATA = 'usi-page-solutions=database-fail';

   private static $capture = false;
   private static $current_time = null;
   private static $dbs = null;
   private static $debug = 0;
   private static $info = array('valid_until' => self::DATE_OMEGA);
   private static $mail = array();
   private static $meta_id = 0;
   private static $meta_value = null;
   private static $post_id = 0;
   private static $query_string = null;
   private static $times = false;
   private static $valid_until = self::DATE_OMEGA;

   private function __construct() {
   } // __construct();

   public static function cache($times = false, $ip = null, $debug = self::DEBUG_DEFAULTS) { 

      self::$times = $times;

      if ($ip == $_SERVER['REMOTE_ADDR']) self::$debug = $debug;

      self::$current_time = date('Y-m-d H:i:s');

      try {

         foreach ($_COOKIE as $key => $value) if (substr($key, 0, 20) == 'wordpress_logged_in_') throw new USI_Page_Exception(__METHOD__.':status:wordpress');

         $path = $_SERVER['REQUEST_URI'];
         if (self::$debug) usi::log('$path=', $path);
         if (($offset = strpos($path, '/?')) !== false) $path = substr($path, 0, $offset);
         $path = rtrim($path, '/') . '/';

         $path_args    = self::POST_META . '*' . $path;
         $path_no_args = self::POST_META . '!' . $path;

         self::dbs_connect();

         $query = self::$dbs->prepare_x(
            'SELECT `meta_id`, `post_id`, `meta_key`, `meta_value` FROM `/* USI-PAGE-SOLUTIONS-7 */postmeta` ' .
            "WHERE (`meta_key` <> '" . self::POST_META . "') AND ((`meta_key` = ?) OR (? LIKE CONCAT(`meta_key`, '%'))) " .
            'ORDER BY `meta_key` DESC LIMIT 1', // SQL;
            array('ss', & $path_no_args, & $path_args), // Input parameters;
            array(& self::$meta_id, & self::$post_id, & $meta_key, & $meta_value) // Output variables;
         );
         if (self::DEBUG_SQL & self::$debug) usi::log('$num_rows=', $query->num_rows, ' get_status()=', $query->get_status());
         if (!($query->num_rows && $query->fetch())) throw new USI_Page_Exception(__METHOD__.":status:not_in_cache:$path");
         $query = null; // Close query;

         self::$query_string = trim(substr($path, strlen(substr($meta_key, 20))), '/');
         if ($length = strlen(self::$query_string)) {
            if (self::$debug) usi::log('$query_string=', self::$query_string);
            $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 0, -$length);
         }

         self::$meta_value = unserialize(base64_decode($meta_value));

         if (self::DEBUG_META_DATA & self::$debug) usi::log('$meta_value:', self::$meta_value);
         switch ($mode = isset(self::$meta_value['cache']['mode']) ? self::$meta_value['cache']['mode'] : 'disabled') {
         default:        throw new USI_Page_Exception(__METHOD__.":status:bad cache mode ($mode)");
         case 'disable': throw new USI_Page_Exception(__METHOD__.':status:cache disabled');
         case 'manual': case 'period': case 'schedule': break;
         }

         if (empty(self::$meta_value['cache']['html']) || (self::$current_time > self::$meta_value['cache']['valid_until'])) {
            self::$capture = true;
            ob_start(
               array(__CLASS__, 'capture') // Output callback, calls USI_PageCache::capture() with output when output is flushed;
            );
            throw new USI_Page_Exception(__METHOD__.':status:capture');
         } 

         if (self::$debug) usi::log('status:fetched');

         $output = self::times();

         if (empty(self::$meta_value['cache']['dynamics'])) {
            $output .= self::$meta_value['cache']['html'];
         } else {
            $dynamics = self::$meta_value['cache']['dynamics'];
            for ($ith = $offset = 0; $ith < count($dynamics); $ith++) {
               $output .= substr(self::$meta_value['cache']['html'], $offset, $dynamics[$ith]['begin'] - $offset);
               $offset = $dynamics[$ith]['end'];
               @ include_once($dynamics[$ith]['file']);
               if (is_callable(array($dynamics[$ith]['class'], 'widget'))) {
                  $class = new $dynamics[$ith]['class']();
                  ob_start();
                  $class->widget($dynamics[$ith]['args'], $dynamics[$ith]['instance']);
                  $output .= ob_get_contents();
                  ob_end_clean();
               }
            }
            $output .= substr(self::$meta_value['cache']['html'], $offset);
         }
         header('connection:close');
         header('content-length:' . strlen($output));
         echo $output;
         ob_end_flush();
         flush();
         die();

      } catch(USI_Page_Dbs_Exception $e) {

         if (self::$debug) {
            usi::log('status:dbs:exception=', $e->GetMessage(), '\ntrace=', $e->GetTraceAsString());
            if (self::TEST_DATA == $_SERVER['QUERY_STRING']) die(self::TEST_DATA);
         }

      } catch(USI_Page_Exception $e) {

         if (self::$debug && !self::$capture) usi::log('status:page:exception=', $e->GetMessage());

      }

   } // cache();

   private static function capture($buffer) {
      unset(self::$meta_value['cache']['html']);
      $cache = self::validate(self::$meta_value['cache']);
      $info['valid_until'] = self::$valid_until;
            
      if ('period' == $cache['mode']) {
         $date = strtotime(substr(self::$current_time, 0, 10));
         $time = strtotime(self::$current_time);
         self::$valid_until = date('Y-m-d H:i:s', $date + $cache['period'] * ((int)(($time - $date) / $cache['period']) + 1));
      } else if ('schedule' == $cache['mode']) {
         $current_time = substr(self::$current_time, 11);
         $schedule = $cache['schedule'];
         $target = null;
         foreach ($schedule as $time) {
            if ($time > $current_time) {
               $target = $time;
               break;
            }
         }
         $today = substr(self::$current_time, 0, 10);
         if ($target) {
            self::$valid_until = $today . ' ' . $target;
         } else {
            $date = strtotime($today) + 86400;
            self::$valid_until = date('Y-m-d ', $date) . $schedule[0];
         }
      }

      if (self::$meta_value['cache']['allow_clear'] && ($info['valid_until'] < self::$valid_until)) self::$valid_until = $info['valid_until'];

      self::$meta_value['cache']['dynamics'] = $info['dynamics'];
      self::$meta_value['cache']['html'] = $buffer;
      self::$meta_value['cache']['size'] = strlen($buffer);
      self::$meta_value['cache']['updated'] = self::$current_time;
      self::$meta_value['cache']['valid_until'] = self::$valid_until;

      $meta_value = base64_encode(serialize(self::$meta_value));
      $query = null;
      try {
         $query = self::$dbs->prepare_x(
            'UPDATE `/* USI-PAGE-SOLUTIONS-7 */postmeta` SET `meta_value` = ? WHERE (`meta_id` = ?)', // SQL;
            array('si', & $meta_value, & self::$meta_id) // Input parameters;
         );
         if (self::DEBUG_SQL & self::$debug) usi::log('get_status()=', $query->get_status());
      } catch(USI_Page_Dbs_Exception $e) {
         if (self::DEBUG_SQL & self::$debug) usi::log('get_status()=', $query->get_status());
         if (self::$debug) usi::log('status:page:exception=', $e->GetMessage(), '\ntrace=', $e->GetTraceAsString());
      }
      $query = null; // Close query;
      return(self::times() . $buffer);
   } // capture();

   public static function dbs_connect() {
      if (!self::$dbs) self::$dbs = new USI_Page_Dbs(array('hash' => /* USI-PAGE-SOLUTIONS-3 */, 'host' => /* USI-PAGE-SOLUTIONS-4 */, 'name' => /* USI-PAGE-SOLUTIONS-5 */, 'user' => /* USI-PAGE-SOLUTIONS-6 */));
      return(self::$dbs);
   } // dbs_connect();

   public static function que($info) {
      self::$info['dynamics'][] = $info;
   } // que();
 
   public static function query_string() { 
      return(self::$query_string); 
   } // query_string();

   private static function times() {
      return(self::$times ? '<!-- ' . self::$meta_value['cache']['updated'] . ' | ' . self::$current_time . ' | ' . self::$meta_value['cache']['valid_until'] . ' | ' . self::VERSION . ' -->' . PHP_EOL : null);
   } // times();

   public static function valid_until(string $time) {
      if (preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $time) && ($time > self::$current_time) && ($time < self::$valid_until)) self::$valid_until = $time;
   } // valid_until();

   public static function validate($cache) {
      $allow_clear = !empty($cache['allow-clear']);
      $clear_every_publish = !empty($cache['clear-every-publish']);
      $inherit_parent = !empty($cache['inherit-parent']);
      $dynamics = isset($cache['dynamics']) ? $cache['dynamics'] : array();
      switch ($mode = isset($cache['mode']) ? $cache['mode'] : 'disable') {
      default: $mode = 'disable'; case 'manual': case 'period': case 'schedule': break;
      }
      switch ($period = (int)(isset($cache['period']) ? $cache['period'] : 86400)) {
      default: $period = 86400;
      case 300:   case 600:   case 900:   case 1200:  case 1800:  case 3600:  case 7200:  
      case 10800: case 14400: case 21600: case 28800: case 43200: break;
      }
      $schedule = isset($cache['schedule']) ? $cache['schedule'] : array('00:00:00');
      $safe_schedule = array();
      foreach ($schedule as $time) {
         if (preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $time)) $safe_schedule[] = $time;
      }
      if (empty($safe_schedule)) { $safe_schedule = array('00:00:00'); } else { sort($safe_schedule); }
      $updated     = isset($cache['updated']) && preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $cache['updated']) ? $cache['updated'] : self::DATE_ALPHA;
      $valid_until = isset($cache['valid_until']) &&  preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $cache['valid_until']) ? $cache['valid_until'] : self::DATE_ALPHA;
      if (isset($cache['html'])) {
         $html     = $cache['html'];
      } else {
         $html     = '';
         $updated  = $valid_until = self::DATE_ALPHA;
      }
      $size = strlen($html);
      return(array('allow-clear' => $allow_clear, 'clear-every-publish' => $clear_every_publish, 'inherit-parent' => $inherit_parent, 
         'mode' => $mode, 'period' => $period, 'schedule' => $safe_schedule, 'size' => $size, 'updated' => $updated, 
         'valid_until' => $valid_until, 'dynamics' => $dynamics, 'html' => $html));
   } // validate();

} // Class USI_Page_Cache;

class USI_Page_Exception extends Exception { } // Class USI_Page_Exception;

if (!function_exists('is_admin')) {

require_once('/* USI-PAGE-SOLUTIONS-8 */');
/* USI-PAGE-SOLUTIONS-9 USI_Page_Cache::cache() or null; */
}

// --------------------------------------------------------------------------------------------------------------------------- // ?>