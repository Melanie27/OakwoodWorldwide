<?php

//save url slugs
if ( isset( $_REQUEST['update'] ) ) {


    $settings               = get_option( 'wpc_settings' );
    $wpc_settings['slugs']  = $_REQUEST['wpc_settings']['slugs'];

    //remove / from start and end slug
    foreach( $wpc_settings['slugs'] as $k => $value ) {
        $wpc_settings['slugs'][$k] = ltrim( rtrim( $value,'/' ), '/' );
        // set default if empty
        if ( '' == $wpc_settings['slugs'][$k] )
            $wpc_settings['slugs'][$k] = $this->defaults_slugs[$k];
    }
    //replace / on placeholders for save it
    $wpc_settings['slugs'] = str_replace( '/', '-slsh-', $wpc_settings['slugs'] );
    //filter slugs
    $wpc_settings['slugs'] = array_map( 'sanitize_title', $wpc_settings['slugs'] );
    // return / from placeholders
    $settings['slugs'] = str_replace( '-slsh-', '/', $wpc_settings['slugs'] );

    update_option( 'wpc_settings', $settings );

    //flush rewrite rules due to slugs
    flush_rewrite_rules( false );

    do_action( 'wp_client_redirect', admin_url() . 'admin.php?page=wpclients_settings&tab=url_slugs&msg=u' );
    exit;
}

$msg = '';
if ( isset( $_GET['msg'] ) ) {
  $msg = $_GET['msg'];
}

$wpc_settings = get_option( 'wpc_settings' );

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
                    case 'u':
                        echo  __( 'URL Slugs Updated Successfully.', WPC_CLIENT_TEXT_DOMAIN );
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
            <li id="url_slugs" class="active"><a href="admin.php?page=wpclients_settings&tab=url_slugs" ><?php _e( 'URL Slugs', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="addons"><a href="admin.php?page=wpclients_settings&tab=addons" ><?php _e( 'Addons', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="about"><a href="admin.php?page=wpclients_settings&tab=about" ><?php _e( 'About', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
        </ul>

        <span class="clear"></span>
        <div class="content23 news">

            <form method="post" class="">

                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'URL Slugs', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <span class="description"><?php _e( "Customizes the url structure of your Client's Portal", WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Portal Base:', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                    <span class="description"><?php _e( '(can be empty)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </th>
                                <td>
                                    /
                                    <input type="text" name="wpc_settings[slugs][base]" value="<?php echo ( isset( $wpc_settings['slugs']['base'] ) ) ? $wpc_settings['slugs']['base'] : '' ?>" size="20" maxlength="50" />
                                    /
                                    <br />
                                    <span class="description"><?php _e( 'This URL will be used like base for all urls bellow.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Login Page: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][login]" value="<?php echo ( isset( $wpc_settings['slugs']['login'] ) && '' != $wpc_settings['slugs']['login'] ) ? $wpc_settings['slugs']['login'] : $this->defaults_slugs['login'] ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Logout Url: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][logout]" value="<?php echo ( isset( $wpc_settings['slugs']['logout'] ) && '' != $wpc_settings['slugs']['logout'] ) ? $wpc_settings['slugs']['logout'] : $this->defaults_slugs['logout'] ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'HUB Page: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][hub]" value="<?php echo ( isset( $wpc_settings['slugs']['hub'] ) && '' != $wpc_settings['slugs']['hub'] ) ? $wpc_settings['slugs']['hub'] : $this->defaults_slugs['hub'] ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Edit Portal Pages: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][edit_clientpage]" value="<?php echo ( isset( $wpc_settings['slugs']['edit_clientpage'] ) && '' != $wpc_settings['slugs']['edit_clientpage'] ) ? $wpc_settings['slugs']['edit_clientpage'] : $this->defaults_slugs['edit_clientpage']  ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Staff Directory: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][staff_directory]" value="<?php echo ( isset( $wpc_settings['slugs']['staff_directory'] ) && '' != $wpc_settings['slugs']['staff_directory'] ) ? $wpc_settings['slugs']['staff_directory'] : $this->defaults_slugs['staff_directory']  ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Add Staff: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][add_staff]" value="<?php echo ( isset( $wpc_settings['slugs']['add_staff'] ) && '' != $wpc_settings['slugs']['add_staff'] ) ? $wpc_settings['slugs']['add_staff'] : $this->defaults_slugs['add_staff']  ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Edit Staff: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][edit_staff]" value="<?php echo ( isset( $wpc_settings['slugs']['edit_staff'] ) && '' != $wpc_settings['slugs']['edit_staff'] ) ? $wpc_settings['slugs']['edit_staff'] : $this->defaults_slugs['edit_staff']  ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Client Registration: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][registration]" value="<?php echo ( isset( $wpc_settings['slugs']['registration'] ) && '' != $wpc_settings['slugs']['registration'] ) ? $wpc_settings['slugs']['registration'] : $this->defaults_slugs['registration']  ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Successful Client Registration: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][registration_successful]" value="<?php echo ( isset( $wpc_settings['slugs']['registration_successful'] ) && '' != $wpc_settings['slugs']['registration_successful'] ) ? $wpc_settings['slugs']['registration_successful'] : $this->defaults_slugs['registration_successful']  ?>" size="20" maxlength="50" />
                                    /
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Feedback Wizard: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][feedback_wizard]" value="<?php echo ( isset( $wpc_settings['slugs']['feedback_wizard'] ) && '' != $wpc_settings['slugs']['feedback_wizard'] ) ? $wpc_settings['slugs']['feedback_wizard'] : $this->defaults_slugs['feedback_wizard']  ?>" size="20" maxlength="50" />
                                    /
                                    <br>
                                    <?php if ( !defined( 'WPC_CLIENT_ADDON_FEEDBACK_WIZARD' ) ) echo '<span class="description">' . sprintf( __( 'You should activate using <a href="%s">Addons</a> to use this setting.', WPC_CLIENT_TEXT_DOMAIN ), 'admin.php?page=wpclients_settings&tab=addons' )  . '</span>' ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Feedback Wizard Sent: *', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                </th>
                                <td>
                                    /<?php echo ( isset( $wpc_settings['slugs']['base'] ) && '' != $wpc_settings['slugs']['base'] ) ? $wpc_settings['slugs']['base'] . '/' : '' ?>
                                    <input type="text" name="wpc_settings[slugs][feedback_wizard_sent]" value="<?php echo ( isset( $wpc_settings['slugs']['feedback_wizard_sent'] ) && '' != $wpc_settings['slugs']['feedback_wizard_sent'] ) ? $wpc_settings['slugs']['feedback_wizard_sent'] : $this->defaults_slugs['feedback_wizard_sent']  ?>" size="20" maxlength="50" />
                                    /
                                    <br>
                                    <?php if ( !defined( 'WPC_CLIENT_ADDON_FEEDBACK_WIZARD' ) ) echo '<span class="description">' . sprintf( __( 'You should activate using <a href="%s">Addons</a> to use this setting.', WPC_CLIENT_TEXT_DOMAIN ), 'admin.php?page=wpclients_settings&tab=addons' )  . '</span>' ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <input type='submit' name='update' id="update" class='button-primary' value='<?php _e( 'Update', WPC_CLIENT_TEXT_DOMAIN ) ?>' />
            </form>
        </div>
    </div>

</div>