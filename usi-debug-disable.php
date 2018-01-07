<?php // ------------------------------------------------------------------------------------------------------------------------ //

class USI_Debug {
   const VERSION = '1.1.0 (2017-11-12)';
   private function __construct() { }
   public static function exception($e) { }
   public static function get_message() { return(null); }
   public static function init($options = null) { } 
   public static function message($message) { }
   public static function output($message = null) {}
   public static function print_r($prefix, $array, $suffix = '') { }
   public static function shutdown() { }
} // Class USI_Debug;

// --------------------------------------------------------------------------------------------------------------------------- // ?>