<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

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
		$interface->assign('canCopy', $this->canCopy());
		$interface->assign('canCompare', $this->canCompare());
		$interface->assign('canDelete', $this->canDelete());
		$interface->assign('showReturnToList', $this->showReturnToList());

		$interface->assign('objectType', $this->getObjectType());
		$interface->assign('toolName', $this->getToolName());
		$interface->assign('initializationJs', $this->getInitializationJs());

		//Define the structure of the object.
		$structure = $this->getObjectStructure();
		$interface->assign('structure', $structure);
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		$customListActions = $this->customListActions();
		$interface->assign('customListActions', $customListActions);
		if (is_null($objectAction) || $objectAction == 'list'){
			$interface->assign('instructions', $this->getListInstructions());
			$this->viewExistingObjects();
		}elseif (($objectAction == 'save' || $objectAction == 'delete')) {
			$this->editObject($objectAction, $structure);
		}elseif ($objectAction == 'compare') {
			$this->compareObjects($structure);
		}elseif ($objectAction == 'history') {
			$this->showHistory($structure);
		}else{
			//check to see if a custom action is being called.
			if (method_exists($this, $objectAction)){
				$this->$objectAction();
			}else{
				$interface->assign('instructions', $this->getInstructions());
				$this->viewIndividualObject($structure);
			}
		}
		$this->display($interface->getTemplate(), $this->getPageTitle(), 'Search/home-sidebar.tpl');
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

	function getExistingObjectById($id){
		$objectType = $this->getObjectType();
		$idColumn = $this->getIdKeyColumn();
		/** @var DataObject $curLibrary */
		$curLibrary = new $objectType;
		$curLibrary->$idColumn = $id;
		$curLibrary->find();
		if ($curLibrary->getNumResults() == 1){
			$curLibrary->fetch();
			return $curLibrary;
		}else{
			return null;
		}
	}

	/**
	 * @param $structure
	 * @return DataObject|false
	 */
	function insertObject($structure){
		$objectType = $this->getObjectType();
		/** @var DataObject $newObject */
		$newObject = new $objectType;
		//Check to see if we are getting default values from the
		$validationResults = $this->updateFromUI($newObject, $structure);
		if ($validationResults['validatedOk']) {
			$ret = $newObject->insert();
			if (!$ret) {
				global $logger;
				if ($newObject->getLastError()) {
					$errorDescription = $newObject->getLastError();
				} else {
					$errorDescription = 'Unknown error';
				}
				$logger->log('Could not insert new object ' . $ret . ' ' . $errorDescription, Logger::LOG_DEBUG);
				@session_start();
				$_SESSION['lastError'] = "An error occurred inserting {$this->getObjectType()} <br/>{$errorDescription}";

				$logger->log($errorDescription, Logger::LOG_DEBUG);
				return false;
			}
		} else {
			global $logger;
			$errorDescription = implode(', ', $validationResults['errors']);
			$logger->log('Could not validate new object ' . $objectType . ' ' . $errorDescription, Logger::LOG_DEBUG);
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
		$allObjects = $this->getAllObjects();
		$interface->assign('dataList', $allObjects);
		if (count($allObjects) < 2){
			$interface->assign('canCompare', false);
		}
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
		$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
		if (empty($id) || $id < 0){
			//Insert a new record
			$curObject = $this->insertObject($structure);
			if ($curObject == false){
				//The session lastError is updated
				$errorOccurred = true;
			}else{
				$id = $curObject->getPrimaryKeyValue();
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
					if ($ret == 0){
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
		if (isset($_REQUEST['submitStay']) || $errorOccurred){
			header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id");
		}elseif (isset($_REQUEST['submitAddAnother'])){
			header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=addNew");
		}else{
			$redirectLocation = $this->getRedirectLocation($objectAction, $curObject);
			if (is_null($redirectLocation)){
				if (isset($_SESSION['redirect_location']) && $objectAction != 'delete'){
					header("Location: " . $_SESSION['redirect_location']);
				}else{
					header("Location: /{$this->getModule()}/{$this->getToolName()}");
				}
			}else{
				header("Location: {$redirectLocation}");
			}
		}
		die();
	}

	/**
	 * @param string $objectAction
	 * @param DataObject $curObject
	 * @return string|null
	 */
	function getRedirectLocation(/** @noinspection PhpUnusedParameterInspection */$objectAction, $curObject){
		return null;
	}
	function showReturnToList(){
		return true;
	}

	function getModule(){
		return 'Admin';
	}

	public function canAddNew(){
		return true;
	}

	public function canCopy() {
		return $this->canAddNew();
	}

	public function canCompare() {
		return true;
	}

	public function canDelete(){
		return true;
	}

	public function customListActions(){
		return array();
	}

	/**
	 * @param DataObject $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions(/** @noinspection PhpUnusedParameterInspection */ $existingObject){
		return array();
	}

	function getInstructions(){
		return '';
	}
	function getListInstructions(){
		return $this->getInstructions();
	}
	function getInitializationJs(){
		return '';
	}

	function compareObjects($structure)
	{
		global $interface;
		$object1 = null;
		$object2 = null;
		if (count($_REQUEST['selectedObject']) == 2){
			$index = 1;
			foreach ($_REQUEST['selectedObject'] as $id => $value){
				if ($index == 1){
					$object1 = $this->getExistingObjectById($id);
					$object1EditUrl = "/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id";
					$interface->assign('object1EditUrl', $object1EditUrl);
					$index = 2;
				}else{
					$object2 = $this->getExistingObjectById($id);
					$object2EditUrl = "/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id";
					$interface->assign('object2EditUrl', $object2EditUrl);
				}
			}
			if ($object1 == null || $object2 == null){
				$interface->assign('error', 'Could not load object from the database');
			}else{
				$properties = [];
				$properties = $this->compareObjectProperties($structure, $object1, $object2, $properties, '');
				$interface->assign('properties', $properties);
			}
		}else{
			$interface->assign('error', 'Please select two objects to compare');
		}

		$interface->setTemplate('../Admin/compareObjects.tpl');
	}

	/**
	 * @param $structure
	 * @param DataObject|null $object1
	 * @param DataObject|null $object2
	 * @param array $properties
	 * @return array
	 */
	protected function compareObjectProperties($structure, ?DataObject $object1, ?DataObject $object2, array $properties, $sectionName): array
	{
		foreach ($structure as $property) {
			if ($property['type'] == 'section') {
				$label = $property['label'];
				if (!empty($sectionName)) {
					$label = $sectionName . ': ' . $label;
				}
				$properties = $this->compareObjectProperties($property['properties'], $object1, $object2, $properties, $label);
			} else {
				$propertyName = $property['property'];
				$uniqueProperty = isset($property['uniqueProperty']) ? $property['uniqueProperty'] : ($propertyName == $this->getPrimaryKeyColumn());
				$propertyValue1 = $this->getPropertyValue($property, $object1->$propertyName, $property['type']);
				$propertyValue2 = $this->getPropertyValue($property, $object2->$propertyName, $property['type']);
				$label = $property['label'];
				if (!empty($sectionName)) {
					$label = $sectionName . ': ' . $label;
				}
				$properties[] = [
					'name' => $label,
					'value1' => $propertyValue1,
					'value2' => $propertyValue2,
					'uniqueProperty' => $uniqueProperty,
				];
				if ($property['type'] == 'color' || $property['type'] == 'font') {
					$defaultPropertyName = $propertyName . 'Default';
					$propertyValue1Default = $this->getPropertyValue($property, $object1->$defaultPropertyName, $property['type']) == 1 ? 'Yes' : 'No';
					$propertyValue2Default = $this->getPropertyValue($property, $object1->$defaultPropertyName, $property['type']) == 1 ? 'Yes' : 'No';
					$properties[] = [
						'name' => $label . ' Use Default',
						'value1' => $propertyValue1Default,
						'value2' => $propertyValue2Default,
						'uniqueProperty' => $uniqueProperty,
					];
				}
			}
		}
		return $properties;
	}

	function getPropertyValue($property, $propertyValue, $propertyType)
	{
		if ($propertyType == 'oneToMany' || $propertyType == 'multiSelect') {
			return implode('<br/>', $propertyValue);
		}elseif ($propertyType == 'enum') {
			return $property['values'][$propertyValue];
		} else {
			return is_array($propertyValue) ? implode(', ', $propertyValue) : (is_object($propertyValue) ? (string)$propertyValue : $propertyValue);
		}
	}

	function showHistory($structure) {
		$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
		if (empty($id) || $id < 0){
			AspenError::raiseError('Please select an object to show history for');
		}else{
			//Work with an existing record
			global $interface;
			$curObject = $this->getExistingObjectById($id);
			$interface->assign('curObject', $curObject);
			$interface->assign('id', $id);
			$displayNameColumn = $curObject->__displayNameColumn;
			$primaryField = $curObject->__primaryKey;
			$objectHistory = [];
			require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
			$historyEntry = new DataObjectHistory();
			$historyEntry->objectType = get_class($curObject);
			$historyEntry->objectId = $curObject->$primaryField;
			if ($displayNameColumn != null){
				$title = 'History for ' . $curObject->$displayNameColumn;
			}else{
				$title = 'History for ' . $historyEntry->objectType . ' - ' . $historyEntry->objectId;
			}
			$interface->assign('title', $title);
			$historyEntry->orderBy('changeDate desc');
			$historyEntry->find();
			while ($historyEntry->fetch()){
				$objectHistory[] = clone $historyEntry;
			}
			$interface->assign('objectHistory', $objectHistory);
			$this->display('../Admin/objectHistory.tpl',$title);
			exit();
		}
	}

	public function hasHistory(){
		return true;
	}
}