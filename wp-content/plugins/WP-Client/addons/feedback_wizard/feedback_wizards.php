<?php
global $wpdb, $wpc_client;

// delete wizard
if ( isset( $_REQUEST['wpc_action'] ) && 'delete_wizard' == $_REQUEST['wpc_action'] && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpc_wizard_form' ) && isset( $_REQUEST['wizard_id'] ) ) {

    $wizard_ids = ( is_array( $_REQUEST['wizard_id'] ) ) ? $_REQUEST['wizard_id'] : (array) $_REQUEST['wizard_id'];
    foreach ( $wizard_ids as $wizard_id) {
        //delete wizard_id
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}wpc_client_feedback_wizards WHERE wizard_id = %d", $wizard_id ) );

        //delete items from wizard
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}wpc_client_feedback_wizard_items WHERE wizard_id = %d", $wizard_id ) );
    }

    do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_feedback_wizard&tab=wizards&msg=d' );
    exit;
}

//send emails to clients
if ( isset( $_REQUEST['wpc_action'] ) && 'send_wizard' == $_REQUEST['wpc_action'] && wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpc_wizard_form' ) && isset( $_REQUEST['wizard_id'] ) ) {
    $wizard_id = ( isset( $_REQUEST['wizard_id'] ) ) ? $_REQUEST['wizard_id'] : 0;
    if ( 0 < $wizard_id ) {
        $wizard_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_wizards WHERE wizard_id = %d", $wizard_id ), ARRAY_A );

        $send_client_ids = array();
        //get clients id
        if ( '' != $wizard_data['clients_id'] ) {
            $send_client_ids = explode( ',', str_replace( '#', '', $wizard_data['clients_id'] ) );
        }

        //get clients id from Client Circles
        if ( '' != $wizard_data['groups_id'] ) {
            $send_group_ids = explode( ',', str_replace( '#', '', $wizard_data['groups_id'] ) );
            if ( is_array( $send_group_ids ) )
                foreach( $send_group_ids as $group_id )
                    $send_client_ids = array_merge( $send_client_ids, $wpc_client->get_group_clients_id( $group_id ) );

            $send_client_ids = array_unique( $send_client_ids );
        }


        //send email
        if ( is_array( $send_client_ids ) && 0 < count( $send_client_ids ) ) {

            $wpc_fbw_templates = get_option( 'wpc_fbw_templates' );

            $sender_name    = get_option("sender_name");
            $sender_email   = get_option("sender_email");
            $email_subject  = $wpc_fbw_templates['emails']['wizard_notify']['subject'];
            $email_body     = stripslashes( $wpc_fbw_templates['emails']['wizard_notify']['body'] );


            $email_subject  = str_replace( '{wizard_name}', $wizard_data['name'], $email_subject );

            $email_body = str_replace('{wizard_url}', wpc_client_get_hub_link() . 'feedback-wizard/' . $wizard_data['wizard_id'] . '/', $email_body);


            $headers = "From: " . get_option("sender_name") . " <" . get_option("sender_email") . "> \r\n";
            $headers .= "Reply-To: " . get_option("admin_email") . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $send1 = 0;
            $send2 = 0;
            foreach( $send_client_ids as $send_client_id ) {
                if ( '' != $send_client_id ) {
                    //there are any assigned clients
                    $send1 = 1;

                    //check if client not left feedback for this version
                    $sql = "SELECT result_id FROM {$wpdb->prefix}wpc_client_feedback_results WHERE wizard_id = %d AND client_id = %d AND wizard_version = '%s' ";
                    $result_id = $wpdb->get_var( $wpdb->prepare( $sql, $wizard_data['wizard_id'], $send_client_id, $wizard_data['version'] ) );
                    if ( empty( $result_id ) || 0 > $result_id  ) {
                        //there are any clients for leave feedback
                        $send2 = 1;

                        $email_body = str_replace('{user_name}', get_userdata( $send_client_id )->get( 'user_login' ), $email_body);

                        $client_email = get_userdata( $send_client_id )->get( 'user_email' );
                        //send email to client
                        wp_mail( $client_email, $email_subject, $email_body, $headers );
                    }
                }
            }

            if ( 0 == $send1 && 0 == $send2 ) {
                //no any clients
                $msg = 'ns1';
            } else if ( 1 == $send1 && 0 == $send2 ) {
                //all left feedback
                $msg = 'ns2';
            } else {
                //sent email for clients
                $msg = 's';
            }

        } else {
            //no any clients
            $msg = 'ns1';
        }

        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_feedback_wizard&tab=wizards&msg=' . $msg );
        exit;
    }

    //do nothing
    do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_feedback_wizard&tab=wizards' );
    exit;

}


