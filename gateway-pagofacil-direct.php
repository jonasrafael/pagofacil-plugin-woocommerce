<?php

    /*
    Plugin Name: Pago Facil Direct Gateway for WooCommerce
    Plugin URI: http://www.patsatech.com
    Description: WooCommerce Plugin for accepting payment through Pago Facil Direct Gateway.
    Author: IRMAcreative / PatSaTech
    Version: 1.3
    Author URI: http://www.irmacreative.com
    */

add_action('plugins_loaded', 'init_woocommerce_pagofacil_direct', 0);

function init_woocommerce_pagofacil_direct() {

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }

class woocommerce_pagofacil_direct extends WC_Payment_Gateway {

	public function __construct() {
		global $woocommerce;

        $this->id			= 'pagofacil_direct';
        $this->method_title = __( 'Pago Facil Direct', 'woocommerce' );
		$this->icon     	= apply_filters( 'woocommerce_pagofacil_direct_icon', '' );
        $this->has_fields 	= TRUE;

		$default_card_type_options = array(
			'VISA' 	=> 'Visa', 
			'MC'   	=> 'MasterCard',
			'AMEX' 	=> 'American Express',
			'DISC' 	=> 'Discover',
			'JCB'	=> 'JCB',
			'DIN'=> 'DINERS'
		);
		$this->card_type_options = apply_filters( 'woocommerce_pagofacil_direct_card_types', $default_card_type_options );
		
		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->is_description_empty();

		// Define user set variables
		$this->title		= $this->get_option( 'title' );
		$this->description	= $this->get_option( 'description' );
		$this->sucursal 	= $this->get_option( 'sucursal' );
		$this->usuario		= $this->get_option( 'usuario' );
		$this->testmode		= $this->get_option( 'testmode' );
		$this->enabledivisa	= $this->get_option( 'enabledivisa' );
		$this->sendemail	= $this->get_option( 'sendemail' );
		$this->divisa		= $this->get_option( 'divisa' );
		$this->cardtypes	= $this->get_option( 'cardtypes' );
		$this->showdesc		= $this->get_option( 'showdesc' );                
                // add 10/03/2014
                $this->msi              = $this->get_option( 'msi' );
		
                
		if($this->testmode == 'yes'){
			$this->request_url = 'https://www.pagofacil.net/st/public/Wsrtransaccion/index/format/json/?method=transaccion';
		}else{
			$this->request_url = 'https://www.pagofacil.net/ws/public/Wsrtransaccion/index/format/json/?method=transaccion';
		}
		
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );

    }
	
	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	function get_icon() {
		global $woocommerce;
		
		$icon = '';
		if ( $this->icon ) {
			// default behavior
			$icon = '<img src="' . $woocommerce->force_ssl( $this->icon ) . '" alt="' . $this->title . '" />';
		} elseif ( $this->cardtypes ) {
			// display icons for the selected card types
			$icon = '';
			foreach ( $this->cardtypes as $cardtype ) {
				if ( file_exists( plugin_dir_path( __FILE__ ) . '/images/card-' . strtolower( $cardtype ) . '.png' ) ) {
					$icon .= '<img src="' . $woocommerce->force_ssl( plugins_url( '/images/card-' . strtolower( $cardtype ) . '.png', __FILE__ ) ) . '" alt="' . strtolower( $cardtype ) . '" />';
				}
			}
		}
		
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

     /**
     * To Check if Description is Empty
     */
    function is_description_empty() {

		$showdesc = '';

		return($showdesc);
    }

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('Pago Facil Direct', 'woothemes'); ?></h3>
    	<p><?php _e('Pago Facil Gateway works by charging the customers Credit Card on site.', 'woothemes'); ?></p>
    	<table class="form-table">
    	<?php
    			// Generate the HTML For the settings form.
    			$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()

	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
		
		$currency_code_options = get_woocommerce_currencies();
		
		unset($currency_code_options['MXN']);
		
		foreach ( $currency_code_options as $code => $name ) {
			$currency_code_options[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
		}
    	
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woothemes' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Pago Facil Gateway', 'woothemes' ),
							'default' => 'yes'
						),
			'title' => array(
							'title' => __( 'Title', 'woothemes' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
							'default' => __( 'Credit Card', 'woothemes' )
						),
			'showdesc' => array(
							'title' => __( 'Show Description', 'woothemes' ),
							'type' => 'checkbox',
							'label' => __( 'To Show Description', 'woothemes' ),
							'default' => 'no'
						),
			'description' => array(
							'title' => __( 'Description', 'woothemes' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ),
							'default' => __("Enter your Credit Card Details below.", 'woothemes')
						),
			'sucursal' => array(
							'title' => __( 'Sucursal', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Sucursal; this is needed in order to take payment.', 'woothemes' ), 
							'default' => ''
						),
			'usuario' => array(
							'title' => __( 'Usuario', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your Usuario; this is needed in order to take payment.', 'woothemes' ), 
							'default' => ''
						), 
			'sendemail' => array(
							'title' => __( 'Enable PagoFacil Notifiaction Emails', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Allow PagoFacil to Send Notification Emails.', 'woothemes' ), 
							'default' => 'no'
						),
			'enabledivisa' => array(
							'title' => __( 'Enable Divisa', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable sending the Currency Code to Pago Facil via divisa parameter.', 'woothemes' ), 
							'default' => 'no'
						),
			'divisa' => array(
							'title' 	=> __( 'Divisa', 'woocommerce' ),
							'desc' 		=> __( "This controls what currency that is being sent in divisa parameter to Pago Facil.", 'woocommerce' ),
							'default'	=> 'USD',
							'type' 		=> 'select',
							'options'   => $currency_code_options
						),
			'testmode' => array(
							'title' => __( 'Sandbox', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Sandbox', 'woothemes' ), 
							'default' => 'no'
						),
			'cardtypes'	=> array(
							'title' => __( 'Accepted Card Logos', 'woothemes' ), 
							'type' => 'multiselect', 
							'description' => __( 'Select which card types you accept to display the logos for on your checkout page.  This is purely cosmetic and optional, and will have no impact on the cards actually accepted by your account.', 'woothemes' ), 
							'default' => '',
							'options' => $this->card_type_options,
						)
                            // add 10/03/2014
                            ,'msi' => array(
                                'title' => __('Installments', 'woothemes')
                                ,'label' => __( 'Enable Installments', 'woothemes' )
                                ,'type' => 'checkbox'
                                ,'default' => 'no'
                            )
			);

    } // End init_form_fields()
	
    /**
	 * There are no payment fields for nmi, but we want to show the description if set.
	 **/
    function payment_fields() {

		if ($this->showdesc == 'yes') {
			echo wpautop(wptexturize($this->description));
		}
		else {
			$this->is_description_empty();
		}
		
		?>
		<p class="form-row" style="width:200px;">
		    <label>Card Number <span class="required">*</span></label>
		    <input class="input-text" style="width:180px;" type="text" size="16" maxlength="16" name="pagofacil_direct_creditcard" />
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first" style="width:200px;">
		    <label>Expiration Month <span class="required">*</span></label>
		    <select name="pagofacil_direct_expdatemonth">
		        <option value=01> 1 - January</option>
		        <option value=02> 2 - February</option>
		        <option value=03> 3 - March</option>
		        <option value=04> 4 - April</option>
		        <option value=05> 5 - May</option>
		        <option value=06> 6 - June</option>
		        <option value=07> 7 - July</option>
		        <option value=08> 8 - August</option>
		        <option value=09> 9 - September</option>
		        <option value=10>10 - October</option>
		        <option value=11>11 - November</option>
		        <option value=12>12 - December</option>
		    </select>
		</p>
		<p class="form-row form-row-second" style="width:150px;">
		    <label>Expiration Year  <span class="required">*</span></label>
		    <select name="pagofacil_direct_expdateyear">
			<?php
		    $today = (int)date('y', time());
			$today1 = (int)date('Y', time());
		    for($i = 0; $i < 8; $i++)
		    {
			?>
		        <option value="<?php echo $today; ?>"><?php echo $today1; ?></option>
			<?php
		        $today++;
				$today1++;
		    }
			?>
		    </select>
		</p>
		<div class="clear"></div>
		<p class="form-row" style="width:200px;">
		    <label>Card CVV <span class="required">*</span></label>

		    <input class="input-text" style="width:100px;" type="text" size="5" maxlength="5" name="pagofacil_direct_cvv" />
		</p>
		<div class="clear"></div>
                <?php
                // add 10/03/2014
                if ($this->msi == 'yes')
                {
                ?>
                    <p class="form-row" style="width:200px;">
                        <label>Installments</label>
                        <select name="pagofacil_direct_msi" style="width:133px;">
                            <option value="00">Seleccione</option>
                            <optgroup label="MasterCard/Visa"></optgroup>
                            <option value="03">3 Meses</option>
                            <option value="06">6 Meses</option>
                            <optgroup label="American Express"></optgroup>
                            <option value="03">3 Meses</option>
                            <option value="06">6 Meses</option>
                            <option value="09">9 Meses</option>
                            <option value="12">12 Meses</option>
                        </select>                        
                    </p>
                    <div class="clear"></div>
		<?php
                }
    }
	
    public function validate_fields()
    {
        global $woocommerce;

        if (!$this->isCreditCardNumber($_POST['pagofacil_direct_creditcard']))
            $woocommerce->add_error( __('(Credit Card Number) is not valid.', 'woothemes'));

        if (!$this->isCorrectExpireDate($_POST['pagofacil_direct_expdatemonth'], $_POST['pagofacil_direct_expdateyear']))
            $woocommerce->add_error( __('(Card Expire Date) is not valid.', 'woothemes'));

        if (!$_POST['pagofacil_direct_cvv'])
			$woocommerce->add_error( __('(Card CVV) is not entered.', 'woothemes'));
    }
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
        global $woocommerce;
		
		$order = new WC_Order( $order_id );
		
		$order->billing_phone = str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $order->billing_phone );		                
                
    	$transaction = array(
                        'idServicio'        => urlencode('3'),
                        'idSucursal'        => urlencode($this->sucursal),
                        'idUsuario'         => urlencode($this->usuario),
                        'nombre'            => urlencode($order->billing_first_name),
                        'apellidos'         => urlencode($order->billing_last_name),
                        'numeroTarjeta'     => urlencode($_POST["pagofacil_direct_creditcard"]),
                        'cvt'               => urlencode($_POST["pagofacil_direct_cvv"]),
                        'cp'                => urlencode($order->billing_postcode),
                        'mesExpiracion'     => urlencode($_POST["pagofacil_direct_expdatemonth"]),
                        'anyoExpiracion'    => urlencode($_POST["pagofacil_direct_expdateyear"]),
                        'monto'             => urlencode($order->get_order_total()),//formato 1000.00
                        'email'             => urlencode($order->billing_email),
                        'telefono'          => urlencode($order->billing_phone), // son 10 digitos
                        'celular'           => urlencode($order->billing_phone), // son 10 digitos
                        'calleyNumero'      => urlencode($order->billing_address_1),
                        'colonia'           => urlencode("N/A"),
                        'municipio'         => urlencode($order->billing_city),
                        'estado'            => urlencode( ($order->billing_state == '' ? "N/A" : $order->billing_state ) ),
                        'pais'              => urlencode($woocommerce->countries->countries[ $order->billing_country ]),
                        'idPedido'          => urlencode($order_id),
                        'param1'            => urlencode(ltrim($order->get_order_number(), '#')),
                        'param2'            => urlencode($order->order_key),
                        'param3'            => urlencode(),
                        'param4'            => urlencode(),
                        'param5'            => urlencode(),
                        'ip'                => urlencode($this->getIpBuyer()),
                        'httpUserAgent'     => urlencode($_SERVER['HTTP_USER_AGENT'])                        
                    );
		
		if($this->enabledivisa == 'yes'){
			$transaction = array_merge( $transaction, array( 'divisa' => urlencode( $this->divisa ) ) );
		}
		
		if($this->sendemail != 'yes'){
			$transaction = array_merge( $transaction, array( 'noMail' => urlencode( '1' ) ) );
		}
                                
                // add 10/03/2014                
                if ($this->msi == 'yes')
                {
                    if (trim($_POST["pagofacil_direct_msi"]) != '00')
                    {
                        $transaction = array_merge(
                            $transaction, array(
                                            'plan' => urlencode('MSI')
                                            ,'mensualidades' => urlencode(trim($_POST["pagofacil_direct_msi"]))
                                        )
                        );
                    }
                }
		
        $data='';
        foreach ($transaction as $key => $value){
            $data.="&data[$key]=$value";
        }
		
		$response = wp_remote_post( 
		    $this->request_url.$data, 
		    array(
		        'method' => 'POST',
		        'timeout' => 120,
		        'httpversion' => '1.0',
		        'sslverify' => false
		    )
		);
	
		if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) { 
			
        	$response = json_decode($response['body'],true);
			
			$response = $response['WebServices_Transacciones']['transaccion'];
			
		   	if($response["autorizado"] == "1" && strtolower($response['status']) == 'success') {
				
				// Payment completed
			    $order->add_order_note( sprintf( __('Pago Facil %s. The Pago Facil Transaction ID %s and Authorization ID %s.', 'woothemes'), $response["texto"], $response["transaccion"], $response["autorizacion"] ) );
				
			    $order->payment_complete();
				
				return array(
					'result' 	=> 'success',
					'redirect'	=>  $this->get_return_url($order)
				);
				
			}else{
				
				if(isset($response['texto'])){
					
					$message = sprintf( __('Transaction Failed. %s', 'woothemes'), $response['texto'] ).'<br>';
					
					foreach( $response['error'] as $k => $v ){
						$message .= $v.'<br>';
					}
				
					$woocommerce->add_error( $message );
				    $order->add_order_note( $message );
					
				}else{
					$woocommerce->add_error( sprintf( __('Transaction Failed. %s', 'woothemes'), $response['response']['message'] ) );
				    $order->add_order_note( sprintf( __('Transaction Failed. %s', 'woothemes'), $response['response']['message'] ) );
				}
			
			}
			
		}else{
			
			$woocommerce->add_error(__('Gateway Error. Please Notify the Store Owner about this error.', 'woothemes'));
			$order->add_order_note(__('Gateway Error. Please Notify the Store Owner about this error.', 'woothemes'));
		} 	
		
	}
	
        /**
         * Obtiene la ip real del comprador
         * @author ivelazquex <isai.velazquez@gmail.com>
         * @return string
         */
        private function getIpBuyer()
        {
            if(isset($_SERVER["HTTP_CLIENT_IP"]))
            {
                if (!empty($_SERVER["HTTP_CLIENT_IP"]))
                {
                    if (strtolower($_SERVER["HTTP_CLIENT_IP"]) != "unknown")
                    {
                        $ip = $_SERVER["HTTP_CLIENT_IP"];
						if (strpos($ip, ",") !== FALSE)
						{
							$ip = substr($ip, 0, strpos($ip, ","));
						}
                        return  trim($ip);
                    }
                }
            }
            
            if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
            {
                if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
                {
                    if (strtolower($_SERVER["HTTP_X_FORWARDED_FOR"]) != "unknown")
                    {
                        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
						if (strpos($ip, ",") !== FALSE)
						{
							$ip = substr($ip, 0, strpos($ip, ","));
						}
                        return  trim($ip);
                    }
                }
            }
            
            return $_SERVER['REMOTE_ADDR'];
            
        }
               
	private function isCreditCardNumber($toCheck)
    {
        if (!is_numeric($toCheck))
            return false;

        $number = preg_replace('/[^0-9]+/', '', $toCheck);
        $strlen = strlen($number);
        $sum    = 0;

        if ($strlen < 13)
            return false;

        for ($i=0; $i < $strlen; $i++)
        {
            $digit = substr($number, $strlen - $i - 1, 1);
            if($i % 2 == 1)
            {
                $sub_total = $digit * 2;
                if($sub_total > 9)
                {
                    $sub_total = 1 + ($sub_total - 10);
                }
            }
            else
            {
                $sub_total = $digit;
            }
            $sum += $sub_total;
        }

        if ($sum > 0 AND $sum % 10 == 0)
            return true;

        return false;
    }
	
	private function isCorrectExpireDate($month, $year)
    {
        $now       = time();
        $result    = false;
        $thisYear  = (int)date('y', $now);
        $thisMonth = (int)date('m', $now);

        if (is_numeric($year) && is_numeric($month))
        {
            if($thisYear == (int)$year)
	        {
	            $result = (int)$month >= $thisMonth;
	        }			
			else if($thisYear < (int)$year)
			{
				$result = true;
			}
        }

        return $result;
    }
}

/**
 * Add the gateway to WooCommerce
 **/
function add_pagofacil_direct_gateway( $methods ) {
	$methods[] = 'woocommerce_pagofacil_direct'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_pagofacil_direct_gateway' );

}