<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Options {

   const VERSION = '1.7.0 (2022-08-09)';

   function __construct() {
      add_action('add_meta_boxes', [$this, 'action_add_meta_boxes']);
      add_action('admin_head', [$this, 'action_admin_head']);
      add_action('save_post', [$this, 'action_save_post']);
   } // __construct();

   function action_add_meta_boxes() {
      add_meta_box(
         'usi-page-solutions-options-meta-box', // Meta box id;
         __('Page-Solutions Options', USI_Page_Solutions::TEXTDOMAIN), // Title;
         [$this, 'render_meta_box'], // Render meta box callback;
         'page', // Screen type;
         'side', // Location on page;
         'low' // Priority;
      );
   } // action_add_meta_boxes();

   function action_admin_head() {

      $screen = get_current_screen();

      if (('page' != $screen->id) || ('page' != $screen->post_type)) return;

      $screen->add_help_tab([
         'title' => __('Page-Solutions Options', USI_Page_Solutions::TEXTDOMAIN),
         'id' => 'usi-page-solutions-options',
         'content'  => 
'<p>' . __('The Page-Solutions plugin adds the following page options:', USI_Page_Solutions::TEXTDOMAIN) . '</p>'.
'<ul>' .
'<li><b>' . __('Accepts arguments', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The page can be called with an argument string that follows the page URL. This argument string can be used to pass information into widgets that have been designed to use this feature. This option is disabled if this page is a parent page.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'</ul>' .
'<p>' . __('The Page-Solutions plugin options are configured on a page by page basis.', USI_Page_Solutions::TEXTDOMAIN) . '</p>'
      ]);

   } // action_admin_head();

   function action_save_post($page_id) {

      if (!current_user_can('edit_page', $page_id)) {      
      } else if (wp_is_post_autosave($page_id)) {
      } else if (wp_is_post_revision($page_id)) {
      } else if (empty($_POST['usi-page-solutions-options-nonce'])) {
      } else if (!wp_verify_nonce($_POST['usi-page-solutions-options-nonce'], basename(__FILE__))) {
      } else {
         $new_arguments = !empty($_POST['usi-page-solutions-options-arguments']);
         $new_theme     = $_POST['usi-page-solutions-options-theme'] ?? 'default';
         $meta_value    = USI_Page_Solutions::meta_value_get($page_id);
         if (($meta_value['options']['arguments'] != $new_arguments) || ($meta_value['options']['theme'] != $new_theme)) {
            delete_post_meta($page_id, $meta_value['key']); 
            $offset = strlen(USI_Page_cache::POST_META);
            $meta_value['key'][$offset] = ($new_arguments ? '*' : '!');
            $meta_value['options']['arguments'] = $new_arguments;
            $meta_value['options']['theme']     = $new_theme;
         }
         //USI_Page_Solutions::meta_value_put($meta_value);
      }

   } // action_save_post();

   function render_meta_box($post) {

      wp_nonce_field(basename(__FILE__), 'usi-page-solutions-options-nonce');

      $meta_value = USI_Page_Solutions::meta_value_get($post->ID);

      $arguments  = $meta_value['options']['arguments'];

      $disabled   = USI_Page_Solutions::number_of_offspring($post->ID) ? ' disabled' : null;

      $theme      = $meta_value['options']['theme'] ?? 'default';

      $themes     = wp_get_themes();

      WP_Theme::sort_by_name($themes);

      $select = '<option value="default"' . ($theme == 'default' ? ' selected' : '') . '>default</option>';
      foreach ($themes as $theme_obj) {
         $theme_id = $theme_obj->template . ':' . $theme_obj->stylesheet;
         $select  .= '<option value="' . $theme_id . '"' . ($theme_id == $theme ? ' selected' : '') . '>' . $theme_obj->name . '</option>';
      }
?>
<div>
   <label for="usi-page-solutions-options-theme"><?php _e('Theme :', USI_Page_Solutions::TEXTDOMAIN); ?></label>
   <select name="usi-page-solutions-options-theme" style="width:100%;"><?php echo $select; ?></select>
   <div>&nbsp;</div>
   <input id="usi-page-solutions-options-arguments"<?php checked($arguments, true); echo $disabled; ?> name="usi-page-solutions-options-arguments" type="checkbox" value="true" />
   <label for="usi-page-solutions-options-arguments"><?php _e('Accepts arguments', USI_Page_Solutions::TEXTDOMAIN); ?></label>
</div>
<?php

   } // render_meta_box();
      
} // USI_Page_Solutions_Options;

// --------------------------------------------------------------------------------------------------------------------------- // ?>