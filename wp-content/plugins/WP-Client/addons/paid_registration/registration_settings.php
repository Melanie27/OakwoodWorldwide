<?php
global $wpdb, $wpc_gateway_plugins;

$error = '';

//update settings
if ( isset( $_POST['registration'] ) ) {

    // validate at php side
    $cost = str_replace( ',', '.', $_POST['registration']['cost'] );
    if ( empty( $cost ) ) {
        $error .= __( 'You should set Cost of registration.<br/>', WPC_CLIENT_TEXT_DOMAIN );
    } elseif ( !is_numeric( $cost ) ) {
        $error .= __( 'Cost should be numeric .<br/>', WPC_CLIENT_TEXT_DOMAIN );
    } elseif ( 0 >= $cost ) {
        $error .= __( 'Cost of registration should be more than 0<br/>', WPC_CLIENT_TEXT_DOMAIN );
    }

    $_POST['registration']['cost'] = $cost;

    if ( empty( $error ) ) {

        update_option( 'wpc_p_registration_settings', $_POST['registration'] );
        do_action('wp_client_redirect', 'admin.php?page=wpclients_payments&tab=registration_settings');
        exit;
    }
    $p_registration_settings = $_POST['registration'];

} else {
    $p_registration_settings = get_option( 'wpc_p_registration_settings' );
}


$settings = get_option( 'wpc_settings' );

if ( isset( $p_registration_settings['enable'] ) && '1' == $p_registration_settings['enable']
    && ( !isset( $p_registration_settings['gateways'] ) || 0 == count( $p_registration_settings['gateways'] ) )   ) {
    $error .= __( 'Note: The registration will not work until you select "Payment Gateways". Clients will see a message that "Registration temporarily unavailable".', WPC_CLIENT_TEXT_DOMAIN );
}

?>

<div style="" class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <?php
    if ( isset( $_GET['msg'] ) ) {
        $msg = $_GET['msg'];
        switch( $msg ) {
            case 'a':
                echo '<div id="message" class="updated fade"><p>' . __( 'Manager <strong>Added</strong> Successfully.', WPC_CLIENT_TEXT_DOMAIN ) . '</p></div>';
                break;
        }
    }
    ?>

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_payments_tabs_menu() ?>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <h2><?php _e( 'Registration Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></h2>

            <p><?php _e( 'From here, you can manage settings for paid registration.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>

            <div id="message" class="updated fade" <?php echo ( empty( $error ) ) ? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>

            <form method="post" action="">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="enable"><?php _e( 'Enable Paid Registration', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                            </th>
                            <td>
                                <select name="registration[enable]" id="enable" >
                                    <option value="0" <?php echo ( isset( $p_registration_settings['enable'] ) && '0' == $p_registration_settings['enable'] ) ? 'selected' : '' ?> ><?php _e( 'Disable', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                    <option value="1" <?php echo ( isset( $p_registration_settings['enable'] ) && '1' == $p_registration_settings['enable'] ) ? 'selected' : '' ?> ><?php _e( 'Enable', WPC_CLIENT_TEXT_DOMAIN ) ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label><?php _e( 'Payment Gateways', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                            </th>
                            <td>
                                <?php
                                foreach ( (array)$wpc_gateway_plugins as $code => $plugin ) {
                                    if ( isset( $settings['gateways']['allowed'] ) && in_array( $code, (array) $settings['gateways']['allowed'] ) ) {
                                        $checked = '';
                                        if ( isset( $p_registration_settings['gateways'] ) && in_array( $code, $p_registration_settings['gateways'] ) ) {
                                            $checked = 'checked';
                                        }
                                        echo '<label><input type="checkbox" name="registration[gateways][]" value="' . $code .'" ' . $checked .' /> ' . esc_attr( $plugin[1] ) . '</label><br>';
                                    }
                                }
                                ?>
                                <span class="description"><?php echo sprintf( __( 'To add or change payments gateway settings, please look in "%s"', WPC_CLIENT_TEXT_DOMAIN ), '<a href="admin.php?page=wpclients_payments&tab=payment_settings" >' . __( 'Payment Settings', WPC_CLIENT_TEXT_DOMAIN ) . '</a>' ) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="cost"><?php _e( 'Registration Cost', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                            </th>
                            <td>
                                <input type="text" name="registration[cost]" id="cost" value="<?php echo ( isset( $p_registration_settings['cost'] ) && '' != $p_registration_settings['cost'] ) ? $p_registration_settings['cost'] : '' ?>" />
                                <span class="description"><?php _e( 'Cost to register as client/member', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="cost"><?php _e( 'Payment Description', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label>
                            </th>
                            <td>
                                <textarea cols="90" rows="2" name="registration[description]" id="description" ><?php echo ( isset( $p_registration_settings['description'] ) && '' != $p_registration_settings['description'] ) ? $p_registration_settings['description'] : '' ?></textarea>
                                <br />
                                <span class="description"><?php _e( 'Will be displayed on the payment page.', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_settings" value="<?php _e( 'Save Changes', WPC_CLIENT_TEXT_DOMAIN ) ?>" />
                </p>
            </form>

        </div>
    </div>


    <script type="text/javascript">
        jQuery(document).ready(function(){

        });
    </script>



</div>