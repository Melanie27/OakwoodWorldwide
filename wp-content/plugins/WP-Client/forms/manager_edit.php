<?php

$error = "";


//save user data
if ( isset( $_REQUEST['update_user'] ) ) {

	// validate at php side

    //empty username
	if ( empty( $_REQUEST['manager_data']['user_login'] ) )
		$error .= __( 'A username is required.<br/>', WPC_CLIENT_TEXT_DOMAIN );

    if ( !isset( $_REQUEST['manager_data']['ID'] ) ) {
    //already exsits user name
	if ( username_exists( $_REQUEST['manager_data']['user_login'] ) )
		$error .= __( 'Sorry, that username already exists!<br/>', WPC_CLIENT_TEXT_DOMAIN );
    }

    // email already exists
    if ( email_exists( $_REQUEST['manager_data']['email'] ) ) {
        if ( !isset( $_REQUEST['manager_data']['ID'] ) || $_REQUEST['manager_data']['ID'] != get_user_by( 'email',  $_REQUEST['manager_data']['email'] )->ID ) {
            // email already exist
            $error .= __( 'Email address already uses.<br/>', WPC_CLIENT_TEXT_DOMAIN );
        }
    }

    if ( !isset( $_REQUEST['manager_data']['ID'] ) || ( isset( $_REQUEST['update_password'] ) && '1' == $_REQUEST['update_password'] ) ) {
	    if ( empty( $_REQUEST['manager_data']['pass1'] ) || empty( $_REQUEST['manager_data']['pass2'] ) ) {
			    if ( empty( $_REQUEST['manager_data']['pass1'] ) ) // password
				    $error .= __( 'Sorry, password is required.<br/>', WPC_CLIENT_TEXT_DOMAIN );
			    elseif ( empty( $_REQUEST['manager_data']['pass2'] ) ) // confirm password
				    $error .= __( 'Sorry, confirm password is required.<br/>', WPC_CLIENT_TEXT_DOMAIN );
			    elseif ( $_REQUEST['manager_data']['pass1'] != $_REQUEST['manager_data']['pass2'] )
				    $error .= __( 'Sorry, Passwords are not matched! .<br/>', WPC_CLIENT_TEXT_DOMAIN );
	    }
    }


	if ( empty( $error ) ) {

		$userdata = array(
			'user_pass'     => esc_attr( $_REQUEST['manager_data']['pass2'] ),
			'user_login'    => esc_attr( $_REQUEST['manager_data']['user_login'] ),
			'user_email'    => esc_attr( $_REQUEST['manager_data']['email'] ),
            'first_name'    => esc_attr( $_REQUEST['manager_data']['first_name'] ),
			'last_name'     => esc_attr( $_REQUEST['manager_data']['last_name'] ),
            'send_password' => ( isset( $_REQUEST['manager_data']['send_password'] ) ) ? esc_attr( $_REQUEST['manager_data']['send_password'] ) : '',
		);

        if ( isset( $_REQUEST['manager_data']['ID'] ) ) {
            $userdata['ID'] = $_REQUEST['manager_data']['ID'];
        } else {
            $userdata['role'] = 'wpc_manager';
        }

        if ( isset( $_REQUEST['manager_data']['ID'] ) && !isset( $_REQUEST['update_password'] ) ) {
            unset( $userdata['user_pass'] );
        }


        if ( !isset( $userdata['ID'] ) ) {
            //insert new manager
            $manager_id = wp_insert_user( $userdata );

            if ( 'on' == $userdata['send_password'] || '1' == $userdata['send_password'] ) {

                //get email template
                $wpc_templates = get_option( 'wpc_templates' );

                $subject = $wpc_templates['emails']['manager_created']['subject'];

                $message = stripslashes( $wpc_templates['emails']['manager_created']['body'] );
                $message = str_replace( '{contact_name}', $userdata['first_name'], $message );
                $message = str_replace( '{user_name}', $userdata['user_login'], $message );
                $message = str_replace( '{user_password}', $userdata['user_pass'], $message );
                $message = str_replace( '{admin_url}', get_admin_url(), $message );

                $headers = "From: " . get_option("sender_name") . " <" . get_option("sender_email") . "> \r\n";
                $headers .= "Reply-To: " . get_option("admin_email") . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

                wp_mail( $userdata['user_email'], $subject, $message, $headers );
            }

        } else {
            //update manager data
            wp_update_user( $userdata );
            $manager_id = $userdata['ID'];
        }

        /*
        * assign clients to manager
        */
        //get new clients
        if ( isset( $_REQUEST['manager_data']['clients_id'] ) ) {
            $new_clients_manager = $_REQUEST['manager_data']['clients_id'];
        } else {
            $new_clients_manager = array();
        }
        //get old clients
        $clients_this_manager = get_users( array( 'role' => 'wpc_client', 'meta_key' => 'admin_manager', 'meta_value' => $manager_id, 'fields' => 'ID', ) );
        //reset manager for clients
        if ( is_array( $clients_this_manager ) && 0 < count( $clients_this_manager ) ) {
            foreach( $clients_this_manager as $client_id ) {
                update_user_meta( $client_id, 'admin_manager', '0' );
            }
        }
        //set manager for clients
        if ( is_array( $new_clients_manager ) && 0 < count( $new_clients_manager ) ) {
            foreach( $new_clients_manager as $client_id ) {
                update_user_meta( $client_id, 'admin_manager', $manager_id );
            }
        }


        //redirect
        if ( isset( $_REQUEST['manager_data']['ID'] ) )
		    do_action( 'wp_client_redirect', 'admin.php?page=wpclients_managers&msg=u' );
        else
            do_action( 'wp_client_redirect', 'admin.php?page=wpclients_managers&msg=a' );

		exit;
	}
}


