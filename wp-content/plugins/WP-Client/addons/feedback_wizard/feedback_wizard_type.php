<?php
global $wpdb;
$msg = "";

if ( isset( $_GET['msg'] ) ) {
  $msg = $_GET['msg'];
}

$wpc_feedback_types = get_option( 'wpc_feedback_types' );
$wpnonce            = wp_create_nonce( 'wpc_feedback_type' );

//to delete custom field
if ( isset( $_GET['delete'] ) && '' != $_GET['delete'] && wp_verify_nonce( $_GET['_wpnonce'], 'wpc_feedback_type' )  ) {
    unset( $wpc_feedback_types[$_GET['delete']] );

    update_option( 'wpc_feedback_types', $wpc_feedback_types );

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

<div style="" class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>
    <?php
	if($msg != ""){
		switch($msg) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Feedback Type <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated fade"><p>' . __( 'Feedback Type <strong>Updated</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Feedback Type <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
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
                <a href="admin.php?page=wpclients_feedback_wizard&tab=feedback_type&add=1" class="add-new-h2"><?php _e( 'Add New Feedback Type', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
            </div>

            <hr />

        <?php if ( is_array( $wpc_feedback_types ) && 0 < count( $wpc_feedback_types ) ) { ?>
            <form method="post" action="" name="edit_cat" id="edit_cat" >
                <input type="hidden" name="wpc_action" id="wpc_action2" value="" />
                <table width="700px" class="widefat post " style="width:95%;" id=sortable>
                    <thead>
                        <tr>
                            <th><?php _e( 'Type Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Type Title', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Type', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 0;
                    foreach( $wpc_feedback_types as $key=>$value ) {
                        $i++;

                    ?>
                        <tr id="field_<?php echo $key ?>" >
                            <td style="vertical-align: middle;">
                                <span id="cat_name_block_<?php echo $key ?>">
                                    <?php echo $key ?>
                                </span>
                            </td>
                            <td style="vertical-align: middle;">
                                <span>
                                    <?php echo $value['title'] ?>
                                </span>
                            </td>
                            <td style="vertical-align: middle;">
                            <?php
                                switch( $value['type'] ) {
                                    case 'button':
                                        _e( 'Buttons', WPC_CLIENT_TEXT_DOMAIN );
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
                                }
                            ?>
                            </td>
                            <td style="vertical-align: middle;">
                                <a href="admin.php?page=wpclients_feedback_wizard&tab=feedback_type&edit=<?php echo $key ?>"><?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                                &nbsp;&nbsp;&nbsp;
                                <a href="admin.php?page=wpclients_feedback_wizard&tab=feedback_type&_wpnonce=<?php echo $wpnonce ?>&delete=<?php echo $key ?>" class="group_delete" ><?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>

            </form>
            <?php
            } else {
            ?>
                <p><?php _e( 'No Feedback Types yet created', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
            <?php
            }
            ?>

        </div>
    </div>

</div>
