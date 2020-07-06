<?php

/*

data_feed.php

Sample Data feed Page

*/


//include('datafeed_checkout_process.php');
include('includes/application_top.php');
include('includes/classes/payment.php');
include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);

// Self defined functions
//
// Append associative array elements
function array_push_associative(&$arr) {
    $args = func_get_args();
    array_unshift($args); // remove &$arr argument
    foreach ($args as $arg) {
        if (is_array($arg)) {
            foreach ($arg as $key => $value) {
                $arr[$key] = $value;
                $ret++;
            }
        }
    }
   
    return $ret;
}

// Obtain variables from Request

$src	= $HTTP_POST_VARS["src"];

$prc	= $HTTP_POST_VARS["prc"];

$Ord	= $HTTP_POST_VARS["Ord"];

$Holder	= $HTTP_POST_VARS["Holder"];

$successcode	= $HTTP_POST_VARS["successcode"];

$Ref	= $HTTP_POST_VARS["Ref"];

$PayRef	= $HTTP_POST_VARS["PayRef"];

$Amt	= $HTTP_POST_VARS["Amt"];

$Cur	= $HTTP_POST_VARS["Cur"];

$remark	= $HTTP_POST_VARS["remark"];

// Load selected payment module require(DIR_WS_CLASSES . 'payment.php');
$payment_modules = new payment($payment);



require(DIR_WS_CLASSES . 'order.php');
global $order;
$order = new order($Ref);

// Get image from database
//
for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
	$productId = $order->products[$i]['id'];
	$sql_prod = "select products_image from products where products_id='$productId'";
	$query_prod =  tep_db_query($sql_prod);
	while ($prod = tep_db_fetch_array($query_prod)) {
		$items = array("image" => $prod['products_image']);
		array_push_associative($order->products[$i], $items);
	}
}

if ($order->info['currency']==840) // American Dollar
$order->info['currency'] = "USD";
else if ($order->info['currency']==344) // Hong Kong Dollar
$order->info['currency'] = "HKD";
else if ($order->info['currency']==764) // Thai Dollar
$order->info['currency'] = "THB";
else if ($order->info['currency']==036) // AUD
$order->info['currency'] = "AUD";
else if ($order->info['currency']==826) // GBP
$order->info['currency'] = "GBP";
else if ($order->info['currency']==978) // EUR
$order->info['currency'] = "EUR";
else if ($order->info['currency']==702) // SGD
$order->info['currency'] = "SGD";
else if ($order->info['currency']==392) // JPY
$order->info['currency'] = "JPY";

//require(DIR_WS_CLASSES . 'currencies.php');
global $currency;
$currency = $order->info['currency'];

// Load the before_process function from the payment modules

$payment_modules->before_process();



require(DIR_WS_CLASSES . 'order_total.php');

$order_total_modules = new order_total;



$order_totals = $order_total_modules->process();


 
// Print out 'OK' to notify us you have received the data feed echo ("OK\n\r");


tep_db_connect();



//	Check the result of transaction if ($successcode == 0){
//Transaction Accepted

//***Add Security Control here, to check currency, amount with the

//***merchant's order reference from your database, if the order exist then

//***accepted otherwise reject the transaction.

//Update your database for Transaction Accepted and send email or notify customer....



//Obtain currency used in the order from database

$query = "SELECT currency FROM orders WHERE orders_id ='$Ref'";

$result = tep_db_query($query);

$item=mysql_fetch_assoc($result);

$num_row=mysql_num_rows($result);

$checkCur = $item['currency'];



//Translate the currency code from the format used in osCommerce database

//to that used in PayDollar

if ($checkCur == "USD")  //Ammerican Dollar

$checkCur = 840;

else if ($checkCur == "HKD")  //Hong Kong Dollar

$checkCur = 344;



//***You can add code for currencies other than USD and HKD

//***Here are some code for other currencies :

//****************************************

//	else if ($checkCur == "SGD")  //Singapore Dollar

//	$checkCur = 702;

