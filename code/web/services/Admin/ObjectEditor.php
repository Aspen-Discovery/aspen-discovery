<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

abstract class ObjectEditor extends Admin_Admin
{
	protected $activeObject;
	function launch()
	{
		global $interface;

		$user = UserAccount::getActiveUserObj();
		if (!empty($user->updateMessage)){
			$interface->assign('lastError', $user->updateMessage);
			$user->updateMessage = '';
			$user->update();
		}

		$structure = $this->getObjectStructure();
		$structure = $this->applyPermissionsToObjectStructure($structure);
		$interface->assign('canAddNew', $this->canAddNew());
		$interface->assign('canCopy', $this->canCopy());
		$interface->assign('canCompare', $this->canCompare());
		$interface->assign('canDelete', $this->canDelete());
		$interface->assign('canSort', $this->canSort());
		$interface->assign('canFilter', $this->canFilter($structure));
		$interface->assign('canBatchUpdate', $this->canBatchEdit());
		$interface->assign('showReturnToList', $this->showReturnToList());

		$interface->assign('objectType', $this->getObjectType());
		$interface->assign('toolName', $this->getToolName());
		$interface->assign('initializationJs', $this->getInitializationJs());
		$interface->assign('initializationAdditionalJs', $this->getInitializationAdditionalJs());

		//Define the structure of the object.
		$interface->assign('structure', $structure);
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		$customListActions = $this->customListActions();
		$interface->assign('customListActions', $customListActions);
		if (is_null($objectAction) || $objectAction == 'list'){
			$this->viewExistingObjects($structure);
		}elseif (($objectAction == 'save' || $objectAction == 'delete')) {
			$this->editObject($objectAction, $structure);
		}elseif ($objectAction == 'compare') {
			$this->compareObjects($structure);
		}elseif ($objectAction == 'history') {
			$this->showHistory();
		}else{
			//check to see if a custom action is being called.
			if (method_exists($this, $objectAction)){
				$this->$objectAction();
			}else{
				$interface->assign('instructions', $this->getInstructions());
				$this->viewIndividualObject($structure);
			}
		}
		$this->display($interface->getTemplate(), $this->getPageTitle());
	}
	/**
	 * The class name of the object which is being edited
	 */
	abstract function getObjectType() : string;
	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	abstract function getToolName() : string;
	/**
	 * The title of the page to be displayed
	 */
	abstract function getPageTitle() : string;

	/**
	 * Load all objects into an array keyed by the primary key
	 * @param int $page - The current page to display
	 * @param int $recordsPerPage - Number of records to show per page
	 * @return DataObject[]
	 */
	abstract function getAllObjects($page, $recordsPerPage) : array;

	protected $_numObjects = null;
	/**
	 * Get a count of the number of objects so we can paginate as needed
	 */
	function getNumObjects() : int{
		if ($this->_numObjects == null) {
			/** @var DataObject $object */
			$objectType = $this->getObjectType();
			$object = new $objectType();
			$this->applyFilters($object);
			$this->_numObjects = $object->count();
		}
		return $this->_numObjects;
	}
	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	abstract function getObjectStructure() : array;
	/**
	 * The name of the column which defines this as unique
	 */
	abstract function getPrimaryKeyColumn() : string;
	/**
	 * The id of the column which serves to join other columns
	 */
	abstract function getIdKeyColumn() : string;

