<?php
global $wpdb;

$filter = '';
$where  = '';
$target = '';

if ( isset( $_GET['filter'] ) ) {
    $filter = $_GET['filter'];

    if ( is_numeric( $filter ) && 0 < $filter ) {
        $where = "WHERE sent_from='" . $filter . "' OR sent_to='" . $filter . "'";
    }

    $target = '&filter=' . $filter;
}


//mark read
if ( isset( $_GET['read'] ) && 0 < $_GET['read'] ) {
    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpc_client_comments SET new_flag='0' WHERE id=%d ", $_GET['read'] ) );

    //redirect
    do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_messages&msg=r' );
    exit;
}


//delete message
if ( isset( $_GET['delete'] ) && 0 < $_GET['delete'] ) {
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpc_client_comments WHERE id=%d ", $_GET['delete'] ) );

    //redirect
    if ( isset( $_GET['from_id'] ) && isset( $_GET['to_id'] ) )
        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_messages&user_id=' . $_GET['user_id'] . '&from_id=' . $_GET['from_id'] . '&to_id=' . $_GET['to_id'] . '&msg=d' );
    else
        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_messages&msg=d' );

    exit;
}


//add message
if ( isset( $_POST['comment'] ) && '' != $_POST['comment'] ) {

    $sent_from = get_current_user_id();

    if ( isset( $_GET['user_id'] ) )
        $user_id = $_GET['user_id'];

    if ( isset( $_POST['from_messages_page'] ) && 1 == $_POST['from_messages_page'] ) {
        $sent_to = $_POST['sent_client_id'];
        $user_id = $_POST['sent_client_id'];
    } elseif ( $_GET['from_id'] == $sent_from ) {
        $sent_to = $_GET['to_id'];
    } else {
        $sent_to = $_GET['from_id'];
    }

    $wpdb->query( $wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}wpc_client_comments SET user_id = %d, page_id = 0, time=%d, comment='%s', sent_from=%d, sent_to=%d"
        , $user_id
        , time()
        , $_POST['comment']
        , $sent_from
        , $sent_to
    ) );


    if ( get_option( 'wpc_notify_message2' ) == 'yes' ) {
        //send notify to client
        $sender_email   = get_option( 'sender_email' );
        $send_to_email  = get_userdata( $sent_to )->get( 'user_email' );
        $username       = ( 0 != $sent_from ) ? get_userdata( $sent_from )->get( 'user_login' ) : 'Administrator';

        //get email template
        $wpc_templates = get_option( 'wpc_templates' );

        $subject = str_replace('{user_name}',  $username, $wpc_templates['emails']['notify_client_about_message']['subject'] );
        $subject = str_replace('{site_title}',   get_bloginfo('name'), $subject );

        $message = stripslashes( $wpc_templates['emails']['notify_client_about_message']['body'] );
        $message = str_replace('{user_name}',  $username, $message );
        $message = str_replace('{login_url}',  wp_login_url(), $message );
        $message = str_replace('{message}',  nl2br( htmlspecialchars( stripslashes( stripslashes( $_POST['comment'] ) ) ) ), $message );
        $message = str_replace('{site_title}', get_bloginfo('name'), $message );

        $headers = "From: " . get_option("sender_name") . " <" . get_option("sender_email") . "> \r\n";
        $headers .= "Reply-To: " . get_option("admin_email") . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        wp_mail( $send_to_email, $subject, $message, $headers );
    }

    //redirect
    if ( isset( $_GET['from_id'] ) && isset( $_GET['to_id'] ) )
        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_messages&user_id=' . $user_id . '&from_id=' . $_GET['from_id'] . '&to_id=' . $_GET['to_id'] . '&msg=s' );
    else
        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_messages&msg=s' );

    exit;
}



//default mode
$mode = 'all_messages';

$not_approved_clients = get_users( array( 'role' => 'wpc_client', 'meta_key' => 'to_approve', 'fields' => 'ID', ) );


