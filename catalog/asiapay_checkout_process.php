<?php
/*
  $Id: checkout_process.php 1750 2007-12-21 05:20:28Z hpdl $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
*/

  include('includes/application_top.php');

// if the customer is not logged on, redirect them to the login page
  if (!tep_session_is_registered('customer_id')) {
    $navigation->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
    tep_redirect(tep_href_link(FILENAME_LOGIN, '', 'SSL'));
  }

// if there is nothing in the customers cart, redirect them to the shopping cart page
  if ($cart->count_contents() < 1) {
    tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
  }

// if no shipping method has been selected, redirect the customer to the shipping method selection page
  if (!tep_session_is_registered('shipping') || !tep_session_is_registered('sendto')) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  }

  if ( (tep_not_null(MODULE_PAYMENT_INSTALLED)) && (!tep_session_is_registered('payment')) ) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
 }

// avoid hack attempts during the checkout procedure by checking the internal cartID
  if (isset($cart->cartID) && tep_session_is_registered('cartID')) {
    if ($cart->cartID != $cartID) {
      tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
    }
  }

  function getDateDiff($d){
          $datenow = date('Ymd');
        $dt1 = new \DateTime($datenow);
        $dt2 = new \DateTime($d);
        $interval = $dt1->diff($dt2)->format('%a');
        return $interval;
      }

  function getAcctAgeInd($d, $isUpDate = FALSE){
        switch ($d) {
          case 0:
            # code...
            $ret = "02";
            if($isUpDate)$ret = "01";
            break;
          case $d<30:
            # code...
            $ret = "03";
            if($isUpDate)$ret = "02";
            break;
          case $d>30 && $d<60:
            # code...
            $ret = "04";
            if($isUpDate)$ret = "03";
            break;
          case $d>60:
            $ret = "05" ;
            if($isUpDate)$ret = "04";
          break;  
          default:
            # code...
            break;
        }
        return $ret;

      }
  function getCreateDate($customer_id){
      
      $customer_query = tep_db_query("select customers_dob as dob from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
      $customers = tep_db_fetch_array($customer_query);
      $customers_dob = $customers['dob'];
      return date('Ymd' ,strtotime($customers_dob));

  }

  function getOrdersHistory($customer_id,$languages_id){

      $orders_total = tep_count_customer_orders();

      $timeQ24 = date('Y-m-d H:i:s', strtotime("-1 day"));
      $timeQ6 = date('Y-m-d H:i:s', strtotime("-6 months"));
      $timeQ1 = date('Y-m-d H:i:s', strtotime("-1 year"));
      $countOrderAnyDay = $countOrder = $countOrderAnyYear = 0;

      if ($orders_total > 0) {
        $history_query_raw = "select o.orders_id, o.date_purchased, o.delivery_name, o.billing_name, ot.text as order_total, s.orders_status_name, o.orders_status from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_TOTAL . " ot, " . TABLE_ORDERS_STATUS . " s where o.customers_id = '" . (int)$customer_id . "' and o.orders_id = ot.orders_id and ot.class = 'ot_total' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languages_id . "' and s.public_flag = '1' order by orders_id DESC";
        
        $history_query = tep_db_query($history_query_raw);

        while ($history = tep_db_fetch_array($history_query)) {

            $dte6 = date('Ymd H:i:s' ,strtotime($history['date_purchased']));

            $dte = date('Ymd H:i:s' ,strtotime($history['date_purchased']));

            if($dte >= $timeQ24)$countOrderAnyDay++;
            if($dte6 >= $timeQ6 && MODULE_PAYMENT_PAYDOLLAR_SUCCESS_ORDER_STATUS_ID == $history['orders_status'])$countOrder++;
            if($dte >= $timeQ1)$countOrderAnyYear++;

        }
      }


      return array(
        isset($countOrder) ? (int)($countOrder) : '',
        isset($countOrderAnyDay) ? (int)($countOrderAnyDay) : '',
        isset($countOrderAnyYear) ? (int)($countOrderAnyYear) : '',
      );

}

function isSameBillShipAddress($b,$s){


    $cnt = 0;

    if($b['state'] == $s['state'])$cnt++;
    if($b['threeDSBillingLine1'] == $s['threeDSShippingLine1'])$cnt++;
    if($b['city'] == $s['city'])$cnt++;
    if($b['street_address'] == $s['street_address'])$cnt++;
    if($b['suburb'] == $s['suburb'])$cnt++;
    if($b['postcode'] == $s['postcode'])$cnt++;


    if($cnt==6)return "T";
    else return "F";

  }

  function getCountryCallAPI($countryCode){
    $method = "GET";
    $url = "https://restcountries.eu/rest/v2/alpha/$countryCode";
    // $data = array('codes'=>$countryCode);
    $data = false;

    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return json_decode($result);

}
  include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);

