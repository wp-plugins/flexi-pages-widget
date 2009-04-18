=== Flexi Pages Widget ===
Contributors: SriniG
Donate link: http://srinig.com/wordpress/plugins/flexi-pages/#donate
Tags: pages, subpages, menu, hierarchy, sidebar, widget, navigation
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: trunk

A highly configurable WordPress sidebar widget to list pages and sub-pages. User friendly widget control comes with various options. 

== Description ==

Flexi Pages Widget is a highly configurable WordPress sidebar widget to list pages and sub-pages. Can be used as an alternative to the default 'Pages' widget.

Features:

* Option to display sub-pages only in parent page and related pages.
* Option to select and exclude certain pages from getting displayed in the list. Alternatively, only certain pages can be displayed by using the 'include' option.
* Option to include a link to the home page.
* Other options include title, sort column/order, hierarchical/flat format, show date.
* Multiple instances of the widget. Unlimited number of instances of the widget can be added to the sidebar.
* Instead of using the widget, the function flexipages() can be called from anywhere in the template. All parameters that can be passed on to [`wp_page_menu()`](http://codex.wordpress.org/Template_Tags/wp_page_menu) and [`wp_list_pages()`](http://codex.wordpress.org/Template_Tags/wp_list_pages) can be passed into the flexipages() template function.

== Installation ==

1. Unzip the compressed file and upload the `flexi-pages-widget.php` file (or `flexi-pages-widget` directory) to the `/wp-content/plugins/` directory
1. Activate the plugin 'Flexi Pages' through the 'Plugins' menu in WordPress admin
1. Go to WP admin -> Appearance -> Widgets, add the 'Flexi Pages' widget into the sidebar and choose your options. Multiple instances of the widget can be added to the sidebar.

== Frequently Asked Questions ==

= After selecting a few pages for exclusion, isn't it possible to deselect all pages? There is always one page selected for exclusion. =

It is possible to deselect all pages. Hold the 'Ctrl' key in your keyboard and click on the name of the page that's not getting deselected.

= What does 'Show only related subpages' and 'Show only strictly related subpages' mean? =

When the option 'Show only related subpages' is selected, a subpage is listed only when the user visits the parent and sibling pages of the subpage. Thus, choosing this option will display the top level pages, children and siblings of the current page, and siblings of the parent page.

'Show only strictly related subpages' is same as the above except that siblings of parent page won't be displayed when on a subpage

= Is there an option to list only subpages and hide the parent pages? =

Although such an option does not exist, the 'Include' option can be used to achieve this. Select 'Include' instead of 'Exclude' and select all the pages you want to be listed. Pages left out won't be displayed.

= Is it possible to display only the child-pages of a particular page? =
Yes. In order to achieve this, select the 'Include' option, select just the child-pages to be listed (leave out all other pages), enable the 'Show subpages' option and select 'Show all sub-pages'.

= The widget treats a password protected page as any other page. Is there were a way to restrict the widget from showing password protected items until the password has been entered? =

The built-in WP template functions `wp_page_menu()` and `wp_list_pages()` treat password protected pages as any other page, and don't have an option to hide password protected pages until the password is entered. Flexi Pages Widget plugin depends on these functions, and until these functions provide an option to hide password protected pages, we can't have it either.

= The widget doesn't list private pages at all. Is there a way to show private pages when the admin is logged in? =

The built-in WP template functions `wp_page_menu()` and `wp_list_pages()` doesn't list private pages and doesn't have an option to show private pages. Flexi Pages Widget plugin depends on these functions, and until these functions provide an option to show private pages when the admin is logged in, it's not possible for us to show private pages.

= Where do I ask a question about the plugin? =

Leave your questions, suggestions, bug reports, etc., as a comment at the [plugin page](http://srinig.com/wordpress-plugins/flexi-pages/ "Flexi Pages Widget") or through [contact form](http://srinig.com/contact/) at the author's website. Questions frequently asked will be incorporated into the FAQ section in future versions of the plugin.

== Screenshots ==

1. Controls for the Flexi Pages Widget

== Changelog ==
* **2009-04-18: Version 1.5.1**
	* Bug fix. Title now doesn't show when there is no items in the list.
	* Frequently asked queries about private pages and password protected pages answered in FAQ.
* **2009-04-07: Version 1.5**
	* *Unlimited* instances of the Flexi Pages Widget can be added to the sidebar.
	* New option to show date. This option, when selected displays creation or last modified date next to each page.
	* The widgets options gets an overhaul. The list of options in the widget control page as of version 1.5.
		* Title
		* Sort column and sort order
		* Exclude/Include a list of pages
		* Show subpages (or list only top level pages). Show all subpages or only related subpages.
		* List the pages in hierarchical or flat format. If hierarchical, choose depth.
		* Show link to the home page
		* Show date, and choose date format.
	* The plugin references `wp_page_menu()` function instead of `wp_list_pages()`. Consequently, version 1.5 will work only with WordPress versions 2.7 and above.
* **2008-05-21: Version 1.4.1**
	* Bug fixes (issues regarding include/exclude sub-pages only with 'List sub-pages only in parent and related pages in hierarchy' option selected.)
* **2008-04-06: Version 1.4**
	* Fixed the odd behaviour when the widget is placed below the recent posts widget.
	* Removed the redundant check box for home page link in widget controls
	* Tested with WordPress 2.5; widget control box styling compatible with WP 2.5
* **2008-02-19: Version 1.3**
	* Multiple instances of the widget
	* Added 'Include pages' option
	* `flexipages()` template function
	* Other minor improvements
* **2007-08-31: Version 1.2**
	* Added option to provide a custom text for the home page link
	* Custom depth of '-3' will display only parents, siblings and children along with top level pages. Parents' siblings wont be displayed.
	* Few other improvements and some optimization.
	* Tested with WordPress 2.3-beta1.
* **2007-08-22: Version 1.1.2**
	* Fixed the missing `</li>` tag for home link
	* Added class name (`page_item`, `current_page_item`) for home link
* **2007-08-17: Version 1.1.1**
	* bug fix
	* tested with WordPress 2.2.2
* **2007-08-12: Version 1.1**
	* bug fix
* **2007-08-08: Version 1.0**
	* Initial release