	function getExistingObjectById($id) : ?DataObject
	{
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
	function insertObject($structure)
	{
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
					$errorDescription = translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]);
				}
				$logger->log('Could not insert new object ' . $ret . ' ' . $errorDescription, Logger::LOG_DEBUG);
				$user = UserAccount::getActiveUserObj();
				$user->updateMessage = "An error occurred inserting {$this->getObjectType()} <br/>{$errorDescription}";
				$user->updateMessageIsError = true;
				$user->update();

				$logger->log($errorDescription, Logger::LOG_DEBUG);
				return false;
			}
		} else {
			global $logger;
			$errorDescription = implode(', ', $validationResults['errors']);
			$logger->log('Could not validate new object ' . $objectType . ' ' . $errorDescription, Logger::LOG_DEBUG);
			$user = UserAccount::getActiveUserObj();
			$user->updateMessage = "The information entered was not valid. <br/>" . implode('<br/>', $validationResults['errors']);
			$user->updateMessageIsError = true;
			$user->update();

			return false;
		}
		return $newObject;
	}

	function setDefaultValues($object, $structure){
		foreach ($structure as $property){
			$propertyName = $property['property'];
			if (isset($_REQUEST[$propertyName])){
				$object->$propertyName = $_REQUEST[$propertyName];
			}elseif (isset($property['default'])){
				$object->$propertyName = $property['default'];
			}elseif ($property['type'] == 'section'){
				$this->setDefaultValues($object, $property['properties']);
			}
		}
	}
	function updateFromUI($object, $structure){
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		DataObjectUtil::updateFromUI($object, $structure);
		return DataObjectUtil::validateObject($structure, $object);
	}
	function viewExistingObjects($structure){
		global $interface;
		$interface->assign('instructions', $this->getListInstructions());
		$interface->assign('sortableFields', $this->getSortableFields($structure));
		$interface->assign('sort', $this->getSort());
		$filterFields = $this->getFilterFields($structure);
		$interface->assign('filterFields', $filterFields);
		$interface->assign('appliedFilters', $this->getAppliedFilters($filterFields));

		$numObjects = $this->getNumObjects();
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		if (!is_numeric($page)){
			$page = 1;
		}
		$recordsPerPage = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : $this->getDefaultRecordsPerPage();
		//Basic List
		$allObjects = $this->getAllObjects($page, $recordsPerPage);

		if ($this->supportsPagination()) {
			$options = [
				'totalItems' => $numObjects,
				'fileName' => "/{$this->getModule()}/{$this->getToolName()}?page=%d",
				'perPage' => $recordsPerPage,
				'canChangeRecordsPerPage' => true,
				'canJumpToPage' => true
			];
			$pager = new Pager($options);
			$interface->assign('pageLinks', $pager->getLinks());
		}

		$interface->assign('dataList', $allObjects);
		if (count($allObjects) < 2){
			$interface->assign('canCompare', false);
		}
		$interface->assign('showQuickFilterOnPropertiesList', $this->showQuickFilterOnPropertiesList());
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
			if ($existingObject != null){
				if ($existingObject->canActiveUserEdit()) {
					$interface->assign('id', $id);
					if (method_exists($existingObject, 'label')) {
						$interface->assign('objectName', $existingObject->label());
					}
					$this->activeObject = $existingObject;
				}else{
					$interface->setTemplate('../Admin/noPermission.tpl');
					return;
				}
			}else{
				$interface->setTemplate('../Admin/invalidObject.tpl');
				return;
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
					$user = UserAccount::getActiveUserObj();
					$validationResults = $this->updateFromUI($curObject, $structure);
					if ($validationResults['validatedOk']) {
						$ret = $curObject->update();
						if ($ret === false) {
							if ($curObject->getLastError()) {
								$errorDescription = $curObject->getLastError();
							} else {
								$errorDescription = translate(['text'=>'Unknown Error', 'isPublicFacing'=>true]);
							}
							$user->updateMessage = "An error occurred updating {$this->getObjectType()} with id of $id <br/>{$errorDescription}";
							$user->updateMessageIsError = true;
							$user->update();
							$errorOccurred         = true;
						}
					} else {
						$errorDescription = implode('<br/>', $validationResults['errors']);
						$user->updateMessage = "An error occurred validating {$this->getObjectType()} with id of $id <br/>{$errorDescription}";
						$user->updateMessageIsError = true;
						$user->update();
						$errorOccurred         = true;
					}
				}else if ($objectAction =='delete'){
					//Delete the record
					$ret = $curObject->delete();
					if ($ret == 0){
						$user = UserAccount::getActiveUserObj();
						$user->updateMessage = "Unable to delete {$this->getObjectType()} with id of $id";
						$user->updateMessageIsError = true;
						$user->update();
						$errorOccurred = true;
					}
				}
			}else{
				//Couldn't find the record.  Something went haywire.
				$user = UserAccount::getActiveUserObj();
				$user->updateMessage = "An error occurred, could not find {$this->getObjectType()} with id of $id";
				$user->updateMessageIsError = true;
				$user->update();
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

	function getModule() : string{
		return 'Admin';
	}

	public function canAddNew(){
		return true;
	}

	public function canCopy() {
		return $this->canAddNew();
	}

	public function canEdit(DataObject $object){
		return true;
	}

	public function canCompare() {
		return true;
	}

	public function canDelete(){
		return true;
	}

	public function canBatchEdit() {
		return $this->getNumObjects() > 1;
	}

	public function canSort() : bool {
		return $this->getNumObjects() > 3;
	}

	function getSort(){
		return isset($_REQUEST['sort'])? $_REQUEST['sort'] : $this->getDefaultSort();
	}

	abstract function getDefaultSort() : string;

	public function canFilter($objectStructure){
		$filterFields = $this->getFilterFields($objectStructure);
		return ($this->getNumObjects() > 3) || (count($this->getAppliedFilters($filterFields)) > 0);
	}

	public function customListActions(){
		return array();
	}

	/**
	 * @param DataObject $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject){
		return array();
	}

	function getInstructions() : string{
		return '';
	}
	function getListInstructions(){
		return $this->getInstructions();
	}
	function getInitializationJs() : string {
		return '';
	}
	function getInitializationAdditionalJs(){
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
				$structure = $this->applyPermissionsToObjectStructure($structure);
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
	 * @param string|null $sectionName
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
			if ($propertyValue == null){
				return 'null';
			}else {
				return implode('<br/>', $propertyValue);
			}
		}elseif ($propertyType == 'enum') {
			if (isset($property['values'][$propertyValue])){
				return $property['values'][$propertyValue];
			}else{
				return translate(['text'=>'Undefined value %1%',1=>$propertyValue,'isAdminFacing'=>true]);
			}
		} else {
			return is_array($propertyValue) ? implode(', ', $propertyValue) : (is_object($propertyValue) ? (string)$propertyValue : $propertyValue);
		}
	}

	function showHistory() {
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
				$title = translate(["text"=>'History for %1%', 1=>$curObject->$displayNameColumn, "isAdminFacing"=>true]);
			}else{
				$title = translate(["text"=>'History for %1%', 1=>$historyEntry->objectType . ' - ' . $historyEntry->objectId, "isAdminFacing"=>true]);
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

	public function getBatchUpdateFields($structure){
		$batchFormatFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $field){
			$this->addFieldToBatchUpdateFieldsArray($batchFormatFields, $field);
		}
		ksort($batchFormatFields);
		return $batchFormatFields;
	}

	public function getSortableFields($structure){
		$sortFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $fieldName => $field){
			$this->addFieldToSortableFieldsArray($sortFields, $field);
		}
		ksort($sortFields);
		return $sortFields;
	}

	private function addFieldToSortableFieldsArray(&$sortableFields, $field){
		if ($field['type'] == 'section'){
			foreach ($field['properties'] as $subFieldName => $subField){
				$this->addFieldToSortableFieldsArray($batchFormatFields, $subField);
			}
		} else {
			$canSort = !isset($field['canSort']) || ($field['canSort'] == true);
			if ($canSort && in_array($field['type'], ['checkbox', 'label', 'date', 'timestamp', 'enum', 'currency', 'text', 'integer', 'email', 'url'])) {
				$sortableFields[$field['label']] = $field;
			}
		}
	}

	public function getFilterFields($structure){
		$filterFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $fieldName => $field){
			$this->addFieldToFilterFieldsArray($filterFields, $field);
		}
		ksort($filterFields);
		return $filterFields;
	}

	private function addFieldToFilterFieldsArray(&$filterFields, $field){
		if ($field['type'] == 'section'){
			foreach ($field['properties'] as $subFieldName => $subField){
				$this->addFieldToFilterFieldsArray($filterFields, $subField);
			}
		} else {
			$canSort = !isset($field['canSort']) || ($field['canSort'] == true);
			if ($canSort && in_array($field['type'], ['checkbox', 'label', 'date', 'timestamp', 'enum', 'currency', 'text', 'integer', 'email', 'url'])) {
				$filterFields[$field['property']] = $field;
				if ($field['type'] == 'enum'){
					$filterFields[$field['property']]['values'] = ['all_values' => translate(['text' => 'All Values', 'isAdminFacing'=>true, 'inAttribute'=>'true'])] + $filterFields[$field['property']]['values'];
				}
			}
		}
	}

	public function getAppliedFilters($filterFields){
		$appliedFilters = [];
		if (isset($_REQUEST['filterType'])){
			foreach ($_REQUEST['filterType'] as $fieldName => $value){
				$appliedFilters[$fieldName] = [
					'fieldName' => $fieldName,
					'filterType' => $value,
					'filterValue' => isset($_REQUEST['filterValue'][$fieldName]) ? $_REQUEST['filterValue'][$fieldName] : '',
					'filterValue2' => isset($_REQUEST['filterValue2'][$fieldName]) ? $_REQUEST['filterValue2'][$fieldName] : '',
					'field' => $filterFields[$fieldName]
				];
			}
		}
		if (count($appliedFilters) == 0){
			$appliedFilters = $this->getDefaultFilters($filterFields);
		}
		return $appliedFilters;
	}

	function getDefaultFilters(array $filterFields) : array{
		return [];
	}

	function applyFilters(DataObject $object){
		$filterFields = $this->getFilterFields($object::getObjectStructure());
		$appliedFilters = $this->getAppliedFilters($filterFields);
		foreach ($appliedFilters as $fieldName => $filter){
			$this->applyFilter($object, $fieldName, $filter);
		}
	}

	function applyFilter(DataObject $object, string $fieldName, array $filter){
		if ($filter['filterType'] == 'matches'){
			if ($filter['field']['type'] == 'enum' && $filter['filterValue'] == 'all_values'){
				//Skip this value
				return;
			}
			if ($filter['filterValue'] == ''){
				$object->whereAdd("$fieldName IS NULL OR $fieldName = ''" );
			}else {
				$object->$fieldName = $filter['filterValue'];
			}
		}elseif ($filter['filterType'] == 'contains'){
			$object->whereAdd($fieldName . ' like ' . $object->escape('%' . $filter['filterValue'] . '%'));
		}elseif ($filter['filterType'] == 'startsWith'){
			$object->whereAdd($fieldName . ' like ' . $object->escape($filter['filterValue'] . '%'));
		}elseif ($filter['filterType'] == 'beforeTime'){
			$fieldValue = strtotime($filter['filterValue2']);
			if ($fieldValue !== false) {
				$object->whereAdd($fieldName . ' < ' . $fieldValue);
			}
		}elseif ($filter['filterType'] == 'afterTime'){
			$fieldValue = strtotime($filter['filterValue']);
			if ($fieldValue !== false) {
				$object->whereAdd($fieldName . ' > ' . $fieldValue);
			}
		}elseif ($filter['filterType'] == 'betweenTimes'){
			$fieldValue = strtotime($filter['filterValue']);
			if ($fieldValue !== false) {
				$object->whereAdd($fieldName . ' > ' . $fieldValue);
			}
			$fieldValue2 = strtotime($filter['filterValue2']);
			if ($fieldValue2 !== false) {
				$object->whereAdd($fieldName . ' < ' . $fieldValue2);
			}
		}
	}

	private function addFieldToBatchUpdateFieldsArray(&$batchFormatFields, $field){
		if ($field['type'] == 'section'){
			foreach ($field['properties'] as $subFieldName => $subField){
				$this->addFieldToBatchUpdateFieldsArray($batchFormatFields, $subField);
			}
		} else {
			$canBatchUpdate = !isset($field['canBatchUpdate']) || ($field['canBatchUpdate'] == true);
			if ($canBatchUpdate && in_array($field['type'], ['checkbox', 'enum', 'currency', 'text', 'integer', 'email', 'url', 'timestamp'])) {
				$batchFormatFields[$field['label']] = $field;
			}
		}
	}

	protected function getDefaultRecordsPerPage()
	{
		return 25;
	}

	protected function showQuickFilterOnPropertiesList(){
		return false;
	}

	protected function supportsPagination(){
		return true;
	}

	protected function limitToObjectsForLibrary(&$object, $linkObjectType, $linkProperty){
		$userHasExistingObjects = true;
		$linkObject = new $linkObjectType();
		$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
		if ($library != null){
			$linkObject->libraryId = $library->libraryId;
			$objectsForLibrary = [];
			$linkObject->find();
			while ($linkObject->fetch()){
				$objectsForLibrary[] = $linkObject->$linkProperty;
			}
			if (count($objectsForLibrary) > 0) {
				$object->whereAddIn('id', $objectsForLibrary, false);
			}else{
				$userHasExistingObjects = false;
			}
		}
		return $userHasExistingObjects;
	}

	protected function applyPermissionsToObjectStructure(array $structure)
	{
		foreach ($structure as $key => &$property){
			if ($property['type'] == 'section'){
				$property['properties'] = $this->applyPermissionsToObjectStructure($property['properties']);
				if (array_key_exists('permissions', $property)) {
					if (!UserAccount::userHasPermission($property['permissions'])){
						unset($structure[$key]);
					}
				}
				if (count($property['properties']) == 0){
					unset($structure[$key]);
				}
			}else{
				if (array_key_exists('permissions', $property)){
					//Verify the correct permission exists for the user
					if (!UserAccount::userHasPermission($property['permissions'])){
						unset($structure[$key]);
					}
				}
				if (array_key_exists('editPermissions', $property)){
					//Verify the correct permission exists for the user
					if (!UserAccount::userHasPermission($property['editPermissions'])){
						$property['type'] = 'label';
					}
				}
			}
		}
		return $structure;
	}
}