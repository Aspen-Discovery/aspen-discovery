<?php

require_once ROOT_DIR . '/sys/Account/UserPayment.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_eCommerceReport extends ObjectEditor
{
	function getObjectType(): string
	{
		return 'UserPayment';
	}
	function getToolName() : string{
		return 'eCommerceReport';
	}
	function getPageTitle() : string{
		return 'eCommerce Report';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new UserPayment();
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
	function getDefaultSort() : string
	{
		return 'transactionDate desc';
	}

	function getObjectStructure() : array {
		return UserPayment::getObjectStructure();
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return false;
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ecommerce', 'eCommerce');
		$breadcrumbs[] = new Breadcrumb('/Admin/eCommerceReport', 'eCommerce Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ecommerce';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('View eCommerce Reports');
	}
}