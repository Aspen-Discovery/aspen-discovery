<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomFormSubmission.php';

class WebBuilder_CustomFormSubmissions extends ObjectEditor
{
	function getObjectType()
	{
		return 'CustomFormSubmission';
	}

	function getToolName()
	{
		return 'CustomFormSubmissions';
	}

	function getModule()
	{
		return 'WebBuilder';
	}

	function getPageTitle()
	{
		return 'Form Submissions';
	}

	function getAllObjects()
	{
		$object = new CustomFormSubmission();
		$formId = $_REQUEST['formId'];
		$object->formId = $formId;
		$object->orderBy('dateSubmitted desc');
		$object->find();
		$objectList = array();
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getObjectStructure()
	{
		return CustomFormSubmission::getObjectStructure();
	}

	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	function getIdKeyColumn()
	{
		return 'id';
	}

	function canEdit(){
		return false;
	}

	function getAdditionalObjectActions($existingObject)
	{
		$objectActions = [];
		if (!empty($existingObject) && $existingObject instanceof CustomFormSubmission && !empty($existingObject->id)){
			$objectActions[] = [
				'text' => 'View Form',
				'url' => empty($existingObject->urlAlias) ? '/WebBuilder/Form?id='.$existingObject->formId: $existingObject->urlAlias,
			];
			$objectActions[] = [
				'text' => 'Edit Form',
				'url' => '/WebBuilder/CustomForms?objectAction=edit&id='.$existingObject->formId,
			];
		}
		return $objectActions;
	}

	function getInstructions()
	{
		return '';
	}
}