<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

/* 
Author:            Jim Schwanda
Author URI:        https://www.usi2solve.com/leader
Description:       The Page-Solutions plugin provides custom CSS and JavaScript modifications, virtual widget mapping and page caching functionality on a page by page basis. This efficient and powerful plugin is well suited for page-intensive and non-blog WordPress applications. The Page-Solutions plugin is developed and maintained by Universal Solutions.
Donate link:       https://www.usi2solve.com/donate/wordpress-solutions
License:           GPL-3.0
License URI:       https://github.com/jaschwanda/wordpress-solutions/blob/master/LICENSE.md
Plugin Name:       Page-Solutions
Plugin URI:        https://github.com/jaschwanda/page-solutions
Requires at least: 5.0
Requires PHP:      5.6.25
Tested up to:      7.4.19
Text Domain:       usi-page-solutions
Version:           1.7.2
*/

/*
Page-Solutions is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
License as published by the Free Software Foundation, either version 3 of the License, or any later version.
 
Page-Solutions is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License along with Page-Solutions. If not, see 
https://github.com/jaschwanda/Page-solutions/blob/master/LICENSE.md

Copyright (c) 2020 by Jim Schwanda.
*/

//require_once('usi-page-cache.php');

require_once('usi-page-dbs-mysqli.php');

if (!class_exists('USI_Page_Cache')) { final class USI_Page_Cache {

   const VERSION = '1.7.0 (2022-08-09)';

   const DATE_ALPHA = '0000-00-00 00:00:00';
   const DATE_OMEGA = '9999-12-31 23:59:59';

   const POST_META = '_usi-page-solutions';
   const TEST_DATA = 'usi-page-solutions=database-fail';

   private function __construct() {
   } // __construct();

   public static function validate($cache) {
      return([ 'allow-clear' => false, 'clear-every-publish' => false, 'inherit-parent' => false, 
         'mode' => 'disable', 'period' => 86400, 'schedule' => [ '00:00:00' ], 'size' => 0, 'updated' => self::DATE_ALPHA, 
         'valid_until' => self::DATE_ALPHA, 'dynamics' => false, 'html' => '' ]);
   } // validate();

} } // Class USI_Page_Cache;

final class USI_Page_Solutions {

   const VERSION = '1.7.2 (2023-03-16)';

   const DEBUG_OFF    = 0x00000000;
   const DEBUG_CACHE  = 0x13000001;
   const DEBUG_HTML   = 0x13000002;
   const DEBUG_SANITZ = 0x13000004;

   const NAME         = 'Page-Solutions';
   const PREFIX       = 'usi-page';
   const TEXTDOMAIN   = 'usi-page-solutions';

   const WIDGETS_INIT_PRIORITY = 100;

   public static $capabilities = array(
      'enhanced-edit'  => 'Select enhanced widget areas|administrator',
      'virtual-add'    => 'Add virtual widget collections|administrator',
      'virtual-edit'   => 'Edit virtual widget collections|administrator',
      'virtual-delete' => 'Delete virtual widget collections|administrator',
      'settings-view'  => 'View settings page|administrator',
   );

   private static $debug = self::DEBUG_OFF;
   private static $info  = null;
   private static $theme = null;

   public static $meta_value = null; // Page/post postmeta data;
   public static $option_name_base = null;
   public static $option_name_virtual = null;
   public static $options = array();
   public static $options_virtual = null; // Virtual widget array for current theme;
   public static $theme_name = null; // Slugified name of current theme;
   public static $virtual_source = null; // Virtual source widget to map in enhanced widget area;

   private function __construct() {
   } // __construct();

   static function action_admin_notices() {
      global $pagenow;
      if ('plugins.php' == $pagenow) {
        $text = sprintf(
           __('The %s plugin is required for the %s plugin to run properly.', self::TEXTDOMAIN), 
           '<b>WordPress-Solutions</b>',
           '<b>Page-Solutions</b>'
        );
        echo '<div class="notice notice-warning is-dismissible"><p>' . $text . '</p></div>';
      }
   } // action_admin_notices();

