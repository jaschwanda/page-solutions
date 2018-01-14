<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Layout_Edit extends USI_Settings_Admin {

   const VERSION = '1.0.2 (2018-01-13)';

   protected $is_tabbed = true;

   private $page_active = false;
   private $page_id = 0;

   function __construct() {

      $this->page_active = !empty($_GET['page']) && ($this->page_slug == $_GET['page']) ? true : false;

      $this->page_id = !empty($_REQUEST['page_id']) ? (int)$_REQUEST['page_id'] : 0;

      $this->sections = array(

         'code' => array(
            'header_callback' => array($this, 'header_codes'),
            'label' => __('Raw Code', USI_Page_Solutions::TEXTDOMAIN),
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
               'codes_head_parent' => array(
                  'class' => 'large-text', 
                  'label' => __('Header Code From Parent', USI_Page_Solutions::TEXTDOMAIN),
                  'readonly' => true,
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'codes_head' => array(
                  'class' => 'large-text', 
                  'label' => __('Header Code', USI_Page_Solutions::TEXTDOMAIN),
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'codes_foot_parent' => array(
                  'class' => 'large-text', 
                  'label' => __('Footer Code From Parent', USI_Page_Solutions::TEXTDOMAIN),
                  'readonly' => true,
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'codes_foot' => array(
                  'class' => 'large-text', 
                  'label' => __('Footer Code', USI_Page_Solutions::TEXTDOMAIN),
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
            ),
         ), // code;

         'css' => array(
            'header_callback' => array($this, 'header_css'),
            'label' => __('Raw CSS', USI_Page_Solutions::TEXTDOMAIN),
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
               'css_parent' => array(
                  'class' => 'large-text', 
                  'label' => __('CSS From Parent', USI_Page_Solutions::TEXTDOMAIN),
                  'readonly' => true,
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
               'css' => array(
                  'class' => 'large-text', 
                  'label' => __('CSS', USI_Page_Solutions::TEXTDOMAIN),
                  'rows' => 10,
                  'type' => 'textarea', 
               ),
            ),
         ), // css;

         'scripts' => array(
            'header_callback' => array($this, 'header_script'),
            'label' => 'Script Links',
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
            ),
         ), // scripts;

         'styles' => array(
            'header_callback' => array($this, 'header_style'),
            'label' => __('Style Links', USI_Page_Solutions::TEXTDOMAIN),
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
            ),
         ), // styles;
      );

      parent::__construct(
         USI_Page_Solutions::NAME . '-Layout', 
         USI_Page_Solutions::PREFIX . '-solutions-layout', 
         USI_Page_Solutions::TEXTDOMAIN,
         false
      );

   } // __construct();

   function action_admin_menu() { 

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $this->page_id);

      USI_Settings::$options[$this->prefix]['code']['page-id']    = $this->page_id;
      USI_Settings::$options[$this->prefix]['code']['codes_foot'] = $meta_value['layout']['codes_foot'];
      USI_Settings::$options[$this->prefix]['code']['codes_head'] = $meta_value['layout']['codes_head'];

      if (empty($meta_value['options']['codes_foot_inherit']) || empty($meta_value['layout']['codes_foot_parent'])) {
         unset($this->sections['code']['settings']['codes_foot_parent']);
      }

      if (empty($meta_value['options']['codes_head_inherit']) || empty($meta_value['layout']['codes_head_parent'])) {
         unset($this->sections['code']['settings']['codes_head_parent']);
      }

      USI_Settings::$options[$this->prefix]['css']['page-id']     = $this->page_id;
      USI_Settings::$options[$this->prefix]['css']['css']         = $meta_value['layout']['css'];
      USI_Settings::$options[$this->prefix]['css']['css_parent']  = $meta_value['layout']['css_parent'];

      if (empty($meta_value['options']['css_inherit']) || empty($meta_value['layout']['css_parent'])) {
         unset($this->sections['css']['settings']['css_parent']);
      }

      USI_Settings::$options[$this->prefix]['scripts']['page-id'] = $this->page_id;

      if (!empty($meta_value['layout']['scripts_parent'])) {
         foreach ($meta_value['layout']['scripts_parent'] as $key => $value) {
            $tokens = self::explode($value);
            $key = $tokens[0];
            USI_Settings::$options[$this->prefix]['scripts']['p-' . $key] = $value;
            $this->sections['scripts']['settings']['p-' . $key] = array(
               'class' => 'large-text', 
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
            USI_Settings::$options[$this->prefix]['scripts']['c-' . $key] = $value;
            $this->sections['scripts']['settings']['c-' . $key] = array(
               'class' => 'large-text', 
               'label' => $key,
               'type' => 'text', 
            );
         }
      }

      $this->sections['scripts']['settings']['scripts_add'] = array(
         'class' => 'large-text', 
         'label' => __('Add Script', USI_Page_Solutions::TEXTDOMAIN),
         'type' => 'text', 
         'notes' => '<i>unique-id &nbsp; script/path/name &nbsp; version &nbsp; in-footer</i>', 
      );

      USI_Settings::$options[$this->prefix]['styles']['page-id'] = $this->page_id;

      if (!empty($meta_value['layout']['styles_parent'])) {
         foreach ($meta_value['layout']['styles_parent'] as $key => $value) {
            $tokens = self::explode($value);
            $key = $tokens[0];
            USI_Settings::$options[$this->prefix]['styles']['p-' . $key] = $value;
            $this->sections['styles']['settings']['p-' . $key] = array(
               'class' => 'large-text', 
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
            USI_Settings::$options[$this->prefix]['styles']['c-' . $key] = $value;
            $this->sections['styles']['settings']['c-' . $key] = array(
               'class' => 'large-text', 
               'label' => $key,
               'type' => 'text', 
            );
         }
      }

      $this->sections['styles']['settings']['styles_add'] = array(
         'class' => 'large-text', 
         'label' => __('Add Style', USI_Page_Solutions::TEXTDOMAIN),
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
         } else if (!empty($input['css']['page-id'])) {
            $this->page_id = $input['css']['page-id'];
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
         $meta_value['layout']['css'] = $input['css']['css'];
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

   function header_codes() {
      echo '<p>' . 
         __('The appropriate open and closing <b>&lt;tag&gt;&lt;/tag&gt;</b> must be included in the code fragments below:', USI_Page_Solutions::TEXTDOMAIN) . 
       '</p>' . PHP_EOL;
    } // header_codes();

   function header_css() {
      echo '<p>' . 
         __('Open and closing <b>&lt;style&gt;</b> tags must be included in the code fragment below:', USI_Page_Solutions::TEXTDOMAIN) . 
       '</p>' . PHP_EOL;
    } // header_css();

   function header_script() {
      echo '<p>' . 
         __('The <b><i>unique-id</i></b> must contain only lowercase letters, numbers or dashes and no spaces. The <b><i>script/path/name</i></b> must not contain any spaces. Use <b><i>null</i></b> as a place holder for <b><i>version</i></b> if you don\'t want to use a version but want to specifiy the <b><i>in-footer</i></b> flag. Use <b><i>true</i></b> for the <b><i>in-footer</i></b> flag if you want the script to be placed at the end of the page.', USI_Page_Solutions::TEXTDOMAIN) . 
       '</p>' . PHP_EOL;
    } // header_script();

   function header_style() {
      echo '<p>' . 
         __('The <b><i>unique-id</i></b> must contain only lowercase letters, numbers or dashes and no spaces. The <b><i>style/path/name</i></b> must not contain any spaces. Use <b><i>null</i></b> as a place holder for <b><i>version</i></b> if you don\'t want to use a version but want to specifiy the <b><i>media</i></b> specifier. Common <b><i>media</i></b> specifiers are <b><i>all</i></b>, <b><i>print</i></b> and <b><i>screen</i></b>.', USI_Page_Solutions::TEXTDOMAIN) . 
       '</p>' . PHP_EOL;
    } // header_style();

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

} // Class USI_Page_Solutions_Layout_Edit;

new USI_Page_Solutions_Layout_Edit();

// --------------------------------------------------------------------------------------------------------------------------- // ?>