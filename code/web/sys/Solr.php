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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
 *
 */
require_once ROOT_DIR . '/sys/IndexEngine.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';
require_once ROOT_DIR . '/sys/ConfigArray.php';
require_once ROOT_DIR . '/sys/SolrUtils.php';
require_once ROOT_DIR . '/sys/VuFindCache.php';

require_once 'XML/Unserializer.php';
require_once 'XML/Serializer.php';

/**
 * Solr HTTP Interface
 *
 * @version		 $Revision: 1.13 $
 * @author			Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @access			public
 */
class Solr implements IndexEngine {
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
	 * The HTTP_Request object used for REST transactions
	 * @var HTTP_Request $client
	 */
	public $client;

	/**
	 * The host to connect to
	 * @var string
	 */
	public $host;

	private $index;

	/**
	 * The status of the connection to Solr
	 * @var string
	 */
	public $status = false;

	/**
	 * An array of characters that are illegal in search strings
	 */
	private $illegal = array('!', ':', ';', '[', ']', '{', '}');

	/**
	 * The path to the YAML file specifying available search types:
	 */
	protected $searchSpecsFile = '../../conf/searchspecs.yaml';

	/**
	 * An array of search specs pulled from $searchSpecsFile (above)
	 *
	 * @var array
	 */
	private $_searchSpecs = false;

	/**
	 * Should boolean operators in the search string be treated as
	 * case-insensitive (false), or must they be ALL UPPERCASE (true)?
	 */
	private $caseSensitiveBooleans = true;

	/**
	 * Should range operators (i.e. [a TO b]) in the search string be treated as
	 * case-insensitive (false), or must they be ALL UPPERCASE (true)?	Note that
	 * making this setting case insensitive not only changes the word "TO" to
	 * uppercase but also inserts OR clauses to check for case insensitive matches
	 * against the edges of the range...	i.e. ([a TO b] OR [A TO B]).
	 */
	private $_caseSensitiveRanges = true;

	/**
	 * Selected shard settings.
	 */
	private $_solrShards = array();
	private $_solrShardsFieldsToStrip = array();

	/**
	 * Should we collect highlighting data?
	 */
	private $_highlight = false;

	/**
	 * How should we cache the search specs?
	 */
	private $_specCache = false;
	/**
	 * Flag to disable default scoping to show ILL book titles, etc.
	 */
	private $scopingDisabled = false;

	/** @var string  */
	private $searchSource = null;

	/**
	 * Constructor
	 *
	 * Sets up the SOAP Client
	 *
	 * @param	  string	$host			 The URL for the local Solr Server
	 * @param   string  $index      The name of the index
	 * @access	public
	 */
	function __construct($host, $index = '')
	{
		global $configArray;
		global $timer;

		// Set a default Solr index if none is provided to the constructor:
		if (empty($index)) {
			global $library;
			if ($library){
				$index = 'grouped';
			}else{
				$index = isset($configArray['Index']['default_core']) ? $configArray['Index']['default_core'] : "grouped";
			}

			$this->index = $index;
		}

		//Check for a more specific searchspecs file
		global $serverName;
		if (file_exists(ROOT_DIR . "/../../sites/$serverName/conf/searchspecs.yaml")){
			// Return the file path (note that all ini files are in the conf/ directory)
			$this->searchSpecsFile = ROOT_DIR . "/../../sites/$serverName/conf/searchspecs.yaml";
		}elseif(file_exists(ROOT_DIR . "/../../sites/default/conf/searchspecs.yaml")){
			// Return the file path (note that all ini files are in the conf/ directory)
			$this->searchSpecsFile = ROOT_DIR . "/../../sites/default/conf/searchspecs.yaml";
		}
		$timer->logTime("Load search specs");

		$this->host = $host . '/' . $index;

		// If we're still processing then solr is online
		$this->client = new Proxy_Request(null, array('useBrackets' => false));

		// Read in preferred boolean behavior:
		$searchSettings = getExtraConfigArray('searches');
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

		// Deal with field-stripping shard settings:
		if (isset($searchSettings['StripFields']) && is_array($searchSettings['StripFields'])) {
			$this->_solrShardsFieldsToStrip = $searchSettings['StripFields'];
		}

		// Deal with search spec cache setting:
		if (isset($searchSettings['Cache']['type'])) {
			$this->_specCache = $searchSettings['Cache']['type'];
		}

		if (isset($_SESSION['shards'])){
			$this->_loadShards($_SESSION['shards']);
		}

		$timer->logTime('Finish Solr Initialization');
	}

	public function __destruct()
	{
		$this->client->disconnect();
		$this->client = null;
	}

	private static $serversPinged = array();
	public function pingServer($failOnError = true){
		/** @var Memcache $memCache */
		global $memCache;
		global $timer;
		global $configArray;
		global $logger;
		$hostEscaped = preg_replace('[\W]', '_', $this->host);
		if (array_key_exists($this->host, Solr::$serversPinged)){
			//$logger->log("Pinging solr has already been done this page load", PEAR_LOG_DEBUG);
			return Solr::$serversPinged[$this->host];
		}
		if ($memCache){

			$pingDone = $memCache->get('solr_ping_' . $hostEscaped);
			if ($pingDone != null){
				//$logger->log("Not pinging solr {$this->host} because we have a cached ping $pingDone", PEAR_LOG_DEBUG);
				Solr::$serversPinged[$this->host] = $pingDone;
				return Solr::$serversPinged[$this->host];
			}else{
				$pingDone = false;
			}
		}else{
			$pingDone = false;
			//$logger->log("Pinging solr because memcache has not been initialized", PEAR_LOG_DEBUG);
		}

		if ($pingDone == false){

			//$logger->log("Pinging solr server {$this->host} $hostEscaped", PEAR_LOG_DEBUG);
			// Test to see solr is online
			$test_url = $this->host . "/admin/ping";
			$test_client = new Proxy_Request('', array('timeout' => 2, 'read_timeout' => 1));
			$test_client->setMethod(HTTP_REQUEST_METHOD_GET);
			$test_client->setURL($test_url);
			$result = $test_client->sendRequest();
			if (!PEAR_Singleton::isError($result)) {
				// Even if we get a response, make sure it's a 'good' one.
				if ($test_client->getResponseCode() != 200) {
					$pingResult = 'false';
					Solr::$serversPinged[$this->host] = false;
					if ($failOnError){
						PEAR_Singleton::raiseError('Solr index is offline.');
					}else{
						$logger->log("Ping of {$this->host} failed", PEAR_LOG_DEBUG);
						return false;
					}
				}else{
					$pingResult = 'true';
				}
			} else {
				$pingResult = 'false';
				Solr::$serversPinged[$this->host] = false;
				if ($failOnError){
					PEAR_Singleton::raiseError($result);
				}else{
					$logger->log("Ping of {$this->host} failed", PEAR_LOG_DEBUG);
					return false;
				}
			}
			if ($memCache){
				$memCache->set('solr_ping_' . $hostEscaped, $pingResult, 0, $configArray['Caching']['solr_ping']);
			}
			Solr::$serversPinged[$this->host] = $pingResult;
			$timer->logTime('Ping Solr instance ' . $this->host);
		}else{
			Solr::$serversPinged[$this->host] = true;
		}
		return Solr::$serversPinged[$this->host];
	}

	public function setDebugging($enableDebug, $enableSolrQueryDebugging) {
		$this->debug = $enableDebug;
		$this->debugSolrQuery = $enableDebug && $enableSolrQueryDebugging;
	}

	private function _loadShards($newShards){
		// Deal with session-based shard settings:
		$shards = array();
		global $configArray;
		foreach ($newShards as $current) {
			if (isset($configArray['IndexShards'][$current])) {
				$shards[$current] = $configArray['IndexShards'][$current];
			}
		}
		$this->setShards($shards);
	}

	/**
	 * Is this object configured with case-sensitive boolean operators?
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function hasCaseSensitiveBooleans()
	{
		return $this->caseSensitiveBooleans;
	}

	/**
	 * Is this object configured with case-sensitive range operators?
	 *
	 * @return boolean
	 * @access public
	 */
	public function hasCaseSensitiveRanges()
	{
		return $this->_caseSensitiveRanges;
	}

	/**
	 * Support method for _getSearchSpecs() -- load the specs from cache or disk.
	 *
	 * @return void
	 * @access private
	 */
	private function _loadSearchSpecs()
	{
		// Generate cache key:
		$key = md5(
			basename($this->searchSpecsFile) . '-' . filemtime($this->searchSpecsFile)
		);

		// Load cache manager:
		$cache = new VuFindCache($this->_specCache, 'searchspecs');

		// Generate data if not found in cache:
		if (!($results = $cache->load($key))) {
			$results = Horde_Yaml::load(
				file_get_contents($this->searchSpecsFile)
			);
			$cache->save($results, $key);
		}
		$this->_searchSpecs = $results;
	}

