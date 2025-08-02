<?php

require_once ROOT_DIR . '/services/API/AbstractAPI.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/ISBN.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class ItemAPI extends AbstractAPI {
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

	function launch() {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';

		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}

		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->grantTokenAccess()) {
				if (in_array($method, [
					'getAppGroupedWork',
					'getBasicItemInfo',
					'getItemDetails',
					'getItemAvailability',
					'getManifestation',
					'getVariations',
					'getRecords',
					'getVolumes',
					'getRelatedRecord',
					'getCopies'
				])) {
					header("Cache-Control: max-age=10800");
					require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
					APIUsage::incrementStat('ItemAPI', $method);
					$output = json_encode($this->$method());
				} else {
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					$output = json_encode(['error' => 'invalid_method']);
				}
			} else {
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('HTTP/1.0 401 Unauthorized');
				$output = json_encode(['error' => 'unauthorized_access']);
			}
			ExternalRequestLogEntry::logRequest('ItemAPI.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} elseif (IPAddress::allowAPIAccessForClientIP()) {
			if ($method != 'loadSolrRecord' && method_exists($this, $method)) {
				// Connect to Catalog
				if ($method != 'getBookcoverById' && $method != 'getBookCover') {
					$this->catalog = CatalogFactory::getCatalogConnectionInstance();
					header('Content-type: application/json');
					header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				}

				if (in_array($method, [
					'getDescriptionByRecordId',
					'getDescriptionByTitleAndAuthor',
				])) {
					$output = json_encode($this->$method());
				} else {
					$output = json_encode(['result' => $this->$method()]);
				}
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				APIUsage::incrementStat('ItemAPI', $method);
			} else {
				$output = json_encode(['error' => "invalid_method '$method'"]);
			}
			echo $output;
		} else {
			$this->forbidAPIAccess();
		}
	}

	/** @noinspection PhpUnused */
	function getDescriptionByTitleAndAuthor() {
		global $configArray;

		//Load the title and author from the data passed in
		$title = trim($_REQUEST['title']);
		$author = trim($_REQUEST['author']);

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1) {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$this->db = new GroupedWorksSolrConnector($url);
		} else {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$this->db = new GroupedWorksSolrConnector2($url);
		}

		//Search the database by title and author
		if ($title && $author) {
			$searchResults = $this->db->search("$title $author");
		} elseif ($title) {
			$searchResults = $this->db->search("title:$title");
		} elseif ($author) {
			$searchResults = $this->db->search("author:$author");
		} else {
			return [
				'result' => false,
				'message' => 'Please enter a title and/or author',
			];
		}

		if ($searchResults['response']['numFound'] == 0) {
			$results = [
				'result' => false,
				'message' => 'Sorry, we could not find a description for that title and author',
			];
		} else {
			$firstRecord = $searchResults['response']['docs'][0];
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$groupedWork = new GroupedWorkDriver($firstRecord);

			$results = [
				'result' => true,
				'message' => 'Found a summary for record ' . $firstRecord['title_display'] . ' by ' . $firstRecord['author_display'],
				'recordsFound' => $searchResults['response']['numFound'],
				'description' => $groupedWork->getDescription(),
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getDescriptionByRecordId() {
		global $configArray;

		//Load the record id that the user wants to search for
		$recordId = trim($_REQUEST['recordId']);

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1) {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$this->db = new GroupedWorksSolrConnector($url);
		} else {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$this->db = new GroupedWorksSolrConnector2($url);
		}

		//Search the database by title and author
		if ($recordId) {
			if (preg_match('/^b\d{7}[\dx]$/', $recordId)) {
				$recordId = '.' . $recordId;
			}
			$searchResults = $this->db->search("$recordId", 'Id');
		} else {
			return [
				'result' => false,
				'message' => 'Please enter the record Id to look for',
			];
		}

		if ($searchResults['response']['numFound'] == 0) {
			$results = [
				'result' => false,
				'message' => 'Sorry, we could not find a description for that record id',
			];
		} else {
			$firstRecord = $searchResults['response']['docs'][0];
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$groupedWork = new GroupedWorkDriver($firstRecord);

			$results = [
				'result' => true,
				'message' => 'Found a summary for record ' . $firstRecord['title_display'] . ' by ' . $firstRecord['author_display'],
				'recordsFound' => $searchResults['response']['numFound'],
				'description' => $groupedWork->getDescription(),
			];
		}
		return $results;
	}

	/**
	 * Get information about a particular item and return it as JSON
	 * @noinspection PhpUnused
	 */
	function getItem() {
		global $timer;
		global $configArray;
		global $solrScope;
		$itemData = [];

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1) {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$this->db = new GroupedWorksSolrConnector($url);
		} else {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$this->db = new GroupedWorksSolrConnector2($url);
		}

		// Retrieve Full Marc Record
		disableErrorHandler();
		if (!($record = $this->db->getRecord($this->id))) {
			return [
				'error',
				'Record does not exist',
			];
		}
		enableErrorHandler();
		$this->record = $record;

		$this->recordDriver = RecordDriverFactory::initRecordDriver($record);
		$timer->logTime('Initialized the Record Driver');

		//Generate basic information from the marc file to make display easier.
		if (isset($record['isbn'])) {
			$itemData['isbn'] = $record['isbn'][0];
		}
		if (isset($record['upc'])) {
			$itemData['upc'] = $record['upc'][0];
		}
		if (isset($record['issn'])) {
			$itemData['issn'] = $record['issn'][0];
		}
		$itemData['title'] = $record['title_display'];
		$itemData['author'] = $record['author_display'];
		$itemData['publisher'] = $record['publisher'];
		$itemData['placeOfPublication'] = $record['placeOfPublication'];
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
	function getCopies() {
		if (!isset($_REQUEST['recordId'])) {
			return [
				'success' => false,
				'message' => 'Record id not provided'
			];
		}

		if (!isset($_REQUEST['variationId'])) {
			return [
				'success' => false,
				'message' => 'Variation id not provided'
			];
		}

		$variationId = $_REQUEST['variationId'];
		$id = $_REQUEST['recordId'];
		$recordId = explode(':', $id);
		$id = $recordId[1];
		$source = $recordId[0];

		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$marcRecord = new MarcRecordDriver($id);

		$copies = $marcRecord->getCopies();
		$items = [];
		foreach($copies as $copy) {
			if($copy['variationId'] == $variationId && $copy['holdable']) {
				$key = $copy['description'] . ' ' . $copy['itemId'];
				$items[$key]['id'] = $copy['itemId'];
				$items[$key]['location'] = $copy['description'];
				$items[$key]['library'] = $copy['section'];
				$items[$key]['volume'] = $copy['volume'];
				$items[$key]['volumeId'] = $copy['volumeId'];
				$items[$key]['variationId'] = $copy['variationId'];
			}
		}
		return [
			'success' => true,
			'recordId' => $_REQUEST['recordId'],
			'copies' => $items,
		];
	}

	/** @noinspection PhpUnused */
	function getBasicItemInfo() {
		global $timer;
		global $configArray;
		$itemData = [];

		//Load basic information
		if (empty($_GET['id'])) {
			$itemData = [
				'title' => 'Unknown Record',
				'author' => '',
				'publisher' => '',
				'placeOfPublication' => '',
				'format' => 'Unknown',
				'formatCategory' => 'Unknown',
				'description' => '',
				'cover' => '',
				'oclcNumber' => '',
			];
		} else {
			$this->id = $_GET['id'];
			$itemData['id'] = $this->id;

			// Setup Search Engine Connection
			$url = $configArray['Index']['url'];
			$systemVariables = SystemVariables::getSystemVariables();
			if ($systemVariables->searchVersion == 1) {
				require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
				$this->db = new GroupedWorksSolrConnector($url);
			} else {
				require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
				$this->db = new GroupedWorksSolrConnector2($url);
			}

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
			if (empty($itemData['isbn'])) {
				unset($itemData['isbn']);
			}
			$itemData['upc'] = $this->recordDriver->getCleanUPC();
			if (empty($itemData['upc'])) {
				unset($itemData['upc']);
			}
			$itemData['issn'] = $this->recordDriver->getISSNs();
			if (empty($itemData['issn'])) {
				unset($itemData['issn']);
			}

			//Generate basic information from the marc file to make display easier.
			$itemData['title'] = $record['title_display'];
			$itemData['author'] = isset($record['author_display']) ? $record['author_display'] : (isset($record['author2']) ? $record['author2'][0] : '');
			$itemData['publisher'] = $record['publisher'];
			$itemData['placeOfPublication'] = $record['placeOfPublication'];
			if(isset($record['isbn'])) {
				$itemData['allIsbn'] = $record['isbn'];
			}
			if(isset($record['upc'])) {
				$itemData['allUpc'] = $record['upc'];
			}
			if(isset($record['issn'])) {
				$itemData['allIssn'] = $record['issn'];
				$itemData['issn'] = $record['issn'];
			}
			$itemData['format'] = isset($record['format']) ? $record['format'][0] : '';
			$itemData['formatCategory'] = $record['format_category'][0];
			$itemData['language'] = $record['language'];
			$itemData['cover'] = $this->recordDriver->getBookcoverUrl('medium', true);

			$itemData['description'] = $this->recordDriver->getDescriptionFast();

			//setup 5 star ratings
			$itemData['ratingData'] = $this->recordDriver->getRatingData();
			$timer->logTime('Got 5 star data');

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecordDriver = new MarcRecordDriver($this->recordDriver->getIdWithSource());
			if ($marcRecordDriver != null) {
				/** @var File_MARC_Control_Field $oclcNumber */
				$oclcNumber = $marcRecordDriver->getMarcRecord()->getField('001');
				if ($oclcNumber != null) {
					$oclcNumberString = StringUtils::truncate($oclcNumber->getData(), 50);
				} else {
					$oclcNumberString = '';
				}
			} else {
				$oclcNumberString = '';
			}

			$itemData['oclcNumber'] = $oclcNumberString;
		}

		return $itemData;
	}

	/** @noinspection PhpUnused */
	function getItemAvailability() {
		$itemData = [];
		global $library;

		//Load basic information
		$this->id = $_GET['id'];
		$itemData['id'] = $this->id;

		$fullId = 'ils:' . $this->id;

		//Rather than calling the catalog, update to load information from the index
		//Need to match historical data so we don't break EBSCO
		$recordDriver = RecordDriverFactory::initRecordDriverById($fullId);
		if ($recordDriver->isValid()) {
			$copies = $recordDriver->getCopies();
			$holdings = [];
			$i = 0;
			foreach ($copies as $copy) {
				$key = $copy['shelfLocation'];
				$key = preg_replace('~\W~', '_', $key);
				$holdings[$key][] = [
					'location' => $copy['shelfLocation'],
					'callnumber' => $copy['callNumber'],
					'status' => $copy['status'],
					'dueDate' => '',
					'statusFull' => $copy['status'],
					'statusfull' => $copy['status'],
					'id' => $fullId,
					'number' => $i++,
					'type' => 'holding',
					'availability' => $copy['available'],
					'holdable' => ($copy['holdable'] && $library->showHoldButton) ? 1 : 0,
					'libraryDisplayName' => $copy['shelfLocation'],
					'section' => $copy['section'],
					'sectionId' => $copy['sectionId'],
					'lastCheckinDate' => $copy['lastCheckinDate'],
				];
			}
			$itemData['holdings'] = $holdings;
		}

		return $itemData;
	}

	/** @noinspection PhpUnused */
	function getBookcoverById() {
		$record = $this->loadSolrRecord($_GET['id']);
		$isbn = !empty($record['isbn']) ? ISBN::normalizeISBN($record['isbn'][0]) : null;
		$upc = !empty($record['upc']) ? $record['upc'][0] : null;
		$id = !empty($record['id']) ? $record['id'][0] : null;
		$issn = !empty($record['issn']) ? $record['issn'][0] : null;
		$formatCategory = !empty($record['format_category']) ? $record['format_category'][0] : null;
		$this->getBookCover($isbn, $upc, $formatCategory, null, $id, $issn);
	}

	function getBookCover($isbn = null, $upc = null, $formatCategory = null, $size = null, $id = null, $issn = null) {
		if (is_null($isbn)) {
			$isbn = !empty($_GET['isbn']) ? $_GET['isbn'] : '';
		}
		$_GET['isn'] = ISBN::normalizeISBN($isbn);
		if (is_null($issn)) {
			$issn = !empty($_GET['issn']) ? $_GET['issn'] : null;
		}
		$_GET['iss'] = $issn;
		if (is_null($upc)) {
			$upc = !empty($_GET['upc']) ? $_GET['upc'] : null;
		}
		$_GET['upc'] = $upc;
		if (is_null($formatCategory)) {
			$formatCategory = !empty($_GET['formatCategory']) ? $_GET['formatCategory'] : null;
		}
		$_GET['category'] = $formatCategory;
		if (is_null($size)) {
			$size = !empty($_GET['size']) ? $_GET['size'] : 'small';
		}
		$_GET['size'] = $size;
		if (is_null($id)) {
			$id = $_GET['id'];
		}
		$_GET['id'] = $id;
		include_once(ROOT_DIR . '/bookcover.php');
	}

	/** @noinspection PhpUnused */
	function clearBookCoverCacheById() {
		$id = strip_tags($_REQUEST['id']);
		$sizes = [
			'small',
			'medium',
			'large',
		];
		$extensions = [
			'jpg',
			'gif',
			'png',
		];
		$record = $this->loadSolrRecord($id);
		$filenamesToCheck = [];
		$filenamesToCheck[] = $id;
		if (isset($record['isbn'])) {
			$isbns = $record['isbn'];
			foreach ($isbns as $isbn) {
				$filenamesToCheck[] = preg_replace('/[^0-9xX]/', '', $isbn);
			}
		}
		if (isset($record['upc'])) {
			$upcs = $record['upc'];
			if (isset($upcs)) {
				$filenamesToCheck = array_merge($filenamesToCheck, $upcs);
			}
		}
		$deletedFiles = [];
		global $configArray;
		$coverPath = $configArray['Site']['coverPath'];
		foreach ($filenamesToCheck as $filename) {
			foreach ($extensions as $extension) {
				foreach ($sizes as $size) {
					$tmpFilename = "$coverPath/$size/$filename.$extension";
					if (file_exists($tmpFilename)) {
						$deletedFiles[] = $tmpFilename;
						unlink($tmpFilename);
					}
				}
			}
		}

		return ['deletedFiles' => $deletedFiles];
	}

	/** @noinspection PhpUnused */
	public function getCopyAndHoldCounts() {
		if (!isset($_REQUEST['recordId']) || strlen($_REQUEST['recordId']) == 0) {
			return ['error' => 'Please provide a record to load data for'];
		}
		$recordId = $_REQUEST['recordId'];
		/** @var GroupedWorkDriver|MarcRecordDriver|OverDriveRecordDriver|ExternalEContentDriver $driver */
		$driver = RecordDriverFactory::initRecordDriverById($recordId);
		if ($driver == null || !$driver->isValid()) {
			return ['error' => 'Sorry we could not find a record with that ID'];
		} else {
			if ($driver instanceof GroupedWorkDriver) {
				/** @var GroupedWorkDriver $driver */
				$manifestations = $driver->getRelatedManifestations();
				$returnData = [];
				foreach ($manifestations as $manifestation) {
					$manifestationSummary = [
						'format' => $manifestation->format,
						'copies' => $manifestation->getCopies(),
						'availableCopies' => $manifestation->getAvailableCopies(),
						'numHolds' => $manifestation->getNumHolds(),
						'available' => $manifestation->isAvailable(),
						'isEContent' => $manifestation->isEContent(),
						'groupedStatus' => $manifestation->getGroupedStatus(),
						'numRelatedRecords' => $manifestation->getNumRelatedRecords(),
					];
					foreach ($manifestation['relatedRecords'] as $relatedRecord) {
						$manifestationSummary['relatedRecords'][] = $relatedRecord['id'];
					}
					$returnData[] = $manifestationSummary;
				}
				return $returnData;
			} elseif ($driver instanceof OverDriveRecordDriver) {
				/** @var OverDriveRecordDriver $driver */
				$copies = count($driver->getItems());
				$holds = $driver->getNumHolds();
				return [
					'copies' => $copies,
					'holds' => $holds,
				];
			} elseif ($driver instanceof ExternalEContentDriver || $driver instanceof HooplaRecordDriver) {
				/** @var ExternalEContentDriver $driver */
				return [
					'copies' => 1,
					'holds' => 0,
				];
			} else {
				/** @var MarcRecordDriver| $driver */
				$copies = count($driver->getCopies());
				$holds = $driver->getNumHolds();
				return [
					'copies' => $copies,
					'holds' => $holds,
				];
			}
		}
	}

	public function loadSolrRecord($id) {
		global $configArray;
		//Load basic information
		if (isset($id)) {
			$this->id = $id;
		} else {
			$this->id = $_GET['id'];
		}

		$itemData['id'] = $this->id;

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1) {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$this->db = new GroupedWorksSolrConnector($url);
		} else {
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$this->db = new GroupedWorksSolrConnector2($url);
		}

		// Retrieve Full Marc Record
		if (!($record = $this->db->getRecord($this->id))) {
			AspenError::raiseError(new AspenError('Record Does Not Exist'));
		}
		return $record;
	}

	function getBreadcrumbs(): array {
		return [];
	}

