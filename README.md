# Page-Solutions #

The Page-Solutions plugin adds enhanced widget areas and page caching functionality.

## Description ##

The Page-Solutions plugin provides custom CSS and JavaScript modifications, virtual widget mapping and page caching functionality on a page by page basis. 
This efficient and powerful plugin is well suited for page-intensive and non-blog WordPress applications. 
The Page-Solutions plugin is developed and maintained by <a href="https://www.usi2solve.com">Universal Solutions</a>.

## Page Caching ##
The Page-Solutions plugin stores content in the database for quick access which improves performance by eliminating the overhead of loading and running WordPress for pages that have not changed recently. In order to see changes however, the page cache must be cleared whenever you edit a page or if a page is updated by a widget running in one the theme's widget areas. The following four options allow you to control the page cache:

   * **Inherit parent page cache settings** - The cache settings are inherited from the parent page. Check this feature if this is a child page and its layout and function is similar to it's parent's page.

   * **Allow widgets to clear cache** - Allow widget(s) in your theme's widget area to dynamically clear the cache as the widget(s) desire. Check this feature if you use widget(s) that were designed to use the Page-Solutions caching system.

   * **Clear cache on next update** - The cache is cleared the next time the Update button is clicked. Check this feature if you don't want to remember to clear the cash manualy the next time you update your changes.

   * **Clear cache on every update** - The cache is cleared every time the Update button is clicked. Check this feature if you don't want to remember to clear the cash manualy every time you update your changes.

The following four options allow you to control how the page cache is cleared.

   * **Disable cache** - The cache is not used for the current page. Select this option if you don't want to use the caching features for this page or if the page is very dynamic and can rarely be re-used. This is the default option.

   * **Clear cache manually** - You manually clear the cache after you edit the page. Select this option if this page is only changed by you and content is never changed by widget. Make sure you click the Clear Cache button when you finish your page edits or your changes will not be seen by the world.

   * **Clear cache every** - The cache is cleared after the given time period has expired. Select this option if page content is changed by a widget(s) but it's not necessary for the changes to show immediately. Specify the period with the drop down box under this option.

   * **Clear cache everyday at** - The cache is cleared based on the given schedule. Select this option if page content is changed by a widget(s) and you want to ensure that changes are show at specific times of the day. List the times when the cache should be cleared under this option.

The Page-Solutions cache features and options are configured on a page by page basis.

## Installation ##
The Page-Solutions plugin follows the standard WordPress <a href="https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation">manual plugin installation</a> procedure:
1. Clone or Download the Page-Solutions archive to your computer.
   1. If you clone it, make sure you pull the ` usi-library ` and ` usi-settings ` submodules.
   1. If you download it, also download the ` usi-library ` submodule and install the submodule source files in the ` usi-library ` folder, likewise download the ` usi-settings ` submodule and install the submodule source files in the ` usi-settings ` folder.
1. Extract the archive contents to your local file system.
1. Rename the extracted folder to ` usi-page-solutions ` if not already done so during the extraction.
1. Upload the ` .php ` files from your ` usi-page-solutions ` folder to the ` wp-content/plugins ` folder in your target WordPress installation.
1. Activate the plugin via the WordPress *Plugins* menu located on the left side bar.

## License ##
> Page-Solutions is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License 
as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

> Page-Solutions is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

> You should have received a copy of the GNU General Public License along with Page-Solutions.  If not, see 
<http://www.gnu.org/licenses/>.

## Donations ##
Donations are accepted at <a href="https://www.usi2solve.com/donate/page-solutions">www.usi2solve.com/donate</a>. Thank you for your support!