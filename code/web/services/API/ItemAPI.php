<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/ISBN.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class ItemAPI extends Action {
	/** @var  AbstractIlsDriver */
	protected $catalog;

	public $id;

	/**
	 * @var MarcRecordDriver|IndexRecordDriver
	 * marc record in File_Marc object
	 */
	protected $recordDriver;

	public $record;

	public $isbn;
	public $issn;
	public $upc;

	/** @var  Solr $db */
	public $db;

	function launch()
	{
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if($this->grantTokenAccess()) {
				if ($method == 'getAppGroupedWork') {
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('ItemAPI', $method);
					$output = json_encode($this->$method());
				} else {
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					$output = json_encode(array('error' => 'invalid_method'));
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(array('error' => 'unauthorized_access'));
			}
			ExternalRequestLogEntry::logRequest('ItemAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			if ($method != 'loadSolrRecord' && method_exists($this, $method)) {
				// Connect to Catalog
				if ($method != 'getBookcoverById' && $method != 'getBookCover'){
					$this->catalog = CatalogFactory::getCatalogConnectionInstance();
					header('Content-type: application/json');
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				}

				if (in_array($method, array('getDescriptionByRecordId', 'getDescriptionByTitleAndAuthor'))){
					$output = json_encode($this->$method());
				}else{
					$output = json_encode(array('result'=>$this->$method()));
				}
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('ItemAPI', $method);
			} else {
				$output = json_encode(array('error'=>"invalid_method '$method'"));
			}
			echo $output;
		} else {
			$this->forbidAPIAccess();
		}
	}

	/** @noinspection PhpUnused */
	function getDescriptionByTitleAndAuthor(){
		global $configArray;

		//Load the title and author from the data passed in
		$title = trim($_REQUEST['title']);
		$author = trim($_REQUEST['author']);

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$this->db = new GroupedWorksSolrConnector($url);

		//Search the database by title and author
		if ($title && $author){
			$searchResults = $this->db->search("$title $author");
		}elseif ($title){
			$searchResults = $this->db->search("title:$title");
		}elseif ($author){
			$searchResults = $this->db->search("author:$author");
		}else{
			return array(
				'result' => false,
				'message' => 'Please enter a title and/or author'
			);
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

	/** @noinspection PhpUnused */
	function getDescriptionByRecordId(){
		global $configArray;

		//Load the record id that the user wants to search for
		$recordId = trim($_REQUEST['recordId']);

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$this->db = new GroupedWorksSolrConnector($url);

		//Search the database by title and author
		if ($recordId){
			if (preg_match('/^b\d{7}[\dx]$/', $recordId)){
				$recordId = '.' . $recordId;
			}
			$searchResults = $this->db->search("$recordId", 'Id');
		}else{
			return array(
				'result' => false,
				'message' => 'Please enter the record Id to look for'
			);
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
	 * @noinspection PhpUnused
	 */
	function getMarcRecord(){
		$id = $_REQUEST['id'];
		$shortId = str_replace('.', '', $id);
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->find();
		while ($indexingProfile->fetch()){
			$folderName = $indexingProfile->individualMarcPath;
			if ($indexingProfile->createFolderFromLeadingCharacters){
				$subFolder = substr($shortId, 0, $indexingProfile->numCharsToCreateFolderFrom);
			}else{
				$subFolder = substr($shortId, 0, -$indexingProfile->numCharsToCreateFolderFrom);
			}
			$individualName = $folderName . "/{$subFolder}/{$shortId}.mrc";
			if (file_exists($individualName)){
				header('Content-Type: application/octet-stream');
				header("Content-Transfer-Encoding: Binary");
				header("Content-disposition: attachment; filename=\"".$id.".mrc\"");
				readfile($individualName);
				die();
			}
		}
		require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';
		$sideLoad = new SideLoad();
		$sideLoad->find();
		while ($sideLoad->fetch()){
			$folderName = $sideLoad->individualMarcPath;
			if ($sideLoad->createFolderFromLeadingCharacters){
				$subFolder = substr($shortId, 0, $sideLoad->numCharsToCreateFolderFrom);
			}else{
				$subFolder = substr($shortId, -$sideLoad->numCharsToCreateFolderFrom);
			}
			$individualName = $folderName . "/{$subFolder}/{$shortId}.mrc";
			if (file_exists($individualName)){
				header('Content-Type: application/octet-stream');
				header("Content-Transfer-Encoding: Binary");
				header("Content-disposition: attachment; filename=\"".$id.".mrc\"");
				readfile($individualName);
				die();
			}
		}
		return [
			'result' => false,
			'message' => 'Could not find a file for the specified record'
		];
	}

	/**
	 * Get information about a particular item and return it as JSON
	 * @noinspection PhpUnused
	 */
	function getItem(){
		global $timer;
		global $configArray;
		global $solrScope;
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$this->db = new GroupedWorksSolrConnector($url);

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
		$itemData['title'] = $record['title_display'];
		$itemData['author'] = $record['author_display'];
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

		$itemData['format'] = isset($record['format_' . $solrScope]) ? $record['format_' . $solrScope] : null;
		$itemData['formatCategory'] = isset($record['format_category_' . $solrScope]) ? $record['format_category_' . $solrScope][0] : null;
		$itemData['language'] = $record['language'];

		//Retrieve description from MARC file
		$itemData['description'] = $this->recordDriver->getDescriptionFast();

		//setup 5 star ratings
		$ratingData = $this->recordDriver->getRatingData();
		$itemData['ratingData'] = $ratingData;

		return $itemData;
	}

	/** @noinspection PhpUnused */
	function getBasicItemInfo(){
		global $timer;
		global $configArray;
		$itemData = array();

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$this->db = new GroupedWorksSolrConnector($url);

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			AspenError::raiseError(new AspenError('Record Does Not Exist'));
		}
		$this->record = $record;
		/** @var GroupedWorkDriver recordDriver */
		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$timer->logTime('Initialized the Record Driver');

		// Get ISBN for cover and review use
		$itemData['isbn'] = $this->recordDriver->getCleanISBN();
		if (empty($itemData['isbn'])) unset($itemData['isbn']);
		$itemData['upc'] = $this->recordDriver->getCleanUPC();
		if (empty($itemData['upc'])) unset($itemData['upc']);
		$itemData['issn'] = $this->recordDriver->getISSNs();
		if (empty($itemData['issn'])) unset($itemData['issn']);

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
		$itemData['cover'] = $this->recordDriver->getBookcoverUrl('medium', true);

		$itemData['description'] = $this->recordDriver->getDescriptionFast();

		//setup 5 star ratings
		$itemData['ratingData'] = $this->recordDriver->getRatingData();
		$timer->logTime('Got 5 star data');

		return $itemData;
	}

	/** @noinspection PhpUnused */
	function getItemAvailability(){
		$itemData = array();
		global $library;

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
						'holdable' => ($copy['holdable'] && $library->showHoldButton) ? 1 : 0,
						'libraryDisplayName' => $copy['shelfLocation'],
						'section' => $copy['section'],
						'sectionId' => $copy['sectionId'],
						'lastCheckinDate' => $copy['lastCheckinDate'],
				);
			}
			$itemData['holdings'] = $holdings;
		}

		return $itemData;
	}

	/** @noinspection PhpUnused */
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

	/** @noinspection PhpUnused */
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

	/** @noinspection PhpUnused */
	public function getCopyAndHoldCounts(){
		if (!isset($_REQUEST['recordId']) || strlen($_REQUEST['recordId']) == 0){
			return array('error' => 'Please provide a record to load data for');
		}
		$recordId = $_REQUEST['recordId'];
		/** @var GroupedWorkDriver|MarcRecordDriver|OverDriveRecordDriver|ExternalEContentDriver $driver */
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
							'format' => $manifestation->format,
							'copies' => $manifestation->getCopies(),
							'availableCopies' => $manifestation->getAvailableCopies(),
							'numHolds' => $manifestation->getNumHolds(),
							'available' => $manifestation->isAvailable(),
							'isEContent' => $manifestation->isEContent(),
							'groupedStatus' => $manifestation->getGroupedStatus(),
							'numRelatedRecords' => $manifestation->getNumRelatedRecords(),
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
				/** @var MarcRecordDriver| $driver */
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
		$url = $configArray['Index']['url'];
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$this->db = new GroupedWorksSolrConnector($url);

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			AspenError::raiseError(new AspenError('Record Does Not Exist'));
		}
		return $record;
	}

	function getBreadcrumbs() : array
	{
		return [];
	}

