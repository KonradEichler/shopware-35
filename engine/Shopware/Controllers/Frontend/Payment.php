<?php
/**
 * Shopware Payment Controller
 * 
 * @link http://www.shopware.de
 * @copyright Copyright (c) 2011, shopware AG
 * @author Heiner Lohaus
 * @package Shopware
 * @subpackage Controllers
 */
abstract class Shopware_Controllers_Frontend_Payment extends Enlight_Controller_Action
{
	/**
	 * Returns the current payment short name.
	 *
	 * @return string
	 */
	public function getPaymentShortName()
	{
		if(($user = $this->getUser()) !== null
		  && !empty($user['additional']['payment']['name'])) {
			return $user['additional']['payment']['name'];
		} else {
			return null;
		}
	}
	
	/**
	 * Returns the current currency short name.
	 *
	 * @return string
	 */
	public function getCurrencyShortName()
	{
		return Shopware()->Currency()->getShortName();
	}
	
	/**
	 * Creates a unique payment id and returns it then.
	 *
	 * @return unknown
	 */
	public function createPaymentUniqueId()
	{
		return md5(uniqid(mt_rand(), true));
	}
	
	/**
	 * Stores the final order and does some more actions accordingly.
	 *
	 * @param string $transactionId
	 * @param string $paymentUniqueId
	 * @param int $paymentStatusId
	 * @param bool $sendStatusMail
	 * @return int
	 */
	public function saveOrder($transactionId, $paymentUniqueId, $paymentStatusId = null, $sendStatusMail = false)
	{
		if(empty($transactionId) || empty($paymentUniqueId)) {
			return false;
		}
		
		$sql = '
			SELECT ordernumber FROM s_order
			WHERE transactionID=? AND temporaryID=?
			AND status!=-1 AND userID=?
		';
		$orderNumber = Shopware()->Db()->fetchOne($sql, array(
			$transactionId,
			$paymentUniqueId,
			Shopware()->Session()->sUserId
		));
		
		if(empty($orderNumber)) {
			$user = $this->getUser();
			$basket = $this->getBasket();
			
	       	$order = Shopware()->Modules()->Order();
			$order->sUserData = $user;
			$order->sComment = Shopware()->Session()->sComment;
			$order->sBasketData = $basket;
			$order->sAmount = $basket['sAmount'];
			$order->sAmountWithTax = $basket['AmountNumeric'];
			$order->sAmountNet = $basket['AmountNetNumeric'];
			$order->sShippingcosts = $basket['sShippingcosts'];
			$order->sShippingcostsNumeric = $basket['sShippingcostsWithTax'];
			$order->sShippingcostsNumericNet = $basket['sShippingcostsNet'];
			$order->bookingId = $transactionId;
			$order->dispatchId = Shopware()->Session()->sDispatch;
			$order->sNet = empty($user['additional']['charge_vat']);
			$order->uniqueID = $paymentUniqueId;
			$orderNumber = $order->sSaveOrder();
			
			if(!empty(Shopware()->Config()->DeleteCacheAfterOrder)) {
				Shopware()->Cache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('Shopware_Adodb'));
			}
		}
        
        if (!empty($orderNumber) && !empty($paymentStatusId)) {
        	$this->savePaymentStatus($transactionId, $paymentUniqueId, $paymentStatusId, $sendStatusMail);
		}
		
		return $orderNumber;
	}
	
	/**
	 * Saves the payment status an sends and possibly sends a status email.
	 *
	 * @param string $transactionId
	 * @param string $paymentUniqueId
	 * @param int $paymentStatusId
	 * @param bool $sendStatusMail
	 * @return void
	 */
	public function savePaymentStatus($transactionId, $paymentUniqueId, $paymentStatusId, $sendStatusMail = false)
	{
		$sql = '
			SELECT id FROM s_order
			WHERE transactionID=? AND temporaryID=?
			AND status!=-1
		';
		$orderId = Shopware()->Db()->fetchOne($sql, array(
			$transactionId,
			$paymentUniqueId
		));
		$order = Shopware()->Modules()->Order();
        $order->setPaymentStatus($orderId, $paymentStatusId, $sendStatusMail);
	}
	
	/**
	 * Return the full amount to pay.
	 *
	 * @return float
	 */
	public function getAmount()
	{
		$user = $this->getUser();
		$basket = $this->getBasket();
		if (!empty($user['additional']['charge_vat'])){
			return empty($basket['AmountWithTaxNumeric']) ? $basket['AmountNumeric'] : $basket['AmountWithTaxNumeric'];
		} else {
			return $basket['AmountNetNumeric'];
		}
	}
	
	/**
	 * Returns the full user data as array.
	 *
	 * @return array
	 */
	public function getUser()
	{
		if(!empty(Shopware()->Session()->sOrderVariables['sUserData'])) {
			return Shopware()->Session()->sOrderVariables['sUserData'];
		} else {
			return null;
		}
	}

	/**
	 * Returns the full basket data as array.
	 *
	 * @return array
	 */
	public function getBasket()
	{
		if(!empty(Shopware()->Session()->sOrderVariables['sBasket'])) {
			return Shopware()->Session()->sOrderVariables['sBasket'];
		} else {
			return null;
		}
	}
}