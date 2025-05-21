<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

abstract class ObjectEditor extends Admin_Admin {
	protected $activeObject;
	protected $objectAction;

	function launch() {
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

		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
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

		$interface->assign('objectType', $this->getObjectType());
		$interface->assign('toolName', $this->getToolName());
		$interface->assign('initializationJs', $this->getInitializationJs());
		$interface->assign('initializationAdditionalJs', $this->getInitializationAdditionalJs());
		$interface->assign('onSubmissionJS', $this->getOnSubmissionJS());
		$interface->assign('allowSearchingProperties', $this->allowSearchingProperties($structure));

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
			$this->getCopyOptions($structure);
		} elseif ($objectAction == 'shareForm') {
			$this->showShareForm($structure);
		} elseif ($objectAction == 'shareToCommunity') {
			$this->shareToCommunity($structure);
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
		$this->display($interface->getTemplate(), $this->getPageTitle());
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
	abstract function getAllObjects($page, $recordsPerPage): array;

	protected $_numObjects = null;

	/**
	 * Get a count of the number of objects so we can paginate as needed
	 */
	function getNumObjects(): int {
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
	function insertObject($structure) {
		$objectType = $this->getObjectType();
		/** @var DataObject $newObject */
		$newObject = new $objectType;
		//Check to see if we are getting default values from the
		$validationResults = $this->updateFromUI($newObject, $structure, null);
		if ($validationResults['validatedOk']) {
			$ret = $newObject->insert($this->getContext());
			if (!$ret) {
				global $logger;
				if ($newObject->getLastError()) {
					$errorDescription = $newObject->getLastError();
				} else {
					$errorDescription = translate([
						'text' => 'Unknown Error',
						'isPublicFacing' => true,
					]);
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

	function setDefaultValues($object, $structure) {
		foreach ($structure as $property) {
			$propertyName = $property['property'];
			if (isset($_REQUEST[$propertyName])) {
				$object->$propertyName = $_REQUEST[$propertyName];
			} elseif (isset($property['default'])) {
				$object->$propertyName = $property['default'];
			} elseif ($property['type'] == 'section') {
				$this->setDefaultValues($object, $property['properties']);
			}
		}
	}

	function updateFromUI($object, $structure, $fieldLocks) {
		require_once ROOT_DIR . '/sys/DataObjectUtil.php';
		DataObjectUtil::updateFromUI($object, $structure, $fieldLocks);
		return DataObjectUtil::validateObject($structure, $object);
	}

	function viewExistingObjects($structure) {
		global $interface;
		$interface->assign('instructions', $this->getListInstructions());
		$interface->assign('sortableFields', $this->getSortableFields($structure));
		$interface->assign('sort', $this->getSort());
		$filterFields = $this->getFilterFields($structure);
		$interface->assign('filterFields', $filterFields);
		$interface->assign('appliedFilters', $this->getAppliedFilters($filterFields));
		$interface->assign('hiddenFields', $this->getHiddenFields());

		$numObjects = $this->getNumObjects();
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		if (!is_numeric($page)) {
			$page = 1;
		}
		$recordsPerPage = isset($_REQUEST['pageSize']) ? $_REQUEST['pageSize'] : $this->getDefaultRecordsPerPage();
		if (isset($_REQUEST['objectAction']) && $_REQUEST['objectAction'] == 'exportToCSV') { // Export [all, filtered] to CSV
			$allObjects = $this->getAllObjects('1', min(1000, $numObjects));
			Exporter::downloadCSV($this->getToolName(), 'Admin/propertiesListCSV.tpl', $structure, $allObjects);
		} else { // Export Selected to CSV OR Display on screen
			$allObjects = $this->getAllObjects($page, $recordsPerPage);
			if ($this->supportsPagination()) {
				$options = [
					'totalItems' => $numObjects,
					'perPage' => $recordsPerPage,
					'canChangeRecordsPerPage' => true,
					'canJumpToPage' => true,
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
				$interface->setTemplate('../Admin/propertiesList.tpl');
			}
		}
	}

	function copyObject($structure) {
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
						$interface->setTemplate('../Admin/noPermission.tpl');
						return;
					}
				} else {
					$interface->setTemplate('../Admin/invalidObject.tpl');
					return;
				}
			} else {
				$interface->setTemplate('../Admin/invalidObject.tpl');
				return;
			}
			$interface->assign('object', $existingObject);
			//Check to see if the request should be multipart/form-data
			$contentType = DataObjectUtil::getFormContentType($structure);
			$interface->assign('contentType', $contentType);

			$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($existingObject));
			$interface->setTemplate('../Admin/objectEditor.tpl');
		}else {
			$interface->setTemplate('../Admin/noPermission.tpl');
		}
	}

	function getCopyOptions() {
		global $interface;
		if ($this->canCopy()) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
			if (empty($id) || $id < 0) {

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
				echo json_encode($results);
				die();
			}

		}else{
			$interface->setTemplate('../Admin/noPermission.tpl');
		}
	}

	function showShareForm($structure) {
		global $interface;
		if (isset($_REQUEST['sourceId'])) {
			$id = $_REQUEST['sourceId'];
			$existingObject = $this->getExistingObjectById($id);
			if ($existingObject != null) {
				if ($existingObject->canActiveUserEdit()) {

					$interface->assign('objectName', $existingObject->__toString());
					$interface->assign('id', $id);
					$interface->setTemplate('../Admin/shareForm.tpl');
				} else {
					$interface->setTemplate('../Admin/noPermission.tpl');
				}
			} else {
				$interface->setTemplate('../Admin/invalidObject.tpl');
			}
		} else {
			$interface->setTemplate('../Admin/invalidObject.tpl');
		}
	}

	function shareToCommunity($structure) {
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
							$response = $curl->curlPostPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=addSharedContent', $body);
							header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id");
							exit;
						} else {
							$error = new AspenError('A community sharing URL has not been configured. You can configure it in System Variables.');
							$interface->setTemplate('../error.tpl');
						}

					} else {
						$interface->setTemplate('../Admin/noPermission.tpl');
					}
				} else {
					$interface->setTemplate('../Admin/invalidObject.tpl');
				}
			} else {
				$interface->setTemplate('../Admin/invalidObject.tpl');
			}
		} else {
			$interface->setTemplate('../Admin/noPermission.tpl');
		}
	}

	function importFromCommunity($structure) {
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
						$interface->setTemplate('../Admin/objectEditor.tpl');
					} else {
						$interface->setTemplate('../Admin/invalidObject.tpl');
					}
				}
			} else {
				$interface->setTemplate('../Admin/invalidObject.tpl');
			}
		} else {
			$interface->setTemplate('../Admin/noPermission.tpl');
		}
	}

	function viewIndividualObject($structure) {
		global $interface;
		//Viewing an individual record, get the id to show
		if (isset($_SERVER['HTTP_REFERER'])) {
			$_SESSION['redirect_location'] = $_SERVER['HTTP_REFERER'];
		} else {
			unset($_SESSION['redirect_location']);
		}
		if (isset($_REQUEST['id'])) {
			$id = $_REQUEST['id'];
			$existingObject = $this->getExistingObjectById($id);
			if ($existingObject != null) {
				if ($existingObject->canActiveUserEdit()) {
					$interface->assign('id', $id);
					$user = UserAccount::getActiveUserObj();
					$interface->assign('patronIdCheck', $user->id);
;					if (method_exists($existingObject, 'label')) {
						$interface->assign('objectName', $existingObject->label());
					}
					$this->activeObject = $existingObject;

					$interface->assign('canDelete', $existingObject->canActiveUserDelete());
				} else {
					$interface->setTemplate('../Admin/noPermission.tpl');
					return;
				}
			} else {
				$interface->setTemplate('../Admin/invalidObject.tpl');
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
			$interface->assign('structure', $structure);
			$isNewObject = false;
		}
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
		}

		$interface->assign('additionalObjectActions', $this->getAdditionalObjectActions($existingObject));
		$interface->setTemplate('../Admin/objectEditor.tpl');
	}

	function getFormContentType($structure, $contentType = null) {
		if ($contentType != null) {
			return $contentType;
		}
		//Check to see if the request should be multipart/form-data
		foreach ($structure as $property) {
			if ($property['type'] == 'section') {
				$contentType = DataObjectUtil::getFormContentType($property['properties'], $contentType);
			} elseif ($property['type'] == 'image' || $property['type'] == 'file') {
				$contentType = 'multipart/form-data';
			}
		}
		return $contentType;
	}

	function editObject($objectAction, $structure) {
		$errorOccurred = false;
		$user = UserAccount::getLoggedInUser();
		$samePatron = true;
		if (isset($_REQUEST['patronIdCheck']) && $_REQUEST['patronIdCheck'] != 0 && $_REQUEST['patronIdCheck'] != $user->id){
			$samePatron = false;
		}
		if ($samePatron) {
			//Save or create a new object
			$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
			if (empty($id) || $id < 0) {
				//Insert a new record
				$curObject = $this->insertObject($structure);
				if ($curObject == false) {
					//The session lastError is updated
					$errorOccurred = true;
				} else {
					$id = $curObject->getPrimaryKeyValue();
				}
			} else {
				//Work with an existing record
				$curObject = $this->getExistingObjectById($id);
				if (!is_null($curObject)) {
					if ($objectAction == 'save') {
						//Update the object
						$user = UserAccount::getActiveUserObj();
						$fieldLocks = $this->getFieldLocks();
						if (UserAccount::userHasPermission('Lock Administration Fields')) {
							$fieldLocks = null;
						}
						$structure = $curObject->updateStructureForEditingObject($structure);
						$validationResults = $this->updateFromUI($curObject, $structure, $fieldLocks);
						if ($validationResults['validatedOk']) {
							//Always save since has changes does not check sub objects for changes (which it should)
							$ret = $curObject->update($this->getContext());
							if ($ret === false) {
								if ($curObject->getLastError()) {
									$errorDescription = $curObject->getLastError();
								} else {
									$errorDescription = translate([
										'text' => 'Unknown Error',
										'isPublicFacing' => true,
									]);
								}
								$user->updateMessage = "An error occurred updating {$this->getObjectType()} with id of $id <br/>{$errorDescription}";
								$user->updateMessageIsError = true;
								$user->update();
								$errorOccurred = true;
							}
						} else {
							$errorDescription = implode('<br/>', $validationResults['errors']);
							$user->updateMessage = "An error occurred validating {$this->getObjectType()} with id of $id <br/>{$errorDescription}";
							$user->updateMessageIsError = true;
							$user->update();
							$errorOccurred = true;
						}
					} elseif ($objectAction == 'delete') {
						//Delete the record
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
					//Couldn't find the record.  Something went haywire.
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
				header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=addNew");
			} else {
				header("Location: /{$this->getModule()}/{$this->getToolName()}");
			}
		} elseif (isset($_REQUEST['submitStay']) || $errorOccurred) {
			header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id");
		} elseif (isset($_REQUEST['submitAddAnother'])) {
			header("Location: /{$this->getModule()}/{$this->getToolName()}?objectAction=addNew");
		} else {
			$redirectLocation = $this->getRedirectLocation($objectAction, $curObject);
			if (is_null($redirectLocation)) {
				if (isset($_SESSION['redirect_location']) && $objectAction != 'delete') {
					header("Location: " . $_SESSION['redirect_location']);
				} else {
					header("Location: /{$this->getModule()}/{$this->getToolName()}");
				}
			} else {
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
	function getRedirectLocation(/** @noinspection PhpUnusedParameterInspection */ $objectAction, $curObject) {
		return null;
	}

	function showReturnToList() {
		return true;
	}

	function getModule(): string {
		return 'Admin';
	}
	public function canAddNew() {
		return true;
	}

	public function canCopy() {
		return false;
	}

	public function hasCopyOptions() {
		return false;
	}

	public function canEdit(DataObject $object) {
		return true;
	}

	public function canCompare() {
		return true;
	}

	public function canDelete() {
		return true;
	}

	public function canBatchDelete() {
		return $this->getNumObjects() > 1 && UserAccount::userHasPermission('Batch Delete');
	}

	public function canBatchEdit() {
		return $this->getNumObjects() > 1;
	}

	public function canExportToCSV() {
		return true;
	}

	public function canSort(): bool {
		return $this->getNumObjects() > 3;
	}

	function getSort() {
		return isset($_REQUEST['sort']) ? $_REQUEST['sort'] : $this->getDefaultSort();
	}

	abstract function getDefaultSort(): string;

	public function canFilter($objectStructure) {
		$filterFields = $this->getFilterFields($objectStructure);
		return ($this->getNumObjects() > 3) || (count($this->getAppliedFilters($filterFields)) > 0);
	}

	public function customListActions() {
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
	 * @param DataObject $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getListInstructions() {
		return $this->getInstructions();
	}

	function getInitializationJs(): string {
		return '';
	}

	function getInitializationAdditionalJs() {
		return '';
	}

	function getOnSubmissionJS() {
		return '';
	}

	function compareObjects($structure) {
		global $interface;
		$object1 = null;
		$object2 = null;
		if (count($_REQUEST['selectedObject']) == 2) {
			$index = 1;
			foreach ($_REQUEST['selectedObject'] as $id => $value) {
				if ($index == 1) {
					$object1 = $this->getExistingObjectById($id);
					$object1EditUrl = "/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id";
					$interface->assign('object1EditUrl', $object1EditUrl);
					$index = 2;
				} else {
					$object2 = $this->getExistingObjectById($id);
					$object2EditUrl = "/{$this->getModule()}/{$this->getToolName()}?objectAction=edit&id=$id";
					$interface->assign('object2EditUrl', $object2EditUrl);
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

		$interface->setTemplate('../Admin/compareObjects.tpl');
	}

	function getLinkedObjectNotifications() {
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
			}
			else if ($_REQUEST['action'] == 'Placards') {
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

	/**
	 * @param $structure
	 * @param DataObject|null $object1
	 * @param DataObject|null $object2
	 * @param array $properties
	 * @param string|null $sectionName
	 * @return array
	 */
	protected function compareObjectProperties($structure, ?DataObject $object1, ?DataObject $object2, array $properties, $sectionName): array {
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

	function getPropertyValue($property, $propertyValue, $propertyType) {
		if ($propertyType == 'oneToMany' || $propertyType == 'multiSelect') {
			if ($propertyValue == null) {
				return 'null';
			} else {
				return implode('<br/>', $propertyValue);
			}
		} elseif ($propertyType == 'enum') {
			if (isset($property['values'][$propertyValue])) {
				return $property['values'][$propertyValue];
			} else {
				return translate([
					'text' => 'Undefined value %1%',
					1 => $propertyValue,
					'isAdminFacing' => true,
				]);
			}
		} else {
			return is_array($propertyValue) ? implode(', ', $propertyValue) : (is_object($propertyValue) ? (string)$propertyValue : $propertyValue);
		}
	}

	function showHistory() {
		$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
		if (empty($id) || $id < 0) {
			AspenError::raiseError('Please select an object to show history for');
		} else {
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
			$this->display('../Admin/objectHistory.tpl', $title);
			exit();
		}
	}

	public function getBatchUpdateFields($structure) {
		$batchFormatFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $field) {
			$this->addFieldToBatchUpdateFieldsArray($batchFormatFields, $field);
		}
		ksort($batchFormatFields);
		return $batchFormatFields;
	}

	public function getSortableFields($structure) {
		$sortFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $fieldName => $field) {
			$this->addFieldToSortableFieldsArray($sortFields, $field);
		}
		ksort($sortFields);
		return $sortFields;
	}

	private function addFieldToSortableFieldsArray(&$sortableFields, $field) {
		if ($field['type'] == 'section') {
			foreach ($field['properties'] as $subFieldName => $subField) {
				$this->addFieldToSortableFieldsArray($batchFormatFields, $subField);
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
					'email',
					'url',
				])) {
				$sortableFields[$field['label']] = $field;
			}
		}
	}

	public function getFilterFields($structure) {
		$filterFields = [];
		$structure = $this->applyPermissionsToObjectStructure($structure);
		foreach ($structure as $fieldName => $field) {
			$this->addFieldToFilterFieldsArray($filterFields, $field);
		}
		ksort($filterFields);
		return $filterFields;
	}

	private function addFieldToFilterFieldsArray(&$filterFields, $field) {
		if ($field['type'] == 'section') {
			foreach ($field['properties'] as $subFieldName => $subField) {
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

	public function getAppliedFilters($filterFields) {
		$appliedFilters = [];
		if (isset($_REQUEST['filterType'])) {
			foreach ($_REQUEST['filterType'] as $fieldName => $value) {
				$appliedFilters[$fieldName] = [
					'fieldName' => $fieldName,
					'filterType' => $value,
					'filterValue' => isset($_REQUEST['filterValue'][$fieldName]) ? $_REQUEST['filterValue'][$fieldName] : '',
					'filterValue2' => isset($_REQUEST['filterValue2'][$fieldName]) ? $_REQUEST['filterValue2'][$fieldName] : '',
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

	function applyFilters(DataObject $object) {
		$filterFields = $this->getFilterFields($object::getObjectStructure($this->getContext()));
		$appliedFilters = $this->getAppliedFilters($filterFields);
		foreach ($appliedFilters as $fieldName => $filter) {
			$this->applyFilter($object, $fieldName, $filter);
		}
	}

	function applyFilter(DataObject $object, string $fieldName, array $filter) {
		if ($filter['filterType'] == 'matches') {
			if ($filter['field']['type'] == 'enum' && $filter['filterValue'] == 'all_values') {
				//Skip this value
				return;
			}
			if ($filter['filterValue'] == '') {
				$object->whereAdd("$fieldName IS NULL OR $fieldName = ''");
			} else {
				$object->$fieldName = $filter['filterValue'];
			}
		} elseif ($filter['filterType'] == 'contains') {
			$object->whereAdd($fieldName . ' like ' . $object->escape('%' . $filter['filterValue'] . '%'));
		} elseif ($filter['filterType'] == 'startsWith') {
			$object->whereAdd($fieldName . ' like ' . $object->escape($filter['filterValue'] . '%'));
		} elseif ($filter['filterType'] == 'beforeTime') {
			$fieldValue = strtotime($filter['filterValue2']);
			if ($fieldValue !== false) {
				$object->whereAdd($fieldName . ' < ' . $fieldValue);
			}
		} elseif ($filter['filterType'] == 'afterTime') {
			$fieldValue = strtotime($filter['filterValue']);
			if ($fieldValue !== false) {
				$object->whereAdd($fieldName . ' > ' . $fieldValue);
			}
		} elseif ($filter['filterType'] == 'betweenTimes') {
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

	private function addFieldToBatchUpdateFieldsArray(&$batchFormatFields, $field) {
		if ($field['type'] == 'section') {
			foreach ($field['properties'] as $subFieldName => $subField) {
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

	protected function getDefaultRecordsPerPage() {
		return 25;
	}

	protected function showQuickFilterOnPropertiesList() {
		return false;
	}

	protected function supportsPagination() {
		return true;
	}

	protected function limitToObjectsForLibrary(&$object, $linkObjectType, $linkProperty) {
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

	protected function applyPermissionsToObjectStructure(array $structure) {
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

	protected function showHistoryLinks() {
		return true;
	}

	public function getContext(): string {
		return $this->objectAction ?? '';
	}

	public function canShareToCommunity() {
		return false;
	}

	public function canFetchFromCommunity() {
		return false;
	}

	public function hasCommunityConnection() {
		//Send the translation to the greenhouse
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
			return true;
		} else {
			return false;
		}
	}

	public function allowSearchingProperties($structure) {
		$hasSections = false;
		foreach ($structure as $property) {
			if ($property['type'] == 'section') {
				$hasSections = true;
			}
		}
		return $hasSections || count($structure) > 6;
	}

	public function getFieldLocks() : array {
		$fieldLocks = [];
		try {
			require_once ROOT_DIR . '/sys/Administration/FieldLock.php';
			$fieldLock = new FieldLock();
			$fieldLock->module = $this->getModule();
			$fieldLock->toolName = $this->getToolName();
			$fieldLocks = $fieldLock->fetchAll('id', 'field');
		}catch (Exception $e) {
			//Nothing since it's not setup yet
		}
		return $fieldLocks;
	}

	public function userCanChangeFieldLocks() : bool {
		return UserAccount::userHasPermission('Lock Administration Fields');
	}

	public function applyFieldLocksToObjectStructure($structure, $fieldLocks, $userCanChangeFieldLocks){
		foreach ($structure as $key => &$property) {
			if ($property['type'] == 'section') {
				$property['properties'] = $this->applyFieldLocksToObjectStructure($property['properties'], $fieldLocks, $userCanChangeFieldLocks);
			} else {
				//Any field can be locked by default, but
				if (array_key_exists('canLock', $property) && $property['canLock'] == false) {
					$canLockField = true;
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

	public function getCopyNotes() {
		return ''
;	}

	public function getCopyOptionsFormStructure($activeObject) {
		return [];
	}

	function getHiddenFields() {
		return [];
	}

	public function hasMultiStepAddNew() : bool {
		return false;
	}
}