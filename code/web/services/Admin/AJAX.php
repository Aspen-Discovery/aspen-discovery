<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Admin_AJAX extends JSON_Action {

	/** @noinspection PhpUnused */
	function getReindexNotes() : array {
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/Indexing/ReindexLogEntry.php';
		$reindexProcess = new ReindexLogEntry();
		$reindexProcess->id = $id;
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'title' => '',
			'modalBody' => '',
			'modalButtons' => '',
		];
		if ($reindexProcess->find(true)) {
			$results['title'] = translate([
				'text' => "Reindex Notes",
				'isAdminFacing' => true,
			]);
			if (strlen(trim($reindexProcess->notes)) == 0) {
				$results['modalBody'] = translate([
					'text' => "No notes have been entered yet",
					'isAdminFacing' => true,
				]);
			} else {
				$results['modalBody'] = "<div class='helpText'>$reindexProcess->notes</div>";
			}
		} else {
			$results['title'] = translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]);
			$results['modalBody'] = translate([
				'text' => "We could not find a reindex entry with that id.  No notes available.",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getCronProcessNotes() : array {
		$id = $_REQUEST['id'];
		$cronProcess = new CronProcessLogEntry();
		$cronProcess->id = $id;
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'title' => '',
			'modalBody' => '',
			'modalButtons' => "",
		];
		if ($cronProcess->find(true)) {
			$results['title'] = translate([
				'text' => "%1% Notes",
				1 => $cronProcess->processName,
				'isAdminFacing' => true,
			]);
			if (strlen($cronProcess->notes) == 0) {
				$results['modalBody'] = translate([
					'text' => "No notes have been entered for this process",
					'isAdminFacing' => true,
				]);
			} else {
				$results['modalBody'] = "<div class='helpText'>$cronProcess->notes</div>";
			}
		} else {
			$results['title'] = "Error";
			$results['modalBody'] = translate([
				'text' => "We could not find a process with that id.  No notes available.",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getCronNotes() : array {
		$id = $_REQUEST['id'];
		$cronLog = new CronLogEntry();
		$cronLog->id = $id;

		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'title' => '',
			'modalBody' => '',
			'modalButtons' => "",
		];
		if ($cronLog->find(true)) {
			$results['title'] = translate([
				'text' => "Cron Process %1% Notes",
				1 => $cronLog->id,
				'isAdminFacing' => true,
			]);
			if (strlen($cronLog->notes) == 0) {
				$results['modalBody'] = translate([
					'text' => "No notes have been entered for this cron run",
					'isAdminFacing' => true,
				]);
			} else {
				$results['modalBody'] = "<div class='helpText'>$cronLog->notes</div>";
			}
		} else {
			$results['title'] = translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]);
			$results['modalBody'] = translate([
				'text' => "We could not find a cron entry with that id.  No notes available.",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getExtractNotes() : array {
		$id = $_REQUEST['id'];
		$source = $_REQUEST['source'];
		$extractLog = null;
		if ($source == 'overdrive') {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveExtractLogEntry.php';
			$extractLog = new OverDriveExtractLogEntry();
		} elseif ($source == 'ils') {
			require_once ROOT_DIR . '/sys/ILS/IlsExtractLogEntry.php';
			$extractLog = new IlsExtractLogEntry();
		} elseif ($source == 'hoopla') {
			require_once ROOT_DIR . '/sys/Hoopla/HooplaExportLogEntry.php';
			$extractLog = new HooplaExportLogEntry();
		} elseif ($source == 'cloud_library') {
			require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryExportLogEntry.php';
			$extractLog = new CloudLibraryExportLogEntry();
		} elseif ($source == 'axis360') {
			require_once ROOT_DIR . '/sys/Axis360/Axis360LogEntry.php';
			$extractLog = new Axis360LogEntry();
		} elseif ($source == 'sideload') {
			require_once ROOT_DIR . '/sys/Indexing/SideLoadLogEntry.php';
			$extractLog = new SideLoadLogEntry();
		} elseif ($source == 'website') {
			require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexLogEntry.php';
			$extractLog = new WebsiteIndexLogEntry();
		} elseif ($source == 'lists') {
			require_once ROOT_DIR . '/sys/UserLists/ListIndexingLogEntry.php';
			$extractLog = new ListIndexingLogEntry();
        } elseif ($source == 'course_reserves') {
            require_once ROOT_DIR . '/sys/CourseReserves/CourseReservesIndexingLogEntry.php';
            $extractLog = new CourseReservesIndexingLogEntry();
		} elseif ($source == 'series') {
			require_once ROOT_DIR . '/sys/Series/SeriesIndexingLogEntry.php';
			$extractLog = new SeriesIndexingLogEntry();
		} elseif ($source == 'nyt_updates') {
			require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';
			$extractLog = new NYTUpdateLogEntry();
		} elseif ($source == 'open_archives') {
			require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesExportLogEntry.php';
			$extractLog = new OpenArchivesExportLogEntry();
		} elseif ($source == 'palace_project') {
			require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectLogEntry.php';
			$extractLog = new PalaceProjectLogEntry();
		} elseif ($source == 'events') {
			require_once ROOT_DIR . '/sys/Events/EventsIndexingLogEntry.php';
			$extractLog = new EventsIndexingLogEntry();
		} elseif ($source == 'search_update') {
			require_once ROOT_DIR . '/sys/SearchUpdateLogEntry.php';
			$extractLog = new SearchUpdateLogEntry();
		}

		if ($extractLog == null) {
			$results['title'] = translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]);
			$results['modalBody'] = translate([
				'text' => "Invalid source for loading notes.",
				'isAdminFacing' => true,
			]);
		} else {
			$extractLog->id = $id;
			/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
			$results = [
				'title' => '',
				'modalBody' => '',
				'modalButtons' => "",
			];
			if ($extractLog->find(true)) {
				$results['title'] = translate([
					'text' => "Extract %1% Notes",
					1 => $extractLog->id,
					'isAdminFacing' => true,
				]);
				if (strlen($extractLog->notes) == 0) {
					$results['modalBody'] = translate([
						'text' => "No notes have been entered for this run",
						'isAdminFacing' => true,
					]);
				} else {
					$results['modalBody'] = "<div class='helpText'>$extractLog->notes</div>";
				}
			} else {
				$results['title'] = translate([
					'text' => "Error",
					'isAdminFacing' => true,
				]);
				$results['modalBody'] = translate([
					'text' => "We could not find an extract entry with that id.  No notes available.",
					'isAdminFacing' => true,
				]);
			}
		}


		return $results;
	}

	/** @noinspection PhpUnused */
	function getAddToSpotlightForm() : array {
		global $interface;
		// Display Page
		$interface->assign('id', strip_tags($_REQUEST['id']));
		$interface->assign('source', strip_tags($_REQUEST['source']));
		require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
		$collectionSpotlight = new CollectionSpotlight();
		if (!UserAccount::userHasPermission('Administer All Collection Spotlights')) {
			//Get all spotlights for the library
			$userLibrary = Library::getPatronHomeLibrary();
			$collectionSpotlight->libraryId = $userLibrary->libraryId;
		}
		$collectionSpotlight->orderBy('name');
		$existingCollectionSpotlights = $collectionSpotlight->fetchAll('id', 'name');

		$spotlightList = new CollectionSpotlightList();
		$spotlightList->find();
		$existingCollectionSpotlightLists = [];
		while ($spotlightList->fetch()) {
			$existingCollectionSpotlightLists[] = clone $spotlightList;
		}

		$interface->assign('existingCollectionSpotlightLists', $existingCollectionSpotlightLists);
		$interface->assign('existingCollectionSpotlights', $existingCollectionSpotlights);
		return [
			'title' => translate([
				"text" => 'Create a Spotlight',
				"isAdminFacing" => true,
			]),
			'modalBody' => $interface->fetch('Admin/addToSpotlightForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#addSpotlight\").submit();'>" . translate([
					"text" => "Create Spotlight",
					"isAdminFacing" => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function ungroupRecord() : array {
		/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
		$results = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
			$ungroupedRecord = new NonGroupedRecord();
			/** @var GroupedWorkSubDriver $record */
			$record = RecordDriverFactory::initRecordDriverById($_REQUEST['recordId']);
			if ($record instanceof AspenError) {
				$results['message'] = "Unable to find the record for this id";
			} else {
				[
					$source,
					$recordId,
				] = explode(':', $_REQUEST['recordId']);
				$ungroupedRecord->source = $source;
				$ungroupedRecord->recordId = $recordId;
				if ($ungroupedRecord->find(true)) {
					$results['success'] = true;
					$results['message'] = 'This record has already been ungrouped';
				} else {
					$ungroupedRecord->notes = '';
					$ungroupedRecord->insert();
					$groupedWork = new GroupedWork();
					$groupedWork->permanent_id = $record->getPermanentId();
					if ($groupedWork->find(true)) {
						$groupedWork->forceReindex(true);
					}
					$results['success'] = true;
					$results['message'] = 'This record has been ungrouped and the index will update shortly';
				}
			}

		} else {
			$results['message'] = translate([
				'text' => "You do not have the correct permissions for this operation",
				'isAdminFacing' => true,
			]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getReleaseNotes() : array {
		global $interface;
		$release = $_REQUEST['release'];
		$releaseNotesPath = ROOT_DIR . '/release_notes';
		$results = [
			'success' => false,
			'message' => 'Unknown error loading release notes',
		];
		if (!file_exists($releaseNotesPath . '/' . $release . '.MD')) {
			$results['message'] = 'Could not find notes for that release';
		} else {
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$releaseNotesFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/' . $release . '.MD'));
			$results = [
				'success' => true,
				'release' => $release,
				'releaseNotes' => $releaseNotesFormatted,
				'actionItems' => '',
				'testingSuggestions' => '',
			];
			if (file_exists($releaseNotesPath . '/' . $release . '_action_items.MD')) {
				$actionItemsFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/' . $release . '_action_items.MD'));
				$results['actionItems'] = $actionItemsFormatted;
				$interface->assign('actionItemsFormatted', $actionItemsFormatted);
			}
			if (file_exists($releaseNotesPath . '/' . $release . '_testing.MD')) {
				$testingSuggestionsFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/' . $release . '_testing.MD'));
				$results['testingSuggestions'] = $testingSuggestionsFormatted;
				$interface->assign('testingSuggestionsFormatted', $testingSuggestionsFormatted);
			}
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getCreateRoleForm() : array {
		global $interface;
		if (UserAccount::userHasPermission('Administer Permissions')) {

			$roles = [];
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$role->orderBy('name');
			$role->find();
			while ($role->fetch()) {
				$roles[$role->roleId]['roleId'] = $role->roleId;
				$roles[$role->roleId]['name'] = $role->name;
			}

			$interface->assign('permissionRoles', $roles);

			return [
				'title' => translate([
					'text' => 'Create New Role',
					'isAdminFacing' => true,
				]),
				'modalBody' => $interface->fetch('Admin/createRoleForm.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Admin.createRole();'>" . translate([
						'text' => "Create Role",
						'isAdminFacing' => true,
					]),
				"</button>",
			];
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => "Sorry, you don't have permissions to add roles",
					'isAdminFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function createRole() : array {
		if (UserAccount::userHasPermission('Administer Permissions')) {
			if (isset($_REQUEST['roleName'])) {
				$name = $_REQUEST['roleName'];
				$description = $_REQUEST['description'];
				$copyFrom = $_REQUEST['copyFrom'];
				require_once ROOT_DIR . '/sys/Administration/Role.php';
				$existingRole = new Role;
				$existingRole->name = $name;
				if ($existingRole->find(true)) {
					return [
						'success' => false,
						'message' => "$name already exists",
					];
				} else {
					$curPermissions = [];
					if ($copyFrom != "-1" || $copyFrom != -1) {
						$curRole = new Role();
						$curRole->roleId = $copyFrom;
						if ($curRole->find(true)) {
							$curPermissions = $curRole->getPermissions();
						}
					}

					$newRole = new Role();
					$newRole->name = $name;
					$newRole->description = $description;
					$newRole->insert();

					if (count($curPermissions) > 0) {
						foreach ($curPermissions as $curPermission) {
							require_once ROOT_DIR . '/sys/Administration/Permission.php';
							$permission = new Permission();
							$permission->name = $curPermission;
							if ($permission->find(true)) {
								require_once ROOT_DIR . '/sys/Administration/RolePermissions.php';
								$newPermission = new RolePermissions();
								$newPermission->roleId = $newRole->roleId;
								$newPermission->permissionId = $permission->id;
								$newPermission->insert();
							}
						}
					}

					return [
						'success' => true,
						'message' => "$name was created successfully",
						'roleId' => $newRole->roleId,
					];
				}
			} else {
				return [
					'success' => false,
					'message' => "The role name must be provided",
				];
			}
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => "Sorry, you don't have permissions to add roles",
					'isAdminFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function deleteRole() : array {
		if (UserAccount::userHasPermission('Administer Permissions')) {
			if (isset($_REQUEST['roleId']) && is_numeric($_REQUEST['roleId'])) {
				//Check to be sure the role is not used by anyone
				require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
				$usersForRole = new UserRoles();
				$usersForRole->roleId = $_REQUEST['roleId'];
				$usersForRole->find();
				if ($usersForRole->getNumResults() > 0) {
					return [
						'success' => false,
						'message' => "The role is in use by " . $usersForRole->getNumResults() . " users, please remove them from the role before deleting",
					];
				} else {
					$role = new Role();
					$role->roleId = $_REQUEST['roleId'];
					$role->delete();
					return [
						'success' => true,
						'message' => "The role was deleted successfully",
					];
				}
			} else {
				return [
					'success' => false,
					'message' => "The role to delete must be provided",
				];
			}
		} else {
			return [
				'success' => false,
				'message' => "Sorry, you don't have permissions to delete roles",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getBatchUpdateFieldForm() : array {
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$batchUpdateScope = $_REQUEST['batchUpdateScope'];

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		if ($tool->canBatchEdit()) {
			$structure = $tool->getObjectStructure();
			$batchFormatFields = $tool->getBatchUpdateFields($structure);
			global $interface;
			$interface->assign('batchFormatFields', $batchFormatFields);

			$modalBody = $interface->fetch('Admin/batchUpdateFieldForm.tpl');
			return [
				'success' => true,
				'title' => translate([
					'text' => "Batch Update {$tool->getPageTitle()}",
					'isAdminFacing' => true,
				]),
				'modalBody' => $modalBody,
				'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processBatchUpdateFieldForm('$moduleName', '$toolName', '$batchUpdateScope');\" class=\"modal-buttons btn btn-primary\">" . translate([
						'text' => 'Update',
						'isAdminFacing' => true,
					]) . "</button>",
			];
		} else {
			return [
				'success' => false,
				'message' => "Sorry, you don't have permission to batch edit",
			];
		}
	}

	/** @noinspection PhpUnused */
	function doBatchUpdateField() : array {
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$batchUpdateScope = $_REQUEST['batchUpdateScope'];
		$selectedField = $_REQUEST['selectedField'];
		if (isset($_REQUEST['newValue'])) {
			$newValue = $_REQUEST['newValue'];
		} else {
			return [
				'success' => false,
				'message' => "New Value was not provided",
			];
		}

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		if ($tool->canBatchEdit()) {
			$structure = $tool->getObjectStructure();
			$batchFormatFields = $tool->getBatchUpdateFields($structure);
			$fieldStructure = null;
			foreach ($batchFormatFields as $field) {
				if ($field['property'] == $selectedField) {
					$fieldStructure = $field;
					break;
				}
			}
			if ($fieldStructure == null) {
				return [
					'success' => false,
					'message' => "Could not find the selected field to edit",
				];
			} else {
				if ($batchUpdateScope == 'all') {
					$numObjects = $tool->getNumObjects();
					$recordsPerPage = 100;
					$numBatches = ceil($numObjects / $recordsPerPage);
					for ($i = 0; $i < $numBatches; $i++) {
						$objectsForBatch = $tool->getAllObjects($i + 1, 1000);
						foreach ($objectsForBatch as $dataObject) {
							$dataObject->setProperty($selectedField, $newValue, $fieldStructure);
							$dataObject->update();
						}
					}
					if ($selectedField == "accountLinkingSetting") { //ToDo: Link to help center
						if ($newValue == 1) {
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting disallows these patrons from linking to other accounts, but they can be linked to. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						} else if ($newValue == 2) {
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting disallows these patrons from being linked to, but they can link to others. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						} else if ($newValue == 3) {
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting disallows all account linking for these patrons. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						} else {
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting allows all account linking functionality for these patrons. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						}
					}else {
						if ($fieldStructure['type'] == 'enum') {
							if (is_array($fieldStructure['values']) && array_key_exists($newValue, $fieldStructure['values'])) {
								$newValue = $fieldStructure['values'][$newValue];
							}
						}
						return [
							'success' => true,
							'title' => 'Success',
							'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue.",
						];
					}
				} else {
					foreach ($_REQUEST['selectedObject'] as $id => $value) {
						$dataObject = $tool->getExistingObjectById($id);
						if ($dataObject != null) {
							$dataObject->setProperty($selectedField, $newValue, $fieldStructure);
							$dataObject->update();
						}
					}
					if ($selectedField == "accountLinkingSetting"){ //ToDo: Link to help center
						if ($newValue == 1){
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting disallows these patrons from linking to other accounts, but they can be linked to. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						}else if ($newValue == 2){
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting disallows these patrons from being linked to, but they can link to others. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						}else if ($newValue == 3){
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting disallows all account linking for these patrons. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						}else {
							return [
								'success' => true,
								'title' => 'Success',
								'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue. This setting allows all account linking functionality for these patrons. For more information on how account linking settings affect these patron types, please visit the help center.",
							];
						}
					}else {
						if ($fieldStructure['type'] == 'enum') {
							if (is_array($fieldStructure['values']) && array_key_exists($newValue, $fieldStructure['values'])) {
								$newValue = $fieldStructure['values'][$newValue];
							}
						}
						return [
							'success' => true,
							'title' => 'Success',
							'message' => "Updated selected {$tool->getPageTitle()} - {$fieldStructure['label']} fields to $newValue.",
						];
					}
				}
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error Processing Update',
				'message' => "Sorry, you don't have permission to batch edit",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getCopyFacetGroupForm() : array {
		if (!empty($_REQUEST['facetGroupId'])) {
			global $interface;
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';
			$facetGroup = new GroupedWorkFacetGroup();
			$facetGroup->id = $_REQUEST['facetGroupId'];
			if ($facetGroup->find(true)) {
				$facetId = $facetGroup->id;
				$facetLabel = $facetGroup->name;
				$interface->assign('facetId', $facetId);
				$interface->assign('facetLabel', $facetLabel);

				$displaySettings = [];
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
				$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
				$groupedWorkDisplaySettings->find();
				while ($groupedWorkDisplaySettings->fetch()) {
					$displaySettings[$groupedWorkDisplaySettings->id]['id'] = $groupedWorkDisplaySettings->id;
					$displaySettings[$groupedWorkDisplaySettings->id]['name'] = $groupedWorkDisplaySettings->name;
				}

				$interface->assign('displaySettings', $displaySettings);

				$modalBody = $interface->fetch('Admin/copyFacetGroupForm.tpl');

				return [
					'success' => true,
					'title' => translate([
						'text' => "Copy $facetLabel Grouped Work Facet Group",
						'isAdminFacing' => true,
					]),
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processCopyFacetGroupForm();\" class=\"modal-buttons btn btn-primary\">" . translate([
							'text' => 'Copy',
							'isAdminFacing' => true,
						]) . "</button>",
				];
			}else{
				return[
					'success' => false,
					'message' => translate([
						'text' => "Facet Group to copy could not be found.",
						'isAdminFacing' => true,
					])
				];
			}
		}else{
			return[
				'success' => false,
				'message' => translate([
					'text' => "Facet Group to copy was not provided.",
					'isAdminFacing' => true,
				])
			];
		}
	}

	/** @noinspection PhpUnused */
	function doCopyFacetGroup() : array {

		if (!empty($_REQUEST['name'])) {
			$facetsProcessed = 0;
			$id = $_REQUEST['id'];
			$name = $_REQUEST['name'];
			$updateDisplaySettings = false;

			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';
			$curObj = new GroupedWorkFacetGroup();
			$curObj->id = $id;
			if ($curObj->find(true)) {
				$curObjFacets = $curObj->getFacets();
				$newGroup = new GroupedWorkFacetGroup();
				$newGroup->name = $name;
				if ($newGroup->insert()) {
					foreach ($curObjFacets as $curFacet) {
						$newFacet = $curFacet;
						$newFacet->id = null;
						$newFacet->facetGroupId = $newGroup->id;
						$newFacet->insert();
						$facetsProcessed++;
					}

					if ($facetsProcessed > 0) {
						if (!empty($_REQUEST['displaySettings']) && $_REQUEST['displaySettings'] != "-1") {
							require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
							$displaySettingId = $_REQUEST['displaySettings'];
							$displaySettings = new GroupedWorkDisplaySetting();
							$displaySettings->id = $displaySettingId;
							if ($displaySettings->find(true)) {
								$displaySettings->facetGroupId = $newGroup->id;
								if ($displaySettings->update()) {
									$updateDisplaySettings = true;
								}
							}
						}
					}
				} else {
					return [
						'success' => false,
						'title' => 'Error',
						'message' => "Unable to create new facet group",
					];
				}
			}

			if ($updateDisplaySettings && $facetsProcessed > 0) {
				return [
					'success' => true,
					'title' => 'Success',
					'message' => "Copied $facetsProcessed facets to $name and updated Grouped Work Display Settings",
				];
			} elseif ($facetsProcessed > 0) {
				return [
					'success' => true,
					'title' => 'Success',
					'message' => "Copied $facetsProcessed facets to $name",
				];
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => "Unable to copy existing facets into $name",
				];
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => "A name was not provided for the new facet group",
			];
		}
	}
    /** @noinspection PhpUnused */
    function getCopyEventsFacetGroupForm() : array {
        if (!empty($_REQUEST['facetGroupId'])) {
            global $interface;
            require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';
            $facetGroup = new EventsFacetGroup();
            $facetGroup->id = $_REQUEST['facetGroupId'];
            if ($facetGroup->find(true)) {
                $facetId = $facetGroup->id;
                $facetLabel = $facetGroup->name;
                $interface->assign('facetId', $facetId);
                $interface->assign('facetLabel', $facetLabel);

                $modalBody = $interface->fetch('Admin/copyEventsFacetGroupForm.tpl');

                return [
                    'success' => true,
                    'title' => translate([
                        'text' => "Copy $facetLabel Events Facet Group",
                        'isAdminFacing' => true,
                    ]),
                    'modalBody' => $modalBody,
                    'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processCopyEventsFacetGroupForm();\" class=\"modal-buttons btn btn-primary\">" . translate([
                            'text' => 'Copy',
                            'isAdminFacing' => true,
                        ]) . "</button>",
                ];
            }else{
                return[
                    'success' => false,
                    'message' => translate([
                        'text' => "Facet Group to copy could not be found.",
                        'isAdminFacing' => true,
                    ])
                ];
            }
        }else{
            return[
                'success' => false,
                'message' => translate([
                    'text' => "Facet Group to copy was not provided.",
                    'isAdminFacing' => true,
                ])
            ];
        }
    }

    /** @noinspection PhpUnused */
    function doCopyEventsFacetGroup() : array {

        if (!empty($_REQUEST['name'])) {
            $facetsProcessed = 0;
            $id = $_REQUEST['id'];
            $name = $_REQUEST['name'];

            require_once ROOT_DIR . '/sys/Events/EventsFacetGroup.php';
            $curObj = new EventsFacetGroup();
            $curObj->id = $id;
            if ($curObj->find(true)) {
                $curObjFacets = $curObj->getFacets();
                $newGroup = new EventsFacetGroup();
                $newGroup->name = $name;
                if (isset($curObj->eventFacetCountsToShow)) {
                    $newGroup->eventFacetCountsToShow = $curObj->eventFacetCountsToShow;
                }
                if ($newGroup->insert()) {
                    foreach ($curObjFacets as $curFacet) {
                        $newFacet = $curFacet;
                        $newFacet->id = null;
                        $newFacet->facetGroupId = $newGroup->id;
                        $newFacet->insert();
                        $facetsProcessed++;
                    }
                } else {
                    return [
                        'success' => false,
                        'title' => 'Error',
                        'message' => "Unable to create new facet group",
                    ];
                }
            }

            if ($facetsProcessed > 0) {
                return [
                    'success' => true,
                    'title' => 'Success',
                    'message' => "Copied $facetsProcessed facets to $name",
                ];
            } else {
                return [
                    'success' => false,
                    'title' => 'Error',
                    'message' => "Unable to copy existing facets into $name",
                ];
            }
        } else {
            return [
                'success' => false,
                'title' => 'Error',
                'message' => "A name was not provided for the new facet group",
            ];
        }
    }

	/** @noinspection PhpUnused */
	function getCopyDisplaySettingsForm() : array {
		if (!empty($_REQUEST['settingsId'])) {
			global $interface;
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
			$displaySettings = new GroupedWorkDisplaySetting();
			$displaySettings->id = $_REQUEST['settingsId'];
			if ($displaySettings->find(true)) {
				$settingsId = $displaySettings->id;
				$settingsName = $displaySettings->name;
				$interface->assign('settingsId', $settingsId);
				$interface->assign('settingsName', $settingsName);

				$modalBody = $interface->fetch('Admin/copyGroupedWorkDisplaySettings.tpl');

				return [
					'success' => true,
					'title' => translate([
						'text' => "Copy $settingsName Grouped Work Display Settings",
						'isAdminFacing' => true,
					]),
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processCopyDisplaySettingsForm();\" class=\"modal-buttons btn btn-primary\">" . translate([
							'text' => 'Copy',
							'isAdminFacing' => true,
						]) . "</button>",
				];
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => "Could not find Grouped Work Display Setting to copy",
				];
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => "Grouped Work Display Setting to copy was not found",
			];
		}
	}

	/** @noinspection PhpUnused */
	function doCopyDisplaySettings() : array {

		if (!empty($_REQUEST['name'])) {
			$id = $_REQUEST['id'];
			$name = $_REQUEST['name'];

			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
			$curObj = new GroupedWorkDisplaySetting();
			$curObj->id = $id;
			if ($curObj->find(true)) {
				$newDisplaySetting = clone $curObj;
				$newDisplaySetting->id = null;
				$newDisplaySetting->name = $name;
				if ($newDisplaySetting->insert()) {
					return [
						'success' => true,
						'title' => 'Success',
						'message' => "Copied Grouped Work Display Settings to $name",
					];
				} else {
					return [
						'success' => false,
						'title' => 'Error',
						'message' => "Unable to create new Grouped Work Display Setting",
					];
				}
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => "Could not find Grouped Work Display Setting to copy",
				];
			}

		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => "A name was not provided for the new Grouped Work Display Setting",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getBatchDeleteForm() : array {
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$batchDeleteScope = $_REQUEST['batchDeleteScope'];

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		if ($tool->canBatchDelete()) {
			$numObjects = $tool->getNumObjects();
			global $interface;
			$interface->assign('batchScope', $batchDeleteScope);
			$interface->assign('numObjects', $numObjects);

			$modalBody = $interface->fetch('Admin/batchDeleteForm.tpl');
			return [
				'success' => true,
				'title' => translate([
					'text' => "Batch Delete {$tool->getPageTitle()}",
					'isAdminFacing' => true,
				]),
				'modalBody' => $modalBody,
				'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processBatchDeleteForm('$moduleName', '$toolName', '$batchDeleteScope');\" class=\"modal-buttons btn btn-danger\">" . translate([
						'text' => 'Yes, Delete',
						'isAdminFacing' => true,
					]) . "</button>",
			];
		} else {
			return [
				'success' => false,
				'message' => "Sorry, you don't have permission to batch edit",
			];
		}
	}

	/** @noinspection PhpUnused */
	function doBatchDelete() : array {
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$batchDeleteScope = $_REQUEST['batchDeleteScope'];

		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		if ($tool->canBatchDelete()) {
			if ($batchDeleteScope == 'all') {
				$numObjects = $tool->getNumObjects();
				$recordsPerPage = 100;
				$numBatches = ceil($numObjects / $recordsPerPage);
				$errorOccurred = false;
				$structure = $tool->getObjectStructure('batchDelete');
				$allErrors = [];
				for ($i = 0; $i < $numBatches; $i++) {
					$objectsForBatch = $tool->getAllObjects($i + 1, 1000);
					foreach ($objectsForBatch as $dataObject) {
						$deletionBlockInfo = $dataObject->getDeletionBlockInformation($structure);
						if (!$deletionBlockInfo['preventDeletion']) {
							$ret = $dataObject->delete();
							if ($ret == 0) {
								$allErrors[] = "Unable to delete {$tool->getObjectType()} with id of {$dataObject->getPrimaryKeyValue()}.";
							}
						}else{
							$allErrors[] = $deletionBlockInfo['message'];
						}
					}
				}
				if (!empty($allErrors)) {
					$user = UserAccount::getActiveUserObj();
					$user->updateMessage = implode('<br/>', $allErrors);
					$user->updateMessageIsError = true;
					$user->update();
					$errorOccurred = true;
				}
				if ($errorOccurred) {
					return [
						'success' => true,
						'title' => 'Error',
						'message' => "One or more {$tool->getPageTitle()} objects could not be deleted.",
					];
				}else{
					return [
						'success' => true,
						'title' => 'Success',
						'message' => "Deleted all {$tool->getPageTitle()} objects.",
					];
				}

			} else {
				$structure = $tool->getObjectStructure('batchDelete');
				$allErrors = [];
				$errorOccurred = false;
				foreach ($_REQUEST['selectedObject'] as $id => $value) {
					$dataObject = $tool->getExistingObjectById($id);
					if ($dataObject != null) {
						$deletionBlockInfo = $dataObject->getDeletionBlockInformation($structure);
						if (!$deletionBlockInfo['preventDeletion']) {
							$ret = $dataObject->delete();
							if ($ret == 0) {
								$allErrors[] = "Unable to delete {$tool->getObjectType()} with id of {$dataObject->getPrimaryKeyValue()}.";
							}
						}else{
							$allErrors[] = $deletionBlockInfo['message'];
						}
					}
				}
				if (!empty($allErrors)) {
					$user = UserAccount::getActiveUserObj();
					$user->updateMessage = implode('<br/>', $allErrors);
					$user->updateMessageIsError = true;
					$user->update();
					$errorOccurred = true;
				}
				if ($errorOccurred) {
					return [
						'success' => true,
						'title' => 'Error',
						'message' => "One or more {$tool->getPageTitle()} objects could not be deleted.",
					];
				}else{
					return [
						'success' => true,
						'title' => 'Success',
						'message' => "Deleted selected {$tool->getPageTitle()} objects.",
					];
				}
			}

		} else {
			return [
				'success' => false,
				'title' => 'Error Processing Update',
				'message' => "Sorry, you don't have permission to batch delete.",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getFilterOptions() : array {
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		$objectStructure = $tool->getObjectStructure();
		if ($tool->canFilter($objectStructure)) {
			$availableFilters = $tool->getFilterFields($objectStructure);
			global $interface;
			$interface->assign('availableFilters', $availableFilters);
			if (count($availableFilters) == 0) {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => "There are no fields left to use as filters",
				];
			} else {
				$modalBody = $interface->fetch('Admin/selectFilterForm.tpl');
				return [
					'success' => true,
					'title' => translate([
						'text' => 'Filter by',
						'isAdminFacing' => true,
					]),
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.getNewFilterRow('$moduleName', '$toolName');\" class=\"modal-buttons btn btn-primary\">" . translate([
							'text' => 'Add Filter',
							'isAdminFacing' => true,
						]) . "</button>",
				];
			}
		} else {
			return [
				'success' => false,
				'title' => 'Error',
				'message' => "Sorry, this form cannot be filtered",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getNewFilterRow() : array {
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$selectedFilter = $_REQUEST['selectedFilter'];

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		$objectStructure = $tool->getObjectStructure();
		if ($tool->canFilter($objectStructure)) {
			$availableFilters = $tool->getFilterFields($objectStructure);
			if (array_key_exists($selectedFilter, $availableFilters)) {
				global $interface;
				$interface->assign('filterField', $availableFilters[$selectedFilter]);
				return [
					'success' => true,
					'filterRow' => $interface->fetch('DataObjectUtil/filterField.tpl'),
				];
			} else {
				return [
					'success' => false,
					'title' => translate([
						'text' => 'Error',
						'isAdminFacing' => true,
					]),
					'message' => translate([
						'text' => "Cannot filter by the selected field",
						'isAdminFacing' => true,
					]),
				];
			}
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isAdminFacing' => true,
				]),
				'message' => translate([
					'text' => "Sorry, this form cannot be filtered",
					'isAdminFacing' => true,
				]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function deleteNYTList() : array {
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Something went wrong.',
				'isAdminFacing' => true,
			]),
		];

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		$listId = $_REQUEST['id'];
		$list = new UserList();
		$list->id = $listId;

		if ($list->find(true)) {
			//Perform an action on the list, but verify that the user has permission to do so.
			$userCanEdit = false;
			$userObj = UserAccount::getActiveUserObj();
			if ($userObj != false) {
				$userCanEdit = $userObj->canEditList($list);
			}
			if ($userCanEdit) {
				$list->delete();
				$result['success'] = true;
				$result['message'] = 'List deleted successfully';
			} else {
				$result['success'] = false;
				$result['message'] = 'You do not have permission to delete this list';
			}
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getFormPTypeSetting() : array {
		require_once ROOT_DIR . '/sys/Account/PType.php';
		$pType = $_REQUEST["data"]["pType"];
		$selected = $_REQUEST["data"]["selected"];

		$accountLinkingSetting = PType::getAccountLinkingSetting($pType);
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown error.',
				'isAdminFacing' => true,
			])
		];

		if (($accountLinkingSetting != $selected) && ($selected != 0) && ($accountLinkingSetting != 3)){
			if ($accountLinkingSetting == 0) {
				if ($selected == 1){
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Account Linking Setting Change - Allow Only to be Linked to',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'This change will break linked accounts where users with this patron type can see other accounts.',
							'isAdminFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' data-dismiss='modal'>" . translate([
							'text' => 'Ok',
							'isAdminFacing' => true,
						])
					];
				}else if ($selected == 2){
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Account Linking Setting Change - Allow Only to Link to Others',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'This change will break linked accounts for any users that can see accounts with this patron type.',
							'isAdminFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' data-dismiss='modal'>" . translate([
							'text' => 'Ok',
							'isAdminFacing' => true,
						])
					];
				}else if ($selected == 3){
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Account Linking Setting Change - Block All Linking',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'This change will break all linked accounts for users with this patron type.',
							'isAdminFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' data-dismiss='modal'>" . translate([
							'text' => 'Ok',
							'isAdminFacing' => true,
						])
					];
				}
			}else if ($accountLinkingSetting == 1){
				if ($selected == 2){
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Account Linking Setting Change - Allow Only to be Linked to',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'This change will break all linked accounts for users with this patron type.',
							'isAdminFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' data-dismiss='modal'>" . translate([
							'text' => 'Ok',
							'isAdminFacing' => true,
						])
					];
				}if ($selected == 3){
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Account Linking Setting Change - Block All Linking',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'This change will break all linked accounts for users with this patron type.',
							'isAdminFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' data-dismiss='modal'>" . translate([
							'text' => 'Ok',
							'isAdminFacing' => true,
						])
					];
				}
			}else if ($accountLinkingSetting == 2){
				if ($selected == 1){
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Account Linking Setting Change - Allow Only to be Linked to',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'This change will break all linked accounts for users with this patron type.',
							'isAdminFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' data-dismiss='modal'>" . translate([
							'text' => 'Ok',
							'isAdminFacing' => true,
						])
					];
				}else if ($selected == 3){
					$result = [
						'success' => true,
						'title' => translate([
							'text' => 'Account Linking Setting Change - Block All Linking',
							'isAdminFacing' => true,
						]),
						'message' => translate([
							'text' => 'This change will break all linked accounts for users with this patron type.',
							'isAdminFacing' => true,
						]),
						'modalButtons' => "<span class='tool btn btn-primary' data-dismiss='modal'>" . translate([
							'text' => 'Ok',
							'isAdminFacing' => true,
						])
					];
				}
			}else {
				$result = [
					'success' => false,
					'message' => translate([
						'text' => 'Unknown account linking setting.',
						'isAdminFacing' => true,
					])
				];
			}
		}else {
			$result = [
				'success' => false
			];
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function createRecoveryCode() : array {
		$user = $_REQUEST['user'] ?? '0';
		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		return $twoFactorAuth->createRecoveryCode($user);
	}

	/** @noinspection PhpUnused */
	function getSearchCommunityContentForm() : array {
		global $interface;
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->communityContentUrl)) {
			$toolName = $_REQUEST['toolName'];
			$toolModule = $_REQUEST['toolModule'];
			$objectType = $_REQUEST['objectType'];
			$interface->assign('objectType', $objectType);
			$interface->assign('toolModule', $toolModule);
			$interface->assign('toolName', $toolName);

			//Do the initial search
			require_once ROOT_DIR . '/sys/CurlWrapper.php';
			$curl = new CurlWrapper();
			$searchResults = $curl->curlGetPage($systemVariables->communityContentUrl . '/API/CommunityAPI?method=searchSharedContent&objectType=' . $objectType);
			$decodedSearchResults = json_decode($searchResults);

			$interface->assign('communitySearchResults', $decodedSearchResults);

			return [
				'title' => translate([
					'text' => 'Find Community Content',
					'isAdminFacing' => true,
				]),
				'modalBody' => $interface->fetch('Admin/searchCommunityContent.tpl'),
				'modalButtons' => '',
			];
		} else {
			return [
				'title' => translate([
					'text' => 'Error',
					'isAdminFacing' => true,
				]),
				'modalBody' => translate([
					'text' => 'Community Sharing is not available',
					'isAdminFacing' => true,
				]),
				'modalButtons' => '',
			];
		}
	}

	function toggleFieldLock() {
		$result = [
			'success' => false,
			'message' => 'Unknown error changing field locking',
		];
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::userHasPermission('Lock Administration Fields')) {
				if (!preg_match_all('/^[a-zA-Z0-9]*$/',$_REQUEST['moduleName'])){
					$result['message'] = translate(['text'=>'Invalid module name', 'isAdminFacing'=>true]);
				}elseif (!preg_match_all('/^[a-zA-Z0-9]*$/',$_REQUEST['toolName'])){
					$result['message'] = translate(['text'=>'Invalid tool name', 'isAdminFacing'=>true]);
				}elseif (!preg_match_all('/^[a-zA-Z0-9]*$/',$_REQUEST['fieldName'])){
					$result['message'] = translate(['text'=>'Invalid field name', 'isAdminFacing'=>true]);
				}
				$moduleName = $_REQUEST['moduleName'];
				$toolName = $_REQUEST['toolName'];
				$fieldName = $_REQUEST['fieldName'];
				require_once ROOT_DIR . '/sys/Administration/FieldLock.php';
				$fieldLock = new FieldLock();
				$fieldLock->module = $moduleName;
				$fieldLock->toolName = $toolName;
				$fieldLock->field = $fieldName;
				if ($fieldLock->find(true)) {
					//We're disabling the locking
					if ($fieldLock->delete()) {
						$result = [
							'success' => true,
							'lockToggle' => "<a id=\"fieldLock$fieldName\" onclick=\"return AspenDiscovery.Admin.toggleFieldLock('$moduleName', '$toolName', '$fieldName');\" role=\"button\"><i role=\"presentation\" class=\"text-info fas fa-unlock-alt\" title=\"" . translate(['text'=>"Click to toggle field locking", 'isAdminFacing'=>true, 'inAttribute'=>true]) . "\"></i></a>",
						];
					}
				} else {
					//We're enabling locking
					if ($fieldLock->insert()) {
						$result = [
							'success' => true,
							'lockToggle' => "<a id=\"fieldLock$fieldName\" onclick=\"return AspenDiscovery.Admin.toggleFieldLock('$moduleName', '$toolName', '$fieldName');\" role=\"button\"><i role=\"presentation\" class=\"text-info fas fa-lock\" title=\"" . translate(['text'=>"Click to toggle field locking", 'isAdminFacing'=>true, 'inAttribute'=>true]) . "\"></i></a>",
						];
					}
				}
			}else {
				$result['message'] = 'You don\'t have the correct permissions to change field locking';
			}
		} else {
			$result['message'] = 'You must be logged in to change field locking';
		}
		return $result;
	}

	public function getCopyMenuLinksForm() {
		$result = [
			'success' => false,
			'message' =>  translate([
				'text' => 'Unknown error getting copy menu links form',
				'isAdminFacing' => true,
			])
		];
		if (!empty($_REQUEST['libraryId'])) {
			$libraryId = $_REQUEST['libraryId'];
			$library = new Library();
			$library->libraryId = $libraryId;
			if ($library->find(true)) {
				if ($library->canActiveUserEdit()) {
					global $interface;
					$interface->assign('sourceLibraryId', $libraryId);
					$menuLinks = $library->getLibraryLinks();
					$interface->assign('menuLinks', $menuLinks);
					//get a list of libraries we can copy to
					$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
					//Do not allow copying to the source library
					unset($libraryList[$libraryId]);
					if (count($libraryList) > 0) {
						$interface->assign('libraryList', $libraryList);

						$modalBody = $interface->fetch('Admin/copyMenuLinksForm.tpl');

						return [
							'success' => true,
							'title' => translate([
								'text' => "Copy menu items",
								'isAdminFacing' => true,
							]),
							'modalBody' => $modalBody,
							'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processCopyMenuLinksForm();\" class=\"modal-buttons btn btn-primary\">" . translate([
									'text' => 'Copy',
									'isAdminFacing' => true,
								]) . "</button>",
						];
					}else{
						$result['message'] = translate([
							'text' => 'No valid libraries to copy to.',
							'isAdminFacing' => true,
						]);
					}
				} else {
					$result['message'] = translate([
						'text' => 'You do not have permissions to edit the provided library.',
						'isAdminFacing' => true,
					]);
				}
			}else{
				$result['message'] = translate([
					'text' => 'Invalid library id provided.',
					'isAdminFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'No library ID was provided.',
				'isAdminFacing' => true,
			]);
		}

		return $result;
	}

	public function copyMenuLinks() {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]),
			'message' =>  translate([
				'text' => 'Unknown copying menu links',
				'isAdminFacing' => true,
			])
		];
		if (!empty($_REQUEST['sourceLibraryId'])) {
			$libraryId = $_REQUEST['sourceLibraryId'];
			$library = new Library();
			$library->libraryId = $libraryId;
			if ($library->find(true)) {
				if ($library->canActiveUserEdit()) {
					global $interface;
					$interface->assign('sourceLibraryId', $libraryId);
					$menuLinks = $library->getLibraryLinks();
					$interface->assign('menuLinks', $menuLinks);
					//get a list of libraries we can copy to
					$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
					//Do not allow copying to the source library
					unset($libraryList[$libraryId]);
					if (count($libraryList) > 0) {
						$interface->assign('libraryList', $libraryList);

						$selectedMenuLinks = $_REQUEST['menuLink'];
						$librariesToCopyTo = $_REQUEST['library'];
						if (!empty($selectedMenuLinks)) {
							if (!empty($librariesToCopyTo)) {
								foreach ($selectedMenuLinks as $menuLinkId => $state) {
									foreach ($librariesToCopyTo as $libraryId => $state2) {
										if (array_key_exists($menuLinkId, $menuLinks) && array_key_exists($libraryId, $libraryList)) {
											$menuLinkCopy = clone $menuLinks[$menuLinkId];
											unset($menuLinkCopy->id);
											$menuLinkCopy->libraryId = $libraryId;
											$menuLinkCopy->insert();
										}
									}
								}

								$result = [
									'success' => true,
									'title' => translate([
										'text' => "Success",
										'isAdminFacing' => true,
									]),
									'message' =>  translate([
										'text' => 'Menu Links were copied successfully',
										'isAdminFacing' => true,
									])
								];
							}else{
								$result['message'] = translate([
									'text' => 'No libraries were selected.',
									'isAdminFacing' => true,
								]);
							}
						}else{
							$result['message'] = translate([
								'text' => 'No menu links were selected.',
								'isAdminFacing' => true,
							]);
						}
					}else{
						$result['message'] = translate([
							'text' => 'No valid libraries to copy to.',
							'isAdminFacing' => true,
						]);
					}
				} else {
					$result['message'] = translate([
						'text' => 'You do not have permissions to edit the source library.',
						'isAdminFacing' => true,
					]);
				}
			}else{
				$result['message'] = translate([
					'text' => 'Invalid library id provided.',
					'isAdminFacing' => true,
				]);
			}
		} else {
			$result['message'] = translate([
				'text' => 'No library ID was provided.',
				'isAdminFacing' => true,
			]);
		}

		return $result;
	}

	public function exportUsageData() {
		require_once ROOT_DIR . '/services/Admin/UsageGraphs.php';
		$aspenUsageGraph = new Admin_UsageGraphs();
		$aspenUsageGraph->buildCSV();

	}
	/**
	 * Retrieves patron types for the admin_sso account profile and
	 * all relevant primary ILS account profiles associated with the current SSO Setting
	 * to help identify which patron types are missing in the admin_sso profile.
	 */
	/** @noinspection PhpUnused */
	function getSSOPatronTypesForProfiles(): array
	{
		$ssoSettingId = $_REQUEST['ssoSettingId'] ?? null;

		require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		require_once ROOT_DIR . '/sys/Account/PType.php';
		require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';

		// Get the admin_sso account profile.
		$adminSSOProfile = new AccountProfile();
		$adminSSOProfile->name = 'admin_sso';
		$adminSSOProfileId = null;
		if ($adminSSOProfile->find(true)) {
			$adminSSOProfileId = $adminSSOProfile->id;
		}

		$result = [
			'success'           => true,
			'adminSSOProfileId' => $adminSSOProfileId,
			'adminSSOPatronTypes' => [],
			'adminSSOExists'    => ($adminSSOProfileId !== null),
			'primaryProfilesData' => [], // Will store { profileId: { ptypeValue: label, ... } }.
			'checkedDefaultProfile' => false
		];

		// Get patron types for admin_sso profile.
		if ($adminSSOProfileId) {
			$result['adminSSOPatronTypes'] = PType::getPatronTypeList(false, true, $adminSSOProfileId);
		}

		// Load the SSOSetting to find associated libraries.
		$ssoSetting = new SSOSetting();
		$ssoSetting->id = $ssoSettingId;
		if (!$ssoSetting->find(true)) {
			return ['success' => false, 'message' => 'SSOSetting not found.'];
		}

		$associatedLibraryIds = $ssoSetting->__get("libraries");
		$primaryProfileIds = [];
		if (!empty($associatedLibraryIds)) {
			foreach ($associatedLibraryIds as $libraryId) {
				$library = new Library();
				$library->libraryId = $libraryId;
				if ($library->find(true) && !empty($library->accountProfileId)) {
					$primaryProfileIds[$library->accountProfileId] = $library->accountProfileId; // Use keys for uniqueness.
				}
			}
		} else {
			// If no libraries are explicitly linked, default to the global library's profile.
			global $library;
			if (!empty($library->accountProfileId)) {
				$primaryProfileIds[$library->accountProfileId] = $library->accountProfileId;
				$result['checkedDefaultProfile'] = true;
			}
		}

		// Get patron types for each unique primary profile.
		foreach ($primaryProfileIds as $profileId) {
			// If -1 is passed for the $profileId, then all patron types will be returned.
			$result['primaryProfilesData'][$profileId] = PType::getPatronTypeList(false, true, $profileId);
		}

		return $result;
	}

	function getNotificationDevicesForUser(): array {
		$result = [
			'success' => false,
			'message' => 'Unknown error getting devices',
		];

		$user = $_REQUEST['user'] ?? null;
		if ($user) {
			$patron = new User();
			$patron->whereAdd("ils_barcode = '$user' OR ils_username = '$user'");
			$patron->find();
			$numResults = $patron->count();
			if ($numResults == 0) {
				$result['message'] = 'Patron barcode or username does not exist.';
				return $result;
			} elseif ($numResults >= 2) {
				$result['message'] = 'Multiple patrons found with that username.';
				return $result;
			} elseif ($patron->find(true)) {
				$tokens = $patron->getNotificationPushTokenByDevice();
				if ($tokens) {
					$html = '<select name="pushToken" id="pushToken" class="form-control">';
					foreach ($tokens as $device => $token) {
						$html .= '<option value="' . $token . '">' . $device . '</option>';
					}
					$html .= '</select>';

					$result['message'] = $html;
					$result['success'] = true;

				} else {
					$result['message'] = 'Patron does not have any stored push tokens.';
				}
			}
		} else {
			$result['message'] = 'No barcode or username provided.';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function searchReleaseNotes() {
		$resultsHTML = [];
		$resultsHTML['html'] = '';
		$results = [];
		if (isset($_REQUEST['searchTerm'])) {
			$searchTerm = $_REQUEST['searchTerm'];
			$releaseNotesPath = ROOT_DIR . '/release_notes';
			$releaseNoteFiles = scandir($releaseNotesPath);

			if ($releaseNoteFiles) {
				foreach ($releaseNoteFiles as $releaseNoteFile) {
					if ($releaseNoteFile != '.' && $releaseNoteFile != '..' && $releaseNoteFile != 'images') {
						$fileContents = file_get_contents($releaseNotesPath . '/' . $releaseNoteFile);
						// Check if the file contains the search term (case-insensitive)
						if ($fileContents !== false && stripos($fileContents, $searchTerm) !== false) {
							if (preg_match('/\d{2}\.\d{2}\.\d{2}\.MD/', $releaseNoteFile)) {
								$releaseNoteFile = str_replace('.MD', '', $releaseNoteFile);
								$results[$releaseNoteFile] = $releaseNoteFile;
							} elseif (preg_match('/^\d{2}\.Q\d(?:\.\d{2}(?:\.\d{2})?)?\.MD$/i', $releaseNoteFile)) {
								$releaseNoteFile = preg_replace('/\.MD$/i', '', $releaseNoteFile);
								$results[$releaseNoteFile] = $releaseNoteFile;
							} elseif (strcasecmp('Supplemental.MD', $releaseNoteFile) === 0) {
								if (filesize($releaseNotesPath . '/' . $releaseNoteFile) > 0) {
									$releaseNoteFile = str_replace('.MD', '', $releaseNoteFile);
									$results[$releaseNoteFile] = $releaseNoteFile;
								}
							}
						}
					}
				}
				arsort($results);

				$resultsHTML['html'] .= '<h4>Results Found: </h4><ul>';

				foreach ($results as $releaseNote) {
					$resultsHTML['html'] .= "<li><a href='/Admin/ReleaseNotes?release={$releaseNote}'" . '>' . $releaseNote . '</a></li>';
				}

				$resultsHTML['html'] .= '</ul>';

			}
			if (empty($results)) {
				$resultsHTML['html'] .= '<h2>No results found for search term: ' . $searchTerm . '</h2>';
			}
		}

		return $resultsHTML;
	}

	/** @noinspection PhpUnused */
	public function searchAdminSettings() : array {
		require_once ROOT_DIR . '/sys/Administration/AdminPropertySearchEntry.php';
		global $interface;
		$error = '';
		if (empty($_REQUEST['searchTerm'])) {
			$error = 'Please enter a search term.';
		}else{
			$searchEntry = new AdminPropertySearchEntry();
			$escapedRelease = $searchEntry->escape('%' . $_REQUEST['searchTerm'] . '%');
			$searchEntry->whereAdd('keywords LIKE ' . $escapedRelease);
			//Filter by modules that are active
			global $enabledModules;
			$escapedModules = implode("','", $enabledModules);
			$searchEntry->whereAdd("requiredModule = '' OR requiredModule IN ('$escapedModules')");
			$searchEntry->orderBy('label');
			$searchEntry->limit(0, 200);
			$matchingSettings = $searchEntry->fetchAll();

			//Post Process to remove things by permission
			foreach ($matchingSettings as $index => $matchingSetting) {
				$requiredPermissions = explode('|', $matchingSetting->requiredPermissions);
				if (!UserAccount::userHasPermission($requiredPermissions)) {
					unset($matchingSettings[$index]);
				}
			}

			$interface->assign('matchingSettings', $matchingSettings);
		}
		$interface->assign('error', $error);

		$results = $interface->fetch('Admin/adminSettingSearchResults.tpl');
		return [
			'success' => false,
			'results' => $results
		];
	}

	/** @noinspection PhpUnused */
	public function getBatchUpdateHolidayForm() {
		global $interface;
		$scopeLevel = $_REQUEST['scopeLevel'];
		if ($scopeLevel == 'library') {
			return [
				'title' => translate([
					'text' => 'Library Holidays Batch Update',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('Admin/batchUpdateHolidaysForm.tpl'),
				'modalButtons' => '<button type="button" class="tool btn btn-primary" id="batchUpdateLibraryHolidays" onclick="AspenDiscovery.Admin.batchUpdateHolidays($(\'#holidayInformation\').val(), \'library\'); return false;">' . translate([
						'text' => "Upload Library Holiday Information",
						'isPublicFacing' => true,
					]) . "</button>" .
					'<button type="button" class="tool btn btn-primary" id="batchUpdateAllHolidays" onclick="AspenDiscovery.Admin.batchUpdateHolidays($(\'#holidayInformation\').val(), \'all\'); return false;">' . translate([
						'text' => "Upload All Holiday Information",
						'isPublicFacing' => true,
					]) . "</button>",
			];
		}
		return [
			'title' => translate([
				'text' => 'Location Holidays Batch Update',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch('Admin/batchUpdateHolidaysForm.tpl'),
			'modalButtons' => '<button type="button" class="tool btn btn-primary" id="batchUpdateLocationHolidays" onclick="AspenDiscovery.Admin.batchUpdateHolidays($(\'#holidayInformation\').val(), \'location\'); return false;">' . translate([
					'text' => "Upload Location Holiday Information",
					'isPublicFacing' => true,
				]) . "</button>" .
				'<button type="button" class="tool btn btn-primary" id="batchUpdateAllHolidays" onclick="AspenDiscovery.Admin.batchUpdateHolidays($(\'#holidayInformation\').val(), \'all\'); return false;">' . translate([
					'text' => "Upload All Holiday Information",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}
	/** @noinspection PhpUnused */
	public function batchUpdateHolidays() {
		$holidayInformation = $_REQUEST['holidayInfo'];
		$scope = $_REQUEST['scope'];
		$currentYear = date('Y');
		$holidaysAdded = 0;
		$locationClosuresAdded = 0;
		$dayMap = [
			'sun' => 0,
			'mon' => 1,
			'tue' => 2,
			'wed' => 3,
			'thu' => 4,
			'fri' => 5,
			'sat' => 6,
		];

		//get list of libraries/locations the user can edit if they don't have both "Administer All" permissions
		if (!UserAccount::userHasPermission('Administer All Libraries')){
			$libraryList = Library::getLibraryList(true);
		}
		if (!UserAccount::userHasPermission('Administer All Locations')){
			$locationList = Location::getLocationList(true);
		}

		//parse data
		$rows = $this->parseSierraHolidayData($holidayInformation);

		//go through each row and update according to user permissions and scope
		foreach ($rows as $row) {
			$libraryLocationCode = $row[0];
			$holidayDate = $row[1];
			$library = new Library();
			$location = new Location();
			$holidayDates = [];

			if (strlen($holidayDate) > 3){
				$holidayDate = $currentYear . '-' . str_replace('/', '-', $holidayDate);
			} else {
				$holidayDates = $this->getDatesForDayOfWeek($holidayDate);
			}

			if ($libraryLocationCode == "?????") { //update all libraries
				if ($scope == 'library' || $scope == 'all') {
					$library->find();
					while ($library->fetch()) {
						if (empty($libraryList) || array_key_exists($library->libraryId, $libraryList)) {
							require_once ROOT_DIR . '/sys/LibraryLocation/Holiday.php';
							$holiday = new Holiday();
							$holiday->libraryId = $library->libraryId;
							$holiday->name = '';
							if (!empty($holidayDates)) {
								foreach ($holidayDates as $holidayDate) {
									$holiday->date = $holidayDate;
									if ($holiday->insert()) {
										$holidaysAdded++;
									}
								}
							} else {
								$holiday->date = $holidayDate;
								if ($holiday->insert()) {
									$holidaysAdded++;
								}
							}
						}
					}
				} if (($scope == 'location' || $scope == 'all') && strlen($holidayDate) == 3) {
					$location->find();
					while ($location->fetch()) {
						if (empty($locationList) || array_key_exists($location->locationId, $locationList)) {
							require_once ROOT_DIR . '/sys/LibraryLocation/LocationHours.php';
							$locationClosure = new LocationHours();
							$locationClosure->locationId = $location->locationId;
							$locationClosure->day = $dayMap[$holidayDate];
							$locationClosure->closed = 1;
							$locationClosure->open = '00:00:00';
							$locationClosure->close = '00:00:00';
							if ($locationClosure->insert()) {
								$locationClosuresAdded++;
							}
						}
					}
				}
			} else {
				if ($scope == 'library' || $scope == 'all') {
					$library->subdomain = $libraryLocationCode;
					if ($library->find(true)){
						if (empty($libraryList) || array_key_exists($library->libraryId, $libraryList)) {
							require_once ROOT_DIR . '/sys/LibraryLocation/Holiday.php';
							$holiday = new Holiday();
							$holiday->libraryId = $library->libraryId;
							$holiday->name = '';
							if (!empty($holidayDates)) {
								foreach ($holidayDates as $holidayDate) {
									$holiday->date = $holidayDate;
									if ($holiday->insert()) {
										$holidaysAdded++;
									}
								}
							} else {
								$holiday->date = $holidayDate;
								if ($holiday->insert()) {
									$holidaysAdded++;
								}
							}
						}
					}
				} if ((($scope == 'location' || $scope == 'all')) && strlen($holidayDate) == 3) {
					$location->code = $libraryLocationCode;
					if ($location->find(true)) {
						if (empty($locationList) || array_key_exists($location->locationId, $locationList)) {
							require_once ROOT_DIR . '/sys/LibraryLocation/LocationHours.php';
							$locationClosure = new LocationHours();
							$locationClosure->locationId = $location->locationId;
							$locationClosure->day = $dayMap[$holidayDate];
							$locationClosure->closed = 1;
							$locationClosure->open = '00:00:00';
							$locationClosure->close = '00:00:00';
							if ($locationClosure->insert()) {
								$locationClosuresAdded++;
							}
						}
					}
				}
			}
		}
		if ($scope == 'all') {
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Success!',
					'isAdminFacing' => true,
				]),
				'message' => translate([
					'text' => 'Successfully added %1% library holidays and %2% location closures',
					1 => $holidaysAdded,
					2 => $locationClosuresAdded,
					'isAdminFacing' => true,
				]),
			];
		} elseif ($scope == 'library') {
			return [
				'success' => true,
				'title' => translate([
					'text' => 'Success!',
					'isAdminFacing' => true,
				]),
				'message' => translate([
					'text' => 'Successfully added %1% library holidays',
					1 => $holidaysAdded,
					'isAdminFacing' => true,
				]),
			];
		}
		return [
			'success' => true,
			'title' => translate([
				'text' => 'Success!',
				'isAdminFacing' => true,
			]),
			'message' => translate([
				'text' => 'Successfully added %1% location closures',
				1 => $locationClosuresAdded,
				'isAdminFacing' => true,
			]),
		];
	}

	function parseSierraHolidayData($holidayInformation): array {
		//parse text into an array of rows that are an array of columns
		$rows = [];

		// Split into lines
		$lines = preg_split('/\r?\n/', trim($holidayInformation));

		foreach ($lines as $line) {
			//replace commas with spaces since we split info up based on whitespace between values later
			$line = trim(str_replace(',', ' ', $line));

			// Skip blank lines
			if ($line === '') {
				continue;
			}

			// Primary split: one or more tabs OR two or more consecutive spaces
			$cols = preg_split('/\t+|\s{2,}/', $line, -1, PREG_SPLIT_NO_EMPTY);

			// Fallback: if only 1 column found, try splitting on any whitespace
			if (count($cols) < 2) {
				$cols = preg_split('/\s+/', $line, 3, PREG_SPLIT_NO_EMPTY);
			}

			$count = count($cols);

			if ($count >= 3) {
				// 3+ columns: skip the first (number prefix), keep the next two
				$rows[] = array_map('trim', array_slice($cols, 1, 2));
			} elseif ($count === 2) {
				// 2 columns: no number prefix, use both as-is
				$rows[] = array_map('trim', $cols);
			} //else we ignore current line since we need a library/location code & date
		}

		return $rows;
	}

	function getDatesForDayOfWeek(string $dayName): array
	{
		$map = [
			'mon' => 'Monday',
			'tue' => 'Tuesday',
			'wed' => 'Wednesday',
			'thu' => 'Thursday',
			'fri' => 'Friday',
			'sat' => 'Saturday',
			'sun' => 'Sunday',
		];

		$dayName = $map[strtolower(substr($dayName, 0, 3))] ?? $dayName;

		$year      = date('Y');
		$startDate = new DateTime("first {$dayName} of January {$year}");
		$endDate   = new DateTime("{$year}-12-31");
		$interval  = new DateInterval('P7D');
		$dates     = [];

		while ($startDate <= $endDate) {
			$dates[] = $startDate->format('Y-m-d');
			$startDate->add($interval);
		}

		return $dates;
	}
}