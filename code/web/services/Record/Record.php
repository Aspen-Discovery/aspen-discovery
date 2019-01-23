<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR  . '/Action.php';

require_once 'File/MARC.php';

require_once ROOT_DIR  . '/sys/Language.php';

require_once ROOT_DIR  . '/RecordDrivers/Factory.php';
require_once ROOT_DIR  . '/RecordDrivers/MarcRecord.php';

abstract class Record_Record extends Action
{
	public $source;
	public $id;

	/**
	 * marc record in File_Marc object
	 */
	protected $recordDriver;
	public $marcRecord;

	public $record;
	public $similarTitles;

	public $isbn;
	public $issn;
	public $upc;

	public $cacheId;

	/** @var  Solr */
	public $db;

	public $description;
	protected $mergedRecords = array();

	function __construct($subAction = false, $record_id = null)
	{
		global $interface;
		global $configArray;
		global $library;
		global $timer;

		$interface->assign('page_body_style', 'sidebar_left');

		//Load basic information needed in subclasses
		if ($record_id == null || !isset($record_id)){
			$this->id = $_GET['id'];
		}else{
			$this->id = $record_id;
		}
		if (strpos($this->id, ':')){
			list($source, $id) = explode(":", $this->id);
			$this->source = $source;
			$this->id = $id;
		}else{
			$this->source = 'ils';
		}
		$interface->assign('id', $this->id);

		//Check to see if the record exists within the resources table
		$this->recordDriver = RecordDriverFactory::initRecordDriverById($this->source . ':' . $this->id);
		if (is_null($this->recordDriver) || !$this->recordDriver->isValid()){  // initRecordDriverById itself does a validity check and returns null if not.
			$this->display('invalidRecord.tpl', 'Invalid Record');
			die();
		}
		$groupedWork = $this->recordDriver->getGroupedWorkDriver();
		if (is_null($groupedWork) || !$groupedWork->isValid()){  // initRecordDriverById itself does a validity check and returns null if not.
			$this->display('invalidRecord.tpl', 'Invalid Record');
			die();
		}

		if ($configArray['Catalog']['ils'] == 'Millennium' || $configArray['Catalog']['ils'] == 'Sierra'){
			$classicId = substr($this->id, 1, strlen($this->id) -2);
			$interface->assign('classicId', $classicId);
			$millenniumScope = $interface->getVariable('millenniumScope');
			if(isset($configArray['Catalog']['linking_url'])){
				$interface->assign('classicUrl', $configArray['Catalog']['linking_url'] . "/record=$classicId&amp;searchscope={$millenniumScope}");
			}

		}elseif ($configArray['Catalog']['ils'] == 'Koha'){
			$interface->assign('classicId', $this->id);
			$interface->assign('classicUrl', $configArray['Catalog']['url'] . '/cgi-bin/koha/opac-detail.pl?biblionumber=' . $this->id);
			$interface->assign('staffClientUrl', $configArray['Catalog']['staffClientUrl'] . '/cgi-bin/koha/catalogue/detail.pl?biblionumber=' . $this->id);
		}elseif ($configArray['Catalog']['ils'] == 'CarlX'){
			$shortId = str_replace('CARL', '', $this->id);
			$shortId = ltrim($shortId, '0');
			$interface->assign('staffClientUrl', $configArray['Catalog']['staffClientUrl'] . '/Items/' . $shortId);
		}

		// Process MARC Data
		$marcRecord = $this->recordDriver->getMarcRecord();
		if ($marcRecord) {
			$this->marcRecord = $marcRecord;
			$interface->assign('marc', $marcRecord);

			$interface->assign('recordDriver', $this->recordDriver);

			//Load information for display in the template rather than processing specific fields in the template
			$marcField = $marcRecord->getField('245');
			$recordTitle = $this->getSubfieldData($marcField, 'a');
			$interface->assign('recordTitle', $recordTitle);
			$recordTitleSubtitle = trim($this->concatenateSubfieldData($marcField, array('a', 'b', 'h', 'n', 'p')));
			$recordTitleSubtitle = preg_replace('~\s+[\/:]$~', '', $recordTitleSubtitle);
			$interface->assign('recordTitleSubtitle', $recordTitleSubtitle);
			$recordTitleWithAuth = trim($this->concatenateSubfieldData($marcField, array('a', 'b', 'h', 'n', 'p', 'c')));
			$interface->assign('recordTitleWithAuth', $recordTitleWithAuth);

			$marcField = $marcRecord->getField('100');
			if ($marcField){
				$mainAuthor = $this->concatenateSubfieldData($marcField, array('a', 'b', 'c', 'd'));
				$interface->assign('mainAuthor', $mainAuthor);
			}

			$marcFields = $marcRecord->getFields('250');
			if ($marcFields){
				$editionsThis = array();
				foreach ($marcFields as $marcField){
					$editionsThis[] = $this->getSubfieldData($marcField, 'a');
				}
				$interface->assign('editionsThis', $editionsThis);
			}

			$marcFields = $marcRecord->getFields('300');
			if ($marcFields){
				$physicalDescriptions = array();
				foreach ($marcFields as $marcField){
					$description = $this->concatenateSubfieldData($marcField, array('a', 'b', 'c', 'e', 'f', 'g'));
					if ($description != 'p. cm.'){
						$description = preg_replace("/[\/|;:]$/", '', $description);
						$description = preg_replace("/p\./", 'pages', $description);
						$physicalDescriptions[] = $description;
					}
				}
				$interface->assign('physicalDescriptions', $physicalDescriptions);
			}

			// Get ISBN for cover and review use
			$mainIsbnSet = false;
			/** @var File_MARC_Data_Field[] $isbnFields */
			if ($isbnFields = $this->marcRecord->getFields('020')) {
				$isbns = array();
				//Use the first good ISBN we find.
				foreach ($isbnFields as $isbnField){
					/** @var File_MARC_Subfield $isbnSubfieldA */
					if ($isbnSubfieldA = $isbnField->getSubfield('a')) {
						$tmpIsbn = trim($isbnSubfieldA->getData());
						if (strlen($tmpIsbn) > 0){

							$isbns[] = $isbnSubfieldA->getData();
							$pos = strpos($tmpIsbn, ' ');
							if ($pos > 0) {
								$tmpIsbn = substr($tmpIsbn, 0, $pos);
							}
							$tmpIsbn = trim($tmpIsbn);
							if (strlen($tmpIsbn) > 0){
								if (strlen($tmpIsbn) < 10){
									$tmpIsbn = str_pad($tmpIsbn, 10, "0", STR_PAD_LEFT);
								}
								if (!$mainIsbnSet){
									$this->isbn = $tmpIsbn;
									$interface->assign('isbn', $tmpIsbn);
									$mainIsbnSet = true;
								}
							}
						}
					}
				}
				if (isset($this->isbn)){
					if (strlen($this->isbn) == 13){
						require_once(ROOT_DIR  . '/Drivers/marmot_inc/ISBNConverter.php');
						$this->isbn10 = ISBNConverter::convertISBN13to10($this->isbn);
					}else{
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
					//Also setup GoldRush link
					if (isset($library) && strlen($library->goldRushCode) > 0){
						$interface->assign('goldRushLink', "http://goldrush.coalliance.org/index.cfm?fuseaction=Search&amp;inst_code={$library->goldRushCode}&amp;search_type=ISSN&amp;search_term={$this->issn}");
					}
				}
			}

			//Get street date
			if ($streetDateField = $this->marcRecord->getField('263')) {
				$streetDate = $this->getSubfieldData($streetDateField, 'a');
				if ($streetDate != ''){
					$interface->assign('streetDate', $streetDate);
				}
			}

			if ($mpaaField = $this->marcRecord->getField('521')) {
				$interface->assign('mpaaRating', $this->getSubfieldData($mpaaField, 'a'));
			}

			$format = $this->recordDriver->getFormat();
			$interface->assign('recordFormat', $format);
			$format_category = $format = $this->recordDriver->getFormatCategory();
			$interface->assign('format_category', $format_category);
			$interface->assign('recordLanguage', $this->recordDriver->getLanguage());

			$timer->logTime('Got detailed data from Marc Record');

			$notes = $this->recordDriver->getNotes();
			if (count($notes) > 0){
				$interface->assign('notes', $notes);
			}

			// Define External Content Provider
			if ($this->marcRecord->getField('020')) {
				if (isset($configArray['Content']['reviews'])) {
					$interface->assign('hasReviews', true);
				}
				if (isset($configArray['Content']['excerpts'])) {
					$interface->assign('hasExcerpt', true);
				}
			}
		} else {
			$interface->assign('error', 'Cannot Process MARC Record');

			$interface->assign('recordTitle', 'Unknown');
		}
		$timer->logTime('Processed the marc record');

		$timer->logTime("Got basic data from Marc Record subaction = $subAction, record_id = $record_id");
		//stop if this is not the main action.
		if ($subAction == true){
			return;
		}

		//Determine the cover to use
		$interface->assign('bookCoverUrl', $this->recordDriver->getBookcoverUrl('large'));

		//Load accelerated reader data
		if (isset($record['accelerated_reader_interest_level'])){
			$arData = array(
				'interestLevel' => $record['accelerated_reader_interest_level'],
				'pointValue' => $record['accelerated_reader_point_value'],
				'readingLevel' => $record['accelerated_reader_reading_level']
			);
			$interface->assign('arData', $arData);
		}

		if (isset($record['lexile_score']) && $record['lexile_score'] > -1){
			$lexileScore = $record['lexile_score'];
			if (isset($record['lexile_code'])){
				$lexileScore = $record['lexile_code'] . $lexileScore;
			}
			$interface->assign('lexileScore', $lexileScore . 'L');
		}

		if (isset($record['fountas_pinnell'])){
			$fountasPinnell = $record['fountas_pinnell'];
			$interface->assign('fountasPinnell', $fountasPinnell);
		}

		//Do actions needed if this is the main action.

		//$interface->caching = 1;
		$interface->assign('id', $this->id);
		if (substr($this->id, 0, 1) == '.'){
			$interface->assign('shortId', substr($this->id, 1));
		}else{
			$interface->assign('shortId', $this->id);
		}

		$interface->assign('addHeader', '<link rel="alternate" type="application/rdf+xml" title="RDF Representation" href="' . $configArray['Site']['path']  . '/Record/' . urlencode($this->id) . '/RDF" />');

		// Define Default Tab
		$tab = (isset($_GET['action'])) ? $_GET['action'] : 'Description';
		$interface->assign('tab', $tab);

		if (isset($_REQUEST['detail'])){
			$detail = strip_tags($_REQUEST['detail']);
			$interface->assign('defaultDetailsTab', $detail);
		}

		// Retrieve User Search History
		$interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ?
		$_SESSION['lastSearchURL'] : false);

		$this->cacheId = 'Record|' . $_GET['id'] . '|' . get_class($this);

		// Send down text for inclusion in breadcrumbs
		$interface->assign('breadcrumbText', $this->recordDriver->getBreadcrumb());

		// Send down legal export formats (if any):
		$interface->assign('exportFormats', $this->recordDriver->getExportFormats());

		// Set AddThis User
		$interface->assign('addThis', isset($configArray['AddThis']['key']) ?
		$configArray['AddThis']['key'] : false);

		//Get Next/Previous Links
		$searchSource = isset($_REQUEST['searchSource']) ? $_REQUEST['searchSource'] : 'local';
		$searchObject = SearchObjectFactory::initSearchObject();
		$searchObject->init($searchSource);
		$searchObject->getNextPrevLinks();

		//Load Staff Details
		$interface->assign('staffDetails', $this->recordDriver->getStaffView());
	}

