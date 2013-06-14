<?php
ob_start();
?>

<form method="post" action="<?php echo wp_login_url(); ?>" id="loginform" name="loginform">
	<p>
		<label for="user_login"><?php _e( 'Username', WPC_CLIENT_TEXT_DOMAIN ) ?><br>
		<input type="text" tabindex="10" size="20" value="" class="input" id="user_login" name="log"></label>
	</p>
	<p>
		<label for="user_pass"><?php _e( 'Password', WPC_CLIENT_TEXT_DOMAIN ) ?><br>
		<input type="password" tabindex="20" size="20" value="" class="input" id="user_pass" name="pwd"></label>
	</p>
	<p class="forgetmenot"><label for="rememberme"><input type="checkbox" tabindex="90" value="forever" id="rememberme" name="rememberme"> <?php _e( 'Remember Me', WPC_CLIENT_TEXT_DOMAIN ) ?></label></p>
	<p class="submit">
		<input type="submit" tabindex="100" value="Log In" class="button-primary" id="wp-submit" name="wp-submit">
		<input type="hidden" value="" name="redirect_to">
		<input type="hidden" value="1" name="testcookie">
	</p>
</form>

<?php
$out2 = ob_get_contents();

ob_end_clean();
return $out2;
?>