// load selected payment module
  require(DIR_WS_CLASSES . 'payment.php');
  $payment_modules = new payment($payment);

// load the selected shipping module
  require(DIR_WS_CLASSES . 'shipping.php');
  $shipping_modules = new shipping($shipping);

  require(DIR_WS_CLASSES . 'order.php');
  $order = new order;

// Stock Check
  $any_out_of_stock = false;
  if (STOCK_CHECK == 'true') {
    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
      if (tep_check_stock($order->products[$i]['id'], $order->products[$i]['qty'])) {
        $any_out_of_stock = true;
      }
    }
    // Out of Stock
    if ( (STOCK_ALLOW_CHECKOUT != 'true') && ($any_out_of_stock == true) ) {
      tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
    }
  }

  $payment_modules->update_status();

  if ( ( is_array($payment_modules->modules) && (sizeof($payment_modules->modules) > 1) && !is_object($$payment) ) || (is_object($$payment) && ($$payment->enabled == false)) ) {
    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode(ERROR_NO_PAYMENT_MODULE_SELECTED), 'SSL'));
  }

  require(DIR_WS_CLASSES . 'order_total.php');
  $order_total_modules = new order_total;

  $order_totals = $order_total_modules->process();

// load the before_process function from the payment modules
  $payment_modules->before_process();

  // Set the order status to Paydollar Pending
  //
  $order->info['order_status'] = MODULE_PAYMENT_PAYDOLLAR_ORDER_STATUS_ID;
  
  $sql_data_array = array('customers_id' => $customer_id,
                          'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                          'customers_company' => $order->customer['company'],
                          'customers_street_address' => $order->customer['street_address'],
                          'customers_suburb' => $order->customer['suburb'],
                          'customers_city' => $order->customer['city'],
                          'customers_postcode' => $order->customer['postcode'], 
                          'customers_state' => $order->customer['state'], 
                          'customers_country' => $order->customer['country']['title'], 
                          'customers_telephone' => $order->customer['telephone'], 
                          'customers_email_address' => $order->customer['email_address'],
                          'customers_address_format_id' => $order->customer['format_id'], 
                          'delivery_name' => trim($order->delivery['firstname'] . ' ' . $order->delivery['lastname']),
                          'delivery_company' => $order->delivery['company'],
                          'delivery_street_address' => $order->delivery['street_address'], 
                          'delivery_suburb' => $order->delivery['suburb'], 
                          'delivery_city' => $order->delivery['city'], 
                          'delivery_postcode' => $order->delivery['postcode'], 
                          'delivery_state' => $order->delivery['state'], 
                          'delivery_country' => $order->delivery['country']['title'], 
                          'delivery_address_format_id' => $order->delivery['format_id'], 
                          'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'], 
                          'billing_company' => $order->billing['company'],
                          'billing_street_address' => $order->billing['street_address'], 
                          'billing_suburb' => $order->billing['suburb'], 
                          'billing_city' => $order->billing['city'], 
                          'billing_postcode' => $order->billing['postcode'], 
                          'billing_state' => $order->billing['state'], 
                          'billing_country' => $order->billing['country']['title'], 
                          'billing_address_format_id' => $order->billing['format_id'], 
                          'payment_method' => $order->info['payment_method'], 
                          'cc_type' => $order->info['cc_type'], 
                          'cc_owner' => $order->info['cc_owner'], 
                          'cc_number' => $order->info['cc_number'], 
                          'cc_expires' => $order->info['cc_expires'], 
                          'date_purchased' => 'now()', 
                          'orders_status' => $order->info['order_status'], 
                          'currency' => $_POST['currCode'], 
                          'currency_value' => $order->info['currency_value']);
  tep_db_perform(TABLE_ORDERS, $sql_data_array);
  $insert_id = tep_db_insert_id();
  for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
    $sql_data_array = array('orders_id' => $insert_id,
                            'title' => $order_totals[$i]['title'],
                            'text' => $order_totals[$i]['text'],
                            'value' => $order_totals[$i]['value'], 
                            'class' => $order_totals[$i]['code'], 
                            'sort_order' => $order_totals[$i]['sort_order']);
    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
  }

  $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
  $sql_data_array = array('orders_id' => $insert_id, 
                          'orders_status_id' => $order->info['order_status'], 
                          'date_added' => 'now()', 
                          'customer_notified' => $customer_notification,
                          'comments' => $order->info['comments']);
  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

