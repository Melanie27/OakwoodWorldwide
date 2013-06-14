<?php

$error = "";

if ( isset( $_POST['submit'] ) ) {
    $feedback_type_name = ( isset( $_POST['feedback_type']['name'] ) ) ? $_POST['feedback_type']['name'] : '';
    $feedback_type_name = strtolower( $feedback_type_name );
    $feedback_type_name = str_replace( ' ', '_', $feedback_type_name );
    $feedback_type_name = preg_replace( '/[^a-z0-9_]/i', '', $feedback_type_name );

    $wpc_feedback_types = get_option( 'wpc_feedback_types' );

    // validate at php side
    //field name
    if ( empty( $feedback_type_name ) ) {
        $error .= __( 'A Feedback Type Name is required.<br/>', WPC_CLIENT_TEXT_DOMAIN );
    } elseif ( isset( $_GET['add'] ) && '1' == $_GET['add'] ) {
        //check that field not already exist
        if ( isset( $wpc_feedback_types[$feedback_type_name] ) )
            $error .= sprintf( __( 'A Feedback Type with this name "%s" already exist already.<br/>', WPC_CLIENT_TEXT_DOMAIN ), $feedback_type_name );

    }

    //field type
    if ( empty( $_POST['feedback_type']['type'] ) )
        $error .= __( 'A Feedback Type is required.<br/>', WPC_CLIENT_TEXT_DOMAIN );





    //save the feedback type field
    if ( empty( $error ) ) {
        $feedback_type = $_POST['feedback_type'];
        unset( $feedback_type['name'] );

        $wpc_feedback_types[$feedback_type_name] = $feedback_type;

        update_option( 'wpc_feedback_types', $wpc_feedback_types );

        do_action( 'wp_client_redirect', 'admin.php?page=wpclients_feedback_wizard&tab=feedback_type&msg=a' );
        exit();

    }
}


//get feedback type data
if ( isset( $_REQUEST['feedback_type'] ) ) {
    $feedback_type = $_REQUEST['feedback_type'];
} elseif ( isset( $_GET['edit'] ) &&  '' != $_GET['edit'] ) {
    $wpc_feedback_types = get_option( 'wpc_feedback_types' );
    if ( isset( $wpc_feedback_types[$_GET['edit']] ) ) {
        $feedback_type           = $wpc_feedback_types[$_GET['edit']];
        $feedback_type['name']   = $_GET['edit'];
        unset( $wpc_feedback_types );
    } else {
        do_action( 'wp_client_redirect', 'admin.php?page=wpclients_feedback_wizard&tab=feedback_type&msg=n' );
        exit();
    }
}

//change text
if ( isset( $_GET['add'] ) && '1' == $_GET['add'] )
    $title_text = __( 'Add Feedback Type', WPC_CLIENT_TEXT_DOMAIN );
