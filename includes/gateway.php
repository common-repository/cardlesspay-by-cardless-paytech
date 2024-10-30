<?php

// Account number: 440018264471
// Routing number: 121202211

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
// wp_enqueue_script('status-update-script', plugins_url('cardless_echeck/js/cardless_scripts.js'), array('jquery'));

add_filter('woocommerce_billing_fields', 'custom_woocommerce_billing_fields');

function custom_woocommerce_billing_fields($fields)
{

	$fields['billing_transit_branch_number'] = array(
		'label' => __('Transit Branch Number', 'woocommerce'), // Add custom field label
		'placeholder' => _x('Transit Branch Number', 'placeholder', 'woocommerce'), // Add custom field placeholder
		'clear' => false, // add clear or not
		'type' => 'text', // add field type
		'priority' => '111',
		'class' => array('first_none'),
	);

	$fields['billing_financial_institution_number'] = array(
		'label' => __('Financial Institution Number', 'woocommerce'), // Add custom field label
		'placeholder' => _x('Financial Institution Number', 'placeholder', 'woocommerce'), // Add custom field placeholder
		'clear' => false, // add clear or not
		'type' => 'text', // add field type
		'priority' => '112',
		'class' => array('first_none'),
	);

	$fields['billing_bankname'] = array(
		'label' => __('Bank Name', 'woocommerce'), // Add custom field label
		'placeholder' => _x('Bank Name', 'placeholder', 'woocommerce'), // Add custom field placeholder
		'clear' => false, // add clear or not
		'type' => 'text', // add field type
		'priority' => '113',
		'class' => array('first_none'),
	);

	$fields['billing_bank_address1'] = array(
		'label' => __('Bank Address 1', 'woocommerce'), // Add custom field label
		'placeholder' => _x('Bank Address 1', 'placeholder', 'woocommerce'), // Add custom field placeholder
		'clear' => false, // add clear or not
		'type' => 'text', // add field type
		'priority' => '114',
		'class' => array('first_none'),
	);


	$fields['billing_bank_address2'] = array(
		'label' => __('Bank Address 2', 'woocommerce'), // Add custom field label
		'placeholder' => _x('Bank Address 2', 'placeholder', 'woocommerce'), // Add custom field placeholder
		'clear' => false, // add clear or not
		'type' => 'text', // add field type
		'priority' => '115',
		'class' => array('first_none'),
	);

	$fields['billing_bank_address3'] = array(
		'label' => __('Bank Address 3', 'woocommerce'), // Add custom field label
		'placeholder' => _x('Bank Address 3', 'placeholder', 'woocommerce'), // Add custom field placeholder
		'clear' => false, // add clear or not
		'type' => 'text', // add field type
		'priority' => '116',
		'class' => array('first_none'),
	);

	$fields['billing_signature'] = array(
		'label' => __('Signature', 'woocommerce'), // Add custom field label
		'placeholder' => _x('Signature', 'placeholder', 'woocommerce'), // Add custom field placeholder
		'clear' => false, // add clear or not
		'type' => 'text', // add field type
		'priority' => '116',
		'class' => array('first_none'),
		// 'required' => true,
	);



	return $fields;
}


add_filter('woocommerce_billing_fields', 'wc_filter_fileds', 10, 1);

function wc_filter_fileds($fields)
{
	$wc = WC();
	$country = $wc->customer->get_billing_country();
	unset($fields['billing']['billing_transit_branch_number']['required']);
	unset($fields['billing']['billing_financial_institution_number']['required']);
	unset($fields['billing']['billing_bankname']['required']);
	unset($fields['billing']['billing_bank_address1']['required']);
	unset($fields['billing']['billing_bank_address2']['required']);
	unset($fields['billing']['billing_bank_address3']['required']);
	if ($country == 'CA') {
		$fields['billing_transit_branch_number']['required'] = true;
		$fields['billing_transit_branch_number']['required'] = true;
		$fields['billing_financial_institution_number']['required'] = true;
		$fields['billing_bankname']['required'] = true;
		$fields['billing_bank_address1']['required'] = true;
		$fields['billing_bank_address2']['required'] = true;
		$fields['billing_bank_address3']['required'] = true;
	} else {
		unset($fields['billing']['billing_transit_branch_number']['required']);
		unset($fields['billing']['billing_financial_institution_number']['required']);
		unset($fields['billing']['billing_bankname']['required']);
		unset($fields['billing']['billing_bank_address1']['required']);
		unset($fields['billing']['billing_bank_address2']['required']);
		unset($fields['billing']['billing_bank_address3']['required']);
	}
	return $fields;
}

