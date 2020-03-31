<?php

require_once ROOT_DIR . '/Action.php';

class Admin_AJAX extends Action
{

	function launch()
	{
		global $timer;
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$timer->logTime("Starting method $method");

			//JSON Responses
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		} else {
			echo json_encode(array('error' => 'invalid_method'));
		}
	}

	function getReindexNotes()
	{
		$id = $_REQUEST['id'];
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
		return json_encode($results);
	}

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
		return json_encode($results);
	}

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
		return json_encode($results);
	}

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
		} elseif ($source == 'sideload') {
			require_once ROOT_DIR . '/sys/Indexing/SideLoadLogEntry.php';
			$extractLog = new SideLoadLogEntry();
		} elseif ($source == 'website') {
			require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexLogEntry.php';
			$extractLog = new WebsiteIndexLogEntry();
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


		return json_encode($results);
	}

	function getAddToSpotlightForm()
	{
		global $interface;
		// Display Page
		$interface->assign('id', strip_tags($_REQUEST['id']));
		$interface->assign('source', strip_tags($_REQUEST['source']));
		require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
		$collectionSpotlight = new CollectionSpotlight();
		if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('contentEditor') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager')) {
			//Get all spotlights for the library
			$userLibrary = Library::getPatronHomeLibrary();
			$collectionSpotlight->libraryId = $userLibrary->libraryId;
		}
		$collectionSpotlight->orderBy('name');
		$existingCollectionSpotlights = $collectionSpotlight->fetchAll('id', 'name');
		$interface->assign('existingCollectionSpotlights', $existingCollectionSpotlights);
		$results = array(
			'title' => 'Create a Spotlight',
			'modalBody' => $interface->fetch('Admin/addToSpotlightForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#addSpotlight\").submit();'>Create Spotlight</button>"
		);
		return json_encode($results);
	}

	function ungroupRecord(){
		$results = [
			'success' => false,
			'message' => 'Unknown Error'
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('cataloging'))) {
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
		return json_encode($results);
	}

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
			require_once ROOT_DIR . '/sys/Parsedown/Parsedown.php';
			$parsedown = Parsedown::instance();
			$releaseNotesFormatted = $parsedown->parse(file_get_contents($releaseNotesPath . '/'. $release . '.MD'));
			$results = [
				'success' => true,
				'releaseNotes' => $releaseNotesFormatted
			];
		}
		return json_encode($results);
	}
}
