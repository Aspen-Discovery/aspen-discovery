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

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/ISBN.php';
require_once ROOT_DIR . '/CatalogConnection.php';

/**
 * API methods related to getting information about specific items.
 *
 * Copyright (C) Douglas County Libraries 2011.
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
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 */
class ItemAPI extends Action {
	/** @var  Millennium|DriverInterface */
	protected $catalog;

	public $id;

	/**
	 * @var MarcRecord|IndexRecord
	 * marc record in File_Marc object
	 */
	protected $recordDriver;
	public $marcRecord;

	public $record;

	public $isbn;
	public $issn;
	public $upc;

	public $cacheId;

	/** @var  Solr $db */
	public $db;

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			// Connect to Catalog
			if ($method != 'getBookcoverById' && $method != 'getBookCover'){
				$this->catalog = CatalogFactory::getCatalogConnectionInstance();;
				//header('Content-type: application/json');
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			}

			if (in_array($method, array('getDescriptionByRecordId', 'getDescriptionByTitleAndAuthor'))){
				$output = json_encode($this->$method());
			}else{
				$output = json_encode(array('result'=>$this->$method()));
			}
		} else {
			$output = json_encode(array('error'=>"invalid_method '$method'"));
		}

		echo $output;
	}

	function getDescriptionByTitleAndAuthor(){
		global $configArray;

		//Load the title and author from the data passed in
		$title = trim($_REQUEST['title']);
		$author = trim($_REQUEST['author']);

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var SearchObject_Solr db */
		$this->db = new $class($url);

		//Setup the results to return from the API method
		$results = array();

		//Search the database by title and author
		if ($title && $author){
			$searchResults = $this->db->search("$title $author");
		}elseif ($title){
			$searchResults = $this->db->search("title:$title");
		}elseif ($author){
			$searchResults = $this->db->search("author:$author");
		}else{
			$results = array(
				'result' => false,
				'message' => 'Please enter a title and/or author'
			);
			return $results;
		}

		if ($searchResults['response']['numFound'] == 0){
			$results = array(
				'result' => false,
				'message' => 'Sorry, we could not find a description for that title and author'
			);
		} else{
			$firstRecord = $searchResults['response']['docs'][0];
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$groupedWork = new GroupedWorkDriver($firstRecord);

			$results = array(
				'result' => true,
				'message' => 'Found a summary for record ' . $firstRecord['title_display'] . ' by ' . $firstRecord['author_display'],
				'recordsFound' => $searchResults['response']['numFound'],
				'description' => $groupedWork->getDescription()
			);
		}
		return $results;
	}

	function getDescriptionByRecordId(){
		global $configArray;

		//Load the record id that the user wants to search for
		$recordId = trim($_REQUEST['recordId']);

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		/** @var SearchObject_Solr db */
		$this->db = new $class($url);

		//Search the database by title and author
		if ($recordId){
			if (preg_match('/^b\d{7}[\dx]$/', $recordId)){
				$recordId = '.' . $recordId;
			}
			$searchResults = $this->db->search("$recordId", 'Id');
		}else{
			$results = array(
				'result' => false,
				'message' => 'Please enter the record Id to look for'
			);
			return $results;
		}

		if ($searchResults['response']['numFound'] == 0){
			$results = array(
				'result' => false,
				'message' => 'Sorry, we could not find a description for that record id'
			);
		} else{
			$firstRecord = $searchResults['response']['docs'][0];
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$groupedWork = new GroupedWorkDriver($firstRecord);

			$results = array(
				'result' => true,
				'message' => 'Found a summary for record ' . $firstRecord['title_display'] . ' by ' . $firstRecord['author_display'],
				'recordsFound' => $searchResults['response']['numFound'],
				'description' => $groupedWork->getDescription()
			);
		}
		return $results;
	}

	/**
	 * Load a marc record for a particular id from the server
	 */
	function getMarcRecord(){
		global $configArray;
		$id = $_REQUEST['id'];
		$shortId = str_replace('.', '', $id);
		$firstChars = substr($shortId, 0, 4);
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=\"".$id.".mrc\"");
		$individualName = $configArray['Reindex']['individualMarcPath'] . "/{$firstChars}/{$shortId}.mrc";
		readfile($individualName);
		die();
	}

	/**
	 * Get information about a particular item and return it as JSON
	 */
	function getItem(){
		global $timer;
		global $configArray;
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			return array('error', 'Record does not exist');
		}
		$this->record = $record;

		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$timer->logTime('Initialized the Record Driver');

		//Generate basic information from the marc file to make display easier.
		if (isset($record['isbn'])){
			$itemData['isbn'] = $record['isbn'][0];
		}
		if (isset($record['upc'])){
			$itemData['upc'] = $record['upc'][0];
		}
		if (isset($record['issn'])){
			$itemData['issn'] = $record['issn'][0];
		}
		$itemData['title'] = $record['title'];
		$itemData['author'] = $record['author'];
		$itemData['publisher'] = $record['publisher'];
		$itemData['allIsbn'] = $record['isbn'];
		$itemData['allUpc'] = isset($record['upc']) ? $record['upc'] : null;
		$itemData['allIssn'] = isset($record['issn']) ? $record['issn'] : null;
		$itemData['edition'] = isset($record['edition']) ? $record['edition'] : null;
		$itemData['callnumber'] = isset($record['callnumber']) ? $record['callnumber'] : null;
		$itemData['genre'] = isset($record['genre']) ? $record['genre'] : null;
		$itemData['series'] = isset($record['series']) ? $record['series'] : null;
		$itemData['physical'] = $record['physical'];
		$itemData['lccn'] = isset($record['lccn']) ? $record['lccn'] : null;
		$itemData['contents'] = isset($record['contents']) ? $record['contents'] : null;

		$itemData['format'] = isset($record['format']) ? $record['format'] : null;
		$itemData['formatCategory'] = isset($record['format_category']) ? $record['format_category'][0] : null;
		$itemData['language'] = $record['language'];

		//Retrieve description from MARC file
		$itemData['description'] = $this->recordDriver->getDescriptionFast();

		//setup 5 star ratings
		$ratingData = $this->recordDriver->getRatingData();
		$itemData['ratingData'] = $ratingData;

		return $itemData;
	}

	function getBasicItemInfo(){
		global $timer;
		global $configArray;
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		$this->record = $record;
		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$timer->logTime('Initialized the Record Driver');

		// Process MARC Data
		require_once ROOT_DIR . '/sys/MarcLoader.php';
		$marcRecord = MarcLoader::loadMarcRecordFromRecord($record);
		if ($marcRecord) {
			$this->marcRecord = $marcRecord;
		} else {
			$itemData['error'] = 'Cannot Process MARC Record';
		}
		$timer->logTime('Processed the marc record');

		// Get ISBN for cover and review use
		if ($isbnFields = $this->marcRecord->getFields('020')) {
			//Use the first good ISBN we find.
			/** @var File_MARC_Data_Field $isbnField */
			foreach ($isbnFields as $isbnField){
				if ($isbnSubfield = $isbnField->getSubfield('a')) {
					$this->isbn = trim($isbnSubfield->getData());
					if ($pos = strpos($this->isbn, ' ')) {
						$this->isbn = substr($this->isbn, 0, $pos);
					}
					if (strlen($this->isbn) < 10){
						$this->isbn = str_pad($this->isbn, 10, "0", STR_PAD_LEFT);
					}
					$itemData['isbn'] = $this->isbn;
					break;
				}
			}
		}
		/** @var File_MARC_Data_Field $upcField */
		if ($upcField = $this->marcRecord->getField('024')) {
			if ($upcSubField = $upcField->getSubfield('a')) {
				$this->upc = trim($upcSubField->getData());
				$itemData['upc'] = $this->upc;
			}
		}
		/** @var File_MARC_Data_Field $issnField */
		if ($issnField = $this->marcRecord->getField('022')) {
			if ($issnSubfield = $issnField->getSubfield('a')) {
				$this->issn = trim($issnSubfield->getData());
				if ($pos = strpos($this->issn, ' ')) {
					$this->issn = substr($this->issn, 0, $pos);
				}
				$itemData['issn'] = $this->issn;
			}
		}
		$timer->logTime('Got UPC, ISBN, and ISSN');

		//Generate basic information from the marc file to make display easier.
		$itemData['title'] = $record['title'];
		$itemData['author'] = isset($record['author']) ? $record['author'] : (isset($record['author2']) ? $record['author2'][0] : '');
		$itemData['publisher'] = $record['publisher'];
		$itemData['allIsbn'] = $record['isbn'];
		$itemData['allUpc'] = $record['upc'];
		$itemData['allIssn'] = $record['issn'];
		$itemData['issn'] = $record['issn'];
		$itemData['format'] = isset($record['format']) ? $record['format'][0] : '';
		$itemData['formatCategory'] = $record['format_category'][0];
		$itemData['language'] = $record['language'];
		$itemData['cover'] = $configArray['Site']['path'] . "/bookcover.php?id={$itemData['id']}&issn={$itemData['issn']}&isbn={$itemData['isbn']}&upc={$itemData['upc']}&category={$itemData['formatCategory']}&format={$itemData['format'][0]}";

		//Retrieve description from MARC file
		$description = '';
		/** @var File_MARC_Data_Field $descriptionField */
		if ($descriptionField = $this->marcRecord->getField('520')) {
			if ($descriptionSubfield = $descriptionField->getSubfield('a')) {
				$description = trim($descriptionSubfield->getData());
			}
		}
		$itemData['description'] = $description;

		//setup 5 star ratings
		$itemData['ratingData'] = $this->recordDriver->getRatingData();
		$timer->logTime('Got 5 star data');

		return $itemData;
	}

	function getItemAvailability(){
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		$fullId = 'ils:' . $this->id;

		//Rather than calling the catalog, update to load information from the index
		//Need to match historical data so we don't break EBSCO
		$recordDriver = RecordDriverFactory::initRecordDriverById($fullId);
		if ($recordDriver->isValid()){
			$copies = $recordDriver->getCopies();
			$holdings = array();
			$i = 0;
			foreach ($copies as $copy) {
				$key = $copy['shelfLocation'];
				$key = preg_replace('~\W~', '_', $key);
				$holdings[$key][] = array(
						'location' => $copy['shelfLocation'],
						'callnumber' => $copy['callNumber'],
						'status' => $copy['status'],
						'dueDate' => '',
						'statusFull' => $copy['status'],
						'statusfull' => $copy['status'],
						'id' => $fullId,
						'number' =>  $i++,
						'type' => 'holding',
						'availability' => $copy['available'],
						'holdable' => $copy['holdable'] ? 1 : 0,
						'bookable' => $copy['bookable'] ? 1 : 0,
						'libraryDisplayName' => $copy['shelfLocation'],
						'section' => $copy['section'],
						'sectionId' => $copy['sectionId'],
						'lastCheckinDate' => $copy['lastCheckinDate'],
				);
			}
			$itemData['holdings'] = $holdings;
		}

		//Update to use same method of loading that we do within AJAX
		/*try {
			$catalog = CatalogFactory::getCatalogConnectionInstance();;
			$timer->logTime("Connected to catalog");
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
			return null;
		}

		if ($catalog->status) {
			$result = $catalog->getHolding($fullId);
			$timer->logTime("Loaded Holding Data from catalog");
			if (PEAR_Singleton::isError($result)) {
				PEAR_Singleton::raiseError($result);
			}
			if (count($result)) {
				$holdings = array();
				$issueSummaries = array();
				foreach ($result as $copy) {
					if (isset($copy['type']) && $copy['type'] == 'issueSummary') {
						$issueSummaries = $result;
						break;
					} else {
						$hasLastCheckinData = (isset($copy['lastCheckinDate']) && $copy['lastCheckinDate'] != null) || $hasLastCheckinData; // if $hasLastCheckinData was true keep that value even when first check is false.
						// flag for at least 1 lastCheckinDate

						$key = $copy['location'];
						$key = preg_replace('~\W~', '_', $key);
						$holdings[$key][] = $copy;
					}
				}
				if (isset($issueSummaries) && count($issueSummaries) > 0) {
					$itemData['holdings'] = $issueSummaries;
				} else {
					$itemData['holdings'] = $holdings;
				}
			} else {
				$itemData['holdings'] = array();
			}
		}*/

		return $itemData;
	}

	function getBookcoverById(){
		$record = $this->loadSolrRecord($_GET['id']);
		$isbn = isset($record['isbn']) ? ISBN::normalizeISBN($record['isbn'][0]) : null;
		$upc = isset($record['upc']) ? $record['upc'][0] : null;
		$id = isset($record['id']) ? $record['id'][0] : null;
		$issn = isset($record['issn']) ? $record['issn'][0] : null;
		$formatCategory = isset($record['format_category']) ? $record['format_category'][0] : null;
		$this->getBookCover($isbn, $upc, $formatCategory, $id, $issn);
	}

	function getBookCover($isbn = null, $upc = null, $formatCategory = null, $size = null, $id = null, $issn = null){
		if (is_null($isbn)) {$isbn = $_GET['isbn'];}
		$_GET['isn'] = ISBN::normalizeISBN($isbn);
		if (is_null($issn)) {$issn = $_GET['issn'];}
		$_GET['iss'] = $issn;
		if (is_null($upc)) {$upc = $_GET['upc'];}
		$_GET['upc'] = $upc;
		if (is_null($formatCategory)) {$formatCategory = $_GET['formatCategory'];}
		$_GET['category'] = $formatCategory;
		if (is_null($size)) {$size = isset($_GET['size']) ? $_GET['size'] : 'small';}
		$_GET['size'] = $size;
		if (is_null($id)) {$id = $_GET['id'];}
		$_GET['id'] = $id;
		include_once(ROOT_DIR . '/bookcover.php');
	}

	function clearBookCoverCacheById(){
		$id = strip_tags($_REQUEST['id']);
		$sizes = array('small', 'medium', 'large');
		$extensions = array('jpg', 'gif', 'png');
		$record = $this->loadSolrRecord($id);
		$filenamesToCheck = array();
		$filenamesToCheck[] = $id;
		if (isset($record['isbn'])){
			$isbns = $record['isbn'];
			foreach ($isbns as $isbn){
				$filenamesToCheck[] = preg_replace('/[^0-9xX]/', '', $isbn);
			}
		}
		if (isset($record['upc'])){
			$upcs = $record['upc'];
			if (isset($upcs)){
				$filenamesToCheck = array_merge($filenamesToCheck, $upcs);
			}
		}
		$deletedFiles = array();
		global $configArray;
		$coverPath = $configArray['Site']['coverPath'];
		foreach ($filenamesToCheck as $filename){
			foreach ($extensions as $extension){
				foreach ($sizes as $size){
					$tmpFilename = "$coverPath/$size/$filename.$extension";
					if (file_exists($tmpFilename)){
						$deletedFiles[] = $tmpFilename;
						unlink($tmpFilename);
					}
				}
			}
		}

		return array('deletedFiles' => $deletedFiles);
	}

	public function getCopyAndHoldCounts(){
		if (!isset($_REQUEST['recordId']) || strlen($_REQUEST['recordId']) == 0){
			return array('error' => 'Please provide a record to load data for');
		}
		$recordId = $_REQUEST['recordId'];
		/** @var GroupedWorkDriver|MarcRecord|OverDriveRecordDriver|ExternalEContentDriver $driver */
		$driver = RecordDriverFactory::initRecordDriverById($recordId);
		if ($driver == null || !$driver->isValid()){
			return array('error' => 'Sorry we could not find a record with that ID');
		}else{
			if ($driver instanceof GroupedWorkDriver) {
				/** @var GroupedWorkDriver $driver */
				$manifestations = $driver->getRelatedManifestations();
				$returnData = array();
				foreach ($manifestations as $manifestation){
					$manifestationSummary = array(
							'format' => $manifestation['format'],
							'copies' => $manifestation['copies'],
							'availableCopies' => $manifestation['availableCopies'],
							'numHolds' => $manifestation['numHolds'],
							'available' => $manifestation['available'],
							'isEContent' => $manifestation['isEContent'],
							'groupedStatus' => $manifestation['groupedStatus'],
							'numRelatedRecords' => $manifestation['numRelatedRecords'],
					);
					foreach ($manifestation['relatedRecords'] as $relatedRecord){
						$manifestationSummary['relatedRecords'][] = $relatedRecord['id'];
					}
					$returnData[] = $manifestationSummary;
				}
				return $returnData;
			}elseif ($driver instanceof OverDriveRecordDriver){
				/** @var OverDriveRecordDriver $driver */
				$copies = count($driver->getItems());
				$holds = $driver->getNumHolds();
				return array(
						'copies' => $copies,
						'holds' => $holds,
				);
			}elseif ($driver instanceof ExternalEContentDriver || $driver instanceof HooplaRecordDriver){
				/** @var ExternalEContentDriver $driver */
				return array(
						'copies' => 1,
						'holds' => 0,
				);
			}else{
				/** @var MarcRecord| $driver */
				$copies = count($driver->getCopies());
				$holds = $driver->getNumHolds();
				return array(
						'copies' => $copies,
						'holds' => $holds,
				);
			}
		}
	}

	public function loadSolrRecord($id){
		global $configArray;
		//Load basic information
		if (isset($id)){
			$this->id = $id;
		}else{
			$this->id = $_GET['id'];
		}

		$itemData['id'] = $this->id;

		// Setup Search Engine Connection
		$class = $configArray['Index']['engine'];
		$url = $configArray['Index']['url'];
		$this->db = new $class($url);

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			PEAR_Singleton::raiseError(new PEAR_Error('Record Does Not Exist'));
		}
		return $record;
	}
}
