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
			$results['title'] = "Reindex Notes";
			if (strlen(trim($reindexProcess->notes)) == 0) {
				$results['modalBody'] = "No notes have been entered yet";
			} else {
				$results['modalBody'] = "<div class='helpText'>{$reindexProcess->notes}</div>";
			}
		} else {
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a reindex entry with that id.  No notes available.";
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
			$results['title'] = "{$cronProcess->processName} Notes";
			if (strlen($cronProcess->notes) == 0) {
				$results['modalBody'] = "No notes have been entered for this process";
			} else {
				$results['modalBody'] = "<div class='helpText'>{$cronProcess->notes}</div>";
			}
		} else {
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a process with that id.  No notes available.";
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
			$results['title'] = "Cron Process {$cronLog->id} Notes";
			if (strlen($cronLog->notes) == 0) {
				$results['modalBody'] = "No notes have been entered for this cron run";
			} else {
				$results['modalBody'] = "<div class='helpText'>{$cronLog->notes}</div>";
			}
		} else {
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a cron entry with that id.  No notes available.";
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
		} elseif ($source == 'rbdigital') {
			require_once ROOT_DIR . '/sys/RBdigital/RBdigitalExportLogEntry.php';
			$extractLog = new RBdigitalExportLogEntry();
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
		}

		if ($extractLog == null) {
			$results['title'] = "Error";
			$results['modalBody'] = "Invalid source for loading notes.";
		} else {
			$extractLog->id = $id;
			$results = array(
				'title' => '',
				'modalBody' => '',
				'modalButtons' => ""
			);
			if ($extractLog->find(true)) {
				$results['title'] = "Extract {$extractLog->id} Notes";
				if (strlen($extractLog->notes) == 0) {
					$results['modalBody'] = "No notes have been entered for this run";
				} else {
					$results['modalBody'] = "<div class='helpText'>{$extractLog->notes}</div>";
				}
			} else {
				$results['title'] = "Error";
				$results['modalBody'] = "We could not find an extract entry with that id.  No notes available.";
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
		$interface->assign('existingCollectionSpotlights', $existingCollectionSpotlights);
		return array(
			'title' => 'Create a Spotlight',
			'modalBody' => $interface->fetch('Admin/addToSpotlightForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#addSpotlight\").submit();'>Create Spotlight</button>"
		);
	}

	/** @noinspection PhpUnused */
	function ungroupRecord(){
		$results = [
			'success' => false,
			'message' => 'Unknown Error'
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
			$results['message'] = "You do not have the correct permissions for this operation";
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getReleaseNotes(){
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
				'releaseNotes' => $releaseNotesFormatted
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getCreateRoleForm()
	{
		global $interface;
		if (UserAccount::userHasPermission('Administer Permissions')) {
			return [
				'title' => 'Create a Spotlight',
				'modalBody' => $interface->fetch('Admin/createRoleForm.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.Admin.createRole();'>Create Role</button>"
			];
		}else{
			return [
				'success' => false,
				'message' => "Sorry, you don't have permissions to add roles",
			];
		}
	}

	/** @noinspection PhpUnused */
	function createRole(){
		if (UserAccount::userHasPermission('Administer Permissions')) {
			if (isset($_REQUEST['roleName'])){
				$name = $_REQUEST['roleName'];
				$description = $_REQUEST['description'];
				require_once ROOT_DIR . '/sys/Administration/Role.php';
				$existingRole = new Role;
				$existingRole->name = $name;
				if ($existingRole->find(true)){
					return [
						'success' => false,
						'message' => "$name already exists",
					];
				}else{
					$newRole = new Role();
					$newRole->name = $name;
					$newRole->description = $description;
					$newRole->insert();
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
				'message' => "Sorry, you don't have permissions to add roles",
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
				'title' => "Batch Update {$tool->getPageTitle()}",
				'modalBody' => $modalBody,
				'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.processBatchUpdateFieldForm('{$moduleName}', '{$toolName}', '{$batchUpdateScope}');\" class=\"modal-buttons btn btn-primary\">" . translate('Update') . "</button>"
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
					'title' => 'Filter by',
					'modalBody' => $modalBody,
					'modalButtons' => "<button onclick=\"return AspenDiscovery.Admin.getNewFilterRow('{$moduleName}', '{$toolName}');\" class=\"modal-buttons btn btn-primary\">" . translate('Add Filter') . "</button>"
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
					'title' => 'Error',
					'message' => "Cannot filter by the selected field",
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
}
