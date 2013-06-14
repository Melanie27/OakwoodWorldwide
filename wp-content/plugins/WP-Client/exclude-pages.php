<?php

define('EPW_PLUGIN_DIR', $wpc_client->plugin_dir);

// Option name for exclusion data
define('EPW_OPTION_NAME', 'wpc_epw_exclude_pages');

// Separator for the string of IDs stored in the option value
define('EPW_OPTION_SEP', ',');

// The textdomain for the WP i18n gear
define( 'EPW_TD', 'exclude-pages' );

// Take the pages array, and return the pages array without the excluded pages
// Doesn't do this when in the admin area
function wpc_client_epw_exclude_pages( $pages ) {
	// If the URL includes "wp-admin", just return the unaltered list
	// This constant, WP_ADMIN, only came into WP on 2007-12-19 17:56:16 rev 6412, i.e. not something we can rely upon unfortunately.
	// May as well check it though.
	// Also check the URL... let's hope they haven't got a page called wp-admin (probably not)
	// SWTODO: Actually, you can create a page with an address of wp-admin (which is then inaccessible), I consider this a bug in WordPress (which I may file a report for, and patch, another time).

	$bail_out = ( ( defined( 'WP_ADMIN' ) && WP_ADMIN == true ) || ( strpos( $_SERVER[ 'PHP_SELF' ], 'wp-admin' ) !== false ) );

	$bail_out = apply_filters( 'epw_admin_bail_out', $bail_out );

	if ( $bail_out ) return $pages;

	$excluded_ids = wpc_client_epw_get_excluded_ids();

	$length = count($pages);

	// Ensure we catch all descendant pages, so that if a parent
	// is hidden, it's children are too.

	for ( $i=0; $i<$length; $i++ ) {

		$page = & $pages[$i];

		// If one of the ancestor pages is excluded, add it to our exclude array
		if ( wpc_client_epw_ancestor_excluded( $page, $excluded_ids, $pages ) ) {

			// Can't actually delete the pages at the moment,
			// it'll screw with our recursive search.
			// For the moment, just tag the ID onto our excluded IDs
			$excluded_ids[] = $page->ID;
		}
	}

	// Ensure the array only has unique values
	$delete_ids = array_unique( $excluded_ids );

	// Loop though the $pages array and actually unset/delete stuff
	for ( $i=0; $i<$length; $i++ ) {

		$page = & $pages[$i];

		// If one of the ancestor pages is excluded, add it to our exclude array
		if ( in_array( $page->ID, $delete_ids ) ) {

			// Finally, delete something(s)
			unset( $pages[$i] );
		}
	}

	// Reindex the array, for neatness
	// SWFIXME: Is reindexing the array going to create a memory optimisation problem for large arrays of WP post/page objects?
	if ( ! is_array( $pages ) ) $pages = (array) $pages;

	$pages = array_values( $pages );

	return $pages;
}


/**
 * Recurse down an ancestor chain, checking if one is excluded
 *
 * @param
 * @return boolean|int The ID of the "nearest" excluded ancestor, otherwise false
 * @author Simon Wheatley
 **/
function wpc_client_epw_ancestor_excluded( $page, $excluded_ids, $pages ) {

	$parent = & wpc_client_epw_get_page( $page->post_parent, $pages );

	// Is there a parent?
	if ( ! $parent )
		return false;

	// Is it excluded?
	if ( in_array( $parent->ID, $excluded_ids ) )
		return (int) $parent->ID;

	// Is it the homepage?
	if ( $parent->ID == 0 )
		return false;

	// Otherwise we have another ancestor to check
	return wpc_client_epw_ancestor_excluded( $parent, $excluded_ids, $pages );
}


/**
 * {no description}
 *
 * @param int $page_id The ID of the WP page to search for
 * @param array $pages An array of WP page objects
 * @return boolean|object the page from the $pages array which corresponds to the $page_id
 * @author Simon Wheatley
 **/
