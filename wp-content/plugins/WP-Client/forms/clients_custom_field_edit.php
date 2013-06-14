<?php

$error = "";

if ( isset( $_POST['submit'] ) ) {
    $custom_field_name = ( isset( $_POST['custom_field']['name'] ) ) ? $_POST['custom_field']['name'] : '';
    $custom_field_name = strtolower( $custom_field_name );
    $custom_field_name = str_replace( ' ', '_', $custom_field_name );
    $custom_field_name = preg_replace( '/[^a-z0-9_]/i', '', $custom_field_name );

    $wpc_custom_fields = get_option( 'wpc_custom_fields' );

    // validate at php side
    //field name
    if ( empty( $custom_field_name ) ) {
        $error .= __( 'A Custom Field Name is required.<br/>', WPC_CLIENT_TEXT_DOMAIN );
    } elseif ( isset( $_GET['add'] ) && '1' == $_GET['add'] ) {
        //add prefix to field
        $custom_field_name = 'wpc_' . $custom_field_name;
        //check that field not already exist
        if ( isset( $wpc_custom_fields[$custom_field_name] ) )
            $error .= sprintf( __( 'A Custom Field with this name "%s" already exist already.<br/>', WPC_CLIENT_TEXT_DOMAIN ), $custom_field_name );

    }

    //field type
    if ( empty( $_POST['custom_field']['type'] ) )
        $error .= __( 'A Custom Field Type is required.<br/>', WPC_CLIENT_TEXT_DOMAIN );





    //save the custom field
    if ( empty( $error ) ) {
        $custom_field = $_POST['custom_field'];
        unset( $custom_field['name'] );
        $custom_field['display'] = ( isset( $custom_field['display'] ) && '1' == $custom_field['display'] ) ? '1' : '0';

        $wpc_custom_fields[$custom_field_name] = $custom_field;

        update_option( 'wpc_custom_fields', $wpc_custom_fields );

        do_action( 'wp_client_redirect', 'admin.php?page=wpclients&tab=custom_fields&msg=a' );
        exit();

    }
}


//get custom field data
if ( isset( $_REQUEST['custom_field'] ) ) {
    $custom_field = $_REQUEST['custom_field'];
} elseif ( isset( $_GET['edit'] ) &&  '' != $_GET['edit'] ) {
    $wpc_custom_fields = get_option( 'wpc_custom_fields' );
    if ( isset( $wpc_custom_fields[$_GET['edit']] ) ) {
        $custom_field           = $wpc_custom_fields[$_GET['edit']];
        $custom_field['name']   = $_GET['edit'];
        unset( $wpc_custom_fields );
    } else {
        do_action( 'wp_client_redirect', 'admin.php?page=wpclients&tab=custom_fields&msg=n' );
        exit();
    }
}

//change text
if ( isset( $_GET['add'] ) && '1' == $_GET['add'] )
    $title_text = __( 'Add Custom Field', WPC_CLIENT_TEXT_DOMAIN );
else
    $title_text = __( 'Update Custom Field', WPC_CLIENT_TEXT_DOMAIN );

?>

<style type="text/css">

.wrap input[type=text] {
    width:400px;
}

.wrap input[type=password] {
    width:400px;
}

</style>




<script type="text/javascript" language="javascript">

    jQuery( document ).ready( function() {
        jQuery(window).bind( 'load', field_type_options );
        jQuery( '#type' ).bind( 'change', field_type_options );


        // public field values initiation
        function field_type_options() {
            if ( jQuery( '#type' ).val() === 'radio' ||
                jQuery( '#type' ).val() === 'selectbox' ||
                jQuery( '#type' ).val() === 'multiselectbox' ||
                jQuery( '#type' ).val() === 'checkbox' ) {
                jQuery( '.ct-field-type-options' ).show();
                jQuery( '#field_title_box' ).show();
                jQuery( '#field_description_box' ).show();
                jQuery( '.ct-field-hiden-value' ).hide();
            }
            else if ( jQuery( '#type' ).val() === 'text' ||
                jQuery( '#type' ).val() === 'textarea' ) {
                jQuery( '#field_title_box' ).show();
                jQuery( '#field_description_box' ).show();
                jQuery( '.ct-field-type-options' ).hide();
                jQuery( '.ct-field-hiden-value' ).hide();
            } else if ( jQuery( '#type' ).val() === 'hidden' ) {
                jQuery( '.ct-field-type-options' ).hide();
                jQuery( '#field_title_box' ).hide();
                jQuery( '#field_description_box' ).hide();
                jQuery( '.ct-field-hiden-value' ).show();

            }
        }

        // custom fields remove options
        jQuery('#wpc_client_custom_filed_form').live('submit', function() {
            if ( jQuery( '#type' ).val() === 'hidden' ) {
                jQuery( '.ct-field-type-options' ).remove();
                jQuery( '#field_title_box' ).remove();
                jQuery( '#field_description_box' ).remove();
            }
        });


        // custom fields add options
        jQuery('.ct-field-add-option').click(function() {
            var count = parseInt(jQuery('input[name="track_number"]').val(), 10) + 1;

            jQuery('.ct-field-additional-options').append(function() {


                jQuery('input[name="track_number"]').val(count);

                return '<p><?php _e( 'Option', WPC_CLIENT_TEXT_DOMAIN ) ?> ' + count + ': ' +
                            '<input type="text" name="custom_field[options][' + count + ']"> ' +
                            '<input type="radio" value="' + count + '" name="custom_field[default_option]"> ' +
                            '<?php _e( 'Default Value', WPC_CLIENT_TEXT_DOMAIN ) ?> ' +
                            '<a href="javascript:;" class="ct-field-delete-option">[x]</a>' +
                        '</p>';
            });

            jQuery( 'input[name="custom_field[options][' + count + ']"]' ).focus();
        });


        // custom fields remove options
        jQuery('.ct-field-delete-option').live('click', function() {
            jQuery(this).parent().remove();
        });


    });

