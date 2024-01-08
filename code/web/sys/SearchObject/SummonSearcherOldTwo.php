<?php

require_once ROOT_DIR . '/sys/Pager.php';
require_once ROOT_DIR . '/sys/Summon/SummonSettings.php';
require_once ROOT_DIR . '/sys/SearchObject/BaseSearcher.php';

class SearchObject_SummonSearcher extends SearchObject_BaseSearcher {
	static $instance;

    /** @var SummonSettings */
	private $summonSettings;
	private $summonBaseApi = 'http://api.summon.serialssolutions.com';
	private $curl_connection;
	private static $sessionId;
	private $summonApiPassword;
	private $summonApiId;
	private static $searchOptions;
	protected $responseType = "json";
	protected $returnErr = false;

	/**
	 * Preferred search language
	 * @var string
	 */
	protected $language = 'en';

	/**
	 * Limit to library's own holdings
	 * 
	 * @var bool
	 */
	protected $holdings = true;

	// private $httpRequest;

	const IDENTIFIER_ID = 1;
	const IDENTIFIER_BOOKMARK = 2;
	// $results = [];

     /**
     * The API version to use
     *
     * @var string
     */
    protected $version = '2.0.0';

	 /**
     * 
     * @var array
     */
    protected $idsToFetch = array();

	/**
	 * Facets to request
	 * @var array
	 */
	protected $facets = null;

	/**
	 * Filters to apply
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Group filters
	 * @var array
	 */
	protected $groupFilters = array();

	/**
	 * Range filters
	 * @var array
	 */
	protected $rangeFilters = array();

	/**
	 * Enable spell checking
	 * @var bool
	 */
	protected $didYouMean = false;

	/**
	 * @var bool
	 */
	protected $openAccessFilter = false;

	/**
	 * Query exapnsion
	 * @var bool
	 */
	protected $expand = false;

	/**
	 * Maximum number of topics to explore
	 * @var int
	 */
	protected $maxTopics = 1;

	/**
	 * Highlight
	 * @var bool
	 */
	protected $highlight = false;

	/**
	 * Highlight start
	 * @var string
	 */
	protected $highlightStart = '';

	/**
	 * Highlight end
	 * @var string
	 */
	protected $highlightEnd = '';

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
	protected $resultsTotal = 0;

	protected $searchTerms;

	protected $lastSearchResults;
	/**
	 * @var string mixed
	 */
	private $searchIndex = 'TX';

    protected $authedUser;

	private $defaultFacets = array(
		'IsScholarly,or,1,2',
		'Library,or,1,30',
		'ContentType,or,1,30',
		'SubjectTerms,or,1,30',
		'Language,or,1,30'
	);

	protected $query = array();

	private $options = array();

    public function __construct() {
		parent::__construct();
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

	public function authenticate() {
		$baseUrl = $this->summonBaseApi . '/auth';
		global $library;
		$settings = $this->getSettings();
		if ($settings !=null && $settings->summonApiProfile) {

			//Build auth headers
			$headers = array(
				'Accept' => 'application/'.$this->responseType,
				'x-summon-date' => gmdate('D, d M Y H:i:s T'),
				'Host' => 'api.summon.serialssolutions.com'
			);
			$data = implode("\n", $headers) . "\n/auth\n";
			$hmacHash = $this->hmacsha1($this->summonApiPassword, $data);
			$headers['Authorization'] = "Summon $this->summonApiId;$hmacHash";
		
			//Send auth request
			$authResult = $this->httpRequest($baseUrl, 'GET', '', $headers);
			$authResult = $this->process($authResult);

			if (is_array($authResult) && isset($authResult['sessionToken'])) {
				$this->sessionId = (string) $authResult['sessionToken'];
				return $this->sessionId;
			} else {
				throw new Exception('Authentication Failed!');
			}
		}	
	}
			

	protected function httpRequest($baseUrl, $method, $queryString, $headers)
    {
    

        // Modify headers as summon needs it in "key: value" format
        $modified_headers = array();
        foreach ($headers as $key=>$value) {
            $modified_headers[] = $key.": ".$value;
        }

        $curl = curl_init();
        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => "{$baseUrl}?{$queryString}",
            CURLOPT_HTTPHEADER => $modified_headers
        );
        curl_setopt_array($curl, $curlOptions);
        $result = curl_exec($curl);
        if ($result === false) {
            throw new Exception("Error in HTTP Request.");
        }
        curl_close($curl);

        return $result;
    }


