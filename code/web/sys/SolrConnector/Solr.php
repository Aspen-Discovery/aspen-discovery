<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/sys/SolrUtils.php';
require_once ROOT_DIR . '/sys/AspenError.php';

abstract class Solr {
	/**
	 * A boolean value determining whether to include debug information in the query
	 * @var bool
	 */
	public $debug = false;

	/**
	 * A boolean value determining whether to print debug information for the query
	 * @var bool
	 */
	public $debugSolrQuery = false;

	public $isPrimarySearch = false;

	/**
	 * Whether to Serialize to a PHP Array or not.
	 * @var bool
	 */
	public $raw = false;

	/**
	 * The CurlWrapper object used for REST transactions
	 * @var CurlWrapper $client
	 */
	public $client;

	/**
	 * The host to connect to
	 * @var string
	 */
	public $host;

	protected $index;

	/**
	 * The status of the connection to Solr
	 * @var string
	 */
	public $status = false;

	/**
	 * An array of characters that are illegal in search strings
	 */
	private $illegal = [
		'!',
		':',
		';',
		'[',
		']',
		'{',
		'}',
	];

	/**
	 * An array of search specs pulled from yaml file
	 *
	 * @var array
	 */
	private static $_searchSpecs = [];

	/**
	 * Should boolean operators in the search string be treated as
	 * case-insensitive (false), or must they be ALL UPPERCASE (true)?
	 */
	private $caseSensitiveBooleans = true;

	/**
	 * Should range operators (i.e. [a TO b]) in the search string be treated as
	 * case-insensitive (false), or must they be ALL UPPERCASE (true)?    Note that
	 * making this setting case insensitive not only changes the word "TO" to
	 * uppercase but also inserts OR clauses to check for case insensitive matches
	 * against the edges of the range...    i.e. ([a TO b] OR [A TO B]).
	 */
	private $_caseSensitiveRanges = true;

	/**
	 * Should we collect highlighting data?
	 */
	protected $_highlight = false;

	/**
	 * How should we cache the search specs?
	 */
	private $_specCache = false;
	/**
	 * Flag to disable default scoping to show ILL book titles, etc.
	 */
	private $scopingDisabled = false;

	/** @var string */
	private $searchSource = null;
	/**
	 * @var string
	 */
	private $debugSearchUrl;
	/**
	 * @var string
	 */
	public $fullSearchUrl;

	/** return string */
	abstract public function getSearchesFile();

	/**
	 * Constructor
	 *
	 * Sets up the SOAP Client
	 *
	 * @param string $host The URL for the local Solr Server
	 * @param string $index The name of the index
	 * @access    public
	 */
	function __construct($host, $index = '') {
		global $configArray;
		global $timer;

		// Set a default Solr index if none is provided to the constructor:
		if (empty($index)) {
			global $library;
			if ($library) {
				$index = 'grouped_works';
			} else {
				$index = isset($configArray['Index']['default_core']) ? $configArray['Index']['default_core'] : "grouped_works";
			}

			$this->index = $index;
		} else {
			$this->index = $index;
		}

		$timer->logTime("Load search specs");

		$this->host = $host . '/' . $index;

		// If we're still processing then solr is online
		$this->client = new CurlWrapper();

		// Read in preferred boolean behavior:
		$searchSettings = getExtraConfigArray($this->getSearchesFile());
		if (isset($searchSettings['General']['case_sensitive_bools'])) {
			$this->caseSensitiveBooleans = $searchSettings['General']['case_sensitive_bools'];
		}
		if (isset($searchSettings['General']['case_sensitive_ranges'])) {
			$this->_caseSensitiveRanges = $searchSettings['General']['case_sensitive_ranges'];
		}

		// Turn on highlighting if the user has requested highlighting or snippet
		// functionality:
		$highlight = $configArray['Index']['enableHighlighting'];
		$snippet = $configArray['Index']['enableSnippets'];
		if ($highlight || $snippet) {
			$this->_highlight = true;
		}

		// Deal with search spec cache setting:
		if (isset($searchSettings['Cache']['type'])) {
			$this->_specCache = $searchSettings['Cache']['type'];
		}

		$timer->logTime('Finish Solr Initialization');
	}

	public function __destruct() {
		$this->client->close_curl();
		$this->client = null;
	}

	private static $serversPinged = [];

	public function pingServer($failOnError = true, $forceCheck = false) : bool {
		global $memCache;
		global $timer;
		global $configArray;
		global $logger;
		$hostEscaped = str_replace('/' . $this->index, '', $this->host);
		$hostEscaped = preg_replace('[\W]', '_', $hostEscaped);
		if ($forceCheck) {
			$pingDone = false;
		}else{
			if (array_key_exists($hostEscaped, Solr::$serversPinged)) {
				return Solr::$serversPinged[$hostEscaped];
			}
			if ($memCache) {
				$pingDone = $memCache->get('solr_ping_' . $hostEscaped);
				if ($pingDone !== false) {
					Solr::$serversPinged[$this->host] = $pingDone;
					return (bool)Solr::$serversPinged[$this->host];
				} else {
					$pingDone = false;
				}
			} else {
				$pingDone = false;
			}
		}

		// Test to see solr is online
		$test_url = $this->host . "/admin/ping";
		$test_client = new CurlWrapper();
		//We can get false positives if the Solr server is busy and timeouts are short.
		//$test_client->setTimeout(1);
		//$test_client->setConnectTimeout(1);
		$result = $test_client->curlGetPage($test_url);
		if ($result !== false) {
			// Even if we get a response, make sure it's a 'good' one.
			if ($test_client->getResponseCode() != 200) {
				$pingResult = 'false';
				Solr::$serversPinged[$this->host] = false;
				if ($failOnError) {
					AspenError::raiseError('Solr index is offline.');
				} else {
					$logger->log("Ping of {$this->host} failed", Logger::LOG_DEBUG);
					return false;
				}
			} else {
				$pingResult = 'true';
			}
		} else {
			$pingResult = 'false';
			Solr::$serversPinged[$this->host] = false;
			if ($failOnError) {
				AspenError::raiseError('The Solr Server is offline, please try your request again in a few minutes.');
			} else {
				$logger->log("Ping of {$this->host} failed", Logger::LOG_DEBUG);
				return false;
			}
		}

		//Don't cache that we are done to be sure ASpen recovers as quickly as possible.
		if ($memCache && $pingResult === 'true') {
			$memCache->set('solr_ping_' . $hostEscaped, $pingResult, $configArray['Caching']['solr_ping']);
		}
		Solr::$serversPinged[$hostEscaped] = $pingResult;
		$timer->logTime('Ping Solr instance ' . $this->host);

		return (bool)Solr::$serversPinged[$hostEscaped];
	}

	public function setTimeout($timeout) {
		$this->client->setTimeout($timeout);
	}

	/**
	 * Is this object configured with case-sensitive boolean operators?
	 *
	 * @access    public
	 * @return    boolean
	 */
	public function hasCaseSensitiveBooleans() {
		return $this->caseSensitiveBooleans;
	}

	/**
	 * Is this object configured with case-sensitive range operators?
	 *
	 * @return boolean
	 * @access public
	 */
	public function hasCaseSensitiveRanges() {
		return $this->_caseSensitiveRanges;
	}

	/**
	 * Support method for _getSearchSpecs() -- load the specs from cache or disk.
	 *
	 * @return void
	 * @access private
	 */
	private function _loadSearchSpecs() {
		if (empty(Solr::$_searchSpecs[$this->host])) {
			require_once ROOT_DIR . '/sys/Yaml.php';
			try {
				$yaml = new Yaml();
				Solr::$_searchSpecs[$this->host] = $yaml->load($this->getSearchSpecsFile());
			} catch (Exception $e) {
				require_once ROOT_DIR . '/sys/AspenError.php';
				AspenError::raiseError('Could not load search specs, check the configuration ' . $e->getMessage());
			}
		}
	}

	/**
	 * @return string
	 */
	abstract function getSearchSpecsFile();

