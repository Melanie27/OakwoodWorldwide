<?php
global $wpdb;


if ( isset( $_GET['action'] ) && 'staff_approve' == $_GET['action'] ) {
    delete_user_meta( $_GET['id'], 'to_approve' );
    $_GET['msg'] = 'a';
}


if ( isset( $_GET['action'] ) && 'delete' == $_GET['action'] ) {
    wp_delete_user( $_GET['id'] );
    $_GET['msg'] = 'd';
}


if (isset($_GET['msg'])) {
	$msg = $_GET['msg'];
	switch($msg) {
		case 'a':
			echo '<div id="message" class="updated fade"><p>' . __( 'Employee is approved.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
			break;
		case 'd':
			echo '<div id="message" class="updated fade"><p>' . __( 'Employee is Deleted.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
			break;
	}
}

if ( !class_exists( 'pagination' ) )
    include_once( 'pagination.php' );

$args = array(
    'role'          => 'wpc_client_staff',
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
    'role'          => 'wpc_client_staff',
    'meta_key'      => 'to_approve',
    'offset'        => ($p->page - 1) * $p->limit,
    'number'        => $p->limit,
);

$staffs = get_users( $args );

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
                    url: '<?php echo get_option( 'siteurl' ) ?>/wp-admin/admin-ajax.php',
                    data: 'action=get_all_groups',
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
                            <th><?php _e( 'Employee', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'First Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Assigned to Client', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>&nbsp;</th>
                            <th><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'First Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Assigned to Client', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                            <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        </tr>
                    </tfoot>
                    <tbody>
                <?php
                foreach ( $staffs as $staff ) :
                    $staff              = get_userdata( $staff->ID );
                    $parent_client_id   = get_user_meta( $staff->ID, 'parent_client_id', true );

                    if (0 < $parent_client_id )
                        $client_name = get_userdata( $parent_client_id )->get( 'user_login' );
                    else
                        $client_name = '';
                ?>
                    <tr class='over'>
                        <td>
                            <input type='checkbox'>
                        </td>
                        <td id="assign_name_block_<?php echo $staff->ID ?>" >
                            <?php echo $staff->user_login ?>
                        </td>
                        <td>
                            <?php echo $staff->first_name ?>
                        </td>
                        <td>
                            <?php echo $staff->user_email ?>
                        </td>
                        <td>
                            <?php echo $client_name ?>
                        </td>
                        <td>
                            <a href="admin.php?page=wpclients&tab=staff_approve&action=staff_approve&id=<?php echo $staff->ID ?>"><?php _e( 'Approve', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                            <a onclick="return confirm('<?php _e( 'Are you sure to delete this Client?', WPC_CLIENT_TEXT_DOMAIN ) ?>');" href="admin.php?page=wpclients&tab=staff_approve&action=delete&id=<?php echo $staff->ID ?>"><?php _e( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) ?></a>
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

    </div>

</div>
