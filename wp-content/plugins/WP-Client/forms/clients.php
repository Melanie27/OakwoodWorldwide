<?php
global $wpdb;
$msg = "";

$target_order   = '';
$orderby        = '';
$order          = '';
$search         = '';

//order by
if ( isset( $_GET['orderby']  ) ) {
    $order = ( isset( $_GET['order'] ) && in_array( $_GET['order'], array( 'asc', 'desc' ) ) ) ? $_GET['order'] : 'desc';
    $orderby = (in_array( $_GET['orderby'], array( 'username', 'email', ) ) ) ? $_GET['orderby'] : 'username';

    if ( 'username' == $orderby )
        $sql_order = 'ORDER BY a.user_login ';
    elseif ( 'email' == $orderby )
        $sql_order = 'ORDER BY a.user_email ';

    $sql_order .= ' ' . strtoupper( $order ) ;

    $target_order = '&orderby=' . $orderby . '&order=' . $order;

} else {
    $sql_order = 'ORDER BY a.ID DESC';
}

//search
if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] ) {

    $search = "
        AND ( a.user_login LIKE '%" . trim( $_REQUEST['s'] ) . "%'
        OR  a.user_email LIKE '%" . trim( $_REQUEST['s'] ) . "%'
        OR  a.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'first_name' AND meta_value LIKE '%" . trim( $_REQUEST['s'] ) . "%' )
        OR  a.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'nickname' AND meta_value LIKE '%" . trim( $_REQUEST['s'] ) . "%' )
        )
    ";

}


if( isset($_GET['msg'] )) {
  $msg = $_GET['msg'];
}

if ( isset( $_POST['import'] ) ) {
    $target_path = wp_upload_dir();;
    $target_path = $target_path['basedir']."/";
    $target_path = $target_path . basename( $_FILES['file']['name']);
	$ext = strtolower(end(explode('.', $_FILES['file']['name'])));

	if($ext === 'csv')
	{
		if(move_uploaded_file($_FILES['file']['tmp_name'], $target_path))
		{
			$row = 1;
			if (($handle = fopen($target_path, "r")) !== FALSE)
			{
				while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
				{
					$row++;
					$userdata = array(
						'user_login' => esc_attr( trim( $data[0] ) ),
						'user_pass' => esc_attr ( $data[1] ),
						'nickname' => esc_attr( trim( $data[2] ) ),
						'first_name' => esc_attr( trim( $data[3] ) ),
						'user_email' => esc_attr( $data[4] ),
						'role' => 'wpc_client',
						'contact_phone' => esc_attr( $data[5] ),
						'send_password' => esc_attr( $data[6] ),
					);
					do_action('wp_clients_update', $userdata );
				}
				fclose($handle);
			}
			else
			{
				$msg = "uf";
			}
		}
		else
		{
			$msg = "uf";
		}
	}
	else
	{
		$msg = "uf";
	}

    do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients' . $target_order . '&msg=' . $msg );
    exit;

}


//to delete client
if ( isset( $_GET['action'] ) && 'delete' == $_GET['action'] ) {
	$client_id  = $_GET['id'];
	$t_name     = $wpdb->prefix . "wpc_client_login_redirects";
	$user_data  = get_userdata( $client_id );

    //delete redirect rules for client
    //$wpdb->query( "DELETE FROM $t_name WHERE rul_value='" . $user_data->user_login . "'" );
	 $wpdb->query($wpdb->prepare("DELETE FROM $t_name WHERE rul_value=%s",$user_data->user_login));

    //find client files and remome access
    $files = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpc_client_files WHERE clients_id LIKE '%#$client_id,%'", "ARRAY_A" );
    if ( is_array( $files ) && 0 < count( $files ) ) {
        foreach( $files as $file ) {
            $new_access = str_replace( "#$client_id,", '', $file['clients_id'] );
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpc_client_files SET clients_id='%s' WHERE id=%d ", $new_access, $file['id'] ) );
        }
    }

    //delete client from Client Circle
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wpc_client_group_clients WHERE client_id=%d ", $client_id ) );


    //get client's clientpages
    $clientpages_id = $wpdb->get_results(
        "SELECT $wpdb->posts.ID FROM $wpdb->posts
        INNER JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID
        WHERE
        $wpdb->posts.post_type = 'clientspage' AND
        $wpdb->postmeta.meta_key = 'user_ids' AND
        $wpdb->postmeta.meta_value like '%\"$client_id\"%'
        "
    );

    //remove access for clientpages
    if ( is_array( $clientpages_id ) && 0 < count( $clientpages_id ) ) {
        foreach( $clientpages_id as $clientpage_id ) {
            $user_ids = get_post_meta( $clientpage_id->ID, 'user_ids', true );
            $user_ids = array_flip( $user_ids );
            unset( $user_ids[$client_id] );
            $user_ids = array_flip( $user_ids );
            update_post_meta( $clientpage_id->ID, 'user_ids', $user_ids );
        }
    }

    //unassign staff
    $args = array(
            'role'          => 'wpc_client_staff',
            'meta_key'      => 'parent_client_id',
            'meta_value'    => $client_id,
            'fields'        => 'ID',
        );

    $client_staff_ids = get_users( $args );
    if ( is_array( $client_staff_ids ) && 0 < count( $client_staff_ids ) )
        foreach( $client_staff_ids as $client_staff_id ) {
            update_user_meta( $client_staff_id, 'parent_client_id', '' );
        }

    //delete HUB
    $user = get_user_meta( $client_id, 'first_name' );
    $page = get_page_by_title( $user[0], object, 'hubpage' );
    if ( isset( $page->ID ) )
        wp_delete_post( $page->ID );


    //delete client
	wp_delete_user( $client_id );

    do_action('wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients' . $target_order . '&msg=d');
    exit;
}