	/**
	 * @param File_MARC_Data_Field[] $noteFields
	 * @return array
	 */
	function processNoteFields($noteFields){
		$notes = array();
		/** File_MARC_Data_Field $marcField */
		foreach ($noteFields as $marcField){
			/** @var File_MARC_Subfield $subfield */
			foreach ($marcField->getSubfields() as $subfield){
				$note = $subfield->getData();
				if ($subfield->getCode() == 't'){
					$note = "&nbsp;&nbsp;&nbsp;" . $note;
				}
				$note = trim($note);
				if (strlen($note) > 0){
					$notes[] = $note;
				}
			}
		}
		return $notes;
	}

	/**
	 * Record a record hit to the statistics index when stat tracking is enabled;
	 * this is called by the Home action.
	 */
	public function recordHit(){
	}

	/**
	 * @param File_MARC_Data_Field $marcField
	 * @param File_MARC_Subfield $subField
	 * @return string
	 */
	public function getSubfieldData($marcField, $subField){
		if ($marcField){
			return $marcField->getSubfield($subField) ? $marcField->getSubfield($subField)->getData() : '';
		}else{
			return '';
		}
	}
	public function concatenateSubfieldData($marcField, $subFields){
		$value = '';
		foreach ($subFields as $subField){
			$subFieldValue = $this->getSubfieldData($marcField, $subField);
			if (strlen($subFieldValue) > 0){
				$value .= ' ' . $subFieldValue;
			}
		}
		return $value;
	}
}
