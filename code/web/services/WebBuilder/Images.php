<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/File/ImageUpload.php';

class WebBuilder_Images extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'ImageUpload';
	}

	function getToolName() : string
	{
		return 'Images';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'Uploaded Images';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new ImageUpload();
		$object->type = 'web_builder_image';
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
	function getDefaultSort() : string
	{
		return 'title asc';
	}

	function updateFromUI($object, $structure){
		$object->type = 'web_builder_image';
		return parent::updateFromUI($object, $structure);
	}

	function getObjectStructure() : array
	{
		$objectStructure = ImageUpload::getObjectStructure();
		unset($objectStructure['type']);
		return $objectStructure;
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	/**
	 * @param FileUpload $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject) : array
	{
		$objectActions = [];
		if (!empty($existingObject) && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View Image',
				'url' => '/WebBuilder/ViewImage?id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getInstructions() : string
	{
		return 'https://help.aspendiscovery.org/help/webbuilder/imagespdfs';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Images', 'Images');
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Web Content']);
	}

	function getActiveAdminSection() : string
	{
		return 'web_builder';
	}
}