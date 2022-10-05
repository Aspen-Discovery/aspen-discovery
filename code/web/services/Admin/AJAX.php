<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Admin_AJAX extends JSON_Action
{

	/** @noinspection PhpUnused */
	function getReindexNotes()
	{
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/Indexing/ReindexLogEntry.php';
		$reindexProcess = new ReindexLogEntry();
		$reindexProcess->id = $id;
		$results = array(
			'title' => '',
			'modalBody' => '',
			'modalButtons' => ''
		);
		if ($reindexProcess->find(true)) {
			$results['title'] = translate(['text'=>"Reindex Notes", 'isAdminFacing'=>true]);
			if (strlen(trim($reindexProcess->notes)) == 0) {
				$results['modalBody'] = translate(['text'=>"No notes have been entered yet", 'isAdminFacing'=>true]);
			} else {
				$results['modalBody'] = "<div class='helpText'>{$reindexProcess->notes}</div>";
			}
		} else {
			$results['title'] = translate(['text'=>"Error", 'isAdminFacing'=>true]);
			$results['modalBody'] = translate(['text'=>"We could not find a reindex entry with that id.  No notes available.", 'isAdminFacing'=>true]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getCronProcessNotes()
	{
		$id = $_REQUEST['id'];
		$cronProcess = new CronProcessLogEntry();
		$cronProcess->id = $id;
		$results = array(
			'title' => '',
			'modalBody' => '',
			'modalButtons' => ""
		);
		if ($cronProcess->find(true)) {
			$results['title'] = translate(['text'=>"%1% Notes",1=>$cronProcess->processName, 'isAdminFacing'=>true]);
			if (strlen($cronProcess->notes) == 0) {
				$results['modalBody'] = translate(['text'=>"No notes have been entered for this process", 'isAdminFacing'=>true]);
			} else {
				$results['modalBody'] = "<div class='helpText'>{$cronProcess->notes}</div>";
			}
		} else {
			$results['title'] = "Error";
			$results['modalBody'] = translate(['text'=>"We could not find a process with that id.  No notes available.", 'isAdminFacing'=>true]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getCronNotes()
	{
		$id = $_REQUEST['id'];
		$cronLog = new CronLogEntry();
		$cronLog->id = $id;

		$results = array(
			'title' => '',
			'modalBody' => '',
			'modalButtons' => ""
		);
		if ($cronLog->find(true)) {
			$results['title'] = translate(['text'=>"Cron Process %1% Notes", 1=>$cronLog->id, 'isAdminFacing'=>true]);
			if (strlen($cronLog->notes) == 0) {
				$results['modalBody'] = translate(['text'=>"No notes have been entered for this cron run", 'isAdminFacing'=>true]);
			} else {
				$results['modalBody'] = "<div class='helpText'>{$cronLog->notes}</div>";
			}
		} else {
			$results['title'] = translate(['text'=>"Error", 'isAdminFacing'=>true]);
			$results['modalBody'] = translate(['text'=>"We could not find a cron entry with that id.  No notes available.", 'isAdminFacing'=>true]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getExtractNotes()
	{
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
		} elseif ($source == 'nyt_updates') {
			require_once ROOT_DIR . '/sys/UserLists/NYTUpdateLogEntry.php';
			$extractLog = new NYTUpdateLogEntry();
		} elseif ($source == 'open_archives') {
			require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesExportLogEntry.php';
			$extractLog = new OpenArchivesExportLogEntry();
		} elseif ($source == 'events') {
			require_once ROOT_DIR . '/sys/Events/EventsIndexingLogEntry.php';
			$extractLog = new EventsIndexingLogEntry();
		} elseif ($source == 'search_update') {
			require_once ROOT_DIR . '/sys/SearchUpdateLogEntry.php';
			$extractLog = new SearchUpdateLogEntry();
		}

		if ($extractLog == null) {
			$results['title'] = translate(['text'=>"Error", 'isAdminFacing'=>true]);
			$results['modalBody'] = translate(['text'=>"Invalid source for loading notes.", 'isAdminFacing'=>true]);
		} else {
			$extractLog->id = $id;
			$results = array(
				'title' => '',
				'modalBody' => '',
				'modalButtons' => ""
			);
			if ($extractLog->find(true)) {
				$results['title'] = translate(['text'=>"Extract %1% Notes", 1=>$extractLog->id, 'isAdminFacing'=>true]);
				if (strlen($extractLog->notes) == 0) {
					$results['modalBody'] = translate(['text'=>"No notes have been entered for this run", 'isAdminFacing'=>true]);
				} else {
					$results['modalBody'] = "<div class='helpText'>{$extractLog->notes}</div>";
				}
			} else {
				$results['title'] = translate(['text'=>"Error", 'isAdminFacing'=>true]);
				$results['modalBody'] = translate(['text'=>"We could not find an extract entry with that id.  No notes available.", 'isAdminFacing'=>true]);
			}
		}


		return $results;
	}

	/** @noinspection PhpUnused */
	function getAddToSpotlightForm()
	{
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
		while ($spotlightList->fetch()){
			$existingCollectionSpotlightLists[] = clone $spotlightList;
		}

		$interface->assign('existingCollectionSpotlightLists', $existingCollectionSpotlightLists);
		$interface->assign('existingCollectionSpotlights', $existingCollectionSpotlights);
		return array(
			'title' => translate(["text"=>'Create a Spotlight', "isAdminFacing"=>true]),
			'modalBody' => $interface->fetch('Admin/addToSpotlightForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#addSpotlight\").submit();'>" . translate(["text"=>"Create Spotlight", "isAdminFacing"=>true]) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function ungroupRecord(){
		$results = [
			'success' => false,
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Manually Group and Ungroup Works'))) {
			require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
			$ungroupedRecord = new NonGroupedRecord();
			/** @var GroupedWorkSubDriver $record */
			$record = RecordDriverFactory::initRecordDriverById($_REQUEST['recordId']);
			if ($record instanceof AspenError){
				$results['message'] = "Unable to find the record for this id";
			}else{
				list($source, $recordId) = explode(':', $_REQUEST['recordId']);
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
					if ($groupedWork->find(true)){
						$groupedWork->forceReindex(true);
					}
					$results['success'] = true;
					$results['message'] = 'This record has been ungrouped and the index will update shortly';
				}
			}

		}else{
			$results['message'] = translate(['text'=>"You do not have the correct permissions for this operation", 'isAdminFacing'=>true]);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getReleaseNotes(){
		global $interface;
		$release = $_REQUEST['release'];
		$releaseNotesPath = ROOT_DIR . '/release_notes';
		$results = [
			'success' => false,
			'message' => 'Unknown error loading release notes'
		];
		if (!file_exists($releaseNotesPath . '/'. $release . '.MD')){
			$results['message'] = 'Could not find notes for that release';
		}else{
			require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
			$parsedown = AspenParsedown::instance();
			$releaseNotesFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/'. $release . '.MD'));
			$results = [
				'success' => true,
				'release' => $release,
				'releaseNotes' => $releaseNotesFormatted,
				'actionItems' => '',
				'testingSuggestions' => ''
			];
			if (file_exists($releaseNotesPath . '/'. $release . '_action_items.MD')){
				$actionItemsFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/'. $release . '_action_items.MD'));
				$results['actionItems'] = $actionItemsFormatted;
				$interface->assign('actionItemsFormatted', $actionItemsFormatted);
			}
			if (file_exists($releaseNotesPath . '/'. $release . '_testing.MD')){
				$testingSuggestionsFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/'. $release . '_testing.MD'));
				$results['testingSuggestions'] = $testingSuggestionsFormatted;
				$interface->assign('testingSuggestionsFormatted', $testingSuggestionsFormatted);
			}
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getCreateRoleForm()
	{
		global $interface;
		if (UserAccount::userHasPermission('Administer Permissions')) {

			$roles = [];
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$role->orderBy('name');
			$role->find();
			while($role->fetch()) {
				$roles[$role->roleId]['roleId'] = $role->roleId;
				$roles[$role->roleId]['name'] = $role->name;
			}

			$interface->assign('permissionRoles', $roles);

			return [
				'title' => translate(['text'=>'Create New Role','isAdminFacing'=>true]),
				'modalBody' => $interface->fetch('Admin/createRoleForm.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Admin.createRole();'>" . translate(['text'=>"Create Role",'isAdminFacing'=>true]) , "</button>"
			];
		}else{
			return [
				'success' => false,
				'message' => translate(['text'=>"Sorry, you don't have permissions to add roles",'isAdminFacing'=>true]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function createRole(){
		if (UserAccount::userHasPermission('Administer Permissions')) {
			if (isset($_REQUEST['roleName'])){
				$name = $_REQUEST['roleName'];
				$description = $_REQUEST['description'];
				$copyFrom = $_REQUEST['copyFrom'];
				require_once ROOT_DIR . '/sys/Administration/Role.php';
				$existingRole = new Role;
				$existingRole->name = $name;
				if ($existingRole->find(true)){
					return [
						'success' => false,
						'message' => "$name already exists",
					];
				}else{
					$curPermissions = [];
					if($copyFrom != "-1" || $copyFrom != -1) {
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

					if(count($curPermissions) > 0) {
						foreach($curPermissions as $curPermission) {
							require_once ROOT_DIR . '/sys/Administration/Permission.php';
							$permission = new Permission();
							$permission->name = $curPermission;
							if($permission->find(true)) {
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
						'roleId' => $newRole->roleId
					];
				}
			}else{
				return [
					'success' => false,
					'message' => "The role name must be provided",
				];
			}
		}else{
			return [
				'success' => false,
				'message' => translate(['text'=>"Sorry, you don't have permissions to add roles",'isAdminFacing'=>true]),
			];
		}
	}

	function deleteRole(){
		if (UserAccount::userHasPermission('Administer Permissions')) {
			if (isset($_REQUEST['roleId']) && is_numeric($_REQUEST['roleId'])){
				//Check to be sure the role is not used by anyone
				require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
				$usersForRole = new UserRoles();
				$usersForRole->roleId = $_REQUEST['roleId'];
				$usersForRole->find();
				if ($usersForRole->getNumResults() > 0){
					return [
						'success' => false,
						'message' => "The role is in use by " . $usersForRole->getNumResults() . " users, please remove them from the role before deleting",
					];
				}else{
					$role = new Role();
					$role->roleId = $_REQUEST['roleId'];
					$role->delete();
					return [
						'success' => true,
						'message' => "The role was deleted successfully",
					];
				}
			}else{
				return [
					'success' => false,
					'message' => "The role to delete must be provided",
				];
			}
		}else{
			return [
				'success' => false,
				'message' => "Sorry, you don't have permissions to delete roles",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getBatchUpdateFieldForm(){
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
				'title' => translate(['text' => "Batch Update {$tool->getPageTitle()}", 'isAdminFacing'=>true]),
				'modalBody' => $modalBody,
				'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processBatchUpdateFieldForm('{$moduleName}', '{$toolName}', '{$batchUpdateScope}');\" class=\"modal-buttons btn btn-primary\">" . translate(['text' => 'Update', 'isAdminFacing'=>true]) . "</button>"
			];
		}else{
			return [
				'success' => false,
				'message' => "Sorry, you don't have permission to batch edit",
			];
		}
	}

	/** @noinspection PhpUnused */
	function doBatchUpdateField(){
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$batchUpdateScope = $_REQUEST['batchUpdateScope'];
		$selectedField = $_REQUEST['selectedField'];
		if (isset($_REQUEST['newValue'])) {
			$newValue = $_REQUEST['newValue'];
		}else{
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
			foreach ($batchFormatFields as $field){
				if ($field['property'] == $selectedField){
					$fieldStructure = $field;
					break;
				}
			}
			if ($fieldStructure == null){
				return [
					'success' => false,
					'message' => "Could not find the selected field to edit",
				];
			}else {
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
					return [
						'success' => true,
						'title' => 'Success',
						'message' => "Updated all {$tool->getPageTitle()} - {$fieldStructure['label']} fields to {$newValue}.",
					];
				} else {
					foreach ($_REQUEST['selectedObject'] as $id => $value){
						$dataObject = $tool->getExistingObjectById($id);
						if ($dataObject != null) {
							$dataObject->setProperty($selectedField, $newValue, $fieldStructure);
							$dataObject->update();
						}
					}
					return [
						'success' => true,
						'title' => 'Success',
						'message' => "Updated selected {$tool->getPageTitle()} - {$fieldStructure['label']} fields to {$newValue}.",
					];
				}
			}
		}else{
			return [
				'success' => false,
				'title' => 'Error Processing Update',
				'message' => "Sorry, you don't have permission to batch edit",
			];
		}
	}

	/** @noinspection PhpUnused */
	function getCopyFacetGroupForm(){
		if(!empty($_REQUEST['facetGroupId'])) {
			global $interface;
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';
			$facetGroup = new GroupedWorkFacetGroup();
			$facetGroup->id = $_REQUEST['facetGroupId'];
			if($facetGroup->find(true)) {
				$facetId = $facetGroup->id;
				$facetLabel = $facetGroup->name;
				$interface->assign('facetId', $facetId);
				$interface->assign('facetLabel', $facetLabel);

				$displaySettings = [];
				require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
				$groupedWorkDisplaySettings = new GroupedWorkDisplaySetting();
				$groupedWorkDisplaySettings->find();
				while($groupedWorkDisplaySettings->fetch()) {
					$displaySettings[$groupedWorkDisplaySettings->id]['id'] = $groupedWorkDisplaySettings->id;
					$displaySettings[$groupedWorkDisplaySettings->id]['name'] = $groupedWorkDisplaySettings->name;
				}

				$interface->assign('displaySettings', $displaySettings);

				$modalBody = $interface->fetch('Admin/copyFacetGroupForm.tpl');

				return [
					'success' => true,
					'title' => translate(['text' => "Copy {$facetLabel} Grouped Work Facet Group", 'isAdminFacing'=>true]),
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processCopyFacetGroupForm();\" class=\"modal-buttons btn btn-primary\">" . translate(['text' => 'Copy', 'isAdminFacing'=>true]) . "</button>"
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function doCopyFacetGroup(){

		if(!empty($_REQUEST['name'])) {
			$facetsProcessed = 0;
			$id = $_REQUEST['id'];
			$name = $_REQUEST['name'];
			$updateDisplaySettings = false;

			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFacetGroup.php';
			$curObj = new GroupedWorkFacetGroup();
			$curObj->id = $id;
			if($curObj->find(true)) {
				$curObjFacets = $curObj->getFacets();
				$newGroup = new GroupedWorkFacetGroup();
				$newGroup->name = $name;
				if($newGroup->insert()) {
					foreach($curObjFacets as $curFacet) {
						$newFacet = $curFacet;
						$newFacet->id = null;
						$newFacet->facetGroupId = $newGroup->id;
						$newFacet->insert();
						$facetsProcessed++;
					}

					if($facetsProcessed > 0) {
						if(!empty($_REQUEST['displaySettings']) && $_REQUEST['displaySettings'] != "-1") {
							require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
							$displaySettingId = $_REQUEST['displaySettings'];
							$displaySettings = new GroupedWorkDisplaySetting();
							$displaySettings->id = $displaySettingId;
							if($displaySettings->find(true)) {
								$displaySettings->facetGroupId = $newGroup->id;
								if($displaySettings->update()) {
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

			if($updateDisplaySettings && $facetsProcessed > 0) {
				return [
					'success' => true,
					'title' => 'Success',
					'message' => "Copied {$facetsProcessed} facets to {$name} and updated Grouped Work Display Settings",
				];
			} elseif($facetsProcessed > 0) {
				return [
					'success' => true,
					'title' => 'Success',
					'message' => "Copied {$facetsProcessed} facets to {$name}",
				];
			} else {
				return [
					'success' => false,
					'title' => 'Error',
					'message' => "Unable to copy existing facets into {$name}",
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
	function getCopyDisplaySettingsForm(){
		if(!empty($_REQUEST['settingsId'])) {
			global $interface;
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
			$displaySettings = new GroupedWorkDisplaySetting();
			$displaySettings->id = $_REQUEST['settingsId'];
			if($displaySettings->find(true)) {
				$settingsId = $displaySettings->id;
				$settingsName = $displaySettings->name;
				$interface->assign('settingsId', $settingsId);
				$interface->assign('settingsName', $settingsName);

				$modalBody = $interface->fetch('Admin/copyGroupedWorkDisplaySettings.tpl');

				return [
					'success' => true,
					'title' => translate(['text' => "Copy {$settingsName} Grouped Work Display Settings", 'isAdminFacing'=>true]),
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processCopyDisplaySettingsForm();\" class=\"modal-buttons btn btn-primary\">" . translate(['text' => 'Copy', 'isAdminFacing'=>true]) . "</button>"
				];
			}
		}
	}

	/** @noinspection PhpUnused */
	function doCopyDisplaySettings(){

		if(!empty($_REQUEST['name'])) {
			$id = $_REQUEST['id'];
			$name = $_REQUEST['name'];

			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php';
			$curObj = new GroupedWorkDisplaySetting();
			$curObj->id = $id;
			if($curObj->find(true)) {
				$newDisplaySetting = clone $curObj;
				$newDisplaySetting->id = null;
				$newDisplaySetting->name = $name;
				if($newDisplaySetting->insert()) {
					return [
						'success' => true,
						'title' => 'Success',
						'message' => "Copied Grouped Work Display Settings to {$name}",
					];
				} else {
					return [
						'success' => false,
						'title' => 'Error',
						'message' => "Unable to create new Grouped Work Display Setting",
					];
				}
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
	function getBatchDeleteForm(){
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
				'title' => translate(['text' => "Batch Delete {$tool->getPageTitle()}", 'isAdminFacing'=>true]),
				'modalBody' => $modalBody,
				'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processBatchDeleteForm('{$moduleName}', '{$toolName}', '{$batchDeleteScope}');\" class=\"modal-buttons btn btn-danger\">" . translate(['text' => 'Yes, Delete', 'isAdminFacing'=>true]) . "</button>"
			];
		}else{
			return [
				'success' => false,
				'message' => "Sorry, you don't have permission to batch edit",
			];
		}
	}

	function doBatchDelete(){
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$batchDeleteScope = $_REQUEST['batchDeleteScope'];

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		if ($tool->canBatchEdit()) {
			if ($batchDeleteScope == 'all') {
				$numObjects = $tool->getNumObjects();
				$recordsPerPage = 100;
				$numBatches = ceil($numObjects / $recordsPerPage);
				for ($i = 0; $i < $numBatches; $i++) {
					$objectsForBatch = $tool->getAllObjects($i + 1, 1000);
					foreach ($objectsForBatch as $dataObject) {
						$dataObject->delete();
					}
				}
				return [
					'success' => true,
					'title' => 'Success',
					'message' => "Deleted all {$tool->getPageTitle()} objects",
				];
			} else {
				foreach ($_REQUEST['selectedObject'] as $id => $value){
					$dataObject = $tool->getExistingObjectById($id);
					if ($dataObject != null) {
						$dataObject->delete();
					}
				}
				return [
					'success' => true,
					'title' => 'Success',
					'message' => "Deleted selected {$tool->getPageTitle()} objects.",
				];
			}

		}else{
			return [
				'success' => false,
				'title' => 'Error Processing Update',
				'message' => "Sorry, you don't have permission to batch delete",
			];
		}
	}

	function getFilterOptions(){
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		$objectStructure = $tool->getObjectStructure();
		if ($tool->canFilter($objectStructure)){
			$availableFilters = $tool->getFilterFields($objectStructure);
			global $interface;
			$interface->assign('availableFilters', $availableFilters);
			if (count($availableFilters) == 0){
				return [
					'success' => false,
					'title' => 'Error',
					'message' => "There are no fields left to use as filters",
				];
			}else{
				$modalBody = $interface->fetch('Admin/selectFilterForm.tpl');
				return [
					'success' => true,
					'title' => translate(['text' => 'Filter by', 'isAdminFacing'=>true]),
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.getNewFilterRow('{$moduleName}', '{$toolName}');\" class=\"modal-buttons btn btn-primary\">" . translate(['text' => 'Add Filter', 'isAdminFacing'=>true]) . "</button>"
				];
			}
		}else{
			return [
				'success' => false,
				'title' => 'Error',
				'message' => "Sorry, this form cannot be filtered",
			];
		}
	}

	function getNewFilterRow(){
		$moduleName = $_REQUEST['moduleName'];
		$toolName = $_REQUEST['toolName'];
		$selectedFilter = $_REQUEST['selectedFilter'];

		/** @noinspection PhpIncludeInspection */
		require_once ROOT_DIR . '/services/' . $moduleName . '/' . $toolName . '.php';
		$fullToolName = $moduleName . '_' . $toolName;
		/** @var ObjectEditor $tool */
		$tool = new $fullToolName();

		$objectStructure = $tool->getObjectStructure();
		if ($tool->canFilter($objectStructure)){
			$availableFilters = $tool->getFilterFields($objectStructure);
			if (array_key_exists($selectedFilter, $availableFilters)){
				global $interface;
				$interface->assign('filterField', $availableFilters[$selectedFilter]);
				return [
					'success' => true,
					'filterRow' => $interface->fetch('DataObjectUtil/filterField.tpl')
				];
			}else{
				return [
					'success' => false,
					'title' => translate(['text' => 'Error', 'isAdminFacing'=>true]),
					'message' => translate(['text' => "Cannot filter by the selected field", 'isAdminFacing'=>true]),
				];
			}
		}else{
			return [
				'success' => false,
				'title' => translate(['text' => 'Error', 'isAdminFacing'=>true]),
				'message' => translate(['text' => "Sorry, this form cannot be filtered", 'isAdminFacing'=>true]),
			];
		}
	}

	/** @noinspection PhpUnused */
	function deleteNYTList() {
		$result = [
			'success' => false,
			'message' => translate(['text' => 'Something went wrong.', 'isAdminFacing'=>true])
		];

		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';

		$listId = $_REQUEST['id'];
		$list = new UserList();
		$list->id = $listId;

		if($list->find(true)) {
			//Perform an action on the list, but verify that the user has permission to do so.
			$userCanEdit = false;
			$userObj = UserAccount::getActiveUserObj();
			if ($userObj != false){
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
	function createRecoveryCode() {
		$user = $_REQUEST['user'] ?? '0';
		require_once ROOT_DIR . '/sys/TwoFactorAuthCode.php';
		$twoFactorAuth = new TwoFactorAuthCode();
		return $twoFactorAuth->createRecoveryCode($user);
	}
}
