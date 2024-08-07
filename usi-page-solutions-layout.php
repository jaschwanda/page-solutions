<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Layout {

   const VERSION = '1.7.2 (2023-03-16)';

   private $options = null;
   private $page_id = 0;
   private $page_slug = 'usi-page-solutions-layout';
   private $section_id = null;

   function __construct() {

      $this->page_id = !empty($_GET['page_id']) ? (int)$_GET['page_id'] : 0;

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout']) 
         || !empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) 
         add_action('add_meta_boxes', [$this, 'action_add_meta_boxes']);

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout'])) {
         add_action('admin_head', [$this, 'action_admin_head']);
      }
      if ($this->page_id) add_action('admin_init', [$this, 'action_admin_init']);
      add_action('admin_menu', [$this, 'action_admin_menu']);
      add_action('save_post', [$this, 'action_save_post']);
      add_action('widgets_init', [$this, 'action_widgets_init'], USI_Page_Solutions::WIDGETS_INIT_PRIORITY);

   } // __construct();

   function action_add_meta_boxes() {
      add_meta_box(
         'usi-page-solutions-layout-meta-box', // Meta box id;
         __('Page-Solutions Layout', USI_Page_Solutions::TEXTDOMAIN), // Title;
         [$this, 'render_meta_box'], // Render meta box callback;
         'page', // Screen type;
         'side', // Location on page;
         'low' // Priority;
      );
   } // action_add_meta_boxes();

   function action_admin_head() {

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout'])) {

         $screen = get_current_screen();

         if (('page' != $screen->id) || ('page' != $screen->post_type)) return;

         $screen->add_help_tab([
            'title' => __('Page-Solutions Layout', USI_Page_Solutions::TEXTDOMAIN),
            'id' => 'usi-page-solutions-layout',
            'content'  => 
'<p>' . __('The Page-Solutions plugin adds the following features to the WordPress system:', USI_Page_Solutions::TEXTDOMAIN) . '</p>'.
'<ul>' .
'</ul>'
         ]);
      }

   } // action_admin_head();

   function action_admin_init() {

      // add_settings_section(
   
   } // action_admin_init();

   function action_admin_menu() {

      if (isset($_GET['post'])) {
         USI_Page_Solutions_Admin::action_load_post_php();
         $offset = 1;
      } else {
         $offset = 0;
      }
      if ((int)USI_Page_Solutions::$options['preferences']['page-mru-max']) {
         $pages = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-page');
         if (is_array($pages)) {
            for ($ith = 0; $ith < count($pages) - 1; $ith++) {
               if (!empty($pages[$ith + $offset]['title'])) {
                  $title = '&raquo; ' . $pages[$ith + $offset]['title'];
                  $page_id = $pages[$ith + $offset]['page_id'];
                  add_submenu_page('edit.php?post_type=page', $title, $title, 'manage_options', 'post.php?post=' . $page_id . '&action=edit');
               }
            }
         }
      }
      if ((int)USI_Page_Solutions::$options['preferences']['post-mru-max']) {
         $posts = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-post');
         if (is_array($posts)) {
            for ($ith = 0; $ith < count($posts) - 1; $ith++) {
               if (!empty($posts[$ith + $offset]['title'])) {
                  $title = '&raquo; ' . $posts[$ith + $offset]['title'];
                  $post_id = $posts[$ith + $offset]['page_id'];
                  add_submenu_page('edit.php', $title, $title, 'manage_options', 'post.php?post=' . $post_id . '&action=edit');
               }
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

         $meta_value = USI_Page_Solutions::meta_value_get($page_id);
         $collection_count = (int)(isset($_POST['usi-page-solutions-layout-enhanced-count']) ? $_POST['usi-page-solutions-layout-enhanced-count'] : 0);
         $widgets = [];

         for ($ith = 1; $ith <= $collection_count; $ith++) {
            $name = 'usi-page-solutions-layout-enhanced-' . $ith . '-id';
            $sidebar_id = isset($_POST[$name]) ? $_POST[$name] : null;
            $name = 'usi-page-solutions-layout-enhanced-' . $ith . '-count';
            $widget_count = (int)(isset($_POST[$name]) ? $_POST[$name] : 0);
            $sidebar_widgets = [];
            for ($jth = 1; $jth <= $widget_count; $jth++) {
               $name = 'usi-page-solutions-layout-enhanced-' . $ith . '-id-' . $jth;
               $virtual_id = (isset($_POST[$name]) ? $_POST[$name] : null);
               if ('0' != $virtual_id) $sidebar_widgets[] = $virtual_id;
            }
            $widgets[$sidebar_id] = $sidebar_widgets;
         }

         $meta_value['options']['codes_head_inherit'] = !empty($_POST['usi-page-solutions-layout-codes-head-inherit']);
         $meta_value['options']['codes_foot_inherit'] = !empty($_POST['usi-page-solutions-layout-codes-foot-inherit']);
         $meta_value['options']['css_inherit']        = !empty($_POST['usi-page-solutions-layout-css-inherit']);
         $meta_value['options']['scripts_inherit']    = !empty($_POST['usi-page-solutions-layout-scripts-inherit']);
         $meta_value['options']['styles_inherit']     = !empty($_POST['usi-page-solutions-layout-styles-inherit']);
         $meta_value['options']['widgets_inherit']    = !empty($_POST['usi-page-solutions-layout-widgets-inherit']);
         $meta_value['widgets'] = $widgets;

         self::update_recursively(null, $meta_value);

      }
   } // action_save_post();

   static function update_recursively($parent_meta_value, $meta_value) {

      if (!$parent_meta_value) {
         $parent_id = wp_get_post_parent_id($meta_value['post_id']);
         if ($parent_id) {
            $parent_meta_value = USI_Page_Solutions::meta_value_get($parent_id);
         }
      }

      if ($parent_meta_value) {

         if (empty($meta_value['options']['codes_head_inherit'])) {
            $meta_value['layout']['codes_head_parent'] = null;
         } else {
            $meta_value['layout']['codes_head_parent'] = 
               (!empty($parent_meta_value['layout']['codes_head_parent']) ? $parent_meta_value['layout']['codes_head_parent'] : null) .
               (!empty($parent_meta_value['layout']['codes_head']) ? $parent_meta_value['layout']['codes_head'] : null);
         }
   
         if (empty($meta_value['options']['codes_foot_inherit'])) {
            $meta_value['layout']['codes_foot_parent'] = null;
         } else {
            $meta_value['layout']['codes_foot_parent'] = 
               (!empty($parent_meta_value['layout']['codes_foot_parent']) ? $parent_meta_value['layout']['codes_foot_parent'] : null) .
               (!empty($parent_meta_value['layout']['codes_foot']) ? $parent_meta_value['layout']['codes_foot'] : null);
         }

         if (empty($meta_value['options']['css_inherit'])) {
            $meta_value['layout']['css_parent'] = null;
         } else {
            $meta_value['layout']['css_parent'] = 
               (!empty($parent_meta_value['layout']['css_parent']) ? $parent_meta_value['layout']['css_parent'] : null) .
               (!empty($parent_meta_value['layout']['css']) ? $parent_meta_value['layout']['css'] : null);
         }

         if (empty($meta_value['options']['scripts_inherit'])) {
            $meta_value['layout']['scripts_parent'] = null;
         } else {
            if (empty($parent_meta_value['layout']['scripts_parent'])) {
               $meta_value['layout']['scripts_parent'] = (!empty($parent_meta_value['layout']['scripts']) ? $parent_meta_value['layout']['scripts'] : null);
            } else if (empty($parent_meta_value['layout']['scripts'])) {
               $meta_value['layout']['scripts_parent'] = (!empty($parent_meta_value['layout']['scripts_parent']) ? $parent_meta_value['layout']['scripts_parent'] : null);
            } else {
               $meta_value['layout']['scripts_parent'] = 
                  array_merge($parent_meta_value['layout']['scripts_parent'], $parent_meta_value['layout']['scripts']);
            }
         }

         if (empty($meta_value['options']['styles_inherit'])) {
            $meta_value['layout']['styles_parent'] = null;
         } else {
            if (empty($parent_meta_value['layout']['styles_parent'])) {
               $meta_value['layout']['styles_parent'] = (!empty($parent_meta_value['layout']['styles']) ? $parent_meta_value['layout']['styles'] : null);
            } else if (empty($parent_meta_value['layout']['styles'])) {
               $meta_value['layout']['styles_parent'] = (!empty($parent_meta_value['layout']['styles_parent']) ? $parent_meta_value['layout']['styles_parent'] : null);
            } else {
               $meta_value['layout']['styles_parent'] = 
                  array_merge($parent_meta_value['layout']['styles_parent'], $parent_meta_value['layout']['styles']);
            }
         }
   
         if (!empty($meta_value['options']['widgets_inherit'])) {
            $meta_value['widgets'] = $parent_meta_value['widgets'];
         }

      }

      USI_Page_Solutions::meta_value_put($meta_value);

      // Get array of children, if any;
      global $wpdb;
      $SAFE_table_name = $wpdb->prefix . 'posts';
      $children = $wpdb->get_results(
         $wpdb->prepare("SELECT `ID` FROM `$SAFE_table_name` WHERE (`post_parent` = %d) AND (`post_type` = 'page')", $meta_value['post_id']), ARRAY_A
      );

      // Load this page's value into children and propagate down to all descendants;
      if (!empty($children)) {
         for ($ith = 0; $ith < count($children); $ith++) {
            $child_meta_value = USI_Page_Solutions::meta_value_get($children[$ith]['ID']);
            self::update_recursively($meta_value, $child_meta_value);
         }
      }

   } // update_recursively();

   function action_widgets_init() {
      $this->section_id = 'usi-page-solutions-options-dummy-' . get_current_user_id();
      $this->options    = get_option($this->section_id);
   } // action_widgets_init();

   function render_meta_box($post) {

      global $wp_registered_sidebars;

      wp_nonce_field(basename(__FILE__), 'usi-page-solutions-layout-nonce');

      $disabled = ($post->post_parent ? null : ' disabled');

      $html = !empty(USI_Page_Solutions::$options['preferences']['enable-layout']) ?
         '<p>' .
           '<a class="button button-secondary" href="options-general.php?page=usi-page-solutions-layout-settings&tab=code&page_id='    . $post->ID . '">Code</a> &nbsp; ' .
           '<a class="button button-secondary" href="options-general.php?page=usi-page-solutions-layout-settings&tab=page-css&page_id='     . $post->ID . '">CSS</a> &nbsp; ' .
           '<a class="button button-secondary" href="options-general.php?page=usi-page-solutions-layout-settings&tab=scripts&page_id=' . $post->ID . '">Script</a> &nbsp; ' .
           '<a class="button button-secondary" href="options-general.php?page=usi-page-solutions-layout-settings&tab=styles&page_id='  . $post->ID . '">Style</a> &nbsp; ' .
         '</p>' : '';

      $meta_value = USI_Page_Solutions::meta_value_get($post->ID);

      $codes_foot_inherit = $meta_value['options']['codes_foot_inherit'];
      $codes_head_inherit = $meta_value['options']['codes_head_inherit'];
      $css_inherit        = $meta_value['options']['css_inherit'];
      $scripts_inherit    = $meta_value['options']['scripts_inherit'];
      $styles_inherit     = $meta_value['options']['styles_inherit'];
      $widgets_inherit    = $meta_value['options']['widgets_inherit'];

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) {

         $collection_index = 0;

         $enhanced_widget_areas = get_option(USI_Page_Solutions::$option_name_base . '-enhanced');

         $options_virtual = USI_Page_Solutions::$options_virtual;

         $widgets = $meta_value['widgets'];

         foreach ($wp_registered_sidebars as $id => $sidebar) {
            // Skip virtual widget areas created by Page-Solutions;
            if ($id == ($options_virtual[0]['id'] ?? null)) break;
            if (!empty($enhanced_widget_areas[$id])) {
               $order_by = 0;
               $html .= '<p>' . $sidebar['name'] . '<br />';
               $html .= '<input name="usi-page-solutions-layout-enhanced-' . ++$collection_index . '-id" type="hidden" value="' . $id . '" />';

               if (isset($widgets[$id])) {
                  for ($ith = 0; $ith < count($widgets[$id]); $ith++) {
                     $html .= '<select name="usi-page-solutions-layout-enhanced-' . $collection_index . '-id-' . ++$order_by . '" style="width:100%;">';
                     if (!$widgets_inherit) $html .= '<option ' . ((0 == $options_virtual[0]['id']) ? 'selected ' : '') . 'value="0">-- Remove Item --</option>';
                     for ($jth = 0; $jth < count($options_virtual); $jth++) {
                        if ($widgets_inherit && ($options_virtual[$jth]['id'] != $widgets[$id][$ith])) continue;
                        $html .= '<option ' . (($options_virtual[$jth]['id'] == $widgets[$id][$ith]) ? 'selected ' : '') . 'value="' . 
                           $options_virtual[$jth]['id'] . '">' . $options_virtual[$jth]['name'] . '</option>';
                     }
                     $html .= '</select>';
                  }
               }

               if (!$widgets_inherit) {
                  $html .= '<select name="usi-page-solutions-layout-enhanced-' . $collection_index . '-id-' . ++$order_by . '" style="width:100%;">';
                  if (empty($options_virtual)) {
                     $html .= '<option selected value="0">-- ' . __('Nothing Configured', USI_Page_Solutions::TEXTDOMAIN) . ' --</option>';
                  } else {
                     $html .= '<option selected value="0">-- ' . __('Select Item', USI_Page_Solutions::TEXTDOMAIN) . ' --</option>';
                     for ($ith = 0; $ith < count($options_virtual); $ith++) {
                        $html .= '<option value="' . $options_virtual[$ith]['id'] . '">' . 
                           $options_virtual[$ith]['name'] . '</option>';
                     }
                  }
                  $html .= '</select>';
               }
               $html .= '<input name="usi-page-solutions-layout-enhanced-' . $collection_index . '-count" type="hidden" value="' . $order_by . '" />';

            }
         }
         $html .= '<input name="usi-page-solutions-layout-enhanced-count" type="hidden" value="' . $collection_index . '" />';
      }

      echo $html;

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout']) 
         || !empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) echo '<p>';

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout'])) {
?>
  <input id="usi-page-solutions-layout-codes-head-inherit"<?php checked($codes_head_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-codes-head-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-codes-head-inherit"><?php _e('Inherit raw header code from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-codes-foot-inherit"<?php checked($codes_foot_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-codes-foot-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-codes-foot-inherit"><?php _e('Inherit raw footer code from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-css-inherit"<?php checked($css_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-css-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-css-inherit"><?php _e('Inherit raw CSS from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-scripts-inherit"<?php checked($scripts_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-scripts-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-scripts-inherit"><?php _e('Inherit script links from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
  <br />
  <input id="usi-page-solutions-layout-styles-inherit"<?php checked($styles_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-styles-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-styles-inherit"><?php _e('Inherit style links from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
<?php
      }

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout']) 
         && !empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) echo '<br />';

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) {
?>
  <input id="usi-page-solutions-layout-widgets-inherit"<?php checked($widgets_inherit, true); echo $disabled; ?> name="usi-page-solutions-layout-widgets-inherit" type="checkbox" value="true" />
  <label for="usi-page-solutions-layout-widgets-inherit"><?php _e('Inherit widgets from parent', USI_Page_Solutions::TEXTDOMAIN); ?></label>
<?php
      }

      if (!empty(USI_Page_Solutions::$options['preferences']['enable-layout']) 
         || !empty(USI_Page_Solutions::$options['preferences']['enable-enhanced-areas'])) echo '</p>';

   } // render_meta_box();
      
} // USI_Page_Solutions_Layout;

// --------------------------------------------------------------------------------------------------------------------------- // ?>