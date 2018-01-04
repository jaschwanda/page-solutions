<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Layout {

   const VERSION = '0.0.2 (2018-01-04)';

   private $options = null;
   private $page_slug = 'usi-page-solutions-layout';
   private $section_id = null;

   function __construct() {
      if (!empty(USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-layout']) 
         || !empty(USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-enhanced-areas'])) 
         add_action('add_meta_boxes', array($this, 'action_add_meta_boxes'));
      add_action('admin_head', array($this, 'action_admin_head'));
      add_action('admin_init', array($this, 'action_admin_init'));
      add_action('admin_menu', array($this, 'action_admin_menu'));
      add_action('save_post', array($this, 'action_save_post'));
      add_action('widgets_init', array($this, 'action_widgets_init'), USI_Page_Solutions::WIDGETS_INIT_PRIORITY);
   } // __construct();

   function action_add_meta_boxes() {
      add_meta_box(
         'usi-page-solutions-layout-meta-box', // Meta box id;
         __('Page-Solutions Layout', USI_Page_Solutions::TEXTDOMAIN), // Title;
         array($this, 'render_meta_box'), // Render meta box callback;
         'page', // Screen type;
         'side', // Location on page;
         'low' // Priority;
      );
   } // action_add_meta_boxes();

   function action_admin_head() {
      $page = !empty($_GET['page']) ? $_GET['page'] : null;
      if ('usi-page-solutions-layout-links-edit' == $page) {
         echo '<style>' . PHP_EOL .
            '.form-table td{padding-bottom:2px; padding-top:2px;} /* 15px; */' . PHP_EOL .
            '.form-table th{padding-bottom:7px; padding-top:7px;} /* 20px; */' . PHP_EOL .
            'h2{margin-bottom:0.1em; margin-top:2em;} /* 1em; */' . PHP_EOL .
            '</style>' . PHP_EOL;
      } else if (('usi-page-solutions-layout-css-edit' == $page) || ('usi-page-solutions-layout-codes-edit' == $page)) {
         echo '<style>' . PHP_EOL .
            '.usi-page-solutions-options-mono-font{font-family:courier;}' . PHP_EOL .
            '</style>' . PHP_EOL;
      }

      if (!empty(USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-layout'])) {

         $screen = get_current_screen();

         if (('page' != $screen->id) || ('page' != $screen->post_type)) return;

         $screen->add_help_tab(array(
            'title' => __('Page-Solutions Layout', USI_Page_Solutions::TEXTDOMAIN),
            'id' => 'usi-page-solutions-layout',
            'content'  => 
'<p>' . __('The Page-Solutions plugin adds the following features to the WordPress system:', USI_Page_Solutions::TEXTDOMAIN) . '</p>'.
'<ul>' .
'</ul>'
         ));
      }

   } // action_admin_head();

   function action_admin_init() {

      add_settings_section(
         $this->section_id, // Section id;
         null, // Section title;
         null, // Render section callback;
         $this->page_slug // Settings page menu slug;
      );
      USI_Page_Solutions::post_meta_get(!empty($_GET['page_id']) ? (int)$_GET['page_id'] : 0);

      switch ($page = !empty($_GET['page']) ? $_GET['page'] : 'usi-page-solutions-layout-css-edit') {
      default:
         $key = 'css';
         $label_parent = __('Parent CSS', USI_Page_Solutions::TEXTDOMAIN);
         $label = __('CSS', USI_Page_Solutions::TEXTDOMAIN);
         break;
      case 'usi-page-solutions-layout-codes-edit':
         $key = 'codes_head';
         $label_parent = __('Parent Header Code', USI_Page_Solutions::TEXTDOMAIN);
         $label = __('Header Code', USI_Page_Solutions::TEXTDOMAIN);
         break;
      case 'usi-page-solutions-layout-links-edit':
         $key = 'styles';
         $section = 'styles_parent';
         if (USI_Page_Solutions::$post_meta['options'][$key . '_inherit']) {
            if (!empty(USI_Page_Solutions::$post_meta['layout'][$section])) {
               $styles = USI_Page_Solutions::$post_meta['layout'][$section];
               foreach ($styles as $style_value) {
                  $tokens = explode(' ', $style_value);
                  $style_id = $field_title = $tokens[0];
                  $args = array('id' => $style_id, 'section' => $section, 'readonly' => true);
                  add_settings_field(
                     $this->option_name . '[styles][' . $style_id . ']', // Option name;
                     $field_title, // Field title;
                     array($this, 'fields_render'), // Render field callback;
                     $this->page_slug, // Settings page menu slug;
                     $this->section_id, // Section id;
                     $args // Additional arguments;
                  );
               }
            }
         }
         $section = 'styles';
         $styles = USI_Page_Solutions::$post_meta['layout'][$key];
         $styles['new-style'] = 'new-style';
         foreach ($styles as $style_value) {
            $tokens = explode(' ', $style_value);
            $style_id = $field_title = $tokens[0];
            $args = array('id' => $style_id, 'section' => $section);
            if ('new-style' == $style_id) {
               $args['notes'] = '<i>unique-id &nbsp; style/path/name &nbsp; version &nbsp; media</i><p>&nbsp;</p>';
               $field_title = __('Add Style', USI_Page_Solutions::TEXTDOMAIN);
            }
            add_settings_field(
               $this->option_name . '[' . $section . '][' . $style_id . ']', // Option name;
               $field_title, // Field title;
               array($this, 'fields_render'), // Render field callback;
               $this->page_slug, // Settings page menu slug;
               $this->section_id, // Section id;
               $args // Additional arguments;
            );
         }

         $key = 'scripts';
         $section = 'scripts_parent';
         if (USI_Page_Solutions::$post_meta['options'][$key . '_inherit']) {
            if (!empty(USI_Page_Solutions::$post_meta['layout'][$section])) {
               $scripts = USI_Page_Solutions::$post_meta['layout'][$section];
               foreach ($scripts as $script_value) {
                  $tokens = explode(' ', $script_value);
                  $script_id = $field_title = $tokens[0];
                  $args = array('id' => $script_id, 'section' => $section, 'readonly' => true);
                  add_settings_field(
                     $this->option_name . '[scripts][' . $script_id . ']', // Option name;
                     $field_title, // Field title;
                     array($this, 'fields_render'), // Render field callback;
                     $this->page_slug, // Settings page menu slug;
                     $this->section_id, // Section id;
                     $args // Additional arguments;
                  );
               }
            }
         }
         $section = 'scripts';
         $scripts = USI_Page_Solutions::$post_meta['layout'][$key];
         $scripts['new-script'] = 'new-script';
         foreach ($scripts as $script_value) {
            $tokens = explode(' ', $script_value);
            $script_id = $field_title = $tokens[0];
            $args = array('id' => $script_id, 'section' => $section);
            if ('new-script' == $script_id) {
               $args['notes'] = '<i>unique-id &nbsp; script/path/name &nbsp; version &nbsp; footer</i>';
               $field_title = __('Add Script', USI_Page_Solutions::TEXTDOMAIN);
            }
            add_settings_field(
               $this->option_name . '[' . $section . '][' . $script_id . ']', // Option name;
               $field_title, // Field title;
               array($this, 'fields_render'), // Render field callback;
               $this->page_slug, // Settings page menu slug;
               $this->section_id, // Section id;
               $args // Additional arguments;
            );
         }
         break;
      }

      if ('usi-page-solutions-layout-links-edit' != $page) {
         if (USI_Page_Solutions::$post_meta['options'][$key . '_inherit']) {
            add_settings_field(
               $this->option_name . '[' . ($id = $key . '_parent') . ']', // Option name;
               $label_parent, // Field title;
               array($this, 'settings_field_render'), // Render field callback;
               $this->page_slug, // Settings page menu slug;
               $this->section_id, // Section id;
               array('id' => $id, 'type' => 'textarea', 'readonly' => true)
            );
         }
         
         add_settings_field(
            $this->option_name . '[' . ($id = $key) . ']', // Option name;
            $label, // Field title;
            array($this, 'settings_field_render'), // Render field callback;
            $this->page_slug, // Settings page menu slug;
            $this->section_id, // Section id;
            array('id' => $id, 'type' => 'textarea')
         );
         
         if ('codes_head' == $key) {
            $key = 'codes_foot';
            $label_parent = __('Parent Footer Code', USI_Page_Solutions::TEXTDOMAIN);
            $label = __('Footer Code', USI_Page_Solutions::TEXTDOMAIN);
            if (USI_Page_Solutions::$post_meta['options'][$key . '_inherit']) {
               add_settings_field(
                  $this->option_name . '[' . ($id = $key . '_parent') . ']', // Option name;
                  $label_parent, // Field title;
                  array($this, 'settings_field_render'), // Render field callback;
                  $this->page_slug, // Settings page menu slug;
                  $this->section_id, // Section id;
                  array('id' => $id, 'type' => 'textarea', 'readonly' => true)
               );
            }
         
            add_settings_field(
               $this->option_name . '[' . ($id = $key) . ']', // Option name;
               $label, // Field title;
               array($this, 'settings_field_render'), // Render field callback;
               $this->page_slug, // Settings page menu slug;
               $this->section_id, // Section id;
               array('id' => $id, 'type' => 'textarea')
            );
         }
      }

      register_setting(
         $this->section_id, // Settings group name, must match the group name in settings_fields();
         $this->option_name, // Option name;
         array($this, 'settings_fields_validate') // Sanitize field callback;
      );
   
   } // action_admin_init();

   function action_admin_menu() {
      add_submenu_page(null, null, null, 'manage_options', 'usi-page-solutions-layout-codes-edit', array($this, 'settings_page_render'));
      add_submenu_page(null, null, null, 'manage_options', 'usi-page-solutions-layout-css-edit', array($this, 'settings_page_render'));
      add_submenu_page(null, null, null, 'manage_options', 'usi-page-solutions-layout-links-edit', array($this, 'settings_page_render'));

      if (isset($_GET['post'])) {
         USI_Page_Solutions_Admin::action_load_post_php();
         $offset = 1;
      } else {
         $offset = 0;
      }
      if ((int)USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['page-mru-max']) {
         $pages = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-page');
         for ($ith = 0; $ith < count($pages) - 1; $ith++) {
            if (!empty($pages[$ith + $offset]['title'])) {
               $title = '&raquo; ' . $pages[$ith + $offset]['title'];
               $page_id = $pages[$ith + $offset]['page_id'];
               add_submenu_page('edit.php?post_type=page', $title, $title, 'manage_options', 'post.php?post=' . $page_id . '&action=edit');
            }
         }
      }
      if ((int)USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['post-mru-max']) {
         $posts = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-post');
         for ($ith = 0; $ith < count($posts) - 1; $ith++) {
            if (!empty($posts[$ith + $offset]['title'])) {
               $title = '&raquo; ' . $posts[$ith + $offset]['title'];
               $post_id = $posts[$ith + $offset]['page_id'];
               add_submenu_page('edit.php', $title, $title, 'manage_options', 'post.php?post=' . $post_id . '&action=edit');
            }
         }
      }
   } // action_admin_menu();

   function action_save_post($page_id) {
      if (!current_user_can('edit_page', $page_id)) {      
      } else if (wp_is_post_autosave($page_id)) {
      } else if (wp_is_post_revision($page_id)) {
      } else if (empty($_POST['usi-page-solutions-layout-nonce'])) {
      } else if (!wp_verify_nonce($_POST['usi-page-solutions-layout-nonce'], basename(__FILE__))) {
      } else {
         USI_Page_Solutions::post_meta_get();
         $collection_count = (int)(isset($_POST['usi-page-solutions-layout-enhanced-count']) ? $_POST['usi-page-solutions-layout-enhanced-count'] : 0);
         $options_widgets = array();
         for ($ith = 1; $ith <= $collection_count; $ith++) {
            $name = 'usi-page-solutions-layout-enhanced-' . $ith . '-id';
            $sidebar_id = isset($_POST[$name]) ? $_POST[$name] : null;
            $name = 'usi-page-solutions-layout-enhanced-' . $ith . '-count';
            $widget_count = (int)(isset($_POST[$name]) ? $_POST[$name] : 0);
            $sidebar_widgets = array();
            for ($jth = 1; $jth <= $widget_count; $jth++) {
               $name = 'usi-page-solutions-layout-enhanced-' . $ith . '-id-' . $jth;
               $virtual_id = (isset($_POST[$name]) ? $_POST[$name] : null);
               if ('0' != $virtual_id)
               $sidebar_widgets[] = $virtual_id;
            }
            $options_widgets[$sidebar_id] = $sidebar_widgets;
         }
         USI_Page_Solutions::$post_meta['options']['codes_head_inherit'] = !empty($_POST['usi-page-solutions-layout-codes-head-inherit']);
         USI_Page_Solutions::$post_meta['options']['codes_foot_inherit'] = !empty($_POST['usi-page-solutions-layout-codes-foot-inherit']);
         USI_Page_Solutions::$post_meta['options']['css_inherit']        = !empty($_POST['usi-page-solutions-layout-css-inherit']);
         USI_Page_Solutions::$post_meta['options']['scripts_inherit']    = !empty($_POST['usi-page-solutions-layout-scripts-inherit']);
         USI_Page_Solutions::$post_meta['options']['styles_inherit']     = !empty($_POST['usi-page-solutions-layout-styles-inherit']);
         USI_Page_Solutions::$post_meta['options']['widgets_inherit']    = !empty($_POST['usi-page-solutions-layout-widgets-inherit']);
         USI_Page_Solutions::$post_meta['widgets'] = $options_widgets;
         USI_Page_Solutions::post_meta_update();

         //$this->settings_fields_update_recursive(1, $page_id, 'codes_foot');
         //$this->settings_fields_update_recursive(1, $page_id, 'codes_head');
         //$this->settings_fields_update_recursive(1, $page_id, 'css');
         //$this->settings_fields_update_recursive(1, $page_id, 'scripts');
         //$this->settings_fields_update_recursive(1, $page_id, 'styles');
      }

   } // action_save_post();

   function action_widgets_init() {
      $this->option_name = $this->section_id = 'usi-page-solutions-options-dummy-' . get_current_user_id();
      $this->options = get_option($this->option_name);
   } // action_widgets_init();

   function render_meta_box($post) {

      global $wp_registered_sidebars;

      wp_nonce_field(basename(__FILE__), 'usi-page-solutions-layout-nonce');

      $disabled = ($post->post_parent ? null : ' disabled');

      $html = USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-layout'] ?
         '<p>' .
           '<a class="button button-secondary" href="options-general.php?page=usi-page-solutions-layout-codes-edit&page_id=' . $post->ID . '">Code</a> &nbsp; ' .
           '<a class="button button-secondary" href="options-general.php?page=usi-page-solutions-layout-css-edit&page_id=' . $post->ID . '">CSS</a> &nbsp; ' .
           '<a class="button button-secondary" href="options-general.php?page=usi-page-solutions-layout-links-edit&page_id=' . $post->ID . '">Links</a> &nbsp; ' .
         '</p>' : '';

      if (USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-enhanced-areas']) {

         USI_Page_Solutions::post_meta_get();

         $collection_index = 0;

         $options_enhanced   = get_option(USI_Page_Solutions::$option_name_base . '-enhanced');
         $options_virtual    = USI_Page_Solutions::$options_virtual;

         $codes_foot_inherit = USI_Page_Solutions::$post_meta['options']['codes_foot_inherit'];
         $codes_head_inherit = USI_Page_Solutions::$post_meta['options']['codes_head_inherit'];
         $css_inherit        = USI_Page_Solutions::$post_meta['options']['css_inherit'];
         $scripts_inherit    = USI_Page_Solutions::$post_meta['options']['scripts_inherit'];
         $styles_inherit     = USI_Page_Solutions::$post_meta['options']['styles_inherit'];
         $widgets_inherit    = USI_Page_Solutions::$post_meta['options']['widgets_inherit'];

         $options_widgets    = USI_Page_Solutions::$post_meta['widgets'];

         foreach ($wp_registered_sidebars as $id => $sidebar) {

            if ($id == $options_virtual[0]['id']) break;

            if (!empty($options_enhanced[$id])) {

               $order_by = 0;
               $html .= '<p>' . $sidebar['name'] . '<br />';
               $html .= '<input name="usi-page-solutions-layout-enhanced-' . ++$collection_index . '-id" type="hidden" value="' . $id . '" />';

               if (isset($options_widgets[$id])) {
                  for ($ith = 0; $ith < count($options_widgets[$id]); $ith++) {
                     $html .= '<select name="usi-page-solutions-layout-enhanced-' . $collection_index . '-id-' . ++$order_by . '" style="width:100%;">';
                     $html .= '<option ' . ((0 == $options_virtual[0]['id']) ? 'selected ' : '') . 'value="0">-- Remove Item --</option>';
                     for ($jth = 0; $jth < count($options_virtual); $jth++) {
                        $html .= '<option ' . (($options_virtual[$jth]['id'] == $options_widgets[$id][$ith]) ? 'selected ' : '') . 'value="' . 
                           $options_virtual[$jth]['id'] . '">' . $options_virtual[$jth]['name'] . '</option>';
                     }
                     $html .= '</select>';
                  }
               }

               $html .= '<select name="usi-page-solutions-layout-enhanced-' . $collection_index . '-id-' . ++$order_by . '" style="width:100%;">';
               $html .= '<option selected value="0">-- Select Item --</option>';
               for ($ith = 0; $ith < count($options_virtual); $ith++) {
                  $html .= '<option value="' . $options_virtual[$ith]['id'] . '">' . 
                     $options_virtual[$ith]['name'] . '</option>';
               }
               $html .= '</select>';
               $html .= '<input name="usi-page-solutions-layout-enhanced-' . $collection_index . '-count" type="hidden" value="' . $order_by . '" />';

            }
         }
         $html .= '<input name="usi-page-solutions-layout-enhanced-count" type="hidden" value="' . $collection_index . '" />';
      }

      echo $html;

      if (USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-layout'] 
         || USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-enhanced-areas']) echo '<p>';

      if (USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-layout']) {
?>
  <input id="usi-page-solutions-layout-codes-head-inherit"<?php checked($codes_head_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-codes-head-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-codes-head-inherit"><?php _e('Inherit header code from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-codes-foot-inherit"<?php checked($codes_foot_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-codes-foot-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-codes-foot-inherit"><?php _e('Inherit footer code from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-css-inherit"<?php checked($css_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-css-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-css-inherit"><?php _e('Inherit CSS from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-styles-inherit"<?php checked($styles_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-styles-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-styles-inherit"><?php _e('Inherit style links from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-scripts-inherit"<?php checked($scripts_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-scripts-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-scripts-inherit"><?php _e('Inherit script links from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
<?php
      }

      if (USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-layout'] 
         && USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-enhanced-areas']) echo '<br />';

      if (USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-enhanced-areas']) {
?>
  <input id="usi-page-solutions-layout-widgets-inherit"<?php checked($widgets_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-widgets-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-widgets-inherit"><?php _e('Inherit widgets from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
<?php
      }

      if (USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-layout'] 
         || USI_Settings::$options[USI_Page_Solutions::PREFIX]['preferences']['enable-enhanced-areas']) echo '</p>';

   } // render_meta_box();

   function settings_field_render($args) {
      $id = $args['id'];
      $name = $this->option_name . '[' . $id . ']';
      switch ($args['type']) {
      case 'hidden';
         echo '<input name="' . $name . '" type="hidden" value="' . $this->options[$id] . '" />';
         break;
      case 'textarea';
         echo '<textarea id="' . $name . '" class="large-text usi-page-solutions-options-mono-font" cols="80" name="' . $name . '"' . 
            (!empty($args['readonly']) ? ' readonly' : '') . ' rows="10">' . $this->options[$id] . '</textarea>';
         break;
      }
   } // settings_field_render();

   function settings_fields_validate($input) {
      if (!current_user_can('manage_options')) {
         $error = __('You are not authorized to perform that operation.', USI_Page_Solutions::TEXTDOMAIN);
      } else {
         $key = !empty($_POST['usi-page-solutions-layout-key']) ? $_POST['usi-page-solutions-layout-key'] : null;
         $page_id = !empty($_POST['usi-page-solutions-layout-post-id']) ? (int)$_POST['usi-page-solutions-layout-post-id'] : 0;
         switch ($key) {
         case 'codes': $key = 'codes_head';
         case 'css': 
            do {
               USI_Page_Solutions::post_meta_get($page_id);
               USI_Page_Solutions::$post_meta['layout'][$key] = $input[$key];
               USI_Page_Solutions::post_meta_update();
               $this->settings_fields_update_recursive(1, $page_id, $key);
               $key = ('codes_head' == $key) ? 'codes_foot' : null;
            } while ($key);
            $error = null;
            break;
         case 'links': 
            $key = 'scripts';
            USI_Page_Solutions::post_meta_get($page_id);
            $scripts = $input['scripts'];
            $new_scripts = array();
            foreach($scripts as $dummy => $value) {
               if (!empty($value)) {
                  $value = preg_replace('/\s+/', ' ', $value);
                  $tokens = explode(' ', $value);
                  $new_scripts[$tokens[0]] = $value;
               }
            }
            unset($new_scripts['new-script']);
            USI_Page_Solutions::$post_meta['layout'][$key] = $input[$key] = $new_scripts;;
            USI_Page_Solutions::post_meta_update();
            $this->settings_fields_update_recursive(1, $page_id, $key);

            $key = 'styles';
            USI_Page_Solutions::post_meta_get($page_id);
            $styles = $input['styles'];
            $new_styles = array();
            foreach($styles as $dummy => $value) {
               if (!empty($value)) {
                  $value = preg_replace('/\s+/', ' ', $value);
                  $tokens = explode(' ', $value);
                  $new_styles[$tokens[0]] = $value;
               }
            }
            unset($new_styles['new-style']);
            USI_Page_Solutions::$post_meta['layout'][$key] = $input[$key] = $new_styles;;
            USI_Page_Solutions::post_meta_update();
            $this->settings_fields_update_recursive(1, $page_id, $key);
            $error = null;
            break;
         default:
            $error = __('Internal error, invalid layout key.', USI_Page_Solutions::TEXTDOMAIN);
         }
      }

      if ($error) {
         add_settings_error(
            $this->page_slug, // Slug title;
            esc_attr('settings-error'), // Message slug name identifier;
            $error, // Message text;
            'error' // Message type;
         );
         unset($input);
      }
      return($input);
   } // settings_fields_validate();

   private function settings_fields_update_recursive($level, $page_id, $key, $parent_value = null) {

      // IF this is starting level of recursion;
      if (1 == $level) {
         // Get highest parent if any, otherwise start with self;
         while ($parent_id = wp_get_post_parent_id($page_id)) $page_id = $parent_id;
      }

      // May be redundant, but get post meta for this page;
      USI_Page_Solutions::post_meta_get($page_id);

      // Clear parent's value if this page doesn't inherit;
      if (empty(USI_Page_Solutions::$post_meta['options'][$key . '_inherit'])) $parent_value = null;

      // Load parent's value or null if not inherited or this page has no parent;
      USI_Page_Solutions::$post_meta['layout'][$key . '_parent'] = $parent_value;

      // Update this page's post meta data;
      USI_Page_Solutions::post_meta_update();

      // Use this page's value as children's parent value;
      $childrens_parent_value = USI_Page_Solutions::$post_meta['layout'][$key];

      // Get array of children, if any;
      global $wpdb;
      $SAFE_table_name = $wpdb->prefix . 'posts';
      $children = $wpdb->get_results(
         $wpdb->prepare("SELECT `ID` FROM `$SAFE_table_name` WHERE (`post_parent` = %d) AND (`post_type` = 'page')", $page_id), ARRAY_A
      );
      // USI_Page_Cache::log(__METHOD__.':'.__LINE__.':children=' . print_r($children, true));

      // Load this page's value into children and propagate down to all descendants;
      for ($ith = 0; $ith < count($children); $ith++) {
         $this->settings_fields_update_recursive($level + 1, $children[$ith]['ID'], $key, $childrens_parent_value);
      }

   } // settings_fields_update_recursive();

   function settings_page_render() {

      if (!current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));
      switch ($page = isset($_GET['page']) ? $_GET['page'] : 'usi-page-solutions-layout-css-edit') {
      default:
         $key = 'css';
         $header = __('Edit Page CSS', USI_Page_Solutions::TEXTDOMAIN);
         $this->options['css'] = USI_Page_Solutions::$post_meta['layout']['css'];
         $this->options['css_parent'] = USI_Page_Solutions::$post_meta['layout']['css_parent'];
         break;
      case 'usi-page-solutions-layout-codes-edit':
         $key = 'codes';
         $header = __('Edit Page Code', USI_Page_Solutions::TEXTDOMAIN);
         $this->options['codes_head'] = USI_Page_Solutions::$post_meta['layout']['codes_head'];
         $this->options['codes_head_parent'] = USI_Page_Solutions::$post_meta['layout']['codes_head_parent'];
         $this->options['codes_foot'] = USI_Page_Solutions::$post_meta['layout']['codes_foot'];
         $this->options['codes_foot_parent'] = USI_Page_Solutions::$post_meta['layout']['codes_foot_parent'];
         break;
      case 'usi-page-solutions-layout-links-edit':
         $key = 'links';
         $header = __('Edit Links', USI_Page_Solutions::TEXTDOMAIN);
         $this->options['scripts'] = USI_Page_Solutions::$post_meta['layout']['scripts'];
         $this->options['scripts_parent'] = USI_Page_Solutions::$post_meta['layout']['scripts_parent'];
         $this->options['styles'] = USI_Page_Solutions::$post_meta['layout']['styles'];
         $this->options['styles_parent'] = USI_Page_Solutions::$post_meta['layout']['styles_parent'];
      }

      $page_id = (int)(isset($_GET['page_id']) ? $_GET['page_id'] : 0);
      $title = get_the_title($page_id);
?>
<div class="wrap">
  <h1><?php echo $header . ' - ' . $title; ?></h1>
  <form method="post" action="options.php">
    <input name="usi-page-solutions-layout-post-id" type="hidden" value="<?php echo $page_id; ?>" />
    <input name="usi-page-solutions-layout-key" type="hidden" value="<?php echo $key; ?>" />
    <?php settings_fields($this->section_id); do_settings_sections($this->page_slug); ?>
    <div class="submit">'
      <?php submit_button(__('Save'), 'primary', 'submit', false); ?> &nbsp; 
      <?php submit_button(__('Back To Page'), 'secondary', 'usi-page-solutions-layout-back-to-page', false); ?>
    </div>
  </form>
</div>
<script>
jQuery(document).ready(function($) {
   $('#usi-page-solutions-layout-back-to-page').click(function() {
      window.location.href = 'post.php?post=<?php echo $page_id; ?>&action=edit';
      return(false);
   });
});
</script>
<?php

   } // settings_page_render();

   function fields_render($args){
      $id = $args['id'];
      $section = $args['section'];
      echo '<input class="large-text" name="' . $this->option_name . '[' . $section . '][' . $id . ']"' .
         (!empty($args['readonly']) ? ' readonly' : '') . ' type="text" value="' . 
         esc_attr(!empty($this->options[$section][$id]) ? $this->options[$section][$id] : null) . '" />';
      if (isset($args['notes'])) echo $args['notes'];
   } // fields_render();
      
} // USI_Page_Solutions_Layout;

new USI_Page_Solutions_Layout();

// --------------------------------------------------------------------------------------------------------------------------- // ?>