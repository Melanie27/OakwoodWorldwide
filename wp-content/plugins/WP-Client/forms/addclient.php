<?php
global $wpdb;

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
			'user_login'    => esc_attr( trim( $contact_username ) ),
			'nickname'      => esc_attr( trim( $contact_name ) ),
			'user_email'    => esc_attr( $contact_email ),
			'role'          => 'wpc_client',
			'first_name'    => esc_attr( trim( $business_name ) ),
			'contact_phone' => esc_attr( $contact_phone ),
            'send_password' => ( isset( $send_password ) ) ? esc_attr( $send_password ) : '',
			'admin_manager' => esc_attr( $admin_manager ),
		);

        //set custom fields
        if ( isset( $custom_fields ) )
            $userdata['custom_fields'] = $custom_fields;

		do_action('wp_clients_update', $userdata );
		do_action('wp_client_redirect', 'admin.php?page=wpclients&msg=a');

		exit;
	}
}

$groups = $this->get_groups();


//get managers
$args = array(
    'role'      => 'wpc_manager',
    'orderby'   => 'user_login',
    'order'     => 'ASC',
    'fields'    => array( 'ID','user_login' ),

);

$managers           = get_users( $args );

?>

<style type="text/css">

.wrap input[type=text] {
    width:400px;
}

.wrap input[type=password] {
    width:400px;
}

.wrap textarea {
    width:400px;
}

</style>

<div class='wrap'>

    <div class="wpc_logo"></div>
    <hr />

    <div class="clear"></div>

    <div id="container23">
        <ul class="menu">
            <?php echo $this->gen_tabs_menu( 'clients' ) ?>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

        <div id="message" class="updated fade" <?php echo ( empty( $error ) )? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>


                <form action="" method="post">
                 <table class="form-table">
                    <tr>
                        <td>
                            <label for="business_name"><?php _e( 'Business or Client Name', WPC_CLIENT_TEXT_DOMAIN ) ?> | <span style="font-size: x-small;"><?php _e( 'This value is used in the {name} placeholder that is used in HUB Templates, Portal Page Templates &amp; Emails', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label> <br/>
                            <input type="text" id="business_name" name="business_name" value="<?php if ( $error ) echo esc_html( $_REQUEST['business_name'] ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="contact_name"><?php _e( 'Contact Name', WPC_CLIENT_TEXT_DOMAIN ) ?> | <span style="font-size: x-small;"><?php _e( 'This value is used in the {contact_name} placeholder that is used in HUB Templates, Portal Page Templates &amp; Emails', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label> <br/>
                            <input type="text" id="contact_name" name="contact_name" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_name'] ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="contact_email"><?php _e( 'Email', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
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
                            <label for="contact_username"><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?> | <span style="font-size: x-small;"><?php _e( 'This value is used in the {user_name} placeholder that is used in HUB Templates, Portal Page Templates &amp; Emails', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label> <br/>
                            <input type="text" id="contact_username" name="contact_username" value="<?php if ( $error ) echo esc_html( $_REQUEST['contact_username'] ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="admin_manager"><?php _e( 'Admin Manager', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
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
                        </td>
                    </tr>

                    <?php if ( is_array( $groups ) && 0 < count( $groups ) ): ?>
                    <tr>
                        <td>
                            <label for="contact_username"><?php _e( 'Client Circles', WPC_CLIENT_TEXT_DOMAIN ) ?>:</label> <br/>
                            <ul class="groups_list">
                            <?php
                            $i = 0;
                            $n = 5;
                            foreach ( $groups as $group ):
                                if ( $i%$n == 0 && 0 != $i ) echo '</ul><ul class="groups_list">';

                                $checked  = '';
                                if ( ( isset( $_REQUEST['groups'] ) && in_array( $group['group_id'], $_REQUEST['groups'] ) ) || ( '1' == $group['auto_select'] && !$error ) )
                                    $checked = 'checked';

                                ?>
                                    <li>
                                        <label>
                                            <input type="checkbox" id="groups" name="groups[]" value="<?php echo $group['group_id'] ?>" <?php echo $checked; ?> />
                                            <?php echo $group['group_name'] ?>
                                        </label>

                                    </li>

                                <?php

                                $i++;
                            endforeach; ?>
                            </ul>
                            <br>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <tr>
                        <td>
                            <label for="contact_password"><?php _e( 'Password', WPC_CLIENT_TEXT_DOMAIN ) ?> | <span style="font-size: x-small;"><?php _e( 'This value is used in the {user_password} placeholder that is used in HUB Templates, Portal Page Templates &amp; Emails', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label> <br/>
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
                            <div class="description indicator-hint" style="clear:both"><?php _e( 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ & ).', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="send_password"><input type="checkbox" checked="checked" id="send_password" name="send_password"> <?php _e( 'Send this password to the new user by email.', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type='submit' name='btnAdd' id="btnAdd" class='button-primary' value='<?php _e( 'Add Client', WPC_CLIENT_TEXT_DOMAIN ) ?>' />
                            &nbsp; &nbsp; &nbsp;
                            <input type='reset' name='btnreset' class='button-secondary' value='<?php _e( 'Reset Form', WPC_CLIENT_TEXT_DOMAIN ) ?>' />
                        </td>
                    </tr>
                </table>
            </form>
        </div>
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