	/**
	 * Get the search specifications loaded from the specified YAML file.
	 *
	 * @param string $handler The named search to provide information about (set
	 * to null to get all search specifications)
	 *
	 * @return mixed Search specifications array if available, false if an invalid
	 * search is specified.
	 * @access	private
	 */
	private function _getSearchSpecs($handler = null)
	{
		// Only load specs once:
		if ($this->_searchSpecs === false) {
			$this->_loadSearchSpecs();
		}

		// Special case -- null $handler means we want all search specs.
		if (is_null($handler)) {
			return $this->_searchSpecs;
		}

		// Return specs on the named search if found (easiest, most common case).
		if (isset($this->_searchSpecs[$handler])) {
			return $this->_searchSpecs[$handler];
		}

		// Check for a case-insensitive match -- this provides backward
		// compatibility with different cases used in early VuFind versions
		// and allows greater tolerance of minor typos in config files.
		foreach ($this->_searchSpecs as $name => $specs) {
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
	 * @param	 string	$id				 The document to retrieve from Solr
	 * @param string $fieldsToReturn An optional list of fields to return separated by commas
	 * @access	public
	 * @throws	object							PEAR Error
	 * @return	string							The requested resource
	 */
	function getRecord($id, $fieldsToReturn = null)
	{
		/*if ($this->debugSolrQuery) {
			echo "<pre>Get Record: $id</pre>\n";
		}*/
		/** @var Memcache $memCache */
		global $memCache;
		global $configArray;
		global $solrScope;
		if (!$fieldsToReturn){
			$validFields = $this->_loadValidFields();
			$fieldsToReturn = implode(',', $validFields);
		}
		$record = $memCache->get("solr_record_{$id}_{$solrScope}_{$fieldsToReturn}");

		if ($record == false || isset($_REQUEST['reload'])){
			$this->pingServer();
			// Query String Parameters
			$options = array('ids' => "$id");
			$options['fl'] = $fieldsToReturn;
			$this->client->setMethod('GET');
			$this->client->setURL($this->host . "/get");
			$this->client->addRawQueryString(http_build_query($options));

			global $timer;
			$timer->logTime("Prepare to send get (ids) request to solr returning fields $fieldsToReturn");
			$result = $this->client->sendRequest();
			//$this->client->clearPostData();
			$timer->logTime("Send data to solr during getRecord $id $fieldsToReturn");

			if (PEAR_Singleton::isError($result)) {
				PEAR_Singleton::raiseError($result);
			}else{
				$result = $this->_process($this->client->getResponseBody());
			}

			if (isset($result['response']['docs'][0])){
				$record = $result['response']['docs'][0];
				$memCache->set("solr_record_{$id}_{$solrScope}_{$fieldsToReturn}", $record, 0, $configArray['Caching']['solr_record']);
			}else{
				//global $logger;
				//$logger->log("Unable to find record $id in Solr", PEAR_LOG_ERR);
				PEAR_Singleton::raiseError("Record not found $id");
			}
		}
		return $record;
	}

	function getRecordByBarcode($barcode){
		if ($this->debug) {
			echo "<pre>Get Record by Barcode: $barcode</pre>\n";
		}

		// Query String Parameters
		$options = array('q' => "barcode:\"$barcode\"", 'fl' => SearchObject_Solr::$fields);
		$result = $this->_select('GET', $options);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		if (isset($result['response']['docs'][0])){
			return $result['response']['docs'][0];
		}else{
			return null;
		}
	}

	function getRecordByIsbn($isbns, $fieldsToReturn = null){
		// Query String Parameters
		if ($fieldsToReturn == null){
			$fieldsToReturn = SearchObject_Solr::$fields;
		}
		$options = array('q' => 'isbn:' . implode(' OR ', $isbns), 'fl' => $fieldsToReturn);
		$result = $this->_select('GET', $options);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		if (isset($result['response']['docs'][0])){
			return $result['response']['docs'][0];
		}else{
			return null;
		}
	}

	/**
	 * Retrieves a document specified by the ID.
	 *
	 * @param	 array	$ids				 A list of document to retrieve from Solr
	 * @param string $fieldsToReturn An optional list of fields to return separated by commas
	 * @access	public
	 * @throws	object							PEAR Error
	 * @return	array							The requested resources
	 */
	function getRecords($ids, $fieldsToReturn = null)
	{
		if (count($ids) == 0){
			return array();
		}
		//Solr does not seem to be able to return more than 50 records at a time,
		//If we have more than 50 ids, we will ned to make multiple calls and
		//concatenate the results.
		$records = array();
		$startIndex = 0;
		$batchSize = 40;

		$this->pingServer();

		$lastBatch = false;
		while (true){
			$endIndex = $startIndex + $batchSize;
			if ($endIndex >= count($ids)){
				$lastBatch = true;
				$endIndex = count($ids);
				$batchSize = count($ids) - $startIndex;
			}
			$tmpIds = array_slice($ids, $startIndex, $batchSize);

			// Query String Parameters
			$idString = '';
			foreach ($tmpIds as $id){
				if (strlen($idString) > 0){
					$idString .= ',';
				}
				$idString .= $id;
			}
			$options = array('ids' => "$idString");
			$options['fl'] = $fieldsToReturn;

			$this->client->setMethod('GET');
			$this->client->setURL($this->host . "/get");
			$this->client->addRawQueryString(http_build_query($options));

			// Send Request
			global $timer;
			$timer->logTime("Prepare to send get (ids)  request to solr");
			$result = $this->client->sendRequest();
			//$this->client->clearPostData();
			$timer->logTime("Send data to solr for getRecords");

			if (PEAR_Singleton::isError($result)) {
				PEAR_Singleton::raiseError($result);
			}else{
				$result = $this->_process($this->client->getResponseBody());
			}
			foreach ($result['response']['docs'] as $record){
				$records[$record['id']] = $record;
			}
			if ($lastBatch){
				break;
			} else{
				$startIndex = $endIndex;
			}
		}
		//echo("Found " . count($records) . " records.	Should have found " . count($ids) . "\r\n<br/>");
		return $records;
	}

	function searchForRecordIds($ids){
		if (count($ids) == 0){
			return array();
		}
		// Query String Parameters
		$idString = '';
		foreach ($ids as $id){
			if (strlen($idString) > 0){
				$idString .= ' OR ';
			}
			$idString .= "id:\"$id\"";
		}
		$options = array('q' => $idString, 'rows' => count($ids), 'fl' => SearchObject_Solr::$fields);
		$result = $this->_select('GET', $options);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}
		return $result;
	}

	/**
	 * Get records similar to one record
	 * Uses MoreLikeThis Request Handler
	 *
	 * Uses SOLR MLT Query Handler
	 *
	 * @access	public
	 * @var     string  $id       The id to retrieve similar titles for
	 * @throws	object						PEAR Error
	 * @return	array							An array of query results
	 *
	 */
	function getMoreLikeThis($id)
	{
		// Query String Parameters
		$options = array('q' => "id:$id", 'qt' => 'morelikethis', 'fl' => SearchObject_Solr::$fields);
		$result = $this->_select('GET', $options);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Get records similar to one record
	 * Uses MoreLikeThis Request Handler
	 *
	 * Uses SOLR MLT Query Handler
	 *
	 * @access	public
	 * @var     string  $id             The id to retrieve similar titles for
	 * @var     array   $originalResult The original record we are getting similar titles for.
	 * @throws	object						PEAR Error
	 * @return	array							An array of query results
	 *
	 */
	function getMoreLikeThis2($id, $originalResult = null)
	{
		global $configArray;
		if ($originalResult == null){
			$originalResult = $this->getRecord($id, 'target_audience_full,target_audience_full,literary_form,language,isbn,upc');
		}
		// Query String Parameters
		$options = array('q' => "id:$id", 'qt' => 'morelikethis2', 'mlt.interestingTerms' => 'details', 'rows' => 25, 'fl' => SearchObject_Solr::$fields);
		if ($originalResult){
			$options['fq'] = array();
			if (isset($originalResult['target_audience_full'])){
				if (is_array($originalResult['target_audience_full'])){
					$filter = '';
					foreach ($originalResult['target_audience_full'] as $targetAudience){
						if ($targetAudience != 'Unknown'){
							if (strlen($filter) > 0){
								$filter .= ' OR ';
							}
							$filter .= 'target_audience_full:"' . $targetAudience . '"';
						}
					}
					if (strlen($filter) > 0){
						$options['fq'][] = "($filter)";
					}
				}else{
					$options['fq'][] = 'target_audience_full:"' . $originalResult['target_audience_full'] . '"';
				}
			}
			if (isset($originalResult['literary_form'])){
				if (is_array($originalResult['literary_form'])){
					$filter = '';
					foreach ($originalResult['literary_form'] as $literaryForm){
						if ($literaryForm != 'Not Coded'){
							if (strlen($filter) > 0){
								$filter .= ' OR ';
							}
							$filter .= 'literary_form:"' . $literaryForm . '"';
						}
					}
					if (strlen($filter) > 0){
						$options['fq'][] = "($filter)";
					}
				}else{
					$options['fq'][] = 'literary_form:"' . $originalResult['literary_form'] . '"';
				}
			}
			if (isset($originalResult['language'])){
				$options['fq'][] = 'language:"' . $originalResult['language'][0] . '"';
			}
			//Don't want to get other editions of the same work (that's a different query)
			if ($this->index != 'grouped'){
				if (isset($originalResult['isbn'])){
					if (is_array($originalResult['isbn'])){
						foreach($originalResult['isbn'] as $isbn){
							$options['fq'][] = '-isbn:' . ISBN::normalizeISBN($isbn);
						}
					}else{
						$options['fq'][] = '-isbn:' . ISBN::normalizeISBN($originalResult['isbn']);
					}
				}
				if (isset($originalResult['upc'])){
					if (is_array($originalResult['upc'])){
						foreach($originalResult['upc'] as $upc){
							$options['fq'][] = '-upc:' . ISBN::normalizeISBN($upc);
						}
					}else{
						$options['fq'][] = '-upc:' . ISBN::normalizeISBN($originalResult['upc']);
					}
				}
			}
		}

		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		if ($searchLibrary && $searchLocation){
			if ($searchLibrary->ilsCode == $searchLocation->code){
				$searchLocation = null;
			}
		}

		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);
		foreach ($scopingFilters as $filter){
			$options['fq'][] = $filter;
		}
		$boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation);
		if ($configArray['Index']['enableBoosting']){
			$options['bf'] = $boostFactors;
		}

		if (!empty($this->_solrShards) && is_array($this->_solrShards)) {
			$options['shards'] = implode(',',$this->_solrShards);
		}

		$result = $this->_select('GET', $options);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Get records similar to one record
	 * Uses MoreLikeThis Request Handler
	 *
	 * Uses SOLR MLT Query Handler
	 *
	 * @access	public
	 * @var     string[]  $ids     A list of ids to return data for
	 * @var     string[]  $notInterestedIds     A list of ids the user is not interested in
	 * @throws	object						PEAR Error
	 * @return	array							An array of query results
	 *
	 */
	function getMoreLikeThese($ids, $notInterestedIds)
	{
		global $configArray;
		// Query String Parameters
		$idString = implode(' OR ', $ids);
		$options = array('q' => "id:($idString)", 'qt' => 'morelikethese', 'mlt.interestingTerms' => 'details', 'rows' => 25);

		$searchLibrary = Library::getSearchLibrary();
		$searchLocation = Location::getSearchLocation();
		$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);

		$notInterestedString = implode(' OR ', $notInterestedIds);
		if (strlen($notInterestedString) > 0){
			$idString .= ' OR ' . $notInterestedString;
		}
		$options['fq'][] = "-id:($idString)";
		foreach ($scopingFilters as $filter){
			$options['fq'][] = $filter;
		}
		$boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation);
		if ($configArray['Index']['enableBoosting']){
			$options['bf'] = $boostFactors;
		}
		if (!empty($this->_solrShards) && is_array($this->_solrShards)) {
			$options['shards'] = implode(',',$this->_solrShards);
		}

