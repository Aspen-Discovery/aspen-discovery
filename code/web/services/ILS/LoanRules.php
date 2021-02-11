<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LoanRule.php';

class LoanRules extends ObjectEditor
{
	function launch(){
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'reloadFromCsv'){
			$this->display('../ILS/importLoanRuleData.tpl', "Reload Loan Rules");
			exit();
		}elseif($objectAction == 'doLoanRuleReload'){
			$loanRuleData = $_REQUEST['loanRuleData'];
			//Truncate the current data
			$loanRule = new LoanRule();
			$loanRule->query("TRUNCATE table " . $loanRule->__table);

			//Parse the new data
			$data = preg_split('/\\r\\n|\\r|\\n/', $loanRuleData);
			foreach ($data as $dataRow){
				if (strpos($dataRow, "\t") > 0){
					$dataFields = preg_split('/\\t/', $dataRow);
					$loanRuleNew = new LoanRule();
					$loanRuleNew->loanRuleId = $dataFields[0];
					$loanRuleNew->name = trim($dataFields[1]);
					$loanRuleNew->code = trim($dataFields[2]);
					$loanRuleNew->normalLoanPeriod = trim($dataFields[3]);
					$loanRuleNew->holdable = strcasecmp(trim($dataFields[4]), 'y') === 0;
					$loanRuleNew->bookable = strcasecmp(trim($dataFields[5]), 'y') === 0;
					$loanRuleNew->homePickup = strcasecmp(trim($dataFields[6]), 'y') === 0;
					$loanRuleNew->shippable = strcasecmp(trim($dataFields[7]), 'y') === 0 ;
					$loanRuleNew->insert();
				}
			}

			//Show the results
			$_REQUEST['objectAction'] = 'list';
		}
		parent::launch();
	}
	function getObjectType(){
		return 'LoanRule';
	}
	function getToolName(){
		return 'LoanRules';
	}
	function getPageTitle(){
		return 'Loan Rules';
	}
	function getAllObjects($page, $recordsPerPage){
		$object = new LoanRule();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->loanRuleId] = clone $object;
		}
		return $objectList;
	}
	function getDefaultSort()
	{
		return 'loanRuleId asc';
	}
	function getObjectStructure(){
		return LoanRule::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'loanRuleId';
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
		$breadcrumbs[] = new Breadcrumb('/ILS/LoanRules', 'Loan Rules');
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