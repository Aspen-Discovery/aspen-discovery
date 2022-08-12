<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ILL/DPLASetting.php';

class ILL_VDXSettings extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'DPLASetting';
	}

	function getToolName() : string
	{
		return 'DPLASettings';
	}

	function getModule() : string
	{
		return 'Enrichment';
	}

	function getPageTitle() : string
	{
		return 'DP.LA Settings';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new DPLASetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'id asc';
	}

	function getObjectStructure() : array
	{
		return DPLASetting::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function getAdditionalObjectActions($existingObject) : array
	{
		return [];
	}

	function getInstructions() : string
	{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ill_integration', 'Interlibrary Loan');
		$breadcrumbs[] = new Breadcrumb('/ILL/VDXSettings', 'VDX Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ill_integration';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer VDX Settings');
	}

	function canAddNew()
	{
		return $this->getNumObjects() == 0;
	}
}