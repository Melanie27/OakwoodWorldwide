<?php
global $wpdb;

$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpc_client_file_categories ORDER BY cat_order", "ARRAY_A" );

//Display status message
if ( isset( $_GET['updated'] ) ) {
    ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
}

?>


<script type="text/javascript">
    jQuery( document ).ready( function() {

        //Show/hide new form
        jQuery( '#slide_new_form_panel' ).click( function() {
            jQuery( '#new_form_panel' ).slideToggle( 'slow' );
            jQuery( this ).toggleClass( 'active' );
            return false;
        });


        var group_name          = "";
        var group_auto_select   = "";


        jQuery.fn.editGroup = function ( id ) {
            if ( '<?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?>' == jQuery( this ).val() ) {
                group_name = jQuery( '#cat_name_block_' + id ).html();
                group_name = group_name.replace(/(^\s+)|(\s+$)/g, "");

                jQuery( '#cat_name_block_' + id ).html( '<input type="text" name="cat_name" size="30" id="edit_cat_name"  value="' + group_name + '" /><input type="hidden" name="cat_id" value="' + id + '" />' );

                jQuery( '#edit_cat input[type="button"]' ).attr( 'disabled', true );

                jQuery( this ).val( '<?php _e( 'Close', WPC_CLIENT_TEXT_DOMAIN ) ?>' );
                jQuery( this ).attr( 'disabled', false );

                jQuery( '#save_block_' + id ).html( '<input type="button" name="save_button" onClick="jQuery(this).saveGroup();" value="<?php _e( 'Save', WPC_CLIENT_TEXT_DOMAIN ) ?>" />' );

                return;
            }

            if ( '<?php _e( 'Close', WPC_CLIENT_TEXT_DOMAIN ) ?>' == jQuery( this ).val() ) {
                jQuery( '#cat_name_block_' + id ).html( group_name );

                jQuery( this ).val( '<?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?>' );
                jQuery( '#edit_cat input[type="button"]' ).attr( 'disabled', false );

                 jQuery( '#save_block_' + id ).html( '' );

                return;
            }


        };


        jQuery.fn.saveGroup = function ( ) {

            jQuery( '#edit_cat_name' ).parent().parent().attr( 'class', '' );

            if ( '' == jQuery( '#edit_cat_name' ).val() ) {
                jQuery( '#edit_cat_name' ).parent().parent().attr( 'class', 'wpc_error' );
                return false;
            }

            jQuery( '#wpc_action2' ).val( 'edit_file_cat' );
            jQuery( '#edit_cat' ).submit();
        };

        //block for delete cat
        jQuery.fn.deleteCat = function ( id, act ) {
            if ( 'show' == act ) {
                jQuery( '#cat_reassign_block_' + id ).slideToggle( 'slow' );
            } else if( 'reassign' == act ) {
                jQuery( '#wpc_action2' ).val( 'delete_file_category' );
                jQuery( '#cat_id' ).val( id );
                jQuery( '#reassign_cat_id' ).val( jQuery( '#cat_reassign_block_' + id + ' select' ).val() );
                jQuery( '#edit_cat' ).submit();
            } else if( 'delete' == act ) {
                jQuery( '#wpc_action2' ).val( 'delete_file_category' );
                jQuery( '#cat_id' ).val( id );
                jQuery( '#edit_cat' ).submit();
            }
        };



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
                data: 'action=change_cat_order&new_order=' + new_order,
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



        //Reassign files to another cat
        jQuery( '#reassign_files' ).click( function() {
            if ( jQuery( '#old_cat_id' ).val() == jQuery( '#new_cat_id' ).val() ) {
                jQuery( '#old_cat_id' ).parent().parent().attr( 'class', 'wpc_error' );
                return false;
            }
            jQuery( '#wpc_action3' ).val( 'reassign_files_from_category' );
            jQuery( '#reassign_files_cat' ).submit();
            return false;
        });

    });
</script>



