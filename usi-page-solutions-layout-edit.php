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

class USI_Page_Solutions_Layout_Edit extends USI_WordPress_Solutions_Settings {

   const VERSION = '1.5.5 (2020-03-24)';

   protected $is_tabbed = true;

   private $page_active = false;
   private $page_id = 0;

   function __construct() {

      $this->page_active = !empty($_GET['page']) && ($this->page_slug == $_GET['page']) ? true : false;

      $this->page_id = !empty($_REQUEST['page_id']) ? (int)$_REQUEST['page_id'] : 0;

      parent::__construct(
         array(
            'name' => USI_Page_Solutions::NAME . '-Layout', 
            'prefix' => USI_Page_Solutions::PREFIX . '-solutions-layout', 
            'text_domain' => USI_Page_Solutions::TEXTDOMAIN,
            'options' => & $this->options,
            'no_settings_link' => true
         )
      );

   } // __construct();

   function action_admin_head($css = null) {
      parent::action_admin_head(
         'textarea,.usi-page-solutions-mono-font{font-family:courier;}' . PHP_EOL
      );
   } // action_admin_head();

   function action_admin_menu() { 

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $this->page_id);

      $this->options['code']['page-id']    = $this->page_id;
      $this->options['code']['codes_foot'] = $meta_value['layout']['codes_foot'];
      $this->options['code']['codes_head'] = $meta_value['layout']['codes_head'];

      if (empty($meta_value['options']['codes_foot_inherit']) || empty($meta_value['layout']['codes_foot_parent'])) {
         unset($this->sections['code']['settings']['codes_foot_parent']);
      }

      if (empty($meta_value['options']['codes_head_inherit']) || empty($meta_value['layout']['codes_head_parent'])) {
         unset($this->sections['code']['settings']['codes_head_parent']);
      }

      $this->options['page-css']['page-id']     = $this->page_id;
      $this->options['page-css']['css']         = $meta_value['layout']['css'];
      $this->options['page-css']['css_parent']  = $meta_value['layout']['css_parent'];

      if (empty($meta_value['options']['css_inherit']) || empty($meta_value['layout']['css_parent'])) {
         unset($this->sections['page-css']['settings']['css_parent']);
      }

      $this->options['scripts']['page-id'] = $this->page_id;

      if (!empty($meta_value['layout']['scripts_parent'])) {
         foreach ($meta_value['layout']['scripts_parent'] as $key => $value) {
            $tokens = self::explode($value);
            $key = $tokens[0];
            $this->options['scripts']['p-' . $key] = $value;
            $this->sections['scripts']['settings']['p-' . $key] = array(
               'f-class' => 'large-text', 
               'label' => $key,
               'readonly' => true,
               'type' => 'text', 
            );
         }
      }

      if (!empty($meta_value['layout']['scripts'])) {
         foreach ($meta_value['layout']['scripts'] as $key => $value) {
            if ('scripts_add' == $key) continue;
            $tokens = self::explode($value);
            $key = $tokens[0];
            $this->options['scripts']['c-' . $key] = $value;
            $this->sections['scripts']['settings']['c-' . $key] = array(
               'f-class' => 'large-text', 
               'label' => $key,
               'type' => 'text', 
            );
         }
      }

      $this->sections['scripts']['settings']['scripts_add'] = array(
         'f-class' => 'large-text', 
         'label' => 'Add Script',
         'type' => 'text', 
         'notes' => '<i>unique-id &nbsp; script/path/name &nbsp; version &nbsp; in-footer</i>', 
      );

      $this->options['styles']['page-id'] = $this->page_id;

      if (!empty($meta_value['layout']['styles_parent'])) {
         foreach ($meta_value['layout']['styles_parent'] as $key => $value) {
            $tokens = self::explode($value);
            $key = $tokens[0];
            $this->options['styles']['p-' . $key] = $value;
            $this->sections['styles']['settings']['p-' . $key] = array(
               'f-class' => 'large-text', 
               'label' => $key,
               'readonly' => true,
               'type' => 'text', 
            );
         }
      }

      if (!empty($meta_value['layout']['styles'])) {
         foreach ($meta_value['layout']['styles'] as $key => $value) {
            if ('styles_add' == $key) continue;
            $tokens = self::explode($value);
            $key = $tokens[0];
            $this->options['styles']['c-' . $key] = $value;
            $this->sections['styles']['settings']['c-' . $key] = array(
               'f-class' => 'large-text', 
               'label' => $key,
               'type' => 'text', 
            );
         }
      }