        /**
     * Generate an HMAC hash
     *
     * @param string $key  Hash key
     * @param string $data Data to hash
     *
     * @return string      Generated hash
     */
    public function hmacsha1($key, $data)
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

	public function endSession() {
		if ($this->curl_connection) {
			curl_setopt($this->curl_connection, CURLOPT_URL, $this->summonBaseApi . '/endsession?sessiontoken=' . SearchObject_SummonSearcher::$sessionId);
			curl_exec($this->curl_connection);
		}
	}

	public function __destruct() {
		$this->endSession();
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
		$this->queryStartTime = $time[1] . $time[0];
	}

	/**
	 * End the timer to figure out how long a query takes.  Complements
	 * startQueryTimer().
	 *
	 * @access protected
	 */
	protected function stopQueryTimer() {
		$time = explode(" ", microtime());
		$this->queryEndTime = $time[1] . $time[0];
		$this->queryTime = $this->queryEndTime - $this->queryStartTime;
	}

	/**
	 * Get search options from Summon
	 * 
	 * @return Search options or null
	 */
	public function getSearchOptions() {
		//Check for cached search options
		if (SearchObject_SummonSearcher::$searchOptions == null) {
			//Ensure authentication
			if (SearchObject_SummonSearcher::$sessionId) {
				$this->authenticate();

					//cURL connection (initialize)
					$curlConnection = $this->getCurlConnection();
					curl_setopt($curlConnection, CURLOPT_HTTPGET, true);
					curl_setopt($curlConnection, CURLOPT_HTTPHEADER, [
						'Content-Type: application/json',
						'Accept: application/json',
						'x-summon-date: ' . gmdate('D, d M Y H:i:s T'),
						'x-summon-session-id: ' . $this->sessionId,
						'Host: api.summon.serialssolutions.com',
					]);

					//API search options endpoint
					$infoUrl = $this->summonBaseApi . '/info';
					curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);

					//Execute cURL request
					$searchOptionsStr = curl_exec($curlConnection);
					//Decode response
					$searchOptions = json_decode($searchOptionsStr);

					//Determine if decode was successful
					if ($searchOptions) {
						return $searchOptions;
					} else {
						return null;
					}
			} else {
				return null;
			}	
		} else {
			return SearchObject_SummonSearcher::$searchOptions;
		}
	}

	/**
	 * Get search indexes from Summon
	 * 
	 * @return array of search indexes or an empty array
	 */
	public function getSearchIndexes() {
		global $memCache;

		//Authenticate
		// $this->authenticate();
		//Get search options
		if ($this->getSettings() == null) {
			return [];
		} else {
			//If there are cachecd indexes, retreive them
			$searchIndexes = $memCache->get('summon_search_indexes_' . $this->getSettings()->summonApiProfile);
			if ($searchIndexes === false) {
				$searchOptions = $this->getSearchOptions();

				$searchIndexes = [];
				//Search indexes from search options
				if ($searchOptions != null) {
					foreach ($searchOptions->AvailableSearchCriteria->AvailableSearchFields as $searchField) {
						$searchIndexes[$searchField->FieldCode] = translate([
							'text' => $searchField->Label,
							'isPublicFacing' => true,
							'inAttribute' => true,
						]);
					}
				}
				global $configArray;
				//Cache search indexes
				$memCache->set('summon_search_indexes_' . $this->getSettings()->summonApiProfile, $searchIndexes, $configArray['Caching']['summon_options']);	
			}
			return $searchIndexes;
		}
	}

	
	/**
	 * Get list of available sort options
	 * 
	 * @return array - List of search options
	 */

	public function getSortList() {
		$sortOptions = $this->getSortOptions();
		$list = [];
		if ($sortOptions != null) {
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
		global $memCache;
		$settings = $this->getSettings();
		if ($settings === null) {
			return [];
		}
		$sortOptions = $memCache->get('summon_sort_options_' . $this->getSettings()->summonApiProfile);
		if ($sortOptions === false) {
			$searchOptions = $this->getSearchOptions();
			$sortOptions = [];
			//Retreive sort options from search options
			if ($searchOptions != null) {
				foreach ($searchOptions->AvailableSearchCriteria->AvailableSorts as $sortOption) {
					$sort = $sortOption->Id;
					$desc = $sortOption->Label;
					$sortOptions[$sort] = $desc;
				}
			}
			global $configArray;
			//Cache sort options
			$memCache->set('summon_sort_options_' . $this->getSettings()->summonApiProfile, $sortOptions, $configArray['Caching']['summon_options']);
		}
		return $sortOptions;
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

	public function retrieveRecord($id, $raw = false, $idType = self::IDENTIFIER_ID, $retryOnAuthenticationFailure = true) {
		if (!$this->authenticate()) {
			return null;
		} else {
			$options = $idType === self::IDENTIFIER_BOOKMARK 	
				? array('s.bookMark' => $id)
				: array('s.q' => sprintf('ID:"%s"', $id));
			$options['s.role'] = $this->authedUser? 'authenticated' : 'none';
			return $this->authenticate($options, 'search', 'GET', $raw);
		}
	}

	public function createQuery() {

		$queryArray = array('query' => $this->query);
		foreach ($this->options as $key => $value) {
			$queryArray[$key] =  $value;
		}
		//Define default facets
		if (!isset($queryArray['facets'])) {
			$queryArray['facets'] = array(
				'IsScholarly,or,1,2',
				'Library,or,1,30',
				'ContentType,or,1,30',
				'SubjectTerms,or,1,30',
				'Language,or,1,30'
			);
		}

		return $queryArray;
	}



	public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false){
		$this->startQueryTimer();
		$baseUrl = $this->summonBaseApi . '/' . $this->version . '/search?';

		


		$searchTerms = array('query' => $this->query);
		foreach ($options as $key => $value) {
			$searchTerms[$key] = $value;
		}
		if (!isset($searchTerms['facets'])) {
			$searchTerms['facets'] = $this->defaultFacets;
		}

		foreach ($this->params as $function => $searchTerms) {
            if (is_array($this->searchTerms)) {
				$termIndex = 1;
                foreach ($this->searchTerms as $term) {
					if (!empty($term)) {
						if ($termIndex > 1) {
							$baseUrl .= '&';
						}
						$term = str_replace(',', '', $term);
						$searchIndex = $term['index'];
						$baseUrl .= "query-{$termIndex}=AND," . urlencode($searchIndex . ":" . $term['lookfor']);
						$termIndex++;
						$hasSearchTerm = true;
				   }
          	  } 
			} else {
				if (isset($_REQUEST['searchIndex'])) {
					$this->searchIndex = $_REQUEST['searchIndex'];
				}
				$searchTerms = str_replace(',', '', $this->searchTerms);
				if (!empty($searchTerms)) {
					$searchTerms = $this->searchIndex . ':' . $searchTerms;
					// $Url = $this->edsBaseApi . '/Search?query=' . urlencode($searchTerms);
					$hasSearchTerm = true;
				}
			}
			if (!$hasSearchTerm) {
				return new AspenError('Please specify a search term');
			}
			$baseUrl .= '&searchmode=all';

			if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] != 1) {
				$this->page = $_REQUEST['page'];
				$baseUrl .= '&pagenumber=' . $this->page;
			} else {
				$this->page = 1;
				$baseUrl .= '&relatedcontent=rs';
			}

			// Build Authorization Headers
			$headers = array(
				'Accept' => 'application/'.$this->responseType,
				'x-summon-date' => gmdate('D, d M Y H:i:s T'),
				'Host' => 'api.summon.serialssolutions.com'
			);
			$data = implode("\n", $headers) . "\n/$this->version/'search'\n" .
				urldecode($searchTerms) . "\n";
			$hmacHash = $this->hmacsha1($this->summonApiPassword, $data);
			$headers['Authorization'] = "Summon $this->summonApiId;$hmacHash";
			if ($this->sessionId) {
				$headers['x-summon-session-id'] = $this->sessionId;
			}
			 //Modify headers as summon needs it in "key: value" format
			$modified_headers = array();
			foreach ($headers as $key=>$value) {
				$modified_headers[] = $key.": ".$value;
			}


			$curlConnection = $this->getCurlConnection();
			curl_setopt($curlConnection, CURLOPT_HTTPGET, true);
			curl_setopt($curlConnection, CURLOPT_HTTPHEADER, $modified_headers);
			curl_setopt($curlConnection, CURLOPT_URL, $baseUrl);
			$result = curl_exec($curlConnection);
			try {
				$searchData = json_decode($result);
				$this->stopQueryTimer();
				if ($searchData && empty($searchData->ErrorNumber)) {
					$this->lastSearchResults = $searchData->SearchResult;
					return $searchData->SearchResult;
				} else {

				}$this->lastSearchResults = false;
				return new AspenError("Error processing search in Summon");
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error loading data from Summon $e", Logger::LOG_ERROR);
				return new AspenError("Error loading data from Summon $e");
			}
		}
	}

	/**
	 * Cretae array of Summon params from object
	 * 
	 * @return array
	 */
	public function getOptionsArray()
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
        return $options;
    }



	// public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false)
	// {
	// 	// if(!$this->authenticate()) {
	// 	// 	return null;
	// 	// }

	// 	$this->startQueryTimer();
	// 	$hasSearchTerm = false;
	// 	if(is_array($this->searchTerms)) {
	// 		$searchUrl = $this->summonBaseApi . '/' . $this->version . '/search?';
	// 		$termIndex = 1;
	// 		foreach ($this->searchTerms as $term) {
	// 			if (!empty($term)) {
	// 				if ($termIndex > 1) {
	// 					$searchUrl .= '&';
	// 				}
	// 				$term = str_replace(',', '', $term);
	// 				$searchIndex = $term['index'];
	// 				$searchUrl .= "query-{$termIndex}=AND," . urlencode($searchIndex . ":" . $term['lookfor']);
	// 				$termIndex++;
	// 				$hasSearchTerm = true;
	// 			}
	// 		}
	// 	} else {
	// 		if (isset($_REQUEST['searchIndex'])) {
	// 			$this->searchIndex = $_REQUEST['searchIndex'];
	// 		}
	// 		$searchTerms = str_replace(',', '', $this->searchTerms);
	// 		if (!empty($searchTerms)) {
	// 			$searchTerms = $this->searchIndex . ':' . $searchTerms;
	// 			$searchUrl = $this->summonBaseApi . '/Search?query=' . urlencode($searchTerms);
	// 			$hasSearchTerm = true;
	// 		}
	// 	}
	// 	if (!$hasSearchTerm) {
	// 		return new AspenError('Please specify a search term');
	// 	}
	// 	$searchUrl .= '&searchmode=all';

	// 	if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] != 1) {
	// 		$this->page = $_REQUEST['page'];
	// 		$searchUrl .= '&pagenumber=' . $this->page;
	// 	} else {
	// 		$this->page = 1;
	// 		$searchUrl .= '&relatedcontent=rs';
	// 	}
	// 	$headers = array(
	// 		'Accept' => 'application/'.$responseType,
	// 		'x-summon-date' => gmdate('D, d M Y H:i:s T'),
	// 		'Host' => $this->summonBaseApi,
	// 	);
	// 	// $data = implode("\n", $headers) . "\n/$this->version/$service\n";
	// 	// $hmacHash = $this->hmacsha1($this->summonApiPassword, $data);
	// 	// $headers['Authorization'] = "Summon $this->summonApiId;$hmacHash";
	// 	// if ($this->sessionId) {
	// 	// 	$headers['x-summon-session-id'] = $this->sessionId;
	// 	// } 
		

		
	// 	if ($settings != null && $settings->summonSettingsId) {
	// 		$curl = curl_init();
	// 		$curlOptions = array(
	// 			CURLOPT_RETURNTRANSFER => 1,
	// 			CURLOPT_URL => "{$baseUrl}",
	// 			CURLOPT_HTTPHEADER => $headers
	// 		);
	// 		curl_setopt_array($curl, $curlOptions);
	// 		$result = curl_exec($curl);
	// 		if ($result === false) {
	// 			throw new Exception("Error in HTTP Request.");
	// 		}
	// 		curl_close($curl);

	// 		  // Send request
	// 		  $result = $this->httpRequest($baseUrl, $queryString, $headers);
	// 		  if (!$raw) {
	// 		  // Process response
	// 			  $result = $this->process($result); 
	// 		  }
	// 		  return $result;            
	// 	 }

		
	// }
		
	public function getDefaultIndex() {
		return 'TX';
	}
		
	

	



    

    // public function endSession() {
	// 	if ($this->curl_connection) {
	// 		curl_setopt($this->curl_connection, CURLOPT_URL, $this->summonBaseApi . '/endsession?sessiontoken=' . SearchObject_SummonSearcher::$sessionId);
	// 		curl_exec($this->curl_connection);
	// 	}
	// }

    // 	/**
	//  * Start the timer to figure out how long a query takes.  Complements
	//  * stopQueryTimer().
	//  *
	//  * @access protected
	//  */
	// protected function startQueryTimer() {
	// 	// Get time before the query
	// 	$time = explode(" ", microtime());
	// 	$this->queryStartTime = $time[1] . $time[0];
	// }

    // 	/**
	//  * End the timer to figure out how long a query takes.  Complements
	//  * startQueryTimer().
	//  *
	//  * @access protected
	//  */
	// protected function stopQueryTimer() {
	// 	$time = explode(" ", microtime());
	// 	$this->queryEndTime = $time[1] . $time[0];
	// 	$this->queryTime = $this->queryEndTime - $this->queryStartTime;
	// }

    // public function getSearchOptions($responseType) {
	// 	if (SearchObject_SummonSearcher::$searchOptions == null) {
	// 		if (!$this->call($responseType)) {
	// 			return null;
	// 		}

	// 		$curlConnection = $this->getCurlConnection();
	// 		curl_setopt($curlConnection, CURLOPT_HTTPGET, true);
	// 		curl_setopt($curlConnection, CURLOPT_HTTPHEADER, [
	// 			'Content-Type: application/json',
	// 			'Accept: application/json',
	// 			'x-authenticationToken: ' . SearchObject_SummonSearcher::$authedUser,
	// 			'x-sessionToken: ' . SearchObject_SummonSearcher::$sessionId,
	// 		]);
	// 		$infoUrl = $this->summonBaseApi . '/info';
	// 		curl_setopt($curlConnection, CURLOPT_URL, $infoUrl);
	// 		$searchOptionsStr = curl_exec($curlConnection);

	// 		SearchObject_SummonSearcher::$searchOptions = json_decode($searchOptionsStr);
	// 		if (SearchObject_SummonSearcher::$searchOptions) {
	// 			return SearchObject_SummonSearcher::$searchOptions;
	// 		} else {
	// 			return null;
	// 		}
	// 	} else {
	// 		return SearchObject_SummonSearcher::$searchOptions;
	// 	}
	// }

	// public function getSortList() {
	// 	$sortOptions = $this->getSortOptions();
	// 	$list = [];
	// 	if ($sortOptions != null) {
	// 		foreach ($sortOptions as $sort => $label) {
	// 			$list[$sort] = [
	// 				'sortUrl' => $this->renderLinkWithSort($sort),
	// 				'desc' => $label,
	// 				'selected' => ($sort == $this->sort),
	// 			];

	// 		}
	// 	}

	// 	return $list;
	// }

	// public function getSortOptions() {
	// 	global $memCache;
	// 	$sortOptions = $memCache->get('summon_sort_options_' . $this->getSettings()->summonApiProfile);
	// 	if ($sortOptions === false) {
	// 		$searchOptions = $this->getSearchOptions();
	// 		$sortOptions = [];
	// 		if ($searchOptions != null) {
	// 			foreach ($searchOptions->AvailableSearchCriteria->AvailableSorts as $sortOption) {
	// 				$sort = $sortOption->Id;
	// 				$desc = $sortOption->Label;
	// 				$sortOptions[$sort] = $desc;
	// 			}
	// 		}
	// 		global $configArray;
	// 		$memCache->set('summon_sort_options_' . $this->getSettings()->summonSettingsId, $sortOptions, $configArray['Caching']['summon_options']);
	// 	}

	// 	return $sortOptions;
	// }

    // public function getSearchIndexes() {
	// 	global $memCache;

	// 	if ($this->getSettings() == null) {
	// 		return [];
	// 	} else {
	// 		$searchIndexes = $memCache->get('summon_search_indexes_' . $this->getSettings()->summonSettingsId);
	// 		if ($searchIndexes === false) {
	// 			$searchOptions = $this->getSearchOptions();
	// 			$searchIndexes = [];
	// 			if ($searchOptions != null) {
	// 				foreach ($searchOptions->AvailableSearchCriteria->AvailableSearchFields as $searchField) {
	// 					$searchIndexes[$searchField->FieldCode] = translate([
	// 						'text' => $searchField->Label,
	// 						'isPublicFacing' => true,
	// 						'inAttribute' => true,
	// 					]);
	// 				}
	// 			}
	// 			global $configArray;
	// 			$memCache->set('summon_search_indexes_' . $this->getSettings()->summonSettingsId, $searchIndexes, $configArray['Caching']['ebsco_options']);
	// 		}

	// 		return $searchIndexes;
	// 	}
	// }

	function getSearchesFile() {
		return false;
	}

	// 	/**
	//  * Return a url for the current search with a new sort
	//  *
	//  * @access  public
	//  * @param string $newSort A field to sort by
	//  * @return  string   URL of a new search
	//  */
	// public function renderLinkWithSort($newSort) {
	// 	// Stash our old data for a minute
	// 	$oldSort = $this->sort;
	// 	// Add the new sort
	// 	$this->sort = $newSort;
	// 	// Get the new url
	// 	$url = $this->renderSearchUrl();
	// 	// Restore the old data
	// 	$this->sort = $oldSort;
	// 	// Return the URL
	// 	return $url;
	// }

    // public function setSearchTerm($searchTerm) {
	// 	if (strpos($searchTerm, ':') !== false) {
	// 		[
	// 			$searchIndex,
	// 			$term,
	// 		] = explode(':', $searchTerm, 2);
	// 		$this->setSearchTerms([
	// 			'lookfor' => $term,
	// 			'index' => $searchIndex,
	// 		]);
	// 	} else {
	// 		$this->setSearchTerms([
	// 			'lookfor' => $searchTerm,
	// 			'index' => $this->getDefaultIndex(),
	// 		]);
	// 	}
	// }

	// public function getOptionsArray()
    // {
    //     $options = array(
    //         's.q' => $this->query,
    //         's.ps' => $this->pageSize,
    //         's.pn' => $this->pageNumber,
    //         's.ho' => $this->holdings ? 'true' : 'false',
    //         's.dym' => $this->didYouMean ? 'true' : 'false',
    //         's.l' => $this->language,
    //     );
	// }

	// /**
    //  * Execute a search.
    //  *
    //  * @param Summon_Query $query     Query object
    //  * @param bool                          $returnErr On fatal error, should we fail
    //  * outright (false) or treat it as an empty result set with an error key set
    //  * (true)?
    //  * @param bool                          $raw       Return raw (true) or processed
    //  * (false) response?
    //  *
    //  * @return array             An array of query results
	//  * */
    // public function query($query, $returnErr = false, $raw = false) {
	// 	// Query String Parameters
    //     $options = $query->getOptionsArray();
    //     $options['s.role'] = $this->authedUser ? 'authenticated' : 'none';
	// 	$this->startQueryTimer();

	// 		if (isset($options['s.fvf']) && is_array($options['s.fvf'])
	// 			&& in_array('ContentType,Newspaper Article,true', $options['s.fvf'])
	// 			&& in_array('ContentType,Newspaper Article', $options['s.fvf'])
    //    	 ) {
    //         return array(
    //             'recordCount' => 0,
    //             'documents' => array()
    //         );
	// 		}	
	// 		try {
	// 			$result = $this->call($options, 'search', 'GET', $raw);
	// 		} catch (Exception $e) {
	// 			if ($returnErr) {
	// 				return array(
	// 					'recordCount' => 0,
	// 					'documents' => array(),
	// 					'errors' => $e->getMessage()
	// 				);
	// 			} else {
	// 				$this->handleFatalError($e);
	// 			}
	// 		}
	
	// 		return $result;
	// }
	

	//   /**
    //  * Submit REST Request
    //  *
    //  * @param array  $params  An array of parameters for the request
    //  * @param string $service The API Service to call
    //  * @param string $method  The HTTP Method to use
    //  * @param bool   $raw     Return raw (true) or processed (false) response?
    //  * @throws Exception
    //  * @return object         The Summon API response
    //  */
    // protected function call($params = array(), $service = 'search', $method = 'GET',
    //     $raw = false
    // ) {
    //     $baseUrl =  $this ->summonBaseApi. '/' . $this->version . '/' . $service;

    //     // Build Query String
    //     $query = array();
    //     foreach ($params as $function => $value) {
    //         if (is_array($value)) {
    //             foreach ($value as $additional) {
    //                 $additional = urlencode($additional);
    //                 $query[] = "$function=$additional";
    //             }
    //         } elseif (!is_null($value)) {
    //             $value = urlencode($value);
    //             $query[] = "$function=$value";
    //         }
    //     }
    //     asort($query);
    //     $queryString = implode('&', $query);

    //     // Build Authorization Headers
    //     $headers = array(
    //         'Accept' => 'application/'.$this->responseType,
    //         'x-summon-date' => gmdate('D, d M Y H:i:s T'),
    //         'Host' => 'api.summon.serialssolutions.com'
    //     );
    //     $data = implode("\n", $headers) . "\n/$this->version/$service\n" .
    //         urldecode($queryString) . "\n";
    //     $hmacHash = $this->hmacsha1($this->apiKey, $data);
    //     $headers['Authorization'] = "Summon $this->apiId;$hmacHash";
    //     if ($this->sessionId) {
    //         $headers['x-summon-session-id'] = $this->sessionId;
    //     }

    //     // Send request
    //     $result = $this->httpRequest($baseUrl, $method, $queryString, $headers);
    //     if (!$raw) {
    //         // Process response
    //         $result = $this->process($result); 
    //     }
    //     return $result;
    // }

	  /**
     * Perform normalization and analysis of Summon return value.
     *
     * @param array $input The raw response from Summon
     *
     * @throws Exception
     * @return array       The processed response from Summon
     */
    protected function process($input)
    {
        if ($this->responseType !== "json") {
            return $input;
        }

        // Unpack JSON Data
        $result = json_decode($input, true);

        // Catch decoding errors -- turn a bad JSON input into an empty result set
        if (!$result) {
            $result = array(
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
          if (isset($result['errors']) && is_array($result['errors'])) {
            foreach ($result['errors'] as $current) {
                $errors[] = "{$current['code']}: {$current['message']}";
            }
            $msg = 'Unable to process query<br />Summon returned: ' .
                implode('<br />', $errors);
            throw new Exception($msg);
        }

        return $result;
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
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML() {
		global $interface;
		$html = [];
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records); $x++) {
				$current = &$this->lastSearchResults->Data->Records[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

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

		//Save to history
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
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records); $x++) {
				$current = &$this->lastSearchResults->Data->Records[$x];
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

	public function getEngineName() {
		return 'Summon';
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

	function getBrowseRecordHTML() {
		global $interface;
		$html = [];
		if (isset($this->lastSearchResults->Data->Records)) {
			for ($x = 0; $x < count($this->lastSearchResults->Data->Records); $x++) {
				$current = &$this->lastSearchResults->Data->Records[$x];
				$interface->assign('recordIndex', $x + 1);
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));

				require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
				$record = new SummonRecordDriver($current);
				if ($record->isValid()) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getBrowseResult());
				} else {
					$html[] = "Unable to find record";
				}
			}
		}

		return $html;
	}






}