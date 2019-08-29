<?php

require_once ROOT_DIR . '/Action.php';

class Admin_AJAX extends Action {


	function launch() {
		global $timer;
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$timer->logTime("Starting method $method");
			if (in_array($method, array('getReindexNotes', 'getExtractNotes', 'getReindexProcessNotes', 'getCronNotes', 'getCronProcessNotes', 'getAddToWidgetForm', 'getRecordGroupingNotes', 'getSierraExportNotes'))) {
				//JSON Responses
				header('Content-type: application/json');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo $this->$method();
			} else {
				//XML responses
				header('Content-type: text/xml');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				$xml = '<?xml version="1.0" encoding="UTF-8"?' . ">\n" .
						"<AJAXResponse>\n";
				$xml .= $this->$_GET['method']();
				$xml .= '</AJAXResponse>';

				echo $xml;
			}
		}else {
			echo json_encode(array('error'=>'invalid_method'));
		}
	}

	function getReindexNotes(){
		$id = $_REQUEST['id'];
		$reindexProcess = new ReindexLogEntry();
		$reindexProcess->id = $id;
		$results = array(
				'title' => '',
				'modalBody' => '',
				'modalButtons' => ''
		);
		if ($reindexProcess->find(true)){
			$results['title'] = "Reindex Notes";
			if (strlen(trim($reindexProcess->notes)) == 0){
				$results['modalBody'] = "No notes have been entered yet";
			}else{
				$results['modalBody'] = "<div class='helpText'>{$reindexProcess->notes}</div>";
			}
		}else{
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a reindex entry with that id.  No notes available.";
		}
		return json_encode($results);
	}

	function getRecordGroupingNotes(){
		$id = $_REQUEST['id'];
		$recordGroupingProcess = new RecordGroupingLogEntry();
		$recordGroupingProcess->id = $id;
		$results = array(
				'title' => '',
				'modalBody' => '',
				'modalButtons' => ''
		);
		if ($recordGroupingProcess->find(true)){
			$results['title'] = "Record Grouping Notes";
			if (strlen(trim($recordGroupingProcess->notes)) == 0){
				$results['modalBody'] = "No notes have been entered yet";
			}else{
				$results['modalBody'] = "<div class='helpText'>{$recordGroupingProcess->notes}</div>";
			}
		}else{
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a record grouping log entry with that id.  No notes available.";
		}
		return json_encode($results);
	}

	function getSierraExportNotes(){
		$id = $_REQUEST['id'];
		$sierraExportProcess = new SierraExportLogEntry();
		$sierraExportProcess->id = $id;
		$results = array(
				'title' => '',
				'modalBody' => '',
				'modalButtons' => ''
		);
		if ($sierraExportProcess->find(true)){
			$results['title'] = "Sierra Export Notes";
			if (strlen(trim($sierraExportProcess->notes)) == 0){
				$results['modalBody'] = "No notes have been entered yet";
			}else{
				$results['modalBody'] = "<div class='helpText'>{$sierraExportProcess->notes}</div>";
			}
		}else{
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a sierra extract log entry with that id.  No notes available.";
		}
		return json_encode($results);
	}


	function getCronProcessNotes(){
		$id = $_REQUEST['id'];
		$cronProcess = new CronProcessLogEntry();
		$cronProcess->id = $id;
		$results = array(
				'title' => '',
				'modalBody' => '',
				'modalButtons' => ""
		);
		if ($cronProcess->find(true)){
			$results['title'] = "{$cronProcess->processName} Notes";
			if (strlen($cronProcess->notes) == 0){
				$results['modalBody'] = "No notes have been entered for this process";
			}else{
				$results['modalBody'] = "<div class='helpText'>{$cronProcess->notes}</div>";
			}
		}else{
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a process with that id.  No notes available.";
		}
		return json_encode($results);
	}

	function getCronNotes()	{
		$id = $_REQUEST['id'];
		$cronLog = new CronLogEntry();
		$cronLog->id = $id;

		$results = array(
				'title' => '',
				'modalBody' => '',
				'modalButtons' => ""
		);
		if ($cronLog->find(true)){
			$results['title'] = "Cron Process {$cronLog->id} Notes";
			if (strlen($cronLog->notes) == 0){
				$results['modalBody'] = "No notes have been entered for this cron run";
			}else{
				$results['modalBody'] = "<div class='helpText'>{$cronLog->notes}</div>";
			}
		}else{
			$results['title'] = "Error";
			$results['modalBody'] = "We could not find a cron entry with that id.  No notes available.";
		}
		return json_encode($results);
	}

    function getExtractNotes()	{
        $id = $_REQUEST['id'];
        $source = $_REQUEST['source'];
        $extractLog = null;
        if ($source == 'overdrive'){
            require_once ROOT_DIR . '/sys/OverDrive/OverDriveExtractLogEntry.php';
            $extractLog = new OverDriveExtractLogEntry();
        }elseif ($source == 'ils'){
            require_once ROOT_DIR . '/sys/ILS/IlsExtractLogEntry.php';
            $extractLog = new IlsExtractLogEntry();
        }elseif ($source == 'hoopla'){
            require_once ROOT_DIR . '/sys/Hoopla/HooplaExportLogEntry.php';
            $extractLog = new HooplaExportLogEntry();
        }elseif ($source == 'rbdigital'){
            require_once ROOT_DIR . '/sys/RBdigital/RBdigitalExportLogEntry.php';
            $extractLog = new RBdigitalExportLogEntry();
        }elseif ($source == 'cloud_library'){
	        require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryExportLogEntry.php';
	        $extractLog = new CloudLibraryExportLogEntry();
        }elseif ($source == 'sideload'){
	        require_once ROOT_DIR . '/sys/Indexing/SideLoadLogEntry.php';
	        $extractLog = new SideLoadLogEntry();
        }

        if ($extractLog == null){
            $results['title'] = "Error";
            $results['modalBody'] = "Invalid source for loading notes.";
        }else{
            $extractLog->id = $id;
            $results = array(
                'title' => '',
                'modalBody' => '',
                'modalButtons' => ""
            );
            if ($extractLog->find(true)){
                $results['title'] = "Extract {$extractLog->id} Notes";
                if (strlen($extractLog->notes) == 0){
                    $results['modalBody'] = "No notes have been entered for this run";
                }else{
                    $results['modalBody'] = "<div class='helpText'>{$extractLog->notes}</div>";
                }
            }else{
                $results['title'] = "Error";
                $results['modalBody'] = "We could not find an extract entry with that id.  No notes available.";
            }
        }


	    return json_encode($results);
	}

	function getAddToWidgetForm(){
		global $interface;
		$user = UserAccount::getLoggedInUser();
		// Display Page
		$interface->assign('id', strip_tags($_REQUEST['id']));
		$interface->assign('source', strip_tags($_REQUEST['source']));
		$existingWidgets = array();
		$listWidget = new ListWidget();
		if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('contentEditor') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager')){
			//Get all widgets for the library
			$userLibrary = Library::getPatronHomeLibrary();
			$listWidget->libraryId = $userLibrary->libraryId;
		}
		$listWidget->orderBy('name');
		$existingWidgets = $listWidget->fetchAll('id', 'name');
		$interface->assign('existingWidgets', $existingWidgets);
		$results = array(
				'title' => 'Create a Widget',
				'modalBody' => $interface->fetch('Admin/addToWidgetForm.tpl'),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#bulkAddToList\").submit();'>Create Widget</button>"
		);
		return json_encode($results);
	}
}
