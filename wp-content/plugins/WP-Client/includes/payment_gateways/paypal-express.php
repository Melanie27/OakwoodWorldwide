<?php
/*
PayPal Express Gateway
*/

class WPC_Gateway_Paypal_Express extends WPC_Gateway_API {

  //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'paypal-express';

  //name of your gateway, for the admin side.
  var $admin_name = '';

  //public name of your gateway, for lists and such.
  var $public_name = '';

  //url for an image for your checkout method. Displayed on checkout form if set
  var $method_img_url = '';

  //url for an submit button image for your checkout method. Displayed on checkout form if set
  var $method_button_img_url = '';

  //whether or not ssl is needed for checkout page
  var $force_ssl = false;

  //always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
  var $ipn_url;

  //whether if this is the only enabled gateway it can skip the payment_form step
  var $skip_form = true;

  //only required for global capable gateways. The maximum stores that can checkout at once
  var $max_stores = 10;

  // Payment action
  var $payment_action = 'Sale';

  //paypal vars
  var $API_Username, $API_Password, $API_Signature, $SandboxFlag, $returnURL, $cancelURL, $API_Endpoint, $paypalURL, $version, $currencyCode, $locale;

  /****** Below are the public methods you may overwrite via a plugin ******/

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
    global $wpc_payments_core;
    $settings = get_option( 'wpc_settings' );

    //set names here to be able to translate
    $this->admin_name = __('PayPal Express Checkout', WPC_GATEWAYS_TD);
    $this->public_name = __('PayPal', WPC_GATEWAYS_TD);

    //dynamic button img, see: https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_ECButtonIntegration
    $this->method_img_url = 'https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&buttontype=ecmark&locale=' . get_locale();
    $this->method_button_img_url = 'https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&locale=' . get_locale();

    //set paypal vars

    $this->API_Username     = ( isset( $settings['gateways']['paypal-express']['api_user'] ) ) ? $settings['gateways']['paypal-express']['api_user'] : '';
    $this->API_Password     = ( isset( $settings['gateways']['paypal-express']['api_pass'] ) ) ? $settings['gateways']['paypal-express']['api_pass'] : '';
    $this->API_Signature    = ( isset( $settings['gateways']['paypal-express']['api_sig'] ) ) ? $settings['gateways']['paypal-express']['api_sig'] : '';
    $this->currencyCode     = ( isset( $settings['gateways']['paypal-express']['currency'] ) ) ? $settings['gateways']['paypal-express']['currency'] : '';
    $this->locale           = ( isset( $settings['gateways']['paypal-express']['locale'] ) ) ? $settings['gateways']['paypal-express']['locale'] : '';
    $this->ipn_url          = $wpc_payments_core->ipnURL . '/paypal-express';
    $this->returnURL        = $wpc_payments_core->returnURL;
  	$this->cancelURL        = $wpc_payments_core->cancelURL;
    $this->version          = "93"; //api version

