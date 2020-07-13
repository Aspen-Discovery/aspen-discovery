<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';

class WebBuilder_CustomForms extends ObjectEditor
{
	function getObjectType()
	{
		return 'CustomForm';
	}

	function getToolName()
	{
		return 'CustomForms';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Custom WebBuilder Forms';
	}

	function getAllObjects()
	{
		$object = new CustomForm();
		$object->orderBy('title');
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return CustomForm::getObjectStructure();
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

	function getAdditionalObjectActions($existingObject)
	{
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof CustomForm && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/Form?id='.$existingObject->id: $existingObject->urlAlias,
			];
			$objectActions[] = [
				'text' => 'View Submissions',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/CustomFormSubmissions?formId='.$existingObject->id: $existingObject->urlAlias,
			];
		}
		return $objectActions;
	}

	function getInstructions()
	{
		return '';
	}
}