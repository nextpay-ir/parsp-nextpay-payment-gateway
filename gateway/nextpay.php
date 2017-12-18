<?php
   
if ( isset($_GET['nextpay'] ))
{
  $trans_id = isset($_POST['trans_id']) ? $_POST['trans_id'] : false ;
  $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : false ;

  if (!$trans_id) {
      echo 'خطا در انجام عملیات بانکی ، شناسه تراکنش موجود نمی باشد';
      die();
  }

  if (!is_string($trans_id) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $trans_id) !== 1)) {
      echo 'تراکنش ارسال شده معتبر نمیباشد';
      die();
  }

  if (isset($_GET['modID']) && isset($trans_id) && isset($order_id)) {

      $modID = $_GET['modID'];
      
      $q = db_query("SELECT * FROM ".SETTINGS_TABLE." WHERE settings_constant_name='CONF_PAYMENTMODULE_NEXTPAY_APIKEY_$modID'");
      $res = db_fetch_row($q);
      
      if(!empty($res['settings_value'])){
	      $payment['api_key'] = $res['settings_value'];
      }else{
	      Redirect( "index.php" );
      }
      
      $comStatID = _getSettingOptionValue('CONF_COMPLETED_ORDER_STATUS');
      
      if($order['StatusID'] != $comStatID){
	  $order =_getOrderById($order_id);
	  $api_key = $payment['api_key'];
	  
	  if(!isset($order["order_amount"])){
	      $order = ordGetOrder( $order_id );
	      if ( $this->_getSettingValue('CONF_PAYMENTMODULE_NEXTPAY_RIAL_CURRENCY') > 0 )
	      {
		      $curr = currGetCurrencyByID ( $this->_getSettingValue('CONF_PAYMENTMODULE_NEXTPAY_RIAL_CURRENCY') );
		      $curr_rate = $curr["currency_value"];
	      }
	      if (!isset($curr) || !$curr)
	      {
		      $curr_rate = 1;
	      }
	  }

	  $order_amount = round(100*$order["order_amount"] * $curr_rate)/100;
	  
	  $parameters = array
	  (
	      'api_key'		=> $api_key,
	      'order_id'	=> $order_id,
	      'trans_id' 	=> $trans_id,
	      'amount'		=> $order_amount,
	  );
	  try {
	      $nextpay = new Nextpay_Payment();
	      $nextpay->setDefaultVerify(0);
	      $result = $nextpay->verify_request($parameters);
	      if( $result < 0 ) {
		  $body="<center>عملیات پرداخت با خطا روبرو شد<br>";
		  $body.=$nextpay->code_error(intval($result->code))."</center>";
	      } elseif ($result==0) {		  
		  $pininfo = ostSetOrderStatusToOrder($order_id, $comStatID, 'سفارش شما از طریق نکست پی پرداخت شد ', 1);
		  $body = "<font color='green'>با تشکر ، پرداخت با موفقیت انجام شد .</font><br> شماره پیگیری  : $trans_id<br>";
	      }else{
		  $body="<center>عملیات پرداخت با خطا روبرو شد<br>";
		  $body.=$nextpay->code_error(intval($result->code))."</center>";
	      }
	  }catch (Exception $e) { echo 'Error'. $e->getMessage();  }
						  
	  $smarty->assign("payment_name","پرداخت از طریق بانک ملت");                                                               				
	  $smarty->assign("page_body", $body );
	  $smarty->assign("main_content_template", "pay_result.tpl.html" );
      }
      else
      {
	  $smarty->assign("main_content_template", "page_not_found.tpl.html" );
      }
  }
?>