	/**
	 * Get the search specifications loaded from the specified YAML file.
	 *
	 * @param string $handler The named search to provide information about (set
	 * to null to get all search specifications)
	 *
	 * @return mixed Search specifications array if available, false if an invalid
	 * search is specified.
	 * @access    private
	 */
	private function _getSearchSpecs($handler = null) {
		// Only load specs once:
		if (empty(Solr::$_searchSpecs[$this->host])) {
			$this->_loadSearchSpecs();
		}

		// Special case -- null $handler means we want all search specs.
		if (is_null($handler)) {
			return Solr::$_searchSpecs[$this->host];
		}

		// Return specs on the named search if found (easiest, most common case).
		if (isset(Solr::$_searchSpecs[$this->host][$handler])) {
			return Solr::$_searchSpecs[$this->host][$handler];
		}

		// Check for a case-insensitive match -- this provides backward
		// compatibility with different cases used in early VuFind versions
		// and allows greater tolerance of minor typos in config files.
		foreach (Solr::$_searchSpecs[$this->host] as $name => $specs) {
			if (strcasecmp($name, $handler) == 0) {
				return $specs;
			}
		}

		// If we made it this far, no search specs exist -- return false.
		return false;
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param string $id The document to retrieve from Solr
	 * @param string $fieldsToReturn An optional list of fields to return separated by commas
	 * @access    public
	 * @return    array                            The requested resource
	 * @throws    AspenError
	 */
	function getRecord($id, $fieldsToReturn = null) {
		$record = null;
		if (!$fieldsToReturn) {
			$validFields = $this->loadValidFields();
			$fieldsToReturn = implode(',', $validFields);
		}
		//$this->pingServer();
		// Query String Parameters
		$options = ['q' => "id:\"$id\""];
		$options['fl'] = $fieldsToReturn;

		global $timer;
		$timer->logTime("Prepare to send get (ids) request to solr returning fields $fieldsToReturn");
		$result = $this->client->curlGetPage($this->host . "/select?" . http_build_query($options));
		$timer->logTime("Send data to solr during getRecord $id $fieldsToReturn");

		$result = $this->_process($result);

		if ($result != null && count($result['response']['docs']) >= 1) {
			$record = $result['response']['docs'][0];
		} else {
			AspenError::raiseError("Record not found $id");
		}
		return $record;
	}


	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param array $ids A list of document to retrieve from Solr
	 * @param string $fieldsToReturn An optional list of fields to return separated by commas
	 * @access    public
	 * @return    array                            The requested resources
	 * @throws    AspenError
	 */
	function getRecords($ids, $fieldsToReturn = null) {
		if (count($ids) == 0) {
			return [];
		}
		//Solr does not seem to be able to return more than 50 records at a time,
		//If we have more than 50 ids, we will ned to make multiple calls and
		//concatenate the results.
		$records = [];
		$startIndex = 0;
		$batchSize = 40;

		//$this->pingServer();

		$lastBatch = false;
		while (true) {
			$endIndex = $startIndex + $batchSize;
			if ($endIndex >= count($ids)) {
				$lastBatch = true;
				$endIndex = count($ids);
				$batchSize = count($ids) - $startIndex;
			}
			$tmpIds = array_slice($ids, $startIndex, $batchSize);

			// Query String Parameters
			$idString = implode(' OR ', $tmpIds);
			$options = ['q' => "id:($idString)"];
			$options['fl'] = $fieldsToReturn;
			$options['rows'] = count($tmpIds);

			// Send Request
			global $timer;
			$timer->logTime("Prepare to send get (ids)  request to solr");
			$result = $this->client->curlGetPage($this->host . "/select?" . http_build_query($options));
			$timer->logTime("Send data to solr for getRecords");

			if ($result) {
				$result = $this->_process($result);

				foreach ($result['response']['docs'] as $record) {
					$records[$record['id']] = $record;
				}
			}
			if ($lastBatch) {
				break;
			} else {
				$startIndex = $endIndex;
			}
		}
		//echo("Found " . count($records) . " records.	Should have found " . count($ids) . "\r\n<br/>");
		return $records;
	}


	/**
	 * Get record data based on the provided field and phrase.
	 * Used for AJAX suggestions.
	 *
	 * @access    public
	 * @param string $phrase The input phrase
	 * @param string $field The field to search on
	 * @param int $limit The number of results to return
	 * @return    array     An array of query results
	 */
	function getSuggestion($phrase, $field, $limit) {
		if (!strlen($phrase)) {
			return null;
		}

		// Ignore illegal characters
		$phrase = str_replace($this->illegal, '', $phrase);

		// Process Search
		$query = "$field:($phrase*)";
		$result = $this->search($query, null, null, 0, $limit, [
			'field' => $field,
			'limit' => $limit,
		]);
		return $result['facet_counts']['facet_fields'][$field];
	}

	/**
	 * Get search suggestions based on input phrase.
	 *
	 * @param string $phrase The input phrase.
	 * @param string $suggestionHandler
	 * @return array|AspenError An array of search suggestions.
	 */
	function getSearchSuggestions(string $phrase, string $suggestionHandler = 'suggest'): array|AspenError {
		$options = [
			'suggest.q' => $phrase,
			'q.op' => 'AND',
			'rows' => 0,
			'start' => 1,
			'indent' => 'yes',
		];

		$searchLibrary = Library::getSearchLibrary($this->searchSource);
		$searchLocation = Location::getSearchLocation($this->searchSource);
		$cfqFilters = $this->getScopingFiltersForCFQ($searchLibrary, $searchLocation);
		if (!empty($cfqFilters)) {
			$options['suggest.cfq'] = implode(' AND ', $cfqFilters);
		}

		$result = $this->_select('GET', $options, false, $suggestionHandler);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		// Add highlighting because suggestion highlighting still does not
		// work in Solr (https://issues.apache.org/jira/browse/SOLR-7964).
		if (isset($result['suggest']) && !empty($phrase)) {
			$searchTerms = preg_split('/\s+/', strtolower(trim($phrase)));
			$searchTerms = array_filter($searchTerms, function($term) { return strlen($term) > 1; });

			foreach ($result['suggest'] as &$dictionary) {
				foreach ($dictionary as &$queryData) {
					if (isset($queryData['suggestions'])) {
						foreach ($queryData['suggestions'] as &$suggestion) {
							if (isset($suggestion['term'])) {
								foreach ($searchTerms as $term) {
									$pattern = '/(' . preg_quote($term, '/') . ')/i';
									$suggestion['term'] = preg_replace($pattern, '<b>$1</b>', $suggestion['term']);
								}
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get spelling suggestions based on input phrase.
	 *
	 * @access    public
	 * @param string $phrase The input phrase
	 * @return    array     An array of spelling suggestions
	 */
	function checkSpelling($phrase) {
		if ($this->debugSolrQuery) {
			echo "<pre>Spell Check: $phrase</pre>\n";
		}

		// Query String Parameters
		$options = [
			'q' => $phrase,
			'rows' => 0,
			'start' => 1,
			'indent' => 'yes',
			'spellcheck' => 'true',
		];

		$result = $this->_select('GET', $options, false, 'spell');
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	/**
	 * applySearchSpecs -- internal method to build query string from search parameters
	 *
	 * @access    private
	 * @param array $structure the SearchSpecs-derived structure or substructure defining the search, derived from the yaml file
	 * @param array $values the various values in an array with keys 'onephrase', 'and', 'or' (and perhaps others)
	 * @param string $joiner
	 * @return    string A search string suitable for adding to a query URL
	 * @throws    AspenError
	 * @static
	 */
	private function _applySearchSpecs($structure, $values, $joiner = "OR") {
		global $solrScope;
		$clauses = [];
		foreach ($structure as $field => $clauseArray) {
			if (is_numeric($field)) {
				// shift off the join string and weight
				$sw = array_shift($clauseArray);
				$internalJoin = ' ' . $sw[0] . ' ';
				// Build it up recursively
				$searchString = '(' . $this->_applySearchSpecs($clauseArray, $values, $internalJoin) . ')';
				// ...and add a weight if we have one
				$weight = $sw[1];
				if (!is_null($weight) && $weight && $weight > 0) {
					$searchString .= '^' . $weight;
				}
				// push it onto the stack of clauses
				$clauses[] = $searchString;
			} else {
				if ($solrScope) {
					if ($field == 'local_callnumber' || $field == 'local_callnumber_left' || $field == 'local_callnumber_exact') {
						$field .= '_' . $solrScope;
					}
				}

				// Otherwise, we've got a (list of) [munge, weight] pairs to deal with
				foreach ($clauseArray as $spec) {
					$fieldValue = $values[$spec[0]];

					if ($field == 'isbn') {
						if (!preg_match('/^((?:\sOR\s)?["(]?\d{9,13}X?[\s")]*)+$/', $fieldValue)) {
							continue;
						} else {
							require_once(ROOT_DIR . '/sys/ISBN.php');
							$isbn = new ISBN($fieldValue);
							if ($isbn->isValid()) {
								$isbn10 = $isbn->get10();
								$isbn13 = $isbn->get13();
								if ($isbn10 && $isbn13) {
									$fieldValue = '(' . $isbn->get10() . ' OR ' . $isbn->get13() . ')';
								}
							}
						}
					} elseif ($field == 'id') {
						if (!preg_match('/^"?(\d+|.[boi]\d+x?|[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12})(-\w{3})?"?$/i', $fieldValue)) {
							continue;
						}
					} elseif ($field == 'alternate_ids') {
						if (!preg_match('/^"?(\d+|.?[boi]\d+x?|[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}|MWT\d+|CARL\d+)"?$/i', $fieldValue)) {
							continue;
						}
					} elseif ($field == 'issn') {
						if (!preg_match('/^"?[\d\hXx-]+"?$/', $fieldValue)) {
							continue;
						}
					} elseif ($field == 'upc') {
						if (!preg_match('/^"?\d+"?$/', $fieldValue)) {
							continue;
						}
					}

					//Ignore empty searches
					if (strlen($fieldValue) == 0) {
						continue;
					}

					// build a string like title:("one two")
					if ($fieldValue[0] != '(') {
						$searchString = $field . ':(' . $fieldValue . ')';
					} else {
						$searchString = $field . ':' . $fieldValue;
					}
					//Check to make sure we don't already have this clause.  We will get the same clause if we have a single word and are doing different munges
					$okToAdd = true;
					foreach ($clauses as $clause) {
						if (strpos($clause, $searchString) === 0) {
							$okToAdd = false;
							break;
						}
					}
					if (!$okToAdd) {
						continue;
					}

					// Add the weight if we have one. Yes, I know, it's redundant code.
					$weight = $spec[1];
					if (!is_null($weight) && $weight && $weight > 0) {
						$searchString .= '^' . $weight;
					}

					// ..and push it on the stack of clauses
					$clauses[] = $searchString;
				}
			}
		}

		// Join it all together
		return implode(' ' . $joiner . ' ', $clauses);
	}

	/**
	 * Load Boost factors for a query
	 *
	 * @param Library $searchLibrary
	 * @return array
	 */
	public function getBoostFactors(/** @noinspection PhpUnusedParameterInspection */ $searchLibrary, $searchLocation, $searchTerm, $searchIndex) {
		return [];
	}

	/**
	 * Given a field name and search string, return an array containing munged
	 * versions of the search string for use in _applySearchSpecs().
	 *
	 * @access    private
	 * @param string $lookfor The string to search for in the field
	 * @param array $custom Custom munge settings from YAML search specs
	 * @param bool $basic Is $lookfor a basic (true) or advanced (false) query?
	 * @return    array                             Array for use as _applySearchSpecs() values param
	 */
	private function _buildMungeValues($lookfor, $custom = null, $basic = true) {
		$grouped_word_id_pattern_1 = '/^"?(\d+|.[boi]\d+x?|[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12})(-\w{3})?"?$/i';
		$grouped_word_id_pattern_2 = '/^"?(\d+|.?[boi]\d+x?|[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}|MWT\d+|CARL\d+)"?$/i';
		if ($basic) {
			// Don't strip characters out/escape them if it's a grouped work ID
			if (!preg_match($grouped_word_id_pattern_1, $lookfor) && !preg_match($grouped_word_id_pattern_2, $lookfor)) {
				$cleanedQuery = str_replace(':', ' ', $lookfor);
				$cleanedQuery = str_replace('“', '"', $cleanedQuery);
				$cleanedQuery = str_replace('”', '"', $cleanedQuery);
				// Fix for date ranges
				//This is no longer needed because the - is escaped later
				//$cleanedQuery = preg_replace("/([0-9a-zA-Z])([-])([0-9a-zA-Z])/", "$1 $3", $cleanedQuery);
				// Fix for ordinal numbers
				$cleanedQuery = preg_replace("/([0-9])(st|nd|rd|th)/", "$1 $2", $cleanedQuery);
				$cleanedQuery = preg_replace('%([-+!(){}\][^~?/\\\\])%', '\\\\$1', $cleanedQuery);
			} else {
				$cleanedQuery = $lookfor;
			}
			require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
			$noTrailingPunctuation = StringUtils::removeTrailingPunctuation($cleanedQuery);

			// Tokenize Input
			$tokenized = $this->tokenizeInput($noTrailingPunctuation);

			// Create AND'd and OR'd queries
			$tokenizedNoStopWords = $this->removeStopWords($tokenized);
			$andQuery = implode(' AND ', $tokenizedNoStopWords);
			$orQuery = implode(' OR ', $tokenizedNoStopWords);

			// Build possible inputs for searching:
			$values = [];
			$values['onephrase'] = '"' . str_replace('"', '', implode(' ', $tokenized)) . '"';
			if (count($tokenized) > 1) {
				$values['proximal'] = $values['onephrase'] . '~10';
				$values['single_word'] = null;
			} else {
				$values['proximal'] = null;
				if (!array_key_exists(0, $tokenized)) {
					$values['single_word'] = '';
				} else {
					$values['single_word'] = $tokenized[0];
				}
			}

			$values['exact'] = str_replace(':', '\\:', $noTrailingPunctuation);
			$values['exact_quoted'] = '"' . $noTrailingPunctuation . '"';
			$values['and'] = $andQuery;
			$values['or'] = $orQuery;
			$singleWordRemoval = "";
			if (count($tokenized) <= 4) {
				$singleWordRemoval = '"' . str_replace('"', '', implode(' ', $tokenized)) . '"';
			} else {
				for ($i = 0; $i < count($tokenized); $i++) {
					$newTerm = '"';
					for ($j = 0; $j < count($tokenized); $j++) {
						if ($j != $i) {
							$newTerm .= $tokenized[$j] . ' ';
						}
					}
					$newTerm = trim($newTerm) . '"';
					if (strlen($singleWordRemoval) > 0) {
						$singleWordRemoval .= ' OR ';
					}
					$singleWordRemoval .= $newTerm;
				}
			}
			$values['single_word_removal'] = $singleWordRemoval;
			//Create localized call number
			$noWildCardLookFor = str_replace('*', '', $noTrailingPunctuation);
			$noWildCardLookFor = str_replace('?', '', $noWildCardLookFor);
			$values['localized_callnumber'] = str_replace([
				'"',
				':',
				'/',
			], ' ', $noWildCardLookFor);
			$values['text_left'] = str_replace([
				'"',
				':',
				'/',
			], ' ', $noWildCardLookFor);
			$values['text_left_word'] = $values['text_left'] . ' ';
		} else {
			// If we're skipping tokenization, we just want to pass $lookfor through
			// unmodified (it's probably an advanced search that won't benefit from
			// tokenization).	We'll just set all possible values to the same thing,
			// except that we'll try to do the "one phrase" in quotes if possible.
			$cleanedQuery = str_replace('“', '"', $lookfor);
			$cleanedQuery = preg_replace('%([-+!(){}\][^~&?/\\\\])%', '\\\\$1', $cleanedQuery);
            // Fix for ordinal numbers
            $cleanedQuery = preg_replace("/([0-9])([a-zA-Z])/", "$1 $2", $cleanedQuery);
			if (strlen($cleanedQuery) > 0 && $cleanedQuery[0] == '(') {
				$onephrase = $cleanedQuery;
			} else {
				$onephrase = strstr($lookfor, '"') ? $cleanedQuery : '"' . $cleanedQuery . '"';
			}
			$values = [
				'exact' => $onephrase,
				'onephrase' => $onephrase,
				'and' => $cleanedQuery,
				'or' => $cleanedQuery,
				'proximal' => $cleanedQuery,
				'single_word' => $cleanedQuery,
				'single_word_removal' => $onephrase,
				'exact_quoted' => $onephrase,
				'localized_callnumber' => str_replace([
					'"',
					':',
					'/',
				], ' ', $cleanedQuery),
				'text_left' => str_replace([
					'"',
					':',
					'/',
				], ' ', $cleanedQuery),
			];
			$values['text_left_word'] = $values['text_left'] . ' ';
		}

		// Apply custom munge operations if necessary
		if (is_array($custom) && $basic) {
			foreach ($custom as $mungeName => $mungeOps) {
				$values[$mungeName] = $lookfor;

				// Skip munging if tokenization is disabled.
				foreach ($mungeOps as $operation) {
					switch ($operation[0]) {
						case 'exact':
							$values[$mungeName] = '"' . $values[$mungeName] . '"';
							break;
						case 'append':
							$values[$mungeName] .= $operation[1];
							break;
						case 'lowercase':
							$values[$mungeName] = strtolower($values[$mungeName]);
							break;
						case 'preg_replace':
							$values[$mungeName] = preg_replace($operation[1], $operation[2], $values[$mungeName]);
							break;
						case 'uppercase':
							$values[$mungeName] = strtoupper($values[$mungeName]);
							break;
					}
				}
			}
		}
		return $values;
	}

	/**
	 * Given a field name and search string, expand this into the necessary Lucene
	 * query to perform the specified search on the specified field(s).
	 *
	 * @access    public            Has to be public since it can be called as part of a preg replace statement
	 * @param string $field The YAML search spec field name to search
	 * @param string $lookfor The string to search for in the field
	 * @param bool $tokenize Should we tokenize $lookfor or pass it through?
	 * @return    string                            The query
	 */
	public function _buildQueryComponent($field, $lookfor, $tokenize = true) {
		// Load the YAML search specifications:
		$ss = $this->_getSearchSpecs($field);

		if ($field == 'AllFields') {
			$field = 'Keyword';
		}

		// If we received a field spec that wasn't defined in the YAML file,
		// let's try simply passing it along to Solr.
		if ($ss === false) {
			$allFields = $this->loadValidFields();
			if (in_array($field, $allFields)) {
				return $field . ':(' . $lookfor . ')';
			}
			$dynamicFields = $this->loadDynamicFields();
			global $solrScope;
			foreach ($dynamicFields as $dynamicField) {
				if ($dynamicField . $solrScope == $field) {
					return $field . ':(' . $lookfor . ')';
				}
			}
			//Not a search by field
			return '"' . $field . ':' . $lookfor . '"';
		}

		// Munge the user query in a few different ways:
		$customMunge = isset($ss['CustomMunge']) ? $ss['CustomMunge'] : null;
		$values = $this->_buildMungeValues($lookfor, $customMunge, $tokenize);

		// Apply the $searchSpecs property to the data:
		$baseQuery = $this->_applySearchSpecs($ss['QueryFields'], $values);

		// Apply filter query if applicable:
		if (isset($ss['FilterQuery'])) {
			return "({$baseQuery}) AND ({$ss['FilterQuery']})";
		}

		return "($baseQuery)";
	}

	/**
	 * Given a field name and search string known to contain advanced features
	 * (as identified by isAdvanced()), expand this into the necessary Lucene
	 * query to perform the specified search on the specified field(s).
	 *
	 * @access    private
	 * @param string $handler The handler for the search
	 * @param string $query The string to search for in the field
	 * @return    string                            The query
	 */
	private function _buildAdvancedQuery($handler, $query) {
		// Special case -- if the user wants all records but the current handler
		// has a filter query, apply the filter query:
		if (trim($query) == '*:*') {
			$ss = $this->_getSearchSpecs($handler);
			if (isset($ss['FilterQuery'])) {
				return $ss['FilterQuery'];
			}
		}

		// Strip out any colons that are NOT part of a field specification:
		$query = preg_replace('/(\:\s+|\s+:)/', ' ', $query);

		// If the query already includes field specifications, we can't easily
		// apply it to other fields through our defined handlers, so we'll leave
		// it as-is:
		if (strstr($query, ':')) {
			return $query;
		}

		// Convert empty queries to return all values in a field:
		if (empty($query)) {
			$query = '[* TO *]';
		}

		// If the query ends in a question mark, the user may not really intend to
		// use the question mark as a wildcard -- let's account for that possibility
//		if (substr($query, -1) == '?') {
//			$query = "({$query}) OR (" . substr($query, 0, strlen($query) - 1) . ")";
//		}

		// We're now ready to use the regular YAML query handler but with the
		// $tokenize parameter set to false so that we leave the advanced query
		// features unmolested.
		return $this->_buildQueryComponent($handler, $query, false);
	}

	/* Build Query string from search parameters
	 *
	 * @access	public
	 * @param	 array	 $search		  An array of search parameters
	 * @param	 boolean $forDisplay  Whether or not the query is being built for display purposes
	 * @throws	AspenError
	 * @static
	 * @return	string							The query
	 */
	function buildQuery($search, $forDisplay = false) {
		$groups = [];
		$excludes = [];
		$query = '';

		if (is_array($search)) {
			$lookfor = '';
			foreach ($search as $params) {
				//Check to see if need to break up a basic search into an advanced search
				$modifiedQuery = false;
				$that = $this;
				if (isset($params['lookfor']) && !$forDisplay) {
					$lookfor = preg_replace_callback('/([\\w-]+):([\\w\\d\\s"-]+?)\\s?(?<=\b)(AND|OR|AND NOT|OR NOT|\\)|$)(?=\b)/', function ($matches) use ($that) {
						$field = $matches[1];
						$lookfor = $matches[2];
						$newQuery = $that->_buildQueryComponent($field, $lookfor);
						return $newQuery . $matches[3];
					}, $params['lookfor']);
					require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
					$lookfor = StringUtils::removeTrailingPunctuation($lookfor);
					$modifiedQuery = $lookfor != $params['lookfor'];
				}
				if ($modifiedQuery) {
					//This is an advanced search
					$query = $lookfor;
				} else {
					// Advanced Search
					if (isset($params['group'])) {
						$thisGroup = [];
						// Process each search group
						foreach ($params['group'] as $group) {
							// Build this group individually as a basic search
							if (strpos($group['lookfor'], ' ') > 0) {
								$group['lookfor'] = '(' . $group['lookfor'] . ')';
							}
							if ($group['field'] == 'AllFields') {
								$group['field'] = 'Keyword';
							}
							$thisGroup[] = $this->buildQuery([$group]);
						}
						// Is this an exclusion (NOT) group or a normal group?
						if ($params['group'][0]['bool'] == 'NOT') {
							$excludes[] = join(" OR ", $thisGroup);
						} else {
							$groups[] = join(" " . $params['group'][0]['bool'] . " ", $thisGroup);
						}
					}

					// Basic Search
					if (isset($params['lookfor']) && $params['lookfor'] != '') {
						// Clean and validate input
						$lookfor = $this->validateInput($params['lookfor']);

						// Force boolean operators to uppercase if we are in a case-insensitive
						// mode:
						if (!$this->caseSensitiveBooleans) {
							$lookfor = SolrUtils::capitalizeBooleans($lookfor);
						}

						if (isset($params['field']) && ($params['field'] != '')) {
							if ($this->isAdvanced($lookfor)) {
								$query .= $this->_buildAdvancedQuery($params['field'], $lookfor);
							} else {
								$query .= $this->_buildQueryComponent($params['field'], $lookfor);
							}
						} else {
							$query .= $lookfor;
						}
					}
				}
			}
		}

		// Put our advanced search together
		if (count($groups) > 0) {
			$query = "(" . join(") " . $search[0]['join'] . " (", $groups) . ")";
		}
		// and concatenate exclusion after that
		if (count($excludes) > 0) {
			$query .= " NOT ((" . join(") OR (", $excludes) . "))";
		}

		// Ensure we have a valid query to this point
		if (!isset($query) || $query == '') {
			$query = '*:*';
		}

		return $query;
	}

	/**
	 * Normalize a sort option.
	 *
	 * @param string $sort The sort option.
	 *
	 * @return string            The normalized sort value.
	 * @access private
	 */
	protected function _normalizeSort($sort) {
		// Break apart sort into field name and sort direction (note error
		// suppression to prevent notice when direction is left blank):
		$sort = trim($sort);
		if (str_contains($sort, ' ')) {
			@list($sortField, $sortDirection) = explode(' ', $sort);
		}else{
			$sortField = $sort;
			$sortDirection = 'asc';
		}

		// Default sort order (may be overridden by switch below):
		$defaultSortDirection = 'asc';

		// Normalize the sort direction to either "asc" or "desc":
		$sortDirection = strtolower(trim($sortDirection));
		if ($sortDirection != 'desc' && $sortDirection != 'asc') {
			$sortDirection = $defaultSortDirection;
		}

		return $sortField . ' ' . $sortDirection;
	}

	function disableScoping() {
		$this->scopingDisabled = true;
	}

	function enableScoping() {
		$this->scopingDisabled = false;
	}

	function isScopingEnabled() {
		$scopingEnabled = false;
		if (!$this->scopingDisabled) {
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			if (isset($searchLocation) && $searchLocation->useScope) {
				$scopingEnabled = true;
			} elseif (isset($searchLibrary) && $searchLibrary->useScope) {
				$scopingEnabled = true;
			}
		}

		return $scopingEnabled;
	}

	/**
	 * Execute a search.
	 *
	 * @param string $query The XQuery script in binary encoding.
	 * @param string $handler The Query Handler to use (null for default)
	 * @param array $filter The fields and values to filter results on
	 * @param int $start The record to start with
	 * @param int $limit The amount of records to return
	 * @param array $facet An array of faceting options
	 * @param string $spell Phrase to spell check
	 * @param string $dictionary Spell check dictionary to use
	 * @param string $sort Field name to use for sorting
	 * @param string $fields A list of fields to be returned
	 * @param string $method Method to use for sending request (GET/POST)
	 * @param bool $returnSolrError If Solr reports a syntax error,
	 *                                                                            should we fail outright (false) or
	 *                                                                            treat it as an empty result set with
	 *                                                                            an error key set (true)?
	 * @access    public
	 * @return    array                             An array of query results
	 * @throws    AspenError
	 */
	function search($query, $handler = null, $filter = null, $start = 0, $limit = 20, $facet = null, $spell = '', $dictionary = null, $sort = null, $fields = null, $method = 'POST', $returnSolrError = false) {
		global $timer;
		global $configArray;
		global $solrScope;
		// Query String Parameters
		$options = [
			'q' => $query,
			'q.op' => 'AND',
			'rows' => $limit,
			'start' => $start,
			'indent' => 'yes',
		];

		// Add Sorting
		if ($sort && !empty($sort)) {
			// There may be multiple sort options (ranked, with tie-breakers);
			// process each individually, then assemble them back together again:
			$sortParts = explode(',', $sort);
			for ($x = 0; $x < count($sortParts); $x++) {
				$sortParts[$x] = $this->_normalizeSort($sortParts[$x]);
			}
			$options['sort'] = implode(',', $sortParts);
		}

		//Convert from old AllFields Search to Keyword search
		if ($handler == 'AllFields') {
			$handler = 'Keyword';
		}

		//Check to see if we need to automatically convert to a proper case only (no stemming search)
		//We will do this whenever all or part of a string is surrounded by quotes.
		if (is_array($query)) {
			echo("Invalid query " . print_r($query, true));
		}

		// Determine which handler to use
		if (!$this->isAdvanced($query)) {
			//Remove extraneous colons to make sure that the query isn't treated as a field spec.
			$ss = is_null($handler) ? null : $this->_getSearchSpecs($handler);
			// Is this a Dismax search?
			if (isset($ss['DismaxFields'])) {
				// Specify the fields to do a Dismax search on:
				$options['qf'] = implode(' ', $ss['DismaxFields']);

				// Specify the default dismax search handler so we can use any
				// global settings defined by the user:
				$options['qt'] = 'dismax';

				// Load any custom Dismax parameters from the YAML search spec file:
				if (isset($ss['DismaxParams']) && is_array($ss['DismaxParams'])) {
					foreach ($ss['DismaxParams'] as $current) {
						$options[$current[0]] = $current[1];
					}
				}

				// Apply search-specific filters if necessary:
				if (isset($ss['FilterQuery'])) {
					if (is_array($filter)) {
						$filter[] = $ss['FilterQuery'];
					} else {
						$filter = [$ss['FilterQuery']];
					}
				}
			} else {
				// Not DisMax... but do we need to format the query based on
				// a setting in the YAML search specs?	If $ss is an array
				// at this point, it indicates that we found YAML details.
				if (is_array($ss)) {
					$options['q'] = $this->_buildQueryComponent($handler, $query);
				} elseif (!empty($handler)) {
					$options['q'] = "({$handler}:{$query})";
				}
			}
		} else {
			// Force boolean operators to uppercase if we are in a case-insensitive
			// mode:
			if (!$this->caseSensitiveBooleans) {
				$query = SolrUtils::capitalizeBooleans($query);
			}

			// Process advanced search -- if a handler was specified, let's see
			// if we can adapt the search to work with the appropriate fields.
			if (!empty($handler)) {
				$options['q'] = $this->_buildAdvancedQuery($handler, $query);
			}
		}
		$timer->logTime("build query in Solr");

		// Limit Fields
		if ($fields) {
			$options['fl'] = $fields;
		} else {
			// This should be an explicit list
			$options['fl'] = '*,score';
		}
		if ($this->debug) {
			$options['fl'] = $options['fl'] . ',explain';
		}

		//unset($options['qt']); //Force the query to never use dismax handling
		$searchLibrary = Library::getSearchLibrary($this->searchSource);
		//Boost items owned at our location
		$searchLocation = Location::getSearchLocation($this->searchSource);

		//Apply automatic boosting for queries
		$boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation, $query, $handler);
		if (!empty($boostFactors)) {
			if (isset($options['qt']) && $options['qt'] == 'dismax') {
				$options['bf'] = "sum(" . implode(',', $boostFactors) . ")";
			} else {
				$baseQuery = $options['q'];
				//Boost items in our system
				if (count($boostFactors) > 0) {
					$boost = "sum(" . implode(',', $boostFactors) . ")";
				} else {
					$boost = '';
				}
				if (empty($boost)) {
					$options['q'] = $baseQuery;
				} else {
					$options['q'] = "{!boost b=$boost} $baseQuery";
				}
				//echo ("Advanced Query " . $options['q']);
			}

			$timer->logTime("apply boosting");

		}
		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);

		if ($filter != null && $scopingFilters != null) {
			if (!is_array($filter)) {
				$filter = [$filter];
			}

			$filters = array_merge($filter, $scopingFilters);
		} elseif ($filter == null) {
			$filters = $scopingFilters;
		} else {
			$filters = $filter;
		}


		// Build Facet Options
		if ($facet && !empty($facet['field']) && $configArray['Index']['enableFacets']) {
			$options['facet'] = 'true';
			$options['facet.mincount'] = 1;
			$options['facet.method'] = 'fcs';
			$options['facet.threads'] = 25;
			$options['facet.limit'] = (isset($facet['limit'])) ? $facet['limit'] : null;

			unset($facet['limit']);
			if (isset($facet['field']) && is_array($facet['field']) && in_array('date_added', $facet['field'])) {
				$options['facet.date'] = 'date_added';
				$options['facet.date.end'] = 'NOW';
				$options['facet.date.start'] = 'NOW-1YEAR';
				$options['facet.date.gap'] = '+1WEEK';
				foreach ($facet['field'] as $key => $value) {
					if ($value == 'date_added') {
						unset($facet['field'][$key]);
						break;
					}
				}
			}

			if (isset($facet['field'])) {
				foreach ($facet['field'] as $facetField => $facetInfo) {
					$options['facet.field'][] = $facetInfo;
				}
			} else {
				$options['facet.field'] = null;
			}

			//unset($facet['field']);
			$options['facet.sort'] = (isset($facet['sort'])) ? $facet['sort'] : 'count';
			unset($facet['sort']);
			if (isset($facet['offset'])) {
				$options['facet.offset'] = $facet['offset'];
				unset($facet['offset']);
			}
			if (isset($facet['limit'])) {
				$options['facet.limit'] = $facet['limit'];
				unset($facet['limit']);
			}

			foreach ($facet as $param => $value) {
				if ($param != 'additionalOptions' && $param != 'field') {
					$options[$param] = $value;
				}
			}
		}

		if (isset($facet['additionalOptions'])) {
			$options = array_merge($options, $facet['additionalOptions']);
		}

		$timer->logTime("build facet options");

		// Build Filter Query
		if (is_array($filters) && count($filters)) {
			$options['fq'] = $filters;
		}

		// Enable Spell Checking
		if ($spell != '') {
			$options['spellcheck'] = 'true';
			$options['spellcheck.q'] = $spell;
//			if ($dictionary != null) {
//				$options['spellcheck.dictionary'] = $dictionary;
//			}
			$options['spellcheck.extendedResults'] = 'true';
			$options['spellcheck.count'] = 5;
			$options['spellcheck.onlyMorePopular'] = 'true';
			$options['spellcheck.maxResultsForSuggest'] = 5;
			$options['spellcheck.alternativeTermCount'] = 5;
			$options['spellcheck.collate'] = 'true';
			$options['spellcheck.collateParam.q.op'] = 'AND';
			$options['spellcheck.collateParam.mm'] = '100%';
			$options['spellcheck.maxCollations'] = 5;
			$options['spellcheck.collateExtendedResults'] = 'true';
			$options['spellcheck.maxCollationTries'] = 25;
			$options['spellcheck.accuracy'] = .5;
		}

		// Enable highlighting
		if ($this->_highlight) {
			$this->getHighlightOptions($fields, $options);
		}

		$solrSearchDebug = print_r($options, true) . "\n";
		if ($this->debugSolrQuery) {

			if ($filters) {
				$solrSearchDebug .= "\nFilterQuery: ";
				foreach ($filters as $filterItem) {
					$solrSearchDebug .= " $filterItem";
				}
			}

			if ($sort) {
				$solrSearchDebug .= "\nSort: " . $options['sort'];
			}

			if ($this->isPrimarySearch) {
				global $interface;
				$interface->assign('solrSearchDebug', $solrSearchDebug);
			}
		}
		if ($this->debugSolrQuery || $this->debug) {
			$options['debugQuery'] = 'on';
		}

		$timer->logTime("end solr setup");
		$result = $this->_select($method, $options, $returnSolrError);
		$timer->logTime("run select");
		if ($result instanceof AspenError && !$returnSolrError) {
			AspenError::raiseError($result);
		}

		return $result;
	}


	/**
	 * Get filters based on scoping for the search
	 * @param Library $searchLibrary
	 * @param Location $searchLocation
	 * @return array
	 */
	public function getScopingFilters($searchLibrary, $searchLocation) {
		return [];
	}

	/**
	 * Get scoping filters specifically formatted for Context Filter Query (CFQ) in search suggestions.
	 * This method extracts and formats only the language and edition_info filters from the full
	 * scoping filters, stripping the field prefixes for use in suggest.cfq parameter.
	 *
	 * @param ?Library $searchLibrary
	 * @param ?Location $searchLocation
	 * @return array Array of CFQ filter strings without field prefixes.
	 */
	protected function getScopingFiltersForCFQ(?Library $searchLibrary, ?Location $searchLocation): array {
		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);
		if (!is_array($scopingFilters) || empty($scopingFilters)) {
			return [];
		}

		$cfqParts = [];
		foreach ($scopingFilters as $filter) {
			if (str_starts_with($filter, 'edition_info:')) {
				$cfqParts[] = substr($filter, strlen('edition_info:'));
			} elseif (str_starts_with($filter, 'language:')) {
				$cfqParts[] = substr($filter, strlen('language:'));
			}
		}

		return $cfqParts;
	}

	/**
	 * Convert an array of fields into XML for saving to Solr.
	 *
	 * @param array $fields Array of fields to save
	 * @param boolean $waitFlush Whether or not to pass the waitFlush flag to the Solr add call
	 * @param boolean $delayedCommit Whether or not the commit should be delayed
	 * @return    string                            XML document ready for posting to Solr.
	 * @access    public
	 */
	public function getSaveXML($fields, $waitFlush = true, $delayedCommit = false) {
		global $logger;
		// Create XML Document
		$doc = new DOMDocument('1.0', 'UTF-8');

		// Create add node
		$node = $doc->createElement('add');
		/** @var DOMElement $addNode */
		$addNode = $doc->appendChild($node);
		if (!$waitFlush) {
			$addNode->setAttribute('waitFlush', 'false');
		}
		if ($delayedCommit) {
			//Make sure the update is committed within 60 seconds
			$addNode->setAttribute('commitWithin', 60000);
		} else {
			$addNode->setAttribute('commitWithin', 100);
		}

		// Create doc node
		$node = $doc->createElement('doc');
		$docNode = $addNode->appendChild($node);

		// Add fields to XML document
		foreach ($fields as $field => $value) {
			// Normalize current value to an array for convenience:
			if (!is_array($value)) {
				$value = [$value];
			}
			// Add all non-empty values of the current field to the XML:
			foreach ($value as $current) {
				if ($current != '') {
					$logger->log("Adding field $field", Logger::LOG_DEBUG);
					$logger->log("  value " . $current, Logger::LOG_DEBUG);
					$node = $doc->createElement('field');
					$node->setAttribute('name', $field);
					$node->appendChild(new DOMText($current));
					$docNode->appendChild($node);
				}
			}
		}

		return $doc->saveXML();
	}

	/**
	 * Save Record to Database
	 *
	 * @param string $xml XML document to post to Solr
	 * @return    mixed                             Boolean true on success or AspenError
	 * @access    public
	 */
	function saveRecord($xml) {
		if ($this->debugSolrQuery) {
			echo "<pre>Add Record</pre>\n";
		}

		$result = $this->_update($xml);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	/**
	 * Delete Record from Database
	 *
	 * @param string $id ID for record to delete
	 * @return    boolean
	 * @access    public
	 */
	function deleteRecord($id) {
		$body = "<delete><id>$id</id></delete>";

		$result = $this->_update($body);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	/**
	 * Delete All Records from Database
	 *
	 * @return    boolean
	 * @access    public
	 */
	function deleteAllRecords() {
		$body = "<delete><query>*:*</query></delete>";

		$result = $this->_update($body);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	/**
	 * Delete Record from Database
	 *
	 * @param string[] $idList Array of IDs for record to delete
	 * @return    boolean
	 * @access    public
	 */
	function deleteRecords($idList) {
		if ($this->debugSolrQuery) {
			echo "<pre>Delete Record List</pre>\n";
		}

		// Delete XML
		$body = '<delete>';
		foreach ($idList as $id) {
			$body .= "<id>$id</id>";
		}
		$body .= '</delete>';

		$result = $this->_update($body);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	/**
	 * Commit
	 *
	 * @return    string
	 * @access    public
	 */
	function commit() {
		if ($this->debugSolrQuery) {
			echo "<pre>Commit</pre>\n";
		}

		$body = '<commit softCommit="true" waitSearcher = "false"/>';

		$result = $this->_update($body);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	/**
	 * Optimize
	 *
	 * @return    string
	 * @access    public
	 */
	function optimize() {
		if ($this->debugSolrQuery) {
			echo "<pre>Optimize</pre>\n";
		}

		$body = '<optimize/>';

		$result = $this->_update($body);
		if ($result instanceof AspenError) {
			AspenError::raiseError($result);
		}

		return $result;
	}

	/**
	 * Submit REST Request to write data (protected wrapper to allow child classes
	 * to use this mechanism -- we should eventually phase out private _update).
	 *
	 * @param string $xml The command to execute
	 *
	 * @return mixed            Boolean true on success or AspenError
	 * @access protected
	 */
	protected function update($xml) {
		return $this->_update($xml);
	}

	/**
	 * Submit REST Request to read data
	 *
	 * @param string $method HTTP Method to use: GET, POST,
	 * @param array $params Array of parameters for the request
	 * @param bool $returnSolrError If Solr reports a syntax error,
	 *                                                                                    should we fail outright (false) or
	 *                                                                                    treat it as an empty result set with
	 *                                                                                    an error key set (true)?
	 * @return    array|AspenError                                                     The Solr response (or an AspenError)
	 * @access    protected
	 */
	protected function _select($method = 'GET', $params = [], $returnSolrError = false, $queryHandler = 'select') {
		global $timer;
		global $memoryWatcher;

		$memoryWatcher->logMemory('Start Solr Select');

		//$this->pingServer();

		$params['wt'] = 'json';
		$params['json.nl'] = 'arrarr';

		// Build query string for use with GET or POST:
		$query = [];
		if ($params) {
			foreach ($params as $function => $value) {
				if ($function != '') {
					if ($function === 'facet.field') {
						// If we stripped all values, skip the parameter:
						if (empty($value)) {
							continue;
						}
					}
					if (is_array($value)) {
						foreach ($value as $additional) {
							if ($additional instanceof FacetSetting) {
								$additional = urlencode($additional->facetName);
								$query[] = "$function=$additional";
							} elseif (is_string($additional)) {
								$additional = urlencode($additional);
								$query[] = "$function=$additional";
							}
						}
					} else {
						$value = urlencode($value);
						$query[] = "$function=$value";
					}
				}
			}
		}
		$queryString = implode('&', $query);

		$this->fullSearchUrl = $this->host . "/select/?" . $queryString;
		if ($this->debug || $this->debugSolrQuery) {
			$solrQueryDebug = "";
			if ($this->debugSolrQuery) {
				$solrQueryDebug .= "$method: ";
			}
			//Add debug parameter so we can see the explain section at the bottom.
			$this->debugSearchUrl = $this->host . "/select/?debugQuery=on&" . $queryString;

			if ($this->debugSolrQuery) {
				$solrQueryDebug .= "<a href='" . $this->debugSearchUrl . "' target='_blank'>$this->fullSearchUrl</a>";
			}

			if ($this->isPrimarySearch) {
				global $interface;
				if ($interface) {
					$interface->assign('solrLinkDebug', $solrQueryDebug);
				}
			}
		}

		// Send Request
		$timer->logTime("Prepare to send request to solr");
		$memoryWatcher->logMemory('Prepare to send request to solr');
		$result = false;
		if ($method == 'GET') {
			$result = $this->client->curlGetPage($this->host . "/$queryHandler/?$queryString");
		} elseif ($method == 'POST') {
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			if ($systemVariables && $systemVariables->solrConnectTimeout > 0) {
				$this->client->setConnectTimeout($systemVariables->solrConnectTimeout);
			}
			if ($systemVariables && $systemVariables->solrQueryTimeout > 0) {
				$this->client->setTimeout($systemVariables->solrQueryTimeout);
			}
			$result = $this->client->curlPostPage($this->host . "/$queryHandler/", $queryString);
		}

		$timer->logTime("Send data to solr for select $queryString");
		$memoryWatcher->logMemory("Send data to solr for select $queryString");

		return $this->_process($result, $returnSolrError, $queryString);
	}

	/**
	 * Submit REST Request to write data
	 *
	 * @param string $xml The command to execute
	 * @return    mixed                                     Boolean true on success or AspenError
	 * @access    private
	 */
	private function _update($xml) {
		global $timer;

		$this->pingServer();

		// Set up XML
		$this->client->addCustomHeaders(['Content-Type: text/xml; charset=utf-8'], false);

		// Send Request
		$result = $this->client->curlPostBodyData($this->host . "/update?commit=true", $xml, false);
		$responseCode = $this->client->getResponseCode();

		if ($responseCode == 500 || $responseCode == 400) {
			$timer->logTime("Send the update request");

			// Attempt to extract the most useful error message from the response:
			if (preg_match("/<title>(.*)<\/title>/msi", $result, $matches)) {
				$errorMsg = $matches[1];
			} else {
				$errorMsg = $result;
			}
			global $logger;
			$logger->log("Error updating document\r\n$xml", Logger::LOG_DEBUG);
			return new AspenError("Unexpected response -- " . $errorMsg);
		}

		return true;
	}

	/**
	 * Perform normalization and analysis of Solr return value.
	 *
	 * @param string $result The raw response from Solr
	 * @param bool $returnSolrError If Solr reports a syntax error,
	 *                                                                                    should we fail outright (false) or
	 *                                                                                    treat it as an empty result set with
	 *                                                                                    an error key set (true)?
	 * @param string $queryString The raw query that was sent
	 * @return    array                                                     The processed response from Solr
	 * @access    private
	 */
	private function _process($result, $returnSolrError = false, $queryString = null) {
		global $timer;
		global $memoryWatcher;
		// Catch errors from SOLR
		if (substr(trim($result), 0, 2) == '<h') {
			$errorMsg = substr($result, strpos($result, '<pre>'));
			$errorMsg = substr($errorMsg, strlen('<pre>'), strpos($result, "</pre>"));
			if ($returnSolrError) {
				return [
					'response' => [
						'numFound' => 0,
						'docs' => [],
					],
					'error' => $errorMsg,
				];
			} else {
				if ($this->debug) {
					$errorMessage = 'Unable to process query ' . urldecode($queryString);
				} else {
					$errorMessage = 'Unable to process query ';
				}
				AspenError::raiseError(new AspenError($errorMessage . '<br />' . 'Solr Returned: ' . $errorMsg));

			}
		}
		$memoryWatcher->logMemory('receive result from solr result is ' . strlen($result) . ' bytes long');
		$result = json_decode($result, true);
		$timer->logTime("receive result from solr and load from json data");
		$memoryWatcher->logMemory('load json for solr result');

		// Inject highlighting details into results if necessary:
		if (isset($result['highlighting'])) {
			foreach ($result['response']['docs'] as $key => $current) {
				if (isset($result['highlighting'][$current['id']])) {
					$result['response']['docs'][$key]['_highlighting'] = $result['highlighting'][$current['id']];
				}
			}
			// Remove highlighting section now that we have copied its contents:
			unset($result['highlighting']);
		}
		$timer->logTime("process highlighting");
		$memoryWatcher->logMemory('process highlighting');

		return $result;
	}

	/**
	 * Input Tokenizer
	 *
	 * Tokenize the user input based on spaces and quotes.    Then joins phrases
	 * together that have an AND, OR, NOT present.
	 *
	 * @param string $input User's input string
	 * @return    array                    Tokenized array
	 * @access    public
	 */
	public function tokenizeInput($input) {
		// Tokenize on spaces and quotes
		//preg_match_all('/"[^"]*"|[^ ]+/', $input, $words);
		preg_match_all('/"[^"]*"[~[0-9]+]*|"[^"]*"|[^ ]+/', $input, $words);
		$words = $words[0];

		// Join words with AND, OR, NOT
		$newWords = [];
		for ($i = 0; $i < count($words); $i++) {
			if (($words[$i] == 'OR') || ($words[$i] == 'AND') || ($words[$i] == 'NOT')) {
				if (count($newWords)) {
					$newWords[count($newWords) - 1] .= ' ' . trim($words[$i]) . ' ' . trim($words[$i + 1]);
					$i = $i + 1;
				}
			} else {
				// Previously we removed -- and any punctuation at this step, but this was causing problems
				if (strlen($words[$i]) > 0) {
					$newWords[] = trim($words[$i]);
				}
			}
		}

		return $newWords;
	}

	/**
	 * Input Validator
	 *
	 * Cleans the input based on the Lucene Syntax rules.
	 *
	 * @param string $input User's input string
	 * @return    bool                                Fixed input
	 * @access    public
	 */
	public function validateInput($input) {
		//Get rid of any spaces at the end
		$input = trim($input);

		// Normalize fancy quotes:
		$quotes = [
			"\xC2\xAB" => '"',
			// Â« (U+00AB) in UTF-8
			"\xC2\xBB" => '"',
			// Â» (U+00BB) in UTF-8
			"\xE2\x80\x98" => "'",
			// â€˜ (U+2018) in UTF-8
			"\xE2\x80\x99" => "'",
			// â€™ (U+2019) in UTF-8
			"\xE2\x80\x9A" => "'",
			// â€š (U+201A) in UTF-8
			"\xE2\x80\x9B" => "'",
			// â€› (U+201B) in UTF-8
			"\xE2\x80\x9C" => '"',
			// â€œ (U+201C) in UTF-8
			"\xE2\x80\x9D" => '"',
			// â€ (U+201D) in UTF-8
			"\xE2\x80\x9E" => '"',
			// â€ž (U+201E) in UTF-8
			"\xE2\x80\x9F" => '"',
			// â€Ÿ (U+201F) in UTF-8
			"\xE2\x80\xB9" => "'",
			// â€¹ (U+2039) in UTF-8
			"\xE2\x80\xBA" => "'",
			// â€º (U+203A) in UTF-8
		];
		$input = strtr($input, $quotes);

		// If the user has entered a lone BOOLEAN operator, convert it to lowercase
		// so it is treated as a word (otherwise it will trigger a fatal error):
		switch (trim($input)) {
			case 'OR':
				return 'or';
			case 'AND':
				return 'and';
			case 'NOT':
				return 'not';
		}

		// If the string consists only of control characters and/or BOOLEAN operators with no
		// other input, wipe it out entirely to prevent weird errors:
		$operators = [
			'AND',
			'OR',
			'NOT',
			'+',
			'-',
			'"',
			'&',
			'|',
		];
		if (trim(str_replace($operators, '', $input)) == '') {
			return '';
		}

		// Translate "all records" search into a blank string
		if (trim($input) == '*:*') {
			return '';
		}

		// Ensure wildcards are not at beginning of input
		if ((substr($input, 0, 1) == '*') || (substr($input, 0, 1) == '?')) {
			$input = substr($input, 1);
		}

		// Ensure all parens match
		$start = preg_match_all('/\(/', $input, $tmp);
		$end = preg_match_all('/\)/', $input, $tmp);
		if ($start != $end) {
			$input = str_replace([
				'(',
				')',
			], '', $input);
		}

		// Check to make sure we have an even number of quotes
		$numQuotes = preg_match_all('/"/', $input, $tmp);
		if ($numQuotes % 2 != 0) {
			//We have an uneven number of quotes, delete the last one
			$input = substr_replace($input, '', strrpos($input, '"'), 1);
		}

		// Ensure ^ is used properly
		$cnt = preg_match_all('/\^/', $input, $tmp);
		$matches = preg_match_all('/.+\^[0-9]/', $input, $tmp);

		if (($cnt) && ($cnt !== $matches)) {
			$input = str_replace('^', '', $input);
		}

		// Remove unwanted brackets/braces that are not part of range queries.
		// This is a bit of a shell game -- first we replace valid brackets and
		// braces with tokens that cannot possibly already be in the query (due
		// to ^ normalization in the step above).	Next, we remove all remaining
		// invalid brackets/braces, and transform our tokens back into valid ones.
		// Obviously, the order of the patterns/merges array is critically
		// important to get this right!!
		$patterns = [
			// STEP 1 -- escape valid brackets/braces
			'/\[([^\[\]\s]+\s+TO\s+[^\[\]\s]+)\]/',
			'/\{([^\{\}\s]+\s+TO\s+[^\{\}\s]+)\}/',
			// STEP 2 -- destroy remaining brackets/braces
			'/[\[\]\{\}]/',
			// STEP 3 -- unescape valid brackets/braces
			'/\^\^lbrack\^\^/',
			'/\^\^rbrack\^\^/',
			'/\^\^lbrace\^\^/',
			'/\^\^rbrace\^\^/',
		];
		$matches = [
			// STEP 1 -- escape valid brackets/braces
			'^^lbrack^^$1^^rbrack^^',
			'^^lbrace^^$1^^rbrace^^',
			// STEP 2 -- destroy remaining brackets/braces
			'',
			// STEP 3 -- unescape valid brackets/braces
			'[',
			']',
			'{',
			'}',
		];
		$input = preg_replace($patterns, $matches, $input);

		//Remove any exclamation marks that Solr will handle incorrectly.
		$input = str_replace('!', ' ', $input);

		//Remove any semi-colons that Solr will handle incorrectly.
		$input = str_replace(';', ' ', $input);

		//Remove slashes occur in the middle of a word that Solr will handle incorrectly.
		$input = preg_replace("/([0-9a-zA-Z])([\/])([0-9a-zA-Z])/", "$1 $3", $input);
		$input = preg_replace("/([0-9a-zA-Z])([\\\\])([0-9a-zA-Z])/", "$1 $3", $input);
		$input = str_replace('\\', '\\\\', $input);

		//$input = preg_replace('/\\\\(?![&:])/', ' ', $input);

		//Look for any colons that are not identifying fields

		//Remove spaces from start/end of string
		return trim($input);
	}

	public function isAdvanced($query) {
		// Check for various conditions that flag an advanced Lucene query:
		if ($query == '*:*') {
			return true;
		}

		// The following conditions do not apply to text inside quoted strings,
		// so let's just strip all quoted strings out of the query to simplify
		// detection.	We'll replace quoted phrases with a dummy keyword so quote
		// removal doesn't interfere with the field specifier check below.
		$query = preg_replace('/"[^"]*"/', 'quoted', $query);

		// Check for field specifiers:
		if (preg_match("/([^\(\s\:]+)\s?\:[^\s]/", $query, $matches)) {
			//Make sure the field is actually one of our fields
			$fieldName = $matches[1];
			$fields = $this->loadValidFields();
			if (in_array($fieldName, $fields)) {
				return true;
			}
			/*$searchSpecs = $this->_getSearchSpecs();
			if (array_key_exists($fieldName, $searchSpecs)){
				return true;
			}*/
		}

		// Check for parentheses and range operators:
		if (strstr($query, '(') && strstr($query, ')')) {
			return true;
		}
		$rangeReg = '/(\[.+\s+TO\s+.+\])|(\{.+\s+TO\s+.+\})/';
		if (preg_match($rangeReg, $query)) {
			return true;
		}

		// Build a regular expression to detect booleans -- AND/OR/NOT surrounded
		// by whitespace, or NOT leading the query and followed by whitespace.
		$boolReg = '/((\s+(AND|OR|NOT)\s+)|^NOT\s+)/';
		if (!$this->caseSensitiveBooleans) {
			$boolReg .= "i";
		}
		if (preg_match($boolReg, $query)) {
			return true;
		}

		// Check for wildcards and fuzzy matches:
		if (strstr($query, '*') || strstr($query, '?') || strstr($query, '~')) {
			return true;
		}

		// Check for boosts:
		if (preg_match('/[\^][0-9]+/', $query)) {
			return true;
		}

		return false;
	}

	/**
	 * Get the boolean clause limit.
	 *
	 * @return int
	 * @access public
	 */
	public function getBooleanClauseLimit() {
		global $configArray;

		// Use setting from config.ini if present, otherwise assume 1024:
		return isset($configArray['Index']['maxBooleanClauses']) ? $configArray['Index']['maxBooleanClauses'] : 1024;
	}

	public function setSearchSource($searchSource) {
		$this->searchSource = $searchSource;
	}

	public function getSearchSource() {
		return $this->searchSource;
	}

	function loadDynamicFields() {
		global $memCache;
		global $solrScope;
		$fields = $memCache->get("schema_dynamic_fields_{$solrScope}_{$this->index}");
		if (!$fields || $fields == 'FALSE' || isset($_REQUEST['reload'])) {
			global $configArray;
			$schemaUrl = $configArray['Index']['url'] . "/$this->index/admin/file?file=schema.xml&contentType=text/xml;charset=utf-8";
			$schema = simplexml_load_file($schemaUrl);
			$fields = [];
			foreach ($schema->fields->dynamicField as $field) {
				$fields[] = substr((string)$field['name'], 0, -1);
			}
			$memCache->set("schema_dynamic_fields_{$solrScope}_{$this->index}", $fields, 24 * 60 * 60);
		}
		return $fields;
	}

	private static $_validFields = [];

	function loadValidFields() {
		global $memCache;
		global $solrScope;
		if (isset($_REQUEST['allFields'])) {
			return ['*'];
		}
		$key = "{$solrScope}_{$this->index}";
		if (!isset(Solr::$_validFields[$key])) {
			//There are very large performance gains for caching this in memory since we need to do a remote call and file parse
			$fields = $memCache->get("schema_fields_$key");
			if (!$fields || isset($_REQUEST['reload'])) {
				$schemaUrl = $this->host . '/admin/file?file=schema.xml&contentType=text/xml;charset=utf-8';
				$schema = @simplexml_load_file($schemaUrl);
				if ($schema == null) {
					AspenError::raiseError("Solr is not currently running");
				}
				$fields = [];
				foreach ($schema->fields->field as $field) {
					//print_r($field);
					if ($field['stored'] == 'true' || $field['indexed'] == 'true') {
						$fields[] = (string)$field['name'];
					}
				}
				if ($solrScope) {
					foreach ($schema->fields->dynamicField as $field) {
						if ($field['name'] != 'custom_facet_*') {
							$fields[] = substr((string)$field['name'], 0, -1) . $solrScope;
						}else{
							for ($i = 1; $i <= 4; $i++) {
								$fields[] = substr((string)$field['name'], 0, -1) . $i;
							}
						}
					}
				}
				$memCache->set("schema_fields_$key", $fields, 24 * 60 * 60);
				Solr::$_validFields[$key] = $fields;
			} else {
				Solr::$_validFields[$key] = $fields;
			}
		}
		return Solr::$_validFields[$key];
	}

	function getIndex() {
		return $this->index;
	}

	protected function getHighlightOptions($fields, &$options) {
		$highlightFields = $fields;
		$options['hl'] = 'true';
		$options['hl.fl'] = $highlightFields;
		$options['hl.simple.pre'] = '{{{{START_HILITE}}}}';
		$options['hl.simple.post'] = '{{{{END_HILITE}}}}';
	}

	private static $stopWords = [
		"a",
		"an",
		"and",
		"are",
		"as",
		"at",
		"be",
		"but",
		"by",
		"for",
		"if",
		"in",
		"into",
		"is",
		"it",
		"no",
		"not",
		"of",
		"on",
		"or",
		"such",
		"that",
		"the",
		"their",
		"then",
		"there",
		"these",
		"they",
		"this",
		"to",
		"was",
		"will",
		"with",
	];

	/**
	 * @param string[] $tokenized
	 * @return string[]
	 */
	private function removeStopWords(array $tokenized): array {
		$tokenizedNoStopWords = [];
		foreach ($tokenized as $word) {
			if (!in_array($word, Solr::$stopWords)) {
				$tokenizedNoStopWords[] = $word;
			}
		}
		if (count($tokenizedNoStopWords) == 0) {
			return $tokenized;
		}
		return $tokenizedNoStopWords;
	}
}