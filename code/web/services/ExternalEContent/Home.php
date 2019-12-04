<?php

require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';

class ExternalEContent_Home extends Action{
	private $id;

	function launch(){
		global $interface;

		if (isset($_REQUEST['searchId'])){
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		}else if (isset($_SESSION['searchId'])){
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$this->id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $this->id);

		global $activeRecordProfile;
		if (isset($activeRecordProfile)){
			$subType = $activeRecordProfile;
		}else{
			$indexingProfile = new IndexingProfile();
			$indexingProfile->name = 'ils';
			if ($indexingProfile->find(true)){
				$subType = $indexingProfile->name;
			}else{
				$indexingProfile = new IndexingProfile();
				$indexingProfile->id = 1;
				if ($indexingProfile->find(true)){
					$subType = $indexingProfile->name;
				}
			}
		}

		/** @var ExternalEContentDriver $recordDriver */
		$recordDriver = new ExternalEContentDriver($subType . ':'. $this->id);

		if (!$recordDriver->isValid()){
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record');
			die();
		}

		$groupedWork = $recordDriver->getGroupedWorkDriver();
		if (is_null($groupedWork) || !$groupedWork->isValid()){  // initRecordDriverById itself does a validity check and returns null if not.
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record');
			die();
		}else{
			$interface->assign('recordDriver', $recordDriver);

			$this->loadCitations($recordDriver);

			$interface->assign('cleanDescription', strip_tags($recordDriver->getDescriptionFast(), '<p><br><b><i><em><strong>'));

			// Retrieve User Search History
			$interface->assign('lastSearch', isset($_SESSION['lastSearchURL']) ?
			$_SESSION['lastSearchURL'] : false);

			//Get Next/Previous Links
			$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init($searchSource);
			$searchObject->getNextPrevLinks();

			//Get Related Records to make sure we initialize items
			$recordInfo = $recordDriver->getGroupedWorkDriver()->getRelatedRecord($recordDriver->getIdWithSource());
			$interface->assign('actions', $recordInfo->getActions());

			// Set Show in Main Details Section options for templates
			// (needs to be set before moreDetailsOptions)
			global $library;
			foreach ($library->showInMainDetails as $detailOption) {
				$interface->assign($detailOption, true);
			}

			$interface->assign('moreDetailsOptions', $recordDriver->getMoreDetailsOptions());

			$interface->assign('semanticData', json_encode($recordDriver->getSemanticData()));

			//Load Staff Details
			$interface->assign('staffDetails', $recordDriver->getStaffView());

			// Display Page
			$this->display('full-record.tpl', $recordDriver->getTitle(),'Search/home-sidebar.tpl', false);

		}
	}

	/**
	 * @param ExternalEContentDriver $recordDriver
	 */
	function loadCitations($recordDriver)
	{
		global $interface;

		$citationCount = 0;
		$formats = $recordDriver->getCitationFormats();
		foreach($formats as $current) {
			$interface->assign(strtolower($current), $recordDriver->getCitation($current));
			$citationCount++;
		}
		$interface->assign('citationCount', $citationCount);
	}

}