function hide_fields_script()
{
	if (is_checkout()) {
?>
<script>
jQuery('#billing_country').val("US").trigger('change');

jQuery('#billing_transit_branch_number_field').hide();
jQuery('#billing_financial_institution_number_field').hide();
jQuery('#billing_bankname_field').hide();
jQuery('#billing_bank_address1_field').hide();
jQuery('#billing_bank_address2_field').hide();
jQuery('#billing_bank_address3_field').hide();

jQuery('#billing_country').change(function() {
    var customercountry = jQuery('#billing_country').val();
    if (customercountry == "CA") {
        jQuery('#billing_transit_branch_number_field').show();
        jQuery('#billing_financial_institution_number_field').show();
        jQuery('#billing_bankname_field').show();
        jQuery('#billing_bank_address1_field').show();
        jQuery('#billing_bank_address2_field').show();
        jQuery('#billing_bank_address3_field').show();
        // jQuery('#billing_routing_number_filed').hide();
    } else {
        jQuery('#billing_transit_branch_number_field').hide();
        jQuery('#billing_financial_institution_number_field').hide();
        jQuery('#billing_bankname_field').hide();
        jQuery('#billing_bank_address1_field').hide();
        jQuery('#billing_bank_address2_field').hide();
        jQuery('#billing_bank_address3_field').hide();
        // jQuery('#billing_routing_number_filed').show();
    }
});
</script>
<?php
	}
}
add_action('wp_footer', 'hide_fields_script');


add_filter('woocommerce_checkout_fields', 'remove_postcode_validation', 99);

function remove_postcode_validation($fields)
{

	unset($fields['billing']['billing_postcode']['validate']);

	return $fields;
}


