<?php
/**
 * Shopware Menu Plugin
 * 
 * @link http://www.shopware.de
 * @copyright Copyright (c) 2011, shopware AG
 * @package Shopware
 * @subpackage Plugins
 */
class Shopware_Plugins_Backend_Menu_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
	/**
	 * Install plugin method
	 *
	 * @return bool
	 */
	public function install()
	{
		$event = $this->createEvent(
	 		'Enlight_Bootstrap_InitResource_Menu',
	 		'onInitResourceMenu'
	 	);
		$this->subscribeEvent($event);
		return true;
	}
	
	/**
	 * Event listener method
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public static function onInitResourceMenu(Enlight_Event_EventArgs $args)
	{
		$menu = new Shopware_Components_Menu();
		
		$saveHandler = new Shopware_Components_Menu_SaveHandler_DbTable();
		$menu->setSaveHandler($saveHandler);
		
		$menu->load();
		
		$identity = Shopware()->Auth()->getIdentity();
		$acl = Shopware()->Acl();
			
		$iterator = new RecursiveIteratorIterator($menu, RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($iterator as $page) {
        	if($page->getResource() === null && preg_match('#\'(.+?)\'#', $page->onclick, $match)) {
        		if(!$acl->has($match[1])) {
        			$acl->addResource($match[1]);
        		}
        		$page->setResource($match[1]);
        		if($page->getPrivilege() === null) {
	        		$page->setPrivilege(strpos($page->onclick, 'deleteCache') === 0 ? 'cache' : 'view');
	        	}
        	}
        	if($page->getResource() !== null || $page->getPrivilege() !== null) {
        		$page->setVisible($acl->isAllowed($identity->role, $page->getResource(), $page->getPrivilege()));
        	}
        }
        
        //Hide empty parent container
        foreach ($iterator as $container) {
        	if($container->hasPages() && !$container->getResource()) {
        		foreach ($container->getPages() as $page) {
		    		if($page->isVisible()) {
		    			continue 2;
		    		}
		    	}
        		$container->setVisible(false);
        	}
        }
        	
        return $menu;
	}
}