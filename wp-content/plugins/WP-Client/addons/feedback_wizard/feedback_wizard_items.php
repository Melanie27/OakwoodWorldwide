<?php
global $wpdb;


 if ( isset( $_REQUEST['wpc_action'] ) && 'delete_feedback_item' == $_REQUEST['wpc_action'] && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpc_feedback_items_form' ) && isset( $_REQUEST['item_id'] ) ) {

    $item_ids = ( is_array( $_REQUEST['item_id'] ) ) ? $_REQUEST['item_id'] : (array) $_REQUEST['item_id'];
    foreach ( $item_ids as $item_id) {
       //delete item
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}wpc_client_feedback_items WHERE item_id = %d", $item_id ) );

        //delete item from wizard
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}wpc_client_feedback_wizard_items WHERE item_id = %d", $item_id ) );
    }

    do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_feedback_wizard&tab=items&msg=d' );
    exit;
}


$items = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_items", ARRAY_A );
$wpnonce = wp_create_nonce( 'wpc_feedback_items_form' );

$msg = '';
if ( isset( $_GET['msg'] ) ) {
  $msg = $_GET['msg'];
}

?>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>
    <?php
    if ( '' != $msg ) {
        switch( $msg ) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Item <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated fade"><p>' . __( 'Item <strong>Updated</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Item(s) <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_feedback_tabs_menu() ?>
        </ul>
        <span class="clear"></span>

        <div class="content23 news">

            <br>
            <div>
                <a href="admin.php?page=wpclients_feedback_wizard&tab=add_item" class="add-new-h2"><?php _e( 'Add New Item', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
            </div>

            <hr />

            <form method="post" name="items_form" id="items_form">
                <input type="hidden" value="<?php echo $wpnonce ?>" name="_wpnonce" id="_wpnonce" />
                <input type="hidden" value="" name="wpc_action" id="wpc_action" />

                <table cellspacing="0" class="wp-list-table widefat media">
                    <thead>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Item Name', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Item type', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Item Name', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Item type', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </tfoot>

                    <tbody id="the-list">
                    <?php
                    if ( is_array( $items ) && 0 < count( $items ) ):
                        foreach( $items as $item ):
                    ?>
                     <tr valign="top" id="post-11" class="alternate author-other status-inherit">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="item_id[]" value="<?php echo $item['item_id'] ?>">
                            </th>
                            <td class="title column-title">
                                <strong>
                                    <a href="admin.php?page=wpclients_feedback_wizard&tab=edit_item&item_id= <?php echo '' . $item['item_id'] ?>" title="edit '<?php echo $item['name'] ?>'"><?php echo $item['name'] ?></a>
                                </strong>
                                <div class="row-actions">
                                        <span class="edit"><a href="admin.php?page=wpclients_feedback_wizard&tab=edit_item&item_id= <?php echo '' . $item['item_id'] ?>" title="edit '<?php echo $item['name'] ?>'" ><?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                        <span class="delete"><a class="submitdelete" onclick="return showNotice.warn();" href="admin.php?page=wpclients_feedback_wizard&tab=items&wpc_action=delete_feedback_item&item_id=<?php echo $item['item_id']  ?>&_wpnonce=<?php echo $wpnonce ?>"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></a> </span>
                                </div>
                            </td>
                            <td class="author column-author">
                            <?php
                                switch( $item['type'] ) {
                                    case 'img':
                                        _e( 'Image', WPC_CLIENT_TEXT_DOMAIN );
                                        break;

                                    case 'pdf':
                                        _e( 'PDF', WPC_CLIENT_TEXT_DOMAIN );
                                        break;

                                    case 'att':
                                        _e( 'Attachment', WPC_CLIENT_TEXT_DOMAIN );
                                        break;

                                }
                            ?>
                            </td>
                        </tr>

                    <?php
                        endforeach;
                    endif;
                    ?>
                    </tbody>

                </table>

                <div class="tablenav bottom">

                    <div class="alignleft actions">
                        <select name="action" id="action">
                            <option selected="selected" value="-1"><?php _e( 'Bulk Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <option value="delete_items"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                        </select>
                        <input type="button" value="<?php _e( 'Apply', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-secondary action" id="doaction" name="" />
                    </div>

                    <div class="alignleft actions"></div>

                    <div class="tablenav-pages one-page">
                        <div class="tablenav">
                            <div class='tablenav-pages'>
                                <?php // echo $p->show(); ?>
                            </div>
                        </div>
                    </div>

                    <br class="clear">
                </div>

            </form>

        </div>
    </div>


</div>



<script type="text/javascript">
    jQuery( document ).ready( function() {

        //delete file from Bulk Actions
        jQuery( '#doaction' ).click( function() {
            if ( 'delete_items' == jQuery( '#action' ).val() ) {
                jQuery( '#wpc_action' ).val( 'delete_feedback_item' );
                jQuery( '#items_form' ).submit();
            }
            return false;
        });

    });
</script>