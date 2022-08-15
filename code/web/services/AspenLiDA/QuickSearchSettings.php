<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/AspenLiDA/QuickSearchSetting.php';

class AspenLiDA_QuickSearchSettings extends ObjectEditor {
	function getObjectType() : string{
		return 'QuickSearchSetting';
	}

	function getToolName() : string{
		return 'QuickSearchSettings';
	}

	function getModule() : string{
		return 'AspenLiDA';
	}

	function getPageTitle() : string{
		return 'Quick Search Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array{
		$list = array();

		$object = new QuickSearchSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getDefaultSort() : string
	{
		return 'id asc';
	}

	function getObjectStructure() : array{
		return QuickSearchSetting::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function getInstructions() : string{
		return '';
	}
	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#aspen_lida', 'Aspen LiDA');
		$breadcrumbs[] = new Breadcrumb('/AspenLiDA/QuickSearchSettings', 'Quick Search Settings');
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