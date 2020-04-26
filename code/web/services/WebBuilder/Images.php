<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/File/ImageUpload.php';

class WebBuilder_Images extends ObjectEditor
{
	function getObjectType()
	{
		return 'ImageUpload';
	}

	function getToolName()
	{
		return 'Images';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Uploaded Images';
	}

	function getAllObjects()
	{
		$object = new ImageUpload();
		$object->type = 'web_builder_image';
		$object->orderBy('title');
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function updateFromUI($object, $structure){
		$object->type = 'web_builder_image';
		return parent::updateFromUI($object, $structure);
	}

	function getObjectStructure()
	{
		$objectStructure = ImageUpload::getObjectStructure();
		unset($objectStructure['type']);
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

	function getAllowableRoles()
	{
		return array('opacAdmin', 'web_builder_admin', 'web_builder_creator');
	}

	function canAddNew()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('web_builder_admin') || UserAccount::userHasRole('web_builder_creator');
	}

	function canDelete()
	{
		return UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('web_builder_admin');
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
				'text' => 'View Image',
				'url' => '/WebBuilder/ViewImage?id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getInstructions()
	{
		return '';
	}
}