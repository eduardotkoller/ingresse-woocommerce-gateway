<?php 
/*
 * Plugin Name: WooCommerce Ingresse Credit Card Payment Gateway
 * Plugin URI: https://github.com/eduardotkoller/ingresse-woocommerce-gateway
 * Description: Take credit card payments on your store using ingresse.com whitelist payment gateway.
 * Author: Eduardo Koller (BossaBox)
 * Author URI: https://github.com/eduardotkoller
 * Version: 1.0.0
 */


add_filter( 'woocommerce_payment_gateways', 'ingresse_add_cc_gateway_class' );
function ingresse_add_cc_gateway_class( $gateways ) {
	$gateways[] = 'WC_Ingresse_CC_Gateway'; //the class name
	return $gateways;
}
add_action( 'plugins_loaded', 'ingresse_init_cc_gateway_class' );
function ingresse_init_cc_gateway_class() {

    class WC_Ingresse_CC_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'ingresse_cc'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Ingresse';
            $this->method_description = 'Use Ingresse.com whitelist payments to proccess your credit card payments'; // will be displayed on the options page
        
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
        
            // Method with all the options fields
            $this->init_form_fields();
        
            // Load the settings.
            $this->init_settings();
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->debug = $this->get_option( 'debug' );
            $this->testmode = $this->get_option( 'testmode' );
            $this->api_key = $this->get_option( 'api_key' );
        
            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        
            // You can also register a webhook here
            add_action( 'woocommerce_api_ingresse_cc_postback', array( $this, 'webhook' ) );
        } 

        public function payment_scripts() {
            // we need JavaScript to process a token only on cart/checkout pages, right?
            if ( ! is_cart() && ! is_checkout() ) {
                return;
            }
        
            // if our payment gateway is disabled, we do not have to enqueue JS too
            if ( 'no' === $this->enabled ) {
                return;
            }
        
            // no reason to enqueue JavaScript if API keys are not set
            if ( empty( $this->api_key ) ) {
                return;
            }
        
            // js to prettify the cc form
            wp_enqueue_script( 'card_js',  plugins_url( 'card.js', __FILE__ ));
        
            // and this is our custom JS in your plugin directory
            wp_register_script( 'woocommerce_ingress_cc_form', plugins_url( 'form.js', __FILE__ ), array( 'jquery', 'card_js' ) );
        
            wp_enqueue_script( 'woocommerce_ingress_cc_form' );
        }

        public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Ingresse Credit Card Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Cartão de Crédito',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Pague com seu cartão de crédito',
                ),
                'debug' => array(
                    'title'       => 'Debug mode',
                    'label'       => 'Enable Debug Mode',
                    'type'        => 'checkbox',
                    'description' => 'Enable Debug mode for developers. Will print a lot of things during payment process.',
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'testmode' => array(
                    'title'       => 'Test mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Enable Test mode. In test mode, you can put any value in the credit card form and select the result you want.',
                    'default'     => 'no',
                    'desc_tip'    => true,
                ),
                'api_key' => array(
                    'title'       => 'Ingresse API Key',
                    'type'        => 'text'
                )
            );
        }

        public function payment_fields() {
            if('yes' === $this->debug) {
                echo wpautop(wp_kses_post('Debug mode is active!'));
            }
            echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

            // Add this action hook if you want your custom gateway to support it
            do_action( 'woocommerce_credit_card_form_start', $this->id );
        
            // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
            echo '
            <div class="card-wrapper"></div>
            <div class="form-row form-row-wide"><label>Número do cartão <span class="required">*</span></label>
                <input id="ingresse_ccNo" type="text" autocomplete="off" name="ingresse_ccNo">
                </div>
                <div class="form-row form-row-wide"><label>Nome no cartão <span class="required">*</span></label>
                <input id="ingresse_ccName" type="text" autocomplete="off" name="ingresse_ccName">
                </div>
                <div class="form-row form-row-first">
                    <label>Validade <span class="required">*</span></label>
                    <input id="ingresse_expdate" type="text" name="ingresse_expdate" autocomplete="off" placeholder="MM/AA">
                </div>
                <div class="form-row form-row-last">
                    <label>CVV <span class="required">*</span></label>
                    <input id="ingresse_cvv" type="password" name="ingresse_cvv" autocomplete="off" placeholder="CVV">
                </div>
                <div class="clear"></div>';
            if('yes' === $this->testmode) {
                echo '<label>Resultado (Test mode)</label>
                <select name="ingresse_result" required>
                    <option disabled selected>Selecione</option>
                    <option value="approved">Aprovado</option>
                    <option value="refused">Recusado</option>
                </select>';
            }
            do_action( 'woocommerce_credit_card_form_end', $this->id );
        
            echo '<div class="clear"></div></fieldset>';
        }

        public function process_payment($order_id) {
            global $woocommerce;

            $order = wc_get_order($order_id);
            $order_items = $order->get_items();

            if('yes' === $this->debug){
                //wc_add_notice('Order: '.print_r($order, true));
                foreach($order_items as $order_item) {
                    $product_data = $order_item->get_data();
                    $vendor = get_wcmp_product_vendors( $product_data['product_id'] );
                    wc_add_notice('Item: '.print_r($product_data, true).'<br>Vendor:'.var_export($vendor, true));
                }
                //wc_add_notice('Items:'.var_export($order->get_items(), true));
            }
            if('yes' === $this->testmode && isset($_POST['ingresse_result'])) {
                $result = $_POST['ingresse_result'];
                $order->update_meta_data('ingresse_transactionId', 'TESTMODE');
                if($result=='approved') {
                    $order->payment_complete();
                    $order->reduce_order_stock();
                    $woocommerce->cart->empty_cart();
                    return array(
                        'result' => 'success',
                        'redirect' => $this->get_return_url($order)
                    );
                } else {
                    wc_add_notice('O pagamento foi recusado. Verifique se as informações do cartão de crédito estão corretas.');
                    return;
                }
            } else {

                $items = array();
                foreach($order_items as $order_item) {
                    $data = $order_item->get_data();
                    $item = array();
                    $item['externalId'] = $data['product_id'];
                    $item['name'] = $data['name'].' x'.$data['quantity'];
                    $item['quantity'] = 1;
                    $item['unitPrice'] = $data['total']*100;
                    $items[] = $item;
                }
                $startUrl = 'https://api.ingresse.com/shop?apikey='.$this->api_key;
                $args = array(
                    'domain' => get_site_url(),
                    'path' => '/checkout',
                    'extras' => $items
                );

                if('yes'===$this->debug) {
                    wc_add_notice('Args for startTransaction on Ingresse.com: '.json_encode($args));
                }

                $data = wp_remote_post($startUrl, array(
                    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                    'body'        => json_encode($args),
                    'method'      => 'POST',
                    'data_format' => 'body'
                ));
                if(!is_wp_error($data)) {

                    $response = json_decode($data['body']);

                    if('yes' === $this->debug) {
                        wc_add_notice('startTransaction result:'.$data['body']);
                    }
    
                    //save transactionId to order meta -- we should save it to order transaction id when it is complete using payment_complete()
                    if(isset($response->responseData->data->transactionId)) {
                        $transactionId = $response->responseData->data->transactionId;
                        $order->update_meta_data('ingresse_transactionId', $transactionId);
                    }
    
                    //now we pay this transaction using user cc info
                    $payUrl = "https://api.ingresse.com/shop/{$transactionId}/payment?apikey={$this->api_key}";
    
                    $args = array(
                        'document' => isset($_POST['billing_cpf']) ? $_POST['billing_cpf'] : $_POST['billing_cnpj'],
                        'method' => 'creditCard',
                        'capture' => true,
                        'softDescriptor' => 'MundoPsicodelico',
                        'creditCard' => array(
                            'installments' => 1,
                            'number' => preg_replace('/[^0-9]/', '', $_POST['ingresse_ccNo']),
                            'holder' => $_POST['ingresse_ccName'],
                            'expiration' => $_POST['ingresse_expdate'],
                            'cvv' => $_POST['ingresse_cvv'],
                            'brand' => $_POST['ingresse_ccBrand']
                        ),
                        'customer' => array(
                            'name' => $order->get_billing_first_name().' '.$order->get_billing_last_name(),
                            'address' => array(
                                'street' => $order->get_billing_address_1(),
                                'number' => $_POST['billing_number'],
                                'district' => $_POST['billing_neighborhood'],
                                'zipcode' => preg_replace('/[^0-9]/', '', $order->get_billing_postcode()),
                                'city' => $order->get_billing_city(),
                                'state' => $order->get_billing_state(),
                                'country' => $order->get_billing_country(),
                            )
                        )
                    );
    
                    $data = wp_remote_post($startUrl, array(
                        'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                        'body'        => json_encode($args),
                        'method'      => 'POST',
                        'data_format' => 'body'
                    )); 

                    if(!is_wp_error($data)) {
                        $response = json_decode($data['body']);
                        if(isset($response->responseData->status) && $response->responseData->status=='approved') {
                            $order->payment_complete($transactionId);
                            $order->reduce_order_stock();
                            $woocommerce->cart->empty_cart();
                            return array(
                                'result' => 'success',
                                'redirect' => $this->get_return_url($order)
                            );
                        } else if(isset($response->responseData->status) && $response->responseData->status=='authorized') {
                            // authorized but not captured -- shoudln't happen as we pass capture = true, what to do?
                            wc_add_notice('Não foi possível realizar a compra no seu cartão de crédito. Verifique se suas informações estão corretas e tente novamente.');
                            return;
                        } else {
                            wc_add_notice('Compra recusada. Verifique suas informações e tente novamente.');
                            return;
                        }
                    }
                    wc_add_notice('Houve um problema na transação.');
                    return;
                }
                wc_add_notice('O pagamento não foi concluído. Entre em contato com um administrador do site.');
                return;

            }
        }

        public function webhook() {
            //get order status (see ingresse docs/example), get order by transactionId, update status and reduce stock
        }
    }   

}


//para boleto, usar $order->update_status('pending');