if ( !class_exists( 'pagination' ) )
    include_once( 'pagination.php' );

//$items = count_users();
//$items = ( isset( $items['avail_roles']['wpc_client'] ) ) ? $items['avail_roles']['wpc_client'] : 0;
$sql = "SELECT count( a.ID ) FROM {$wpdb->users} a, {$wpdb->usermeta} b
        WHERE
            a.ID = b.user_id
            AND b.meta_key = '{$wpdb->prefix}capabilities'
            AND b.meta_value LIKE '%s:10:\"wpc_client\";%'
            AND a.ID NOT IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'to_approve')
        {$search}
        {$sql_order}
        ";
$items = $wpdb->get_var( $sql );

$p = new pagination;
$p->items($items);
$p->limit(25);
$p->target("admin.php?page=wpclients" . $target_order );
$p->calculate();
$p->parameterName('p');
$p->adjacents(2);

if(!isset($_GET['p'])) {
	$p->page = 1;
} else {
	$p->page = $_GET['p'];
}

$limit = " LIMIT " . ( $p->page - 1 ) * $p->limit . ", " . $p->limit;

$sql = "SELECT a.ID FROM {$wpdb->users} a, {$wpdb->usermeta} b
        WHERE
            a.ID = b.user_id
            AND b.meta_key = '{$wpdb->prefix}capabilities'
            AND b.meta_value LIKE '%s:10:\"wpc_client\";%'
            AND a.ID NOT IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'to_approve')
        {$search}
        {$sql_order}
        {$limit}
        ";
$clients = $wpdb->get_results( $sql, 'ARRAY_A' );

$code = md5( 'wpc_client_' . get_current_user_id() . '_send_mess' );
?>

