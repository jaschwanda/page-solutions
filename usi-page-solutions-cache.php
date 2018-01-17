<?php // ------------------------------------------------------------------------------------------------------------------------ //

defined('ABSPATH') or die('Accesss not allowed.');

// 2do add action prefix;

class USI_Page_Solutions_Cache {

   const VERSION = '1.1.1 (2018-01-17)';

   private static $current_time = null;
   private static $valid_until = USI_Page_Cache::DATE_OMEGA;

   function __construct() {
      self::$current_time = current_time('mysql');
      add_action('add_meta_boxes', array($this, 'action_add_meta_boxes'));
      add_action('admin_head', array($this, 'action_admin_head'));
      add_action('save_post', array($this, 'action_save_post'));
   } // __construct();

   function action_add_meta_boxes() {
      add_meta_box(
         'usi-page-solutions-cache-meta-box', // Meta box id;
         __('Page-Solutions Cache', USI_Page_Solutions::TEXTDOMAIN), // Title;
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
         'title' => __('Page-Solutions Cache', USI_Page_Solutions::TEXTDOMAIN),
         'id' => 'usi-page-solutions-cache',
         'content'  => 
'<p>' . __('The Page-Solutions plugin stores content in the database for quick access which improves performance by eliminating the overhead of loading and running WordPress for pages that have not changed recently. In order to see changes however, the page cache must be cleared whenever you edit a page or if a page is updated by a widget running in one the theme\'s widget areas. The following four options allow you to control the page cache:', USI_Page_Solutions::TEXTDOMAIN) . '</p>'.
'<ul>' .
'<li><b>' . __('Inherit parent page cache settings', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The cache settings are inherited from the parent page. Check this feature if this is a child page and its layout and function is similar to it\'s parent\'s page.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'<li><b>' . __('Allow widgets to clear cache', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('Allow widget(s) in your theme\'s widget area to dynamically clear the cache as the widget(s) desire. Check this feature if you use widget(s) that were designed to use the Page-Solutions caching system.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'<li><b>' . __('Clear cache on next update', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The cache is cleared the next time the Update button is clicked. Check this feature if you don\'t want to remember to clear the cash manualy the next time you update your changes.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'<li><b>' . __('Clear cache on every update', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The cache is cleared  every time the Update button is clicked. Check this feature if you don\'t want to remember to clear the cash manualy every time you update your changes.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'</ul>' .
'<p>' . __('The following four options allow you to control how the page cache is cleared.', USI_Page_Solutions::TEXTDOMAIN) . '</p>' .
'<ul>' .
'<li><b>' . __('Disable cache', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The cache is not used for the current page. Select this option if you don\'t want to use the caching features for this page or if the page is very dynamic and can rarely be re-used. This is the default option.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'<li><b>' . __('Clear cache manually', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('You manually clear the cache after you edit the page. Select this option if this page is only changed by you and content is never changed by widget. Make sure you click the Clear Cache button when you finish your page edits or your changes will not be seen by the world.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'<li><b>' . __('Clear cache every', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The cache is cleared after the given time period has expired. Select this option if page content is changed by a widget(s) but it\'s not necessary for the changes to show immediately. Specify the period with the drop down box under this option.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'<li><b>' . __('Clear cache everyday at', USI_Page_Solutions::TEXTDOMAIN) . '</b> - ' . __('The cache is cleared based on the given schedule. Select this option if page content is changed by a widget(s) and you want to ensure that changes are show at specific times of the day. List the times when the cache should be cleared under this option.', USI_Page_Solutions::TEXTDOMAIN) . '</li>' .
'</ul>' .
'<p>' . __('The Page-Solutions cache features and options are configured on a page by page basis.', USI_Page_Solutions::TEXTDOMAIN) . '</p>'
      ));

   } // action_admin_head();

   function action_save_post($page_id) {

      if (!current_user_can('edit_page', $page_id)) {
      } else if (wp_is_post_autosave($page_id)) {
      } else if (wp_is_post_revision($page_id)) {
      } else if (empty($_POST['usi-page-solutions-cache-nonce'])) {
      } else if (!wp_verify_nonce($_POST['usi-page-solutions-cache-nonce'], basename(__FILE__))) {
      } else {
         $this->action_save_post_recursive($page_id);
      }

   } // action_save_post();

   private function action_save_post_recursive($page_id, $parent_meta_value = null) {

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $page_id);

      if ($parent_meta_value) { // IF copying parent parameters to this page;

         $clear_cache = false;

         $meta_value['cache']['allow-clear']         = $parent_meta_value['cache']['allow-clear'];
         $meta_value['cache']['clear-every-publish'] = $parent_meta_value['cache']['clear-every-publish'];
         $meta_value['cache']['mode']                = $parent_meta_value['cache']['mode'];
         $meta_value['cache']['period']              = $parent_meta_value['cache']['period'];
         $meta_value['cache']['schedule']            = $parent_meta_value['cache']['schedule'];

      } else { // ELSE not copying parent parameters to this page;

         $meta_value['cache']['allow-clear'] = !empty($_POST['usi-page-solutions-cache-allow-clear']);

         $clear_cache = $meta_value['cache']['clear-every-publish'] = !empty($_POST['usi-page-solutions-cache-clear-every-publish']);
         
         switch ($mode = isset($_POST['usi-page-solutions-cache-mode']) ? $_POST['usi-page-solutions-cache-mode'] : 'disable') {
         default: $mode = 'disable'; case 'manual': case 'period': case 'schedule': break;
         }
         $meta_value['cache']['mode'] = $mode;

         switch ($period = (int)(isset($_POST['usi-page-solutions-cache-period']) ? $_POST['usi-page-solutions-cache-period'] : 86400)) {
         default: $period = 86400;
         case 300:   case 600:   case 900:   case 1200:  case 1800:  case 3600:  case 7200:  
         case 10800: case 14400: case 21600: case 28800: case 43200: break;
         }
         $meta_value['cache']['period'] = $period;
         
         if ('period' == $mode) {
            // Address WordPress bug and set time zone to proper value;
            $WordPress_BUG_current_timezone = date_default_timezone_get();
            date_default_timezone_set(get_option('timezone_string'));
            
            $date = strtotime(substr(USI_Page_Solutions_Cache::$current_time, 0, 10));
            $time = strtotime(USI_Page_Solutions_Cache::$current_time);
            $meta_value['cache']['valid_until'] = date('Y-m-d H:i:s', $date + $period * (((int)(($time - $date) / $period)) + 1));
            
            // Address WordPress bug and set time zone back to bug value;
            date_default_timezone_set($WordPress_BUG_current_timezone);
         }
      
         $schedule_items = (int)(isset($_POST['usi-page-solutions-cache-schedule-count']) ? $_POST['usi-page-solutions-cache-schedule-count'] : '0');
         $SAFE_schedule = array();
         for ($ith = 0; $ith < $schedule_items; $ith++) {
            $name = 'usi-page-solutions-cache-schedule-' . $ith;
            if (isset($_POST[$name])) {
               if (preg_match('/([0-1][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/', $_POST[$name])) $SAFE_schedule[] = $_POST[$name];
            }
         }
         if (empty($SAFE_schedule)) { $SAFE_schedule = array('00:00:00'); } else { sort($SAFE_schedule); }
         $meta_value['cache']['schedule'] = $SAFE_schedule;
         
         if ('schedule' == $mode) {
            // Address WordPress bug and set time zone to proper value;
            $WordPress_BUG_current_timezone = date_default_timezone_get();
            date_default_timezone_set(get_option('timezone_string'));
      
            $current_time = substr(USI_Page_Solutions_Cache::$current_time, 11);
            $target = null;
            foreach ($SAFE_schedule as $time) {
               if ($time > $current_time) {
                  $target = $time;
                  break;
               }
            }
            if ($target) {
               $meta_value['cache']['valid_until'] = substr(USI_Page_Solutions_Cache::$current_time, 0, 10) . ' ' . $target;
            } else {
               $date = strtotime(substr(USI_Page_Solutions_Cache::$current_time, 0, 10)) + 86400;
               $meta_value['cache']['valid_until'] = date('Y-m-d ', $date) . $SAFE_schedule[0];
            }
            
            // Address WordPress bug and set time zone back to bug value;
            date_default_timezone_set($WordPress_BUG_current_timezone);
         }

         // Clear cache in case there was a cache before and now it is disabled;
         if ('disable' == $mode) $clear_cache = true;

         // Clear cache if clear on next publish;
         if (!empty($_POST['usi-page-solutions-cache-clear-next-publish'])) $clear_cache = true;

      } // ENDIF not copying parent parameters to this page;

      if ($clear_cache) {
         $meta_value['cache']['updated'] = 
         $meta_value['cache']['valid_until'] = USI_Page_Cache::DATE_ALPHA;
         $meta_value['cache']['html'] = '';
         $meta_value['cache']['size'] = 0;
      }

      // Save this page's parameters;
      USI_Page_Solutions::meta_value_put(__METHOD__, $meta_value);

      if ($clear_cache) {
         // Since index.php does the updates, just call curl and discard the data.
         $ch = curl_init(); 
         curl_setopt($ch, CURLOPT_URL, rtrim(get_permalink($page_id), '/') . '/');
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
         curl_exec($ch);
         curl_close($ch);
      }

      // Propagate this page's parameters down to all pages that inherit from this page;
      global $wpdb;
      $SAFE_table_name = $wpdb->prefix . 'posts';
      $children = $wpdb->get_results(
         $wpdb->prepare("SELECT `ID` FROM `$SAFE_table_name` WHERE (`post_parent` = %d) AND (`post_type` = 'page')", $page_id), ARRAY_A
      );

      for ($ith = 0; $ith < count($children); $ith++) {
         $this->action_save_post_recursive($children[$ith]['ID'], $meta_value);
      }

   } // action_save_post_recursive();

   function render_meta_box($post) {

      wp_nonce_field(basename(__FILE__), 'usi-page-solutions-cache-nonce');

      $meta_value = USI_Page_Solutions::meta_value_get(__METHOD__, $post->ID);

      $cache = $meta_value['cache'];

      $disabled = (($post->post_parent && $cache['inherit-parent']) ? ' disabled' : '');

?>
   <table style="width:100%;">
     <tr>
       <td>
         <input<?php checked(!empty($cache['inherit-parent']), true); echo $post->post_parent ? '' : ' disabled'; ?> id="usi-page-solutions-cache-inherit-parent" name="usi-page-solutions-cache-inherit-parent" type="checkbox" value="true">
       </td>
       <td><label for="usi-page-solutions-cache-inherit-parent"><?php _e('Inherit parent page cache settings', USI_Page_Solutions::TEXTDOMAIN); ?></label></td>
     </tr>
     <tr>
       <td>
         <input<?php checked(!empty($cache['allow-clear']), true); echo $disabled; ?> id="usi-page-solutions-cache-allow-clear" name="usi-page-solutions-cache-allow-clear" type="checkbox" value="true">
       </td>
       <td><label for="usi-page-solutions-cache-allow-clear"><?php _e('Allow widgets to clear cache', USI_Page_Solutions::TEXTDOMAIN); ?></label></td>
     </tr>
     <tr>
       <td>
         <input<?php checked(!empty($cache['clear-next-publish']), true); ?> id="usi-page-solutions-cache-clear-next-publish" name="usi-page-solutions-cache-clear-next-publish" type="checkbox" value="true">
       </td>
       <td><label for="usi-page-solutions-cache-clear-next-publish"><?php _e('Clear cache on next update', USI_Page_Solutions::TEXTDOMAIN); ?></label></td>
     </tr>
     <tr>
       <td>
         <input<?php checked(!empty($cache['clear-every-publish']), true); ?> id="usi-page-solutions-cache-clear-every-publish" name="usi-page-solutions-cache-clear-every-publish" type="checkbox" value="true">
       </td>
       <td><label for="usi-page-solutions-cache-clear-every-publish"><?php _e('Clear cache on every update', USI_Page_Solutions::TEXTDOMAIN); ?></label></td>
     </tr>
     <tr><td>&nbsp;</td><td>&nbsp;</td><tr>
     <tr>
       <td>
         <input<?php checked($cache['mode'], 'disable'); echo $disabled; ?> id="usi-page-solutions-cache-mode-disable" name="usi-page-solutions-cache-mode" type="radio" value="disable">
       </td>
       <td><label for="usi-page-solutions-cache-mode-disable"><?php _e('Disable cache', USI_Page_Solutions::TEXTDOMAIN); ?></label></td>
     </tr>
     <tr>
       <td>
         <input<?php checked($cache['mode'], 'manual'); echo $disabled; ?> id="usi-page-solutions-cache-mode-manual" name="usi-page-solutions-cache-mode" type="radio" value="manual">
       </td>
       <td><label for="usi-page-solutions-cache-mode-manual"><?php _e('Clear cache manually', USI_Page_Solutions::TEXTDOMAIN); ?></label></td>
     </tr>
     <tr>
       <td>
         <input<?php checked($cache['mode'], 'period'); echo $disabled; ?> id="usi-page-solutions-cache-mode-period" name="usi-page-solutions-cache-mode" type="radio" value="period">
       </td>
       <td><label for="usi-page-solutions-cache-mode-period"><?php _e('Clear cache every', USI_Page_Solutions::TEXTDOMAIN); ?> :</label></td>
     </tr>
     <tr>
       <td></td>
       <td>
         <select<?php echo $disabled; ?> name="usi-page-solutions-cache-period" style="width:100%;">
           <option <?php if (  300 == $cache['period']) echo 'selected '; ?>value="300"  >5 Minutes</option>
           <option <?php if (  600 == $cache['period']) echo 'selected '; ?>value="600"  >10 Minutes</option>
           <option <?php if (  900 == $cache['period']) echo 'selected '; ?>value="900"  >15 Minutes</option>
           <option <?php if ( 1200 == $cache['period']) echo 'selected '; ?>value="1200" >20 Minutes</option>
           <option <?php if ( 1800 == $cache['period']) echo 'selected '; ?>value="1800" >30 Minutes</option>
           <option <?php if ( 3600 == $cache['period']) echo 'selected '; ?>value="3600" >1 Hour</option>
           <option <?php if ( 7200 == $cache['period']) echo 'selected '; ?>value="7200" >2 Hours</option>
           <option <?php if (10800 == $cache['period']) echo 'selected '; ?>value="10800">3 Hours</option>
           <option <?php if (14400 == $cache['period']) echo 'selected '; ?>value="14400">4 Hours</option>
           <option <?php if (21600 == $cache['period']) echo 'selected '; ?>value="21600">6 Hours</option>
           <option <?php if (28800 == $cache['period']) echo 'selected '; ?>value="28800">8 Hours</option>
           <option <?php if (43200 == $cache['period']) echo 'selected '; ?>value="43200">12 Hours</option>
           <option <?php if (86400 == $cache['period']) echo 'selected '; ?>value="86400">1 Day</option>
         </select>
       </td>
     </tr>
     <tr>
       <td>
         <input<?php checked($cache['mode'], 'schedule'); echo $disabled; ?> id="usi-page-solutions-cache-mode-schedule" name="usi-page-solutions-cache-mode" type="radio" value="schedule">
       </td>
       <td><label for="usi-page-solutions-cache-mode-schedule"><?php _e('Clear cache everyday at', USI_Page_Solutions::TEXTDOMAIN); ?> :</label></td>
     </tr>
     <tr>
       <td></td>
       <td>
<?php
      $ith = 0;
      $schedule = $cache['schedule'];
      $schedule[] = '';
      foreach ($schedule as $time) {
         echo '         <input' . $disabled . ' id="usi-page-solutions-cache-schedule-' . $ith . 
            '" name="usi-page-solutions-cache-schedule-' . $ith . '" style="width:100%;" type="text" value="' . $time . '" />' . PHP_EOL;
         $ith++;
      }
?>
         <input id="usi-page-solutions-cache-schedule-count" name="usi-page-solutions-cache-schedule-count" type="hidden" value="<?php echo $ith; ?>">
       </td>
     </tr>
   </table>
   <p><label><?php _e('Cache created', USI_Page_Solutions::TEXTDOMAIN); ?> :</label><input readonly style="width:100%;" type="text" value="<?php echo $cache['updated']; ?>" /></p>
   <p><label><?php _e('Cache valid until', USI_Page_Solutions::TEXTDOMAIN); ?> :</label><input readonly style="width:100%;" type="text" value="<?php echo $cache['valid_until']; ?>" /></p>
<?php

   } // render_meta_box();
      
} // USI_Page_Solutions_Cache;

new USI_Page_Solutions_Cache();

// --------------------------------------------------------------------------------------------------------------------------- // ?>
