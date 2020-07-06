<?php
/**
 * paydollar.php payment module class for Paypal IPN payment method
 * created by: Emmanuel L. Bautista for Zen Cart
 * email: emmanuel_lauron_bautista@yahoo.com.ph
 * Copyright June 2006
 */


/**
 * paydollar pyment method class
 *
 */
class paydollar{
  /**
   * string repesenting the payment method
   *
   * @var string
   */
  var $code;
  /**
   * $title is the displayed name for this payment method
   *
   * @var string
    */
  var $title;
  /**
   * $description is a soft name for this payment method
   *
   * @var string
    */
  var $description;
  /**
   * $enabled determines whether this module shows or not... in catalog.
   *
   * @var boolean
    */
  var $enabled;
  /**
    * constructor
    *
    * @param int $paypal_ipn_id
    * @return paypal
    */
  function paydollar() {
    global $order;

    $this->code = 'paydollar';
    $this->signature = 'paydollar|paydollar|1.0|2.2|2.3';
    if ($_GET['main_page'] != '') {
      $this->title = MODULE_PAYMENT_PAYDOLLAR_TEXT_CATALOG_TITLE; // Payment module title in Catalog
    } else {
      $this->title = MODULE_PAYMENT_PAYDOLLAR_TEXT_ADMIN_TITLE; // Payment module title in Admin
    }
    $this->description = MODULE_PAYMENT_PAYDOLLAR_TEXT_DESCRIPTION;
    $this->enabled = ((MODULE_PAYMENT_PAYDOLLAR_STATUS == 'True') ? true : false);
    $this->sort_order = MODULE_PAYMENT_PAYDOLLAR_SORT_ORDER;

    if ((int)MODULE_PAYMENT_PAYDOLLAR_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_PAYDOLLAR_ORDER_STATUS_ID;
    }

    if (is_object($order)) $this->update_status();

    $this->form_action_url = tep_href_link('asiapay_checkout_process.php'); //MODULE_PAYMENT_PAYDOLLAR_HANDLER
  }
  /**
   * calculate zone matches and flag settings to determine whether this module should display to customers or not
    *
    */
  function update_status() {
         global $order;

    if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYDOLLAR_ZONE > 0) ) {
      $check_flag = false;
      $check = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYDOLAR_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
  }
  /**
   * JS validation which does error-checking of data-entry if this module is selected for use
   * (Number, Owner, and CVV Lengths)
   *
   * @return string
    */
  function javascript_validation() {

  }
  /**
   * Displays Credit Card Information Submission Fields on the Checkout Payment Page
   * In the case of paypal, this only displays the paypal title
   *
   * @return array
    */
  function selection() {
   return array('id' => $this->code,
                 'module' => $this->title);
  }
  /**
   * Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
   * Since paypal module is not collecting info, it simply skips this step.
   *
   * @return boolean
   */
  function pre_confirmation_check() {

  }
  /**
   * Display Credit Card Information on the Checkout Confirmation Page
   * Since none is collected for paypal before forwarding to paypal site, this is skipped
   *
   * @return boolean
    */
  function confirmation() {

  }
  /**
   * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
   * This sends the data to the payment gateway for processing.
   * (These are hidden fields on the checkout confirmation page)
   *
   * @return string
    */
  function process_button() {
        global $_SERVER, $order;

        $this->totalsum = $order->info['total'];
		$this->orderRef = '';

        $process_button_string = '<input type="hidden" name="merchantId" value="'.MODULE_PAYMENT_PAYDOLLAR_ID.'"/>
                                  <input type="hidden" name="amount" value="'. $this->totalsum .'" />
                                  <input type="hidden" name="orderRef" value="'. $this->orderRef .'"/>
                                  <input type="hidden" name="currCode" value="'. $this->getCurrencyCode() .'" />
                                  <input type="hidden" name="successUrl" value="'. tep_href_link(FILENAME_CHECKOUT_SUCCESS, 'osCsid='.tep_session_id(), 'SSL', false) .'"/>
                                  <input type="hidden" name="failUrl" value="' . tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_status=failed', 'SSL') . '"/>
                                  <input type="hidden" name="cancelUrl" value="'. tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_status=canceled', 'SSL') .'"/>
                                  <input type="hidden" name="payType" value="N">
                                  <input type="hidden" name="actionUrl" value="'.MODULE_PAYMENT_PAYDOLLAR_HANDLER.'">
                                  <input type="hidden" name="lang" value="'. $this->getLanguageCode() .'">';

  return $process_button_string;

  }
    /*?44?- HKD
    ?40??USD
    ?02??SGD
    ?56??CNY (RMB)
    ?92??JPY
    ?01??TWD
    ?36??AUD
    ?78??EUR
    ?26??GBP
    ?24??CAD
  */
  function getCurrencyCode(){

    switch (MODULE_PAYMENT_PAYDOLLAR_CURRENCY) {
            case 'Only HKD':  $cur = '344';
                    break;
            case 'Only USD':  $cur = '840';
                   break;
            case 'Only SGD':  $cur = '702';
                    break;
            case 'Only CNY':  $cur = '156';
                    break;
            case 'Only JPY':  $cur = '392';
                    break;
            case 'Only TWD':  $cur = '901';
                    break;
            case 'Only AUD':  $cur = '036';
                    break;
            case 'Only EUR':  $cur = '978';
                    break;
            case 'Only GBP':  $cur = '826';
                    break;
            case 'Only CAD':  $cur = '124';
                    break;
            default:  $cur = '344';

    }

    return $cur;
  }

  /*
    The language of the payment page i.e.
    ：C??Traditional Chinese
    ：E??English
    ：X??Simplified Chinese
    ：K??Korean
    ：J??Japanese
  */
  function getLanguageCode(){
  	   global $languages_id;
       switch (MODULE_PAYMENT_PAYDOLLAR_LANGUAGE) {
            case 'Traditional Chinese':  $lang = 'C';
                    break;
            case 'English':  $lang = 'E';
                   break;
            case 'Simplified Chinese':  $lang = 'X';
                    break;
            case 'Korean':  $lang = 'K';
                    break;
            case 'Japanese':  $lang = 'J';
                    break;
            case 'Customer Choice':
	             	$lang_query = tep_db_query("select code from " . TABLE_LANGUAGES . " where languages_id = '" . (int)$languages_id . "'");
	      		 	$language_code = tep_db_fetch_array($lang_query);
	      		 	$this_code = $language_code['code'];
	
					if ($this_code == MODULE_PAYMENT_PAYDOLLAR_TRADITIONAL_CHINESE_CODE) {
						$lang = 'C';
					} else if ($this_code == MODULE_PAYMENT_PAYDOLLAR_ENGLISH_CODE) {
						$lang = 'E';
					} else if ($this_code == MODULE_PAYMENT_PAYDOLLAR_SIMPLIFIED_CHINESE_CODE) {
						$lang = 'X';
					} else if ($this_code == MODULE_PAYMENT_PAYDOLLAR_KOREAN_CODE) {
						$lang = 'K';
					} else if ($this_code == MODULE_PAYMENT_PAYDOLLAR_JAPANESE_CODE) {
						$lang = 'J';
					} else {
						$lang = 'E';	// default
					}		
                    break;
            default:  $lang = 'E';

    }
	
    return $lang;
  }
  /**
   * Store transaction info to the order and process any results that come back from the payment gateway
    *
    */
  function before_process() {

  }
  /**
    * Checks referrer
    *
    * @param string $zf_domain
    * @return boolean
    */
  function check_referrer($zf_domain) {

  }
  /**
    * Build admin-page components
    *
    * @param int $zf_order_id
    * @return string
    */
  function admin_notification($zf_order_id) {

  }
  /**
   * Post-processing activities
   *
   * @return boolean
    */
  function after_process() {

  }
  /**
   * Used to display error message details
   *
   * @return boolean
    */
  function output_error() {

  }
  /**
   * Check to see whether module is installed
   *
   * @return boolean
    */
  function check() {
    if (!isset($this->_check)) {
      $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYDOLLAR_STATUS'");
      $this->_check = tep_db_num_rows($check_query);
    }
    return $this->_check;
  }
  /**
   * Install the payment module and its configuration settings
    *
    */
  function install() {
   //global $db;
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable PayDollar Module', 'MODULE_PAYMENT_PAYDOLLAR_STATUS', 'True', 'Do you want to accept Paydollar payments?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('PayDollar ID', 'MODULE_PAYMENT_PAYDOLLAR_ID', '1', 'The merchant id used for the Paydollar service', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Currency', 'MODULE_PAYMENT_PAYDOLLAR_CURRENCY', 'Only HKD', 'Choose the currency/currencies you want to accept', '6', '0', 'tep_cfg_select_option(array(\'Only HKD\',\'Only USD\',\'Only SGD\',\'Only CNY\',\'Only JPY\',\'Only TWD\',\'Only AUD\',\'Only EUR\',\'Only GBP\',\'Only CAD\'), ', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Language', 'MODULE_PAYMENT_PAYDOLLAR_LANGUAGE', 'English', 'Choose the language of payment page<br><br>(Select \"Customer Choice\" if you like to display the payment page with the language same as customer selected language in your store. Enter the language codes defined in your store below \"Customer Choice\")', '6', '0', 'tep_cfg_select_option(array(\'Traditional Chinese\',\'English\',\'Simplified Chinese\',\'Korean\',\'Japanese\', \'Customer Choice\'), ', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Paydollar Pending Order Status', 'MODULE_PAYMENT_PAYDOLLAR_ORDER_STATUS_ID', '1', 'Set the status of pending orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Paydollar Acknowledged Order Status', 'MODULE_PAYMENT_PAYDOLLAR_SUCCESS_ORDER_STATUS_ID', '2', 'Set the status of successful orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Paydollar Failed Order Status', 'MODULE_PAYMENT_PAYDOLLAR_FAIL_ORDER_STATUS_ID', '3', 'Set the status of failed orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Traditional Chinese', 'MODULE_PAYMENT_PAYDOLLAR_TRADITIONAL_CHINESE_CODE', '', 'The language code for traditional chineses', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('English', 'MODULE_PAYMENT_PAYDOLLAR_ENGLISH_CODE', 'en', 'The language code for english', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Simplified Chinese', 'MODULE_PAYMENT_PAYDOLLAR_SIMPLIFIED_CHINESE_CODE', '', 'The language code for simplified chineses', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Korean', 'MODULE_PAYMENT_PAYDOLLAR_KOREAN_CODE', '', 'The language code for korean', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Japanese', 'MODULE_PAYMENT_PAYDOLLAR_JAPANESE_CODE', '', 'The language code for japanese', '6', '0', now())");
    tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Paydollar server', 'MODULE_PAYMENT_PAYDOLLAR_HANDLER', 'https://test.paydollar.com/b2c2/eng/payment/payForm.jsp', 'Type the server that will handle the transaction. The default is <code>https://www.paydollar.com/b2c2/eng/payment/payForm.jsp</code>', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYDOLLAR_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0' , now())");
  }
  /**
   * Remove the module and all its settings
    *
    */
  function remove() {
      //global $db;
    tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

  }
  /**
   * Internal list of configuration keys used for configuration of the module
   *
   * @return array
    */
  function keys() {
           return array('MODULE_PAYMENT_PAYDOLLAR_STATUS', 'MODULE_PAYMENT_PAYDOLLAR_ID', 'MODULE_PAYMENT_PAYDOLLAR_ORDER_STATUS_ID', 'MODULE_PAYMENT_PAYDOLLAR_SUCCESS_ORDER_STATUS_ID', 'MODULE_PAYMENT_PAYDOLLAR_FAIL_ORDER_STATUS_ID', 'MODULE_PAYMENT_PAYDOLLAR_CURRENCY','MODULE_PAYMENT_PAYDOLLAR_LANGUAGE', 'MODULE_PAYMENT_PAYDOLLAR_TRADITIONAL_CHINESE_CODE', 'MODULE_PAYMENT_PAYDOLLAR_ENGLISH_CODE', 'MODULE_PAYMENT_PAYDOLLAR_SIMPLIFIED_CHINESE_CODE', 'MODULE_PAYMENT_PAYDOLLAR_KOREAN_CODE', 'MODULE_PAYMENT_PAYDOLLAR_JAPANESE_CODE', 'MODULE_PAYMENT_PAYDOLLAR_HANDLER','MODULE_PAYMENT_PAYDOLLAR_SORT_ORDER');
  }
}
?>