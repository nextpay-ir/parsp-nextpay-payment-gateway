<?php
/**
 * @connect_module_class_name CNextpay
 *
 */
require_once "nextpay_payment.php";
class CNextpay extends PaymentModule{
	
	function _initVars(){
		
		$this->title 		= CNEXTPAY_TTL;
		$this->description 	= CNEXTPAY_DSCR;
		$this->sort_order 	= 1;
		
		$this->Settings = array( 
			"CONF_PAYMENTMODULE_NEXTPAY_APIKEY",
			"CONF_PAYMENTMODULE_NEXTPAY_RIAL_CURRENCY"
			);
	}

	function after_processing_html( $orderID ) 
	{
	  
		$order = ordGetOrder( $orderID );
		if ( $this->_getSettingValue('CONF_PAYMENTMODULE_NEXTPAY_RIAL_CURRENCY') > 0 )
		{
			$Mellatcurr = currGetCurrencyByID ( $this->_getSettingValue('CONF_PAYMENTMODULE_NEXTPAY_RIAL_CURRENCY') );
			$Mellatcurr_rate = $Mellatcurr["currency_value"];
		}
		if (!isset($Mellatcurr) || !$Mellatcurr)
		{
			$Mellatcurr_rate = 1;
		}

		$order_amount = round(100*$order["order_amount"] * $Mellatcurr_rate)/100;
		
		$modID =  $this ->get_id();
		$callbackUrl = CONF_FULL_SHOP_URL."?nextpay&modID=$modID";
		
		$api_key = $this->_getSettingValue('CONF_PAYMENTMODULE_NEXTPAY_APIKEY');
		
		$parameters = array
		(
		    'api_key'	=> $api_key,
		    'order_id'	=> $orderID,
		    'callback_uri' 	=> $callbackUrl,
		    'amount'	=> $order_amount,
		);
		try {
		    $nextpay = new Nextpay_Payment();
		    $nextpay->setDefaultVerify(0);
		    $result = $nextpay->token();
		    if(intval($result->code) == -1){
			    $nextpay->send($result->trans_id);
		    } else {
			    $res="<p><center>";
			    $res.="متاسفانه امکان پرداخت وجود ندارد ."."<br><br><b>"."دلیل شماره خطا:". $result->code;
			    $res.="<br>".$nextpay->code_error(intval($result->code))."</center></b></p>";
		    }

		}catch (Exception $e) { echo 'Error'. $e->getMessage();  }

		return $res;
	}

	function _initSettingFields(){

		
		$this->SettingsFields['CONF_PAYMENTMODULE_NEXTPAY_APIKEY'] = array(
			'settings_value' 		=> '', 
			'settings_title' 			=> CNEXTPAY_CFG_APIKEY_TTL, 
			'settings_description' 	=> CNEXTPAY_CFG_APIKEY_DSCR, 
			'settings_html_function' 	=> 'setting_TEXT_BOX(0,', 
			'sort_order' 			=> 1,
		);

		$this->SettingsFields['CONF_PAYMENTMODULE_NEXTPAY_RIAL_CURRENCY'] = array(
			'settings_value' 		=> '0', 
			'settings_title' 			=> CNEXTPAY_CFG_RIAL_CURRENCY_TTL, 
			'settings_description' 	=> CNEXTPAY_CFG_RIAL_CURRENCY_DSCR, 
			'settings_html_function' 	=> 'setting_CURRENCY_SELECT(', 
			'sort_order' 			=> 1,
		);
	}
}
?>