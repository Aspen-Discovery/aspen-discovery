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
    private static $sessionId;
    private $version = '2.0.0';
    private $service = 'search';
    private $authedUser = false;
    private $responseType = "json";
    private static $searchOptions;
    private $params = array();
    private $method = 'GET';
    private $raw = false;
    private $curl_connection;

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

    //From base searcher
    // Module and Action for building search results URLs
	protected $resultsModule = 'Search';
	protected $resultsAction = 'Results';
    	/** @var string */
	protected $searchSource = 'local';
    protected $searchType = 'basic';

  





    public function __construct() {
        global $library;

        //Initialize properties with default values
        $this->searchSource = 'summon';
        $this->searchType = 'summon';
        $this->resultsModule = 'Summon';
        $this->resultsAction = 'Results';
        $legalOptions = array('authedUser', 'summonBaseApi', 'sessionId', 'version', 'responseType');
        foreach($legalOptions as $option) {
            if(isset($options[$option])){
            $this->$option = $options[$option];
            }
        }


        //Set Summon Settings
        $this->summonSettings = new SummonSettings();
        $this->summonSettings->id = $library->summonSettingsId;

        if (!$this->summonSettings->find(true)) {
            $this->summonSettings = null;
        } 
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
			return true;
		} elseif ($restored instanceof AspenError) {
			return false;
		}

		//********************
		// Initialize standard search parameters
		// $this->initView();
		// $this->initPage();
		// $this->initSort();
		// $this->initFilters();
		// $this->initLimiters();

		// //********************
		// // Basic Search logic
		// if (!$this->initBasicSearch()) {
		// 	$this->initAdvancedSearch();
		// }

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = $_REQUEST['q'];
		}

		return true;
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
			$this->summonSettings->id = $library->edsSettingsId;
			if (!$this->summonSettings->find(true)) {
				$this->summonSettings = null;
			}
		}
		return $this->summonSettings;
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

    protected function sendRequest() {
            $baseUrl = $this->summonBaseApi . '/' .$this->version . '/' .$this->service;
            global $libray;
            $settings = $this->getSettings();
            if ($settings != null && $settings->summonApiProfile) {
                $query = array();
                $this->startQueryTimer();
                $hasSearchTerm = false;
                if (is_array($this->searchTerms)) {
                    $termIndex = 1;
                    foreach ($this->searchTerms as $term) {
                        if (!empty($term)) {
                            if ($termIndex > 1) {
                                $searchUrl = $baseUrl . '&';
                            }
                            $term = str_replace(',', '', $term);
                            $searchIndex = $term['index'];
                            $searchUrl .= "query-{$termIndex}=AND," .urlencode($searchIndex . ":" . $term['lookfor']);
                            $termIndex ++;
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
                        $searchUrl = $baseUrl . '?query=' . urlencode($searchTerms);
                        $hasSearchTerm = true;
                    }
                }
                if (!$hasSearchTerm) {
                    return new AspenError('Please specify a search term');
                }
                $searchUrl .= '&searchmode=all';



                // foreach ($this->params as $function => $value){
                //     if (is_array($value)) {
                //         foreach ($value as $additional) {
                //             if(!empty($additional)) {
                //                 if ($termIndex > 1) {
                //                     $searchUrl = $baseUrl . '&';
                //                 }
                //             }
                //             $additional = urlencode($additional);
                //             $query[] = "$function=$additional";
                //         }
               

                // Build Authorization Headers
                $headers = array(
                    'Accept' => 'application/'.$this->responseType,
                    'x-summon-date' => gmdate('D, d M Y H:i:s T'),
                    'Host' => 'api.summon.serialssolutions.com'
                );
                $data = implode("\n", $headers) . "\n/$this->version/$this->service\n" .
                    urldecode($searchUrl) . "\n";
                $hmacHash = $this->hmacsha1($settings->summonApiPassword, $data);
                $headers['Authorization'] = "Summon $settings->summonApiId;$hmacHash";

                if ($this->sessionId) {
                    $headers['x-summon-session-id'] = $this->sessionId;
                } 
                // Send request
                $recordData = $this->httpRequest($searchUrl, $this->method, $headers);
                if (!$this->raw) {
                    // Process response
                    $recordData = $this->process($recordData); 
                }
                return $recordData;
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
    
          

    

    protected function httpRequest($headers, $queryString, $baseUrl) {
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
        curl_close($curlConnection);

        return $result;
    }

      /**
     * @param SummonQuery $query Query object
     */
     public function query($query, $returnErr = false, $raw = false) {
         // Query String Parameters
         $options = $query->getOptionsArray();
         $options['s.role'] = $this->authedUser ? 'authenticated' : 'none';
 
         // Special case -- if user filtered down to newspapers AND excluded them,
         // we can't possibly have any results:
         if (isset($options['s.fvf']) && is_array($options['s.fvf'])
             && in_array('ContentType,Newspaper Article,true', $options['s.fvf'])
             && in_array('ContentType,Newspaper Article', $options['s.fvf'])
         ) {
             return array(
                 'recordCount' => 0,
                 'documents' => array()
             );
         }
 
 
         try {
             $result = $this->sendRequest($options, 'search', 'GET', $raw);
         } catch (Exception $e) {
             if ($returnErr) {
                 return array(
                     'recordCount' => 0,
                     'documents' => array(),
                     'errors' => $e->getMessage()
                 );
             } else {
                 throw new AspenError($e);
             }
         }
 
         return $result;
     }
     
     public function processSearch($returnIndexErrors = false, $recommendations = false, $preventQueryModification = false)
     {
        
     }

     public function retreiveRecord($id, $raw = false, $idType = self::IDENTIFIER_ID) {
        $options = $idType === self::IDENTIFIER_BOOKMARK
            ? array('s.bookMark' => $id)
            : array('s.q' => sprintf('ID:"%s"', $id));
        $options['s.role'] = $this->authedUser ? 'authenticated' : 'none';
        return $this->sendRequest($options, 'search', 'GET', $raw);
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

    //  public function getSearchIndexes() {
	// 	global $memCache;
    //     $settings = $this->getSettings();

	// 	if ($settings == null) {
	// 		return [];
	// 	} else {
	// 		$searchIndexes = $memCache->get('summon_search_indexes_' . $this->getSettings()->summonApiProfile);
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
	// 			$memCache->set('summon_search_indexes_' . $this->getSettings()->summonApiProfile, $searchIndexes, $configArray['Caching']['summon_options']);
	// 		}

	// 		return $searchIndexes;
	// 	}
	// }

    public function getDefaultIndex() {
		return 'SummonKeyword';
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
		return 'Summon';
	}

	function getSearchesFile() {
		return false;
	}

 

 
  


}