   static function action_widgets_init() {
      $theme = wp_get_theme();
      self::$theme_name = sanitize_title($theme->get('Name'));
      // self::$option_name_base = 'usi-page-solutions-options-' . self::$theme_name; // This seems too f++king long;
      self::$option_name_base = 'usi-page-' . self::$theme_name;
      self::$option_name_virtual = self::$option_name_base . '-virtual';
      self::$options_virtual = get_option(self::$option_name_virtual);
      if (!empty(self::$options_virtual)) {
         for ($ith = 0; $ith < count(self::$options_virtual); $ith++) {
            register_sidebar(self::$options_virtual[$ith]);
         }
      }
   } // action_widgets_init();

   static function action_wp_body_open() {
      echo USI_Page_Solutions::$options['preferences']['global-header'];
   } // action_wp_body_open();

   static function action_wp_enqueue_scripts() {

      global $post;

      $post_id = (int)(!empty($post->ID) ? $post->ID : 0);

      self::$meta_value = self::meta_value_get($post_id);

      $what2do = array(
         array('key' => 'styles_parent',  'inherit' => 'styles_inherit',  'funtction' => 'wp_enqueue_style',  'default' => null),
         array('key' => 'styles',         'inherit' => false,             'funtction' => 'wp_enqueue_style',  'default' => null),
         array('key' => 'scripts_parent', 'inherit' => 'scripts_inherit', 'funtction' => 'wp_enqueue_script', 'default' => false),
         array('key' => 'scripts',        'inherit' => false,             'funtction' => 'wp_enqueue_script', 'default' => false),
      );

      foreach ($what2do as $do) {
         if (!$do['inherit'] || !empty(self::$meta_value['options'][$do['inherit']])) {
            if (!empty(self::$meta_value['layout'][$do['key']])) {
               $link = self::$meta_value['layout'][$do['key']];
               foreach ($link as $key => $value) {
                  if (!empty($value)) {
                     $tokens = explode(' ', $value);
                     $do['funtction'](
                        $tokens[0], // Unique link sheet name;
                        $tokens[1], // Full URL of the link sheet, or path of link relative to root;
                        null, // Array of registered link handles this link depends on;
                        (!empty($tokens[2]) && ('null' != $tokens[2])) ? $tokens[2] : null, // Link version number;
                        !empty($tokens[3]) ? $tokens[3] : $do['default'] // Media flag;
                     );
                  }
               }
            }
         }
      }

   } // action_wp_enqueue_scripts();

   static function action_wp_footer() {
      if (!empty(self::$meta_value['layout']['codes_foot_parent'])) echo self::$meta_value['layout']['codes_foot_parent'];
      if (!empty(self::$meta_value['layout']['codes_foot'])) echo self::$meta_value['layout']['codes_foot'];
   } // action_wp_footer();

   static function action_wp_head() {
      if (self::$meta_value['options']['css_inherit'] && !empty(self::$meta_value['layout']['css_parent'])) echo self::$meta_value['layout']['css_parent'];
      if (!empty(self::$meta_value['layout']['css'])) echo self::$meta_value['layout']['css'];
      if (!empty(self::$meta_value['layout']['codes_head_parent'])) echo self::$meta_value['layout']['codes_head_parent'];
      if (!empty(self::$meta_value['layout']['codes_head'])) echo self::$meta_value['layout']['codes_head'];
   } // action_wp_head();

