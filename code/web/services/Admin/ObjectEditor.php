<?php

use JetBrains\PhpStorm\NoReturn;

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/User/PageDefaults.php';
abstract class ObjectEditor extends Admin_Admin {
	protected ?DataObject $activeObject;
	protected ?string $objectAction;

	function launch() : void {
		global $interface;
		global $activeLanguage;

		$interface->assign('activeLanguage', $activeLanguage);

		$user = UserAccount::getActiveUserObj();
		if (!empty($user->updateMessage)) {
			$interface->assign('updateMessage', $user->updateMessage);
			$interface->assign('updateMessageIsError', $user->updateMessageIsError);
			$user->updateMessage = '';
			$user->updateMessageIsError = 0;
			$user->update();
		}

		$objectAction = $_REQUEST['objectAction'] ?? null;
		$this->objectAction = $objectAction;
		$interface->assign('context', $this->getContext());
		$structure = $this->getObjectStructure($this->getContext());
		$structure = $this->applyPermissionsToObjectStructure($structure);
		$interface->assign('canAddNew', $this->canAddNew());
		$interface->assign('canCopy', $this->canCopy());
		$interface->assign('hasCopyOptions', $this->hasCopyOptions());
		if ($this->canCopy() && $objectAction == 'copy') {
			$copyNoteTemplate = $this->getCopyNotes();
			if (empty($copyNoteTemplate) || !file_exists(ROOT_DIR . $copyNoteTemplate)) {
				$interface->assign('copyNotes', '');
			}else {
				require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
				$parsedown = AspenParsedown::instance();
				$copyNotes = $parsedown->parse(file_get_contents(ROOT_DIR . $copyNoteTemplate));
				$interface->assign('copyNotes', $copyNotes);
			}
		}
		$interface->assign('canCompare', $this->canCompare());
		$interface->assign('canDelete', $this->canDelete());
		$interface->assign('canDeleteAll', $this->canDeleteAll());
		$interface->assign('canSort', $this->canSort());
		$interface->assign('canFilter', $this->canFilter($structure));
		$interface->assign('canBatchUpdate', $this->canBatchEdit());
		$interface->assign('canBatchDelete', $this->canBatchDelete());
		$interface->assign('canExportToCSV', $this->canExportToCSV());
		$interface->assign('showReturnToList', $this->showReturnToList());
		$interface->assign('showHistoryLinks', $this->showHistoryLinks());
		$interface->assign('canShareToCommunity', $this->canShareToCommunity());
		$interface->assign('canFetchFromCommunity', $this->canFetchFromCommunity());
		$interface->assign('hasMultiStepAddNew', $this->hasMultiStepAddNew());
		$interface->assign('linkedObjectNotifications', $this->getLinkedObjectNotifications());
		$interface->assign('editFormInstructions', $this->getEditFormInstructions());

		$interface->assign('objectType', $this->getObjectType());
		$interface->assign('toolName', $this->getToolName());
		$interface->assign('initializationJs', $this->getInitializationJs());
		$interface->assign('initializationAdditionalJs', $this->getInitializationAdditionalJs());
		$interface->assign('onSubmissionJS', $this->getOnSubmissionJS());
		$interface->assign('allowSearchingProperties', $this->allowSearchingProperties($structure));
		$interface->assign('hasRecordLocking', $this->hasRecordLocking());
		$interface->assign('userCanChangeRecordLocks', $this->userCanChangeRecordLocks());

		//Define the structure of the object.
		$interface->assign('structure', $structure);
		$interface->assign('objectAction', $objectAction);
		$customListActions = $this->customListActions();
		$interface->assign('customListActions', $customListActions);
		$customListPanel = $this->getCustomListPanel();
		$interface->assign('customListPanel', $customListPanel);
		if (is_null($objectAction) || $objectAction == 'list') {
			$this->viewExistingObjects($structure);
		} elseif ($objectAction == 'save' || $objectAction == 'saveCopy' || $objectAction == 'delete') {
			$this->editObject($objectAction, $structure);
		} elseif ($objectAction == 'compare') {
			$this->compareObjects($structure);
		} elseif ($objectAction == 'history') {
			$this->showHistory();
		} elseif ($objectAction == 'copy') {
			$this->copyObject($structure);
		} elseif ($objectAction == 'getCopyOptions') {
			$this->getCopyOptions();
		} elseif ($objectAction == 'lockRecord') {
			$this->lockRecord($structure);
		} elseif ($objectAction == 'unlockRecord') {
			$this->unlockRecord($structure);
		} elseif ($objectAction == 'shareForm') {
			$this->showShareForm();
		} elseif ($objectAction == 'shareToCommunity') {
			$this->shareToCommunity();
		} elseif ($objectAction == 'importFromCommunity') {
			$this->importFromCommunity($structure);
		} elseif ($objectAction == 'exportToCSV' || $objectAction == 'exportSelectedToCSV') {
			$this->viewExistingObjects($structure);
		} else {
			//check to see if a custom action is being called.
			if (method_exists($this, $objectAction)) {
				$this->$objectAction();
			} else {
				$interface->assign('instructions', $this->getInstructions());
				$this->viewIndividualObject($structure);
			}
		}
		$template = $interface->getTemplate();
		$this->display($template, $this->getPageTitle());
	}

	/**
	 * The class name of the object which is being edited
	 */
	abstract function getObjectType(): string;

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	abstract function getToolName(): string;

	/**
	 * The title of the page to be displayed
	 */
	abstract function getPageTitle(): string;

	/**
	 * Load all objects into an array keyed by the primary key
	 * @param int $page - The current page to display
	 * @param int $recordsPerPage - Number of records to show per page
	 * @return DataObject[]
	 */
	abstract function getAllObjects(int $page, int $recordsPerPage): array;

	protected ?int $_numObjects = null;

