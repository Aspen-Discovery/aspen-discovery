<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/CurbsidePickups/CurbsidePickupSetting.php';

class CurbsidePickup_Settings extends ObjectEditor
{
	function getObjectType() : string {
		return 'CurbsidePickupSetting';
	}

	function getModule() : string
	{
		return "CurbsidePickup";
	}

	function getToolName() : string {
		return 'Settings';
	}

	function getPageTitle() : string {
		return 'Curbside Pickup Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array {
		$list = array();

		$object = new CurbsidePickupSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort() : string {
		return 'name asc';
	}

	function getObjectStructure() : array
	{
		return CurbsidePickupSetting::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/CurbsidePickup/Settings', 'Curbside Pickup Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ils_integration';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Curbside Pickup');
	}
}