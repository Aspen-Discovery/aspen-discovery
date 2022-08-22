<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/VDX/VdxHoldGroup.php';

class VDX_VDXHoldGroups extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'VdxHoldGroup';
	}

	function getToolName() : string
	{
		return 'VDXHoldGroups';
	}

	function getModule() : string
	{
		return 'VDX';
	}

	function getPageTitle() : string
	{
		return 'VDX Hold Groups';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new VdxHoldGroup();
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
		return VdxHoldGroup::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/VDX/VDXHoldGroups', 'VDX Hold Groups');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'ill_integration';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer VDX Hold Groups');
	}
}