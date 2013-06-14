<?php

global $wpdb;

$client_page_name   = ( isset( $_POST['client_page_name'] ) ) ? $_POST['client_page_name'] : '';
$selected_page_name = ( isset( $_POST['selected_page_name'] ) ) ? $_POST['selected_page_name'] : '';
$users              = ( isset( $_POST['users'] ) ) ? $_POST['users'] : array();
$groups_id          = ( isset( $_POST['groups_id'] ) ) ? $_POST['groups_id'] : array();
//$page_name_id       = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '" . $selected_page_name . "'");
$page_name_id       = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s",$selected_page_name) );
$error              = '';

if ( $selected_page_name == 'wp-default' ) {
    $template_content = get_option( 'client_template' );
    $template_content = html_entity_decode( $template_content );
} else {
    //$template_content = $wpdb->get_var( "SELECT post_content FROM $wpdb->posts WHERE post_name = '" . $selected_page_name . "'" );
	$template_content = $wpdb->get_var( $wpdb->prepare("SELECT post_content FROM $wpdb->posts WHERE post_name = %s",$selected_page_name) );
}

//save Portal Page
if ( isset( $_POST['create_clientpage'] ) ) {
    if ( $client_page_name != '' ) {

        //???
       // $sql = "SELECT template FROM {$wpdb->prefix}wpc_client_clients_page WHERE template='" . $page_name_id . "'";

 	   // $query = mysql_query($sql);

		    //	$already_exisits= mysql_num_rows($query);

    //	 		if($already_exisits!=0)

    //			{

    //				$message="Page Already Exists ! Please Edit Clients Page to Assign New Users";

    //			}

    //			else

    //			{


        // Create post object
        $my_post = array(
            'post_title'        => esc_html( $client_page_name ),
            'post_content'      => $template_content,
            'post_status'       => 'publish',
            'post_author'       => 1,
            'post_type'         => 'clientspage',
            'comment_status'    => 'closed'
        );

        // Insert the post into the database
        $client_page_id = wp_insert_post( $my_post );

        update_post_meta( $client_page_id, 'user_ids', $users );

        //update clientpage file template
        if ( isset( $_POST['clientpage_template'] ) && 'default' != $_POST['clientpage_template'] ) {
            update_post_meta( $client_page_id, '_wp_page_template', $_POST['clientpage_template'] );
        }

        //save Client Circles for Portal Page
        if ( 0 < count( $groups_id ) )
            update_post_meta( $client_page_id, 'groups_id', $groups_id );
        else
            update_post_meta( $client_page_id, 'groups_id', null );

		//$sql_query = "INSERT INTO {$wpdb->prefix}wpc_client_clients_page SET pagename ='" . $client_page_name . "',template='" . $page_name_id . "',users='" . implode( ',', $users ) . "'";

        //mysql_query( $sql_query );

        //	}

		$wpdb->insert(
			"{$wpdb->prefix}wpc_client_clients_page",
			array(
				'pagename' => $client_page_name,
				'template' => $page_name_id,
				'users' => implode( ',', $users )
			)
		);

        do_action( 'wp_client_redirect', get_admin_url(). 'admin.php?page=add_client_page&msg=a' );
        exit;
    } else {
        $error .= __( 'You must enter Portal Page Title.<br/>', WPC_CLIENT_TEXT_DOMAIN );
    }

}
?>

<style type="text/css">
    .wrap input[type=text] {
        width:200px;
    }