function wpc_client_epw_get_page( $page_id, $pages ) {

	// PHP 5 would be much nicer here, we could use foreach by reference, ah well.
	$length = count($pages);

	for ( $i=0; $i<$length; $i++ ) {
		$page = & $pages[$i];
		if ( $page->ID == $page_id ) return $page;
	}

	// Unusual.
	return false;
}



// Is this page we're editing (defined by global $post_ID var)
// currently NOT excluded (i.e. included),
// returns true if NOT excluded (i.e. included)
// returns false is it IS excluded.
// (Tricky this upside down flag business.)
function wpc_client_epw_this_page_included() {
	global $post_ID;

	// New post? Must be included then.
	if ( ! $post_ID ) return true;

	$excluded_ids = wpc_client_epw_get_excluded_ids();

	// If there's no exclusion array, we can return true
	if ( empty($excluded_ids) ) return true;

	// Check if our page is in the exclusion array
	// The bang (!) reverses the polarity [1] of the boolean
	return ! in_array( $post_ID, $excluded_ids );

	// fn1. (of the neutron flow, ahem)
}


// Check the ancestors for the page we're editing (defined by
// global $post_ID var), return the ID if the nearest one which
// is excluded (if any);
function wpc_client_epw_nearest_excluded_ancestor() {
	global $post_ID, $wpdb;
	// New post? No problem.
	if ( ! $post_ID ) return false;

	$excluded_ids = wpc_client_epw_get_excluded_ids();

	// Manually get all the pages, to avoid our own filter.
	$sql = "SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'page'";

	$pages = $wpdb->get_results( $sql );

	// Start recursively checking the ancestors
	$parent = wpc_client_epw_get_page( $post_ID, $pages );

	return wpc_client_epw_ancestor_excluded( $parent, $excluded_ids, $pages );
}


function wpc_client_epw_get_excluded_ids() {

	$exclude_ids_str = get_option( EPW_OPTION_NAME );

	// No excluded IDs? Return an empty array
	if ( empty($exclude_ids_str) ) return array();

	// Otherwise, explode the separated string into an array, and return that
	return explode( EPW_OPTION_SEP, $exclude_ids_str );
}



// This function gets all the exclusions out of the options
// table, updates them, and resaves them in the options table.
// We're avoiding making this a postmeta (custom field) because we
// don't want to have to retrieve meta for every page in order to
// determine if it's to be excluded. Storing all the exclusions in
// one row seems more sensible.
function wpc_client_epw_update_exclusions( $post_ID ) {

	// Bang (!) to reverse the polarity of the boolean, turning include into exclude
	$exclude_this_page = ! (bool) @ $_POST['wpc_client_epw_this_page_included'];

	// SWTODO: Also check for a hidden var, which confirms that this checkbox was present
	// If hidden var not present, then default to including the page in the nav (i.e. bomb out here rather
	// than add the page ID to the list of IDs to exclude)

	$ctrl_present = (bool) @ $_POST['epw_ctrl_present'];

	if ( ! $ctrl_present )
		return;

	$excluded_ids = wpc_client_epw_get_excluded_ids();

	// If we need to EXCLUDE the page from the navigation...
	if ( $exclude_this_page ) {
		// Add the post ID to the array of excluded IDs
		array_push( $excluded_ids, $post_ID );

		// De-dupe the array, in case it was there already
		$excluded_ids = array_unique( $excluded_ids );
	}

	// If we need to INCLUDE the page in the navigation...
	if ( ! $exclude_this_page ) {
		// Find the post ID in the array of excluded IDs
		$index = array_search( $post_ID, $excluded_ids );

		// Delete any index found
		if ( $index !== false ) unset( $excluded_ids[$index] );
	}

	$excluded_ids_str = implode( EPW_OPTION_SEP, $excluded_ids );

	wpc_client_epw_set_option( EPW_OPTION_NAME, $excluded_ids_str, __( "Comma separated list of post and page IDs to exclude when returning pages from the get_pages function.", "exclude-pages" ) );
}