// initialized for the email confirmation	$order->info['currency']
  $products_ordered = '';
  $subtotal = 0;
  $total_tax = 0;

  for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
// Stock Update - Joao Correia
    if (STOCK_LIMITED == 'true') {
      if (DOWNLOAD_ENABLED == 'true') {
        $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename 
                            FROM " . TABLE_PRODUCTS . " p
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                             ON p.products_id=pa.products_id
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                             ON pa.products_attributes_id=pad.products_attributes_id
                            WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
        $products_attributes = $order->products[$i]['attributes'];
        if (is_array($products_attributes)) {
          $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
        }
        $stock_query = tep_db_query($stock_query_raw);
      } else {
        $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
      }
      if (tep_db_num_rows($stock_query) > 0) {
        $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
        /*if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
          $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
        } else {
          $stock_left = $stock_values['products_quantity'];
        }*/
		$stock_left = $stock_values['products_quantity'];
        tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
        if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
          tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
        }
      }
    }

// Update products_ordered (for bestsellers list)
    tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

    $sql_data_array = array('orders_id' => $insert_id, 
                            'products_id' => tep_get_prid($order->products[$i]['id']), 
                            'products_model' => $order->products[$i]['model'], 
                            'products_name' => $order->products[$i]['name'], 
                            'products_price' => $order->products[$i]['price'], 
                            'final_price' => $order->products[$i]['final_price'], 
                            'products_tax' => $order->products[$i]['tax'], 
                            'products_quantity' => $order->products[$i]['qty']);
    tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
    $order_products_id = tep_db_insert_id();

//------insert customer choosen option to order--------
    $attributes_exist = '0';
    $products_ordered_attributes = '';
    if (isset($order->products[$i]['attributes'])) {
      $attributes_exist = '1';
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
        if (DOWNLOAD_ENABLED == 'true') {
          $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
                               from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
                               left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                on pa.products_attributes_id=pad.products_attributes_id
                               where pa.products_id = '" . $order->products[$i]['id'] . "' 
                                and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' 
                                and pa.options_id = popt.products_options_id 
                                and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' 
                                and pa.options_values_id = poval.products_options_values_id 
                                and popt.language_id = '" . $languages_id . "' 
                                and poval.language_id = '" . $languages_id . "'";
          $attributes = tep_db_query($attributes_query);
        } else {
          $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
        }
        $attributes_values = tep_db_fetch_array($attributes);

        $sql_data_array = array('orders_id' => $insert_id, 
                                'orders_products_id' => $order_products_id, 
                                'products_options' => $attributes_values['products_options_name'],
                                'products_options_values' => $attributes_values['products_options_values_name'], 
                                'options_values_price' => $attributes_values['options_values_price'], 
                                'price_prefix' => $attributes_values['price_prefix']);
        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

        if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
          $sql_data_array = array('orders_id' => $insert_id, 
                                  'orders_products_id' => $order_products_id, 
                                  'orders_products_filename' => $attributes_values['products_attributes_filename'], 
                                  'download_maxdays' => $attributes_values['products_attributes_maxdays'], 
                                  'download_count' => $attributes_values['products_attributes_maxcount']);
          tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
        }
        $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
      }
    }
