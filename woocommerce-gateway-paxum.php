<?php
/**
 * Plugin Name: Gateway for PAXUM on WooCommerce
 * Plugin URI: https://electric-blue-industries.com/gateway-for-paxum-on-woocommerce
 * Description: Gateway for PAXUM on WooCommerce
 * Author: Electric Blue Industries Ltd.
 * Author URI: https://electric-blue-industries.com/
 * Version: 1.0.1
 * Text Domain: wc-gateway-paxum
 * Domain Path: /languages/
 *
 * Copyright: (c) 2015-2020 Electric Blue Industries Ltd. (admin@electric-blue-industris.com) and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-Paxum
 * @author    Electric Blue Industries Ltd.
 * @category  Admin
 * @copyright Copyright (c) 2015-2020, Electric Blue Industries Ltd. and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This paxum gateway forks the WooCommerce core "Cheque" payment gateway to create another paxum payment method.
 */
 
defined('ABSPATH') or exit;

// Make sure WooCommerce is active
if ( ! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {

    return;
}

/**
 * Add the gateway to WC Available Gateways
 * 
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Paxum gateway
 */
add_filter( 'woocommerce_payment_gateways', 'wc_paxum_add_to_gateways' );

function wc_paxum_add_to_gateways( $gateways ) {

	$gateways[] = 'WC_Gateway_Paxum';
	return $gateways;
}

/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_paxum_gateway_plugin_links' );

function wc_paxum_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paxum_gateway' ) . '">' . __( 'Configure', 'wc-gateway-paxum' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}

/**
 * Override number of orders listed in 'Orders' in My Account
 * 
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
add_filter( 'woocommerce_my_account_my_orders_query', 'custom_my_account_orders', 10, 1 );

function custom_my_account_orders( $args ) {

    // Set the post per page
    $args['limit'] = 20;

    return $args;
}

/**
 * Paxum Payment Gateway
 *
 * Provides an PAXUM Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class 		WC_Gateway_Paxum
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Electric Blue Industries Ltd.
 */
add_action( 'plugins_loaded', 'wc_paxum_gateway_init', 11 );

function wc_paxum_gateway_init() {

	class WC_Gateway_Paxum extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway. （管理画面用表示）
		 */
		public function __construct() {
	  
			$this->id                 = 'paxum_gateway';
			$this->icon               = apply_filters('woocommerce_offline_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __( 'PAXUM', 'wc-gateway-paxum' );
			$this->method_description = __( 'This WooCommerce extention allows PAXUM payments. PAXUM is a Canadian origined leading company in online payment and online banking, and suitable for receiving international payment at low transaction cost in USD securely. You can create a PAXUM account <a href="https://secure.paxum.com/payment/registerAccount.php?affiliateId=37420&page=register" target="newwin">here</a>.', 'wc-gateway-paxum' );
            
			// Load the settings.
			$this->init_form_fields();
            $this->init_settings();
		  
			// Define user set variables
			$this->enabled            = $this->get_option( 'enabled' );
            $this->sandbox            = $this->get_option( 'sandbox' );
			$this->email              = $this->get_option( 'paxumid' );
            $this->shared_secret      = $this->get_option( 'shared_secret' );
            $this->title              = $this->get_option( 'title' );
            $this->description        = $this->get_option( 'description' );
			$this->instructions       = $this->get_option( 'instructions', $this->description );
            
            $this->supports = array('products', 'refunds');
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            
            // PAXUM IPN endpoint. This will create 'https://yourdomain.com/wc-api/paxum_gateway/'
            add_action( 'woocommerce_api_' . 'paxum_ipn', array( $this, 'webhook' ) );

		}
	
		/**
		 * Initialize Gateway Settings Form Fields（管理画面用表示）
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_paxum_form_fields', array(
				'enabled' => array(
					'title'       => __( 'Enable/Disable', 'wc-gateway-paxum' ),
					'type'        => 'checkbox',
                    'description' => __( 'Enable this to show this option to buyers in checkout process.', 'wc-gateway-paxum' ),
					'label'       => __( 'Enable Paxum Payment', 'wc-gateway-paxum' ),
					'default'     => 'yes',
                    'desc_tip'    => true
				),
                'sandbox' => array(
					'title'       => __( 'Sandbox mode', 'wc-gateway-paxum' ),
					'type'        => 'checkbox',
                    'description' => __( 'Enable this to perform test in sandbox environment.', 'wc-gateway-paxum' ),
					'label'       => __( 'Enable Sandbox Mode', 'wc-gateway-paxum' ),
					'default'     => 'no',
                    'desc_tip'    => true,
				),
                'paxumid' => array(
					'title'       => __( 'PAXUM id', 'wc-gateway-paxum' ),
					'type'        => 'email',
					'description' => __( 'Your email address registered to PAXUM. Money paid is to be sent to a PAXUM account associated with this email. You need to pass KYC (Know Your Customer) process by PAXUM to receive payment.', 'wc-gateway-paxum' ),
					'default'     => __( 'you@yourdomain.com', 'wc-gateway-paxum' ),
					'desc_tip'    => true,
				),
				'shared_secret' => array(
					'title'       => __( 'API Shared Secret', 'wc-gateway-paxum' ),
					'type'        => 'password',
					'description' => __( 'A secret key consists of 32 alpha-numeric characters that is needed in calling PAXUM API (used for 1-click automatic refund only)', 'wc-gateway-paxum' ),
					'default'     => __( '1234567890abcdefghijklmnopqrstuv', 'wc-gateway-paxum' ),
					'desc_tip'    => true,
				),
				'title' => array(
					'title'       => __( 'Title', 'wc-gateway-paxum' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-paxum' ),
					'default'     => __( 'Paxum', 'wc-gateway-paxum' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'wc-gateway-paxum' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-paxum' ),
					'default'     => __( 'You will be fowarded to PAXUM site.', 'wc-gateway-paxum' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'wc-gateway-paxum' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-paxum' ),
					'default'     => __( 'Your payment with PAXUM has been completed.', 'wc-gateway-paxum' ),
					'desc_tip'    => true,
				),
			));
		}
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
            
			if ( $this->instructions ) {
                
				echo wpautop( wptexturize( $this->instructions ) );
                
			}
		}
	
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
            $order->update_status( 'pending-payment', __( 'Awaiting PAXUM payment', 'wc-gateway-paxum' ) );
			// Reduce stock levels
			$order->reduce_order_stock();
			// Remove cart
			WC()->cart->empty_cart();
            
            // parameters to be post to PAXUM to initiate payment process
            
            // shop url
            $shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
            // my account url
            $myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );

            if ( $myaccount_page_id ) {

                $myaccount_page_url = get_permalink( $myaccount_page_id );

            }
            
            if ( $this->sandbox == 'yes' ) {

                $sandbox_onoff = 'ON';

            } elseif ( $this->sandbox == 'no' ) {

                $sandbox_onoff = 'OFF';

            } else {

                $sandbox_onoff = 'ON';

            }
            
            // redirect url
            $redirect_url_base = plugins_url('woocommerce-gateway-paxum-poster.php', __FILE__);
            
            $redirect_url = add_query_arg( array(
                //Mandatory. This is a verified email address attached to your account
                'business_email' => $this->email,
                //Optional. ID of a logo defined within your account in My Account >> Profile Settings >> Business Logos that will be used to customize the payment confirmation page. If this field is not specified the default logo defined in your account is used. If this field is not specified and there is no logo defined within your account, no logo will be displayed.
                'business_logo_id' => '',
                //Mandatory. 1= Pay Now button; 2 = Subscribe button
                'button_type_id' => 1,
                //Optional. An internal ID from your system (WooCommerce order id)
                'item_id' => $order_id,
                //Mandatory. Name of the item that will be displayed to the buyer on the payment confirmation page
                'item_name' => 'Order item(s)',
                //Mandatory. The amount the buyer will be charged.
                'amount' => $order->get_total(),
                //Mandatory. Three letter currency code: USD, EUR, CAD, etc (for now only USD is allowed)
                'currency' => $order->get_currency(),
                //Optional. Shipping amount the buyer will be charged
                'shipping' => '',
                //Optional. Tax percent the buyer will be charged
                'tax' => '',
                //Optional. Allow the buyer to change quantities on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
                'change_quantities' => 2,
                //Optional. Allow the buyer to add special instruction on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
                'special_instructions' => 2,
                //Optional. Ask the buyer to enter shipping address on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
                'ask_shipping' => 2,
                //Optional. Ask the buyer to enter his phone on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
                'ask_phone' => 2,
                //Optional. Ask the buyer to enter his name on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
                'ask_name' => 2,
                //Optional. URL where the buyer is redirected if the payment is canceled. If not specified the buyer is taken to our website.
                'cancel_url' => $shop_page_url,
                //Optional. After the payment is successful the buyer will have an option to return to the merchant website. This is the Merchant's website URL. If not specified the buyer will not have this option
                'finish_url' => $myaccount_page_url . 'view-order/' . $order_id . '/',
                //Optional. Other variables to be submitted as field=value pairs separated by &. notify_url=https://www.mystore.com/ipn/ Add notify_url if you want to receive an instant payment notification (IPN) after the transaction was completed successfuly.
                'variables' => 'notify_url=' . get_site_url() . '/wc-api/paxum/',
                //Optional. ID of merchant defined within your account in Merchant Services >> Merchant Accounts, this ID is showing what merchant should be used for this button. If this field is not specified it means there is no merchant.
                'merchant_id' => '',
                //Optional. An internal ID of your system to identify the transaction.
                'reference_id' => $order_id,
                //Optional. This field is used only for card transactions. 1 = only authorisation, 2 = both authorisation and settlement. Default is 2.
                'button_action' => 2,
                //Subscription payment periodicity:
                //  1 = Every Week
                //  2 = Every Two Weeks
                //  3 = Every Month
                //  4 = Every Three Months
                //  5 = Every Six Months
                //  6 = Anually
                //'payment_interval' => '',
                //Depends*. The subscription will be automatically canceled at this date.
                //'end_date' => '',
                //Depends*. The subscription will be active until the user cancels it. Value of this field should be 1.
                //'user_cancel' => '',
                //Depends*. The subscription will be automatically canceled after the number of transfers specified here.
                //'transfers' => '',
                //Optional. Set ON for test.
                'sandbox' => $sandbox_onoff,
                //Opional. The error code you wold like to receive back after the input data is validated. E.g. 00, 51, ...
                'return' => 200
            ), $redirect_url_base);

			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				//'redirect'	=> $this->get_return_url( $order )
                'redirect' => esc_url_raw($redirect_url)
			);

		}
        
        /**
		 * IPN listener behavior
		 */
        public function webhook() {
            
            header( 'HTTP/1.1 200 OK' );
            
            // Receiving IPN and SANITIZATION and VALIDATION
            // parameter spec reference >> https://www.paxum.com/developers/api-documentation/ipn-pbn/ipn-notification-parameters/
            //
            // transaction_id:              Mandatory. The transaction ID.
            // transaction_description:     Mandatory. The description of the transaction
            // transaction_item_id:         Optional.  Internal item ID of the item set by the merchant
            // transaction_item_name:       Mandatory. Item name set by the merchant.
            // transaction_amount:          Mandatory. Item price.
            // transaction_status:          Mandatory. Contains "done" string.
            // transaction_exchange_rate:   Mandatory. Transaction exchange rate.
            // transaction_currency:        Mandatory. Three letter currency code. Example: USD, CAD, EUR, etc.
            // transaction_date:            Mandatory. Date/Time of the transaction. Please note that here is reported the server time.
            // transaction_type:            Mandatory. Type of the transaction can be one of the following values:
            //
            //    0 = None, 1 = Auction Goods, 2 = Goods, 3 = Auction Services, 4 = Services,
            //    5 = Quasi Cash, 6 = Payments, 7 = Money Request, 8 = Funds Added, 9 = Funds Withdrawn,
            //    10 = Currency Conversion, 11 = Balance Transfer, 12 = Refund, 13 = Fee, 14 = Transfer,
            //    15 = Cost, 16 = Purchase, 17 = Verification, 18 = Funds Added EFT, 19 = Funds Withdrawn EFT
            //
            // transaction_quantity:        Mandatory. Item quantity.
            // transaction_instructions:    Optional.  Buyer instructions sent to the merchant.
            // transaction_shipping:        Mandatory. Shipping amount. May be 0.00
            // transaction_tax:             Mandatory. Tax amount. May be 0.00
            // transaction_reference_id:    Optional. An internal ID of your system to identify the transaction.
            //
            // SANITIZE
            $transaction_id             = filter_var($_REQUEST['transaction_id'],               FILTER_SANITIZE_STRING);
            $transaction_description    = filter_var($_REQUEST['transaction_description'],      FILTER_SANITIZE_STRING);
            $transaction_item_id        = filter_var($_REQUEST['transaction_item_id'],          FILTER_SANITIZE_STRING);
            $transaction_item_name      = filter_var($_REQUEST['transaction_item_name'],        FILTER_SANITIZE_STRING);
            $transaction_amount         = $_REQUEST['transaction_amount'];                      // INT (system assigned)
            $transaction_status         = filter_var($_REQUEST['transaction_status'],           FILTER_SANITIZE_STRING);
            $transaction_exchange_rate  = filter_var($_REQUEST['transaction_exchange_rate'],    FILTER_SANITIZE_STRING);
            $transaction_currency       = filter_var($_REQUEST['transaction_currency'],         FILTER_SANITIZE_STRING);
            $transaction_date           = filter_var($_REQUEST['transaction_date'],             FILTER_SANITIZE_STRING);
            $transaction_type           = $_REQUEST['transaction_type'];                        // INT (system assigned)
            $transaction_quantity       = $_REQUEST['transaction_quantity'];                    // INT (system assigned)
            $transaction_instructions   = filter_var($_REQUEST['transaction_instructions'],     FILTER_SANITIZE_STRING);
            $transaction_shipping       = $_REQUEST['transaction_shipping'];                    // FLOAT (system assigned)
            $transaction_tax            = $_REQUEST['transaction_tax'];                         // FLOAT (system assigned)
            $transaction_reference_id   = filter_var($_REQUEST['transaction_reference_id'],     FILTER_SANITIZE_STRING);
            
            // VALIDATE not required
            
            // create array for log record text
            $log_array = array(
                // timestamp for log record
                'date'                      => (new DateTime('NOW'))->format("y:m:d h:i:s") . ' UTC',
                'transaction_id'            => $transaction_id,
                'transaction_description'   => $transaction_description,
                'transaction_item_id'       => $transaction_item_id,
                'transaction_item_name'     => $transaction_item_name,
                'transaction_amount'        => $transaction_amount,
                'transaction_status'        => $transaction_status,
                'transaction_exchange_rate' => $transaction_exchange_rate,
                'transaction_currency'      => $transaction_currency,
                'transaction_date'          => $transaction_date,
                'transaction_type'          => $transaction_type,
                'transaction_quantity'      => $transaction_quantity,
                'transaction_instructions'  => $transaction_instructions,
                'transaction_shipping'      => $transaction_shipping,
                'transaction_tax'           => $transaction_tax,
                'transaction_reference_id'  => $transaction_reference_id,
                // order process log comment (to be filled later)
                'order_process_comment'     => ''
            );
            
            // IPN記録テキストファイルの存在確認と作成
            $ipn_log = 'logs/paxum_ipn_log.txt';
            
            // Log rotation (keeps last 30 days log separately by date)
            $logfilestokeep = 30;
            
            // Log rotation
            $logfilename = $ipn_log;

            if (file_exists($logfilename)) {
                
                if (date ("Y-m-d", filemtime($logfilename)) !== date('Y-m-d')) {
                    
                    if (file_exists($logfilename . "." . $logfilestokeep)) {
                        
                        unlink($logfilename . "." . $logfilestokeep);
                        
                    }
                    
                    for ($i = $logfilestokeep; $i > 0; $i--) {
                        
                        if (file_exists($logfilename . "." . $i)) {
                            
                            $next = $i+1;
                            rename($logfilename . "." . $i, $logfilename . "." . $next);
                            
                        }
                    }
                    
                    rename($logfilename, $logfilename . ".1");
                }
            }
            
            $ipn_log_path = plugin_dir_path( __FILE__ ) . $ipn_log;
            
            if ( $log_array['transaction_item_id'] != null ) {

                $order_id = $log_array['transaction_item_id'];
                $order = wc_get_order( $order_id );

                if ( $order != null ) {

                    if ( $log_array['transaction_amount'] != null ) {

                        if ( $order->get_total() == $log_array['transaction_amount'] ) {

                            // PAXUM internal Transaction Id is passed to WC here
                            $order->payment_complete($log_array['transaction_id']);
                            $log_array['order_process_comment'] = 'success.';

                        } else {

                            $log_array['order_process_comment'] = 'payment amount not correct.';

                        }

                    } else {

                        $log_array['order_process_comment'] = 'payment amount not set';

                    }

                } else {

                    $log_array['order_process_comment'] = 'order not found';

                }

            }
            
            // create log record line
            $log_record = json_encode($log_array) . "\n";
            // logファイルに追加書き込み
            file_put_contents($ipn_log_path, $log_record, FILE_APPEND | LOCK_EX);
                
        }
        
        /**
         * Can the order be refunded via PAXUM?
         *
         * @param  WC_Order $order Order object.
         * @return bool
         */
        public function can_refund_order( $order ) {
            
            $has_shared_secret = $this->get_option( 'shared_secret' );

            return $order && $order->get_transaction_id() && $has_shared_secret;
        }
        
        /**
         * Process a refund if supported.
         *
         * @param  int    $order_id Order ID.
         * @param  float  $amount Refund amount.
         * @param  string $reason Refund reason.
         * @return bool|WP_Error
         */
        public function process_refund( $order_id, $amount = null, $reason = '' ) {
            
            $order = wc_get_order( $order_id );
            $method = 'refundTransaction';
            $email = $this->email;
            $transaction_id = $order->get_transaction_id();
            $shared_secret = $this->shared_secret;
            $key = md5($shared_secret . $transaction_id);
            
            if ( $this->sandbox != 'no' ) {
                
                $sandbox = $this->sandbox;
                $return = '200';
                
            } 

            if ( ! $this->can_refund_order( $order ) ) {
                
                return new WP_Error( 'error', __( 'Refund failed.', 'woocommerce' ) );
                
            }

            // POST refund parameters to PAXUM
            $api_url_base = 'https://secure.paxum.com/payment/api/paymentAPI.php';
            $data_string = 'method=' . $method . '&fromEmail=' . $email . '&transId=' . $transaction_id . '&key=' . $key . '&sandbox=' . $sandbox . '&return=' . $return;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            $result = curl_exec($ch);

            curl_close($ch);

            $response = [];
            $result_array = json_decode(json_encode(simplexml_load_string($result)),true);
            
            /** $result (XML)
            <?xml version="1.0"?>
            <Response>
                <Environment>PRODUCTION</Environment>
                <Method>refundTransaction</Method>
                <ResponseCode>00</ResponseCode>
                <ResponseDescription>Approved or Completed Successfully</ResponseDescription >
                <Fee>0.00</Fee>
            </Response>
            **/
            
            if ( !empty($array)) {

                $response['Environment'] =          $result_array['Environment'];           // 'PRODUCTION'
                $response['Method'] =               $result_array['Method'];                // 'refundTransaction'
                $response['ResponseCode'] =         $result_array['ResponseCode'];          // '00' when succesful
                $response['ResponseDescription'] =  $result_array['ResponseDescription'];   // 'Approved or Completed Successfully'
                $response['Fee'] =                  $result_array['Fee'];                   // '0.00'

            }

            if ( $response['ResponseCode'] != '00' ) {

                $this->log( 'Refund Failed: ' . $result->get_error_message(), 'error' );
                return new WP_Error( 'error', $result->get_error_message() );

            }

            $this->log( 'Refund Result: ' . wc_print_r( $result, true ) );

            return isset( $response['ResponseDescription'] ) ? new WP_Error( 'error', $response['ResponseDescription'] ) : false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
        }
        
    } // end \WC_Gateway_Paxum class
}