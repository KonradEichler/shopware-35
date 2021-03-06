<?php
/**
 * Shopware Plugin Bootstrap
 * 
 * @link http://www.shopware.de
 * @copyright Copyright (c) 2011, shopware AG
 * @author Heiner Lohaus
 * @package Shopware
 * @subpackage Plugins
 */
abstract class Shopware_Components_Plugin_Bootstrap extends Enlight_Plugin_Bootstrap
{
	/**
	 * Array with properties of plugin
	 * @var array
	 */
	protected $capabilities;

	/**
	 * Save path to widget configuration file
	 * @var string
	 */
	protected $widgetXML;
	
	/**
	 * Constructor method
	 *
	 * @param Enlight_Plugin_Namespace $namespace
	 * @param string $name
	 */
	public function __construct(Enlight_Plugin_Namespace $namespace, $name)
	{
		parent::__construct($namespace, $name);
		$this->capabilities = $this->getCapabilities();
		$this->widgetXML = Shopware()->DocPath()."/files/config/Widgets.xml";
	}
	
	/**
	 * Install plugin method
	 *
	 * @return bool
	 */
	public function install()
	{
		return !empty($this->capabilities['install']);
	}
	
	/**
	 * Uninstall plugin method
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		if(empty($this->capabilities['uninstall'])) {
			return false;
		}
		
		$this->unsubscribeHooks();
		$this->unsubscribeEvents();
		$this->deleteForm();
		$this->deleteConfig();
		$this->deleteMenuItems();
		$this->unsubscribeCron();

		$this->removeWidget();

		
		return true;
	}

	/**
	 * Check if a list of given plugins is currently available
	 * and active
	 * @param array $plugins
	 * @return bool
	 */
	public function assertRequiredPluginsPresent(array $plugins){
		foreach ($plugins as $plugin){
			if (!Shopware()->Db()->fetchOne("
			SELECT id FROM s_core_plugins WHERE name = ? AND active = 1
			",array($plugin))){
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if a given version is greater or equal to the currently installed version
	 * @param  $requiredVersion Format: 3.5.4 or 3.5.4.21111
	 * @return bool
	 */
	public function assertVersionGreaterThen($requiredVersion){
		$installedVersion = Shopware()->Config()->Version;
		$installedVersion = explode(".",$installedVersion);
		$requiredVersion = explode(".",$requiredVersion);

		$majorInstalled = $installedVersion[0];
		$minorInstalled = $installedVersion[1];
		$bugfixInstalled = $installedVersion[2];
		if (!empty($installedVersion[3])){
			$revisionInstalled = $installedVersion[3];
		}else {
			$revisionInstalled = 0;
		}

		if ($majorInstalled >= $requiredVersion[0] &&
			$minorInstalled >= $requiredVersion[1] &&
			$bugfixInstalled >= $requiredVersion[2] &&
			$revisionInstalled >= $requiredVersion[3]
		){
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * Remove a widget during plugin uninstall
	 * @return void
	 */
	public function removeWidget()
	{
		if (is_file($this->widgetXML)){
			$xml = new Shopware_Components_Xml_SimpleXml();
			$xml->loadFile($this->widgetXML);
			$xpath = '//Widget[@object="'.get_class($this).'"]';
			$xml->SimpleXML->removeNodes($xpath);
			$xml->setFilename($this->widgetXML);
			$xml->save();
		}
	}

	/**
	 * Update plugin method
	 *
	 * @return bool
	 */
	public function update()
	{
		if(empty($this->capabilities['update'])||empty($this->capabilities['install'])) {
			return false;
		}
		
		$this->uninstall();
		$this->install();
		
		return true;
	}
	
	/**
	 * Enable plugin method
	 *
	 * @return bool
	 */
	public function enable()
	{
		if(empty($this->capabilities['enable'])) {
			return false;
		}
		
		$sql = 'UPDATE `s_core_plugins` SET `active`=1 WHERE `id`=?';
		Shopware()->Db()->query($sql, $this->getId());
		
		return true;
	}
	
	/**
	 * Disable plugin method
	 *
	 * @return bool
	 */
	public function disable()
	{
		if(empty($this->capabilities['disable'])) {
			return false;
		}
		
		$sql = 'UPDATE `s_core_plugins` SET `active`=0 WHERE `id`=?';
		Shopware()->Db()->query($sql, $this->getId());
		
		return true;
	}
	
	/**
	 * Returns plugin config
	 *
	 * @return Shopware_Models_Plugin_Config
	 */
	public function Config()
	{
		return $this->namespace->getConfig($this->name);
	}
	
	/**
	 * Returns plugin form
	 *
	 * @return Shopware_Components_Form
	 */
	public function Form()
	{
		$form = new Shopware_Components_Form();
		$form->setId($this->getId());
		$saveHandler = new Shopware_Components_Form_SaveHandler_DbTable();
		$form->setSaveHandler($saveHandler);
		$form->load();
		return $form;
	}
	
	/**
	 * Returns shopware menu
	 *
	 * @return Shopware_Components_Menu
	 */
	public function Menu()
	{
		return Shopware()->Menu();
	}
			
	/**
	 * Enter description here...
	 *
	 * @return Enlight_Event_EventHandler
	 */
	public function createEvent($event, $listener, $position=null)
	{
		$event = new Enlight_Event_EventHandler(
	 		$event,
	 		get_class($this).'::'.$listener,
	 		$position,
	 		$this->getId()
	 	);
	 	return $event;
	}

	/**
	 * Create a new widget for display in backend start panel
	 * 
	 * @param  $name
	 * @param  $label
	 * @param  $configuration
	 * @param  $template
	 * @param  $viewDirectory
	 * @param  $extender
	 * @return bool
	 */
	public function createWidget($name, $label, $configuration, $template, $viewDirectory){
		
		if (!$this->widgetXML){
			throw new Enlight_Exception("\$this->widgetXML is null");
		}
		if (!$name){
			throw new Enlight_Exception("Empty widget name given");
		}

		$xml = new Shopware_Components_Xml_SimpleXml();

		if (!is_file($this->widgetXML)){
			$xml->setNamespace('Widgets')->create();
		}else {
			$xml->loadFile($this->widgetXML);
			if ($xml->attributeExists('Widgets','name',$name)==true){
				throw new Exception("Widget with name $name already exists");
			}
		}
		$temp["Widget"]["@attributes"] = array("name"=>$name,"object"=>get_class($this));
		$temp["Widget"]["label"] =  $label;
		$temp["Widget"]["template"] = $template;
		//$temp["Widget"]["extender"] = $extender;
		$temp["Widget"]["views"] = str_replace(Shopware()->DocPath(),'',$viewDirectory);
		$temp["Widget"]["configuration"] = $configuration;

		$xml->set($xml->getXmlAtNode('Widgets'),$temp);
		$xml->setFilename($this->widgetXML);
		$xml->save();

		return true;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return Enlight_Core_Hook_HookHandler
	 */
	public function createHook($class, $method, $listener, $type=null, $position=null)
	{
		$hook = new Enlight_Hook_HookHandler(
    		$class,
    		$method,
    		get_class($this).'::'.$listener,
    		$type,
    		$position,
	 		$this->getId()
    	);
		return $hook;
	}
	
	/**
	 * Create a new menu item instance
	 *
	 * @return Shopware_Components_Menu_Item
	 */
	public function createMenuItem($options)
	{
		$options['pluginID'] = $this->getId();
		return Shopware_Components_Menu_Item::factory($options);
	}
	
	/**
	 * Create a new payment instance
	 *
	 * @return Enlight_Components_Table_Row
	 */
	public function createPayment($name, $description, $action=null)
	{
		return Shopware()->Payments()->createRow(array(
			'name' => $name,
			'description' => $description,
			'action' => $action,
			'active' => 1,
			'pluginID' => $this->getId()
		));
	}
	
	/**
	 * Subscribe cron method
	 *
	 * @param Shopware_Components_Cron_CronHandler $handler
	 * @return Shopware_Components_Plugin_Bootstrap
	 */
	public function subscribeCron($name, $action, $interval=86400, $active=1, $next=null, $start=null, $end=null)
	{
		if (empty($next)) {
			$next = date('Y-m-d H:i:s', time());
		}
		if (empty($start)) {
			$start = date('Y-m-d H:i:s', time()-86400);
		}
		if (empty($end)) {
			$end = date('Y-m-d H:i:s', time()-86400);
		}
		$sql = '
			INSERT INTO s_crontab (`name`, `action`, `next`, `start`, `interval`, `active`, `end`, `pluginID`)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?)
		';
		Shopware()->Db()->query($sql, array(
			$name, $action, $next, $start, $interval, $active, $end, $this->getId()
		));
		
		/*
		if(!$handler instanceof Shopware_Components_Cron_CronHandler) {
			$reflection = new ReflectionClass('Shopware_Components_Cron_CronHandler');
			$handler = $reflection->newInstanceArgs(func_get_args());
		}
		if(!$handler->getPlugin()) {
			$handler->setPlugin($this->getId());
		}
		Shopware()->Cron()->addCronJob($handler);
		*/
		return $this;
	}
	
	/**
	 * Subscribe cron method
	 *
	 * @param Enlight_Event_EventHandler $handler
	 * @return Shopware_Components_Plugin_Bootstrap
	 */
	public function subscribeEvent(Enlight_Event_EventHandler $handler)
	{
		Shopware()->Subscriber()->subscribeEvent($handler);
		
		return $this;
	}
	
	/**
	 * Subscribe hook method
	 *
	 * @param Enlight_Hook_HookHandler $handler
	 * @return Shopware_Components_Plugin_Bootstrap
	 */
	public function subscribeHook(Enlight_Hook_HookHandler $handler)
	{
		Shopware()->Subscriber()->subscribeHook($handler);
		
		return $this;
	}
	
	/**
	 * Unsubscribe cron method
	 */
	public function unsubscribeCron()
	{
		$sql = 'DELETE FROM `s_crontab` WHERE `pluginID`=?';
		Shopware()->Db()->query($sql, $this->getId());
		
		return $this;
	}
		
	/**
	 * Unsubscribe hooks
	 */
	public function unsubscribeHooks()
	{
		$pluginId = $this->getId();
		if (!empty($pluginId)){
			Shopware()->Subscriber()->unsubscribeHooks(array('pluginID'=>$this->getId()));
		}
		return $this;
	}
	
	/**
	 * Unsubscribe events
	 */
	public function unsubscribeEvents()
	{
		$pluginId = $this->getId();
		if (!empty($pluginId)){
			Shopware()->Subscriber()->unsubscribeEvents(array('pluginID'=>$this->getId()));
		}

		return $this;
	}
	
	/**
	 * Delete plugin form
	 */
	public function deleteForm()
	{
		$sql = 'DELETE FROM `s_core_plugin_elements` WHERE `pluginID`=?';
		Shopware()->Db()->query($sql, $this->getId());
		
		return $this;
	}
	
	/**
	 * Delete plugin config
	 */
	public function deleteConfig()
	{
		$sql = 'DELETE FROM `s_core_plugin_configs` WHERE `pluginID`=?';
		Shopware()->Db()->query($sql, $this->getId());
		
		return $this;
	}
	
	/**
	 * Delete menu items
	 */
	public function deleteMenuItems()
	{
		$sql = 'DELETE FROM `s_core_menu` WHERE `pluginID`=?';
		Shopware()->Db()->query($sql, $this->getId());
		
		return $this;
	}
	
	/**
	 * Returns capabilities
	 */
	public function getCapabilities()
    {
        return array(
    		'install' => true,
    		'uninstall' => true,
    		'update' => true,
    		'enable' => true,
    		'disable' => true,
    	);
    }
    
    /**
	 * Returns plugin id
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->namespace->getPluginId($this->name);
	}
    
	/**
	 * Returns plugin version
	 *
	 * @return string
	 */
    public function getVersion()
    {
        return '1.0.0';
    }
    
    /**
	 * Returns plugin name
	 *
	 * @return string
	 */
    public function getName()
    {
    	return $this->name;
    }
    
    /**
	 * Returns plugin source
	 *
	 * @return string
	 */
    public function getSource()
    {
    	return $this->namespace->getSource($this->name);
    }
    
    /**
	 * Returns plugin info
	 *
	 * @return array
	 */
    public function getInfo()
    {
    	return array(
    		'version' => $this->getVersion(),
			'autor' => 'shopware AG',
			'copyright' => 'Copyright � 2011, shopware AG',
			'label' => $this->getName(),
			'source' => $this->getSource(),
			'description' => '',
			'license' => '',
			'support' => 'http://wiki.shopware.de',
			'link' => 'http://www.shopware.de/'
    	);
    }
}