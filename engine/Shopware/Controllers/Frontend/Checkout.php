<?php
/**
 * Checkout Controller
 * 
 * Used for cart / confirm / confirmFinished Views
 * Display information in templates and do order processing
 * 
 * @link http://www.shopware.de
 * @copyright Copyright (c) 2011, shopware AG
 * @author Heiner Lohaus
 * @author Stefan Hamann
 * @package Shopware
 * @subpackage Controllers
 */
class Shopware_Controllers_Frontend_Checkout extends Enlight_Controller_Action
{
	/**
	 * Reference to sAdmin object (core/class/sAdmin.php)
	 * 
	 * @var sAdmin
	 */
	protected $admin;
	
	/**
	 * Reference to sBasket object (core/class/sBasket.php)
	 * 
	 * @var sBasket
	 */
	protected $basket;
	
	/**
	 * Reference to Shopware session object (Shopware()->Session)
	 * 
	 * @var Zend_Session_Namespace
	 */
	protected $session;
	
	/**
	 * Init method that get called automatically
	 *
	 * Set class properties
	 */
	public function init()
	{
		$this->admin = Shopware()->Modules()->Admin();
		$this->basket = Shopware()->Modules()->Basket();
		$this->session = Shopware()->Session();
	}
		
	/**
	 * Forward to cart or confirm action depending on user state
	 */
	public function indexAction()
	{
		if ($this->basket->sCountBasket()<1||!$this->admin->sCheckUser()) {
			$this->forward('cart');
		} else {
			$this->forward('confirm');
		}
	}
	
	/**
	 * Read all data from objects / models that are required in cart view
	 * (User-Data / Payment-Data / Basket-Data etc.)
	 */
	public function cartAction()
 	{
		$this->View()->sUserData = $this->getUserData();	
		
		$this->View()->sCountry = $this->getSelectedCountry();
		$this->View()->sPayment = $this->getSelectedPayment();
		$this->View()->sDispatch = $this->getSelectedDispatch();
		
		$this->View()->sCountryList = $this->getCountryList();
		$this->View()->sPayments = $this->getPayments();
		$this->View()->sDispatches = $this->getDispatches();
		
		$this->View()->sBasket = $this->getBasket();
		
		$this->View()->sShippingcosts = $this->View()->sBasket['sShippingcosts'];
		$this->View()->sShippingcostsDifference = $this->View()->sBasket['sShippingcostsDifference'];
		$this->View()->sAmount = $this->View()->sBasket['sAmount'];
		$this->View()->sAmountWithTax = $this->View()->sBasket['sAmountWithTax'];
		$this->View()->sAmountTax = $this->View()->sBasket['sAmountTax'];
		$this->View()->sAmountNet = $this->View()->sBasket['AmountNetNumeric'];
		
		$this->View()->sMinimumSurcharge = $this->getMinimumCharge();
		$this->View()->sPremiums = $this->getPremiums();
		
		$this->View()->sInquiry = $this->getInquiry();
		$this->View()->sInquiryLink = $this->getInquiryLink();
		
		$this->View()->sTargetAction = 'cart';
	}
	
	/**
	 * Mostly equivalent to cartAction
	 * Get user- basket- and payment-data for view assignment
	 * Create temporary entry in s_order table
	 * Check some conditions (minimum charge)
	 *
	 * @return void
	 */
	public function confirmAction()
	{
		if (!$this->admin->sCheckUser()) {	
			return $this->forward('login', 'account', null, array('sTarget'=>'checkout'));
		} elseif ($this->basket->sCountBasket()<1) {
			return $this->forward('cart');
		}
		
		$this->View()->sUserData = $this->getUserData();
		$this->View()->sCountry = $this->getSelectedCountry();
		$this->View()->sPayment = $this->getSelectedPayment();
		$this->View()->sDispatch = $this->getSelectedDispatch();
		$this->View()->sPayments = $this->getPayments();
		$this->View()->sDispatches = $this->getDispatches();
		$this->View()->sBasket = $this->getBasket();
		$this->View()->sLaststock = Shopware()->Modules()->Basket()->sCheckBasketQuantities();
		$this->View()->sShippingcosts = $this->View()->sBasket['sShippingcosts'];
		$this->View()->sShippingcostsDifference = $this->View()->sBasket['sShippingcostsDifference'];
		$this->View()->sAmount = $this->View()->sBasket['sAmount'];
		$this->View()->sAmountWithTax = $this->View()->sBasket['sAmountWithTax'];
		$this->View()->sAmountTax = $this->View()->sBasket['sAmountTax'];
		$this->View()->sAmountNet = $this->View()->sBasket['AmountNetNumeric'];
		
		$this->View()->sPremiums = $this->getPremiums();
		
		$this->View()->sNewsletter = isset($this->session['sNewsletter']) ? $this->session['sNewsletter'] : null;
		$this->View()->sComment = isset($this->session['sComment']) ? $this->session['sComment'] : null;
		
		$this->View()->sShowEsdNote = $this->getEsdNote();
		$this->View()->sDispatchNoOrder = $this->getDispatchNoOrder();
		$this->View()->sRegisterFinished = !empty($this->session['sRegisterFinished']);
		
		$this->saveTemporaryOrder();
		
		if($this->getMinimumCharge()) {
			return $this->forward('cart');
		}
		
		$this->session['sOrderVariables'] = new ArrayObject($this->View()->getAssign(), ArrayObject::ARRAY_AS_PROPS);
		
		$this->View()->sTargetAction = 'confirm';
	}
	