		$options['rows'] = 30;

		// TODO: Limit Fields
		if ($this->debug && isset($fields)) {
			$options['fl'] = $fields;
		} else {
			// This should be an explicit list
			$options['fl'] = '*,score';
		}
		$result = $this->_select('GET', $options);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Get record data based on the provided field and phrase.
	 * Used for AJAX suggestions.
	 *
	 * @access	public
	 * @param	 string	$phrase		 The input phrase
	 * @param	 string	$field			The field to search on
	 * @param	 int		 $limit			The number of results to return
	 * @return	array	 An array of query results
	 */
	function getSuggestion($phrase, $field, $limit)
	{
		if (!strlen($phrase)) {
			return null;
		}

		// Ignore illegal characters
		$phrase = str_replace($this->illegal, '', $phrase);

		// Process Search
		$query = "$field:($phrase*)";
		$result = $this->search($query, null, null, 0, $limit, array('field' => $field, 'limit' => $limit));
		return $result['facet_counts']['facet_fields'][$field];
	}

	/**
	 * Get spelling suggestions based on input phrase.
	 *
	 * @access	public
	 * @param	 string	$phrase		 The input phrase
	 * @return	array	 An array of spelling suggestions
	 */
	function checkSpelling($phrase)
	{
		if ($this->debugSolrQuery) {
			echo "<pre>Spell Check: $phrase</pre>\n";
		}

		// Query String Parameters
		$options = array(
			'q'					=> $phrase,
			'rows'			 => 0,
			'start'			=> 1,
			'indent'		 => 'yes',
			'spellcheck' => 'true'
			);

			$result = $this->_select(HTTP_REQUEST_METHOD_GET, $options);
			if (PEAR_Singleton::isError($result)) {
				PEAR_Singleton::raiseError($result);
			}

			return $result;
	}

