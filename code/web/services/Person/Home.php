<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
require_once ROOT_DIR . '/sys/Genealogy/Person.php';

class Person_Home extends Action {
	private $record;
	/** @var PersonRecord */
	private $recordDriver;
	private $lastSearch;

	function __construct() {
		global $interface;
		global $timer;
		parent::__construct(false);
		$user = UserAccount::getLoggedInUser();

		//Check to see if a user is logged in with admin permissions
		if ($user && UserAccount::userHasPermission('Administer Genealogy')) {
			$interface->assign('userIsAdmin', true);
		} else {
			$interface->assign('userIsAdmin', false);
		}

		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';

		//Load basic information needed in subclasses
		$id = $_GET['id'];

		// Setup Search Engine Connection
		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GenealogySolrConnector.php';
		$timer->logTime('Include search engine');

		// Initialise from the current search globals
		/** @var SearchObject_GenealogySearcher $db */
		$db = SearchObjectFactory::initSearchObject('Genealogy');
		$db->init($searchSource);

		// Retrieve Full Marc Record
		if (!($record = $db->getRecord($id))) {
			AspenError::raiseError(new AspenError('Record Does Not Exist'));
		}
		$this->record = $record;

		//Load person from the database to get additional information
		$person = new Person();
		$person->get($id);
		$record['picture'] = $person->picture;

		$interface->assign('record', $record);
		$interface->assign('person', $person);
		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$interface->assign('recordDriver', $this->recordDriver);
		$timer->logTime('Initialized the Record Driver');

		//Check to see if there are lists the record is on
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$appearsOnLists = UserList::getUserListsForRecord('Genealogy', $this->recordDriver->getPermanentId());
		$interface->assign('appearsOnLists', $appearsOnLists);

		$marriages = [];
		$personMarriages = $person->getMarriages();
		if (isset($personMarriages)) {
			foreach ($personMarriages as $marriage) {
				$marriageArray = (array)$marriage;
				$marriageArray['formattedMarriageDate'] = $person->formatPartialDate($marriage->marriageDateDay, $marriage->marriageDateMonth, $marriage->marriageDateYear);
				$marriages[] = $marriageArray;
			}
		}
		$interface->assign('marriages', $marriages);
		$obituaries = [];
		$personObituaries = $person->getObituaries();
		if (isset($personObituaries)) {
			foreach ($personObituaries as $obit) {
				$obitArray = (array)$obit;
				$obitArray['formattedObitDate'] = $person->formatPartialDate($obit->dateDay, $obit->dateMonth, $obit->dateYear);
				$obituaries[] = $obitArray;
			}
		}
		$interface->assign('obituaries', $obituaries);

		//Do actions needed if this is the main action.
		$interface->assign('id', $id);

		// Retrieve User Search History
		$this->lastSearch = isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false;
		$interface->assign('lastSearch', $this->lastSearch);

		$formattedBirthdate = $person->formatPartialDate($person->birthDateDay, $person->birthDateMonth, $person->birthDateYear);
		$interface->assign('birthDate', $formattedBirthdate);

		$formattedDeathdate = $person->formatPartialDate($person->deathDateDay, $person->deathDateMonth, $person->deathDateYear);
		$interface->assign('deathDate', $formattedDeathdate);

		//Setup next and previous links based on the search results.
		if (isset($_REQUEST['searchId'])) {
			//rerun the search
			$s = new SearchEntry();
			$s->id = $_REQUEST['searchId'];
			$interface->assign('searchId', $_REQUEST['searchId']);
			$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
			$interface->assign('page', $currentPage);

			$s->find();
			if ($s->getNumResults() > 0) {
				$s->fetch();
				$minSO = unserialize($s->search_object);
				$searchObject = SearchObjectFactory::deminify($minSO);
				$searchObject->setPage($currentPage);
				//Run the search
				$result = $searchObject->processSearch(true, false, false);

				//Check to see if we need to run a search for the next or previous page
				$currentResultIndex = $_REQUEST['recordIndex'] - 1;
				$recordsPerPage = $searchObject->getLimit();

				if (($currentResultIndex) % $recordsPerPage == 0 && $currentResultIndex > 0) {
					//Need to run a search for the previous page
					$interface->assign('previousPage', $currentPage - 1);
					$previousSearchObject = clone $searchObject;
					$previousSearchObject->setPage($currentPage - 1);
					$previousSearchObject->processSearch(true, false, false);
					$previousResults = $previousSearchObject->getResultRecordSet();
				} elseif (($currentResultIndex + 1) % $recordsPerPage == 0 && ($currentResultIndex + 1) < $searchObject->getResultTotal()) {
					//Need to run a search for the next page
					$nextSearchObject = clone $searchObject;
					$interface->assign('nextPage', $currentPage + 1);
					$nextSearchObject->setPage($currentPage + 1);
					$nextSearchObject->processSearch(true, false, false);
					$nextResults = $nextSearchObject->getResultRecordSet();
				}

				//If we get an error executing the search, just eat it for now.
				if (!($result instanceof AspenError)) {
					if ($searchObject->getResultTotal() > 0) {
						$recordSet = $searchObject->getResultRecordSet();
						//Record set is 0 based, but we are passed a 1 based index
						if ($currentResultIndex > 0) {
							if (isset($previousResults)) {
								$previousRecord = $previousResults[count($previousResults) - 1];
							} else {
								$previousRecord = $recordSet[$currentResultIndex - 1 - (($currentPage - 1) * $recordsPerPage)];
							}
							$interface->assign('previousId', $previousRecord['id']);
							//Convert back to 1 based index
							$interface->assign('previousIndex', $currentResultIndex - 1 + 1);
							$interface->assign('previousTitle', $previousRecord['title']);
						}
						if ($currentResultIndex + 1 < $searchObject->getResultTotal()) {

							if (isset($nextResults)) {
								$nextRecord = $nextResults[0];
							} else {
								$nextRecord = $recordSet[$currentResultIndex + 1 - (($currentPage - 1) * $recordsPerPage)];
							}
							$interface->assign('nextId', $nextRecord['id']);
							//Convert back to 1 based index
							$interface->assign('nextIndex', $currentResultIndex + 1 + 1);
							$interface->assign('nextTitle', $nextRecord['title']);
						}

					}
				}
			}
			$timer->logTime('Got next/previous links');
		}
	}

	function launch() {
		$titleField = $this->recordDriver->getName(); //$this->record['firstName'] . ' ' . $this->record['lastName'];

		// Display Page
		$this->display('full-record.tpl', $titleField, '');
	}


	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Genealogy Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}