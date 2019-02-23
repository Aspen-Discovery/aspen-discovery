<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/LoanRule.php';

class LoanRules extends ObjectEditor
{
	function launch(){
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'reloadFromCsv'){
			global $interface;
			$interface->setTemplate('../Admin/importLoanRuleData.tpl');
			$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
			$interface->setPageTitle("Reload Loan Rules");
			$interface->display('layout.tpl');
			exit();
		}elseif($objectAction == 'doLoanRuleReload'){
			$loanRuleData = $_REQUEST['loanRuleData'];
			//Truncate the current data
			$loanRule = new LoanRule();
			$loanRule->query("TRUNCATE table " . $loanRule->__table);

			//Parse the new data
			$data = preg_split('/\\r\\n|\\r|\\n/', $loanRuleData);
			foreach ($data as $dataRow){
				$dataFields = preg_split('/\\t/', $dataRow);
				$loanRuleNew = new LoanRule();
				$loanRuleNew->loanRuleId = $dataFields[0];
				$loanRuleNew->name = trim($dataFields[1]);
				$loanRuleNew->code = trim($dataFields[2]);
				$loanRuleNew->normalLoanPeriod = trim($dataFields[3]);
				$loanRuleNew->holdable = strcasecmp(trim($dataFields[4]), 'y') == 0;
				$loanRuleNew->bookable = strcasecmp(trim($dataFields[5]), 'y') == 0;
				$loanRuleNew->homePickup = strcasecmp(trim($dataFields[6]), 'y') == 0;
				$loanRuleNew->shippable = strcasecmp(trim($dataFields[7]), 'y') == 0 ;
				$loanRuleNew->insert();
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
	function getAllObjects(){
		$object = new LoanRule();
		$object->orderBy('loanRuleId');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->loanRuleId] = clone $object;
		}
		return $objectList;
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
	function getAllowableRoles(){
		return array('opacAdmin');
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
}