      $this->sections['styles']['settings']['styles_add'] = array(
         'f-class' => 'large-text', 
         'label' => 'Add Style',
         'type' => 'text', 
         'notes' => '<i>unique-id &nbsp; style/path/name &nbsp; version &nbsp; media</i>', 
      );

      // We don't want the page to show up in the sidebar menu, just be accessable from the list;
      // We need it in the menu or the settings API won't allow option changes, so we remove it from the menu
      // if this page isn't active, and if it is active we remove the menu item with jQuery down below;

      add_options_page(
         __($this->name . ' Settings', $this->text_domain), // Page <title/> text;
         '<span id="usi-page-solutions-menu-remove"></span>', // Sidebar menu text; 
         'manage_options', // Capability required to enable page;
         $this->page_slug, // Settings page menu slug;
         array($this, 'page_render') // Render page callback;
      );

      if (!$this->page_active) {
         remove_submenu_page('options-general.php', $this->page_slug);
      }

   } // action_admin_menu();

   private static function explode($value) {
      $tokens = explode(' ', $value);
      if (!empty($tokens[0])) $tokens[0] = sanitize_title($tokens[0]);
      return($tokens);
   } // explode();

   function fields_sanitize($input) {

      $input = parent::fields_sanitize($input);

      if (!$this->page_id) {
         if (!empty($input['code']['page-id'])) {
            $this->page_id = $input['code']['page-id'];
         } else if (!empty($input['page-css']['page-id'])) {
            $this->page_id = $input['page-css']['page-id'];
         } else if (!empty($input['scripts']['page-id'])) {
            $this->page_id = $input['scripts']['page-id'];
         } else if (!empty($input['styles']['page-id'])) {
            $this->page_id = $input['styles']['page-id'];
         }
      }

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $this->page_id);

      if ('code' == $this->active_tab) {
         $meta_value['layout']['codes_foot'] = $input['code']['codes_foot'];
         $meta_value['layout']['codes_head'] = $input['code']['codes_head'];
      } else if ('css' == $this->active_tab) {
         $meta_value['layout']['css'] = $input['page-css']['css'];
      } else if ('scripts' == $this->active_tab) {
         unset($meta_value['layout']['scripts']);
         if (!empty($input['scripts'])) {
            foreach ($input['scripts'] as $key => $value) {
               if ('page-id' == $key) continue;
               $tokens = self::explode($value);
               if ('scripts_add' == $key) $key = $tokens[0];
               if ('p-' == substr($key, 0, 2)) continue;
               if (!empty($tokens[1])) $meta_value['layout']['scripts']['c-' . $key] = $value;
            }
         }
      } else if ('styles' == $this->active_tab) {
         unset($meta_value['layout']['styles']);
         if (!empty($input['styles'])) {
            foreach ($input['styles'] as $key => $value) {
               if ('page-id' == $key) continue;
               $tokens = self::explode($value);
               if ('styles_add' == $key) $key = $tokens[0];
               if ('p-' == substr($key, 0, 2)) continue;
               if (!empty($tokens[1])) $meta_value['layout']['styles']['c-' . $key] = $value;
            }
         }
      }

      USI_Page_Solutions_Layout::update_recursively(__METHOD__, null, $meta_value);

      return($input);

   } // fields_sanitize();

   function page_render($options = null) {

      ob_start();
         submit_button(__('View Page', USI_Page_Solutions::TEXTDOMAIN), 'secondary', 'usi-page-solutions-layout-view-page', false);
         echo ' &nbsp; ';
         submit_button(__('Back To Page', USI_Page_Solutions::TEXTDOMAIN), 'secondary', 'usi-page-solutions-layout-back-to-page', false);
         $submit_button = ' &nbsp; ' . ob_get_contents();
      ob_end_clean(); 

      $page_url = rtrim(get_permalink($this->page_id), '/');
      $trailing_code = 
'<script>' . PHP_EOL .
'jQuery(document).ready(function($) {' . PHP_EOL .
'   $("#usi-page-solutions-layout-back-to-page").click(function() {' . PHP_EOL .
"      window.location.href = 'post.php?post={$this->page_id}&action=edit'" . PHP_EOL .
'      return(false);' . PHP_EOL .
'   });' . PHP_EOL .
'   $("#usi-page-solutions-layout-view-page").click(function() {' . PHP_EOL .
"      window.location.href = '{$page_url}'" . PHP_EOL .
'      return(false);' . PHP_EOL .
'   });' . PHP_EOL .
'   $("#usi-page-solutions-menu-remove").parent().remove();' . PHP_EOL .
'});' . PHP_EOL .
'</script>' . PHP_EOL;

      $options = array(
         'page_header'   => get_the_title($this->page_id),
         'submit_button' => $submit_button,
         'tab_parameter' => '&page_id=' . $this->page_id,
         'trailing_code' => $trailing_code,
         'wrap_submit'   => true,
      );

      parent::page_render($options);

   } // page_render();

   function sections() {

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $this->page_id);

      $sections = array(

         'code' => array(
            'header_callback' => array($this, 'sections_header', '<p>' . __('The appropriate open and closing <span class="usi-page-solutions-mono-font"><b>&lt;tag&gt;&lt;/tag&gt;</b></span> must be included in the code fragments below:', USI_Page_Solutions::TEXTDOMAIN) . '</p>' . PHP_EOL),
            'label' => 'Raw Code',
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
               'codes_head_parent' => array(
                  'f-class' => 'large-text', 
                  'label' => 'Header Code From Parent',
                  'readonly' => true,
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'codes_head' => array(
                  'f-class' => 'large-text', 
                  'label' => 'Header Code',
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'codes_foot_parent' => array(
                  'f-class' => 'large-text', 
                  'label' => 'Footer Code From Parent',
                  'readonly' => true,
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'codes_foot' => array(
                  'f-class' => 'large-text', 
                  'label' => 'Footer Code',
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
            ),
         ), // code;

         'page-css' => array(
            'header_callback' => array($this, 'sections_header', '<p>' . __('Open and closing <span class="usi-page-solutions-mono-font"><b>&lt;style&gt;</b><b>&lt;/style&gt;</b></span> tags must be included in the code fragment below:', USI_Page_Solutions::TEXTDOMAIN) . '</p>' . PHP_EOL),
            'label' => 'Raw CSS',
            'localize_labels' => 'yes',
            'localize_notes' => 3, // <p class="description">__()</p>;
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
               'css_parent' => array(
                  'f-class' => 'large-text', 
                  'label' => 'CSS From Parent',
                  'readonly' => true,
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'css' => array(
                  'f-class' => 'large-text', 
                  'label' => 'CSS',
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
            ),
         ), // css;

         'scripts' => array(
            'header_callback' => array($this, 'sections_header', '<p>' . __('The <b><i>unique-id</i></b> must contain only lowercase letters, numbers or dashes and no spaces. The <b><i>script/path/name</i></b> must not contain any spaces. Use <b><i>null</i></b> as a place holder for <b><i>version</i></b> if you don\'t want to use a version but want to specifiy the <b><i>in-footer</i></b> flag. Use <b><i>true</i></b> for the <b><i>in-footer</i></b> flag if you want the script to be placed at the end of the page.', USI_Page_Solutions::TEXTDOMAIN) . '</p>' . PHP_EOL),
            'label' => 'Script Links',
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
            ),
         ), // scripts;

         'styles' => array(
            'header_callback' => array($this, 'sections_header', '<p>' . __('The <b><i>unique-id</i></b> must contain only lowercase letters, numbers or dashes and no spaces. The <b><i>style/path/name</i></b> must not contain any spaces. Use <b><i>null</i></b> as a place holder for <b><i>version</i></b> if you don\'t want to use a version but want to specifiy the <b><i>media</i></b> specifier. Common <b><i>media</i></b> specifiers are <b><i>all</i></b>, <b><i>print</i></b> and <b><i>screen</i></b>.', USI_Page_Solutions::TEXTDOMAIN) . '</p>' . PHP_EOL),
            'label' => 'Style Links',
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
            ),
         ), // styles;
      );

      return($sections);

   } // sections();

} // Class USI_Page_Solutions_Layout_Edit;

new USI_Page_Solutions_Layout_Edit();

// --------------------------------------------------------------------------------------------------------------------------- // ?>