    //set api urls
  	if ( !isset( $settings['gateways']['paypal-express']['mode'] ) || $settings['gateways']['paypal-express']['mode'] == 'sandbox' )	{
  		$this->API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
  		$this->paypalURL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
  	} else {
  		$this->API_Endpoint = "https://api-3t.paypal.com/nvp";
  		$this->paypalURL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
    }
  }

	/**
   * Echo fields you need to add to the payment screen, like your credit card info fields.
   *  If you don't need to add form fields set $skip_form to true so this page can be skipped
   *  at checkout.
   *
   * @param array $order_info. Contains order info and email in case you need it
   */
  function payment_form( $order_info) {
    if (isset($_GET['cancel']))
      echo '<div class="wpc_checkout_error">' . __('Your PayPal transaction has been canceled.', WPC_GATEWAYS_TD) . '</div>';
  }



  /**
   * Use this to process any fields you added. Use the $_POST global,
   *  and be sure to save it to both the $_SESSION and usermeta if logged in.
   *  DO NOT save credit card details to usermeta as it's not PCI compliant.
   *  Call $wpc_payments_core->cart_checkout_error($msg, $context); to handle errors. If no errors
   *  it will redirect to the next step.
   *
   * @param array $order_info. Contains order info and email in case you need it
   */
  function process_payment_form($order_info) {
    global $wpc_payments_core;

    //create order id for paypal invoice
    $order_id = $wpc_payments_core->generate_order_id();

    //set it up with PayPal
    $result = $this->SetExpressCheckout( $order_info, $order_id);

    //check response
    if($result["ACK"] == "Success" || $result["ACK"] == "SuccessWithWarning")	{
      $token = urldecode($result["TOKEN"]);
      $this->RedirectToPayPal($token);
    } else { //whoops, error
        $error = '';
        for ($i = 0; $i <= 5; $i++) { //print the first 5 errors
            if (isset($result["L_ERRORCODE$i"])) {
                $error .= "<li>{$result["L_ERRORCODE$i"]} - {$result["L_SHORTMESSAGE$i"]} - {$result["L_LONGMESSAGE$i"]}</li>";
            }
        }

      $error = '<br /><ul>' . $error . '</ul>';
      $wpc_payments_core->cart_checkout_error( __('There was a problem connecting to PayPal to setup your purchase. Please try again.', WPC_GATEWAYS_TD) . $error );
    }
  }

  /**
   * Return the chosen payment details here for final confirmation. You probably don't need
   *  to post anything in the form as it should be in your $_SESSION var already.
   *
   * @param array $order_info. Contains order info and email in case you need it
   */
  function confirm_payment_form( $order_info ) {

    $content = '';

    if (isset($_GET['token']) && isset($_GET['PayerID'])) {
      $_SESSION['wpc_payment']['token'] = $_GET['token'];
      $_SESSION['wpc_payment']['PayerID'] = $_GET['PayerID'];

      //get details from PayPal
      $result = $this->GetExpressCheckoutDetails($_SESSION['wpc_payment']['token']);

      //check response
  		if($result["ACK"] == "Success" || $result["ACK"] == "SuccessWithWarning")	{

        $account_name = ( isset( $result["BUSINESS"] ) ) ? $result["BUSINESS"] : $result["EMAIL"];
        //set final amount

        $_SESSION['wpc_payment']['final_amt']              = $result['PAYMENTREQUEST_0_AMT'];
        $_SESSION['wpc_payment']['CURRENCY_CODE']          = $result['CURRENCYCODE'];
        $_SESSION['wpc_payment']['prs']                    = $result['PAYMENTREQUEST_0_PAYMENTREQUESTID'];
        $_SESSION['wpc_payment']['ipns']                   = ( isset( $result["PAYMENTREQUEST_0_NOTIFYURL"] ) ) ? $result["PAYMENTREQUEST_0_NOTIFYURL"] : '';
        $_SESSION['wpc_payment']['seller_paypal_accounts'] = ( isset( $result["PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID"] ) ) ? $result["PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID"] : '';

        //print payment details
        $content .= '<p>' . sprintf(__('Please confirm your final payment for this order totaling %s. It will be made via your "%s" PayPal account.', WPC_GATEWAYS_TD), $_SESSION['wpc_payment']['final_amt'] . ' ' . $_SESSION['wpc_payment']['CURRENCY_CODE'], $account_name) . '</p>';

  		} else { //whoops, error
        $error = '';
        for ($i = 0; $i <= 5; $i++) { //print the first 5 errors
          if (isset($result["L_ERRORCODE$i"]))
            $error .= "<li>{$result["L_ERRORCODE$i"]} - {$result["L_SHORTMESSAGE$i"]} - {$result["L_LONGMESSAGE$i"]}</li>";
        }

        $error = '<br /><ul>' . $error . '</ul>';
        $content .= '<div class="wpc_checkout_error">' . sprintf(__('There was a problem with your PayPal transaction. Please <a href="%s">go back and try again</a>.', WPC_GATEWAYS_TD), get_home_url() . '/' . wpc_client_get_slug( 'registration', false ) . '-step-checkout' ) . $error . '</div>';
      }

    } else {
      $content .= '<div class="wpc_checkout_error">' . sprintf(__('Whoops, looks like you skipped a step! Please <a href="%s">go back and try again</a>.', WPC_GATEWAYS_TD), get_home_url() . '/' . wpc_client_get_slug( 'registration', false ) . '-step-checkout' ) . '</div>';
    }

    return $content;
  }

  /**
   * Use this to do the final payment. Create the order then process the payment. If
   *  you know the payment is successful right away go ahead and change the order status
   *  as well.
   *  Call $wpc_payments_core->cart_checkout_error($msg, $context); to handle errors. If no errors
   *  it will redirect to the next step.
   *
   * @param array $order_info. Contains order info and email in case you need it
   */
  function process_payment($order_info) {
    global $wpc_payments_core;

    if (isset($_SESSION['wpc_payment']['token']) && isset($_SESSION['wpc_payment']['PayerID']) && isset($_SESSION['wpc_payment']['final_amt'])) {
      //attempt the final payment
      $result = $this->DoExpressCheckoutPayment($_SESSION['wpc_payment']['token'], $_SESSION['wpc_payment']['PayerID'], $_SESSION['wpc_payment']['final_amt'], $_SESSION['wpc_payment']['seller_paypal_accounts'], $_SESSION['wpc_payment']['ipns'], $_SESSION['wpc_payment']['prs']);

      //check response
      if($result["ACK"] == "Success" || $result["ACK"] == "SuccessWithWarning")	{

        //setup our payment details
  		$payment_info['gateway_public_name'] = $this->public_name;
        $payment_info['gateway_private_name'] = $this->admin_name;
				for ($i=0; $i<10; $i++) {
				  if (!isset($result['PAYMENTINFO_'.$i.'_PAYMENTTYPE'])) {
				    continue;
				  }
				  $payment_info['method'] = ($result["PAYMENTINFO_{$i}_PAYMENTTYPE"] == 'echeck') ? __('eCheck', WPC_GATEWAYS_TD) : __('PayPal balance, Credit Card, or Instant Transfer', WPC_GATEWAYS_TD);
				  $payment_info['transaction_id'] = $result["PAYMENTINFO_{$i}_TRANSACTIONID"];

				  $timestamp = strtotime($result["PAYMENTINFO_{$i}_ORDERTIME"]);
				  //setup status
				  switch ($result["PAYMENTINFO_{$i}_PAYMENTSTATUS"]) {
				    case 'Canceled-Reversal':
				      $status = __('A reversal has been canceled; for example, when you win a dispute and the funds for the reversal have been returned to you.', WPC_GATEWAYS_TD);
				      $paid = true;
				      break;
				    case 'Expired':
				      $status = __('The authorization period for this payment has been reached.', WPC_GATEWAYS_TD);
				      $paid = false;
				      break;
				    case 'Voided':
				      $status = __('An authorization for this transaction has been voided.', WPC_GATEWAYS_TD);
				      $paid = false;
				      break;
				    case 'Failed':
				      $status = __('The payment has failed. This happens only if the payment was made from your customer\'s bank account.', WPC_GATEWAYS_TD);
				      $paid = false;
				      break;
				    case 'Partially-Refunded':
				      $status = __('The payment has been partially refunded.', WPC_GATEWAYS_TD);
				      $paid = true;
				      break;
				    case 'In-Progress':
				      $status = __('The transaction has not terminated, e.g. an authorization may be awaiting completion.', WPC_GATEWAYS_TD);
				      $paid = false;
				      break;
				    case 'Completed':
				      $status = __('The payment has been completed, and the funds have been added successfully to your account balance.', WPC_GATEWAYS_TD);
				      $paid = true;
				      break;
				    case 'Processed':
				      $status = __('A payment has been accepted.', WPC_GATEWAYS_TD);
				      $paid = true;
				      break;
				    case 'Reversed':
				      $status = __('A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer:', WPC_GATEWAYS_TD);
				      $reverse_reasons = array(
								'none' => '',
								'chargeback' => __('A reversal has occurred on this transaction due to a chargeback by your customer.', WPC_GATEWAYS_TD),
								'guarantee' => __('A reversal has occurred on this transaction due to your customer triggering a money-back guarantee.', WPC_GATEWAYS_TD),
								'buyer-complaint' => __('A reversal has occurred on this transaction due to a complaint about the transaction from your customer.', WPC_GATEWAYS_TD),
								'refund' => __('A reversal has occurred on this transaction because you have given the customer a refund.', WPC_GATEWAYS_TD),
								'other' => __('A reversal has occurred on this transaction due to an unknown reason.', WPC_GATEWAYS_TD)
								);
				      $status .= '<br />' . $reverse_reasons[$result["PAYMENTINFO_{$i}_REASONCODE"]];
				      $paid = false;
				      break;
				    case 'Refunded':
				      $status = __('You refunded the payment.', WPC_GATEWAYS_TD);
				      $paid = false;
				      break;
				    case 'Denied':
				      $status = __('You denied the payment when it was marked as pending.', WPC_GATEWAYS_TD);
				      $paid = false;
				      break;
				    case 'Pending':
				      $pending_str = array(
								'address' => __('The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences  section of your Profile.', WPC_GATEWAYS_TD),
								'authorization' => __('The payment is pending because it has been authorized but not settled. You must capture the funds first.', WPC_GATEWAYS_TD),
								'echeck' => __('The payment is pending because it was made by an eCheck that has not yet cleared.', WPC_GATEWAYS_TD),
								'intl' => __('The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', WPC_GATEWAYS_TD),
								'multi-currency' => __('You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', WPC_GATEWAYS_TD),
								'order' => __('The payment is pending because it is part of an order that has been authorized but not settled.', WPC_GATEWAYS_TD),
								'paymentreview' => __('The payment is pending while it is being reviewed by PayPal for risk.', WPC_GATEWAYS_TD),
								'unilateral' => __('The payment is pending because it was made to an email address that is not yet registered or confirmed.', WPC_GATEWAYS_TD),
								'upgrade' => __('The payment is pending because it was made via credit card and you must upgrade your account to Business or Premier status in order to receive the funds. It can also mean that you have reached the monthly limit for transactions on your account.', WPC_GATEWAYS_TD),
								'verify' => __('The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', WPC_GATEWAYS_TD),
								'other' => __('The payment is pending for an unknown reason. For more information, contact PayPal customer service.', WPC_GATEWAYS_TD),
								'*' => ''
				      );
				      $status = __('The payment is pending.', WPC_GATEWAYS_TD);
				      $status .= '<br />' . $pending_str[$result["PAYMENTINFO_{$i}_PENDINGREASON"]];
				      $paid = false;
				      break;
				    default:
				      // case: various error cases
				      $paid = false;
				  }
				  $status = $result["PAYMENTINFO_{$i}_PAYMENTSTATUS"] . ': '. $status;


                  //status's are stored as an array with unix timestamp as key
                  $payment_info['status'] = array();
                  $payment_info['status'][$timestamp] = $status;
				  $payment_info['order_time'] = $timestamp;
				  $payment_info['currency'] = $result["PAYMENTINFO_{$i}_CURRENCYCODE"];
				  $payment_info['total'] = $result["PAYMENTINFO_{$i}_AMT"];

				  $payment_info['note'] = ( isset( $result["NOTE"] ) ) ? $result["NOTE"] : null; //optional, only shown if gateway supports it

                  $unique_id = ($result["PAYMENTINFO_{$i}_PAYMENTREQUESTID"]) ? $result["PAYMENTINFO_{$i}_PAYMENTREQUESTID"] : $result["PAYMENTREQUEST_{$i}_PAYMENTREQUESTID"]; //paypal docs messed up, not sure which is valid return
                  @list( $client_id, $order_id) = explode(':', $unique_id);

		          //succesful payment, create our order now
	              $wpc_payments_core->create_order($_SESSION['wpc_payment']['order_id'], $order_info, $payment_info, $paid, $client_id);
				}

        //success. Do nothing, it will take us to the confirmation page
      } else { //whoops, error
      $error = '';
	  for ($i = 0; $i <= 5; $i++) { //print the first 5 errors
          if (isset($result["L_ERRORCODE$i"]))
            $error .= "<li>{$result["L_ERRORCODE$i"]} - {$result["L_SHORTMESSAGE$i"]} - {$result["L_LONGMESSAGE$i"]}</li>";
        }

        $error = '<br /><ul>' . $error . '</ul>';
        $wpc_payments_core->cart_checkout_error( sprintf(__('There was a problem finalizing your purchase with PayPal. Please <a href="%s">go back and try again</a>.', WPC_GATEWAYS_TD), get_home_url() . '/' . wpc_client_get_slug( 'registration', false ) . '-step-checkout' ) . $error );
      }
    } else {
      $wpc_payments_core->cart_checkout_error( sprintf(__('There was a problem finalizing your purchase with PayPal. Please <a href="%s">go back and try again</a>.', WPC_GATEWAYS_TD), get_home_url() . '/' . wpc_client_get_slug( 'registration', false ) . '-step-checkout' ) );
    }
  }

	/**
   * Runs before page load incase you need to run any scripts before loading the success message page
   */
	function order_confirmation($order) {

  }

  /**
   * Filters the order confirmation email message body. You may want to append something to
   *  the message. Optional
   *
   * Don't forget to return!
   */
	function order_confirmation_email($msg, $order) {
    return $msg;
  }

  /**
   * Return any html you want to show on the confirmation screen after checkout. This
   *  should be a payment details box and message.
   *
   * Don't forget to return!
   */
  function order_confirmation_msg($content, $order) {
	    if ( $order['order_status'] == 'order_received') {
	      $content .= '<p>' . sprintf(__('Your PayPal payment for this order totaling %s %s is not yet complete. Here is the latest status:', WPC_GATEWAYS_TD), $order['currency'], $order['amount'] ) . '</p>';
	      $statuses = maybe_unserialize( $order['transaction_status'] );
	      krsort($statuses); //sort with latest status at the top
	      $status = reset($statuses);
	      $timestamp = key($statuses);
	      $content .= '<p><strong>' . date(get_option('date_format') . ' - ' . get_option('time_format'), $timestamp) . ':</strong> ' . $status . '</p>';
	    } else {
	      $content .= '<p>' . sprintf(__('Your PayPal payment for this order totaling %s %s is complete. The PayPal transaction number is <strong>%s</strong>.', WPC_GATEWAYS_TD), $order['currency'], $order['amount'], $order['transaction_id']) . '</p>';
	    }

    return $content;
  }

	/**
   * Echo a settings meta box with whatever settings you need for you gateway.
   *  Form field names should be prefixed with wpc_gateway[gateways][plugin_name], like "wpc_gateway[gateways][plugin_name][mysetting]".
   *  You can access saved settings via $settings array.
   */
	function gateway_settings_box($settings) {
    global $wpc_payments_core;
    ?>
    <div id="tabs-<?php echo $settings['gateways']['paypal-express']['tab_id'] ?>">

        <div id="wpc_paypal_express" class="postbox">

          <h3 class='hndle'><span><?php _e('PayPal Express Checkout Settings', WPC_GATEWAYS_TD); ?></span></h3>
          <div class="inside">
            <span class="description"><?php _e('Express Checkout is PayPal\'s premier checkout solution, which streamlines the checkout process for buyers and keeps them on your site after making a purchase. Unlike PayPal Pro, there are no additional fees to use Express Checkout, though you may need to do a free upgrade to a business account. <a target="_blank" href="https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_ECGettingStarted">More Info &raquo;</a>', WPC_GATEWAYS_TD) ?></span>
            <table class="form-table">
              <tr>
  				    <th scope="row"><?php _e('PayPal Site', WPC_GATEWAYS_TD) ?></th>
  				    <td>
                <select name="wpc_gateway[gateways][paypal-express][locale]">
                <?php
                $sel_locale = ($settings['gateways']['paypal-express']['locale']) ? $settings['gateways']['paypal-express']['locale'] : $settings['base_country'];
                $locales = array(
                  'AU'	=> 'Australia',
                  'AT'	=> 'Austria',
                  'BE'	=> 'Belgium',
                  'CA'	=> 'Canada',
                  'CN'	=> 'China',
                  'FR'	=> 'France',
                  'DE'	=> 'Germany',
                  'HK'	=> 'Hong Kong',
                  'IT'	=> 'Italy',
                  'MX'	=> 'Mexico',
                  'NL'	=> 'Netherlands',
                  'PL'	=> 'Poland',
                  'SG'	=> 'Singapore',
                  'ES'	=> 'Spain',
                  'SE'	=> 'Sweden',
                  'CH'	=> 'Switzerland',
				  'TR' 	=> 'Turkey',
                  'GB'	=> 'United Kingdom',
                  'US'	=> 'United States'
                );

                foreach ($locales as $k => $v) {
                    echo '		<option value="' . $k . '"' . ($k == $sel_locale ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
                }
                ?>
                </select>
  				    </td>
              </tr>
              <tr>
	            <th scope="row"><?php _e('Paypal Currency', WPC_GATEWAYS_TD) ?></th>
	            <td>
	              <select name="wpc_gateway[gateways][paypal-express][currency]">
	              <?php
	              $sel_currency = ($settings['gateways']['paypal-express']['currency']) ? $settings['gateways']['paypal-express']['currency'] : $settings['currency'];
	              $currencies = array(
	                  'AUD' => 'AUD - Australian Dollar',
	                  'BRL' => 'BRL - Brazilian Real',
	                  'CAD' => 'CAD - Canadian Dollar',
	                  'CHF' => 'CHF - Swiss Franc',
	                  'CZK' => 'CZK - Czech Koruna',
	                  'DKK' => 'DKK - Danish Krone',
	                  'EUR' => 'EUR - Euro',
	                  'GBP' => 'GBP - Pound Sterling',
	                  'ILS' => 'ILS - Israeli Shekel',
	                  'HKD' => 'HKD - Hong Kong Dollar',
	                  'HUF' => 'HUF - Hungarian Forint',
	                  'JPY' => 'JPY - Japanese Yen',
	                  'MYR' => 'MYR - Malaysian Ringgits',
	                  'MXN' => 'MXN - Mexican Peso',
	                  'NOK' => 'NOK - Norwegian Krone',
	                  'NZD' => 'NZD - New Zealand Dollar',
	                  'PHP' => 'PHP - Philippine Pesos',
	                  'PLN' => 'PLN - Polish Zloty',
	                  'SEK' => 'SEK - Swedish Krona',
	                  'SGD' => 'SGD - Singapore Dollar',
	                  'TWD' => 'TWD - Taiwan New Dollars',
	                  'THB' => 'THB - Thai Baht',
					  'TRY' => 'TRY - Turkish lira',
	                  'USD' => 'USD - U.S. Dollar'
	              );

	              foreach ($currencies as $k => $v) {
	                  echo '		<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
	              }
	              ?>
	              </select>
	            </td>
	            </tr>
	            <tr>
					    <th scope="row"><?php _e('PayPal Mode', WPC_GATEWAYS_TD) ?></th>
					    <td>
					    <select name="wpc_gateway[gateways][paypal-express][mode]">
	              <option value="sandbox"<?php ( isset( $settings['gateways']['paypal-express']['mode'] ) ) ? selected($settings['gateways']['paypal-express']['mode'], 'sandbox') : '' ?>><?php _e('Sandbox', WPC_GATEWAYS_TD) ?></option>
	              <option value="live"<?php ( isset( $settings['gateways']['paypal-express']['mode'] ) ) ? selected($settings['gateways']['paypal-express']['mode'], 'live') : '' ?>><?php _e('Live', WPC_GATEWAYS_TD) ?></option>
	            </select>
					    </td>
	            </tr>
					    <tr>
					    <th scope="row"><?php _e('PayPal Merchant E-mail', WPC_GATEWAYS_TD) ?></th>
					    <td>
					    <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['merchant_email'] ) ) ? esc_attr($settings['gateways']['paypal-express']['merchant_email']) : '' ?>" size="30" name="wpc_gateway[gateways][paypal-express][merchant_email]" type="text" />
					    </td>
	            </tr>
	            <tr>
					    <th scope="row"><?php _e('PayPal API Credentials', WPC_GATEWAYS_TD) ?></th>
					    <td>
	  				    <span class="description"><?php _e('You must login to PayPal and create an API signature to get your credentials. <a target="_blank" href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_ECAPICredentials">Instructions &raquo;</a>', WPC_GATEWAYS_TD) ?></span>
	              <p><label><?php _e('API Username', WPC_GATEWAYS_TD) ?><br />
	              <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['api_user'] ) ) ? esc_attr($settings['gateways']['paypal-express']['api_user']) : '' ?>" size="30" name="wpc_gateway[gateways][paypal-express][api_user]" type="text" />
	              </label></p>
	              <p><label><?php _e('API Password', WPC_GATEWAYS_TD) ?><br />
	              <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['api_pass'] ) ) ? esc_attr($settings['gateways']['paypal-express']['api_pass']) : '' ?>" size="20" name="wpc_gateway[gateways][paypal-express][api_pass]" type="text" />
	              </label></p>
	              <p><label><?php _e('Signature', WPC_GATEWAYS_TD) ?><br />
	              <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['api_sig'] ) ) ? esc_attr($settings['gateways']['paypal-express']['api_sig']) : '' ?>" size="70" name="wpc_gateway[gateways][paypal-express][api_sig]" type="text" />
	              </label></p>
	            </td>
	            </tr>
	            <tr>
					    <th scope="row"><?php _e('PayPal Header Image (optional)', WPC_GATEWAYS_TD) ?></th>
					    <td>
	  				    <span class="description"><?php _e('URL for an image you want to appear at the top left of the payment page. The image has a maximum size of 750 pixels wide by 90 pixels high. PayPal recommends that you provide an image that is stored on a secure (https) server. If you do not specify an image, the business name is displayed.', WPC_GATEWAYS_TD) ?></span>
	              <p>
	              <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['header_img'] ) ) ? esc_attr($settings['gateways']['paypal-express']['header_img']) : '' ?>" size="80" name="wpc_gateway[gateways][paypal-express][header_img]" type="text" />
	              </p>
	            </td>
	            </tr>
	            <tr>
					    <th scope="row"><?php _e('PayPal Header Border Color (optional)', WPC_GATEWAYS_TD) ?></th>
					    <td>
	  				    <span class="description"><?php _e('Sets the border color around the header of the payment page. The border is a 2-pixel perimeter around the header space, which is 750 pixels wide by 90 pixels high. By default, the color is black.', WPC_GATEWAYS_TD) ?></span>
	              <p>
	              <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['header_border'] ) ) ? esc_attr($settings['gateways']['paypal-express']['header_border']) : '' ?>" size="6" maxlength="6" name="wpc_gateway[gateways][paypal-express][header_border]" id="wpc-hdr-bdr" type="text" />
	              </p>
	            </td>
	            </tr>
	            <tr>
					    <th scope="row"><?php _e('PayPal Header Background Color (optional)', WPC_GATEWAYS_TD) ?></th>
					    <td>
	  				    <span class="description"><?php _e('Sets the background color for the header of the payment page. By default, the color is white.', WPC_GATEWAYS_TD) ?></span>
	              <p>
	              <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['header_back'] ) ) ? esc_attr($settings['gateways']['paypal-express']['header_back']) : '' ?>" size="6" maxlength="6" name="wpc_gateway[gateways][paypal-express][header_back]" id="wpc-hdr-bck" type="text" />
	              </p>
	            </td>
	            </tr>
	            <tr>
                        <th scope="row"><?php _e('PayPal Page Background Color (optional)', WPC_GATEWAYS_TD) ?></th>
                        <td>
                          <span class="description"><?php _e('Sets the background color for the payment page. By default, the color is white.', WPC_GATEWAYS_TD) ?></span>
                  <p>
                  <input value="<?php echo ( isset( $settings['gateways']['paypal-express']['page_back'] ) ) ? esc_attr($settings['gateways']['paypal-express']['page_back']) : '' ?>" size="6" maxlength="6" name="wpc_gateway[gateways][paypal-express][page_back]" id="wpc-pg-bck" type="text" />
                  </p>
                </td>
                </tr>
                <tr>
				<th scope="row">

                </th>
				<td>
                    <p class="submit">
                        <input type="hidden" name="key" value="paypal-express" />
                        <input type="submit" name="submit_settings" value="<?php _e('Save Changes', WPC_GATEWAYS_TD) ?>" />
                    </p>
                </td>
	            </tr>
            </table>
          </div>
        </div>

    </div>

    <?php
  }

  /**
   * Filters posted data from your settings form. Do anything you need to the $settings['gateways']['plugin_name']
   *  array. Don't forget to return!
   */
	function process_gateway_settings($settings) {

    return $settings;
  }

	/**
   * Use to handle any payment returns from your gateway to the ipn_url. Do not echo anything here. If you encounter errors
   *  return the proper headers to your ipn sender. Exits after.
   */
	function process_ipn_return() {
    global $wpc_payments_core;


    // PayPal IPN handling code
    if (isset($_POST['payment_status']) || isset($_POST['txn_type'])) {
      $settings = get_option('wpc_settings');

			if ($settings['gateways']['paypal-express']['mode'] == 'sandbox') {
        $domain = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			} else {
				$domain = 'https://www.paypal.com/cgi-bin/webscr';
			}

			$req = 'cmd=_notify-validate';
			if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
			foreach ($_POST as $k => $v) {
				if (get_magic_quotes_gpc()) $v = stripslashes($v);
				$req .= '&' . $k . '=' . urlencode($v);
			}

      $args['user-agent'] = "PayPal Express Plugin";
      $args['body'] = $req;
      $args['sslverify'] = false;
			$args['timeout'] = 30;

      //use built in WP http class to work with most server setups
    	$response = wp_remote_post($domain, $args);

    	//check results
    	if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200 || $response['body'] != 'VERIFIED') {
        header("HTTP/1.1 503 Service Unavailable");
        _e( 'There was a problem verifying the IPN string with PayPal. Please try again.', WPC_GATEWAYS_TD );
        exit;
      }
			// process PayPal response
			switch ($_POST['payment_status']) {

			  case 'Canceled-Reversal':
          $status = __('A reversal has been canceled; for example, when you win a dispute and the funds for the reversal have been returned to you.', WPC_GATEWAYS_TD);
          $paid = true;
					break;

        case 'Expired':
          $status = __('The authorization period for this payment has been reached.', WPC_GATEWAYS_TD);
          $paid = false;
					break;

        case 'Voided':
          $status = __('An authorization for this transaction has been voided.', WPC_GATEWAYS_TD);
          $paid = false;
					break;

        case 'Failed':
          $status = __("The payment has failed. This happens only if the payment was made from your customer's bank account.", WPC_GATEWAYS_TD);
          $paid = false;
					break;

   			case 'Partially-Refunded':
          $status = __('The payment has been partially refunded.', WPC_GATEWAYS_TD);
          $paid = true;
					break;

				case 'In-Progress':
          $status = __('The transaction has not terminated, e.g. an authorization may be awaiting completion.', WPC_GATEWAYS_TD);
          $paid = false;
					break;

				case 'Completed':
          $status = __('The payment has been completed, and the funds have been added successfully to your account balance.', WPC_GATEWAYS_TD);
          $paid = true;
					break;

				case 'Processed':
					$status = __('A payment has been accepted.', WPC_GATEWAYS_TD);
          $paid = true;
					break;

				case 'Reversed':
					$status = __('A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer:', WPC_GATEWAYS_TD);
          $reverse_reasons = array(
            'none' => '',
            'chargeback' => __('A reversal has occurred on this transaction due to a chargeback by your customer.', WPC_GATEWAYS_TD),
            'guarantee' => __('A reversal has occurred on this transaction due to your customer triggering a money-back guarantee.', WPC_GATEWAYS_TD),
            'buyer-complaint' => __('A reversal has occurred on this transaction due to a complaint about the transaction from your customer.', WPC_GATEWAYS_TD),
            'refund' => __('A reversal has occurred on this transaction because you have given the customer a refund.', WPC_GATEWAYS_TD),
            'other' => __('A reversal has occurred on this transaction due to an unknown reason.', WPC_GATEWAYS_TD)
            );
          $status .= '<br />' . $reverse_reasons[$result["PAYMENTINFO_0_REASONCODE"]];
          $paid = false;
					break;

				case 'Refunded':
					$status = __('You refunded the payment.', WPC_GATEWAYS_TD);
          $paid = false;
					break;

				case 'Denied':
					$status = __('You denied the payment when it was marked as pending.', WPC_GATEWAYS_TD);
          $paid = false;
					break;

				case 'Pending':
					$pending_str = array(
						'address'           => __('The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences  section of your Profile.', WPC_GATEWAYS_TD),
						'authorization'     => __('The payment is pending because it has been authorized but not settled. You must capture the funds first.', WPC_GATEWAYS_TD),
						'echeck'            => __('The payment is pending because it was made by an eCheck that has not yet cleared.', WPC_GATEWAYS_TD),
						'intl'              => __('The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', WPC_GATEWAYS_TD),
						'multi-currency'    => __('You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', WPC_GATEWAYS_TD),
                        'order'             => __('The payment is pending because it is part of an order that has been authorized but not settled.', WPC_GATEWAYS_TD),
                        'paymentreview'     => __('The payment is pending while it is being reviewed by PayPal for risk.', WPC_GATEWAYS_TD),
                        'unilateral'        => __('The payment is pending because it was made to an email address that is not yet registered or confirmed.', WPC_GATEWAYS_TD),
						'upgrade'           => __('The payment is pending because it was made via credit card and you must upgrade your account to Business or Premier status in order to receive the funds. It can also mean that you have reached the monthly limit for transactions on your account.', WPC_GATEWAYS_TD),
						'verify'            => __('The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', WPC_GATEWAYS_TD),
						'other'             => __('The payment is pending for an unknown reason. For more information, contact PayPal customer service.', WPC_GATEWAYS_TD),
                        '*'                 => ''
						);
          $status = __('The payment is pending.', WPC_GATEWAYS_TD);
          $status .= '<br />' . $pending_str[$_POST["pending_reason"]];
          $paid = false;
					break;

				default:
					// case: various error cases
			}
      $status = $_POST['payment_status'] . ': '. $status;
      //record transaction
      $wpc_payments_core->update_order_payment_status($_POST['invoice'], $status, $paid);
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('Status: 404 Not Found');
			echo 'Error: Missing POST variables. Identification is not possible.';
			exit;
		}
  }

  /**** PayPal API methods *****/


	//Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
  function SetExpressCheckout( $order_info, $order_id)	{
    global $wpc_payments_core;


	$settings = get_option('wpc_settings');

$nvpstr = "";
$nvpstr .= "&returnUrl=" . $this->returnURL;
$nvpstr .= "&cancelUrl=" . $this->cancelURL;
$nvpstr .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $this->currencyCode;
$nvpstr .= "&NOSHIPPING=1";
//$nvpstr .= "&EMAIL=" . $order_info['amount']; //pre-fill the PayPal membership
//$nvpstr .= "&BUYERID=" . $order_info['BUYERID']; // The unique identifier provided for this buyer.
//$nvpstr .= "&BUYERUSERNAME=" . $order_info['BUYERUSERNAME']; // The user name of the user at the site.
$nvpstr .= "&PAYMENTREQUEST_0_AMT=" . $order_info['amount'];
$nvpstr .= "&PAYMENTREQUEST_0_PAYMENTACTION=" . $this->payment_action;
$nvpstr .= "&PAYMENTREQUEST_0_ITEMAMT=" . $order_info['amount'];
$nvpstr .= "&PAYMENTREQUEST_0_INVNUM=" . $order_id;
$nvpstr .= "&PAYMENTREQUEST_0_PAYMENTREQUESTID=" . $order_info['client_id'] . ":" . $order_id;
$nvpstr .= "&PAYMENTREQUEST_0_NOTIFYURL=" . $this->ipn_url;  //this is supposed to be in DoExpressCheckoutPayment, but I put it here as well as docs are lacking
$nvpstr .= "&L_PAYMENTREQUEST_0_QTY0=1";
$nvpstr .= "&L_PAYMENTREQUEST_0_AMT0=" . $order_info['amount'];
$nvpstr .= "&L_PAYMENTREQUEST_0_DESC0=" . $order_info['description'];


    //'---------------------------------------------------------------------------------------------------------------
    //' Make the API call to PayPal
    //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
    //' If an error occured, show the resulting errors
    //'---------------------------------------------------------------------------------------------------------------
    $resArray = $this->api_call("SetExpressCheckout", $nvpstr);

    $ack = strtoupper($resArray["ACK"]);
    if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")	{
      $token = urldecode($resArray["TOKEN"]);
      $_SESSION['wpc_payment']['TOKEN'] = $token;
    }
    return $resArray;
  }

	//Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	function GetExpressCheckoutDetails( $token )	{
		//'--------------------------------------------------------------
		//' At this point, the buyer has completed authorizing the payment
		//' at PayPal.  The function will call PayPal to obtain the details
		//' of the authorization, incuding any order information of the
		//' buyer.  Remember, the authorization is not a completed transaction
		//' at this state - the buyer still needs an additional step to finalize
		//' the transaction
		//'--------------------------------------------------------------

	    //'---------------------------------------------------------------------------
		//' Build a second API request to PayPal, using the token as the
		//'  ID to get the details on the payment authorization
		//'---------------------------------------------------------------------------
	  $nvpstr = "&TOKEN=" . $token;

		//'---------------------------------------------------------------------------
		//' Make the API call and store the results in an array.
		//'	If the call was a success, show the authorization details, and provide
		//' 	an action to complete the payment.
		//'	If failed, show the error
		//'---------------------------------------------------------------------------
    $resArray = $this->api_call("GetExpressCheckoutDetails", $nvpstr);
    $ack = strtoupper($resArray["ACK"]);
		if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING") {
			$_SESSION['wpc_payment']['payer_id'] =	$resArray['PAYERID'];
		}
		return $resArray;
	}


	//Purpose: 	Prepares the parameters for the DoExpressCheckoutPayment API Call.
	function DoExpressCheckoutPayment($token, $payer_id, $final_amts, $seller_paypal_accounts, $ipns, $prs) {
	  $nvpstr  = '&TOKEN=' . urlencode($token);
	  $nvpstr .= '&PAYERID=' . urlencode($payer_id);
	    $nvpstr .= "&PAYMENTREQUEST_0_AMT=" . $final_amts;
	    $nvpstr .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $this->currencyCode;
	    $nvpstr .= "&PAYMENTREQUEST_0_PAYMENTACTION=" . $this->payment_action;
	    $nvpstr .= "&PAYMENTREQUEST_0_NOTIFYURL=" . $ipns;
	    $nvpstr .= "&PAYMENTREQUEST_0_SELLERPAYPALACCOUNTID=" . $seller_paypal_accounts;
	    $nvpstr .= "&PAYMENTREQUEST_0_PAYMENTREQUESTID=" . $prs;


	  /* Make the call to PayPal to finalize payment
	    */
	  return $this->api_call("DoExpressCheckoutPayment", $nvpstr);
	}

	//Purpose: 	Prepares the parameters for the DoAuthorization API Call.
	function DoAuthorization($transaction_id, $final_amt) {

	  $nvpstr .= '&TRANSACTIONID=' . urlencode($transaction_id);
	  $nvpstr .= '&AMT=' . $final_amt;
	  $nvpstr .= '&TRANSACTIONENTITY=Order';
	  $nvpstr .= '&CURRENCYCODE=' . $this->currencyCode;

	  /* Make the call to PayPal to finalize payment
	   */
	  return $this->api_call("DoAuthorization", $nvpstr);
	}

	//Purpose: 	Prepares the parameters for the DoCapture API Call.
	function DoCapture($transaction_id, $final_amt) {

	  $nvpstr .= '&AUTHORIZATIONID=' . urlencode($transaction_id);
	  $nvpstr .= '&AMT=' . $final_amt;
	  $nvpstr .= '&CURRENCYCODE=' . $this->currencyCode;
	  $nvpstr .= '&COMPLETETYPE=Complete';

	  /* Make the call to PayPal to finalize payment
	   */
	  return $this->api_call("DoCapture", $nvpstr);
	}

	/**
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	  * $this->api_call: Function to perform the API call to PayPal using API signature
	  * @methodName is name of API  method.
	  * @nvpStr is nvp string.
	  * returns an associtive array containing the response from the server.
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	*/
	function api_call($methodName, $nvpStr) {
	  global $wpc_payments_core;

	  //NVPRequest for submitting to server
	  $query_string = "METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($this->version) . "&PWD=" . urlencode($this->API_Password) . "&USER=" . urlencode($this->API_Username) . "&SIGNATURE=" . urlencode($this->API_Signature) . $nvpStr;
	  //build args
	  $args['user-agent'] = "PayPal Express Plugin";
	  $args['body'] = $query_string;
	  $args['sslverify'] = false;
	  $args['timeout'] = 60;

	  //use built in WP http class to work with most server setups
	  $response = wp_remote_post($this->API_Endpoint, $args);
	  if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
	    $wpc_payments_core->cart_checkout_error( __('There was a problem connecting to PayPal. Please try again.', WPC_GATEWAYS_TD) );
	    return false;
	  } else {
	    //convert NVPResponse to an Associative Array
	    $nvpResArray = $this->deformatNVP($response['body']);
	    return $nvpResArray;
	  }
	}

	/*'----------------------------------------------------------------------------------
	 Purpose: Redirects to PayPal.com site.
	 Inputs:  NVP string.
	 Returns:
	----------------------------------------------------------------------------------
	*/
	function RedirectToPayPal($token) {
	  // Redirect to paypal.com here
	  $payPalURL = $this->paypalURL . $token;
	  wp_redirect($payPalURL);
	  exit;
	}


	//This function will take NVPString and convert it to an Associative Array and it will decode the response.
	function deformatNVP($nvpstr) {
		parse_str($nvpstr, $nvpArray);
		return $nvpArray;
	}

	function trim_name($name, $length = 127) {
		while (strlen(urlencode($name)) > $length)
			$name = substr($name, 0, -1);

		return urlencode($name);
	}

}

