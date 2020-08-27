<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/IP/IPAddress.php';

class Admin_IPAddresses extends ObjectEditor
{
	function getObjectType(){
		return 'IPAddress';
	}
	function getToolName(){
		return 'IPAddresses';
	}
	function getPageTitle(){
		return 'Location IP Addresses';
	}
	function getAllObjects(){
		$object = new IPAddress();
		$object->orderBy('ip');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return IPAddress::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'ip';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function getInstructions()
	{
		return '/Admin/HelpManual?page=Location-IP-Addresses';
	}
	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/IPAddresses', 'IP Addresses');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}
}