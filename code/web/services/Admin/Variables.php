<?php

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
	function getAllObjects($page, $recordsPerPage){
		$variableList = array();

		$object = new Variable();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$variableList[$object->id] = clone $object;
		}
		return $variableList;
	}

	function getDefaultSort()
	{
		return 'name asc';
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
	function canAddNew(){
		return false;
	}
	function canDelete(){
		return true;
	}

	/**
	 * @param DataObject $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject){
		$actions = array();
		if ($existingObject && $existingObject->getPrimaryKeyValue() != ''){
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-time" aria-hidden="true"></span> Set to Current Timestamp (seconds)',
				'url' => "/{$this->getModule()}/{$this->getToolName()}?objectAction=setToNow&amp;id=" . $existingObject->getPrimaryKeyValue(),
			);
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-time" aria-hidden="true"></span> Set to Current Timestamp (milliseconds)',
				'url'  => "/{$this->getModule()}/{$this->getToolName()}?objectAction=setToNow&amp;ms=1&amp;id=" . $existingObject->getPrimaryKeyValue(),
			);
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span> Increase by 10,000',
				'url'  => "/{$this->getModule()}/{$this->getToolName()}?objectAction=IncrementVariable&amp;direction=up&amp;id=" . $existingObject->getPrimaryKeyValue(),
			);
			$actions[] = array(
				'text' => '<span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span> Decrease by 500',
				'url'  => "/{$this->getModule()}/{$this->getToolName()}?objectAction=IncrementVariable&amp;direction=down&amp;id=" . $existingObject->getPrimaryKeyValue(),
			);
		}
		return $actions;
	}

	/** @noinspection PhpUnused */
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

	/** @noinspection PhpUnused */
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
						header("Location: {$_SERVER['REQUEST_URI']}");
						die();
					}
				}
			}
		}
		parent::editObject($objectAction, $structure);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Variables', 'Variables');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'system_admin';
	}

	function canView()
	{
		return UserAccount::userHasPermission('Administer System Variables');
	}

	function canBatchEdit()
	{
		return false;
	}

	function canCompare()
	{
		return false;
	}
}