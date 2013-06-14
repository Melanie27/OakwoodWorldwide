<?php
$rul_local_only = 1;

// Allow a POST or GET "redirect_to" variable to take precedence over settings within the plugin
$rul_allow_post_redirect_override = false;

// Allow a POST or GET logout "redirect_to" variable to take precedence over settings within the plugin
$rul_allow_post_redirect_override_logout = false;

// Set this to true if you're using a plugin such as Gigya that bypasses the regular WordPress redirect process (and only allow one fixed redirect URL)
// Then, set that plugin to redirect to http://www.yoursite.com/wp-content/plugins/peters-login-redirect/wplogin_redirect_control.php
// For more troubleshooting with this setting, make sure the paths are set correctly in wplogin_redirect_control.php
$rul_use_redirect_controller = false;

// To edit the redirect settings in the WordPress admin panel, users need this capability
// Typically editors and up have "manage_links" capabilities
// See http://codex.wordpress.org/Roles_and_Capabilities for more information about out of the box capabilities
$rul_required_capability = 'manage_links';

/*
--------------
All other settings are configured in Settings > Login/logout redirects in the WordPress admin panel
--------------
*/

// Enable translations
add_action( 'init', 'wpc_client_rul_textdomain' );

function wpc_client_rul_textdomain() {
    global $wpc_client;
	load_plugin_textdomain( 'wp_clients', PLUGINDIR . '/' . dirname( plugin_basename( $wpc_client->plugin_dir ) ), dirname( plugin_basename( $wpc_client->plugin_dir ) ) );
}

global $wpdb, $rul_db_addresses, $rul_version;

// Name of the database table that will hold Client Circle information and moderator rules
$rul_db_addresses = $wpdb->prefix . 'wpc_client_login_redirects';
$rul_version = '2.2.0';

// A global variable that we will add to on the fly when $rul_local_only is set to equal 1
$rul_allowed_hosts = array();

// Some helper functions, all "public static" in PHP5 land
class wpc_rulRedirectFunctionCollection {
    // Thanks to http://wordpress.org/support/topic/97314 for this function
    // This extra function is necessary to support the use case where someone was previously logged in
    function wpc_redirect_current_user_can($capability, $current_user) {
        global $wpdb;

        $roles          = get_option($wpdb->prefix . 'user_roles');
        $user_roles     = $current_user->{$wpdb->prefix . 'capabilities'};
        $user_roles     = array_keys($user_roles, true);
        $role           = $user_roles[0];
        $capabilities   = $roles[$role]['capabilities'];

        if ( in_array( $capability, array_keys( $capabilities, true) ) ) {
            // check array keys of capabilities for match against requested capability
            return true;
        }
        return false;
    }

    // A generic function to return the value mapped to a particular variable
    function wpc_rul_get_variable( $variable, $user ) {
        switch( $variable ) {
            // Returns the current user's username (only use this if you know they're logged in)
            case 'username':
            default:
                return rawurlencode( $user->user_login );
                break;
        }
    }

    // Replaces the syntax [wpclients]variable_name[/wpclients] with whatever has been mapped to the variable_name in the wpc_rul_get_variable function
    function wpc_rul_replace_variable( $string, $user ) {
        preg_match_all( "/\[wpclients\](.*?)\[\/wpclients\]/is", $string, $out );

        foreach( $out[0] as $instance => $full_match ) {
            $replaced_variable = wpc_rulRedirectFunctionCollection::wpc_rul_get_variable( $out[1][ $instance ], $user );
            $string = str_replace( $full_match, $replaced_variable, $string );
        }

        return $string;
    }
    // Allow users to be redirected to external URLs as specified by redirect rules
    function wpc_rul_trigger_allowed_host( $url ) {
        global $rul_allowed_hosts;

        $url_parsed = parse_url( $url );

        if( isset( $url_parsed[ 'host' ] ) ) {
            $rul_allowed_hosts[] = $url_parsed[ 'host' ];
            add_filter( 'allowed_redirect_hosts', array( 'wpc_rulRedirectFunctionCollection', 'wpc_rul_add_allowed_host' ), 10, 1 );
        }
    }

	function wpc_rul_add_allowed_host( $hosts ) {
        global $rul_allowed_hosts;
        return array_merge( $hosts, $rul_allowed_hosts );
    }
}

// Functions specific to logout redirecting
class wpc_rulLogoutFunctionCollection {
    function logout_redirect() {
        global $rul_allow_post_redirect_override_logout, $rul_local_only;

        $requested_redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : false;

        if( is_user_logged_in() && ( !$requested_redirect_to || !$rul_allow_post_redirect_override_logout ) ) {
            $current_user = wp_get_current_user();
            $rul_url = wpc_rulLogoutFunctionCollection::get_redirect_url( $current_user, $requested_redirect_to );

            if( $rul_url ) {
                if( 1 === $rul_local_only ) {
                    wpc_rulRedirectFunctionCollection::wpc_rul_trigger_allowed_host( $rul_url );
                    wp_safe_redirect( $rul_url );
                    die();
                } elseif( 2 === $rul_local_only ) {
                    wp_redirect( $rul_url );
                    die();
                } else {
                    wp_safe_redirect( $rul_url );
                    die();
                }
            }
        }
        return false;
    }
    // Get the logout redirect URL according to defined rules
    // Functionality for user-, role-, and capability-specific redirect rules is available
    // Note that only the "all other users" redirect URL is currently implemented in the UI
    function get_redirect_url( $user, $requested_redirect_to ) {
        global $wpdb, $rul_db_addresses;

        $redirect_to = false;

        // Check for an extended custom redirect rule
        $rul_custom_redirect = apply_filters( 'rul_before_user_logout', false, $requested_redirect_to, $user );
        if( $rul_custom_redirect ) {
            $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $requested_redirect_to, $user );
            return $redirect_to;
        }

