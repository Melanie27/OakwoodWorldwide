<?php
global $wpdb, $wpc_client;

$error = "";


//save wizard data
if ( isset( $_POST['save_wizard'] ) ) {

    $error = '';

    if ( '' == trim(  $_POST['wizard_data']['name'] ) ) {
        $error .= __( "Sorry, Wizard name is required.<br>", WPC_CLIENT_TEXT_DOMAIN );
    }

    if ( isset( $_POST['wizard_data']['version'] ) ) {
        $version = $_POST['wizard_data']['ver_1'] . '.' . $_POST['wizard_data']['ver_2'] . '.' . $_POST['wizard_data']['ver_3'];

        if ( $_POST['wizard_data']['version'] > $version ) {
            $error .= __( "New version number can't be less than previous version.<br>", WPC_CLIENT_TEXT_DOMAIN );
        }
    }

    if ( '' == $error ) {

        if ( isset( $_POST['wizard_data']['wizard_id'] ) && 0 < $_POST['wizard_data']['wizard_id'] ) {
            $wizard_id = $_POST['wizard_data']['wizard_id'];
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->base_prefix}wpc_client_feedback_wizards SET
                name = '%s',
                feedback_type = '%s',
                version = '%s'
                WHERE wizard_id = %d
                ", $_POST['wizard_data']['name'], $_POST['wizard_data']['feedback_type'], $version, $wizard_id ) );

            //delete old items
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}wpc_client_feedback_wizard_items WHERE wizard_id = %d", $wizard_id ) );
            $msg = 'u';

        } else {
            $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}wpc_client_feedback_wizards SET
                name = '%s',
                feedback_type = '%s',
                version = '%s',
                clients_id = '',
                groups_id = '',
                time = '%s'
                ", $_POST['wizard_data']['name'], $_POST['wizard_data']['feedback_type'], $version, time() ) );

            $wizard_id = $wpdb->insert_id;
            $msg = 'a';
        }


        //add items to wizard
        if ( isset( $_POST['wizard_data']['items'] ) && is_array( $_POST['wizard_data']['items'] ) && 0 < count( $_POST['wizard_data']['items'] ) ) {
            foreach( $_POST['wizard_data']['items'] as $item_id ) {
                $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}wpc_client_feedback_wizard_items SET
                    wizard_id   = %d,
                    item_id   = %d
                    ", $wizard_id, $item_id ) );
            }
        }

        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_feedback_wizard&tab=wizards&msg=' . $msg );
        exit;
    }

}


//get wizard data
if ( isset( $_REQUEST['wizard_data'] ) ) {
    $wizard_data = $_REQUEST['wizard_data'];
} elseif ( 'edit_wizard' == $_GET['tab'] && isset( $_GET['wizard_id'] ) ) {
    $wizard_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_wizards WHERE wizard_id = %d", $_GET['wizard_id'] ), ARRAY_A );
    $wizard_data['items'] = $wpdb->get_col( $wpdb->prepare( "SELECT item_id FROM {$wpdb->base_prefix}wpc_client_feedback_wizard_items WHERE wizard_id = %d ORDER BY id ASC", $_GET['wizard_id'] ) );

    //wizard version
    $version = ( isset( $wizard_data['version'] ) && '' != $wizard_data['version']  ) ? $wizard_data['version'] : '1.0.0';
    $version = explode( '.', $version );
    $wizard_data['ver_1'] = $version[0];
    $wizard_data['ver_2'] = $version[1];
    $wizard_data['ver_3'] = $version[2];
    unset( $version );

}


//change text
if ( 'create_wizard' == $_GET['tab'] )
    $button_text = __( 'Create Wizard', WPC_CLIENT_TEXT_DOMAIN );
else
    $button_text = __( 'Update Wizard', WPC_CLIENT_TEXT_DOMAIN );


global $wpdb;

$items = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_items", ARRAY_A );

$wpc_feedback_types = get_option( 'wpc_feedback_types' );



?>

<style type="text/css">

