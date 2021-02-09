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

	function getAllObjects($page, $recordsPerPage)
	{
		$object = new CustomForm();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
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
				'url' => '/WebBuilder/CustomFormSubmissions?formId='.$existingObject->id,
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
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/CustomForms', 'Custom Forms');
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