   static function callback() {
    
      global $wp_registered_widgets;

      self::$info = array();

      $original_params = func_get_args();
      $widget_id = $original_params[0]['widget_id'];   
      $wp_registered_widgets[$widget_id]['callback'] = $original_callback = $wp_registered_widgets[$widget_id]['original_callback'];
    
      if (is_callable($original_callback)) {
         self::$info['class'] = get_class($original_callback[0]);
         if (method_exists($original_callback[0], 'USI_Page_Cache_file')) {
            $file = $original_callback[0]->USI_Page_Cache_file();
         } else {
            $rf = new ReflectionClass(self::$info['class']);
            $file = $rf->getFileName();
         }
         self::$info['file'] = str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $file);
         self::$info['begin'] = ob_get_length();
         call_user_func_array($original_callback, $original_params);
         self::$info['end'] = ob_get_length();
         if (!empty(self::$info['instance']['is_dynamic'])) USI_Page_Cache::que(self::$info);

      }
    
   } // callback();

   static function filter_dynamic_sidebar_params($sidebar_params) {
      // Override theme html with virtual widget values;
      static $ps_offset = 0;
      if (!empty(self::$virtual_source[$ps_offset])) {
         $virtual_widget_id = self::$virtual_source[$ps_offset++];
         if (!empty(self::$options_virtual)) {
            for ($ith = 0; $ith < count(self::$options_virtual); $ith++) {
               if ($virtual_widget_id == self::$options_virtual[$ith]['id']) {
                  $option_virtual = self::$options_virtual[$ith];
                  if ('disable' != $option_virtual['before_title'])  $sidebar_params[0]['before_title']  = $option_virtual['before_title'];
                  if ('disable' != $option_virtual['after_title'])   $sidebar_params[0]['after_title']   = $option_virtual['after_title'];
                  if ('disable' != $option_virtual['before_widget']) $sidebar_params[0]['before_widget'] = $option_virtual['before_widget'];
                  if ('disable' != $option_virtual['after_widget'])  $sidebar_params[0]['after_widget']  = $option_virtual['after_widget'];
                  break;
               }
            }
         }
      }
      
      // This filter intercepts the widget() calls so that the widget output can be buffered and
      // the dynamics can be saved, not required when caching is disabled as everything must run;
      if (is_admin() || ('disable' == self::$meta_value['cache']['mode'])) return($sidebar_params);

      global $wp_registered_widgets;

      $widget_id = $sidebar_params[0]['widget_id'];
 
      $wp_registered_widgets[$widget_id]['original_callback'] = $wp_registered_widgets[$widget_id]['callback'];
      $wp_registered_widgets[$widget_id]['callback'] = array(__CLASS__, 'callback');
      return($sidebar_params);

   } // filter_dynamic_sidebar_params();

   static function filter_pre_option_stylesheet() { 

      return(self::load_theme(1)); 

   } // filter_pre_option_stylesheet();

   static function filter_pre_option_template() { 

      return(self::load_theme(0)); 

   } // filter_pre_option_template();

   static function filter_sidebars_widgets($sidebars_widgets) {
   
      static $mapped_widgets = null;
   
      if (is_page()) {
   
         if ($mapped_widgets) return($mapped_widgets);

         if (!empty(self::$meta_value['widgets'])) {
            foreach (self::$meta_value['widgets'] as $target_id => $enhanced_widget_areas) {
               $jth = 0;
               foreach ($enhanced_widget_areas as $source_id) {
                  for ($ith = 0; $ith < count($sidebars_widgets[$source_id]); $ith++, $jth++) {
                     $sidebars_widgets[$target_id][$jth] = $sidebars_widgets[$source_id][$ith];
                     self::$virtual_source[] = $source_id;
                  }
               }
            }
         }
         $mapped_widgets = $sidebars_widgets;
      }

      return($sidebars_widgets);
   
   } // filter_sidebars_widgets();

   static function filter_widget_display_callback($instance, $that, $args) {
      self::$info['args'] = $args;
      return(self::$info['instance'] = $instance);
   } // filter_widget_display_callback();

   static function init() {

      if (empty(USI_Page_Solutions::$options)) {
         $defaults['cache']['config-location'] =
         $defaults['cache']['root-location']   =
         $defaults['cache']['debug-ip']        = '';
         $defaults['cache']['track-times']     =
         $defaults['cache']['debug-meta-data'] = 
         $defaults['cache']['debug-sql']       = 
         $defaults['preferences']['enable-cache'] = 
         $defaults['preferences']['enable-enhanced-areas'] = 
         $defaults['preferences']['enable-layout'] = false;
         $defaults['preferences']['global-header'] = null;
         $defaults['preferences']['page-mru-max'] = 
         $defaults['preferences']['post-mru-max'] = 4;
         USI_Page_Solutions::$options = get_option(self::PREFIX . '-options', $defaults);
         if (!is_admin()) {
            if (empty(USI_Page_Solutions::$options['cache']['root-location'])) {
               $included_files = get_included_files();
               USI_Page_Solutions::$options['cache']['root-location'] = $included_files[0];
               update_option(self::PREFIX . '-options', USI_Page_Solutions::$options);
            }
         }
      }

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) {
         add_action('widgets_init', array(__CLASS__, 'action_widgets_init'), self::WIDGETS_INIT_PRIORITY);
         add_filter('dynamic_sidebar_params', array(__CLASS__, 'filter_dynamic_sidebar_params'));
         add_filter('sidebars_widgets', array(__CLASS__, 'filter_sidebars_widgets'));
         add_filter('widget_display_callback', array(__CLASS__, 'filter_widget_display_callback'), 10, 3);
      }

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout'])) {
         add_action('wp_enqueue_scripts', array(__CLASS__, 'action_wp_enqueue_scripts'), 20);
        add_action('wp_footer', array(__CLASS__, 'action_wp_footer'), 9999);
        add_action('wp_head', array(__CLASS__, 'action_wp_head'), 100);
      }

      if (!empty(USI_Page_Solutions::$options['preferences']['global-header'])) {
         add_action('wp_body_open', array(__CLASS__, 'action_wp_body_open'));
      }

      if (!empty(USI_Page_Cache::$theme) && ('default' != USI_Page_Cache::$theme)) {
         self::$theme = explode(':', USI_Page_Cache::$theme);
         add_filter('pre_option_stylesheet', [__CLASS__, 'filter_pre_option_stylesheet']);
         add_filter('pre_option_template',   [__CLASS__, 'filter_pre_option_template']);
      }

   } // init();

   static public function load_theme($index) {
      return(self::$theme[$index] ?? '');
   } // load_theme();

   static function meta_value_get($post_id, $debug = false) {

      try {
         global $wpdb;
         $dbs = new USI_Page_Dbs(array('hash' => 'db', 'host' => 'ddev-nbrc-db', 'name' => 'db', 'user' => 'db'));
         $query = $dbs->prepare_x(
            'SELECT `meta_key`, `meta_value` FROM `' . $wpdb->prefix . 'postmeta` ' .
            "WHERE (`post_id` = ?) AND (`meta_key` LIKE '" . USI_Page_Cache::POST_META . "%') " .
            'LIMIT 1', // SQL;
            array('i', & $post_id), // Input parameters;
            array(& $meta_key, & $meta_value) // Output variables;
         );
         $data = array();
         if (1 == $query->num_rows) {
            $query->fetch();
            try {
               $data = unserialize(base64_decode($meta_value));
            } catch(exception $e) {
            }
         }
         $query = null; // Close query;
      } catch(USI_Page_Dbs_Exception $e) {
         if (self::$debug || $debug) {
            usi::log('message=', $e->GetMessage(), '\ntrace=', $e->GetTraceAsString());
         }
      }

      $layout['codes_foot']          = $data['layout']['codes_foot']        ?? null;
      $layout['codes_foot_parent']   = $data['layout']['codes_foot_parent'] ?? null;
      $layout['codes_head']          = $data['layout']['codes_head']        ?? null;
      $layout['codes_head_parent']   = $data['layout']['codes_head_parent'] ?? null;
      $layout['css']                 = $data['layout']['css']               ?? null;
      $layout['css_parent']          = $data['layout']['css_parent']        ?? null;
      $layout['scripts']             = $data['layout']['scripts']           ?? null;
      $layout['scripts_parent']      = $data['layout']['scripts_parent']    ?? null;
      $layout['styles']              = $data['layout']['styles']            ?? null;
      $layout['styles_parent']       = $data['layout']['styles_parent']     ?? null;

      $options['arguments']          = !empty($data['options']['arguments']);
      $options['codes_foot_inherit'] = !empty($data['options']['codes_foot_inherit']);
      $options['codes_head_inherit'] = !empty($data['options']['codes_head_inherit']);
      $options['css_inherit']        = !empty($data['options']['css_inherit']);
      $options['scripts_inherit']    = !empty($data['options']['scripts_inherit']);
      $options['styles_inherit']     = !empty($data['options']['styles_inherit']);
      $options['theme']              = $data['options']['theme'] ?? 'default';
      $options['widgets_inherit']    = !empty($data['options']['widgets_inherit']);

      $widgets = $data['widgets'] ?? null;

      $url      = rtrim(get_permalink($post_id), '/') . '/';
      $path     = str_replace(array($_SERVER['SERVER_NAME'], 'https://', 'http://'), '', $url);
      $meta_key = USI_Page_Cache::POST_META . ($options['arguments'] ? '*' : '!') . $path;

      $value = array(
         'key'     => $meta_key, 
         'post_id' => $post_id, 
         'cache'   => null, 
         'layout'  => $layout, 
         'options' => $options, 
         'widgets' => $widgets
      );

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-cache'])) {
         $value['cache'] = USI_Page_Cache::validate(!empty($data['cache']) ? $data['cache'] : null);
      }

      if (self::$debug || $debug) {

         if (!(self::$debug & self::DEBUG_HTML)) { 
            $html = $value['cache']['html']; 
            $value['cache']['html'] = '~~~'; 
         }

         usi::log2('meta_value_get():meta_value=', $value);

         if (!(self::$debug & self::DEBUG_HTML)) {
            $value['cache']['html'] = $html;
         }

      }

      return($value);

   } // meta_value_get();

   static function meta_value_put($value, $debug = false) {

      if (self::$debug || $debug) {

         if (!(self::$debug & self::DEBUG_HTML)) { 
            $html = $value['cache']['html']; 
            $value['cache']['html'] = '~~~'; 
         }

         usi::log2('meta_value_put():meta_value=', $value);

         if (!(self::$debug & self::DEBUG_HTML)) {
            $value['cache']['html'] = $html;
         }

      }

      update_post_meta($value['post_id'], $value['key'], base64_encode(serialize($value)));

   } // meta_value_put();

   public static function number_of_offspring($page_id) {
      global $wpdb;
      $SAFE_table_name = $wpdb->prefix . 'posts';
      $children = (int)$wpdb->get_var(
         $wpdb->prepare("SELECT count(`ID`) FROM `$SAFE_table_name` WHERE (`post_parent` = %d) AND (`post_type` = 'page')", $page_id)
      );
      return($children);
   } // number_of_offspring

} // Class USI_Page_Solutions;