//register shipping plugin
wpc_register_gateway_plugin( 'WPC_Gateway_Paypal_Express', 'paypal-express', __('PayPal Express Checkout', WPC_GATEWAYS_TD), true );

if ( is_multisite() ) {
	//tie into network settings form
	add_action( 'wpc_network_gateway_settings', 'pe_network_gateway_settings_box' );
}

function pe_network_gateway_settings_box($settings) {
  global $wpc_payments_core;
  $blog_settings = get_option('wpc_settings');
  ?>
  <script type="text/javascript">
	  jQuery(document).ready(function($) {
      $("#gbl_gw_paypal-express").change(function() {
        $("#wpc-main-form").submit();
  		});
    });
	</script>
	<?php
  if ( $settings['global_gateway'] != 'paypal-express')
    return;
  ?>
  <div id="wpc_paypal_express" class="postbox">
    <script type="text/javascript">
  	  jQuery(document).ready(function ($) {
    		$('#wpc-hdr-bdr').ColorPicker({
        	onSubmit: function(hsb, hex, rgb, el) {
        		$(el).val(hex);
        		$(el).ColorPickerHide();
        	},
        	onBeforeShow: function () {
        		$(this).ColorPickerSetColor(this.value);
        	},
          onChange: function (hsb, hex, rgb) {
        		$('#wpc-hdr-bdr').val(hex);
        	}
        })
        .bind('keyup', function(){
        	$(this).ColorPickerSetColor(this.value);
        });
        $('#wpc-hdr-bck').ColorPicker({
        	onSubmit: function(hsb, hex, rgb, el) {
        		$(el).val(hex);
        		$(el).ColorPickerHide();
        	},
        	onBeforeShow: function () {
        		$(this).ColorPickerSetColor(this.value);
        	},
          onChange: function (hsb, hex, rgb) {
        		$('#wpc-hdr-bck').val(hex);
        	}
        })
        .bind('keyup', function(){
        	$(this).ColorPickerSetColor(this.value);
        });
        $('#wpc-pg-bck').ColorPicker({
        	onSubmit: function(hsb, hex, rgb, el) {
        		$(el).val(hex);
        		$(el).ColorPickerHide();
        	},
        	onBeforeShow: function () {
        		$(this).ColorPickerSetColor(this.value);
        	},
          onChange: function (hsb, hex, rgb) {
        		$('#wpc-pg-bck').val(hex);
        	}
        })
        .bind('keyup', function(){
        	$(this).ColorPickerSetColor(this.value);
        });
  		});
  	</script>
    <h3 class='hndle'><span><?php _e('PayPal Express Checkout Global Cart Settings', WPC_GATEWAYS_TD); ?></span></h3>
    <div class="inside">
      <span class="description"><?php _e('Express Checkout is PayPal\'s premier checkout solution, which streamlines the checkout process for buyers and keeps them on your site after making a purchase. Unlike PayPal Pro, there are no additional fees to use Express Checkout, though you may need to do a free upgrade to a business account. This gateway allows carts from up to 10 stores to checkout at once using parallel payments. <a target="_blank" href="https://cms.paypal.com/us/cgi-bin/?&cmd=_render-content&content_ID=developer/e_howto_api_ECGettingStarted">More Info &raquo;</a>', WPC_GATEWAYS_TD) ?></span>
      <table class="form-table">
        <tr>
				<th scope="row"><?php _e('PayPal Site', WPC_GATEWAYS_TD) ?></th>
				<td>
          <select name="wpc_gateway[gateways][paypal-express][locale]">
          <?php
          $sel_locale = ($settings['gateways']['paypal-express']['locale']) ? $settings['gateways']['paypal-express']['locale'] : $blog_settings['base_country'];
          $locales = array(
            'AU'	=> 'Australia',
            'AT'	=> 'Austria',
            'BE'	=> 'Belgium',
            'CA'	=> 'Canada',
            'CN'	=> 'China',
            'FR'	=> 'France',
            'DE'	=> 'Germany',
            'HK'	=> 'Hong Kong',
            'IT'	=> 'Italy',
            'MX'	=> 'Mexico',
            'NL'	=> 'Netherlands',
            'PL'	=> 'Poland',
            'SG'	=> 'Singapore',
            'ES'	=> 'Spain',
            'SE'	=> 'Sweden',
            'CH'	=> 'Switzerland',
            'GB'	=> 'United Kingdom',
            'US'	=> 'United States'
          );

          foreach ($locales as $k => $v) {
              echo '		<option value="' . $k . '"' . ($k == $sel_locale ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
          }
          ?>
          </select>
				</td>
        </tr>
        <tr>
        <th scope="row"><?php _e('Paypal Currency', WPC_GATEWAYS_TD) ?></th>
        <td>
          <select name="wpc_gateway[gateways][paypal-express][currency]">
          <?php
          $sel_currency = ($settings['gateways']['paypal-express']['currency']) ? $settings['gateways']['paypal-express']['currency'] : $blog_settings['currency'];
	        $currencies = array(
              'AUD' => 'AUD - Australian Dollar',
              'BRL' => 'BRL - Brazilian Real',
              'CAD' => 'CAD - Canadian Dollar',
              'CHF' => 'CHF - Swiss Franc',
              'CZK' => 'CZK - Czech Koruna',
              'DKK' => 'DKK - Danish Krone',
              'EUR' => 'EUR - Euro',
              'GBP' => 'GBP - Pound Sterling',
              'ILS' => 'ILS - Israeli Shekel',
              'HKD' => 'HKD - Hong Kong Dollar',
              'HUF' => 'HUF - Hungarian Forint',
              'JPY' => 'JPY - Japanese Yen',
              'MYR' => 'MYR - Malaysian Ringgits',
              'MXN' => 'MXN - Mexican Peso',
              'NOK' => 'NOK - Norwegian Krone',
              'NZD' => 'NZD - New Zealand Dollar',
              'PHP' => 'PHP - Philippine Pesos',
              'PLN' => 'PLN - Polish Zloty',
              'SEK' => 'SEK - Swedish Krona',
              'SGD' => 'SGD - Singapore Dollar',
              'TWD' => 'TWD - Taiwan New Dollars',
              'THB' => 'THB - Thai Baht',
              'USD' => 'USD - U.S. Dollar'
          );

          foreach ($currencies as $k => $v) {
              echo '		<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . wp_specialchars($v, true) . '</option>' . "\n";
          }
          ?>
          </select>
        </td>
        </tr>
        <tr>
				<th scope="row"><?php _e('PayPal Mode', WPC_GATEWAYS_TD) ?></th>
				<td>
				<select name="wpc_gateway[gateways][paypal-express][mode]">
          <option value="sandbox"<?php selected($settings['gateways']['paypal-express']['mode'], 'sandbox') ?>><?php _e('Sandbox', WPC_GATEWAYS_TD) ?></option>
          <option value="live"<?php selected($settings['gateways']['paypal-express']['mode'], 'live') ?>><?php _e('Live', WPC_GATEWAYS_TD) ?></option>
        </select>
				</td>
        </tr>
        <tr>
				<th scope="row"><?php _e('PayPal API Credentials', WPC_GATEWAYS_TD) ?></th>
				<td>
  				<span class="description"><?php _e('You must login to PayPal and create an API signature to get your credentials. <a target="_blank" href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_ECAPICredentials">Instructions &raquo;</a>', WPC_GATEWAYS_TD) ?></span>
          <p><label><?php _e('API Username', WPC_GATEWAYS_TD) ?><br />
          <input value="<?php echo esc_attr(($settings['gateways']['paypal-express']['api_user']) ? $settings['gateways']['paypal-express']['api_user'] : $blog_settings['gateways']['paypal-express']['api_user']); ?>" size="30" name="wpc_gateway[gateways][paypal-express][api_user]" type="text" />
          </label></p>
          <p><label><?php _e('API Password', WPC_GATEWAYS_TD) ?><br />
          <input value="<?php echo esc_attr(($settings['gateways']['paypal-express']['api_pass']) ? $settings['gateways']['paypal-express']['api_pass'] : $blog_settings['gateways']['paypal-express']['api_pass']); ?>" size="20" name="wpc_gateway[gateways][paypal-express][api_pass]" type="text" />
          </label></p>
          <p><label><?php _e('Signature', WPC_GATEWAYS_TD) ?><br />
          <input value="<?php echo esc_attr(($settings['gateways']['paypal-express']['api_sig']) ? $settings['gateways']['paypal-express']['api_sig'] : $blog_settings['gateways']['paypal-express']['api_sig']); ?>" size="70" name="wpc_gateway[gateways][paypal-express][api_sig]" type="text" />
          </label></p>
        </td>
        </tr>
        <tr>
				<th scope="row"><?php _e('PayPal Header Image (optional)', WPC_GATEWAYS_TD) ?></th>
				<td>
  				<span class="description"><?php _e('URL for an image you want to appear at the top left of the payment page. The image has a maximum size of 750 pixels wide by 90 pixels high. PayPal recommends that you provide an image that is stored on a secure (https) server. If you do not specify an image, the business name is displayed.', WPC_GATEWAYS_TD) ?></span>
          <p>
          <input value="<?php echo esc_attr(($settings['gateways']['paypal-express']['header_img']) ? $settings['gateways']['paypal-express']['header_img'] : $blog_settings['gateways']['paypal-express']['header_img']); ?>" size="80" name="wpc_gateway[gateways][paypal-express][header_img]" type="text" />
          </p>
        </td>
        </tr>
        <tr>
				<th scope="row"><?php _e('PayPal Header Border Color (optional)', WPC_GATEWAYS_TD) ?></th>
				<td>
  				<span class="description"><?php _e('Sets the border color around the header of the payment page. The border is a 2-pixel perimeter around the header space, which is 750 pixels wide by 90 pixels high. By default, the color is black.', WPC_GATEWAYS_TD) ?></span>
          <p>
          <input value="<?php echo esc_attr(($settings['gateways']['paypal-express']['header_border']) ? $settings['gateways']['paypal-express']['header_border'] : $blog_settings['gateways']['paypal-express']['header_border']); ?>" size="6" maxlength="6" name="wpc_gateway[gateways][paypal-express][header_border]" id="wpc-hdr-bdr" type="text" />
          </p>
        </td>
        </tr>
        <tr>
				<th scope="row"><?php _e('PayPal Header Background Color (optional)', WPC_GATEWAYS_TD) ?></th>
				<td>
  				<span class="description"><?php _e('Sets the background color for the header of the payment page. By default, the color is white.', WPC_GATEWAYS_TD) ?></span>
          <p>
          <input value="<?php echo esc_attr(($settings['gateways']['paypal-express']['header_back']) ? $settings['gateways']['paypal-express']['header_back'] : $blog_settings['gateways']['paypal-express']['header_back']); ?>" size="6" maxlength="6" name="wpc_gateway[gateways][paypal-express][header_back]" id="wpc-hdr-bck" type="text" />
          </p>
        </td>
        </tr>
        <tr>
				<th scope="row"><?php _e('PayPal Page Background Color (optional)', WPC_GATEWAYS_TD) ?></th>
				<td>
  				<span class="description"><?php _e('Sets the background color for the payment page. By default, the color is white.', WPC_GATEWAYS_TD) ?></span>
          <p>
          <input value="<?php echo esc_attr(($settings['gateways']['paypal-express']['page_back']) ? $settings['gateways']['paypal-express']['page_back'] : $blog_settings['gateways']['paypal-express']['page_back']); ?>" size="6" maxlength="6" name="wpc_gateway[gateways][paypal-express][page_back]" id="wpc-pg-bck" type="text" />
          </p>
        </td>
        </tr>
      </table>
    </div>
  </div>
  <?php
}
?>