//------insert customer choosen option eof ----
    $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
    $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
    $total_cost += $total_products_price;

    $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
  }

// lets start with the email confirmation
  /*$email_order = STORE_NAME . "\n" . 
                 EMAIL_SEPARATOR . "\n" . 
                 EMAIL_TEXT_ORDER_NUMBER . ' ' . $insert_id . "\n" .
                 EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false) . "\n" .
                 EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
  if ($order->info['comments']) {
    $email_order .= tep_db_output($order->info['comments']) . "\n\n";
  }
  $email_order .= EMAIL_TEXT_PRODUCTS . "\n" . 
                  EMAIL_SEPARATOR . "\n" . 
                  $products_ordered . 
                  EMAIL_SEPARATOR . "\n";

  for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
    $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
  }

  if ($order->content_type != 'virtual') {
    $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" . 
                    EMAIL_SEPARATOR . "\n" .
                    tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
  }

  $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                  EMAIL_SEPARATOR . "\n" .
                  tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";
  if (is_object($$payment)) {
    $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" . 
                    EMAIL_SEPARATOR . "\n";
    $payment_class = $$payment;
    $email_order .= $order->info['payment_method'] . "\n\n";
    if ($payment_class->email_footer) { 
      $email_order .= $payment_class->email_footer . "\n\n";
    }
  }
  tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);*/

// send emails to other people
  /*if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
    tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
  }*/

// load the after_process function from the payment modules
  $payment_modules->after_process();

  $cart->reset(true);

// unregister session variables used during checkout
  tep_session_unregister('sendto');
  tep_session_unregister('billto');
  tep_session_unregister('shipping');
  tep_session_unregister('payment');
  tep_session_unregister('comments');

  //tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));

  //require(DIR_WS_INCLUDES . 'application_bottom.php');
?>

<html>
<body onLoad="payFormCcard.submit();">
<?php
//translating credit card type  
$ccType = $order->info['cc_type'];
if ($ccType =="Visa")
$ccType = "VISA";
else if ($ccType =="Master Card")
$ccType = "Master";
else if ($ccType =="Diners Club")
$ccType = "Diners";
else if ($ccType =="American Express")
$ccType = "AMEX";
else if ($ccType =="JCB")
$ccType = "JCB";
//translating year
$tempYear = "2000" + substr($order->info['cc_expires'],2,4);

