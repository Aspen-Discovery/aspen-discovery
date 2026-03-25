<?php

require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/Gale/GaleSetting.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_GaleSearcher extends SearchObject_BaseSearcher {
	static $instance;

	protected $limit = 20;
	protected $sortOptions = array();
	protected $defaultSort = 'relevance';
	private ?string $dateRangeStart = null;
	private ?string $dateRangeEnd = null;

	/** @var GaleSetting */
	private string $galeBaseUrl = 'https://sru.galegroup.com/';
	/** @var SimpleXMLElement[] */
	private array $lastSearchResults = [];
	private ?SimpleXMLElement $lastResponse = null;
	private $curl_connection;

	/**Track query time info */
	protected $queryStartTime = null;
	protected $queryEndTime = null;
	protected $queryTime = null;


	private array $searchIndexFieldMap = [
		'Keyword' => null,
		'Title' => 'dc.title',
		'Author' => 'dc.creator',
		'Subject' => 'dc.subject',
		'Publication' => 'dc.relation',
		'ISSN' => 'bath.issn',
	];

	public function __construct() {
		parent::__construct();
		$this->searchSource = 'gale';
		$this->searchType = 'gale';
		$this->resultsModule = 'Gale';
		$this->resultsAction = 'Results';
	}

	
	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 */
	public function init($searchSource = null) : bool {
		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch();
		if ($restored === true) {
			return true;
		} elseif ($restored instanceof AspenError) {
			return false;
		}

		//********************
		// Initialize standard search parameters
		$this->initView();
		$this->initPage();
		$this->initFilters();
		$this->initLimiters();
		//Sorting needs to be initialized after filters since they depend on the selected database
		$this->initSort();

		//********************
		// Basic Search logic
		if (!$this->initBasicSearch()) {
			$this->initAdvancedSearch();
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = $_REQUEST['q'];
		}

		return true;
	}

	protected function initFilters(): void {
		parent::initFilters();
		$range = $this->filterList['start_date'][0] ?? null;
		if ($range && preg_match('/\\[(.*?)\\sTO\\s(.*?)\\]/', $range, $matches)) {
			$this->dateRangeStart = $matches[1] !== '*' ? $matches[1] : null;
			$this->dateRangeEnd = $matches[2] !== '*' ? $matches[2] : null;
		}
		if ($this->dateRangeStart === null && $this->dateRangeEnd === null) {
			unset($this->filterList['start_date']);
			return;
		}
		$start = $this->dateRangeStart ?? '*';
		$end   = $this->dateRangeEnd ?? '*';
		$this->filterList['start_date'] = [
			'[' . $start . ' TO ' . $end . ']',
		];
	}

	/**
	 * Create an instance of the Gale Searcher
	 * @return SearchObject_GaleSearcher
	 */
	public static function getInstance(): SearchObject_GaleSearcher {
		if (SearchObject_GaleSearcher::$instance == null) {
			SearchObject_GaleSearcher::$instance = new SearchObject_GaleSearcher();
		}
		return SearchObject_GaleSearcher::$instance;
	}

	public function getEngineName() {
		return 'Gale';
	}

	public function getCurlConnection() {
		if ($this->curl_connection == null) {
			$this->curl_connection = curl_init();
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
		}
		return $this->curl_connection;
	}

	public function processSearch(bool $returnIndexErrors = false, bool $recommendations = false, bool $preventQueryModification = false): AspenError|SimpleXMLElement|array|null {
		$galeSettings = $this->getSettings();
		if ($galeSettings == null || empty($galeSettings->locationId)) {
			return new AspenError('Gale searching is not configured for this library.');
		}

		$productCode = $this->getProductCode();
		$searchTerm = $this->searchTerms[0]['lookfor'] ?? '';
		$searchIndex = $this->searchTerms[0]['index'] ?? '';
		$mappedIndex = $this->searchIndexFieldMap[$searchIndex] ?? '';
		if ($mappedIndex === '') {
			$searchQuery = $searchTerm;
		} else {
			$searchQuery = $mappedIndex . '=' . $searchTerm;
		}
		if ($this->dateRangeStart !== null || $this->dateRangeEnd !== null) {
			$dateClauses = [];
			if ($this->dateRangeStart !== null) {
				$normalizedStart = $this->normalizeDateFomat($this->dateRangeStart);
				$dateClauses[] = 'dc.date>=' . $normalizedStart;
			}
			if ($this->dateRangeEnd !== null) {
				$normalizedEnd = $this->normalizeDateFomat($this->dateRangeEnd);
				$dateClauses[] = 'dc.date<=' . $normalizedEnd;
			}
			$searchQuery .= ' and ' . implode(' and ', $dateClauses);
		}
		$filterFullText = isset($this->filterList['fullTextOnly']) && in_array('fullTextOnly', $this->filterList['fullTextOnly'], true);
		if ($galeSettings->fullTextOnly || $filterFullText) {
			$searchQuery .= ' and marc.992=fulltext';
		}
		$this->lastSearchResults = [];
		$this->lastResponse = null;

		$this->startQueryTimer();

		$startRecord = (($this->page - 1) * $this->limit) + 1;
		$params = [
			'startRecord' => $startRecord,
			'maximumRecords' => $this->limit,
			'operation' => 'searchRetrieve',
			'version' => '1.1',
			'query' => $searchQuery,
			'x-username' => $galeSettings->locationId,
		];
		$queryParams = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
		$requestUrl = rtrim($this->galeBaseUrl, '/') . '/' . rawurlencode($productCode) . '?' . $queryParams;

		$curl = $this->getCurlConnection();
		curl_setopt($curl, CURLOPT_URL, $requestUrl);
		$response = curl_exec($curl);
		if ($response === false) {
			$this->stopQueryTimer();
			return new AspenError('Error retrieving data from Gale.');
		}

		$xml = simplexml_load_string($response);
		if ($xml === false) {
			$this->stopQueryTimer();
			return new AspenError('Error processing search in Gale.');
		}
		$xml->registerXPathNamespace('zs', 'http://www.loc.gov/zing/srw/');
		$xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

		if (isset($xml->diagnostics->diagnostic)) {
			$message = (string)$xml->diagnostics->diagnostic->message;
			$this->stopQueryTimer();
			return new AspenError("Error processing search in Gale: $message");
		}

		$this->lastResponse = $xml;
		$numberNodes = $xml->xpath('//zs:numberOfRecords');
		$this->resultsTotal = $numberNodes ? (int)$numberNodes[0] : 0;

		$this->lastSearchResults = $xml->xpath('//zs:records/zs:record/zs:recordData/dc:dc') ?: [];

		$this->stopQueryTimer();
		return $this->lastSearchResults;
	}

	public function getResultSummary(): array {
		$summary = [];
		$summary['page'] = $this->page;
		$summary['perPage'] = $this->limit;
		$summary['resultTotal'] = $this->resultsTotal;
		$summary['startRecord'] = (($this->page - 1) * $this->limit) + 1;
		if ($this->resultsTotal < $this->limit) {
			$summary['endRecord'] = $this->resultsTotal;
		} elseif (($this->page * $this->limit) > $this->resultsTotal) {
			$summary['endRecord'] = $this->resultsTotal;
		} else {
			$summary['endRecord'] = $this->page * $this->limit;
		}
		return $summary;
	}

	public function getResultRecordHTML(): array {
		global $interface;
		$html = [];

		if (!empty($this->lastSearchResults)) {
			require_once ROOT_DIR . '/RecordDrivers/GaleRecordDriver.php';
			foreach ($this->lastSearchResults as $index => $recordNode) {
				$interface->assign('recordIndex', $index + 1);
				$interface->assign('resultIndex', $index + 1 + (($this->page - 1) * $this->limit));

				$record = new GaleRecordDriver($recordNode);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}

		$this->addToHistory();

		return $html;
	}

	public function getCombinedResultHTML(): array {
		global $interface;
		$html = [];

		if (!empty($this->lastSearchResults)) {
			require_once ROOT_DIR . '/RecordDrivers/GaleRecordDriver.php';
			foreach ($this->lastSearchResults as $index => $recordNode) {
				$interface->assign('recordIndex', $index + 1);
				$interface->assign('resultIndex', $index + 1 + (($this->page - 1) * $this->limit));

				$recordDriver = new GaleRecordDriver($recordNode);
				if ($recordDriver->isValid()) {
					$interface->assign('recordDriver', $recordDriver);
					$html[] = $interface->fetch($recordDriver->getCombinedResult());
				}
			}
		}

		return $html;
	}

	public function retrieveRecord(string $identifier): ?SimpleXMLElement {
		$settings = $this->getSettings();
		if ($settings === null || empty($settings->locationId)) {
			return null;
		}
	
		$productCode = $this->getProductCode();
		$params = [
			'startRecord'    => 1,
			'maximumRecords' => 1,
			'operation'      => 'searchRetrieve',
			'version'        => '1.1',
			'query'          => 'rec.id=' . $identifier . '',
			'x-username'     => $settings->locationId,
		];
		$recordUrl = rtrim($this->galeBaseUrl, '/') . '/' . rawurlencode($productCode) .
			'?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
		$curl = $this->getCurlConnection();
		curl_setopt($curl, CURLOPT_URL, $recordUrl);
		$response = curl_exec($curl);
		if ($response === false) {
			return null;
		}
		$xml = simplexml_load_string($response);
		if ($xml === false) {
			return null;
		}
		$xml->registerXPathNamespace('zs', 'http://www.loc.gov/zing/srw/');
		$xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		$records = $xml->xpath('//zs:records/zs:record/zs:recordData/dc:dc');
		return $records ? $records[0] : null;
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string[] $ids An array of documents to retrieve from Solr
	 * @access  public
	 * @return  array              The requested resources
	 */
	public function getRecords($ids) {
		$records = [];
		require_once ROOT_DIR . '/RecordDrivers/GaleRecordDriver.php';
		foreach ($ids as $index => $id) {
			$records[$index] = new GaleRecordDriver($id);
		}
		return $records;
	}
	

	public function getSearchIndexes(): array {
		return [
			'Keyword' => translate([
				'text' => 'Keyword',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'Title' => translate([
				'text' => 'Title',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'Author' => translate([
				'text' => 'Author',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'Subject' => translate([
				'text' => 'Subject',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'Publication' => translate([
				'text' => 'Publication',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'ISSN' => translate([
				'text' => 'ISSN',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
		];
	}

	public function getSortOptions(): array {
		return [
			'relevance' => translate([
				'text' => 'Relevance',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
		];
	}

	public function getFacetSet(): array {
		if (empty($this->lastSearchResults)) {
			return [];
		}
		$facetSet = [];
		$facetDefinitions = $this->getFacetDefinitions();
		$settings = $this->getSettings();
		foreach ($facetDefinitions as $facetKey => $facetDefinition) {
			if ($facetKey === 'productCode') {
				if ($settings != null && !empty($settings->getProductCodes())){
					$productCodes = $settings->getProductCodes();
					foreach ($productCodes as $productCode) {
						if (empty($productCode->productCode)) {
							continue;
						}
						$code = $productCode->productCode;
						// Only show the product code that is filtered
						if (!empty($this->filterList['productCode']) && !in_array($code, $this->filterList['productCode'], true)) {
							continue;
						}
						$isApplied = isset($this->filterList['productCode']) && in_array($code, $this->filterList['productCode'], true);
						$list[$code] = [
							'value' => $code,
							'count' => '',
							'display' => $productCode->displayName ?: $code,
							'url' => $this->renderLinkWithFilter('productCode', $code),
							'removalUrl' => $this->renderLinkWithoutFilter("productCode:$code"),
							'isApplied' => $isApplied,
						];
					}
					if (!empty($list)) {
						$facetSet['productCode'] = [
							'label' => 'Product',
							'collapseByDefault' => false,
							'multiSelect' => false,
							'valuesToShow' => 5,
							'list' => $list,
							'hasApplied' => isset($this->filterList['productCode']) && !empty($this->filterList['productCode']),
						];
					}
				}

			} elseif ($facetKey === 'start_date') {
				$startDate = $this->dateRangeStart;
				$endDate = $this->dateRangeEnd;
				$facetSet['start_date'] = [
					'label' => 'Date',
					'collapseByDefault' => false,
					'valuesToShow' => 0,
					'list' => ['_gale_date' => ['display' => '']],
					'hasApplied' => $this->dateRangeStart !== null || $this->dateRangeEnd !== null,
					'start' => $startDate,
					'end' => $endDate,
					'allowPastDates' => true,
				];
			} elseif ($facetKey === 'fullTextOnly') {
				if ($settings != null && !$settings->fullTextOnly) {
					$fullTextValue = 'fullTextOnly';
					$isApplied = isset($this->filterList['fullTextOnly']) && in_array($fullTextValue, $this->filterList['fullTextOnly'], true);
					$facetSet['fullTextOnly'] = [
						'label' => 'Full Text Only',
						'collapseByDefault' => false,
						'valuesToShow' => 2,
						'list' => [
							$fullTextValue => [
								'value' => $fullTextValue,
								'count' => '',
								'display' => translate(['text' => 'Full Text Only', 'isPublicFacing' => true]),
								'url' => $this->renderLinkWithFilter('fullTextOnly', $fullTextValue),
								'removalUrl' => $this->renderLinkWithoutFilter("fullTextOnly:$fullTextValue"),
								'isApplied' => $isApplied,

							],
						],
						'hasApplied' => $isApplied,
					];
				}
			}
		}
		return $facetSet;
	}

	public function getFilterList(): array {
		$filterList = [];
		$facetDefinitions = $this->getFacetDefinitions();
		$settings = $this->getSettings();
		$productCodeLabels = [];
		if ($settings != null) {
			foreach ($settings->getProductCodes() as $productCode) {
				if (!empty($productCode->productCode)) {
					$productCodeLabels[$productCode->productCode] = $productCode->displayName ?: $productCode->productCode;
				}
			}
		}

		foreach ($this->filterList as $field => $values) {
			$label = $facetDefinitions[$field]['label'] ?? ucfirst($field);
			foreach ($values as $value) {
				if ($field === 'productCode') {
					$display = isset($productCodeLabels[$value]) ? $productCodeLabels[$value] : $value;
				} elseif ($field === 'start_date') {
					$display = '';
					$utcTimeZone = new DateTimeZone('UTC');
					$defaultTimezone = new DateTimeZone(date_default_timezone_get());
					if ($this->dateRangeStart != '*') {
						$dt = new DateTime($this->dateRangeStart, $utcTimeZone);
						$startDate =  $dt->format("m/d/Y");
					}else{
						$startDate = '';
					}
					if ($this->dateRangeEnd != '*') {
						$dt = new DateTime($this->dateRangeEnd, $utcTimeZone);
						$endDate = $dt->format("m/d/Y");
					}else{
						$endDate = '';
					}
					if (empty($startDate)) {
						$display = translate(['text'=>'Before %1%', 1=>$endDate, 'isPublicFacing'=>true]);
					}else if (empty($endDate)) {
						$display = translate(['text'=>'After %1%', 1=>$startDate, 'isPublicFacing'=>true]);
					}else{
						$display = translate(['text'=>'Between %1% and %2%', 1=>$startDate, 2=>$endDate, 'isPublicFacing'=>true]);
					}
				} elseif ($field === 'fullTextOnly') {
					$display = translate(['text' => 'Full Text Only', 'isPublicFacing' => true]);
				}
				$filterList[$label][] = [
					'value' => $value,
					'display' => $display,
					'field' => $field,
					'url' => $this->renderLinkWithFilter($field, $value),
					'removalUrl' => $this->renderLinkWithoutFilter("$field:$value"),
					'countIsApproximate' => false,
				];
			}
		}
		return $filterList;
	}

	function getSearchesFile() {
		// Gale does not have a searches file, we load dynamically
		return false;
	}

	public function getIndexError() {
		// TODO: Implement getIndexError() method.
	}

	public function buildRSS($result = null) {
		// TODO: Implement buildRSS() method.
	}

	public function buildExcel($result = null) {
		// TODO: Implement buildExcel() method.
	}

	public function getResultRecordSet() {
		// TODO: Implement getResultRecordSet() method.
	}

	public function getSearchName() {
		return $this->searchSource;
	}

	public function getDefaultIndex() {
		return 'Keyword';
	}

	function loadValidFields() {
		// TODO: Implement loadValidFields() method.
	}

	function loadDynamicFields() {
		// TODO: Implement loadDynamicFields() method.
	}

	/**
	 * Gale defines the facets instead of building dynamically from the search results
	 *
	 */
	private function getFacetDefinitions(): array {
		return [
			'productCode' => [
				'label' => translate([
					'text' => 'Product',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]),
			],
			'start_date' => [
				'label' => translate([
					'text' => 'Date',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]),
			],
			'fullTextOnly' => [
				'label' => translate([
					'text' => 'Full Text Only',
					'isPublicFacing' => true,
					'inAttribute' => true,
				]),
			],
		];
	}

	private function getSettings() {
		global $library;
		if ($library->galeSettingsId != -1) {
			$galeSetting = new GaleSetting();
			$galeSetting->id = $library->galeSettingsId;
			if (!$galeSetting->find(true)) {
				$galeSetting = null;
			}
			return $galeSetting;
		}
		return null;
	}	

	private function getProductCode(): string {
		$filterValues = $this->filterList['productCode'] ?? [];
		if (!empty($filterValues)) {
			return (string)reset($filterValues);
		}
		return 'GPS';
	}
	private function normalizeDateFomat(string $date): string {
		if (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $date, $matches)) {
			return $matches[1] . $matches[2] . $matches[3];
		}
		return $date;
	}

	public function __destruct() {
		if ($this->curl_connection) {
			curl_close($this->curl_connection);
		}
	}

	public function setSearchTerm($searchTerm) {
		if (strpos($searchTerm, ':') !== false) {
			[
				$searchIndex,
				$term,
			] = explode(':', $searchTerm, 2);
			$this->setSearchTerms([
				'lookfor' => $term,
				'index' => $searchIndex,
			]);
		} else {
			$this->setSearchTerms([
				'lookfor' => $searchTerm,
				'index' => $this->getDefaultIndex(),
			]);
		}
	}
		
}
