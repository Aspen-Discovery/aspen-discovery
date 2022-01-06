<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
require_once ROOT_DIR . '/sys/WebBuilder/CustomFormSubmission.php';

class WebBuilder_CustomFormSubmissions extends ObjectEditor
{
	function getObjectType() : string
	{
		return 'CustomFormSubmission';
	}

	function getToolName() : string
	{
		return 'CustomFormSubmissions';
	}

	function getModule() : string
	{
		return 'WebBuilder';
	}

	function getPageTitle() : string
	{
		return 'Form Submissions';
	}

	function getAllObjects($page, $recordsPerPage) : array
	{
		$object = new CustomFormSubmission();
		$formId = $_REQUEST['formId'];
		$this->applyFilters($object);
		$object->formId = $formId;
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
		return 'dateSubmitted desc';
	}

	function getObjectStructure() : array
	{
		return CustomFormSubmission::getObjectStructure();
	}

	function getPrimaryKeyColumn() : string
	{
		return 'id';
	}

	function getIdKeyColumn() : string
	{
		return 'id';
	}

	function canEdit(DataObject $object){
		return false;
	}

	function getAdditionalObjectActions($existingObject) : array
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

	function getInstructions() : string
	{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		if (!empty($this->activeObject) && $this->activeObject instanceof CustomFormSubmission){
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomForms?id=' . $this->activeObject->formId, 'Form');
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomFormSubmissions?formId='.  $this->activeObject->formId, 'All Form Submissions');
		}
		return $breadcrumbs;
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission(['Administer All Custom Forms', 'Administer Library Custom Forms']);
	}

	function getActiveAdminSection() : string
	{
		return 'web_builder';
	}
}