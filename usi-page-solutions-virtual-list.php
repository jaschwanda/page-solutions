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

if (!class_exists('WP_List_Table')) {
   require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class USI_Page_Solutions_Virtual_List extends WP_List_Table {

   const VERSION = '1.6.0 (2021-06-12)';

   private $delete_virtual = false;
   private $edit_virtual = false;

   function __construct($delete_virtual, $edit_virtual) {
      parent::__construct(array(
         'singular' => 'Virtual Widget Collection',
         'plural' => 'Virtual Widget Collections',
         'ajax' => false,
      ));

      $this->delete_virtual = $delete_virtual;
      $this->edit_virtual = $edit_virtual;

      $this->prepare_items();
      echo '<form method="get">' . PHP_EOL;
      $this->display();
      echo '</form>' . PHP_EOL;
   } // __construct();

   function column_id($item) {
      $actions = array();
      if ($this->edit_virtual) $actions['edit'] = '<a href="?page=usi-page-solutions-virtual&action=edit&id=' . $item['id'] . '">Edit</a>';
      if ($this->delete_virtual) $actions['delete'] = '<a href="options-general.php?page=usi-page-solutions-settings&tab=virtual&action=delete&id=' . 
         $item['id'] . '" onclick=\'return(confirm("Are you sure you want to delete virtual widget collection \"' . $item['id'] . 
         '\"?"));\'>Delete</a>';
      return($item['id'] . ' ' . $this->row_actions($actions));
   } // column_id();

   function column_default($item, $column_name){
      switch ($column_name) {
      case 'after_title':
      case 'after_widget':
      case 'before_title':
      case 'before_widget':
         return(esc_attr($item[$column_name]));
      case 'description':
      case 'id':
      case 'name':
      case 'rating':
         return $item[$column_name];
      default: return(print_r($item,true));
      }
   } // column_default();

   function get_columns(){
      return(array(
         'id' => 'Id',
         'name' => 'Name',
         'description' => 'Description',
         'before_widget' => 'Before Widget',
         'after_widget' => 'After Widget',
         'before_title' => 'Before Title',
         'after_title' => 'After Title',
        ));
   } // get_columns();

   function get_sortable_columns() {
      return(array(
         'after_title' => array('after_title', false),
         'after_widget' => array('after_widget', false),
         'before_title' => array('before_title', false),
         'before_widget' => array('before_widget', false),
         'description' => array('description', false),
         'id' => array('id', false),
         'name' => array('name', false),
      ));
   } // get_sortable_columns();
   
   function prepare_items() {

      if (isset($_GET['id']) && isset($_GET['action']) && ('delete' == $_GET['action'])) {
         $id = $_GET['id'];
         for ($ith = 0; $ith < count(USI_Page_Solutions::$options_virtual); $ith++) {
            if ($id == USI_Page_Solutions::$options_virtual[$ith]['id']) {
               unset(USI_Page_Solutions::$options_virtual[$ith]);
               USI_Page_Solutions::$options_virtual = array_values(USI_Page_Solutions::$options_virtual);
               update_option(USI_Page_Solutions::$option_name_virtual, USI_Page_Solutions::$options_virtual);
               break;
            }
         }
      }

      $user_id = get_current_user_id();

      $per_page = (int)get_user_option('usi_ps_options_virtual_list_per_page', $user_id);
      if (0 == $per_page) $per_page = 10;

      $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());

      $data = USI_Page_Solutions::$options_virtual;

      function usort_reorder($a, $b) {
         $order   = !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'asc';
         $orderby = !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'id';
         $result  = strcmp($a[$orderby], $b[$orderby]);
         return(('asc' === $order) ? $result : -$result);
      }
      if (!empty($data)) usort($data, 'usort_reorder');

      $current_page = $this->get_pagenum();
      $total_items  = is_array($data) ? count($data) : 0;
      if (!empty($data)) $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
      $this->items = $data;

      $this->set_pagination_args(array(
         'total_items' => $total_items, 
         'per_page'    => $per_page,
         'total_pages' => ceil($total_items / $per_page),
      ));

   } // prepare_items();

   static function screen_options_load() {

      $screen = get_current_screen();

      if (!is_object($screen) || ($screen->id != 'settings_page_usi-page-solutions-settings')) return;

      $args = array(
         'label' => __('Number of virtual widgets per page', USI_Page_Solutions::TEXTDOMAIN),
         'default' => 10,
         'option' => 'usi_ps_options_virtual_list_per_page',
      );

      add_screen_option('per_page', $args);

   } // screen_options_load();

   static function screen_options_set($status, $option, $value) {
      if ('usi_ps_options_virtual_list_per_page' == $option) return($value);
      return($status);
   } // screen_options_set();

} // Class USI_Page_Solutions_Virtual_List;

// This filter fires early, doing it in the class is too late;
add_filter('set-screen-option', 'USI_Page_Solutions_Virtual_List::screen_options_set', 10, 3);

// --------------------------------------------------------------------------------------------------------------------------- // ?>