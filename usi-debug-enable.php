<?php // ------------------------------------------------------------------------------------------------------------------------ //

class USI_Debug {

   const VERSION = '1.1.2 (2017-12-31)';

   private static $message = null;

   private function __construct() {
   } // __construct();

   public static function exception($e) {
      self::$message .= $e->GetMessage() . PHP_EOL . $e->GetTraceAsString() . PHP_EOL;
   } // exception();

   public static function get_message($keep = false) {
      $message_save = self::$message;
      if (!$keep) self::$message = null;
      return($message_save);
   } // get_message();

   public static function message($message) {
      self::$message .= $message . PHP_EOL;
   } // message();

   public static function output($message = null, $prefix = null, $suffix = null) {
      if ($message) self::$message .= $message . PHP_EOL;
      if (self::$message) echo $prefix . self::$message . $suffix;
      self::$message = null;
   } // output();

   public static function print_r($prefix, $array, $suffix = '', $escape = false) {
      self::$message .= $prefix . ($escape ? str_replace(array('<!--', '-->'), array('<!==', '==>'), 
         print_r($array, true)) : print_r($array, true)) . $suffix . PHP_EOL;
   } // print_r();

} // Class USI_Debug;

// --------------------------------------------------------------------------------------------------------------------------- // ?>