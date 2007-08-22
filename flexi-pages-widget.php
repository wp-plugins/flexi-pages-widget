<?php
/*
Plugin Name: Flexi Pages Widget
Plugin URI: http://srinig.com/wordpress-plugins/flexi-pages/
Description: A highly configurable WordPress sidebar widget to list pages and sub-pages. User friendly widget control comes with various options. 
Version: 1.1.2
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

function flexipages_init()
{
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
	function flexipages_widget($args)
	{
		$options = get_option('flexipages');
		$title = isset($options['title'])?$options['title']:__('Pages');
		$sort_column = isset($options['sort_column'])?$options['sort_column']:'menu_order';
		$sort_order = isset($options['sort_order'])?$options['sort_order']:'ASC';
		$exclude = isset($options['exclude'])?explode(',',$options['exclude']):array();
		$depth = $options['depth'];
		$home_link = isset($options['home_link'])?$options['home_link']:'on';
		if($depth == 'custom') {
			if(is_numeric($options['depth_value']))
				$depth = $options['depth_value'];
			else
				$depth = 2;
		}
		if($depth == -2)  { 
		//display subpages only in related pages
		
			global $post, $wpdb;
			
			// get parents, grandparents of the current page
			$hierarchy[] = $post->ID;
			while($post->post_parent) {
				$post = &get_post($post->post_parent);
				$hierarchy[] = $post->ID;
			}
			
			// get ids of all sub pages along with the ids of thier parents
		
			$sql = "SELECT ID, post_parent FROM ".$wpdb->posts;
			$sql .= " WHERE 
						post_type = 'page' 
						AND post_status = 'publish' 
						AND post_parent <> 0 ";
			$subpages = $wpdb->get_results($sql, ARRAY_A);
			if($subpages) {
				foreach ($subpages as $subpage) { //loop through the sub pages
					// If the parent of any of the subpage is not in our hierarchy,
					// add it to the exclusion list
					if (!in_array($subpage['post_parent'], $hierarchy))
						$exclude[] = $subpage['ID'];
				}
			}
			$depth = "";
		}
		else $depth = "&depth=".$depth;
		if($exclude = implode(',', $exclude)) $exclude = "&exclude=".$exclude;
		extract($args);
		echo $before_widget . $before_title . $title . $after_title . "<ul>\n";
		if($home_link == 'on') {
			?><li class="page_item<?php if(is_home()) echo " current_page_item"; ?>"><a href="<?php bloginfo('home') ?>" title="<?php echo wp_specialchars(get_bloginfo('name'), 1) ?>"><?php _e('Home'); ?></a></li>
<?php
		}
		wp_list_pages('title_li=&sort_column='.$sort_column.'&sort_order='.$sort_order.$exclude.$depth);
		echo "</ul>\n" . $after_widget;
	}
	
	function flexipages_widget_control()
	{
		global $wpdb;
		
		// default options
		$options = array('title' => 'Pages', 'sort_column' => 'post_title', 'sort_order' => 'ASC', 'exclude' => '', 'depth' => -2, 'depth_value' => 2, 'home_link' => 'on');
		
		$saved_options = get_option('flexipages');
		if(is_array($saved_options))	
			$options = array_merge($options, $saved_options);

		if($_REQUEST['flexipages_submit']) { 
			$options['title'] 
				= strip_tags(stripslashes($_REQUEST['flexipages_title']));
			$options['sort_column'] = strip_tags(stripslashes($_REQUEST['flexipages_sort_column']));
			$options['sort_order'] = strip_tags(stripslashes($_REQUEST['flexipages_sort_order']));
			$options['exclude'] = isset($_REQUEST['flexipages_exclude'])?implode(',', $_REQUEST['flexipages_exclude']):'';
			$options['depth'] = strip_tags(stripslashes($_REQUEST['flexipages_depth']));
			$options['depth_value'] = strip_tags(stripslashes($_REQUEST['flexipages_depth_value']));
			if($options['depth'] != 'custom' || !is_numeric($options['depth_value']))
				$options['depth_value'] = 2;
			$options['home_link'] = ($_REQUEST['flexipages_home_link'] == 'on')?'on':'off';
			update_option('flexipages', $options);
		}
	      $sort_column_select[$options['sort_column']]
        	=' selected="selected"';
        $sort_order_select[$options['sort_order']]
        	=' selected="selected"';
        $depth_check[$options['depth']] = ' checked="checked"';
        $home_link_check = ($options['home_link'] == 'on')?' checked="checked"':'';
		// Display widget options menu
  ?>
<table cellpadding="5px">
<tr>
	<td valign="top"><label for="flexipages_title">Title</label></td>
	<td><input type="text" id="flexipages_title" name="flexipages_title" value="<?php  echo htmlspecialchars($options['title'], ENT_QUOTES) ?>" /></td>
</tr>
<tr>
	<td valign="top" width="40%"><label for="flexipages_sort_column">Sort by</label></td>
	<td valign="top" width="60%"><select name="flexipages_sort_column" id="flexipages_sort_column">
			<option value="post_title"<?php echo $sort_column_select['post_title']; ?>>Page title</option>
			<option value="menu_order"<?php echo $sort_column_select['menu_order']; ?>>Menu order</option>
			<option value="post_date"<?php echo $sort_column_select['post_date']; ?>>Date created</option>
			<option value="post_modified"<?php echo $sort_column_select['post_modified']; ?>>Date modified</option>
			<option value="ID"<?php echo $sort_column_select['ID']; ?>>Page ID</option>
			<option value="post_author"<?php echo $sort_column_select['post_author']; ?>>Page author ID</option>
			<option value="post_name"<?php echo $sort_column_select['post_name']; ?>>Page slug</option>
		</select>
		<select name="flexipages_sort_order" id="flexipages_sort_order">
			<option<?php echo $sort_order_select['ASC']; ?>>ASC</option>
			<option<?php echo $sort_order_select['DESC']; ?>>DESC</option>
		</select>
	</td>
</tr>


<tr>
	<td valign="top"><label for="flexipages_exclude">Exclude pages</label><br /><small>(use &lt;Ctrl&gt; key to select multiple pages)</small></td>
	<td><select name="flexipages_exclude[]" id="flexipages_exclude" multiple="multiple" size="4">
<?php flexipages_exclude_options($options['sort_column'], $options['sort_order'], explode(',', $options['exclude']),0,0); ?>
	</select>
	</td>
</tr>

<tr>
	<td valign="top"><label for="flexipages_home_link">Link to home page?</label></td>
	<td><input type="checkbox" id="flexipages_home_link" name="flexipages_home_link"<?php echo $home_link_check; ?> /></td>
</tr>

<tr>
	<td colspan="2"><table cellpadding="2px">
	<tr><td><input type="radio" name="flexipages_depth" id="flexipages_depth0" value="0"<?php echo $depth_check[0]; ?> /></td><td><label for="flexipages_depth0">List all pages and sub-pages in hierarchical (indented) form.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth" id="flexipages_depth-1" value="-1"<?php echo $depth_check[-1]; ?> /></td><td><label for="flexipages_depth-1">List all pages and sub-pages in flat (no-indent) form.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth" id="flexipages_depth1" value="1"<?php echo $depth_check[1]; ?> /></td><td><label for="flexipages_depth1">List top level pages only. Don't list subpages.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth" id="flexipages_depth-2" value="-2"<?php echo $depth_check[-2]; ?> /></td><td><label for="flexipages_depth-2">List sub-pages only in parent and related pages in hierarchy.</label></td></tr>
	<tr><td><input type="radio" name="flexipages_depth" id="flexipages_depth_custom" value="custom"<?php echo $depth_check['custom']; ?> /></td><td><label for="flexipages_depth_custom">Custom depth level</label> (number) <input type="text" name="flexipages_depth_value" id="flexipages_depth_value" value="<?php echo $options['depth_value'] ?>" size="1" maxlength="1" /></td></tr>
	</table>
	</td>
</tr>


</table>
<input type="hidden" name="flexipages_submit" value="1" />
<p style="font-size:0.8em;font-style:italic"><a href="http://srinig.com/wordpress-plugins/flexi-pages/">Flexi Pages widget</a> by Srini G</p>
<?php
	}

	//function adapted from wp-admin/admin-functions.php/function parent_dropdown()
	function flexipages_exclude_options(
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
				flexipages_exclude_options( $sort_column, $sort_order, $selected, $item->ID,  $level +1 );
			}
		} else {
			return false;
		}
	}

	register_sidebar_widget(array('Flexi Pages', 'widgets'), 'flexipages_widget');
	register_widget_control('Flexi Pages', 'flexipages_widget_control', 400, 420);
}

add_action('plugins_loaded', 'flexipages_init');
?>
