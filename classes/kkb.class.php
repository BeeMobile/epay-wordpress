<?php
class WC_Gateway_Kkb extends WC_Payment_Gateway {

	public $version = '1.0.0';

	public function __construct() {
        global $woocommerce;
        $this->id			= 'kkb';
        $this->method_title = __( 'Kkb', 'woocommerce-gateway-kkb' );
        $this->icon 		= $this->plugin_url() . '/assets/images/kkb.png';
        $this->has_fields 	= true;
        $this->debug_email 	= get_option( 'admin_email' );

		// Setup available currency codes.
		$this->available_currencies = array( 'KZT', 'RUB', 'USD');

//		'AED' => string 'United Arab Emirates Dirham' (length=27)
//  'AUD' => string 'Australian Dollars' (length=18)
//  'BDT' => string 'Bangladeshi Taka' (length=16)
//  'BRL' => string 'Brazilian Real' (length=14)
//  'BGN' => string 'Bulgarian Lev' (length=13)
//  'CAD' => string 'Canadian Dollars' (length=16)
//  'CLP' => string 'Chilean Peso' (length=12)
//  'CNY' => string 'Chinese Yuan' (length=12)
//  'COP' => string 'Colombian Peso' (length=14)
//  'CZK' => string 'Czech Koruna' (length=12)
//  'DKK' => string 'Danish Krone' (length=12)
//  'DOP' => string 'Dominican Peso' (length=14)
//  'EUR' => string 'Euros' (length=5)
//  'HKD' => string 'Hong Kong Dollar' (length=16)
//  'HRK' => string 'Croatia kuna' (length=12)
//  'HUF' => string 'Hungarian Forint' (length=16)
//  'ISK' => string 'Icelandic krona' (length=15)
//  'IDR' => string 'Indonesia Rupiah' (length=16)
//  'INR' => string 'Indian Rupee' (length=12)
//  'NPR' => string 'Nepali Rupee' (length=12)
//  'ILS' => string 'Israeli Shekel' (length=14)
//  'JPY' => string 'Japanese Yen' (length=12)
//  'KIP' => string 'Lao Kip' (length=7)
//  'KRW' => string 'South Korean Won' (length=16)
//  'MYR' => string 'Malaysian Ringgits' (length=18)
//  'MXN' => string 'Mexican Peso' (length=12)
//  'NGN' => string 'Nigerian Naira' (length=14)
//  'NOK' => string 'Norwegian Krone' (length=15)
//  'NZD' => string 'New Zealand Dollar' (length=18)
//  'PYG' => string 'Paraguayan Guaraní' (length=19)
//  'PHP' => string 'Philippine Pesos' (length=16)
//  'PLN' => string 'Polish Zloty' (length=12)
//  'GBP' => string 'Pounds Sterling' (length=15)
//  'RON' => string 'Romanian Leu' (length=12)
//  'RUB' => string 'Russian Ruble' (length=13)
//  'SGD' => string 'Singapore Dollar' (length=16)
//  'ZAR' => string 'South African rand' (length=18)
//  'SEK' => string 'Swedish Krona' (length=13)
//  'CHF' => string 'Swiss Franc' (length=11)
//  'TWD' => string 'Taiwan New Dollars' (length=18)
//  'THB' => string 'Thai Baht' (length=9)
//  'TRY' => string 'Turkish Lira' (length=12)
//  'USD' => string 'US Dollars' (length=10)
//  'VND' => string 'Vietnamese Dong' (length=15)
//  'EGP' => string 'Egyptian Pound' (length=14)
//  'KZT' => string 'Kazakhstan tenge' (length=16)
//
//		<currency code="156">CNY</currency>
//<currency code="356">INR</currency>
//<currency code="398">KZT</currency>
//<currency code="410">KRW</currency>
//<currency code="417">KGS</currency>
//<currency code="764">THB</currency>
//<currency code="784">AED</currency>
//<currency code="810">RUR</currency>
//<currency code="826">GBP</currency>
//<currency code="840">USD</currency>
//<currency code="972">TJS</currency>
//<currency code="978">EUR</currency>
//<currency code="344">HKD</currency>

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Setup constants.
		//$this->setup_constants();

		// Setup default merchant da	ta.
		$this->merchant_id = $this->settings['merchant_id'];
		$this->merchant_name = $this->settings['merchant_name'];
		$this->merchant_certificate_id = $this->settings['merchant_certificate_id'];
		$this->private_key_path = $this->settings['private_key_path'];
		$this->private_key_pass = $this->settings['private_key_pass'];
		$this->public_key_path = 'kkbca.pem' ;
		//$this->private_key_path = $this->settings['private_key_path'];
		//$this->private_key_path = $this->settings['private_key_path'];

		$this->url = 'https://epay.kkb.kz/jsp/process/logon.jsp';
		$this->title = $this->settings['title'];
		$this->approve_method = $this->settings['approve_method'];

		$this->approve_url = 'https://epay.kkb.kz/jsp/remote/control.jsp';

		// Setup the test data, if in test mode.
		if ( $this->settings['testmode'] == 'yes' ) {
			$this->add_testmode_admin_settings_notice();
			$this->url = 'https://testpay.kkb.kz/jsp/process/logon.jsp';
			$this->approve_url = 'https://testpay.kkb.kz/jsp/remote/control.jsp';
			$this->approve_url = 'https://epay.kkb.kz/jsp/remote/control.jsp';

			$this->merchant_id = '92061101';
			$this->merchant_name = 'Test shop';
			$this->merchant_certificate_id = '00C182B189';
			$this->private_key_path = 'test_prv.pem';
			$this->private_key_pass = 'nissan';

		}

		$this->response_url	= add_query_arg( 'wc-api', 'WC_Gateway_Kkb', home_url( '/' ) );
		$this->response_url	= add_query_arg( 'wc-api', 'WC_Gateway_Kkb', 'http://f75f0509.ngrok.io' );

		add_action( 'woocommerce_api_wc_gateway_kkb', array( $this, 'check_itn_response' ) );
		add_action( 'valid-kkb-standard-itn-request', array( $this, 'successful_request' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_receipt_kkb', array( $this, 'receipt_page' ) );


		add_action( 'woocommerce_order_status_processing',  array( $this, 'status_processing'));
		add_action( 'woocommerce_order_status_changed_kkb',  array( $this, 'status_changed'));


		// Check if the base currency supports this gateway.
		if ( ! $this->is_valid_for_use() )
			$this->enabled = false;
    }

	/**
     * Initialise Gateway Settings Form Fields
     *
     * @since 1.0.0
     */
    function init_form_fields () {


		$data_dir = $this->getDataDirectory();

		$files = glob($data_dir . "*.*");

		$options = array();
		foreach($files as $file)
		{
			$fileName = str_replace($data_dir, '', $file);
			$options[$fileName] = $fileName;
		}

    	$this->form_fields = array(
    						'enabled' => array(
											'title' => __( 'Enable/Disable', 'woocommerce-gateway-kkb' ),
											'label' => __( 'Enable Epay.kkb.kz', 'woocommerce-gateway-kkb' ),
											'type' => 'checkbox',
											'description' => __( 'This controls whether or not this gateway is enabled within WooCommerce.', 'woocommerce-gateway-kkb' ),
											'default' => 'yes'
										),
    						'title' => array(
    										'title' => __( 'Title', 'woocommerce-gateway-kkb' ),
    										'type' => 'text',
    										'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-kkb' ),
    										'default' => __( 'Epay.kkb.kz', 'woocommerce-gateway-kkb' )
    									),
							'description' => array(
											'title' => __( 'Description', 'woocommerce-gateway-kkb' ),
											'type' => 'text',
											'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-kkb' ),
											'default' => ''
										),
							'testmode' => array(
											'title' => __( 'Sandbox', 'woocommerce-gateway-kkb' ),
											'type' => 'checkbox',
											'description' => __( 'Place the payment gateway in development mode.', 'woocommerce-gateway-kkb' ),
											'default' => 'yes'
										),
							'merchant_id' => array(
											'title' => __( 'Shop/merchant id', 'woocommerce-gateway-kkb' ),
											'type' => 'text',
											'description' => __( 'This is the merchant ID, received from kkb.', 'woocommerce-gateway-kkb' ),
											'default' => ''
										),
							'merchant_name' => array(
								'title' => __( 'Shop/merchant Name', 'woocommerce-gateway-kkb' ),
								'type' => 'text',
								'description' => __( 'This is the merchant name, received from kkb.', 'woocommerce-gateway-kkb' ),
								'default' => ''
							),
							'merchant_certificate_id' => array(
								'title' => __( 'Certificate Serial Number', 'woocommerce-gateway-kkb' ),
								'type' => 'text',
								'description' => __( 'This is the certificate id, received from kkb.', 'woocommerce-gateway-kkb' ),
								'default' => ''
							),
							'private_key_path' => array(
								'title'       => __( 'Private certificate', 'woocommerce-gateway-kkb' ),
								'type'        => 'select',
								'description' => __( 'Choose private certificate', 'woocommerce-gateway-kkb' ),
								'default'     => '',
								'desc_tip'    => true,
								'options'     => $options
							),
							'private_key_pass' => array(
								'title' => __( 'Private certificate password', 'woocommerce-gateway-kkb' ),
								'type' => 'text',
								'description' => __( 'This is the private certificate password, received from kkb.', 'woocommerce-gateway-kkb' ),
								'default' => ''
							),

							'approve_method' => array(
								'title'       => __( 'Approve payment method', 'woocommerce-gateway-kkb' ),
								'type'        => 'select',
								'description' => __( 'Choose approve payment method', 'woocommerce-gateway-kkb' ),
								'default'     => '',
								'desc_tip'    => true,
								'options'     => array(
									'automatic' => __('Automatic', 'woocommerce-gateway-kkb'),
									'manual' 	=> __('Manual', 'woocommerce-gateway-kkb')
								)
							),

							'log' => array(
								'title' => __( 'Log', 'woocommerce-gateway-kkb' ),
								'type' => 'checkbox',
								'description' => __( 'Enable logging', 'woocommerce-gateway-kkb' ),
								'default' => 'no'
							),
							);



    } // End init_form_fields()