$wizards = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}wpc_client_feedback_wizards", ARRAY_A );
$wpnonce = wp_create_nonce( 'wpc_wizard_form' );

$msg = '';
if ( isset( $_GET['msg'] ) ) {
  $msg = $_GET['msg'];
}


//Set date format
if ( get_option( 'date_format' ) ) {
    $date_format = get_option( 'date_format' );
} else {
    $date_format = 'm/d/Y';
}
if ( get_option( 'time_format' ) ) {
    $time_format = get_option( 'time_format' );
} else {
    $time_format = 'g:i:s A';
}




?>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>
    <?php
    if ( '' != $msg ) {
    ?>
        <div id="message" class="updated fade">
            <p>
            <?php
                switch( $msg ) {
                    case 'a':
                        echo  __( 'Wizard <strong>Created</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'u':
                        echo __( 'Wizard <strong>Updated</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'd':
                        echo __( 'Wizard(s) <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'ac':
                        echo __( 'Clients are assigned', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'ag':
                        echo __( 'Client Circles are assigned!', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'ae':
                        echo __( 'Some error with assigning permission for wizard.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 's':
                        echo __( 'Email sent to Client(s)', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'ns1':
                        echo __( 'Email are not sent: no assigned Clients.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'ns2':
                        echo __( 'Email are not sent: Clients already left feedback for this wizard version.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                }
            ?>
            </p>
        </div>
    <?php
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
                <a href="admin.php?page=wpclients_feedback_wizard&tab=create_wizard " class="add-new-h2"><?php _e( 'Create New Wizard', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
            </div>

            <hr />

            <form method="post" name="wizards_form" id="wizards_form">
                <input type="hidden" value="<?php echo $wpnonce ?>" name="_wpnonce" id="_wpnonce" />
                <input type="hidden" value="" name="wpc_action" id="wpc_action" />

                <table cellspacing="0" class="wp-list-table widefat media">
                    <thead>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col" width="330">
                                <span><?php _e( 'Wizard Name', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Version', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Items', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Clients', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column sortable desc" id="comments" scope="col">
                                <span><?php _e( 'Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-date sortable asc" id="date" scope="col">
                                <span><?php _e( 'Date', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </thead>

                    <tfoot>
                        <tr>
                            <th style="" class="manage-column column-cb check-column" id="cb" scope="col">
                                <input type="checkbox">
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Wizard Name', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                                <span><?php _e( 'Version', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Items', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column  sortable desc" id="" scope="col">
                                <span><?php _e( 'Clients', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column sortable desc" id="comments" scope="col">
                                <span><?php _e( 'Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                            <th style="" class="manage-column column-date sortable asc" id="date" scope="col">
                                <span><?php _e( 'Date', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </th>
                        </tr>
                    </tfoot>
                    <?php
                    if ( is_array( $wizards ) && 0 < count( $wizards ) ):
                        foreach( $wizards as $wizard ):
                    ?>
                       <tr valign="top" id="post-11" class="alternate author-other status-inherit">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="wizard_id[]" value="<?php echo $wizard['wizard_id'] ?>">
                            </th>
                            <td class="title column-title">
                                <input type="hidden" id="assign_name_block_<?php echo $wizard['wizard_id'] ?>" value="<?php echo $wizard['name'] ?>" />
                                <strong>
                                    <a href="admin.php?page=wpclients_feedback_wizard&tab=edit_wizard&wizard_id= <?php echo '' . $wizard['wizard_id'] ?>" title="edit '<?php echo $wizard['name'] ?>'"><?php echo $wizard['name'] ?></a>
                                </strong>
                                <div class="row-actions">
                                        <span class="edit"><a href="admin.php?page=wpclients_feedback_wizard&tab=edit_wizard&wizard_id= <?php echo '' . $wizard['wizard_id'] ?>" title="edit '<?php echo $wizard['name'] ?>'" ><?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                        <span class="delete"><a class="submitdelete" onclick="return showNotice.warn();" href="admin.php?page=wpclients_feedback_wizard&tab=wizards&wpc_action=delete_wizard&wizard_id=<?php echo $wizard['wizard_id']  ?>&_wpnonce=<?php echo $wpnonce ?>"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                        <span class="send"><a class="submitsend" href="admin.php?page=wpclients_feedback_wizard&tab=wizards&wpc_action=send_wizard&wizard_id=<?php echo $wizard['wizard_id']  ?>&_wpnonce=<?php echo $wpnonce ?>"><?php _e( 'Send Email to Client(s)', WPC_CLIENT_TEXT_DOMAIN ) ?></a> </span>
                                </div>
                            </td>
                            <td class="title column-title">
                                <?php echo ( isset( $wizard['version'] ) && '' != $wizard['version'] ) ? $wizard['version'] : '1.0.0' ?>
                            </td>
                            <td class="author column-author">
                            <?php echo $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(item_id) FROM {$wpdb->base_prefix}wpc_client_feedback_wizard_items WHERE wizard_id = %d", $wizard['wizard_id'] ) ); ?>
                            </td>
                            <td class="author column-author">
                                <span class="edit"><a href="javascript:;" onclick="jQuery(this).getWizardClients( <?php echo $wizard['wizard_id'];?> );" title="assign clients to '<?php echo $wizard['name'] ?>'" ><?php _e( 'Assign', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span> |
                            <?php  if ( '' == $wizard['clients_id'] ): ?>
                                <span class="edit"><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            <?php else: ?>
                                <span class="edit"><a href="javascript:;" class="view_clients" rel="<?php echo $wizard['wizard_id'] ?>" title="view clients of '<?php echo $wizard['name'] ?>'" ><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                    <div class="popup_view_block" id="popup_view_block_<?php echo $wizard['wizard_id'] ?>">
                                            <h4><?php _e( 'Those Clients have access to wizard', WPC_CLIENT_TEXT_DOMAIN ) ?>: <?php echo $wizard['name'] ?></h4>

                                            <?php

                                                if ( current_user_can( 'wpc_manager' ) && !current_user_can( 'administrator' ) ) {
                                                    $args = array(
                                                        'role'          => 'wpc_client',
                                                        'orderby'       => 'user_login',
                                                        'order'         => 'ASC',
                                                        'meta_key'      => 'admin_manager',
                                                        'meta_value'    => get_current_user_id(),
                                                        'fields'        => 'ID',
                                                    );
                                                    $manager_clients = get_users( $args );
                                                }

                                                $clients_id = explode( ',', str_replace( '#', '', $wizard['clients_id'] ) );

                                                $i = 0;
                                                $n = ceil( count( $clients_id ) / 4 );

                                                $html = '';
                                                $html .= '<ul class="clients_list">';

                                                foreach ( $clients_id as $client_id ) {
                                                    if ( 0 < $client_id ) {

                                                        //if manager - skip not manager's clients
                                                        if ( isset( $manager_clients ) && !in_array( $client_id, $manager_clients ) )
                                                            continue;

                                                        $client = get_userdata( $client_id );

                                                        if ( !is_object( $client ) )
                                                            continue;

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
                            <td class="author column-author">
                                <span class="edit"><a href="javascript:;" onclick="jQuery(this).getWizardGroups( <?php echo $wizard['wizard_id'];?> );" title="assign Client Circles to '<?php echo $wizard['name'] ?>'" ><?php _e( 'Assign', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span> |

                            <?php  if ( '' == $wizard['groups_id'] ): ?>
                                <span class="edit"><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            <?php else: ?>
                                <span class="edit"><a href="javascript:;" class="view_groups" rel="<?php echo $wizard['wizard_id'] ?>" title="view Client Circles of '<?php echo $wizard['name'] ?>'" ><?php _e( 'View', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                                    <div class="popup_view_block" id="popup_group_block_<?php echo $wizard['wizard_id'] ?>">
                                            <h4><?php _e( 'Those Client Circles have access to wizard', WPC_CLIENT_TEXT_DOMAIN ) ?>: <?php echo $wizard['name'] ?></h4>

                                            <?php

                                                $groups_id = explode( ',', str_replace( '#', '', $wizard['groups_id'] ) );

                                                $i = 0;
                                                $n = ceil( count( $groups_id ) / 4 );

                                                $html = '';
                                                $html .= '<ul class="clients_list">';

                                                foreach ( $groups_id as $group_id ) {
                                                    if ( 0 < $group_id ) {
                                                        $group = $wpc_client->get_group( $group_id );

                                                        if ( $i%$n == 0 && 0 != $i )
                                                            $html .= '</ul><ul class="clients_list">';

                                                        $html .= '<li>' . $group['group_name'] . '</li>';

                                                        $i++;
                                                    }
                                                }

                                                echo $html;

                                            ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="date column-date">
                                <?php echo $wpc_client->date_timezone( $date_format, $wizard['time'] ) ?>
                                <br>
                                <?php echo $wpc_client->date_timezone( $time_format, $wizard['time'] ) ?>
                            </td>
                        </tr>

                    <?php
                        endforeach;
                    endif;
                    ?>
                </table>

                <div class="tablenav bottom">

                    <div class="alignleft actions">
                        <select name="action" id="action">
                            <option selected="selected" value="-1"><?php _e( 'Bulk Actions', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <option value="delete_wizards"><?php _e( 'Delete Permanently', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
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


            <div id="opaco"></div>
            <div id="opaco2"></div>

            <div id="popup_block">
                <form name="assign_clients" method="post" >
                    <input type="hidden" name="wpc_action" value="save_wizard_access" />
                    <input type="hidden" name="access_field" id="access_field" value="" />
                    <input type="hidden" name="assign_id" id="assign_id" value="" />

                    <h3 id="assign_name"></h3>

                    <table>
                        <tr>
                            <td>
                                <label>
                                    <input type="checkbox" id="select_all" value="all" />
                                    <?php _e( 'Select All.', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div id="popup_content" >
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="submit" name="save" value="<?php _e( 'Save', WPC_CLIENT_TEXT_DOMAIN ) ?>" id="save_popup" />
                                <input type="button" name="cancel" id="cancel_popup" value="<?php _e( 'Cancel', WPC_CLIENT_TEXT_DOMAIN ) ?>" />

                            </td>
                        </tr>
                    </table>

                </form>
            </div>



        </div>
    </div>


</div>

<script type="text/javascript">

    jQuery( document ).ready( function() {

        //delete wizard from Bulk Actions
        jQuery( '#doaction' ).click( function() {
            if ( 'delete_wizards' == jQuery( '#action' ).val() ) {
                jQuery( '#wpc_action' ).val( 'delete_wizard' );
                jQuery( '#wizards_form' ).submit();
            }
            return false;
        });


        //Cancel Assign block
        jQuery( "#cancel_popup2" ).click( function() {
            jQuery( '#popup_block2' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
            jQuery( '#popup_block2 input[type="checkbox"]' ).attr( 'checked', false );
        });

        //Ok Assign block
        jQuery( "#ok_popup2" ).click( function() {
            jQuery( '#popup_block2' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
        });

        //Select/Un-select all clients
        jQuery( "#select_all2" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery( '#popup_block2 input[type="checkbox"]' ).attr( 'checked', true );
            } else {
                jQuery( '#popup_block2 input[type="checkbox"]' ).attr( 'checked', false );
            }
        });

        //Cancel Assign block
        jQuery( "#cancel_popup3" ).click( function() {
            jQuery( '#popup_block3' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
            jQuery( '#popup_block3 input[type="checkbox"]' ).attr( 'checked', false );
        });

        //Ok Assign block
        jQuery( "#ok_popup3" ).click( function() {
            jQuery( '#popup_block3' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
        });

        //Select/Un-select all Client Circles
        jQuery( "#select_all3" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery( '#popup_block3 input[type="checkbox"]' ).attr( 'checked', true );
            } else {
                jQuery( '#popup_block3 input[type="checkbox"]' ).attr( 'checked', false );
            }
        });


        // AJAX - assign clients to wizard
        jQuery.fn.getWizardClients = function ( wizard_id ) {
            jQuery( '#popup_content' ).html( '' );
            jQuery( '#access_field' ).val( 'clients_id' );
            jQuery( '#select_all' ).parent().hide();
            jQuery( '#save_popup' ).hide();
            jQuery( '#assign_id' ).val( wizard_id );
            jQuery( '#assign_name' ).html( '<?php _e( 'Assign Clients to the wizard', WPC_CLIENT_TEXT_DOMAIN ) ?>: ' + jQuery( '#assign_name_block_' + wizard_id ).val() );
            jQuery( 'body' ).css( 'cursor', 'wait' );
            jQuery( '#opaco' ).css( { opacity: 0.5 } );
            jQuery( '#opaco' ).fadeIn( 'slow' );
            jQuery( '#popup_block' ).fadeIn( 'slow' );

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                data: 'action=get_wizard_clients&wizard_id=' + wizard_id,
                success: function( html ){
                    jQuery( 'body' ).css( 'cursor', 'default' );
                    if ( 'false' == html ) {
                        jQuery( '#popup_content' ).html( '<p><?php _e( 'No Clients for assign.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>' );
                    } else {
                        jQuery( '#save_popup' ).show();
                        jQuery( '#select_all' ).parent().show();
                        jQuery( '#popup_content' ).html( html );
                    }
                }
             });
        };


        // AJAX - assign Client Circles to wizard
        jQuery.fn.getWizardGroups = function ( wizard_id ) {
            jQuery( '#popup_content' ).html( '' );
            jQuery( '#access_field' ).val( 'groups_id' );
            jQuery( '#select_all' ).parent().hide();
            jQuery( '#save_popup' ).hide();
            jQuery( '#assign_id' ).val( wizard_id );
            jQuery( '#assign_name' ).html( '<?php _e( 'Assign Client Circles to the wizard', WPC_CLIENT_TEXT_DOMAIN ) ?>: ' + jQuery( '#assign_name_block_' + wizard_id ).val() );
            jQuery( 'body' ).css( 'cursor', 'wait' );
            jQuery( '#opaco' ).fadeIn( 'slow' );
            jQuery( '#popup_block' ).fadeIn( 'slow' );

            jQuery.ajax({
                type: 'POST',
                url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                data: 'action=get_wizard_groups&wizard_id=' + wizard_id,
                success: function( html ){
                    jQuery( 'body' ).css( 'cursor', 'default' );
                    if ( 'false' == html ) {
                        jQuery( '#popup_content' ).html( '<p><?php _e( 'No Client Circles for assign.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>' );
                    } else {
                        jQuery( '#save_popup' ).show();
                        jQuery( '#select_all' ).parent().show();
                        jQuery( '#popup_content' ).html( html );
                    }
                }
             });
        };





        //Cancel Assign block
        jQuery( "#cancel_popup" ).click( function() {
            jQuery( '#popup_block' ).fadeOut( 'fast' );
            jQuery( '#opaco' ).fadeOut( 'fast' );
        });

        //Select/Un-select all clients
        jQuery( "#select_all" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery( '#popup_content input[type="checkbox"]' ).attr( 'checked', true );
            } else {
                jQuery( '#popup_content input[type="checkbox"]' ).attr( 'checked', false );
            }
        });



        //Display list of clients which have access to wizard
        jQuery( ".view_clients" ).mousemove( function( kmouse ) {
            jQuery( '#popup_view_block_' + jQuery( this ).attr( 'rel' ) ).css({left: 200, top:kmouse.pageY-70});
            jQuery( '#popup_view_block_' + jQuery( this ).attr( 'rel' ) ).fadeIn( 'fast' );
            jQuery( '#opaco2' ).css({opacity:0});
            jQuery( '#opaco2' ).show();

        });


        //Cancel list of clients which have access to wizard
        jQuery( "#opaco2" ).mousemove( function() {
            jQuery( '.popup_view_block' ).fadeOut( 'fast' );
            jQuery( '#opaco2' ).fadeOut( 'fast' );
        });

        //Display list of clients which have access to wizard
        jQuery( ".view_groups" ).mousemove( function( kmouse ) {
            jQuery( '#popup_group_block_' + jQuery( this ).attr( 'rel' ) ).css({left: 200, top:kmouse.pageY-70});
            jQuery( '#popup_group_block_' + jQuery( this ).attr( 'rel' ) ).fadeIn( 'fast' );
            jQuery( '#opaco2' ).css({opacity:0});
            jQuery( '#opaco2' ).show();

        });


        //Cancel list of clients which have access to wizard
        jQuery( "#opaco2" ).mousemove( function() {
            jQuery( '.popup_view_block' ).fadeOut( 'fast' );
            jQuery( '#opaco2' ).fadeOut( 'fast' );
        });



    });
</script>
