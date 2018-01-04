<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

/*
Plugin Name: Page-Solutions
Plugin URI: https://github.com/jaschwanda/page-solutions
Description: The Page-Solutions plugin extends the WordPress widget system by enabling the creation of virtual widget collections that can be displayed in enhanced widget areas on a page by page basis. It also provides page caching functionality. The Page-Solutions plugin is developed and maintained by Universal Solutions. 
Version: 0.0.1 (2018-01-03)
Author: Jim Schwanda
Author URI: http://www.usi2solve.com/leader
Text Domain: usi-page-solutions
*/

require_once('usi-page-cache.php');
require_once('usi-settings.php'); 

final class USI_Page_Solutions {

   const VERSION = '0.0.2 (2018-01-04)';

   const NAME       = 'Page-Solutions';
   const PREFIX     = 'usi-page';
   const TEXTDOMAIN = 'usi-page-solutions';

   const WIDGETS_INIT_PRIORITY = 100;

   public static $capabilities = array(
      'Enhanced-Edit'  => 'Select enhanced widget areas',
      'Virtual-Add'    => 'Add virtual widget collections',
      'Virtual-Edit'   => 'Edit virtual widget collections',
      'Virtual-Delete' => 'Delete virtual widget collections',
      'Settings-View'  => 'View settings page',
   );

   private static $info = null;

   public static $option_name_base = null;
   public static $option_name_virtual = null;
   public static $options_virtual = null; // Virtual widget array for current theme;
   public static $post_meta = null; // Page/post postmeta data;
   public static $theme_name = null; // Slugified name of current theme;
   public static $virtual_source = null; // Virtual source widget to map in enhanced widget area;

   private function __construct() {
   } // __construct();

   static function action_widgets_init() {
      $theme = wp_get_theme();
      self::$theme_name = sanitize_title($theme->get('Name'));
      self::$option_name_base = 'usi-page-solutions-options-' . self::$theme_name;
      self::$option_name_virtual = self::$option_name_base . '-virtual';
      self::$options_virtual = get_option(self::$option_name_virtual);
      if (!empty(self::$options_virtual)) {
         for ($ith = 0; $ith < count(self::$options_virtual); $ith++) {
            register_sidebar(self::$options_virtual[$ith]);
         }
      }
   } // action_widgets_init();