//get manager data
if ( isset( $_REQUEST['manager_data'] ) ) {
    $manager_data = $_REQUEST['manager_data'];
} elseif ( 'edit' == $_GET['tab'] ) {
    $manager = get_userdata( $_GET['id'] );
    $manager_data['ID']         = $manager->data->ID;
    $manager_data['user_login'] = $manager->data->user_login;
    $manager_data['email']      = $manager->data->user_email;
    $manager_data['first_name'] = get_user_meta( $manager->data->ID, 'first_name', true );
    $manager_data['last_name']  = get_user_meta( $manager->data->ID, 'last_name', true );
}


//change text
if ( 'add' == $_GET['tab'] )
    $button_text = __( 'Add new Manager', WPC_CLIENT_TEXT_DOMAIN );
else
    $button_text = __( 'Update Manager', WPC_CLIENT_TEXT_DOMAIN );


//get assigned clients for manager
if ( isset( $manager_data['ID'] ) )
    $clients_this_manager = get_users( array( 'role' => 'wpc_client', 'meta_key' => 'admin_manager', 'meta_value' => $manager_data['ID'], 'fields' => 'ID', ) );
else
    $clients_this_manager = array();


$clients_with_manager    = get_users( array( 'role' => 'wpc_client', 'meta_key' => 'admin_manager', 'meta_value' => '0', 'meta_compare' => '>', 'fields' => 'ID', ) );
$clients_another_manager = array_diff( $clients_with_manager, $clients_this_manager );


//get all clients
$args = array(
    'role'      => 'wpc_client',
    'orderby'   => 'user_login',
    'order'     => 'ASC',
    'fields'    => array( 'ID', 'user_login' ),

);