	/**
	 * applySearchSpecs -- internal method to build query string from search parameters
	 *
	 * @access	private
	 * @param	 array $structure					 the SearchSpecs-derived structure or substructure defining the search, derived from the yaml file
	 * @param	 array $values							the various values in an array with keys 'onephrase', 'and', 'or' (and perhaps others)
	 * @param  string $joiner
	 * @throws	object							PEAR Error
	 * @static
	 * @return	string							A search string suitable for adding to a query URL
	 */
	private function _applySearchSpecs($structure, $values, $joiner = "OR")
	{
		global $solrScope;
		$clauses = array();
		foreach ($structure as $field => $clauseArray) {
			if (is_numeric($field)) {
				// shift off the join string and weight
				$sw = array_shift($clauseArray);
				$internalJoin = ' ' . $sw[0] . ' ';
				// Build it up recursively
				$searchString = '(' .	$this->_applySearchSpecs($clauseArray, $values, $internalJoin) . ')';
				// ...and add a weight if we have one
				$weight = $sw[1];
				if(!is_null($weight) && $weight && $weight > 0) {
					$searchString .= '^' . $weight;
				}
				// push it onto the stack of clauses
				$clauses[] = $searchString;
			} else {
				if ($solrScope){
					if ($field == 'local_callnumber' || $field == 'local_callnumber_left' || $field == 'local_callnumber_exact'){
						$field .= '_' . $solrScope;
					}
				}

				// Otherwise, we've got a (list of) [munge, weight] pairs to deal with
				foreach ($clauseArray as $spec) {
					$fieldValue = $values[$spec[0]];

					if ($field == 'isbn'){
						if (!preg_match('/^((?:\sOR\s)?["(]?\d{9,13}X?[\s")]*)+$/', $fieldValue)){
							continue;
						}else{
							require_once(ROOT_DIR . '/sys/ISBN.php');
							$isbn = new ISBN($fieldValue);
							if ($isbn->isValid()){
								$isbn10 = $isbn->get10();
								$isbn13 = $isbn->get13();
								if ($isbn10 && $isbn13){
									$fieldValue = '(' . $isbn->get10() . ' OR ' . $isbn->get13() . ')';
								}
							}
						}
					}elseif($field == 'id'){
						if (!preg_match('/^"?(\d+|.[boi]\d+x?|[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12})"?$/i', $fieldValue)){
							continue;
						}
					}elseif($field == 'alternate_ids'){
						if (!preg_match('/^"?(\d+|.?[boi]\d+x?|[A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12}|MWT\d+|CARL\d+)"?$/i', $fieldValue)){
							continue;
						}
					}elseif($field == 'issn'){
						if (!preg_match('/^"?[\dXx-]+"?$/', $fieldValue)){
							continue;
						}
					}elseif($field == 'upc'){
						if (!preg_match('/^"?\d+"?$/', $fieldValue)){
							continue;
						}
					}

					// build a string like title:("one two")
					$searchString = $field . ':(' . $fieldValue . ')';
					//Check to make sure we don't already have this clause.  We will get the same clause if we have a single word and are doing different munges
					$okToAdd = true;
					foreach ($clauses as $clause){
						if (strpos($clause, $searchString) === 0){
							$okToAdd = false;
							break;
						}
					}
					if (!$okToAdd) continue;

					// Add the weight it we have one. Yes, I know, it's redundant code.
					$weight = $spec[1];
					if(!is_null($weight) && $weight && $weight > 0) {
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
	 * @param Location $searchLocation
	 * @return array
	 */
	public function getBoostFactors($searchLibrary, $searchLocation)
	{
		$boostFactors = array();

		global $language;
		if ($language == 'es') {
			$boostFactors[] = 'language_boost_es';
		} else {
			$boostFactors[] = 'language_boost';
		}

		$applyHoldingsBoost = true;
		if (isset($searchLibrary) && !is_null($searchLibrary)) {
			$applyHoldingsBoost = $searchLibrary->applyNumberOfHoldingsBoost;
		}
		if ($applyHoldingsBoost) {
			//$boostFactors[] = 'product(num_holdings,15,div(format_boost,50))';
			$boostFactors[] = 'product(sum(popularity,1),format_boost)';
		} else {
			$boostFactors[] = 'format_boost';
		}
		//Add rating as part of the ranking, normalize so ratings of less that 2.5 are below unrated entries.
		$boostFactors[] = 'sum(rating,1)';

		global $solrScope;
		if (isset($searchLibrary) && !is_null($searchLibrary) && $searchLibrary->boostByLibrary == 1) {
			if ($searchLibrary->additionalLocalBoostFactor > 1){
				$boostFactors[] = "sum(product(lib_boost_{$solrScope},{$searchLibrary->additionalLocalBoostFactor}),1)";
			}else{
				$boostFactors[] = "sum(lib_boost_{$solrScope},1)";
			}
		}else{
			//Handle boosting even if we are in a global scope
			global $library;
			if ($library && $library->boostByLibrary == 1){
				if ($library->additionalLocalBoostFactor > 1) {
					$boostFactors[] = "sum(product(lib_boost_{$solrScope},{$library->additionalLocalBoostFactor}),1)";
				}else{
					$boostFactors[] = "sum(lib_boost_{$solrScope},1)";
				}
			}
		}

		if (isset($searchLocation) && !is_null($searchLocation) && $searchLocation->boostByLocation == 1) {
			if ($searchLocation->boostByLocation > 1){
				$boostFactors[] = "sum(product(lib_boost_{$solrScope},{$searchLocation->additionalLocalBoostFactor}),1)";
			}else{
				$boostFactors[] = "sum(lib_boost_{$solrScope},1)";
			}

		}else{
			//Handle boosting even if we are in a global scope
			global $locationSingleton;
			$physicalLocation = $locationSingleton->getActiveLocation();
			if ($physicalLocation != null && $physicalLocation->boostByLocation ==1){
				if ($physicalLocation->additionalLocalBoostFactor > 1){
					$boostFactors[] = "sum(product(lib_boost_{$solrScope},{$physicalLocation->additionalLocalBoostFactor}),1)";
				}else{
					$boostFactors[] = "sum(lib_boost_{$solrScope},1)";
				}
			}
		}
		return $boostFactors;
	}

	/**
	 * Given a field name and search string, return an array containing munged
	 * versions of the search string for use in _applySearchSpecs().
	 *
	 * @access	private
	 * @param	 string	$lookfor		The string to search for in the field
	 * @param	 array	$custom		 Custom munge settings from YAML search specs
	 * @param  bool	  $basic	 Is $lookfor a basic (true) or advanced (false) query?
	 * @return	array							 Array for use as _applySearchSpecs() values param
	 */
	private function _buildMungeValues($lookfor, $custom = null, $basic = true)
	{
		if ($basic) {
			$cleanedQuery = str_replace(':', ' ', $lookfor);

			// Tokenize Input
			$tokenized = $this->tokenizeInput($cleanedQuery);

			// Create AND'd and OR'd queries
			$andQuery = implode(' AND ', $tokenized);
			$orQuery = implode(' OR ', $tokenized);

			// Build possible inputs for searching:
			$values = array();
			$values['onephrase'] = '"' . str_replace('"', '', implode(' ', $tokenized)) . '"';
			if (count($tokenized) > 1){
				$values['proximal'] = $values['onephrase'] . '~10';
			}else{
				if (!array_key_exists(0, $tokenized)){
					$values['proximal'] = '';
				}else{
					$values['proximal'] = $tokenized[0];
				}
			}

			$values['exact'] = str_replace(':', '\\:', $lookfor);
			$values['exact_quoted'] = '"' . $lookfor . '"';
			$values['and'] = $andQuery;
			$values['or'] = $orQuery;
			$singleWordRemoval = "";
			if (count($tokenized) <= 4){
				$singleWordRemoval = '"' . str_replace('"', '', implode(' ', $tokenized)) . '"';
			}else{
				for ($i = 0; $i < count($tokenized); $i++){
					$newTerm = '"';
					for ($j = 0; $j < count($tokenized); $j++){
						if ($j != $i){
							$newTerm .= $tokenized[$j] . ' ';
						}
					}
					$newTerm = trim($newTerm) . '"';
					if (strlen($singleWordRemoval) > 0){
						$singleWordRemoval .= ' OR ';
					}
					$singleWordRemoval .= $newTerm;
				}
			}
			$values['single_word_removal'] = $singleWordRemoval;
		} else {
			// If we're skipping tokenization, we just want to pass $lookfor through
			// unmodified (it's probably an advanced search that won't benefit from
			// tokenization).	We'll just set all possible values to the same thing,
			// except that we'll try to do the "one phrase" in quotes if possible.
			$onephrase = strstr($lookfor, '"') ? $lookfor : '"' . $lookfor . '"';
			$values = array(
					'exact' => $onephrase,
					'onephrase' => $onephrase,
					'and' => $lookfor,
					'or' => $lookfor,
					'proximal' => $lookfor,
					'single_word_removal' => $onephrase,
					'exact_quoted' => '"' . $lookfor . '"',
			);
		}

		//Create localized call number
		$noWildCardLookFor = str_replace('*', '', $lookfor);
		if (strpos($lookfor, '*') !== false){
			$noWildCardLookFor = str_replace('*', '', $lookfor);
		}
		$values['localized_callnumber'] = '"' . str_replace(array('"', ':', '/'), ' ', $noWildCardLookFor) . '"';

		// Apply custom munge operations if necessary
		if (is_array($custom) && $basic) {
			foreach($custom as $mungeName => $mungeOps) {
				$values[$mungeName] =  $lookfor;

				// Skip munging if tokenization is disabled.
				foreach($mungeOps as $operation) {
					switch($operation[0]) {
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
							$values[$mungeName] = preg_replace($operation[1],
							$operation[2], $values[$mungeName]);
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
	 * @access	public            Has to be public since it can be called as part of a preg replace statement
	 * @param	 string	$field			The YAML search spec field name to search
	 * @param	 string	$lookfor		The string to search for in the field
	 * @param	 bool		$tokenize	  Should we tokenize $lookfor or pass it through?
	 * @return	string							The query
	 */
	public function _buildQueryComponent($field, $lookfor, $tokenize = true)
	{
		// Load the YAML search specifications:
		$ss = $this->_getSearchSpecs($field);

		if ($field == 'AllFields'){
			$field = 'Keyword';
		}

		// If we received a field spec that wasn't defined in the YAML file,
		// let's try simply passing it along to Solr.
		if ($ss === false) {
			$allFields = $this->_loadValidFields();
			if (in_array($field, $allFields)){
				return $field . ':(' . $lookfor . ')';
			}
			$dynamicFields = $this->_loadDynamicFields();
			global $solrScope;
			foreach ($dynamicFields as $dynamicField){
				if ($dynamicField . $solrScope == $field){
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
	 * @access	private
	 * @param	 string	$handler			The handler for the search
	 * @param	 string	$query		    The string to search for in the field
	 * @return	string							The query
	 */
	private function _buildAdvancedQuery($handler, $query)
	{
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
		if (substr($query, -1) == '?') {
			$query = "({$query}) OR (" . substr($query, 0, strlen($query) - 1) . ")";
		}

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
	 * @throws	object							PEAR Error
	 * @static
	 * @return	string							The query
	 */
	function buildQuery($search, $forDisplay = false)
	{
		$groups	 = array();
		$excludes = array();
		$query = '';
		if (is_array($search)) {

			foreach ($search as $params) {
				//Check to see if need to break up a basic search into an advanced search
				$modifiedQuery = false;
				$that = $this;
				if (isset($params['lookfor']) && !$forDisplay){
					$lookfor = preg_replace_callback(
						'/([\\w-]+):([\\w\\d\\s"-]+?)\\s?(?<=\b)(AND|OR|AND NOT|OR NOT|\\)|$)(?=\b)/',
						function ($matches) use($that){
							$field = $matches[1];
							$lookfor = $matches[2];
							$newQuery = $that->_buildQueryComponent($field, $lookfor);
							return $newQuery . $matches[3];
						},
						$params['lookfor']
					);
					$modifiedQuery = $lookfor != $params['lookfor'];
				}
				if ($modifiedQuery){
					//This is an advanced search
					$query = $lookfor;
				}else{
					// Advanced Search
					if (isset($params['group'])) {
						$thisGroup = array();
						// Process each search group
						foreach ($params['group'] as $group) {
							// Build this group individually as a basic search
							if (strpos($group['lookfor'], ' ') > 0){
								$group['lookfor'] = '(' . $group['lookfor'] . ')';
							}
							if ($group['field'] == 'AllFields'){
								$group['field'] = 'Keyword';
							}
							$thisGroup[] = $this->buildQuery(array($group));
						}
						// Is this an exclusion (NOT) group or a normal group?
						if ($params['group'][0]['bool'] == 'NOT') {
							$excludes[] = join(" OR ", $thisGroup);
						} else {
							$groups[] = join(" ".$params['group'][0]['bool']." ", $thisGroup);
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
							/*if ($forDisplay &&
									isset($params['index']) &&
									$params['index'] != 'Keyword' &&
									!strpos($lookfor, $params['index']) === 0) {

								$query = $params['index'] . ':' . $lookfor;
							} else {*/
								$query .= $lookfor;
							//}
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
		if (!isset($query) || $query	== '') {
			$query = '*:*';
		}

		return $query;
	}

	/**
	 * Normalize a sort option.
	 *
	 * @param string $sort The sort option.
	 *
	 * @return string			The normalized sort value.
	 * @access private
	 */
	private function _normalizeSort($sort)
	{
		// Break apart sort into field name and sort direction (note error
		// suppression to prevent notice when direction is left blank):
		$sort = trim($sort);
		@list($sortField, $sortDirection) = explode(' ', $sort);

		// Default sort order (may be overridden by switch below):
		$defaultSortDirection = 'asc';

		// Translate special sort values into appropriate Solr fields:
		switch ($sortField) {
			case 'year':
			case 'publishDate':
				$sortField = 'publishDateSort';
				$defaultSortDirection = 'desc';
				break;
			case 'author':
				$sortField = 'authorStr asc, title_sort';
				break;
			case 'title':
				$sortField = 'title_sort asc, authorStr';
				break;
			case 'callnumber_sort':
				$searchLibrary = Library::getSearchLibrary($this->searchSource);
				if ($searchLibrary != null){
					$sortField = 'callnumber_sort_' . $searchLibrary->subdomain;
				}

				break;
		}

		// Normalize sort direction to either "asc" or "desc":
		$sortDirection = strtolower(trim($sortDirection));
		if ($sortDirection != 'desc' && $sortDirection != 'asc') {
			$sortDirection = $defaultSortDirection;
		}

		return $sortField . ' ' . $sortDirection;
	}

	function disableScoping(){
		$this->scopingDisabled = true;
		global $configArray;
		if (isset($configArray['ShardPreferences']['defaultChecked']) && !empty($configArray['ShardPreferences']['defaultChecked']) ) {
			$checkedShards = $configArray['ShardPreferences']['defaultChecked'];
			$shards = is_array($checkedShards) ? $checkedShards : array($checkedShards);
		} else {
			// If no default is configured, use all shards...
			if (isset($configArray['IndexShards'])){
				$shards = array_keys($configArray['IndexShards']);
			}
		}
		if (isset($shards)){
			$this->_loadShards($shards);
		}
	}

	function enableScoping(){
		$this->scopingDisabled = false;
		global $configArray;
		if (isset($configArray['ShardPreferences']['defaultChecked']) && !empty($configArray['ShardPreferences']['defaultChecked']) ) {
			$checkedShards = $configArray['ShardPreferences']['defaultChecked'];
			$shards = is_array($checkedShards) ? $checkedShards : array($checkedShards);
		} else {
			// If no default is configured, use all shards...
			if (isset($configArray['IndexShards'])){
				$shards = array_keys($configArray['IndexShards']);
			}
		}
		if (isset($shards)){
			$this->_loadShards($shards);
		}
	}

	function isScopingEnabled(){
		$scopingEnabled = false;
		if (!$this->scopingDisabled){
			$searchLibrary = Library::getSearchLibrary();
			$searchLocation = Location::getSearchLocation();
			if (isset($searchLocation) && $searchLocation->useScope){
				$scopingEnabled = true;
			}else if (isset($searchLibrary) && $searchLibrary->useScope){
				$scopingEnabled = true;
			}
		}

		return $scopingEnabled;
	}

	/**
	 * Execute a search.
	 *
	 * @param	 string	$query			The XQuery script in binary encoding.
	 * @param	 string	$handler		The Query Handler to use (null for default)
	 * @param	 array	$filter		 The fields and values to filter results on
	 * @param	 int	  $start			The record to start with
	 * @param	 int	  $limit			The amount of records to return
	 * @param	 array	$facet			An array of faceting options
	 * @param	 string	$spell			Phrase to spell check
	 * @param	 string	$dictionary Spell check dictionary to use
	 * @param	 string	$sort			 Field name to use for sorting
	 * @param	 string	$fields		 A list of fields to be returned
	 * @param	 string	$method		 Method to use for sending request (GET/POST)
	 * @param	 bool		$returnSolrError		If Solr reports a syntax error,
	 *																			should we fail outright (false) or
	 *																			treat it as an empty result set with
	 *																			an error key set (true)?
	 * @access	public
	 * @throws	object							PEAR Error
	 * @return	array							 An array of query results
	 */
	function search($query, $handler = null, $filter = null, $start = 0,
	$limit = 20, $facet = null, $spell = '', $dictionary = null,
	$sort = null, $fields = null,
	$method = HTTP_REQUEST_METHOD_POST, $returnSolrError = false)
	{
		global $timer;
		global $configArray;
		// Query String Parameters
		$options = array('q' => $query, 'rows' => $limit, 'start' => $start, 'indent' => 'yes');

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
		if ($handler == 'AllFields'){
			$handler = 'Keyword';
		}

		//Check to see if we need to automatically convert to a proper case only (no stemming search)
		//We will do this whenever all or part of a string is surrounded by quotes.
		if (is_array($query)){
			echo("Invalid query " . print_r($query, true));
		}
		if (preg_match('/\\".+?\\"/',$query)){
			if ($handler == 'Keyword'){
				$handler = 'KeywordProper';
			}else if ($handler == 'Author'){
				$handler = 'AuthorProper';
			}else if ($handler == 'Subject'){
				$handler = 'SubjectProper';
			}else if ($handler == 'AllFields'){
				$handler = 'KeywordProper';
			}else if ($handler == 'Title'){
				$handler = 'TitleProper';
			}else if ($handler == 'Title'){
				$handler = 'TitleProper';
			}else if ($handler == 'IslandoraKeyword'){
				$handler = 'IslandoraKeywordProper';
			}else if ($handler == 'IslandoraSubject'){
				$handler = 'IslandoraSubjectProper';
			}
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
				if (isset($ss['DismaxParams']) &&
				is_array($ss['DismaxParams'])) {
					foreach($ss['DismaxParams'] as $current) {
						$options[$current[0]] = $current[1];
					}
				}

				// Apply search-specific filters if necessary:
				if (isset($ss['FilterQuery'])) {
					if (is_array($filter)) {
						$filter[] = $ss['FilterQuery'];
					} else {
						$filter = array($ss['FilterQuery']);
					}
				}
			} else {
				// Not DisMax... but do we need to format the query based on
				// a setting in the YAML search specs?	If $ss is an array
				// at this point, it indicates that we found YAML details.
				if (is_array($ss)) {
					$options['q'] = $this->_buildQueryComponent($handler, $query);
				} else if (!empty($handler)) {
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
		$timer->logTime("build query");

		// Limit Fields
		if ($fields) {
			$options['fl'] = $fields;
		} else {
			// This should be an explicit list
			$options['fl'] = '*,score';
		}
		if ($this->debug){
			$options['fl'] = $options['fl'] . ',explain';
		}

		if (is_object($this->searchSource)){
			$defaultFilters = preg_split('/\r\n/', $this->searchSource->defaultFilter);
			foreach ($defaultFilters as $tmpFilter){
				$filter[] = $tmpFilter;
			}
		}

		//Apply automatic boosting (only to biblio and econtent queries)
		if (preg_match('/.*(grouped).*/i', $this->host)){
			//unset($options['qt']); //Force the query to never use dismax handling
			$searchLibrary = Library::getSearchLibrary($this->searchSource);
			//Boost items owned at our location
			$searchLocation = Location::getSearchLocation($this->searchSource);

			$boostFactors = $this->getBoostFactors($searchLibrary, $searchLocation);

			if (isset($options['qt']) && $options['qt'] == 'dismax'){
				//Boost by number of holdings
				if (count($boostFactors) > 0 && $configArray['Index']['enableBoosting']){
					$options['bf'] = "sum(" . implode(',', $boostFactors) . ")";
				}
				//print ($options['bq']);
			}else{
				$baseQuery = $options['q'];
				//Boost items in our system
				if (count($boostFactors) > 0){
					$boost = "sum(" . implode(',', $boostFactors) . ")";
				}else{
					$boost = '';
				}
				if (empty($boost) || !$configArray['Index']['enableBoosting']){
					$options['q'] = $baseQuery;
				}else{
					$options['q'] = "{!boost b=$boost} $baseQuery";
				}
				//echo ("Advanced Query " . $options['q']);
			}

			$timer->logTime("apply boosting");

			$scopingFilters = $this->getScopingFilters($searchLibrary, $searchLocation);

			$timer->logTime("apply filters based on location");
		}else{
			//Non book search (genealogy)
			$scopingFilters = array();
		}
		if ($filter != null && $scopingFilters != null){
			if (!is_array($filter)){
				$filter = array($filter);
			}
			//Check the filters to make sure they are for the correct scope
			$validFields = $this->_loadValidFields();
			$dynamicFields = $this->_loadDynamicFields();
			global $solrScope;
			$validFilters = array();
			foreach ($filter as $id => $filterTerm){
				list($fieldName, $term) = explode(":", $filterTerm, 2);
				if (!in_array($fieldName, $validFields)){
					//Special handling for availability_by_format
					if (preg_match("/^availability_by_format_([^_]+)_[\\w_]+$/", $fieldName)) {
						//This is a valid field
						$validFilters[$id] = $filterTerm;
					}elseif (preg_match("/^available_at_by_format_([^_]+)_[\\w_]+$/", $fieldName)){
						//This is a valid field
						$validFilters[$id] = $filterTerm;
					}else{
						//Field doesn't exist, check to see if it is a dynamic field
						//Where we can replace the scope with the current scope
						foreach ($dynamicFields as $dynamicField){
							if (preg_match("/^{$dynamicField}[^_]+$/", $fieldName)){
								//This is a dynamic field with the wrong scope
								$validFilters[$id] = $dynamicField . $solrScope . ":" . $term;
								break;
							}
						}
					}
				}else{
					$validFilters[$id] = $filterTerm;
				}
			}
			$filters = array_merge($validFilters, $scopingFilters);
		}else if ($filter == null){
			$filters = $scopingFilters;
		}else{
			$filters = $filter;
		}



		// Build Facet Options
		if ($facet && !empty($facet['field']) && $configArray['Index']['enableFacets']) {
			$options['facet'] = 'true';
			$options['facet.mincount'] = 1;
			$options['facet.method'] = 'fcs';
			$options['facet.threads'] = 25;
			$options['facet.limit'] = (isset($facet['limit'])) ? $facet['limit'] : null;

			//Determine which fields should be treated as enums
			global $solrScope;
			if (preg_match('/.*(grouped).*/i', $this->host)) {
				$options["f.target_audience_full.facet.method"] = 'enum';
				$options["f.target_audience.facet.method"] = 'enum';
				$options["f.literary_form_full.facet.method"] = 'enum';
				$options["f.literary_form.facet.method"] = 'enum';
				$options["f.literary_form.econtent_device"] = 'enum';
				$options["f.literary_form.lexile_code"] = 'enum';
				$options["f.literary_form.mpaa_rating"] = 'enum';
				$options["f.literary_form.rating_facet"] = 'enum';
				$options["f.format_category_{$solrScope}.rating_facet"] = 'enum';
				$options["f.format_{$solrScope}.rating_facet"] = 'enum';
				$options["f.availability_toggle_{$solrScope}.rating_facet"] = 'enum';
				$options["f.local_time_since_added_{$solrScope}.rating_facet"] = 'enum';
				$options["f.owning_library_{$solrScope}.rating_facet"] = 'enum';
				$options["f.owning_location_{$solrScope}.rating_facet"] = 'enum';
			}

			unset($facet['limit']);
			if (isset($facet['field']) && is_array($facet['field']) && in_array('date_added', $facet['field'])){
				$options['facet.date'] = 'date_added';
				$options['facet.date.end'] = 'NOW';
				$options['facet.date.start'] = 'NOW-1YEAR';
				$options['facet.date.gap'] = '+1WEEK';
				foreach ($facet['field'] as $key => $value){
					if ($value == 'date_added'){
						unset($facet['field'][$key]);
						break;
					}
				}
			}



			if (isset($facet['field'])){
				$options['facet.field'] = $facet['field'];
				if ($options['facet.field'] && is_array($options['facet.field'])){
					foreach($options['facet.field'] as $key => $facetName){
						if (strpos($facetName, 'availability_toggle') === 0 || strpos($facetName, 'availability_by_format') === 0){
							$options['facet.field'][$key] = '{!ex=avail}' . $facetName;
							$options["f.{$facetName}.facet.missing"] = 'true';
						}
						//Update facets for grouped core
					}
				}
			}else{
				$options['facet.field'] = null;
			}

			unset($facet['field']);
			$options['facet.prefix'] = (isset($facet['prefix'])) ? $facet['prefix'] : null;
			unset($facet['prefix']);
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
			if (preg_match('/.*(grouped).*/i', $this->host)) {
				if (isset($searchLibrary) && $searchLibrary->showAvailableAtAnyLocation) {
					$options['f.available_at.facet.missing'] = 'true';
				}
			}

			foreach($facet as $param => $value) {
				if ($param != 'additionalOptions'){
					$options[$param] = $value;
				}
			}
		}

		if (isset($facet['additionalOptions'])){
			$options = array_merge($options, $facet['additionalOptions']);
		}

		$timer->logTime("build facet options");

		//Check to see if there are filters we want to show all values for
		if (isset($filters) && is_array($filters)){
			foreach ($filters as $key => $value){
				if (strpos($value, 'availability_toggle') === 0 || strpos($value, 'availability_by_format') === 0){
					$filters[$key] = '{!tag=avail}' . $value;
				}
			}
		}

		// Build Filter Query
		if (is_array($filters) && count($filters)) {
			$options['fq'] = $filters;
		}

		// Enable Spell Checking
		if ($spell != '') {
			$options['spellcheck'] = 'true';
			$options['spellcheck.q'] = $spell;
			if ($dictionary != null) {
				$options['spellcheck.dictionary'] = $dictionary;
			}
		}

		// Enable highlighting
		if ($this->_highlight) {
			global $solrScope;
			$highlightFields = $fields . ",table_of_contents";
			$highlightFields = str_replace(",related_record_ids_$solrScope", '', $highlightFields);
			$highlightFields = str_replace(",related_items_$solrScope", '', $highlightFields);
			$highlightFields = str_replace(",format_$solrScope", '', $highlightFields);
			$highlightFields = str_replace(",format_category_$solrScope", '', $highlightFields);
			$options['hl'] = 'true';
			$options['hl.fl'] = $highlightFields;
			$options['hl.simple.pre'] = '{{{{START_HILITE}}}}';
			$options['hl.simple.post'] = '{{{{END_HILITE}}}}';
			$options['f.display_description.hl.fragsize'] = 50000;
			$options['f.title_display.hl.fragsize'] = 1000;
			$options['f.title_full.hl.fragsize'] = 1000;
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

			if ($this->isPrimarySearch){
				global $interface;
				$interface->assign('solrSearchDebug', $solrSearchDebug);
			}
		}
		if ($this->debugSolrQuery || $this->debug){
			$options['debugQuery'] = 'on';
		}

		$timer->logTime("end solr setup");
		$result = $this->_select($method, $options, $returnSolrError);
		$timer->logTime("run select");
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}


	/**
	 * Get filters based on scoping for the search
	 * @param Library $searchLibrary
	 * @param Location $searchLocation
	 * @return array
	 */
	public function getScopingFilters($searchLibrary, $searchLocation){
		global $solrScope;

		$filter = array();

		//Simplify detecting which works are relevant to our scope
		if (!$solrScope){
			if (isset($searchLocation)){
				$filter[] = "scope_has_related_records:{$searchLocation->code}";
			}elseif(isset($searchLibrary)){
				$filter[] = "scope_has_related_records:{$searchLibrary->subdomain}";
			}
		}else{
			$filter[] = "scope_has_related_records:$solrScope";
		}

		//*************************
		//Marmot overrides for filtering based on library system and location
		//Only include titles that the user has access to based on pType
		/*$pType = 0;
		$owningSystem = '';
		$owningLibrary = '';
		$canUseDefaultPType = !$this->scopingDisabled;

		if ($user){
			$pType = $user->patronType;
		}elseif (isset($searchLocation) && $searchLocation->defaultPType > 0 && $canUseDefaultPType){
			$pType = $searchLocation->defaultPType;
		}
		if ($pType == 0 && isset($searchLibrary)){
			//We always want to restrict by pType even if we aren't scoping to just the library
			//holdings since patron's don't want to see things they can't see.
			if (strlen($searchLibrary->pTypes) > 0){
				$pType = str_replace(',', ' OR ', $searchLibrary->pTypes);
			}else if ($searchLibrary->defaultPType > 0){
				$pType = $searchLibrary->defaultPType;
			}
		}

		if (isset($searchLocation)){
			if (strlen($searchLocation->facetLabel) == 0){
				$owningLibrary = $searchLocation->displayName;
			}else{
				$owningLibrary = $searchLocation->facetLabel;
			}
		}
		if (isset($searchLibrary)){
			if (strlen($searchLibrary->facetLabel) == 0){
				$owningSystem = $searchLibrary->displayName;
			}else{
				$owningSystem = $searchLibrary->facetLabel;
			}
		}
		$buildingFacetName = 'owning_location';
		$institutionFacetName = 'owning_library';*/

		//This block makes sure that titles are usable by the current user.  It is always run if we have a reasonable idea
		//who is using the catalog. This enables "super scope" even if the user is doing a repeat search.
		/*if ($pType > 0 && $configArray['Index']['enableUsableByFilter'] == true){
			//First check usability.
			//It is usable if the title is usable by the ptypes in question OR it is owned by the current branch/ system
			$usableFilter = 'usable_by:('.$pType . ' OR all)';
			$owningBranchFilter = "";
			$usableEContentFilter = "";
			$onOrderFilter = "";
			if (strlen($owningLibrary) > 0){
				$owningBranchFilter .= " $buildingFacetName:\"$owningLibrary\"";
				$usableEContentFilter .= "$buildingFacetName:\"$owningLibrary Online\"";
				$onOrderFilter .= "$buildingFacetName:\"$owningLibrary On Order\"";
			}
			if (strlen($owningSystem) > 0){
				if (strlen($owningBranchFilter) > 0) $owningBranchFilter .= " OR ";
				if (strlen($usableEContentFilter) > 0) $usableEContentFilter .= " OR ";
				if (strlen($onOrderFilter) > 0) $onOrderFilter .= " OR ";
				$owningBranchFilter .= "$institutionFacetName:\"$owningSystem\"";
				$usableEContentFilter .= "$institutionFacetName:\"$owningSystem Online\"";
				$onOrderFilter .= "$institutionFacetName:\"$owningSystem On Order\"";
			}
			$homeLibrary = Library::getPatronHomeLibrary();
			if ($homeLibrary && $homeLibrary != $searchLibrary){
				if (strlen($owningBranchFilter) > 0) $owningBranchFilter .= " OR ";
				if (strlen($usableEContentFilter) > 0) $usableEContentFilter .= " OR ";
				if (strlen($onOrderFilter) > 0) $onOrderFilter .= " OR ";
				$homeLibraryFacet = $homeLibrary->facetLabel;
				$owningBranchFilter .= "$buildingFacetName:\"$homeLibraryFacet\"";
				$usableEContentFilter .= "$buildingFacetName:\"$homeLibraryFacet Online\"";
				$onOrderFilter .= "$buildingFacetName:\"$homeLibraryFacet On Order\"";
			}
			if (isset($searchLibrary) && $searchLibrary->enableOverdriveCollection){
				if (strlen($usableEContentFilter) > 0) $usableEContentFilter .= " OR ";
				$usableEContentFilter .= " $institutionFacetName:\"Shared Digital Collection\"";
			}
			if (strlen($owningBranchFilter)){
				$fullFilter = "($usableFilter OR $owningBranchFilter)";
			}else{
				$fullFilter = "($usableFilter)";
			}
			if (strlen($usableEContentFilter)){
				$fullFilter .= " OR $usableEContentFilter";
			}
			if (strlen($onOrderFilter)){
				$fullFilter .= " OR $onOrderFilter";
			}
			$filter[] = $fullFilter;
		}*/

		//This block checks whether or not the title is owned by
		if ($this->scopingDisabled == false){
			/*if (isset($searchLibrary)){
				if ($searchLibrary->restrictSearchByLibrary && $searchLibrary->enableOverdriveCollection){
					$filter[] = "($institutionFacetName:\"{$owningSystem}\"
							OR $institutionFacetName:\"Shared Digital Collection\"
							OR $institutionFacetName:\"Digital Collection\"
							OR $institutionFacetName:\"{$owningSystem} Online\"
							OR $institutionFacetName:\"{$owningSystem} On Order\"
							)";
				}else if ($searchLibrary->restrictSearchByLibrary){
					$filter[] = "$institutionFacetName:\"{$owningSystem}\"";
				}else if (!$searchLibrary->enableOverdriveCollection){
					//This doesn't work because it effectively removes anything with both OverDrive and Print titles
					//$filter[] = "!($institutionFacetName:\"Digital Collection\" OR $institutionFacetName:\"{$searchLibrary->facetLabel} Online\")";
				}
			}

			if ($searchLocation != null){
				if ($searchLocation->restrictSearchByLocation && $searchLocation->enableOverdriveCollection){
					$filter[] = "($buildingFacetName:\"{$owningLibrary}\"
							OR $buildingFacetName:\"Shared Digital Collection\"
							OR $buildingFacetName:\"Digital Collection\"
							OR $buildingFacetName:\"{$owningLibrary} Online\"
							OR $buildingFacetName:\"{$owningLibrary} On Order\"
							)";
				}else if ($searchLocation->restrictSearchByLocation){
					$filter[] = "($buildingFacetName:\"{$owningLibrary}\")";
				}else if (!$searchLocation->enableOverdriveCollection){
					//This doesn't work because it effectively removes anything with both OverDrive and Print titles
					//$filter[] = "!($buildingFacetName:\"Shared Digital Collection\" OR $buildingFacetName:\"Digital Collection\" OR $buildingFacetName:\"{$searchLibrary->facetLabel} Online\")";
				}
			}*/
		}

		$blacklistRecords = null;
		if (isset($searchLocation) && strlen($searchLocation->recordsToBlackList) > 0){
			$blacklistRecords = $searchLocation->recordsToBlackList;
		}
		if (isset($searchLibrary) && strlen($searchLibrary->recordsToBlackList) > 0){
			if (is_null($blacklistRecords)){
				$blacklistRecords = $searchLibrary->recordsToBlackList;
			}else{
				$blacklistRecords .= "\n" . $searchLibrary->recordsToBlackList;
			}
		}
		if (!is_null($blacklistRecords)){
			$recordsToBlacklist = preg_split('/\s|\r\n|\r|\n/s', $blacklistRecords);
			$blacklist = "NOT (";
			$numRecords = 0;
			foreach ($recordsToBlacklist as $curRecord){
				if (strlen($curRecord) > 0){
					$numRecords++;
					if ($numRecords > 1){
						$blacklist .= " OR ";
					}
					$blacklist .= "id:" . $curRecord;
				}
			}
			$blacklist .= ")";
			$filter[] = $blacklist;
		}

		//Process anything that the user is not interested in.
		/*require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		if ($user){
			$notInterested = new NotInterested();
			$notInterested->userId = $user->id;
			$notInterested->find();
			if ($notInterested->N > 0){
				$notInterestedFilter = " NOT(";
				$numRecords = 0;
				while ($notInterested->fetch()){
					$numRecords++;
					if ($numRecords > 1){
						$notInterestedFilter .= " OR ";
					}
					$notInterestedFilter .= "id:" . $notInterested->groupedRecordPermanentId;
				}
				$notInterestedFilter .= ")";
				$filter[] = $notInterestedFilter;
			}
		}*/

		return $filter;
	}

	/**
	 * Convert an array of fields into XML for saving to Solr.
	 *
	 * @param	  array	  $fields		      Array of fields to save
	 * @param   boolean $waitFlush      Whether or not to pass the waitFlush flag to the Solr add call
	 * @param   boolean $delayedCommit  Whether or not the commit should be delayed
	 * @return	string							XML document ready for posting to Solr.
	 * @access	public
	 */
	public function getSaveXML($fields, $waitFlush = true, $delayedCommit = false)
	{
		global $logger;
		// Create XML Document
		$doc = new DOMDocument('1.0', 'UTF-8');

		// Create add node
		$node = $doc->createElement('add');
		/** @var DOMElement $addNode */
		$addNode = $doc->appendChild($node);
		if (!$waitFlush){
			$addNode->setAttribute('waitFlush', 'false');
		}
		if ($delayedCommit){
			//Make sure the update is committed within 60 seconds
			$addNode->setAttribute('commitWithin', 60000);
		}else{
			$addNode->setAttribute('commitWithin', 100);
		}

		// Create doc node
		$node = $doc->createElement('doc');
		$docNode = $addNode->appendChild($node);

		// Add fields to XML document
		foreach ($fields as $field => $value) {
			// Normalize current value to an array for convenience:
			if (!is_array($value)) {
				$value = array($value);
			}
			// Add all non-empty values of the current field to the XML:
			foreach($value as $current) {
				if ($current != '') {
					$logger->log("Adding field $field", PEAR_LOG_DEBUG);
					$logger->log("  value " . $current, PEAR_LOG_DEBUG);
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
	 * @param	 string	$xml				XML document to post to Solr
	 * @return	mixed							 Boolean true on success or PEAR_Error
	 * @access	public
	 */
	function saveRecord($xml)
	{
		if ($this->debugSolrQuery) {
			echo "<pre>Add Record</pre>\n";
		}

		$result = $this->_update($xml);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Delete Record from Database
	 *
	 * @param	 string	$id				 ID for record to delete
	 * @return	boolean
	 * @access	public
	 */
	function deleteRecord($id)
	{
		if ($this->debugSolrQuery) {
			echo "<pre>Delete Record: $id</pre>\n";
		}

		$body = "<delete><id>$id</id></delete>";

		$result = $this->_update($body);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Delete Record from Database
	 *
	 * @param	 string[]	$idList		 Array of IDs for record to delete
	 * @return	boolean
	 * @access	public
	 */
	function deleteRecords($idList)
	{
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
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Commit
	 *
	 * @return	string
	 * @access	public
	 */
	function commit()
	{
		if ($this->debugSolrQuery) {
			echo "<pre>Commit</pre>\n";
		}

		$body = '<commit softCommit="true" waitSearcher = "false"/>';

		$result = $this->_update($body);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Optimize
	 *
	 * @return	string
	 * @access	public
	 */
	function optimize()
	{
		if ($this->debugSolrQuery) {
			echo "<pre>Optimize</pre>\n";
		}

		$body = '<optimize/>';

		$result = $this->_update($body);
		if (PEAR_Singleton::isError($result)) {
			PEAR_Singleton::raiseError($result);
		}

		return $result;
	}

	/**
	 * Set the shards for distributed search
	 *
	 * @param array $shards Name => URL array of shards
	 *
	 * @return void
	 * @access public
	 */
	public function setShards($shards)
	{
		$this->_solrShards = $shards;
	}

	/**
	 * Submit REST Request to write data (protected wrapper to allow child classes
	 * to use this mechanism -- we should eventually phase out private _update).
	 *
	 * @param string $xml The command to execute
	 *
	 * @return mixed			Boolean true on success or PEAR_Error
	 * @access protected
	 */
	protected function update($xml)
	{
		return $this->_update($xml);
	}

	/**
	 * Strip facet settings that are illegal due to shard settings.
	 *
	 * @param array $value Current facet.field setting
	 *
	 * @return array			 Filtered facet.field setting
	 * @access private
	 */
	private function _stripUnwantedFacets($value)
	{
		// Load the configuration of facets to strip and build a list of the ones
		// that currently apply:
		$facetConfig = getExtraConfigArray('facets');
		$badFacets = array();
		if (!empty($this->_solrShards) && is_array($this->_solrShards)
		&& isset($facetConfig['StripFacets'])
		&& is_array($facetConfig['StripFacets'])
		) {
			$shardNames = array_keys($this->_solrShards);
			foreach ($facetConfig['StripFacets'] as $indexName => $facets) {
				if (in_array($indexName, $shardNames) === true) {
					$badFacets = array_merge($badFacets, explode(",", $facets));
				}
			}
		}

		// No bad facets means no filtering necessary:
		if (empty($badFacets)) {
			return $value;
		}

		// Ensure that $value is an array:
		if (!is_array($value)) {
			$value = array($value);
		}

		// Rebuild the $value array, excluding all unwanted facets:
		$newValue = array();
		foreach ($value as $current) {
			if (!in_array($current, $badFacets)) {
				$newValue[] = $current;
			}
		}

		return $newValue;
	}

	/**
	 * Submit REST Request to read data
	 *
	 * @param	 string			$method						 HTTP Method to use: GET, POST,
	 * @param	 array			 $params						 Array of parameters for the request
	 * @param	 bool				$returnSolrError		If Solr reports a syntax error,
	 *																					should we fail outright (false) or
	 *																					treat it as an empty result set with
	 *																					an error key set (true)?
	 * @return	array|PEAR_Error													 The Solr response (or a PEAR error)
	 * @access	private
	 */
	private function _select($method = HTTP_REQUEST_METHOD_GET, $params = array(), $returnSolrError = false)
	{
		global $timer;
		global $memoryWatcher;

		$memoryWatcher->logMemory('Start Solr Select');

		$this->pingServer();

		$this->client->setMethod($method);
		$this->client->setURL($this->host . "/select/");

		$params['wt'] = 'json';
		$params['json.nl'] = 'arrarr';

		// Build query string for use with GET or POST:
		$query = array();
		if ($params) {
			foreach ($params as $function => $value) {
				if ($function != '') {
					// Strip custom FacetFields when sharding makes it necessary:
					if ($function === 'facet.field') {
						$value = $this->_stripUnwantedFacets($value);

						// If we stripped all values, skip the parameter:
						if (empty($value)) {
							continue;
						}
					}
					if(is_array($value)) {
						foreach ($value as $additional) {
							$additional = urlencode($additional);
							$query[] = "$function=$additional";
						}
					} else {
						$value = urlencode($value);
						$query[] = "$function=$value";
					}
				}
			}
		}
		// pass the shard parameter along to Solr if necessary:
		if (!empty($this->_solrShards) && is_array($this->_solrShards)) {
			$query[] = 'shards=' . urlencode(implode(',', $this->_solrShards));
		}
		$queryString = implode('&', $query);

		$fullSearchUrl = print_r($this->host . "/select/?" . $queryString, true);

		// Save to file for Jmeter
		//$write_result = file_put_contents(ROOT_DIR . '\solrQueries.csv', $fullSearchUrl."\n", FILE_APPEND);

		$this->fullSearchUrl = $this->host . "/select/?" . $queryString;
		if ($this->debug || $this->debugSolrQuery) {
			$solrQueryDebug = "";
			if ($this->debugSolrQuery) {
				$solrQueryDebug .= "$method: ";
			}
			//Add debug parameter so we can see the explain section at the bottom.
			$this->debugSearchUrl = $this->host . "/select/?debugQuery=on&" . $queryString;

			if ($this->debugSolrQuery) {
				$solrQueryDebug .=  "<a href='" . $this->debugSearchUrl . "' target='_blank'>$this->fullSearchUrl</a>";
			}

			if ($this->isPrimarySearch) {
				global $interface;
				if ($interface) {
					$interface->assign('solrLinkDebug', $solrQueryDebug);
				}
			}
		}

		if ($method == 'GET') {
			$this->client->addRawQueryString($queryString);
		} elseif ($method == 'POST') {
			$this->client->setBody($queryString);
		}

		// Send Request
		$timer->logTime("Prepare to send request to solr");
		$memoryWatcher->logMemory('Prepare to send request to solr');
		$result = $this->client->sendRequest();
		//$this->client->clearPostData();
		$timer->logTime("Send data to solr for select $queryString");
		$memoryWatcher->logMemory("Send data to solr for select $queryString");

		if (!PEAR_Singleton::isError($result)) {
			return $this->_process($this->client->getResponseBody(), $returnSolrError, $queryString);
		} else {
			return $result;
		}
	}

	/**
	 * Submit REST Request to write data
	 *
	 * @param	 string			$xml				The command to execute
	 * @return	mixed									 Boolean true on success or PEAR_Error
	 * @access	private
	 */
	private function _update($xml)
	{
		global $configArray;
		global $timer;

		$this->pingServer();

		$this->client->setMethod('POST');
		$this->client->setURL($this->host . "/update/");

		if ($this->debugSolrQuery) {
			echo "<pre>POST: ";
			print_r($this->host . "/update/");
			echo "XML:\n";
			print_r($xml);
			echo "</pre>\n";
		}

		// Set up XML
		$this->client->addHeader('Content-Type', 'text/xml; charset=utf-8');
		$this->client->addHeader('Content-Length', strlen($xml));
		$this->client->setBody($xml);

		// Send Request
		$result = $this->client->sendRequest();
		$responseCode = $this->client->getResponseCode();
		//$this->client->clearPostData();

		if ($responseCode == 500 || $responseCode == 400) {
			$detail = $this->client->getResponseBody();
			$timer->logTime("Send the update request");

			// Attempt to extract the most useful error message from the response:
			if (preg_match("/<title>(.*)<\/title>/msi", $detail, $matches)) {
				$errorMsg = $matches[1];
			} else {
				$errorMsg = $detail;
			}
			global $logger;
			$logger->log("Error updating document\r\n$xml", PEAR_LOG_DEBUG);
			return new PEAR_Error("Unexpected response -- " . $errorMsg);
		}elseif ($configArray['System']['debugSolr'] == true){
			$this->client->getResponseBody();
			$timer->logTime("Get response body");
			// Attempt to extract the most useful error message from the response:
			//print_r("Update Response:");
			//print_r($detail);
		}

		if (!PEAR_Singleton::isError($result)) {
			return true;
		} else {
			return $result;
		}
	}

	/**
	 * Perform normalization and analysis of Solr return value.
	 *
	 * @param	 array			 $result						 The raw response from Solr
	 * @param	 bool				$returnSolrError		If Solr reports a syntax error,
	 *																					should we fail outright (false) or
	 *																					treat it as an empty result set with
	 *																					an error key set (true)?
	 * @param string      $queryString        The raw query that was sent
	 * @return	array													 The processed response from Solr
	 * @access	private
	 */
	private function _process($result, $returnSolrError = false, $queryString = null)
	{
		global $timer;
		global $memoryWatcher;
		// Catch errors from SOLR
		if (substr(trim($result), 0, 2) == '<h') {
			$errorMsg = substr($result, strpos($result, '<pre>'));
			$errorMsg = substr($errorMsg, strlen('<pre>'), strpos($result, "</pre>"));
			if ($returnSolrError) {
				return array('response' => array('numfound' => 0, 'docs' => array()),
										'error' => $errorMsg);
			} else {
				if ($this->debug){
					$errorMessage = 'Unable to process query ' . urldecode($queryString);
				}else{
					$errorMessage = 'Unable to process query ';
				}
				PEAR_Singleton::raiseError(new PEAR_Error($errorMessage. '<br />' .
						'Solr Returned: ' . $errorMsg));

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
					$result['response']['docs'][$key]['_highlighting']
					= $result['highlighting'][$current['id']];
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
	 * Tokenizes the user input based on spaces and quotes.	Then joins phrases
	 * together that have an AND, OR, NOT present.
	 *
	 * @param	 string	$input			User's input string
	 * @return	array							 Tokenized array
	 * @access	public
	 */
	public function tokenizeInput($input)
	{
		// Tokenize on spaces and quotes
		//preg_match_all('/"[^"]*"|[^ ]+/', $input, $words);
		preg_match_all('/"[^"]*"[~[0-9]+]*|"[^"]*"|[^ ]+/', $input, $words);
		$words = $words[0];

		// Join words with AND, OR, NOT
		$newWords = array();
		for ($i=0; $i<count($words); $i++) {
			if (($words[$i] == 'OR') || ($words[$i] == 'AND') || ($words[$i] == 'NOT')) {
				if (count($newWords)) {
					$newWords[count($newWords)-1] .= ' ' . trim($words[$i]) . ' ' . trim($words[$i+1]);
					$i = $i+1;
				}
			} else {
				//If we are tokenizing, remove any punctuation
				$tmpWord = preg_replace('/[^\s\-\w.\'aàáâãåäæeèéêëiìíîïoòóôõöøuùúûü&]/', '', $words[$i]);
				if (strlen($tmpWord) > 0){
					$newWords[] = trim($tmpWord);
				}
			}
		}

		return $newWords;
	}

	/**
	 * Input Validater
	 *
	 * Cleans the input based on the Lucene Syntax rules.
	 *
	 * @param	 string	$input			User's input string
	 * @return	bool								Fixed input
	 * @access	public
	 */
	public function validateInput($input)
	{
		//Get rid of any spaces at the end
		$input = trim($input);

		// Normalize fancy quotes:
		$quotes = array(
						"\xC2\xAB"		 => '"', // Â« (U+00AB) in UTF-8
						"\xC2\xBB"		 => '"', // Â» (U+00BB) in UTF-8
						"\xE2\x80\x98" => "'", // â€˜ (U+2018) in UTF-8
						"\xE2\x80\x99" => "'", // â€™ (U+2019) in UTF-8
						"\xE2\x80\x9A" => "'", // â€š (U+201A) in UTF-8
						"\xE2\x80\x9B" => "'", // â€› (U+201B) in UTF-8
						"\xE2\x80\x9C" => '"', // â€œ (U+201C) in UTF-8
						"\xE2\x80\x9D" => '"', // â€ (U+201D) in UTF-8
						"\xE2\x80\x9E" => '"', // â€ž (U+201E) in UTF-8
						"\xE2\x80\x9F" => '"', // â€Ÿ (U+201F) in UTF-8
						"\xE2\x80\xB9" => "'", // â€¹ (U+2039) in UTF-8
						"\xE2\x80\xBA" => "'", // â€º (U+203A) in UTF-8
		);
		$input = strtr($input, $quotes);

		// If the user has entered a lone BOOLEAN operator, convert it to lowercase
		// so it is treated as a word (otherwise it will trigger a fatal error):
		switch(trim($input)) {
			case 'OR':
				return 'or';
			case 'AND':
				return 'and';
			case 'NOT':
				return 'not';
		}

		// If the string consists only of control characters and/or BOOLEANs with no
		// other input, wipe it out entirely to prevent weird errors:
		$operators = array('AND', 'OR', 'NOT', '+', '-', '"', '&', '|');
		if (trim(str_replace($operators, '', $input)) == '') {
			return '';
		}

		// Translate "all records" search into a blank string
		if (trim($input) == '*:*') {
			return '';
		}

		// Ensure wildcards are not at beginning of input
		if ((substr($input, 0, 1) == '*') ||
		(substr($input, 0, 1) == '?')) {
			$input = substr($input, 1);
		}

		// Ensure all parens match
		$start = preg_match_all('/\(/', $input, $tmp);
		$end = preg_match_all('/\)/', $input, $tmp);
		if ($start != $end) {
			$input = str_replace(array('(', ')'), '', $input);
		}

		// Check to make sure we have an even number of quotes
		$numQuotes = preg_match_all('/"/', $input, $tmp);
		if ($numQuotes % 2 != 0){
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
		$patterns = array(
		// STEP 1 -- escape valid brackets/braces
						'/\[([^\[\]\s]+\s+TO\s+[^\[\]\s]+)\]/',
						'/\{([^\{\}\s]+\s+TO\s+[^\{\}\s]+)\}/',
		// STEP 2 -- destroy remaining brackets/braces
						'/[\[\]\{\}]/',
		// STEP 3 -- unescape valid brackets/braces
						'/\^\^lbrack\^\^/', '/\^\^rbrack\^\^/',
						'/\^\^lbrace\^\^/', '/\^\^rbrace\^\^/');
		$matches = array(
		// STEP 1 -- escape valid brackets/braces
						'^^lbrack^^$1^^rbrack^^', '^^lbrace^^$1^^rbrace^^',
		// STEP 2 -- destroy remaining brackets/braces
						'',
		// STEP 3 -- unescape valid brackets/braces
						'[', ']', '{', '}');
		$input = preg_replace($patterns, $matches, $input);

		//Remove any exclamation marks that Solr will handle incorrectly.
		$input = str_replace('!', ' ', $input);

		//Remove any semi-colons that Solr will handle incorrectly.
		$input = str_replace(';', ' ', $input);

		//Remove any slashes that Solr will handle incorrectly.
		$input = str_replace('\\', ' ', $input);
		$input = str_replace('/', ' ', $input);
		//$input = preg_replace('/\\\\(?![&:])/', ' ', $input);

		//Look for any colons that are not identifying fields


		return $input;
	}

	public function isAdvanced($query)
	{
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
			$fields = $this->_loadValidFields();
			if (in_array($fieldName, $fields)){
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
	 * Obtain information from an alphabetic browse index.
	 *
	 * @param string $source					Name of index to search
	 * @param string $from						Starting point for browse results
	 * @param int		$page						Result page to return (starts at 0)
	 * @param int		$page_size			 Number of results to return on each page
	 * @param bool	 $returnSolrError Should we fail outright on syntax error
	 * (false) or treat it as an empty result set with an error key set (true)?
	 *
	 * @return array
	 * @access public
	 */
	public function alphabeticBrowse($source, $from, $page, $page_size = 20, $returnSolrError = false) {
		$this->pingServer();

		$this->client->setMethod('GET');
		$this->client->setURL($this->host . "/browse");

		$offset = $page * $page_size;

		$this->client->addQueryString('from', $from);
		$this->client->addQueryString('json.nl', 'arrarr');
		$this->client->addQueryString('offset', $offset);
		$this->client->addQueryString('rows', $page_size);
		$this->client->addQueryString('source', $source);
		$this->client->addQueryString('wt', 'json');

		$result = $this->client->sendRequest();

		if (!PEAR_Singleton::isError($result)) {
			return $this->_process(
			$this->client->getResponseBody(), $returnSolrError);
		} else {
			return $result;
		}
	}

	/**
	 * Convert a terms array (where every even entry is a term and every odd entry
	 * is a count) into an associate array of terms => counts.
	 *
	 * @param array $in Input array
	 *
	 * @return array		Processed array
	 * @access private
	 */
	private function _processTerms($in)
	{
		$out = array();

		for ($i = 0; $i < count($in); $i += 2) {
			$out[$in[$i]] = $in[$i + 1];
		}

		return $out;
	}

	/**
	 * Get the boolean clause limit.
	 *
	 * @return int
	 * @access public
	 */
	public function getBooleanClauseLimit()
	{
		global $configArray;

		// Use setting from config.ini if present, otherwise assume 1024:
		return isset($configArray['Index']['maxBooleanClauses'])
		? $configArray['Index']['maxBooleanClauses'] : 1024;
	}

	/**
	 * Extract terms from the Solr index.
	 *
	 * @param string $field					 Field to extract terms from
	 * @param string $start					 Starting term to extract (blank for beginning
	 * of list)
	 * @param int		$limit					 Maximum number of terms to return (-1 for no
	 * limit)
	 * @param bool	 $returnSolrError Should we fail outright on syntax error
	 * (false) or treat it as an empty result set with an error key set (true)?
	 *
	 * @return array									Associative array parsed from Solr JSON
	 * response; meat of the response is in the ['terms'] element, which contains
	 * an index named for the requested term, which in turn contains an associative
	 * array of term => count in index.
	 * @access public
	 */
	public function getTerms($field, $start, $limit, $returnSolrError = false)
	{
		$this->pingServer();
		$this->client->setMethod('GET');
		$this->client->setURL($this->host . '/term');

		$this->client->addQueryString('terms', 'true');
		$this->client->addQueryString('terms.fl', $field);
		$this->client->addQueryString('terms.lower.incl', 'false');
		$this->client->addQueryString('terms.lower', $start);
		$this->client->addQueryString('terms.limit', $limit);
		$this->client->addQueryString('terms.sort', 'index');
		$this->client->addQueryString('wt', 'json');

		$result = $this->client->sendRequest();

		if (!PEAR_Singleton::isError($result)) {
			// Process the JSON response:
			$data = $this->_process(
			$this->client->getResponseBody(), $returnSolrError
			);

			// Tidy the data into a more usable format:
			if (isset($data['terms'])) {
				$data['terms'] = array(
				$data['terms'][0] => $this->_processTerms($data['terms'][1])
				);
			}
			return $data;
		} else {
			return $result;
		}
	}

	public function setSearchSource($searchSource){
		$this->searchSource = $searchSource;
	}

	private function _loadDynamicFields(){
		/** @var Memcache $memCache */
		global $memCache;
		global $solrScope;
		$fields = $memCache->get("schema_dynamic_fields_$solrScope");
		if (!$fields || isset($_REQUEST['reload'])){
			global $configArray;
			$schemaUrl = $configArray['Index']['url'] . '/grouped/admin/file?file=schema.xml&contentType=text/xml;charset=utf-8';
			$schema = simplexml_load_file($schemaUrl);
			$fields = array();
			/** @var SimpleXMLElement $field */
			foreach ($schema->fields->dynamicField as $field){
				$fields[] = substr((string)$field['name'], 0, -1);
			}
			$memCache->set("schema_dynamic_fields_$solrScope", $fields, 0, 24 * 60 * 60);
		}
		return $fields;
	}
	private function _loadValidFields(){
		/** @var Memcache $memCache */
		global $memCache;
		global $solrScope;
		if (isset($_REQUEST['allFields'])){
			return array('*');
		}
		$fields = $memCache->get("schema_fields_$solrScope");
		if (!$fields || isset($_REQUEST['reload'])){
			global $configArray;
			$schemaUrl = $configArray['Index']['url'] . '/grouped/admin/file?file=schema.xml&contentType=text/xml;charset=utf-8';
			$schema = simplexml_load_file($schemaUrl);
			$fields = array();
			/** @var SimpleXMLElement $field */
			foreach ($schema->fields->field as $field){
				//print_r($field);
				$fields[] = (string)$field['name'];
			}
			if ($solrScope){
				foreach ($schema->fields->dynamicField as $field){
					$fields[] = substr((string)$field['name'], 0, -1) . $solrScope ;
				}
			}
			$memCache->set("schema_fields_$solrScope", $fields, 0, 24 * 60 * 60);
		}
		return $fields;
	}
}