USI_Page_Solutions::init();

if (is_admin() && !defined('WP_UNINSTALL_PLUGIN')) {
   if (is_dir(plugin_dir_path(__DIR__) . 'usi-wordpress-solutions')) {
      require_once('usi-page-solutions-admin.php');
      require_once('usi-page-solutions-install.php');
      require_once('usi-page-solutions-layout.php');
      require_once('usi-page-solutions-layout-edit.php');
      require_once('usi-page-solutions-settings.php');
      require_once('usi-page-solutions-virtual.php');
      require_once('usi-page-solutions-virtual-list.php');
      if (!empty(USI_Page_Solutions::$options['preferences']['enable-cache'])) {
         require_once('usi-page-solutions-cache.php');
         require_once('usi-page-solutions-options.php');
      }
      if (!empty(USI_Page_Solutions::$options['updates']['git-update'])) {
         require_once(plugin_dir_path(__DIR__) . 'usi-wordpress-solutions/usi-wordpress-solutions-update.php');
         new USI_WordPress_Solutions_Update_GitHub(__FILE__, 'jaschwanda', 'page-solutions');
      }
   } else {
      add_action('admin_notices', array('USI_Page_Solutions', 'action_admin_notices'));
   }
}

// --------------------------------------------------------------------------------------------------------------------------- // ?>