	/**
	 * Get a count of the number of objects so we can paginate as needed
	 */
	function getNumObjects(): int {
		if ($this->_numObjects === null) {
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
	abstract function getObjectStructure($context = ''): array;

	/**
	 * The name of the column which defines this as unique
	 */
	abstract function getPrimaryKeyColumn(): string;

	/**
	 * The id of the column which serves to join other columns
	 */
	abstract function getIdKeyColumn(): string;

	function getExistingObjectById($id): ?DataObject {
		$objectType = $this->getObjectType();
		$idColumn = $this->getIdKeyColumn();
		/** @var DataObject $curLibrary */
		$curLibrary = new $objectType;
		$curLibrary->$idColumn = $id;
		$curLibrary->find();
		if ($curLibrary->getNumResults() == 1) {
			$curLibrary->fetch();
			return $curLibrary;
		} else {
			return null;
		}
	}

	/**
	 * @param $structure
	 * @return DataObject|false
	 */
	function insertObject($structure): bool|DataObject {
		$objectType = $this->getObjectType();
		/** @var DataObject $newObject */
		$newObject = new $objectType;
		$validationResults = $this->updateFromUI($newObject, $structure, null);
		if ($validationResults['validatedOk']) {
			$ret = $newObject->insert($this->getContext());
			$doImageAndFileUpdateAfterInsert = DataObjectUtil::structureContainsImagesOrFiles($structure);
			if ($ret && $doImageAndFileUpdateAfterInsert) {
				$this->updateImagesAndFilesAfterInsert($newObject, $structure);
				$ret = $newObject->update('updateImagesAndFilesAfterInsert');
			}
			// Strict comparison because the update() above could return 0, as no rows changed.
			if ($ret === false) {
				if ($newObject->getLastError()) {
					$errorDescription = $newObject->getLastError();
				} else {
					$errorDescription = translate([
						'text' => 'Unknown Error',
						'isPublicFacing' => true,
					]);
				}
				global $logger;
				$logger->log('Could not insert new object ' . $this->getObjectType() . ': ' . $errorDescription, Logger::LOG_DEBUG);
				$user = UserAccount::getActiveUserObj();
				$user->updateMessage = "An error occurred inserting {$this->getObjectType()}: <br/>$errorDescription";
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

	/**
	 * This is called:
	 * - when adding a new object
	 * - after adding a new object fails due to validation errors
	 */
	function setDefaultValues($object, $structure) : void {
		$fieldLocks = $this->getFieldLocks();
		foreach ($structure as $property) {
			$propertyName = $property['property'];
			if ($property['type'] == 'section') {
				$this->setDefaultValues($object, $property['properties']);
			}else {
				if (isset($_REQUEST[$propertyName])) {
					//Use Process Property to make sure values are interpreted properly (i.e. checkboxes)
					DataObjectUtil::processProperty($object, $property, $fieldLocks);
				} elseif (isset($property['default']) && $this->objectAction == 'addNew') {
					//We're adding a new object, use the defaults
					$object->$propertyName = $property['default'];
				}
			}
		}
	}

	function updateFromUI($object, $structure, $fieldLocks) : array {
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		DataObjectUtil::updateFromUI($object, $structure, $fieldLocks);
		return DataObjectUtil::validateObject($structure, $object);
	}

	function updateImagesAndFilesAfterInsert($object, $structure) : void {
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		DataObjectUtil::updateImagesAndFilesAfterInsert($object, $structure);
	}

	function viewExistingObjects($structure) : void {
		global $interface;
		$user = UserAccount::getActiveUserObj();
		// Assign all context parameters for edit links.
		$contextParams = [];
		$preservedParams = ['page', 'pageSize', 'sort', 'filterType', 'filterValue', 'filterValue2'];
		foreach ($preservedParams as $param) {
			if (!empty($_REQUEST[$param])) {
				$contextParams[$param] = $_REQUEST[$param];
				$interface->assign($param, $_REQUEST[$param]);
			}
		}

		// Build context parameter string for edit links.
		$contextQueryString = '';
		if (!empty($contextParams)) {
			$queryParts = [];
			foreach ($contextParams as $param => $value) {
				if (is_array($value)) {
					foreach ($value as $key => $val) {
						$queryParts[] = urlencode($param) . '[' . urlencode($key) . ']=' . urlencode($val);
					}
				} else {
					$queryParts[] = urlencode($param) . '=' . urlencode($value);
				}
			}
			$contextQueryString = '&' . implode('&', $queryParts);
		}
		$interface->assign('contextParams', $contextQueryString);

		$interface->assign('instructions', $this->getListInstructions());
		$interface->assign('sortableFields', $this->getSortableFields($structure));
		$interface->assign('sort', $this->getSort());
		$filterFields = $this->getFilterFields($structure);
		$interface->assign('filterFields', $filterFields);
		$interface->assign('appliedFilters', $this->getAppliedFilters($filterFields));
		$interface->assign('hiddenFields', $this->getHiddenFields());
		$interface->assign('lockedRecords', $this->getLockedRecordIds());

		$numObjects = $this->getNumObjects();
		$page = $_REQUEST['page'] ?? 1;
		if (!is_numeric($page)) {
			$page = 1;
		}

		if (isset($_REQUEST['pageSize'])) {
			$recordsPerPage = $_REQUEST['pageSize'];
			if ($user !== false) {
				PageDefaults::updatePageDefaultsForUser($user->id, $this->getModule(), $this->getToolName(),null, $recordsPerPage, null);
			}
		}else{
			$pageDefaults = PageDefaults::getPageDefaultsForUser($user->id, $this->getModule(), $this->getToolName(),null);
			if ($pageDefaults !== null && !empty($pageDefaults->pageSize)) {
				$recordsPerPage =  $pageDefaults->pageSize;
			}else{
				$recordsPerPage = $this->getDefaultRecordsPerPage();
			}
		}
		if (isset($_REQUEST['objectAction']) && $_REQUEST['objectAction'] == 'exportToCSV') { // Export [all, filtered] to CSV
			$allObjects = $this->getAllObjects('1', min(1000, $numObjects));
			Exporter::downloadCSV($this->getToolName(), 'Admin/propertiesListCSV.tpl', $structure, $allObjects);
		} else { // Export Selected to CSV OR Display on screen
			$allObjects = $this->getAllObjects($page, $recordsPerPage);
			if ($this->supportsPagination()) {
				// Build clean URL for pagination without scrollToId parameter.
				$cleanUrl = $_SERVER['REQUEST_URI'];
				$cleanUrl = preg_replace("/scrollToId=\d+&|[?&]scrollToId=\d+/", '', $cleanUrl);

				$options = [
					'totalItems' => $numObjects,
					'perPage' => $recordsPerPage,
					'canChangeRecordsPerPage' => true,
					'fileName' => $cleanUrl,
				];
				$pager = new Pager($options);
				$interface->assign('pageLinks', $pager->getLinks());
			}
			if (isset($_REQUEST['objectAction']) && $_REQUEST['objectAction'] == 'exportSelectedToCSV') {
				$allObjects = $this->getAllObjects('1', min(1000, $numObjects));
				$exportObjects = [];
				if (isset($_REQUEST['selectedObject'])) {
					foreach ($_REQUEST['selectedObject'] as $k => $v) {
						if ($v == 'on') {
							$exportObjects[] = $allObjects[$k];
						}
					}
				}
				Exporter::downloadCSV($this->getToolName(), 'Admin/propertiesListCSV.tpl', $structure, $exportObjects);
			} else { // Display on screen
				$interface->assign('dataList', $allObjects);
				if (count($allObjects) < 2) {
					$interface->assign('canCompare', false);
				}
				$interface->assign('showQuickFilterOnPropertiesList', $this->showQuickFilterOnPropertiesList());
				$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/propertiesList.tpl');
			}
		}
	}

	function copyObject($structure) : void {
		global $interface;
		if ($this->canCopy()) {
			//Viewing an individual record, get the id to show
			if (isset($_SERVER['HTTP_REFERER'])) {
				$_SESSION['redirect_location'] = $_SERVER['HTTP_REFERER'];
			} else {
				unset($_SESSION['redirect_location']);
			}
			if (isset($_REQUEST['sourceId'])) {
				$id = $_REQUEST['sourceId'];
				$existingObject = $this->getExistingObjectById($id);
				if ($existingObject != null) {
					if ($existingObject->canActiveUserEdit()) {
						$existingObject->loadCopyableSubObjects();
						$interface->assign('objectName', $existingObject->__toString());
						$existingObject->unsetUniquenessFields();
						if (method_exists($existingObject, 'label')) {
							$interface->assign('objectName', $existingObject->label());
						}
						$this->activeObject = $existingObject;
						$interface->assign('sourceId', $id);
					} else {
						$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
						return;
					}
				} else {
					$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
					return;
				}
			} else {
				$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
				return;
			}
			$interface->assign('object', $existingObject);
			//Check to see if the request should be multipart/form-data
			$contentType = DataObjectUtil::getFormContentType($structure);
			$interface->assign('contentType', $contentType);

			$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($existingObject));
			$interface->assign('returnToListUrl', $this->getReturnToListUrl());
			$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/objectEditor.tpl');
		}else {
			$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
		}
	}

	function getCopyOptions() : void {
		global $interface;
		if ($this->canCopy()) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			$id = $_REQUEST['id'] ?? '';
			if (empty($id) || $id < 0) {
				//There is nothing to copy from, currently returns nothing
				$results = [
					'success' => false,
					'message' => translate([
						'text' => "You must provide an object id to copy from.",
						'isAdminFacing' => true,
					]),
				];
			}else{
				$curObject = $this->getExistingObjectById($id);
				$copyOptions = $this->getCopyOptionsFormStructure($curObject);

				$interface->assign('id', '');
				$interface->assign('sourceId', $id);
				$interface->assign('submitUrl', "/{$this->getModule()}/{$this->getToolName()}?objectAction=copy&sourceId=$id");
				$interface->assign('structure', $copyOptions);
				$interface->assign('ajaxFormId', 'copyOptions');

				$optionsForm = $interface->fetch('DataObjectUtil/ajaxForm.tpl');

				$results = [
					'success' => true,
					'title' => translate([
						'text' => "Copy Options",
						'isAdminFacing' => true,
					]),
					'modalBody' => $optionsForm,
					'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return $(\'#copyOptions\').submit();">' . translate([
							'text' => 'Continue',
							'isPublicFacing' => true,
						]) . '</a>',
				];

			}
			echo json_encode($results);
			die();

		}else{
			$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
		}
	}

	function showShareForm() : void {
		global $interface;
		if (isset($_REQUEST['sourceId'])) {
			$id = $_REQUEST['sourceId'];
			$existingObject = $this->getExistingObjectById($id);
			if ($existingObject != null) {
				if ($existingObject->canActiveUserEdit()) {

					$interface->assign('objectName', $existingObject->__toString());
					$interface->assign('id', $id);
					$interface->assign('returnToListUrl', $this->getReturnToListUrl());
					$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/shareForm.tpl');
				} else {
					$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
				}
			} else {
				$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
			}
		} else {
			$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
		}
	}

	function shareToCommunity() : void {
		global $interface;
		if (UserAccount::userHasPermission('Share Content with Community')) {
			if (isset($_REQUEST['sourceId'])) {
				$id = $_REQUEST['sourceId'];
				$existingObject = $this->getExistingObjectById($id);
				if ($existingObject != null) {
					if ($existingObject->canActiveUserEdit()) {

						$interface->assign('objectName', $existingObject->__toString());
						$interface->assign('id', $id);
						$existingObject->prepareForSharingToCommunity();
						$jsonRepresentation = $existingObject->getJSONString(false, true);

						//Submit to the greenhouse
						require_once ROOT_DIR . '/sys/SystemVariables.php';
						$systemVariables = SystemVariables::getSystemVariables();
						if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
							require_once ROOT_DIR . '/sys/CurlWrapper.php';
							$curl = new CurlWrapper();
							$body = [
								'name' => $_REQUEST['contentName'],
								'type' => $this->getObjectType(),
								'description' => $_REQUEST['contentDescription'],
								'sharedFrom' => $interface->getVariable('librarySystemName'),
								'sharedByUserName' => UserAccount::getActiveUserObj()->displayName,
								'data' => $jsonRepresentation,
							];
							$curl->curlPostPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=addSharedContent', $body);
							header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id");
							exit;
						} else {
							AspenError::raiseError('A community sharing URL has not been configured. You can configure it in System Variables.');
						}

					} else {
						$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
					}
				} else {
					$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
				}
			} else {
				$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
			}
		} else {
			$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
		}
	}

	function importFromCommunity($structure) : void {
		global $interface;
		if (UserAccount::userHasPermission('Import Content from Community')) {
			if (isset($_REQUEST['sourceId'])) {
				$sourceId = $_REQUEST['sourceId'];
				$objectType = $_REQUEST['objectType'];

				//Get the raw data from the greenhouse
				require_once ROOT_DIR . '/sys/SystemVariables.php';
				$systemVariables = SystemVariables::getSystemVariables();
				if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
					require_once ROOT_DIR . '/sys/CurlWrapper.php';
					$curl = new CurlWrapper();
					$response = $curl->curlGetPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=getSharedContent&objectType=' . $objectType . '&objectId=' . $sourceId);
					$jsonResponse = json_decode($response);
					if ($jsonResponse->success) {
						$rawData = json_decode($jsonResponse->rawData, true);

						$objectType = $this->getObjectType();
						/** @var DataObject $newObject */
						$newObject = new $objectType;
						$newObject->loadFromJSON($rawData, [], 'doNotSave');
						$interface->assign('objectName', $newObject->__toString());
						$newObject->unsetUniquenessFields();
						if (method_exists($newObject, 'label')) {
							$interface->assign('objectName', $newObject->label());
						}
						$this->activeObject = $newObject;

						$interface->assign('object', $newObject);
						//Check to see if the request should be multipart/form-data
						$contentType = DataObjectUtil::getFormContentType($structure);
						$interface->assign('contentType', $contentType);

						$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($newObject));
						$interface->assign('returnToListUrl', $this->getReturnToListUrl());
						$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/objectEditor.tpl');
					} else {
						$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
					}
				}
			} else {
				$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
			}
		} else {
			$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
		}
	}