        // Check for a redirect rule for this user
        $rul_user = $wpdb->get_var('SELECT rul_url_logout FROM ' . $rul_db_addresses .
            ' WHERE rul_type = \'user\' AND rul_value = \'' . $user->user_login . '\' LIMIT 1');

        if ( $rul_user ) {
            $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_user, $user );
            return $redirect_to;
        }

        // Check for an extended custom redirect rule
        $rul_custom_redirect = apply_filters( 'rul_before_role_logout', false, $requested_redirect_to, $user );
        if( $rul_custom_redirect ) {
            $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $user );
            return $redirect_to;
        }

        // Check for a redirect rule that matches this user's role
        $rul_roles = $wpdb->get_results('SELECT rul_value, rul_url_logout FROM ' . $rul_db_addresses .
            ' WHERE rul_type = \'role\'', OBJECT);

        if( $rul_roles ) {
            foreach( $rul_roles as $rul_role ) {
                if( '' != $rul_role->rul_url_logout && isset( $user->{$wpdb->prefix . 'capabilities'}[$rul_role->rul_value] ) ) {
                    $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_role->rul_url_logout, $user );
                    return $redirect_to;
                }
            }
        }

        // Check for an extended custom redirect rule
        $rul_custom_redirect = apply_filters( 'rul_before_capability_logout', false, $requested_redirect_to, $user );
        if( $rul_custom_redirect ) {
            $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $user );
            return $redirect_to;
        }

        // Check for a redirect rule that matches this user's capability
        $rul_levels = $wpdb->get_results( 'SELECT rul_value, rul_url_logout FROM ' . $rul_db_addresses .
            ' WHERE rul_type = \'level\' ORDER BY rul_order, rul_value', OBJECT );

        if( $rul_levels ) {
            foreach( $rul_levels as $rul_level ) {
                if( '' != $rul_level->rul_url_logout && wpc_rulRedirectFunctionCollection::wpc_redirect_current_user_can( $rul_level->rul_value, $user ) ) {
                    $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_level->rul_url_logout, $user );
                    return $redirect_to;
                }
            }
        }

        // Check for an extended custom redirect rule
        $rul_custom_redirect = apply_filters( 'rul_before_fallback_logout', false, $requested_redirect_to, $user );
        if( $rul_custom_redirect ) {
            $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $user );
            return $redirect_to;
        }

        // If none of the above matched, look for a rule to apply to all users
        $rul_all = $wpdb->get_var('SELECT rul_url_logout FROM ' . $rul_db_addresses .
            ' WHERE rul_type = \'all\' LIMIT 1');

        if( $rul_all ) {
            $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_all, $user );
            return $redirect_to;
        }

        // No rules matched or existed, so just send them to the WordPress admin panel as usual
        return $redirect_to;
    }
}

// This function wraps around the main redirect function to determine whether or not to bypass the WordPress local URL limitation
function wpc_client_redirect_wrapper( $redirect_to, $requested_redirect_to, $user ) {
    global $rul_local_only, $rul_allow_post_redirect_override;

    // If they're on the login page, don't do anything
    if( !isset( $user->user_login ) ) {
        return $redirect_to;
    }

    //redirection for client staff
    if ( user_can( $user, 'wpc_client_staff' ) && !user_can( $user, 'manage_network_options' ) )  {
        $client_id = get_user_meta( $user->get( 'ID' ), 'parent_client_id', true );
        if ( 0 < $client_id )
            $user = get_userdata( $client_id );
    }

    if( ( admin_url() == $redirect_to && $rul_allow_post_redirect_override ) || !$rul_allow_post_redirect_override ) {
        $rul_url = wpc_client_redirect_to_front_page( $redirect_to, $requested_redirect_to, $user );

        //redirect Client and Staff to my-hub page
        if ( ( user_can( $user, 'wpc_client' ) || user_can( $user, 'wpc_client_staff' ) ) && !user_can( $user, 'manage_network_options' ) )  {
            if ( get_option( 'permalink_structure' ) ) {
                if ( is_multisite() ) {
                    wp_redirect( get_blog_option( get_current_blog_id(), 'home' ) . '/' . wpc_client_get_slug( 'hub' ) );
                    die();
                } else {
                    wp_redirect( get_option( 'home' ) . '/' . wpc_client_get_slug( 'hub' ) );
                    die();
                }
            }
        }

        if( 1 === $rul_local_only ) {
            wpc_rulRedirectFunctionCollection::wpc_rul_trigger_allowed_host( $rul_url );
            return $rul_url;
        } elseif( 2 === $rul_local_only ) {
            wp_redirect( $rul_url );
            die();
        } else {
            return $rul_url;
        }
    } else {
        return $redirect_to;
    }
}

