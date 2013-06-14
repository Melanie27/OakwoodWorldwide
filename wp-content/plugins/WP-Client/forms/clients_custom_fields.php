<?php
global $wpdb;
$msg = "";

if ( isset( $_GET['msg'] ) ) {
  $msg = $_GET['msg'];
}

$wpc_custom_fields = get_option( 'wpc_custom_fields' );
$wpnonce           = wp_create_nonce( 'wpc_custom_field' );
//to delete custom field
if ( isset( $_GET['delete'] ) && '' != $_GET['delete'] && wp_verify_nonce( $_GET['_wpnonce'], 'wpc_custom_field' )  ) {
    unset( $wpc_custom_fields[$_GET['delete']] );

    update_option( 'wpc_custom_fields', $wpc_custom_fields );

    $client_ids = get_users( array( 'role' => 'wpc_client', 'meta_key' => $_GET['delete'], 'fields' => 'ID', ) );

    if ( is_array( $client_ids ) && 0 < count( $client_ids ) ) {
        foreach( $client_ids as $id ) {
            delete_user_meta( $id, $_GET['delete'] );
        }
    }


    do_action( 'wp_client_redirect', 'admin.php?page=wpclients&tab=custom_fields&msg=d' );
    exit;
}
?>

<script type="text/javascript">
    jQuery( document ).ready( function() {

        /*
        * sorting
        */

        var fixHelper = function(e, ui) {
            ui.children().each(function() {
                jQuery(this).width(jQuery(this).width());
            });
            return ui;
        };

        jQuery( '#sortable tbody' ).sortable({
            axis: 'y',
            helper: fixHelper,
            handle: '.sorting_button',
            items: 'tr',
        });

        jQuery( '#sortable' ).bind( 'sortupdate', function(event, ui) {

            new_order = jQuery('#sortable tbody').sortable('toArray');
            jQuery( 'body' ).css( 'cursor', 'wait' );

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                data: 'action=change_custom_field_order&new_order=' + new_order,
                success: function( html ) {
                    var i = 1;
                    jQuery( '.order_num' ).each( function () {
                        jQuery( this ).html(i);
                        i++;
                    });
                    jQuery( 'body' ).css( 'cursor', 'default' );
                }
             });
        });


    });
</script>




<div style="" class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>
    <?php
	if($msg != ""){
		switch($msg) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Custom Field <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated fade"><p>' . __( 'Custom Field <strong>Updated</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Custom Field <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
	}

    ?>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_tabs_menu( 'clients' ) ?>
        </ul>
        <span class="clear"></span>

        <div class="content23 news">

            <br>
            <div>
                <a href="admin.php?page=wpclients&tab=custom_fields&add=1" class="add-new-h2"><?php _e( 'Add New Custom Field', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
            </div>

            <hr />

        <?php if ( is_array( $wpc_custom_fields ) && 0 < count( $wpc_custom_fields ) ) { ?>
            <form method="post" action="" name="edit_cat" id="edit_cat" >
                <input type="hidden" name="wpc_action" id="wpc_action2" value="" />
                <table width="700px" class="widefat post " style="width:95%;" id=sortable>
                    <thead>
                        <tr>
                            <th><?php _e( 'Order', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Field Name (ID)', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Title', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Description', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Type', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Display', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 0;
                    foreach( $wpc_custom_fields as $key=>$value ) {
                        $i++;

                    ?>
                        <tr id="field_<?php echo $key ?>" >
                            <td class="sorting_button">
                                <span class="order_num"><?php echo $i ?> </span>
                                <span class="order_img"></span>
                            </td>
                            <td style="vertical-align: middle;">
                                <span id="cat_name_block_<?php echo $key ?>">
                                    <?php echo $key ?>
                                </span>
                            </td>
                            <td style="vertical-align: middle;">
                                <?php echo $value['title'] ?>
                            </td>
                            <td style="vertical-align: middle;">
                                <?php echo $value['description'] ?>
                            </td>
                            <td style="vertical-align: middle;">
                            <?php
                                switch( $value['type'] ) {
                                    case 'text':
                                        _e( 'Text Box', WPC_CLIENT_TEXT_DOMAIN );
                                    break;
                                    case 'textarea':
                                        _e( 'Multi-line Text Box', WPC_CLIENT_TEXT_DOMAIN );
                                    break;
                                    case 'radio':
                                        _e( 'Radio Buttons', WPC_CLIENT_TEXT_DOMAIN );
                                    break;
                                    case 'checkbox':
                                        _e( 'Checkboxes', WPC_CLIENT_TEXT_DOMAIN );
                                    break;
                                    case 'selectbox':
                                        _e( 'Select Box', WPC_CLIENT_TEXT_DOMAIN );
                                    break;
                                    case 'multiselectbox':
                                        _e( 'Multi Select Box', WPC_CLIENT_TEXT_DOMAIN );
                                    break;
                                    case 'hidden':
                                        _e( 'Hidden Field', WPC_CLIENT_TEXT_DOMAIN );
                                    break;
                                }
                            ?>
                            </td>
                            <td style="vertical-align: middle;">
                                <input type="checkbox" disabled <?php echo ( isset( $value['display'] ) && '1' == $value['display'] ) ? 'checked' : '' ?> />
                            </td>
                            <td style="vertical-align: middle;">
                                <a href="admin.php?page=wpclients&tab=custom_fields&edit=<?php echo $key ?>"><?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                                &nbsp;&nbsp;&nbsp;
                                <a href="admin.php?page=wpclients&tab=custom_fields&_wpnonce=<?php echo $wpnonce ?>&delete=<?php echo $key ?>" class="group_delete" ><?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <p>
                    <span class="description" ><img src="<?php echo $this->plugin_url . 'images/sorting_button.png' ?>" style="vertical-align: middle;" /> - <?php _e( 'Drag&Drop for change category order.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                </p>

            </form>
            <?php
            } else {
            ?>
                <p><?php _e( 'No Custom Fields have been created', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
            <?php
            }
            ?>

        </div>
    </div>

</div>
