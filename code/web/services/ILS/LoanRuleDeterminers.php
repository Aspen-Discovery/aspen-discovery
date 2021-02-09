<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LoanRuleDeterminer.php';

class LoanRuleDeterminers extends ObjectEditor {
	function launch(){
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'reloadFromCsv'){
			$this->display('../ILS/importLoanRuleDeterminerData.tpl', "Reload Loan Rule Determiners");
			exit();
		}elseif($objectAction == 'doLoanRuleDeterminerReload'){
			$loanRuleDeterminerData = $_REQUEST['loanRuleDeterminerData'];
			//Truncate the current data
			$loanRuleDeterminer = new LoanRuleDeterminer();
			$loanRuleDeterminer->query("TRUNCATE table " . $loanRuleDeterminer->__table);

			//Parse the new data
			$data = preg_split('/\\r\\n|\\r|\\n/', $loanRuleDeterminerData);
			foreach ($data as $dataRow){
				$dataFields = preg_split('/\\t/', $dataRow);
				$loanRuleDeterminerNew = new LoanRuleDeterminer();
				$loanRuleDeterminerNew->rowNumber = trim($dataFields[0]);
				$loanRuleDeterminerNew->location = trim($dataFields[1]);
				$loanRuleDeterminerNew->patronType = trim($dataFields[2]);
				$loanRuleDeterminerNew->itemType = trim($dataFields[3]);
				$loanRuleDeterminerNew->ageRange = trim($dataFields[4]);
				$loanRuleDeterminerNew->loanRuleId = trim($dataFields[5]);
				$loanRuleDeterminerNew->active = strcasecmp(trim($dataFields[6]), 'y') == 0;
				$loanRuleDeterminerNew->insert();
			}

			//Show the results
			$_REQUEST['objectAction'] = 'list';
		}
		parent::launch();
	}
	function getObjectType(){
		return 'LoanRuleDeterminer';
	}
	function getToolName(){
		return 'LoanRuleDeterminers';
	}
	function getPageTitle(){
		return 'Loan Rule Determiners';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new LoanRuleDeterminer();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->rowNumber] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'rowNumber asc';
	}
	function getObjectStructure(){
		return LoanRuleDeterminer::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'rowNumber';
	}
	function customListActions(){
		$actions = array();
		$actions[] = array(
			'label' => 'Reload From CSV',
			'action' => 'reloadFromCsv',
		);
		return $actions;
	}
	public function canAddNew(){
		return false;
	}
	public function canCompare()
	{
		return false;
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/LoanRuleDeterminers', 'Loan Rule Determiners');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'ils_integration';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer Loan Rules');
	}
}