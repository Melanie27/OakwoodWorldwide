<?php
global $wpdb, $wp_roles;

/*
* Convert to WP-Client's roles
*/
if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpc_convert_form' ) ) {
    if ( isset( $_REQUEST['convert_to'] ) && in_array( $_REQUEST['convert_to'], $this->wpc_roles ) ) {
        if ( isset( $_REQUEST['ids'] ) && is_array( $_REQUEST['ids'] ) && 0 < count( $_REQUEST['ids'] ) ) {
            $convert_to = $_REQUEST['convert_to'];
            $ids        = $_REQUEST['ids'];
            switch( $convert_to ) {
            case 'wpc_client':

                    foreach( $ids as $user_id ) {
                        if ( isset( $_REQUEST['save_role'] ) && 1 == $_REQUEST['save_role'] ) {
                            //Save role
                            $user_object = new WP_User( $user_id );
                            $user_object->add_role( 'wpc_client' );
                        } else {
                            // replace role
                            update_user_meta( $user_id, $wpdb->prefix . 'capabilities', array( 'wpc_client' => '1' ) );
                        }

                        $first_name = get_user_meta( $user_id, 'first_name', true );

                        //set Client Circles
                        if ( isset( $_REQUEST['group_ids'] ) && 0 < count( $_REQUEST['group_ids'] ) ) {
                            foreach ( $_REQUEST['group_ids'] as $group_id ) {
                                $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wpc_client_group_clients SET group_id = %d, client_id = '%d'", $group_id,  $user_id ) );
                            }
                        }

                        update_user_option( $user_id, 'unqiue', md5( time() ) );

                        //set manager
                        if ( isset( $_REQUEST['manager_id'] ) && 0 < $_REQUEST['manager_id'] ) {
                             update_user_meta( $user_id, 'admin_manager', $_REQUEST['manager_id'] );
                        }

                        //hide admin bar
                        if ( isset( $_REQUEST['hide_admin_bar'] ) && 1 == $_REQUEST['hide_admin_bar'] ) {
                            update_user_meta( $user_id, 'show_admin_bar_front', false );
                        }

                        //create Portal Page
                        if ( isset( $_REQUEST['create_client_page'] ) && 1 == $_REQUEST['create_client_page'] ) {

                            $client_template = get_option( 'client_template' );
                            $client_template = html_entity_decode( $client_template );
                            $client_template = str_replace( '{name}', $first_name, $client_template) ;
                            $client_template = str_replace( '{page_title}',$first_name,$client_template );

                            $clients = array(
                                'comment_status'    => 'closed',
                                'ping_status'       => 'closed',
                                'post_author'       => get_current_user_id(),
                                'post_content'      => $client_template,
                                'post_name'         => $first_name,
                                'post_status'       => 'publish',
                                'post_title'        => $first_name,
                                'post_type'         => 'clientspage'
                            );

                            $client_page_id = wp_insert_post( $clients );

                            update_post_meta( $client_page_id, 'user_ids', array( $user_id ) );
                        }


                        //create hub page for the user

                        $post = array();
                        $post['post_type']      = 'hubpage';
                        $post['post_content']   = html_entity_decode( get_option( 'hub_template' ) );
                        $post['post_author']    = 1;
                        $post['post_status']    = 'publish';
                        $post['comment_status'] = 'closed';
                        $post['post_title']     = $first_name;
                        $post['post_parent']    = 0;
                        $post['post_status']    = "publish";

                        $postid = wp_insert_post( $post );

                        $link   = get_permalink( $postid );

                        // add clients redirect url to its page
                        $sql = "REPLACE INTO {$wpdb->prefix}wpc_client_login_redirects SET rul_url = '$link', rul_url_logout='', rul_type = 'user', rul_value = '" . $user_object->user_login ."'";

                        $wpdb->query( $sql );

                        $msg = 'ac';
                    }

                 break;

            case 'wpc_client_staff':
                    foreach( $ids as $user_id ) {
                        if ( isset( $_REQUEST['save_role'] ) && 1 == $_REQUEST['save_role'] ) {
                            //Save role
                            $user_object = new WP_User( $user_id );
                            $user_object->add_role( 'wpc_client_staff' );
                        } else {
                            // replace role
                            update_user_meta( $user_id, $wpdb->prefix . 'capabilities', array( 'wpc_client_staff' => '1' ) );
                        }

                        //assign Employee to client
                        if ( isset( $_REQUEST['client_id'] ) && 0 < $_REQUEST['client_id'] ) {
                            update_user_meta( $user_id, 'parent_client_id', $_REQUEST['client_id'] );
                        }

                        $msg = 'as';
                    }
                 break;

            case 'wpc_manager':

                    foreach( $ids as $user_id ) {

                        if ( isset( $_REQUEST['save_role'] ) && 1 == $_REQUEST['save_role'] ) {
                            //Save role
                            $user_object = new WP_User( $user_id );
                            $user_object->add_role( 'wpc_manager' );
                        } else {
                            // replace role
                            update_user_meta( $user_id, $wpdb->prefix . 'capabilities', array( 'wpc_manager' => '1' ) );
                        }

                        //set manager for clients
                        if ( isset( $_REQUEST['client_ids'] ) && 0 < count( $_REQUEST['client_ids'] ) ) {
                            foreach ( $_REQUEST['client_ids'] as $client_id ) {
                                update_user_meta( $client_id, 'admin_manager', $user_id );
                            }
                        }

                        $msg = 'am';
                    }
                 break;

            }


            do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients&tab=convert&msg=' . $msg );
            exit;
        }

    }
}


