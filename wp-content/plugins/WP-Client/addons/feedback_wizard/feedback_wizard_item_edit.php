<?php
global $wpdb;

$error = "";


//save item data
if ( isset( $_POST['item_data'] ) ) {

    $item_data = $_POST['item_data'];

    if ( '' == trim( $item_data['name'] ) ) {
        $error .= __( "Sorry, Item name is required.<br>", WPC_CLIENT_TEXT_DOMAIN );
    }

    if ( !isset( $item_data['item_id'] ) && !isset( $_FILES['file']['name'] ) ) {
        $error .= __( "Sorry, Item file is required.<br>", WPC_CLIENT_TEXT_DOMAIN );
    }


    if ( '' == $error ) {
        if ( isset( $item_data['item_id'] ) && 0 < $item_data['item_id'] ) {

            if ( isset( $_POST['item_type'] ) )
                $item_data['type'] = $_POST['item_type'];

            //Update item
            $msg = 'u';

            if ( isset( $_FILES['file']['name'] ) && '' != $_FILES['file']['name'] ) {
                $uploads        = wp_upload_dir();
                $target_path    = $uploads['basedir'] . '/';

                if ( !is_dir( $target_path . '/wpclient/items' ) ) {
                    if ( !is_dir( $target_path . '/wpclient' ) ) {
                        mkdir( $target_path . '/wpclient', 0777 );

                        $htp = fopen( $target_path . '/wpclient/.htaccess', 'w' );

                        // $file being the .htpasswd file
                        fputs( $htp, 'deny from all' );
                    }

                    mkdir( $target_path . '/wpclient/items', 0777 );

                    $htp = fopen( $target_path . '/wpclient/items/.htaccess', 'w' );

                    // $file being the .htpasswd file
                    fputs( $htp, 'deny from all' );
                }

                $file_name      = $_FILES['file']['name'];
                $random_digit   = rand( 0000,9999 );
                $new_file_name  = basename( $random_digit . $_FILES['file']['name'] );
                $target_path    = $target_path . "/wpclient/items/";

                if ( move_uploaded_file( $_FILES['file']['tmp_name'], $target_path . $new_file_name ) ) {

                    //delete old files
                    $old_item_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_items WHERE item_id = %d", $item_data['item_id'] ), ARRAY_A );
                    if ( isset( $old_item_data['file'] ) && '' != $old_item_data['file'] ) {
                        //old file
                        if ( file_exists( $target_path . $old_item_data['file'] ) )
                            unlink( $target_path . $old_item_data['file'] );

                        if ( 'img' == $item_data['type'] ) {
                            //old thumbnail file
                            if ( file_exists( $target_path . 't_'. $old_item_data['file'] ) )
                                unlink( $target_path . 't_'. $old_item_data['file'] );
                        }
                    }


                    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->base_prefix}wpc_client_feedback_items SET
                        name            = '%s',
                        description     = '%s',
                        file_name       = '%s',
                        file            = '%s'
                        WHERE item_id = %d
                        ", trim( $item_data['name'] ), trim( $item_data['description'] ), $file_name, $new_file_name, $item_data['item_id'] ) );

                    /*
                    * create Thumbnail
                    */
                    if ( 'img' == $item_data['type'] ) {
                        $this->create_thumbnail( $target_path, $new_file_name, 400 );
                    }

                }

            } else {
                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->base_prefix}wpc_client_feedback_items SET
                    name        = '%s',
                    description = '%s'
                    WHERE item_id = %d
                    ", trim( $item_data['name'] ), trim( $item_data['description'] ), $item_data['item_id'] ) );
            }

        } else {
            //Add item
            $msg = 'a';

            if ( isset( $_FILES['file']['name'] ) && '' != $_FILES['file']['name'] ) {
                $uploads        = wp_upload_dir();
                $target_path    = $uploads['basedir'] . '/';

                if ( !is_dir( $target_path . '/wpclient/items' ) ) {
                    if ( !is_dir( $target_path . '/wpclient' ) ) {
                        mkdir( $target_path . '/wpclient', 0777 );

                        $htp = fopen( $target_path . '/wpclient/.htaccess', 'w' );

                        // $file being the .htpasswd file
                        fputs( $htp, 'deny from all' );
                    }
                    mkdir( $target_path . '/wpclient/items', 0777 );

                    $htp = fopen( $target_path . '/wpclient/items/.htaccess', 'w' );

                    // $file being the .htpasswd file
                    fputs( $htp, 'deny from all' );
                }

            }

            $file_name      = $_FILES['file']['name'];
            $random_digit   = rand( 0000,9999 );
            $new_file_name  = basename( $random_digit . $_FILES['file']['name'] );
            $target_path    = $target_path . "/wpclient/items/";

            if ( move_uploaded_file( $_FILES['file']['tmp_name'], $target_path . $new_file_name ) ) {

                $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}wpc_client_feedback_items SET
                    name        = '%s',
                    description = '%s',
                    file_name   = '%s',
                    file        = '%s',
                    type        = '%s'
                    ", trim( $item_data['name'] ), trim( $item_data['description'] ), $file_name, $new_file_name, $item_data['type'] ) );

                /*
                * create Thumbnail
                */
                if ( 'img' == $item_data['type'] ) {
                    $this->create_thumbnail( $target_path, $new_file_name, 400 );
                }
            }
        }

        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_feedback_wizard&tab=items&msg=' . $msg );
        exit;

    }
}