	/**
	 * Called from confirmAction View
	 * Customers requests to finish current order
	 * Check if all conditions match and save order
	 *
	 * @return void
	 */
	public function finishAction()
	{		
		if($this->Request()->getParam('sUniqueID') && !empty($this->session['sOrderVariables'])) {
			$sql = '
				SELECT transactionID as sTransactionumber, ordernumber as sOrderNumber
				FROM s_order
				WHERE temporaryID=? AND userID=?
			';
			$order = Shopware()->Db()->fetchRow($sql, array($this->Request()->getParam('sUniqueID'), Shopware()->Session()->sUserId));
			if(!empty($order)) {
				$this->View()->assign($order);
				$this->View()->assign($this->session['sOrderVariables']->getArrayCopy());
				return;
			}
		}
		
		if(empty($this->session['sOrderVariables'])||$this->getMinimumCharge()||$this->getEsdNote()||$this->getDispatchNoOrder()) {
			return $this->forward('confirm');
		}
		
		$checkQuantities = Shopware()->Modules()->Basket()->sCheckBasketQuantities();
		if (!empty($checkQuantities['hideBasket'])){
			return $this->forward('confirm');
		}
		
		$this->View()->assign($this->session['sOrderVariables']->getArrayCopy());
		$this->View()->sUserData = $this->getUserData();
		
		if ($this->basket->sCountBasket()>0
		 && empty($this->View()->sUserData['additional']['payment']['embediframe'])) {
			if($this->Request()->getParam('sNewsletter')!==null) {
				$this->session['sNewsletter'] = $this->Request()->getParam('sNewsletter') ? true : false;
			}
			if($this->Request()->getParam('sComment')!==null) {
				$this->session['sComment'] = trim(strip_tags($this->Request()->getParam('sComment')));
			}
			if (!Shopware()->Config()->get('IgnoreAGB') && !$this->Request()->getParam('sAGB')) {
				$this->View()->sAGBError = true;
				return $this->forward('confirm');
			}
			if(!empty($this->session['sNewsletter'])) {
				$this->admin->sUpdateNewsletter(true, $this->admin->sGetUserMailById(), true);
			}
			$this->session['sOrderVariables']['sOrderNumber'] = $this->saveOrder();
			
			if(!empty(Shopware()->Config()->DeleteCacheAfterOrder)) {
				Shopware()->Cache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('Shopware_Adodb'));
			}
		}
		
