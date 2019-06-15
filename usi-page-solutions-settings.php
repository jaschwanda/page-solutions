<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

require_once(plugin_dir_path(__DIR__) . 'usi-wordpress-solutions/usi-wordpress-solutions-capabilities.php');
require_once(plugin_dir_path(__DIR__) . 'usi-wordpress-solutions/usi-wordpress-solutions-settings.php');
require_once(plugin_dir_path(__DIR__) . 'usi-wordpress-solutions/usi-wordpress-solutions-versions.php');

class USI_Page_Solutions_Settings extends USI_WordPress_Solutions_Settings {

   const VERSION = '1.3.0 (2019-06-15)';

   protected $is_tabbed = true;

   private static $cache_config_status  = null;
   private static $cache_config_warning = null;

   function __construct() {

      $good = __('Good', USI_Page_Solutions::TEXTDOMAIN);
      $root_status = !empty(USI_Page_Solutions::$options['cache']['root-status']) ? USI_Page_Solutions::$options['cache']['root-status'] : null;
      if ($root_status == $good) {
         $pages = get_pages(array('sort_column' => 'ID', 'number' => 1, 'post_type' => 'page', 'post_status' => 'publish'));
         if (!empty($pages)) {
            $url = get_home_url(null, $pages[0]->post_name . '/?' . USI_Page_Cache::TEST_DATA);
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $content = curl_exec($ch);
            curl_close($ch);
            if ($content == USI_Page_Cache::TEST_DATA) {
               self::$cache_config_status  = __('Data base connect error', USI_Page_Solutions::TEXTDOMAIN);
               self::$cache_config_warning = __('The Page-Solutions caching system cannot connect to the WordPress database, go to the <a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=usi-page-settings&tab=cache">Cache Options</a> tab on the Page-Solutions Settings page and check your configuration parameters.', USI_Page_Solutions::TEXTDOMAIN);
               add_action('admin_notices', array(__CLASS__, 'action_admin_notices'));
            }
         }
      } else if (!empty(USI_Page_Solutions::$options['preferences']['enable-cache'])) {
         $root = !empty(USI_Page_Solutions::$options['cache']['root-location']) ? USI_Page_Solutions::$options['cache']['root-location'] : null;
         if (!$root) {
            self::$cache_config_status  = __('Unknown', USI_Page_Solutions::TEXTDOMAIN);
            self::$cache_config_warning = __('The <b>index.php</b> file location is unknown. Access any WordPress page in the site from another browser that is not running in administrator mode, then go to the <a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=usi-page-settings&tab=cache">Cache Options</a> tab on the Page-Solutions Settings page and click the <b>Save Cache Options</b> button.', USI_Page_Solutions::TEXTDOMAIN);
            add_action('admin_notices', array(__CLASS__, 'action_admin_notices'));
         } else {
            $root_folder  = substr($root, 0, -9);
            $plugin_path  = plugin_dir_path(__FILE__);
            $include_path = str_replace('\\', '/', substr($plugin_path, strlen($root_folder)));
            $include_file = $include_path . 'usi-page-cache.php';
            $modification = "<?php /* USI-PAGE-SOLUTIONS */ @ include('$include_file'); ?>";
            if (is_file($root) && is_readable($root)) {
               if ($root_stream = fopen($root, 'r')) {
                  $first_line =  trim(fgets($root_stream), PHP_EOL);
                  fclose($root_stream);
                  if ($modification == $first_line) {
                     self::$cache_config_status  = $good;
                  } else {
                     self::$cache_config_status  = __('Pending Modification', USI_Page_Solutions::TEXTDOMAIN);
                     self::$cache_config_warning ='The <b>index.php</b> file has has not been modified to support caching. Go to the <a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=usi-page-settings&tab=cache">Cache Options</a> tab on the Page-Solutions Settings page and click the <b>Save Cache Options</b> button.';
                     add_action('admin_notices', array(__CLASS__, 'action_admin_notices'));
                  }
               }
            }
         }
      }
      USI_Page_Solutions::$options['cache']['root-status'] = self::$cache_config_status;

      parent::__construct(
         USI_Page_Solutions::NAME, 
         USI_Page_Solutions::PREFIX, 
         USI_Page_Solutions::TEXTDOMAIN,
         USI_Page_Solutions::$options
      );

   } // __construct();