	function viewIndividualObject($structure) : void {
		global $interface;
		//Viewing an individual record, get the id to show
		if (isset($_SERVER['HTTP_REFERER'])) {
			$_SESSION['redirect_location'] = $_SERVER['HTTP_REFERER'];
		} else {
			unset($_SESSION['redirect_location']);
		}
		// Capture and assign all context parameters for preserving list state.
		$contextParams = [];
		$preservedParams = ['returnPage', 'page', 'pageSize', 'sort', 'filterType', 'filterValue', 'filterValue2'];
		foreach ($preservedParams as $param) {
			if (!empty($_REQUEST[$param])) {
				$contextParams[$param] = $_REQUEST[$param];
				$interface->assign($param, $_REQUEST[$param]);
			}
		}
		// Build context parameter string for edit links.
		$contextQueryString = '';
		if (!empty($contextParams)) {
			$queryParts = [];
			foreach ($contextParams as $param => $value) {
				if (is_array($value)) {
					foreach ($value as $key => $val) {
						$queryParts[] = urlencode($param) . '[' . urlencode($key) . ']=' . urlencode($val);
					}
				} else {
					$queryParts[] = urlencode($param) . '=' . urlencode($value);
				}
			}
			$contextQueryString = '&' . implode('&', $queryParts);
		}
		$interface->assign('contextParams', $contextQueryString);

		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			$existingObject = $this->getExistingObjectById($id);
			if ($existingObject != null) {
				if ($existingObject->canActiveUserEdit()) {
					$interface->assign('id', $id);
					$user = UserAccount::getActiveUserObj();
					$interface->assign('patronIdCheck', $user->id);
					if (method_exists($existingObject, 'label')) {
						$interface->assign('objectName', $existingObject->label());
					}
					$this->activeObject = $existingObject;

					$interface->assign('canDelete', $this->canDelete() && $existingObject->canActiveUserDelete());
				} else {
					$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/noPermission.tpl');
					return;
				}
			} else {
				$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/invalidObject.tpl');
				return;
			}
		} else {
			$existingObject = null;
		}
		if (!isset($_REQUEST['id']) || $existingObject == null) {
			$objectType = $this->getObjectType();
			$existingObject = new $objectType;
			$this->setDefaultValues($existingObject, $structure);
			$isNewObject = true;
		} else {
			$structure = $existingObject->updateStructureForEditingObject($structure);
			if ($this->objectAction == 'save') {
				//We are redisplaying an object that failed to save. Set values provided using setDefaultValues
				$this->setDefaultValues($existingObject, $structure);
			}

			$interface->assign('structure', $structure);
			$isNewObject = false;
		}

		DataObjectUtil::preprocessOneToManySubObjects($existingObject, $structure);
		$interface->assign('object', $existingObject);
		//Check to see if the request should be multipart/form-data
		$contentType = DataObjectUtil::getFormContentType($structure);
		$interface->assign('contentType', $contentType);