else
    $title_text = __( 'Update Feedback Type', WPC_CLIENT_TEXT_DOMAIN );

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
            <h2><?php echo $title_text ?></h2>
            <br>


            <form action="" method="post">
                <?php if ( isset( $_GET['edit'] ) && '' != $_GET['edit'] ): ?>
                <input type="hidden" name="feedback_type[name]" value="<?php echo ( isset( $feedback_type['name'] ) ) ? $feedback_type['name'] : '' ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th>
                            <label for="name"><?php _e( 'Feedback Type Name', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="required">(<?php _e( 'required', WPC_CLIENT_TEXT_DOMAIN ) ?>)</span></label>
                        </th>
                        <td>
                            <?php if ( isset( $_GET['edit'] ) && '' != $_GET['edit'] ): ?>
                                <input type="text" disabled value="<?php echo ( isset( $feedback_type['name'] ) ) ? $feedback_type['name'] : '' ?>" />
                                <br>
                                <span class="description"><?php _e( "Can't be changed.", WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            <?php else: ?>
                                <input type="text" name="feedback_type[name]" id="name" value="<?php echo ( isset( $feedback_type['name'] ) ) ? $feedback_type['name'] : '' ?>" />
                                <br>
                                <span class="description"><?php _e( 'The name used for identification the feedback type. Should consist "a-z" and sign "_".', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="title"><?php _e( 'Feedback Type Title', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                        </th>
                        <td>
                            <input type="text" name="feedback_type[title]" id="title" value="<?php echo ( isset( $feedback_type['title'] ) ) ? $feedback_type['title'] : '' ?>" />
                            <br>
                            <span class="description"><?php _e( 'Displayed ', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="type"><?php _e( 'Feedback Type', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="required">(<?php _e( 'required', WPC_CLIENT_TEXT_DOMAIN) ?>)</span></label>
                        </th>
                        <td>
                            <select name="feedback_type[type]" id="type">
                                <option value="button" <?php echo ( isset( $feedback_type['type'] ) && $feedback_type['type'] == 'button' ) ? 'selected' : ''  ?>><?php _e( 'Buttons', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                <option value="radio" <?php echo ( isset( $feedback_type['type'] ) && $feedback_type['type'] == 'radio' ) ? 'selected' : ''  ?>><?php _e( 'Radio Buttons', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                <option value="checkbox" <?php echo ( isset( $feedback_type['type'] ) && $feedback_type['type'] == 'checkbox' ) ? 'selected' : ''  ?>><?php _e( 'Checkboxes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                <option value="selectbox" <?php echo ( isset( $feedback_type['type'] ) && $feedback_type['type'] == 'selectbox' ) ? 'selected' : ''  ?>><?php _e( 'Select Box', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            </select>
                            <br>
                            <span class="description"><?php _e( 'Select type of the feedback.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>

                            <div class="ct-field-type-options">
                                <h4><?php _e( 'Fill in the options for feedback', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h4>
                                <p><?php _e( 'Order By', WPC_CLIENT_TEXT_DOMAIN ) ?>:
                                    <select name="feedback_type[sort_order]">
                                        <option value="default" <?php echo ( isset( $feedback_type['sort_order'] ) && 'default' == $feedback_type['sort_order'] ) ? 'selected' : '' ?> ><?php _e( 'Order Entered', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="asc" <?php echo ( isset( $feedback_type['sort_order'] ) && 'asc' == $feedback_type['sort_order'] ) ? 'selected' : '' ?> ><?php _e( 'Name - Ascending', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="desc" <?php echo ( isset( $feedback_type['sort_order'] ) && 'desc' == $feedback_type['sort_order'] ) ? 'selected' : '' ?> ><?php _e( 'Name - Descending', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </p>
                                <p><?php _e( 'Align', WPC_CLIENT_TEXT_DOMAIN ) ?>:
                                    <select name="feedback_type[align]">
                                        <option value="hor" <?php echo ( isset( $feedback_type['align'] ) && 'hor' == $feedback_type['align'] ) ? 'selected' : '' ?> ><?php _e( 'Horizontally', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="ver" <?php echo ( isset( $feedback_type['align'] ) && 'ver' == $feedback_type['align'] ) ? 'selected' : '' ?> ><?php _e( 'Vertically', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </p>

                                <?php if ( isset( $feedback_type['options'] ) && is_array( $feedback_type['options'] ) ) { ?>
                                    <?php foreach ( $feedback_type['options'] as $key => $field_option ) { ?>
                                        <p>
                                            <?php _e( 'Option', WPC_CLIENT_TEXT_DOMAIN ) ?> <?php echo( $key ) ?>:
                                            <input type="text" name="feedback_type[options][<?php echo( $key ) ?>]" value="<?php echo( $field_option ) ?>" />
                                            <input type="radio" value="<?php echo( $key ) ?>" name="feedback_type[default_option]" <?php echo ( isset( $feedback_type['default_option'] ) && $feedback_type['default_option'] == $key ) ? 'checked'  : '' ?> />
                                            <?php _e( 'Default Value', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                            <?php if ( $key != 1 ): ?>
                                                <a href="javascript:;" class="ct-field-delete-option">[x]</a>
                                            <?php endif; ?>
                                        </p>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p><?php _e( 'Option', WPC_CLIENT_TEXT_DOMAIN ) ?> 1:
                                        <input type="text" name="feedback_type[options][1]" value="<?php echo ( isset( $feedback_type['options'][1] ) ) ? $feedback_type['options'][1] : '' ?>" />
                                        <input type="radio" value="1" name="feedback_type[default_option]" <?php echo ( isset( $feedback_type['default_option'] ) && $feedback_type['default_option'] == '1' ) ? 'checked' : '' ?> />
                                        <?php _e( 'Default Value', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                    </p>
                                <?php } ?>

                                <div class="ct-field-additional-options"></div>
                                <input type="hidden" value="<?php echo ( isset( $feedback_type['options'] ) ) ? count( $feedback_type['options'] ) : '1' ?>" name="track_number" />
                                <p><a href="javascript:;" class="ct-field-add-option"><?php _e( 'Add another option', WPC_CLIENT_TEXT_DOMAIN ) ?></a></p>
                            </div>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" class="button-primary" name="submit" value="<?php _e( 'Save Feedback Type', WPC_CLIENT_TEXT_DOMAIN ) ?>">
                </p>
            </form>

        </div>
    </div>


</div>



<script type="text/javascript" language="javascript">

    jQuery( document ).ready( function() {
        jQuery(window).bind( 'load', field_type_options );
        jQuery( '#type' ).bind( 'change', field_type_options );


        // public field values initiation
        function field_type_options() {
            if ( jQuery( '#type' ).val() === 'radio' ||
                jQuery( '#type' ).val() === 'selectbox' ||
                jQuery( '#type' ).val() === 'button' ||
                jQuery( '#type' ).val() === 'checkbox' ) {
                jQuery( '.ct-field-type-options' ).show();
            }
        }


        // Feedback Type add options
        jQuery('.ct-field-add-option').click(function() {
            var count = parseInt(jQuery('input[name="track_number"]').val(), 10) + 1;

            jQuery('.ct-field-additional-options').append(function() {


                jQuery('input[name="track_number"]').val(count);

                return '<p><?php _e( 'Option', WPC_CLIENT_TEXT_DOMAIN ) ?> ' + count + ': ' +
                            '<input type="text" name="feedback_type[options][' + count + ']"> ' +
                            '<input type="radio" value="' + count + '" name="feedback_type[default_option]"> ' +
                            '<?php _e( 'Default Value', WPC_CLIENT_TEXT_DOMAIN ) ?> ' +
                            '<a href="javascript:;" class="ct-field-delete-option">[x]</a>' +
                        '</p>';
            });

            jQuery( 'input[name="feedback_type[options][' + count + ']"]' ).focus();
        });


        // Feedback Type remove options
        jQuery('.ct-field-delete-option').live('click', function() {
            jQuery(this).parent().remove();
        });


    });

</script>