//translating currency
// if ($order->info['currency']=="USD") // American Dollar
// $currCode = 840;
// else if ($order->info['currency']=="HKD") // Hong Kong Dollar
// $currCode = 344;
// else if ($order->info['currency']=="THB") // Thai Dollar
// $currCode = 764;
// else if ($order->info['currency']=="AUD") // AUD
// $currCode = 036;
// else if ($order->info['currency']=="GBP") // GBP
// $currCode = 826;
// else if ($order->info['currency']=="EUR") // EUR
// $currCode = 978;
// else if ($order->info['currency']=="SGD") // SGD
// $currCode = 702;
// else if ($order->info['currency']=="JPY") // JPY
// $currCode = 392;
if (DEFAULT_CURRENCY=="USD") // American Dollar
$currCode = 840;
else if (DEFAULT_CURRENCY=="HKD") // Hong Kong Dollar
$currCode = 344;
else if (DEFAULT_CURRENCY=="THB") // Thai Dollar
$currCode = 764;
else if (DEFAULT_CURRENCY=="AUD") // AUD
$currCode = 036;
else if (DEFAULT_CURRENCY=="GBP") // GBP
$currCode = 826;
else if (DEFAULT_CURRENCY=="EUR") // EUR
$currCode = 978;
else if (DEFAULT_CURRENCY=="SGD") // SGD
$currCode = 702;
else if (DEFAULT_CURRENCY=="JPY") // JPY
$currCode = 392;
else if (DEFAULT_CURRENCY=="PHP") // JPY
$currCode = 608;
//***You can add code for currencies other than USD and HKD
//***Here are some code for other currencies :
//****************************************
// else if ($order->info['currency']=="SGD") //Singapore Dollar
// $currCode = 702;
// else if ($order->info['currency']=="CNY(RMB)") // Chinese Renminbi Yuan
// $currCode = 156;
// else if ($order->info['currency']=="JPY") // Japanese Yen
// $currCode = 392;
// else if ($order->info['currency']=="TWD") // New Taiwan Dollar
// $currCode = 901;
//****************************************
  // $orders_status_query_raw = "select value,type from orders_no";
  // $orders_status_query = tep_db_query($orders_status_query_raw);
  // $s_value = "";
  // $s_type = "";
  // while ($orders_status = tep_db_fetch_array($orders_status_query)) {
	// $s_value = $orders_status['value'];
	// $s_type = $orders_status['type'];
  // }
  // $sOrderRef = "";
  // if ($s_type == "MANUAL") {
	  // $sOrderRef = $s_value;
  // } else if ($s_type == "FIXED") {
	  // $sOrderRef = $s_value . $insert_id;
  // } else if ($s_type == "AUTO") {
	  // $sOrderRef = $insert_id;
  // } else {
	  // $sOrderRef = $insert_id;
  // }
  $merchantId = $_POST['merchantId'];  
  $orderRef = $insert_id;
  $currCode = $_POST['currCode'];
  $amount = $_POST['amount'];
  $payType = $_POST['payType'];	
  $secureHashSecret = $_POST['secureHashSecret'];
  if ($secureHashSecret) {
    require_once ('SHAPaydollarSecure.php');
    $paydollarSecure = new SHAPaydollarSecure ();
    $secureHash = $paydollarSecure->generatePaymentSecureHash ( $merchantId, $orderRef, $currCode, $amount, $payType, $secureHashSecret );
    // $data ['secureHash'] = $secureHash;
  } else {
    // $data ['secureHash'] = '';
  }

  foreach ($_POST as $key => $value)
    $$key = $value;
$orders_total = tep_count_customer_orders();


$dte_add = getCreateDate($customer_id);
$dteAdd_diff = getDateDiff($dte_add);
$dteAddAge = getAcctAgeInd($dteAdd_diff);
list($threeDSAcctPurchaseCount,$threeDSAcctNumTransDay,$threeDSAcctNumTransYear) = getOrdersHistory($customer_id,$languages_id);
$isSameAddress = isSameBillShipAddress($order->billing,$order->delivery);
$shipDetl = $isSameAddress ? '01' : '03';
$authMethod = (int)$customer_id > 0 ? "02" : "01";
$country = getCountryCallAPI($order->customer['country']['iso_code_2']);
if(count($country)>0)
  $phoneCountryCode = $country->callingCodes[0];


$billCountry = getCountryCallAPI($order->billing['country']['iso_code_2']);

if(count($billCountry)>0)
  $countryBNumCode = $country->numericCode;


$shipCountry = getCountryCallAPI($order->delivery['country']['iso_code_2']);

if(count($shipCountry)>0)
  $countrySNumCode = $country->numericCode;



// echo "$countryNumCode === $countryNumCode<br>";
// // echo "$isSameAddress,$shipDetl <br>";
// // // echo $orders_total;
// echo "<pre>";
// print_r($country);
// print_r($order);exit;
$form_action_url = $_POST["actionUrl"];

$customerPhone = preg_replace('/\D/', '',$order->customer['telephone']);




