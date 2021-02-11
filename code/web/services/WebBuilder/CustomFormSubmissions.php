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

	function getAllObjects($page, $recordsPerPage)
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
	function getDefaultSort()
	{
		return 'dateSubmitted desc';
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

	function getBreadcrumbs()
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

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Custom Forms', 'Administer Library Custom Forms']);
	}

	function getActiveAdminSection()
	{
		return 'web_builder';
	}
}