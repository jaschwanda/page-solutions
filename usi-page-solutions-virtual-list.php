<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

if (!class_exists('WP_List_Table')) {
   require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class USI_Page_Solutions_Virtual_List extends WP_List_Table {

   const VERSION = '1.7.0 (2022-08-09)';

   private $delete_virtual = false;
   private $edit_virtual = false;

   function __construct($delete_virtual, $edit_virtual) {
      parent::__construct(
         [
            'singular' => 'Virtual Widget Collection',
            'plural' => 'Virtual Widget Collections',
            'ajax' => false,
         ]
      );

      $this->delete_virtual = $delete_virtual;
      $this->edit_virtual = $edit_virtual;

      $this->prepare_items();
      echo '<form method="get">' . PHP_EOL;
      $this->display();
      echo '</form>' . PHP_EOL;
   } // __construct();

   function column_id($item) {
      $actions = [];
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
      return
         [
            'id' => 'Id',
            'name' => 'Name',
            'description' => 'Description',
            'before_widget' => 'Before Widget',
            'after_widget' => 'After Widget',
            'before_title' => 'Before Title',
            'after_title' => 'After Title',
         ]
      ;
   } // get_columns();

   function get_sortable_columns() {
      return
         [
            'after_title' => ['after_title', false],
            'after_widget' => ['after_widget', false],
            'before_title' => ['before_title', false],
            'before_widget' => ['before_widget', false],
            'description' => ['description', false],
            'id' => ['id', false],
            'name' => ['name', false],
         ]
      ;
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

      $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

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

      $this->set_pagination_args(
         [
            'total_items' => $total_items, 
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
         ]
      );

   } // prepare_items();

   static function screen_options_load() {

      $screen = get_current_screen();

      if (!is_object($screen) || ($screen->id != 'settings_page_usi-page-solutions-settings')) return;

      $args = [
         'label' => __('Number of virtual widgets per page', USI_Page_Solutions::TEXTDOMAIN),
         'default' => 10,
         'option' => 'usi_ps_options_virtual_list_per_page',
      ];

      add_screen_option('per_page', $args);

   } // screen_options_load();

   static function screen_options_set($status, $option, $value) {
      if ('usi_ps_options_virtual_list_per_page' == $option) return($value);
      return($status);
   } // screen_options_set();

} // Class USI_Page_Solutions_Virtual_List;

// --------------------------------------------------------------------------------------------------------------------------- // ?>