//get chain of messages
if ( isset( $_GET['from_id'] ) && isset( $_GET['to_id'] ) ) {
    $mode = 'chain_messages';

    $messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpc_client_comments WHERE user_id=%d ORDER BY time ", $_GET['user_id'] ), "ARRAY_A" );

    //mark all message in chain as read
    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpc_client_comments SET new_flag='0' WHERE new_flag='1' AND user_id=%d ", $_GET['user_id'] ) );

    $args = array(
            'role'          => 'wpc_client_staff',
            'orderby'       => 'user_login',
            'order'         => 'ASC',
            'meta_key'      => 'parent_client_id',
            'meta_value'    => $_GET['user_id'],
            'fields'        => array( 'ID', 'user_login' ),
        );

    $client_staff = get_users( $args );

}

//get all messages
if ( 'all_messages' == $mode ) {

    if ( !class_exists( 'pagination' ) )
        include_once( 'pagination.php' );

    if ( current_user_can( 'wpc_manager' ) && !current_user_can( 'administrator' ) ) {
        $manager_id = get_current_user_id();
        $items = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) FROM {$wpdb->prefix}wpc_client_comments WHERE sent_from=%d OR sent_to=%d", $manager_id, $manager_id ) );
    } else {
        $items = $wpdb->get_var( "SELECT count(id) FROM {$wpdb->prefix}wpc_client_comments " . $where );
    }
    $p = new pagination;
    $p->items( $items );
    $p->limit( 25 );
    $p->target( 'admin.php?page=wpclients_messages' . $target );
    $p->calculate();
    $p->parameterName( 'p' );
    $p->adjacents( 2 );

    if( !isset( $_GET['p'] ) ) {
        $p->page = 1;
    } else {
        $p->page = $_GET['p'];
    }


    if ( current_user_can( 'wpc_manager' ) && !current_user_can( 'administrator' ) ) {
        $messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpc_client_comments WHERE sent_from=%d OR sent_to=%d ORDER BY time DESC LIMIT %d, %d "
        , $manager_id
        , $manager_id
        , ( $p->page - 1 ) * $p->limit
        , $p->limit
        ), "ARRAY_A" );

        $args = array(
            'role'          => 'wpc_client',
            'orderby'       => 'user_login',
            'order'         => 'ASC',
            'meta_key'      => 'admin_manager',
            'meta_value'    => $manager_id,
            'exclude'       => $not_approved_clients,
            'fields'        => array( 'ID', 'user_login' ),
        );
    } else {
        $messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpc_client_comments " . $where . " ORDER BY time DESC LIMIT %d, %d ", ( $p->page - 1 ) * $p->limit, $p->limit ),  "ARRAY_A" );

        $args = array(
            'role'      => 'wpc_client',
            'orderby'   => 'user_login',
            'order'     => 'ASC',
            'exclude'   => $not_approved_clients,
            'fields'    => array( 'ID', 'user_login' ),
        );
    }

    $clients = get_users( $args );

}




//Set date format
if ( get_option( 'date_format' ) ) {
    $time_format = get_option( 'date_format' );
} else {
    $time_format = 'm/d/Y';
}
if ( get_option( 'time_format' ) ) {
    $time_format .= ' ' . get_option( 'time_format' );
} else {
    $time_format .= ' g:i:s A';
}

