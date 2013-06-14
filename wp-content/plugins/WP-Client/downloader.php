<?php
/* Short and sweet */
define('WP_USE_THEMES', false);

global $wpdb, $wpc_client;

$id     = $_GET['id'];
$trusted_integer = (int) $id;
if(!$trusted_integer){
	die( __( 'Invalid file. Please try downloading again!', WPC_CLIENT_TEXT_DOMAIN ) );
}

$line = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpc_client_files WHERE id = %s", $id ), "ARRAY_A" );
if ( sizeof( $line ) <= 0 ) {
	die( __( 'Invalid file. Please try downloading again!', WPC_CLIENT_TEXT_DOMAIN ) );
}

$access     = false;
$download   = true;

if ( is_user_logged_in() ) {

    if ( current_user_can( 'wpc_client_staff' ) && !current_user_can( 'manage_network_options' ) )
        $user_id = get_user_meta(  $current_user->ID, 'parent_client_id', true );
    else
        $user_id = $current_user->ID;

    //checking access for file
    if( current_user_can( 'administrator' ) || current_user_can( 'wpc_manager' ) ) {
        //access for admin
        $access = true;

        $download = ( isset( $_GET['d'] ) && 'false' == $_GET['d'] ) ? false : true;

    } elseif ( $line['user_id'] == $user_id ) {
        //access for file owner
        $access = true;
    } else {
        //access for other clients
        $clients_id = explode( ',', str_replace( '#', '', $line['clients_id'] ) );
        if ( is_array( $clients_id ) && in_array( $user_id, $clients_id) ) {
            $access = true;
        } else {
            //access for clients in Client Circles
            $groups_id = explode( ',', str_replace( '#', '', $line['groups_id'] ) );
            if ( is_array( $groups_id ) && 0 < count( $groups_id ) ) {
                foreach( $groups_id as $group_id ) {
                    $clients_id = $wpc_client->get_group_clients_id( $group_id );
                    if ( is_array( $clients_id ) && in_array( $user_id, $clients_id) ) {
                        $access = true;
                        break;
                    }
                }
            }
        }
    }

}

if( $access ) {
    $uploads        = wp_upload_dir();
    $target_path    = $uploads['basedir'] . "/wpclient/$line[filename]";

    //set last download
    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpc_client_files SET last_download = '%s' WHERE id = %s", time(), $id ) );

    if ( $download ) {
        header("Content-type: application/octet-stream");
        header('Content-Disposition: attachment; filename="' . $line[name] . '"');
        ob_clean();
        flush();
        readfile( $target_path );
    } else {

        $type = ( isset( $_GET['t'] ) && '' != $_GET['t'] ) ? $_GET['t'] : '';

        if ( isset( $type ) )
            switch( $type ) {
                case 'gif':
                    header("Content-type: image/gif");
                    break;
                case 'jpg';
                case 'jpeg':
                    header("Content-type: image/jpeg");
                    break;
                case 'png':
                    header("Content-type: image/png");
                    break;
                case 'pdf':
                    header("Content-type: application/pdf");
                    break;

                default:
                    header("Content-type: text/html");
                    break;
            }

        echo readfile( $target_path );
    }

} else {
    die( __( 'You do not have access to this file!', WPC_CLIENT_TEXT_DOMAIN ) );
}
exit;
?>