// Take an option, delete it if it exists, then add it.
function wpc_client_epw_set_option( $name, $value, $description ) {

	// Delete option
	delete_option($name);

	// Insert option
	add_option($name, $value, $description);
}



/**
 * Callback function for the metabox on the page edit screen.
 *
 * @return void
 * @author Simon Wheatley
 **/
function wpc_client_epw_admin_sidebar_wp25() {

	$nearest_excluded_ancestor = wpc_client_epw_nearest_excluded_ancestor();

	echo '	<div id="excludepagediv" class="new-admin-wp25">';
	echo '		<div class="outer"><div class="inner">';
	echo '		<p><label for="wpc_client_epw_this_page_included" class="selectit">';
	echo '		<input ';
	echo '			type="checkbox" ';
	echo '			name="wpc_client_epw_this_page_included" ';
	echo '			id="wpc_client_epw_this_page_included" ';

	if ( wpc_client_epw_this_page_included() )
		echo 'checked="checked"';

	echo ' />';
	echo '			'.__( 'Include this page in lists of pages', EPW_TD ).'</label>';
	echo '		<input type="hidden" name="epw_ctrl_present" value="1" /></p>';

	if ( $nearest_excluded_ancestor !== false ) {
		echo '<p class="epw_exclude_alert"><em>';
		printf( __( 'N.B. An ancestor of this page is excluded, so this page is too (<a href="%1$s" title="%2$s">edit ancestor</a>).', EPW_TD), "post.php?action=edit&amp;post=$nearest_excluded_ancestor", __( 'edit the excluded ancestor', EPW_TD ) );
		echo '</em></p>';
	}

	// If there are custom menus (WP 3.0+) then we need to clear up some
	// potential confusion here.
	if ( wpc_client_epw_has_menu() ) {
		echo '<p id="epw_custom_menu_alert"><em>';

		if ( current_user_can( 'edit_theme_options' ) )
			printf( __( 'N.B. This page can still appear in explicitly created <a href="%1$s">menus</a> (<a id="epw_toggle_more" href="#epw_explain_more">explain more</a>)', EPW_TD),
				"nav-menus.php" );
		else
			_e( 'N.B. This page can still appear in explicitly created menus (<a id="epw_toggle_more" href="#epw_explain_more">explain more</a>)', EPW_TD);

		echo '</em></p>';

		echo '<div id="epw_explain_more"><p>';

		if ( current_user_can( 'edit_theme_options' ) )
			printf( __( 'WordPress provides a simple function for you to maintain your site <a href="%1$s">menus</a>. If you create a menu which includes this page, the checkbox above will not have any effect on the visibility of that menu item.', EPW_TD),
				"nav-menus.php" );
		else
			_e( 'WordPress provides a simple function for you to maintain the site menus, which your site administrator is using. If a menu includes this page, the checkbox above will not have any effect on the visibility of that menu item.', EPW_TD);

		echo '</p><p>';

		echo _e( 'If you think you no longer need the Exclude Pages plugin you should talk to your WordPress administrator about disabling it.', EPW_TD );

		echo '</p></div>';
	}

	echo '		</div><!-- .inner --></div><!-- .outer -->';
	echo '	</div><!-- #excludepagediv -->';
}


/**
 * A conditional function to determine whether there are any menus
 * defined in this WordPress installation.
 *
 * @return bool Indicates the presence or absence of menus
 * @author Simon Wheatley
 **/
function wpc_client_epw_has_menu() {

	if ( ! function_exists( 'wp_get_nav_menus' ) )
		return false;

	$menus = wp_get_nav_menus();

	foreach ( $menus as $menu_maybe ) {
		if ( $menu_items = wp_get_nav_menu_items($menu_maybe->term_id) )
			return true;
	}
}


