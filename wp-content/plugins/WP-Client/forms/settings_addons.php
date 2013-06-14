<?php

$msg = '';
if ( isset( $_GET['msg'] ) ) {
  $msg = $_GET['msg'];
}

if ( isset( $_GET['action'] ) && isset( $_GET['addon'] ) && '' != $_GET['addon'] ) {

    $n      = '';
    $addon  = $_GET['addon'];
    $active = get_option( 'wpc_activated_addons', array() );

    switch( $_GET['action'] ) {
        case 'activate':
            if( !in_array( $addon, $active ) ) {
                $active[] = $addon;
                add_option( 'wpc_addon_activated_' . $addon, '1' );
                $n = 'a';
            } else {
                $n = 'na';
            }
            break;

        case 'deactivate':
            $found = array_search( $addon, $active );
            if( $found !== false ) {
                unset( $active[$found] );
                $n = 'd';
            } else {
                $n = 'nd';
            }
            break;

    }

    update_option( 'wpc_activated_addons', array_unique( $active ) );

    do_action('wp_client_redirect', get_admin_url(). 'admin.php?page=wpclients_settings&tab=addons&msg=' . $n );
    exit;

}



$plugins    = $this->get_wpclient_addons();
$active     = get_option( 'wpc_activated_addons', array() );


?>

<style type="text/css">
    .wrap input[type=text] {
        width:400px;
    }

    .wrap input[type=password] {
        width:400px;
    }
</style>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>

    <div class="icon32" id="icon-options-general"></div>
    <h2><?php _e( 'WP-Client Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></h2>

    <p><?php _e( 'From here you can manage a variety of options for the WP-Client plugin.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>

    <?php
    if ( '' != $msg ) {
    ?>
        <div id="message" class="updated fade">
            <p>
            <?php
                switch( $msg ) {
                    case 'a':
                        echo  __( 'Addon activated.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'na':
                        echo __( 'Addon not activated.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'd':
                        echo __( 'Addon deactivated.', WPC_CLIENT_TEXT_DOMAIN );
                        break;
                    case 'nd':
                        echo __( 'Addon not deactivated', WPC_CLIENT_TEXT_DOMAIN );
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
            <li id="general"><a href="admin.php?page=wpclients_settings" ><?php _e( 'General', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="clogin"><a href="admin.php?page=custom_login_admin" ><?php _e( 'Custom Login', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="redirects"><a href="admin.php?page=xyris-login-logout" ><?php _e( 'Login/Logout Redirects', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="skins"><a href="admin.php?page=wpclients_settings&tab=skins" ><?php _e( 'Skins', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="alerts"><a href="admin.php?page=wpclients_settings&tab=alerts" ><?php _e( 'Login Alerts', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="url_slugs"><a href="admin.php?page=wpclients_settings&tab=url_slugs" ><?php _e( 'URL Slugs', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="addons" class="active" ><a href="admin.php?page=wpclients_settings&tab=addons" ><?php _e( 'Addons', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="about"><a href="admin.php?page=wpclients_settings&tab=about" ><?php _e( 'About', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
        </ul>

        <span class="clear"></span>
        <div class="content23 news">

            <form method="post" action="" class="wpc_addons">
                <table cellspacing="0" class="widefat fixed">
                    <thead>
                    <tr>
                        <th class="manage-column column-c" scope="col" width="10">&nbsp;</th>
                        <th class="manage-column column-name" scope="col"><?php _e( 'Addon Name',  WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th class="manage-column column-name" scope="col" width="700"><?php _e( 'Description',  WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th class="manage-column column-active" scope="col"><?php _e( 'Active',  WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                    </thead>

                    <tfoot>
                    <tr>
                        <th class="manage-column column-c" scope="col">&nbsp;</th>
                        <th class="manage-column column-name" scope="col"><?php _e( 'Addon Name',  WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th class="manage-column column-name" scope="col"><?php _e( 'Description',  WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                        <th class="manage-column column-active" scope="col"><?php _e( 'Active',  WPC_CLIENT_TEXT_DOMAIN ) ?></th>
                    </tr>
                    </tfoot>
                    <tbody>
                        <?php
                        if( !empty( $plugins ) ) {

                            foreach( $plugins as $key => $plugin ) {

                                $default_headers = array(
                                    'Name'          => 'Addon Name',
                                    'Description'   => 'Description',
                                );

                                $plugin_data = get_file_data( $this->plugin_dir . 'addons/' . $plugin . '/' . $plugin . '.php', $default_headers, 'plugin' );

                                if(empty($plugin_data['Name'])) {
                                    continue;
                                }

                                ?>
                                <tr valign="middle" class="alternate" id="plugin-<?php echo $plugin; ?>">
                                    <td class="column-c" valign="bottom">
                                        <input type="checkbox" value="" disabled <?php echo ( in_array( $plugin, $active ) ) ? 'checked' : '' ?>  />
                                    </td>
                                    <td class="column-name">
                                        <?php echo '<strong>' . esc_html( $plugin_data['Name'] ) . '</strong>' ?>

                                        <div class="actions">
                                        <?php if( in_array( $plugin, $active ) ) { ?>
                                            <span class="edit deactivate">
                                                <a href="admin.php?page=wpclients_settings&tab=addons&action=deactivate&addon=<?php echo $plugin ?>"> <?php _e( 'Deactivate',  WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                                            </span>
                                        <?php } else { ?>
                                            <span class="edit activate">
                                                <a href="admin.php?page=wpclients_settings&tab=addons&action=activate&addon=<?php echo $plugin ?>"> <?php _e( 'Activate',  WPC_CLIENT_TEXT_DOMAIN ) ?></a>
                                            </span>
                                        <?php } ?>
                                        </div>

                                    </td>
                                    <td class="column-c" valign="bottom" align="justify">
                                        <div class="wpc_addon_description">
                                        <?php
                                        if ( !empty( $plugin_data['Description'] ) ) {
                                            echo esc_html($plugin_data['Description']);
                                        }
                                        ?>
                                        </div>
                                    </td>

                                    <td class="column-active">
                                        <?php
                                            if( in_array( $plugin, $active ) ) {
                                                echo "<strong>" . __( 'Active',  WPC_CLIENT_TEXT_DOMAIN ) . "</strong>";
                                            } else {
                                                _e( 'Inactive',  WPC_CLIENT_TEXT_DOMAIN );
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr valign="middle" class="alternate" >
                                <td colspan="3" scope="row"><?php _e( 'No Addons where found for this install.', WPC_CLIENT_TEXT_DOMAIN ); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

</div>