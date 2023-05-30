<?php // --- file generated from usi-page-cache-template.php -------------------------------------------------------------------- //

defined('USI_WP_CONFIG') or die('Accesss not allowed.');

/*
Page-Solutions is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
License as published by the Free Software Foundation, either version 3 of the License, or any later version.
 
Page-Solutions is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License along with Page-Solutions. If not, see 
https://github.com/jaschwanda/page-solutions/blob/master/LICENSE.md

Copyright (c) 2020 by Jim Schwanda.
*/

require_once(USI_WP_CONFIG);
require_once('/* USI-PAGE-SOLUTIONS-8 */');

final class USI_Page_Cache {

   const VERSION = '1.7.0 (2022-08-09)';

   const DATE_ALPHA = '0000-00-00 00:00:00';
   const DATE_OMEGA = '9999-12-31 23:59:59';
   const DATE_WRITE = '/* USI-PAGE-SOLUTIONS-2 */';

   const POST_META = '_usi-page-solutions';

   public static $theme = 'default';

   private static $fetch = false;
   private static $current_time = null;
   private static $dbs = null;
   private static $debug = false;
   private static $info = [ 'valid_until' => self::DATE_OMEGA ];
   private static $meta_id = 0;
   private static $meta_value = null;
   private static $times = false;
   private static $valid_until = self::DATE_OMEGA;

   private function __construct() {
   } // __construct();

   public static function cache($times = false, $session = 'all') { 

      try {

         global $table_prefix;

         self::$times = $times;

         self::$current_time = date('Y-m-d H:i:s');

         // Set up debugging;
         if ($session && (('all' == $session) || (session_start() && (session_id() == $session)))) self::$debug = true;

         // ESCAPE if user logged into WordPress;
         foreach ($_COOKIE as $key => $value) if (substr($key, 0, 20) == 'wordpress_logged_in_') throw new USI_Page_Status('wordpress');

         // Get requested page's cache information from database;
         self::$dbs = usi::dbs_connect();

         $request_uri = $_SERVER['REQUEST_URI'];
         if (($offset = strpos($request_uri, '/?')) !== false) $request_uri = substr($request_uri, 0, $offset);
         $request_uri = rtrim($request_uri, '/');

         $request_uri_args    = self::POST_META . '*' . $request_uri . '/';
         $request_uri_no_args = self::POST_META . '!' . $request_uri . '/';

         $results = self::$dbs->query($sql = 
           'SELECT `meta_id`, `post_id`, `meta_key`, `meta_value` FROM `' . $table_prefix . 'postmeta` ' .
           "WHERE (`meta_key` <> '" . self::POST_META . "') AND ((`meta_key` = '" . $request_uri_no_args 
          . "') OR ('" . $request_uri_args . "' LIKE CONCAT(`meta_key`, '%'))) " .
           'ORDER BY `meta_key` DESC LIMIT 1'
         );
         $row = (1 == $results->num_rows) ? $results->fetch_assoc() : [];
         $results->close();
         if (self::$debug) usi::log('status=', (empty($row) ? 'failure' : 'success'), ' sql:', $sql, ' row=', $row);
         if (empty($row)) throw new USI_Page_Status('no-meta:' . $request_uri);

         // Check if cached html is valid;
         self::$meta_id    = $row['meta_id'];
         self::$meta_value = unserialize(base64_decode($row['meta_value']));
         self::$theme      = self::$meta_value['options']['theme'];
         if (self::$debug) usi::log('$meta_value:', self::$meta_value);

         switch ($mode = self::$meta_value['cache']['mode'] ?? 'disabled') {
         default:        throw new USI_Page_Status('invalid:' . $mode);
         case 'disable': throw new USI_Page_Status('disabled');
         case 'manual': case 'period': case 'schedule': break;
         }

         if (empty(self::$meta_value['cache']['html'])) {
            self::$fetch = true;
            throw new USI_Page_Status('empty');
         }
         if (self::$current_time > self::$meta_value['cache']['valid_until']) {
            self::$fetch = true;
            throw new USI_Page_Status('expired');
         }

         $output = self::times() . self::$meta_value['cache']['html'];

         $length = strlen($output);

         if (self::$debug) usi::log('cache=emit:' . $length . ' bytes');

         header('connection:close');
         header('content-length:' . $length);
         echo $output;
         ob_end_flush();
         flush();
         die();

      } catch(USI_Dbs_Exception $e) {

           if (self::$debug) echo '<!--' . PHP_EOL . $e->GetMessage() . PHP_EOL . $e->GetTraceAsString() . PHP_EOL . '-->' . PHP_EOL;

      } catch(USI_Page_Status $e) {

         if (self::$debug) usi::log('cache=', $e->GetMessage());

         if (self::$fetch) {
            ob_start(
               [__CLASS__, 'fetch'] // Output callback, calls USI_PageCache::fetch() with output when output is flushed;
            );
         }

      } catch(Exception $e) {
         usi::log($e);
      }

   } // cache();

