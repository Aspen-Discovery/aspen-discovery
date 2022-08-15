<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php';

class AspenLiDA_NotificationSettings extends ObjectEditor
{
	function getObjectType() : string {
		return 'NotificationSetting';
	}

	function getToolName() : string {
		return 'NotificationSettings';
	}

	function getModule() : string{
		return 'AspenLiDA';
	}

	function getPageTitle() : string {
		return 'Notification Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array {
		$list = array();

		$object = new NotificationSetting();
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
		return 'id asc';
	}

	function getObjectStructure() : array
	{
		return NotificationSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/NotificationSettings', 'Notification Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'aspen_lida';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Aspen LiDA Settings');
	}

}