</script>

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
            <h2><?php echo $title_text ?></h2>
            <br>
            <div>
                <div id="message" class="updated fade" <?php echo ( empty( $error ) )? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>

                <form action="" method="post" name="wpc_client_custom_filed_form" id="wpc_client_custom_filed_form">
                    <?php if ( isset( $_GET['edit'] ) && '' != $_GET['edit'] ): ?>
                    <input type="hidden" name="custom_field[name]" value="<?php echo ( isset( $custom_field['name'] ) ) ? $custom_field['name'] : '' ?>" />
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="name"><?php _e( 'Field Name', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="required">(<?php _e( 'required', WPC_CLIENT_TEXT_DOMAIN ) ?>)</span></label>
                            </th>
                            <td>
                                <?php if ( isset( $_GET['edit'] ) && '' != $_GET['edit'] ): ?>
                                    <input type="text" disabled value="<?php echo ( isset( $custom_field['name'] ) ) ? $custom_field['name'] : '' ?>" />
                                    <br>
                                    <span class="description"><?php _e( "Can't be changed.", WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                <?php else: ?>
                                    wpc_<input type="text" name="custom_field[name]" id="name" style="width: 372px;" value="<?php echo ( isset( $custom_field['name'] ) ) ? $custom_field['name'] : '' ?>" />
                                    <br>
                                    <span class="description"><?php _e( 'The name used to identify the custom field. Should consist only of these characters "a-z" and the underscore symbol "_" <br> - (not displayed on the form).', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="field_title_box">
                            <th>
                                <label for="title"><?php _e( 'Field Title', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="custom_field[title]" id="title" value="<?php echo ( isset( $custom_field['title'] ) ) ? $custom_field['title'] : '' ?>" />
                                <br>
                                <span class="description"><?php _e( 'The title of the custom field (displayed on the form).', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </td>
                        </tr>
                        <tr id="field_description_box">
                            <th>
                                <label for="description"><?php _e( 'Field Description', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                            </th>
                            <td>
                               <textarea name="custom_field[description]" rows="3" cols="69" id="description" ><?php echo ( isset( $custom_field['description'] ) ) ? $custom_field['description'] : '' ?></textarea>
                               <br>
                               <span class="description"><?php _e( 'Description for the custom field (displayed on the form).', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="type"><?php _e( 'Field Type', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="required">(<?php _e( 'required', WPC_CLIENT_TEXT_DOMAIN) ?>)</span></label>
                            </th>
                            <td>
                                <select name="custom_field[type]" id="type">
                                    <option value="text" <?php echo ( isset( $custom_field['type'] ) && $custom_field['type'] == 'text' ) ? 'selected' : '' ?>><?php _e( 'Text Box', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    <option value="textarea" <?php echo ( isset( $custom_field['type'] ) && $custom_field['type'] == 'textarea' ) ? 'selected' : ''  ?>><?php _e( 'Multi-line Text Box', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    <option value="radio" <?php echo ( isset( $custom_field['type'] ) && $custom_field['type'] == 'radio' ) ? 'selected' : ''  ?>><?php _e( 'Radio Buttons', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    <option value="checkbox" <?php echo ( isset( $custom_field['type'] ) && $custom_field['type'] == 'checkbox' ) ? 'selected' : ''  ?>><?php _e( 'Checkboxes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    <option value="selectbox" <?php echo ( isset( $custom_field['type'] ) && $custom_field['type'] == 'selectbox' ) ? 'selected' : ''  ?>><?php _e( 'Select Box', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    <option value="multiselectbox" <?php echo ( isset( $custom_field['type'] ) && $custom_field['type'] == 'multiselectbox' ) ? 'selected' : ''  ?>><?php _e( 'Multi Select Box', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    <option value="hidden" <?php echo ( isset( $custom_field['type'] ) && $custom_field['type'] == 'hidden' ) ? 'selected' : ''  ?>><?php _e( 'Hidden Field', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                </select>
                                <br>
                                <span class="description"><?php _e( 'Select type of the custom field.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>

                                <div class="ct-field-type-options">
                                    <h4><?php _e( 'Fill in the options for this field', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h4>
                                    <p><?php _e( 'Order By', WPC_CLIENT_TEXT_DOMAIN ) ?>:
                                        <select name="custom_field[sort_order]">
                                            <option value="default" <?php echo ( isset( $custom_field['sort_order'] ) && 'default' == $custom_field['sort_order'] ) ? 'selected' : '' ?> ><?php _e( 'Order Entered', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                            <option value="asc" <?php echo ( isset( $custom_field['sort_order'] ) && 'asc' == $custom_field['sort_order'] ) ? 'selected' : '' ?> ><?php _e( 'Name - Ascending', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                            <option value="desc" <?php echo ( isset( $custom_field['sort_order'] ) && 'desc' == $custom_field['sort_order'] ) ? 'selected' : '' ?> ><?php _e( 'Name - Descending', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        </select>
                                    </p>

                                    <?php if ( isset( $custom_field['options'] ) && is_array( $custom_field['options'] ) ) { ?>
                                        <?php foreach ( $custom_field['options'] as $key => $field_option ) { ?>
                                            <p>
                                                <?php _e( 'Option', WPC_CLIENT_TEXT_DOMAIN ) ?> <?php echo( $key ) ?>:
                                                <input type="text" name="custom_field[options][<?php echo( $key ) ?>]" value="<?php echo( $field_option ) ?>" />
                                                <input type="radio" value="<?php echo( $key ) ?>" name="custom_field[default_option]" <?php echo ( isset( $custom_field['default_option'] ) && $custom_field['default_option'] == $key ) ? 'checked'  : '' ?> />
                                                <?php _e( 'Default Value', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                                <?php if ( $key != 1 ): ?>
                                                    <a href="javascript:;" class="ct-field-delete-option">[x]</a>
                                                <?php endif; ?>
                                            </p>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <p><?php _e( 'Option', WPC_CLIENT_TEXT_DOMAIN ) ?> 1:
                                            <input type="text" name="custom_field[options][1]" value="<?php echo ( isset( $custom_field['options'][1] ) ) ? $custom_field['options'][1] : '' ?>" />
                                            <input type="radio" value="1" name="custom_field[default_option]" <?php echo ( isset( $custom_field['default_option'] ) && $custom_field['default_option'] == '1' ) ? 'checked' : '' ?> />
                                            <?php _e( 'Default Value', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                        </p>
                                    <?php } ?>

                                    <div class="ct-field-additional-options"></div>
                                    <input type="hidden" value="<?php echo ( isset( $custom_field['options'] ) ) ? count( $custom_field['options'] ) : '1' ?>" name="track_number" />
                                    <p><a href="javascript:;" class="ct-field-add-option"><?php _e( 'Add another option', WPC_CLIENT_TEXT_DOMAIN ) ?></a></p>
                                </div>


                                <div class="ct-field-hiden-value">
                                    <p>
                                        <?php _e( 'Fill in the value for this field', WPC_CLIENT_TEXT_DOMAIN ) ?>:
                                        <input type="text" name="custom_field[options][1]" value="<?php echo( isset( $custom_field['options'][1] ) ? $custom_field['options'][1] : '' ) ?>" />
                                    </p>
                                </div>

                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="display"><?php _e( 'Display Field on Client Form', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                            </th>
                            <td>
                                <input type="checkbox" name="custom_field[display]" id="display" value="1" <?php echo ( !isset( $custom_field['display'] ) || '1' == $custom_field['display'] ) ? 'checked' : '' ?> />
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" class="button-primary" name="submit" value="<?php _e( 'Save Custom Field', WPC_CLIENT_TEXT_DOMAIN ) ?>">
                    </p>
                </form>
            </div>

        </div>
    </div>

</div>