   private static function fetch($buffer) {

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
      $query      = null;
      try {

//         $results = self::$dbs->query(
//            $sql  = 'UPDATE `/* USI-PAGE-SOLUTIONS-7 */postmeta` SET `meta_value` = "' . $meta_value . '" WHERE (`meta_id` = ' . self::$meta_id . ')'
//         );
usi::log('$results=', $results, '\2nsql=', $sql, '\2nself::$meta_value=', self::$meta_value);
//           'SELECT `meta_id`, `post_id`, `meta_key`, `meta_value` FROM `' . $table_prefix . 'postmeta` ' .
//           "WHERE (`meta_key` <> '" . self::POST_META . "') AND ((`meta_key` = '" . $request_uri_no_args 
//          . "') OR ('" . $request_uri_args . "' LIKE CONCAT(`meta_key`, '%'))) " .
//           'ORDER BY `meta_key` DESC LIMIT 1'
//         );
//         $row = (1 == $results->num_rows) ? $results->fetch_assoc() : [];
//         $results->close();
 //        if (self::$debug) usi::log('status=', (empty($row) ? 'failure' : 'success'), ' sql:', $sql, ' row=', $row);
 //        if (empty($row)) throw new USI_Page_Status('no-meta:' . $request_uri);


//         $query   = self::$dbs->prepare_x(
//            'UPDATE `/* USI-PAGE-SOLUTIONS-7 */postmeta` SET `meta_value` = ? WHERE (`meta_id` = ?)', // SQL;
//            array('si', & $meta_value, & self::$meta_id) // Input parameters;
//         );
//         if (self::$debug) usi::log($query->get_status());
      } catch(Exception $e) {
//         if (self::$debug) usi::log($query->get_status());
//         if (self::$debug) usi::log('status:page:exception=', $e->GetMessage(), '\ntrace=', $e->GetTraceAsString());
      }
//      $query = null; // Close query;
      return($times . $buffer);

   } // fetch();

//   public static function que($info) {
//      self::$info['dynamics'][] = $info;
//   } // que();

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
      $dynamics = isset($cache['dynamics']) ? $cache['dynamics'] : [];
      switch ($mode = isset($cache['mode']) ? $cache['mode'] : 'disable') {
      default: $mode = 'disable'; case 'manual': case 'period': case 'schedule': break;
      }
      switch ($period = (int)(isset($cache['period']) ? $cache['period'] : 86400)) {
      default: $period = 86400;
      case 300:   case 600:   case 900:   case 1200:  case 1800:  case 3600:  case 7200:  
      case 10800: case 14400: case 21600: case 28800: case 43200: break;
      }
      $schedule = isset($cache['schedule']) ? $cache['schedule'] : ['00:00:00'];
      $safe_schedule = [];
      foreach ($schedule as $time) {
         if (preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $time)) $safe_schedule[] = $time;
      }
      if (empty($safe_schedule)) { $safe_schedule = ['00:00:00']; } else { sort($safe_schedule); }
      $updated     = isset($cache['updated']) && preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $cache['updated']) ? $cache['updated'] : self::DATE_ALPHA;
      $valid_until = isset($cache['valid_until']) &&  preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $cache['valid_until']) ? $cache['valid_until'] : self::DATE_ALPHA;
      if (isset($cache['html'])) {
         $html     = $cache['html'];
      } else {
         $html     = '';
         $updated  = $valid_until = self::DATE_ALPHA;
      }
      $size = strlen($html);
      return(['allow-clear' => $allow_clear, 'clear-every-publish' => $clear_every_publish, 'inherit-parent' => $inherit_parent, 
         'mode' => $mode, 'period' => $period, 'schedule' => $safe_schedule, 'size' => $size, 'updated' => $updated, 
         'valid_until' => $valid_until, 'dynamics' => $dynamics, 'html' => $html]);
   } // validate();

} // Class USI_Page_Cache;

final class USI_Page_Status extends Exception { } // Class USI_Page_Status;

if (!function_exists('is_admin')) {
/* USI-PAGE-SOLUTIONS-9 USI_Page_Cache::cache() or null; */
}

// --------------------------------------------------------------------------------------------------------------------------- // ?>