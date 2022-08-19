<?php

require_once ROOT_DIR . '/sys/Account/UserNotification.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class AspenLiDA_NotificationsReport extends ObjectEditor
{
	function getObjectType(): string
	{
		return 'UserNotification';
	}
	function getToolName() : string{
		return 'NotificationsReport';
	}
	function getPageTitle() : string{
		return 'Notifications Report';
	}

	function getAllObjects($page, $recordsPerPage) : array{
		$object = new UserNotification();
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
		return UserNotification::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/Admin/AspenLiDA/NotificationsReport', 'Notifications Report');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'aspen_lida';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('View Notifications Reports');
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