if (!class_exists('WC_Gateway_Cardless_Pay')) {

	class WC_Gateway_Cardless_Pay extends WC_Payment_Gateway
	{

		/** @var bool Whether or not logging is enabled */
		public static $log_enabled = false;

		/** @var WC_Logger Logger instance */
		public static $log = false;
		public static $deleted;
		public static $processed;
		public static $rejected;

		/**
		 * Cloning is forbidden
		 *
		 * @since 1.0
		 */
		public function __clone()
		{
			//do nothing
		}
		/**
		 * Unserializing instances of this class is forbidden
		 *
		 * @since 1.0
		 */
		public function __wakeup()
		{
			//do nothing
		}

		//See parent class WC_Payment_Gateway for (id, has_fields, method_title, method_description, title, supports)
		public function __construct()
		{
			$this->error = '';
			$this->id					 = 'cardlessmoney';
			$this->has_fields			 = true;
			$this->method_title			 = __('Cardless', 'woocommerce-gateway-cardless-money');
			$new_settings_page = get_admin_url(null, 'admin.php?page=CardlessPay_payment_gateway');
			$this->method_description	 = __("<a href=" . $new_settings_page . " target='_blank'>CardlessPay Settings</a>", 'woocommerce-gateway-cardless-money');
			$this->supports				 = array(
				'products',
				'refunds',
			);


			//Get options we need and make them usable
			$this->options = get_option('cardless_settings');
			$this->endpoint = trailingslashit($this->options['cardless_api_endpoint']);
			$this->cardless_token = $this->options['cardless_token'];
			$this->description = $this->options['cardless_gateway_description'];
			$this->title = $this->options['cardless_title'];
			$this->enabled = array_key_exists("cardless_is_enabled", $this->options) ? $this->options['cardless_is_enabled'] : 0;
			$this->debug = array_key_exists("cardless_debug_log", $this->options) ? $this->options['cardless_debug_log'] : 0;

			//Set enabled
			if ($this->enabled === '1') {
				$this->enabled = True;
			} else if ($this->enabled === 1) {
				$this->enabled = True;
			} else {
				$this->enabled = False;
			}

			//Set debug
			if ($this->debug === '1') {
				$this->debug = True;
			} else if ($this->debug === 1) {
				$this->debug = True;
			} else {
				$this->debug = False;
			}

			self::$log_enabled = $this->debug;

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('admin_notices', array($this, 'do_ssl_check'), 999); //QUESTION: What is this doing?
		} //END __construct()

		function __toString()
		{
			$str  = "Gateway Type: POST\n";
			$str .= "Endpoint: " . $this->endpoint . "\n";
			$str .= "Cardless Token: " . $this->cardless_token . "\n";

			return $str;
		}

		function cardless_money_toString($html = TRUE)
		{
			if ($html) {
				return nl2br($this->__toString());
			}

			return $this->__toString();
		}

		private function cardless_money_setLastError($error)
		{
			$this->error = $error;
		}

		public function cardless_money_getLastError()
		{
			return $this->error;
		}

		/**
		 * Logging method.
		 *
		 * @param string $message
		 */
		public static function log($message)
		{
			if (self::$log_enabled) {
				if (empty(self::$log)) {
					self::$log = new WC_Logger();
				}
				self::$log->add('cardlessmoney', $message);
			}
		}

		// Check if we are forcing SSL on checkout pages
		public function do_ssl_check()
		{
			if (False === $this->enabled) {
				return;
			}
		}

		/**
		 * Check if this gateway is enabled
		 */
		public function is_available()
		{
			return true;
		}

		/**
		 * Payment form on checkout page.
		 *
		 * See WC_Payment_Gateway::payment_fields()
		 */
		public function payment_fields()
		{

			$description_output = $this->description;

			if ($description_output) {
				echo wpautop(wptexturize(trim($description_output)));
			}

			$wc = WC();
			$country = $wc->customer->get_billing_country();
			$fields = array();

			if ($country == 'CA') {

				$default_fields = array(

					'account-number' => '<p class="form-row form-row-first validate-required" id="billing_account_number_filed">
					<label for="' . esc_attr($this->id) . '-account-number">' . __('Account Number', 'woocommerce-gateway-cardless-money') . ' <span class="required">*</span></label>
					<input id="' . esc_attr($this->id) . '-account-number" class="input-text" type="text" name="' . esc_attr($this->id) . '_account_number"  autocomplete="off"  maxlength="17" />
				</p>',
				);
			} else {
				$default_fields = array(
					'routing-number' => '<p class="form-row form-row-first validate-required usa_routing_number" id="billing_routing_number_filed">
					<label for="' . esc_attr($this->id) . '-routing-number">' . __('Routing Number', 'woocommerce-gateway-cardless-money') . ' <span class="required">*</span></label>
					<input id="' . esc_attr($this->id) . '-routing-number" class="input-text" type="text"  autocomplete="off" name="' . esc_attr($this->id) . '_routing_number" placeholder="•••••••••" maxlength="9" />
				</p>',
					'account-number' => '<p class="form-row form-row-first validate-required" id="billing_account_number_filed">
					<label for="' . esc_attr($this->id) . '-account-number">' . __('Account Number', 'woocommerce-gateway-cardless-money') . ' <span class="required">*</span></label>
					<input id="' . esc_attr($this->id) . '-account-number" class="input-text" type="text" name="' . esc_attr($this->id) . '_account_number"  autocomplete="off"  maxlength="17" />
				</p>',
				);
			}
			$fields = wp_parse_args($fields, apply_filters('woocommerce_gateway_cardless_money_checkout_fields', $default_fields, $this->id));
		?>

<fieldset id="wc-<?php echo esc_attr($this->id); ?>-check-form" class='wc-credit-card-form wc-payment-form'>

    <?php
				foreach ($fields as $field) {
					echo $field;
				}
				?>

    <div class="clear"></div>
</fieldset>
<?php
		}

		/**
		 * Safely get and trim data from $_POST
		 *
		 * @since 1.0.0
		 * @param string $key array key to get from $_POST array
		 * @return string value from $_POST or blank string if $_POST[ $key ] is not set
		 */
		public static function get_post($key)
		{
			if (isset($_POST[$key])) {
				return trim($_POST[$key]);
			}
		}

		/**
		 * Validate the payment fields when processing the checkout
		 *
		 * @since 1.0.0
		 * @see WC_Payment_Gateway::validate_fields()
		 * @return bool true if fields are valid, false otherwise
		 */
		public function validate_fields()
		{

			$is_valid = parent::validate_fields(); //$is_valid = true in MOST cases

			return $this->validate_check_fields($is_valid);
		}

		/**
		 * Returns true if the posted echeck fields are valid, false otherwise
		 *
		 * @since 1.0.0
		 * @param bool $is_valid true if the fields are valid, false otherwise
		 * @return bool
		 */
		protected function validate_check_fields($is_valid)
		{
			$this->account_number	 = $this->get_post($this->id . '_account_number');
			$this->routing_number	 = $this->get_post($this->id . '_routing_number');

			// routing number exists?
			if (empty($this->routing_number)) {
				wc_add_notice('Routing Number is missing', 'woocommerce-gateway-cardless-money', 'error');
				$is_valid = false;
			} else {
				// routing number digit validation
				if (!ctype_digit($this->routing_number)) {
					wc_add_notice('Routing Number is invalid (only digits are allowed)', 'woocommerce-gateway-cardless-money', 'error');
					$is_valid = false;
				}

				// routing number length validation
				if (9 != strlen($this->routing_number)) {
					wc_add_notice('Routing number is invalid (must be 9 digits)', 'woocommerce-gateway-cardless-money', 'error');
					$is_valid = false;
				}
			}

			// account number exists?
			if (empty($this->account_number)) {

				wc_add_notice('Account Number is missing', 'woocommerce-gateway-cardless-money', 'error');
				$is_valid = false;
			} else {
				// account number digit validation
				if (!ctype_digit($this->account_number)) {
					//echo 'here';
					wc_add_notice('Account Number is invalid (only digits are allowed)', 'woocommerce-gateway-cardless-money', 'error');
					$is_valid = false;
				}

				// account number length validation
				if (strlen($this->account_number) < 5 || strlen($this->account_number) > 17) {
					wc_add_notice('Account number is invalid (must be between 5 and 17 digits)', 'woocommerce-gateway-cardless-money', 'error');
					$is_valid = false;
				}
			}

			return apply_filters('wc_payment_gateway_' . $this->id . '_validate_check_fields', $is_valid, $this);
		}

		/**
		 * Helper function to call correct api method
		 *
		 * @param string $entity
		 * @param array $body
		 * @return mixed Returns associative array or delimited string on success OR cURL error string on failure
		 */
		function api_call($entity, $body = null)
		{
			if ('OneTimeDraftRTV' == $entity) {
				$response = $this->cardless_money_singleCheck($body); //$body is array with client id, password, name, email, phone, etc
			}
			return $response;
		}

		/**
		 * A default method used to generate API Calls
		 *
		 * This method is used internally by all other methods to generate API calls easily.
		 *
		 * @param string  $method       The name of the API method to call at the endpoint (ex. OneTimeDraftRTV, CheckStatus, etc.)
		 * @param array   $options      An array of "APIFieldName" => "Value" pairs. Must include the Client_ID and ApiPassword variables
		 * @param array   $resultArray  An array of "APIResultName" which must match the return of the API call
		 *
		 * @return mixed                Returns associative array or delimited string on success OR cURL error string on failure
		 */
		function cardless_money_request($method, $options, $resultArray = array())
		{

			$url = 'https://www.cardlesspaytech.com/api/v1/create_check';

			$q = wp_remote_post(
				$url,
				array(
					'method' => 'POST',
					'timeout' => 900,
					'body' => $options,
				)
			);

			$response = json_decode($q['body']);
			return $response;
		}

		/**
		 * Inserts a single check
		 *
		 * @param array   $data    An array of "APIFieldName" => "Value" pairs. Must include the Client_ID and ApiPassword variables
		 *
		 * @return mixed           Returns associative array or delimited string on success OR cURL error string on failure
		 */
		function cardless_money_singleCheck($data)
		{
			return $this->cardless_money_request('OneTimeDraftRTV', $data, array(
				"Result",
				"ResultDescription",
				"VerifyResult",
				"VerifyResultDescription",
				"CheckNumber",
				"Check_ID"
			));
		}


		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 *
		 * See WC_Payment_Gateway::process_payment, WC_Order::update_status()
		 */
		public function process_payment($order_id)
		{
			$order = wc_get_order($order_id);
			$check_id	 = get_post_meta($order->get_id(), '_cardlessmoney_payment_check_id', true);
			$orderdata = $this->get_order_info($order);

			if ('cardlessmoney' === $orderdata["payment_method"]) {
				$this->log(__('Started to process order:', 'woocommerce-gateway-cardless-money') . $orderdata["id"]);
				$this->log(__('Setting order status to pending for order ', 'woocommerce-gateway-cardless-money') . $orderdata["id"]);

				// POST fields we'll be sending.
				$ve = get_option('gmt_offset') > 0 ? '+' : '-';

				$check_date	 = strtotime('now ' . $ve . get_option('gmt_offset') . ' HOURS');
				$check_date	 = date('m-d-Y', $check_date);

				if ($orderdata["billing_country"] == "US") {
					$country = 'USA';
				} else if ($orderdata["billing_country"] == "CA") {
					$country = 'CANADA';
				} else {
					$country = $orderdata["billing_country"];
				}

				if ($country == 'CANADA') {
					$currency = 'CAD';
				} else {
					$currency = 'USD';
				}

				if ($orderdata["billing_company"] != '') {
					$account_holder_name = $orderdata["billing_company"];
				} else {
					$account_holder_name = sanitize_text_field($orderdata["billing_first_name"]) . ' ' . sanitize_text_field($orderdata["billing_last_name"]);
				}

				$data = array(

					'access_token' => $this->cardless_token,
					'check[account_holder_name]' => $account_holder_name,
					'check[street_address]' => sanitize_text_field($orderdata["billing_address_1"]) . ' ' . sanitize_text_field($orderdata["billing_address_2"]),
					'check[city]' => sanitize_text_field($orderdata["billing_city"]),
					'check[state]' =>  sanitize_text_field($orderdata["billing_state"]),
					'check[zip]' =>  sanitize_text_field(trim($orderdata["billing_postcode"])),
					'check[date]' => $check_date,
					'check[check_number]' => '',
					'check[amount]' => $order->get_total(),
					'check[memo1]' => __('Order #', 'woocommerce-gateway-cardless-money') . $order_id,
					'check[routing_number]' => sanitize_text_field($this->routing_number),
					'check[checking_account_number]' => sanitize_text_field($this->account_number),
					'check[bank_name]' => sanitize_text_field($orderdata["billing_bankname"]),
					'check[bank_address1]' => sanitize_text_field($orderdata["billing_bank_address1"]),
					'check[bank_address2]' => sanitize_text_field($orderdata["billing_bank_address2"]),
					'check[bank_address3]' => sanitize_text_field($orderdata["billing_bank_address3"]),
					'check[client_email]' => sanitize_text_field($orderdata["billing_email"]),
					'check[country]' =>  $country,
					'check[currency]' => $currency,
					'check[signature]' => sanitize_text_field($orderdata["billing_signature"]),
					'check[contact_number]' => sanitize_text_field($orderdata["billing_phone"]),
					'check[transit_branch_number]' => sanitize_text_field($orderdata["billing_transit_branch_number"]),
					'check[financial_institution_number]' => sanitize_text_field($orderdata["billing_financial_institution_number"]),
					'x_delim_data'  => FALSE,
					'x_delim_char'  => ',',
				);

				// print_r($data);
				// exit();
				// Prepare payload for transer
				$this->log(__('Preparing order data, endpoint is ', 'woocommerce-gateway-cardless-money') . $this->endpoint . 'OneTimeDraftRTV');
				$data_string = http_build_query($data);
				$this->log(__('Sending POST to CardlessPay, this is what we are sending: ', 'woocommerce-gateway-cardless-money') . $data_string);

				// Send this payload to CardlessPay for processing
				$response = $this->api_call('OneTimeDraftRTV', $data);

				if (isset($response->errors) && $response->errors != '') {

					$errors = $response->errors;
					foreach ($errors as $error) {

						wc_add_notice('Payment Error: ' . $error, 'error');
						return;
					}
				} elseif (isset($response->check) && $response->check != '') {

					$check = $response->check;
					$this->log(__('Check Accepted. CardlessPay returned Check_ID: ', 'woocommerce-gateway-cardless-money') . $check_id);
					$this->log(__('Check Accepted. CardlessPay returned CheckNumber: ', 'woocommerce-gateway-cardless-money') . $check_id);
					$order->add_order_note(sprintf(__('CardlessPay check accepted (Check_ID: %s, CheckNumber: %s)', 'woocommerce-gateway-cardless-money'), $check_id, $check->check_number));
					// Add post meta
					add_post_meta($order_id, '_cardlessmoney_payment_check_id', (string) $check_id, true);
					add_post_meta($order_id, '_cardlessmoney_payment_check_number', (string) $check_id, true);
					// Mark order as processing
					$order->update_status('processing', __('Check created at Cardless Paytech and is pending the rest of the transaction process. Depending on your merchant settings, more verification may be required. You may need to verify the check has processed in the Cardless Paytech Portal manually.', 'woocommerce-gateway-cardless-money'));
					// Empty cart
					WC()->cart->empty_cart();
					//$order->reduce_order_stock();
					wc_reduce_stock_levels($order->get_id());
					// Return thankyou redirect
					return array(
						'result'	 => 'success',
						'redirect'	 => $this->get_return_url($order),
					);
				}
			}
		}

		/**
		 * Return the order information in a version independent way
		 *
		 * @param WC_Order $order
		 * @return array
		 */
		public function get_order_info($order)
		{
			$data = array(
				"id" => '',
				"payment_method" => '',
				"billing_first_name" => '',
				"billing_last_name" => '',
				"billing_company" => '',
				"billing_email" => '',
				"billing_phone" => '',
				"billing_address_1" => '',
				"billing_address_2" => '',
				"billing_city" => '',
				"billing_state" => '',
				"billing_postcode" => '',
				"billing_country" => '',
				"order_total" => '',
				"billing_transit_branch_number" => '',
				"billing_financial_institution_number" => '',
				"billing_bankname" => '',
				"billing_bank_address1" => '',
				"billing_bank_address2" => '',
				"billing_bank_address3" => '',
				"billing_signature" => '',
			);
			if (version_compare(WC_VERSION, '3.0', '<')) {
				//Do it the old school way
				$data["id"] = $order->id;
				$data["payment_method"] = $order->payment_method;
				$data["billing_first_name"] = sanitize_text_field($order->billing_first_name);
				$data["billing_last_name"] = sanitize_text_field($order->billing_last_name);
				$data["billing_company"] = sanitize_text_field($order->billing_company);
				$data["billing_email"] = sanitize_text_field($order->billing_email);
				$data["billing_phone"] = sanitize_text_field($order->billing_phone);
				$data["billing_address_1"] = sanitize_text_field($order->billing_address_1);
				$data["billing_address_2"] = sanitize_text_field($order->billing_address_2);
				$data["billing_city"] = sanitize_text_field($order->billing_city);
				$data["billing_state"] = sanitize_text_field($order->billing_state);
				$data["billing_postcode"] = sanitize_text_field(trim($order->billing_postcode));
				$data["billing_country"] = sanitize_text_field($order->billing_country);
				$data["order_total"] = $order->order_total;
				$data["billing_transit_branch_number"] = sanitize_text_field($order->billing_transit_branch_number);
				$data["billing_financial_institution_number"] = sanitize_text_field($order->billing_financial_institution_number);
				$data["billing_bankname"] = sanitize_text_field($order->billing_bankname);
				$data["billing_bank_address1"] = sanitize_text_field($order->billing_bank_address1);
				$data["billing_bank_address2"] = sanitize_text_field($order->billing_bank_address2);
				$data["billing_bank_address3"] = sanitize_text_field($order->billing_bank_address3);
				$data["billing_signature"] = $order->billing_signature;
			} else {
				//New school
				$data["id"] = $order->get_id();
				$data["payment_method"] = $order->get_payment_method();
				$data["billing_first_name"] = sanitize_text_field($order->get_billing_first_name());
				$data["billing_last_name"] = sanitize_text_field($order->get_billing_last_name());
				$data["billing_company"] = sanitize_text_field($order->get_billing_company());
				$data["billing_email"] = sanitize_text_field($order->get_billing_email());
				$data["billing_phone"] = sanitize_text_field($order->get_billing_phone());
				$data["billing_address_1"] = sanitize_text_field($order->get_billing_address_1());
				$data["billing_address_2"] = sanitize_text_field($order->get_billing_address_2());
				$data["billing_city"] = sanitize_text_field($order->get_billing_city());
				$data["billing_state"] = sanitize_text_field($order->get_billing_state());
				$data["billing_postcode"] = sanitize_text_field(trim($order->get_billing_postcode()));
				$data["billing_country"] = $order->get_billing_country();
				$data["order_total"] = $order->get_total();
				$data["billing_transit_branch_number"] = sanitize_text_field($order->billing_transit_branch_number);
				$data["billing_financial_institution_number"] = sanitize_text_field($order->billing_financial_institution_number);
				$data["billing_bankname"] = sanitize_text_field($order->billing_bankname);
				$data["billing_bank_address1"] = sanitize_text_field($order->billing_bank_address1);
				$data["billing_bank_address2"] = sanitize_text_field($order->billing_bank_address2);
				$data["billing_bank_address3"] = sanitize_text_field($order->billing_bank_address3);
				$data["billing_signature"] = $order->billing_signature;
			}
			return $data;
		}


		/**
		 * Can the order be refunded via CardlessPay?
		 *
		 * @param  WC_Order $order
		 * @return bool
		 */
		public function via_cardlessmoney($order)
		{

			return $order && get_post_meta($order->get_id(), '_cardlessmoney_payment_check_id', true);
		}
	} // END class WC_Gateway_Cardless_Pay
} // END if(!class_exists('WC_Gateway_Cardless_Pay'))