    /**
     * add_testmode_admin_settings_notice()
     *
     * Add a notice to the merchant_key and merchant_id fields when in test mode.
     *
     * @since 1.0.0
     */
    function add_testmode_admin_settings_notice () {
    	//$this->form_fields['merchant_id']['description'] .= ' <strong>' . __( 'PayFast Sandbox Merchant ID currently in use.', 'woocommerce-gateway-kkb' ) . ' ( 10000100 )</strong>';
    	//$this->form_fields['merchant_key']['description'] .= ' <strong>' . __( 'PayFast Sandbox Merchant Key currently in use.', 'woocommerce-gateway-kkb' ) . ' ( 46f0cd694581a )</strong>';
    } // End add_testmode_admin_settings_notice()

    /**
	 * Get the plugin URL
	 *
	 * @since 1.0.0
	 */
	function plugin_url() {
		if( isset( $this->plugin_url ) )
			return $this->plugin_url;

		if ( is_ssl() ) {
			return $this->plugin_url = str_replace( 'http://', 'https://', WP_PLUGIN_URL ) . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) );
		} else {
			return $this->plugin_url = WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) );
		}
	} // End plugin_url()

    /**
     * is_valid_for_use()
     *
     * Check if this gateway is enabled and available in the base currency being traded with.
     *
     * @since 1.0.0
     */
	function is_valid_for_use() {
		global $woocommerce;

		$is_available = false;

        $user_currency = get_option( 'woocommerce_currency' );

        $is_available_currency = in_array( $user_currency, $this->available_currencies );
//		var_dump($user_currency);
//
//		var_dump($is_available_currency);
//		exit();
		if ( $is_available_currency)
			$is_available = true;

        return $is_available;
	} // End is_valid_for_use()



	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

		?>
		<h3><?php _e( 'Epay.kkb.kz', 'woocommerce-gateway-kkb' ); ?></h3>
		<p><?php printf( __( 'Eay.kkb.kz works by sending the user to %sEpay.kkb.kz%s to enter their payment information.', 'woocommerce-gateway-kkb' ), '<a href="http://epay.kkb.kz/">', '</a>' ); ?></p>

		<?php
		if ( $this->is_valid_for_use() ) {
			?><table class="form-table"><?php
			// Generate the HTML For the settings form.
			$this->generate_settings_html();
			?></table><!--/.form-table--><?php
		} else {
			?>
			<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce-gateway-kkb' ); ?></strong> <?php echo sprintf( __( 'Choose Kazakhstan tenge as your store currency in "Currency Options" to enable the Epay.kkb.kz Gateway.', 'woocommerce-gateway-kkb' )); ?></p></div>
		<?php
		} // End check currency
		?>
	<?php
	} // End admin_options()


    /**
	 * There are no payment fields for kkb, but we want to show the description if set.
	 *
	 * @since 1.0.0
	 */
    function payment_fields() {
    	if ( isset( $this->settings['description'] ) && ( '' != $this->settings['description'] ) ) {
    		echo wpautop( wptexturize( $this->settings['description'] ) );
    	}
    } // End payment_fields()

	/**
	 * Generate the kkb button link.
	 */
    public function generate_kkb_form( $order_id ) {

		global $woocommerce;

		$order = new WC_Order( $order_id );

		$helper = $this->getKkbHelper();
		//$currency_id = '398';

		$user_currency = get_option( 'woocommerce_currency' );
		switch($user_currency)
		{
			case 'KZT':
				$currency_id = '398';
				break;
			case 'RUB':
				$currency_id = '810';
				break;
			case 'USD':
				$currency_id = '840';
				break;
			break;
		}

		$content = $helper->process_request($order->id . '0000' , $currency_id, $order->order_total, false);

		// Construct variables for post
	    $data_to_send = array(
			'Signed_Order_B64' => base64_encode($content),
			'Language' => 'rus',

	        'BackLink' => $this->get_return_url( $order ),
	        'FailureBackLink' => $order->get_cancel_order_url(),
	        'PostLink' => $this->response_url,

			'email' => $order->billing_email,

	   	);


	   	// Override merchant_id and merchant_key if the gateway is in test mode.
//	   	if ( $this->settings['testmode'] == 'yes' ) {
//	   		$this->data_to_send['merchant_id'] = '10000100';
//	   		$this->data_to_send['merchant_key'] = '46f0cd694581a';
//	   	}

		$kkb_args_array = array();
		$message = "Kkb request: \n";
		foreach ($data_to_send as $key => $value) {
			$kkb_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			$message .=  sprintf( "%s: %s\n" , $key , $value );
		}
		$this->log($message);

		return '<form action="' . $this->url . '" method="post" id="kkb_payment_form">
				' . implode('', $kkb_args_array) . '
				<input type="submit" class="button-alt" id="submit_kkb_payment_form" value="' . __( 'Pay via Epay.kkb.kz', 'woocommerce-gateway-kkb' ) . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce-gateway-kkb' ) . '</a>
				<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{
								message: "<img src=\"' . $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />' . __( 'Thank you for your order. We are now redirecting you to epay.kkb.kz to make payment.', 'woocommerce-gateway-kkb' ) . '",
								overlayCSS:
								{
									background: "#fff",
									opacity: 0.6
								},
								css: {
							        padding:        20,
							        textAlign:      "center",
							        color:          "#555",
							        border:         "3px solid #aaa",
							        backgroundColor:"#fff",
							        cursor:         "wait"
							    }
							});
						jQuery( "#submit_kkb_payment_form" ).click();
					});
				</script>
			</form>';

	} // End generate_payfast_form()

	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.0.0
	 */
	function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		return array(
			'result' 	=> 'success',
			'redirect'	=> $order->get_checkout_payment_url( true )
		);

	}

	/**
	 * Reciept page.
	 *
	 * Display text and a button to direct the user to kkb.
	 *
	 * @since 1.0.0
	 */
	function receipt_page( $order ) {
		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with kkb.', 'woocommerce-gateway-kkb' ) . '</p>';

		echo $this->generate_kkb_form( $order );
	} // End receipt_page()

	/**
	 * Check PayFast ITN response.
	 *
	 * @since 1.0.0
	 */
	function check_itn_response() {
		$_POST = stripslashes_deep( $_POST );

		//if ( $this->check_itn_request_is_valid( $_POST ) ) {
			do_action( 'valid-kkb-standard-itn-request', $_POST );
		//}
	} // End check_itn_response()

	/**
	 * Successful Payment!
	 *
	 * @since 1.0.0
	 */
	function successful_request( $posted ) {

		$helper = $this->getKkbHelper();

		$result = 0;
		if(isset($posted["response"]))
		{
			$response = $posted["response"];
		} else
		{
			echo 'error';
			exit();
		}

		$result = $helper->process_response(stripslashes($response));

		$message = '_receivedNotification: response:' . $_POST["response"] . "\n";
		$this->log($message);


		$err = false;
		$err_message = date('d.m.Y H:i:s').' ';
		if (is_array($result)) {
			if (in_array("ERROR",$result)){
				$err = true;
				if ($result["ERROR_TYPE"]=="ERROR"){
					$err_message.= "System error:".$result["ERROR"]."\n";
				} elseif ($result["ERROR_TYPE"]=="system"){
					$err_message.= "Bank system error > Code: '".$result["ERROR_CODE"]."' Text: '".$result["ERROR_CHARDATA"]."' Time: '".$result["ERROR_TIME"]."' Order_ID: '".$result["RESPONSE_ORDER_ID"]."'";
				} elseif ($result["ERROR_TYPE"]=="auth"){
					$err_message.= "Bank system user autentication error > Code: '".$result["ERROR_CODE"]."' Text: '".$result["ERROR_CHARDATA"]."' Time: '".$result["ERROR_TIME"]."' Order_ID: '".$result["RESPONSE_ORDER_ID"]."'";
				};
			};
			if (in_array("DOCUMENT", $result)){
				$order_id = ltrim($result['ORDER_ORDER_ID'], '0');
			};
		}
		else {
			$err = true;
			$err_message.= "System error: ".$result;
		};

		if (!isset($order_id))
		{
			echo 'error';
			exit();
		}
		$order = new WC_Order( substr($order_id, 0, 2));



		if ($result) {
			update_post_meta( $order->id, 'kkb_fullresponse', wp_slash(json_encode($result)) );
		}

		if ($err) {
			$status = 'cancel';
		}
		else {

			if ($this->settings['approve_method'] == 'automatic')
			{

				$success = $this->approvePayment($result,$order);

				if ($success)
				{
					$status = 'success';
				} else
				{
					$status = 'pending';
				}


			} else {
				$status = 'pending';
			}
		}

		if ( $order->status !== 'completed' ) {
			// We are here so lets check status and do actions
			switch ( strtolower( $status ) ) {
				case 'success' :
					// Payment completed
					$order->add_order_note( __( 'kkb payment completed', 'woocommerce-gateway-kkb' ) );
					$order->payment_complete();
				break;
				case 'pending' :
					$order->update_status( 'on-hold', __('Payment on-hold via kkb.', 'woocommerce-gateway-kkb' ) );
				break;
				default:
					$order->update_status( 'failed', __('Payment error via kkb.', 'woocommerce-gateway-kkb' ) );
				break;
			}

		}

		exit;
	}


	/**
	 * Process a refund if supported
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @return  bool|wp_error True or false based on success, or a WP_Error object
	 */
