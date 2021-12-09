<?php

require_once ROOT_DIR . '/sys/Donations/Donation.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_DonationsReport extends ObjectEditor
{
	function getObjectType(): string
	{
		return 'Donation';
	}
	function getToolName() : string{
		return 'DonationsReport';
	}
	function getPageTitle() : string{
		return 'Donations Report';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new Donation();
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
		return 'id desc';
	}

	function getObjectStructure() : array {
		return Donation::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/donationsReport', 'donations Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ecommerce';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('View Donations Reports');
	}

	function canBatchEdit()
	{
		return false;
	}

	function canCompare()
	{
		return false;
	}

	public function canEdit(DataObject $object){
		return false;
	}
}