// This function sets the URL to redirect to
function wpc_client_redirect_to_front_page( $redirect_to, $requested_redirect_to, $user ) {
    global $wpdb, $rul_db_addresses;

    // Check for an extended custom redirect rule
    $rul_custom_redirect = apply_filters( 'rul_before_user', false, $redirect_to, $requested_redirect_to, $user );

    if( $rul_custom_redirect ) {
        $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $user );
        return $redirect_to;
    }

    // Check for a redirect rule for this user
    $rul_user = $wpdb->get_var('SELECT rul_url FROM ' . $rul_db_addresses .
        ' WHERE rul_type = \'user\' AND rul_value = \'' . $user->user_login . '\' LIMIT 1');

    if ( $rul_user ) {
        $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_user, $user );
        return $redirect_to;
    }

    // Check for an extended custom redirect rule
    $rul_custom_redirect = apply_filters( 'rul_before_role', false, $redirect_to, $requested_redirect_to, $user );
    if( $rul_custom_redirect ) {
        $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $user );
        return $redirect_to;
    }

    // Check for a redirect rule that matches this user's role
    $rul_roles = $wpdb->get_results('SELECT rul_value, rul_url FROM ' . $rul_db_addresses .
        ' WHERE rul_type = \'role\'', OBJECT);

    if( $rul_roles ) {
        foreach( $rul_roles as $rul_role ) {
            if( '' != $rul_role->rul_url && isset( $user->{$wpdb->prefix . 'capabilities'}[$rul_role->rul_value] ) ) {
                $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_role->rul_url, $user );
                return $redirect_to;
            }
        }
    }

    // Check for an extended custom redirect rule
    $rul_custom_redirect = apply_filters( 'rul_before_capability', false, $redirect_to, $requested_redirect_to, $user );
    if( $rul_custom_redirect ) {
        $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $user );
        return $redirect_to;
    }

    // Check for a redirect rule that matches this user's capability
    $rul_levels = $wpdb->get_results('SELECT rul_value, rul_url FROM ' . $rul_db_addresses .
        ' WHERE rul_type = \'level\' ORDER BY rul_order, rul_value', OBJECT);

    if( $rul_levels ) {
        foreach( $rul_levels as $rul_level ) {
            if( '' != $rul_level->rul_url && wpc_rulRedirectFunctionCollection::wpc_redirect_current_user_can ( $rul_level->rul_value, $user ) ) {
                $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_level->rul_url, $user );
                return $redirect_to;
            }
        }
    }

    // Check for an extended custom redirect rule
    $rul_custom_redirect = apply_filters( 'rul_before_fallback', false, $redirect_to, $requested_redirect_to, $user );
    if( $rul_custom_redirect ) {
        $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_custom_redirect, $user );
        return $redirect_to;
    }

    // If none of the above matched, look for a rule to apply to all users
    $rul_all = $wpdb->get_var('SELECT rul_url FROM ' . $rul_db_addresses .
        ' WHERE rul_type = \'all\' LIMIT 1');

    if( $rul_all ) {
        $redirect_to = wpc_rulRedirectFunctionCollection::wpc_rul_replace_variable( $rul_all, $user );
        return $redirect_to;
    }

    // No rules matched or existed, so just send them to the WordPress admin panel as usual
    return $redirect_to;

}

// Typically this function is used in templates, similarly to the wp_register function
// It returns a link to the administration panel or the one that was custom defined
// If no user is logged in, it returns the "Register" link
// You can specify tags to go around the returned link (or wrap it with no tags); by default this is a list item
// You can also specify whether to print out the link or just return it
function wpc_client_rul_register( $before = '<li>', $after = '</li>', $give_echo = true ) {
    global $current_user;

	if ( ! is_user_logged_in() ) {
		if ( get_option('users_can_register') )
			$link = $before . '<a href="' . site_url('wp-login.php?action=register', 'login') . '">' . __('Register', WPC_CLIENT_TEXT_DOMAIN) . '</a>' . $after;
		else
			$link = '';
	} else {
        $link = $before . '<a href="' . wpc_client_redirect_to_front_page('', '', $current_user) . '">' . __('Site Admin', WPC_CLIENT_TEXT_DOMAIN) . '</a>' . $after;;
	}

    if ($give_echo) {
        echo $link;
    } else {
        return $link;
    }
}


