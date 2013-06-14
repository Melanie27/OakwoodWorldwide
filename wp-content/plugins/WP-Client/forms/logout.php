<?php
ob_start();
?>
<a href="<?php echo wp_logout_url(); ?>"><?php _e( 'LOGOUT', WPC_CLIENT_TEXT_DOMAIN ) ?></a>

<?php
$out2 = ob_get_contents();

ob_end_clean();
return $out2;
?>