<?php

ob_start();

$wpc_settings = get_option( 'wpc_settings' );
if ( 'no' == $wpc_settings['client_registration'] ) {
    return __( 'Registration is disabled!', WPC_CLIENT_TEXT_DOMAIN );
}

//unset($_SESSION);

//paid registration
if ( defined( 'WPC_CLIENT_ADDON_PAID_REGISTRATION' ) ) {
    $p_registration_settings = get_option( 'wpc_p_registration_settings' );
    //paid registration enabled
    if ( isset( $p_registration_settings['enable'] ) && '1' == $p_registration_settings['enable'] ) {
        //if client created redirect on checkout page
        if ( isset( $_SESSION['wpc_payment']['client_id'] ) && 0 < $_SESSION['wpc_payment']['client_id'] ) {
            $step = ( isset( $_SESSION['wpc_payment']['step'] ) && 0 < $_SESSION['wpc_payment']['step'] ) ? $_SESSION['wpc_payment']['step'] : 'checkout';
            do_action( 'wp_client_redirect', get_home_url() . '/' . wpc_client_get_slug( 'registration', false ) . '-step-' . $step );
            exit;
        }
    }
}



extract($_REQUEST);

$error = "";

if ( isset( $btnAdd ) ) {

	// validate at php side
	if ( empty( $contact_name ) ) // empty username
		$error .= __('A Contact Name is required.<br/>', WPC_CLIENT_TEXT_DOMAIN);

	if ( empty( $contact_username ) ) // empty username
		$error .= __('A username is required.<br/>', WPC_CLIENT_TEXT_DOMAIN);

	if ( username_exists( $contact_username ) ) //  already exsits user name
		$error .= __('Sorry, that username already exists!<br/>', WPC_CLIENT_TEXT_DOMAIN);

	if ( email_exists( $contact_email ) ) // email already exists
		$error .= __('Sorry, that email address is already used!<br/>', WPC_CLIENT_TEXT_DOMAIN);

	if ( empty( $contact_password ) || empty( $contact_password2 ) ) {
			if ( empty( $contact_password ) ) // password
				$error .= __("Sorry, password is required.<br/>", WPC_CLIENT_TEXT_DOMAIN);
			elseif ( empty( $contact_password2 ) ) // confirm password
				$error .= __("Sorry, confirm password is required.<br/>", WPC_CLIENT_TEXT_DOMAIN);
			elseif ( $contact_password != $contact_password2 )
				$error .= __("Sorry, Passwords are not matched! .<br/>", WPC_CLIENT_TEXT_DOMAIN);
	}


	if ( empty( $error ) ) {
		$userdata = array(
			'user_pass'     => esc_attr ( $contact_password2 ),
			'user_login'    => esc_attr( $contact_username ),
			'nickname'      => esc_attr( $contact_name ),
			'user_email'    => esc_attr( $contact_email ),
            'role'          => 'wpc_client',
			'first_name'    => esc_attr( $business_name ),
            'contact_phone' => esc_attr( $contact_phone ),
            'send_password' => ( isset( $_REQUEST['user_data']['send_password'] ) ) ? esc_attr( $_REQUEST['user_data']['send_password'] ) : '',
		);

        //approve the new client
        if ( isset( $wpc_settings['auto_client_approve'] ) && '1' == $wpc_settings['auto_client_approve'] ) {
            $userdata['to_approve'] = 'auto';
        } else {
            $userdata['to_approve'] = '1';
        }

        //set custom fields
        if ( isset( $custom_fields ) )
            $userdata['custom_fields'] = $custom_fields;

		do_action( 'wp_clients_update', $userdata );

        do_action( 'wp_client_redirect', get_home_url() . '/' . wpc_client_get_slug( 'registration_successful' ) );
		exit;
	}
}

?>

<style type="text/css">

#form_content input[type=text] {
    width: 95%;
}

#form_content input[type=password] {
    width: 150px;
}

</style>

<div class='registration_form'>

    <div id="message" class="updated" <?php echo ( empty( $error ) )? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>

    <form action="" method="post" id="form_content" >
         <table class="form-table">
            <tr>
                <td>
                    <label for="business_name"><?php _e( 'Business or Client Name', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                    <input type="text" id="business_name" name="business_name" value="<?php if ( $error ) echo esc_html( $_REQUEST['business_name'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <label for="contact_name"><?php _e( 'Contact Name', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                    <input type="text" id="contact_name" name="contact_name" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_name'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <label for="contact_email"><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>:</label> <br/>
                    <input type="text" id="contact_email" name="contact_email" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_email'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <label for="contact_phone"><?php _e( 'Phone', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                    <input type="text" id="contact_phone" name="contact_phone" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_phone'] ); ?>" />
                </td>
            </tr>

            <?php wpc_client_get_custom_fields() ?>

            <tr>
                <td>
                    <hr />
                    <label for="contact_username"><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>:</label> <br/>
                    <input type="text" id="contact_username" name="contact_username" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_username'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <label for="contact_password"><?php _e( 'Password', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span>:</label> <br/>
                    <input type="password" id="contact_password" name="contact_password" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_password'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <label for="contact_password2"><?php _e( 'Confirm Password', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                    <input type="password" id="contact_password2" name="contact_password2" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_password2'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <div id="pass-strength-result" style="display: block;"><?php _e( 'Strength indicator', WPC_CLIENT_TEXT_DOMAIN ) ?></div>
			<hr />
                    <span class="description indicator-hint" style="clear:both"><?php _e( '>> HINT: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ & ).', WPC_CLIENT_TEXT_DOMAIN ) ?></span>
                </td>
            </tr>
            <tr>
                <td scope="row">
                <label for="send_password">>> <?php _e( 'Send this password to email?', WPC_CLIENT_TEXT_DOMAIN ) ?> >> <input type="checkbox" name="user_data[send_password]" id="send_password" /> Check to Enable</label>
		<hr />
                </td>
            </tr>
            <tr>
                <td>
                    <input type='submit' name='btnAdd' id="btnAdd" class='button-primary' value='<?php _e( 'Submit Registration', WPC_CLIENT_TEXT_DOMAIN ) ?>' />
                </td>
            </tr>
        </table>
    </form>
