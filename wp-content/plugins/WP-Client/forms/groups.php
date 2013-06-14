<?php
global $wpdb;

$groups = $this->get_groups();

//Display status message
if ( isset( $_GET['updated'] ) ) {
    ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['dmsg'] ); ?></p></div><?php
}

?>

<script type="text/javascript">
    jQuery( document ).ready( function() {

        //Show/hide new Client Circle form
        jQuery( '#slide_new_form_panel' ).click( function() {
            jQuery( '#new_form_panel' ).slideToggle( 'slow' );
            jQuery( this ).toggleClass( 'active' );
            return false;
        });


        //Add Client Circle action
        jQuery( "#add_group" ).click( function() {

            jQuery( '#group_name' ).parent().parent().attr( 'class', '' );

            if ( "" == jQuery( "#group_name" ).val() ) {
                jQuery( '#group_name' ).parent().parent().attr( 'class', 'wpc_error' );
                return false;
            }

            jQuery( '#wpc_action' ).val( 'create_group' );
            jQuery( '#create_group' ).submit();
        });


        var group_name          = "";
        var group_auto_select   = "";


        jQuery.fn.editGroup = function ( id ) {
            if ( '<?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?>' == jQuery( this ).val() ) {
                group_name = jQuery( '#group_name_block_' + id ).html();
                group_name = group_name.replace(/(^\s+)|(\s+$)/g, "");

                jQuery( '#group_name_block_' + id ).html( '<input type="text" name="group_name" size="30" id="edit_group_name"  value="' + group_name + '" /><input type="hidden" name="group_id" value="' + id + '" />' );

                group_auto_select = jQuery( '#auto_select_block_' + id ).html();
                group_auto_select = group_auto_select.replace(/(^\s+)|(\s+$)/g, "");

                if ( 'Yes' == group_auto_select )
                    jQuery( '#auto_select_block_' + id ).html( '<input type="checkbox" name="auto_select" id="edit_auto_select" value="1" checked="checked" />' );
                else
                    jQuery( '#auto_select_block_' + id ).html( '<input type="checkbox" name="auto_select" id="edit_auto_select" value="1" />' );


                jQuery( '#edit_group input[type="button"]' ).attr( 'disabled', true );

                jQuery( this ).val( '<?php _e( 'Close', WPC_CLIENT_TEXT_DOMAIN ) ?>' );
                jQuery( this ).attr( 'disabled', false );

                jQuery( '#save_block_' + id ).html( '<input type="button" name="save_button" onClick="jQuery(this).saveGroup();" value="<?php _e( 'Save', WPC_CLIENT_TEXT_DOMAIN ) ?>" />' );

                return;
            }

            if ( '<?php _e( 'Close', WPC_CLIENT_TEXT_DOMAIN ) ?>' == jQuery( this ).val() ) {
                jQuery( '#group_name_block_' + id ).html( group_name );
                jQuery( '#auto_select_block_' + id ).html( group_auto_select );

                jQuery( this ).val( 'Edit' );
                jQuery( '#edit_group input[type="button"]' ).attr( 'disabled', false );

                 jQuery( '#save_block_' + id ).html( '' );

                return;
            }


        };


        jQuery.fn.saveGroup = function ( ) {

            jQuery( '#edit_group_name' ).parent().parent().attr( 'class', '' );

            if ( '' == jQuery( '#edit_group_name' ).val() ) {
                jQuery( '#edit_group_name' ).parent().parent().attr( 'class', 'wpc_error' );
                return false;
            }

            jQuery( '#wpc_action2' ).val( 'edit_group' );
            jQuery( '#edit_group' ).submit();
        };


        jQuery.fn.deleteGroup = function ( id ) {
            jQuery( '#wpc_action2' ).val( 'delete_group' );
            jQuery( '#group_id' ).val( id );
            jQuery( '#edit_group' ).submit();
        };

        // AJAX - assign clients to Client Circle
        jQuery.fn.getGroupClients = function ( group_id ) {
            jQuery( '#group_clients' ).html( '' );
            jQuery( '#select_all' ).parent().hide();
            jQuery( '#save_popup' ).hide();
            jQuery( '#assign_group_id' ).val( group_id );
            jQuery( '#assign_group_name' ).html( jQuery( '#group_name_block_' + group_id ).html() );
            jQuery( 'body' ).css( 'cursor', 'wait' );
            jQuery( '#opaco' ).fadeIn( 'slow' );
            jQuery( '#popup_block' ).fadeIn( 'slow' );

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                data: 'action=get_group_clients&group_id=' + group_id,
                success: function( html ){
                    jQuery( 'body' ).css( 'cursor', 'default' );
                    if ( 'false' == html ) {
                        jQuery( '#group_clients' ).html( '<p><?php _e( 'No Clients for assign.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>' );
                    } else {
                        jQuery( '#save_popup' ).show();
                        jQuery( '#select_all' ).parent().show();
                        jQuery( '#group_clients' ).html( html );
                    }
                }
             });

        };

        //Cancel Assign block
        jQuery( "#cancel_group_clients" ).click( function() {
            jQuery( '#popup_block' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
        });

        //Select/Un-select all clients
        jQuery( "#select_all" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery( '#group_clients input[type="checkbox"]' ).attr( 'checked', true );
            } else {
                jQuery( '#group_clients input[type="checkbox"]' ).attr( 'checked', false );
            }
        });



        //Display list of clients which have access to file
        jQuery( ".view_clients" ).mousemove( function( kmouse ) {
            jQuery( '#popup_view_block_' + jQuery( this ).attr( 'rel' ) ).css({left: 200, top:kmouse.pageY-70});
            jQuery( '#popup_view_block_' + jQuery( this ).attr( 'rel' ) ).fadeIn( 'fast' );
            jQuery( '#opaco2' ).css({opacity:0});
            jQuery( '#opaco2' ).show();
        });


        //Cancel list of clients which have access to file
        jQuery( "#opaco2" ).mousemove( function() {
            jQuery( '.popup_view_block' ).fadeOut( 'fast' );
            jQuery( '#opaco2' ).fadeOut( 'fast' );
        });




    });
