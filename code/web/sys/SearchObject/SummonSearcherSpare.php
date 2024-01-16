<?php

require_once ROOT_DIR . '/sys/Summon/SummonSettings.php';
require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_SummonSearcher extends SearchObject_BaseSearcher{

    const IDENTIFIER_ID = 1;
    const IDENTIFIER_BOOKMARK = 2;
    static $instance;
    private $summonSettings;
    private $summonBaseApi ='http://api.summon.serialssolutions.com';
	private $summonApiId;
	private $summonApiPassword;
    private $sessionId;
    private $version = '2.0.0';
    private $service = 'search';
    private $authedUser = false;
    private $responseType = "json";
    private static $searchOptions;
    private $params = array();
    private $method = 'GET';
	private $filters = array();
    private $raw = false;
    private $curl_connection;


    /**
	 * @var string mixed
	 */
	private $searchIndex = 'Everything';

    protected $queryStartTime = null;
	protected $queryEndTime = null;
	protected $queryTime = null;

    // Page number
	// protected $page = 1;
	// Result limit
	// protected $limit = 20;

	// Sorting
	protected $sort = null;
	protected $defaultSort = 'relevance';
	protected $debug = false;

    // STATS
	protected $resultsTotal;

	protected $searchTerms;

	protected $lastSearchResults;

    //From base searcher
    // Module and Action for building search results URLs
	protected $resultsModule = 'Search';
	protected $resultsAction = 'Results';
    	/** @var string */
	protected $searchSource = 'local';
    protected $searchType = 'basic';

	protected $pageSize = 20;

	// protected $facets;
/** Values for the options array*/
	protected $holdings = false;
	protected $didYouMean = false;
	protected $language = 'en';
	protected $idsToFetch = array();
	protected $maxTopics = 1;
	protected $groupFilters = array();
	protected $rangeFilters = array();
	// protected $expand = false;
	protected $openAccessFilter = false;
	protected $highlight = false;
	protected $pageNumber = 1;
	protected $sendQuery = null;
	protected $expand = false;


	protected $facetValueFilters = [
		// 'ContentType,or,1,30',
		// 'IsScholarly,or,1,2',
		// 'Discipline',
		// 'Library,or,1,30',
		// 'SubjectTerms,or,130',
		// 'Language,or,1,30'
		// <facet name=”Audience” cardinality=”ZeroToMany” type=”string”/>
		'Author',
		'ContentType',
		'CorporateAuthor',
		'DatabaseName',
		'Discipline',
		'SubjectTerms',
		'PublicationYear',
	];

	protected $limitOptions = [
		'Full Text Online',
		'Scholarly',
		'Peer Reviewed',
		'Open Access',
		'Available in Library Collection',
	];
	private $listFacetValues;

	

	protected $facets;

	protected $clearAllFacetFields;
	protected $removeFacetField;
	protected $addFacetField;
	protected $facetFields;
	protected $queryFacets;
	protected $facetValue;
	protected $sortOptions = [];
	protected $queryOptions = [];



    public function __construct() {
        $this->searchSource = 'summon';
        $this->searchType = 'summon';
        $this->resultsModule = 'Summon';
        $this->resultsAction = 'Results';
    }

      /**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 * @param string $searchSource
	 * @return  boolean
	 */
	public function init($searchSource = null) {
		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch();
		if ($restored === true) {
			//there is a saved search that can be reused
			return true;
		} elseif ($restored instanceof Exception) {
			//there is an error with hte restored search
			return false;
		}
		//Carry out a new search
		//********************
		// Initialize standard search parameters
		$this->initView();
		$this->initPage();
		$this->initSort();
		$this->initFilters();
		$this->initLimiters();

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

    	/**
	 * @return SearchObject_SummonSearcher
	 */
	public static function getInstance() {
		if (SearchObject_SummonSearcher::$instance == null) {
			SearchObject_SummonSearcher::$instance = new SearchObject_SummonSearcher();
		}
		return SearchObject_SummonSearcher::$instance;
	}


	//Retreive settings for institution's summon connector
	private function getSettings() {
		global $library;
		if ($this->summonSettings == null) {
			$this->summonSettings = new SummonSettings();
			$this->summonSettings->id = $library->summonSettingsId;
			if (!$this->summonSettings->find(true)) {
				$this->summonSettings = null;
			}
		}
		return $this->summonSettings;
	}


    public function getCurlConnection() {
		if ($this->curl_connection == null) {
            $this->curl_connection = curl_init();
			curl_setopt($this->curl_connection, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($this->curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl_connection, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->curl_connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($this->curl_connection, CURLOPT_TIMEOUT, 30);
			curl_setopt($this->curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
		}
		return $this->curl_connection;
	}

     /**
	 * Return an array of data summarising the results of a search.
	 *
	 * @access  public
	 * @return  array   summary of results
	 */
	public function getResultSummary() {
		$summary = [];

		$summary['page'] = $this->page;
		$summary['perPage'] = $this->limit;
		$summary['resultTotal'] = (int)$this->resultsTotal;
		// $summary['facetFields'] = $this->facetFields;
		// 1st record is easy, work out the start of this page
		$summary['startRecord'] = (($this->page - 1) * $this->limit) + 1;
		// Last record needs more care
		if ($this->resultsTotal < $this->limit) {
			// There are less records returned than one page, then use total results
			$summary['endRecord'] = $this->resultsTotal;
		} elseif (($this->page * $this->limit) > $this->resultsTotal) {
			// The end of the current page runs past the last record, use total results
			$summary['endRecord'] = $this->resultsTotal;
		} else {
			// Otherwise use the last record on this page
			$summary['endRecord'] = $this->page * $this->limit;
		}
		return $summary;
	}

    	/**
	 * Return a url for use by pagination template
	 *
	 * @access  public
	 * @return  string   URL of a new search
	 */
	public function renderLinkPageTemplate() {
		// Stash our old data for a minute
		$oldPage = $this->page;
		// Add the page template
		$this->page = '%d';
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->page = $oldPage;
		// Return the URL
		return $url;
	}

    	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML() {
		global $interface;
		$html = [];
		if (isset($this->lastSearchResults)) {
			// foreach($this->lastSearchResults as $key=>$value){
			for ($x = 0; $x < count($this->lastSearchResults); $x++) {
				$current = &$this->lastSearchResults[$x];
				// $interface->assign('recordIndex', $key + 1);
				// $interface->assign('resultIndex', $key + 1 + (($this->page - 1) * $this->limit));
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->pageSize));
				require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
				// $record = new SummonRecordDriver($value);
				$record = new SummonRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		// } else {
		// 	$html[] = "Unable to find record";

		// }
		} $this->addToHistory();
		return $html;
	}

      	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getCombinedResultHTML() {
        global $interface;
		$html = [];
		if (isset($this->lastSearchResults)) {
			foreach($this->lastSearchResults as $key=>$value){
				$interface->assign('recordIndex', $key + 1);
				$interface->assign('resultIndex', $key + 1 + (($this->page - 1) * $this->pageSize));

				require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
				$record = new SummonRecordDriver($value);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getCombinedResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		} else {
			$html[] = "Unable to find record";

		}
		return $html;
    }

    public function getSortList() {
		//Get available sort options
		$sortOptions = $this->getSortOptions();
		//Initialize empty list 
		$list = [];
		//Ensure that there are sort options available
		if ($sortOptions != null) {
			//For each sort option, add relevant info and add to array
			foreach ($sortOptions as $sort => $label) {
				$list[$sort] = [
					'sortUrl' => $this->renderLinkWithSort($sort),
					'desc' => $label,
					'selected' => ($sort == $this->sort),
				];

			 }
		}

		return $list;
	}

    public function getSortOptions() {
		$this->sortOptions =[
			'relevance' =>[
				'id' => 'relevance',
				'label' => 'Relevance',
			],
			'date(newest)' =>[
				'id' => 'date(newest)',
				'label' => 'Date(newest)',
			],
			'date(oldest)' =>[
				'id' => 'date(oldest)',
				'label' => 'Date(oldest)',
			],
			'author' =>[
				'id' => 'author',
				'label' => 'Author'
			],
			'title' =>[
				'id' => 'title',
				'label' => 'Title',
			],		
		];
		if ($this->sortOptions != null) {
			foreach ($this->sortOptions as $sortOption) {
				$this->sort = $sortOption['id'];
				$desc = $sortOption['label'];
				$this->sortOptions[$this->sort] =$desc;
			}
		}
		return $this->sortOptions;
	}

    	/**
	 * Return a url for the current search with a new sort
	 *
	 * @access  public
	 * @param string $newSort A field to sort by
	 * @return  string   URL of a new search
	 */
	public function renderLinkWithSort($newSort) {
		// Stash our old data for a minute
		$oldSort = $this->sort;
		// Add the new sort
		$this->sort = $newSort;
		// Get the new url
		$url = $this->renderSearchUrl();
		// Restore the old data
		$this->sort = $oldSort;
		// Return the URL
		return $url;
	}

    public function getFacetSet() {
		$availableFacets = [];
		$availableFacetValues = [];
		
		
		//Check for search
		if (isset($this->facetValueFilters)){
			foreach($this->facetValueFilters as $facetValueFilter) {
				$facetLabel = $facetValueFilter;
				$availableFacets[$facetValueFilter] = [
					'collapseByDefault' => true,
					'multiSelect' => true,
					//Filter heading label
					'label' => (string)$facetLabel,
					'valuesToShow' => 5,
				];
				if ($this->facetValueFilters == 'SourceType') {
					$availableFacets[$facetValueFilter]['collapseByDefault'] = false;
				}
				$list = [];
				switch($facetValueFilter) {
					case 'ContentType':
						$availableFacetValues = array(
							'Archival Material',
							'Article',
							'Audio Recording',
							'Book / eBook', 
							'Book Chapter',
							'Book Review',
							'Computer File',
							'Conference Proceeding',
							'Data Set',
							'Dissertation / Thesis',
							'Government Document',
							'Journal / eJournal',
							'Journal Article',
							'Library Holding',
							'Magazine Article',
							'Manuscript',
							'Map',
							'Market Research',
							'Microfilm',
							'Newsletter',
							'Newspaper',
							'Newspaper Article',
							'Paper',
							'Presentation',
							'Publication',
							'Reference',
							'Report',
							'Technical Report',
							'Trade Publication Article',
							'Transcript',
							'Web Resource',
						);
						$facetLabel = 'Content Type';
						break;
					case 'IsScholarly':
						$availableFacetValues = array(
							'true',
							'false',
						);
						break;
					case 'Discipline':
						$availableFacetValues = array(
							'Agriculture',
							'Anatomy & Physiology',
							'Anthropology',
							'Applied Sciences',
							'Architecture',
							'Astronomy & Astrophysics',
							'Biology',
							'Botany',
							'Business',
							'Chemistry',
							'Computer Science',
							'Dance',
							'Dentistry',
							'Diet & Clinical Nutrition',
							'Drama',
							'Ecology',
							'Economics',
							'Education',
							'Engineering',
							'Environmental Sciences',
							'Film',
							'Forestry',
							'Geography',
							'Geology',
							'Government',
							'History & Archaeology',
							'Human Anatomy & Physiology',
							'International Relations',
							'Journalism & Communications',
							'Languages & Literatures',
							'Law',
							'Library & Information Science',
							'Mathematics',
							'Medicine',
							'Meteorology & Climatology',
							'Military & Naval Science',
							'Music',
							'Nursing',
							'Occupational Therapy & Rehabilitation',
							'Oceanography',
							'Parapsychology & Occult Sciences',
							'Pharmacy, Therapeutics, & Pharmacology',
							'Philosophy',
							'Physical Therapy',
							'Physics',
							'Political Science',
							'Psychology',
							'Public Health',
							'Recreation & Sports',
							'Religion',
							'Sciences (general)',
							'Social Sciences (general)',
							'Social Welfare & Social Work',
							'Sociology & Social History',
							'Statistics',
							'Veterinary Medicine',
							'Visual Arts',
							'Women\'s Studies',
							'Zoology',
						);
						break;
					case 'DatabaseName':
						$availableFacetValues = array(
							'American Economic Association',
							'AUC Wiley Frozen Package in 2012',
							'BMJ Journals',
							'Cambridge Journals 2016 Full Collection',
							'Cambridge University Press Journals',
							'DOAJ Directory of Oprn Access Journals',
							'Emerald Complete Journals',
							'EZB Electronic Journals Library',
							'Free E - Journals',
							'Free Medical Journals',
							'Freely Accessible Arts & Humanities Journals',
							'Freely Accessible Social Science Journals - check A-Z of ejournals',
							'HighWire Press (Subscribed Journals)',
							'Individual e-journals',
							'Ingenta',
							'ITC Publications',
							'Lexis Library',
							'LexisLibrary - UK Journals',
							'Nature_OA',
							'New England Journal of Medicine Current',
							'NUS Single-Journal Subscriptions',
							'Oxford Journals - Coonect here FIRST to enable access',
							'Oxford Journals 2021 Law',
							'Sage',
							'SAGE Journals',
							'SAGE:BIBSAM:Premier:2014-2016',
							'Science Journals (Open access)',
							'Springer Nature - Connect here FIRST to enable access',
							'Taylor & Francis',
							'Taylor & Francis Journals Complete',
							'The Lancet',
							'University of California Press journals (for access to the journal Film Quarterly',
							'vLex Global - General Edition',
							'Westlaw',
							'Wiley Online Library Journals'
						);
						break;
					case 'SubjectTerms':
						$availableFacetValues = array(
							'adult',
							'aged',
							'airlines',
							'analysis',
							'animals',
							'arts & humanities',
							'banking industry',
							'banks',
							'biological and medical sciences',
							'biology',
							'business',
							'business & economics',
							'children',
							'china',
							'clinical medicine',
							'coronaviruses',
							'covid-19',
							'criminology',
							'criminology & penology',
							'democracy',
							'economic aspects',
							'economic conditions',
							'economic development',
							'economic growth',
							'economic history',
							'economic policy',
							'economics',
							'education',
							'elections',
							'employment',
							'europe',
							'european union',
							'evaluation',
							'female',
							'finance',
							'forecasts and trends',
							'foreign policy',
							'general & internal medicine',
							'government',
							'government & law',
							'health aspects',
							'history',
							'human rights',
							'humans',
							'internal medicine',
							'international',
							'international law',
							'international relations',
							'international trade',
							'journalists',
							'law',
							'laws, regulations and rules',
							'legislation',
							'life sciences & biomedicine',
							'male',
							'management',
							'medical and health sciences',
							'medical sciences',
							'medicine',
							'medicine, general & internal',
							'middle aged',
							'multidisciplinary sciences',
							'news',
							'nonfiction',
							'patients',
							'physicians',
							'planning',
							'political activity',
							'political aspects',
							'political economy',
							'political parties',
							'political science',
							'political science & public administration',
							'politics',
							'population',
							'presidents',
							'prices and rates',
							'prime ministers',
							'psychology',
							'public administration',
							'public health',
							'public relations',
							'science & technology ',
							'science & technology - other topics',
							'social aspects',
							'social sciences',
							'social sciences - other topics',
							'social sciences, interdisciplinary',
							'sociology',
							'state',
							'statistics',
							'studies',
							'surgery',
							'taxation',
							'terrorism',
							'u.s.a',
							'united kingdom',
							'united states',
							'war',
							'women'
						);
						$facetLabel = 'Subject Terms';
						break;
					default: 
						$availableFacetValues = array(
							''
						);
						$facetLabel = $facetValueFilter;
						break;
				}
				foreach ($availableFacetValues as $value) {
					$facetValue = $value;
					$isApplied = array_key_exists($facetValueFilter, $this->filterList) && in_array($facetValue, $this->filterList[$facetValueFilter]);
				

						$facetSettings = [
							'value' => $facetValue,
							//Displays the different values available for each facet
							'display' => $facetValue,
							// 'count' => $facetId->Count,
							// 'isApplied' => $isApplied,
							'countIsApproximate' => false,
						];
						 if ($isApplied) {
							$facetSettings['removalUrl'] = $this->renderLinkWithoutFilter($facetValueFilter . ':' . $facetValue);
					 	} else {
							$facetSettings['url'] = $this->renderSearchUrl() . '&filter=' . $facetValueFilter . ':' . urlencode($facetValue);
					 	}
					 	$list[] = $facetSettings;
					 }
					$availableFacets[$facetValueFilter]['list'] = $list;
				}
			}
		// var_dump($this->filterList);
		return 	$availableFacets;
	}	

      /**
     * Generate an HMAC hash
     *
     * @param string $key  Hash key
     * @param string $data Data to hash
     *
     * @return string      Generated hash
     */
    protected function hmacsha1($key, $data)
    {
        $blocksize=64;
        $hashfunc='sha1';
        if (strlen($key)>$blocksize) {
            $key=pack('H*', $hashfunc($key));
        }
        $key=str_pad($key, $blocksize, chr(0x00));
        $ipad=str_repeat(chr(0x36), $blocksize);
        $opad=str_repeat(chr(0x5c), $blocksize);
        $hmac = pack(
            'H*', $hashfunc(
                ($key^$opad).pack(
                    'H*', $hashfunc(
                        ($key^$ipad).$data
                    )
                )
            )
        );
        return base64_encode($hmac);
    }

	/**
	 * @param array $params params for request
	 * @param string $service for API to call
	 * @param string $method HTTP method
	 * @param bool $raw raw or processed response
	 * 
	 * @throws Exception
	 * @return object API response
	 */

     public function sendRequest() {

        $baseUrl = $this->summonBaseApi . '/' .$this->version . '/' .$this->service;

        $settings = $this->getSettings();
        $this->startQueryTimer();

        if ($settings != null) {
            $query = array();
            $queryOptions = array();
            foreach ($this->searchTerms as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $term) {
                        $term = urlencode($term);
                        $query[] = "$key=$term";
                    }
                } elseif (!is_null($value)) {
                    $value = urlencode($value);
                    $query[] = "$key=$value";
                }
            }
			$queryOptions = $query;
			$options = array(
				's.q' => $query,
				//set to default at top of page
				's.ps' => $this->pageSize,
				//set to default at top of page
				's.pn' => $this->pageNumber,
				//set to default -  false at top of page
				's.ho' => $this->holdings ? 'true' : 'false',
				//set to default -  false at top of page
				's.dym' => $this->didYouMean ? 'true' : 'false',
				//set to default - en at top of page
				's.l' => $this->language,

				's.fids' => $this->idsToFetch,

				's.ff' =>$this->facets,

				's.fvf' => $this->filters,
				//set to default 1 at top of page
				's.rec.topic.max' => $this->maxTopics,

				's.fvgf' => $this->groupFilters,

				's.rf' => $this->rangeFilters,

				's.sort' => $this->sort,

				's.exp' => $this->expand,

				's.oaf' => $this->openAccessFilter,
				
			);
			foreach ($options as $key => $value) {
				$buildQuery = [];
				if (is_array($value)) {
					foreach($value as $additionalValue) {
						$additionalValue = urlencode($additionalValue);
						$buildQuery[] = $additionalValue;
					}
				} elseif (!is_null($value)) {
					$value = urlencode($value);
					$buildQuery[] = $value;
				}
			}

			$buildQuery = implode('&', $buildQuery);
			$queryOptions .= $buildQuery;

			

            //$queryString = 's.q='.$query[0].':('.implode('&', array_slice($query,1)).')' . $this->queryOptions ;
            $queryString = implode('& ', $queryOptions);
            // Build Authorization Headers
            $headers = array(
                'Accept' => 'application/'.$this->responseType,
                'x-summon-date' => gmdate('D, d M Y H:i:s T'),
                'Host' => 'api.summon.serialssolutions.com'
            );
            // $headers = $this->authenticate($headers,$settings, "&q=".urlencode($searchTerms));
            $data = implode("\n", $headers). "\n/$this->version/search\n" . urldecode($queryString) . "\n";
            $hmacHash = $this->hmacsha1($settings->summonApiPassword, $data);
            $headers['Authorization'] = "Summon $settings->summonApiId;$hmacHash";
            if (!is_null($this->sessionId)){
                $headers['x-summon-session-id'] = $this->sessionId;
            } 

            $recordData = $this->httpRequest($baseUrl, $queryString, $options, $headers);
            if (!empty($recordData)){
                $recordData = $this->process($recordData); 
                $this->stopQueryTimer();

                if (is_array($recordData)){

                    $this->sessionId = $recordData['sessionId'];
                    $this->lastSearchResults = $recordData['documents'];
                    $this->page = $recordData['query']['pageNumber'];
                    // $this->didYouMean = $recordData['didYouMeanSuggestions'];
                    $this->resultsTotal = $recordData['recordCount'];
                    $this->sort = $recordData['query']['sort'];
                    $this->facetFields= $recordData['facetFields'];
                    $this->queryFacets = $recordData['query']['rangeFacetFields'];
                    // $this->facetVals = $recordData['facetValueFilters'];
                    // $this->pageSize = $recordData['query']['pageSize'];
                }
            }
            var_dump($queryString);
            return $recordData;
        } else {
            return $this->lastSearchResults = false;
        }

     }

     public function process($input) {
        if (SearchObject_SummonSearcher::$searchOptions == null) {
            if ($this->responseType != 'json') {
                return $input;
            }

            SearchObject_SummonSearcher::$searchOptions = json_decode($input, true);

           

            if (!SearchObject_SummonSearcher::$searchOptions) {
                SearchObject_SummonSearcher::$searchOptions = array(
                    'recordCount' => 0,
                    'documents' => array(),
                    'errors' => array(
                        array(
                            'code' => 'PHP-Internal',
                            'message' => 'Cannot decode JSON response: ' . $input
                        )
                    )
                );
            }
               // Detect errors
            if (isset(SearchObject_SummonSearcher::$searchOptions['errors']) && is_array(SearchObject_SummonSearcher::$searchOptions['errors'])) {
                foreach (SearchObject_SummonSearcher::$searchOptions['errors'] as $current) {
                    $errors[] = "{$current['code']}: {$current['message']}";
                }
                $msg = 'Unable to process query<br />Summon returned: ' .
                    implode('<br />', $errors);
                throw new Exception($msg);
            }
            if (SearchObject_SummonSearcher::$searchOptions) {
                return SearchObject_SummonSearcher::$searchOptions;
            } else {
                return null;
            }
        } else {
            return SearchObject_SummonSearcher::$searchOptions;
        }
     }

        protected function httpRequest($baseUrl, $queryString, $options, $headers ) {
            foreach ($headers as $key =>$value) {
                $modified_headers[] = $key.": ".$value;
            }
        
    
            $curlConnection = $this->getCurlConnection();
            $curlOptions = array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => "{$baseUrl}?{$queryString}",
                CURLOPT_HTTPHEADER => $modified_headers
            );
            curl_setopt_array($curlConnection, $curlOptions);
            $result = curl_exec($curlConnection);
            if ($result === false) {
                throw new Exception("Error in HTTP Request.");
            }
            // curl_close($curlConnection);
    
            return $result;
        }
    
        public function __destruct() {
            if ($this->curl_connection) {
                curl_close($this->curl_connection);
            }
        }
    
        public function getQuerySpeed() {
            return $this->queryTime;
        }
    
    
            /**
         * Start the timer to figure out how long a query takes.  Complements
         * stopQueryTimer().
         *
         * @access protected
         */
        protected function startQueryTimer() {
            // Get time before the query
            $time = explode(" ", microtime());
            $this->queryStartTime = $time[1] + $time[0];
        }
    
        /**
         * End the timer to figure out how long a query takes.  Complements
         * startQueryTimer().
         *
         * @access protected
         */
        protected function stopQueryTimer() {
            $time = explode(" ", microtime());
            $this->queryEndTime = $time[1] + $time[0];
            $this->queryTime = $this->queryEndTime - $this->queryStartTime;
        }

        public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false) {
        }

        public function getSearchIndexes() {
            return [
                "Everything" => translate([
                    'text' => "Everything",
                    'isPublicFacing' => true,
                    'inAttribute' => true,
                ]),
                'Books' => translate([
                    'text' => "Books",
                    'isPublicFacing' => true,
                    'inAttribute' => true,
                ]),
                'Articles' => translate([
                    'text' => "Articles",
                    'isPublicFacing' => true,
                    'inAttribute' => true,
                ])
            ];
         }

         public function getDefaultIndex() {
            return $this->searchIndex;
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

          	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string[] $ids An array of documents to retrieve from Solr
	 * @access  public
	 * @return  array              The requested resources
	 */
	public function getRecords($ids) {
		$records = [];
		require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
		foreach ($ids as $index => $id) {
			$records[$index] = new SummonRecordDriver($id);
		}
		return $records;
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

	function getSearchName() {
		return $this->searchSource;
	}

	function loadValidFields() {
		// TODO: Implement loadValidFields() method.
	}

	function loadDynamicFields() {
		// TODO: Implement loadDynamicFields() method.
	}

    public function getEngineName() {
		return 'summon';
	}

	function getSearchesFile() {
		return false;
	}

    
 }










}