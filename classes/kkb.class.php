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
		$this->available_currencies = array( 'KZT');

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

		// Setup the test data, if in test mode.
		if ( $this->settings['testmode'] == 'yes' ) {
			$this->add_testmode_admin_settings_notice();
			$this->url = 'http://3dsecure.kkb.kz/jsp/process/logon.jsp';

			$this->merchant_id = '92061101';
			$this->merchant_name = 'Test shop';
			$this->merchant_certificate_id = '00C182B189';
			$this->private_key_path = 'test_prv.pem';
			$this->private_key_pass = 'nissan';

		}

		$this->response_url	= add_query_arg( 'wc-api', 'WC_Gateway_Kkb', home_url( '/' ) );
		//$this->response_url	= add_query_arg( 'wc-api', 'WC_Gateway_Kkb', 'http://1b8af1ef.ngrok.com/' );

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
		$currency_id = '398';
		$content = $helper->process_request($order->id, $currency_id, $order->order_total, false);

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
		$order = new WC_Order( $order_id );



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
                $fh = @fopen( $dir .'/kkb.log', 'a' );
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
		$data_dir = ABSPATH . 'wp-content/plugins/woocommerce-gateway-kkb/data/';
		return $data_dir;
	}


	function approvePayment($result, $order)
	{
		$helper = $this->getKkbHelper();
		$xml = $helper->process_complete($result['PAYMENT_REFERENCE'], $result['PAYMENT_APPROVAL_CODE'], (int)$result['ORDER_ORDER_ID'], $result['ORDER_CURRENCY'], $result['PAYMENT_AMOUNT']);

		$url = 'http://3dsecure.kkb.kz/jsp/remote/control.jsp';
		$message = "_sendRequest: \n url: $url \n xml: $xml \n";
		$this->log($message);

		$urlFull = $url . '?'. urlencode($xml);

		$response = $helper->request($urlFull);

		$this->log(trim($response) . "\n");

		if ($response) {
			update_post_meta( $order->id, 'kkb_approveresponse', wp_slash(json_encode($result)) );
		}

		if (strpos(strtolower($response), 'error'))
		{
			$success =  0;
		} else {
			$success = 1;
			$order->add_order_note( __( 'Payment approved via kkb', 'woocommerce-gateway-kkb' ) );
		}


		return $success;
	}

	function refundPayment($result, $order)
	{
		$helper = $this->getKkbHelper();
		$xml = $helper->process_refund($result['PAYMENT_REFERENCE'], $result['PAYMENT_APPROVAL_CODE'], (int)$result['ORDER_ORDER_ID'], $result['ORDER_CURRENCY'], $result['PAYMENT_AMOUNT'], '');

		$url = 'http://3dsecure.kkb.kz/jsp/remote/control.jsp';

		$message = "_sendRequest: \n url: $url \n xml: $xml \n";
		$this->log($message);

		$urlFull = $url . '?'. urlencode($xml);

		$response = $helper->request($urlFull);

		$this->log(trim($response) . "\n");

		if ($response) {
			update_post_meta( $order->id, 'kkb_refundresponse', wp_slash(json_encode($result)) );
		}

		if (strpos(strtolower($response), 'error'))
		{
			$success =  0;
		} else {
			$success = 1;
			$order->add_order_note( __( 'Payment refunded via kkb', 'woocommerce-gateway-kkb' ) );
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