		//ADM-7 Do not apply field locks to new records.
		if (!$isNewObject) {
			$userCanChangeFieldLocks = $this->userCanChangeFieldLocks();
			$interface->assign('userCanChangeFieldLocks', $userCanChangeFieldLocks);
			$fieldLocks = $this->getFieldLocks();
			$interface->assign('fieldLocks', $fieldLocks);
			if (!empty($fieldLocks)) {
				$structure = $this->applyFieldLocksToObjectStructure($structure, $fieldLocks, $userCanChangeFieldLocks);
				$interface->assign('structure', $structure);
			}

			$id = $_REQUEST['id'];
			$isRecordLocked = $this->isRecordLocked($id);
			$interface->assign('isRecordLocked', $isRecordLocked);
			if ($isRecordLocked && !$this->userCanChangeRecordLocks()) {
				$structure = $this->makeObjectStructureReadOnly($structure);
				$interface->assign('structure', $structure);
			}
		}

		$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($existingObject));
		$interface->assign('returnToListUrl', $this->getReturnToListUrl());
		$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/objectEditor.tpl');
	}

	#[NoReturn]
	function editObject($objectAction, $structure) : void {
		$errorOccurred = false;
		$user = UserAccount::getLoggedInUser();
		$samePatron = true;
		if (isset($_REQUEST['patronIdCheck']) && $_REQUEST['patronIdCheck'] != 0 && $_REQUEST['patronIdCheck'] != $user->id){
			$samePatron = false;
		}
		if ($samePatron) {
			// Save or create a new object.
			$id = $_REQUEST['id'] ?? '';
			if (empty($id) || $id < 0) {
				$curObject = $this->insertObject($structure);
				if (!$curObject) {
					// The session lastError is updated.
					$errorOccurred = true;
				} else {
					$id = $curObject->getPrimaryKeyValue();
				}
			} else {
				$curObject = $this->getExistingObjectById($id);
				if (!is_null($curObject)) {
					if ($objectAction == 'save') {
						$user = UserAccount::getActiveUserObj();
						$fieldLocks = $this->getFieldLocks();
						if (UserAccount::userHasPermission('Lock Administration Fields')) {
							$fieldLocks = null;
						}
						$structure = $curObject->updateStructureForEditingObject($structure);
						$validationResults = $this->updateFromUI($curObject, $structure, $fieldLocks);
						if ($validationResults['validatedOk']) {
							// Always save since has changes does not check sub objects for changes (which it should).
							$ret = $curObject->update($this->getContext());
							if ($ret === false) {
								if ($curObject->getLastError()) {
									$errorDescription = $curObject->getLastError();
								} else {
									$errorDescription = translate([
										'text' => 'An unknown error has occurred. Please try again later.',
										'isPublicFacing' => true,
									]);
								}
								$user->updateMessage = "An error occurred updating {$this->getObjectType()} with ID of $id: <br/>$errorDescription";
								$user->updateMessageIsError = true;
								$user->update();
								$errorOccurred = true;
							}
						} else {
							$errorDescription = implode('<br/>', $validationResults['errors']);
							$user->updateMessage = "An error occurred validating {$this->getObjectType()} with ID of $id: <br/>$errorDescription";
							$user->updateMessageIsError = true;
							$user->update();
							$errorOccurred = true;
						}
					} elseif ($objectAction == 'delete') {
						$deletionBlockInfo = $curObject->getDeletionBlockInformation($structure);
						if (!$deletionBlockInfo['preventDeletion']) {
							$ret = $curObject->delete();
							if ($ret == 0) {
								$user = UserAccount::getActiveUserObj();
								$user->updateMessage = "Unable to delete {$this->getObjectType()} with id of $id";
								$user->updateMessageIsError = true;
								$user->update();
								$errorOccurred = true;
							}
						}else{
							$user = UserAccount::getActiveUserObj();
							$user->updateMessage = $deletionBlockInfo['message'];
							$user->updateMessageIsError = true;
							$user->update();
							$errorOccurred = true;
						}
					}
				} else {
					$user = UserAccount::getActiveUserObj();
					$user->updateMessage = "An error occurred, could not find {$this->getObjectType()} with id of $id";
					$user->updateMessageIsError = true;
					$user->update();
					$errorOccurred = true;
				}
			}
			if (!empty($id) && $objectAction == 'saveCopy') {
				if (!empty($_REQUEST['sourceId'])) {
					$sourceId = $_REQUEST['sourceId'];
					$curObject->finishCopy($sourceId);
				}
			}
		} else {
			$errorOccurred = true;
			global $interface;
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle400');
			require_once ROOT_DIR . "/services/Error/Handle400.php";
			$interface->assign('errorMessage', translate(['text' => 'Invalid user information', 'isAdminFacing'=>true]));
			$actionClass = new Error_Handle400();
			$actionClass->launch();
			die();
		}
		if (empty($id) && $errorOccurred) {
			if ($this->canAddNew()) {
				//Don't do a redirect here, display the data with the user's inputs

				//Display the error message
				global $interface;
				$user = UserAccount::getActiveUserObj();
				$interface->assign('updateMessage', $user->updateMessage);
				$interface->assign('updateMessageIsError', $user->updateMessageIsError);
				$user->updateMessage = '';
				$user->updateMessageIsError = 0;
				$user->update();

				//Redisplay the form
				$this->viewIndividualObject($structure);
				return;
				//header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=addNew");
			} else {
				header("Location: /{$this->getModule()}/{$this->getToolName()}");
			}
		} elseif ($errorOccurred) {
			//An error occurred updating an existing object, redisplay the form so the user doesn't lose their changes
			//Display the error message
			global $interface;
			$user = UserAccount::getActiveUserObj();
			$interface->assign('updateMessage', $user->updateMessage);
			$interface->assign('updateMessageIsError', $user->updateMessageIsError);
			$user->updateMessage = '';
			$user->updateMessageIsError = 0;
			$user->update();

			//Redisplay the form
			$this->viewIndividualObject($structure);
			return;
		} elseif (isset($_REQUEST['submitStay'])) {
			$editUrl = "/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id";
			// Preserve all context parameters for submitStay to maintain list context.
			$preservedParams = ['page', 'pageSize', 'sort', 'filterType', 'filterValue', 'filterValue2'];
			$contextParams = [];
			foreach ($preservedParams as $param) {
				if (!empty($_REQUEST[$param])) {
					$contextParams[$param] = $_REQUEST[$param];
				}
			}
			if (!empty($contextParams)) {
				$editUrl .= '&' . http_build_query($contextParams);
			}
			header("Location: " . $editUrl);
		} elseif (isset($_REQUEST['submitAddAnother'])) {
			header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=addNew");
		} else {
			$redirectLocation = $this->getRedirectLocation($objectAction, $curObject);
			if (is_null($redirectLocation)) {
				// Try to use context-aware return URL first.
				$returnToListUrl = $this->getReturnToListUrl();
				if (!empty($returnToListUrl) && $objectAction != 'delete') {
					header("Location: " . $returnToListUrl);
				} elseif (isset($_SESSION['redirect_location']) && $objectAction != 'delete') {
					header("Location: " . $_SESSION['redirect_location']);
				} else {
					header("Location: /{$this->getModule()}/{$this->getToolName()}");
				}
			} else {
				header("Location: $redirectLocation");
			}
		}
		die();
	}

	/**
	 * @param string $objectAction
	 * @param DataObject $curObject
	 * @return string|null
	 */
	function getRedirectLocation(string $objectAction, DataObject $curObject) : ?string {
		return null;
	}

	function showReturnToList() : bool {
		return true;
	}

	function getModule(): string {
		return 'Admin';
	}
	public function canAddNew() : bool {
		return true;
	}

	public function canCopy() : bool {
		return false;
	}

	public function hasCopyOptions() : bool {
		return false;
	}

	public function canEdit() : bool {
		return true;
	}

	public function canCompare() : bool {
		return true;
	}

	public function canDelete() : bool {
		return true;
	}

	public function canBatchDelete(): bool {
		return $this->getNumObjects() > 1 && UserAccount::userHasPermission('Batch Delete');
	}

	/**
	 * Determines if the user can delete all objects of this type at once based upon batch delete.
	 * Purpose: Override it in the deriving class to prevent the display of the "Delete All" button.
	 * @return bool True if the user is allowed to delete all objects, false otherwise.
	 */
	public function canDeleteAll(): bool
	{
		return $this->canBatchDelete();
	}

	public function canBatchEdit() : bool {
		return $this->getNumObjects() > 1;
	}

	public function canExportToCSV() : bool {
		return true;
	}

	public function canSort(): bool {
		return $this->getNumObjects() > 3;
	}

	function getSort() : string {
		$user = UserAccount::getActiveUserObj();
		if (isset($_REQUEST['sort'])) {
			$sort = $_REQUEST['sort'];
			PageDefaults::updatePageDefaultsForUser($user->id, $this->getModule(), $this->getToolName(), null, null, $sort);
		} else {
			$pageDefaults = PageDefaults::getPageDefaultsForUser($user->id, $this->getModule(), $this->getToolName(), null);
			if ($pageDefaults == null || is_null($pageDefaults->pageSort)) {
				$sort = $this->getDefaultSort();
			}else{
				$sort = $pageDefaults->pageSort;
			}
		}
		return $sort;
	}

	abstract function getDefaultSort(): string;

	public function canFilter($objectStructure) : bool {
		$filterFields = $this->getFilterFields($objectStructure);
		return ($this->getNumObjects() > 3) || (count($this->getAppliedFilters($filterFields)) > 0);
	}

	public function customListActions() : array {
		return [];
	}

	/**
	 * Returns the template for the custom list panel if any
	 *
	 * @return string
	 */
	public function getCustomListPanel() : string {
		return '';
	}

	/**
	 * @param ?DataObject $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getListInstructions() : string {
		return $this->getInstructions();
	}

	function getInitializationJs(): string {
		return '';
	}

	function getInitializationAdditionalJs() : string {
		return '';
	}

	function getOnSubmissionJS() : string {
		return '';
	}

	function compareObjects($structure) : void {
		global $interface;
		$object1 = null;
		$object2 = null;
		if (count($_REQUEST['selectedObject']) == 2) {
			$index = 1;
			foreach ($_REQUEST['selectedObject'] as $id => $value) {
				if ($index == 1) {
					$object1 = $this->getExistingObjectById($id);
					if ($this->showEditButtonsInCompareAndHistoryViews()) {
						$object1EditUrl = "/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id";
						$interface->assign('object1EditUrl', $object1EditUrl);
					}
					$index = 2;
				} else {
					$object2 = $this->getExistingObjectById($id);
					if ($this->showEditButtonsInCompareAndHistoryViews()) {
						$object2EditUrl = "/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id";
						$interface->assign('object2EditUrl', $object2EditUrl);
					}
				}
			}
			if ($object1 == null || $object2 == null) {
				$interface->assign('error', 'Could not load object from the database');
			} else {
				$properties = [];
				$structure = $this->applyPermissionsToObjectStructure($structure);
				$properties = $this->compareObjectProperties($structure, $object1, $object2, $properties, '');
				$interface->assign('properties', $properties);
			}
		} else {
			$interface->assign('error', 'Please select two objects to compare');
		}

		$interface->assign('showEditButtonsInCompareAndHistoryViews', $this->showEditButtonsInCompareAndHistoryViews());
		$interface->assign('showReturnToList', $this->getToolName() === 'ObjectRestorations');
		$interface->assign('module', $this->getModule());
		$interface->assign('toolName', $this->getToolName());
		$interface->assign('returnToListUrl', $this->getReturnToListUrl());
		$interface->setTemplate(ROOT_DIR . '/interface/themes/responsive/Admin/compareObjects.tpl');
	}

	function getLinkedObjectNotifications() : ?string {
		$result = null;
		if (!empty($_REQUEST['id'])){
			if ($_REQUEST['action'] == 'WebResources') {
				require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
				$placard = new Placard();
				$placard->sourceId = $_REQUEST['id'];
				if ($placard->find(true)) {
					$url = "/Admin/Placards?objectAction=edit&id=" . $placard->id;
					$result = translate([
							'text' => 'This Web Resource is linked to the Placard ',
							'isAdminFacing' => true,
						]) . "<a href='$url'>$placard->title</a>";
					if ($placard->isCustomized){
						$result .= translate([
							'text' => ', which has been customized.',
							'isAdminFacing' => true,
						]);
					}
				}
			} else if ($_REQUEST['action'] == 'Placards') {
				require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
				$placard = new Placard();
				$placard->id = $_REQUEST['id'];
				if ($placard->find(true)) {
					if ($placard->sourceType == 'web_resource') {
						require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
						$webResource = new WebResource();
						$webResource->id = $placard->sourceId;
						if ($webResource->find(true)) {
							$url = "/WebBuilder/WebResources?objectAction=edit&id=" . $webResource->id;
							$result = translate([
									'text' => 'This Placard is linked to the Web Resource ',
									'isAdminFacing' => true,
								]) . "<a href='$url'>$webResource->name</a>";
							if ($placard->isCustomized){
								$result .= translate([
										'text' => ' and has been customized.',
										'isAdminFacing' => true,
									]);
							}
						}
					}
				}
			}
		}
		return $result;
	}

	function getEditFormInstructions() : ?string {
		global $activeLanguage;
		$result = null;
		if (!empty($_REQUEST['id'])) {
			if ($_REQUEST['action'] == 'Events') {
				require_once ROOT_DIR . '/sys/Events/Event.php';
				$event = new Event();
				$event->id = $_REQUEST['id'];
				if ($event->find(true)) {
					require_once ROOT_DIR . '/sys/Events/EventType.php';
					$eventType = new EventType();
					$eventType->id = $event->eventTypeId;
					if ($eventType->find(true)) {
						$result = $eventType->getTextBlockTranslation('editFormInstructions', $activeLanguage->code);
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @param $structure
	 * @param DataObject|null $object1
	 * @param DataObject|null $object2
	 * @param array $properties
	 * @param string|null $sectionName
	 * @return array
	 */
	protected function compareObjectProperties($structure, ?DataObject $object1, ?DataObject $object2, array $properties, ?string $sectionName): array {
		foreach ($structure as $property) {
			if ($property['type'] == 'section') {
				$label = $property['label'];
				if (!empty($sectionName)) {
					$label = $sectionName . ': ' . $label;
				}
				$properties = $this->compareObjectProperties($property['properties'], $object1, $object2, $properties, $label);
			} else {
				$propertyName = $property['property'];
				$uniqueProperty = $property['uniqueProperty'] ?? ($propertyName == $this->getPrimaryKeyColumn());
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

	function getPropertyValue($property, $propertyValue, $propertyType) {
		if ($propertyType == 'oneToMany' || $propertyType == 'multiSelect') {
			if ($propertyValue == null) {
				return translate([
					'text' => 'None Selected',
					'isAdminFacing' => true,
				]);
			} else {
				if ($propertyType == 'multiSelect' && isset($property['values']) && is_array($propertyValue)) {
					$displayValues = [];
					foreach ($propertyValue as $id) {
						if (isset($property['values'][$id])) {
							$displayValues[] = $property['values'][$id];
						} else {
							$displayValues[] = $id;
						}
					}
					return implode('<br/>', $displayValues);
				} else {
					return implode('<br/>', $propertyValue);
				}
			}
		} elseif ($propertyType == 'enum') {
			return $property['values'][$propertyValue] ?? translate([
				'text' => 'Undefined Value %1%',
				1 => $propertyValue,
				'isAdminFacing' => true,
			]);
		} elseif ($propertyType == 'html') {
			if ($propertyValue === null || $propertyValue === '') {
				return '';
			}
			// Strip all HTML tags and collapse whitespace.
			$plain = strip_tags($propertyValue);
			return trim(preg_replace('/\s+/u', ' ', $plain));
		} else {
			return is_array($propertyValue) ? implode(', ', $propertyValue) : (is_object($propertyValue) ? (string)$propertyValue : $propertyValue);
		}
	}

	function showHistory(): void {
		$id = $_REQUEST['id'] ?? '';
		if (empty($id) || $id < 0) {
			AspenError::raiseError('Please select an object to display its history.');
		} else {
			global $interface;
			$curObject = $this->getExistingObjectById($id);
			if (!$curObject) {
				AspenError::raiseError('The object with ID ' . $id . ' does not exist.');
			}
			$interface->assign('curObject', $curObject);
			$interface->assign('id', $id);
			$displayNameColumn = $curObject->__displayNameColumn;
			$primaryField = $curObject->__primaryKey;
			$objectHistory = [];
			require_once ROOT_DIR . '/sys/DB/DataObjectHistory.php';
			$historyEntry = new DataObjectHistory();
			$historyEntry->objectType = get_class($curObject);
			$historyEntry->objectId = $curObject->$primaryField;
			if ($displayNameColumn != null) {
				$title = translate([
					"text" => 'History for %1%',
					1 => $curObject->$displayNameColumn,
					"isAdminFacing" => true,
				]);
			} else {
				$title = translate([
					"text" => 'History for %1%',
					1 => $historyEntry->objectType . ' - ' . $historyEntry->objectId,
					"isAdminFacing" => true,
				]);
			}
			$interface->assign('title', $title);
			$historyEntry->orderBy('changeDate desc');
			$historyEntry->find();
			while ($historyEntry->fetch()) {
				$objectHistory[] = clone $historyEntry;
			}
			$interface->assign('objectHistory', $objectHistory);
			$interface->assign('showEditButtonsInCompareAndHistoryViews', $this->showEditButtonsInCompareAndHistoryViews());
			$interface->assign('module', $this->getModule());
			$interface->assign('toolName', $this->getToolName());
			$interface->assign('returnToListUrl', $this->getReturnToListUrl());
			$this->display('../Admin/objectHistory.tpl', $title);
			exit();
		}
	}

	public function getBatchUpdateFields($structure) : array {
		$batchFormatFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $field) {
			$this->addFieldToBatchUpdateFieldsArray($batchFormatFields, $field);
		}
		ksort($batchFormatFields);
		return $batchFormatFields;
	}

	public function getSortableFields($structure) : array {
		$sortFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $field) {
			$this->addFieldToSortableFieldsArray($sortFields, $field);
		}
		ksort($sortFields);
		return $sortFields;
	}

	private function addFieldToSortableFieldsArray(&$sortableFields, $field) : void {
		if ($field['type'] == 'section') {
			foreach ($field['properties'] as $subField) {
				$this->addFieldToSortableFieldsArray($sortableFields, $subField);
			}
		} else {
			$canSort = !isset($field['canSort']) || ($field['canSort'] == true);
			if ($canSort && in_array($field['type'], [
					'checkbox',
					'label',
					'date',
					'timestamp',
					'enum',
					'currency',
					'text',
					'integer',
					'calculatedInteger',
					'calculatedBoolean',
					'email',
					'url'
				])) {
				$sortableFields[$field['label']] = $field;
			}
		}
	}

	public function getFilterFields($structure) : array {
		$filterFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $field) {
			$this->addFieldToFilterFieldsArray($filterFields, $field);
		}
		ksort($filterFields);
		return $filterFields;
	}

	private function addFieldToFilterFieldsArray(&$filterFields, $field) : void {
		if ($field['type'] == 'section') {
			foreach ($field['properties'] as $subField) {
				$this->addFieldToFilterFieldsArray($filterFields, $subField);
			}
		} else {
			$canSort = !isset($field['canSort']) || ($field['canSort'] == true);
			if ($canSort && in_array($field['type'], [
					'checkbox',
					'label',
					'date',
					'timestamp',
					'enum',
					'currency',
					'text',
					'integer',
					'calculatedInteger',
					'calculatedBoolean',
					'email',
					'url',
				])) {
				$filterFields[$field['property']] = $field;
				if ($field['type'] == 'enum') {
					$filterFields[$field['property']]['values'] = [
							'all_values' => translate([
								'text' => 'All Values',
								'isAdminFacing' => true,
								'inAttribute' => 'true',
							]),
						] + $filterFields[$field['property']]['values'];
				}
			}
		}
	}

	public function getAppliedFilters($filterFields) : array {
		$appliedFilters = [];
		if (isset($_REQUEST['filterType'])) {
			foreach ($_REQUEST['filterType'] as $fieldName => $value) {
				$appliedFilters[$fieldName] = [
					'fieldName' => $fieldName,
					'filterType' => $value,
					'filterValue' => $_REQUEST['filterValue'][$fieldName] ?? '',
					'filterValue2' => $_REQUEST['filterValue2'][$fieldName] ?? '',
					'field' => $filterFields[$fieldName],
				];
			}
		}
		if (count($appliedFilters) == 0) {
			$appliedFilters = $this->getDefaultFilters($filterFields);
		}
		return $appliedFilters;
	}

	function getDefaultFilters(array $filterFields): array {
		return [];
	}

	function applyFilters(DataObject $object) : void {
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$filterFields = $this->getFilterFields($object::getObjectStructure($this->getContext()));
		$appliedFilters = $this->getAppliedFilters($filterFields);
		foreach ($appliedFilters as $fieldName => $filter) {
			if ($filter['field']['type'] != "calculatedInteger" || !empty($filter->filterValue)) {
				$this->applyFilter($object, $fieldName, $filter);
			}
		}
	}

	function applyFilter(DataObject $object, string $fieldName, array $filter) : void {
		$table = empty($filter['field']['filterOmitTablename']) ? "$object->__table." : '';
		$addAsHaving = in_array($filter['field']['type'], ['calculatedInteger', 'calculatedBoolean']);
		$fullFieldName = "$table$fieldName";
		$filterType = $filter['filterType'];
		switch ($filterType) {
			case 'matches':
				if ($filter['field']['type'] == 'enum' && $filter['filterValue'] == 'all_values') {
					//Skip this value
					return;
				}
				if ($filter['filterValue'] == '') {
					$object->whereAdd("$fullFieldName IS NULL OR $fullFieldName = ''");
				} else {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName = {$filter['filterValue']}");
					} else {
						$object->$fieldName = $filter['filterValue'];
					}
				}
				break;
			case 'contains':
				$object->whereAdd("$fullFieldName like " . $object->escape('%' . $filter['filterValue'] . '%'));
				break;
			case 'startsWith':
				$object->whereAdd("$fullFieldName like " . $object->escape($filter['filterValue'] . '%'));
				break;
			case 'beforeTime':
			case 'afterTime':
			case 'betweenTimes':
				$fieldValueLower = null;
				$fieldValueUpper = null;
				if($filter['field']['type'] == 'timestamp') {
					$fieldValueLower = strtotime($filter['filterValue']);
					$fieldValueUpper = strtotime($filter['filterValue2']);
				} elseif ($filter['field']['type'] == 'date') {
					$fieldValueLower = 'DATE(' . $object->escape($filter['filterValue']) . ')';
					$fieldValueUpper = 'DATE(' . $object->escape($filter['filterValue2']) . ')';
				}

				if ($fieldValueLower && ($filterType == 'afterTime' || $filterType == 'betweenTimes')) {
					$object->whereAdd("$fullFieldName" . ' > ' . $fieldValueLower);
				}
				if ($fieldValueUpper && ($filterType == 'beforeTime' || $filterType == 'betweenTimes')) {
					$object->whereAdd("$fullFieldName" . ' < ' . $fieldValueUpper);
				}
				break;
			case 'lessThan':
				$fieldValue = $filter['filterValue2'];
				if ($fieldValue !== false) {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName < $fieldValue");
					} else {
						$object->whereAdd("$fullFieldName < $fieldValue");
					}
				}
				break;
			case 'lessThanOrEqual':
				$fieldValue = $filter['filterValue2'];
				if ($fieldValue !== false) {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName <= $fieldValue");
					} else {
						$object->whereAdd("$fullFieldName <= $fieldValue");
					}
				}
				break;
			case 'equals':
				$fieldValue = $filter['filterValue'];
				if ($fieldValue !== false) {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName = $fieldValue");
					} else {
						$object->whereAdd("$fullFieldName = $fieldValue");
					}
				}
				break;
			case 'greaterThan':
				$fieldValue = $filter['filterValue'];
				if ($fieldValue !== false) {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName > $fieldValue");
					} else {
						$object->whereAdd("$fullFieldName > $fieldValue");
					}
				}
				break;
			case 'greaterThanOrEqual':
				$fieldValue = $filter['filterValue'];
				if ($fieldValue !== false) {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName  >= $fieldValue");
					} else {
						$object->whereAdd("$fullFieldName >= $fieldValue");
					}
				}
				break;
			case 'between':
				$fieldValue = $filter['filterValue'];
				if ($fieldValue !== false) {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName  >= $fieldValue");
					} else {
						$object->whereAdd("$fullFieldName >= $fieldValue");
					}
				}
				$fieldValue2 = $filter['filterValue2'];
				if ($fieldValue2 !== false) {
					if ($addAsHaving) {
						$object->havingAdd("$fieldName <= $fieldValue2");
					} else {
						$object->whereAdd("$fullFieldName <= $fieldValue2");
					}
				}
				break;
		}
	}

	protected function applySpecialFilter($object, $filter, $filterOptions = []) : void {
		$defaults = [
			'sourceTable' => '',
			'sourceField' => '',
			'targetClass' => '',
			'targetField' => '',
			'getCompareValueMethod' => '',
			'compareFormat' => 'default',
		];
		$options = array_merge($defaults, $filterOptions);

		$matchingValues = [];

		if (($filter['filterType'] == 'matches' && $filter['filterValue'] == '')) {
			$object->whereAdd("{$options['sourceField']} IS NULL");
			return;
		}

		if (($filter['filterType'] === 'beforeTime' || $filter['filterType'] === 'afterTime' || $filter['filterType'] === 'betweenTimes') && empty($filter['filterValue']) && empty($filter['filterValue2'])) {
			return;
		}

		$targetObject = new $options['targetClass']();
		$targetObject->whereAdd("{$options['targetField']} IN (SELECT DISTINCT {$options['sourceField']} FROM {$options['sourceTable']} WHERE {$options['sourceField']} IS NOT NULL)");
		$targetObject->find();

		while ($targetObject->fetch()) {
			if ($options['compareFormat'] == 'nameWithBarcode') {
				$compareValue = $targetObject->{$options['getCompareValueMethod']}() . ' (' . $targetObject->getBarcode() . ')';
			} elseif ($options['compareFormat'] == 'property') {
				$compareValue = $targetObject->{$options['getCompareValueMethod']};
			} elseif ($options['compareFormat'] == 'boolean') {
				$compareValue = $targetObject->{$options['getCompareValueMethod']} ? 'true' : 'false';
			} else {
				$compareValue = $targetObject->{$options['getCompareValueMethod']}();
			}

			if ($filter['filterType'] == 'matches') {
				if (strcasecmp($compareValue, $filter['filterValue']) == 0) {
					$matchingValues[] = $targetObject->{$options['targetField']};
				}
			} elseif ($filter['filterType'] == 'contains') {
				if (stripos($compareValue, $filter['filterValue']) !== false) {
					$matchingValues[] = $targetObject->{$options['targetField']};
				}
			} elseif ($filter['filterType'] == 'startsWith') {
				if (stripos($compareValue, $filter['filterValue']) === 0) {
					$matchingValues[] = $targetObject->{$options['targetField']};
				}
			} elseif ($filter['filterType'] == 'beforeTime') {
				$filterTime = strtotime($filter['filterValue2']);
				if ($filterTime !== false && $compareValue < $filterTime) {
					$matchingValues[] = $targetObject->{$options['targetField']};
				}
			} elseif ($filter['filterType'] == 'afterTime') {
				$filterTime = strtotime($filter['filterValue']);
				if ($filterTime !== false && $compareValue > $filterTime) {
					$matchingValues[] = $targetObject->{$options['targetField']};
				}
			} elseif ($filter['filterType'] == 'betweenTimes') {
				$startTime = strtotime($filter['filterValue']);
				$endTime = strtotime($filter['filterValue2']);
				if ($startTime !== false && $endTime !== false && $compareValue >= $startTime && $compareValue <= $endTime) {
					$matchingValues[] = $targetObject->{$options['targetField']};
				}
			}
		}

		if (empty($matchingValues)) {
			$object->whereAdd("{$options['sourceField']} = ''");
		} else {
			$escapedValues = array_map(function($value) {
				return "'" . addslashes($value) . "'";
			}, $matchingValues);
			$object->whereAdd("{$options['sourceField']} IN (" . implode(',', $escapedValues) . ")");
		}
	}

	private function addFieldToBatchUpdateFieldsArray(&$batchFormatFields, $field) : void {
		if ($field['type'] == 'section') {
			foreach ($field['properties'] as $subField) {
				$this->addFieldToBatchUpdateFieldsArray($batchFormatFields, $subField);
			}
		} else {
			$canBatchUpdate = !isset($field['canBatchUpdate']) || ($field['canBatchUpdate'] == true);
			$readOnly = isset($field['readOnly']) && ($field['readOnly'] == true);
			if ($canBatchUpdate && !$readOnly && in_array($field['type'], [
					'checkbox',
					'enum',
					'currency',
					'text',
					'integer',
					'email',
					'url',
					'timestamp',
				])) {
				$batchFormatFields[$field['label']] = $field;
			}
		}
	}

	protected function getDefaultRecordsPerPage() : int {
		return 25;
	}

	protected function showQuickFilterOnPropertiesList() : bool {
		return false;
	}

	protected function supportsPagination() : bool {
		return true;
	}

	protected function limitToObjectsForLibrary($object, $linkObjectType, $linkProperty) : bool {
		$userHasExistingObjects = true;
		$libraries = Library::getLibraryList(true);
		$objectsForLibrary = [];
		foreach ($libraries as $libraryId => $displayName) {
			$linkObject = new $linkObjectType();
			$linkObject->libraryId = $libraryId;
			$linkObject->find();
			while ($linkObject->fetch()) {
				$objectsForLibrary[] = $linkObject->$linkProperty;
			}
		}
		if (count($objectsForLibrary) > 0) {
			$object->whereAddIn('id', $objectsForLibrary, false);
		} else {
			$userHasExistingObjects = false;
		}
		return $userHasExistingObjects;
	}

	protected function applyPermissionsToObjectStructure(array $structure) : array {
		foreach ($structure as $key => &$property) {
			if ($property['type'] == 'section') {
				$property['properties'] = $this->applyPermissionsToObjectStructure($property['properties']);
				if (array_key_exists('permissions', $property)) {
					if (!UserAccount::userHasPermission($property['permissions'])) {
						unset($structure[$key]);
					}
				}
				if (count($property['properties']) == 0) {
					unset($structure[$key]);
				}
			} else {
				if (array_key_exists('permissions', $property)) {
					//Verify the correct permission exists for the user
					if (!UserAccount::userHasPermission($property['permissions'])) {
						unset($structure[$key]);
					}
				}
				if (array_key_exists('editPermissions', $property)) {
					//Verify the correct permission exists for the user
					if (!UserAccount::userHasPermission($property['editPermissions'])) {
						$property['type'] = 'label';
					}
				}
			}
		}
		return $structure;
	}

	protected function showHistoryLinks() : bool {
		return true;
	}

	/**
	 * Control whether edit buttons should be shown in history and compare views.
	 * Purpose: Override it to hide edit functionality when appropriate.
	 * @return bool True if edit buttons are enabled, false otherwise.
	 */
	protected function showEditButtonsInCompareAndHistoryViews(): bool {
		return true;
	}

	public function getContext(): string {
		return $this->objectAction ?? '';
	}

	public function canShareToCommunity() : bool {
		return false;
	}

	public function canFetchFromCommunity() : bool {
		return false;
	}

	public function hasCommunityConnection() : bool {
		//Send the translation to the greenhouse
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
			return true;
		} else {
			return false;
		}
	}

	public function allowSearchingProperties($structure) : bool {
		$hasSections = false;
		foreach ($structure as $property) {
			if ($property['type'] == 'section') {
				$hasSections = true;
			}
		}
		return $hasSections || count($structure) > 6;
	}

	private ?array $_fieldLocks = null;
	public function getFieldLocks() : array {
		if ($this->_fieldLocks == null) {
			$this->_fieldLocks = [];
			try {
				require_once ROOT_DIR . '/sys/Administration/FieldLock.php';
				$fieldLock = new FieldLock();
				$fieldLock->module = $this->getModule();
				$fieldLock->toolName = $this->getToolName();
				$this->_fieldLocks = $fieldLock->fetchAll('id', 'field');
			}catch (Exception) {
				//Nothing since it's not setup yet
			}
		}

		return $this->_fieldLocks;
	}

	public function userCanChangeFieldLocks() : bool {
		return UserAccount::userHasPermission('Lock Administration Fields');
	}

	public function applyFieldLocksToObjectStructure($structure, $fieldLocks, $userCanChangeFieldLocks){
		foreach ($structure as &$property) {
			if ($property['type'] == 'section') {
				$property['properties'] = $this->applyFieldLocksToObjectStructure($property['properties'], $fieldLocks, $userCanChangeFieldLocks);
			} else {
				//Any field can be locked by default, but can override by setting canLock to false
				if (array_key_exists('canLock', $property) && !$property['canLock']) {
					$canLockField = false;
				}else{
					$canLockField = true;
				}
				if ($canLockField) {
					if (in_array($property['property'], $fieldLocks)) {
						$property['locked'] = true;
						if (!$userCanChangeFieldLocks) {
							$property['readOnly'] = true;
						}
					} else {
						$property['locked'] = false;
					}
				}
			}
		}
		return $structure;
	}

	public function makeObjectStructureReadOnly($structure) : array {
		foreach ($structure as &$property) {
			if ($property['type'] == 'section') {
				$property['properties'] = $this->makeObjectStructureReadOnly($property['properties']);
			} else {
				$property['readOnly'] = true;
			}
		}
		return $structure;
	}

	public function getLockedRecordIds() : array {
		try {
			require_once ROOT_DIR . '/sys/Administration/RecordLock.php';
			$recordLock = new RecordLock();
			$recordLock->module = $this->getModule();
			$recordLock->toolName = $this->getToolName();
			return $recordLock->fetchAll('recordId', 'recordId');
		}catch (Exception) {
			//Nothing since it's not setup yet
			return [];
		}
	}

	public function isRecordLocked(int $id) : bool {
		try {
			require_once ROOT_DIR . '/sys/Administration/RecordLock.php';
			$recordLock = new RecordLock();
			$recordLock->module = $this->getModule();
			$recordLock->toolName = $this->getToolName();
			$recordLock->recordId = $id;
			return $recordLock->count() == 1;
		}catch (Exception) {
			//Nothing since it's not setup yet
			return false;
		}
	}

	public function userCanChangeRecordLocks() : bool {
		return UserAccount::userHasPermission('Lock Administration Records');
	}

	public function getCopyNotes() : string {
		return ''
;	}

	public function getCopyOptionsFormStructure($activeObject) : array {
		return [];
	}

	function getHiddenFields() : array {
		return [];
	}

	public function hasMultiStepAddNew() : bool {
		return false;
	}

	/**
	 * Builds a return URL that preserves the user's complete list context including page number,
	 * page size, sorting, and filters. Context parameters are primarily obtained from the current
	 * request for form submissions and direct navigation, with HTTP referer parsing as fallback.
	 * Includes scrollToId parameter to maintain scroll position when returning to the list.
	 *
	 * @return string The constructed return URL with preserved context parameters.
	 */
	public function getReturnToListUrl(): string {
		$baseUrl = '/' . $this->getModule() . '/' . $this->getToolName() . '?objectAction=list';
		$listParams = [];
		$preservedParams = ['page', 'pageSize', 'sort', 'filterType', 'filterValue', 'filterValue2'];

		// First priority: use parameters from current request for form submissions and direct navigation.
		foreach ($preservedParams as $param) {
			if (!empty($_REQUEST[$param])) {
				$listParams[$param] = $_REQUEST[$param];
			}
		}

		// If no context parameters found in request, fall back to HTTP referer parsing.
		if (empty($listParams) && !empty($_SERVER['HTTP_REFERER'])) {
			$refererUrl = parse_url($_SERVER['HTTP_REFERER']);
			if (!empty($refererUrl['query'])) {
				parse_str($refererUrl['query'], $refererParams);
				foreach ($preservedParams as $param) {
					if (isset($refererParams[$param])) {
						$listParams[$param] = $refererParams[$param];
					}
				}
			}
		}

		if (!empty($listParams)) {
			$baseUrl .= '&' . http_build_query($listParams);
		}

		// Always add scroll ID to return to the object.
		$currentParams = parse_url($baseUrl, PHP_URL_QUERY);
		parse_str($currentParams ?: '', $existingParams);

		if (empty($existingParams['scrollToId'])) {
			if (!empty($_REQUEST['scrollToId'])) {
				$baseUrl .= (!str_contains($baseUrl, '?') ? '?' : '&') . 'scrollToId=' . $_REQUEST['scrollToId'];
			} elseif (!empty($_REQUEST['id'])) {
				$baseUrl .= (!str_contains($baseUrl, '?') ? '?' : '&') . 'scrollToId=' . $_REQUEST['id'];
			}
		}
		return $baseUrl;
	}

	public function hasRecordLocking() : bool {
		return false;
	}

	public function lockRecord($structure) : void {
		if (!empty($_REQUEST['id'])) {
			require_once ROOT_DIR . '/sys/Administration/RecordLock.php';
			$recordLock = new RecordLock();
			$recordLock->module = $this->getModule();
			$recordLock->toolName = $this->getToolName();
			$recordLock->recordId = $_REQUEST['id'];
			$recordLock->insert();
		}

		$this->viewIndividualObject($structure);
	}

	public function unlockRecord($structure) : void {
		if (!empty($_REQUEST['id'])) {
			require_once ROOT_DIR . '/sys/Administration/RecordLock.php';
			$recordLock = new RecordLock();
			$recordLock->module = $this->getModule();
			$recordLock->toolName = $this->getToolName();
			$recordLock->recordId = $_REQUEST['id'];
			$recordLock->delete(true);
		}

		$this->viewIndividualObject($structure);
	}

	public function getRequiredModule() : ?string {
		return null;
	}

	function canView(): bool {
		return UserAccount::userHasPermission($this->getViewPermissions());
	}

	abstract function getViewPermissions() : array;
}
