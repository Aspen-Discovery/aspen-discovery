<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Grouping/GroupedWorkTestSearch.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_GroupedWorkSearchTests extends ObjectEditor
{
	function getObjectType() : string{
		return 'GroupedWorkTestSearch';
	}
	function getToolName() : string{
		return 'GroupedWorkSearchTests';
	}
	function getPageTitle() : string{
		return 'Grouped Work Search Tests';
	}
	function getAllObjects($page, $recordsPerPage) : array{
		$object = new GroupedWorkTestSearch();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort() : string
	{
		return 'searchTerm asc';
	}
	function getObjectStructure() : array {
		return GroupedWorkTestSearch::getObjectStructure();
	}
	function getPrimaryKeyColumn() : string{
		return 'id';
	}
	function getIdKeyColumn() : string{
		return 'id';
	}
	function getInstructions() : string{
		return '';
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/GroupedWorkSearchTests', 'Grouped Work Search Tests');
		return $breadcrumbs;
	}

	function getActiveAdminSection() : string
	{
		return 'cataloging';
	}

	function canView() : bool
	{
		return UserAccount::userHasPermission('Administer Grouped Work Tests');
	}

	function customListActions(){
		return array(
			array('label'=>'Run Tests', 'action'=>'runAllTests'),
		);
	}

	function runAllTests(){
		set_time_limit(0);
		$object = new GroupedWorkTestSearch();
		/** @var GroupedWorkTestSearch[] $allTests */
		$allTests = $object->fetchAll();
		foreach ($allTests as $test){
			$test->runTest();
		}

		$structure = $this->getObjectStructure();
		$structure = $this->applyPermissionsToObjectStructure($structure);
		$this->viewExistingObjects($structure);
	}

	function getAdditionalObjectActions($existingObject) : array{
		$objectActions = array();
		if (isset($existingObject) && $existingObject != null){
			$objectActions[] = array(
				'text' => 'Run Test',
				'url' => '/Admin/GroupedWorkSearchTests?objectAction=runTest&id=' . $existingObject->id,
			);
		}
		return $objectActions;
	}

	function runTest(){
		set_time_limit(0);
		$searchTest = new GroupedWorkTestSearch();
		$searchTest->id = $_REQUEST['id'];
		if ($searchTest->find(true)){
			$searchTest->runTest();
		}else{
			AspenError::raiseError("Could not find a test with that id");
		}

		$structure = $this->getObjectStructure();
		$structure = $this->applyPermissionsToObjectStructure($structure);
		$this->viewIndividualObject($structure);
	}
}