<?php
/**
 * Copyright: (c) 2015-2020 Electric Blue Industries Ltd. (admin@electric-blue-industris.com) and WooCommerce
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-Paxum
 * @author    Electric Blue Industries Ltd.
 * @category  Front
 * @copyright Copyright (c) 2015-2020, Electric Blue Industries Ltd. and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * This code POST parameters to PAXUM by receiving parameters received with GET method.
**/


// referal check to prevent direct access
$allowed_host = $_SERVER['HTTP_HOST'];
$host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

if(substr($host, 0 - strlen($allowed_host)) == $allowed_host) {

    // no direct access & referal from same server
    //
    // business_email:          Mandatory. This is a verified email address attached to your account
    // business_logo_id:        Optional. ID of a logo defined within your account in My Account >> Profile Settings >> Business Logos that will be used to customize the payment confirmation page. If this field is not specified the default logo defined in your account is used. If this field is not specified and there is no logo defined within your account, no logo will be displayed.
    // button_type_id:          Mandatory. 1= Pay Now button; 2 = Subscribe button
    // item_id:                 Optional. An internal ID from your system
    // item_name:               Mandatory. Name of the item that will be displayed to the buyer on the payment confirmation page
    // amount:                  Mandatory. The amount the buyer will be charged.
    // currency:                Mandatory. Three letter currency code: USD, EUR, CAD, etc (for now only USD is allowed)
    // shipping:                Optional. Shipping amount the buyer will be charged
    // tax:                     Optional. Tax percent the buyer will be charged
    // change_quantities:       Optional. Allow the buyer to change quantities on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
    // special_instructions:    Optional. Allow the buyer to add special instruction on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
    // ask_shipping:            Optional. Ask the buyer to enter shipping address on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
    // ask_phone:               Optional. Ask the buyer to enter his phone on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
    // ask_name:                Optional. Ask the buyer to enter his name on the payment confirmation page. 1 = Yes; 2 = No; default value is 2
    // cancel_url:              Optional. URL where the buyer is redirected if the payment is canceled. If not specified the buyer is taken to our website.
    // finish_url:              Optional. After the payment is successful the buyer will have an option to return to the merchant website. This is the Merchant's website URL. If not specified the buyer will not have this option
    // variables:               Optional. Other variables to be submitted as field=value pairs separated by &. notify_url=https://www.mystore.com/ipn/ Add notify_url if you want to receive an instant payment notification (IPN) after the transaction was completed successfuly.
    // merchant_id:             Optional. ID of merchant defined within your account in Merchant Services >> Merchant Accounts, this ID is showing what merchant should be used for this button. If this field is not specified it means there is no merchant.
    // reference_id:            Optional. An internal ID of your system to identify the transaction.
    // button_action:           Optional. This field is used only for card transactions. 1 = only authorisation, 2 = both authorisation and settlement. Default is 2.

    // loading wp-include files (to include wp-includes/formatting.php for WP sanitization functions)
    require_once( dirname(__FILE__) . '/../../../wp-load.php' );
    
    // SANITIZE
    $business_email         = sanitize_email($_REQUEST["business_email"]);          // EMAIL
    $business_logo_id       = sanitize_text_field($_REQUEST["business_logo_id"]);   // STRING
    $button_type_id         = (int)$_REQUEST["button_type_id"];                     // INT (system assigned) casted
    $item_id                = sanitize_text_field($_REQUEST["item_id"]);            // STRING
    $item_name              = sanitize_text_field($_REQUEST["item_name"]);          // STRING
    $amount                 = (float)$_REQUEST["amount"];                           // FLOAT (system assigned) casted
    $currency               = sanitize_text_field($_REQUEST["currency"]);           // STRING
    $shipping               = (float)$_REQUEST["shipping"];                         // FLOAT (system assigned) casted
    $tax                    = (float)$_REQUEST["tax"];                              // FLOAT (system assigned) casted
    $change_quantities      = (int)$_REQUEST["change_quantities"];                  // INT (system assigned) casted
    $special_instructions   = (int)$_REQUEST["special_instructions"];               // INT (system assigned) casted
    $ask_shipping           = (int)$_REQUEST["ask_shipping"];                       // INT (system assigned) casted
    $ask_phone              = (int)$_REQUEST["ask_phone"];                          // INT (system assigned) casted
    $ask_name               = (int)$_REQUEST["ask_name"];                           // INT (system assigned) casted
    $cancel_url             = esc_url_raw($_REQUEST["cancel_url"]);                 // URL
    $finish_url             = esc_url_raw($_REQUEST["finish_url"]);                 // URL
    $variables              = sanitize_text_field($_REQUEST["variables"]);          // STRING
    $merchant_id            = sanitize_text_field($_REQUEST["merchant_id"]);        // STRING
    $reference_id           = sanitize_text_field($_REQUEST["reference_id"]);       // STRING
    $button_action          = (int)$_REQUEST["button_action"];                      // INT (system assigned) casted
    $sandbox                = sanitize_text_field($_REQUEST["sandbox"]);            // STRING
    $return                 = (int)$_REQUEST["return"];                             // INT (system assigned) casted
    
    // VALIDATE
    $validate_flag = 0;
    // EMAIL
    if ( !is_email($business_email) ) {  $validate_flag = 11; }
    // URL
    if ( !wp_http_validate_url($cancel_url) ) {    $validate_flag = 31; }
    if ( !wp_http_validate_url($finish_url) ) {    $validate_flag = 32; }
    
    if ( $validate_flag != 0 ) {
        echo '<center>';
        echo 'error code ' . $validate_flag . ': found inappropriate value(s) submitted. please contact administrator.<br>';
        echo '<a href="#" onclick="javascript:window.history.back(-1);return false;">Back</a>';
        echo '</center>';
        exit;
    }
    
} else {
    
    // diret access refused
    echo header("HTTP/1.0 403 Forbidden");
    exit;
    
}

