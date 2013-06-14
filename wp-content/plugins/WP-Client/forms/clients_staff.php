<?php
global $wpdb;

if ( isset( $_GET['action'] ) ) {

	wp_delete_user( $_GET['id'] );
	$_GET['msg'] = 'd';
}

if ( !class_exists( 'pagination' ) )
    include_once( 'pagination.php' );

$items = count_users();
$items = ( isset( $items['avail_roles']['wpc_client'] ) ) ? $items['avail_roles']['wpc_client'] : 0;

$p = new pagination;
$p->items($items);
$p->limit(25);
$p->target("admin.php?page=wpclients&tab=staff");
$p->calculate();
$p->parameterName('p');
$p->adjacents(2);

if(!isset($_GET['p'])) {
	$p->page = 1;
} else {
	$p->page = $_GET['p'];
}


$not_approved_clients = get_users( array( 'role' => 'wpc_client_staff', 'meta_key' => 'to_approve', 'fields' => 'ID', ) );


$args = array(
    'role'      => 'wpc_client_staff',
    'orderby'   => 'user_login',
    'order'     => 'ASC',
    'exclude'   => $not_approved_clients,
    'offset'    => ($p->page - 1) * $p->limit,
    'number'    => $p->limit,

);

$rows = get_users( $args );

?>

<div style="" class='wrap'>

    <script type="text/javascript">
        jQuery(document).ready(function(){

	        jQuery(".over").hover(function(){
		        jQuery(this).css("background-color","#bcbcbc");
		        },function(){
		        jQuery(this).css("background-color","transparent");
		    });

        });

    </script>

    <div class="wpc_logo"></div>
    <hr />

    <?php
    if (isset($_GET['msg'])) {
        $msg = $_GET['msg'];
        switch($msg) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Employee <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated fade"><p>' . __( 'Employee <strong>Updated</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Employee <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>

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
                        <th><?php _e( 'Employee', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'First Name', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Assigned to Client', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </tfoot>
                <tbody>
            <?php
            foreach ( $rows as $author ) :
                $author = get_userdata( $author->ID );
                $parent_client_id = get_user_meta( $author->ID, 'parent_client_id', true );
                if (0 < $parent_client_id )
                    $client_name = get_userdata( $parent_client_id )->get( 'user_login' );
                else
                    $client_name = '';


                echo "
                <tr class='over'>
                    <td><input type='checkbox'></td>
                    <td>$author->user_login</td>
                    <td>$author->first_name</td>
                    <td>$author->user_email</td>
                    <td>$client_name</td>
                    <td>
                    <a href='admin.php?page=wpclients_messages&user_id=$parent_client_id'>" . __( 'Messages', WPC_CLIENT_TEXT_DOMAIN ) . "</a> /
                    <a href='admin.php?page=wpclients&tab=staff_edit&id=$author->ID'>" . __( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) . "</a> /

                    <a onclick='return confirm(\"" . __( 'Are you sure to delete this Employee?', WPC_CLIENT_TEXT_DOMAIN ) . "\");' href='admin.php?page=wpclients&tab=staff&action=delete&id=$author->ID'>" . __( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) . "</a>
                    </td>
                </tr>";
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