<?php
/**
 * Shopware Order Model
 * 
 * @link http://www.shopware.de
 * @copyright Copyright (c) 2011, shopware AG
 * @author Stefan Hamann
 */
class
	Shopware_Models_Document_Order extends Enlight_Class implements Enlight_Hook
{
	/**
     * Id of the order (s_order.id)
     *
     * @var int
     */
	protected $_id;
	/**
     * Metadata of the order
     *
     * @var array
     */
	protected $_order;
	/**
     * Metadata of the order positions
     *
     * @var array
     */
	protected $_positions;
	/**
     * Id of the user (s_user.id)
     *
     * @var int
     */
	protected $_userID;
	/**
     * Metadata of the user (email,customergroup etc. s_user.*)
     *
     * @var array
     */
	protected $_user;
	/**
     * Billingdata for this order / user (s_order_billingaddress)
     *
     * @var array
     */
	protected $_billing;
	/**
     * Shippingdata for this order / user (s_order_shippingaddress)
     *
     * @var array
     */
	protected $_shipping;
	/**
     * Payment information for this order (s_core_paymentmeans)
     *
     * @var array
     */
	protected $_payment;
	/**
     * Information about the dispatch for this order
     *
     * @var array
     */
	protected $_dispatch;
	/**
     * Calculate complete without tax
     *
     * @var bool
     */
	protected $_net;
	/**
     * Hide Gross amount
     *
     * @var bool
     */
	protected $_summaryNet;
	/**
     * Complete net amount
     *
     * @var double
     */
	protected $_amountNetto;
	/**
     * Complete gross amount
     *
     * @var double
     */
	protected $_amount;
	/**
     * Array with tax rates
     *
     * @var array
     */
	protected $_tax;
	/**
     * Currency information (s_core_currencies)
     *
     * @var array
     */
	protected $_currency;
	/**
     * Shippingcosts
     *
     * @var double
     */
	protected $_shippingCosts;
	/**
     * Add shippingcosts as order position
     *
     * @var bool
     */
	protected $_shippingCostsAsPosition;
	protected $_discount;

	/**
	 * Initiate order model
	 * @param  $id
	 * @param array $config
	 */
	public function __construct($id,$config=array()){

		// Test-data for preview mode
		if ($config["_preview"] == true && $config["_previewSample"]==true){
			$php53 = 'a:17:{s:7:"_amount";i:142;s:12:"_amountNetto";d:119.32773109243697717829491011798381805419921875;s:8:"_billing";s:1011:"C:11:"ArrayObject":986:{x:i:2;a:24:{s:2:"id";s:1:"1";s:6:"userID";s:1:"2";s:7:"orderID";s:1:"7";s:7:"company";s:11:"shopware AG";s:10:"department";s:0:"";s:10:"salutation";s:2:"mr";s:14:"customernumber";s:0:"";s:9:"firstname";s:6:"Test  ";s:8:"lastname";s:6:"Test  ";s:6:"street";s:9:"Testerweg";s:12:"streetnumber";s:1:"2";s:7:"zipcode";s:5:"48624";s:4:"city";s:11:"Sch�ppingen";s:5:"phone";s:14:"02555 / 997500";s:3:"fax";s:0:"";s:9:"countryID";s:1:"2";s:5:"ustid";s:0:"";s:5:"text1";s:0:"";s:5:"text2";s:0:"";s:5:"text3";s:0:"";s:5:"text4";s:0:"";s:5:"text5";s:0:"";s:5:"text6";s:0:"";s:7:"country";C:11:"ArrayObject":373:{x:i:2;a:13:{s:2:"id";s:1:"2";s:11:"countryname";s:11:"Deutschland";s:10:"countryiso";s:2:"DE";s:11:"countryarea";s:11:"deutschland";s:9:"countryen";s:7:"GERMANY";s:8:"position";s:1:"1";s:6:"notice";s:0:"";s:12:"shippingfree";s:1:"0";s:7:"taxfree";s:1:"0";s:13:"taxfree_ustid";s:1:"0";s:21:"taxfree_ustid_checked";s:1:"0";s:6:"active";s:1:"1";s:4:"iso3";s:3:"DEU";};m:a:0:{}}};m:a:0:{}}";s:9:"_currency";s:138:"C:11:"ArrayObject":113:{x:i:2;a:4:{s:8:"currency";s:3:"EUR";s:4:"name";s:4:"Euro";s:6:"factor";s:1:"1";s:4:"char";s:6:"&euro;";};m:a:0:{}}";s:9:"_dispatch";s:45:"C:11:"ArrayObject":21:{x:i:2;a:0:{};m:a:0:{}}";s:3:"_id";i:0;s:4:"_net";b:0;s:6:"_order";s:1014:"C:11:"ArrayObject":989:{x:i:2;a:36:{s:2:"id";s:1:"7";s:11:"ordernumber";s:5:"10001";s:6:"userID";s:1:"2";s:14:"invoice_amount";s:3:"142";s:18:"invoice_amount_net";s:6:"119.32";s:16:"invoice_shipping";s:2:"13";s:20:"invoice_shipping_net";s:5:"10.92";s:9:"ordertime";s:19:"2010-09-09 00:00:00";s:6:"status";s:1:"7";s:7:"cleared";s:2:"17";s:9:"paymentID";s:1:"3";s:13:"transactionID";s:0:"";s:7:"comment";s:0:"";s:15:"customercomment";s:0:"";s:3:"net";s:1:"0";s:7:"taxfree";s:1:"0";s:9:"partnerID";s:0:"";s:11:"temporaryID";s:0:"";s:7:"referer";s:0:"";s:11:"cleareddate";s:19:"0000-00-00 00:00:00";s:12:"trackingcode";s:0:"";s:8:"language";s:2:"de";s:10:"dispatchID";s:1:"9";s:8:"currency";s:3:"EUR";s:14:"currencyFactor";s:1:"1";s:9:"subshopID";s:1:"1";s:7:"o_attr1";s:0:"";s:7:"o_attr2";s:0:"";s:7:"o_attr3";s:0:"";s:7:"o_attr4";s:0:"";s:7:"o_attr5";s:0:"";s:7:"o_attr6";s:0:"";s:11:"remote_addr";s:14:"192.168.178.53";s:6:"factor";s:1:"1";s:12:"templatechar";s:6:"&euro;";s:12:"currencyName";s:4:"Euro";};m:a:0:{}}";s:8:"_payment";s:493:"C:11:"ArrayObject":468:{x:i:2;a:16:{s:2:"id";s:1:"3";s:4:"name";s:4:"cash";s:11:"description";s:9:"Nachnahme";s:8:"template";s:8:"cash.tpl";s:5:"class";s:8:"cash.php";s:5:"table";s:0:"";s:4:"hide";s:1:"0";s:21:"additionaldescription";s:35:"(zzgl. 2,00 Euro Nachnahmegeb�hren)";s:13:"debit_percent";s:1:"0";s:9:"surcharge";s:1:"0";s:15:"surchargestring";s:0:"";s:8:"position";s:1:"2";s:6:"active";s:1:"1";s:9:"esdactive";s:1:"0";s:11:"embediframe";s:0:"";s:12:"hideprospect";s:1:"0";};m:a:0:{}}";s:10:"_positions";s:1354:"C:11:"ArrayObject":1328:{x:i:2;a:2:{i:0;a:46:{s:2:"id";s:1:"7";s:7:"orderID";s:1:"7";s:11:"ordernumber";s:5:"10001";s:9:"articleID";s:1:"8";s:18:"articleordernumber";s:6:"SW2003";s:5:"price";d:129;s:8:"quantity";s:1:"1";s:4:"name";s:18:"Lunaracer Gr��e 42";s:6:"status";s:1:"0";s:7:"shipped";s:1:"0";s:12:"shippedgroup";s:1:"0";s:11:"releasedate";s:10:"0000-00-00";s:5:"modus";s:1:"0";s:10:"esdarticle";s:1:"0";s:5:"taxID";s:1:"1";s:6:"config";s:0:"";s:8:"od_attr1";s:0:"";s:8:"od_attr2";s:0:"";s:8:"od_attr3";s:0:"";s:8:"od_attr4";s:0:"";s:8:"od_attr5";s:0:"";s:8:"od_attr6";s:0:"";s:5:"attr1";N;s:5:"attr2";N;s:5:"attr3";N;s:5:"attr4";N;s:5:"attr5";N;s:5:"attr6";N;s:5:"attr7";N;s:5:"attr8";N;s:5:"attr9";N;s:6:"attr10";N;s:6:"attr11";N;s:6:"attr12";N;s:6:"attr13";N;s:6:"attr14";N;s:6:"attr15";N;s:6:"attr16";N;s:6:"attr17";N;s:6:"attr18";N;s:6:"attr19";N;s:6:"attr20";N;s:3:"tax";s:2:"19";s:5:"netto";d:108.40336134453781369302305392920970916748046875;s:12:"amount_netto";d:108.40336134453781369302305392920970916748046875;s:6:"amount";i:129;}i:1;a:8:{s:8:"quantity";i:1;s:5:"netto";d:10.924369747899159932558177388273179531097412109375;s:3:"tax";s:2:"19";s:5:"price";s:2:"13";s:6:"amount";s:2:"13";s:12:"amount_netto";d:10.924369747899159932558177388273179531097412109375;s:18:"articleordernumber";s:0:"";s:4:"name";s:13:"Versandkosten";}};m:a:0:{}}";s:9:"_shipping";s:900:"C:11:"ArrayObject":875:{x:i:2;a:20:{s:2:"id";s:1:"1";s:6:"userID";s:1:"2";s:7:"orderID";s:1:"7";s:7:"company";s:0:"";s:10:"department";s:0:"";s:10:"salutation";s:2:"mr";s:9:"firstname";s:6:"Test  ";s:8:"lastname";s:6:"Test  ";s:6:"street";s:9:"Testerweg";s:12:"streetnumber";s:1:"2";s:7:"zipcode";s:5:"48624";s:4:"city";s:11:"Sch�ppingen";s:9:"countryID";s:1:"2";s:5:"text1";s:0:"";s:5:"text2";s:0:"";s:5:"text3";s:0:"";s:5:"text4";s:0:"";s:5:"text5";s:0:"";s:5:"text6";s:0:"";s:7:"country";C:11:"ArrayObject":373:{x:i:2;a:13:{s:2:"id";s:1:"2";s:11:"countryname";s:11:"Deutschland";s:10:"countryiso";s:2:"DE";s:11:"countryarea";s:11:"deutschland";s:9:"countryen";s:7:"GERMANY";s:8:"position";s:1:"1";s:6:"notice";s:0:"";s:12:"shippingfree";s:1:"0";s:7:"taxfree";s:1:"0";s:13:"taxfree_ustid";s:1:"0";s:21:"taxfree_ustid_checked";s:1:"0";s:6:"active";s:1:"1";s:4:"iso3";s:3:"DEU";};m:a:0:{}}};m:a:0:{}}";s:14:"_shippingCosts";s:2:"13";s:24:"_shippingCostsAsPosition";b:1;s:11:"_summaryNet";b:0;s:4:"_tax";s:61:"a:1:{i:19;d:22.67226890756302282170508988201618194580078125;}";s:5:"_user";s:615:"C:11:"ArrayObject":590:{x:i:2;a:19:{s:2:"id";s:1:"2";s:8:"password";s:32:"c899f4ef7be7ddaab75bcd8b6c9a85a8";s:5:"email";s:14:"hl@shopware.ag";s:6:"active";s:1:"1";s:11:"accountmode";s:1:"0";s:15:"confirmationkey";s:0:"";s:9:"paymentID";s:1:"5";s:10:"firstlogin";s:10:"2010-06-18";s:9:"lastlogin";s:19:"2010-08-09 15:04:12";s:9:"sessionID";s:32:"b33a7d0a8ee56fe430b70e36c6de7eec";s:10:"newsletter";s:1:"0";s:10:"validation";s:0:"";s:9:"affiliate";s:1:"0";s:13:"customergroup";s:2:"EK";s:13:"paymentpreset";s:1:"0";s:8:"language";s:2:"de";s:9:"subshopID";s:1:"1";s:7:"referer";s:0:"";s:12:"pricegroupID";N;};m:a:0:{}}";s:7:"_userID";i:0;}';
			
			$php52 = 'a:18:{s:3:"_id";s:2:"13";s:6:"_order";s:1021:"O:11:"ArrayObject":37:{s:2:"id";s:2:"13";s:11:"ordernumber";s:5:"10080";s:6:"userID";s:1:"5";s:14:"invoice_amount";s:6:"119.89";s:18:"invoice_amount_net";s:6:"100.75";s:16:"invoice_shipping";s:2:"17";s:20:"invoice_shipping_net";s:5:"14.29";s:9:"ordertime";s:19:"2010-09-27 17:25:15";s:6:"status";s:1:"0";s:7:"cleared";s:2:"17";s:9:"paymentID";s:1:"5";s:13:"transactionID";s:0:"";s:7:"comment";s:0:"";s:15:"customercomment";s:0:"";s:3:"net";s:1:"0";s:7:"taxfree";s:1:"0";s:9:"partnerID";s:0:"";s:11:"temporaryID";s:0:"";s:7:"referer";s:0:"";s:11:"cleareddate";s:19:"0000-00-00 00:00:00";s:12:"trackingcode";s:0:"";s:8:"language";s:2:"de";s:10:"dispatchID";s:1:"6";s:8:"currency";s:3:"EUR";s:14:"currencyFactor";s:1:"1";s:9:"subshopID";s:1:"1";s:7:"o_attr1";s:0:"";s:7:"o_attr2";s:0:"";s:7:"o_attr3";s:0:"";s:7:"o_attr4";s:0:"";s:7:"o_attr5";s:0:"";s:7:"o_attr6";s:0:"";s:11:"remote_addr";s:14:"192.168.178.18";s:6:"factor";s:1:"1";s:10:"currencyID";s:1:"1";s:12:"templatechar";s:6:"&euro;";s:12:"currencyName";s:4:"Euro";}";s:10:"_positions";s:3789:"O:11:"ArrayObject":4:{i:0;a:47:{s:2:"id";s:2:"37";s:7:"orderID";s:2:"13";s:11:"ordernumber";s:5:"10080";s:9:"articleID";s:2:"47";s:18:"articleordernumber";s:14:"SW2024_5983.12";s:5:"price";d:99.9899999999999948840923025272786617279052734375;s:8:"quantity";s:1:"1";s:4:"name";s:43:"Konfigurator Standard Gr��e: XL
Farbe: gelb";s:6:"status";s:1:"0";s:7:"shipped";s:1:"0";s:12:"shippedgroup";s:1:"0";s:11:"releasedate";s:10:"0000-00-00";s:5:"modus";s:1:"0";s:10:"esdarticle";s:1:"0";s:5:"taxID";s:1:"1";s:6:"config";s:0:"";s:8:"od_attr1";s:0:"";s:8:"od_attr2";s:0:"";s:8:"od_attr3";s:0:"";s:8:"od_attr4";s:0:"";s:8:"od_attr5";s:0:"";s:8:"od_attr6";s:0:"";s:5:"attr1";s:0:"";s:5:"attr2";s:0:"";s:5:"attr3";s:0:"";s:5:"attr4";s:0:"";s:5:"attr5";s:0:"";s:5:"attr6";s:0:"";s:5:"attr7";s:0:"";s:5:"attr8";s:0:"";s:5:"attr9";s:0:"";s:6:"attr10";s:0:"";s:6:"attr11";s:0:"";s:6:"attr12";s:0:"";s:6:"attr13";s:0:"";s:6:"attr14";s:0:"";s:6:"attr15";s:0:"";s:6:"attr16";s:0:"";s:6:"attr17";s:10:"0000-00-00";s:6:"attr18";s:0:"";s:6:"attr19";s:0:"";s:6:"attr20";s:0:"";s:4:"meta";b:0;s:3:"tax";s:2:"19";s:5:"netto";d:84.025210084033602697672904469072818756103515625;s:12:"amount_netto";d:84.025210084033602697672904469072818756103515625;s:6:"amount";d:99.9899999999999948840923025272786617279052734375;}i:1;a:47:{s:2:"id";s:2:"39";s:7:"orderID";s:2:"13";s:11:"ordernumber";s:5:"10080";s:9:"articleID";s:1:"0";s:18:"articleordernumber";s:11:"sw-discount";s:5:"price";d:-2.100000000000000088817841970012523233890533447265625;s:8:"quantity";s:1:"1";s:4:"name";s:21:"- 2 % Warenkorbrabatt";s:6:"status";s:1:"0";s:7:"shipped";s:1:"0";s:12:"shippedgroup";s:1:"0";s:11:"releasedate";s:10:"0000-00-00";s:5:"modus";s:1:"3";s:10:"esdarticle";s:1:"0";s:5:"taxID";s:1:"0";s:6:"config";s:0:"";s:8:"od_attr1";s:0:"";s:8:"od_attr2";s:0:"";s:8:"od_attr3";s:0:"";s:8:"od_attr4";s:0:"";s:8:"od_attr5";s:0:"";s:8:"od_attr6";s:0:"";s:5:"attr1";N;s:5:"attr2";N;s:5:"attr3";N;s:5:"attr4";N;s:5:"attr5";N;s:5:"attr6";N;s:5:"attr7";N;s:5:"attr8";N;s:5:"attr9";N;s:6:"attr10";N;s:6:"attr11";N;s:6:"attr12";N;s:6:"attr13";N;s:6:"attr14";N;s:6:"attr15";N;s:6:"attr16";N;s:6:"attr17";N;s:6:"attr18";N;s:6:"attr19";N;s:6:"attr20";N;s:4:"meta";b:0;s:3:"tax";s:2:"19";s:5:"netto";d:-1.76470588235294112422479884116910398006439208984375;s:12:"amount_netto";d:-1.76470588235294112422479884116910398006439208984375;s:6:"amount";d:-2.100000000000000088817841970012523233890533447265625;}i:2;a:47:{s:2:"id";s:2:"38";s:7:"orderID";s:2:"13";s:11:"ordernumber";s:5:"10080";s:9:"articleID";s:1:"0";s:18:"articleordernumber";s:12:"sw-surcharge";s:5:"price";d:5;s:8:"quantity";s:1:"1";s:4:"name";s:20:"Mindermengenzuschlag";s:6:"status";s:1:"0";s:7:"shipped";s:1:"0";s:12:"shippedgroup";s:1:"0";s:11:"releasedate";s:10:"0000-00-00";s:5:"modus";s:1:"4";s:10:"esdarticle";s:1:"0";s:5:"taxID";s:1:"0";s:6:"config";s:0:"";s:8:"od_attr1";s:0:"";s:8:"od_attr2";s:0:"";s:8:"od_attr3";s:0:"";s:8:"od_attr4";s:0:"";s:8:"od_attr5";s:0:"";s:8:"od_attr6";s:0:"";s:5:"attr1";N;s:5:"attr2";N;s:5:"attr3";N;s:5:"attr4";N;s:5:"attr5";N;s:5:"attr6";N;s:5:"attr7";N;s:5:"attr8";N;s:5:"attr9";N;s:6:"attr10";N;s:6:"attr11";N;s:6:"attr12";N;s:6:"attr13";N;s:6:"attr14";N;s:6:"attr15";N;s:6:"attr16";N;s:6:"attr17";N;s:6:"attr18";N;s:6:"attr19";N;s:6:"attr20";N;s:4:"meta";b:0;s:3:"tax";s:2:"19";s:5:"netto";d:4.20168067226890773468994666473008692264556884765625;s:12:"amount_netto";d:4.20168067226890773468994666473008692264556884765625;s:6:"amount";i:5;}i:3;a:9:{s:8:"quantity";i:1;s:5:"netto";d:14.28571428571428469922466319985687732696533203125;s:3:"tax";s:2:"19";s:5:"price";s:2:"17";s:6:"amount";s:2:"17";s:5:"modus";i:1;s:12:"amount_netto";d:14.28571428571428469922466319985687732696533203125;s:18:"articleordernumber";s:0:"";s:4:"name";s:13:"Versandkosten";}}";s:7:"_userID";s:1:"5";s:5:"_user";s:586:"O:11:"ArrayObject":19:{s:2:"id";s:1:"5";s:8:"password";s:32:"a15370144783d9da799fcfacb99a881d";s:5:"email";s:14:"sk@shopware.de";s:6:"active";s:1:"1";s:11:"accountmode";s:1:"0";s:15:"confirmationkey";s:0:"";s:9:"paymentID";s:1:"5";s:10:"firstlogin";s:10:"2010-09-27";s:9:"lastlogin";s:19:"2010-09-27 18:01:47";s:9:"sessionID";s:26:"10pfde4j8usf5u5i9sr647aoi6";s:10:"newsletter";s:1:"0";s:10:"validation";s:0:"";s:9:"affiliate";s:1:"0";s:13:"customergroup";s:2:"EK";s:13:"paymentpreset";s:1:"0";s:8:"language";s:2:"de";s:9:"subshopID";s:1:"1";s:7:"referer";s:0:"";s:12:"pricegroupID";N;}";s:8:"_billing";s:966:"O:11:"ArrayObject":24:{s:2:"id";s:2:"13";s:6:"userID";s:1:"5";s:7:"orderID";s:2:"13";s:7:"company";s:0:"";s:10:"department";s:0:"";s:10:"salutation";s:2:"mr";s:14:"customernumber";s:5:"20033";s:9:"firstname";s:9:"Sebastian";s:8:"lastname";s:7:"Kl�pper";s:6:"street";s:11:"Hauptstra�e";s:12:"streetnumber";s:2:"36";s:7:"zipcode";s:5:"48624";s:4:"city";s:11:"Sch�ppingen";s:5:"phone";s:12:"02555-997500";s:3:"fax";s:0:"";s:9:"countryID";s:1:"2";s:5:"ustid";s:0:"";s:5:"text1";s:0:"";s:5:"text2";s:0:"";s:5:"text3";s:0:"";s:5:"text4";s:0:"";s:5:"text5";s:0:"";s:5:"text6";s:0:"";s:7:"country";O:11:"ArrayObject":13:{s:2:"id";s:1:"2";s:11:"countryname";s:11:"Deutschland";s:10:"countryiso";s:2:"DE";s:11:"countryarea";s:11:"deutschland";s:9:"countryen";s:7:"GERMANY";s:8:"position";s:1:"1";s:6:"notice";s:0:"";s:12:"shippingfree";s:1:"0";s:7:"taxfree";s:1:"0";s:13:"taxfree_ustid";s:1:"0";s:21:"taxfree_ustid_checked";s:1:"0";s:6:"active";s:1:"1";s:4:"iso3";s:3:"DEU";}}";s:9:"_shipping";s:864:"O:11:"ArrayObject":20:{s:2:"id";s:2:"13";s:6:"userID";s:1:"5";s:7:"orderID";s:2:"13";s:7:"company";s:0:"";s:10:"department";s:0:"";s:10:"salutation";s:2:"mr";s:9:"firstname";s:9:"Sebastian";s:8:"lastname";s:7:"Kl�pper";s:6:"street";s:11:"Hauptstra�e";s:12:"streetnumber";s:2:"36";s:7:"zipcode";s:5:"48624";s:4:"city";s:11:"Sch�ppingen";s:9:"countryID";s:1:"2";s:5:"text1";s:0:"";s:5:"text2";s:0:"";s:5:"text3";s:0:"";s:5:"text4";s:0:"";s:5:"text5";s:0:"";s:5:"text6";s:0:"";s:7:"country";O:11:"ArrayObject":13:{s:2:"id";s:1:"2";s:11:"countryname";s:11:"Deutschland";s:10:"countryiso";s:2:"DE";s:11:"countryarea";s:11:"deutschland";s:9:"countryen";s:7:"GERMANY";s:8:"position";s:1:"1";s:6:"notice";s:0:"";s:12:"shippingfree";s:1:"0";s:7:"taxfree";s:1:"0";s:13:"taxfree_ustid";s:1:"0";s:21:"taxfree_ustid_checked";s:1:"0";s:6:"active";s:1:"1";s:4:"iso3";s:3:"DEU";}}";s:8:"_payment";s:563:"O:11:"ArrayObject":16:{s:2:"id";s:1:"5";s:4:"name";s:10:"prepayment";s:11:"description";s:8:"Vorkasse";s:8:"template";s:14:"prepayment.tpl";s:5:"class";s:14:"prepayment.php";s:5:"table";s:0:"";s:4:"hide";s:1:"0";s:21:"additionaldescription";s:107:"Sie zahlen einfach vorab und erhalten die Ware bequem und g�nstig bei Zahlungseingang nach Hause geliefert.";s:13:"debit_percent";s:1:"0";s:9:"surcharge";s:1:"0";s:15:"surchargestring";s:0:"";s:8:"position";s:1:"1";s:6:"active";s:1:"1";s:9:"esdactive";s:1:"0";s:11:"embediframe";s:0:"";s:12:"hideprospect";s:1:"0";}";s:9:"_dispatch";s:83:"O:11:"ArrayObject":2:{s:4:"name";s:15:"Express Versand";s:11:"description";s:0:"";}";s:4:"_net";b:0;s:11:"_summaryNet";b:0;s:12:"_amountNetto";d:100.74789915966385933643323369324207305908203125;s:7:"_amount";d:119.8900000000000005684341886080801486968994140625;s:4:"_tax";a:1:{i:19;d:19.14210084033613412657359731383621692657470703125;}s:9:"_currency";s:115:"O:11:"ArrayObject":4:{s:8:"currency";s:3:"EUR";s:4:"name";s:4:"Euro";s:6:"factor";s:1:"1";s:4:"char";s:6:"&euro;";}";s:14:"_shippingCosts";s:2:"17";s:24:"_shippingCostsAsPosition";b:1;s:9:"_discount";d:-2.100000000000000088817841970012523233890533447265625;}';
			
			if (floatval(phpversion()) >= 5.3){
				$test = $php53;
			}else {
				$test = $php52;
			}
			
			$test = unserialize($test);
			
			foreach ($test as $key => $v){
				if (preg_match("/\{/",$v)){
					$this->$key = unserialize($v);
				}else {
					$this->$key = $v;
				}
			}
			
			return;
		}
		
		$this->_id = $id;

		$this->_summaryNet = isset($config["summaryNet"]) ? $config["summaryNet"] : false;
		$this->_shippingCostsAsPosition = isset($config["shippingCostsAsPosition"]) ? $config["shippingCostsAsPosition"] : false;
		
		$this->getOrder();
		$this->getPositions();
		
		$this->getUser();
		$this->getBilling();
		$this->getShipping();
		$this->getDispatch();
		$this->getPayment();
		
		$this->processPositions();
		$this->processOrder();
	}

	/**
	 * Get order database entries
	 * @throws Enlight_Exception
	 * @return void
	 */
	public function getOrder(){
		
		$this->_order = new ArrayObject(Shopware()->Db()->fetchRow("
			SELECT s_order.*,s_core_currencies.factor,s_core_currencies.id AS currencyID,s_core_currencies.templatechar,s_core_currencies.name AS currencyName
			FROM s_order 
		 	LEFT JOIN s_core_currencies ON s_core_currencies.currency = s_order.currency
		 	WHERE s_order.id = ? 
			",array($this->_id)), ArrayObject::ARRAY_AS_PROPS);
		
		if (empty($this->_order["id"])){
			throw new Enlight_Exception ("Order with id $id not found!");
		}
		$this->_userID = $this->_order["userID"];
		if (!empty($this->_order["net"]))$this->_net = true; else $this->_net = false;
		$this->_currency = new ArrayObject(array("currency"=>$this->_order["currency"],"name"=>$this->_order["currencyName"],"factor"=>$this->_order["factor"],"char"=>$this->_order["templatechar"]), ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Convert this object into an array
	 * @return array
	 */
	public function __toArray(){
		$array = get_object_vars($this);
		$array["_order"] = $array["_order"]->getArrayCopy();
		$array["_positions"] = $array["_positions"]->getArrayCopy();
		$array["_user"] = $array["_user"]->getArrayCopy();
		$array["_billing"] = $array["_billing"]->getArrayCopy();
		$array["_shipping"] = $array["_shipping"]->getArrayCopy();
		$array["_payment"] = $array["_payment"]->getArrayCopy();
		$array["_dispatch"] = $array["_dispatch"]->getArrayCopy();
		$array["_currency"] = $array["_currency"]->getArrayCopy();
		//$array["_order"] = current($array["_order"]);
		return $array;
	}

	/**
	 * Get all information from a certain order (model)
	 * @return void
	 */
	public function processOrder(){
		$sql = "
				SELECT `value` as tax 
				FROM s_core_config
				WHERE name='sTAXSHIPPING'
				AND ROUND(?*100/(100+`value`),2)=?
			UNION
				SELECT tax
				FROM `s_core_tax`
				WHERE ROUND(?*100/(100+tax),2)=?
		";
		$taxShipping = Shopware()->Db()->fetchOne($sql,array(
			$this->_order["invoice_shipping"],
			$this->_order["invoice_shipping_net"],
			$this->_order["invoice_shipping"],
			$this->_order["invoice_shipping_net"]
		));
		
	
		if ($this->_order["taxfree"]){
			$this->_amountNetto =  $this->_amountNetto + $this->_order["invoice_shipping"];
		
		}else {
			$this->_amountNetto =  $this->_amountNetto + ($this->_order["invoice_shipping"]/(100+$taxShipping)*100);
			if(!empty($taxShipping)) {
				$this->_tax[$taxShipping] += ($this->_order["invoice_shipping"]/(100+$taxShipping))*$taxShipping;
			}
		}
		
		$this->_amount =  $this->_amount + $this->_order["invoice_shipping"];
		$this->_shippingCosts = $this->_order["invoice_shipping"];	
					
		if ($this->_shippingCostsAsPosition == true){
			$shipping = array();
			$shipping['quantity'] = 1;
			
			if ($this->_order["taxfree"]){
				$shipping['netto'] =  $this->_shippingCosts;
				$shipping['tax'] = 0;
			}else {
				$shipping['netto'] =  $this->_shippingCosts/(100+$taxShipping)*100;
				$shipping['tax'] = $taxShipping;
			}
			$shipping['price'] = $this->_shippingCosts;
			$shipping['amount'] = $shipping['price'];
			$shipping["modus"] = 1;
			$shipping['amount_netto'] = $shipping['netto'];
			$shipping['articleordernumber'] = "";
			$shipping['name'] = "Versandkosten";
			
			$this->positions[] = $shipping;
		}
	}

	/**
	 * Get all order positions
	 * @return void
	 */
	public function getPositions(){
		$this->_positions = new ArrayObject(Shopware()->Db()->fetchAll("
		SELECT
			od.*, 
			at.attr1, at.attr2, at.attr3, at.attr4, at.attr5, at.attr6, at.attr7, at.attr8, at.attr9, at.attr10,
			at.attr11, at.attr12, at.attr13, at.attr14, at.attr15, at.attr16, at.attr17, at.attr18, at.attr19, at.attr20
		FROM  s_order_details od
		
		LEFT JOIN s_articles_groups_value g
		ON g.ordernumber=od.articleordernumber
		AND g.articleID=od.articleID
		AND od.modus=0
		
		LEFT JOIN s_articles_details d
		ON (g.articleID=d.articleID OR d.ordernumber=od.articleordernumber)
		AND d.articleID=od.articleID
		AND od.modus=0

		LEFT JOIN s_articles_attributes at
		ON at.articledetailsID=d.id
		
		WHERE od.orderID=?
		ORDER BY od.modus ASC
		",array($this->_id)), ArrayObject::ARRAY_AS_PROPS);
		foreach ($this->_positions as &$position){
			$position["meta"] = Shopware()->Modules()->Articles()->sGetPromotionById('fix',0,$position["articleordernumber"]);
		}
	}

	/**
	 * Get maximum used tax-rate in this order
	 * @return int|string
	 */
	public function getMaxTaxRate(){
		$maxTax = 0;
		foreach ($this->_positions as $position){
			if ($position["mode"] == 0){
				$getTax = Shopware()->Db()->fetchOne("
				SELECT tax FROM s_core_tax WHERE id = ?
				",array($position["taxID"]));
				if ($getTax > $maxTax){
					$maxTax = $getTax;
				}
			}
		}
		return $maxTax;
	}

	/**
	 * Process basket positions and add tax-informations
	 * @return void
	 */
	public function processPositions(){
		foreach ($this->_positions as &$position){
			$position["name"] = str_replace(array("�"),array("&euro;"),$position["name"]);
			if (empty($position["quantity"])) continue;
			
			/*
			modus 0 = default article
			modus 1 = premium articles
			modus 2 = voucher
			modus 3 = customergroup discount
			modus 4 = payment surcharge / discount
			modus 10 = bundle discount
			*/
			if ($position["modus"]==0 || $position["modus"]==4 || $position["modus"] == 3 || $position["modus"]==10)
			{
				/* 
				Read tax for each order position
				*/
				if($position["modus"]==4 || $position["modus"] == 3)
				{
					// Discounts get tax from configuration
					if (!empty(Shopware()->Config()->sTAXAUTOMODE)){
						$tax = $this->getMaxTaxRate();
					}else {
						$tax = Shopware()->Config()->sDISCOUNTTAX;
					}
					$position["tax"] = $tax;
				}
				elseif(empty($position["taxID"]))
				{
					// Articles get tax per item configuration
					$position["tax"] = Shopware()->Db()->fetchOne("SELECT s_core_tax.tax AS tax FROM s_core_tax, s_articles WHERE s_articles.id=? AND s_core_tax.id=s_articles.taxID",array($position["articleID"]));
				}
				else 
				{
					// Bundles tax
					$position["tax"] = Shopware()->Db()->fetchOne("SELECT tax FROM s_core_tax WHERE s_core_tax.id=?",array($position["taxID"]));
				}
				
				if ($this->_net == true){
					$position["netto"] = round($position["price"],2);
					$position["price"] = round($position["price"],2)*(1+$position["tax"]/100);
				}else {
					$position["netto"] = $position["price"] / (100 + $position["tax"]) * 100;
				}
			}
			elseif ($position["modus"]==2)
			{
				$ticketResult = Shopware()->Db()->fetchRow("
				SELECT * FROM s_emarketing_vouchers WHERE ordercode=?
				",array($position["articleordernumber"]));

				if ($ticketResult["taxconfig"] == "default" || empty($ticketResult["taxconfig"])){
					$position["tax"] =  Shopware()->Config()->sVOUCHERTAX;
					// Pre 3.5.4 behaviour
				}elseif ($ticketResult["taxconfig"]=="auto"){
					// Check max. used tax-rate from basket
					$position["tax"] = $this->getMaxTaxRate();
				}elseif (intval($ticketResult["taxconfig"])){
					// Fix defined tax
					$temporaryTax = $ticketResult["taxconfig"];
					$getTaxRate = Shopware()->Db()->fetchOne("
					SELECT tax FROM s_core_tax WHERE id = $temporaryTax
					");
					$position["tax"]  = $getTaxRate["tax"];
				}else {
					$position["tax"]  = 0;
				}
				
				if($this->_net == true)
				{
					$position["netto"] = $position["price"];
					$position["price"] =  $position["price"]*(1+$position["tax"]/100);
				}
				else 
				{
					$position["netto"] =  $position["price"]/(100+$position["tax"])*100;
				}
			}
			elseif ($position["modus"]==1)
			{
				$position["tax"] = 0;
				$position["netto"] = 0;
			}
			
			$position["amount_netto"] = $position["netto"] * $position["quantity"];
			$position["amount"] = $position["price"] * $position["quantity"];
			$this->_amountNetto +=  $position["amount_netto"];
			$this->_amount += $position["amount"];
			
			if($this->_net ==  true)
			{
				$position["price"] = $position["price"];
				$position["netto"] = $position["netto"];
			}
			else 
			{
				$position["netto"] = $position["netto"];
				$position["price"] = $position["price"];
				$position["price"] = round($position["price"],2);
			}
			if(!empty($position["tax"])){
				$this->_tax[$position["tax"]] += ($position["amount"] / ($position["tax"]+100) *$position["tax"]);
			}
			if ($position["amount"] <= 0)
				$this->_discount += $position["amount"];
				
			$position["price"] = $position["price"];
		}
	}

	/**
	 * Get user details
	 * @return void
	 */
	public function getUser(){
		
		$this->_user = new ArrayObject(Shopware()->Db()->fetchRow("
		SELECT * FROM s_user WHERE id = ?
		",array($this->_userID)), ArrayObject::ARRAY_AS_PROPS);
		
		
	}

	/**
	 * Get user billingaddress
	 * @return void
	 */
	public function getBilling(){
		$this->_billing =  new ArrayObject(Shopware()->Db()->fetchRow("
		SELECT sob.*,sub.ustid,sub.customernumber FROM s_order_billingaddress AS sob
		LEFT JOIN s_user_billingaddress AS sub ON sub.userID = ? 
		WHERE sob.userID = ? AND
		sob.orderID = ?
		",array($this->_userID,$this->_userID,$this->_id)), ArrayObject::ARRAY_AS_PROPS);
		$this->_billing["country"] = new ArrayObject(Shopware()->Db()->fetchRow("
		SELECT * FROM s_core_countries
		WHERE id=?
		",array($this->_billing["countryID"])), ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Get user shippingaddress
	 * @return void
	 */
	public function getShipping(){
		$this->_shipping = new ArrayObject(Shopware()->Db()->fetchRow("
		SELECT * FROM s_order_shippingaddress WHERE userID = ? AND
		orderID = ?
		",array($this->_userID,$this->_id)), ArrayObject::ARRAY_AS_PROPS);
		
		if (!$this->_shipping){
			$this->_shipping = clone $this->_billing;
		}else {
			if (empty($this->_shipping["countryID"])){
				$this->_shipping["countryID"] = $this->_billing["countryID"];
			}
			$this->_shipping["country"] =  new ArrayObject(Shopware()->Db()->fetchRow("
			SELECT * FROM s_core_countries
			WHERE id=?
			",array($this->_shipping["countryID"])), ArrayObject::ARRAY_AS_PROPS);
		}
	}

	/**
	 * Get dispatch information
	 * @return void
	 */
	public function getDispatch(){
		if (!empty(Shopware()->Config()->sPREMIUMSHIPPIUNG))
		{
			$dispatch_table = 's_premium_dispatch';
		}
		else
		{
			$dispatch_table = 's_shippingcosts_dispatch';
		}
		$this->_dispatch = Shopware()->Db()->fetchRow("
			SELECT name, description FROM $dispatch_table
			WHERE id = ?
		",array($this->_order["dispatchID"]));
		
		if(empty($this->_dispatch)) {
			$this->_dispatch = array();
		}		
		$this->_dispatch =  new ArrayObject($this->_dispatch, ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Get payment information
	 * @return void
	 */
	public function getPayment(){
		$this->_payment =  new ArrayObject(Shopware()->Db()->fetchRow("
		SELECT * FROM s_core_paymentmeans
		WHERE id=?
		",array($this->_order["paymentID"])), ArrayObject::ARRAY_AS_PROPS);
		if (!empty($this->_payment["table"])){
			$this->_payment["data"] = new ArrayObject(Shopware()->Db()->fetchRow("
			SELECT * FROM ".$this->_payment["table"]." WHERE userID=?",$this->_userID), ArrayObject::ARRAY_AS_PROPS);
		}
	}

	/**
	 * Magic getter
	 * @throws Enlight_Exception
	 * @param  $var_name
	 * @return
	 */
	public function __get($var_name)
	{
		$var_name = "_".$var_name;
		if (property_exists($this,$var_name)){
			return $this->$var_name;
		}else {
			throw new Enlight_Exception("Property $var_name does not exists");
		}
	}	
}
