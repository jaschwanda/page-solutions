<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Settings extends USI_WordPress_Solutions_Settings {

   const VERSION = '1.7.1 (2023-01-25)';

   protected $is_tabbed = true;

   function __construct() {

      parent::__construct(
         [
            'name' => USI_Page_Solutions::NAME, 
            'prefix' => USI_Page_Solutions::PREFIX, 
            'text_domain' => USI_Page_Solutions::TEXTDOMAIN,
            'options' => USI_Page_Solutions::$options,
            'capabilities' => USI_Page_Solutions::$capabilities,
            'file' => str_replace('-settings', '', __FILE__), // Plugin main file, this initializes capabilities on plugin activation;
         ]
      );

   } // __construct();

   static function cache_all_clear() {
      global $wpdb;
      $SAFE_table_name = $wpdb->prefix . 'posts';
      $pages = $wpdb->get_results("SELECT `ID` FROM `$SAFE_table_name` WHERE (`post_type` = 'page')", ARRAY_A);

      for ($ith = 0; $ith < count($pages); $ith++) {
         $page_id = $pages[$ith]['ID'];
         $meta_value = USI_Page_Solutions::meta_value_get($page_id);
         $meta_value['cache']['updated'] = 
         $meta_value['cache']['valid_until'] = USI_Page_Cache::DATE_ALPHA;
         $meta_value['cache']['html'] = '';
         $meta_value['cache']['size'] = 0;
         USI_Page_Solutions::meta_value_put($meta_value);
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

   static function cache_file_generate($root_folder, $cache = null) {
      global $wpdb;
      if (empty($cache['config-location'])) {
         $config_location = null;
         $db_pass = "'" . DB_PASSWORD . "'";
         $db_host = "'" . DB_HOST . "'";
         $db_name = "'" . DB_NAME . "'";
         $db_user = "'" . DB_USER . "'";
      } else {
         $config_location = PHP_EOL . "@require_once '" . $cache['config-location'] . "';" . PHP_EOL;
         $db_pass = 'DB_PASSWORD';
         $db_host = 'DB_HOST';
         $db_name = 'DB_NAME';
         $db_user = 'DB_USER';
      }

      $option = empty($cache['track-times']) ? 'false' : 'true';
      if (!empty($cache['session'])) {
         $option .= ", '" . $cache['session'] . "'";
      }
      if ('false' == $option) $option = null;

      $template = dirname(__FILE__) . '/usi-page-cache-template.php';

      if (is_file($template) && is_readable($template)) {
         try {
            $template_stream  = fopen($template, 'r');
            $template_content = str_replace(
               [
                  '/* USI-PAGE-SOLUTIONS-1 */',
                  '/* USI-PAGE-SOLUTIONS-2 */',
                  '/* USI-PAGE-SOLUTIONS-3 */',
               ],
               [
               /* 1 */ WPMU_PLUGIN_DIR . '/usi.php',
               /* 2 */ current_time('mysql'),
               /* 3 */ $option,
               ], 
               fread($template_stream, filesize($template))
            );
            fclose($template_stream);
            // $usi_page_cache = fopen(dirname(__FILE__) . '/usi-page-cache.php', 'w');
            //$usi_page_cache = fopen($root_folder . '/index-cache.php', 'w');
            //fwrite($usi_page_cache, $template_content);
            //fclose($usi_page_cache);
         } catch(Exception $e) {
         }
      }

   } // cache_file_generate();

   function config_section_header_cache() {
      echo '<p style="text-align:justify;">' . __('The Page-Solutions caching functionality stores content in the WordPress database for quick access which improves performance by eliminating the overhead of loading and running WordPress for pages that have not changed recently. ', USI_Page_Solutions::TEXTDOMAIN) . '</p>' . PHP_EOL;
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
      $input       = parent::fields_sanitize($input);
      $root_folder = empty($input['cache']['root-location']) ? $_SERVER['DOCUMENT_ROOT'] : rtrim(rtrim($input['cache']['root-location'], '/'), '\\');
      $root_index  = $root_folder . DIRECTORY_SEPARATOR . 'index.php';
      $root_cache  = $root_folder . DIRECTORY_SEPARATOR . 'index-cache.php';
      $log         = (USI_Page_Solutions::DEBUG_SANITZ == (USI_Page_Solutions::DEBUG_SANITZ & USI_WordPress_Solutions_Diagnostics::get_log(USI_Page_Solutions::$options)));
      if ($log) usi::log('$this->active_tab=', $this->active_tab, ' $root_folder=', $root_folder, ' $root_index=', $root_index, ' $root_cache=', $root_cache, '\2n$input=', $input);
      if ('preferences' == $this->active_tab) {
         if (!empty(USI_Page_Solutions::$options['preferences']['enable-cache'])) {
            if (empty($input['preferences']['enable-cache'])) {
               self::index_file_restore($root_index, $log);
               self::cache_file_generate($root_folder);
            }
         }
      } else if ('cache' == $this->active_tab) {
         if (!empty($input['cache']['clear-all-cache'])) {
            self::cache_all_clear();
            unset($input['cache']['clear-all-cache']);
         }
         if (!empty($input['cache']['update-cache-config'])) {
            $root         = $root_folder . '/index.php';
            $modification = "<?php /* USI-PAGE-SOLUTIONS */ @ include('index-cache.php'); ?>";
            if (is_file($root) && is_readable($root)) {
               if ($root_stream = fopen($root, 'r')) {
                  $first_line =  trim(fgets($root_stream), PHP_EOL);
                  fclose($root_stream);
                  if ($modification != $first_line) $this->index_file_modify($root_index, $log);
               }
            }
            $input['cache']['root-location'] = $root_folder;
            unset($input['cache']['update-cache-config']);
         }
         if (!empty($input['diagnostics']['DEBUG_CACHE']) && !empty($input['diagnostics']['session']) && !empty($input['diagnostics']['DEBUG_CACHE'])) {
            $input['cache']['session'] = $input['diagnostics']['session'];
         }
         self::cache_file_generate($root_folder, $input['cache']);
      } else if ('widgets' == $this->active_tab) {
         $options = [];
         foreach ($input['widgets'] as $name => & $value) {
            $options[$name] = $value;
         }
         update_option(USI_Page_Solutions::$option_name_base . '-enhanced', $options);
      }
      return $input;
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
      return $links;
   } // filter_plugin_row_meta();

   function index_file_modify($root_index, $log = false) {
usi::log('bail!');return;
      if (!is_file($root_index)) {
         if ($log) usi::log('not a file:', $root_index);
      } else {
         if ($root_stream = fopen($root_index, 'r')) {
            $root_content = fread($root_stream, filesize($root_index));
            fclose($root_stream);
            if ('<?php /* USI-PAGE-SOLUTIONS */' != substr($root_content, 0, 30)) {
               $root_folder  = substr($root, 0, -9);
               $modification = "<?php /* USI-PAGE-SOLUTIONS */ @ include('index-cache.php'); ?>";
               $root_stream  = fopen($root_index, 'w');
               $bytes        = fwrite($root_stream, $modification . PHP_EOL . $root_content);
               if ($log) usi::log('file=', $root_index, ' $bytes=', ($bytes ? $bytes : 'failed'));
               fclose($root_stream);
            }
         }
      }
   } // index_file_modify();

   static function index_file_restore($root_index, $log) {
usi::log('bail!');return;
      if (!is_file($root_index)) {
         if ($log) usi::log('not a file:', $root_index);
      } else {
         if ($root_stream = fopen($root_index, 'r')) {
            $root_content = fread($root_stream, filesize($root_index));
            fclose($root_stream);
            if ('<?php /* USI-PAGE-SOLUTIONS */' == substr($root_content, 0, 30)) {
               if (false !== ($offset = strpos($root_content, PHP_EOL))) {
                  $restored_content = substr($root_content, $offset + strlen(PHP_EOL));
                  $root_stream      = fopen($root_index, 'w');
                  $bytes            = fwrite($root_stream, $restored_content);
                  if ($log) usi::log('file=', $root_index, ' $bytes=', ($bytes ? $bytes : 'failed'));
                  fclose($root_stream);
               }
            }
         }
      }
   } // index_file_restore();

   function page_render($options = null) {

      $options = [];

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

      $sections = [
         'preferences' => [
            'header_callback' => [$this, 'config_section_header_preferences'],
            'label' => 'Preferences',
            'localize_labels' => 'yes',
            'localize_notes' => 3, // <p class="description">__()</p>;
            'settings' => [
               'page-mru-max' => [
                  'f-class' => 'small-text', 
                  'type' => 'number', 
                  'label' => 'Page MRU size',
                  'min' => 0,
                  'max' => 12,
                  'notes' => 'Maximum number of entries in the most recently used (MRU) page list. Enter 1 through 12 inclusive or 0 to disable the list. Defaults to <b>4</b> enties.',
               ],
               'post-mru-max' => [
                  'f-class' => 'small-text', 
                  'type' => 'number', 
                  'label' => 'Post MRU size',
                  'min' => 0,
                  'max' => 12,
                  'notes' => 'Maximum number of entries in the most recently used (MRU) post list. Enter 1 through 12 inclusive or 0 to disable the list. Defaults to <b>4</b> enties.',
               ],
               'enable-cache' => [
                  'type' => 'checkbox', 
                  'label' => 'Page cache',
                  'notes' => 'Enables page caching functionality. Another tab appears at the top of the page if this option is checked.',
               ],
               'enable-enhanced-areas' => [
                  'type' => 'checkbox', 
                  'label' => 'Enhanced widget areas',
                  'notes' => 'Enables enhanced widget area functionality. Two tabs appear at the top of the page if this option is checked.',
               ],
               'enable-layout' => [
                  'type' => 'checkbox', 
                  'label' => 'Enable layout enhancements',
                  'notes' => 'Enables addition of custom code, CSS, styles and scripts.',
               ],
               'global-header' => [
                  'f-class' => 'large-text', 
                  'label' => 'Global Header Content',
                  'rows' => 5,
                  'type' => 'textarea', 
                  'notes' => 'The above content, if given, is emitted after the <i>&lt;body&gt;</i> tag.',
               ],
            ],
         ], // preferences;

         'capabilities' => new USI_WordPress_Solutions_Capabilities($this),

         'diagnostics' => new USI_WordPress_Solutions_Diagnostics($this, 
            [
               'DEBUG_CACHE' => [
                  'value' => USI_Page_Solutions::DEBUG_CACHE,
                  'notes' => 'Log USI_Page_Cache::cache() method. <b>Note</b> - you must resave the Cached Options tab to use this feature.',
               ],
               'DEBUG_HTML' => [
                  'value' => USI_Page_Solutions::DEBUG_HTML,
                  'notes' => 'Log meta_value_[get|put]() methods.',
               ],
               'DEBUG_SANITZ' => [
                  'value' => USI_Page_Solutions::DEBUG_SANITZ,
                  'notes' => 'Log USI_Page_Solutions::fields_sanitize() method.',
               ],
            ]
         ),

      ];

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-cache'])) {

         $sections['cache'] = [
            'header_callback' => [$this, 'config_section_header_cache'],
            'label' => 'Cache Options',
            'settings' => [
               'config-location' => [
                  'f-class' => 'large-text', 
                  'type' => 'text', 
                  'label' => 'External configuration',
                  'notes' => 'Most WordPress installations store the database connection parameters in the <b>wp-config.php</b> file. To increase security, some experts recommend that you store theses parameters in a different file outside of your root folder and include this file in your <b>wp-config.php</b> file. If you follow this recommendation, please enter this file location path in the above field.',
               ],
               'root-location' => [
                  'f-class' => 'large-text', 
                  'type' => 'text', 
                  'label' => 'Location of index.php',
                  'notes' => 'Location of the the WordPress root <b>index.php</b> file, defaults to <b>' . $_SERVER['DOCUMENT_ROOT'] . '</b> .',
               ],
               'update-cache-config' => [
                  'type' => 'checkbox', 
                  'label' => 'Update cache configuration',
                  'notes' => 'Check this box whenever the above parameters are modified.',
               ],
               'track-times' => [
                  'type' => 'checkbox', 
                  'label' => 'Prepend cache times',
                  'notes' => 'Formats the page cache creation time, call-up time, expiration time and the Page-Solutions version as an HTML comment and prepends it to the top of the page. It is recommended to use this feature as it uses negligible resources and gives valuable cache usage information.',
               ],
               'clear-all-cache' => [
                  'type' => 'checkbox', 
                  'label' => 'Clear all page cache information',
                  'notes' => 'If checked, all page cache information will be cleared. This is useful when a change is made that globally effects the site, like a menu change or template modification.',
               ],
            ],
         ]; // cache;

      }

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) {

         global $wp_registered_sidebars;

         $sections['widgets'] = [
            'header_callback' => [$this, 'config_section_header_widgets'],
            'label' => 'Enhanced Widget Areas',
            'settings' => [],
         ]; // widgets;

         $disabled = USI_Page_Solutions_Admin::$enhanced_edit ? null : 'disabled';

         foreach ($wp_registered_sidebars as $id => $sidebar) {
            // Skip virtual widget areas created by Page-Solutions;
            if ($id == (USI_Page_Solutions::$options_virtual[0]['id'] ?? null)) break;
            $sections['widgets']['settings'][$id] = [
               'type' => 'checkbox', 
               'disabled' => $disabled, 
               'label' => $sidebar['name'], 
               'notes' => !empty($sidebar['description']) ? ' (<i>' . $sidebar['description'] . '</i>)' : ''
            ];
         }

         $sections['collections'] = [
            'header_callback' => [$this, 'config_section_header_collections'],
            'label' => 'Virtual Widget Collections',
            'settings' => [],
            'submit' => '',
         ]; // collections;

      }
      return $sections;

   } // sections();

} // Class USI_Page_Solutions_Settings;

// --------------------------------------------------------------------------------------------------------------------------- // ?>