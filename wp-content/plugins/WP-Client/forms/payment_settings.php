<?php
global $wpdb, $wpc_gateway_plugins;

$settings = get_option('wpc_settings');

//save settings
if ( isset( $_POST['gateway_settings'] ) ) {
  echo '<div class="updated fade"><p>' . __('Settings saved.', WPC_CLIENT_TEXT_DOMAIN) . '</p></div>';
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
        <style>
            .ui-tabs-vertical { width: 100%; }
            .ui-tabs-vertical .ui-tabs-nav { float: left; width: 16em; }
            .ui-tabs-vertical .ui-tabs-nav li { clear: left; width: 100%; border-bottom-width: 1px !important; border-right-width: 0 !important; margin: 0 -1px .2em 0; }
/*            .ui-tabs-vertical .ui-tabs-nav li a { display:block; }*/
            .ui-tabs-vertical .ui-tabs-nav li.ui-tabs-active { padding-bottom: 0; padding-right: .1em; border-right-width: 1px; border-right-width: 1px; }
            .ui-tabs-vertical .ui-tabs-panel { padding: 1em; float: right; width: 76em; padding-top: 0;}
        </style>



    <div class="clear"></div>

    <div id="container23">

        <ul class="menu">
            <?php echo $this->gen_payments_tabs_menu() ?>
        </ul>
        <span class="clear"></span>
        <div class="content23 news">

            <h2><?php _e( 'Payment Settings', WPC_CLIENT_TEXT_DOMAIN ) ?></h2>
            <p><?php _e( 'From here, you can manage payment gateways.', WPC_CLIENT_TEXT_DOMAIN ) ?></p>


            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery("input.wpc_allowed_gateways").change(function() {
                        jQuery("#wpc-gateways-form").submit();
                    });
                });
            </script>




            <div id="tabs">
                <form id="wpc-gateways-form" method="post" action="">
                    <input type="hidden" name="gateway_settings" value="1" />

                    <ul>
                        <?php
                        $i = 0;
                        foreach ((array)$wpc_gateway_plugins as $code => $plugin) {
                        ?>
                        <li>
                        <?php
                        if ( isset( $settings['gateways']['allowed'] ) && in_array( $code, (array) $settings['gateways']['allowed'] ) ) {
                            $i++;
                            echo '<input type="checkbox" class="wpc_allowed_gateways" name="wpc_gateway[gateways][allowed][]" value="' . $code .'" checked="checked" />';
                            echo ' <a href="#tabs-'. $i .'">' . esc_attr($plugin[1]) . '</a>';
                            $settings['gateways'][$code]['tab_id'] = $i;
                        } else {
                            echo '<input type="checkbox" class="wpc_allowed_gateways" name="wpc_gateway[gateways][allowed][]" value="' . $code .'"/> ' . esc_attr($plugin[1]) ;
                        }
                        ?>
                        </li>
                        <?php
                        }
                        ?>
                    </ul>

                    <?php
                    //for adding additional settings for a payment gateway plugin
                    do_action('wpc_gateway_settings', $settings);
                    ?>
                </form>
            </div>

            <div class="clear"></div>
        </div>
    </div>


    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery( "#tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
            jQuery( "#tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
        });
    </script>



</div>