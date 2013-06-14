<?php
global $wp_query, $wpdb;


if ( !is_user_logged_in() ) {
    return __( 'Sorry, you do not have permission to see this page.', WPC_CLIENT_TEXT_DOMAIN ) . " Click <a href='" . wp_login_url() . "'>here</a> to login";
}

if ( !current_user_can( 'wpc_client' ) && !current_user_can( 'wpc_client_staff' ) )
   return __( 'Sorry, you do not have permission to see this page!', WPC_CLIENT_TEXT_DOMAIN );


//remove buttons for editor
remove_all_filters( 'mce_external_plugins' );

$edit_clientpage_id = $wp_query->query_vars['edit_clientpage_id'];

//$clientpage = $wpdb->get_row("SELECT * FROM  {$wpdb->prefix}posts WHERE ID = '$edit_clientpage_id' AND post_type = 'clientspage' "), "ARRAY_A" );
$clientpage = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM  {$wpdb->prefix}posts WHERE ID = %d AND post_type = 'clientspage' ", $edit_clientpage_id ), "ARRAY_A" );

if ( !is_array( $clientpage ) )
    echo "Wrong Portal Page.";

?>


<script type="text/javascript">
    jQuery( document ).ready( function() {

        //update clientpage
        jQuery( '#update' ).click( function() {
            jQuery( '#wpc_action' ).val( 'update' );
            jQuery( '#edit_clientpage' ).submit();
            return false;
        });

        //delete clientpage
        jQuery( '#delete' ).click( function() {
            jQuery( '#wpc_action' ).val( 'delete' );
            jQuery( '#edit_clientpage' ).submit();
            return false;
        });

        //cancel edit clientpage
        jQuery( '#cancel' ).click( function() {
            jQuery( '#wpc_action' ).val( 'cancel' );
            jQuery( '#edit_clientpage' ).submit();
            return false;
        });

    });
</script>

<div class='registration_form'>

    <div id="message" class="updated fade" <?php echo ( empty( $error ) )? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>

    <form method="post" name="edit_clientpage" id="edit_clientpage" >
        <input type="hidden" name="wpc_action" id="wpc_action" value="" />
        <input type="hidden" name="clientpage_id" value="<?php echo $clientpage['ID'] ?>" />
        <input type="hidden" name="wpc_wpnonce" id="wpc_wpnonce" value="<?php echo wp_create_nonce( 'wpc_edit_clientpage' . $clientpage['ID'] ) ?>" />

        <div id="titlewrap">
            <input type="text" name="clientpage_title" autocomplete="off"  value="<?php echo ( isset( $_POST['clientpage_title'] ) ) ? $_POST['clientpage_title'] : $clientpage['post_title'] ?>" style="width: 100%;" >
        </div>

        <div class="postarea" id="postdivrich">
            <?php
            $clientpage_content = ( isset( $_POST['clientpage_content'] ) ) ? $_POST['clientpage_content'] : $clientpage['post_content'];
            wp_editor($clientpage_content, 'clientpage_content' );
            ?>
        </div>

        <br clear="all" />
        <br clear="all" />

        <div>
           <input type="button" name="" id="update" value="<?php _e( 'Update', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
           <input type="button" name="" id="cancel" value="<?php _e( 'Cancel', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
           <input type="button" name="" id="delete" value="<?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
        </div>

    </form>
</div>
<?php

?>
