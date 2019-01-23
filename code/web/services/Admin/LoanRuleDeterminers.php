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
require_once ROOT_DIR . '/Drivers/marmot_inc/LoanRuleDeterminer.php';
require_once 'XML/Unserializer.php';

class LoanRuleDeterminers extends ObjectEditor {
	function launch(){
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'reloadFromCsv'){
			global $interface;
			$interface->setTemplate('../Admin/importLoanRuleDeterminerData.tpl');
			$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
			$interface->setPageTitle("Reload Loan Rule Determiners");
			$interface->display('layout.tpl');
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
	function getAllObjects(){
		$object = new LoanRuleDeterminer();
		$object->orderBy('rowNumber');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->rowNumber] = clone $object;
		}
		return $objectList;
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