<?php

extract( $_REQUEST );

if ( isset( $update_settings ) ) {
    if ( 'login_alerts' == $key ) {
        $settings['login_alerts']                           = $login_alerts;
    } elseif( 'skins' == $key ) {
        $settings['theme']                                  = $theme;
    } elseif( 'general' == $key ) {
        $settings['create_client']                          = $create_client;
        $settings['notify_message']                         = $notify_message;
        $settings['notify_message2']                        = $notify_message2;
        $settings['graphic']                                = $graphic;
        $settings['show_link']                              = $show_link;
        $settings['link_text']                              = $link_text;
        $settings['show_sort']                              = $show_sort;
        $settings['wpc_settings']['hide_dashboard']         = $wpc_settings['hide_dashboard'];
        $settings['wpc_settings']['show_custom_menu']       = $wpc_settings['show_custom_menu'];
        $settings['wpc_settings']['custom_menu_logged_in']  = ( isset( $wpc_settings['custom_menu_logged_in'] ) ) ? $wpc_settings['custom_menu_logged_in'] : '';
        $settings['wpc_settings']['custom_menu_logged_out'] = ( isset( $wpc_settings['custom_menu_logged_out'] ) ) ? $wpc_settings['custom_menu_logged_out'] : '';
        $settings['wpc_settings']['client_registration']    = $wpc_settings['client_registration'];
        $settings['wpc_settings']['auto_client_approve']    = ( isset( $wpc_settings['auto_client_approve'] ) ) ? '1' : '0';
        $settings['wpc_settings']['staff_registration']     = $wpc_settings['staff_registration'];
        $settings['wpc_settings']['show_file_cats']         = $wpc_settings['show_file_cats'];
        $settings['wpc_settings']['deny_file_cats']         = $wpc_settings['deny_file_cats'];
        $settings['wpc_settings']['flash_uplader_admin']    = $wpc_settings['flash_uplader_admin'];
        $settings['wpc_settings']['flash_uplader_client']   = $wpc_settings['flash_uplader_client'];
        $settings['wpc_settings']['file_size_limit']        = $wpc_settings['file_size_limit'];

    }

    $settings['key'] = $key;

	do_action( 'wp_settings_update', $settings );

	echo '<div id="message" class="updated fade"><p>' . __( 'Settings updated Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
}


$theme2                     = get_option( 'wpclients_theme' );
$show_link                  = get_option( 'wpc_show_link' );
$link_text                  = get_option( 'wpc_link_text' );
$graphic                    = get_option( 'wpc_graphic' );
$show_sort                  = get_option( 'show_sort' );
$create_client              = get_option( 'wpc_create_client' );
$login_alerts               = get_option( 'wpc_login_alerts' );
$wpc_settings               = get_option( 'wpc_settings' );
$notify_message             = get_option( 'wpc_notify_message' );
$notify_message2            = get_option( 'wpc_notify_message2' );

if ( empty( $graphic ) ) {
    $graphic = '';
}


if ( empty( $show_sort ) ) {
    $show_sort = 'yes';
}

if(empty($notify_message)) {
           $notify_message = "yes";
}

if( empty( $notify_message2 ) ) {
    $notify_message2 = 'yes';
}

if(empty($create_client)) {
           $create_client = "yes";
}

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

    <div id="container23">
        <ul class="menu">
            <li id="general" <?php echo ( !isset( $_GET['tab'] ) ) ? 'class="active"' : '' ?>><a href="admin.php?page=wpclients_settings" ><?php _e( 'General', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="clogin"><a href="admin.php?page=custom_login_admin" ><?php _e( 'Custom Login', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="redirects"><a href="admin.php?page=xyris-login-logout" ><?php _e( 'Login/Logout Redirects', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="skins" <?php echo ( isset( $_GET['tab'] ) && 'skins' == $_GET['tab'] ) ? 'class="active"' : '' ?> ><a href="admin.php?page=wpclients_settings&tab=skins" ><?php _e( 'Skins', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="alerts" <?php echo ( isset( $_GET['tab'] ) && 'alerts' == $_GET['tab'] ) ? 'class="active"' : '' ?> ><a href="admin.php?page=wpclients_settings&tab=alerts" ><?php _e( 'Login Alerts', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="url_slugs" <?php echo ( isset( $_GET['tab'] ) && 'url_slugs' == $_GET['tab'] ) ? 'class="active"' : '' ?>><a href="admin.php?page=wpclients_settings&tab=url_slugs" ><?php _e( 'URL Slugs', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="addons"><a href="admin.php?page=wpclients_settings&tab=addons" ><?php _e( 'Addons', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="about" <?php echo ( isset( $_GET['tab'] ) && 'about' == $_GET['tab'] ) ? 'class="active"' : '' ?> ><a href="admin.php?page=wpclients_settings&tab=about" ><?php _e( 'About', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
        </ul>

        <span class="clear"></span>
        <div class="content23 news">

        <?php if ( isset( $_GET['tab'] ) && 'about' == $_GET['tab'] ): ?>
            <p>
                Version <?php echo WPC_CLIENT_VER ?>
            </p>
            <h3>Privacy Policy</h3>
            <p><a href="http://www.wp-client.com/">WP-Client.com</a>&nbsp;will never sell any information associated with your account. Server logs are maintained for hit-tracking purposes and your IP address will be recorded.</p>
            <p><a href="http://www.wp-client.com/">WP-Client.com</a>&nbsp;was developed with security and privacy as its highest priority, however, it is not responsible should information get exposed through hacker exploitation or otherwise.</p>
            <h3>Terms of Service</h3>
            <p>You agree that the owners of this web site (<a href="http://www.wp-client.com/">WP-Client.com</a>) exclusively reserve the right and may, at any time and without notice and any liability to you, modify or discontinue this web site and its services or delete the data you provide, whether temporarily or permanently. We (<a href="http://www.wp-client.com/">WP-Client.com</a>) shall have no liability for the timeliness, deletion, failure to store, inaccuracy, or improper delivery of any data or information.</p>
            <p>The use of this site and/or application is provided "AS IS" and NO guarantee, implied or express, is provided should there be any error, data loss, breach of privacy or any other event causing any malfunction whatsoever. It is up to the user to back-up any information input into the <a href="http://www.wp-client.com/">WP-Client.com</a>&nbsp;application and service may stop without prior warning. Any account may be removed at any time for any reason.</p>
            <p>You assume total responsibility and risk for your use of this Service; use at your own risk.</p>
            <p>By using this service, you acknowledge you are at least 14 years old.</p>
            <h3>DISCLAIMER OF WARRANTIES</h3>
            <p>YOU UNDERSTAND AND AGREE THAT YOUR USE OF THIS WEB SITE AND ANY SERVICES OR CONTENT PROVIDED (THE SERVICE) IS MADE AVAILABLE AND PROVIDED TO YOU AT YOUR OWN RISK. IT IS PROVIDED TO YOU AS IS AND WE EXPRESSLY DISCLAIM ALL WARRANTIES OF ANY KIND, IMPLIED OR EXPRESS, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</p>
            <p><a href="http://www.wp-client.com/">WP-Client.com</a>&nbsp;MAKES NO WARRANTY, IMPLIED OR EXPRESS, THAT ANY PART OF THE SERVICE WILL BE UNINTERRUPTED, ERROR-FREE, VIRUS-FREE, TIMELY, SECURE, ACCURATE, RELIABLE, OF ANY QUALITY, NOR THAT ANY CONTENT IS SAFE IN ANY MANNER FOR DOWNLOAD. YOU UNDERSTAND AND AGREE THAT NEITHER US NOR ANY PARTICIPANT IN THE SERVICE PROVIDES PROFESSIONAL ADVICE OF ANY KIND AND THAT USE OF SUCH ADVICE OR ANY OTHER INFORMATION IS SOLELY AT YOUR OWN RISK AND WITHOUT OUR LIABILITY OF ANY KIND.</p>
            <p>Some jurisdictions may not allow disclaimers of implied warranties and the above disclaimer may not apply to you only as it relates to implied warranties.</p>
            <p><a href="http://www.wp-client.com/">WP-Client.com</a>&nbsp;makes no guarantee of availability of service and reserves the right to change, withdraw, suspend, or discontinue any functionality or feature of the <a href="http://www.wp-client.com/">WP-Client.com</a>&nbsp;service.</p>
            <h3>LIMITATION OF LIABILITY</h3>
            <p>YOU EXPRESSLY UNDERSTAND AND AGREE THAT WE SHALL NOT BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL, INDICENTAL, CONSEQUENTIAL OR EXEMPLARY DAMAGES, INCLUDING BUT NOT LIMITED TO, DAMAGES FOR LOSS OF PROFITS, GOODWILL, USE, DATA OR OTHER INTANGIBLE LOSS (EVEN IF WE HAVE BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES), RESULTING FROM OR ARISING OUT OF (I) THE USE OF OR THE INABILITY TO USE THE SERVICE, (II) THE COST TO OBTAIN SUBSTITUTE GOODS AND/OR SERVICES RESULTING FROM ANY TRANSACTION ENTERED INTO ON THROUGH THE SERVICE, (III) UNAUTHORIZED ACCESS TO OR ALTERATION OF YOUR DATA TRANSMISSIONS, (IV) STATEMENTS OR CONDUCT OF ANY THIRD PARTY ON THE SERVICE, OR (V) ANY OTHER MATTER RELATING TO THE SERVICE.</p>
            <h3>Third Party Services</h3>
            <p>Goods and services of third parties may be mentioned and/or made available on or through this web site. Representations made regarding products and services provided by third parties are governed by the policies and representations made by these third parties. We shall not be liable for or responsible in any manner for any of your dealings or interaction with third parties.</p>


        <?php elseif ( isset( $_GET['tab'] ) && 'alerts' == $_GET['tab'] ): ?>

            <form action="" method="post" name="wpc_settings" id="wpc_settings" >
                <input type="hidden" name="key" value="login_alerts" />

                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'Login Alerts', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="login_alerts_email"><?php _e( 'Email Address', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <input name="login_alerts[email]" id="login_alerts_email" type="text" size="30" value="<?php echo ( isset( $login_alerts['email'] ) ) ? $login_alerts['email'] : '' ; ?>" />
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="login_alerts_successful"><?php _e( 'Successful Logins', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="login_alerts[successful]" id="login_alerts_successful" style="width: 100px;">
                                        <option value="0"><?php _e( 'Off', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="1" <?php echo ( isset( $login_alerts['successful'] ) && '1' == $login_alerts['successful'] ) ? 'selected' : '' ; ?> ><?php _e( 'On', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="login_alerts_failed"><?php _e( 'Failed Logins', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                                </th>
                                <td>
                                    <select name="login_alerts[failed]" id="login_alerts_failed" style="width: 100px;">
                                        <option value="0"><?php _e( 'Off', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="1" <?php echo ( isset( $login_alerts['failed'] ) && '1' == $login_alerts['failed'] ) ? 'selected' : '' ; ?> ><?php _e( 'On', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <input type='submit' name='update_settings' class='button-primary' value='<?php _e( 'Update Settings', WPC_CLIENT_TEXT_DOMAIN ) ?>' />
            </form>

            <script language="JavaScript">
                jQuery( document ).ready( function() {

                    jQuery( "#wpc_settings" ).submit( function () {
                        if ( ( '1' == jQuery( '#login_alerts_successful' ).val() || '1' == jQuery( '#login_alerts_failed' ).val() ) && '' == jQuery( '#login_alerts_email' ).val() ) {
                            jQuery( '#login_alerts_email' ).parent().parent().attr( 'class', 'wpc_error' );
                            return false;
                        }

                        return true;
                    });

                });
            </script>

        <?php elseif ( isset( $_GET['tab'] ) && 'skins' == $_GET['tab'] ): ?>

            <form action="" method="post">
                <input type="hidden" name="key" value="skins" />

                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'Change Skins | Changes the color of the default images used in Hub Page', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="theme"><?php _e( 'Select Skin Style', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="theme" id="theme" style="width: 100px;">
                                        <option value="dark" <?php if($theme2=="dark") {?> selected="selected" <?php }?> ><?php _e( 'Dark', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="light" <?php if($theme2=="light") {?> selected="selected" <?php }?> ><?php _e( 'Light', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <input type='submit' name='update_settings' class='button-primary' value='<?php _e( 'Update Settings', WPC_CLIENT_TEXT_DOMAIN ) ?>' />
            </form>

        <?php else: ?>

            <form action="" method="post">
                <input type="hidden" name="key" value="general" />

                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'Notification Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="notify_message"><?php _e( 'Receive email notification of private messages from clients', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="notify_message" id="notify_message" style="width: 100px;">
                                        <option value="yes" <?php if($notify_message=="yes") {?> selected="selected" <?php }?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php if($notify_message=="no") {?> selected="selected" <?php }?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="notify_message2"><?php _e( 'Receive email notification of private messages from admin', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="notify_message2" id="notify_message2" style="width: 100px;">
                                        <option value="yes" <?php if($notify_message2=="yes") {?> selected="selected" <?php }?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php if($notify_message2=="no") {?> selected="selected" <?php }?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'Create Client/Staff Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="create_client"><?php _e( 'Automatically create Portal Page', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="create_client" id="create_client" style="width: 100px;">
                                        <option value="yes" <?php if($create_client=="yes") {?> selected="selected" <?php }?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php if($create_client=="no") {?> selected="selected" <?php }?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_hide_dashboard"><?php _e( 'Hide dashboard/backend', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[hide_dashboard]" id="wpc_settings_hide_dashboard" style="width: 100px;">
                                        <option value="yes" <?php echo ( isset( $wpc_settings['hide_dashboard'] ) && 'yes' == $wpc_settings['hide_dashboard'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php echo ( !isset( $wpc_settings['hide_dashboard'] ) || 'no' == $wpc_settings['hide_dashboard'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                    <span class="description">Hide dashboard/backend from clients and client staff.</span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_client_registration"><?php _e( 'Open Client Registration', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[client_registration]" id="wpc_settings_client_registration" style="width: 100px;">
                                        <option value="yes" <?php echo ( isset( $wpc_settings['client_registration'] ) && 'yes' == $wpc_settings['client_registration'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php echo ( isset( $wpc_settings['client_registration'] ) && 'no' == $wpc_settings['client_registration'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'Allow registration client. All clients require approval from the Administrator.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="wpc_settings[auto_client_approve]" id="wpc_settings_auto_client_approve" value="1" <?php echo ( isset( $wpc_settings['auto_client_approve'] ) && '1' == $wpc_settings['auto_client_approve'] ) ? 'checked' : '' ?> />
                                        <?php _e( 'Automatically approve clients who register using form.', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                    </label>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_staff_registration"><?php _e( 'Open Staff Registration', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[staff_registration]" id="wpc_settings_staff_registration" style="width: 100px;">
                                        <option value="yes" <?php echo ( isset( $wpc_settings['staff_registration'] ) && 'yes' == $wpc_settings['staff_registration'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php echo ( isset( $wpc_settings['staff_registration'] ) && 'no' == $wpc_settings['staff_registration'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'Allow Client to add staff. All staff requires approval from the Administrator.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'File Display Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="show_sort"><?php _e( 'Show sort', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="show_sort" id="show_sort" style="width: 100px;">
                                        <option value="yes" <?php if($show_sort=="yes") {?> selected="selected" <?php }?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php if($show_sort=="no") {?> selected="selected" <?php }?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_show_file_cats"><?php _e( 'Show File Categories', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[show_file_cats]" id="wpc_settings_show_file_cats" style="width: 100px;">
                                        <option value="1" <?php echo ( isset( $wpc_settings['show_file_cats'] ) && '1' == $wpc_settings['show_file_cats'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="0" <?php echo ( isset( $wpc_settings['show_file_cats'] ) && '0' == $wpc_settings['show_file_cats'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'Display File Categories on Client HUB page.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_deny_file_cats"><?php _e( 'Enable category choice for client file upload', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[deny_file_cats]" id="wpc_settings_deny_file_cats" style="width: 100px;">
                                        <option value="1" <?php echo ( isset( $wpc_settings['deny_file_cats'] ) &&  '1' == $wpc_settings['deny_file_cats'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="0" <?php echo ( isset( $wpc_settings['deny_file_cats'] ) && '0' == $wpc_settings['deny_file_cats'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'By default, files will be uploaded in "General" category.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_deny_file_cats"><?php _e( 'Use Flash uploader in Admin area', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[flash_uplader_admin]" id="wpc_settings_flash_uplader_admin" style="width: 100px;">
                                        <option value="1" <?php echo ( isset( $wpc_settings['flash_uplader_admin'] ) &&  '1' == $wpc_settings['flash_uplader_admin'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="0" <?php echo ( isset( $wpc_settings['flash_uplader_admin'] ) && '0' == $wpc_settings['flash_uplader_admin'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'with progress bar, multiple files uploading.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_deny_file_cats"><?php _e( 'Use Flash uploader in Client area', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[flash_uplader_client]" id="wpc_settings_flash_uplader_admin" style="width: 100px;">
                                        <option value="1" <?php echo ( isset( $wpc_settings['flash_uplader_client'] ) &&  '1' == $wpc_settings['flash_uplader_client'] ) ? 'selected' : '' ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="0" <?php echo ( isset( $wpc_settings['flash_uplader_client'] ) && '0' == $wpc_settings['flash_uplader_client'] ) ? 'selected' : '' ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'with progress bar, multiple files uploading.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wpc_settings_file_size_limit"><?php _e( 'Max File Size For Upload (Kb)', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <input type="text" name="wpc_settings[file_size_limit]" id="wpc_settings_file_size_limit" value="<?php echo ( isset( $wpc_settings['file_size_limit'] ) ) ? $wpc_settings['file_size_limit'] : '' ?>" />
                                    <span class="description"><?php _e( 'Remember: 1M = 1024Kb', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                    <br>
                                    <span class="description"><?php _e( 'Leave blank to allow unlimited file size.<br>NOTE: This setting does not change your server settings. You should change your server settings if you are have trouble.<br>Your server settings are:', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                    <?php
                                    echo '<br><span class="description"><b>upload_max_filesize</b> = ' . ini_get( 'upload_max_filesize' ) . '</span>';
                                    echo '<br><span class="description"><b>post_max_size</b> = ' . ini_get( 'post_max_size' ) . '</span>';
                                     ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>


                <div class="postbox">
                    <h3 class='hndle'><span><?php _e( 'Custom Navigation Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></span></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="show_custom_menu"><?php _e( 'Show custom menu on login', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="wpc_settings[show_custom_menu]" id="show_custom_menu" style="width: 100px;">
                                        <option value="yes" <?php echo ( isset( $wpc_settings['show_custom_menu'] ) && $wpc_settings['show_custom_menu'] == 'yes' ) ? "selected" : ''; ?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php echo ( isset( $wpc_settings['show_custom_menu'] ) && $wpc_settings['show_custom_menu'] == 'no' ) ? "selected" : ''; ?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <?php
                            $locations = get_registered_nav_menus();
                            if ( is_array( $locations ) && 0 < count( $locations ) ) {
                                foreach( $locations as $key => $value ) {
                            ?>
                                <tr valign="top">
                                    <th scope="row">
                                        <label for="custom_menu_logged_in"><?php echo $value ?> <span class="description"><?php _e( '(logged-in)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>:</label>
                                    </th>
                                    <td>
                                        <?php
                                            $nav_menus = wp_get_nav_menus( array( 'orderby' => 'name' ) );
                                            $num_menus = count( array_keys( $nav_menus ) );
                                            if( $num_menus > 0 ) {

                                        ?>
                                        <select name="wpc_settings[custom_menu_logged_in][<?php echo $key ?>]" id="custom_menu_logged_in" style="width: 100px;">
                                            <option value=""></option>
                                            <?php
                                                foreach ( $nav_menus as $menu ) {
                                            ?>
                                                    <option value="<?php echo $menu->term_id; ?>" <?php echo ( isset( $wpc_settings['custom_menu_logged_in'][$key] ) && $wpc_settings['custom_menu_logged_in'][$key] == $menu->term_id ) ? 'selected' : ''; ?> ><?php echo $menu->name; ?></option>
                                            <?php
                                                }
                                            ?>
                                        </select>
                                        <?php
                                            }
                                            else {
                                        ?>
                                            <span class="description"><?php _e( 'Please first create menu in Appearance->Menus', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        <?php
                                            }
                                        ?>
                                        <span class="description"><?php _e( '(Custom menu for logged-in users)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                    </td>
                                </tr>

                                <tr valign="top">
                                    <th scope="row">
                                        <label for="custom_menu_logged_out"><?php echo $value ?> <span class="description"><?php _e( '(not logged-in)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>:</label>
                                    </th>
                                    <td>
                                        <?php
                                            $nav_menus = wp_get_nav_menus( array( 'orderby' => 'name' ) );
                                            $num_menus = count( array_keys( $nav_menus ) );
                                            if ( $num_menus > 0 ) {
                                        ?>
                                        <select name="wpc_settings[custom_menu_logged_out][<?php echo $key ?>]" id="custom_menu_logged_out" style="width: 100px;">
                                            <option value=""></option>
                                            <?php
                                                foreach ( $nav_menus as $menu ) {
                                            ?>
                                                    <option value="<?php echo $menu ->term_id; ?>" <?php echo ( isset( $wpc_settings['custom_menu_logged_out'][$key] ) && $wpc_settings['custom_menu_logged_out'][$key] == $menu->term_id ) ? 'selected' : ''; ?> ><?php echo $menu->name; ?></option>
                                            <?php
                                                }
                                            ?>
                                        </select>
                                        <?php
                                            }
                                            else {
                                        ?>
                                            <span class="description"><?php _e( 'Please first create menu in Appearance->Menus', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                        <?php
                                            }
                                        ?>
                                        <span class="description"><?php _e( '(Custom menu for not logged-in users)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                                    </td>
                                </tr>

                            <?php
                                }
                            }
                            ?>



                            <tr valign="top">
                                <th scope="row">
                                    <label for="show_link"><?php _e( 'Show HUB page link in menu', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <select name="show_link" id="show_link" style="width: 100px;">
                                        <option value="yes" <?php if($show_link=="yes") {?> selected="selected" <?php }?> ><?php _e( 'Yes', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                        <option value="no" <?php if($show_link=="no") {?> selected="selected" <?php }?> ><?php _e( 'No', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="link_text"><?php _e( 'HUB page link text', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <input type="text" name="link_text" id="link_text" value="<?php echo $link_text;?>" />
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="graphic"><?php _e( 'Graphic (for shortcode [wpc_client_graphic])', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                                </th>
                                <td>
                                    <input type="text" name="graphic" id="graphic" value="<?php echo $graphic;?>" />
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <input type='submit' name='update_settings' id="update_settings" class='button-primary' value='<?php _e( 'Update Settings', WPC_CLIENT_TEXT_DOMAIN ) ?>' />
            </form>

        <?php endif; ?>
        </div>
    </div>