.wrap input[type=text] {
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

            <table>
                <tr>
                    <td>
                        <div id="item_box">
                            <div class="widgets-holder-wrap ui-droppable" id="available-widgets">
                                <div class="sidebar-name">
                                    <h3><?php _e( 'Available Items', WPC_CLIENT_TEXT_DOMAIN ) ?></h3>
                                </div>
                                <div class="box-holder">
                                    <p class="description"><?php _e( 'Drag Items from here to a Wizard on the right. Drag Items back here to delete them from Wizard.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
                                    <br class="clear">

                                    <div id="item_list" class="connectedSortable">
                                        <?php
                                        if ( is_array( $items ) && 0 < count( $items ) ):
                                            foreach( $items as $item ):
                                                if ( !isset( $wizard_data['items'] ) || !in_array( $item['item_id'], $wizard_data['items'] ) ) {
                                        ?>
                                        <div class="item">
                                            <div class="postbox">
                                                <input type="hidden" name="wizard_data[items][]" value="<?php echo $item['item_id'] ?>" />
                                                <h3 class='hndle'><span><?php echo $item['name'] ?></span></h3>
                                                <div class="item_description">
                                                    <?php
                                                    if ( 'img' == $item['type'] ) {
                                                        echo '<img class="img" src="' . site_url() . '/wp-admin/admin-ajax.php?action=wpc_get_fw_item_file&id=' . $item['item_id'] . '&thumbnail=1" alt="" />';
                                                    } elseif ( 'pdf' == $item['type'] ) {
                                                        echo '<img class="pdf" src="' .  $wpc_client->plugin_url . 'addons/feedback_wizard/images/icon_pdf.png" alt="" width="70" hight="82" />';

                                                        if ( 27 < strlen( $item['file_name'] ) ) {
                                                            $file_name='';
                                                            for ( $start = 0, $length = 27; $subtext = substr( $item['file_name'], $start, $length); $start = $start + 27 ){
//                                                                $file_name .= $subtext . '&shy;';
                                                                $file_name .= $subtext . ' ';
                                                            }
                                                        } else {
                                                            $file_name = $item['file_name'];
                                                        }
                                                        echo '<div class="file_name">' . $file_name . '</div>';
                                                    } elseif ( 'att' == $item['type'] ) {
                                                        echo '<img class="att" src="' .  $wpc_client->plugin_url . 'addons/feedback_wizard/images/icon_attachment.png" alt="" width="83" hight="82" />';

                                                        if ( 27 < strlen( $item['file_name'] ) ) {
                                                            $file_name='';
                                                            for ( $start = 0, $length = 27; $subtext = substr( $item['file_name'], $start, $length); $start = $start + 27 ){
//                                                                $file_name .= $subtext . '&shy;';
                                                                $file_name .= $subtext . ' ';
                                                            }
                                                        } else {
                                                            $file_name = $item['file_name'];
                                                        }
                                                        echo '<div class="file_name">' . $file_name . '</div>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                                }
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>

                                    <br class="clear">
                                </div>
                                <br class="clear">
                            </div>
                        </div>
                    </td>
                    <td>
                        <form name="edit_wizard" id="edit_wizard" method="post" >
                            <input type="hidden" name="save_wizard" value="save_wizard" />
                            <input type="hidden" name="wizard_data[wizard_id]" value="<?php echo ( 'edit_wizard' == $_GET['tab'] && isset( $_GET['wizard_id'] ) ) ? $_GET['wizard_id'] : '' ?>" />
                            <input type="hidden" name="wizard_data[version]" value="<?php echo ( isset( $wizard_data['version'] ) ) ? $wizard_data['version'] : '1.0.0' ?>" />

                            <div id="wizard_box">
                                <div class="widgets-holder-wrap">
                                    <div class="sidebar-name">
                                        <h3><?php _e( 'Wizard Items', WPC_CLIENT_TEXT_DOMAIN ) ?></h3>
                                    </div>
                                    <div class="box-holder">
                                        <div class="wizard_name">
                                            <label for="wizard_name"><?php _e( 'Wizard Name:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                            <input type="text" name="wizard_data[name]" id="wizard_name" value="<?php echo ( isset( $wizard_data['name'] ) ) ? $wizard_data['name'] : '' ?>" />
                                            <p>
                                                <label for="wizard_feedback_type"><?php _e( 'Feedback Type:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                                <select name="wizard_data[feedback_type]" id="wizard_feedback_type">
                                                    <option value="-1"><?php _e( 'Default Type', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                                    <?php if ( is_array( $wpc_feedback_types ) && 0 < count( $wpc_feedback_types ) ) {
                                                        foreach( $wpc_feedback_types as $key => $type ) {
                                                            $selected = ( isset( $wizard_data['feedback_type'] ) && $key == $wizard_data['feedback_type'] ) ? 'selected' : '';
                                                            echo '<option value="' . $key . '" ' . $selected . ' >' . $type['title'] . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </p>
                                            <p>
                                                <label for="wizard_feedback_type"><?php _e( 'Version:', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                                <select class="wizard_version" name="wizard_data[ver_1]" id="ver_1">
                                                    <?php
                                                        for( $i = 1; $i < 10; $i++ ) {
                                                            $selected = '';
                                                            if ( isset( $wizard_data['ver_1'] ) && $i == $wizard_data['ver_1'] )
                                                                $selected = 'selected';

                                                            echo '<option value="' . $i . '" ' . $selected . ' >' . $i . '&nbsp;</option>';
                                                        }
                                                    ?>
                                                </select>
                                                <select class="wizard_version" name="wizard_data[ver_2]" id="ver_2">
                                                    <?php
                                                        for( $i = 0; $i < 10; $i++ ) {
                                                            $selected = '';
                                                            if ( isset( $wizard_data['ver_2'] ) && $i == $wizard_data['ver_2'] )
                                                                $selected = 'selected';

                                                            echo '<option value="' . $i . '" ' . $selected . ' >' . $i . '&nbsp;</option>';
                                                        }
                                                    ?>
                                                </select>
                                                <select class="wizard_version" name="wizard_data[ver_3]" id="ver_3">
                                                    <?php
                                                        for( $i = 0; $i < 10; $i++ ) {
                                                            $selected = '';
                                                            if ( isset( $wizard_data['ver_3'] ) && $i == $wizard_data['ver_3'] )
                                                                $selected = 'selected';

                                                            echo '<option value="' . $i . '" ' . $selected . ' >' . $i . '&nbsp;</option>';
                                                        }
                                                    ?>
                                                </select>
                                            </p>
                                        </div>
                                        <p class="description"><?php _e( 'Drag Items here.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
                                        <br class="clear">

                                        <div id="wizard_item_list" class="connectedSortable">
                                            <?php
                                            if ( isset( $wizard_data['items'] ) && is_array( $wizard_data['items'] ) && 0 < count( $wizard_data['items'] ) ):
                                                foreach( $wizard_data['items'] as $item_id ):
                                                    $item = $wpdb->get_row( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_items WHERE item_id = $item_id", ARRAY_A );
                                            ?>
                                            <div class="item">
                                                <div class="postbox">
                                                    <input type="hidden" name="wizard_data[items][]" value="<?php echo $item['item_id'] ?>" />
                                                    <h3 class='hndle'><span><?php echo $item['name'] ?></span></h3>
                                                    <div class="item_description">
                                                        <?php
                                                        if ( 'img' == $item['type'] ) {
                                                            echo '<img class="img" src="' . site_url() . '/wp-admin/admin-ajax.php?action=wpc_get_fw_item_file&id=' . $item['item_id'] . '&thumbnail=1" alt="" />';
                                                        } elseif ( 'pdf' == $item['type'] ) {
                                                            echo '<img class="pdf" src="' .  $wpc_client->plugin_url . 'addons/feedback_wizard/images/icon_pdf.png" alt="" width="70" hight="82" />';

                                                            if ( 27 < strlen( $item['file_name'] ) ) {
                                                                $file_name='';
                                                                for ( $start = 0, $length = 27; $subtext = substr( $item['file_name'], $start, $length); $start = $start + 27 ){
    //                                                                $file_name .= $subtext . '&shy;';
                                                                    $file_name .= $subtext . ' ';
                                                                }
                                                            } else {
                                                                $file_name = $item['file_name'];
                                                            }
                                                            echo '<div class="file_name">' . $file_name . '</div>';
                                                        } elseif ( 'att' == $item['type'] ) {
                                                            echo '<img class="att" src="' .  $wpc_client->plugin_url . 'addons/feedback_wizard/images/icon_attachment.png" alt="" width="83" hight="82" />';

                                                            if ( 27 < strlen( $item['file_name'] ) ) {
                                                                $file_name='';
                                                                for ( $start = 0, $length = 27; $subtext = substr( $item['file_name'], $start, $length); $start = $start + 27 ){
    //                                                                $file_name .= $subtext . '&shy;';
                                                                    $file_name .= $subtext . ' ';
                                                                }
                                                            } else {
                                                                $file_name = $item['file_name'];
                                                            }
                                                            echo '<div class="file_name">' . $file_name . '</div>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                                endforeach;
                                            endif;
                                            ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" value="<?php echo $button_text ?>" class="button-primary" id="save_wizard">
            </p>

        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">

    jQuery( document ).ready( function( $ ) {

        jQuery( "#item_list, #wizard_item_list" ).sortable({
            connectWith: ".connectedSortable",
            items: '.item',
        }).disableSelection();


        jQuery( "#save_wizard" ).live( 'click', function() {

            var msg = '';

            if ( jQuery( "#wizard_name" ).val() == '' ) {
                msg += "<?php _e( 'A Wizard Name is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
            }


            if ( msg != '' ) {
                jQuery( "#message" ).html( msg );
                jQuery( "#message" ).show();
                return false;
            }


            jQuery( '#edit_wizard' ).submit();

            return false;
        });


    });

</script>