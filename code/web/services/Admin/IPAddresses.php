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
	function getAllObjects($page, $recordsPerPage){
		$object = new IPAddress();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'ip asc';
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

	function canView()
	{
		return UserAccount::userHasPermission('Administer IP Addresses');
	}
}