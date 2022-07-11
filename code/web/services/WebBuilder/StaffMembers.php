<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/StaffMember.php';

class WebBuilder_StaffMembers extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'StaffMember';
	}

	function getToolName() : string
	{
		return 'StaffMembers';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'Staff Members';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new StaffMember();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Staff Members')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$object->libraryId = $library->libraryId;
		}
		$objectList = array();
		$object->find();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort() : string
	{
		return 'name asc';
	}

	function getObjectStructure() : array
	{
		return StaffMember::getObjectStructure();
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
		return 'https://help.aspendiscovery.org/help/webbuilder/staff';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/StaffMembers', 'Staff Members');
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Staff Members', 'Administer Library Staff Members']);
	}

	function getActiveAdminSection() : string
	{
		return 'web_builder';
	}
}