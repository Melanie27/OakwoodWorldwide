<?php
global $wpdb;

if ( isset( $_GET['action'] ) ) {
	$id         = $_GET['id'];
	$t_name     = $wpdb->prefix . "wpc_client_login_redirects";
	$user_data  = get_userdata($id);

	//$sql         = "DELETE FROM $t_name WHERE rul_value='".$user_data->user_login."'";
	//mysql_query($sql);
	$wpdb->query($wpdb->prepare("DELETE FROM $t_name WHERE rul_value=%s",$user_data->user_login));

	wp_delete_user( $id, $reassign );
	$_GET['msg'] = 'd';
}

if ( !class_exists( 'pagination' ) )
    include_once( 'pagination.php' );

$items = count_users();
$items = ( isset( $items['avail_roles']['wpc_manager'] ) ) ? $items['avail_roles']['wpc_manager'] : 0;

$p = new pagination;
$p->items( $items );
$p->limit( 25 );
$p->target( "admin.php?page=wpclients_managers" );
$p->target( "admin.php?page=wpclients_managers" );
$p->calculate();
$p->parameterName( 'p' );
$p->adjacents( 2 );

if ( !isset( $_GET['p'] ) ) {
	$p->page = 1;
} else {
	$p->page = $_GET['p'];
}


$args = array(
    'role'      => 'wpc_manager',
    'orderby'   => 'user_login',
    'order'     => 'ASC',
    'offset'    => ( $p->page - 1 ) * $p->limit,
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
    if ( isset( $_GET['msg'] ) ) {
        $msg = $_GET['msg'];
        switch( $msg ) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Manager <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'u':
                echo '<div id="message" class="updated fade"><p>' . __( 'Manager <strong>Updated</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
            case 'd':
                echo '<div id="message" class="updated fade"><p>' . __( 'Manager <strong>Deleted</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <li id="news" class="active"><a href="admin.php?page=wpclients_managers" ><?php _e( 'Managers', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="tutorials"><a href="admin.php?page=wpclients_managers&tab=add" ><?php _e( 'Add Manager', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Nickname', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Nickname', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th><?php _e( 'Action', WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                </tfoot>
                <tbody>
            <?php
            foreach ( $rows as $manager ) :
                $manager = get_userdata( $manager->ID );
                echo "
                <tr class='over'>
                    <td>$manager->user_login</td>
                    <td>$manager->nickname</td>
                    <td>$manager->user_email</td>
                    <td>
                    <a href='admin.php?page=wpclients_managers&tab=edit&id=$manager->ID'>" . __( 'Edit', WPC_CLIENT_TEXT_DOMAIN ) . "</a> /
                    <a onclick='return confirm(\"" . __( 'Are you sure to delete this Client?', WPC_CLIENT_TEXT_DOMAIN ) . "\");' href='admin.php?page=wpclients_managers&action=delete&id=$manager->ID'>" . __( 'Delete', WPC_CLIENT_TEXT_DOMAIN ) . "</a>
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