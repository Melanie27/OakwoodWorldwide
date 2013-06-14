<?php
global $wpdb;

if ( isset( $_GET['action'] ) && 'delete' == $_GET['action'] ) {
	$id         = $_GET['id'];
	$t_name     = $wpdb->prefix . "wpc_client_login_redirects";
	$user_data  = get_userdata($id);

	//$sql         = "DELETE FROM $t_name WHERE rul_value='".$user_data->user_login."'";
	//mysql_query($sql);
	 $wpdb->query($wpdb->prepare("DELETE FROM $t_name WHERE rul_value=%s",$user_data->user_login));

	wp_delete_user( $id, $reassign );
	$_GET['msg'] = 'd';
}

if (isset($_GET['msg'])) {
	$msg = $_GET['msg'];
	switch($msg) {
		case 'a':
			echo '<div id="message" class="updated fade"><p>' . __( 'Client <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
			break;
		case 'd':
			echo '<div id="message" class="updated fade"><p>' . __( 'Client <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
			break;
	}
}

if ( !class_exists( 'pagination' ) )
    include_once( 'pagination.php' );


$args = array(
    'role'          => 'wpc_client',
    'meta_key'      => 'to_approve',
    'fields'        => 'ID',
);

$items = count( get_users( $args ) );

$p = new pagination;
$p->items($items);
$p->limit(25);
$p->target("admin.php?page=wpclients");
$p->calculate();
$p->parameterName('p');
$p->adjacents(2);

if(!isset($_GET['p'])) {
	$p->page = 1;
} else {
	$p->page = $_GET['p'];
}

$args = array(
    'role'          => 'wpc_client',
    'meta_key'      => 'to_approve',
    'offset'        => ($p->page - 1) * $p->limit,
    'number'        => $p->limit,
);

$clients = get_users( $args );


//get managers
$args = array(
    'role'      => 'wpc_manager',
    'orderby'   => 'user_login',
    'order'     => 'ASC',
    'fields'    => array( 'ID','user_login' ),

);

$managers = get_users( $args );



?>

<div style="" class='wrap'>

    <script type="text/javascript">
        jQuery(document).ready(function(){

	        jQuery(".over").hover(function(){
		        jQuery(this).css("background-color","#bcbcbc");
		        },function(){
		        jQuery(this).css("background-color","transparent");
		    });



            // AJAX - assign Client Circles to client
            jQuery.fn.getGroups = function ( client_id ) {
                jQuery( '#popup_content' ).html( '' );
                jQuery( '#select_all' ).parent().hide();
                jQuery( '#admin_manager :first' ).attr( 'selected', 'selected' );
                jQuery( '#select_all' ).attr( 'checked', false );
                jQuery( '#save_popup' ).hide();
                jQuery( '#client_id' ).val( client_id );
                jQuery( '#assign_name' ).html( '<?php _e( 'Approve the Client', WPC_CLIENT_TEXT_DOMAIN ) ?>: ' + jQuery( '#assign_name_block_' + client_id ).html() );
                jQuery( 'body' ).css( 'cursor', 'wait' );
                jQuery( '#opaco' ).fadeIn( 'slow' );
                jQuery( '#popup_block' ).fadeIn( 'slow' );

                jQuery.ajax({
                    type: 'POST',
                    url: '<?php echo site_url() ?>/wp-admin/admin-ajax.php',
                    data: 'action=get_all_groups',
                    success: function( html ){
                        jQuery( 'body' ).css( 'cursor', 'default' );
                        if ( 'false' == html ) {
                            jQuery( '#save_popup' ).show();
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

            //Select/Un-select
            jQuery( "#select_all" ).change( function() {
                if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                    jQuery( '#popup_content input[type="checkbox"]' ).attr( 'checked', true );
                } else {
                    jQuery( '#popup_content input[type="checkbox"]' ).attr( 'checked', false );
                }
            });



        });

    </script>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_tabs_menu( 'clients' ) ?>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <table class="widefat">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Contact Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Business Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Contact Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Business Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </tfoot>
                <tbody>
            <?php
            foreach ( $clients as $client ) :
                $client = get_userdata( $client->ID );
            ?>
                <tr class='over'>
                    <td>
                        <input type='checkbox'>
                    </td>
                    <td id="assign_name_block_<?php echo $client->ID ?>" >
                        <?php echo $client->user_login ?>
                    </td>
                    <td>
                        <?php echo $client->nickname ?>
                    </td>
                    <td>
                        <?php echo $client->first_name ?>
                    </td>
                    <td>
                        <?php echo $client->user_email ?>
                    </td>
                    <td>
                        <input type="button" id="assign_button_<?php echo $group['group_id'];?>" value="Approve" onclick="jQuery(this).getGroups( <?php echo $client->ID ?> );" />
                        <a onclick="return confirm('<?php _e( 'Are you sure to delete this Client?', WPC_CLIENT_TEXT_DOMAIN ) ?>');" href="admin.php?page=wpclients&tab=approve&action=delete&id=<?php echo $client->ID ?>"><?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                    </td>
                </tr>

            <?php
            endforeach;
            ?>
                </tbody>
            </table>

            <div class="tablenav">
                <div class='tablenav-pages'>
                    <?php echo $p->show(); ?>
                </div>
            </div>

        </div>




        <div id="opaco"></div>
        <div id="opaco2"></div>

        <div id="popup_block">
            <form name="approve_client" method="post" >
                <input type="hidden" name="wpc_action" value="client_approve" />
                <input type="hidden" name="client_id" id="client_id" value="" />
                <input type="hidden" value="<?php echo wp_create_nonce( 'wpc_client_approve' ) ?>" name="_wpnonce" id="_wpnonce">

                <h3 id="assign_name"></h3>

                <table>
                    <tr>
                        <td>
                            <h4><?php _e( 'Set Admin Manager', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h4>
                            <select name="admin_manager" id="admin_manager">
                                <option value="0"><?php _e( 'None', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                <?php
                                if ( is_array( $managers ) && 0 < count( $managers ) ) {
                                    foreach( $managers as $manager ) {
                                        echo '<option value="' . $manager->ID . '">' . $manager->user_login . ' </option>';
                                    }
                                }
                                ?>
                            </select>
                            <br/>
                            <br/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h4><?php _e( 'Assign to Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h4>
                            <label>
                                <input type="checkbox" id="select_all" value="all" />
                                <?php _e( 'Select all.', WPC_CLIENT_TEXT_DOMAIN ) ?>
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
                            <input type="submit" name="save" id="save_popup" value="<?php _e( 'Approve', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                            <input type="button" name="cancel" id="cancel_popup" value="<?php _e( 'Cancel', WPC_CLIENT_TEXT_DOMAIN ) ?>" />

                        </td>
                    </tr>
                </table>

            </form>
        </div>

    </div>

</div>
