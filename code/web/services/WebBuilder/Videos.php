<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/File/FileUpload.php';

class WebBuilder_Videos extends ObjectEditor
{
	function getObjectType()
	{
		return 'FileUpload';
	}

	function getToolName()
	{
		return 'Videos';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Uploaded Videos';
	}

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new FileUpload();
		$object->type = 'web_builder_video';
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort()
	{
		return 'title asc';
	}

	function updateFromUI($object, $structure){
		$object->type = 'web_builder_video';
		return parent::updateFromUI($object, $structure);
	}

	function getObjectStructure()
	{
		$objectStructure = FileUpload::getObjectStructure();
		unset($objectStructure['type']);
		$fileProperty = $objectStructure['fullPath'];
		global $serverName;
		$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_video/';
		$fileProperty['path'] = $dataPath;
		$fileProperty['validTypes'] = ['video/mp4'];
		$objectStructure['fullPath'] = $fileProperty;
		return $objectStructure;
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	/**
	 * @param FileUpload $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject)
	{
		$objectActions = [];
		if (!empty($existingObject) && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'Watch Video',
				'url' => '/Files/' . $existingObject->id . '/WatchVideo',
			];
		}
		return $objectActions;
	}

	function getInstructions()
	{
		return '';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Videos', 'Videos');
		return $breadcrumbs;
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Web Content']);
	}

	function getActiveAdminSection()
	{
		return 'web_builder';
	}
}