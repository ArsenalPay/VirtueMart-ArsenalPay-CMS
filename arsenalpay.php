<?php
/**
 * @version       1.2.1
 * @author        The ArsenalPay Dev. Team
 * @package       VirtueMart
 * @subpackage    payment
 * @copyright     Copyright (C) 2014-2018 ArsenalPay. All rights reserved.
 * @license       http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 */

defined('_JEXEC') or die('Direct Access to ' . basename(__FILE__) . 'is not allowed.');

if (!class_exists('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

class plgVmPaymentArsenalpay extends vmPSPlugin {
	private $callback;
	// instance of class
	public static $_this = false;

	function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		/**
		 * Here we should assign data for two payment tables to work with. Some additional initializations can be done.
		 */
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_vmpayment_arsenalpay', JPATH_ADMINISTRATOR, null, true);
		$this->_loggable = true;
		$this->_debug    = true;
		/**
		 * assign columns for arsenalpay payment plugin table #_virtuemart_payment_plg_arsenalpay
		 */
		$this->tableFields = array_keys($this->getTableSQLFields());
		$this->_tablepkey  = 'id'; //virtuemart_ARSENALPAY_id';
		$this->_tableId    = 'id'; //'virtuemart_ARSENALPAY_id';
		//assign payment parameters from plugin configuration to paymentmethod table #_virtuemart_paymentmethods (payment_params column)
		$varsToPush = $this->getVarsToPush();
		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);

	}
	//===============================================================================
	// BACKEND
	/**
	 * Functions to initialize parameters from configuration
	 * to be saved in payment table #_virtuemart_paymentmethods (payment_params field)
	 *
	 *
	 * @param type $name
	 * @param type $id
	 * @param type $data
	 *
	 * @return type
	 */
	function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
		return $this->declarePluginParams('payment', $name, $id, $data);
	}

	function plgVmDeclarePluginParamsPaymentVM3(&$data) {
		return $this->declarePluginParams('payment', $data);
	}

	function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
		return $this->setOnTablePluginParams($name, $id, $table);
	}
	//===========================================================================================================================
	//BACKEND
	/**
	 * Create the table for this plugin if it does not yet exist.
	 * @author Valerie Isaksen
	 */
	protected function getVmPluginCreateTableSQL() {
		return $this->createTableSQL('Payment ArsenalPay Table');
	}

	/**
	 * Fields to create the payment table
	 * @return string SQL Fileds
	 */
	function getTableSQLFields() {
		$SQLfields = array(
			'id'                          => 'bigint(1) unsigned NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id'         => 'int(11) UNSIGNED ',
			'order_number'                => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED ',
			'payment_name'                => 'char(255) NOT NULL DEFAULT \'\' ',
			'payment_order_total'         => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
			'refund_total'                => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
			'payment_currency'            => 'char(3) ',
			'cost_per_transaction'        => ' decimal(10,2) ',
			'cost_percent_total'          => ' decimal(10,2) ',
			'tax_id'                      => 'smallint(1) ',
			'user_session'                => 'varchar(255)',

			/**
			 * status report data returned by ArsenalPay to merchant
			 */
			'arspay_response_ID'          => 'char(32)',
			'arspay_response_FUNCTION'    => 'char(15)',//FUNCTION
			'arspay_response_RRN'         => 'varchar(20)',//RRN
			'arspay_response_PAYER'       => 'char(20)',//PAYER
			'arspay_response_AMOUNT'      => 'decimal(15,5)',//AMOUNT
			'arspay_response_ACCOUNT'     => 'varchar(20)',//ACCOUNT
			'arspay_response_STATUS'      => 'char(10)',//STATUS
			'arspay_response_DATETIME'    => 'char(10)',//DATETIME
			'arspay_response_SIGN'        => 'char(255)',//SIGN
			'arspay_response_CODE'        => 'char(255)',
		);

		return $SQLfields;
	}

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 * @author Valerie Isaksen
	 *
	 * We must reimplement this trigger for joomla 1.7
	 *
	 */
	function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
		return $this->onStoreInstallPluginTable($jplugin_id);
	}

	//============================================================================================================================================
	//FRONTEND
	/**
	 * This method is called after payer set confirm purchase in check out.
	 * It loads ArsenalPay payment widget.
	 */
	function plgVmConfirmedOrder($cart, $order) {
		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return false;
		}
		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		if (!class_exists('VirtueMartModelCurrency')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
		}

		$session        = JFactory::getSession();
		$return_context = $session->getId();
		$lang           = JFactory::getLanguage();
		$filename       = 'com_virtuemart';
		$lang->load($filename, JPATH_ADMINISTRATOR);

		$paymentCurrency      = CurrencyDisplay::getInstance($method->payment_currency);
		$totalInPaymentAmount = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);
		if ($totalInPaymentAmount <= 0) {
			vmInfo(vmText::_('VMPAYMENT_ARSENALPAY_PAYMENT_AMOUNT_INCORRECT'));

			return false;
		}
		/**
		 * Prepare payments parameters for widget.
		 */
		$trx_id  = $order['details']['BT']->virtuemart_order_id;
		$user_id = $order['details']['BT']->virtuemart_user_id;
		if (!$user_id) {
			$user_id = '';
		}
		$destination = $trx_id;
		$amount      = number_format($totalInPaymentAmount, 2, '.', '');
		$widget      = $method->widget_id;
		$widget_key  = $method->widget_key;
		$nonce       = md5(microtime(true) . mt_rand(100000, 999999));
		$sign_param  = "$user_id;$destination;$amount;$widget;$nonce";
		$widget_sign = hash_hmac('sha256', $sign_param, $widget_key);

		/**
		 * Prepare data that should be stored in the database for arsenalpay payment method.
		 */
		$dbValues['user_session']                = $return_context;
		$dbValues['order_number']                = $order['details']['BT']->order_number;
		$dbValues['payment_name']                = $this->renderPluginName($method, $order);
		$dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
		$dbValues['payment_order_total']         = $totalInPaymentAmount;
		$dbValues['payment_currency']            = $paymentCurrency;
		$dbValues['refund_total']                = 0;
		$dbValues['cost_per_transaction']        = $method->cost_per_transaction;
		$dbValues['cost_percent_total']          = $method->cost_percent_total;
		$dbValues['payment_currency']            = $method->payment_currency;
		$dbValues['tax_id']                      = $method->tax_id;
		$this->storePSPluginInternalData($dbValues); // save prepared data to arsenalpay database

		//=================================================================================
		/**
		 * Prepare data into variable $content to be sent to the processing center.
		 * Uncomment if such data will be needed to be sent.
		 */

		/*$post_variables = Array(
									'transaction_id'           => $order['details']['BT']->order_number,
									//url to redirect after confirmation
									'return_url'               => JURI::root () .
											'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&on=' .
											$order['details']['BT']->order_number .
											'&pm=' .
											$order['details']['BT']->virtuemart_paymentmethod_id .
											'&Itemid=' . vRequest::getInt ('Itemid') .
											'&lang='.vRequest::getCmd('lang',''),
									//url to redirect after cancel
									'cancel_url'               => JURI::root () .
											'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' .
											$order['details']['BT']->order_number .
											'&pm=' .
											$order['details']['BT']->virtuemart_paymentmethod_id .
											'&Itemid=' . vRequest::getInt ('Itemid') .
											'&lang='.vRequest::getCmd('lang',''),
									'callback_url'               => JURI::root () .
											'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&lang='.vRequest::getCmd('lang','') ,
											'amount'  				   => $totalInPaymentAmount);
		$content = http_build_query ($post_variables);  */
		//==========================================================================
		/**
		 * The code for setting an widget.
		 */
		$html = '<html><head><title></title><script type="text/javascript">
                        jQuery(document).ready(function () {
                            jQuery(\'#main h3\').css("display", "none");
                        });
                        </script></head><body>';
		$html .= "
			<div id='arsenalpay-widget'></div>
			<script src='https://arsenalpay.ru/widget/script.js'></script>
			<script>
			var widget = new ArsenalpayWidget();
			widget.element = 'arsenalpay-widget';
			widget.widget = {$widget};
			widget.destination = '{$destination}';
			widget.amount = '{$amount}';
			widget.userId = '{$user_id}';
			widget.nonce = '{$nonce}';
			widget.widgetSign = '{$widget_sign}';
			widget.render();
			</script>
		";

		/**
		 * Here we assign the pending status (from ArsenalPay configs) while the response will not be received back to the merchant site.
		 */
		$modelOrder                 = VmModel::getModel('orders');
		$order['order_status']      = $method->status_pending;
		$order['customer_notified'] = 1;
		$order['comments']          = vmText::sprintf('VMPAYMENT_ARSENALPAY_WIDGET_STATUS', $destination);
		$modelOrder->updateStatusForOneOrder($order['details']['BT']->virtuemart_order_id, $order, true);
		/**
		 * Do nothing while the order will not be confirmed.
		 */
		$cart->_confirmDone   = false;
		$cart->_dataValidated = false;
		$cart->setCartIntoSession();
		$session->clear('arsenalpay', 'vm');
		//We delete the old stuff

		$cart->emptyCart();
		vRequest::setVar('html', $html);

		return true;
	}
	//========================================================================================
	//********************  Here are methods used in processing a callback  ***************//
	//========================================================================================
	function plgVmOnPaymentNotification() {

		if (!class_exists('VirtueMartCart')) {
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		}
		if (!class_exists('shopFunctionsF')) {
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		}
		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		$this->callback = VRequest::getPost();
		/**
		 * the GET paymentmethod parameter in notification url.
		 */
		$virtuemart_paymentmethod = vRequest::getVar('pm', 0);
		if ($virtuemart_paymentmethod != 'arsenalpay') {
			$this->log('Error: notification ulr does`t contains payment method parameter');
			$this->exitf('ERR');
		}
		if (!($this->_checkParams($this->callback))) {
			$this->exitf('ERR');
		}
                $modelOrder = VmModel::getModel('orders');
		$virtuemart_order_id = $this->callback['ACCOUNT'];
		$order_info          = $modelOrder->getOrder($virtuemart_order_id);
		$function            = $this->callback['FUNCTION'];

		//=======================================================================================================================
		/** Here we get preload data from arsenalpay payment table that was stored by method plgVmConfirmedOrder
		 * and just prepare to save it in the renewed table with the response data in case it will be needed for some reason.
		 * Without this block in the renewed table after response all the preload data will be nulled.
		 * ======================================================================================================================== */

		$payment_table = $this->getDataByOrderId($virtuemart_order_id);
		if (!$payment_table) {
			if ($function == 'check') {
				$this->exitf('NO');
			}
			// JError::raiseWarning(500, $db->getErrorMsg());
			$this->log('Error: order #' . $virtuemart_order_id . ' was not founded');
			$this->exitf('ERR');
		}
		$method = $this->getVmPluginMethod($payment_table->virtuemart_paymentmethod_id);
		if (!$method) {
			$this->log('Error in payment method');
			$this->exitf('ERR');
		}

		//check if the ip is allowed
		$REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
		$IP_ALLOW    = trim($method->allowed_ip);
		if (strlen($IP_ALLOW) > 0 && $IP_ALLOW != $REMOTE_ADDR) {
			$this->log('Error: Denied IP ' . $REMOTE_ADDR);
			$this->exitf('ERR');
		}

		$this->log("Request from ". $REMOTE_ADDR);

		if (!$this->selectedThisElement($method->payment_element)) {
			$this->log('Error: another method was selected');
			$this->exitf('ERR');
		}

		if (!($this->_checkSign($this->callback, $method->callback_key))) {
			$this->log('Error: invalid sign');
			$this->exitf('ERR');
		}
		$payment_table->order_status = $order_info['details']['BT']->order_status;
		$payment_table->order_info   = $order_info;
		switch ($function) {
			case 'check':
				$this->callbackCheck($payment_table, $method);
				break;

			case 'payment':
				$this->callbackPayment($payment_table, $method);
				break;

			case 'cancel':
				$this->callbackCancel($payment_table, $method);
				break;

			case 'cancelinit':
				$this->callbackCancel($payment_table, $method);
				break;

			case 'refund':
				$this->callbackRefund($payment_table, $method);
				break;

			case 'reverse':
				$this->callbackReverse($payment_table, $method);
				break;

			case 'reversal':
				$this->callbackReverse($payment_table, $method);
				break;

			case 'hold':
				$this->callbackHold($payment_table, $method);
				break;

			default: {
				$data = array(
					'order_status'      => $method->status_cancelled,
					'customer_notified' => 1,
					'comments'          => vmText::sprintf('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_CANCELLED', $payment_table->order_number),
				);
				$this->_updateOrder($this->callback['ACCOUNT'], $data);
				$this->log('Error: Not supporting function - ' . $function);
				$this->exitf('ERR');

			}
		}
	}

	private function preparePhone($phone) {
		$phone = preg_replace('/[^0-9]/', '', $phone);
		if (strlen($phone) < 10) {
			return false;
		}
		if (strlen($phone) == 10) {
			return $phone;
		}
		if (strlen($phone) == 11) {
			return substr($phone, 1);
		}

		return false;

	}

	private function callbackCheck($payment_table, $method) {
		$requiredStatuses = array(
			$method->status_pending,
			$method->status_holden,
		);

		if (!in_array($payment_table->order_status, $requiredStatuses)) {
			$this->log('Aborting, Order #' . $this->callback['ACCOUNT'] . ' has rejected status(' . $payment_table->order_status . ')');
			$this->exitf('NO');
		}
		$total           = number_format($payment_table->payment_order_total, 2, '.', '');
		$is_correct_amount = ($this->callback['MERCH_TYPE'] == 0 && $total == $this->callback['AMOUNT']) ||
		                   ($this->callback['MERCH_TYPE'] == 1 && $total >= $this->callback['AMOUNT'] && $total == $this->callback['AMOUNT_FULL']);

		if (!$is_correct_amount) {
			$this->log('Check error: Amounts do not match (request amount ' . $this->callback['AMOUNT'] . ' and ' . $total . ')');
			$this->exitf("NO");
		}
		$data   = array(
			'order_status'      => $method->status_pending,
			'customer_notified' => 0,
			'comments'          => vmText::sprintf('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_PENDING', $payment_table->order_number),
		);
		$fiscal = array();
		if (isset($this->callback['OFD']) && $this->callback['OFD'] == 1) {
			$fiscal = $this->prepareFiscal($payment_table->order_info, $method);
			if (!$fiscal) {
				$this->log("Check error: Fiscal document is empty");
				$this->exitf("ERR");
			}
		}
		$this->_updateOrder($this->callback['ACCOUNT'], $data);
		$this->exitf('YES', $fiscal);

	}

	private function prepareFiscal($order, $method) {
		if (!$order) {
			return array();
		}
		$fiscal = array(
			"id"      => $this->callback['ID'],
			"type"    => "sell",
			"receipt" => [
				"attributes" => [
					"email" => $order['details']['BT']->email
				],
				"items"      => array(),
			]

		);

		$phone = $this->preparePhone($order['details']['BT']->phone_1);

		if ($phone) {
			$fiscal['receipt']['attributes']['phone'] = $phone;
		}
		$total_sum = 0;
		$discount  = $order['details']['BT']->coupon_discount + $order['details']['BT']->order_payment;
		$shipping  = $order['details']['BT']->order_shipment + $order['details']['BT']->order_shipment_tax;
		if ($discount) {
			$discount_coef = $discount / ($order['details']['BT']->order_salesPrice + $shipping);
		}
		else {
			$discount_coef = 0;
		}

		for ($i = 0; $i < count($order['items']); $i ++) {
			$item = $order['items'][$i];
			/**
			 * последний элемент нормализует стоимость товаров
			 */
			if ($i == count($order['items'])) {
				$subtotal = round($order['details']['BT']->order_total, 2) - round($shipping * (1 + $discount_coef), 2) - $total_sum;
				$final    = round($subtotal / $item->product_quantity, 2);
			}
			else {
				$final    = round($item->product_final_price * (1 + $discount_coef), 2);
				$subtotal = $final * $item->product_quantity;
			}
			$total_sum   += $subtotal;
			$fiscal_item = array(
				'name'     => $item->order_item_name,
				'price'    => $final,
				'quantity' => round($item->product_quantity, 2),
				'sum'      => $subtotal,
			);
			if (isset($method->product_tax)) {
				$fiscal_item['tax'] = $method->product_tax;
			}

			$fiscal['receipt']['items'][] = $fiscal_item;
		}

		if ($order['details']['BT']->order_shipment) {
			$shipment_price = round($shipping * (1 + $discount_coef), 2);
			$shipment       = array(
				'name'     => "Доставка",
				'price'    => $shipment_price,
				'quantity' => 1,
				'sum'      => $shipment_price,
			);

			if (isset($method->shipment_tax)) {
				$shipment['tax'] = $method->shipment_tax;
			}

			$fiscal['receipt']['items'][] = $shipment;

		}


		return $fiscal;
	}

	private function callbackPayment($payment_table, $method) {
		$requiredStatuses = array(
			$method->status_pending,
			$method->status_holden,
		);

		if (!in_array($payment_table->order_status, $requiredStatuses)) {
			$this->log('Aborting, Order #' . $this->callback['ACCOUNT'] . ' has rejected status(' . $payment_table->order_status . ')');
			$this->exitf('ERR');
		}
		$total = number_format($payment_table->payment_order_total, 2, '.', '');
		if ($this->callback['MERCH_TYPE'] == '0' && $total == $this->callback['AMOUNT']) {
			$comment = vmText::sprintf('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_CONFIRMED', $payment_table->order_number, $this->callback['AMOUNT']);
		}
		elseif ($this->callback['MERCH_TYPE'] == '1' && $total >= $this->callback['AMOUNT'] && $total == $this->callback['AMOUNT_FULL']) {
			$comment = vmText::sprintf('VMPAYMENT_ARSENALPAY_LESS_PAYMENT_STATUS_CONFIRMED', $payment_table->order_number, $this->callback['AMOUNT']);
		}
		else {
			$this->log('Payment error: Amounts do not match (request amount ' . $this->callback['AMOUNT'] . ' and ' . $total . ')');
			$this->exitf("ERR");
		}

		$data = array(
			'order_status'      => $method->status_confirmed,
			'customer_notified' => 1,
			'comments'          => $comment,
		);
		$this->_updateOrder($this->callback['ACCOUNT'], $data);
		$this->exitf('OK');

	}

	private function callbackCancel($payment_table, $method) {
		$required_statuses = array(
			$method->status_pending,
			$method->status_holden,
		);
		if (!in_array($payment_table->order_status, $required_statuses)) {
			$this->log('CANCEL_ERROR, Order #' . $this->callback['ACCOUNT'] . ' has status:' . $payment_table->order_status);
			$this->exitf('ERR');
		}
		$data = array(
			'order_status'      => $method->status_cancelled,
			'customer_notified' => 1,
			'comments'          => vmText::sprintf('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_CANCELLED', $payment_table->order_number, $this->callback['AMOUNT']),
		);
		$this->_updateOrder($this->callback['ACCOUNT'], $data);
		$this->exitf('OK');

	}

	private function callbackRefund($payment_table, $method) {
		$required_statuses = array(
			$method->status_refunded,
			$method->status_confirmed,
		);
		if (!in_array($payment_table->order_status, $required_statuses)) {
			$this->log('REFUND_ERROR, Order #' . $this->callback['ACCOUNT'] . ' was not paid or refunded. Order has status (' . $payment_table->order_status . ')');
			$this->exitf('ERR');
		}

		$paid     = floatval($payment_table->payment_order_total);
		$refunded = floatval($payment_table->refund_total);
		$total    = number_format($paid - $refunded, 2, '.', '');

		$is_correct_amount = ($this->callback['MERCH_TYPE'] == 0 && $total >= $this->callback['AMOUNT']) ||
		                   ($this->callback['MERCH_TYPE'] == 1 && $total >= $this->callback['AMOUNT'] && $total >= $this->callback['AMOUNT_FULL']);

		if (!$is_correct_amount) {
			$this->log("Refund error: Paid amount({$total}) < request refund amount({$this->callback['AMOUNT']})");
			$this->exitf('ERR');
		}
		$data = array(
			'order_status'      => $method->status_refunded,
			'customer_notified' => 1,
			'comments'          => vmText::sprintf('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_REFUNDED', $payment_table->order_number, $this->callback['AMOUNT']),
		);

		$total_refunded = number_format($refunded + floatval($this->callback['AMOUNT']), 2, '.', '');
		$this->_setRefund($total_refunded, $payment_table);
		$this->_updateOrder($this->callback['ACCOUNT'], $data);
		$this->exitf('OK');
	}

	private function callbackReverse($payment_table, $method) {
		$required_statuses = array(
			$method->status_confirmed,
		);
		if (!in_array($payment_table->order_status, $required_statuses)) {
			$this->log('REVERSE_ERROR, Order #' . $this->callback['ACCOUNT'] . ' was not paid . Order has status (' . $payment_table->order_status . ')');
			$this->exitf('ERR');
		}

		$paid     = floatval($payment_table->payment_order_total);
		$refunded = floatval($payment_table->refund_total);
		$total    = number_format($paid - $refunded, 2, '.', '');

		$is_correct_amount = ($this->callback['MERCH_TYPE'] == 0 && $total == $this->callback['AMOUNT']) ||
		                   ($this->callback['MERCH_TYPE'] == 1 && $total >= $this->callback['AMOUNT'] && $total == $this->callback['AMOUNT_FULL']);

		if (!$is_correct_amount) {
			$this->log('REVERSE_ERROR: Amounts do not match (request amount ' . $this->callback['AMOUNT'] . ' and ' . $total . ')');
			$this->exitf('ERR');
		}
		$total_refunded = number_format($refunded + floatval($this->callback['AMOUNT']), 2, '.', '');
		$this->_setRefund($total_refunded, $payment_table);
		$data = array(
			'order_status'      => $method->status_reversed,
			'customer_notified' => 1,
			'comments'          => vmText::sprintf('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_REVERSED', $payment_table->order_number, $this->callback['AMOUNT']),
		);
		$this->_updateOrder($this->callback['ACCOUNT'], $data);
		$this->exitf('OK');

	}

	private function callbackHold($payment_table, $method) {
		$required_statuses = array(
			$method->status_pending,
			$method->status_holden,
		);
		if (!in_array($payment_table->order_status, $required_statuses)) {
			$this->log('Aborting, Order #' . $this->callback['ACCOUNT'] . ' has rejected status(' . $payment_table->order_status . ')');
			$this->exitf('ERR');
		}
		$total           = number_format($payment_table->payment_order_total, 2, '.', '');
		$is_correct_amount = ($this->callback['MERCH_TYPE'] == 0 && $total == $this->callback['AMOUNT']) ||
		                   ($this->callback['MERCH_TYPE'] == 1 && $total >= $this->callback['AMOUNT'] && $total == $this->callback['AMOUNT_FULL']);

		if (!$is_correct_amount) {
			$this->log('Hold error: Amounts do not match (request amount ' . $this->callback['AMOUNT'] . ' and ' . $total . ')');
			$this->exitf("ERR");
		}
		$data = array(
			'order_status'      => $method->status_holden,
			'customer_notified' => 1,
			'comments'          => vmText::sprintf('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_HOLDEN', $payment_table->order_number),
		);
		$this->_updateOrder($this->callback['ACCOUNT'], $data);
		$this->exitf('OK');

	}

	private function log($msg) {
		$this->logInfo($msg, 'message');
	}

	private function _updateOrder($order_id, $order_info) {
		$modelOrder = VmModel::getModel('orders');
		$modelOrder->updateStatusForOneOrder($order_id, $order_info, true);
	}

	private function _checkParams($callbackParams) {
		$required_keys = array
		(
			'ID',           /* Merchant identifier */
			'FUNCTION',     /* Type of request to which the response is received*/
			'RRN',          /* Transaction identifier */
			'PAYER',        /* Payer(customer) identifier */
			'AMOUNT',       /* Payment amount */
			'ACCOUNT',      /* Order number */
			'STATUS',       /* When /check/ - response for the order number checking, when
									// payment/ - response for status change.*/
			'DATETIME',     /* Date and time in ISO-8601 format, urlencoded.*/
			'SIGN',         /* Response sign  = md5(md5(ID).md(FUNCTION).md5(RRN).md5(PAYER).md5(request amount).
									// md5(ACCOUNT).md(STATUS).md5(PASSWORD)) */
		);

		/**
		 * Checking the absence of each parameter in the post request.
		 */
		foreach ($required_keys as $key) {
			if (empty($callbackParams[$key]) || !array_key_exists($key, $callbackParams)) {
				$this->log('Error in callback parameters ERR' . $key);

				return false;
			}
			else {
				$this->log(" $key=$callbackParams[$key]");
			}
		}
		if ($callbackParams['FUNCTION'] != $callbackParams['STATUS']) {
			$this->log("Error: FUNCTION ({$callbackParams['FUNCTION']} not equal STATUS ({$callbackParams['STATUS']})");

			return false;
		}

		return true;
	}

	private function _setRefund($total_refunded, $payment_table) {
		$dbValues['id']                          = $payment_table->id;
		$dbValues['virtuemart_order_id']         = $payment_table->virtuemart_order_id;
		$dbValues['user_session']                = $payment_table->user_session;
		$dbValues['order_number']                = $payment_table->order_number;
		$dbValues['payment_name']                = $payment_table->payment_name;
		$dbValues['virtuemart_paymentmethod_id'] = $payment_table->virtuemart_paymentmethod_id;
		$dbValues['payment_order_total']         = $payment_table->payment_order_total;
		$dbValues['payment_currency']            = $payment_table->payment_currency;
		$dbValues['refund_total']                = $total_refunded;
		$dbValues['cost_per_transaction']        = $payment_table->cost_per_transaction;
		$dbValues['cost_percent_total']          = $payment_table->cost_percent_total;
		$dbValues['payment_currency']            = $payment_table->payment_currency;
		$dbValues['tax_id']                      = $payment_table->tax_id;
		$this->storePSPluginInternalData($dbValues, 0, true); // save prepared data to arsenalpay database
	}

	/**
	 * plgVmOnPaymentResponseReceived
	 * This event is fired when the  method returns to the shop after the transaction
	 *
	 * The method itself should send in the URL the parameters needed
	 * NOTE for Plugin developers:
	 * If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
	 *
	 * @param int  $virtuemart_order_id : should return the virtuemart_order_id
	 * @param text $html                : the html to display
	 *
	 * @return mixed Null when this method was not selected, otherwise the true or false
	 *
	 * @author Valerie Isaksen
	 *
	 */
	// actions after responce is received, to redirect user to the order result page after confirmation.
	function plgVmOnPaymentResponseReceived(&$html) {

		if (!class_exists('VirtueMartCart')) {
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		}
		if (!class_exists('shopFunctionsF')) {
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		}
		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}

		$cart = VirtueMartCart::getCart();
		$cart->emptyCart();

		return true;
	}

	/**
	 * What to do after payment cancel
	 */
	function plgVmOnUserPaymentCancel() {
		if (!class_exists('VirtueMartModelOrders')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
		}
		$order_number                = vRequest::getString('on', '');
		$virtuemart_paymentmethod_id = vRequest::getInt('pm', '');
		if (empty($order_number) or empty($virtuemart_paymentmethod_id) or !$this->selectedThisByMethodId($virtuemart_paymentmethod_id)) {
			return null;
		}
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			$this->logInfo('getOrderIdByOrderNumber payment not found: exit ', 'ERROR');

			return null;
		}
		if (!($payment_table = $this->getDataByOrderNumber($order_number))) {
			$this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');

			return null;
		}
		VmInfo(vmText::_('VMPAYMENT_ARSENALPAY_PAYMENT_CANCELLED'));
		$session        = JFactory::getSession();
		$return_context = $session->getId();
		if (strcmp($payment_table->user_session, $return_context) === 0) {
			$this->handlePaymentUserCancel($virtuemart_order_id);
		}

		return true;
	}

	private function _checkSign($callback, $pass) {
		$validSign = ($callback['SIGN'] === md5(md5($callback['ID']) .
		                                        md5($callback['FUNCTION']) . md5($callback['RRN']) .
		                                        md5($callback['PAYER']) . md5($callback['AMOUNT']) . md5($callback['ACCOUNT']) .
		                                        md5($callback['STATUS']) . md5($pass))) ? true : false;

		return $validSign;
	}

	public function exitf($msg, $fiscal = array()) {
		if (isset($this->callback['FORMAT']) && $this->callback['FORMAT'] == 'json') {
			$msg = array("response" => $msg);
			if ($fiscal && isset($this->callback['OFD']) && $this->callback['OFD'] == 1) {
				$msg['ofd'] = $fiscal;
			}
			$msg = json_encode($msg);
		}
		ob_start();
		$this->log('Process callback ' . vmText::sprintf($msg));
		ob_end_clean();
		echo $msg;
		jexit();
	}

	//==========================================================================================
	//***********      Additional standard vmpayment methods   *****************************
	//==========================================================================================
	//FRONTEND
	/**
	 * Display stored order payment data
	 *
	 */
	function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
		if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
			return null; // Another method was selected, do nothing
		}
		$db = JFactory::getDBO();
		$q  = 'SELECT * FROM `' . $this->_tablename . '` '
		      . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
		$db->setQuery($q);
		if (!($payment_table = $db->loadObject())) {
			vmWarn(500, $q . " " . $db->getErrorMsg());

			return '';
		}
		$this->getPaymentCurrency($payment_table);

		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE();
		$html .= $this->getHtmlRowBE('ARSENALPAY_PAYMENT_NAME', $payment_table->payment_name);
		$html .= $this->getHtmlRowBE('ARSENALPAY_PAYMENT_TOTAL_CURRENCY', $payment_table->payment_order_total . ' ' . $payment_table->payment_currency);
		$html .= '</table>' . "\n";

		return $html;
	}

	/**
	 * Calculations for this payment method and final cost with tax calculation etc.
	 */
	function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
		if (preg_match('/%$/', $method->cost_percent_total)) {
			$cost_percent_total = substr($method->cost_percent_total, 0, - 1);
		}
		else {
			$cost_percent_total = $method->cost_percent_total;
		}

		return ((float)$method->cost_per_transaction + ((float)$cart_prices['salesPrice'] * (float)$cost_percent_total * 0.01));
	}

	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 * @author: Valerie Isaksen
	 *
	 * @param $cart_prices : cart prices
	 * @param $payment
	 *
	 * @return true: if the conditions are fulfilled, false otherwise
	 *
	 */
	protected function checkConditions($cart, $method, $cart_prices) {

		$address            = (($cart->ST == 0) ? $cart->BT : $cart->ST);
		$method->min_amount = (!empty($method->min_amount) ? $method->min_amount : 0);
		$method->max_amount = (!empty($method->max_amount) ? $method->max_amount : 0);

		$amount      = $cart_prices['salesPrice'];
		$amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
		                OR
		                ($method->min_amount <= $amount AND ($method->max_amount == 0)));
		if (!$amount_cond) {
			return false;
		}
		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array($method->countries)) {
				$countries[0] = $method->countries;
			}
			else {
				$countries = $method->countries;
			}
		}
		/**
		 * probably did not gave his BT:ST address
		 */
		if (!is_array($address)) {
			$address                          = array();
			$address['virtuemart_country_id'] = 0;
		}
		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
			return true;
		}

		return false;
	}

	//=========================================================================================================================
	/*
	 * We must reimplement this triggers for joomla 1.7
	 */
	/**
	 * This event is fired after the payment method has been selected. It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Max Milbers
	 * @author Valerie isaksen
	 *
	 * @param VirtueMartCart $cart : the actual cart
	 *
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 *
	 */
	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg) {
		return $this->OnSelectCheck($cart);
	}

	/**
	 * plgVmDisplayListFEPayment
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
	 *
	 * @param object  $cart     Cart object
	 * @param integer $selected ID of the method selected
	 *
	 * @return boolean True on succes, false on failures, null when this plugin was not selected.
	 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
	 *
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
		return $this->displayListFE($cart, $selected, $htmlIn);
	}

	//===============================================================================
	//FRONEND
	/*
	 * plgVmonSelectedCalculatePricePayment
	 * Calculate the price (value, tax_id) of the selected method
	 * It is called by the calculator
	 * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
	 * @author Valerie Isaksen
	 * @cart: VirtueMartCart the current cart
	 * @cart_prices: array the new cart prices
	 * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
	 *
	 *
	 */

	public function plgVmOnSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
	}

	//===================================================================================
	function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return null; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return false;
		}
		$this->getPaymentCurrency($method);

		$paymentCurrencyId = $method->payment_currency;
	}

	//==============================================================

	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 *
	 * @param VirtueMartCart cart: the cart object
	 *
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {
		return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
	}

	//======================================================================

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 *
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {

		$this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}

	//============================================================================

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id            method used for this order
	 *
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmOnShowOrderPrintPayment($order_number, $method_id) {
		return $this->onShowOrderPrint($order_number, $method_id);
	}

}

// No closing tag

    
    
    

	