/**
 * Hooks the WordPress admin_head action to inject some CSS.
 *
 * @return void
 * @author Simon Wheatley
 **/
function wpc_client_epw_admin_css() {

	echo '

        <style type="text/css" media="screen">

	        .epw_exclude_alert { font-size: 11px; }

	        .new-admin-wp25 { font-size: 11px; background-color: #fff; }

	        .new-admin-wp25 .inner {  padding: 8px 12px; background-color: #EAF3FA; border: 1px solid #EAF3FA; -moz-border-radius: 3px; -khtml-border-bottom-radius: 3px; -webkit-border-bottom-radius: 3px; border-bottom-radius: 3px; }

	        #epw_admin_meta_box .inner {  padding: inherit; background-color: transparent; border: none; }

	        #epw_admin_meta_box .inner label { background-color: none; }

	        .new-admin-wp25 .exclude_alert { padding-top: 5px; }

	        .new-admin-wp25 .exclude_alert em { font-style: normal; }

        </style>

    ';
}


/**
 * Hooks the WordPress admin_head action to inject some JS.
 *
 * @return void
 * @author Simon Wheatley
 **/
function wpc_client_epw_admin_js() {

	echo '

        <script type="text/javascript">

        //<![CDATA[

	        jQuery( "#epw_explain_more" ).hide();

	        jQuery( "#epw_toggle_more" ).click( function() {

		        jQuery( "#epw_explain_more" ).toggle();

		        return false;

	        } );

        //]]>

        </script>

    ';
}


// Add our ctrl to the list of controls which AREN'T hidden
function wpc_client_epw_hec_show_dbx( $to_show ) {
	array_push( $to_show, 'excludepagediv' );
	return $to_show;
}


// PAUSE & RESUME FUNCTIONS
function wpc_client_pause_exclude_pages() {
	remove_filter('get_pages','wpc_client_epw_exclude_pages');
}


function wpc_client_resume_exclude_pages() {
	add_filter('get_pages','wpc_client_epw_exclude_pages');
}


// INIT FUNCTIONS
function wpc_client_epw_init() {
    global $wpc_client;
	// Call this function on the get_pages filter
	// (get_pages filter appears to only be called on the "consumer" side of WP,
	// the admin side must use another function to get the pages. So we're safe to
	// remove these pages every time.)
	add_filter('get_pages','wpc_client_epw_exclude_pages');

	// Load up the translation gear
	$locale = get_locale();

	$folder = rtrim( basename( $wpc_client->plugin_dir ), '/' );

	$mo_file = trailingslashit( WP_PLUGIN_DIR ) . "$folder/locale/" . EPW_TD . "-$locale.mo";

	load_textdomain( EPW_TD, $mo_file );
}


function wpc_client_epw_admin_init() {
	// Add panels into the editing sidebar(s)
	global $wp_version;

	add_meta_box('epw_admin_meta_box', __( 'Exclude Pages', EPW_TD ), 'wpc_client_epw_admin_sidebar_wp25', 'page', 'side', 'low');

	// Set the exclusion when the post is saved
	add_action('save_post', 'wpc_client_epw_update_exclusions');

	// Add the JS & CSS to the admin header
	add_action('admin_head', 'wpc_client_epw_admin_css');
	add_action('admin_footer', 'wpc_client_epw_admin_js');

	// Call this function on our very own hec_show_dbx filter
	// This filter is harmless to add, even if we don't have the
	// Hide Editor Clutter plugin installed as it's using a custom filter
	// which won't be called except by the HEC plugin.
	// Uncomment to show the control by default
	// add_filter('hec_show_dbx','wpc_client_epw_hec_show_dbx');
}


// HOOK IT UP TO WORDPRESS

//add_action( 'init', 'wpc_client_epw_init' );

//add_action( 'admin_init', 'wpc_client_epw_admin_init' );