if (is_admin()) {

    // Returns all option HTML for all usernames in the system except for those supplied to it
    function wpc_client_rul_returnusernames($exclude) {
        global $wpdb;

        $rul_returnusernames = '';

        // Build the "not in" part of the MySQL query
        $exclude_users = "'" . implode( "','", $exclude ) . "'";

        $rul_userresults = $wpdb->get_results('SELECT user_login FROM ' . $wpdb->users . ' WHERE user_login NOT IN (' . $exclude_users . ') ORDER BY user_login', ARRAY_N);

        // Built the option HTML
        if ($rul_userresults) {
            foreach ($rul_userresults as $rul_userresult) {
                $rul_returnusernames .= '                <option value="' . $rul_userresult[0] . '">' . $rul_userresult[0] . '</option>' . "\n";
            }
        }

        return $rul_returnusernames;
    }

    // Returns all roles in the system
    function wpc_client_rul_returnrolenames() {
        global $wp_roles;

        $rul_returnrolenames = array();
        foreach (array_keys($wp_roles->role_names) as $rul_rolename) {
            $rul_returnrolenames[$rul_rolename] = $rul_rolename;
        }

        return $rul_returnrolenames;
    }

    // Returns option HTML for all roles in the system, except for those supplied to it
    function wpc_client_rul_returnroleoptions($exclude) {

        // Relies on a function that just returns the role names
        $rul_rolenames = wpc_client_rul_returnrolenames($exclude);

        $rul_returnroleoptions = '';

        // Build the option HTML
        if ($rul_rolenames) {
            foreach ($rul_rolenames as $rul_rolename) {
                if (!isset($exclude[$rul_rolename])) {
                    $rul_returnroleoptions .= '                <option value="' . $rul_rolename . '">' . $rul_rolename . '</option>' . "\n";
                }
            }
        }

        return $rul_returnroleoptions;
    }

    // Returns all level names in the system
    function wpc_client_rul_returnlevelnames() {
        global $wp_roles;

        $rul_returnlevelnames = array();

        // Builds the array of level names by combing through each of the roles and listing their levels
        foreach ($wp_roles->roles as $wp_role) {
            $rul_returnlevelnames = array_unique((array_merge($rul_returnlevelnames, array_keys($wp_role['capabilities']))));
        }

        // Sort the level names in alphabetical order
        sort($rul_returnlevelnames);

        return $rul_returnlevelnames;
    }

    // Returns option HTML for all levels in the system, except for those supplied to it
    function wpc_client_rul_returnleveloptions($exclude) {

        // Relies on a function that just returns the level names
        $rul_levelnames = wpc_client_rul_returnlevelnames();

        $rul_returnleveloptions = '';

        // Build the option HTML
        foreach ($rul_levelnames as $rul_levelname) {
            if (!isset($exclude[$rul_levelname])) {
                $rul_returnleveloptions .= '                <option value="' . $rul_levelname . '">' . $rul_levelname . '</option>' . "\n";
            }
        }

        return $rul_returnleveloptions;
    }

    // Processes the rule updates per user
    function wpc_client_rul_submit_username($usernames, $addresses,$logout) {
        global $wpdb, $rul_db_addresses;

        $rul_whitespace = '        ';

        // Open the informational div
        $rul_process_submit = '<div id="message" class="updated fade">' . "\n";

        // Code for closing the informational div
        $rul_process_close = $rul_whitespace . '</div>' . "\n";

        // ----------------------------------
        // Process the rule changes
        // ----------------------------------

        if($usernames && $addresses) {
            $rul_submit_success     = true;
            $rul_usernames_updated  = array();
            $rul_username_keys      = array_keys($usernames);
            $rul_username_loop      = 0;

            // Loop through all submitted usernames
            foreach( $usernames as $username ) {
                $i = $rul_username_keys[$rul_username_loop];

                if ( username_exists($username) ) {

                    // Check to see whether it matches the "local URL" test
                    $address = wpc_client_rul_safe_redirect($addresses[$i]);
                    $lgt = wpc_client_rul_safe_redirect($logout[$i]);

                    if (!$address) {
                        $rul_submit_success = false;
                        $rul_process_submit .= '<p><strong>****' .__('ERROR: Non-local or invalid URL submitted for user ',WPC_CLIENT_TEXT_DOMAIN)  . $username .  '****</strong></p>' . "\n";
                    } else {
                        // Update the existing entry or insert a new one
						$sql = 'REPLACE INTO ' . $rul_db_addresses . ' SET rul_url = \'' . $address . '\', rul_type = \'user\', rul_value = \'' . $username . '\', rul_url_logout=\''.$lgt.'\'';

                        //mysql_query($sql) or die(mysql_error());
                        $rul_update_username = $wpdb->query($sql);

                        if (!$rul_update_username) {
                            $rul_submit_success = false;
                            $rul_process_submit .= '<p><strong>****' .__('ERROR: Unknown error updating user-specific URL for user ',WPC_CLIENT_TEXT_DOMAIN) . $username . '****</strong></p>' . "\n";
                        }
                    }

                    // Make a note that we've updated this username
                    $rul_usernames_updated[] = $username;
                } elseif ($username != -1) {
                    $rul_submit_success = false;
                    $rul_process_submit .= '<p><strong>****' .__('ERROR: Non-existent username submitted ',WPC_CLIENT_TEXT_DOMAIN) .'****</strong></p>' . "\n";
                }

                ++$rul_username_loop;
            }

            // Prepare the "not in" MySQL code
            $rul_usernames_notin = "'" . implode( "','", $rul_usernames_updated ) . "'";

            // Delete all username rules in the database that weren't updated (in other words, the user unchecked the box next to it)
            $wpdb->query('DELETE FROM ' . $rul_db_addresses . ' WHERE rul_type = \'user\' AND rul_value NOT IN (' . $rul_usernames_notin . ')');

            if ($rul_submit_success) {
                $rul_process_submit .= '<p>'.__('Successfully updated user-specific URLs',WPC_CLIENT_TEXT_DOMAIN).'</p>'. "\n";
            }
        }

        // Close the informational div
        $rul_process_submit .= $rul_process_close;

        // We've made it this far, so success!
        return $rul_process_submit;
    }

    // Processes the rule updates per role
    function wpc_client_rul_submit_role($roles, $addresses) {
        global $wpdb, $rul_db_addresses;

        $rul_whitespace = '        ';

        // Open the informational div
        $rul_process_submit = '<div id="message" class="updated fade">' . "\n";

        // Code for closing the informational div
        $rul_process_close = $rul_whitespace . '</div>' . "\n";

        // ----------------------------------
        // Process the rule changes
        // ----------------------------------

        if($roles && $addresses) {
            $rul_submit_success = true;
            $rul_roles_updated = array();
            $rul_role_keys = array_keys($roles);
            $rul_role_loop = 0;

            // Loop through all submitted roles
            foreach( $roles as $role ) {
                $i = $rul_role_keys[$rul_role_loop];

                // Get a list of roles in the system so that we can verify that a valid role was submitted
                $rul_existing_rolenames = wpc_client_rul_returnrolenames();
                if ( isset($rul_existing_rolenames[$role]) ) {

                    // Check to see whether it matches the "local URL" test
                    $address = wpc_client_rul_safe_redirect($addresses[$i]);

                    if (!$address) {
                        $rul_submit_success = false;
                        $rul_process_submit .= '<p><strong>****' .__('ERROR: Non-local or invalid URL submitted for role ',WPC_CLIENT_TEXT_DOMAIN) . $role . '****</strong></p>' . "\n";
                    } else {
                        // Update the existing entry or insert a new one
                        $rul_update_role = $wpdb->query('REPLACE INTO ' . $rul_db_addresses . ' SET rul_url = \'' . $address . '\', rul_type = \'role\', rul_value = \'' . $role . '\'');

                        if (!$rul_update_role) {
                            $rul_submit_success = false;
                            $rul_process_submit .= '<p><strong>****' .__('ERROR: Unknown error updating role-specific URL for role ',WPC_CLIENT_TEXT_DOMAIN) . $role . '****</strong></p>' . "\n";
                        }
                    }

                    // Make a note that this role name was updated
                    $rul_roles_updated[] = $role;
                } elseif ($role != -1) {
                    $rul_submit_success = false;
                    $rul_process_submit .= '<p><strong>****' .__('ERROR: Non-existent role submitted ',WPC_CLIENT_TEXT_DOMAIN) .'****</strong></p>' . "\n";
                }

                ++$rul_role_loop;
            }

            // Built the "not in" MySQL query
            $rul_roles_notin = "'" . implode( "','", $rul_roles_updated ) . "'";

            // Delete all role rules in the database that weren't updated (in other words, the user unchecked the box next to it)
            $wpdb->query('DELETE FROM ' . $rul_db_addresses . ' WHERE rul_type = \'role\' AND rul_value NOT IN (' . $rul_roles_notin . ')');

            if ($rul_submit_success) {
                $rul_process_submit .= '<p>'.__('Successfully updated role-specific URLs',WPC_CLIENT_TEXT_DOMAIN) .'</p>' . "\n";
            }
        }

        // Close the informational div
        $rul_process_submit .= $rul_process_close;

        // We've made it this far, so success!
        return $rul_process_submit;
    }

    function wpc_client_rul_submit_level($levels, $orders, $addresses) {
        global $wpdb, $rul_db_addresses;

        $rul_whitespace = '        ';

        // Open the informational div
        $rul_process_submit = '<div id="message" class="updated fade">' . "\n";

        // Code for closing the informational div
        $rul_process_close = $rul_whitespace . '</div>' . "\n";

        // ----------------------------------
        // Process the rule changes
        // ----------------------------------

        if($levels && $addresses) {
            $rul_submit_success = true;
            $rul_levels_updated = array();
            $rul_level_keys = array_keys($levels);
            $rul_level_loop = 0;

            // Loop through all submitted levels
            foreach( $levels as $level ) {
                $i = $rul_level_keys[$rul_level_loop];

                // Build the array of existing level names
                $rul_existing_levelnames = array_flip(wpc_client_rul_returnlevelnames());

                // The order should only be between 0 and 99
                $order = abs(intval($orders[$i]));
                if ($order > 99) {
                    $order = 0;
                }

                if ( isset($rul_existing_levelnames[$level]) ) {

                    // Check to see whether it passes the "local URL" test
                    $address = wpc_client_rul_safe_redirect($addresses[$i]);

                    if (!$address) {
                        $rul_submit_success = false;
                        $rul_process_submit .= '<p><strong>****' .__('ERROR: Non-local or invalid URL submitted for level ',WPC_CLIENT_TEXT_DOMAIN)  . $level . '****</strong></p>' . "\n";
                    } else {
                        // Update the existing entry or insert a new one
                        $rul_update_level = $wpdb->query('REPLACE INTO ' . $rul_db_addresses . ' SET rul_url = \'' . $address . '\', rul_type = \'level\', rul_value = \'' . $level . '\', rul_order = ' . $order);

                        if (!$rul_update_level) {
                            $rul_submit_success = false;
                            $rul_process_submit .= '<p><strong>****' .__('ERROR: Unknown error updating level-specific URL for level ',WPC_CLIENT_TEXT_DOMAIN)  . $level . '****</strong></p>' . "\n";
                        }
                    }

                    // Make a note that this level was updated
                    $rul_levels_updated[] = $level;
                } elseif ($level != -1) {
                    $rul_submit_success = false;
                    $rul_process_submit .= '<p><strong>****' .__('ERROR: Non-existent level submitted ',WPC_CLIENT_TEXT_DOMAIN) .'****</strong></p>'. "\n";
                }

                ++$rul_level_loop;
            }

            // Build the "not in" MySQL code
            $rul_levels_notin = "'" . implode( "','", $rul_levels_updated ) . "'";

            // Delete all level rules in the database that weren't updated (in other words, the user unchecked the box next to it)
            $wpdb->query('DELETE FROM ' . $rul_db_addresses . ' WHERE rul_type = \'level\' AND rul_value NOT IN (' . $rul_levels_notin . ')');

            if ($rul_submit_success) {
                $rul_process_submit .= '<p>'.__('Successfully updated level-specific URLs',WPC_CLIENT_TEXT_DOMAIN).'</p>'. "\n";
            }
        }

        // Close the informational div
        $rul_process_submit .= $rul_process_close;

        // We've made it this far, so success!
        return $rul_process_submit;
    }

    function wpc_client_rul_submit_all( $update_or_delete, $address, $address_logout ) {
        global $wpdb, $rul_db_addresses;

        $address        = trim( $address );
        $address_logout = trim( $address_logout );

        // Open the informational div
        $rul_process_submit = '<div id="message" class="updated fade">' . "\n";

        // Code for closing the informational div
        $rul_process_close = '</div>' . "\n";

        // ----------------------------------
        // Process the rule changes
        // ----------------------------------

        // Since we never actually, remove the "all" entry, here we just make its value empty
        if( $update_or_delete == 'Delete' ) {
            $update = $wpdb->update (
                $rul_db_addresses,
                array( 'rul_url' => '', 'rul_url_logout' => '' ),
                array( 'rul_type' => 'all' )
            );

            if ($update === false) {
                $rul_process_submit .= '<p><strong>****' .__('ERROR: Unknown database problem removing URL for &#34;all other users&#34; ',WPC_CLIENT_TEXT_DOMAIN) .'****</strong></p>' . "\n";
            } else {
                $rul_process_submit .= '<p>'.__('Successfully removed URL for &#34;all other users&#34; ',WPC_CLIENT_TEXT_DOMAIN) .'</p>'. "\n";
            }
        } elseif( $update_or_delete == 'Update' ) {
            $address = wpc_client_rul_safe_redirect( $address );
            $address_logout = wpc_client_rul_safe_redirect( $address_logout );

            if( ( '' != $address && !$address ) || ( '' != $address_logout && !$address_logout ) ) {
                $rul_process_submit .= '<p><strong>****' .__('ERROR: Non-local or invalid URL submitted ',WPC_CLIENT_TEXT_DOMAIN) .'****</strong></p>' . "\n";
            } else {
                $update = $wpdb->update(
                    $rul_db_addresses,
                    array( 'rul_url' => $address, 'rul_url_logout' => $address_logout ),
                    array( 'rul_type' => 'all' )
                );

                if( $update === false ) {
                    $rul_process_submit .= '<p><strong>****' .__('ERROR: Unknown database problem updating URL for &#34;all other users&#34; ',WPC_CLIENT_TEXT_DOMAIN) .'****</strong></p>' . "\n";
                } else {
                    $rul_process_submit .= '<p>'.__('Successfully updated URL for &#34;all other users&#34;',WPC_CLIENT_TEXT_DOMAIN) .'</p>'. "\n";
                }
            }
        }

        // Close the informational div
        $rul_process_submit .= $rul_process_close;

        // We've made it this far, so success!
        return $rul_process_submit;
    }

    /*
    Stolen from wp_safe_redirect, which validates the URL
    */
    function wpc_client_rul_safe_redirect($location) {
        global $rul_local_only;

        if( 2 == $rul_local_only || 1 == $rul_local_only ) {
            return $location;
        }

        // Need to look at the URL the way it will end up in wp_redirect()
        $location = wp_sanitize_redirect($location);

        // browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
        if ( substr($location, 0, 2) == '//' ) {
            $location = 'http:' . $location;
        }

        // In php 5 parse_url may fail if the URL query part contains http://, bug #38143
        $test = ( $cut = strpos($location, '?') ) ? substr( $location, 0, $cut ) : $location;

        $lp  = parse_url($test);
        $wpp = parse_url(get_option('home'));

        $allowed_hosts = (array) apply_filters('allowed_redirect_hosts', array($wpp['host']), isset($lp['host']) ? $lp['host'] : '');

        if ( isset($lp['host']) && ( !in_array($lp['host'], $allowed_hosts) && $lp['host'] != strtolower($wpp['host'])) ) {
    		    return false;
        } else {
            return $location;
        }
    }

    // This is the Settings > Login/logout redirects menu
    function wpc_client_rul_optionsmenu() {
        global $wpdb, $rul_db_addresses;

        // Upgrade check here because it's the only place we know they will visit
        wpc_client_rul_upgrade();

        $rul_process_submit = '';

        // Process submitted information to update redirect rules
        if( isset( $_POST['rul_usernamesubmit'] ) ) {
            $rul_process_submit = wpc_client_rul_submit_username( $_POST['rul_username'], $_POST['rul_usernameaddress'],$_POST['rul_logout_usernameaddress'] );
        } elseif( isset( $_POST['rul_rolesubmit'] ) ) {
            $rul_process_submit = wpc_client_rul_submit_role( $_POST['rul_role'], $_POST['rul_roleaddress'] );
        } elseif( isset( $_POST['rul_levelsubmit'] ) ) {
            $rul_process_submit = wpc_client_rul_submit_level( $_POST['rul_level'], $_POST['rul_levelorder'], $_POST['rul_leveladdress'] );
        } elseif( isset( $_POST['rul_allsubmit'] ) ) {
            $rul_process_submit = wpc_client_rul_submit_all( $_POST['rul_allsubmit'], $_POST['rul_all'], $_POST['rul_all_logout'] );
        }

        // -----------------------------------
        // Get the existing rules
        // -----------------------------------
        $rul_rules = $wpdb->get_results('SELECT rul_type, rul_value, rul_url, rul_url_logout, rul_order FROM ' . $rul_db_addresses . ' ORDER BY rul_type, rul_order, rul_value', ARRAY_N);

        $rul_usernamevalues     = '';
        $rul_rolevalues         = '';
        $rul_levelvalues        = '';
        $rul_usernames_existing = array();
        $rul_roles_existing     = array();
        $rul_levels_existing    = array();

        if( $rul_rules ) {

            $i_user     = 0;
            $i_role     = 0;
            $i_level    = 0;

            foreach ( $rul_rules as $rul_rule ) {
                list( $rul_type, $rul_value, $rul_url, $rul_url_logout, $rul_order ) = $rul_rule;

                // Specific users
                if( $rul_type == 'user' ) {
                    $rul_usernamevalues .= '            <tr>' . "\n";
                    $rul_usernamevalues .= '                <td><p><input type="checkbox" name="rul_username[' . $i_user . ']" value="' . $rul_value . '" checked="checked" /> ' . $rul_value . '</p></td>' . "\n";
                    $rul_usernamevalues .= '                <td><p><input type="text" size="83" maxlength="500" name="rul_usernameaddress[' . $i_user . ']" value="' . $rul_url . '" /> </p></td>' . "\n";
                    $rul_usernamevalues .= '                <td><p><input type="text" size="83" maxlength="500" name="rul_logout_usernameaddress[' . $i_user . ']" value="' . $rul_url_logout . '" /> </p></td>' . "\n";
                    $rul_usernamevalues .= '            </tr>' . "\n";

                    $rul_usernames_existing[] = $rul_value;

                    ++$i_user;

                } elseif( $rul_type == 'role' ) {

                    $rul_rolevalues .= '            <tr>' . "\n";
                    $rul_rolevalues .= '                <td><p><input type="checkbox" name="rul_role[' . $i_role . ']" value="' . $rul_value . '" checked="checked" /> ' . $rul_value . '</p></td>' . "\n";
                    $rul_rolevalues .= '                <td><p><input type="text" size="83" maxlength="500" name="rul_roleaddress[' . $i_role . ']" value="' . $rul_url . '" /></p></td>' . "\n";
                    $rul_rolevalues .= '            </tr>' . "\n";

                    $rul_roles_existing[$rul_value] = '';

                    ++$i_role;

                } elseif( $rul_type == 'level' ) {
                    $rul_levelvalues .= '            <tr>' . "\n";
                    $rul_levelvalues .= '                <td><p><input type="checkbox" name="rul_level[' . $i_level . ']" value="' . $rul_value . '" checked="checked" /> ' . $rul_value . '</p></td>' . "\n";
                    $rul_levelvalues .= '                <td><p><input type="text" size="2" maxlength="2" name="rul_levelorder[' . $i_level . ']" value="' . $rul_order . '" /></p></td>' . "\n";
                    $rul_levelvalues .= '                <td><p><input type="text" size="83" maxlength="500" name="rul_leveladdress[' . $i_level . ']" value="' . $rul_url . '" /></p></td>' . "\n";
                    $rul_levelvalues .= '            </tr>' . "\n";

                    $rul_levels_existing[$rul_value] = '';

                    ++$i_level;

                } elseif( $rul_type == 'all' ) {
                    $rul_allvalue = $rul_url;
                    $rul_allvalue_logout = $rul_url_logout;
                }
            }
        }
        ?>

        <div class='wrap'>

        <div class="wpc_logo"></div>
        <hr />

        <div class="icon32" id="icon-options-general"></div>
        <h2><?php _e( 'WP-Client Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></h2>

        <p><?php _e( 'From here you can manage a variety of options for the WP-Client plugin.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
        <div id="container23">
            <ul class="menu">
                <li id="general"><a href="admin.php?page=wpclients_settings" ><?php _e( 'General', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
                <li id="clogin"><a href="admin.php?page=custom_login_admin" ><?php _e( 'Custom Login', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
                <li id="redirects" class="active"><a href="admin.php?page=xyris-login-logout" ><?php _e( 'Login/Logout Redirects', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
                <li id="skins"><a href="admin.php?page=wpclients_settings&tab=skins" ><?php _e( 'Skins', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
                <li id="alerts"><a href="admin.php?page=wpclients_settings&tab=alerts" ><?php _e( 'Login Alerts', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
                <li id="url_slugs"><a href="admin.php?page=wpclients_settings&tab=url_slugs" ><?php _e( 'URL Slugs', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
                <li id="addons"><a href="admin.php?page=wpclients_settings&tab=addons" ><?php _e( 'Addons', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
                <li id="about"><a href="admin.php?page=wpclients_settings&tab=about" ><?php _e( 'About', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            </ul>
            <span class="clear"></span>
            <div class="content23 news">


                <form name="rul_usernameform" action="" method="post">
                    <div class="postbox">
                        <h3 class='hndle'><span><?php _e( 'Manage Login/Logout Redirect rules', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                        <div class="inside">
                    <?php print $rul_process_submit; ?>
                            <p><?php _e('>> Define custom URLs to which different users will be redirected upon successful login.', WPC_CLIENT_TEXT_DOMAIN ); ?></p>
                            <p><?php _e('>> Define custom URLs to which different users will be redirected upon logout', WPC_CLIENT_TEXT_DOMAIN ); ?></p>
                            <p><?php //_e('Note that you can use the syntax <strong>[wpclients]username[/wpclients]</strong> in your URLs so that the system will build a dynamic URL upon each login, replacing that text with the users username.', WPC_CLIENT_TEXT_DOMAIN ); ?></p>

                            <h4><?php _e('Specific users', WPC_CLIENT_TEXT_DOMAIN ); ?></h4>

                            <table class="widefat">
                                <tr>
                                    <th><?php _e('Username', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                                    <th><?php _e('Login URL', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                                    <th><?php _e('Logout URL', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                                </tr>
                    <?php print $rul_usernamevalues; ?>

                            </table>
                            <p><?php _e('Add:', WPC_CLIENT_TEXT_DOMAIN ); ?>
                                <select name="rul_username[<?php print $i_user; ?>]" >
                                    <option value="-1"><?php _e('Select a username', WPC_CLIENT_TEXT_DOMAIN ); ?></option>
                    <?php print wpc_client_rul_returnusernames($rul_usernames_existing); ?>
                                </select>
                                <br /><?php _e('URL:', WPC_CLIENT_TEXT_DOMAIN ); ?> <input type="text" size="83" maxlength="500" name="rul_usernameaddress[<?php print $i_user; ?>]" />
                            </p>
                        </div>
                    </div>

                    <p class="submit"><input type="submit" class='button-primary' name="rul_usernamesubmit" value="<?php _e('Update', WPC_CLIENT_TEXT_DOMAIN ); ?>" /></p>
                </form>
                <!--
                <h3><?php _e('Specific roles', WPC_CLIENT_TEXT_DOMAIN ); ?></h3>
                <form name="rul_roleform" action="" method="post">
                <table class="widefat">
                    <tr>
                        <th><?php _e('Role', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                        <th><?php _e('URL', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                    </tr>
                    <?php print $rul_rolevalues; ?>

                </table>
                <p><?php _e('Add:', WPC_CLIENT_TEXT_DOMAIN ); ?>
                    <select name="rul_role[<?php print $i_role; ?>]" >
                        <option value="-1"><?php _e('Select a role', WPC_CLIENT_TEXT_DOMAIN ); ?></option>
        <?php print wpc_client_rul_returnroleoptions($rul_roles_existing); ?>
                    </select>
                    <br /><?php _e('URL:', WPC_CLIENT_TEXT_DOMAIN ); ?>  <input type="text" size="83" maxlength="500" name="rul_roleaddress[<?php print $i_role; ?>]" />
                </p>
                <p class="submit"><input type="submit" name="rul_rolesubmit" value="<?php _e('Update', WPC_CLIENT_TEXT_DOMAIN ); ?>" /></p>
                </form>

                <h3><?php _e('Specific levels', WPC_CLIENT_TEXT_DOMAIN ); ?></h3>
                <form name="rul_levelform" action="" method="post">
                <table class="widefat">
                    <tr>
                        <th><?php _e('Level', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                        <th><?php _e('Order', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                        <th><?php _e('URL', WPC_CLIENT_TEXT_DOMAIN ); ?></th>
                    </tr>
                    <?php print $rul_levelvalues; ?>

                </table>
                <p><?php _e('Add:', WPC_CLIENT_TEXT_DOMAIN ); ?>
                    <select name="rul_level[<?php print $i_level; ?>]" >
                        <option value="-1"><?php _e('Select a level', WPC_CLIENT_TEXT_DOMAIN ); ?></option>
        <?php print wpc_client_rul_returnleveloptions($rul_levels_existing); ?>
                    </select>
                    <br /><?php _e('Order:', WPC_CLIENT_TEXT_DOMAIN ); ?> <input type="text" size="2" maxlength="2" name="rul_levelorder[<?php print $i_level; ?>]" />
                    <br /><?php _e('URL:', WPC_CLIENT_TEXT_DOMAIN ); ?> <input type="text" size="83" maxlength="500" name="rul_leveladdress[<?php print $i_level; ?>]" />
                </p>
                <p class="submit"><input type="submit" name="rul_levelsubmit" value="<?php _e('Update', WPC_CLIENT_TEXT_DOMAIN ); ?>" /></p>
                </form>

                <h3><?php _e('All other users', WPC_CLIENT_TEXT_DOMAIN ); ?></h3>
                <form name="rul_allform" action="" method="post">
                <p><?php _e('URL:', WPC_CLIENT_TEXT_DOMAIN ) ?> <input type="text" size="83" maxlength="500" name="rul_all" value="<?php print $rul_allvalue; ?>" /></p>
                        <p><?php _e('Logout URL:', WPC_CLIENT_TEXT_DOMAIN ) ?> <input type="text" size="83" maxlength="500" name="rul_all_logout" value="<?php print $rul_allvalue_logout; ?>" /></p>
                <p class="submit"><input type="submit" name="rul_allsubmit" value="<?php _e('Update', WPC_CLIENT_TEXT_DOMAIN ); ?>" /> <input type="submit" name="rul_allsubmit" value="<?php _e('Delete', WPC_CLIENT_TEXT_DOMAIN ); ?>" /></p>
                </form>
                --!>
            </div>
        </div>
<?php
    }


    // Add and remove database tables when installing and uninstalling

    // Perform upgrade functions
    // Some newer operations are duplicated from rul_install() as there's no guarantee that the user will follow a specific upgrade procedure
    function wpc_client_rul_upgrade() {
        global $wpdb, $rul_version, $rul_db_addresses;

        // Turn version into an integer for comparisons
        $current_version = intval( str_replace( '.', '', get_option( 'rul_version' ) ) );

        if( $current_version != intval( str_replace( '.', '', $rul_version ) ) ) {
            //Add the version number to the database
            delete_option( 'rul_version' );
            add_option( 'rul_version', $rul_version, '', 'no' );
        }
    }


    function wpc_client_rul_install() {
        global $wpdb, $rul_db_addresses, $rul_version;

        // Add the table to hold Client Circle information and moderator rules
        if( $rul_db_addresses != $wpdb->get_var( 'SHOW TABLES LIKE \'' . $rul_db_addresses . '\'')
            && "{$wpdb->prefix}login_redirects" != $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}login_redirects'" ) ) {
            $sql = 'CREATE TABLE ' . $rul_db_addresses . ' (
            `rul_type` enum(\'user\',\'role\',\'level\',\'all\') NOT NULL,
            `rul_value` varchar(255) NOT NULL default \'\',
            `rul_url` LONGTEXT NOT NULL,
            `rul_url_logout` LONGTEXT NOT NULL default \'\',
            `rul_order` int(2) NOT NULL default \'0\',
            UNIQUE KEY `rul_type` (`rul_type`,`rul_value`)
            )';

            $wpdb->query($sql);

            // Insert the "all" redirect entry
            $wpdb->insert($rul_db_addresses,
                array('rul_type' => 'all')
            );

            // Set the version number in the database
            add_option( 'rul_version', $rul_version, '', 'no' );
        }

        wpc_client_rul_upgrade();
    }

    function wpc_client_rul_uninstall() {
        global $wpdb, $rul_db_addresses;

        // Remove the table we created
        if( $rul_db_addresses == $wpdb->get_var('SHOW TABLES LIKE \'' . $rul_db_addresses . '\'') ) {
            //$sql = 'DROP TABLE ' . $rul_db_addresses;
            //$wpdb->query($sql);
        }

        //delete_option( 'rul_version' );
    }

}