if ( isset( $_GET['msg'] ) ) {
	$msg = $_GET['msg'];
	switch( $msg ) {
        case 'ac':
            echo '<div id="message" class="updated fade"><p>' . __( 'User(s) <strong>Converted</strong> to Client(s) Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
            break;
        case 'as':
            echo '<div id="message" class="updated fade"><p>' . __( 'User(s) <strong>Converted</strong> to Staff(s) Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
            break;
        case 'am':
			echo '<div id="message" class="updated fade"><p>' . __( 'User(s) <strong>Converted</strong> to Manager(s) Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
			break;
	}
}


$wpnonce = wp_create_nonce( 'wpc_convert_form' );

if ( !class_exists( 'pagination' ) )
    include_once( 'pagination.php' );

if (  isset( $_GET['role'] ) && 'all' != $_GET['role'] ) {
    $c_role = $_GET['role'];
    $target = '&role=' . $_GET['role'];
} else {
    $c_role = '';
    $target = '';
}

//exclude WP Clients users
$exclude_clients    = get_users( array( 'role' => 'wpc_client',  'fields' => 'ID', ) );
$exclude_staff      = get_users( array( 'role' => 'wpc_client_staff',  'fields' => 'ID', ) );
$exclude_managers   = get_users( array( 'role' => 'wpc_manager',  'fields' => 'ID', ) );
$exclude_users      = array_merge( $exclude_clients, $exclude_staff, $exclude_managers );
unset( $exclude_clients, $exclude_staff, $exclude_managers );


$args = array(
    'role'          => $c_role,
    'fields'        => 'ID',
    'exclude'       => $exclude_users,
);

$items = count( get_users( $args ) );

$p = new pagination;
$p->items($items);
$p->limit(25);
$p->target( "admin.php?page=wpclients&tab=convert" . $target );
$p->calculate();
$p->parameterName('p');
$p->adjacents(2);

if(!isset($_GET['p'])) {
	$p->page = 1;
} else {
	$p->page = $_GET['p'];
}

$args = array(
    'role'          => $c_role,
    'offset'        => ($p->page - 1) * $p->limit,
    'number'        => $p->limit,
    'exclude'       => $exclude_users,
//    'fields'        => 'ID',
);

$users = get_users( $args );

$users_of_blog = count_users();

$groups = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpc_client_groups ORDER BY group_name ASC", "ARRAY_A" );

//get managers
$args = array(
    'role'      => 'wpc_manager',
    'orderby'   => 'user_login',
    'order'     => 'ASC',
);

$managers = get_users( $args );


//all clients
$not_approved_clients   = get_users( array( 'role' => 'wpc_client', 'meta_key' => 'to_approve', 'fields' => 'ID', ) );
$args = array(
    'role'      => 'wpc_client',
    'exclude'   => $not_approved_clients,
    'fields'    => array( 'ID', 'display_name' ),
    'orderby'   => 'user_login',
    'order'     => 'ASC',
);

$clients = get_users( $args );

?>

<div style="" class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_tabs_menu( 'clients' ) ?>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <ul class="subsubsub">
                <li class="all"><a href="admin.php?page=wpclients&tab=convert" <?php echo ( '' == $c_role ) ? 'class="current"' : '' ?>><?php _e( 'All', WPC_CLIENT_TEXT_DOMAIN ) ?></a> |</li>
                <?php
                if ( isset( $users_of_blog['avail_roles'] ) && is_array( $users_of_blog['avail_roles'] ) ) {
                    $wpc_roles = array( 'wpc_client', 'wpc_client_staff', 'wpc_manager' );
                    $role_names = $wp_roles->get_names();
                    foreach( $users_of_blog['avail_roles'] as $role => $num ) {
                        if ( !in_array( $role, $wpc_roles ) ) {
                            $class = ( $role == $c_role ) ? 'class="current"' : '';
                            echo ' | <li class="' . $role . '"><a href="admin.php?page=wpclients&tab=convert&role=' . $role . '" ' . $class . '>' . $role_names[$role] . '</a></li>';

                        }
                    }

                }
                ?>
            </ul>

            <form method="post" name="wpc_clients_convert_form" id="wpc_clients_convert_form" >
                <input type="hidden" value="<?php echo $wpnonce ?>" name="_wpnonce" id="_wpnonce" />

                <table class="widefat">
                    <thead>
                        <tr>
                            <th class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox" />
                            </th>
                            <th><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'E-Mail', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Role', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox" />
                            </th>
                            <th><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'E-Mail', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Role', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        </tr>
                    </tfoot>
                    <tbody>
                <?php
                foreach ( $users as $user ) :
                    $user = get_userdata( $user->ID );
                ?>
                    <tr class='over'>
                        <th scope="row" class="check-column" style="padding: 5px 0px 0px 0px;">
                            <input type="checkbox" name="ids[]" value="<?php echo $user->ID ?>">
                        </th>
                        <td id="assign_name_block_<?php echo $user->ID ?>" >
                            <?php echo $user->user_login ?>
                        </td>
                        <td>
                            <?php echo $user->nickname ?>
                        </td>
                        <td>
                            <?php echo $user->user_email ?>
                        </td>
                        <td>
                        </td>
                    </tr>

                <?php
                endforeach;
                ?>
                    </tbody>
                </table>
                <div class="tablenav bottom">
                    <div class="tablenav-pages one-page">
                        <div class="tablenav">
                            <div class='tablenav-pages'>
                                <?php echo $p->show(); ?>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="alignleft actions">
                    <span><?php _e( 'Convert to:', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                    <select name="convert_to" id="convert_to">
                        <option value="-1" selected="selected"><?php _e( 'Select Role', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                        <option value="wpc_client">WPC Client</option>
                        <option value="wpc_client_staff">WPC Client STAFF</option>
                        <option value="wpc_manager">WPC Manager</option>
                    </select>
                </div>



                <div id="for_wpc_client" style="display: none;" >
                    <table>
                        <tr>
                            <td colspan="2">
                                <strong> <?php _e( '>> Select Options for Client:', WPC_CLIENT_TEXT_DOMAIN ) ?></strong>
                            </td>
                            </tr>
                        <tr>
                            <td width="40"></td>
                            <td>
                                <table cellspacing="6">
                                    <tr>
                                        <td>
                                            <label for="groups"><?php _e( 'Select Client Circles:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br>
                                            <select name="group_ids[]" id="group_ids" multiple="multiple" style="height: 180px;">
                                            <option value="-1" selected ><?php _e( '- No Group -', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                            <?php if ( is_array( $groups ) && 0 < count( $groups ) ) {
                                            foreach ( $groups as $group ) {
                                            echo '<option value="' . $group['group_id'] . '">' . $group['group_name'] . '</option>';
                                            }
                                            } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="groups"><?php _e( 'Select Manager:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br>
                                            <select name="manager_id" id="manager_id">
                                                <option value="-1"><?php _e( '- No Manager -', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                            <?php if ( is_array( $managers ) && 0 < count( $managers ) ) {
                                                foreach ( $managers as $manager ) {
                                                    echo '<option value="' . $manager->ID . '">' . $manager->display_name . '</option>';
                                                }
                                            } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="create_client_page"><input type="checkbox" name="create_client_page" id="create_client_page" value="1" checked /> <?php _e( 'Create Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="hide_admin_bar"><input type="checkbox" name="hide_admin_bar" id="hide_admin_bar" value="1" checked /> <?php _e( 'Hide Admin Bar', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="save_role"><input type="checkbox" name="save_role" id="save_role" value="1" /> <?php _e( 'Save Current User Role', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br>
                                            <span class="description"><?php _e( "If checked, the user's current role will be saved, but user will also take on characteristics of the WP-Client role.", WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="button" value="<?php _e( 'Convert to Client', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary action" name="convert" />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="for_wpc_client_staff" style="display: none;" >
                    <table>
                        <tr>
                            <td colspan="2">
                                <strong> <?php _e( '>> Select Options for Staff:', WPC_CLIENT_TEXT_DOMAIN ) ?></strong>
                            </td>
                            </tr>
                        <tr>
                            <td width="40"></td>
                            <td>
                                <table cellspacing="6">
                                    <tr>
                                        <td>
                                            <label for="groups"><?php _e( 'Select Client:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select name="client_id" id="client_id">
                                                <option value="-1"><?php _e( '- No Client -', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                            <?php if ( is_array( $clients ) && 0 < count( $clients ) ) {
                                                foreach ( $clients as $client ) {
                                                    echo '<option value="' . $client->ID . '">' . $client->display_name . '</option>';
                                                }
                                            } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="hide_admin_bar"><input type="checkbox" name="hide_admin_bar" id="hide_admin_bar" value="1" checked /> <?php _e( 'Hide Admin Bar', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="save_role"><input type="checkbox" name="save_role" id="save_role" value="1" /> <?php _e( 'Save Current User Role', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br>
                                            <span class="description"><?php _e( "If checked, the user's current role will be saved, but user will also take on characteristics of the WP-Client role.", WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="button" value="<?php _e( 'Convert to Staff', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary action" name="convert" />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="for_wpc_manager" style="display: none;" >
                    <table>
                        <tr>
                            <td colspan="2">
                                <strong> <?php _e( '>> Select Options for Manager:', WPC_CLIENT_TEXT_DOMAIN ) ?></strong>
                            </td>
                            </tr>
                        <tr>
                            <td width="40"></td>
                            <td>
                                <table cellspacing="6">
                                    <tr>
                                        <td>
                                            <label for="groups"><?php _e( 'Select Clients:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select name="client_ids[]" id="client_ids" multiple="multiple" style="height: 180px;">
                                            <option value="-1" selected><?php _e( '- No Client -', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                            <?php if ( is_array( $clients ) && 0 < count( $clients ) ) {
                                            foreach ( $clients as $client ) {
                                            echo '<option value="' . $client->ID . '">' . $client->display_name . '</option>';
                                            }
                                            } ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="save_role"><input type="checkbox" name="save_role" id="save_role" value="1" /> <?php _e( 'Save Current User Role', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <br>
                                            <span class="description"><?php _e( "If checked, the user's current role will be saved, but user will also take on characteristics of the WP-Client role.", WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="button" value="<?php _e( 'Convert to Manager', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary action" name="convert" />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>


            </form>

            <br>



            <script type="text/javascript">
                jQuery(document).ready(function(){

                    jQuery(".over").hover(function(){
                        jQuery(this).css("background-color","#bcbcbc");
                        },function(){
                        jQuery(this).css("background-color","transparent");
                    });



                    //show reassign cats
                    jQuery( '#convert_to' ).change( function() {
                        jQuery( '#for_wpc_client' ).hide();
                        jQuery( '#for_wpc_client_staff' ).hide();
                        jQuery( '#for_wpc_manager' ).hide();

                        if ( '-1' != jQuery( this ).val() ) {
                            jQuery( '#for_' + jQuery( this ).val() ).slideToggle( 'slow' );
//                            jQuery( '#for_' + jQuery( this ).val() ).show();
                        }
                        return false;
                    });

                    //Send convert data
                    jQuery( 'input[name="convert"]' ).click( function() {
                        jQuery( '#for_wpc_client:hidden' ).remove();
                        jQuery( '#for_wpc_client_staff:hidden' ).remove();
                        jQuery( '#for_wpc_manager:hidden' ).remove();

                        jQuery( '#wpc_clients_convert_form' ).submit();
                        return false;
                    });

                });

            </script>

        </div>


    </div>

</div>