		$this->View()->assign($this->session['sOrderVariables']->getArrayCopy());
	}
	
	/**
	 * If any external payment mean chooses by customer
	 * Forward to payment page after order submitting
	 */
	public function paymentAction()
	{
		if(empty($this->session['sOrderVariables']) 
		  || $this->getMinimumCharge()
		  || $this->getEsdNote()
		  || $this->getDispatchNoOrder()) {
			return $this->forward('confirm');
		}
		
		if($this->Request()->getParam('sNewsletter')!==null) {
			$this->session['sNewsletter'] = $this->Request()->getParam('sNewsletter') ? true : false;
		}
		if($this->Request()->getParam('sComment')!==null) {
			$this->session['sComment'] = trim(strip_tags($this->Request()->getParam('sComment')));
		}
		
		if (!Shopware()->Config()->get('IgnoreAGB') && !$this->Request()->getParam('sAGB')) {
			$this->View()->sAGBError = true;
			return $this->forward('confirm');
		}
		
		$this->View()->assign($this->session['sOrderVariables']->getArrayCopy());
		$this->View()->sAGBError = false;
		
		if(empty($this->View()->sPayment['embediframe'])
		  && empty($this->View()->sPayment['action'])) {
			return $this->forward('confirm');
		}
		
		if(!empty($this->session['sNewsletter'])) {
			$this->admin->sUpdateNewsletter(true, $this->admin->sGetUserMailById(), true);
		}
		
		if(!empty($this->View()->sPayment['embediframe'])) {
			$embedded = $this->View()->sPayment['embediframe'];
			$embedded = preg_replace('#^[./]+#', '', $embedded);
			$embedded .= '?sCoreId='.Shopware()->SessionID();
			$embedded .= '&sAGB=1';
			
			$this->View()->sEmbedded = $embedded;
		} else {
			$action = explode('/', $this->View()->sPayment['action']);
			$this->redirect(array(
				'controller' => $action[0],
				'action' => empty($action[1]) ? 'index' : $action[1],
				'forceSecure' => true
			));
		}
	}
	
	/**
	 * Add an article to cart directly from cart / confirm view
	 * @param sAdd = ordernumber
	 * @param sQuantity = quantity
	 */
	public function addArticleAction()
	{
		$ordernumber = $this->Request()->getParam('sAdd');
		$quantity = $this->Request()->getParam('sQuantity');
		$articleID = Shopware()->Modules()->Articles()->sGetArticleIdByOrderNumber($ordernumber);
		
		$this->View()->sBasketInfo = $this->getInstockInfo($ordernumber, $quantity);

		if(!empty($articleID))
		{
			$insertID = $this->basket->sAddArticle($ordernumber, $quantity);
			$this->View()->sArticleName = Shopware()->Modules()->Articles()->sGetArticleNameByOrderNumber($ordernumber);
			if(!empty($insertID))
			{
				$basket = $this->getBasket();
				foreach ($basket['content'] as $item)
				{
					if($item['id']==$insertID)
					{
						$this->View()->sArticle = $item;
						break;
					}
				}
			}
			
			$this->View()->sCrossSimilarShown = $this->getSimilarShown($articleID);
			$this->View()->sCrossBoughtToo = $this->getBoughtToo($articleID);
		}
		
		if($this->request->isXmlHttpRequest()||!empty($this->Request()->callback)){
			$this->Request()->setParam('sTargetAction', 'ajax_add_article');
		}
		
		if($this->Request()->getParam('sAddAccessories')) {
			$this->forward('addAccessories');
		} else {
			$this->forward($this->Request()->getParam('sTargetAction', 'index'));
		}
	}
	
	/**
	 * Add more then one article directly from cart / confirm view
	 * @param sAddAccessories = List of article ordernumbers separated by ;
	 * @param sAddAccessoriesQuantity = List of article quantities separated by ;
	 */
	public function addAccessoriesAction()
	{
		$accessories = $this->Request()->getParam('sAddAccessories');
		$accessoriesQuantity = $this->Request()->getParam('sAddAccessoriesQuantity');
		if(is_string($accessories)) {
			$accessories = explode(';', $accessories);
		}
		
		if(!empty($accessories)&&is_array($accessories)) {
			foreach ($accessories as $key => $accessory) {
				try {
					if (!empty($accessoriesQuantity[$key])){
						$quantity = intval($accessoriesQuantity[$key]);
					}else {
						$quantity = 1;
					}
					$this->basket->sAddArticle($accessory, $quantity);
				} catch (Exception $e) {
					
				}
			}
		}
		
		$this->forward($this->Request()->getParam('sTargetAction', 'index'));
	}
	
	/**
	 * Add bundle product to cart defined with request parameters sAddBundle and sBID
	 */
	public function addBundleAction()
	{
		if ($this->Request()->getParam('sAddBundle') && $this->Request()->getParam('sBID'))
		{ 
			$this->basket->sAddBundleArticle($this->Request()->getParam('sAddBundle'), $this->Request()->getParam('sBID'));
		}
		$this->forward($this->Request()->getParam('sTargetAction', 'index'));
	}
	
	/**
	 * Delete an article from cart -
	 * @param sDelete = id from s_basket identifying the product to delete
	 * Forward to cart / confirmation page after success
	 */
	public function deleteArticleAction()
	{
		if($this->Request()->getParam('sDelete'))
		{
			$this->basket->sDeleteArticle($this->Request()->getParam('sDelete'));
		}
		$this->forward($this->Request()->getParam('sTargetAction', 'index'));
	}
	
	/**
	 * Change quantity of a certain product
	 * @param sArticle = The article to update
	 * @param sQuantity = new quantity
	 * Forward to cart / confirm view after success
	 */
	public function changeQuantityAction()
	{
		if($this->Request()->getParam('sArticle') && $this->Request()->getParam('sQuantity'))
		{
			$this->View()->sBasketInfo = $this->basket->sUpdateArticle($this->Request()->getParam('sArticle'), $this->Request()->getParam('sQuantity'));
		}
		$this->forward($this->Request()->getParam('sTargetAction', 'index'));
	}
	
	/**
	 * Add voucher to cart
	 * 
	 * At failure view variable sVoucherError will give further information
	 * At success return to cart / confirm view
	 */
	public function addVoucherAction()
	{
		if ($this->Request()->isPost())
		{
			$voucher = $this->basket->sAddVoucher($this->Request()->getParam('sVoucher'));
			if (!empty($voucher['sErrorMessages']))
			{
				$this->View()->sVoucherError = $voucher['sErrorMessages'];
			}
		}
		$this->forward($this->Request()->getParam('sTargetAction', 'index'));
	}
	
	/**
	 * Add premium / bonus article to cart
	 * @param sAddPremium - ordernumber of bonus article (defined in s_articles_premiums)
	 * Return to cart / confirm page on success
	 */
	public function addPremiumAction()
	{
		if ($this->Request()->isPost())
		{
			if(!$this->Request()->getParam('sAddPremium'))
			{
				$this->View()->sBasketInfo = Shopware()->Snippets()->getSnippet()->get('CheckoutSelectPremiumVariant', 'Bitte w�hlen Sie eine Variante aus, um den gew�nschte Pr�mie in den Warenkorb zu legen.', true);
			}
			else
			{
				$this->basket->sSYSTEM->_GET['sAddPremium'] = $this->Request()->getParam('sAddPremium');
				$this->basket->sInsertPremium();
			}
		}
		$this->forward($this->Request()->getParam('sTargetAction', 'index'));
	}
	
	/**
	 * On any change on country, payment or dispatch recalculate shipping costs
	 * and forward to cart / confirm view
	 */
	public function calculateShippingCostsAction()
	{
		if ($this->Request()->getPost('sCountry')) {
			$this->session['sCountry'] = (int) $this->Request()->getPost('sCountry');
		}
		if ($this->Request()->getPost('sPayment')) {
			$this->session['sPaymentID'] = (int) $this->Request()->getPost('sPayment');
		}
		if ($this->Request()->getPost('sDispatch')) {
			$this->session['sDispatch'] = (int) $this->Request()->getPost('sDispatch');
		}
		$this->forward($this->Request()->getParam('sTargetAction', 'index'));
	}
	
	/**
	 * Get complete user-data as an array to use in view
	 *
	 * @return array
	 */
	public function getUserData()
	{
		$userData = $this->admin->sGetUserData();

		if(!empty($userData['additional']['countryShipping']))
		{
			$sTaxFree = false;
			if (!empty( $userData['additional']['countryShipping']['taxfree'])){
				$sTaxFree = true;
			} elseif (
				(!empty($userData['additional']['countryShipping']['taxfree_ustid']) || !empty($userData['additional']['countryShipping']['taxfree_ustid_checked']))
				&& !empty($userData['billingaddress']['ustid'])
				&& $userData['additional']['country']['id'] == $userData['additional']['countryShipping']['id']) {
				$sTaxFree = true;
			}
			if(!empty($sTaxFree))
			{
				Shopware()->System()->sUSERGROUPDATA['tax'] = 0;
				Shopware()->System()->sCONFIG['sARTICLESOUTPUTNETTO'] = 1;
				Shopware()->System()->_SESSION['sUserGroupData'] = Shopware()->System()->sUSERGROUPDATA;
				$userData['additional']['charge_vat'] = false;
				$userData['additional']['show_net'] = false;
			}
			else
			{
				$userData['additional']['charge_vat'] = true;
				$userData['additional']['show_net'] = !empty(Shopware()->System()->sUSERGROUPDATA['tax']);
			}
		}
		
		return $userData;
	}
	
	/**
	 * Create temporary order in s_order_basket on confirm page
	 * Used to track failed / aborted orders
	 */
	public function saveTemporaryOrder()
	{
		$order = Shopware()->Modules()->Order();
		
		$order->sUserData = $this->View()->sUserData;
		$order->sComment = isset($this->session['sComment']) ? $this->session['sComment'] : '';
		$order->sBasketData = $this->View()->sBasket;
		$order->sAmount = $this->View()->sBasket['sAmount'];
		$order->sAmountWithTax = !empty($this->View()->sBasket['AmountWithTaxNumeric']) ? $this->View()->sBasket['AmountWithTaxNumeric'] : $this->View()->sBasket['AmountNumeric'];
		$order->sAmountNet = $this->View()->sBasket['AmountNetNumeric'];
		$order->sShippingcosts = $this->View()->sBasket['sShippingcosts'];
		$order->sShippingcostsNumeric = $this->View()->sBasket['sShippingcostsWithTax'];
		$order->sShippingcostsNumericNet = $this->View()->sBasket['sShippingcostsNet'];
		$order->bookingId = Shopware()->System()->_POST['sBooking'];
		$order->dispatchId = $this->session['sDispatch'];
		$order->sNet = !$this->View()->sUserData['additional']['charge_vat'];
		
		$order->sDeleteTemporaryOrder();	// Delete previous temporary orders
		$order->sCreateTemporaryOrder();	// Create new temporary order
	}
	
	/**
	 * Finish order - set some object properties to do this
	 */
	public function saveOrder()
	{
		$order = Shopware()->Modules()->Order();
		
		$order->sUserData = $this->View()->sUserData;
		$order->sComment = isset($this->session['sComment']) ? $this->session['sComment'] : '';
		$order->sBasketData = $this->View()->sBasket;
		$order->sAmount = $this->View()->sBasket['sAmount'];
		$order->sAmountWithTax = !empty($this->View()->sBasket['AmountWithTaxNumeric']) ? $this->View()->sBasket['AmountWithTaxNumeric'] : $this->View()->sBasket['AmountNumeric'];
		$order->sAmountNet = $this->View()->sBasket['AmountNetNumeric'];
		$order->sShippingcosts = $this->View()->sBasket['sShippingcosts'];
		$order->sShippingcostsNumeric = $this->View()->sBasket['sShippingcostsWithTax'];
		$order->sShippingcostsNumericNet = $this->View()->sBasket['sShippingcostsNet'];
		$order->bookingId = Shopware()->System()->_POST['sBooking'];
		$order->dispatchId = $this->session['sDispatch'];
		$order->sNet = !$this->View()->sUserData['additional']['charge_vat'];
		
		return $order->sSaveOrder();
	}
		
	/**
	 * Used in ajax add cart action
	 * Check availability of product and return info / error - messages
	 *
	 * @param unknown_type $ordernumber article order number
	 * @param unknown_type $quantity quantity
	 * @return unknown
	 */
	public function getInstockInfo($ordernumber, $quantity)
	{		
		if(empty($ordernumber))	{
			return Shopware()->Snippets()->getSnippet()->get('CheckoutSelectVariant', 'Bitte w�hlen Sie eine Variante aus, um den gew�nschte Artikel in den Warenkorb zu legen.', true);
		}
		
		$quantity = max(1, (int) $quantity);
		$instock = $this->getAvailableStock($ordernumber);
		$instock['quantity'] += $quantity;
						
		if(empty($instock['articleID'])) {
			return  Shopware()->Snippets()->getSnippet()->get('CheckoutArticleNotFound', 'Artikel konnte nicht gefunden werden.', true);
		}
		
		if(!empty($instock['laststock'])||!empty(Shopware()->Config()->InstockInfo)) {
			if($instock['instock']<=0&&!empty($instock['laststock'])) {
				return Shopware()->Snippets()->getSnippet()->get('CheckoutArticleNoStock', 'Leider k�nnen wir den von Ihnen gew�nschten Artikel nicht mehr in ausreichender St�ckzahl liefern.', true);
			} elseif($instock['instock']<$instock['quantity']) {
				$result = 'Leider k�nnen wir den von Ihnen gew�nschten Artikel nicht mehr in ausreichender St�ckzahl liefern. (#0 von #1 lieferbar).';
				$result = Shopware()->Snippets()->getSnippet()->get('CheckoutArticleLessStock', $result, true);
				return str_replace(array('#0', '#1'), array($instock['instock'], $instock['quantity']), $result);
			}
		}
		return null;
	}
	
	/**
	 * Get current stock from a certain product defined by $ordernumber
	 * Support for multidimensional variants
	 *
	 * @param unknown_type $ordernumber
	 * @return array with article id / current basket quantity / instock / laststock
	 */
	public function getAvailableStock($ordernumber)
	{
		$sql = '
			SELECT
				a.id as articleID,
				ob.quantity,
				IF(IFNULL(av.instock, ad.instock)<0,0,IFNULL(av.instock, ad.instock)) as instock,
				a.laststock,
				IFNULL(av.ordernumber, ad.ordernumber) as ordernumber
			FROM s_articles a
			LEFT JOIN s_articles_groups_value av
			ON av.ordernumber=?
			LEFT JOIN s_articles_details ad
			ON ad.ordernumber=?
			LEFT JOIN s_order_basket ob
			ON ob.sessionID=?
			AND ob.ordernumber=IFNULL(av.ordernumber, ad.ordernumber)
			AND ob.modus=0
			WHERE a.id=av.articleID
			OR a.id=ad.articleID
		';
		$row = Shopware()->Db()->fetchRow($sql, array(
			$ordernumber,
			$ordernumber,
			Shopware()->SessionID(),
		));
		return $row;
	}
	
	/**
	 * Get Shippingcosts as an array (brutto / netto) depending on selected country / payment
	 *
	 * @return array
	 */
	public function getShippingCosts()
	{
		$country = $this->getSelectedCountry();
		$payment = $this->getSelectedPayment();
		if(empty($country)||empty($payment)) return array('brutto'=>0, 'netto'=>0);
		$shippingcosts = $this->admin->sGetShippingcosts($country, $payment['surcharge'], $payment['surchargestring']);
		return empty($shippingcosts) ? array('brutto'=>0, 'netto'=>0) : $shippingcosts;
	}
	
	/**
	 * Return complete basket data to view
	 * Basket items / Shippingcosts / Amounts / Tax-Rates
	 *
	 * @return array
	 */
	public function getBasket()
	{
		$this->basket->sCheckBasketBundles(); 
		
		$basket = $this->basket->sGetBasket();
		
		$shippingcosts = $this->getShippingCosts();
		
		$basket = $this->basket->sGetBasket();
		
		$basket['sShippingcostsWithTax'] = $shippingcosts['brutto'];
		$basket['sShippingcostsNet'] = $shippingcosts['netto'];
		$basket['sShippingcostsTax'] = $shippingcosts['tax'];
		
		if (!empty($shippingcosts['brutto']))
		{
			$basket['AmountNetNumeric'] += $shippingcosts['netto'];
			$basket['AmountNumeric'] += $shippingcosts['brutto'];
			$basket['sShippingcostsDifference'] = $shippingcosts['difference']['float'];
		}
		if (!empty($basket['AmountWithTaxNumeric']))
		{
			$basket['AmountWithTaxNumeric'] += $shippingcosts['brutto'];
		}
		if ((!Shopware()->System()->sUSERGROUPDATA['tax'] && Shopware()->System()->sUSERGROUPDATA['id']))
		{
			$basket['sTaxRates'] = $this->getTaxRates($basket);
			
			$basket['sShippingcosts'] = $shippingcosts['netto'];
			$basket['sAmount'] = round($basket['AmountNetNumeric'], 2);
			$basket['sAmountTax'] = round($basket['AmountWithTaxNumeric']-$basket['AmountNetNumeric'], 2);
			$basket['sAmountWithTax'] = round($basket['AmountWithTaxNumeric'], 2);
			
		}
		else
		{
			$basket['sTaxRates'] = $this->getTaxRates($basket);	
			
			$basket['sShippingcosts'] = $shippingcosts['brutto'];
			$basket['sAmount'] = $basket['AmountNumeric'];
			
			$basket['sAmountTax'] = round($basket['AmountNumeric']-$basket['AmountNetNumeric'], 2);
		}
		return $basket;
	}
	
	/**
	 * Returns tax rates for all basket positions
	 *
	 * @param unknown_type $basket array returned from this->getBasket
	 * @return array
	 */
	public function getTaxRates($basket)
	{
		$result = array();

		if (!empty($basket['sShippingcostsTax']))
		{
			$result[$basket['sShippingcostsTax']] = $basket['sShippingcostsWithTax']-$basket['sShippingcostsNet'];
		}
		elseif ($basket['sShippingcostsWithTax'])
		{
			$result[Shopware()->Config()->get('sTAXSHIPPING')] = $basket['sShippingcostsWithTax']-$basket['sShippingcostsNet'];
		}

		if(empty($basket['content'])){
			ksort($result, SORT_NUMERIC);
			return $result;
		}


		foreach ($basket['content'] as $item)
		{
			if(!empty($item['taxID']))
			{
				$item['tax_rate'] = Shopware()->Db()->fetchOne('SELECT tax FROM s_core_tax WHERE id=?', array($item['taxID']));
			} elseif($item['modus']==2) {
				// Ticket 4842 - dynamic tax-rates
				$resultVoucherTaxMode = Shopware()->Db()->fetchOne("SELECT taxconfig FROM s_emarketing_vouchers WHERE ordercode=?",array($item["ordernumber"]));
				// Old behaviour
				if (empty($resultVoucherTaxMode) || $resultVoucherTaxMode == "default"){
					$tax = Shopware()->Config()->get('sVOUCHERTAX');
				}elseif ($resultVoucherTaxMode == "auto"){
					// Automaticly determinate tax
					$tax = Shopware()->Modules()->Basket()->getMaxTax();
				}elseif ($resultVoucherTaxMode=="none"){
					// No tax
					$tax = "0";
				}elseif (intval($resultVoucherTaxMode)){
					// Fix defined tax
					$tax = Shopware()->Db()->fetchOne("
					SELECT tax FROM s_core_tax WHERE id = ?
					",array($resultVoucherTaxMode));
				}
				$item['tax_rate'] = $tax;
			} else {
				// Ticket 4842 - dynamic tax-rates
				$taxAutoMode = Shopware()->Config()->get('sTAXAUTOMODE');
				if (!empty($taxAutoMode)){
					$tax = Shopware()->Modules()->Basket()->getMaxTax();
				}else {
					$tax = Shopware()->Config()->get('sDISCOUNTTAX');
				}
				$item['tax_rate'] = $tax;
			}

			if (empty($item['tax_rate'])) continue; // Ignore 0 % tax
			if(!isset($result[$item['tax_rate']])) $result[$item['tax_rate']] = 0;
			$result[floatval($item['tax_rate'])] += str_replace(',', '.', $item['tax']);
		}

		ksort($result, SORT_NUMERIC);

		return $result;
	}
	
	/**
	 * Get similar shown products to display in ajax add dialog
	 *
	 * @param unknown_type $articleID
	 * @return unknown
	 */
	public function getSimilarShown($articleID)
	{
		Shopware()->Modules()->Crossselling()->sBlacklist = Shopware()->Modules()->Basket()->sGetBasketIds();

		$smilarIDs = Shopware()->Modules()->Crossselling()->sGetSimilaryShownArticles($articleID);
				
		$smilars = array();
		if(!empty($smilarIDs))
		foreach ($smilarIDs as $smilarID){
			$smilar = Shopware()->Modules()->Articles()->sGetPromotionById('fix', 0, (int)$smilarID['id']);
			if(!empty($smilar))
			{
				$smilars[] = $smilar;
			}
		}
		return $smilars;
		
		$this->View()->sCrossSimilarShown = $smilars;
	}
	
	/**
	 * Get articles that bought in combination with last added product to
	 * display on cart page
	 *
	 * @param unknown_type $articleID
	 * @return unknown
	 */
	public function getBoughtToo($articleID)
	{
		Shopware()->Modules()->Crossselling()->sBlacklist = Shopware()->Modules()->Basket()->sGetBasketIds();
		
		$boughttooIDs = Shopware()->Modules()->Crossselling()->sGetAlsoBoughtArticles($articleID);
		$boughttoos = array();
		if(!empty($boughttooIDs))
		foreach ($boughttooIDs as $boughttooID){
			$boughttoo = Shopware()->Modules()->Articles()->sGetPromotionById('fix',0,(int) $boughttooID['id']);
			if(!empty($boughttoo))
			{
				$boughttoos[] = $boughttoo;
			}
		}
		return $boughttoos;
	}
	
	/**
	 * Get configured minimum charge to check in order processing
	 *
	 * @return unknown
	 */
	public function getMinimumCharge()
	{
		return $this->basket->sCheckMinimumCharge();
	}
	
	/**
	 * Check if order is possible under current conditions (dispatch)
	 *
	 * @return unknown
	 */
	public function getDispatchNoOrder()
	{
		return !empty(Shopware()->Config()->PremiumShippiungNoOrder)&&(empty($this->session['sDispatch'])||empty($this->session['sCountry']));
	}
	
	/**
	 * Get all premium products that are configured and available for this order
	 *
	 * @return unknown
	 */
	public function getPremiums()
	{
		$sql = 'SELECT `id` FROM `s_order_basket` WHERE `sessionID`=? AND `modus`=1';
		$result = Shopware()->Db()->fetchOne($sql, array(Shopware()->SessionID()));
		if(!empty($result)) return array();
		return Shopware()->Modules()->Marketing()->sGetPremiums();
	}
	
	/**
	 * Check if any electronically distribution product is in basket
	 *
	 * @return unknown
	 */
	public function getEsdNote()
	{
		$payment = empty($this->View()->sUserData['additional']['payment']) ? $this->session['sOrderVariables']['sUserData']['additional']['payment'] : $this->View()->sUserData['additional']['payment'];
		return $this->basket->sCheckForESD() && !$payment['esdactive'];
	}
	
	/**
	 * Check if a custom inquiry possibility should displayed on cart page
	 * Compare configured inquirevalue with current amount
	 *
	 * @return boolean
	 */
	public function getInquiry()
	{
		if (Shopware()->Config()->get('sINQUIRYVALUE'))
		{
			$factor = Shopware()->System()->sCurrency['factor'] ? 1 : Shopware()->System()->sCurrency['factor'];
			$value = Shopware()->Config()->get('sINQUIRYVALUE')*$factor;
			if ((!Shopware()->System()->sUSERGROUPDATA['tax'] && Shopware()->System()->sUSERGROUPDATA['id'])){
				$amount = $this->View()->sBasket['AmountWithTaxNumeric'];
			}else {
				$amount = $this->View()->sBasket['AmountNumeric'];
			}
			if (!empty($amount) && $amount >= $value)
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Get link to inquiry form if getInquiry returend true
	 *
	 * @return link
	 */
	public function getInquiryLink()
	{
		return Shopware()->Config()->get('sBASEFILE').'?sViewport=support&sFid='.Shopware()->Config()->get('sINQUIRYID').'&sInquiry=basket';
	}
	
	/**
	 * Get all countries from database via sAdmin object
	 *
	 * @return list of countries
	 */
	public function getCountryList()
	{
		return $this->admin->sGetCountryList();
	}
	
	/**
	 * Get all dispatches available in selected country from sAdmin object
	 *
	 * @return list of dispatches
	 */
	public function getDispatches()
	{
		$country = $this->getSelectedCountry();
		if(empty($country)) {
			return false;
		}
		return $this->admin->sGetDispatches($country['id']);
	}
	
	/**
	 * Returns all available payment methods from sAdmin object
	 *
	 * @return list of payment methods
	 */
	public function getPayments()
	{
		return $this->admin->sGetPaymentMeans();
	}
	
	/**
	 * Get current selected country - if no country is selected, choose first one from list
	 * of available countries
	 *
	 * @return array with country information
	 */
	public function getSelectedCountry()
	{
		if(!empty($this->View()->sUserData['additional']['countryShipping'])) {
			$this->session['sCountry'] = (int) $this->View()->sUserData['additional']['countryShipping']['id'];
			return $this->View()->sUserData['additional']['countryShipping'];
		}
		$countries = $this->getCountryList();
		if(empty($countries))
		{
			unset($this->session['sCountry']);
			return false;
		}
		$country = reset($countries);
		$this->session['sCountry'] = (int) $country['id'];
		$this->View()->sUserData['additional']['countryShipping'] = $country;
		return $country;
	}
	
	/**
	 * Get selected payment or do payment mean selection automatically
	 *
	 * @return unknown
	 */
	public function getSelectedPayment()
	{
		if(!empty($this->View()->sUserData['additional']['payment'])) {
			$payment = $this->View()->sUserData['additional']['payment'];
		} elseif(!empty($this->session['sPaymentID'])) {
			$payment = $this->admin->sGetPaymentMeanById($this->session['sPaymentID'], $this->View()->sUserData);
		}
		if (!empty($payment['table'])) {
			$paymentClass = $this->admin->sInitiatePaymentClass($payment);
			if (!empty($paymentClass)) {
				$payment['data'] = $paymentClass->getData();
			}
		}
		if(!empty($payment)) {
			return $payment;
		}
		$payments = $this->getPayments();
		if(empty($payments)) {
			unset($this->session['sPaymentID']);
			return false;
		}
		$payment = reset($payments);
		$this->session['sPaymentID'] = (int) $payment['id'];
		return $payment;
	}
	
	/**
	 * Get selected dispatch or select a default dispatch
	 *
	 * @return false|array
	 */
	public function getSelectedDispatch()
	{
		if(empty($this->session['sCountry'])) {
			return false;
		}
			
		$dispatches = $this->admin->sGetDispatches($this->session['sCountry']);
		if(empty($dispatches))
		{
			unset($this->session['sDispatch']);
			return false;
		}
					
		foreach ($dispatches as $dispatch)
		{
			if($dispatch['id']==$this->session['sDispatch'])
			{
				return $dispatch;
			}
		}
		$dispatch = reset($dispatches);
		$this->session['sDispatch'] = (int) $dispatch['id'];
		return $dispatch;
	}

	/**
	 * Ajax add article action
	 * 
	 * Loads the ajax padding plugin.
	 */
	public function ajaxAddArticleAction()
	{
		Enlight()->Plugins()->Controller()->Json()->setPadding();
	}
	
	/**
	 * Ajax cart action
	 * 
	 * Loads the cart in order to send via ajax.
	 */
	public function ajaxCartAction()
	{
		Enlight()->Plugins()->Controller()->Json()->setPadding();
		
		$this->View()->sUserData = $this->getUserData();	
				
		$this->View()->sBasket = $this->getBasket();
		
		$this->View()->sShippingcosts = $this->View()->sBasket['sShippingcosts'];
		$this->View()->sShippingcostsDifference = $this->View()->sBasket['sShippingcostsDifference'];
		$this->View()->sAmount = $this->View()->sBasket['sAmount'];
		$this->View()->sAmountWithTax = $this->View()->sBasket['sAmountWithTax'];
		$this->View()->sAmountTax = $this->View()->sBasket['sAmountTax'];
		$this->View()->sAmountNet = $this->View()->sBasket['AmountNetNumeric'];
		
	}
	
	/**
	 * Get current amount from cart via ajax to display in realtime
	 */
	public function ajaxAmountAction()
	{
		Enlight()->Plugins()->Controller()->Json()->setPadding();
	}
}