<?php

ob_start();

global $wpdb;

if ( !is_user_logged_in() ) {
    return __( 'Sorry, you do not have permission to see this page.', WPC_CLIENT_TEXT_DOMAIN ) . " Click <a href='" . wp_login_url() . "'>here</a> to login";
}

if ( !current_user_can( 'wpc_client' ) )
   return __( 'Sorry, you do not have permission to see this page!', WPC_CLIENT_TEXT_DOMAIN );


if ( isset( $_GET['wpc_client_action'] ) && 'delete_staff' == $_GET['wpc_client_action'] ) {

    require_once( ABSPATH . 'wp-admin/includes/user.php' );
	wp_delete_user( $_GET['id'] );

    //redirect
    if( get_option( 'permalink_structure' ) ) {
        $hub_url = wpc_client_get_hub_link() . '?staff=d';
    } else {
        $hub_url = wpc_client_get_hub_link() . '&staff=d';
    }
    do_action( 'wp_client_redirect', $hub_url );
    exit;
}


$args = array(
    'role'          => 'wpc_client_staff',
    'meta_key'      => 'parent_client_id',
    'meta_value'    => get_current_user_id(),
    'orderby'       => 'user_login',
    'order'         => 'ASC',

);

$rows = get_users( $args );

$delete_link = get_home_url() . '/' . wpc_client_get_slug( 'staff_directory' ) . '?wpc_client_action=delete_staff';

?>

<div class='staff_directory'>

    <?php
    if (isset($_GET['msg'])) {
        $msg = $_GET['msg'];
        switch($msg) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Employee <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Employee <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>


    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e( 'Employee', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'First Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'Status', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
            </tr>
        </thead>
    <?php
    foreach ( $rows as $author ) :
        $author = get_userdata( $author->ID );

        if ( '1' == get_user_meta( $author->ID, 'to_approve', true ) ) {
            $to_approve = __( 'Waiting for approval', WPC_CLIENT_TEXT_DOMAIN );
        } else {
            $to_approve = __( 'Approved', WPC_CLIENT_TEXT_DOMAIN );
        }

        echo "
        <tr class='over'>
            <td>$author->user_login</td>
            <td>$author->first_name</td>
            <td>$author->user_email</td>
            <td>$to_approve</td>
            <td>
                <a onclick='return confirm(\"" . __( 'Are you sure to delete this Employee?', WPC_CLIENT_TEXT_DOMAIN ) . "\");' href='$delete_link&id=$author->ID'>" . __( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) . "</a>
            </td>
        </tr>";
    endforeach;
    ?>
        </tbody>
    </table>

</div>


<?php

$out3 = ob_get_contents();

ob_end_clean();

return $out3;
?>
