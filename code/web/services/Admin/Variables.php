<?php
/**
 * Display a list of internal variables that have been defined in the system.
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/27/14
 * Time: 2:21 PM
 */
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_Variables extends ObjectEditor{

	function getObjectType(){
		return 'Variable';
	}
	function getToolName(){
		return 'Variables';
	}
	function getPageTitle(){
		return 'System Variables';
	}
	function getAllObjects(){
		$variableList = array();

		$variable = new Variable();
		$variable->orderBy('name');
		$variable->find();
		while ($variable->fetch()){
			$variableList[$variable->id] = clone $variable;
		}
		return $variableList;
	}
	function getObjectStructure(){
		return Variable::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'name';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function canAddNew(){
		return false;
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}


	function getAdditionalObjectActions($existingObject){
		$actions = array();
		if ($existingObject && $existingObject->id != ''){
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-time" aria-hidden="true"></span> Set to Current Timestamp (seconds)',
				'url' => "/{$this->getModule()}/{$this->getToolName()}?objectAction=setToNow&amp;id=" . $existingObject->id,
			);
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-time" aria-hidden="true"></span> Set to Current Timestamp (milliseconds)',
				'url'  => "/{$this->getModule()}/{$this->getToolName()}?objectAction=setToNow&amp;ms=1&amp;id=" . $existingObject->id,
			);
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span> Increase by 10,000',
				'url'  => "/{$this->getModule()}/{$this->getToolName()}?objectAction=IncrementVariable&amp;direction=up&amp;id=" . $existingObject->id,
			);
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span> Decrease by 500',
				'url'  => "/{$this->getModule()}/{$this->getToolName()}?objectAction=IncrementVariable&amp;direction=down&amp;id=" . $existingObject->id,
			);
		}
		return $actions;
	}

	function setToNow(){
		$id = $_REQUEST['id'];
		$useMilliseconds = isset($_REQUEST['ms']) && ($_REQUEST['ms'] == 1 || $_REQUEST['ms'] == 'true');
		if (!empty($id) && ctype_digit($id)) {
			$variable = new Variable();
			$variable->get($id);
			if ($variable) {
				$variable->value = $useMilliseconds ? time() * 1000 : time();
				$variable->update();
			}
			header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=" . $id);
		}
	}

	function IncrementVariable(){
		$id = $_REQUEST['id'];
		if (!empty($id) && ctype_digit($id)) {
			$variable = new Variable();
			$variable->get($id);
			if ($variable) {
				$amount = 0;
				if ($_REQUEST['direction'] == 'up') {
					$amount = 10000;
				} elseif ($_REQUEST['direction'] == 'down') {
					$amount = -500;
				}
				if ($amount) {
					$variable->value += $amount;
					$variable->update();
				}
			}
			header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=" . $id);
		}
	}

	function editObject($objectAction, $structure)
	{
		if ($objectAction == 'save') {
			if (!empty($_REQUEST['name']) && $_REQUEST['name'] == 'offline_mode_when_offline_login_allowed') {
				if (!empty($_REQUEST['value']) && $_REQUEST['value'] == 'true' || $_REQUEST['value'] == 1) {
					global $configArray;
					if (isset($configArray['Catalog']['enableLoginWhileOffline']) && empty($configArray['Catalog']['enableLoginWhileOffline'])) {
						$_SESSION['lastError'] = "While offline logins are disabled offline mode can not be turned on with this variable.";
						header("Location: {$configArray['Site']['path']}{$_SERVER['REQUEST_URI']}");
						die();
					}
				}
			}
		}
		parent::editObject($objectAction, $structure);
	}


}