# ****************************************************************************************************************************
# * Functions for Aspen LiDA
# *
# ****************************************************************************************************************************
	/** @noinspection PhpUnused */
	//todo: This should be moved over to Work API
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
			if ($itemData['description'] == '') {
				$itemData['description'] = "Description Not Provided";
			}
			$itemData['isbn'] = $groupedWorkDriver->getCleanISBN();
			$itemData['cover'] = $groupedWorkDriver->getBookcoverUrl('large', true);

			$ratingData = $groupedWorkDriver->getRatingData();
			$itemData['ratingData']['average'] = $ratingData['average'];
			$itemData['ratingData']['count'] = $ratingData['count'];

			$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
			foreach ($relatedManifestations as $relatedManifestation) {

				/** @var  $relatedVariations Grouping_Variation[] */
				$relatedVariations = $relatedManifestation->getVariationInformation();
				$records = [];
				foreach ($relatedVariations as $relatedVariation) {
					$relatedRecords = $relatedVariation->getRecords();


					foreach ($relatedRecords as $relatedRecord) {
						$recordActions = $relatedRecord->getActions();
						$actions = [];
						foreach ($recordActions as $recordAction) {
							$action = [
								'title' => $recordAction['title'],
							];

							if (isset($recordAction['type'])) {
								$action['type'] = $recordAction['type'];
							}

							if (isset($recordAction['url'])) {
								$action['url'] = $recordAction['url'];
							}

							if (isset($recordAction['redirectUrl'])) {
								$action['redirectUrl'] = $recordAction['redirectUrl'];
							}

							if ($relatedRecord->source == "overdrive" && isset($recordAction['type'])) {
								if ($recordAction['type'] == "overdrive_sample") {
									$action['formatId'] = $recordAction['formatId'];
									$action['sampleNumber'] = $recordAction['sampleNumber'];
								}
							}

							$action['volumeId'] = $recordAction['volumeId'] ?? null;
							$action['volumeName'] = $recordAction['volumeName'] ?? null;

							$action['message'] = $recordAction['message'] ?? null;

							$actions[] = $action;
						}

						$isAvailable = $relatedRecord->isAvailable();
						$isAvailableOnline = $relatedRecord->isAvailableOnline();
						$groupedStatus = $relatedRecord->getGroupedStatus();
						$isEContent = $relatedRecord->isEContent();

						$numItemsWithVolumes = 0;
						$numItemsWithoutVolumes = 0;
						$items = $relatedRecord->getItems();
						foreach ($items as $item) {
							$shelfLocation = $item->shelfLocation;
							$callNumber = $item->callNumber;

							if (empty($item->volume)) {
								$numItemsWithoutVolumes++;
							} else {
								$numItemsWithVolumes++;
							}

							$hasItemsWithoutVolumes = $numItemsWithoutVolumes > 0;
							$majorityOfItemsHaveVolumes = $numItemsWithVolumes > $numItemsWithoutVolumes;

							if ($item->eContentSource == "Hoopla") {
								require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
								$hooplaDriver = new HooplaRecordDriver($item->itemId);
								$publicationDate = $hooplaDriver->getPublicationDates();
								if (is_array($publicationDate) && $publicationDate != null) {
									$publicationDate = $publicationDate[0];
								} elseif (count($publicationDate) == 0) {
									$publicationDate = $relatedRecord->publicationDate;
								}
								$publisher = $hooplaDriver->getPublishers();
								if (is_array($publisher) && $publisher != null) {
									$publisher = $publisher[0];
								} elseif (count($publisher) == 0) {
									$publisher = $relatedRecord->publisher;
								}
								$placeOfPublication = $hooplaDriver->getPlacesOfPublication();
								if (is_array($placeOfPublication) && $placesOfPublication != null) {
									$placeOfPublication = $placeOfPublication[0];
								} elseif (count($placeOfPublication) == 0){
									$placeOfPublication = $relatedRecord->placeOfPublication;
								}
								$edition = $hooplaDriver->getEditions();
								if (is_array($edition) && $edition != null) {
									$edition = $edition[0];
								} elseif (count($edition) == 0) {
									$edition = $relatedRecord->edition;
								}
								$physical = $hooplaDriver->getPhysicalDescriptions();
								if (is_array($physical) && $physical != null) {
									$physical = $physical[0];
								} elseif (count($physical) == 0) {
									$physical = $relatedRecord->physical;
								}
							} elseif ($item->eContentSource == "OverDrive") {
								require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
								$overdriveDriver = new OverDriveRecordDriver($item->itemId);
								$publicationDate = $relatedRecord->publicationDate;
								$publisher = $relatedRecord->publisher;
								$placeOfPublication = $relatedRecord->placeOfPublication;
								$edition = $relatedRecord->edition;
								$physical = $relatedRecord->physical;
							} elseif ($item->eContentSource == "CloudLibrary") {
								require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
								$cloudLibraryDriver = new CloudLibraryRecordDriver($item->itemId);
								$publicationDate = $cloudLibraryDriver->getPublicationDates();
								$placeOfPublication = $cloudLibraryDriver->getPlacesOfPublication();
								$publisher = $cloudLibraryDriver->getPublishers();
								$edition = $cloudLibraryDriver->getEditions();
								$physical = $relatedRecord->physical;
							} elseif ($item->eContentSource == "Axis360") {
								require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
								$axis360Driver = new Axis360RecordDriver($item->itemId);
								$publicationDate = $axis360Driver->getPublicationDates();
								$publisher = $axis360Driver->getPublishers();
								$placeOfPublication = $axis360Driver->getPlacesOfPublication();
								$edition = $axis360Driver->getEditions();
								$physical = $relatedRecord->physical;
							} else {
								$publicationDate = $relatedRecord->publicationDate;
								$publisher = $relatedRecord->publisher;
								$placeOfPublication = $relatedRecord->placeOfPublication;
								$edition = $relatedRecord->edition;
								$physical = $relatedRecord->physical;
							}
						}

						$recordVolumes = $relatedRecord->getUnsuppressedVolumeData();

						$holdable = $relatedRecord->isHoldable();
						$record = [
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
							'totalCopies' => $relatedRecord->getCopies(),
							'availableCopies' => $relatedRecord->getAvailableCopies(),
							'edition' => $edition,
							'publisher' => $publisher,
							'publicationDate' => $publicationDate,
							'placeOfPublication' => $placeOfPublication,
							'physical' => $physical,
							'action' => $actions,
							'hasItemsWithoutVolumes' => $hasItemsWithoutVolumes,
							'majorityOfItemsHaveVolumes' => $majorityOfItemsHaveVolumes,
							'volumes' => $recordVolumes,
							'copyDetails' => $relatedRecord->getItemSummary(),
						];
						$records[] = $record;
						$itemData['language'] = $relatedRecord->language;

					}
					$variationCategoryInfo[$relatedManifestation->format] = $records;


					$itemData['variation'] = $variationCategoryInfo;
				}

				/** @var  $allVariationsFormat Grouping_Variation[] */
				/** @var  $allVariationsLanguage Grouping_Variation[] */

				foreach ($relatedManifestation->getVariations() as $filter) {
					if (!isset($filterOnFormat)) {
						$filterOnFormat[] = [
							'format' => $filter->manifestation->format,
							'format' => $filter->manifestation->format,
						];
					} elseif (!in_array($filter->manifestation->format, array_column($filterOnFormat, 'format'))) {
						$filterOnFormat[] = [
							'format' => $filter->manifestation->format,
							'format' => $filter->manifestation->format,
						];
					}
					if (!isset($filterOnLanguage)) {
						$filterOnLanguage[] = ['language' => $filter->language];
					} elseif (!in_array($filter->language, array_column($filterOnLanguage, 'language'))) {
						$filterOnLanguage[] = ['language' => $filter->language];
					}
				}

				$itemData['filterOn'] = [
					'format' => $filterOnFormat,
					'language' => $filterOnLanguage,
				];

			}
			return $itemData;
		}
	}

	/** @noinspection PhpUnused */
	function getAppBasicItemInfo() {
		$itemData = [];

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
			if ($itemData['description'] == '') {
				$itemData['description'] = "Description Not Provided";
			}
			$itemData['cover'] = $groupedWorkDriver->getBookcoverUrl('large', true);

			$ratingData = $groupedWorkDriver->getRatingData();
			$itemData['ratingData']['average'] = $ratingData['average'];
			$itemData['ratingData']['count'] = $ratingData['count'];

			$relatedManifestations = $groupedWorkDriver->getRelatedManifestations();
			$allVariations = [];

			foreach ($relatedManifestations as $manifestation) {

				$statusMessage = $manifestation->getNumberOfCopiesMessage();
				if ($statusMessage == '') {
					$statusMessage = $manifestation->getStatusInformation()->_groupedStatus;
				}

				$action = $manifestation->getActions();

				$manifestationSummary = [
					'format' => $manifestation->format,
					'records' => $manifestation->getItemSummary(),
					'variation' => $manifestation->getVariations(),
					'status' => $statusMessage,
					'action' => $action,
				];

				$itemList[] = $manifestationSummary;

				/** @var  $allVariationsFormat Grouping_Variation[] */
				/** @var  $allVariationsLanguage Grouping_Variation[] */

				foreach ($manifestation->getVariations() as $variation) {
					if (!isset($allVariationsFormat)) {
						$allVariationsFormat[] = [
							'format' => $variation->manifestation->format,
							'formatCategory' => $variation->manifestation->formatCategory,
						];
					} elseif (!in_array($variation->manifestation->format, array_column($allVariationsFormat, 'format'))) {
						$allVariationsFormat[] = [
							'format' => $variation->manifestation->format,
							'formatCategory' => $variation->manifestation->formatCategory,
						];
					}
					if (!isset($allVariationsLanguage)) {
						$allVariationsLanguage[] = ['language' => $variation->language];
					} elseif (!in_array($variation->language, array_column($allVariationsLanguage, 'language'))) {
						$allVariationsLanguage[] = ['language' => $variation->language];
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
		if ($recordDriver->isValid()) {
			$relatedManifestation = null;
			foreach ($recordDriver->getRelatedManifestations() as $relatedManifestation) {
				if ($relatedManifestation->format == $format) {
					break;
				}
			}

			if ($relatedManifestation != null) {
				return $relatedManifestation->getItemSummary();
			}
		}
		return [
			'totalCopies' => 0,
			'availableCopies' => 0,
			'shelfLocation' => '',
			'callNumber' => ''
		];
	}

	function getManifestation() {
		if (!isset($_REQUEST['id']) || !isset($_REQUEST['format'])) {
			return [
				'success' => false,
				'message' => 'Grouped work id or format not provided'
			];
		}
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkId = $_REQUEST['id'];
		$format = $_REQUEST['format'];
		$recordDriver = new GroupedWorkDriver($groupedWorkId);
		$relatedManifestation = null;
		foreach ($recordDriver->getRelatedManifestations() as $relatedManifestation) {
			if ($relatedManifestation->format == $format) {
				break;
			}
		}

		if ($relatedManifestation == null) {
			return [
				'success' => false,
				'id' => $groupedWorkId,
				'format' => translate(['text' => $format, 'isPublicFacing' => true]),
				'manifestation' => [],
			];
		}else{
			return [
				'success' => true,
				'id' => $groupedWorkId,
				'format' => translate(['text' => $format, 'isPublicFacing' => true]),
				'manifestation' => $relatedManifestation->getItemSummary(),
			];
		}
	}

	function getVariations() {
		global $library;
		global $interface;
		if (!isset($_REQUEST['id']) || !isset($_REQUEST['format'])) {
			return [
				'success' => false,
				'message' => 'Grouped work id or format not provided'
			];
		}
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkId = $_REQUEST['id'];
		$format = $_REQUEST['format'];
		$recordDriver = new GroupedWorkDriver($groupedWorkId);
		$relatedManifestation = null;
		foreach ($recordDriver->getRelatedManifestations() as $relatedManifestation) {
			if ($relatedManifestation->format == $format) {
				break;
			}
		}

		if ($relatedManifestation == null) {
			return [
				'success' => false,
				'message' => 'Manifestation could not be found'
			];
		}

		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$marcRecord = new MarcRecordDriver($groupedWorkId);
		$alwaysPlaceVolumeHoldWhenVolumesArePresent = $marcRecord->getCatalogDriver()->alwaysPlaceVolumeHoldWhenVolumesArePresent();

		$relatedRecord = $relatedManifestation->getFirstRecord();
		//Get a list of volumes for the record
		require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
		$volumeData = [];
		$volumeDataDB = new IlsVolumeInfo();
		$volumeDataDB->recordId = $relatedRecord->id;
		$volumeDataDB->orderBy('displayOrder ASC, displayLabel ASC');
		if ($volumeDataDB->find()) {
			while ($volumeDataDB->fetch()) {
				$volumeData[$volumeDataDB->volumeId] = clone($volumeDataDB);
				$volumeData[$volumeDataDB->volumeId]->setHasLocalItems(false);
			}
		}

		$numItemsWithVolumes = 0;
		$numItemsWithoutVolumes = 0;
		foreach ($relatedRecord->getItems() as $item) {
			if (empty($item->volume)) {
				$numItemsWithoutVolumes++;
			} else {
				if ($item->libraryOwned || $item->locallyOwned) {
					if (array_key_exists($item->volumeId, $volumeData)) {
						$volumeData[$item->volumeId]->setHasLocalItems(true);
					}
				}
				$numItemsWithVolumes++;
			}
		}

		$hasItemsWithoutVolumes = $numItemsWithoutVolumes > 0;
		$majorityOfItemsHaveVolumes = $numItemsWithVolumes > $numItemsWithoutVolumes;

		if ($numItemsWithoutVolumes > 0 && $alwaysPlaceVolumeHoldWhenVolumesArePresent) {
			$hasItemsWithoutVolumes = false;
			$majorityOfItemsHaveVolumes = true;
		}

		$relatedVariation = null;
		$variations = [];
		foreach($relatedManifestation->getVariations() as $relatedVariation) {
			$relatedRecord = $relatedVariation->getFirstRecord();

			$holdType = 'bib';
			global $indexingProfiles;
			$indexingProfile = $indexingProfiles[$marcRecord->getRecordType()];
			$formatMap = $indexingProfile->formatMap;
			/** @var FormatMapValue $formatMapValue */
			foreach ($formatMap as $formatMapValue) {
				if($formatMapValue->format === $format) {
					$holdType = $formatMapValue->holdType;
				}
			}

			if($holdType == 'either') {
				//Check for an override at the library level
				if ($library->treatBibOrItemHoldsAs == 2) {
					$holdType = 'bib';
				} elseif ($library->treatBibOrItemHoldsAs == 3) {
					$holdType = 'item';
				}
			}

			$relatedRecordDriver = $relatedRecord->getDriver();
			/** @var File_MARC_Control_Field $oclcNumber */
			$oclcNumber = $relatedRecordDriver->getOCLCNumber();

			$actionButtons = [];
			$actions = $relatedVariation->getActions();
			foreach ($actions as $key => $action) {
				$actionButtons[$key]['id'] = $key;
				$actionButtons[$key]['type'] = $action['type'] ?? null;
				$actionButtons[$key]['title'] = $action['title'];
				$actionButtons[$key]['url'] = $action['url'] ?? null;
				$actionButtons[$key]['requireLogin'] = $action['requireLogin'] ?? false;
				$actionButtons[$key]['sampleNumber'] = $action['sampleNumber'] ?? null;
				$actionButtons[$key]['formatId'] = $action['formatId'] ?? null;
				$actionButtons[$key]['volumeId'] = $action['volumeId'] ?? null;
				$actionButtons[$key]['volumeName'] = $action['volumeName'] ?? null;
				$actionButtons[$key]['message'] = $action['message'] ?? null;

				if(isset($action['redirectUrl'])) {
					$actionButtons[$key]['redirectUrl'] = $action['redirectUrl'];
				}
				$actionButtons[$key]['redirectParams'] = $action['redirectParams'] ?? null;

			}

			$variations[$relatedVariation->label]['id'] = $relatedRecord->id;
			$variations[$relatedVariation->label]['source'] = $relatedRecord->source;
			$variations[$relatedVariation->label]['closedCaptioned'] = (int) $relatedRecord->closedCaptioned;
			$variations[$relatedVariation->label]['actions'] = $actionButtons;
			$variations[$relatedVariation->label]['variationId'] = $relatedVariation->databaseId;
			$variations[$relatedVariation->label]['holdType'] = $holdType;
			$variations[$relatedVariation->label]['statusIndicator'] = [
				'isAvailable' => $relatedVariation->getStatusInformation()->isAvailable(),
				'isEContent' => $relatedVariation->getStatusInformation()->isEContent(),
				'isAvailableOnline' => $relatedVariation->getStatusInformation()->isAvailableOnline(),
				'groupedStatus' => $relatedVariation->getStatusInformation()->getGroupedStatus(),
				'isAvailableHere' => $relatedVariation->getStatusInformation()->isAvailableHere(),
				'isAvailableLocally' => $relatedVariation->getStatusInformation()->isAvailableLocally(),
				'isAllLibraryUseOnly' => $relatedVariation->getStatusInformation()->isAllLibraryUseOnly(),
				'hasLocalItem' => $relatedVariation->getStatusInformation()->hasLocalItem(),
				'numCopiesMessage' => $relatedVariation->getStatusInformation()->getNumberOfCopiesMessage(),
				'numHolds' => $relatedVariation->getStatusInformation()->getNumHolds(),
				'numOnOrder' => $relatedVariation->getStatusInformation()->getOnOrderCopies(),
				'isShowStatus' => $relatedVariation->getStatusInformation()->isShowStatus(),
				'showGroupedHoldCopiesCount' => (int) $library->showGroupedHoldCopiesCount,
				'showItsHere' => (int) $library->showItsHere,
				'isGlobalScope' => $interface->getVariable('isGlobalScope'),
			];
			$variations[$relatedVariation->label]['title'] = $relatedRecordDriver->getTitle() ?? '';
			$variations[$relatedVariation->label]['author'] = $relatedRecordDriver->getAuthor() ?? '';
			$variations[$relatedVariation->label]['publisher'] = $relatedRecord->publisher ?? '';
			$variations[$relatedVariation->label]['placeOfPublication'] = $relatedRecord->placeOfPublication ?? '';
			$variations[$relatedVariation->label]['isbn'] = $relatedRecordDriver->getCleanISBN() ?? '';
			$variations[$relatedVariation->label]['oclcNumber'] = $oclcNumber;
		}

		return [
			'success' => true,
			'id' => $groupedWorkId,
			'format' => translate(['text' => $format, 'isPublicFacing' => true]),
			'variations' => $variations,
			'alwaysPlaceVolumeHoldWhenVolumesArePresent' => $alwaysPlaceVolumeHoldWhenVolumesArePresent,
			'localSystemName' => $library->displayName,
			'numItemsWithVolumes' => $numItemsWithVolumes,
			'numItemsWithoutVolumes' => $numItemsWithoutVolumes,
			'hasItemsWithoutVolumes' => $hasItemsWithoutVolumes,
			'majorityOfItemsHaveVolumes' => $majorityOfItemsHaveVolumes,
		];
	}

	function getRecords() : array {
		if (!isset($_REQUEST['id']) || !isset($_REQUEST['format']) || !isset($_REQUEST['source'])) {
			return [
				'success' => false,
				'message' => 'Grouped work id, source or format not provided'
			];
		}
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$groupedWorkId = $_REQUEST['id'];
		$format = $_REQUEST['format'];
		$source = $_REQUEST['source'];
		$recordDriver = new GroupedWorkDriver($groupedWorkId);
		if (!$recordDriver->isValid) {
			return [
				'success' => false,
				'message' => 'Invalid Grouped Work Id'
			];
		}
		$relatedManifestation = null;
		$relatedVariation = null;
		$relatedRecord = null;
		$records = [];
		global $interface;
		global $library;
		$relatedManifestations = $recordDriver->getRelatedManifestations();
		if (empty($relatedManifestations)) {
			return [
				'success' => false,
				'message' => 'No records found for the provided grouped work'
			];
		}
		$relatedManifestationFound = false;
		foreach ($recordDriver->getRelatedManifestations() as $relatedManifestation) {
			if ($relatedManifestation->format == $format) {
				$relatedManifestationFound = true;
				break;
			}
		}
		$records = [];
		if (!$relatedManifestationFound) {
			return [
				'success' => false,
				'message' => 'The specified format did not exist in the work'
			];
		}
		foreach ($relatedManifestation->getRelatedRecords() as $relatedRecord) {
			if ($relatedRecord->source == $source) {
				foreach ($relatedRecord->recordVariations as $variationFormat => $recordVariation) {
					if ($variationFormat == $format) {
						$recordInfo = explode(':', $relatedRecord->id);
						$recordType = $recordInfo[0];
						$recordId = $recordInfo[1];
						$source = $relatedRecord->source;

						$holdType = 'bib';

						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$marcRecord = new MarcRecordDriver($groupedWorkId);
						global $indexingProfiles;
						$indexingProfile = $indexingProfiles[$marcRecord->getRecordType()];
						$formatMap = $indexingProfile->formatMap;
						/** @var FormatMapValue $formatMapValue */
						foreach ($formatMap as $formatMapValue) {
							if ($formatMapValue->format === $format) {
								$holdType = $formatMapValue->holdType;
							}
						}

						if ($holdType == 'either') {
							//Check for an override at the library level
							if ($library->treatBibOrItemHoldsAs == 2) {
								$holdType = 'bib';
							} elseif ($library->treatBibOrItemHoldsAs == 3) {
								$holdType = 'item';
							}
						}

						$records[$relatedRecord->id]['id'] = $relatedRecord->id;
						$records[$relatedRecord->id]['source'] = $relatedRecord->source;
						$records[$relatedRecord->id]['recordId'] = $recordId;
						$records[$relatedRecord->id]['format'] = translate([
							'text' => $variationFormat,
							'isPublicFacing' => true
						]);
						$records[$relatedRecord->id]['edition'] = $relatedRecord->edition;
						$records[$relatedRecord->id]['publisher'] = $relatedRecord->publisher;
						$records[$relatedRecord->id]['publicationDate'] = $relatedRecord->publicationDate;
						$records[$relatedRecord->id]['placeOfPublication'] = $relatedRecord->placeOfPublication;
						$records[$relatedRecord->id]['physical'] = $relatedRecord->physical;
						$records[$relatedRecord->id]['closedCaptioned'] = $relatedRecord->closedCaptioned;
						$records[$relatedRecord->id]['variationId'] = $recordVariation->databaseId;
						$records[$relatedRecord->id]['holdType'] = $holdType;
						$records[$relatedRecord->id]['statusIndicator'] = [
							'isAvailable' => $relatedRecord->getStatusInformation()->isAvailable(),
							'isEContent' => $relatedRecord->getStatusInformation()->isEContent(),
							'isAvailableOnline' => $relatedRecord->getStatusInformation()->isAvailableOnline(),
							'groupedStatus' => $relatedRecord->getStatusInformation()->getGroupedStatus(),
							'isAvailableHere' => $relatedRecord->getStatusInformation()->isAvailableHere(),
							'isAvailableLocally' => $relatedRecord->getStatusInformation()->isAvailableLocally(),
							'isAllLibraryUseOnly' => $relatedRecord->getStatusInformation()->isAllLibraryUseOnly(),
							'hasLocalItem' => $relatedRecord->getStatusInformation()->hasLocalItem(),
							'numCopiesMessage' => $relatedRecord->getStatusInformation()->getNumberOfCopiesMessage(),
							'numHolds' => $relatedRecord->getStatusInformation()->getNumHolds(),
							'numOnOrder' => $relatedRecord->getStatusInformation()->getOnOrderCopies(),
							'isShowStatus' => $relatedRecord->getStatusInformation()->isShowStatus(),
							'showGroupedHoldCopiesCount' => (int)$library->showGroupedHoldCopiesCount,
							'showItsHere' => (int)$library->showItsHere,
							'isGlobalScope' => $interface->getVariable('isGlobalScope'),
						];

						$buttons = [];
						$actionButtons = $relatedRecord->getActions();
						foreach ($actionButtons as $key => $actionButton) {
							$buttons[$key]['id'] = $key;
							$buttons[$key]['type'] = $actionButton['type'] ?? null;
							$buttons[$key]['title'] = $actionButton['title'];
							$buttons[$key]['url'] = $actionButton['url'] ?? null;
							$buttons[$key]['requireLogin'] = $actionButton['requireLogin'] ?? false;
							$buttons[$key]['sampleNumber'] = $actionButton['sampleNumber'] ?? null;
							$buttons[$key]['formatId'] = $actionButton['formatId'] ?? null;
							$buttons[$key]['volumeId'] = $actionButton['volumeId'] ?? null;
							$buttons[$key]['volumeName'] = $actionButton['volumeName'] ?? null;
							$buttons[$key]['message'] = $actionButton['message'] ?? null;

							if (isset($actionButton['redirectUrl'])) {
								$buttons[$key]['redirectUrl'] = $actionButton['redirectUrl'];
							}
							$buttons[$key]['redirectParams'] = $actionButton['redirectParams'] ?? null;
						}

						if ($this->context == 'lida') {
							require_once ROOT_DIR . '/sys/AspenLiDA/GeneralSetting.php';
							$appGeneralSetting = new GeneralSetting();
							$appGeneralSetting->id = $library->lidaGeneralSettingId;
							if($appGeneralSetting->find(true)) {
								$showMoreInfo = $appGeneralSetting->showMoreInfoBtn;
							}else{
								$showMoreInfo = false;
							}

							if ($showMoreInfo) {
								$moreInfoButton = [
									'type' => 'more_info_link',
									'title' => translate([
										'text' => 'More Info',
										'isPublicFacing' => true
									]),
									'source' => $relatedRecord->source,
									'groupedWorkId' => $relatedRecord->getDriver()->getGroupedWorkId(),
									'module' => $relatedRecord->getDriver()->getModule(),
									'recordId' => $relatedRecord->getDriver()->getId()
								];
								$buttons[] = $moreInfoButton;
							}
						}

						$records[$relatedRecord->id]['actions'] = $buttons;
						//$records[$relatedRecord->id]['information'] = $relatedRecord->getItemSummary();

						if ($source == 'hoopla') {
							require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
							$hooplaDriver = new HooplaRecordDriver($recordId);
							$publicationDate = $hooplaDriver->getPublicationDates();
							if (is_array($publicationDate) && $publicationDate != null) {
								$publicationDate = $publicationDate[0];
							} elseif (count($publicationDate) == 0) {
								$publicationDate = $relatedRecord->publicationDate;
							}
							$records[$relatedRecord->id]['publicationDate'] = $publicationDate;
							$publisher = $hooplaDriver->getPublishers();
							if (is_array($publisher) && $publisher != null) {
								$publisher = $publisher[0];
							} elseif (count($publisher) == 0) {
								$publisher = $relatedRecord->publisher;
							}
							$records[$relatedRecord->id]['publisher'] = $publisher;
							$placeOfPublication = $hooplaDriver->getPlacesOfPublication();
							if (is_array($placeOfPublication) && $placeOfPublication != null) {
								$placeOfPublication = $placeOfPublication[0];
							} elseif (count($placeOfPublication) == 0) {
								$placeOfPublication = $relatedRecord->placeOfPublication;
							}
							$records[$relatedRecord->id]['placeOfPublication'] = $placeOfPublication;
							$edition = $hooplaDriver->getEditions();
							if (is_array($edition) && $edition != null) {
								$edition = $edition[0];
							} elseif (count($edition) == 0) {
								$edition = $relatedRecord->edition;
							}
							$records[$relatedRecord->id]['edition'] = $edition;
							$physical = $hooplaDriver->getPhysicalDescriptions();
							if (is_array($physical) && $physical != null) {
								$physical = $physical[0];
							} elseif (count($physical) == 0) {
								$physical = $relatedRecord->physical;
							}
							$records[$relatedRecord->id]['physical'] = $physical;
						} elseif ($source == 'cloudlibrary') {
							require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
							$cloudLibraryDriver = new CloudLibraryRecordDriver($recordId);
							$records[$relatedRecord->id]['publicationDate'] = $cloudLibraryDriver->getPublicationDates();
							$records[$relatedRecord->id]['placeOfPublication'] = $cloudLibraryDriver->getPlacesOfPublication();
							$records[$relatedRecord->id]['publisher'] = $cloudLibraryDriver->getPublishers();
							$records[$relatedRecord->id]['edition'] = $cloudLibraryDriver->getEditions();
						} elseif ($source == 'axis360') {
							require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
							$axis360Driver = new Axis360RecordDriver($recordId);
							$records[$relatedRecord->id]['publicationDate'] = $axis360Driver->getPublicationDates();
							$records[$relatedRecord->id]['placeOfPublication'] = $axis360Driver->getPlacesOfPublication();
							$records[$relatedRecord->id]['publisher'] = $axis360Driver->getPublishers();
							$records[$relatedRecord->id]['edition'] = $axis360Driver->getEditions();
						}

						//Get a list of volumes for the record
						require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
						$volumeData = [];
						$volumeDataDB = new IlsVolumeInfo();
						$volumeDataDB->recordId = $relatedRecord->id;
						$volumeDataDB->orderBy('displayOrder ASC, displayLabel ASC');
						if ($volumeDataDB->find()) {
							while ($volumeDataDB->fetch()) {
								$volumeData[$volumeDataDB->volumeId] = clone($volumeDataDB);
								$volumeData[$volumeDataDB->volumeId]->setHasLocalItems(false);
							}
						}

						$numItemsWithVolumes = 0;
						$numItemsWithoutVolumes = 0;
						foreach ($relatedRecord->getItems() as $item) {
							if ($item->holdable) {
								if (empty($item->volume)) {
									$numItemsWithoutVolumes++;
								} else {
									if ($item->libraryOwned || $item->locallyOwned) {
										if (array_key_exists($item->volumeId, $volumeData)) {
											$volumeData[$item->volumeId]->setHasLocalItems(true);
										}
									}
									$numItemsWithVolumes++;
								}
							}
						}
						$records[$relatedRecord->id]['numItemsWithVolumes'] = $numItemsWithVolumes;
						$records[$relatedRecord->id]['numItemsWithoutVolumes'] = $numItemsWithoutVolumes;
						$records[$relatedRecord->id]['hasItemsWithoutVolumes'] = $numItemsWithoutVolumes > 0;
						$records[$relatedRecord->id]['majorityOfItemsHaveVolumes'] = $numItemsWithVolumes > $numItemsWithoutVolumes;
					}
				}
			}
		}

		return [
			'success' => true,
			'id' => $groupedWorkId,
			'format' => $format,
			'records' => $records,
		];
	}

	/**
	 * Return a list of all holdable volumes for a specific record id
	 *
	 * @return array
	 */
	function getVolumes() : array {
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'Record id not provided'
			];
		}

		require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
		$marcRecord = new MarcRecordDriver($_REQUEST['id']);
		$alwaysPlaceVolumeHoldWhenVolumesArePresent = $marcRecord->getCatalogDriver()->alwaysPlaceVolumeHoldWhenVolumesArePresent();
		$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());

		//Get a list of volumes for the record
		require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
		$volumeData = [];
		$volumeDataDB = new IlsVolumeInfo();
		$volumeDataDB->recordId = $marcRecord->getIdWithSource();
		$volumeDataDB->orderBy('displayOrder ASC, displayLabel ASC');
		if ($volumeDataDB->find()) {
			while ($volumeDataDB->fetch()) {
				$volumeData[$volumeDataDB->volumeId] = clone($volumeDataDB);
				$volumeData[$volumeDataDB->volumeId]->setHasLocalItems(false);
			}
		}

		$numItemsWithVolumes = 0;
		$numItemsWithoutVolumes = 0;
		foreach ($relatedRecord->getItems() as $item) {
			if ($item->holdable) {
				if (empty($item->volume)) {
					$numItemsWithoutVolumes++;
				} else {
					$volumeData[$item->volumeId]->addItem($item);
					if ($item->libraryOwned || $item->locallyOwned) {
						if (array_key_exists($item->volumeId, $volumeData)) {
							$volumeData[$item->volumeId]->setHasLocalItems(true);
						}
					}
					$numItemsWithVolumes++;
				}
			}
		}

		if ($numItemsWithoutVolumes > 0 && $alwaysPlaceVolumeHoldWhenVolumesArePresent) {
			$blankVolume = new IlsVolumeInfo();
			$blankVolume->displayLabel = translate([
				'text' => 'Untitled Volume',
				'isPublicFacing' => true,
			]);
			$blankVolume->volumeId = '';
			$blankVolume->recordId = $marcRecord->getIdWithSource();
			$blankVolume->relatedItems = '';
			$blankVolume->displayOrder = '0';
			$blankVolume->setHasLocalItems(false);
			$blankVolume->holdableItems = [];
			foreach ($relatedRecord->getItems() as $item) {
				if (empty($item->volumeId) && $item->holdable) {
					$blankVolume->addItem($item);
					if ($item->libraryOwned || $item->locallyOwned) {
						$blankVolume->setHasLocalItems(true);
					}
					$blankVolume->relatedItems .= $item->itemId . '|';
				}
			}
			$volumeData[] = $blankVolume;
		}
		$volumeDataDB = null;
		unset($volumeDataDB);

		//Sort the volumes so locally owned volumes are shown first
		$volumeSorter = function (IlsVolumeInfo $a, IlsVolumeInfo $b) {
			if ($a->hasLocalItems() && !$b->hasLocalItems()) {
				return -1;
			} elseif ($b->hasLocalItems() && !$a->hasLocalItems()) {
				return 1;
			} else {
				if ($a->displayOrder > $b->displayOrder) {
					return 1;
				} elseif ($b->displayOrder > $a->displayOrder) {
					return -1;
				} else {
					return 0;
				}
			}
		};

		global $library;
		if ($library->showVolumesWithLocalCopiesFirst) {
			uasort($volumeData, $volumeSorter);
		}

		$volumes = [];
		$i = 0;
		foreach($volumeData as $key => $volume) {
			if (empty($volume->getItems())) {
				unset($volumeData['key']);
				continue;
			}
			$label = $volume->displayLabel;
			if($alwaysPlaceVolumeHoldWhenVolumesArePresent && $volume->hasLocalItems()) {
				$label .= ' (' . translate(['text' => 'Owned by %1%', 'isPublicFacing' => true, 1=>$library->displayName]) . ')';
			}
			$volumes[$volume->id]['key'] = $i;
			$volumes[$volume->id]['id'] = $volume->id;
			$volumes[$volume->id]['label'] = $label;
			$volumes[$volume->id]['displayOrder'] = $volume->displayOrder;
			$volumes[$volume->id]['volumeId'] = $volume->volumeId;
			$i++;
		}

		return [
			'success' => true,
			'id' => $_REQUEST['id'],
			'volumes' => $volumes,
		];
	}

	function getRelatedRecord() {
		if (!isset($_REQUEST['id']) || !isset($_REQUEST['record']) || !isset($_REQUEST['format'])) {
			return [
				'success' => false,
				'message' => 'Grouped work id, record id or format not provided'
			];
		}

		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$id = $_REQUEST['id'];
		$recordDriver = new GroupedWorkDriver($id);
		$recordId = $_REQUEST['record'];
		$selectedFormat = urldecode($_REQUEST['format']);

		$relatedManifestation = null;
		foreach ($recordDriver->getRelatedManifestations() as $relatedManifestation) {
			if ($relatedManifestation->format == $selectedFormat) {
				break;
			}
		}

		$summary = [];
		$record = $recordDriver->getRelatedRecord($recordId);
		if ($record != null) {
			$summary = $record->getItemSummary();
		} else {
			$summary = null;
			foreach ($relatedManifestation->getVariations() as $variation) {
				if ($recordId == $id . '_' . $variation->label) {
					$summary = $variation->getItemSummary();
					break;
				}
			}
		}

		return [
			'success' => true,
			'id' => $_REQUEST['id'],
			'recordId' => $_REQUEST['record'],
			'format' => translate(['text' => $_REQUEST['format'], 'isPublicFacing' => true]),
			'record' => $summary,
		];
	}
}