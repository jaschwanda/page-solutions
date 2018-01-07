<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

class USI_Page_Solutions_Layout_Edit extends USI_Settings_Admin {

   const VERSION = '1.0.1 (2018-01-07)';

   protected $is_tabbed = true;

   private $page_active = false;
   private $page_id = 0;

   function __construct() {

      $this->page_active = !empty($_GET['page']) && ($this->page_slug == $_GET['page']) ? true : false;

      $this->page_id = !empty($_REQUEST['page_id']) ? (int)$_REQUEST['page_id'] : 0;

      $this->sections = array(

         'code' => array(
            'header_callback' => array($this, 'header_codes'),
            'label' => 'Code',
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
            'label' => 'CSS',
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
/*
         'links' => array(
            'label' => 'Links',
            'settings' => array(
               'page-id' => array(
                  'type' => 'hidden', 
                  'value' =>  $this->page_id, 
               ),
               'codes_head' => array(
                  'class' => 'large-text', 
                  'type' => 'text', 
                  'label' => 'Header Code',
               ),
            ),
         ), // links;
*/
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

      USI_Settings::$options[$this->prefix]['css']['page-id']     = $this->page_id;
      USI_Settings::$options[$this->prefix]['css']['css']         = $meta_value['layout']['css'];
      USI_Settings::$options[$this->prefix]['css']['css_parent']  = $meta_value['layout']['css_parent'];

      if (empty($meta_value['options']['codes_foot_inherit']) || empty($meta_value['layout']['codes_foot_parent'])) {
         unset($this->sections['code']['settings']['codes_foot_parent']);
      }

      if (empty($meta_value['options']['codes_head_inherit']) || empty($meta_value['layout']['codes_head_parent'])) {
         unset($this->sections['code']['settings']['codes_head_parent']);
      }

      if (empty($meta_value['options']['css_inherit']) || empty($meta_value['layout']['css_parent'])) {
         unset($this->sections['css']['settings']['css_parent']);
      }

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

   function fields_sanitize($input) {

      $input = parent::fields_sanitize($input);

      if (!$this->page_id) {
         if (!empty($input['code']['page-id'])) {
            $this->page_id = $input['code']['page-id'];
         } else if (!empty($input['css']['page-id'])) {
            $this->page_id = $input['css']['page-id'];
         } else if (!empty($input['links']['page-id'])) {
            $this->page_id = $input['links']['page-id'];
         }
      }

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $this->page_id);

      if ('code' == $this->active_tab) {
         $meta_value['layout']['codes_foot'] = $input['code']['codes_foot'];
         $meta_value['layout']['codes_head'] = $input['code']['codes_head'];
      } else if ('css' == $this->active_tab) {
         $meta_value['layout']['css'] = $input['css']['css'];
      } else if ('links' == $this->active_tab) {
      }

      USI_Page_Solutions_Layout::update_recursively(__METHOD__, null, $meta_value);

      return($input);

   } // fields_sanitize();

   function header_codes() {
      echo '<p>' . 
         __('Open and closing <b>&lt;script&gt;</b> tags must be included in the code fragments below:', USI_Page_Solutions::TEXTDOMAIN) . 
       '</p>' . PHP_EOL;
    } // header_codes();

   function header_css() {
      echo '<p>' . 
         __('Open and closing <b>&lt;style&gt;</b> tags must be included in the code fragment below:', USI_Page_Solutions::TEXTDOMAIN) . 
       '</p>' . PHP_EOL;
    } // header_css();

   function page_render($options = null) {

      ob_start();
         submit_button(__('Back To Page', USI_Page_Solutions::TEXTDOMAIN), 'secondary', 'usi-page-solutions-layout-back-to-page', false);
         $submit_button = ' &nbsp; ' . ob_get_contents();
      ob_end_clean(); 

      $trailing_code = 
'<script>' . PHP_EOL .
'jQuery(document).ready(function($) {' . PHP_EOL .
'   $("#usi-page-solutions-layout-back-to-page").click(function() {' . PHP_EOL .
"      window.location.href = 'post.php?post={$this->page_id}&action=edit'" . PHP_EOL .
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