$clients = get_users( $args );


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

    <div id="container23">
        <ul class="menu">
            <li id="news"  <?php echo ( isset( $_GET['tab'] ) && 'edit' == $_GET['tab'] ) ? 'class="active"' : '' ?> ><a href="admin.php?page=wpclients_managers" ><?php _e( 'Managers', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
            <li id="tutorials"  <?php echo ( isset( $_GET['tab'] ) && 'add' == $_GET['tab'] ) ? 'class="active"' : '' ?>><a href="admin.php?page=wpclients_managers&tab=add" ><?php _e( 'Add Manager', WPC_CLIENT_TEXT_DOMAIN ) ?></a></li>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <div id="message" class="updated fade" <?php echo ( empty( $error ) )? 'style="display: none;" ' : '' ?> ><?php echo $error; ?></div>

            <h3><?php echo $button_text ?>:</h3>
            <form name="edit_manager" id="edit_manager" method="post" >
                <?php if ( 'edit' == $_GET['tab'] ): ?>
                <input type="hidden" name="manager_data[ID]" value="<?php echo ( isset( $manager_data['ID'] ) ) ? $manager_data['ID'] : ''  ?>" />
                <input type="hidden" name="manager_data[user_login]" value="<?php echo ( isset( $manager_data['user_login'] ) ) ? $manager_data['user_login'] : ''  ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="user_login"><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                            </th>
                            <td>
                                <?php if ( 'add' == $_GET['tab'] ): ?>
                                    <input type="text" name="manager_data[user_login]" id="user_login" value="<?php echo ( isset( $manager_data['user_login'] ) ) ? $manager_data['user_login'] : ''  ?>" />
                                <?php else: ?>
                                    <input type="text" disabled id="user_login" value="<?php echo ( isset( $manager_data['user_login'] ) ) ? $manager_data['user_login'] : ''  ?>" />
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email"><?php _e( 'E-mail', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                            </th>
                            <td>
                                <input type="text" name="manager_data[email]" id="email" value="<?php echo ( isset( $manager_data['email'] ) ) ? $manager_data['email'] : ''  ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="first_name"><?php _e( 'First Name', WPC_CLIENT_TEXT_DOMAIN ) ?> </label>
                            </th>
                            <td>
                                <input type="text" name="manager_data[first_name]" id="first_name" value="<?php echo ( isset( $manager_data['first_name'] ) ) ? $manager_data['first_name'] : ''  ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="last_name"><?php _e( 'Last Name', WPC_CLIENT_TEXT_DOMAIN ) ?> </label>
                            </th>
                            <td>
                                <input type="text" name="manager_data[last_name]" id="last_name" value="<?php echo ( isset( $manager_data['last_name'] ) ) ? $manager_data['last_name'] : ''  ?>" />
                            </td>
                        </tr>
                        <?php if ( 'add' == $_GET['tab'] ) : ?>
                        <tr>
                            <th scope="row">
                                <label for="send_password"><?php _e( 'Send Password?', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                            </th>
                            <td>
                                <label for="send_password"><input type="checkbox" name="manager_data[send_password]" id="send_password" /> <?php _e( 'Send this password to the new user by email.', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                            </td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <th scope="row">
                                <label for="send_password"><?php _e( 'Update Password?', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                            </th>
                            <td>
                                <label for="send_password"><input type="checkbox" name="update_password" value="1" id="update_password" /> <?php _e( 'Checking this box will change the password.', WPC_CLIENT_TEXT_DOMAIN ) ?></label>
                            </td>
                        </tr>
                        <?php endif; ?>


                        <tr>
                            <th scope="row">
                                <label for="pass1"><?php _e( 'Password', WPC_CLIENT_TEXT_DOMAIN ) ?> <span class="description"><?php _e( '(twice, required)', WPC_CLIENT_TEXT_DOMAIN ) ?></span></label>
                            </th>
                            <td>
                                <input type="password" name="manager_data[pass1]" autocomplete="off" id="pass1" value="" />
                                <br>
                                <input type="password" name="manager_data[pass2]" autocomplete="off" id="pass2" value="" />
                                <br>
                                <div id="pass-strength-result" style="display: block;"><?php _e( 'Strength indicator', WPC_CLIENT_TEXT_DOMAIN ) ?></div>
                                <p class="description indicator-hint"><?php _e( 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', WPC_CLIENT_TEXT_DOMAIN ) ?></p>
                            </td>
                        </tr>

                        <?php if ( is_array( $clients ) && 0 < count( $clients ) ): ?>
                        <tr>
                            <td colspan="2">
                                <h4><?php _e( 'Assign Clients', WPC_CLIENT_TEXT_DOMAIN ) ?>:</h4>
                                <label>
                                    <?php _e( 'Select all', WPC_CLIENT_TEXT_DOMAIN ) ?>
                                    <input type="checkbox" id="select_all" value="all" />
                                </label>
                                <br>

                                <ul class="groups_list">
                                <?php
                                $i = 0;
                                $n = 5;

                                foreach ( $clients as $client ) {
                                    if ( $i%$n == 0 && 0 != $i ) echo '</ul><ul class="groups_list">';

                                    $checked    = '';
                                    $text       = '';

                                    //checked selected clients
                                    if ( ( isset( $_REQUEST['manager_data']['clients_id'] ) && in_array( $client->ID, $_REQUEST['manager_data']['clients_id'] ) ) ) {
                                        $checked = 'checked';
                                    } elseif ( ( isset( $clients_this_manager ) && in_array( $client->ID, $clients_this_manager ) ) ) {
                                        $checked = 'checked';
                                    }

                                    //mark clients with another manager
                                    if ( ( isset( $clients_another_manager ) && in_array( $client->ID, $clients_another_manager ) ) ) {
										$user_manager_info = get_userdata(get_user_meta($client->ID, 'admin_manager', true ));
										$text = '<span style="color: #ff0000;">' . $client->user_login . ' (' . $user_manager_info->user_login . ')</span>';

                                    } else {
                                        $text = $client->user_login;
                                    }

                                    echo '
                                        <li><label>
                                                <input type="checkbox" name="manager_data[clients_id][]" value="' . $client->ID . '" ' . $checked . ' />
                                                ' . $text . '
                                        </label></li>';

                                    $i++;
                                }
                                ?>
                                </ul>
                            </td>

                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                        <input type="submit" value="<?php echo $button_text ?>" class="button-primary" id="update_user" name="update_user">
                </p>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">

    jQuery( document ).ready( function( $ ) {

        //Select/Un-select all clients
        jQuery( "#select_all" ).change( function() {
            if ( 'checked' == jQuery( this ).attr( 'checked' ) ) {
                jQuery( '#edit_manager input[name="manager_data[clients_id][]"]' ).attr( 'checked', true );
            } else {
                jQuery( '#edit_manager input[name="manager_data[clients_id][]"]' ).attr( 'checked', false );
            }
        });


	    <?php echo ( empty( $error ) )? '$( "#message" ).hide();' : '' ?>

	    $( "#update_user" ).live ( 'click', function() {

		    var msg = '';

		    var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;

		    if ( $( "#user_login" ).val() == '' ) {
			    msg += "<?php _e( 'A username is required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    }

		    if ( $( "#email" ).val() == '' ) {
			    msg += "<?php _e( 'Email required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    } else if ( !emailReg.test( $( "#email" ).val() ) ) {
			    msg += "<?php _e( 'Invalid Email.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
		    }


            if ( $( '#update_password' ).length == 0 || $( "#update_password" ).is(':checked') ) {
                if ( $( "#pass1" ).val() == '' ) {
                    msg += "<?php _e( 'Password required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                } else if ( $( "#pass2" ).val() == '' ) {
                    msg += "<?php _e( 'Confirm Password required.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                } else if ( $( "#pass1" ).val() != $( "#pass2" ).val() ) {
                    msg += "<?php _e( 'Passwords are not matched.', WPC_CLIENT_TEXT_DOMAIN ) ?><br/>";
                }
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

	    var pass1 = jQuery("#pass1").val(), user = jQuery("#user_login").val(), pass2 = jQuery("#pass2").val(), strength;

	    jQuery("#pass-strength-result").removeClass("short bad good strong mismatch");

	    if ( !pass1 ) {
		    jQuery("#pass-strength-result").html( pwsL10n.empty );
		    return;
	    }

	    strength = passwordStrength(pass1, user, pass2);

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
	    jQuery("#pass1").val("").keyup( check_pass_strength );
	    jQuery("#pass2").val("").keyup( check_pass_strength );
    });

</script>