echo tep_draw_form('payFormCcard', $form_action_url, 'post');
?>
      <input type="hidden" name="merchantId" value="<?php echo $_POST['merchantId']; ?>">   
      <input type="hidden" name="amount" value="<?php echo $_POST['amount']?>" >
      <input type="hidden" name="orderRef" value="<?php echo $insert_id; ?>"> 
      <input type="hidden" name="currCode" value="<?php echo $_POST['currCode']; ?>"> 
	  <input type="hidden" name="successUrl" value="<?php echo $_POST["successUrl"]; ?>">
	  <input type="hidden" name="failUrl" value="<?php echo $_POST["failUrl"]; ?>">
	  <input type="hidden" name="cancelUrl" value="<?php echo $_POST["cancelUrl"]; ?>">
      <input type="hidden" name="payType" value="<?php echo $_POST["payType"]; ?>">  
      <input type="hidden" name="lang" value="<?php echo $_POST["lang"]; ?>">
      <!-- <input type="hidden" name="payMethod" value="CC"> -->
      <input type="hidden" name="name" value="<?php echo $order->info['cc_owner']?>">
      <input type="hidden" name="payType" value="<?php echo $_POST['payType'];?>">
      <input type="hidden" name="secureHashSecret" value="<?php echo $_POST['secureHashSecret'];?>">
      <input type="hidden" name="secureHash" value="<?php echo $secureHash;?>">

      <input type="hidden" name="threeDSTransType" value="<?php echo $threeDSTransType;?>">
      <input type="hidden" name="threeDSCustomerEmail" value="<?php echo $order->customer['email_address'];?>">
      <input type="hidden" name="threeDSMobilePhoneNumber" value="<?php echo $customerPhone;?>">
      <input type="hidden" name="threeDSHomePhoneNumber" value="<?php echo $customerPhone;?>">
      <input type="hidden" name="threeDSWorkPhoneNumber" value="<?php echo $customerPhone;?>">

      <input type="hidden" name="threeDSMobilePhoneCountryCode" value="<?php echo $phoneCountryCode;?>">
      <input type="hidden" name="threeDSHomePhoneCountryCode" value="<?php echo $phoneCountryCode;?>">
      <input type="hidden" name="threeDSWorkPhoneCountryCode" value="<?php echo $phoneCountryCode;?>">

      <input type="hidden" name="threeDSBillingCountryCode" value="<?php echo $countryBNumCode;?>">
      <input type="hidden" name="threeDSBillingState" value="<?php echo $order->billing['state'];?>">
      <input type="hidden" name="threeDSBillingCity" value="<?php echo $order->billing['city'];?>">
      <input type="hidden" name="threeDSBillingLine1" value="<?php echo $order->billing['street_address'];?>">
      <input type="hidden" name="threeDSBillingLine2" value="<?php echo $order->billing['suburb'];?>">
      <input type="hidden" name="threeDSBillingPostalCode" value="<?php echo $order->billing['postcode'];?>">
      
      <input type="hidden" name="threeDSShippingCountryCode" value="<?php echo $countrySNumCode;?>">
      <input type="hidden" name="threeDSShippingDetails" value="<?php echo $shipDetl;?>">
      <input type="hidden" name="threeDSDeliveryEmail" value="<?php echo $order->customer['email_address'];?>">
      <input type="hidden" name="threeDSShippingState" value="<?php echo $order->delivery['state'];?>">
      <input type="hidden" name="threeDSShippingCity" value="<?php echo $order->delivery['city'];?>">
      <input type="hidden" name="threeDSShippingLine1" value="<?php echo $order->delivery['street_address'];?>">
      <input type="hidden" name="threeDSShippingLine2" value="<?php echo $order->delivery['suburb'];?>">
      <input type="hidden" name="threeDSShippingPostalCode" value="<?php echo $order->delivery['postcode'];?>">
      <input type="hidden" name="threeDSIsAddrMatch" value="<?php echo $isSameAddress;?>">

      <input type="hidden" name="threeDSAcctCreateDate" value="<?php echo $dte_add;?>">
      <input type="hidden" name="threeDSAcctAgeInd" value="<?php echo $dteAddAge;?>">
      <input type="hidden" name="threeDSAcctPurchaseCount" value="<?php echo $threeDSAcctPurchaseCount;?>">
      <input type="hidden" name="threeDSAcctNumTransDay" value="<?php echo $threeDSAcctNumTransDay;?>">
      <input type="hidden" name="threeDSAcctNumTransYear" value="<?php echo $threeDSAcctNumTransYear;?>">

      <input type="hidden" name="threeDSAcctAuthMethod" value="<?php echo $authMethod;?>">
      


      <input type="hidden" name="threeDSChallengePreference" value="<?php echo $threeDSChallengePreference;?>">
	  </form>
</body>
</html>
