<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

final class USI_Page_Solutions_Admin {

   const VERSION = '1.7.0 (2022-08-09)';

   public static $enhanced_edit  = false;
   public static $settings_view  = false;
   public static $virtual_add    = false;
   public static $virtual_edit   = false;
   public static $virtual_delete = false;

   function __construct() {
      add_action('admin_menu', [$this,'action_admin_menu']);
      add_action('before_delete_post', [$this, 'action_before_delete_post']);
      add_action('load-post.php', [__CLASS__, 'action_load_post_php']);
   } // __construct();

   function action_before_delete_post($post_id) {
      $mru = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-page');
      for ($ith = 0; $ith < count($mru); $ith++) {
         if ($mru[$ith]['page_id'] == $post_id) {
            unset($mru[$ith]);
            update_user_option(get_current_user_id(), USI_Page_Solutions::PREFIX . '-options-mru-page', $mru);
            return;
         }
      }
      $mru = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-post');
      if (!empty($mru)) for ($ith = 0; $ith < count($mru); $ith++) {
         if ($mru[$ith]['page_id'] == $post_id) {
            unset($mru[$ith]);
            update_user_option(get_current_user_id(), USI_Page_Solutions::PREFIX . '-options-mru-post', $mru);
            return;
         }
      }
   } // action_before_delete_post();

   static function action_load_post_php() {
      if (!isset($_GET['post'])) return;
      $post = get_post($_GET['post']);
      $get  = $put = 0;
      $new  = [];
      if ('page' == $post->post_type) {
         $page_mru_max = (int)USI_Page_Solutions::$options['preferences']['page-mru-max'];
         if ($page_mru_max++) {
            $old = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-page');
            $new[$put++] = ['page_id' => $post->ID, 'title' => $post->post_title];
            if (is_array($old)) {
               while (($put < $page_mru_max) && ($get < count($old ?? []))) {
                  if ($new[0] == $old[$get]) {
                     $get++;
                  } else {
                     $new[$put++] = $old[$get++];
                  }
               }
            }
            update_user_option(get_current_user_id(), USI_Page_Solutions::PREFIX . '-options-mru-page', $new);
         }
      } else {
         $post_mru_max = (int)USI_Page_Solutions::$options['preferences']['post-mru-max'];
         if ($post_mru_max++) {
            $old = get_user_option(USI_Page_Solutions::PREFIX . '-options-mru-post');
            $new[$put++] = ['page_id' => $post->ID, 'title' => $post->post_title];
            while (($put < $post_mru_max) && ($get < count($old ?? []))) {
               if ($new[0] == $old[$get]) {
                  $get++;
               } else {
                  $new[$put++] = $old[$get++];
               }
            }
            update_user_option(get_current_user_id(), USI_Page_Solutions::PREFIX . '-options-mru-post', $new);
         }
      }
   } // action_load_post_php();

   function action_admin_menu() {
      self::$enhanced_edit  = USI_WordPress_Solutions_Capabilities::current_user_can(USI_Page_Solutions::PREFIX, 'enhanced-edit');
      self::$settings_view  = USI_WordPress_Solutions_Capabilities::current_user_can(USI_Page_Solutions::PREFIX, 'settings-view');
      self::$virtual_add    = USI_WordPress_Solutions_Capabilities::current_user_can(USI_Page_Solutions::PREFIX, 'virtual-add');
      self::$virtual_edit   = USI_WordPress_Solutions_Capabilities::current_user_can(USI_Page_Solutions::PREFIX, 'virtual-edit');
      self::$virtual_delete = USI_WordPress_Solutions_Capabilities::current_user_can(USI_Page_Solutions::PREFIX, 'virtual-delete');
   } // action_admin_menu();

} // Class USI_Page_Solutions_Admin;

// --------------------------------------------------------------------------------------------------------------------------- // ?>
