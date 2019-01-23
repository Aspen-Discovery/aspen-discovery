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
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once 'XML/Unserializer.php';

abstract class ObjectEditor extends Admin_Admin
{
	function launch()
	{
		global $interface;

		if (isset($_SESSION['lastError'])){
			$interface->assign('lastError', $_SESSION['lastError']);
			unset($_SESSION['lastError']);
		}

		$interface->assign('canAddNew', $this->canAddNew());
		$interface->assign('canDelete', $this->canDelete());
		$interface->assign('showReturnToList', $this->showReturnToList());

		$interface->assign('objectType', $this->getObjectType());
		$interface->assign('toolName', $this->getToolName());

		//Define the structure of the object.
		$structure = $this->getObjectStructure();
		$interface->assign('structure', $structure);
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		$customListActions = $this->customListActions();
		$interface->assign('customListActions', $customListActions);
		if (is_null($objectAction) || $objectAction == 'list'){
			$interface->assign('instructions', $this->getListInstructions());
			$this->viewExistingObjects();
		}elseif (($objectAction == 'save' || $objectAction == 'delete') && isset($_REQUEST['id'])){
			$this->editObject($objectAction, $structure);
		}else{
			//check to see if a custom action is being called.
			if (method_exists($this, $objectAction)){
				$this->$objectAction();
			}else{
				$interface->assign('instructions', $this->getInstructions());
				$this->viewIndividualObject($structure);
			}
		}
		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setPageTitle($this->getPageTitle());
		$interface->display('layout.tpl');

	}
	/**
	 * The class name of the object which is being edited
	 */
	abstract function getObjectType();
	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	abstract function getToolName();
	/**
	 * The title of the page to be displayed
	 */
	abstract function getPageTitle();
	/**
	 * Load all objects into an array keyed by the primary key
	 */
	abstract function getAllObjects();
	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	abstract function getObjectStructure();
	/**
	 * The name of the column which defines this as unique
	 */
	abstract function getPrimaryKeyColumn();
	/**
	 * The id of the column which serves to join other columns
	 */
	abstract function getIdKeyColumn();

	function getExistingObjectByPrimaryKey($objectType, $value){
		$primaryKeyColumn = $this->getPrimaryKeyColumn();
		/** @var DB_DataObject $curLibrary */
		$curLibrary = new $objectType();
		$curLibrary->$primaryKeyColumn = $value;
		$curLibrary->find();
		if ($curLibrary->N == 1){
			$curLibrary->fetch();
			return $curLibrary;
		}else{
			return null;
		}
	}
	function getExistingObjectById($id){
		$objectType = $this->getObjectType();
		$idColumn = $this->getIdKeyColumn();
		/** @var DB_DataObject $curLibrary */
		$curLibrary = new $objectType;
		$curLibrary->$idColumn = $id;
		$curLibrary->find();
		if ($curLibrary->N == 1){
			$curLibrary->fetch();
			return $curLibrary;
		}else{
			return null;
		}
	}

	function insertObject($structure){
		$objectType = $this->getObjectType();
		/** @var DB_DataObject $newObject */
		$newObject = new $objectType;
		//Check to see if we are getting default values from the
		$validationResults = $this->updateFromUI($newObject, $structure);
		if ($validationResults['validatedOk']) {
			$ret = $newObject->insert();
			if (!$ret) {
				global $logger;
				if ($newObject->_lastError) {
					$errorDescription = $newObject->_lastError->getUserInfo();
				} else {
					$errorDescription = 'Unknown error';
				}
				$logger->log('Could not insert new object ' . $ret . ' ' . $errorDescription, PEAR_LOG_DEBUG);
				@session_start();
				$_SESSION['lastError'] = "An error occurred inserting {$this->getObjectType()} <br/>{$errorDescription}";

				$logger->log(mysql_error(), PEAR_LOG_DEBUG);
				return false;
			}
		} else {
			global $logger;
			$errorDescription = implode(', ', $validationResults['errors']);
			$logger->log('Could not validate new object ' . $objectType . ' ' . $errorDescription, PEAR_LOG_DEBUG);
			@session_start();
			$_SESSION['lastError'] = "The information entered was not valid. <br/>" . implode('<br/>', $validationResults['errors']);

			return false;
		}
		return $newObject;
	}

