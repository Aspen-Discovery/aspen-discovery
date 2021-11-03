<?php

require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';
class Greenhouse_ExternalRequestLog extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'ExternalRequestLogEntry';
	}

	function getToolName() : string
	{
		return 'ExternalRequestLog';
	}

	function getModule() : string
	{
		return 'Greenhouse';
	}

	function getPageTitle() : string
	{
		return 'External Request Log';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new ExternalRequestLogEntry();
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
		return 'requestTime desc';
	}

	function getObjectStructure() : array
	{
		return ExternalRequestLogEntry::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function canAddNew()
	{
		return false;
	}

	function canDelete()
	{
		return false;
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
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/ExternalRequestLog', 'External Request Log');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source = 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}

	protected function getDefaultRecordsPerPage()
	{
		return 100;
	}
}