# ****************************************************************************************************************************
# * Functions for Aspen LiDA
# *
# ****************************************************************************************************************************
	/** @noinspection PhpUnused */
	function getAppGroupedWork() {

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($this->id);

		if ($groupedWorkDriver->isValid()) {
			$itemData['title'] = $groupedWorkDriver->getShortTitle();
			$itemData['subtitle'] = $groupedWorkDriver->getSubtitle();
			$itemData['author'] = $groupedWorkDriver->getPrimaryAuthor();
			$itemData['description'] = strip_tags($groupedWorkDriver->getDescriptionFast());
			if($itemData['description'] == '') {
				$itemData['description'] = "Description Not Provided";
			}
			$itemData['cover'] = $groupedWorkDriver->getBookcoverUrl('large', true);

			$ratingData = $groupedWorkDriver->getRatingData();
			$itemData['ratingData']['average'] = $ratingData['average'];
			$itemData['ratingData']['count'] = $ratingData['count'];

			$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
			foreach ($relatedManifestations as $relatedManifestation){

				/** @var  $relatedVariations Grouping_Variation[] */
				$relatedVariations = $relatedManifestation->getVariationInformation();
				$records = [];
				foreach ($relatedVariations as $relatedVariation) {
					$relatedRecords = $relatedVariation->getRecords();


					foreach ($relatedRecords as $relatedRecord) {
						$recordActions = $relatedRecord->getActions();
						$actions = [];
						foreach ($recordActions as $recordAction) {
							$action = array(
								'title' => $recordAction['title'],
							);

							if(isset($recordAction['type'])) {
								$action['type'] = $recordAction['type'];
							}

							if(isset($recordAction['url'])) {
								$action['url'] = $recordAction['url'];
							}

							if(isset($recordAction['redirectUrl'])) {
								$action['redirectUrl'] = $recordAction['redirectUrl'];
							}

							if($relatedRecord->source == "overdrive" && isset($recordAction['type'])) {
								if($recordAction['type'] == "overdrive_sample") {
									$action['formatId'] = $recordAction['formatId'];
									$action['sampleNumber'] = $recordAction['sampleNumber'];
								}
							}
							$actions[] = $action;
						}

						$isAvailable = $relatedRecord->isAvailable();
						$isAvailableOnline = $relatedRecord->isAvailableOnline();
						$groupedStatus = $relatedRecord->getGroupedStatus();
						$isEContent = $relatedRecord->isEContent();

						$items = $relatedRecord->getItems();
						foreach ($items as $item) {
							$shelfLocation = $item->shelfLocation;
							$callNumber = $item->callNumber;

							if($item->eContentSource == "Hoopla") {
								require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
								$hooplaDriver = new HooplaRecordDriver($item->itemId);
								$publicationDate = $hooplaDriver->getPublicationDates();
								if(is_array($publicationDate) && $publicationDate != null) {
									$publicationDate = $publicationDate[0];
								} elseif (count($publicationDate) == 0) {
									$publicationDate = $relatedRecord->publicationDate;
								}
								$publisher = $hooplaDriver->getPublishers();
								if(is_array($publisher) && $publisher != null) {
									$publisher = $publisher[0];
								} elseif (count($publisher) == 0) {
									$publisher = $relatedRecord->publisher;
								}
								$edition = $hooplaDriver->getEditions();
								if(is_array($edition) && $edition != null) {
									$edition = $edition[0];
								} elseif (count($edition) == 0) {
									$edition = $relatedRecord->edition;
								}
								$physical = $hooplaDriver->getPhysicalDescriptions();
								if(is_array($physical) && $physical != null) {
									$physical = $physical[0];
								} elseif (count($physical) == 0) {
									$physical = $relatedRecord->physical;
								}
							} elseif($item->eContentSource == "OverDrive") {
								require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
								$overdriveDriver = new OverDriveRecordDriver($item->itemId);
								$publicationDate = $relatedRecord->publicationDate;
								$publisher = $relatedRecord->publisher;
								$edition = $relatedRecord->edition;
								$physical = $relatedRecord->physical;
							} elseif($item->eContentSource == "CloudLibrary") {
								require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
								$cloudLibraryDriver = new CloudLibraryRecordDriver($item->itemId);
								$publicationDate = $cloudLibraryDriver->getPublicationDates();
								$publisher = $cloudLibraryDriver->getPublishers();
								$edition = $cloudLibraryDriver->getEditions();
								$physical = $relatedRecord->physical;
							} elseif($item->eContentSource == "Axis360") {
								require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
								$axis360Driver = new Axis360RecordDriver($item->itemId);
								$publicationDate = $axis360Driver->getPublicationDates();
								$publisher = $axis360Driver->getPublishers();
								$edition = $axis360Driver->getEditions();
								$physical = $relatedRecord->physical;
							} else {
								$publicationDate = $relatedRecord->publicationDate;
								$publisher = $relatedRecord->publisher;
								$edition = $relatedRecord->edition;
								$physical = $relatedRecord->physical;
							}
						}


						$holdable = $relatedRecord->isHoldable();
						$record = array(
							'id' => $relatedRecord->id,
							'source' => $relatedRecord->source,
							'format' => $relatedRecord->format,
							'language' => $relatedRecord->language,
							'available' => $isAvailable,
							'availableOnline' => $isAvailableOnline,
							'eContent' => $isEContent,
							'status' => $groupedStatus,
							'holdable' => $holdable,
							'shelfLocation' => $shelfLocation,
							'callNumber' => $callNumber,
							'copiesMessage' => $relatedManifestation->getNumberOfCopiesMessage(),
							'edition' => $edition,
							'publisher' => $publisher,
							'publicationDate' => $publicationDate,
							'physical' => $physical,
							'action' => $actions,
						);
						$records[] = $record;

					}
					$variationCategoryInfo[$relatedManifestation->format] = $records;


					$itemData['variation'] = $variationCategoryInfo;
				}

				/** @var  $allVariationsFormat Grouping_Variation[] */
				/** @var  $allVariationsLanguage Grouping_Variation[] */

				foreach($relatedManifestation->getVariations() as $filter) {
					if (!isset($filterOnFormat)) {
						$filterOnFormat[] = array('format' => $filter->manifestation->format, 'format' => $filter->manifestation->format);
					} elseif (!in_array( $filter->manifestation->format, array_column($filterOnFormat, 'format'))) {
						$filterOnFormat[] = array('format' =>  $filter->manifestation->format, 'format' => $filter->manifestation->format);
					}
					if (!isset($filterOnLanguage)) {
						$filterOnLanguage[] = array('language' => $filter->language);
					} elseif (!in_array( $filter->language, array_column($filterOnLanguage, 'language'))) {
						$filterOnLanguage[] = array('language' =>  $filter->language);
					}
				}

				$itemData['filterOn'] = array('format' => $filterOnFormat, 'language' => $filterOnLanguage);

			}
			return $itemData;
		}
	}

	/** @noinspection PhpUnused */
	function getAppBasicItemInfo(){
		$itemData = array();

		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		require_once ROOT_DIR . '/sys/Grouping/Manifestation.php';
		require_once ROOT_DIR . '/sys/Grouping/Variation.php';
		require_once ROOT_DIR . '/sys/Grouping/Record.php';
		require_once ROOT_DIR . '/sys/Grouping/Item.php';

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkDriver = new GroupedWorkDriver($this->id);
		if ($groupedWorkDriver->isValid()) {


			$itemData['title'] = $groupedWorkDriver->getShortTitle();
			$itemData['author'] = $groupedWorkDriver->getPrimaryAuthor();
			$itemData['formats'] = $groupedWorkDriver->getFormatsArray();
			$itemData['description'] = $groupedWorkDriver->getDescriptionFast();
			if($itemData['description'] == '') {
				$itemData['description'] = "Description Not Provided";
			}
			$itemData['cover'] = $groupedWorkDriver->getBookcoverUrl('large', true);

			$ratingData = $groupedWorkDriver->getRatingData();
			$itemData['ratingData']['average'] = $ratingData['average'];
			$itemData['ratingData']['count'] = $ratingData['count'];

			$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
			$allVariations = [];

			foreach($relatedManifestations as $manifestation) {

				$statusMessage = $manifestation->getNumberOfCopiesMessage();
				if($statusMessage == '') {
					$statusMessage = $manifestation->getStatusInformation()->_groupedStatus;
				}

				$action = $manifestation->getActions();

				$manifestationSummary = array(
					'format' => $manifestation->format,
					'records' => $manifestation->getItemSummary(),
					'variation' => $manifestation->getVariations(),
					'status' => $statusMessage,
					'action' => $action,
				);

				$itemList[] = $manifestationSummary;

				/** @var  $allVariationsFormat Grouping_Variation[] */
				/** @var  $allVariationsLanguage Grouping_Variation[] */

				foreach($manifestation->getVariations() as $variation) {
					if (!isset($allVariationsFormat)) {
						$allVariationsFormat[] = array('format' => $variation->manifestation->format, 'formatCategory' => $variation->manifestation->formatCategory);
					} elseif (!in_array( $variation->manifestation->format, array_column($allVariationsFormat, 'format'))) {
						$allVariationsFormat[] = array('format' =>  $variation->manifestation->format, 'formatCategory' => $variation->manifestation->formatCategory);
					}
					if (!isset($allVariationsLanguage)) {
						$allVariationsLanguage[] = array('language' => $variation->language);
					} elseif (!in_array( $variation->language, array_column($allVariationsLanguage, 'language'))) {
						$allVariationsLanguage[] = array('language' =>  $variation->language);
					}
				}


			}


			$itemData['variations_format'] = $allVariationsFormat;
			$itemData['variations_language'] = $allVariationsLanguage;
			$itemData['manifestations'] = $itemList;

			return $itemData;
		}

	}

	function getItemDetails() {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';

		$groupedWorkId = $_REQUEST['recordId'];
		$format = $_REQUEST['format'];

		$recordDriver = new GroupedWorkDriver($groupedWorkId);

		$relatedManifestation = null;
		foreach($recordDriver->getRelatedManifestations() as $relatedManifestation){
			if($relatedManifestation->format == $format) {
				break;
			}
		}

		return $relatedManifestation->getItemSummary();
	}

}