//	public function process_refund( $order_id, $amount = null, $reason = '' ) {
//		$order = wc_get_order( $order_id );
//
//		if ( ! $order) {
//			return false;
//		}
//
//		$response = get_post_meta($order_id, 'kkb_fullresponse', true);
//		$response2 = (array)json_decode($response);
//		$success = $this->refundPayment($response2, $order);
//
//		return $success;
//	}

	/**
	 * log()
	 *
	 * Log system processes.
	 *
	 * @since 1.0.0
	 */

	function log ( $message, $close = false ) {
		if ( $this->settings['testmode'] != 'yes' ) { return; }

		static $fh = 0;

		if( $close ) {
            @fclose( $fh );
        } else {
            // If file doesn't exist, create it
            if( !$fh ) {
                $pathinfo = pathinfo( __FILE__ );
                $dir = str_replace( '/classes', '/logs', $pathinfo['dirname'] );

                $fh = fopen( $dir .'/kkb.log', 'a' );
            }

            // If file was successfully created
            if( $fh ) {
				$line = date('d.m.Y H:i:s') . "\n";
                $line .= $message ."\n";

                fwrite( $fh, $line );
            }
        }
	} // End log()



	function getDataDirectory()
	{
		$data_dir = __DIR__ .'/../data/';
		//$data_dir = ABSPATH . 'wp-content/plugins/woocommerce-gateway-kkb/data/';
		return $data_dir;
	}


	function approvePayment($result, $order)
	{

		$helper = $this->getKkbHelper();
		$xml = $helper->process_complete($result['PAYMENT_REFERENCE'], $result['PAYMENT_APPROVAL_CODE'], (int)$result['ORDER_ORDER_ID'], $result['ORDER_CURRENCY'], $result['PAYMENT_AMOUNT']);

		$url = $this->approve_url;

		$message = "_sendRequest: \n url: $url \n xml: $xml \n";
		$this->log($message);

		$urlFull = $url . '?'. urlencode($xml);

		$response = $helper->request($urlFull);

		$this->log(trim($response) . "\n");


		$res = $helper->process_response($response);

		if ($response) {
			update_post_meta( $order->id, 'kkb_approveresponse', wp_slash(json_encode($result)) );
		}

		if (isset($res['RESPONSE_CODE']) && $res['RESPONSE_CODE'] == '00')
		{
			$success = 1;
			$order->add_order_note( __( 'Payment approved via kkb', 'woocommerce-gateway-kkb' ) );

		} else {
			$success =  0;
		}


		return $success;
	}

	function refundPayment($result, $order)
	{
		$helper = $this->getKkbHelper();
		$xml = $helper->process_refund($result['PAYMENT_REFERENCE'], $result['PAYMENT_APPROVAL_CODE'], (int)$result['ORDER_ORDER_ID'], $result['ORDER_CURRENCY'], $result['PAYMENT_AMOUNT'], '');

		$url = $this->approve_url;

		$message = "_sendRequest: \n url: $url \n xml: $xml \n";
		$this->log($message);

		$urlFull = $url . '?'. urlencode($xml);

		$response = $helper->request($urlFull);

		$this->log(trim($response) . "\n");


		$res = $helper->process_response($response);

		if ($response) {
			update_post_meta( $order->id, 'kkb_refundresponse', wp_slash(json_encode($result)) );
		}



		if ($res['RESPONSE_CODE'] == '00')
		{
			$success =  1;
			$order->add_order_note( __( 'Payment refunded via kkb', 'woocommerce-gateway-kkb' ) );
		} else {
			$success = 0;

		}


		return $success;
	}

	private function getKkbHelper()
	{
		require_once( 'KkbHelper.php');
		$dataDirectory = $this->getDataDirectory();
		$helper = new KkbHelper($this->merchant_certificate_id, $this->merchant_name, $this->merchant_id, $dataDirectory . $this->private_key_path, $this->private_key_pass, $dataDirectory . $this->public_key_path, $this->url);

		return $helper;
	}

} // End Class