</script>


<div class="wrap">

    <div class="wpc_logo"></div>
    <hr />

    <h2><?php _e( 'Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h2>

    <div id="slide_new_form_panel">
        <h3><?php _e( 'Create New Client Circle', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="arrow"></span></h3>
    </div>

    <div id="new_form_panel">
        <form method="post" action="" name="create_group" id="create_group" >
            <input type="hidden" name="wpc_action" id="wpc_action" value="" />

            <table class="form-table">
                <tr>
                    <td>
                        <?php _e( 'Client Circle Name', WPC_CLIENT_TEXT_DOMAIN ) ?>:<span class="required">*</span>
                        <input type="text" class="input" name="group_name" id="group_name" value="" size="30" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_select" id="auto_select" value="1" /> <?php _e( 'Auto-Select this Client Circle on the Add Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" name="assign_all" id="assign_all" value="1" /> <?php _e( 'Assign all existing Clients', WPC_CLIENT_TEXT_DOMAIN ) ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="button" name="add_group" id="add_group" value="<?php _e( 'Add Client Circle', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                    </td>
                </tr>
            </table>

        </form>
    </div>


    <h3><?php _e( 'List of Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h3>
    <form method="post" action="" name="edit_group" id="edit_group" >
        <input type="hidden" name="wpc_action" id="wpc_action2" value="" />
        <input type="hidden" name="group_id" id="group_id" value="" />
        <table width="700px" class="widefat post fixed" style="width:95%;">
            <thead>
                <tr>
                    <th><?php _e( 'Client Circle Na', WPC_CLIENT_TEXT_DOMAIN ) ?>me</th>
                    <th><?php _e( 'Auto-Select', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    <th><?php _e( 'Clients', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    <th width="300px"><?php _e( 'Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                </tr>
            </thead>
        <?php
        $i = 0;
        if ( $groups )
            foreach( $groups as $group ) {
                if ( $i % 2 == 0 )
                    echo "<tr class='alternate'>";
                else
                    echo "<tr class='' >";

                $i++;
        ?>
                <td style="vertical-align: middle;">
                    <span id="group_name_block_<?php echo $group['group_id'];?>">
                        <?php echo $group['group_name']; ?>
                    </span>
                </td>
                <td style="vertical-align: middle;">
                    <span id="auto_select_block_<?php echo $group['group_id'];?>">
                        <?php
                        if ( "1" == $group['auto_select'] )
                            echo 'Yes';
                        else
                            echo 'No';
                        ?>
                    </span>
                </td>
                <td style="vertical-align: middle;">
                <?php
                $clients_id = $this->get_group_clients_id( $group['group_id'] );

                if ( !is_array( $clients_id ) || 1 > count( $clients_id ) ): ?>
                    <span class="edit"><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                <?php else: ?>
                    <span class="edit"><a href="javascript:;" class="view_clients" rel="<?php echo $group['group_id'] ?>" title="view clients of '<?php echo $group['group_name'] ?>'" ><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                        <div class="popup_view_block" id="popup_view_block_<?php echo $group['group_id'] ?>">
                                <h4><?php _e( 'Those Clients have access to Client Circle', WPC_CLIENT_TEXT_DOMAIN ) ?>: <?php echo $group['group_name'] ?></h4>

                                <?php

                                    $i = 0;
                                    $n = ceil( count( $clients_id ) / 4 );

                                    $html = '';
                                    $html .= '<ul class="clients_list">';

                                    foreach ( $clients_id as $client_id ) {
                                        if ( 0 < $client_id ) {
                                            $client = get_userdata( $client_id );

                                            if ( $i%$n == 0 && 0 != $i )
                                                $html .= '</ul><ul class="clients_list">';

                                            $html .= '<li>' . $client->ID . ' - ' . $client->user_login . '</li>';

                                            $i++;
                                        }
                                    }

                                    echo $html;

                                ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td style="vertical-align: middle;">
                    <input type="button" id="edit_button_<?php echo $group['group_id'];?>" value="<?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?>" onclick="jQuery(this).editGroup( <?php echo $group['group_id'];?> );" />
                    <span id="save_block_<?php echo $group['group_id'];?>"></span>
                    <input type="button" id="assign_button_<?php echo $group['group_id'];?>" value="<?php _e( 'Assign Clients', WPC_CLIENT_TEXT_DOMAIN ) ?>" onclick="jQuery(this).getGroupClients( <?php echo $group['group_id'];?> );" />
                    <input type="button" value="<?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="group_delete" onclick="jQuery(this).deleteGroup( <?php echo $group['group_id'];?> );" />
                </td>
            </tr>
        <?php
            }
        ?>
        </table>
    </form>

        <div id="opaco"></div>
        <div id="opaco2"></div>

        <div id="popup_block">
            <form name="assign_clients" method="post" >
                <input type="hidden" name="wpc_action" value="save_group_clients" />
                <input type="hidden" name="group_id" id="assign_group_id" value="" />

                <h3><?php _e( 'Assign Clients to the Client Circle', WPC_CLIENT_TEXT_DOMAIN ) ?>: <span id="assign_group_name"></span></h3>

                <table>
                    <tr>
                        <td>
                            <label>
                                <input type="checkbox" id="select_all" value="all" />
                                <?php _e( 'Select all Clients.  ', WPC_CLIENT_TEXT_DOMAIN ) ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id="group_clients" >
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" name="save" value="<?php _e( 'Save', WPC_CLIENT_TEXT_DOMAIN ) ?>" id="save_popup" />
                            <input type="button" name="cancel" id="cancel_group_clients" value="<?php _e( 'Cancel', WPC_CLIENT_TEXT_DOMAIN ) ?>" />

                        </td>
                    </tr>
                </table>

            </form>
        </div>


</div><!--/wrap-->