   static function action_admin_notices() {
      echo '<div class="notice notice-warning"><p>' . self::$cache_config_warning . '</p></div>';
   } // action_admin_notices();

   function action_admin_init() {
      if (!empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) {
         global $wp_registered_sidebars;
         $disabled = USI_Page_Solutions_Admin::$enhanced_edit ? null : 'disabled';
         foreach ($wp_registered_sidebars as $id => $sidebar) {
            // Skip virtual widget areas created by Page-Solutions;
            if ($id == USI_Page_Solutions::$options_virtual[0]['id']) break;
            $this->sections['widgets']['settings'][$id] = array(
               'type' => 'checkbox', 
               'disabled' => $disabled, 
               'label' => $sidebar['name'], 
               'notes' => !empty($sidebar['description']) ? ' (<i>' . $sidebar['description'] . '</i>)' : ''
            );
         }
      }
      parent::action_admin_init();
   } // action_admin_init();

   static function cache_all_clear() {
      global $wpdb;
      $SAFE_table_name = $wpdb->prefix . 'posts';
      $pages = $wpdb->get_results("SELECT `ID` FROM `$SAFE_table_name` WHERE (`post_type` = 'page')", ARRAY_A);

      for ($ith = 0; $ith < count($pages); $ith++) {
         $page_id = $pages[$ith]['ID'];
         $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $page_id);
         $meta_value['cache']['updated'] = 
         $meta_value['cache']['valid_until'] = USI_Page_Cache::DATE_ALPHA;
         $meta_value['cache']['html'] = '';
         $meta_value['cache']['size'] = 0;
         USI_Page_Solutions::meta_value_put(__METHOD__, $meta_value);
         // Since index.php does the updates, just call curl and discard the data.
         $ch = curl_init(); 
         curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_URL, rtrim(get_permalink($page_id), '/') . '/');
         curl_exec($ch);
         curl_close($ch);
      }
   } // cache_all_clear();

   static function cache_file_generate($cache = null) {
      global $wpdb;
      if (empty($cache['config-location'])) {
         $config_location = null;
         $db_pass = "'" . DB_PASSWORD . "'";
         $db_host = "'" . DB_HOST . "'";
         $db_name = "'" . DB_NAME . "'";
         $db_user = "'" . DB_USER . "'";
      } else {
         $config_location = PHP_EOL . "@include('" . $cache['config-location'] . "');" . PHP_EOL;
         $db_pass = 'DB_PASSWORD';
         $db_host = 'DB_HOST';
         $db_name = 'DB_NAME';
         $db_user = 'DB_USER';
      }

      $option = empty($cache['track-times']) ? 'false' : 'true';
      if (!empty($cache['debug-ip'])) {
         $debug = null;
         if (!empty($cache['debug-meta-data']))  $debug .= ($debug ? '|' : '') . 'USI_Page_Cache::DEBUG_META_DATA';
         if (!empty($cache['debug-sql']))        $debug .= ($debug ? '|' : '') . 'USI_Page_Cache::DEBUG_SQL';
         $option .= ", '" . $cache['debug-ip'] . "'" . ($debug ? ", " . $debug : '');
      }
      if ('false' == $option) $option = null;

      $instantiate_class = empty($cache) ? null : PHP_EOL . 'USI_Page_Cache::cache(' . $option . ');' . PHP_EOL;

      $template = dirname(__FILE__) . '/usi-page-cache-template.php';

      if (is_file($template) && is_readable($template)) {
         $template_stream = fopen($template, 'r');
         $template_content = str_replace(
            array(
               '/* USI-PAGE-SOLUTIONS-1 */',
               '/* USI-PAGE-SOLUTIONS-2 */',
               '/* USI-PAGE-SOLUTIONS-3 */',
               '/* USI-PAGE-SOLUTIONS-4 */',
               '/* USI-PAGE-SOLUTIONS-5 */',
               '/* USI-PAGE-SOLUTIONS-6 */',
               '/* USI-PAGE-SOLUTIONS-7 */',
               '/* USI-PAGE-SOLUTIONS-8 */',
            ),
            array(
               $config_location,
               current_time('mysql'),
               $db_pass,
               $db_host,
               $db_name,
               $db_user,
               $wpdb->prefix,
               $instantiate_class,
            ), 
            fread($template_stream, filesize($template))
         );
         fclose($template_stream);
         $usi_page_cache = fopen(dirname(__FILE__) . '/usi-page-cache.php', 'w');
         fwrite($usi_page_cache, $template_content);
         fclose($usi_page_cache);
      }
   } // cache_file_generate();

   function config_section_header_cache() {
      echo '<p>' . __('The Page-Solutions caching functionality stores content in the WordPress database for quick access which improves performance by eliminating the overhead of loading and running WordPress for pages that have not changed recently. ', USI_Page_Solutions::TEXTDOMAIN) . '</p>' . PHP_EOL;
   } // config_section_header_cache();

   function config_section_header_collections() {
      if ('collections' == $this->active_tab) {
         new USI_Page_Solutions_Virtual_List(USI_Page_Solutions_Admin::$virtual_delete, USI_Page_Solutions_Admin::$virtual_edit);
      }
   } // config_section_header_collections();

   function config_section_header_preferences() {
      echo '<p>&nbsp;</p>' . PHP_EOL;
   } // config_section_header_preferences();

   function config_section_header_widgets() {
      echo '<p>' . 
         __("The Page-Solutions plugin extends the widget system and enables you to enhance your theme's widget areas and " .
            'create virtual widget collections that can be displayed in the enhanced widget areas on a page by page basis. ' .
            'Place a check mark before each widget area that you want to enhance. <i><b>Note</b> - Any widgets in previously unchecked ' .
            'widget areas that you now check and any widgets in previously checked enhaced widget areas that you now unchecked ' .
            'will be moved to the <b>Inactive Widgets</b> area.</i>', USI_Page_Solutions::TEXTDOMAIN) . 
      '</p>' . PHP_EOL . '<p>' .
         __('The following widget areas have been created by your theme and appear within your pages and posts:', USI_Page_Solutions::TEXTDOMAIN) . 
       '</p>' . PHP_EOL;
    } // config_section_header_widgets();

   function fields_sanitize($input) {
      $input = parent::fields_sanitize($input);
      if ('preferences' == $this->active_tab) {
         if (!empty(USI_Page_Solutions::$options['preferences']['enable-cache'])) {
            if (empty($input['preferences']['enable-cache'])) {
               self::index_file_restore();
               self::cache_file_generate();
            }
         }
      } else if ('cache' == $this->active_tab) {
         $root = $input['cache']['root-location'];
         if (!empty($root)) {
            $root_folder  = substr($root, 0, -9);
            $plugin_path  = plugin_dir_path(__FILE__);
            $include_path = str_replace('\\', '/', substr($plugin_path, strlen($root_folder)));
            $include_file = $include_path . 'usi-page-cache.php';
            $modification = "<?php /* USI-PAGE-SOLUTIONS */ @ include('$include_file'); ?>";
            if (is_file($root) && is_readable($root)) {
               if ($root_stream = fopen($root, 'r')) {
                  $first_line =  trim(fgets($root_stream), PHP_EOL);
                  fclose($root_stream);
                  if ($modification != $first_line) $this->index_file_modify();
               }
            }
         }
         if (!empty($input['cache']['clear-all-cache'])) {
            self::cache_all_clear();
            unset($input['cache']['clear-all-cache']);
         }
         self::cache_file_generate($input['cache']);
      } else if ('widgets' == $this->active_tab) {
         $options = array();
         foreach ($input['widgets'] as $name => & $value) {
            $options[$name] = $value;
         }
         update_option(USI_Page_Solutions::$option_name_base . '-enhanced', $options);
      }
      return($input);
   } // fields_sanitize();

   function filter_plugin_row_meta($links, $file) {
      if (false !== strpos($file, USI_Page_Solutions::TEXTDOMAIN)) {
         $links[0] = USI_WordPress_Solutions_Versions::link(
            $links[0], // Original link text;
            USI_Page_Solutions::NAME, // Title;
            USI_Page_Solutions::VERSION, // Version;
            USI_Page_Solutions::TEXTDOMAIN, // Text domain;
            __DIR__ // Folder containing plugin or theme;
         );
         $links[] = '<a href="https://www.usi2solve.com/donate/page-solutions" target="_blank">' . 
            __('Donate', USI_Page_Solutions::TEXTDOMAIN) . '</a>';
      }
      return($links);
   } // filter_plugin_row_meta();

   function index_file_modify() {
      $root = USI_Page_Solutions::$options['cache']['root-location'];
      if (!empty($root)) {
         if (is_file($root)) {
            if ($root_stream = fopen($root, 'r')) {
               $root_content = fread($root_stream, filesize($root));
               fclose($root_stream);
               if ('<?php /* USI-PAGE-SOLUTIONS */' != substr($root_content, 0, 30)) {
                  $root_folder  = substr($root, 0, -9);
                  $plugin_path  = plugin_dir_path(__FILE__);
                  $include_path = str_replace('\\', '/', substr($plugin_path, strlen($root_folder)));
                  $include_file = $include_path . 'usi-page-cache.php';
                  $modification = "<?php /* USI-PAGE-SOLUTIONS */ @ include('$include_file'); ?>";
                  $root_stream = fopen($root, 'w');
                  fwrite($root_stream, $modification . PHP_EOL . $root_content);
                  fclose($root_stream);
               }
            }
         }
      }
   } // index_file_modify();

   static function index_file_restore() {
      $root = !empty(USI_Page_Solutions::$options['cache']['root-location']) ? USI_Page_Solutions::$options['cache']['root-location'] : null;
      if ($root) {
         if (is_file($root)) {
            if ($root_stream = fopen($root, 'r')) {
               $root_content = fread($root_stream, filesize($root));
               fclose($root_stream);
               if ('<?php /* USI-PAGE-SOLUTIONS */' == substr($root_content, 0, 30)) {
                  $length = strlen($root_content);
                  if (false !== ($offset = strpos($root_content, PHP_EOL))) {
                     $restored_content = substr($root_content, $offset + strlen(PHP_EOL));
                     $root_stream = fopen($root, 'w');
                     fwrite($root_stream, $restored_content);
                     fclose($root_stream);
                  }
               }
            }
         }
      }
   } // index_file_restore();

   function page_render($options = null) {

      $options = array();

      if ('collections' == $this->active_tab) {
         if (USI_Page_Solutions_Admin::$virtual_add) {
            $options['title_buttons'] = 
               ' <a href="options-general.php?page=usi-page-solutions-virtual&action=add" class="page-title-action">' . 
               __('Add New', USI_Page_Solutions::TEXTDOMAIN) . '</a>';
         }
      }

      parent::page_render($options);

   } // page_render();

   function sections() {

      global $wpdb;

      $sections = array(
         'preferences' => array(
            'header_callback' => array($this, 'config_section_header_preferences'),
            'label' => 'Preferences',
            'settings' => array(
               'page-mru-max' => array(
                  'class' => 'small-text', 
                  'type' => 'number', 
                  'label' => 'Page MRU size',
                  'min' => 0,
                  'max' => 12,
                  'notes' => 'Maximum number of entries in the most recently used (MRU) page list. Enter 1 through 12 inclusive or 0 to disable the list. Defaults to <b>4</b> enties.',
               ),
               'post-mru-max' => array(
                  'class' => 'small-text', 
                  'type' => 'number', 
                  'label' => 'Post MRU size',
                  'min' => 0,
                  'max' => 12,
                  'notes' => 'Maximum number of entries in the most recently used (MRU) post list. Enter 1 through 12 inclusive or 0 to disable the list. Defaults to <b>4</b> enties.',
               ),
               'enable-cache' => array(
                  'type' => 'checkbox', 
                  'label' => 'Page cache',
                  'notes' => 'Enables page caching functionality. Another tab appears at the top of the page if this option is checked.',
               ),
               'enable-enhanced-areas' => array(
                  'type' => 'checkbox', 
                  'label' => 'Enhanced widget areas',
                  'notes' => 'Enables enhanced widget area functionality. Two tabs appear at the top of the page if this option is checked.',
               ),
               'enable-layout' => array(
                  'type' => 'checkbox', 
                  'label' => 'Enable layout enhancements',
                  'notes' => 'Enables layout enhancements.',
               ),
            ),
         ), // preferences;

         'capabilities' => USI_WordPress_Solutions_Capabilities::section(
            USI_Page_Solutions::NAME, 
            USI_Page_Solutions::PREFIX, 
            USI_Page_Solutions::TEXTDOMAIN,
            USI_Page_Solutions::$capabilities,
            USI_Page_Solutions::$options
         ), // capabilities;

      );

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-cache'])) {

         $sections['cache'] = array(
            'header_callback' => array($this, 'config_section_header_cache'),
            'label' => 'Cache Options',
            'settings' => array(
               'config-location' => array(
                  'class' => 'large-text', 
                  'type' => 'text', 
                  'label' => 'External configuration',
                  'notes' => 'Most WordPress installations store the database connection parameters in the <b>wp-config.php</b> file. To increase security, some experts recommend that you store theses parameters in a different file outside of your root folder and include this file in your <b>wp-config.php</b> file. If you follow this recommendation, please enter this file location path in the above field.',
               ),
               'root-location' => array(
                  'class' => 'large-text', 
                  'type' => 'text', 
                  'label' => 'index.php location',
                  'notes' => 'You can manually set the location of the root <b>index.php</b> file by entering the location above and clicking the <b>Save Cache Options</b> button. To force WordPress to scan for the actual location, clear the above field and click the <b>Save Cache Options</b> button, then access any page from another browser not in administrator mode.',
               ),
               'root-status' => array(
                  'class' => 'large-text', 
                  'type' => 'text', 
                  'label' => 'index.php status',
                  'notes' => self::$cache_config_warning,
                  'readonly' => true,
               ),
               'track-times' => array(
                  'type' => 'checkbox', 
                  'label' => 'Append cache times',
                  'notes' => 'Formats the page cache creation time, call-up time, expiration time and the Page-Solutions version as an HTML comment and appends it to the end of the page. It is recommended to use this feature as it uses negligible resources and gives valuable cache usage information.',
               ),
               'debug-ip' => array(
                  'type' => 'text', 
                  'label' => 'Debug IP address',
                  'notes' => 'Enter the IP address of the user you wish to track for debugging.',
               ),
               'clear-all-cache' => array(
                  'type' => 'checkbox', 
                  'label' => 'Clear all page cache information',
                  'notes' => 'If checked, all page cache information will be cleared. This is useful when a change is made that globally effects the site, like a menu change or template modification.',
               ),
             ),
         ); // cache;

         if (!empty(USI_Page_Solutions::$options['cache']['debug-ip'])) {
            $sections['cache']['settings']['debug-meta-data'] = array(
               'type' => 'checkbox', 
               'label' => 'DEBUG_META_DATA',
               'notes' => 'Writes the page meta data to the WordPress <b>' . $wpdb->prefix . 'USI_log</b> database table.',
            );
            $sections['cache']['settings']['debug-sql'] = array(
               'type' => 'checkbox', 
               'label' => 'DEBUG_SQL',
               'notes' => 'Writes SQL statements to the WordPress <b>' . $wpdb->prefix . 'USI_log</b> database table.',
            );
         } else {
            unset(USI_Page_Solutions::$options['cache']['debug-meta-data']);
            unset(USI_Page_Solutions::$options['cache']['debug-sql']);
         }

      }

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) {

         $sections['widgets'] = array(
            'header_callback' => array($this, 'config_section_header_widgets'),
            'label' => 'Enhanced Widget Areas',
            'settings' => array(),
         ); // widgets;

         $sections['collections'] = array(
            'header_callback' => array($this, 'config_section_header_collections'),
            'label' => 'Virtual Widget Collections',
            'settings' => array(),
            'submit' => '',
         ); // collections;

      }

      foreach ($sections as $section_name => & $section) {
         if ('capabilities' == $section_name) continue;
         foreach ($section['settings'] as $setting_name => & $setting) {
            if (!empty($setting['notes'])) {
               $setting['notes'] = '<p class="description">' . __($setting['notes'], USI_Page_Solutions::TEXTDOMAIN) . '</p';
            }
         }
      }
      unset($setting);

      return($sections);

   } // sections();

} // Class USI_Page_Solutions_Settings;

new USI_Page_Solutions_Settings();

// --------------------------------------------------------------------------------------------------------------------------- // ?>