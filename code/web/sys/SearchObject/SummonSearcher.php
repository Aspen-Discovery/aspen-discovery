<?php

require_once ROOT_DIR . '/sys/SearchObject/BuildQuery.php';
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
	protected $page = 1;
	// Result limit
	protected $limit = 20;

	// Sorting
	protected $sort = null;
	protected $defaultSort = 'relevance';

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

	protected $facetFields;

  





    public function __construct() {

        //Initialize properties with default values
        $this->searchSource = 'summon';
        $this->searchType = 'summon';
        $this->resultsModule = 'Summon';
        $this->resultsAction = 'Results';
	}
        // $legalOptions = array('authedUser', 'summonBaseApi', 'sessionId', 'version', 'responseType');
        // foreach($legalOptions as $option) {
        //     if(isset($options[$option])){
        //     $this->$option = $options[$option];
        //     }
        // }


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

    public function authenticate($headers, $settings, $queryString) {

        $data = implode("\n", $headers) . "\n/$this->version/auth\n".
    	urldecode($queryString) . "\n";
        $hmacHash = $this->hmacsha1($settings->summonApiPassword, $data);
        $headers['Authorization'] = "Summon $settings->summonApiId;$hmacHash";

        return $headers;
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
		$summary['facetFields'] = $this->
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
		if (isset($this->lastSearchResults->recordData->documents)) {
			for($i=0; $i < count($this->lastSearchResults->recordData->documents); $i++) {
				$current = $this->lastSearchResults->recordData->documents[$i];
				$interface->assign('recordIndex', $i +1);
				$interface->assign('resultIndex', $i + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
				$record = new SummonRecordDriver($current);
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
		//global $logger;
		//$logger->log(print_r($this->lastSearchResults, true), Logger::LOG_WARNING);
		if (isset($this->lastSearchResults)) {
			for ($x = 0; $x < count($this->lastSearchResults); $x++) {
				$current = &$this->lastSearchResults[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
				$record = new SummonRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getCombinedResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}

		return $html;
	}


	//Facets set for Summon - callled in Summon's Results
    public function getFacetSet() {
		// $availableFacets = [
        //     'IsScholarly,or,1,2',
        //     'Library,or,1,30',
        //     'ContentType,or,1,30',
        //      'SubjectTerms,or,1,30',
        //      'Language,or,1,30'
        // ];
		if (isset($this->lastSearchResults) && isset($this->facetFields)) {
			foreach ($this->facetFields as $facet) {
				$facet = [
					'collapseByDefault' => true,
					'multiSelect' => true,
					'label' => (string)$facet->Label,
					'valuesToShow' => 5,
				];
				// if ($facetId == 'SourceType') {
				// 	$facetId['collapseByDefault'] = false;
				// }
				// $list = [];
				// foreach ($facet as $value) {
				// 	$facetValue = (string)$value->Value;
					//Check to see if the facet has been applied
					// $isApplied = array_key_exists($facetId, $this->filterList) && in_array($facetValue, $this->filterList[$facetId]);

				// 	$facetSettings = [
				// 		'value' => $facetValue,
				// 		'display' => $facetValue,
				// 		'count' => (string)$value->Count,
				// 		'isApplied' => $isApplied,
				// 		'countIsApproximate' => false,
				// 	];
				// 	if ($isApplied) {
				// 		$facetSettings['removalUrl'] = $this->renderLinkWithoutFilter($facetId . ':' . $facetValue);
				// 	} else {
				// 		$facetSettings['url'] = $this->renderSearchUrl() . '&filter[]=' . $facetId . ':' . urlencode($facetValue);
				// 	}
				// 	$list[] = $facetSettings;
				// }
				// $availableFacets[$facetId]['list'] = $list;
			}
		}
		//return $availableFacets;
		// var_dump($facet);
		return $facet;
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
     * Set array of options relevant to summon as Summon Search Options
     *
     * @return array
     */
    public function getSearchOptionsArray()
    {
        $options = array(
            's.q' => $this->query,
            's.ps' => $this->limit,
            's.pn' => $this->page,
            's.ho' => $this->holdings ? 'true' : 'false',
            's.dym' => $this->didYouMean ? 'true' : 'false',
            's.l' => $this->language,
        );
        if (!empty($this->idsToFetch)) {
            $options['s.fids'] = implode(',', (array)$this->idsToFetch);
        }
        if (!empty($this->facets)) {
            $options['s.ff'] = $this->facets;
        }
        if (!empty($this->filters)) {
            $options['s.fvf'] = $this->filters;
        }
        if ($this->maxTopics !== false) {
            $options['s.rec.topic.max'] = $this->maxTopics;
        }
        if (!empty($this->groupFilters)) {
            $options['s.fvgf'] = $this->groupFilters;
        }
        if (!empty($this->rangeFilters)) {
            $options['s.rf'] = $this->rangeFilters;
        }
        if (!empty($this->sort)) {
            $options['s.sort'] = $this->sort;
        }
        if ($this->expand) {
            $options['s.exp'] = 'true';
        }
        if ($this->openAccessFilter) {
            $options['s.oaf'] = 'true';
        }
        if ($this->highlight) {
            $options['s.hl'] = 'true';
            $options['s.hs'] = $this->highlightStart;
            $options['s.he'] = $this->highlightEnd;
        } else {
            $options['s.hl'] = 'false';
            $options['s.hs'] = $options['s.he'] = '';
        }
		// return optionsToString($options);
		return $options;
    }

	public function optionsToString($options) {
		$buildQuery = '';
		foreach ($options as $key => $value) {
			switch ($key) {
				case 's.ps':
					$buildQuery .= '&s.ps' . $value;
					break;
			}
		}
	}


	//Set the search options to the Summon searcher
	public function getSearchOptions() {
		if (SearchObject_SummonSearcher::$searchOptions == null) {
			SearchObject_SummonSearcher::$searchOptions = $this->getSearchOptionsArray();
			return SearchObject_SummonSearcher::$searchOptions;
		}
	}

    

    	public function sendRequest() {
            $baseUrl = $this->summonBaseApi . '/' .$this->version . '/' .$this->service;
            global $library;
            $settings = $this->getSettings();
			$this->startQueryTimer();
            if ($settings != null) {
		
                // $query .= '&searchmode=all';
				$query = array();
				foreach ($this->searchTerms as $function => $value) {
					if (is_array($value)) {
						foreach ($value as $term) {
							$term = urlencode($term);
							$query[] =$term;
						}
					} elseif (!is_null($value)) {
						$value = urlencode($value);
						$query[] =$value;
					}
				}
				// foreach ($this->searchTerms['lookfor'] as $term) {
				// 	$term = urlencode($term);
				// }
				$queryString = 's.q='.$query[0].':('.implode('&', array_slice($query,1)).')' ;

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
                // Send request
				
                $recordData = $this->httpRequest($baseUrl, $queryString, $headers);
				if (!empty($recordData)){
					$recordData = $this->process($recordData); 
					$this->stopQueryTimer();

					if (is_array($recordData)){

						$this->sessionId = $recordData['sessionId'];
						$this->lastSearchResults = $recordData['documents'];
						$this->page = $recordData['query']['pageNumber'];
						$this->didYouMean = $recordData['didYouMeanSuggestions'];
						$this->resultsTotal = $recordData['recordCount'];
						$this->facetFields = $recordData['rangeFacetFields'];
				
						



					}
				}
                return $recordData;
            } else {
				return $this->lastSearchResults = false;
				// return new Exception('Please add your Summon settings');
			}
    	}

		//Get last search
		public function getLastSearchResults() {
			return $this->lastSearchResults;
		}

	

	//Process individual record and return it
	public function processRecord(array $record) {
		return $record;
	}

		public function getSessionId() {
			return $this->sessionId;
		}

		public function getresultsTotal(){
			return $this->resultsTotal;
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
    
          

    

    protected function httpRequest($baseUrl, $queryString, $headers ) {
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
	// 	$this->startQueryTimer();
	// 	if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] != 1) {
	// 		$this->page = $_REQUEST['page'];
	// 		$searchUrl .= '&pagenumber=' . $this->page;
	// 	} else {
	// 		$this->page = 1;
	// 		$searchUrl .= '&relatedcontent=rs';
	// 	}

	// 	$searchUrl .= '&sort=' . $this->sort;

	// 	$searchUrl .= "&highlight=n&view=detailed&autosuggest=n&autocorrect=n";

	// 	$facetIndex = 1;
	// 	foreach ($this->filters as $field => $filter ) {
	// 		$appliedFilters = '';
	// 		if (is_array($filter))
	// 	}


       
    }

     public function retreiveRecord() {
       //call send request to access records array
	   $recordsArray = $this->sendRequest();

	   //check the documents key exists in the recorddata
	   if(!empty($recordArray['recordData']['documents'])) {
		//Return each individual document
		return $recordsArray['recordData']['documents'];
	   } 
	   return;
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

   

	 //Used in Union/Ajax - getSummonResults
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