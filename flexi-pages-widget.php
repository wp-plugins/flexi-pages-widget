<?php
/*
Plugin Name: Flexi Pages Widget
Plugin URI: http://srinig.com/wordpress/plugins/flexi-pages/
Description: A highly configurable WordPress sidebar widget to list pages and sub-pages. User friendly widget control comes with various options. 
Version: 1.5.6
Author: Srini G
Author URI: http://srinig.com/wordpress
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
	return array(
		'title' => __('Pages', 'flexipages'), 
		'sort_column' => 'post_title', 
		'sort_order' => 'ASC', 
		'exinclude' => 'exclude', 
		'exinclude_values' => '', 
		'show_subpages_check' => 'on', 
		'show_subpages' => -2, 
		'hierarchy' => 'on', 
		'depth' => 0, 
		'show_home_check' => 'on',
		'show_home' => __('Home', 'flexipages'), 
		'show_date' => 'off'
	);
}

function flexipages_wp_head()
{
	global $post;
	global $flexipages_post;
	$flexipages_post = $post;
}

add_action('wp_head', 'flexipages_wp_head');

function flexipages_currpage_hierarchy()
{
	if(is_home() && !is_front_page()) {
		if($curr_page_id = get_option('page_for_posts'))
			$curr_page = &get_post($curr_page_id);
	}
	else if( is_page() ) {
		global $flexipages_post;
		$curr_page = $flexipages_post;
	}
	else
		return array();


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



function flexipages_pageids()
{
	global $wpdb;
	$page_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish'" );
	return $page_ids;
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


function flexipages($args='')
{
	$key_value = explode('&', $args);
	$options = array();
	foreach($key_value as $value) {
		$x = explode('=', $value);
		$options[$x[0]] = $x[1]; // $options['key'] = 'value';
	}
	
	if($options['exclude'])
		$exclude = explode(',',$options['exclude']);
	else
		$exclude = array();
	
	
	if( $options['depth'] == -2 || $options['show_subpages'] == -2 || !isset($options['depth']))  { // display subpages only in related pages
	
		
		$hierarchy = flexipages_currpage_hierarchy();
		
			
		$subpages = flexipages_get_subpages();

		foreach ($subpages as $subpage) { //loop through the sub pages
			// if the parent of any of the subpage is not in our hierarchy,
			// add it to the exclusion list
			if ( !in_array ($subpage['post_parent'], $hierarchy) )
				$exclude[] = $subpage['ID'];
		}
	}
	else if( $options['depth'] == -3 || $options['show_subpages'] == -3 )  { // display subpages only in related pages
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
	}
	
	if($options['depth'] < -1 || !isset($options['depth']))
		$options['depth'] = 0;


	if($options['include']) {
		$include = explode(',', $options['include']);
		$page_ids = flexipages_pageids();
		foreach($page_ids as $page_id) {
			if(!in_array($page_id, $include) && !in_array($page_id, $exclude))
				$exclude[] = $page_id;
		}
		$options['include'] = '';	
	}

	if($exclude)
		$options['exclude'] = implode(',', $exclude);


	if($options['title_li']) {
		$title_li = $options['title_li'];
		$options['title_li'] = "";
	}
	
	if(!$options['date_format'])
		$options['date_format'] = get_option('date_format');
		
	if($options['show_home']) {
		$display .="<li class=\"page_item";
		if(is_home()) $display .= " current_page_item";
		$display .= "\"><a href=\"".get_bloginfo('home')."\" title=\"".wp_specialchars(get_bloginfo('name'), 1) ."\">".$options['show_home']."</a></li>\n";
	}

	foreach($options as $key => $value) {
		if($key == 'home_link' || $key == 'echo')
			continue;
		if($opts) $opts .= '&';
		$opts .= $key.'='.$value;
	}

	$display .= wp_list_pages('echo=0&'.$opts);
	
	if($title_li && $display) 
		$display = "<li>".$title_li."<ul>\n".$display."</ul></li>";
	if(isset($options['echo']) && $options['echo'] == 0)
		return $display;
	else
		echo $display;
}


function flexipages_init()
{

	if(function_exists('load_plugin_textdomain'))
		load_plugin_textdomain('flexipages', 'wp-content/plugins/flexi-pages-widget/languages/');

	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	function flexipages_widget($args, $widget_args = 1)
	{
		extract( $args, EXTR_SKIP );
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );
	
		$options = get_option('flexipages_widget');
		if ( !isset($options[$number]) )
			$options[$number] = flexipages_options_default();
			
		extract($options[$number]);
		
		$title = apply_filters('widget_title', $options[$number]['title']);
		
		if($exinclude == 'include')
			$include = $exinclude_values;
		else
			$exclude = $exinclude_values;
			
		if($hierarchy == 'off' || !$hierarchy)
			$depth = -1;
		
		if($show_subpages_check == 'off' || !$show_subpages_check) {
			$depth = 1;
			$show_subpages = '';
		}
		
		if($home_link)
			$show_home = $home_link;
		else if ($show_home_check != 'on')
			$show_home = '';
		else if ($show_home_check == on && !$show_home)
			$show_home = __('Home');
			
			

		if($pagelist = flexipages('echo=0&title_li=&sort_column='.$sort_column.'&sort_order='.$sort_order.'&include='.$include.'&exclude='.$exclude.'&depth='.$depth.'&show_home='.$show_home.'&show_date='.$show_date.'&date_format='.$date_format.'&show_subpages='.$show_subpages)) {
		
			echo $before_widget;

			if($title && $pagelist)
				echo $before_title . $title . $after_title;

			echo "<ul>\n". $pagelist . "</ul>\n";

			echo $after_widget;
		}
	}
	
	function flexipages_widget_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = get_option('flexipages_widget');
		if ( !is_array($options) )
			$options = array();

		if ( !$updated && !empty($_POST['sidebar']) ) {
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( (array) $this_sidebar as $_widget_id ) {
				if ( 'flexipages_widget' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "flexipages-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}

			foreach ( (array) $_POST['flexipages_widget'] as $widget_number => $flexipages_widget ) {
				if ( !isset($flexipages_widget['title']) && isset($options[$widget_number]) ) // user clicked cancel
					continue;
				$title = strip_tags(stripslashes($flexipages_widget['title']));
				$sort_column = strip_tags(stripslashes($flexipages_widget['sort_column']));
				$sort_order = strip_tags(stripslashes($flexipages_widget['sort_order']));
				$exinclude = strip_tags(stripslashes($flexipages_widget['exinclude']));
				$exinclude_values = $flexipages_widget['exinclude_values']?implode(',', $flexipages_widget['exinclude_values']):'';
				$show_subpages_check = strip_tags(stripslashes($flexipages_widget['show_subpages_check']));
				$show_subpages = strip_tags(stripslashes($flexipages_widget['show_subpages']));
				$hierarchy = strip_tags(stripslashes($flexipages_widget['hierarchy']));
				$depth = strip_tags(stripslashes($flexipages_widget['depth']));
				$show_home_check = strip_tags(stripslashes($flexipages_widget['show_home_check']));
				$show_home = strip_tags(stripslashes($flexipages_widget['show_home']));
				$show_date = strip_tags(stripslashes($flexipages_widget['show_date']));
				$date_format = strip_tags(stripslashes($flexipages_widget['date_format']));
				
				$options[$widget_number] = compact('title', 'sort_column', 'sort_order', 'exinclude', 'exinclude_values', 'show_subpages_check', 'show_subpages', 'hierarchy', 'depth', 'show_home_check', 'show_home', 'show_date', 'date_format');
			}

			update_option('flexipages_widget', $options);
			$updated = true;
		}

		if ( -1 == $number ) {
			$number = '%i%';
			$options[$number] = flexipages_options_default();
		}
		
		$title = attribute_escape($options[$number]['title']);
		$sort_column_select[$options[$number]['sort_column']] = " selected=\"selected\"";
		$sort_order_select[$options[$number]['sort_order']] = " selected=\"selected\"";
		$exinclude_select[$options[$number]['exinclude']] = ' selected="selected"';
		$show_subpages_check_check = ($options[$number]['show_subpages_check'] == 'on')?' checked="checked"':'';
		if($options[$number]['depth'] == -2)
			$show_subpages_select[-2] = ' selected="selected"';
		else if($options[$number]['depth'] == -3)
			$show_subpages_select[-3] = ' selected="selected"';
		else
			$show_subpages_select[$options[$number]['show_subpages']] = ' selected="selected"';
		$show_subpages_display = $show_subpages_check_check?'':' style="display:none;"';
		$hierarchy_check = ($options[$number]['hierarchy'] == 'on')?' checked="checked"':'';
		if(in_array($options[$number]['depth'], array(0, 2, 3, 4, 5)))
			$depth_select[$options[$number]['depth']] = ' selected="selected"';
		else
			$depth_select[0] = ' selected="selected"';
		$depth_display = $hierarchy_check?'':' style="display:none;"';
		$show_home_check_check = ($options[$number]['home_link'] || $options[$number]['show_home_check'] == 'on')?' checked="checked"':'';
		$show_home_display = $show_home_check_check?'':' style="display:none;"';
		$show_home = isset($options[$number]['home_link'])?attribute_escape($options[$number]['home_link']):attribute_escape($options[$number]['show_home']);
		$show_date_check = ($options[$number]['show_date'] == 'on')?' checked="checked"':'';
		$date_format_display = $show_date_check?'':' style="display:none;"';
		$date_format_select[$options[$number]['date_format']] = ' selected="selected"';
		$date_format_options = array('j F Y', 'F j, Y', 'Y/m/d', 'd/m/Y', 'm/d/Y');
		
		?>
		<table cellpadding="10px" cellspacing="10px">
			<tr>
				<td><label for="flexipages-title-<?php echo $number; ?>"><?php _e('Title', 'flexipages'); ?></label></td>
				<td><input class="widefat" id="flexipages-title-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" /></td>
			</tr>
			
			<tr>
				<td valign="top"><label for="flexipages-sort_column-<?php echo $number; ?>"><?php _e('Sort by', 'flexipages'); ?></label></td>
				<td><select class="widefat" style="display:inline;width:auto;" name="flexipages_widget[<?php echo $number; ?>][sort_column]" id="flexipages-sort_column-<?php echo $number; ?>">
					<option value="post_title"<?php echo $sort_column_select['post_title']; ?>><?php _e('Page title', 'flexipages'); ?></option>
					<option value="menu_order"<?php echo $sort_column_select['menu_order']; ?>><?php _e('Menu order', 'flexipages'); ?></option>
					<option value="post_date"<?php echo $sort_column_select['post_date']; ?>><?php _e('Date created', 'flexipages'); ?></option>
					<option value="post_modified"<?php echo $sort_column_select['post_modified']; ?>><?php _e('Date modified', 'flexipages'); ?></option>
					<option value="ID"<?php echo $sort_column_select['ID']; ?>><?php _e('Page ID', 'flexipages'); ?></option>	
					<option value="post_author"<?php echo $sort_column_select['post_author']; ?>><?php _e('Page author ID', 'flexipages'); ?></option>
					<option value="post_name"<?php echo $sort_column_select['post_name']; ?>><?php _e('Page slug', 'flexipages'); ?></option>
				</select>
				<select class="widefat" style="display:inline;width:auto;" name="flexipages_widget[<?php echo $number; ?>][sort_order]" id="flexipages-sort_order-<?php echo $number; ?>">
					<option value="ASC"<?php echo $sort_order_select['ASC']; ?>><?php _e('ASC', 'flexipages'); ?></option>
					<option value="DESC"<?php echo $sort_order_select['DESC']; ?>><?php _e('DESC', 'flexipages'); ?></option>
				</select></td>
			</tr>
			<tr>			
				<td valign="top"><select class="widefat" style="display:inline;width:auto;" name="flexipages_widget[<?php echo $number; ?>][exinclude]" id="flexipages-exinclude-<?php echo $number; ?>">
					<option value="exclude"<?php echo $exinclude_select['exclude']; ?>><?php _e('Exclude', 'flexipages'); ?></option>
					<option value="include"<?php echo $exinclude_select['include']; ?>><?php _e('Include', 'flexipages'); ?></option>
				</select><?php _e('pages', 'flexipages'); ?></td>
				<td><select name="flexipages_widget[<?php echo $number; ?>][exinclude_values][]" id="flexipages-exinclude_values-<?php echo $number; ?> class="widefat" style="height:auto;max-height:6em" multiple="multiple" size="4">
					<?php flexipages_exinclude_options($options[$number]['sort_column'], $options[$number]['sort_order'], explode(',', $options[$number]['exinclude_values']),0,0) ?>
				</select><br />
				<small class="setting-description"><?php _e('use &lt;Ctrl&gt; key to select multiple pages', 'flexipages'); ?></small>
				</td>
			</tr>
			<tr>
				<td  style="padding:5px 0;"><label for="flexipages-show_subpages_check-<?php echo $number; ?>"><input type="checkbox" class="checkbox" id="flexipages-show_subpages_check-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][show_subpages_check]" onchange="if(this.checked) { getElementById('flexipages-show_subpages-<?php echo $number; ?>').style.display='block'; } else { getElementById('flexipages-show_subpages-<?php echo $number; ?>').style.display='none'; }"<?php echo $show_subpages_check_check; ?> /> <?php _e('Show sub-pages', 'flexipages'); ?></label></td>
				<td><select<?php echo $show_subpages_display; ?> class="widefat" id="flexipages-show_subpages-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][show_subpages]">
						<option value="0"<?php echo $show_subpages_select[0]; ?>><?php _e('Show all sub-pages', 'flexipages'); ?></option>
						<option value="-2"<?php echo $show_subpages_select[-2]; ?>><?php _e('Only related sub-pages', 'flexipages'); ?></option>
						<option value="-3"<?php echo $show_subpages_select[-3]; ?>><?php _e('Only strictly related sub-pages', 'flexipages'); ?></option>
						
					</select>
				</td>
			</tr>	
			<tr>
				<td style="padding:5px 0;"><label for="flexipages-hierarchy-<?php echo $number; ?>"><input type="checkbox" class="checkbox" id="flexipages-hierarchy-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][hierarchy]" onchange="if(this.checked) { getElementById('flexipages-depth-<?php echo $number; ?>').style.display='block'; } else { getElementById('flexipages-depth-<?php echo $number; ?>').style.display='none'; }"<?php echo $hierarchy_check; ?> /> <?php _e('Show hierarchy', 'flexipages'); ?></label></td>
				<td>
					<select<?php echo $depth_display; ?> class="widefat" id="flexipages-depth-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][depth]">
					<?php for($i=2;$i<=5;$i++) { ?>
						<option value="<?php echo $i; ?>"<?php echo $depth_select[$i]; ?>><?php printf(__('%d levels deep', 'flexipages'), $i); ?></option>
					<?php } ?>
					<option value="0"<?php echo $depth_select[0]; ?>><?php _e('Unlimited depth', 'flexipages'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td style="padding:5px 0;"><label for="flexipages-show_home_check-<?php echo $number; ?>"><input type="checkbox" class="checkbox" id="flexipages-show_home_check-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][show_home_check]" onchange="if(this.checked) { getElementById('flexipages-show_home-<?php echo $number; ?>').style.display='block'; } else { getElementById('flexipages-show_home-<?php echo $number; ?>').style.display='none'; }"<?php echo $show_home_check_check; ?> /> <?php _e('Show home page', 'flexipages'); ?></label></td>
				<td><input<?php echo $show_home_display; ?> class="widefat" type="text" name="flexipages_widget[<?php echo $number; ?>][show_home]" id ="flexipages-show_home-<?php echo $number; ?>" value="<?php echo htmlspecialchars($show_home, ENT_QUOTES); ?>" /></td>	
			</tr>
			<tr>
			<td style="padding:5px 0;"><label for="flexipages-show_date-<?php echo $number; ?>"><input type="checkbox" class="checkbox" id="flexipages-show_date-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][show_date]" onchange="if(this.checked) { getElementById('flexipages-date_format-<?php echo $number; ?>').style.display='block'; } else { getElementById('flexipages-date_format-<?php echo $number; ?>').style.display='none'; }"<?php echo $show_date_check; ?> /> <?php _e('Show date', 'flexipages'); ?></label></td>
			<td><select<?php echo $date_format_display; ?> class="widefat" id="flexipages-date_format-<?php echo $number; ?>" name="flexipages_widget[<?php echo $number; ?>][date_format]" text="Select format">
				<option value=""><?php _e('Choose Format', 'flexipages'); ?></option>
				<?php foreach($date_format_options as $date_format_option) { ?>
					<option value="<?php echo $date_format_option; ?>"<?php echo $date_format_select[$date_format_option]; ?>><?php echo date($date_format_option); ?></option>
				<?php } ?>
			</select>
			</td>
			</tr>
		</table>
			<p>	
				<input type="hidden" name="flexipages_widget[<?php echo $number; ?>][submit]" value="1" />
			</p>
		<?php
	}
	
	function flexipages_widget_register()
	{
		if ( !$options = get_option('flexipages_widget') )
			$options = array();
		$widget_ops = array('classname' => 'flexipages_widget', 'description' => __('A highly configurable widget to list pages and sub-pages.', 'flexipages'));
		$control_ops = array('width' => '380', 'height' => '', 'id_base' => 'flexipages');
		$name = 'Flexi Pages';

		$id = false;
		foreach ( (array) array_keys($options) as $o ) {
			if ( !isset($options[$o]['title']) )
				continue;
			$id = "flexipages-$o";
			wp_register_sidebar_widget($id, $name, 'flexipages_widget', $widget_ops, array( 'number' => $o ));
			wp_register_widget_control($id, $name, 'flexipages_widget_control', $control_ops, array( 'number' => $o ));
		}

		if ( !$id ) {
			wp_register_sidebar_widget( 'flexipages-1', $name, 'flexipages_widget', $widget_ops, array( 'number' => -1 ) );
			wp_register_widget_control( 'flexipages-1', $name, 'flexipages_widget_control', $control_ops, array( 'number' => -1 ) );
		}	
	}
	
	flexipages_widget_register();	
}

add_action('plugins_loaded', 'flexipages_init');
?>
