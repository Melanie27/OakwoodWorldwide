<?php
/*
Core for Payments
*/

//{{{PHP_ENCODE}}}

define( 'WPC_GATEWAYS_TD', 'wp-client-gateways' );

if ( !class_exists( 'wpc_payments_core' ) ) {

    class wpc_payments_core {

        var $wpc_gateway_plugins;
        var $checkout_error;
        var $ipnURL;
        var $returnURL;
        var $cancelURL;

        function wpc_payments_core() {
            __construct();
        }

        /**
        * PHP 5 constructor
        **/
        function __construct() {

            $this->ipnURL       = get_home_url() . '/' .wpc_client_get_slug( 'base', false ) . 'wpc-payment-return';
            $this->returnURL    = get_home_url() . '/' .wpc_client_get_slug( 'registration', false ) . '-step-confirm-checkout';
            $this->cancelURL    = get_home_url() . '/' .wpc_client_get_slug( 'registration', false ) . '-step-checkout';

            //load gateways
            add_action( 'plugins_loaded', array(&$this, 'load_gateway_plugins') );

            //Payment gateway returns
            add_action( 'pre_get_posts', array(&$this, 'handle_gateway_returns'), 1 );

        }


        /*
        *  returns a new unique order id
        */
        function generate_order_id() {
            global $wpdb;

            $count = true;
            while ( $count ) { //make sure it's unique
              $order_id = substr( sha1( uniqid( '' ) ), rand( 1, 24 ), 12 );
              $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpc_client_payments WHERE order_id = '" . $order_id . "'" );
            }

            //save it to session
            $_SESSION['wpc_payment']['order_id'] = $order_id;

            return $order_id;
        }


        /*
        * save order
        */
        function create_order( $order_id, $order_info, $payment_info, $paid, $client_id = false ) {
            global $wpdb;

            $order_status = ( $paid ) ? 'order_paid' : 'order_received';
            $transaction_status = serialize( $payment_info['status'] );

            $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wpc_client_payments SET
                order_id = %s,
                order_status = %s,
                function = %s,
                payment_method = '%s',
                client_id = '%d',
                amount = '%s',
                currency = '%s',
                transaction_id = '%s',
                transaction_status = '%s',
                time_created = '%s',
                time_paid = '%s'
                ",
                $order_id,
                $order_status,
                $_SESSION['wpc_payment']['function'],
                $_SESSION['wpc_payment']['payment_method'],
                $client_id,
                $payment_info['total'],
                $payment_info['currency'],
                $payment_info['transaction_id'],
                $transaction_status,
                $payment_info['order_time'],
                time()
            ) );

        }


        /*
        * called by payment gateways to update order statuses
        */
        function update_order_payment_status( $order_id, $status, $paid ) {
            global $wpdb;

            //get the order
            $order = $this->get_order( $order_id );
            if ( !$order )
                return false;

            $timestamp = time();
            $transaction_status = serialize( array( $timestamp => $status ) );

            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpc_client_payments SET transaction_status = '%s' WHERE order_id = '%s'", $transaction_status, $order_id ) );
            if ( $paid ) {
                if ( $order['order_status'] == 'order_received' ) {
                    $this->update_order_status( $order_id, 'paid');
                    //get new order data
                    $order = $this->get_order( $order_id );
                    //run action after paid
                    do_action( 'wpc_order_paid_' . $order['function'], $order );
                }
            } else {
                $this->update_order_status( $order_id, 'received' );
            }

            //return merged payment info
            return $transaction_status;
        }


        /*
        * Update order status
        */
        function update_order_status($order_id, $new_status) {
            global $wpdb;
            $statuses = array('received' => 'order_received', 'paid' => 'order_paid', 'closed' => 'order_closed' );
            if ( !array_key_exists( $new_status, $statuses ) )
                return false;

            //get the order
            $order = $this->get_order( $order_id );
            if ( !$order )
                return false;

            if ( $statuses[$new_status] == $order['order_status'] )
                return;

            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpc_client_payments SET order_status = '%s' WHERE order_id = '%s'", $statuses[$new_status], $order_id ) );
        }


        /*
        * get order
        */
        function get_order( $order_id ) {
            global $wpdb;

            if ( empty( $order_id ) )
              return false;

            $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpc_client_payments WHERE order_id = %s ", $order_id ), "ARRAY_A" );

            if ( $order )
                return $order;
            else
                return false;
        }


        /*
        *
        */
        function handle_gateway_returns( $wp_query ) {
            //listen for gateway IPN returns and tie them in to proper gateway plugin
            if ( isset( $wp_query->query_vars['wpc_page'] ) && 'paymentgateway' == $wp_query->query_vars['wpc_page']
            && isset( $wp_query->query_vars['wpc_page_value'] ) && '' != $wp_query->query_vars['wpc_page_value'] ) {
                do_action( 'wpc_handle_payment_return_' . $wp_query->query_vars['wpc_page_value'] );
                exit();
            }
        }


        /*
        *
        */
        function cart_checkout_error($msg, $context = 'checkout') {
            $msg = str_replace('"', '\"', $msg); //prevent double quotes from causing errors.
            $content = 'return "<div class=\"wpc_checkout_error\">' . $msg . '</div>";';
            add_action( 'wpc_checkout_error_' . $context, create_function( '', $content ) );
            $this->checkout_error = true;
        }


        /*
        *
        */
        function load_gateway_plugins() {
            global $wpc_client;
            //load gateway plugin API
            require_once( $wpc_client->plugin_dir . 'includes/gateways.php' );

            //save settings from screen. Put here to be before plugin is loaded
            if ( isset( $_POST['gateway_settings'] ) ) {
                $settings = get_option( 'wpc_settings' );

                //see if there are checkboxes checked
                if ( isset( $_POST['wpc_gateway'] ) ) {

                    //clear allowed array as it will be refilled
                    unset( $settings['gateways']['allowed'] );

                    //allow plugins to verify settings before saving
                    $settings = array_merge($settings, apply_filters('wpc_gateway_settings_filter', $_POST['wpc_gateway']));

                } else {
                    //blank array if no checkboxes
                    $settings['gateways']['allowed'] = array();
                }

                update_option( 'wpc_settings', $settings );
            }

            //get gateway plugins dir
            $dir = $wpc_client->plugin_dir . 'includes/payment_gateways/';

            //search the dir for files
            $gateway_plugins = array();
            if ( !is_dir( $dir ) )
                return;
            if ( ! $dh = opendir( $dir ) )
                return;
            while ( ( $plugin = readdir( $dh ) ) !== false ) {
                if ( substr( $plugin, -4 ) == '.php' )
                    $gateway_plugins[] = $dir . '/' . $plugin;
            }
            closedir( $dh );
            sort( $gateway_plugins );

            //include them suppressing errors
            foreach ($gateway_plugins as $file)
                include( $file );

            //load chosen plugin classes
            global $wpc_gateway_plugins, $wpc_gateway_active_plugins;
            $settings = get_option('wpc_settings');

            foreach ((array)$wpc_gateway_plugins as $code => $plugin) {
                $class = $plugin[0];
                if ( isset( $settings['gateways']['allowed'] ) && in_array($code, (array)$settings['gateways']['allowed']) && class_exists($class) && !$plugin[3] )
                    $wpc_gateway_active_plugins[] = new $class;
            }

        }


        /*
        * display Paymants pages
        */
        function payments_pages() {

            if ( !isset( $_GET['tab'] ) || 'history' == $_GET['tab'] )
                include 'forms/payments_history.php';
            elseif ( isset( $_GET['tab'] ) && 'payment_settings' == $_GET['tab'] )
                include 'forms/payment_settings.php';
            elseif ( isset( $_GET['tab'] ) && 'registrations' == $_GET['tab'] && defined( 'WPC_CLIENT_ADDON_PAID_REGISTRATION' ) )
                include 'addons/paid_registration/registrations.php';
            elseif ( isset( $_GET['tab'] ) && 'registration_settings' == $_GET['tab'] && defined( 'WPC_CLIENT_ADDON_PAID_REGISTRATION' ) )
                include 'addons/paid_registration/registration_settings.php';

        }


        /**
         * Gen tabs manu
         */
        function gen_payments_tabs_menu() {

            $tabs = '';
            $active = '';

            $active = ( !isset( $_GET['tab'] ) || 'history' == $_GET['tab'] ) ? 'class="wpc_color_tab_1 active"' : 'class="wpc_color_tab_1"';
            $tabs .= '<li id="tutorials" ' . $active . ' ><a href="admin.php?page=wpclients_payments&tab=history" >' . __( 'Payment History', WPC_CLIENT_TEXT_DOMAIN ) . '</a></li>';

            //paid registration
            if ( defined( 'WPC_CLIENT_ADDON_PAID_REGISTRATION' ) ) {
                $active = ( isset( $_GET['tab'] ) && 'registrations' == $_GET['tab'] ) ? 'class="wpc_color_tab_2 active"' : 'class="wpc_color_tab_2"';
                $tabs .= '<li id="tutorials" ' . $active . ' ><a href="admin.php?page=wpclients_payments&tab=registrations" >' . __( 'Registrations', WPC_CLIENT_TEXT_DOMAIN ) . '</a></li>';

                $active = ( isset( $_GET['tab'] ) && 'registration_settings' == $_GET['tab'] ) ? 'class="wpc_color_tab_2 active"' : 'class="wpc_color_tab_2"';
                $tabs .= '<li id="tutorials" ' . $active . ' ><a href="admin.php?page=wpclients_payments&tab=registration_settings" >' . __( 'Registration Settings', WPC_CLIENT_TEXT_DOMAIN ) . '</a></li>';

            }

            $active = ( isset( $_GET['tab'] ) && 'payment_settings' == $_GET['tab'] ) ? 'class="wpc_color_tab_1 active"' : 'class="wpc_color_tab_1"';
            $tabs .= '<li id="tutorials" ' . $active . ' ><a href="admin.php?page=wpclients_payments&tab=payment_settings" >' . __( 'Payment Settings', WPC_CLIENT_TEXT_DOMAIN ) . '</a></li>';


            return $tabs;
        }

    }

    $wpc_payments_core = new wpc_payments_core();

}


//{{{/PHP_ENCODE}}}

?>