   static function action_wp_enqueue_scripts() {
      self::post_meta_get();
      $what2do = array(
         array('key' => 'styles_parent',  'inherit' => 'styles_inherit',  'funtction' => 'wp_enqueue_style',  'default' => null),
         array('key' => 'styles',         'inherit' => false,             'funtction' => 'wp_enqueue_style',  'default' => null),
         array('key' => 'scripts_parent', 'inherit' => 'scripts_inherit', 'funtction' => 'wp_enqueue_script', 'default' => false),
         array('key' => 'scripts',        'inherit' => false,             'funtction' => 'wp_enqueue_script', 'default' => false),
      );
      foreach ($what2do as $do) {
         if (!$do['inherit'] || !empty(self::$post_meta['options'][$do['inherit']])) {
            if (!empty(self::$post_meta['layout'][$do['key']])) {
               $link = self::$post_meta['layout'][$do['key']];
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
      if (!empty(self::$post_meta['layout']['codes_foot_parent'])) echo self::$post_meta['layout']['codes_foot_parent'];
      if (!empty(self::$post_meta['layout']['codes_foot'])) echo self::$post_meta['layout']['codes_foot'];
   } // action_wp_footer();

   static function action_wp_head() {
      if (self::$post_meta['options']['css_inherit'] && !empty(self::$post_meta['layout']['css_parent'])) echo self::$post_meta['layout']['css_parent'];
      if (!empty(self::$post_meta['layout']['css'])) echo self::$post_meta['layout']['css'];
      if (!empty(self::$post_meta['layout']['codes_head_parent'])) echo self::$post_meta['layout']['codes_head_parent'];
      if (!empty(self::$post_meta['layout']['codes_head'])) echo self::$post_meta['layout']['codes_head'];
   } // action_wp_head();

   function callback() {
    
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
      
      // This filter intercepts the widget() calls so that the widget output can be buffered and
      // the dynamics can be saved, not required when caching is disabled as everything must run;
      if (is_admin() || ('disable' == self::$post_meta['cache']['mode'])) return($sidebar_params);

      global $wp_registered_widgets;

      $widget_id = $sidebar_params[0]['widget_id'];
 
      $wp_registered_widgets[$widget_id]['original_callback'] = $wp_registered_widgets[$widget_id]['callback'];
      $wp_registered_widgets[$widget_id]['callback'] = array(__CLASS__, 'callback');
      return($sidebar_params);

   } // filter_dynamic_sidebar_params();

   static function filter_sidebars_widgets($sidebars_widgets) {
   
      static $mapped_widgets = null;
   
      if (is_page()) {
   
         if ($mapped_widgets) return($mapped_widgets);

         if (!empty(self::$post_meta['widgets'])) {
            foreach (self::$post_meta['widgets'] as $target_id => $options_enhanced) {
               $jth = 0;
               foreach ($options_enhanced as $source_id) {
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
      if (empty(USI_Settings::$options[self::PREFIX])) {
         $defaults['cache']['config-location'] =
         $defaults['cache']['root-location']   =
         $defaults['cache']['root-status']     =
         $defaults['cache']['debug-ip']        = '';
         $defaults['cache']['track-times']     =
         $defaults['cache']['debug-meta-data'] = 
         $defaults['cache']['debug-sql']       = 
         $defaults['preferences']['enable-cache'] = 
         $defaults['preferences']['enable-enhanced-areas'] = 
         $defaults['preferences']['enable-layout'] = false;
         $defaults['preferences']['page-mru-max'] = 
         $defaults['preferences']['post-mru-max'] = 4;
         USI_Settings::$options[self::PREFIX] = get_option(self::PREFIX . '-options', $defaults);
         if (!is_admin()) {
            if (empty(USI_Settings::$options[self::PREFIX]['cache']['root-location'])) {
               $included_files = get_included_files();
               USI_Settings::$options[self::PREFIX]['cache']['root-location'] = $included_files[0];
               update_option(self::PREFIX . '-options', USI_Settings::$options[self::PREFIX]);
            }
         }
      }

      add_action('widgets_init', array(__CLASS__, 'action_widgets_init'), self::WIDGETS_INIT_PRIORITY);
      add_action('wp_enqueue_scripts', array(__CLASS__, 'action_wp_enqueue_scripts'), 20);
      add_action('wp_footer', array(__CLASS__, 'action_wp_footer'), 9999);
      add_action('wp_head', array(__CLASS__, 'action_wp_head'), 100);

      if (!empty(USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-enhanced-areas'])) {
         add_filter('dynamic_sidebar_params', array(__CLASS__, 'filter_dynamic_sidebar_params'));
         add_filter('sidebars_widgets', array(__CLASS__, 'filter_sidebars_widgets'));
         add_filter('widget_display_callback', array(__CLASS__, 'filter_widget_display_callback'), 10, 3);
      }

   } // init();

   public static function number_of_offspring($page_id) {
      global $wpdb;
      $SAFE_table_name = $wpdb->prefix . 'posts';
      $children = (int)$wpdb->get_var(
         $wpdb->prepare("SELECT count(`ID`) FROM `$SAFE_table_name` WHERE (`post_parent` = %d) AND (`post_type` = 'page')", $page_id)
      );
      return($children);
   } // number_of_offspring

   static function post_meta_get($post_id = 0) {

      if (!self::$post_meta || $post_id) {

         global $post;

         $id = (int)($post_id ? $post_id : (!empty($post->ID) ? $post->ID : 0));

         if ($id) {

            try {
               global $wpdb;
               $dbs = new USI_Dbs(array('hash' => DB_PASSWORD, 'host' => DB_HOST, 'name' => DB_NAME, 'user' => DB_USER));
               $query = $dbs->prepare_x(
                  'SELECT `meta_key`, `meta_value` FROM `' . $wpdb->prefix . 'postmeta` ' .
                  "WHERE (`post_id` = ?) AND (`meta_key` LIKE '" . USI_Page_Cache::POST_META . "%') " .
                  'LIMIT 1', // SQL;
                  array('i', & $id), // Input parameters;
                  array(& $meta_key, & $meta_value) // Output variables;
               );
               if (1 == $query->num_rows) {
                  $query->fetch();
                  $stuff = unserialize($meta_value);
               } else {
                  $stuff = array();
               }
               $query = null; // Close query;
            } catch(USI_Dbs_Exception $e) {
               USI_Debug::exception($e);
            }

            $SAFE_cache = USI_Page_Cache::validate(!empty($stuff['cache']) ? $stuff['cache'] : null);

            $SAFE_layout['codes_foot']          = !empty($stuff['layout']['codes_foot'])        ? $stuff['layout']['codes_foot']        : null;
            $SAFE_layout['codes_foot_parent']   = !empty($stuff['layout']['codes_foot_parent']) ? $stuff['layout']['codes_foot_parent'] : null;
            $SAFE_layout['codes_head']          = !empty($stuff['layout']['codes_head'])        ? $stuff['layout']['codes_head']        : null;
            $SAFE_layout['codes_head_parent']   = !empty($stuff['layout']['codes_head_parent']) ? $stuff['layout']['codes_head_parent'] : null;
            $SAFE_layout['css']                 = !empty($stuff['layout']['css'])               ? $stuff['layout']['css']               : null;
            $SAFE_layout['css_parent']          = !empty($stuff['layout']['css_parent'])        ? $stuff['layout']['css_parent']        : null;
            $SAFE_layout['scripts']             = !empty($stuff['layout']['scripts'])           ? $stuff['layout']['scripts']           : null;
            $SAFE_layout['scripts_parent']      = !empty($stuff['layout']['scripts_parent'])    ? $stuff['layout']['scripts_parent']    : null;
            $SAFE_layout['styles']              = !empty($stuff['layout']['styles'])            ? $stuff['layout']['styles']            : null;
            $SAFE_layout['styles_parent']       = !empty($stuff['layout']['styles_parent'])     ? $stuff['layout']['styles_parent']     : null;

            $SAFE_options['arguments']          = !empty($stuff['options']['arguments']);
            $SAFE_options['codes_foot_inherit'] = !empty($stuff['options']['codes_foot_inherit']);
            $SAFE_options['codes_head_inherit'] = !empty($stuff['options']['codes_head_inherit']);
            $SAFE_options['css_inherit']        = !empty($stuff['options']['css_inherit']);
            $SAFE_options['scripts_inherit']    = !empty($stuff['options']['scripts_inherit']);
            $SAFE_options['styles_inherit']     = !empty($stuff['options']['styles_inherit']);
            $SAFE_options['widgets_inherit']    = !empty($stuff['options']['widgets_inherit']);

            $SAFE_widgets = !empty($stuff['widgets']) ? $stuff['widgets'] : null;

            $url = rtrim(get_permalink($id), '/') . '/';
            $path = str_replace(array($_SERVER['SERVER_NAME'], 'https://', 'http://'), '', $url);
            $meta_key = USI_Page_Cache::POST_META . ($SAFE_options['arguments'] ? '*' : '!') . $path;

            self::$post_meta = array('key' => $meta_key, 'post_id' => $id, 'cache' => $SAFE_cache, 
               'layout' => $SAFE_layout, 'options' => $SAFE_options, 'widgets' => $SAFE_widgets);
            // USI_Debug::message(__METHOD__.':'.__LINE__.':post_meta=' . print_r(self::$post_meta, true));

         }
      }
   } // post_meta_get();

   static function post_meta_update() {
      update_post_meta(self::$post_meta['post_id'], self::$post_meta['key'], self::$post_meta);
      // USI_Debug::message(__METHOD__.':post_meta=' . print_r(self::$post_meta, true));
   } // post_meta_update();

} // Class USI_Page_Solutions;

USI_Page_Solutions::init();

if (is_admin() && !defined('WP_UNINSTALL_PLUGIN')) {
   require_once('usi-page-solutions-admin.php');
   require_once('usi-page-solutions-install.php');
   require_once('usi-page-solutions-layout.php');
   require_once('usi-page-solutions-settings.php');
   require_once('usi-page-solutions-virtual.php');
   require_once('usi-page-solutions-virtual-list.php');
   if (!empty(USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-cache'])) {
      require_once('usi-page-solutions-cache.php');
      require_once('usi-page-solutions-options.php');
   }
   require_once('usi-settings-admin.php');
   require_once('usi-settings-capabilities.php');
}

// --------------------------------------------------------------------------------------------------------------------------- // ?>