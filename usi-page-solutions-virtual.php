<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Virtual {

   const VERSION = '0.0.3 (2018-01-04)';

   private $options = null;
   private $option_name = null;
   private $page_slug = 'usi-page-solutions-virtual';
   private $section_id = null;

   function __construct() {
      add_action('admin_init', array($this, 'action_admin_init_settings'));
      add_action('admin_menu', array($this, 'action_admin_menu'));
      add_action('widgets_init', array($this, 'action_widgets_init'), USI_Page_Solutions::WIDGETS_INIT_PRIORITY);
   } // __construct();

   function action_admin_init_settings() {

      add_settings_section(
         $this->section_id, // Section id;
         null, // Section title;
         null, // Render section callback;
         $this->page_slug // Settings page menu slug;
      );

      add_settings_field(
         $this->option_name . '[' . ($id = 'name') . ']', // Option name;
         __('Name', USI_Page_Solutions::TEXTDOMAIN), // Field title;
         array($this, 'settings_fields_render'), // Render field callback;
         $this->page_slug, // Settings page menu slug;
         $this->section_id, // Section id;
         array(
            'id' => $id, 
            'type' => 'text', 
            'notes' => '<div class="usi-page-solutions-virtual-notes">Widget collection name.</div>',
         )
      );

      add_settings_field(
         $this->option_name . '[' . ($id = 'id') . ']', // Option name;
         __('Id', USI_Page_Solutions::TEXTDOMAIN), // Field title;
         array($this, 'settings_fields_render'), // Render field callback;
         $this->page_slug, // Settings page menu slug;
         $this->section_id, // Section id;
         array(
            'id' => $id, 
            'type' => 'text', 
            'readonly' => (isset($_GET['action']) && ('edit' == $_GET['action']) ? ' readonly ' : null), 
            'notes' => '<div class="usi-page-solutions-virtual-notes">Widgert collection ID, ' .
                       'must be all in lowercase with no spaces, default is slugified widget collection name.</div>',
         )
      );

      add_settings_field(
         $this->option_name . '[' . ($id = 'description') . ']', // Option name;
         __('Description', USI_Page_Solutions::TEXTDOMAIN), // Field title;
         array($this, 'settings_fields_render'), // Render field callback;
         $this->page_slug, // Settings page menu slug;
         $this->section_id, // Section id;
         array(
            'id' => $id, 
            'type' => 'text', 
            'notes' => '<div class="usi-page-solutions-virtual-notes">Widget collection description, ' .
                       'shown on widget management screen.</div>',
         )
      );

      add_settings_field(
         $this->option_name . '[' . ($id = 'before_widget') . ']', // Option name;
         __('Before Widget', USI_Page_Solutions::TEXTDOMAIN), // Field title;
         array($this, 'settings_fields_render'), // Render field callback;
         $this->page_slug, // Settings page menu slug;
         $this->section_id, // Section id;
         array(
            'id' => $id, 
            'type' => 'text', 
            'html_id' => 'usi-page-solutions-virtual-before-widget',
            'notes' => '<div class="usi-page-solutions-virtual-notes">HTML to place before every widget, default is ' .
                       '<span class="usi-page-solutions-virtual-notes-code">&lt;li id="%1$s" class="widget %2$s"&gt;</span></div>',
            'style' => 'font-family:courier;',
         )
      );

      add_settings_field(
         $this->option_name . '[' . ($id = 'after_widget') . ']', // Option name;
         __('After Widget', USI_Page_Solutions::TEXTDOMAIN), // Field title;
         array($this, 'settings_fields_render'), // Render field callback;
         $this->page_slug, // Settings page menu slug;
         $this->section_id, // Section id;
         array(
            'id' => $id, 
            'type' => 'text', 
            'html_id' => 'usi-page-solutions-virtual-after-widget',
            'notes' => '<div class="usi-page-solutions-virtual-notes">HTML to place after every widget, default is ' .
                       '<span class="usi-page-solutions-virtual-notes-code">&lt;/li&gt;</span></div>',
            'style' => 'font-family:courier;',
         )
      );

      add_settings_field(
         $this->option_name . '[' . ($id = 'before_title') . ']', // Option name;
         __('Before Title', USI_Page_Solutions::TEXTDOMAIN), // Field title;
         array($this, 'settings_fields_render'), // Render field callback;
         $this->page_slug, // Settings page menu slug;
         $this->section_id, // Section id;
         array(
            'id' => $id, 
            'type' => 'text', 
            'html_id' => 'usi-page-solutions-virtual-before-title',
            'notes' => '<div class="usi-page-solutions-virtual-notes">HTML to place before every title, default is ' .
                       '<span class="usi-page-solutions-virtual-notes-code">&lt;h2 class="widgettitle"&gt;</span></div>',
            'style' => 'font-family:courier;',
         )
      );

      add_settings_field(
         $this->option_name . '[' . ($id = 'after_title') . ']', // Option name;
         __('After Title', USI_Page_Solutions::TEXTDOMAIN), // Field title;
         array($this, 'settings_fields_render'), // Render field callback;
         $this->page_slug, // Settings page menu slug;
         $this->section_id, // Section id;
         array(
            'id' => $id, 
            'type' => 'text', 
            'html_id' => 'usi-page-solutions-virtual-after-title',
            'notes' => '<div class="usi-page-solutions-virtual-notes">HTML to place after every title, default is ' .
                       '<span class="usi-page-solutions-virtual-notes-code">&lt;h2&gt;</span></div>',
            'style' => 'font-family:courier;',
         )
      );

      register_setting(
         $this->section_id, // Settings group name, must match the group name in settings_fields();
         $this->option_name, // Option name;
         array($this, 'settings_fields_sanitize') // Sanitize field callback;
      );
   
   } // action_admin_init_settings();

   function action_admin_menu() {
      // We don't want the page to show up in the sidebar menu, just be accessable from the list;
      // We need it in the menu or the settings API won't allow option changes, so we remove it from the menu
      // if this page isn't active, and if it is active we remove the menu item with jQuery down below;
      add_options_page(
         __('Page-Solutions | Add Virtual Widget Collection', USI_Page_Solutions::TEXTDOMAIN), // Page <title/> text;
         '<span id="usi-page-solutions-virtual-remove"></span>', // Sidebar menu text; 
         USI_Page_Solutions::NAME .'-Virtual-Add', // Capability required to enable page;
         $this->page_slug, // Settings page menu slug;
         array($this, 'settings_page_render') // Render page callback;
      );
      if (empty($_GET['page']) || ('usi-page-solutions-virtual' != $_GET['page'])) {
         remove_submenu_page('options-general.php', $this->page_slug);
      }
   } // action_admin_menu();

   function action_widgets_init() {
      $this->section_id = USI_Page_Solutions::$option_name_virtual;
      $this->option_name = USI_Page_Solutions::$option_name_virtual . '-add';
      $this->options = get_option($this->option_name);
   } // action_widgets_init();

   function settings_fields_render($args){
      $id = $args['id'];
      switch ($args['type']) {
      case 'text';
         echo '<input class="large-text" ' . (isset($args['html_id']) ? 'id="' . $args['html_id'] . '" ' : '') . 
            (!empty($args['readonly']) ? $args['readonly'] : '') .
            'name="' . $this->option_name . '[' . $id . ']" ' . 
            (isset($args['style']) ? 'style="' . $args['style'] . '" ' : '') . 'type="text" value="' . 
            esc_attr(isset($this->options[$id]) ? $this->options[$id] : null) . '" />' .
            (!empty($args['notes']) ? $args['notes'] : '');
         break;
      }
   } // settings_fields_render();

   function settings_fields_sanitize($input){

      if (!current_user_can('manage_options')) wp_die(__('You are not authorized to perform that operation.', USI_Page_Solutions::TEXTDOMAIN));

      if (empty($_REQUEST['submit'])) {

         $message = sprintf(__('Internal error, action not set (%d).', USI_Page_Solutions::TEXTDOMAIN), __LINE__);
         $type = 'error';

      } else if (__('Add Collection', USI_Page_Solutions::TEXTDOMAIN) == $_REQUEST['submit']) {

         global $wp_registered_sidebars;

         $old_number_of_sidebars = count($wp_registered_sidebars);

         if (empty($input['id'])) $input['id'] = strtolower(sanitize_title($input['name']));

         register_sidebar($input);

         $new_number_of_sidebars = count($wp_registered_sidebars);

         if ((1 + $old_number_of_sidebars) != $new_number_of_sidebars) {
            $message = sprintf(__('Internal error, widget area not created (%d).', USI_Page_Solutions::TEXTDOMAIN), __LINE__);
            $type = 'error';
         } else {
            end($wp_registered_sidebars);
            $new_sidebar = key($wp_registered_sidebars);
            USI_Page_Solutions::$options_virtual[] = $wp_registered_sidebars[$new_sidebar];
            update_option(USI_Page_Solutions::$option_name_virtual, USI_Page_Solutions::$options_virtual);
            $message = __('Widget collection created. <a href="widgets.php">View widgets</a>', USI_Page_Solutions::TEXTDOMAIN);
            $type = 'updated';
         }

      } else if (empty($input['id'])) {

         $message = sprintf(__('Internal error, null ID (%d).', USI_Page_Solutions::TEXTDOMAIN), __LINE__);
         $type = 'error';

      } else if (__('Save', USI_Page_Solutions::TEXTDOMAIN) == $_REQUEST['submit']) {

         $message = sprintf(__('Internal error, could not find collection (%d).', USI_Page_Solutions::TEXTDOMAIN), __LINE__);
         $type = 'error';
         for ($ith = 0; $ith < count(USI_Page_Solutions::$options_virtual); $ith++) {
            if ($input['id'] == USI_Page_Solutions::$options_virtual[$ith]['id']) {
               USI_Page_Solutions::$options_virtual[$ith]['description']   = $input['description'];
               USI_Page_Solutions::$options_virtual[$ith]['name']          = $input['name'];
               USI_Page_Solutions::$options_virtual[$ith]['before_widget'] = $input['before_widget'];
               USI_Page_Solutions::$options_virtual[$ith]['after_widget']  = $input['after_widget'];
               USI_Page_Solutions::$options_virtual[$ith]['before_title']  = $input['before_title'];
               USI_Page_Solutions::$options_virtual[$ith]['after_title']   = $input['after_title'];
               update_option(USI_Page_Solutions::$option_name_virtual, USI_Page_Solutions::$options_virtual);
               $message = __('Widget collection updated. <a href="widgets.php">View widgets</a>', USI_Page_Solutions::TEXTDOMAIN);
               $type = 'updated';
               break;
            }
         }
      }

      add_settings_error(
         $this->page_slug, // Slug title;
         esc_attr('settings_updated'), // Message slug name identifier;
         $message, // Message text;
         $type // Message type;
      );

      return($input);
   } // settings_fields_sanitize();

   function settings_page_render() {

      // Load defaults for ADD and if edit doesn't find collection;
      $this->options['description'] = $this->options['id'] = $this->options['name'] = '';
      $this->options['before_widget'] = '<li id="%1$s" class="widget %2$s">';
      $this->options['after_widget']  = '</li>';
      $this->options['before_title']  = '<h2 class="widgettitle">';
      $this->options['after_title']   = '</h2>';

      switch ($action = !empty($_GET['action']) ? $_GET['action'] : 'add') {
      default: $action = 'add'; 

      case 'add': 
         $button = __('Add Collection', USI_Page_Solutions::TEXTDOMAIN);
         $header = __('Add Virtual Widget Collection', USI_Page_Solutions::TEXTDOMAIN);
         break;

      case 'edit':
         $button = null;
         $header = __('Edit Virtual Widget Collection', USI_Page_Solutions::TEXTDOMAIN);
         if (!empty($_GET['id'])) {
            for ($ith = 0; $ith < count(USI_Page_Solutions::$options_virtual); $ith++) {
               if ($_GET['id'] == USI_Page_Solutions::$options_virtual[$ith]['id']) {
                  $button = __('Save', USI_Page_Solutions::TEXTDOMAIN);
                  $this->options['id']            = USI_Page_Solutions::$options_virtual[$ith]['id'];
                  $this->options['description']   = USI_Page_Solutions::$options_virtual[$ith]['description'];
                  $this->options['name']          = USI_Page_Solutions::$options_virtual[$ith]['name'];
                  $this->options['before_widget'] = USI_Page_Solutions::$options_virtual[$ith]['before_widget'];
                  $this->options['after_widget']  = USI_Page_Solutions::$options_virtual[$ith]['after_widget'];
                  $this->options['before_title']  = USI_Page_Solutions::$options_virtual[$ith]['before_title'];
                  $this->options['after_title']   = USI_Page_Solutions::$options_virtual[$ith]['after_title'];
                  break;
               }
            }
         }
         if (!$button) {
            add_settings_error(
               $this->page_slug, // Slug title;
               esc_attr('settings_updated'), // Message slug name identifier;
               sprintf(__('Internal error, could not find collection (%d).', USI_Page_Solutions::TEXTDOMAIN), __LINE__), // Message text;
               'error' // Message type;
            );
         }
         break;
      }
?>
<div class="wrap">
  <h1><?php echo __('Page-Solutions', USI_Page_Solutions::TEXTDOMAIN) . ' - ' . $header; ?></h1>
  <form method="post" action="options.php">
    <p>
      <?php submit_button(__('Set Theme HTML Override Defaults'), 'secondary', 'usi-page-solutions-virtual-defaults-set', false); ?> &nbsp; 
      <?php submit_button(__('Clear Theme HTML Override Defaults'), 'secondary', 'usi-page-solutions-virtual-defaults-clear', false); ?>
      <?php submit_button(__('Disable Theme HTML Overrides'), 'secondary', 'usi-page-solutions-virtual-defaults-disable', false); ?>
    </p>
    <?php if (!$button) settings_errors(); ?>
    <?php settings_fields($this->section_id); ?>
    <?php do_settings_sections($this->page_slug); ?>
    <div class="submit">
      <?php if ($button) { submit_button($button, 'primary', 'submit', false); echo ' &nbsp; '; }?>
      <?php submit_button(__('Back to List'), 'secondary', 'usi-page-solutions-virtual-back-to-list', false); ?>
    </div>
  </form>
</div>
<script>
jQuery(document).ready(function($) {
   $('#usi-page-solutions-virtual-remove').parent().parent().remove();
   $('#usi-page-solutions-virtual-back-to-list').click(function(){
      window.location.href = 'options-general.php?page=usi-page-settings&tab=collections';
      return(false);
   });
   $('#usi-page-solutions-virtual-defaults-clear').click(function(){
      $('#usi-page-solutions-virtual-before-widget').val('');
      $('#usi-page-solutions-virtual-after-widget').val('');
      $('#usi-page-solutions-virtual-before-title').val('');
      $('#usi-page-solutions-virtual-after-title').val('');
      return(false);
   });
   $('#usi-page-solutions-virtual-defaults-disable').click(function(){
      $('#usi-page-solutions-virtual-before-widget').val('disable');
      $('#usi-page-solutions-virtual-after-widget').val('disable');
      $('#usi-page-solutions-virtual-before-title').val('disable');
      $('#usi-page-solutions-virtual-after-title').val('disable');
      return(false);
   });
   $('#usi-page-solutions-virtual-defaults-set').click(function(){
      $('#usi-page-solutions-virtual-before-widget').val('<li id="%1$s" class="widget %2$s">');
      $('#usi-page-solutions-virtual-after-widget').val('</li>');
      $('#usi-page-solutions-virtual-before-title').val('<h2 class="widgettitle">');
      $('#usi-page-solutions-virtual-after-title').val('</h2>');
      return(false);
   });
});
</script>
<?php
   } // settings_page_render();

} // Class USI_Page_Solutions_Virtual;

new USI_Page_Solutions_Virtual();

// --------------------------------------------------------------------------------------------------------------------------- // ?>