?>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />


    <?php
    if ( isset( $_GET['msg'] ) ) {
        switch( $_GET['msg'] ) {
            case 'r':
                echo '<div id="message" class="updated fade"><p>' . __( 'Message marked as read.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 's':
                echo '<div id="message" class="updated fade"><p>' . __( 'Message is sent.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Message is deleted.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }

    ?>


<?php if ( 'all_messages' == $mode ): ?>


    <?php if ( is_array( $clients ) && 0 < count( $clients ) ): ?>
    <br>
    <form action="" method="post">
    <input type="hidden" name="from_messages_page" value="1" />
    <table>
        <tr>
            <td>
                <label for="sent_client_id"><?php _e( 'Sent message to', WPC_CLIENT_TEXT_DOMAIN ) ?>: </label>
                <select name="sent_client_id" id="sent_client_id" >
                    <option value="-1"><?php _e( '-Select Client-', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                    <?php foreach( $clients as $client )
                        echo '<option value="' . $client->ID . '">' . $client->user_login . '</option>';
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <textarea name="comment" style="width:500px; height:100px;" placeholder="<?php _e( 'Type your private message here', WPC_CLIENT_TEXT_DOMAIN ) ?>"></textarea>
            </td>
        </tr>
        <tr>
            <td align="center">
                <input type="submit" name="submit" id="submit" class='button-primary' value="<?php _e( 'Send private message', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
            </td>
        </tr>
    </table>
    </form>

    <script type="text/javascript">
        jQuery(document).ready(function(){

            //submit message
            jQuery( "#submit" ).click( function() {
                if ( 1 > jQuery( "#sent_client_id" ).val() ) {
                    jQuery( '#sent_client_id' ).parent().parent().attr( 'class', 'wpc_error' );
                    return false;
                }
                return true;
            });

        });
    </script>

    <?php endif; ?>


    <br>
    <div class="clear"></div>
    <h2><?php _e( 'Messages', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h2>
    <div class="clear"></div>


    <table class="widefat">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <th><?php _e( 'Message', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'From', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'To', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'Time', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>&nbsp;</th>
                <th><?php _e( 'Message', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'From', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'To', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                <th><?php _e( 'Time', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
            </tr>
        </tfoot>
        <tbody>
        <?php
        if ( is_array( $messages ) && 0 < count( $messages ) ) :
            foreach ( $messages as $message ) :
        ?>
            <tr class='over'>
                <td>
                    <input type='checkbox'>
                </td>
                <td>
                    <?php if ( $message['new_flag'] )
                        echo '<span style="color: #ff0000;">' . __( 'NEW!', WPC_CLIENT_TEXT_DOMAIN ) . '</span> <b>' . wp_trim_words( nl2br( htmlspecialchars( stripslashes( $message['comment'] ) ) ), 10 ) . '</b>';
                    else
                        echo wp_trim_words( nl2br( htmlspecialchars( stripslashes( $message['comment'] ) ) ), 10 );
                    ?>
                    <div class="row-actions">
                    <?php if ( $message['new_flag'] ) :  ?>
                        <span class="view"><a href="admin.php?page=wpclients_messages&read=<?php echo $message['id'] ?>" title="Mark as Read" ><?php _e( 'Mark as Read', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                    <?php endif; ?>
                        <span class="edit"><a href="admin.php?page=wpclients_messages&user_id=<?php echo $message['user_id'] ?>&from_id=<?php echo $message['sent_from'] ?>&to_id=<?php echo $message['sent_to'] ?>" title="Read" ><?php _e( 'Read & Reply', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                        <span class="delete"><a href="admin.php?page=wpclients_messages&delete=<?php echo $message['id'] ?>" class="submitdelete" title="Delete" ><?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?></a> </span>
                    </div>
                </td>
                <td>
                    <?php
                    if ( 0 < $message['sent_from'] ) {
                        if ( false != $user = get_userdata( $message['sent_from'] ) ) {
                             echo $user->get( 'user_login' );
                        } else {
                            echo __( '(deleted user)', WPC_CLIENT_TEXT_DOMAIN );
                        }
                    } else {
                        echo __( 'Administrator', WPC_CLIENT_TEXT_DOMAIN );
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ( 0 < $message['sent_to'] ) {
                        if ( false != $user = get_userdata( $message['sent_to'] ) ) {
                             echo $user->get( 'user_login' );
                        } else {
                            echo __( '(deleted user)', WPC_CLIENT_TEXT_DOMAIN );
                        }
                    } else {
                        echo __( 'Administrator', WPC_CLIENT_TEXT_DOMAIN );
                    }
                    ?>
                </td>
                <td>
                    <?php echo $this->date_timezone( $time_format, $message['time'] ) ?>
                </td>
            </tr>
        <?php
            endforeach;
        endif;
        ?>
        </tbody>
    </table>
    <div class="tablenav">
        <div class='tablenav-pages'>
            <?php echo $p->show(); ?>
        </div>
    </div>


<?php elseif ( 'chain_messages' == $mode ): ?>

    <h3><?php _e( 'Chain of Messages', WPC_CLIENT_TEXT_DOMAIN ) ?></h3>
    <table class="widefat" >
        <?php
        if ( is_array( $messages ) && 0 < count( $messages ) ) :
            foreach ( $messages as $message ) :
        ?>
        <tr <?php echo ( $message['sent_from'] == get_current_user_id() ) ? 'style="background-color: #F4F4F4;"' : 'style="background-color: #FFF;"' ?> >
            <td>
                <?php echo $this->date_timezone( $time_format, $message['time'] ) ?>  -
                <strong>
                <?php
                if ( 0 == $message['sent_from'] )
                    _e( 'Administrator', WPC_CLIENT_TEXT_DOMAIN );
                else
                    echo get_userdata( $message['sent_from'] )->get( 'user_login' );
                ?></strong>: <br />
                <?php echo nl2br( htmlspecialchars( stripslashes( $message['comment'] ) ) ) ?>
                <br>
                <br>
                <span class="delete"><a href="admin.php?page=wpclients_messages&user_id=<?php echo $_GET['user_id'] ?>&from_id=<?php echo $_GET['from_id'] ?>&to_id=<?php echo $_GET['to_id'] ?>&delete=<?php echo $message['id'] ?>" class="submitdelete" title="Delete" ><?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>
                <br>
                <br>
            </td>
        </tr>
        <?php
            endforeach;

        else:
        ?>
        <tr>
            <td>
                <p>
                    <?php _e( 'No message.', WPC_CLIENT_TEXT_DOMAIN ) ?>
                </p>
            </td>
        </tr>
        <?php

        endif;
        ?>
    </table>

    <br>
    <form action="" method="post">
    <table width="100%">
        <tr>
            <td align="center">
                <label for="sent_client_id"><?php _e( 'Sent message to', WPC_CLIENT_TEXT_DOMAIN ) ?>: </label>  &nbsp;
                <select name="sent_client_id" id="sent_client_id" >
                    <option value="-1">&nbsp;<?php _e( '-Select Client / Staff-', WPC_CLIENT_TEXT_DOMAIN ) ?>&nbsp;&nbsp;</option>
                    <option value="<?php echo $_GET['user_id'] ?>"><?php echo get_userdata( $_GET['user_id'] )->get( 'user_login' ) ?></option>';

                    <?php if ( is_array( $client_staff ) && 0 < count( $client_staff ) ) {
                    foreach( $client_staff as $staff )
                        echo '<option value="' . $staff->ID . '"> - ' . $staff->user_login . '</option>';

                    } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td align="center">
                    <textarea name="comment" style="width:500px; height:100px;" placeholder="<?php _e( 'Type your private message here', WPC_CLIENT_TEXT_DOMAIN ) ?>"></textarea>
                    <br/>
                    <input type="submit" name="submit" id="submit" class='button-primary' value="<?php _e( 'Send private message', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
            </td>
        </tr>
    </table>
    </form>

    <script type="text/javascript">
        jQuery(document).ready(function(){

            //submit message
            jQuery( "#submit" ).click( function() {
                if ( 1 > jQuery( "#sent_client_id" ).val() ) {
                    jQuery( '#sent_client_id' ).parent().parent().attr( 'class', 'wpc_error' );
                    return false;
                }
                return true;
            });

        });
    </script>


<?php endif; ?>