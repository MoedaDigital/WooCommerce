<?php
/**
 * Plugin Name: WooCommerce Moeda Digital Gateway
 * Plugin URI: http://docs.moeda.digital/#modulos-e-plugins-woocommerce
 * Description: Aceite as principais bandeiras de cartões em sua loja virtual com WooCommerce de uma forma simples e segura, utilizando o checkout transparente da Moeda Digital.
 * Version: 1.0.2
 * Author: Moeda Digital
 * Author URI: https://www.moeda.digital/
 * License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @package WordPress
 * @author Moeda Digital
 * @since 1.0.0
 */


/*URL de retorno Moeda Digital
http://www.site.com.br/index.php?wc-api=retorno_moeda_digital&
*/


add_action( 'plugins_loaded', 'woocommerce_moeda_digital_init', 0 );



function woocommerce_moeda_digital_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    };

    DEFINE ('PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
    DEFINE ('GATEWAY_URL', 'https://moeda.digital/Modulos/WooCommerce/Modulo.aspx?');

	/**
     * Moeda Digital Gateway Class
     */
    class WC_MoedaDigital extends WC_Payment_Gateway {

        function __construct() {

            // Register plugin information
            $this->id			    = 'moedadigital';
            $this->has_fields = true;
            $this->supports   = array(
                 'products',
                 'subscriptions',
                 'subscription_cancellation',
                 'subscription_suspension',
                 'subscription_reactivation',
                 'subscription_amount_changes',
                 'subscription_date_changes',
                 'subscription_payment_method_change',
                 'refunds'
                 );

            // Create plugin fields and settings
            $this->init_form_fields();
            $this->init_settings();

            // Get setting values
            foreach ( $this->settings as $key => $val ) $this->$key = $val;

            // Load plugin checkout icon
            $this->icon = PLUGIN_DIR . 'images/moedadigital.png';

            // Add hooks
            add_action( 'woocommerce_before_my_account',                            array( $this, 'add_payment_method_options' ) );
            add_action( 'woocommerce_update_options_payment_gateways',              array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'wp_enqueue_scripts',                                       array( $this, 'add_moeda_digital_scripts' ) );
			add_action( 'woocommerce_api_retorno_moeda_digital',                    array( $this, 'retorno_moeda_digital' ));
        }
		
		function retorno_moeda_digital(){
            $order_id = (int)$_GET['?pedido'];
            $order = wc_get_order( $order_id );

            echo 'Retorno Moeda Digital - Status do pedido: ' . $order_id . '<br/>';
            $moedadigital_request = array (
                'username' 		    => $this->username,
                'password' 	      	=> $this->password,
                'orderid' 		    => $order_id,
                'type' 			    => 'ConsultaStatus'
            );

            $response = $this->post_and_get_response( $moedadigital_request );

            echo 'Status<br/>';
            echo $response['body'];

            if ( $response['body'] == 'APROVADO') {
                $order->add_order_note( __( 'Moeda Digital payment completed. Transaction ID: ' , 'woocommerce' ) . $response['transactionid'] );
                $order->payment_complete();
            }


            exit;

		}
        /****************************************** Painel Administrativo do WordPress/WooCommerce ********************************************************/

        /**
         * Initialize Gateway Settings Form Fields.
         */
	    function init_form_fields() {

            $this->form_fields = array(
            'enabled'     => array(
              'title'       => __( 'Ativar/Desativar', 'woothemes' ),
              'label'       => __( 'Ativar Moeda Digital', 'woothemes' ),
              'type'        => 'checkbox',
              'description' => '',
              'default'     => 'no'
              ),
            'title'       => array(
              'title'       => __( 'Título', 'woothemes' ),
              'type'        => 'text',
              'description' => __( 'Título a ser exibido para seu cliente quando finalizar o pedido.', 'woothemes' ),
              'default'     => __( 'Moeda Digital', 'woothemes' )
              ),
            'description' => array(
              'title'       => __( 'Descrição', 'woothemes' ),
              'type'        => 'textarea',
              'description' => __( 'Texto a ser exibido para seu cliente quando finalizar o pedido.', 'woothemes' ),
              'default'     => 'Efetuar o pagamento através da Moeda Digital.'
              ),
            'username'    => array(
              'title'       => __( 'Token', 'woothemes' ),
              'type'        => 'text',
              'description' => __( 'Token da loja no cadatro do Moeda Digital.', 'woothemes' ),
              'default'     => ''
              ),
            'password'    => array(
              'title'       => __( 'Aplicação', 'woothemes' ),
              'type'        => 'text',
              'description' => __( 'Nome da apliação cadastrada no Moeda Digital.', 'woothemes' ),
              'default'     => ''
              ),
              );
        }

        /**
         * UI - Admin Panel Options
         */
        function admin_options() { ?>
				<h3><?php _e( 'Moeda Digital','woothemes' ); ?></h3>
			    <p><?php _e( 'Moeda Digital é a forma mais simples e segura de receber pagamentos em sua loja virtual.  Este plugin adiciona as principais bandeiras de cartões em seu site e processa pagamento. Mais detalhes em Moeda Digital.  <a href="https://moeda.digital/woocommerce/">Clique aqui</a>.', 'woothemes' ); ?></p>
			    <table class="form-table">
					<?php $this->generate_settings_html(); ?>
				</table>
			<?php }

        /***************************************************************** Tela de Pagamento do Pedido *************************************************/
        /**
         * Monta a tela de pagamento para Moeda Digital.
         */
        function payment_fields() {

            $this->icon = PLUGIN_DIR ;
            $amount = WC()->cart->total * 100;

            $moedadigital_request = array (
                      'username' 		    => $this->username,
                      'password' 	      	=> $this->password,
                      'amount' 		      	=> $amount,
                      'type' 			    => 'ConsultaParcelas'
              );

            $response = $this->post_and_get_response( $moedadigital_request );

            // Description of payment method from settings
            if ( $this->description ) { ?>
            		<p><?php echo $this->description; ?></p>
      		<?php } ?>

    <style>
    #payment .payment_methods li img {
        max-height: 4em;
        margin-top: -50px;
    }
    .woocommerce-select{
        height: 35px;
        border: 1px solid #ccc;
        border-radius: 3px 3px 0 0;
    }
    </style>

			<fieldset  style="padding-left: 40px;">
		        <?php
            $user = wp_get_current_user();
            $this->check_payment_method_conversion( $user->user_login, $user->ID );
            if ( $this->user_has_stored_data( $user->ID ) ) { ?>
						<fieldset>
							<input type="radio" name="moedadigital-use-stored-payment-info" 
                                id="moedadigital-use-stored-payment-info-yes" value="yes" checked="checked" 
                                onclick="document.getElementById('moedadigital-new-info').style.display='none'; document.getElementById('moedadigital-stored-info').style.display='block'" />
                            <label for="moedadigital-use-stored-payment-info-yes" style="display: inline;"><?php _e( 'Use a stored credit card', 'woocommerce' ) ?></label>
								</div>
                        </fieldset>

				<?php } else { ?>
              			<fieldset>
              				<!-- Show input boxes for new data -->
              				<div id="moedadigital-new-info" ></div>
              					<?php } ?>

                              <!-- Credit card type -->
                    		  <p class="form-row ">

                      			  <label for="cardtype"><?php echo __( 'Meio de Pagamento', 'woocommerce' ) ?> <span class="required">*</span></label>
                      			  <select name="cardtype" id="cardtype" class="woocommerce-select" onchange="cardtype_change()">

                                  <?php
            $pos = strpos($response['body'], 'parcela');
            if ($pos !== false) {  ?>
                                       <option value="credito">Cartão de Crédito</option>
                                  <?php } ?>

                                  <?php
            $pos = strpos($response['body'], 'Boleto');
            if ($pos !== false) {  ?>
                                      <option value="boleto">Boleto Bancário</option>
                                  <?php } ?>
                       			  </select>
                    		  </p>

							  <div class="clear"></div>

                              <!-- Credit card type -->
                              <p class="form-row ">
                                  <label for="paymenttype"><?php echo __( 'Forma de Pagamento', 'woocommerce' ) ?> <span class="required">*</span></label>
                                  <select name="paymenttypecc" id="paymenttypecc" class="woocommerce-select" onchange="paymenttypecc_change()">
                                      <?php
            $pos = strpos($response['body'], '@');
            echo substr($response['body'],0,$pos); ?>
                                  </select>
                                  <select name="paymenttypebb" id="paymenttypebb" class="woocommerce-select" style="display:none;">
                                      <?php
            $pos = strpos($response['body'], '@');
            echo substr($response['body'],$pos); ?>
                                  </select>
                                  <input id="paymenttypevalue" name="paymenttypevalue" type="text"  style="display:none;" value="<?php
            $pos = strpos($response['body'], '>');
            echo substr($response['body'],14,$pos-14); ?>" />
                              </p>
                              <div class="clear"></div>
                              <input id="lblCardType" name="lblCardType" style="display:none;" value="" />

                              <div id="divCard">
                                  <!-- Credit card number -->
                                  <p class="form-row ">

                                      <label id="lblIcon" style="display:none;"><?php echo PLUGIN_DIR ?></label>

                                      <label for="ccnum" id="lblccnum"><?php echo __( 'Número do Cartão de Crédito', 'woocommerce' ) ?> <span class="required">*</span></label>
                                      <img id="imgBandeira" alt="Bandeira" src="<?php echo $this->icon ?>images/blank.png" style="float:right; height: 32px !important;max-height: 32px !important; margin: 0px !important;" />
                                      <input type="text" class="input-text" id="ccnum" name="ccnum" maxlength="16" onblur="validaCartao();"  style="width:85%;" />
                                  </p>
                                  <div class="clear"></div>
                                  <!-- Credit card expiration -->
                                  <p class="form-row form-row-first">
                                      <label for="cc-expire-month" id="lbl-cc-expire-month"><?php echo __( 'Validade ', 'woocommerce') ?> <span class="required">*</span></label>
                                      <select name="expmonth" id="expmonth" class="woocommerce-select woocommerce-cc-month">
                                          <option value=""><?php _e( 'Mês', 'woocommerce' ) ?></option><?php
            $months = array();
            for ( $i = 1; $i <= 12; $i ++ ) {
                $num = str_pad($i, 2, "0", STR_PAD_LEFT);
                printf( '<option value="%u">%s</option>', $num, $num );
            }
                                                                                                       ?>
                                      </select>
                                  </p>
                                  <p class="form-row form-row-last">
                                      <label for="cc-expire-year"><?php echo __( '', 'woocommerce') ?> <span class="required">*</span></label>
                                      <select name="expyear" id="expyear" class="woocommerce-select woocommerce-cc-year">
                                          <option value=""><?php _e( 'Ano', 'woocommerce' ) ?></option><?php
            $years = array();
            for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
                printf( '<option value="20%u">20%u</option>', $i, $i );
            }
                                                                                                       ?>
                                      </select>
                                  </p>
                                  <div class="clear"></div>
                                  <p class="form-row">
                                      <label for="cvv" id="lblcvv"><?php _e( 'Codigo de Seguraça', 'woocommerce' ) ?> <span class="required">*</span></label>
                                      <input oninput="validate_cvv(this.value)" type="text" class="input-text" id="cvv" name="cvv" maxlength="4" style="width:45px" />
                                      <span class="help" id="lblhelp"><?php _e( '3 ou 4 digitos localizados no verso do cartão.', 'woocommerce' ) ?></span>
                                  </p>
                              </div>

                              <div id="divBoleto"></div>
            			</fieldset>
			</fieldset>
<?php
        }

		/**
         * Process the payment and return the result.
         */
		function process_payment( $order_id ) {

			global $woocommerce;
			$order = new WC_Order( $order_id );
            $user = new WP_User( $order->user_id );
            $this->check_payment_method_conversion( $user->user_login, $user->ID );

			// Convert CC expiration date from (M)M-YYYY to MMYY
			$expmonth = $this->get_post( 'expmonth' );
			if ( $expmonth < 10 ) $expmonth = '0' . $expmonth;
			if ( $this->get_post( 'expyear' ) != null ) $expyear = substr( $this->get_post( 'expyear' ), -2 );

            // Create server request using stored or new payment details
			if ( $this->get_post( 'moedadigital-use-stored-payment-info' ) == 'yes' ) {

                // Short request, use stored billing details
                $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );
                $id = $customer_vault_ids[ $this->get_post( 'moedadigital-payment-method' ) ];
                if( substr( $id, 0, 1 ) !== '_' ) $base_request['customer_vault_id'] = $id;
                else {
                    $base_request['customer_vault_id'] = $user->user_login;
                    $base_request['billing_id']        = substr( $id , 1 );
                    $base_request['ver']               = 2;
                }

            } else {

                // Full request, new customer or new information
                $base_request = array (
                  'ccnumber' 	=> $this->get_post( 'ccnum' ),
                  'cvv' 		=> $this->get_post( 'cvv' ),
                  'ccexp' 		=> $expmonth . $expyear,
                  'firstname'   => $order->billing_first_name,
                  'lastname' 	=> $order->billing_last_name,
                  'address1' 	=> $order->billing_address_1,
                  'city' 	    => $order->billing_city,
                  'state' 		=> $order->billing_state,
                  'zip' 		=> $order->billing_postcode,
                  'country' 	=> $order->billing_country,
                  'phone' 		=> $order->billing_phone,
                  'email'       => $order->billing_email,
                  );
            }

            $card_type =  $this->get_post('lblCardType') ;

            $transaction_details = array (
              'username'  => $this->username,
              'password'  => $this->password,
              'amount' 	  => $order->order_total,
              'type' 	  => $this->salemethod,
              'payment'   => $card_type,
              'value' 	  => $this->get_post('paymenttypevalue'),
              'orderid'   => $order->id,
              'ipaddress' => $_SERVER['REMOTE_ADDR'],
              );

            // Send request and get response from server
            $response = $this->post_and_get_response( array_merge( $base_request, $transaction_details ) );
            $order->add_order_note( __($response['body'] , 'woocommerce' ) );

            // Check response
            if ( $response['body'] == 'APROVADO') {
                // Success
                $order->add_order_note( __( 'Moeda Digital payment completed. Transaction ID: ' , 'woocommerce' ) . $response['transactionid'] );
                $order->payment_complete();

                $url = $this->get_return_url( $order );
                 return array (
                  'result'   => 'success',
                  'redirect' => $url,
                );
            }

            if ( $response['body'] == 'NEGADO') {
                // Decline
                $order->add_order_note( __( 'Transação não aprovada, verifique os dados informados.', 'woocommerce' ) );
                wc_add_notice( __( 'Transação não aprovada, verifique os dados informados.', 'woocommerce' ), $notice_type = 'error' );
                return array (
                    'result'   => 'fail',
                    'redirect' => '',
                );
            }

            if ( strlen($response['body']) > 8) {
                if ( substr($response['body'],0,8) == 'PENDENTE') {

                    $urlboleto = substr($response['body'],9) ;


                    $param = base64_encode($order_id  . "|" . substr($response['body'],0,8) . "|" . $urlboleto . "|" .PLUGIN_DIR);

                    $order->add_order_note( __( 'Transação pendente. Aguardando confirmação de pagamento do boleto bancário.' .  substr($response['body'],8), 'woocommerce' ) );
                    wc_add_notice( __( 'Transação pendente. Aguardando confirmação de pagamento do boleto bancário.' ." <a href='". $urlboleto, 'woocommerce' ) . "'>Abrir Boleto</a>", $notice_type = 'error' );
                    $url = $this->get_return_url( $order );

                    return array (
                      'result'   => 'success',
                      'redirect' =>  $urlboleto, //index.php/md-thank-you' . '?param=' . $param, 
                    );
                }
            }
		}

        /*************************************************** Funcões **************************************************************************************/

        /**
         * Get details of a payment method for the current user from the Customer Vault
         */
        function get_payment_method( $payment_method_number ) {

            if( $payment_method_number < 0 ) die( 'Invalid payment method: ' . $payment_method_number );

            $user = wp_get_current_user();
            $customer_vault_ids = get_user_meta( $user->ID, 'customer_vault_ids', true );
            if( $payment_method_number >= count( $customer_vault_ids ) ) return null;

            $query = array (
              'username' 		  => $this->username,
              'password' 	      => $this->password,
              'report_type'       => 'customer_vault',
              );

            $id = $customer_vault_ids[ $payment_method_number ];
            if( substr( $id, 0, 1 ) !== '_' ) $query['customer_vault_id'] = $id;
            else {
                $query['customer_vault_id'] = $user->user_login;
                $query['billing_id']        = substr( $id , 1 );
                $query['ver']               = 2;
            }
            $response = wp_remote_post( GATEWAY_URL, array(
              'body'         => $query,
              'timeout'      => 45,
              'redirection'  => 5,
              'httpversion'  => '1.0',
              'blocking'     => true,
              'headers'      => array(),
              'cookies'      => array(),
              'ssl_verify'   => false
              )
            );

            //Do we have an error?
            if( is_wp_error( $response ) ) return null;

            // Check for empty response, which means method does not exist
            if ( trim( strip_tags( $response['body'] ) ) == '' ) return null;

            // Format result
            $content = simplexml_load_string( $response['body'] )->customer_vault->customer;
            if( substr( $id, 0, 1 ) === '_' ) $content = $content->billing;

            return $content;
        }

        /**
         * Check if a user's stored billing records have been converted to Single Billing. If not, do it now.
         */
        function check_payment_method_conversion( $user_login, $user_id ) {
        }

        /**
         * Check if the user has any billing records in the Customer Vault
         */
        function user_has_stored_data( $user_id ) {
            return false;
        }

        /**
         * Check payment details for valid format
         */
		function validate_fields() {

            if ( $this->get_post( 'moedadigital-use-stored-payment-info' ) == 'yes' ) return true;

			global $woocommerce;

			// Check for saving payment info without having or creating an account
			if ( $this->get_post( 'saveinfo' )  && ! is_user_logged_in() && ! $this->get_post( 'createaccount' ) ) {
                wc_add_notice( __( 'Sorry, you need to create an account in order for us to save your payment information.', 'woocommerce'), $notice_type = 'error' );
                return false;
            }

            $card_type           = $this->get_post('lblCardType') ;
            $cardType            = $this->get_post( 'cardtype' );
			$cardType            = $this->get_post( 'cardtype' );
			$cardNumber          = $this->get_post( 'ccnum' );
			$cardCSC             = $this->get_post( 'cvv' );
			$cardExpirationMonth = $this->get_post( 'expmonth' );
			$cardExpirationYear  = $this->get_post( 'expyear' );

            if ($card_type != 'Boleto'){
                // Check card number
                if ( empty( $cardNumber ) || ! ctype_digit( $cardNumber ) ) {
                    wc_add_notice( __( 'Número de cartão inválido.', 'woocommerce' ), $notice_type = 'error' );
                    return false;
                }

                if ( $this->cvv == 'yes' ){
                    // Check security code
                    if ( ! ctype_digit( $cardCSC ) ) {
                        wc_add_notice( __( 'Código de segurança inválido.', 'woocommerce' ), $notice_type = 'error' );
                        return false;
                    }
                    if ( ( strlen( $cardCSC ) != 3 && in_array( $cardType, array( 'Visa', 'MasterCard', 'Discover' ) ) ) || ( strlen( $cardCSC ) != 4 && $cardType == 'American Express' ) ) {
                        wc_add_notice( __( 'Código de segurança inválido.', 'woocommerce' ), $notice_type = 'error' );
                        return false;
                    }
                }

                // Check expiration data
                $currentYear = date( 'Y' );

                if ( ! ctype_digit( $cardExpirationMonth ) || ! ctype_digit( $cardExpirationYear ) ||
                     $cardExpirationMonth > 12 ||
                     $cardExpirationMonth < 1 ||
                     $cardExpirationYear < $currentYear ||
                     $cardExpirationYear > $currentYear + 20
                ) {
                    wc_add_notice( __( 'Validade inválida', 'woocommerce' ), $notice_type = 'error' );
                    return false;
                }

                // Strip spaces and dashes
                $cardNumber = str_replace( array( ' ', '-' ), '', $cardNumber );
            }
			return true;

		}

		/**
         * Send the payment data to the gateway server and return the response.
         */
        private function post_and_get_response( $request ) {
            global $woocommerce;

            // Encode request
            $post = http_build_query( $request, '', '&' );

			// Send request
            $content = wp_remote_post( GATEWAY_URL, array(
                'body'        => $post,
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(),
                'cookies'     => array(),
                'ssl_verify'  => false
               )
            );

            // Quit if it didn't work
            if ( is_wp_error( $content ) ) {
                wc_add_notice( __( 'Falha na conexão com Moeda Digital', 'woocommerce' ) . ' ( ' . $content->get_error_message() . ' )', $notice_type = 'error' );
                return null;
            }
            return $content;
        }

        /**
         * Add ability to view and edit payment details on the My Account page.(The WooCommerce 'force ssl' option also secures the My Account page, so we don't need to do that.)
         */
		function receipt_page( $order ) {
			echo '<p>' . __( 'Thank you for your order.', 'woocommerce' ) . '</p>';
		}

        /**
         * Include jQuery and our scripts
         */
        function add_moeda_digital_scripts() {
            wp_enqueue_script( 'jquery' );
            //wp_enqueue_script( 'edit_billing_details', PLUGIN_DIR . 'js/edit_billing_details.js', array( 'jquery' ), 1.0 );
            wp_enqueue_script( 'check_cvv', PLUGIN_DIR . 'js/check_cvv.js', array( 'jquery' ), 1.0 );
        }

        /**
         * Get the current user's login name
         */
        private function get_user_login() {
            global $user_login;
            get_currentuserinfo();
            return $user_login;
		}

		/**
         * Get post data if set
         */
		private function get_post( $name ) {
			if ( isset( $_POST[ $name ] ) ) {
				return $_POST[ $name ];
			}
			return null;
		}

		/**
         * Check whether an order is a subscription
         */
		private function is_subscription( $order ) {
            return class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order );
		}

        /**
         * Generate a string of 36 alphanumeric characters to associate with each saved billing method.
         */
        function random_key() {

            $valid_chars = array( 'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9' );
            $key = '';
            for( $i = 0; $i < 36; $i ++ ) {
                $key .= $valid_chars[ mt_rand( 0, 61 ) ];
            }
            return $key;
        }
	}

	/**
     * Add the gateway to woocommerce
     */
	function add_moeda_digital_gateway( $methods ) {
		$methods[] = 'WC_MoedaDigital';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_moeda_digital_gateway' );
}