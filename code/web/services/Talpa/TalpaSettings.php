<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Talpa/TalpaSettings.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';


class Talpa_TalpaSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'TalpaSettings';
	}

	function getToolName(): string {
		return 'TalpaSettings';
	}

	function getModule(): string {
		return 'Talpa';
	}

	function getPageTitle(): string {
		return 'Talpa Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new TalpaSettings();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return TalpaSettings::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Talpa/Talpa Settings', 'Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'third_party_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Third Party Enrichment API Keys');
	}

	/**
	 * Override the editObject method to trigger the talpa recalculation cron when the setting is enabled
	 */
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
					// Check if the insert was successful and if the sendCatalogItemsToTalpaOnSave setting is enabled
					if ($curObject && isset($_REQUEST['sendCatalogItemsToTalpaOnSave']) && $_REQUEST['sendCatalogItemsToTalpaOnSave'] == 'on') {
						$this->triggerTalpaRecalculation();
					}
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
							} else {
								// Check if the update was successful and if the sendCatalogItemsToTalpaOnSave setting is enabled
								if (isset($_REQUEST['sendCatalogItemsToTalpaOnSave']) && $_REQUEST['sendCatalogItemsToTalpaOnSave'] == 'on') {
									$this->triggerTalpaRecalculation();
								}
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
	 * Trigger the talpa recalculation process directly
	 */
	private function triggerTalpaRecalculation() {
		$result = SystemUtils::startBackgroundProcess("talpaRecalculationCron");

		$activeUser = UserAccount::getActiveUserObj();
		if ($result['success']) {
			$activeUser->__set('updateMessage', translate([
				'text' => 'Successfully started background process to send holdings to Talpa',
				1 => $result['backgroundProcessId'],
				'isAdminFacing' => true
			]));
		} else {
			$activeUser->__set('updateMessage', translate([
					'text' => 'Could not start background process to recalculate Talpa data.',
					'isAdminFacing' => true
				]) . "<br/> " . $result['message']);
		}
		$activeUser->update();
	}
}