?>

<html>
    <header>
        <title>forwarding to PAXUM</title>
    </header>
    <body bgcolor="#2c3a4e">
        <form name="PaxumForm" id="PaxumForm" action="https://www.paxum.com/payment/phrame.php?action=displayProcessPaymentLogin" method="POST">
            <input type='hidden' name='business_email' value='<?php echo $business_email; ?>' />
            <input type='hidden' name='business_logo_id' value='<?php echo $business_logo_id; ?>' />
            <input type='hidden' name='button_type_id' value='<?php echo $button_type_id; ?>' />
            <input type='hidden' name='item_id' value='<?php echo $item_id; ?>' />
            <input type='hidden' name='item_name' value='<?php echo $item_name; ?>' />
            <input type='hidden' name='amount' value='<?php echo $amount; ?>' />
            <input type='hidden' name='currency' value='<?php echo $currency; ?>' />
            <input type='hidden' name='shipping' value='<?php echo $shipping; ?>' />
            <input type='hidden' name='tax' value='<?php echo $tax; ?>' />
            <input type='hidden' name='change_quantities' value='<?php echo $change_quantities; ?>' />
            <input type='hidden' name='special_instructions' value='<?php echo $special_instructions; ?>' />
            <input type='hidden' name='ask_shipping' value='<?php echo $ask_shipping; ?>' />
            <input type='hidden' name='ask_phone' value='<?php echo $ask_phone; ?>' />
            <input type='hidden' name='ask_name' value='<?php echo $ask_name; ?>' />
            <input type='hidden' name='cancel_url' value='<?php echo $cancel_url; ?>' />
            <input type='hidden' name='finish_url' value='<?php echo $finish_url; ?>' />
            <input type='hidden' name='variables' value='<?php echo $variables; ?>' />
            <input type='hidden' name='merchant_id' value='<?php echo $merchant_id; ?>' />
            <input type='hidden' name='reference_id' value='<?php echo $reference_id; ?>' />
            <input type='hidden' name='button_action' value='<?php echo $button_action; ?>' />
            <input type='hidden' name='sandbox' value='<?php echo $sandbox; ?>' />
            <input type='hidden' name='return' value='<?php echo $return; ?>' />
        </form>
        
        <?php //echo '<pre>';var_dump($_REQUEST);echo '</pre>'; ?>
        
        <script type="text/javascript">
            function formAutoSubmit () {
                var form = document.getElementById("PaxumForm");
                form.submit();
            }
            window.onload = formAutoSubmit;
        </script>
        
        <center>
            <img src="./assets/images/PAXUM.png"><br>
            <font color="#f0f0f0">initiating payment process</font>
        </center>
        
    </body>
</html>