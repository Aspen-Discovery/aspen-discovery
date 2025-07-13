<?php

require_once ROOT_DIR . '/GroupedWorkSubRecordHomeAction.php';
require_once ROOT_DIR . '/sys/File/MARC.php';
require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';
require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';

class Record_Home extends GroupedWorkSubRecordHomeAction {
	public $marcRecord;

	public $record;

	public $isbn;
	public $issn;
	public $upc;

	public $description;

	function __construct() {
		parent::__construct();

		global $interface;
		global $timer;

		if (is_null($this->recordDriver) || !$this->recordDriver->isValid()) {  // initRecordDriverById itself does a validity check and returns null if not.
			$interface->assign('showStaffView', false);
			$this->display('invalidRecord.tpl', 'Invalid Record', '');
			die();
		}

		$hasGroupedWork = false;
		$groupedWork = $this->recordDriver->getGroupedWorkDriver();
		if (is_null($groupedWork) || !$groupedWork->isValid()) {  // initRecordDriverById itself does a validity check and returns null if not.
			$parentRecords = $this->recordDriver->getParentRecords();
			if (count($parentRecords) == 0) {
				//If the record is invalid, we only want to show the staff view to staff even if the stff view is normally displayed to general public.
				$interface->assign('showStaffView', $interface->getVariable('showStaffView') && UserAccount::isStaff());
				$interface->assign('invalidWork', true);
				$this->display('invalidRecord.tpl', 'Invalid Record', '');
				die();
			}
		}

		// Process MARC Data
		$marcRecord = $this->recordDriver->getMarcRecord();
		$timer->logTime("Loaded MARC Record");
		if ($marcRecord) {
			$this->marcRecord = $marcRecord;
			$interface->assign('marc', $marcRecord);

			$interface->assign('recordDriver', $this->recordDriver);

			//Check to see if there are lists the record is on
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('GroupedWork', $this->recordDriver->getPermanentId());
			$interface->assign('appearsOnLists', $appearsOnLists);

			$groupedWork->loadReadingHistoryIndicator();

			//Load information for display in the template rather than processing specific fields in the template
			$marcField = $marcRecord->getField('245');
			$recordTitle = $this->getSubfieldData($marcField, 'a');
			$interface->assign('recordTitle', $recordTitle);
			$recordTitleSubtitle = trim($this->concatenateSubfieldData($marcField, [
				'a',
				'b',
				'h',
				'n',
				'p',
			]));
			$recordTitleSubtitle = preg_replace('~\s+[/:]$~', '', $recordTitleSubtitle);
			$interface->assign('recordTitleSubtitle', $recordTitleSubtitle);
			$recordTitleWithAuth = trim($this->concatenateSubfieldData($marcField, [
				'a',
				'b',
				'h',
				'n',
				'p',
				'c',
			]));
			$interface->assign('recordTitleWithAuth', $recordTitleWithAuth);

			$marcField = $marcRecord->getField('100');
			if ($marcField) {
				$mainAuthor = $this->concatenateSubfieldData($marcField, [
					'a',
					'b',
					'c',
					'd',
				]);
				$interface->assign('mainAuthor', $mainAuthor);
			}

			$marcFields = $marcRecord->getFields('250');
			if ($marcFields) {
				$editionsThis = [];
				foreach ($marcFields as $marcField) {
					$editionsThis[] = $this->getSubfieldData($marcField, 'a');
				}
				$interface->assign('editionsThis', $editionsThis);
			}

			if ($this->recordDriver instanceof MarcRecordDriver) {
				$interface->assign('physicalDescriptions', $this->recordDriver->getPhysicalDescriptions());
			}else{
				$marcFields = $marcRecord->getFields('300');
				if ($marcFields) {
					$physicalDescriptions = [];
					foreach ($marcFields as $marcField) {
						$description = $this->concatenateSubfieldData($marcField, [
							'a',
							'b',
							'c',
							'e',
							'f',
							'g',
						]);
						if ($description != 'p. cm.') {
							$description = preg_replace("/[\/|;:]$/", '', $description);
							$description = preg_replace('/\bp\./', 'pages', $description);
							$physicalDescriptions[] = $description;
						}
					}
					$interface->assign('physicalDescriptions', $physicalDescriptions);
				}
			}

			// Get ISBN for cover and review use
			$mainIsbnSet = false;
			/** @var File_MARC_Data_Field[] $isbnFields */
			if ($isbnFields = $this->marcRecord->getFields('020')) {
				$isbns = [];
				//Use the first good ISBN we find.
				foreach ($isbnFields as $isbnField) {
					/** @var File_MARC_Subfield $isbnSubfieldA */
					if ($isbnSubfieldA = $isbnField->getSubfield('a')) {
						$tmpIsbn = trim($isbnSubfieldA->getData());
						if (strlen($tmpIsbn) > 0) {

							$isbns[] = $isbnSubfieldA->getData();
							$pos = strpos($tmpIsbn, ' ');
							if ($pos > 0) {
								$tmpIsbn = substr($tmpIsbn, 0, $pos);
							}
							$tmpIsbn = trim($tmpIsbn);
							if (strlen($tmpIsbn) > 0) {
								if (strlen($tmpIsbn) < 10) {
									$tmpIsbn = str_pad($tmpIsbn, 10, "0", STR_PAD_LEFT);
								}
								if (!$mainIsbnSet) {
									$this->isbn = $tmpIsbn;
									$interface->assign('isbn', $tmpIsbn);
									$mainIsbnSet = true;
								}
							}
						}
					}
				}
				if (isset($this->isbn)) {
					if (strlen($this->isbn) == 13) {
						require_once(ROOT_DIR . '/Drivers/marmot_inc/ISBNConverter.php');
						$this->isbn10 = ISBNConverter::convertISBN13to10($this->isbn);
					} else {
						$this->isbn10 = $this->isbn;
					}
					$interface->assign('isbn10', $this->isbn10);
				}
				$interface->assign('isbns', $isbns);
			}

			if ($upcField = $this->marcRecord->getField('024')) {
				/** @var File_MARC_Data_Field $upcField */
				if ($upcSubField = $upcField->getSubfield('a')) {
					$this->upc = trim($upcSubField->getData());
					$interface->assign('upc', $this->upc);
				}
			}


			if ($issnField = $this->marcRecord->getField('022')) {
				/** @var File_MARC_Data_Field $issnField */
				if ($issnSubField = $issnField->getSubfield('a')) {
					$this->issn = trim($issnSubField->getData());
					if ($pos = strpos($this->issn, ' ')) {
						$this->issn = substr($this->issn, 0, $pos);
					}
					$interface->assign('issn', $this->issn);
				}
			}

			//Get street date
			if ($streetDateField = $this->marcRecord->getField('263')) {
				$streetDate = $this->getSubfieldData($streetDateField, 'a');
				if ($streetDate != '') {
					$interface->assign('streetDate', $streetDate);
				}
			}

			if (!empty($this->recordDriver->getGroupedWorkDriver()->getMpaaRating())) {
				$interface->assign('mpaaRating', $this->recordDriver->getGroupedWorkDriver()->getMpaaRating());
			}

			$format = $this->recordDriver->getFormat();
			$interface->assign('recordFormat', $format);
			$format_category = $this->recordDriver->getFormatCategory();
			$interface->assign('format_category', $format_category);
			$interface->assign('recordLanguage', $this->recordDriver->getLanguage());

			$timer->logTime('Got detailed data from Marc Record');

			$notes = $this->recordDriver->getNotes();
			if (count($notes) > 0) {
				$interface->assign('notes', $notes);
			}
		} else {
			$interface->assign('error', 'Cannot Process MARC Record');

			$interface->assign('recordTitle', 'Unknown');
		}
		$timer->logTime('Processed the marc record');

		//Determine the cover to use
		$interface->assign('bookCoverUrl', $this->recordDriver->getBookcoverUrl('large'));


		$interface->assign('id', $this->id);
		if (substr($this->id, 0, 1) == '.') {
			$interface->assign('shortId', substr($this->id, 1));
		} else {
			$interface->assign('shortId', $this->id);
		}

		$_SESSION['returnToAction'] = $this->id;
		$_SESSION['returnToModule'] = 'Record';

		// Retrieve User Search History
		$this->lastSearch = isset($_SESSION['lastSearchURL']) ? $_SESSION['lastSearchURL'] : false;
		$interface->assign('lastSearch', $this->lastSearch);

		//Get Next/Previous Links
		$searchSource = !empty($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();

		//Load Staff Details
		$interface->assign('staffDetails', $this->recordDriver->getStaffView());
	}

	function launch() {
		global $interface;
		global $timer;

		$recordId = $this->id;

		$this->loadCitations();
		$timer->logTime('Loaded Citations');

		if (isset($_REQUEST['searchId'])) {
			$_SESSION['searchId'] = $_REQUEST['searchId'];
			$interface->assign('searchId', $_SESSION['searchId']);
		} elseif (isset($_SESSION['searchId'])) {
			$interface->assign('searchId', $_SESSION['searchId']);
		}

		$interface->assign('recordId', $recordId);

		// Set Show in Main Details Section options for templates
		// (needs to be set before moreDetailsOptions)
		global $library;
		foreach ($library->getGroupedWorkDisplaySettings()->showInMainDetails as $detailOption) {
			$interface->assign($detailOption, true);
		}

		//Get the actions for the record
		$actions = $this->recordDriver->getRecordActionsFromIndex();
		$interface->assign('actions', $actions);

		$interface->assign('moreDetailsOptions', $this->recordDriver->getMoreDetailsOptions());
		$exploreMoreInfo = $this->recordDriver->getExploreMoreInfo();
		$interface->assign('exploreMoreInfo', $exploreMoreInfo);

		$interface->assign('semanticData', json_encode($this->recordDriver->getSemanticData()));

		// Display Page
		$this->display('full-record.tpl', $this->recordDriver->getTitle(), '', false);

	}

	/**
	 * @param File_MARC_Data_Field[] $noteFields
	 * @return array
	 */
	function processNoteFields($noteFields) {
		$notes = [];
		/** File_MARC_Data_Field $marcField */
		foreach ($noteFields as $marcField) {
			/** @var File_MARC_Subfield $subfield */
			foreach ($marcField->getSubfields() as $subfield) {
				$note = $subfield->getData();
				if ($subfield->getCode() == 't') {
					$note = "&nbsp;&nbsp;&nbsp;" . $note;
				}
				$note = trim($note);
				if (strlen($note) > 0) {
					$notes[] = $note;
				}
			}
		}
		return $notes;
	}

	/**
	 * @param File_MARC_Data_Field $marcField
	 * @param string $subField
	 * @return string
	 */
	public function getSubfieldData($marcField, $subField) {
		if ($marcField) {
			//Account for cases where a subfield is repeated
			$subFields = $marcField->getSubfields($subField);
			$fieldData = '';
			/** @var File_MARC_Subfield $subFieldData */
			foreach ($subFields as $subFieldData) {
				if (strlen($fieldData) > 0) {
					$fieldData .= ' ';
				}
				$fieldData .= $subFieldData->getData();
			}
			return $fieldData;
		} else {
			return '';
		}
	}

	public function concatenateSubfieldData(File_MARC_Data_Field $marcField, array $requestedSubFields) : string {
		$value = '';
		$allSubfields = $marcField->getSubfields();
		foreach ($allSubfields as $subfield) {
			foreach ($requestedSubFields as $requestedSubField) {
				if ($subfield->getCode() == $requestedSubField) {
					$subFieldValue = $subfield->getData();
					if (strlen($subFieldValue) > 0) {
						$value .= ' ' . $subFieldValue;
					}
				}
			}
		}
		return $value;
	}

	function loadRecordDriver($id) {
		global $interface;
		if (strpos($id, ':')) {
			[
				$source,
				$id,
			] = explode(":", $id);
			$this->id = $id;
			$interface->assign('id', $this->id);
		} else {
			$source = 'ils';
			$this->id = $id;
		}
		if (substr($this->id, 0, 1) == 'b' && strlen($this->id) == 8) {
			//This is probably a Sierra/Millennium record without a check digit
			require_once ROOT_DIR . '/Drivers/Millennium.php';
			$this->id = '.' . $this->id . Millennium::getCheckDigitStatic($id);
		} elseif (substr($this->id, 0, 2) == '.b' && strlen($this->id) == 9) {
			require_once ROOT_DIR . '/Drivers/Millennium.php';
			$this->id = $this->id . Millennium::getCheckDigitStatic($id);
		}

		//Check to see if the record exists within the resources table
		$this->recordDriver = RecordDriverFactory::initRecordDriverById($source . ':' . $this->id);
	}
}