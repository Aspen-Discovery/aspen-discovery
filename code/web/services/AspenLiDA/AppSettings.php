<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/AppSetting.php';

class AspenLiDA_AppSettings extends ObjectEditor
{
	function getObjectType() : string {
		return 'AppSetting';
	}

	function getToolName() : string {
		return 'AppSettings';
	}

	function getModule() : string{
		return 'AspenLiDA';
	}

	function getPageTitle() : string {
		return 'App Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array {
		$list = array();

		$object = new AppSetting();
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
		return AppSetting::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/AppSettings', 'App Settings');
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