<div style="" class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>
    <?php
    if ( '' != $msg ) {
        switch( $msg ) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Client <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated fade"><p>' . __( 'Client <strong>Updated</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Client <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
			case 'uf':
                echo '<div id="message" class="updated fade"><p>' . __( 'There was an error uploading the file, please try again!', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
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

            <div class="alignleft actions">
                <form action="?page=wpclients<?php echo $target_order ?>" method="post" enctype="multipart/form-data">
                    <table>
                        <tr>
                            <td>
                            <span style="color: #800000;">
                                <em>
                                    <span style="font-size: small;">
                                        <span style="line-height: normal;">
                                            <?php _e( 'Import Clients from CSV File:', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                        </span>
                                    </span>
                                </em>
                            </span>
                            </td>
                            <td><input type="file" name="file" id="file" /></td>
                            <td><input type="submit" class='button-primary' name="import" value="Import !" onclick="return checkform();" /></td>
                        </tr>
                    </table>
                </form>
            </div>
            <div class="alignright actions">
                <form method="post" name="wpc_client_serach_form" id="wpc_client_serach_form">
                    <p class="search-box">
                        <label for="search" class="screen-reader-text"><?php _e( 'Search Customer', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                        <input type="search" value="<?php echo ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] ) ? $_REQUEST['s'] : '' ?>" name="s" id="search">
                        <input type="submit" value="<?php _e( 'Search', WPC_CLIENT_TEXT_DOMAIN ) ?>" class="button" id="search_submit">
                    </p>
                </form>
            </div>

            <div class="clear"></div>
            <hr />

            <table class="widefat">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th class="manage-column column-title sorted <?php echo ( isset( $_GET['orderby'] ) && 'username' == $_GET['orderby'] ) ? $_GET['order'] : '' ?>" style="" scope="col">
                            <a href="admin.php?page=wpclients&orderby=username&order=<?php echo ( isset( $_GET['orderby'] ) && 'username' == $_GET['orderby'] ) ? ( ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) ? 'asc' : 'desc' ) : 'desc' ?>">
                                <span><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th><?php _e( 'Contact Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Business Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th class="manage-column column-title sorted <?php echo ( isset( $_GET['orderby'] ) && 'email' == $_GET['orderby'] ) ? $_GET['order'] : '' ?>" style="" scope="col">
                            <a href="admin.php?page=wpclients&orderby=email&order=<?php echo ( isset( $_GET['orderby'] ) && 'email' == $_GET['orderby'] ) ? ( ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) ? 'asc' : 'desc' ) : 'desc' ?>">
                                <span><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th style="width:75px;"><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>&nbsp;</th>
                        <th class="manage-column column-title sorted <?php echo ( isset( $_GET['orderby'] ) && 'username' == $_GET['orderby'] ) ? $_GET['order'] : '' ?>" style="" scope="col">
                            <a href="admin.php?page=wpclients&orderby=username&order=<?php echo ( isset( $_GET['orderby'] ) && 'username' == $_GET['orderby'] ) ? ( ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) ? 'asc' : 'desc' ) : 'desc' ?>">
                                <span><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th><?php _e( 'Contact Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Business Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th class="manage-column column-title sorted <?php echo ( isset( $_GET['orderby'] ) && 'email' == $_GET['orderby'] ) ? $_GET['order'] : '' ?>" style="" scope="col">
                            <a href="admin.php?page=wpclients&orderby=email&order=<?php echo ( isset( $_GET['orderby'] ) && 'email' == $_GET['orderby'] ) ? ( ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) ? 'asc' : 'desc' ) : 'desc' ?>">
                                <span><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </tfoot>
                <tbody>
            <?php
            if ( isset( $clients ) && is_array( $clients ) && 0 <  count( $clients ) ) {
                foreach ( $clients as $client ) :
                    $client = get_userdata( $client['ID'] );
            ?>
                    <tr class='over'>
                        <td><input type='checkbox'></td>
                        <td><span id="client_username_<?php echo $client->ID ?>"><?php echo $client->user_login ?></span>
                            <div class="row-actions">
                                <span class="edit"><a href='admin.php?page=edit_client&id=<?php echo $client->ID ?>'><?php _e( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                <span class="delete"><a onclick='return confirm("<?php _e( 'Are you sure to delete this Client? ', WPC_CLIENT_TEXT_DOMAIN ) ?>");' href='admin.php?page=wpclients<?php echo $target_order ?>&action=delete&id=<?php echo $client->ID ?>'><?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                <span class="edit"><a href='admin.php?page=wpclients_files&filter=<?php echo $client->ID ?>'><?php _e( 'Files', WPC_CLIENT_TEXT_DOMAIN ) ?></a> | </span>
                                <span class="edit"><a href='admin.php?page=wpclients_messages&filter=<?php echo $client->ID ?>'><?php _e( 'Messages', WPC_CLIENT_TEXT_DOMAIN ) ?></a></span>

                            </div>
                        </td>
                        <td><?php echo $client->nickname ?></td>
                        <td><?php echo $client->first_name ?></td>
                        <td><?php echo $client->user_email ?></td>
                        <td>
                        <select name="quick_action" class="quick_action" id="qa_<?php echo $client->ID ?>">
                            <option value="-1"><?php _e( 'Quick Action', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <option value="send_message"><?php _e( 'Send Message', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                        </select>
                        </td>
                    </tr>
            <?php
                endforeach;
            } else {
                $text = ( isset( $_REQUEST['s'] ) ) ?  __( 'Not Found Clients', WPC_CLIENT_TEXT_DOMAIN ) :  __( 'No Clients', WPC_CLIENT_TEXT_DOMAIN );
                echo "
                <tr class='over'>
                        <td colspan='6' align='center'>
                        <p>" . $text . "</p>
                        </td>

                    </tr>";
            }
            ?>
                </tbody>
            </table>
            <div class="tablenav">
                <div class='tablenav-pages'>
                    <?php echo $p->show(); ?>
                </div>
            </div>


            <div class="wpc_qa_send_message" id="qa_send_message" style="display: none;">
                <h2><?php _e( 'Send Message To:', WPC_CLIENT_TEXT_DOMAIN ) ?> <span id="qa_send_username"></span></h2>
                <form method="post" name="wpc_qa_send_message" id="wpc_qa_send_message">
                    <input type="hidden" name="qa_send_message_client_id" id="qa_send_message_client_id" value="" />
                    <table>
                        <tr>
                            <td>
                                <textarea name="qa_send_message_comment" id="qa_send_message_comment" style="width:500px; height:100px;" placeholder="<?php _e( 'Type your private message here', WPC_CLIENT_TEXT_DOMAIN ) ?>"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td align="center">
                                <div id="ajax_result_message" style="display: inline;"></div>
                            </td>
                        </tr>
                    </table>
                    <div style="clear: both; text-align: center;">

                        <input type="button" class='button-primary' id="send_message" name="send_message" value="<?php _e( 'Send Message', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                        <input type="button" class='button' id="close_send_message" value="<?php _e( 'Close', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                    </div>
                </form>
            </div>


            <script type="text/javascript">
                jQuery(document).ready(function(){

                    jQuery(".over").hover(function(){
                        jQuery(this).css("background-color","#E2E2E2");
                        },function(){
                        jQuery(this).css("background-color","transparent");
                    });


                    //Quick actions
                    jQuery( '.quick_action' ).change( function() {
//                        alert( jQuery( this ).attr( 'id' ) );
                        var qa_id           = jQuery( this ).attr( 'id' );
                        var client_id       = jQuery( this ).attr( 'id' ).replace( 'qa_', '' );
                        var client_username = jQuery( '#client_username_' + client_id ).html();

                        if ( 'send_message' == jQuery( this ).val() ) {

                            jQuery( '#qa_send_message_client_id' ).val( client_id );
                            jQuery( '#qa_send_username' ).html( client_username );

                            jQuery.fancybox({
                                'type'        : 'inline',
                                'beforeClose' : (function() {
                                    jQuery( '#' + qa_id ).val( '-1' );
                                }),
                                'fitToView'   : 'false',
                                'autoSize'    : 'true',
                                'openEffect'  : 'none',
                                'closeEffect' : 'none',
                                'href'        : '#qa_send_message'
                            });

                        }

                    });


                    //close QA send message
                    jQuery( '#close_send_message' ).click( function() {
                        jQuery( '#qa_send_message_client_id' ).val( '' );
                        jQuery( '#qa_send_message_comment' ).val( '' );
                        jQuery.fancybox.close();
                    });


                    // AJAX - QA send message
                    jQuery( '#send_message' ).click( function() {
                        client_id     = jQuery( '#qa_send_message_client_id' ).val();
                        comment       = jQuery( '#qa_send_message_comment' ).val();

                        jQuery( 'body' ).css( 'cursor', 'wait' );
                        jQuery( '#ajax_result_message' ).html('');
                        jQuery( '#ajax_result_message' ).show();
                        jQuery( '#ajax_result_message' ).css('display', 'inline');
                        jQuery( '#ajax_result_message' ).html('<div class="wpc_ajax_loading"></div>');

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                            data: 'action=wpc_qa_send_message&uid=<?php echo get_current_user_id() ?>&client_id=' + client_id + '&comment=' + comment + '&code=<?php echo $code ?>' ,
                            dataType: "json",
                            success: function( data ){
                                jQuery( 'body' ).css( 'cursor', 'default' );

                                    if( data.status ) {
                                        jQuery( '#ajax_result_message' ).css( 'color', 'green' );
                                        jQuery( '#qa_send_message_comment' ).val( '' );
                                    } else {
                                        jQuery( '#ajax_result_message' ).css( 'color', 'red' );
                                    }
                                    jQuery( '#ajax_result_message' ).html( data.message );
                                    setTimeout( function() {
                                        jQuery( '#ajax_result_message' ).fadeOut(1500);
                                    }, 2500 );

                                },
                            error: function( data ) {
                                jQuery( '#ajax_result_message' ).css( 'color', 'red' );
                                jQuery( '#ajax_result_message' ).html( 'Unknown error.' );
                                setTimeout( function() {
                                    jQuery( '#ajax_result_message' ).fadeOut( 1500 );
                                }, 2500 );
                            }
                         });

                    });


                });

                function checkform(){
                    if(document.getElementById('file').value == ""){
                        alert("<?php _e( 'Please select a valid csv file to import.', WPC_CLIENT_TEXT_DOMAIN ) ?>")
                        return false;
                    }
                    return true;
                }

            </script>

        </div>
    </div>

</div>
