<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Monnify_Gateway extends WC_Payment_Gateway
    {

        // public $monnify_test_mode;
        // public $monnify_public_key;
        // public $monnify_public_key_test;
        // public $monnify_secret_key;
        // public $monnify_secret_key_test;
        // public $monnify_contract_code;
        // public $monnify_contract_code_test; 

        /**
         * Monnify testmode.
         *
         * @var string
         */
        public $monnify_test_mode; // Flag to indicate whether the gateway is in test mode or not.

        /**
         * Monnify public key for production.
         *
         * @var string
         */
        public $monnify_public_key; // Public key for the Monnify production environment.

        /**
         * Monnify public key for testing.
         *
         * @var string
         */
        public $monnify_public_key_test; // Public key for the Monnify test environment.

        /**
         * Monnify secret key for production.
         *
         * @var string
         */
        public $monnify_secret_key; // Secret key for the Monnify production environment.

        /**
         * Monnify secret key for testing.
         *
         * @var string
         */
        public $monnify_secret_key_test; // Secret key for the Monnify test environment.

        /**
         * Monnify contract code for production.
         *
         * @var string
         */
        public $monnify_contract_code; // Contract code for the Monnify production environment.

        /**
         * Monnify contract code for testing.
         *
         * @var string
         */
        public $monnify_contract_code_test; // Contract code for the Monnify test environment.

      


        /**
         * Constructor for the payment gateway.
         */
        public function __construct()
        {
            $this->id = 'monnify'; 
            $this->has_fields = true; // If you need a custom creditcard form, set it to true
            $this->method_title =  __('Monnify Woocommerce Payment', 'monnify-official');
            $this->method_description = sprintf(__('Monnify Woocommerce Payment Plugin allows you to integrate <a href="%s" target="_blank">Monnify Payments</a> to your WordPress Website. Supports various Monnify payment method options such as Pay with Transfer, Card, USSD, or Phone Number. <a href="%s" target="_blank">Click here to get your API keys</a>.', 'monnify-official'), __('https://monnify.com'), __('https://app.monnify.com/login')); // will be displayed on the options page

            $this->supports = array(
                'products',
                'tokenization',
                'subscriptions',
                'multiple_subscriptions',
                'subscription_cancellation',
                'subscription_suspension',
                'subscription_reactivation',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change',
                'subscription_payment_method_change_customer',
                'subscription_payment_method_change_admin',

            );
            
            // Load the settings.
            $this->init_form_fields();

            $this->init_settings(); 

            // Define your settings
            $this->title            = $this->get_option('title');
            $this->description      = $this->get_option('description');
            $this->enabled          = $this->get_option('enabled');
            $this->monnify_test_mode         = $this->get_option('monnify_test_mode') === 'yes' ? true : false;

            // Apikeys
            $this->monnify_public_key              = $this->get_option('monnify_public_key');
            $this->monnify_public_key_test         = $this->get_option('monnify_public_key_test');
            $this->monnify_secret_key              = $this->get_option('monnify_secret_key');
            $this->monnify_secret_key_test         = $this->get_option('monnify_secret_key_test');
            $this->monnify_contract_code           = $this->get_option('monnify_contract_code');
            $this->monnify_contract_code_test      = $this->get_option('monnify_contract_code_test');

            // monnify endpoints
             $this->monnify_endpoint          = $this->monnify_test_mode ? "https://sandbox.monnify.com" : "https://api.monnify.com";


            // main config
            $this->monnify_api_key     = $this->monnify_test_mode ? $this->monnify_public_key_test : $this->monnify_public_key;
            $this->monnify_secret      = $this->monnify_test_mode ? $this->monnify_secret_key_test : $this->monnify_secret_key ;
            $this->monnify_contract    = $this->monnify_test_mode ? $this->monnify_contract_code_test : $this->monnify_contract_code ;

 
            // Hooks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('woocommerce_available_payment_gateways', array($this, 'add_gateway_to_checkout'));
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_api_wc_monnify_gateway', array($this, 'monnify_trans_verify_payment'));
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Monnify Woocommerce',
                    'type'        => 'checkbox',
                    'description' => 'Enable or disable the Monnify Woocommerce payment gateway.',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Pay securely using Monnify (cards, USSD, Bank Transfer etc. )',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pay securely using Monnify Payment.',
                ),
                'monnify_test_mode' => array(
                    'title'       => 'Test Mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Enable test mode for testing purposes.',
                    'default'     => 'no',
                ),

                // test details
                'monnify_public_key_test' => array(
                    'title'       => 'Test API Key on Sandbox Enviroment',
                    'type'        => 'text',
                    'description' => 'Enter your Monnify Test API key.',
                    'default'     => '',

                ),
                'monnify_secret_key_test' => array(
                    'title'       => 'Test Secret key on Sandbox Enviroment',
                    'type'        => 'text',
                    'description' => 'Enter your Monnify Secret Key.',
                    'default'     => '',

                ),
                'monnify_contract_code_test' => array(
                    'title'       => 'Test Contract Code on Sandbox Enviroment',
                    'type'        => 'text',
                    'description' => 'Enter your Monnify Test Contract code.',
                    'default'     => '',

                ),

                // test details
                'monnify_public_key' => array(
                    'title'       => 'Live API Key',
                    'type'        => 'text',
                    'description' => 'Enter your Live Monnify API key.',
                    'default'     => '',

                ),
                'monnify_secret_key' => array(
                    'title'       => 'Live Secret Key',
                    'type'        => 'text',
                    'description' => 'Enter your Live Monnify Secret Key.',
                    'default'     => '',

                ),
                'monnify_contract_code' => array(
                    'title'       => 'Live Contract Code',
                    'type'        => 'text',
                    'description' => 'Enter your Live Monnify contract code.',
                    'default'     => '',

                ),
                'payment_methods' => array(
                    'title'       => 'Supported Payment Methods',
                    'type'        => 'multiselect',
                    'description' => 'Select the payment methods you want to support. Hold "SHIFT" to select multiple. Works only in Live Mode.',
                    'default'     => array('CARD', 'ACCOUNT_TRANSFER', 'USSD', 'PHONE_NUMBER'),
                    'options'     => array(
                        'CARD'            => 'Card',
                        'ACCOUNT_TRANSFER' => 'Account Transfer',
                        'USSD'            => 'USSD',
                        'PHONE_NUMBER'    => 'Phone Number',
                    ),
                ),
            );
        }

        /**
         * Output the payment gateway to the checkout page.
         *
         * @param array $gateways The available payment gateways.
         * @return array The updated list of payment gateways.
         */
        public function add_gateway_to_checkout($gateways)
        {
            // if ('no' == $this->enabled) {
            //     unset($gateways[$this->id]);
            // } else {
            //     $gateways[] = $this;
            // }

            return $gateways;
        }
   
        /*
        * Process the payment and return the result
        */
        public function process_payment($order_id)
        {
            if ( is_user_logged_in() && isset( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ) && true === (bool) $_POST[ 'wc-' . $this->id . '-new-payment-method' ] && $this->saved_cards ) {

                        update_post_meta( $order_id, '_wc_monnify_save_card', true );

                    }

                    $order = wc_get_order( $order_id );

                    return array(
                        'result'   => 'success',
                        'redirect' => $order->get_checkout_payment_url( true ),
                    );
        }

      
 
 
        /**
         * Payment scripts and styles
         */ 
            public function payment_scripts() {
                
                // Add payment scripts and styles

                if (!is_checkout_pay_page() || $this->enabled === 'no') {
                    return;
                }
                $order_key = urldecode( $_GET['key'] );

                $order_id = absint(get_query_var('order-pay'));
                $order    = wc_get_order($order_id);

                $payment_method = method_exists($order, 'get_payment_method') ? $order->get_payment_method() : $order->payment_method;

                if ($this->id !== $payment_method) {
                    return;
                }

                // Add custom payment scripts and styles here

                wp_enqueue_script('jquery');
                wp_enqueue_script('monnify', 'https://sdk.monnify.com/plugin/monnify.js', array('jquery'), WC_MONNIFY_VERSION, false);
                wp_enqueue_script('wc_monnify', plugins_url('assets/js/monnify.js', WC_MONNIFY_MAIN_FILE), array('jquery', 'monnify'), WC_MONNIFY_VERSION, false);

                $selected_payment_methods = $this->get_option('payment_methods', array(
                    'CARD',
                    'ACCOUNT_TRANSFER',
                    'USSD',
                    'PHONE_NUMBER',
                ));
            
                $monnify_params = array(
                    'selectedPaymentMethods' => $selected_payment_methods,
                    'key'              => $this->monnify_api_key,
                    'contractCode'     => $this->monnify_contract,
                    'monnify_test_mode'=> $this->monnify_test_mode,
                    'mon_redirect_url'   => WC()->api_request_url('WC_Monnify_Gateway') . '?monnify_order_id=' . $order_id,
                    'email'            => '',
                    'amount'           => '',
                    'txnref'           => '',
                    'currency'         => '', 
                    'bank_channel'      => in_array('ACCOUNT_TRANSFER', $selected_payment_methods),
                    'card_channel'      => in_array('CARD', $selected_payment_methods),
                    'ussd_channel'      => in_array('USSD', $selected_payment_methods),
                    'phone_number_channel' => in_array('PHONE_NUMBER', $selected_payment_methods), 
                    'first_name'       => '',
                    'last_name'        => '',
                    'phone'            => '', 
                );

                if (is_checkout_pay_page() && get_query_var('order-pay') && $order->get_id() === $order_id && $order->get_order_key() === sanitize_text_field(urldecode($_GET['key']))) {
                    $monnify_params['email']        = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
                    $monnify_params['amount']       = $order->get_total();
                    $monnify_params['txnref']       = "MNFY_WP_" . $order_id . '_' . time();
                    $monnify_params['currency']     = method_exists($order, 'get_currency') ? $order->get_currency() : $order->order_currency;
                    $monnify_params['first_name']   = $order->get_billing_first_name();
                    $monnify_params['last_name']    = $order->get_billing_last_name();
                    $monnify_params['phone']        = $order->get_billing_phone();
                }

                update_post_meta($order_id, '_monnify_txn_ref', $monnify_params['txnref']);

                wp_localize_script('wc_monnify', 'woo_monnify_params', $monnify_params);
            }

      
    /**
     * Display Monnify payment icon.
     */
    public function get_icon()
    {
        $icon = '<img src="' . WC_HTTPS::force_https_url( plugins_url('assets/images/monnify.png', WC_MONNIFY_MAIN_FILE) ) . '" alt="Monnify Payment Gateway"  />';

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    /**
     * Displays the payment page.
     *
     * @param $order_id
     */
    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);

        echo '<div id="seye">' . __('Thank you for your order, please click the button below to pay with Monnify.', 'monnify-official') . '</div>';

        echo '<div id="monnify_form"><form id="order_review" method="post" action="' . WC()->api_request_url('WC_Monnify_Gateway') . '"></form><button class="button alt" id="wc-monnify-gateway-button">' . __('Pay Now', 'monnify-official') . '</button>';
 
    }

    /**
     * Verify Monnify webhook payment
     */
    public function monnify_trans_verify_payment()
    {
        // Verify and process Monnify payment on redirect
        // Check if mnfy_reference is set and not empty
        if (isset($_GET["mnfy_reference"]) && $_GET["mnfy_reference"] != "undefined" && $_GET["mnfy_reference"] != "") {
            // Check if monnify_order_id is set and URL decoded successfully
            if (isset($_GET['monnify_order_id']) && ($order_id = sanitize_text_field(urldecode($_GET['monnify_order_id'])))) {
                $mnfy_reference = sanitize_text_field($_GET['mnfy_reference']);

                // If order_id is empty, try to set it again
                if (!$order_id) {
                    $order_id = sanitize_text_field(urldecode($_GET['monnify_order_id']));
                }

                // Get the WooCommerce order
                $order = wc_get_order($order_id);

                // Make an HTTP request for transaction verification
                $monnify_login_url = $this->monnify_endpoint . '/api/v1/auth/login';

                $headers = array(
                    'Authorization' => 'Basic ' . base64_encode($this->monnify_api_key . ":" . $this->monnify_secret),
                );

                $args = array(
                    'headers' => $headers,
                    'timeout' => 60,
                );

                // Query
                $request = wp_remote_post($monnify_login_url, $args);

                // Check if the request was successful
                if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
                    // Access More Resources
                    $monnify_response = json_decode(wp_remote_retrieve_body($request));
                    $complex_token = $monnify_response->responseBody->accessToken;

                    // Verify Payment
                    $monnify_transaction_status = $this->monnify_endpoint . '/api/v2/transactions/' . urlencode($mnfy_reference);
                    $headers_status = array(
                        'Authorization' => 'Bearer ' . $complex_token,
                    );

                    $args_status = array(
                        'headers' => $headers_status,
                        'timeout' => 60,
                    );

                    // Query
                    $request_status = wp_remote_get($monnify_transaction_status, $args_status);

                    // Check if the verification request was successful
                    if (!is_wp_error($request_status) && 200 === wp_remote_retrieve_response_code($request_status)) {
                        $monnify_response_status = json_decode(wp_remote_retrieve_body($request_status));

                        // Check if paymentStatus is "PAID"
                        if ($monnify_response_status->responseBody->paymentStatus === "PAID") {
                            // Clear the order
                            $order->payment_complete($mnfy_reference);
                            $order->update_status('completed');
                            $order->add_order_note('Payment was successful on Monnify');
                            $order->add_order_note(sprintf(__('Payment via Monnify successful (Transaction Reference: %s)', 'wc-monnify-gateway'), $mnfy_reference));

                            // Customer Note
                            $customer_note  = 'Thank you for your order.<br>';
                            $customer_note .= 'Your payment was successful, we are now <strong>processing</strong> your order.';

                            $order->add_order_note($customer_note, 1);

                            wc_add_notice($customer_note, 'notice');

                            // Clear Cart
                            WC()->cart->empty_cart();
                        }
                    } else {
                        // If verification request fails
                        $order->update_status('Failed');
                        update_post_meta($order_id, '_transaction_id', $mnfy_reference);

                        $notice      = sprintf(__('Thank you for shopping with us.%1$sYour payment is currently having issues with verification and .%1$sYour order is currently on-hold.%2$sKindly contact us for more information regarding your order and payment status.', 'wc-monnify-gateway'), '<br />', '<br />');
                        $notice_type = 'notice';

                        // Add Customer Order Note
                        $order->add_order_note($notice, 1);

                        // Add Admin Order Note
                        $admin_order_note = sprintf(__('<strong>Look into this order</strong>%1$sThis order is currently on hold.%2$sReason: Payment cannot be verified.%3$swhile the <strong>Monnify Transaction Reference:</strong> %4$s', 'wc-monnify-gateway'), '<br />', '<br />', '<br />', $mnfy_reference);
                        $order->add_order_note($admin_order_note);

                        function_exists('wc_reduce_stock_levels') ? wc_reduce_stock_levels($order_id) : $order->reduce_order_stock();

                        wc_add_notice($notice, $notice_type);

                        exit;
                    }
                }

                // Quit and redirect
                wp_redirect($this->get_return_url($order));
                exit;
            }
        }

        // Redirect to the cart page if mnfy_reference is not set or monnify_order_id is not provided
        wp_redirect(wc_get_page_permalink('cart'));
        exit;

    } 
}
 
