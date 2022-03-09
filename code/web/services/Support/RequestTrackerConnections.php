<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
class RequestTrackerConnections extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'RequestTrackerConnection';
	}

	function getToolName() : string
	{
		return 'RequestTrackerConnections';
	}

	function getModule() : string
	{
		return 'Support';
	}

	function getPageTitle() : string
	{
		return 'Request Tracker Connections';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new RequestTrackerConnection();
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
		return RequestTrackerConnection::getObjectStructure();
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#support', 'Support');
		$breadcrumbs[] = new Breadcrumb('/Support/RequestTrackerConnection', 'Request Tracker Connection');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'support';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Request Tracker Connection');
	}

	function canAddNew()
	{
		return $this->getNumObjects() <= 0;
	}
}