//	else if ($checkCur == "CNY(RMB)")  //Chinese Renminbi Yuan

//	$checkCur = 156;

//	else if ($checkCur == "JPY")  //Japanese Yen

//	$checkCur = 392;

//	else if ($checkCur == "TWD")  //New Taiwan Dollar

//	$checkCur = 901;

//****************************************
 
//Obtain amount in the order from database

$value_query = "SELECT value FROM orders_total WHERE orders_id ='$Ref' and class = 'ot_total'";

$value_result = tep_db_query($value_query);

$value_item=mysql_fetch_assoc($value_result);

$checkAmt = $value_item['value'];



//If an order with Ref exists

//Check if currency and amount is matched 
if ($num_row==1 && $prc==0 && $src==0){
	if ($checkAmt == $Amt && $checkCur == $Cur){
	
		//Amount and Currency is matched
		
		//Update the orders_status in table orders
		
		//Set order_status = MODULE_PAYMENT_PAYDOLLAR_SUCCESS_ORDER_STATUS_ID which means Payment Successful
		
		// $write_query ="UPDATE orders SET orders_status=4 where orders_id='$Ref'";
		$write_query ="UPDATE orders SET orders_status=".MODULE_PAYMENT_PAYDOLLAR_SUCCESS_ORDER_STATUS_ID." where orders_id='$Ref'";
		
		$write_result = tep_db_query($write_query);
		
		
		
		//Insert the order status into table order_status_history
		
		//Set orders_status_id = MODULE_PAYMENT_PAYDOLLAR_SUCCESS_ORDER_STATUS_ID which means Payment Successful
		
		//In this sample, e-mail is not sent to customer for notification,
		
		//thus, customer_notified => 0
		
		//If e-mail is sent, customer_notified => 1
		
		$sql_data_array = array('orders_id' => $Ref,
		
		'orders_status_id' => MODULE_PAYMENT_PAYDOLLAR_SUCCESS_ORDER_STATUS_ID,
		
		'date_added' => 'now()',
		
		'customer_notified' => 0,
		
		'comments' => $order->info['comments']);
		
		tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		
		// Update stock quantity
		
		$sql_order_products = "select products_id, products_quantity from orders_products where orders_id='$Ref'";
		
		$query_order_products =  tep_db_query($sql_order_products);
		while ($order_product = tep_db_fetch_array($query_order_products)) {
			$sql_tmp = 'select products_quantity from products where products_id='.$order_product['products_id'];
			$product_stock = tep_db_fetch_array(tep_db_query($sql_tmp));
			$new_stock = $product_stock['products_quantity'] - $order_product['products_quantity'];
			$tmp_data_array = array ('products_quantity'=> $new_stock);
			tep_db_perform('products', $tmp_data_array, 'update', 'products_id='.$order_product['products_id']);
		}
		
		// Email to customers
		
		for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
			$products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n". tep_image(HTTP_SERVER.DIR_WS_HTTP_CATALOG.DIR_WS_IMAGES . $order->products[$i]['image'], $order->products[$i]['name'], SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT). "\n";
		}
		
		$email_order = STORE_NAME . "\n" . 
                 EMAIL_SEPARATOR . "\n" . 
                 EMAIL_TEXT_ORDER_NUMBER . ' ' . $Ref . "\n" .
                 EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $Ref, 'SSL', false) . "\n" .
                 EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
		  if ($order->info['comments']) {
			$email_order .= tep_db_output($order->info['comments']) . "\n\n";
		  }
		  $email_order .= EMAIL_TEXT_PRODUCTS . "\n" . 
						  EMAIL_SEPARATOR . "\n" . 
						  $products_ordered . 
						  EMAIL_SEPARATOR . "\n";
		
		  /*for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
			$email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
		  }*/
		  $order_totals_sql = "select * from orders_total where orders_id=".$Ref." order by sort_order asc";
		  $order_totals_query = tep_db_query($order_totals_sql);
		  while ($order_totalsi = tep_db_fetch_array($order_totals_query)) { 
		  	$email_order .= strip_tags($order_totalsi['title']) . ' ' . strip_tags($order_totalsi['text']) . "\n";
		  }
		
		  $customer_id = $order->customer['id'];
		  if ($order->content_type != 'virtual') {
			$company = tep_output_string_protected($order->delivery['company']);
			$delivery_mail_text = $order->delivery['name'] . "\n" . 
									$order->delivery['street_address'] . "\n" . 
									$order->delivery['city']  . ", ". $order->delivery['postcode'] . "\n" .
									$order->delivery['state']  . ", ". $order->delivery['country']['title'];
			//tep_address_label($customer_id, $sendto, 0, '', "\n")
			$email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" . 
							EMAIL_SEPARATOR . "\n" .
							$delivery_mail_text . "\n";
		  }
		
		  $bill_mail_text = $order->billing['name'] . "\n" .
		  							$order->billing['street_address'] . "\n" . 
									$order->billing['city']  . ", ". $order->billing['postcode'] . "\n" .
									$order->billing['state']  . ", ". $order->billing['country']['title'];
		  //tep_address_label($customer_id, $billto, 0, '', "\n")
		  $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
						  EMAIL_SEPARATOR . "\n" .
						  $bill_mail_text . "\n\n";
		  /*if (is_object($$payment)) {
			$email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" . 
							EMAIL_SEPARATOR . "\n";
			$payment_class = $$payment;
			$email_order .= $order->info['payment_method'] . "\n\n";
			if ($payment_class->email_footer) { 
			  $email_order .= $payment_class->email_footer . "\n\n";
			}
		  }*/
		  $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" . 
							EMAIL_SEPARATOR . "\n".
							"Paydollar" . "\n\n";
		  
		  tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
		
		// send emails to other people
		  if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
			tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
		  }
		
		// Finish
		
		echo("ok");
	
	}else{
	
		//Amount and Currency is not matched
		
		//Update the orders_status in table orders
		
		//Set order_status = 6 which means Amount or Currency not matched
		
		$write_query ="UPDATE orders SET orders_status=".MODULE_PAYMENT_PAYDOLLAR_FAIL_ORDER_STATUS_ID." where orders_id='$Ref'";
		
		$write_result = tep_db_query($write_query);
		
		
		
		//Insert the order status into table order_status_history
		
		//Set orders_status_id = 6 which means Amount or Currency not matched
		
		//In this sample, e-mail is not sent to customer for notification,
		
		//thus, customer_notified => 0
		
		//If e-mail is sent, customer_notified => 1
		
		$sql_data_array = array('orders_id' => $Ref,
		 
		'orders_status_id' => MODULE_PAYMENT_PAYDOLLAR_FAIL_ORDER_STATUS_ID,
		
		'date_added' => 'now()',
		
		'customer_notified' => 0,
		
		'comments' => $order->info['comments']);
		
		tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		
		echo ("either amt or currency not match");
	
	}

}

else{
	
	//Transaction Rejected
	
	//Update your database for Transaction Rejected ...
	
	
	
	//Update the orders_status in table orders
	
	//Set order_status = 5 which means Payment Failed
	
	$write_query ="UPDATE orders SET orders_status=".MODULE_PAYMENT_PAYDOLLAR_FAIL_ORDER_STATUS_ID." where orders_id='$Ref'";
	
	$write_result = tep_db_query($write_query);
	
	
	
	//Insert the order status into table order_status_history
	
	//Set orders_status_id = 5 which means Payment Failed
	
	//In this sample, e-mail is not sent to customer for notification,
	
	//thus, customer_notified => 0
	
	//If e-mail is sent, customer_notified => 1
	
	$sql_data_array = array('orders_id' => $Ref,
	
	'orders_status_id' => MODULE_PAYMENT_PAYDOLLAR_FAIL_ORDER_STATUS_ID,
	
	'date_added' => 'now()',
	
	'customer_notified' => 0,
	
	'comments' => $order->info['comments']);
	
	tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
	
	echo("tx fail");
}

tep_db_close();

?>