<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <li id="news"><a href="admin.php?page=wpclients_files" ><?php _e( 'Files', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="tutorials" class="active"><a href="admin.php?page=wpclients_files&tab=cat" ><?php _e( 'File Categories', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
        </ul>
        <span class="clear"></span>

        <div class="content23 news">

            <div class="icon32" id="icon-upload"><br></div>

            <h2><?php _e( 'File Categories', WPC_CLIENT_TEXT_DOMAIN ) ?><a class="add-new-h2" id="slide_new_form_panel" href="javascript:;"><?php _e( 'Add New', WPC_CLIENT_TEXT_DOMAIN ) ?><span class="arrow"></span></a></h2>

            <div id="new_form_panel">
                    <table class="">
                        <tr>
                            <td>
                                <h3><?php _e( 'New Category', WPC_CLIENT_TEXT_DOMAIN ) ?></h3>

                                <form method="post" name="new_cat" id="new_cat" >
                                    <input type="hidden" name="wpc_action" value="create_file_cat" />
                                    <table class="">
                                        <tr>
                                            <td>
                                                <label for="cat_name_new"><?php _e( 'New Category', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                            </td>
                                            <td>
                                                <input type="text" name="cat_name_new" id="cat_name_new" />
                                            </td>
                                        </tr>
                                    </table>
                                    <input type="submit" class='button-primary' value="<?php _e( 'Create Category', WPC_CLIENT_TEXT_DOMAIN ) ?>" name="create_cat" />
                                </form>
                            </td>
                        </tr>
                    </table>
            </div>

            <form method="post" action="" name="edit_cat" id="edit_cat" >
                <input type="hidden" name="wpc_action" id="wpc_action2" value="" />
                <input type="hidden" name="cat_id" id="cat_id" value="" />
                <input type="hidden" name="reassign_cat_id" id="reassign_cat_id" value="" />
                <table width="700px" class="widefat post " style="width:95%;" id=sortable>
                    <thead>
                        <tr>
                            <th><?php _e( 'Order', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Category Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Files', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th width="300px"><?php _e( 'Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 0;
                    if ( $categories ) {
                        foreach( $categories as $category ) {
                            $i++;
                            $id = $category['cat_id'] ;

                    ?>
                        <tr id="cat<?php echo $id ?>" >
                            <td class="sorting_button">
                                <span class="order_num"><?php echo $i ?> </span>
                                <span class="order_img"></span>
                            </td>
                            <td style="vertical-align: middle;">
                                <span id="cat_name_block_<?php echo $category['cat_id'];?>">
                                    <?php echo $category['cat_name']; ?>
                                </span>
                            </td>
                            <td style="vertical-align: middle;">
                                <?php
                                $files_count = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$wpdb->prefix}wpc_client_files WHERE cat_id=%d ", $category['cat_id'] ) );
                                echo $files_count;
                                ?>
                            </td>
                            <td style="vertical-align: middle;">
                            <?php if ( 'General' != $category['cat_name'] ):?>
                                <input type="button" id="edit_button_<?php echo $category['cat_id'];?>" value="<?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?>" onclick="jQuery(this).editGroup( <?php echo $category['cat_id'];?> );" />
                                <span id="save_block_<?php echo $category['cat_id'];?>"></span>
                                <input type="button" value="<?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="group_delete" onclick="jQuery(this).deleteCat( <?php echo $category['cat_id'];?> , '<?php echo ( 0 < $files_count ) ? 'show' : 'delete' ?>' );" />
                                <?php if ( 0 < $files_count ): ?>
                                <div class="cat_reassign_block" id="cat_reassign_block_<?php echo $category['cat_id'];?>">
                                    <span><?php _e( 'Category have files. What do with files', WPC_CLIENT_TEXT_DOMAIN ) ?>:</span>
                                    <br>
                                    <select name="cat_reassign">
                                        <?php
                                        foreach( $categories as $cat) {
                                            if ( $category['cat_id'] != $cat['cat_id'] )
                                                echo '<option value="' . $cat['cat_id'] . '">' . $cat['cat_name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <input type="button" value="<?php _e( 'Reassign Files', WPC_CLIENT_TEXT_DOMAIN ) ?>" onclick="jQuery(this).deleteCat( <?php echo $category['cat_id'];?>, 'reassign' );" />
                                    <br>or<br>
                                    <input type="button" value="<?php _e( 'Delete Files', WPC_CLIENT_TEXT_DOMAIN ) ?>" onclick="jQuery(this).deleteCat( <?php echo $category['cat_id'];?>, 'delete' );" />
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td>
                                <p><?php _e( 'No File Categories', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
                <p>
                    <span class="description" ><img src="<?php echo $this->plugin_url . 'images/sorting_button.png' ?>" style="vertical-align: middle;" /> - <?php _e( 'Drag&Drop for change category order.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                </p>

            </form>

            <h4><?php _e( 'Reassign Files Category', WPC_CLIENT_TEXT_DOMAIN ) ?></h4>
            <form method="post" name="reassign_files_cat" id="reassign_files_cat" >
                <input type="hidden" name="wpc_action" id="wpc_action3" value="" />
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <?php _e( 'Category From', WPC_CLIENT_TEXT_DOMAIN ) ?>:
                            <select name="old_cat_id" id="old_cat_id">
                                <?php
                                foreach( $categories as $cat) {
                                        echo '<option value="' . $cat['cat_id'] . '">' . $cat['cat_name'] . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <?php _e( 'Category To', WPC_CLIENT_TEXT_DOMAIN ) ?>:
                            <select name="new_cat_id" id="new_cat_id">
                                <?php
                                foreach( $categories as $cat) {
                                        echo '<option value="' . $cat['cat_id'] . '">' . $cat['cat_name'] . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                        <td>
                            <input type="button" name="" value="<?php _e( 'Reassign', WPC_CLIENT_TEXT_DOMAIN ) ?>" id="reassign_files" />
                        </td>
                    </tr>
                </table>
            </form>

        </div>