</div>

<script type="text/javascript" language="javascript">

    jQuery( document ).ready( function( $ ) {

	    <?php echo ( empty( $error ) )? '$( "#message" ).hide();' : '' ?>

	    $( "#btnAdd" ).live ( 'click', function() {

		    var msg = '';

		    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;

		    if ( $( "#business_name" ).val() == '' ) {
			    msg += "<?php _e( 'Business Name required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    }

		    if ( $( "#contact_name" ).val() == '' ) {
			    msg += "<?php _e( 'Contact Name required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    }

		    if ( $( "#contact_email" ).val() == '' ) {
			    msg += "<?php _e( 'Contact Email required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    } else if ( !emailReg.test( $( "#contact_email" ).val() ) ) {
			    msg += "<?php _e( 'Invalid Contact Email.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    }

		    if ( $( "#contact_password" ).val() == '' ) {
			    msg += "<?php _e( 'Password required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    } else if ( $( "#contact_password2" ).val() == '' ) {
			    msg += "<?php _e( 'Confirm Password required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    } else if ( $( "#contact_password" ).val() != $( "#contact_password2" ).val() ) {
			    msg += "<?php _e( 'Passwords are not matched.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    }

		    if ( msg != '' ) {
			    $( "#message" ).html( msg );
			    $( "#message" ).show();
			    return false;
		    }
	    });
    });

</script>

<script type="text/javascript">

    /* <![CDATA[ */

    pwsL10n={
	    empty: "<?php _e( 'Strength Indicator', WPC_CLIENT_TEXT_DOMAIN ) ?>",
	    short: "<?php _e( 'Too Short', WPC_CLIENT_TEXT_DOMAIN ) ?>",
	    bad: "<?php _e( 'Bad Password', WPC_CLIENT_TEXT_DOMAIN ) ?>",
	    good: "<?php _e( 'Good Password', WPC_CLIENT_TEXT_DOMAIN ) ?>",
	    strong: "<?php _e( 'Strong Password', WPC_CLIENT_TEXT_DOMAIN ) ?>",
	    mismatch: "<?php _e( 'Password Mismatch', WPC_CLIENT_TEXT_DOMAIN ) ?>"
    }

    /* ]]> */

    function check_pass_strength() {

	    var contact_password = jQuery("#contact_password").val(), user = jQuery("#contact_name").val(), contact_password2 = jQuery("#contact_password2").val(), strength;

	    jQuery("#pass-strength-result").removeClass("short bad good strong mismatch");

	    if ( !contact_password ) {
		    jQuery("#pass-strength-result").html( pwsL10n.empty );
		    return;
	    }

	    strength = passwordStrength(contact_password, user, contact_password2);

	    switch ( strength ) {
		    case 2:
			    jQuery("#pass-strength-result").addClass("bad").html( pwsL10n["bad"] );
			    break;

		    case 3:
			    jQuery("#pass-strength-result").addClass("good").html( pwsL10n["good"] );
			    break;

		    case 4:
			    jQuery("#pass-strength-result").addClass("strong").html( pwsL10n["strong"] );
			    break;

		    case 5:
			    jQuery("#pass-strength-result").addClass("mismatch").html( pwsL10n["mismatch"] );
			    break;

		    default:
			    jQuery("#pass-strength-result").addClass("short").html( pwsL10n["short"] );
	    }
    }

    function passwordStrength(password1, username, password2) {

	    var shortPass = 1, badPass = 2, goodPass = 3, strongPass = 4, mismatch = 5, symbolSize = 0, natLog, score;

	    // password 1 != password 2
	    if ( (password1 != password2) && password2.length > 0 )
		    return mismatch

	    //password < 4
	    if ( password1.length < 4 )
		    return shortPass

	    //password1 == username
	    if ( password1.toLowerCase() == username.toLowerCase() )
		    return badPass;

	    if ( password1.match(/[0-9]/) )
		    symbolSize +=10;

	    if ( password1.match(/[a-z]/) )
		    symbolSize +=26;

	    if ( password1.match(/[A-Z]/) )
		    symbolSize +=26;

	    if ( password1.match(/[^a-zA-Z0-9]/) )
		    symbolSize +=31;

	    natLog = Math.log( Math.pow(symbolSize, password1.length) );

		score = natLog / Math.LN2;

	    if ( score < 40 )
		    return badPass

	    if ( score < 56 )
		    return goodPass

	    return strongPass;
    }

    jQuery(document).ready( function() {
	    jQuery("#contact_password").val("").keyup( check_pass_strength );
	    jQuery("#contact_password2").val("").keyup( check_pass_strength );
    });

</script>


<?php

$out3 = ob_get_contents();

ob_end_clean();

return $out3;
?>
