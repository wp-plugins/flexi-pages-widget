<?php
/*
Plugin Name: Flexi Pages Widget
Plugin URI: http://srinig.com/wordpress/plugins/flexi-pages/
Description: A highly configurable WordPress sidebar widget to list pages and sub-pages. User friendly widget control comes with various options. 
Version: 1.3
Author: Srini G
Author URI: http://srinig.com/
*/

/*  Copyright 2007 Srini G (email : srinig.com@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function flexipages_options_default()
{
	return array('title' => __('Pages'), 'sort_column' => 'menu_order', 'sort_order' => 'ASC', 'exinclude' => 'exclude', 'exinclude_values' => '', 'depth' => -2, 'depth_value' => 2, 'home_link' => 'on', 'home_link_text' => __('Home'));
}

function flexipages_currpage_hierarchy()
{
	if( !is_page() )
		return array();
		
	global $post;
	$curr_page = $post;
	// get parents, grandparents of the current page
	$hierarchy[] = $curr_page->ID;
	while($curr_page->post_parent) {
		$curr_page = &get_post($curr_page->post_parent);
		$hierarchy[] = $curr_page->ID;
	}
	return $hierarchy;
}

function flexipages_get_subpages()
{
	global $wpdb;
	$sql = "SELECT ID, post_title, post_parent FROM ".$wpdb->posts;
	$sql .= " WHERE 
		post_type = 'page' 
		AND post_status = 'publish' 
		AND post_parent <> 0 ";

	if($subpages = $wpdb->get_results($sql, ARRAY_A))
		return $subpages;

	else return array();
}


//function adapted from wp-admin/admin-functions.php/function parent_dropdown()
function flexipages_exinclude_options(
	$sort_column = "menu_order",
	$sort_order = "ASC",
	$selected = array(),
	$parent = 0,
	$level = 0 )
{
	global $wpdb;
	$items = $wpdb->get_results( "SELECT ID, post_parent, post_title FROM $wpdb->posts WHERE post_parent = $parent AND post_type = 'page' AND post_status = 'publish' ORDER BY {$sort_column} {$sort_order}" );

	if ( $items ) {
		foreach ( $items as $item ) {
			$pad = str_repeat( '&nbsp;', $level * 3 );
			if ( in_array($item->ID, $selected))
				$current = ' selected="selected"';
			else
				$current = '';
	
			echo "\n\t<option value='$item->ID'$current>$pad $item->post_title</option>";
			flexipages_exinclude_options( $sort_column, $sort_order, $selected, $item->ID,  $level +1 );
		}
	} else {
		return false;
	}
}

function flexipages($options = '')
{
	$key_value = explode('&', $options);
	$options = array();
	foreach($key_value as $value) {
		$x = explode('=', $value);
		$options[$x[0]] = $x[1]; // $options['key'] = 'value';
	}

	if($options['exclude'])
		$exclude = explode(',',$options['exclude']);
	else
		$exclude = array();
	
	
	if( $options['depth'] == -2 || !$options['depth'])  { // display subpages only in related pages
		
		$hierarchy = flexipages_currpage_hierarchy();
			
		$subpages = flexipages_get_subpages();

		foreach ($subpages as $subpage) { //loop through the sub pages
			// if the parent of any of the subpage is not in our hierarchy,
			// add it to the exclusion list
			if ( !in_array ($subpage['post_parent'], $hierarchy) )
				$exclude[] = $subpage['ID'];
		}
		$options['depth'] = 0;
	}
	else if( $options['depth'] == -3 )  { // display subpages only in related pages
		// depth = -3 gets rid of parents' siblings
		
		$hierarchy = flexipages_currpage_hierarchy();
			
		$subpages = flexipages_get_subpages();
						
		foreach ($subpages as $subpage) { //loop through the sub pages
			if (
				( $subpage['post_parent'] != $hierarchy[0] ) &&
				( $subpage['post_parent'] != $hierarchy[1] ) &&
				( !in_array ($subpage['ID'], $hierarchy) ) 
			) {
				$exclude[] = $subpage['ID'];
			}
		}
		$options['depth'] = 0;
	}

	if($exclude)
		$options['exclude'] = implode(',', $exclude);
	
	if($options['title_li']) {
		$title_li = $options['title_li'];
		$options['title_li'] = "";
	}

	if($options['home_link']) {
		$display .="<li class=\"page_item";
		if(is_home()) $display .= " current_page_item";
		$display .= "\"><a href=\"".get_bloginfo('home')."\" title=\"".wp_specialchars(get_bloginfo('name'), 1) ."\">".$options['home_link']."</a></li>";
	}

	foreach($options as $key => $value) {
		if($key == 'home_link' || $key == 'echo')
			continue;
		if($opts) $opts .= '&';
		$opts .= $key.'='.$value;
	}

	$display .= wp_list_pages('echo=0&'.$opts);
	
	if($title_li) 
		$display = "<li class=\"pagenav\">".$title_li."<ul>\n".$display."</ul></li>";
	if(isset($options['echo']) && $options['echo'] == 0)
		return $display;
	else
		echo $display;
}

function flexipages_init()
{
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
	function flexipages_widget($args, $number = 1)
	{
		$options = get_option('flexipages_widget');
		if(!$options[$number]) {
			$options[$number] = flexipages_options_default();
		}
		extract($options[$number]);
		if($exinclude == 'include')
			$include = $exinclude_values;
		else
			$exclude = $exinclude_values;
		if($home_link == 'on')
			$home_link = $home_link_text?$home_link_text:__('Home');
		else
			$home_link = "";

		if($depth == 'custom') {
			if(is_numeric($depth_value))
				$depth = $depth_value;
			else
				$depth = 2;
		}

		if( ($depth == -2 || $depth == -3) && !is_page() ) $depth = 1;

		extract($args);

		echo $before_widget;
		if($title)
			echo $before_title . $title . $after_title;
		echo "<ul>\n";
		
		flexipages('title_li=&sort_column='.$sort_column.'&sort_order='.$sort_order.'&exclude='.$exclude.'&include='.$include.'&depth='.$depth.'&home_link='.$home_link);

		echo "</ul>\n" . $after_widget;

	}
	
	function flexipages_widget_control($number)
	{
		global $wpdb;
		
		$options = $newoptions = get_option('flexipages_widget');		

		if ( !is_array($options) )
			$options = $newoptions = array();
		if(!$options[$number]) {
			$options[$number] = $newoptions[$number] = flexipages_options_default();
		}
		

		if($_REQUEST["flexipages_submit-{$number}"]) { 
			$newoptions[$number]['title'] 
				= strip_tags(stripslashes($_REQUEST["flexipages_title-{$number}"]));
			$newoptions[$number]['sort_column'] = strip_tags(stripslashes($_REQUEST["flexipages_sort_column-{$number}"]));
			$newoptions[$number]['sort_order'] = strip_tags(stripslashes($_REQUEST["flexipages_sort_order-{$number}"]));
			$newoptions[$number]['exinclude'] = strip_tags(stripslashes($_REQUEST["flexipages_exinclude-{$number}"]));
			$newoptions[$number]['exinclude_values'] = isset($_REQUEST["flexipages_exinclude_values-{$number}"])?implode(',', $_REQUEST["flexipages_exinclude_values-{$number}"]):'';
			$newoptions[$number]['depth'] = strip_tags(stripslashes($_REQUEST["flexipages_depth-{$number}"]));
			$newoptions[$number]['depth_value'] = strip_tags(stripslashes($_REQUEST["flexipages_depth_value-{$number}"]));
			if($newoptions[$number]['depth'] != 'custom' || !is_numeric($newoptions[$number]['depth_value']))
				$newoptions[$number]['depth_value'] = 2;
			$newoptions[$number]['home_link'] = ($_REQUEST["flexipages_home_link-{$number}"] == 'on')?'on':'off';
			if( !($newoptions[$number]['home_link_text'] = strip_tags( stripslashes($_REQUEST["flexipages_home_link_text-{$number}"]) ) ) ){
				$newoptions[$number]['home_link_text'] = __('Home');
			}
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('flexipages_widget', $options);
		}
	      $sort_column_select[$options[$number]['sort_column']]
        	=' selected="selected"';
        $sort_order_select[$options[$number]['sort_order']]
        	=' selected="selected"';
        $exinclude_select[$options[$number]['exinclude']]
        	=' selected="selected"';
        $depth_check[$options[$number]['depth']] = ' checked="checked"';
        $home_link_check = ($options[$number]['home_link'] == 'on')?' checked="checked"':'';
		// Display widget options menu
  ?>
<table cellpadding="5px">
<tr>
	<td valign="top"><label for="flexipages_title-<?php echo $number; ?>">Title</label></td>
	<td><input type="text" id="flexipages_title-<?php echo $number; ?>" name="flexipages_title-<?php echo $number; ?>" value="<?php  echo htmlspecialchars($options[$number]['title'], ENT_QUOTES) ?>" /></td>
</tr>
<tr>
	<td valign="top" width="40%"><label for="flexipages_sort_column-<?php echo $number; ?>">Sort by</label></td>
	<td valign="top" width="60%"><select name="flexipages_sort_column-<?php echo $number; ?>" id="flexipages_sort_column-<?php echo $number; ?>">
			<option value="post_title"<?php echo $sort_column_select['post_title']; ?>>Page title</option>
			<option value="menu_order"<?php echo $sort_column_select['menu_order']; ?>>Menu order</option>
			<option value="post_date"<?php echo $sort_column_select['post_date']; ?>>Date created</option>
			<option value="post_modified"<?php echo $sort_column_select['post_modified']; ?>>Date modified</option>
			<option value="ID"<?php echo $sort_column_select['ID']; ?>>Page ID</option>
			<option value="post_author"<?php echo $sort_column_select['post_author']; ?>>Page author ID</option>
			<option value="post_name"<?php echo $sort_column_select['post_name']; ?>>Page slug</option>
		</select>
		<select name="flexipages_sort_order-<?php echo $number; ?>" id="flexipages_sort_order-<?php echo $number; ?>">
			<option<?php echo $sort_order_select['ASC']; ?>>ASC</option>
			<option<?php echo $sort_order_select['DESC']; ?>>DESC</option>
		</select>
	</td>
</tr>


<tr>
	<td valign="top">
	<select name="flexipages_exinclude-<?php echo $number; ?>" id="flexipages_exinclude-<?php echo $number; ?>">
		<option value="exclude"<?php echo $exinclude_select['exclude']; ?>>Exclude</option>
		<option value="include"<?php echo $exinclude_select['include']; ?>>Include</option>
	</select> pages
	</td>
	<td><select name="flexipages_exinclude_values-<?php echo $number; ?>[]" id="flexipages_exinclude_values-<?php echo $number; ?>" multiple="multiple" size="4">
<?php flexipages_exinclude_options($options[$number]['sort_column'], $options[$number]['sort_order'], explode(',', $options[$number]['exinclude_values']),0,0); ?>
	</select>
	<br /><small>(use &lt;Ctrl&gt; key to select multiple pages)</small></td>
</tr>

<tr>
	<td><label for="flexipages_home_link-<?php echo $number; ?>">Link to home page?</label></td>
	<td><input type="checkbox" id="flexipages_home_link-<?php echo $number; ?>" name="flexipages_home_link-<?php echo $number; ?>"<?php echo $home_link_check; ?> /></td>
</tr>

<tr>
	<td><label for="flexipages_home_link_text-<?php echo $number; ?>">Home page link text</label></td>
	<td><input type="text" name="flexipages_home_link_text-<?php echo $number; ?>" id ="flexipages_home_link_text-<?php echo $number; ?>" value="<?php echo htmlspecialchars($options[$number]['home_link_text'], ENT_QUOTES); ?>" /></td>
</tr>

<tr>
	<td colspan="2"><table cellpadding="2px">
	<tr><td><input type="radio" name="flexipages_depth-<?php echo $number; ?>" id="flexipages_depth0" value="0"<?php echo $depth_check[0]; ?> /></td><td><label for="flexipages_depth0-<?php echo $number; ?>">List all pages and sub-pages in hierarchical (indented) form.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth-<?php echo $number; ?>" id="flexipages_depth_1-<?php echo $number; ?>" value="-1"<?php echo $depth_check[-1]; ?> /></td><td><label for="flexipages_depth_1-<?php echo $number; ?>">List all pages and sub-pages in flat (no-indent) form.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth-<?php echo $number; ?>" id="flexipages_depth1-<?php echo $number; ?>" value="1"<?php echo $depth_check[1]; ?> /></td><td><label for="flexipages_depth1-<?php echo $number; ?>">List top level pages only. Don't list subpages.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth-<?php echo $number; ?>" id="flexipages_depth_2-<?php echo $number; ?>" value="-2"<?php echo $depth_check[-2]; ?> /></td><td><label for="flexipages_depth_2-<?php echo $number; ?>">List sub-pages only in parent and related pages in hierarchy.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth-<?php echo $number; ?>" id="flexipages_depth_custom-<?php echo $number; ?>" value="custom"<?php echo $depth_check['custom']; ?> /></td><td><label for="flexipages_depth_custom-<?php echo $number; ?>">Custom depth level</label> (number) <input type="text" name="flexipages_depth_value-<?php echo $number; ?>" id="flexipages_depth_value-<?php echo $number; ?>" value="<?php echo $options[$number]['depth_value'] ?>" size="2" maxlength="2" onclick="document.getElementById('flexipages_depth_custom-<?php echo $number; ?>').checked = true;" /></td></tr>
	</table>
	</td>
</tr>


</table>
<input type="hidden" name="flexipages_submit-<?php echo $number; ?>" value="1" />

<?php
	}
	function flexipages_widget_setup() {
		$options = $newoptions = get_option('flexipages_widget');
		if ( isset($_POST['flexipages-number-submit']) ) {
			$number = (int) $_POST['flexipages-number'];
			if ( $number > 9 ) $number = 9;
			if ( $number < 1 ) $number = 1;
			$newoptions['number'] = $number;
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('flexipages_widget', $options);
			flexipages_widget_register($options['number']);
		}
	}
	function flexipages_widget_page() {
		$options = $newoptions = get_option('flexipages_widget');
?>
	<div class="wrap">
		<form method="POST">
			<h2>Flexi Pages Widgets</h2>
			<p style="line-height: 30px;"><?php _e('How many \'Flexi Pages\' widgets would you like?', 'widgets'); ?>
			<select id="flexipages-number" name="flexipages-number" value="<?php echo $options['number']; ?>">
<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
			</select>
			<span class="submit"><input type="submit" name="flexipages-number-submit" id="flexipages-number-submit" value="<?php _e('Save'); ?>" /></span></p>
		</form>
	</div>
<?php
	}

	function flexipages_widget_register() {
		$options = get_option('flexipages_widget');
		$number = $options['number'];
		if ( $number < 1 ) $number = 1;
		if ( $number > 9 ) $number = 9;
		for ($i = 1; $i <= 9; $i++) {
			$name = array('Flexi Pages %s', 'widgets', $i);
			register_sidebar_widget($name, $i <= $number ? 'flexipages_widget' : /* unregister */ '', $i);
			register_widget_control($name, $i <= $number ? 'flexipages_widget_control' : /* unregister */ '', 400, 450, $i);
		}
		add_action('sidebar_admin_setup', 'flexipages_widget_setup');
		add_action('sidebar_admin_page', 'flexipages_widget_page');
	}
	
	flexipages_widget_register();
}

add_action('plugins_loaded', 'flexipages_init');
?>