	function setDefaultValues($object, $structure){
		foreach ($structure as $property){
			$propertyName = $property['property'];
			if (isset($_REQUEST[$propertyName])){
				$object->$propertyName = $_REQUEST[$propertyName];
			}
		}
	}
	function updateFromUI($object, $structure){
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		DataObjectUtil::updateFromUI($object, $structure);
		$validationResults = DataObjectUtil::validateObject($structure, $object);
		return $validationResults;
	}
	function viewExistingObjects(){
		global $interface;
		//Basic List
		$interface->assign('dataList', $this->getAllObjects());
		$interface->setTemplate('../Admin/propertiesList.tpl');
	}
	function viewIndividualObject($structure){
		global $interface;
		//Viewing an individual record, get the id to show
		if (isset($_SERVER['HTTP_REFERER'])){
			$_SESSION['redirect_location'] = $_SERVER['HTTP_REFERER'];
		}else{
			unset($_SESSION['redirect_location']);
		}
		if (isset($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			$existingObject = $this->getExistingObjectById($id);
			$interface->assign('id', $id);
			if (method_exists($existingObject, 'label')){
				$interface->assign('objectName', $existingObject->label());
			}
		}else{
			$existingObject = null;
		}
		if (!isset($_REQUEST['id']) || $existingObject == null){
			$objectType = $this->getObjectType();
			$existingObject = new $objectType;
			$this->setDefaultValues($existingObject, $structure);
		}
		$interface->assign('object', $existingObject);
		//Check to see if the request should be multipart/form-data
		$contentType = null;
		foreach ($structure as $property){
			if ($property['type'] == 'image' || $property['type'] == 'file'){
				$contentType = 'multipart/form-data';
			}
		}
		$interface->assign('contentType', $contentType);

		$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($existingObject));
		$interface->setTemplate('../Admin/objectEditor.tpl');
	}

	function editObject($objectAction, $structure){
		$errorOccurred = false;
		//Save or create a new object
		$id = $_REQUEST['id'];
		if (empty($id) || $id < 0){
			//Insert a new record
			$curObject = $this->insertObject($structure);
			if ($curObject == false){
				//The session lastError is updated
				$errorOccurred = true;
			}
		}else{
			//Work with an existing record
			$curObject = $this->getExistingObjectById($id);
			if (!is_null($curObject)){
				if ($objectAction == 'save'){
					//Update the object
					$validationResults = $this->updateFromUI($curObject, $structure);
					if ($validationResults['validatedOk']) {
						$ret = $curObject->update();
						if ($ret === false) {
							if ($curObject->_lastError) {
								$errorDescription = $curObject->_lastError->getUserInfo();
							} else {
								$errorDescription = 'Unknown error';
							}
							$_SESSION['lastError'] = "An error occurred updating {$this->getObjectType()} with id of $id <br/>{$errorDescription}";
							$errorOccurred         = true;
						}
					} else {
						$errorDescription = implode(', ', $validationResults['errors']);
						$_SESSION['lastError'] = "An error occurred validating {$this->getObjectType()} with id of $id <br/>{$errorDescription}";
						$errorOccurred         = true;
					}
				}else if ($objectAction =='delete'){
					//Delete the record
					$ret = $curObject->delete();
					if ($ret === false){
						$_SESSION['lastError'] = "Unable to delete {$this->getObjectType()} with id of $id";
						$errorOccurred = true;
					}
				}
			}else{
				//Couldn't find the record.  Something went haywire.
				$_SESSION['lastError'] = "An error occurred, could not find {$this->getObjectType()} with id of $id";
				$errorOccurred = true;
			}
		}
		global $configArray;
		if (isset($_REQUEST['submitStay']) || $errorOccurred){
			header("Location: {$configArray['Site']['path']}/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id");
		}elseif (isset($_REQUEST['submitAddAnother'])){
			header("Location: {$configArray['Site']['path']}/{$this->getModule()}/{$this->getToolName()}?objectAction=addNew");
		}else{
			$redirectLocation = $this->getRedirectLocation($objectAction, $curObject);
			if (is_null($redirectLocation)){
				if (isset($_SESSION['redirect_location']) && $objectAction != 'delete'){
					header("Location: " . $_SESSION['redirect_location']);
				}else{
					header("Location: {$configArray['Site']['path']}/{$this->getModule()}/{$this->getToolName()}");
				}
			}else{
				header("Location: {$redirectLocation}");
			}
		}
		die();
	}

	function getRedirectLocation($objectAction, $curObject){
		return null;
	}
	function showReturnToList(){
		return true;
	}

	function getFilters(){
		return array();
	}
	function getModule(){
		return 'Admin';
	}

	function getFilterValues(){
		$filters = $this->getFilters();
		foreach ($filters as $filter){
			if ($_REQUEST[$filter['filter']]){
				$filter['value'] = $_REQUEST[$filter['filter']];
			}else{
				$filter['value'] = '';
			}
		}
		return $filters;
	}

	public function canAddNew(){
		return true;
	}

	public function canDelete(){
		return true;
	}

	public function customListActions(){
		return array();
	}

	function getAdditionalObjectActions($existingObject){
		return array();
	}

	function getInstructions(){
		return '';
	}
	function getListInstructions(){
		return '';
	}
}