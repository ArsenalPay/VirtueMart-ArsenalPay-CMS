<?php

//@author: Arsenal Media Dev.Team
//@date: 19.09.2014

if (!defined('_VALID_MOS') && !defined('_JEXEC'))
        die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');
 
if (!class_exists('vmPSPlugin'))
        require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVmPaymentArsenalpay extends vmPSPlugin {
        // instance of class
        public static $_this = false;

        function __construct(& $subject, $config) {
                parent::__construct($subject, $config);
                /**
                * Here we should assign data for two payment tables to work with. Some additional initializations can be done.
                */
                $jlang = JFactory::getLanguage ();
                $jlang->load ('plg_vmpayment_arsenalpay', JPATH_ADMINISTRATOR, NULL, TRUE);
                $this->_loggable = true;
                //assign payment parameters from plugin configuration to paymentmethods table #_virtuemart_paymentmethods (payment_params column)
                $varsToPush = $this->getVarsToPush ();
                $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
                //assign columns for arsenalpay payment plugin table #_virtuemart_payment_plg_arsenalpay
                $this->tableFields = array_keys($this->getTableSQLFields());
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
        * @return type
        */
        function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
                return $this->declarePluginParams('payment', $name, $id, $data);
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
                        'id' => 'bigint(1) unsigned NOT NULL AUTO_INCREMENT',
                        'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL',
                        'order_number' => 'char(32) DEFAULT NULL',
                        'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL',
                        'payment_name' => 'char(255) NOT NULL DEFAULT \'\' ',
                        'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
                        'payment_currency' => 'char(3) ',
                        'cost_per_transaction' => ' decimal(10,2) DEFAULT NULL ',
                        'cost_percent_total' => ' decimal(10,2) DEFAULT NULL ',
                        'tax_id' => 'smallint(11) DEFAULT NULL',
                        'user_session' => 'varchar(255)',

                        // status report data returned by ArsenalPay to the merchant
                        'arspay_response_ID' => 'char(32)',
                        'arspay_response_FUNCTION' => 'char(15)',//FUNCTION 
                        'arspay_response_RRN' => 'varchar(20)',//RRN 
                        'arspay_response_PAYER' => 'char(20)',//PAYER 
                        'arspay_response_AMOUNT' => 'decimal(15,5)',//AMOUNT 
                        'arspay_response_ACCOUNT' => 'varchar(20)',//ACCOUNT
                        'arspay_response_STATUS' => 'char(10)',//STATUS 
                        'arspay_response_DATETIME' => 'char(10)',//DATETIME
                        'arspay_response_SIGN' => 'char(255)',//SIGN
                        'arspay_response_CODE'  => 'char(255)',  
                );
                return $SQLfields;
        }
        /**
         * Create the table for this plugin if it does not yet exist.
         * This functions checks if the called plugin is active one.
         * When yes it is calling the standard method to create the tables
         * @author Valerie Isaksen
        /*
         * We must reimplement this trigger for joomla 1.7
         *
        */
        function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
                return $this->onStoreInstallPluginTable($jplugin_id);
            }
    
        //============================================================================================================================================
        //FRONTEND
        /**
         * This method is called after buyer set confirm purchase in check out. 
         * It loads ArsenalPay payment frame with iframe.
         */
        function plgVmConfirmedOrder($cart, $order) {
                if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
                        return null; // Another method was selected, do nothing
                    }
                if (!$this->selectedThisElement($method->payment_element)) {
                        return false;
                    }
                if (!class_exists ('VirtueMartModelOrders')) {
                        require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
                    }
                if (!class_exists ('VirtueMartModelCurrency')) {
                        require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
                    }
        
                $session = JFactory::getSession ();
                $return_context = $session->getId ();
                $lang = JFactory::getLanguage();
                $filename = 'com_virtuemart';
                $lang->load($filename, JPATH_ADMINISTRATOR);

                $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
                $totalInPaymentAmount = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);
		if ($totalInPaymentAmount<= 0) {
                        vmInfo (vmText::_ ('VMPAYMENT_ARSENALPAY_PAYMENT_AMOUNT_INCORRECT'));
                        return FALSE;
                    }
                // Prepare parameters for an iframe.
                $uid = $method->unique_id;
                $src = $method->payment_src;
                $f_url = $method->frame_url;
                $f_mode = $method->f_mode;
                $css_file = $method->css_url;

                $f_width = $method->f_width;
                $f_height = $method->f_height;
                $f_border = $method->f_border;
                $f_scrolling = $method->f_scrolling;
                $f_params = "width={$f_width} height={$f_height} frameborder={$f_border} scrolling={$f_scrolling}";

                $trx_id = $order['details']['BT']->virtuemart_order_id;
        
                // Prepare data that should be stored in the database.

                $dbValues['user_session'] = $return_context;
                $dbValues['order_number'] = $order['details']['BT']->order_number;
                $dbValues['payment_name'] = $this->renderPluginName ($method, $order);
                $dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
                $dbValues['payment_order_total'] = $totalInPaymentAmount;
                $dbValues['payment_currency'] = $paymentCurrency;
                $dbValues['cost_per_transaction'] = $method->cost_per_transaction;
                $dbValues['cost_percent_total'] = $method->cost_percent_total;
                $dbValues['payment_currency'] = $method->payment_currency;
                $dbValues['tax_id'] = $method->tax_id;
                $this->storePSPluginInternalData($dbValues); // save prepared data to arsenalpay database
                
                //=================================================================================
                // Prepare data into variable $content to be sent to the processing center.
                // Uncomment if such data will be needed to be sent.
                /*$post_variables = Array(
                                            'transaction_id'           => $order['details']['BT']->order_number,
                                            'return_url'               => JURI::root () .
                                                    'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&on=' .
                                                    $order['details']['BT']->order_number .
                                                    '&pm=' .
                                                    $order['details']['BT']->virtuemart_paymentmethod_id .
                                                    '&Itemid=' . vRequest::getInt ('Itemid') .
                                                    '&lang='.vRequest::getCmd('lang',''),
                                            'cancel_url'               => JURI::root () .
                                                    'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=' .
                                                    $order['details']['BT']->order_number .
                                                    '&pm=' .
                                                    $order['details']['BT']->virtuemart_paymentmethod_id .
                                                    '&Itemid=' . vRequest::getInt ('Itemid') .
                                                    '&lang='.vRequest::getCmd('lang',''),
                                            'status_url'               => JURI::root () .
                                                    'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&lang='.vRequest::getCmd('lang','') ,
                                            'amount'  				   => $totalInPaymentAmount);
                $content = http_build_query ($post_variables);  */
                //==========================================================================
                // The code for setting an iframe.
                $html = '<html><head><title></title><script type="text/javascript">
                        jQuery(document).ready(function () {
                            jQuery(\'#main h3\').css("display", "none");
                        });
                        </script></head><body>';   
                $html .= '<iframe name="arspay" src='.$f_url.'?src='.$src.'&t='.$uid.'&n='.$trx_id.'&a='.number_format($totalInPaymentAmount, 2, '.', '').'&css='.$css_file
                        .'&frame='.$f_mode.' '.$f_params.'></iframe>';

                // Here we assign the pending status (from ArsenalPay configs) while the response will not be received back to the merchant site.
                $modelOrder = VmModel::getModel ('orders');
                $order['order_status'] = $method->status_pending;
                $order['customer_notified'] = 0;
                $order['comments'] = vmText::sprintf ('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_WAITING', $order_number);
                $modelOrder->updateStatusForOneOrder ($order['details']['BT']->virtuemart_order_id, $order, TRUE);
                // Do nothing while the order will not be confirmed.
                $cart->_confirmDone = FALSE;
                $cart->_dataValidated = FALSE;
                $cart->setCartIntoSession (); 
                vRequest::setVar ('html', $html);
                return TRUE;
            }
        //========================================================================================
        //********************  Here are methods used in processing a callback  ***************//
        //========================================================================================
        function plgVmOnPaymentNotification () {
            if (!class_exists ('VirtueMartCart')) {
                    require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
            }
            if (!class_exists ('shopFunctionsF')) {
                    require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
            }
            if (!class_exists ('VirtueMartModelOrders')) {
                    require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
            }
            ob_start();
            $callback_msg = VRequest::getPost();
            // the GET payment paymentmethod id parameter in notification url. 
            $virtuemart_paymentmethod_id = vRequest::getInt ('pm', 0);

            if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
                return NULL;
            } // Another method was selected, do nothing

            if (!$this->selectedThisElement ($method->payment_element)) {
                return NULL;
            }
			
            if (!($virtuemart_order_id = $callback_msg['ACCOUNT'])) {
                return NULL;
            }
			  if (!($paymentTable = $this->getDataByOrderId ($virtuemart_order_id))) {
						// JError::raiseWarning(500, $db->getErrorMsg());
						return "ERR_DB";  
						}

            $response_code = $this->_handleCallBack($callback_msg, $virtuemart_order_id, $method);
			
          

            $modelOrder = VmModel::getModel ('orders');
            if ($response_code==='OK') {
					$order = array();
                    $order['order_status'] = $method->status_confirmed;
                    $order['customer_notified'] = 1;
                    $order['comments'] = vmText::sprintf ('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_CONFIRMED', $paymentTable->order_number);
					$modelOrder->updateStatusForOneOrder ($virtuemart_order_id, $order, TRUE);
					
					//We delete the old stuff
					// get the correct cart session
					if (isset($paymentTable->user_session)) {
							$this->emptyCart($paymentTable->user_session, $paymentTable->order_number );
						}
                    ob_end_clean();
                    echo $response_code;
                    jexit();              
            }
            else {
                    $order['order_status'] = $method->status_cancelled;
                    $order['customer_notified'] = 0;
                    $order['comments'] = vmText::sprintf ('VMPAYMENT_ARSENALPAY_PAYMENT_STATUS_CANCELLED', $paymentTable->order_number);
                    $modelOrder->updateStatusForOneOrder ($virtuemart_order_id, $order, TRUE);
					$this->logInfo ('After status updated to cancelled for order' . $paymentTable->order_number, 'message');
                    echo $response_code;
                    return NULL;
            }
             vRequest::setVar ('html', $html);
			
           
			return TRUE;
            
            

            } 

        /**
         * plgVmOnPaymentResponseReceived
         * This event is fired when the  method returns to the shop after the transaction
         *
         * The method itself should send in the URL the parameters needed
         * NOTE for Plugin developers:
         * If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
         *
         * @param int $virtuemart_order_id : should return the virtuemart_order_id
         * @param text $html: the html to display
         * @return mixed Null when this method was not selected, otherwise the true or false
         *
         * @author Valerie Isaksen
         *
         */
          // actions after responce is received, to redirect user to the order result page after confirmation.
        function plgVmOnPaymentResponseReceived(&$html) {
                return true;
            } 

        // What to do after payment cancel
        function plgVmOnUserPaymentCancel() {
                /*if (!class_exists('VirtueMartModelOrders')) {
                        require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
                }

                $order_number = vRequest::getString('on', '');
                $virtuemart_paymentmethod_id = vRequest::getInt('pm', '');
                if (empty($order_number) or empty($virtuemart_paymentmethod_id) or !$this->selectedThisByMethodId($virtuemart_paymentmethod_id)) {
                    return NULL;
                }
                if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
                    $this->logInfo ('getOrderIdByOrderNumber payment not found: exit ', 'ERROR');
                    return NULL;
                }
                if (!($paymentTable = $this->getDataByOrderNumber($order_number))) {
                    $this->logInfo ('getDataByOrderId payment not found: exit ', 'ERROR');
                    return NULL;
                }

                VmInfo(vmText::_('VMPAYMENT_ARSENALPAY_PAYMENT_CANCELLED'));
                $session = JFactory::getSession();
                $return_context = $session->getId();
                if (strcmp($paymentTable->user_session, $return_context) === 0) {
                        $this->handlePaymentUserCancel($virtuemart_order_id);
                }*/
                return TRUE;
            }


        function _handleCallBack ($callback_msg, $virtuemart_order_id, $method) {
                //check if the ip is allowed 
				if (!class_exists ('VirtueMartModelOrders')) {
                    require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
				}
				if (!($paymentTable = $this->getDataByOrderId ($virtuemart_order_id))) {
						// JError::raiseWarning(500, $db->getErrorMsg());
						return "ERR_DB";  
						}	
                $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
                $IP_ALLOW = $method->allowed_ip;
                if( strlen( $IP_ALLOW ) > 0 && $IP_ALLOW != $REMOTE_ADDR ) {
                        $this->logInfo ('Process callback ' . vmText::sprintf ('ERR_IP'), 'message');
                        return( "ERR_IP" );
                }
				
                //=======================================================================================================================
                // Here we get preload data from arsenalpay payment table that was stored by method plgVmConfirmedOrder
                // and just prepare to save it in the renewed table with the response data in case it will be needed for some reason.
                // Without this block in the renewed table after response all the preload data will be nulled.
                //========================================================================================================================
              
                //check the response data with the preload confirm data
				$order_info = VirtueMartModelOrders::getOrder($virtuemart_order_id);
                if (($paymentTable->order_number!=$order_info['details']['BT']->order_number) OR 
                        (number_format($paymentTable->payment_order_total, 2, '.', '')!=$callback_msg['AMOUNT'])) {
                        return "ERR_CALLBACK_DATA";
                    }
                //=======================================================================================================================
                $keyArray = array(
                'ID',           /* merchant identifier */
                'FUNCTION',     /* type of request to which the response is received*/
                'RRN',          /* transaction identifier */
                'PAYER',        /* payer(custom) identifier */
                'AMOUNT',       /* payment amount */
                'ACCOUNT',      /* order number */
                'STATUS',       /* Payment status. When 'check' - response for the order number checking, when 'payment' - response for status change.*/
                'DATETIME',     /* Date and time in ISO-8601 format, urlencoded.*/
                'SIGN',         /* response sign = md5(md5(ID).md(FUNCTION).md5(RRN).md5(PAYER).md5(AMOUNT).md5(ACCOUNT).md(STATUS).md5(PASSWORD)) */       
                ); 
                /**
                 * Checking the absence of each parameter in the post response.
                 */
                foreach( $keyArray as $key ) {
                        if( empty( $callback_msg[$key] )||!array_key_exists( $key,$callback_msg) ){
                                $this->logInfo ('Process IPN ' . vmText::sprintf ('ERR_' . $key), 'message');
                                return "ERR_".$key;
                            }
                    }   
                /**
                 * Checking the response sign validness.
                 */

                if( !( $this->_checkSign( $callback_msg, $method->sign_key ) ) ) {
                                        //============== For testing, delete after testing =============================
                                        $S=md5(md5($callback_msg['ID']).
                                        md5($callback_msg['FUNCTION']).md5($callback_msg['RRN']).
                                        md5($callback_msg['PAYER']).md5($callback_msg['AMOUNT']).md5($callback_msg['ACCOUNT']).
                                        md5($callback_msg['STATUS']).md5($method->sign_key) );
                                      
                                        echo $S.'</br>';
                                        //======================================
                        return "ERR_INVALID_SIGN";
                    }

                $reply = $this->_returnAnswer( $callback_msg['FUNCTION'] ); 
                return $reply;
            } 

        private function _returnAnswer( $callBackType ){
            switch( $callBackType ){
                    case 'check':
                       /** 
                       /* "YES" 
                       /* "NO"
                       /* "ERR" 
                        */
                       $answer = "YES";
                       //$answer = "NO";
                       //$answer = "ERR";
                       break;
                    case 'payment':
                        /** 
                        /* "OK" 
                        /* "ERR" 
                         */
                        $answer = "OK";
                        break;
                    default:
                        $answer = "ERR_STATUS";     
            }  
            return $answer;
        }

        public function _checkSign( $callback, $pass){
                $validSign = ( $callback['SIGN'] === md5(md5($callback['ID']).
                        md5($callback['FUNCTION']).md5($callback['RRN']).
                        md5($callback['PAYER']).md5($callback['AMOUNT']).md5($callback['ACCOUNT']).
                        md5($callback['STATUS']).md5($pass) ) )? true : false;
                return $validSign;        
            }
        //==========================================================================================
        //***********      Additional standard vmpayment methods   *****************************
        //==========================================================================================
        //FRONTEND
        /**
         * Display stored payment data for an order
         *
         */
        function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
                if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
                        return null; // Another method was selected, do nothing
                    }

                $db = JFactory::getDBO();
                $q = 'SELECT * FROM `' . $this->_tablename . '` '
                        . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
                $db->setQuery($q);
                if (!($paymentTable = $db->loadObject())) {
                        vmWarn(500, $q . " " . $db->getErrorMsg());
                        return '';
                    }
                $this->getPaymentCurrency($paymentTable);

                $html = '<table class="adminlist">' . "\n";
                $html .=$this->getHtmlHeaderBE();
                $html .= $this->getHtmlRowBE('ARSENALPAY_PAYMENT_NAME', $paymentTable->payment_name);
                $html .= $this->getHtmlRowBE('ARSENALPAY_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
                $html .= '</table>' . "\n";
                return $html;
            }   

        // Calculations for this payment method and final cost with tax calculation etc.
        function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
                if (preg_match('/%$/', $method->cost_percent_total)) {
                        $cost_percent_total = substr($method->cost_percent_total, 0, -1);
                    } else {
                        $cost_percent_total = $method->cost_percent_total;
                    }
                    return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
                }
        /**
         * Check if the payment conditions are fulfilled for this payment method
         * @author: Valerie Isaksen
         *
         * @param $cart_prices: cart prices
         * @param $payment
         * @return true: if the conditions are fulfilled, false otherwise
         *
         */
        protected function checkConditions($cart, $method, $cart_prices) {

            $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
            $method->min_amount = (!empty($method->min_amount)?$method->min_amount:0);
            $method->max_amount = (!empty($method->max_amount)?$method->max_amount:0);

            $amount = $cart_prices['salesPrice'];
            $amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
                    OR
             ($method->min_amount <= $amount AND ($method->max_amount == 0) ));
            if (!$amount_cond) {
                return false;
            }
            $countries = array();
            if (!empty($method->countries)) {
                if (!is_array($method->countries)) {
                    $countries[0] = $method->countries;
                } else {
                    $countries = $method->countries;
                }
            }

            // probably did not gave his BT:ST address
            if (!is_array($address)) {
                $address = array();
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
         * @param VirtueMartCart $cart: the actual cart
         * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
         *
         */
        public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
            return $this->OnSelectCheck($cart);
        }

        /**
         * plgVmDisplayListFEPayment
         * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
         *
         * @param object $cart Cart object
         * @param integer $selected ID of the method selected
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

        public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
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
         * @param VirtueMartCart cart: the cart object
         * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
         *
         */
        function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(),  &$paymentCounter) {
            return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
        }
    //======================================================================
        /**
         * This method is fired when showing the order details in the frontend.
         * It displays the method-specific data.
         *
         * @param integer $order_id The order ID
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
         * @param integer $method_id  method used for this order
         * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
         * @author Valerie Isaksen
         */
        function plgVmonShowOrderPrintPayment($order_number, $method_id) {
            return $this->onShowOrderPrint($order_number, $method_id);
        }       
    }

    // No closing tag

    
    
    

	