</style>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>

    <?php
    if ( isset( $_GET['msg'] ) ) {
        switch( $_GET['msg'] ) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Portal Page is added.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>

    <div class="icon32" id="icon-edit"><br></div>
    <h2>Add Portal Page:</h2>
	<hr />

    <div id="message" class="updated fade" <?php echo ( empty( $error ) )? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>

    <form action="admin.php?page=add_client_page" method="post">
        <table>
            <tr>
                <td style="border-right:#666 solid 2px; width:220px; height:400px; vertical-align:top;">
                    <p>
    	                <label for="client_page_name"><?php _e( 'Portal Page Title', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                        <input type="text" id="client_page_name" name="client_page_name" value="<?php echo esc_html( $client_page_name ) ?>" />
                    </p>
                    <p>
                        <label for="selected_page_name"><?php _e( 'Page Content', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                        <select name="selected_page_name">
                            <option value="wp-default"><?php _e( 'Default Portal Page Template', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                            <?php
                            global $post;
                            $myposts = get_posts( 'post_type=page' );
                            foreach( $myposts as $post ) :
                                setup_postdata( $post );
                                ?>
                                <option><?php echo ucwords( $post->post_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                    <?php
                    if ( 0 != count( get_page_templates() ) ) {
                    ?>
                        <label for="selected_page_name"><?php _e( 'Template', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                        <label class="screen-reader-text" for="clientpage_template"><?php _e( 'Portal Page Template', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                        <select name="clientpage_template" id="clientpage_template">
                            <option value='default'><?php _e( 'Default Template', WPC_CLIENT_TEXT_DOMAIN ); ?></option>
                            <?php page_template_dropdown( false ); ?>
                        </select>
                    <?php
                    } else {
                        _e( "Didn't find any page templates", WPC_CLIENT_TEXT_DOMAIN );
                    }
                   ?>
                    </p>
                </td>

               <td style="vertical-align:top; width:500px; padding-left:10px;">
                   <br />
                   <strong><?php _e( 'Select Clients who will have permissions for this Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?>:</strong><br />
                   <span style="color: #800000; font-size: x-small;"><em><?php _e( 'This can be changed later in the editing interface for the appropriate Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?></em></span>

                    <?php

                    $not_approved_clients = get_users( array( 'role' => 'wpc_client', 'meta_key' => 'to_approve', 'fields' => 'ID', ) );

                    if ( current_user_can( 'wpc_manager' ) && !current_user_can( 'administrator' ) ) {
                        $args = array(
                            'role'          => 'wpc_client',
                            'orderby'       => 'user_login',
                            'order'         => 'ASC',
                            'meta_key'      => 'admin_manager',
                            'meta_value'    => get_current_user_id(),
                            'exclude'       => $not_approved_clients,
                        );
                    } else {
                        $args = array(
                            'role'          => 'wpc_client',
                            'orderby'       => 'user_login',
                            'order'         => 'ASC',
                            'exclude'       => $not_approved_clients,
                        );
                    }

                    $clients = get_users( $args );

                    if ( is_array( $clients ) && 0 < count( $clients ) )
                        foreach ( $clients as $client ) {
                            if ( in_array( $client->ID, $users ) )
                                $checked = 'checked';
                            else
                                $checked = '';

                            echo '
                                <br style="clear: both;"/>
                                <label>
                                    <input type="checkbox" name="users[]" value="' . $client->ID . '" ' . $checked . ' />
                                ' . $client->user_login . '
                                </label>
                            ';
                        }
                    ?>

                    <br />
                    <br />

                    <strong><?php _e( 'Select Client Circles who will have permissions for this Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?>:</strong><br />
                    <span style="color: #800000; font-size: x-small;"><em><?php _e( 'This can be changed later in the editing interface for the appropriate Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?></em></span>

                    <?php

                    $groups = $this->get_groups();

                    if ( is_array( $groups ) && 0 < count( $groups ) )
                        foreach ( $groups as $group ) {
                            if ( in_array( $group['group_id'], $groups_id ) )
                                $checked = 'checked';
                            else
                                $checked = '';

                            echo '
                                <br style="clear: both;"/>
                                <label>
                                    <input type="checkbox" name="groups_id[]" value="' . $group['group_id'] . '" ' . $checked . ' />
                                ' . $group['group_name'] . '
                                </label>
                            ';
                        }

                    ?>
                </td>
            </tr>
            <tr>
                <td>
	                <hr /><br />
                    <input type="submit" name="create_clientpage" id="submit" class='button-primary' value="<?php _e( 'Create New Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?>"  />
                </td>
                <td>
                </td>
            </tr>
        </table>
     </form>
</div>



<script type="text/javascript">
    jQuery(document).ready(function(){

        //submit message
        jQuery( "#submit" ).click( function() {
            if ( ''== jQuery( "#client_page_name" ).val() ) {
                jQuery( '#client_page_name' ).parent().attr( 'class', 'wpc_error' );
                jQuery( '#client_page_name' ).focus();
                return false;
            }
            return true;
        });

    });
</script>
