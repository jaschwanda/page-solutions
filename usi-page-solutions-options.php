<?php // ------------------------------------------------------------------------------------------------------------------------ //

/*
Page-Solutions is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
License as published by the Free Software Foundation, either version 3 of the License, or any later version.
 
Page-Solutions is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License along with Page-Solutions. If not, see 
https://github.com/jaschwanda/Page-solutions/blob/master/LICENSE.md

Copyright (c) 2020 by Jim Schwanda.
*/

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Options {

   const VERSION = '1.5.0 (2020-01-12)';

   function __construct() {
      add_action('add_meta_boxes', array($this, 'action_add_meta_boxes'));
      add_action('admin_head', array($this, 'action_admin_head'));
      add_action('save_post', array($this, 'action_save_post'));
   } // __construct();

   function action_add_meta_boxes() {
      add_meta_box(
         'usi-page-solutions-options-meta-box', // Meta box id;
         __('Page-Solutions Options', USI_Page_Solutions::TEXTDOMAIN), // Title;
         array($this, 'render_meta_box'), // Render meta box callback;
         'page', // Screen type;
         'side', // Location on page;
         'low' // Priority;
      );
   } // action_add_meta_boxes();

   function action_admin_head() {

      $screen = get_current_screen();

      if (('page' != $screen->id) || ('page' != $screen->post_type)) return;

      $screen->add_help_tab(array(
         'title' => __('Page-Solutions Options', USI_Page_Solutions::TEXTDOMAIN),
         'id' => 'usi-page-solutions-options',
         'content'  => 
'<p>' . __('The Page-Solutions plugin adds the following page options:', USI_Page_Solutions::TEXTDOMAIN) . '</p>'.
'<ul>' .
'<li><b>' . __('Accepts arguments', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The page can be called with an argument string that follows the page URL. This argument string can be used to pass information into widgets that have been designed to use this feature. This option is disabled if this page is a parent page.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'</ul>' .
'<p>' . __('The Page-Solutions plugin options are configured on a page by page basis.', USI_Page_Solutions::TEXTDOMAIN) . '</p>'
      ));

   } // action_admin_head();

   function action_save_post($page_id) {

      if (!current_user_can('edit_page', $page_id)) {      
      } else if (wp_is_post_autosave($page_id)) {
      } else if (wp_is_post_revision($page_id)) {
      } else if (empty($_POST['usi-page-solutions-options-nonce'])) {
      } else if (!wp_verify_nonce($_POST['usi-page-solutions-options-nonce'], basename(__FILE__))) {
      } else {
         $new_arguments = !empty($_POST['usi-page-solutions-options-arguments']);
         $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $page_id);
         if ($meta_value['options']['arguments'] != $new_arguments) {
            delete_post_meta($page_id, $meta_value['key']); 
            $offset = strlen(USI_Page_cache::POST_META);
            $meta_value['key'][$offset] = ($new_arguments ? '*' : '!');
            $meta_value['options']['arguments'] = $new_arguments;
         }
         USI_Page_Solutions::meta_value_put(__METHOD__, $meta_value);
      }

   } // action_save_post();

   function render_meta_box($post) {

      wp_nonce_field(basename(__FILE__), 'usi-page-solutions-options-nonce');

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $post->ID);

      $arguments = $meta_value['options']['arguments'];

      $disabled = USI_Page_Solutions::number_of_offspring($post->ID) ? ' disabled' : null;

?>
<p>
  <input id="usi-page-solutions-options-arguments"<?php checked($arguments, true); echo $disabled; ?> name="usi-page-solutions-options-arguments" type="checkbox" value="true" />
  <label for="usi-page-solutions-options-arguments"><?php _e('Accepts arguments', USI_Page_Solutions::TEXTDOMAIN); ?></label>
</p>
<?php

   } // render_meta_box();
      
} // USI_Page_Solutions_Options;

new USI_Page_Solutions_Options();

// --------------------------------------------------------------------------------------------------------------------------- // ?>