//get item data
if ( isset( $_POST['item_data'] ) ) {
    $item_data = $_POST['item_data'];
} elseif ( 'edit_item' == $_GET['tab'] && isset( $_GET['item_id'] ) ) {
    $item_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_items WHERE item_id = %d", $_GET['item_id'] ), ARRAY_A );
}

//change text
if ( 'add_item' == $_GET['tab'] )
    $button_text = __( 'Add new Item', WPC_CLIENT_TEXT_DOMAIN );
else
    $button_text = __( 'Edit Item', WPC_CLIENT_TEXT_DOMAIN );

?>

<style type="text/css">

.wrap input[type=text] {
    width:400px;

}.wrap textarea {
    width:400px;
}

.wrap input[type=password] {
    width:400px;
}

</style>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_feedback_tabs_menu() ?>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <div id="message" class="updated fade" <?php echo ( empty( $error ) )? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>

            <h3><?php echo $button_text ?>:</h3>
            <form name="edit_item" id="edit_item" method="post" enctype="multipart/form-data" >
                <?php if ( 'edit_item' == $_GET['tab'] ): ?>
                <input type="hidden" name="item_data[item_id]" value="<?php echo ( isset( $_GET['item_id'] ) ) ? $_GET['item_id'] : '' ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label><?php _e( 'Type of Item', WPC_CLIENT_TEXT_DOMAIN ) ?><span class="description"> <?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? '' : __( "(can't be changed later)", WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                            </th>
                            <td>
                            <?php if ( 'edit_item' == $_GET['tab'] ): ?>
                                <input type="hidden" name="item_type" value="<?php echo ( isset( $item_data['type'] ) ) ?  $item_data['type'] : '' ?>" />
                            <?php endif; ?>
                                <label for="item_data_type_img"><input type="radio" name="item_data[type]" id="item_data_type_img" value="img" <?php echo ( !isset( $item_data['type'] ) || 'img' == $item_data['type'] ) ? 'checked' : '' ?> <?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? 'disabled' : '' ?> /> <?php _e( 'Image', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                <label for="item_data_type_pdf"><input type="radio" name="item_data[type]" id="item_data_type_pdf" value="pdf" <?php echo ( isset( $item_data['type'] ) && 'pdf' == $item_data['type'] ) ? 'checked' : '' ?> <?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? 'disabled' : '' ?> /> <?php _e( 'PDF', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                <label for="item_data_type_att"><input type="radio" name="item_data[type]" id="item_data_type_att" value="att" <?php echo ( isset( $item_data['type'] ) && 'att' == $item_data['type'] ) ? 'checked' : '' ?> <?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? 'disabled' : '' ?> /> <?php _e( 'Attachment', WPC_CLIENT_TEXT_DOMAIN ) ?></label>

                            </td>
                        </tr>
                    </tbody>
                </table>

                 <br><br>

                <fieldset class="sectionwrap" id="type_img">
                    <div class="postbox">
                        <h3 class='hndle'><span class="description"><?php _e( 'Image', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="img_name"><?php _e( 'Name', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                                    </th>
                                    <td>
                                        <input type="text" name="item_data[name]" id="img_name" value="<?php echo ( isset( $item_data['name'] ) ) ? $item_data['name'] : ''  ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="img_description"><?php _e( 'Description', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                    </th>
                                    <td>
                                        <textarea name="item_data[description]" id="img_description"><?php echo ( isset( $item_data['description'] ) ) ? $item_data['description'] : ''  ?></textarea>
                                    </td>
                                </tr>
                                <?php if ( isset( $item_data['file'] ) && '' != $item_data['file'] ) { ?>
                                <tr>
                                    <th scope="row">
                                        <?php _e( 'Current Image', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(Thumbnail 400px)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                    </th>
                                    <td>
                                        <img id="current_img" src="<?php echo site_url() ?>/wp-admin/admin-ajax.php?action=wpc_get_fw_item_file&id=<?php echo $item_data['item_id'] ?>&thumbnail=1" width="400" height="400" alt="" />
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <th scope="row">
                                        <label for="img_file"><?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? __( 'Change Image', WPC_CLIENT_TEXT_DOMAIN ) : __( 'Upload Image', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                                    </th>
                                    <td>
                                        <input type="file" name="file" id="img_file" />
                                        <span class="description"><?php _e( '(jpg, png, gif)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        <span class="description"><?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? __( 'Note: Previous Image will be deleted.', WPC_CLIENT_TEXT_DOMAIN ) : '' ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="sectionwrap" id="type_pdf">
                    <div class="postbox">
                        <h3 class='hndle'><span class="description"><?php _e( 'PDF', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="pdf_name"><?php _e( 'Name', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                                    </th>
                                    <td>
                                        <input type="text" name="item_data[name]" id="pdf_name" value="<?php echo ( isset( $item_data['name'] ) ) ? $item_data['name'] : ''  ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="pdf_description"><?php _e( 'Description', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                    </th>
                                    <td>
                                        <textarea name="item_data[description]" id="pdf_description"><?php echo ( isset( $item_data['description'] ) ) ? $item_data['description'] : ''  ?></textarea>
                                    </td>
                                </tr>
                                <?php if ( isset( $item_data['file'] ) && '' != $item_data['file'] ) { ?>
                                <tr>
                                    <th scope="row">
                                        <?php _e( 'Current PDF', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                    </th>
                                    <td>
                                        <a id="current_pdf" href="<?php echo site_url() ?>/wp-admin/admin-ajax.php?action=wpc_get_fw_item_file&id=<?php echo $item_data['item_id'] ?>&d=true" alt=""><?php echo $item_data['file_name'] ?></a>
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <th scope="row">
                                        <label for="pdf_file"><?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? __( 'Change PDF', WPC_CLIENT_TEXT_DOMAIN ) : __( 'Upload PDF', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                                    </th>
                                    <td>
                                        <input type="file" name="file" id="pdf_file" />
                                        <span class="description"><?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? __( 'Note: Previous PDF will be deleted.', WPC_CLIENT_TEXT_DOMAIN ) : '' ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="sectionwrap" id="type_att">
                    <div class="postbox">
                        <h3 class='hndle'><span class="description"><?php _e( 'Attachment', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="att_name"><?php _e( 'Name', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                                    </th>
                                    <td>
                                        <input type="text" name="item_data[name]" id="att_name" value="<?php echo ( isset( $item_data['name'] ) ) ? $item_data['name'] : ''  ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="att_description"><?php _e( 'Description', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                    </th>
                                    <td>
                                        <textarea name="item_data[description]" id="att_description"><?php echo ( isset( $item_data['description'] ) ) ? $item_data['description'] : ''  ?></textarea>
                                    </td>
                                </tr>
                                <?php if ( isset( $item_data['file'] ) && '' != $item_data['file'] ) { ?>
                                <tr>
                                    <th scope="row">
                                        <?php _e( 'Current Attachment', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                    </th>
                                    <td>
                                        <a id="current_att" href="<?php echo site_url() ?>/wp-admin/admin-ajax.php?action=wpc_get_fw_item_file&id=<?php echo $item_data['item_id'] ?>&d=true" alt=""><?php echo $item_data['file_name'] ?></a>
                                    </td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <th scope="row">
                                        <label for="att_file"><?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? __( 'Change Attachment', WPC_CLIENT_TEXT_DOMAIN ) : __( 'Upload Attachment', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                                    </th>
                                    <td>
                                        <input type="file" name="file" id="att_file" />
                                        <span class="description"><?php echo ( isset( $_GET['item_id'] ) && 0 < $_GET['item_id'] ) ? __( 'Note: Previous File will be deleted.', WPC_CLIENT_TEXT_DOMAIN ) : '' ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </fieldset>

                <p class="submit">
                    <input type="button" value="<?php _e( 'Save Item', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button-primary" id="save_item" name="save_item">
                </p>

            </form>

        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">

    jQuery( document ).ready( function( $ ) {

        jQuery( '#type_<?php echo ( isset( $item_data['type'] ) ) ? $item_data['type'] : 'img' ?>' ).show();

        //hide/show fields
        jQuery( "input[name='item_data[type]']" ).change( function() {
            var type = jQuery( this ).val();
            jQuery( "input[name='item_data[type]']" ).attr( 'disabled', true );
            jQuery( 'fieldset:visible' ).fadeOut( 400, function() {
                jQuery( '#type_' + type ).fadeIn( 500 );
                jQuery( "input[name='item_data[type]']" ).attr( 'disabled', false );
            });

        });


	    <?php echo ( empty( $error ) )? 'jQuery( "#message" ).hide();' : '' ?>


        /*
        * Save ite,
        */
	    jQuery( "#save_item" ).live( 'click', function() {

		    var msg = '';

            var elem = jQuery( 'input[name="item_data[type]"]:checked' );
            if ( elem.length ) {
                jQuery( '.wpc_error' ).attr( 'class', '' );
                if ( 'img' == elem.val() ) {
                    if ( jQuery( "#img_name" ).val() == '' ) {
                        msg += "<?php _e( 'A Item Name is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                        jQuery( '#img_name' ).parent().parent().attr( 'class', 'wpc_error' );
                    }
                    if ( jQuery( "#img_file" ).val() == '' && 0 == jQuery( '#current_img' ).length ) {
			            msg += "<?php _e( 'A Item File is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                        jQuery( '#img_file' ).parent().parent().attr( 'class', 'wpc_error' );
                    }
                } else if ( 'pdf' == elem.val() ) {
                    if ( jQuery( "#pdf_name" ).val() == '' ) {
                        msg += "<?php _e( 'A Item Name is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                        jQuery( '#pdf_name' ).parent().parent().attr( 'class', 'wpc_error' );
                    }
                    if ( jQuery( "#pdf_file" ).val() == '' && 0 == jQuery( '#current_pdf' ).length ) {
                        msg += "<?php _e( 'A Item File is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                        jQuery( '#pdf_file' ).parent().parent().attr( 'class', 'wpc_error' );
                    }
                } else if ( 'att' == elem.val() ) {
                    if ( jQuery( "#att_name" ).val() == '' ) {
                        msg += "<?php _e( 'A Item Name is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                        jQuery( '#att_name' ).parent().parent().attr( 'class', 'wpc_error' );
                    }
                    if ( jQuery( "#att_file" ).val() == '' && 0 == jQuery( '#current_att' ).length ) {
                        msg += "<?php _e( 'A Item File is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                        jQuery( '#att_file' ).parent().parent().attr( 'class', 'wpc_error' );
                    }
                }

		        if ( msg != '' ) {
			        jQuery( "#message" ).html( msg );
			        jQuery( "#message" ).show();
                    return false;
		        }

                jQuery( 'fieldset:hidden' ).remove();
                jQuery( '#edit_item' ).submit();
            }
            return false;
	    });
    });

</script>