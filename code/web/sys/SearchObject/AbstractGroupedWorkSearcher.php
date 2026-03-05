<?php
require_once ROOT_DIR . '/sys/SearchObject/SolrSearcher.php';
require_once ROOT_DIR . '/RecordDrivers/RecordDriverFactory.php';

abstract class SearchObject_AbstractGroupedWorkSearcher extends SearchObject_SolrSearcher {
	protected ?string $searchSubType = null;
	protected int $searchVersion;

	public ?string $selectedAvailabilityToggleValue;

	private bool $automaticFacetsApplied = false;

	/**
	 * This determines if Aspen applies the default availability toggle for the library
	 * or location if no value is provided. It needs to be disabled to search across libraries
	 * and locations.
	 *
	 * @var bool
	 */
	protected bool $disableDefaultAvailabilityToggle = false;

	public function __construct(int $searchVersion) {
		parent::__construct();
		$this->searchVersion = $searchVersion;
	}

	public function disableScoping() : void {
		$this->indexEngine->disableScoping();
	}

	/** @noinspection PhpUnused */
	public function enableScoping() : void {
		$this->indexEngine->enableScoping();
	}

	public function disableBoosting() : void {
		$this->indexEngine->disableBoosting();
	}

	/** @noinspection PhpUnused */
	public function enableBoosting() : void {
		$this->indexEngine->enableBoosting();
	}

	public function disableDefaultAvailabilityToggle() : void {
		$this->disableDefaultAvailabilityToggle = true;
	}

	/** @noinspection PhpUnused */
	public function enableDefaultAvailabilityToggle() : void {
		$this->disableDefaultAvailabilityToggle = false;
	}

	public function disableEditionLimiters() : void {
		$this->indexEngine->disableEditionLimiters();
	}

	/** @noinspection PhpUnused */
	public function enableEditionLimiters() : void {
		$this->indexEngine->enableEditionLimiters();
	}


	/**
	 * Initialise the object from the global
	 *  search parameters in $_REQUEST.
	 *
	 * @access  public
	 *
	 * @param String|null $searchSource
	 * @param String|null $searchTerm
	 * @param ?bool $enableSearchInterpreter
	 * @return  boolean
	 */
	public function init(?string $searchSource = null, ?string $searchTerm = null, ?bool $enableSearchInterpreter = false) : bool {
		// Call the standard initialization routine in the parent:
		parent::init($searchSource);

		$this->indexEngine->setSearchSource($searchSource);

		//********************
		// Check if we have a saved search to restore -- if restored successfully,
		// our work here is done; if there is an error, we should report failure;
		// if restoreSavedSearch returns false, we should proceed as normal.
		$restored = $this->restoreSavedSearch(null, true, true);
		if ($restored === true) {
			return true;
		} elseif (($restored instanceof AspenError)) {
			return false;
		}

		//********************
		// Initialize standard search parameters
		$this->initView();
		$this->initPage();
		$this->initSort();
		$this->initFilters();

		if ($searchTerm == null) {
			$searchTerm = $_REQUEST['lookfor'] ?? null;
		}

		global $module;
		global $action;

		//********************
		// Basic Search logic
		if ($this->initBasicSearch($searchTerm, $enableSearchInterpreter)) {
			// If we found a basic search, we don't need to do anything further.
		} else {
			$this->initAdvancedSearch();
		}

		//********************
		// Author screens - handled slightly differently
		$author_ajax_call = (isset($_REQUEST['author']) && $action == 'AJAX' && $module == 'Search');
		if ($module == 'Author' || $author_ajax_call) {
			// Author module or ajax call from author results page
			// *** Things in common to both screens
			// Log a special type of search
			$this->searchType = 'author';
			// We don't spellcheck this screen
			//   it's not for free user input anyway
			$this->spellcheckEnabled = false;

			// *** Author/Home
			if ($action == 'Home' || $author_ajax_call) {
				$this->searchSubType = 'home';
				// Remove our empty basic search (default)
				$this->searchTerms = [];
				// Prepare the search as a normal author search
				if (isset($_REQUEST['author'])) {
					$author = $_REQUEST['author'];
					if (is_array($author)) {
						$author = array_pop($author);
					}
				} else {
					$author = 'Not Provided';
				}

				$this->searchTerms[] = [
					'index' => 'Author',
					'lookfor' => trim(strip_tags($author)),
				];
			}

			// *** Author/Search
			if ($action == 'Search') {
				$this->searchSubType = 'search';
				// We already have the 'lookfor', just set the index
				$this->searchTerms[0]['index'] = 'Author';
				// We really want author facet data
				$this->addFacet('authorStr');
				// Offset the facet list by the current page of results, and
				// allow up to ten total pages of results -- since we can't
				// get a total facet count, this at least allows the paging
				// mechanism to automatically add more pages to the end of the
				// list so that users can browse deeper and deeper as they go.
				// TODO: Make this better in the future if Solr offers a way
				//       to get a total facet count (currently not possible).
				$this->facetOffset = ($this->page - 1) * $this->limit;
				$this->facetLimit = $this->limit * 10;
				// Sorting - defaults to off with unlimited facets, so let's
				//           be explicit here for simplicity.
				if (isset($_REQUEST['sort']) && ($_REQUEST['sort'] == 'author')) {
					$this->setFacetSortOrder('index');
				} else {
					$this->setFacetSortOrder('count');
				}
			}
		} elseif ($module == 'MyAccount') {
			// Users Lists
			$this->spellcheckEnabled = false;
			$this->searchType = ($action == 'Home') ? 'favorites' : 'list';
		}

		// If a query override has been specified, log it here
		if (isset($_REQUEST['q'])) {
			$this->query = trim(strip_tags($_REQUEST['q']));
		}

		return true;
	} // End init()

	/**
	 * Initialize the object's search settings for a basic search found in the
	 * $_REQUEST super global.
	 *
	 * @access  public
	 * @param null|String|String[] $searchTerm
	 * @param bool $enableSearchInterpreter
	 * @return  boolean  True if search settings were found, false if not.
	 */
	public function initBasicSearch(mixed $searchTerm = null, bool $enableSearchInterpreter = false) : bool{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		if ($searchTerm == null) {
			// If no lookfor parameter was found, we have no search terms to
			// add to our array!
			if (!isset($_REQUEST['lookfor'])) {
				return false;
			} else {
				$searchTerm = StringUtils::removeTrailingPunctuation(trim($_REQUEST['lookfor']));
			}
		} else {
			$searchTerm = StringUtils::removeTrailingPunctuation(trim($searchTerm));
		}

		// If no type defined use default
		if ((isset($_REQUEST['searchIndex'])) && ($_REQUEST['searchIndex'] != '')) {
			$type = $_REQUEST['searchIndex'];

			// Flatten type arrays for backward compatibility:
			if (is_array($type)) {
				$type = strip_tags($type[0]);
			} else {
				$type = strip_tags($type);
			}

			//The type should never have punctuation in it (quotes, colons, etc.)
			$type = preg_replace('/[:"\']/', '', $type);

			if (!array_key_exists($type, $this->getSearchIndexes()) && !array_key_exists($type, $this->advancedTypes)) {
				$type = $this->getDefaultIndex();
			}
		} else {
			$type = $this->getDefaultIndex();
		}

		if (strpos($searchTerm, ':') > 0) {
			$tempSearchInfo = explode(':', $searchTerm);
			if (count($tempSearchInfo) == 2) {
				//Check for leading and trailing parentheses
				if (strlen($tempSearchInfo[0]) > 0 && $tempSearchInfo[0][0] == '(') {
					$tempSearchInfo[0] = substr($tempSearchInfo[0], 1);
				}
				if (strlen($tempSearchInfo[1]) > 0 && $tempSearchInfo[1][-1] == ')') {
					$tempSearchInfo[1] = substr($tempSearchInfo[1], 0, -1);
				}

				if (array_key_exists($tempSearchInfo[0], $this->searchIndexes)) {
					$type = $tempSearchInfo[0];
					$searchTerm = $tempSearchInfo[1];
				} else {
					$validFields = $this->loadValidFields();
					if (is_null($validFields)) {
						$validFields = [];
					}
					$dynamicFields = $this->loadDynamicFields();
					if (is_null($dynamicFields)) {
						$dynamicFields = [];
					}

					if (!in_array($tempSearchInfo[0], $validFields) && !in_array($tempSearchInfo[0], $dynamicFields) || array_key_exists($tempSearchInfo[0], $this->advancedTypes)) {
						$searchTerm = str_replace(':', ' ', $searchTerm);
					} else {
						return false;
					}
				}
			} else {
				//This is an advanced search
				return false;
			}
		}

		$searchTerm = $this->runSearchInterpreter($type, $enableSearchInterpreter, $searchTerm);

		$this->searchTerms[] = [
			'index' => $type,
			'lookfor' => $searchTerm,
		];

		if (isset($_REQUEST['searchId']) && is_numeric($_REQUEST['searchId'])) {
			$searchEntry = new SearchEntry();
			$searchEntry->id = $_REQUEST['searchId'];
			if ($searchEntry->find(true)) {
				$activeUserId = UserAccount::getActiveUserId();
				if ($activeUserId && ($activeUserId == $searchEntry->user_id)) {
					$this->searchId = $searchEntry->id;
					$this->savedSearch = $searchEntry->saved;
				} elseif ($searchEntry->session_id == session_id()) {
					$this->searchId = $searchEntry->id;
					$this->savedSearch = $searchEntry->saved;
				}
			}
		}
		return true;
	}

	/**
	 * Initialise the object for retrieving advanced
	 *   search screen facet data from inside solr.
	 *
	 * @access  public
	 * @return  boolean
	 */
	public function initAdvancedFacets() : bool {
		global $locationSingleton;
		// Call the standard initialization routine in the parent:
		parent::init();

		$searchLibrary = Library::getActiveLibrary();

		$searchLocation = $locationSingleton->getActiveLocation();
		if ($searchLocation != null) {
			$facets = $searchLocation->getGroupedWorkDisplaySettings()->getFacets();
		} else {
			$facets = $searchLibrary->getGroupedWorkDisplaySettings()->getFacets();
		}

		foreach ($facets as $facet) {
			//Adjust facet name for local scoping
			$facet->facetName = $this->getScopedFieldName($facet->facetName);
		}

		//********************

		$facetLimit = $this->getFacetSetting('Advanced_Settings', 'facet_limit');
		if (is_numeric($facetLimit)) {
			$this->facetLimit = $facetLimit;
		}

		// Spellcheck is not needed for facet data!
		$this->spellcheckEnabled = false;

		//********************
		// Basic Search logic
		$this->searchTerms[] = [
			'index' => $this->getDefaultIndex(),
			'lookfor' => "",
		];

		return true;
	}

	public function getDebugTiming() : ?string {
		if (!$this->debug) {
			return null;
		} else {
			if (!isset($this->indexResult['debug'])) {
				return null;
			} else {
				return json_encode($this->indexResult['debug']['timing']);
			}
		}
	}

	/**
	 * Return the field (index) searched by a basic search
	 *
	 * @access  public
	 * @return  ?string   The searched index
	 */
	public function getSearchIndex() : ?string {
		// Use normal parent method for non-advanced searches.
		if ($this->searchType == $this->basicSearchType || $this->searchType == 'author' || $this->searchType == 'list') {
			return parent::getSearchIndex();
		} else {
			if ($this->isAdvanced()) {
				return 'advanced';
			} else {
				return null;
			}
		}
	}

	/**
	 * @param ?array $orderedListOfIDs Use the index of the matched ID as the index of the resulting array of summary data (for later merging)
	 * @return array
	 */
	public function getTitleSummaryInformation(?array $orderedListOfIDs = []) : array {
		global $solrScope;
		$titleSummaries = [];
		for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
			$current = &$this->indexResult['response']['docs'][$x];
			/** @var GroupedWorkDriver $record */
			$record = RecordDriverFactory::initRecordDriver($current);
			if (!($record instanceof AspenError)) {
				$isNew = false;
				if (!empty($this->searchId) && $this->savedSearch) {
					if (isset($current["local_time_since_added_$solrScope"])) {
						$isNew = in_array('Week', $current["local_time_since_added_$solrScope"]);
					}
				}
				if (!empty($orderedListOfIDs)) {
					$position = array_search($current['id'], $orderedListOfIDs);
					if ($position !== false) {
						$summary = $record->getSummaryInformation();
						$summary['isNew'] = $isNew;
						$titleSummaries[$position] = $summary;
					}
				} else {
					$summary = $record->getSummaryInformation();
					$summary['isNew'] = $isNew;
					$titleSummaries[] = $summary;
				}
			} else {
				$titleSummaries[] = "Unable to find record";
			}
		}
		return $titleSummaries;
	}

	/*
	 *  Get the template to use to display the results returned from getRecordHTML()
	 *  based on the view mode
	 *
	 * @return string  Template file name
	 */
	public function getDisplayTemplate() :string {
		if ($this->view == 'covers') {
			$displayTemplate = 'Search/covers-list.tpl'; // structure for bookcover tiles
		} else { // default
			$displayTemplate = 'Search/list-list.tpl'; // structure for regular results
		}
		return $displayTemplate;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results.
	 *
	 * @access  public
	 * @return  array   Array of HTML chunks for individual records.
	 */
	public function getResultRecordHTML() : array {
		global $interface;
		global $memoryWatcher;
		global $timer;
		global $solrScope;

		$searchEntry = new SearchEntry();
		$searchEntry = $searchEntry->getSavedSearchByUrl($this->renderSearchUrl(false), session_id(), UserAccount::getActiveUserId());
		$isSaved = false;
		if ($searchEntry != null) {
			$isSaved = $searchEntry->saved;
		}
		global $library;
		$location = Location::getSearchLocation();
		if ($location != null) {
			$groupedWorkDisplaySettings = $location->getGroupedWorkDisplaySettings();
		} else {
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
		}
		$alwaysFlagNewTitles = $groupedWorkDisplaySettings->alwaysFlagNewTitles;
		$html = [];
		if (isset($this->indexResult['response'])) {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$timer->logTime('Loaded archive links');
			for ($x = 0; $x < count($this->indexResult['response']['docs']); $x++) {
				$memoryWatcher->logMemory("Started loading record information for index $x");
				$current = &$this->indexResult['response']['docs'][$x];
				if (!$this->debug) {
					unset($current['explain']);
					unset($current['score']);
				}
				// Use absolute positioning for navigation links to display on grouped works spanning across pages.
				$interface->assign('recordIndex', $x + 1 + (($this->page - 1) * $this->limit));
				$interface->assign('resultIndex', $x + 1 + (($this->page - 1) * $this->limit));
				if ($isSaved || $alwaysFlagNewTitles) {
					if (isset($current["local_time_since_added_$solrScope"])) {
						$interface->assign('isNew', in_array('Week', $current["local_time_since_added_$solrScope"]));
					} else {
						$interface->assign('isNew', false);
					}
				} else {
					$interface->assign('isNew', false);
				}
				/** @var GroupedWorkDriver $record */
				$record = RecordDriverFactory::initRecordDriver($current);
				if (!($record instanceof AspenError)) {
					$interface->assign('recordDriver', $record);
					$html[] = $interface->fetch($record->getSearchResult($this->view));
				} else {
					$html[] = "Unable to find record";
				}
				//Free some memory
				$record = 0;
				unset($record);
				$memoryWatcher->logMemory("Finished loading record information for index $x");
				$timer->logTime('Loaded search result for ' . $current['id']);
			}
		}
		return $html;
	}

	/**
	 * Set an overriding array of record IDs.
	 *
	 * @access  public
	 * @param string[] $ids Record IDs to load
	 */
	public function setQueryIDs(array $ids) : void {
		$this->searchType = 'basic';
		$this->query = 'id:(' . implode(' OR ', $ids) . ')';
	}

	/**
	 * Set an overriding facet sort order.
	 *
	 * @access  public
	 * @param string $newSort Sort string
	 */
	public function setFacetSortOrder(string $newSort) : void {
		// As of Solr 1.4 valid values are:
		// 'count' = relevancy ranked
		// 'index' = index order, most likely alphabetical
		// more info : http://wiki.apache.org/solr/SimpleFacetParameters#facet.sort
		if ($newSort == 'count' || $newSort == 'index') {
			$this->facetSort = $newSort;
		}
	}

	public function supportsSuggestions() : bool {
		return true;
	}

	/**
	 * @param string $searchTerm
	 * @param string $searchIndex
	 * @return array
	 */
	public function getSearchSuggestions($searchTerm, $searchIndex) : array {
		if ($searchIndex == 'Title' || $searchIndex == 'StartOfTitle' || $searchIndex == 'Series') {
			$suggestionHandler = 'title_suggest';
		} elseif ($searchIndex == 'Author') {
			$suggestionHandler = 'author_suggest';
		} elseif ($searchIndex == 'Subject') {
			$suggestionHandler = 'subject_suggest';
		} elseif ($searchIndex == 'Keyword') {
			$suggestionHandler = 'suggest';
		} else {
			return [];
		}
		return $this->processSearchSuggestions($searchTerm, $suggestionHandler);
	}

	/**
	 * Return a list of valid sort options -- overrides the base class with
	 * custom behavior for Author/Search screen.
	 *
	 * @access  public
	 * @return  array    Sort value => description array.
	 */
	public function getSortOptions() : array {
		// Author/Search screen
		if ($this->searchType == 'author' && $this->searchSubType == 'search') {
			// It's important to remember here we are talking about on-screen
			//   sort values, not what is sent to Solr, since this screen
			//   is really using facet sorting.
			return [
				'relevance' => 'sort_author_relevance',
				'author' => 'sort_author_author',
			];
		}

		// Everywhere else -- use normal default behavior
		$sortOptions = parent::getSortOptions();
		$searchLibrary = Library::getSearchLibrary($this->searchSource);
		if ($searchLibrary == null) {
			unset($sortOptions['callnumber_sort']);
		}
		return $sortOptions;
	}

	/**
	 * @param string $type
	 * @param mixed $enableSearchInterpreter
	 * @param string $searchTerm
	 * @return string
	 */
	public function runSearchInterpreter(string $type, bool $enableSearchInterpreter, string $searchTerm): string {
		$splitPattern = "/[|,]\s*/";
		if ($type == 'Keyword' && $enableSearchInterpreter && !empty($searchTerm) && empty($this->filterList)) {
			$changeMade = false;
			$searchTermLower = strtolower($searchTerm);

			require_once ROOT_DIR . '/sys/SearchObject/SearchInterpreterSetting.php';
			$searchInterpreterSettings = new SearchInterpreterSetting();
			if (!$searchInterpreterSettings->find(true)) {
				return $searchTerm;
			}

			//Ignore boolean searches
			if (preg_match('/(\b|^)(AND|OR|NOT)(\b)/', $searchTerm)) {
				return $searchTerm;
			}

			$searchTermsToSkip = $searchInterpreterSettings->getTermsToSkipAsStrings();
			foreach ($searchTermsToSkip as $term) {
				$valueToCheck = preg_quote($term, '/');
				if (preg_match('/(\b|^)' . $valueToCheck . '(\b|$)/i', $searchTerm)) {
					return $searchTerm;
				}
			}

			$hasFormatApplied = false;
			if ($searchInterpreterSettings->processFormats || $searchInterpreterSettings->processPluralFormats) {
				$formatsToSkip = preg_split($splitPattern, strtolower($searchInterpreterSettings->formatsToSkip), -1, PREG_SPLIT_NO_EMPTY);;
				$pluralFormatsToSkip = preg_split($splitPattern, strtolower($searchInterpreterSettings->pluralFormatsToSkip), -1, PREG_SPLIT_NO_EMPTY);;
				require_once ROOT_DIR . '/sys/Indexing/IndexedFormat.php';
				$indexedFormatObj = new IndexedFormat();
				$indexedFormatObj->orderBy('LENGTH(format) DESC');
				$indexedFormats = $indexedFormatObj->fetchAll('format');
				foreach ($indexedFormats as $indexedFormat) {
					$quoteValue = true;
					$indexedFormatLower = strtolower($indexedFormat);
					$checkSingular = $searchInterpreterSettings->processFormats && (!in_array($indexedFormatLower, $formatsToSkip));
					$checkPlural = $searchInterpreterSettings->processPluralFormats && (!in_array($indexedFormatLower, $pluralFormatsToSkip)) && (!in_array($indexedFormatLower . 's', $pluralFormatsToSkip));
					if ($indexedFormat == 'Large Print' || $indexedFormat == 'Large Type') {
						$indexedFormatRegex = '(Large Print|Large type)( books)?';
						$quoteValue = false;
					} else {
						$indexedFormatRegex = $indexedFormat;
					}
					$filterApplied = $this->checkAndApplyFacetValueToSearch($searchTerm, $indexedFormatRegex, $checkSingular, $checkPlural, $searchInterpreterSettings->processNew, $quoteValue, 'format', $indexedFormat);
					$hasFormatApplied = $hasFormatApplied && $filterApplied;
				}
			}

			if (!$hasFormatApplied && $searchInterpreterSettings->processFormatCategories) {
				$formatCategoriesToSkip = preg_split($splitPattern, strtolower($searchInterpreterSettings->formatCategoriesToSkip), -1, PREG_SPLIT_NO_EMPTY);;
				$indexedFormatCategories = [
					'Books',
					'eBooks',
					'Audio Books',
					'Music',
					'Movies'
				];
				$hasFormatCategoryApplied = false;
				//Since Format Category is not multi-select we only want to pick one.
				//i.e. Books and Audio Books would return less than either on their own
				foreach ($indexedFormatCategories as $indexedFormatCategory) {
					if (in_array(strtolower($indexedFormatCategory), $formatCategoriesToSkip)) {
						continue;
					}
					$quoteValue = true;
					if ($indexedFormatCategory == 'Movies') {
						$indexedFormatCategoryRegex = '(Movie|Video)';
						$checkPlural = true;
						$quoteValue = false;
					}else{
						$indexedFormatCategoryRegex = $indexedFormatCategory;
						$checkPlural = false;
					}
					$filterApplied = $this->checkAndApplyFacetValueToSearch($searchTerm, $indexedFormatCategoryRegex, $quoteValue, $checkPlural, $searchInterpreterSettings->processNew, false, 'format_category', $indexedFormatCategory);
					/** @noinspection PhpConditionAlreadyCheckedInspection */
					$hasFormatCategoryApplied = $hasFormatCategoryApplied && $filterApplied;
					//We can only apply one format category
					if ($hasFormatCategoryApplied) {
						break;
					}
				}
			}

			if ($searchInterpreterSettings->processAudiences || $searchInterpreterSettings->processPluralAudiences) {
				$audiencesToSkip = preg_split($splitPattern, strtolower($searchInterpreterSettings->audiencesToSkip), -1, PREG_SPLIT_NO_EMPTY);;
				$pluralAudiencesToSkip = preg_split($splitPattern, strtolower($searchInterpreterSettings->pluralAudiencesToSkip), -1, PREG_SPLIT_NO_EMPTY);;
				$processSingular = $searchInterpreterSettings->processAudiences && !in_array('kid', $audiencesToSkip);
				$processPlural = $searchInterpreterSettings->processPluralAudiences && !in_array('kids', $pluralAudiencesToSkip);
				$this->checkAndApplyFacetValueToSearch($searchTerm, '(kid|children|juvenile)', $processSingular, $processPlural, $searchInterpreterSettings->processNew, false, 'target_audience', 'Juvenile');
				$processSingular = $searchInterpreterSettings->processAudiences && !in_array('teen', $audiencesToSkip);
				$processPlural = $searchInterpreterSettings->processPluralAudiences && !in_array('teens', $pluralAudiencesToSkip);
				$this->checkAndApplyFacetValueToSearch($searchTerm, '(teen|young adult)', $processSingular, $processPlural, $searchInterpreterSettings->processNew, false, 'target_audience', 'Young Adult');
				$processSingular = $searchInterpreterSettings->processAudiences && !in_array('adult', $audiencesToSkip);
				$processPlural = $searchInterpreterSettings->processPluralAudiences && !in_array('adults', $pluralAudiencesToSkip);
				$this->checkAndApplyFacetValueToSearch($searchTerm, '(adult|senior)', $processSingular, $processPlural, $searchInterpreterSettings->processNew, false, 'target_audience', 'Adult');
			}
			if ($searchInterpreterSettings->processFictionNonFiction) {
				$this->checkAndApplyFacetValueToSearch($searchTerm, 'non[-\s]?fiction(al)?', true, false, $searchInterpreterSettings->processNew, false, 'literary_form', 'Non Fiction');
				$this->checkAndApplyFacetValueToSearch($searchTerm, '(?<!science\s)fiction(al)?', true, false, $searchInterpreterSettings->processNew, false, 'literary_form', 'Fiction');
			}
			if ($this->automaticFacetsApplied && $searchInterpreterSettings->processAvailable) {
				$this->checkAndApplyFacetValueToSearch($searchTerm, 'available', true, false, false, false, 'availability_toggle', 'available');
			}

			$specialSearchTerms = $searchInterpreterSettings->getSpecialTerms();
			foreach ($specialSearchTerms as $term) {
				$applied = $this->checkAndApplyFacetValueToSearch($searchTerm, $term->term, true, false, $searchInterpreterSettings->processNew, false, null, null, $term->facetsToApply);
				if ($applied && !empty($term->sortToApply)) {
					$this->setSort($term->sortToApply);
				}
			}

			if ($this->automaticFacetsApplied) {
				$searchTerm = preg_replace('/(\b)for(\b)/i', '', $searchTerm);
				$searchTerm = preg_replace('/(\b)about(\b)/i', '', $searchTerm);
				$searchTerm = preg_replace('/\s\s/i', ' ', $searchTerm);
				$searchTerm = preg_replace('/\.$/', ' ', $searchTerm);
				$searchTerm = trim($searchTerm);
			}
		}
		return $searchTerm;
	}

	public function hasAutomaticFacetsApplied() : bool {
		return $this->automaticFacetsApplied;
	}

	protected function checkAndApplyFacetValueToSearch(string &$searchTerm, string $valueToCheck, bool $checkSingular, bool $checkPlural, bool $processNew, bool $quoteValue, ?string $facetToApply = null, ?string $facetValueToApply = null, ?string $facetBlockToApply = null) : bool {
		if (empty($searchTerm)) {
			return false;
		}
		$numChanges = 0;
		$prefixedWithNew = false;
		if ($quoteValue) {
			$valueToCheck = preg_quote($valueToCheck, '/');
			$valueToCheck = str_replace(' ', '\s?', $valueToCheck);
		}
		if ($checkPlural && $checkSingular) {
			//This is a really simple check
			$valueToCheck .= 's?';
		}elseif ($checkSingular) {
			//No need to adjust the value
		}elseif ($checkPlural) {
			$valueToCheck .= 's';
		}else{
			//Checking neither, bail
			return false;
		}
		//First check to see if the value is prefixed by new (i.e. new non-fiction)
		if ($processNew) {
			$searchTerm = preg_replace('/(\b|^)new ' . $valueToCheck . '(\b|$)/i', '', $searchTerm, -1, $numChanges);
		}

		if ($numChanges == 0) {
			//If we got no changes then check without the new prefix
			$searchTerm = preg_replace('/(\b|^)' . $valueToCheck . '(\b|$)/i', '', $searchTerm, -1, $numChanges);
		}else{
			$prefixedWithNew = true;
		}

		if ($numChanges > 0) {
			if ($facetToApply != null && $facetValueToApply != null) {
				$this->addFilter("$facetToApply:$facetValueToApply");
			}
			if ($facetBlockToApply != null) {
				$facetsToApply = explode("\n", $facetBlockToApply);
				foreach ($facetsToApply as $facet) {
					$facet = trim($facet);
					if (!empty($facet)) {
						$this->addFilter($facet);
					}
				}
			}
			if ($prefixedWithNew) {
				$lastYear = date('Y', strtotime('-1 year'));
				$this->addFilter("publishDateSort:[$lastYear TO *]");
				$this->setSort('days_since_added asc');
			}
			$this->automaticFacetsApplied = true;
			$searchTerm = trim($searchTerm);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Get the base URL for search results (including ? parameter prefix).
	 *
	 * @access  protected
	 * @return  string   Base URL
	 */
	protected function getBaseUrl() : string {
		// Base URL is different for author searches:
		if ($this->searchType == 'author') {
			if ($this->searchSubType == 'home') {
				return "/Author/Home?";
			}
			if ($this->searchSubType == 'search') {
				return "/Author/Search?";
			}
		} elseif ($this->searchType == 'favorites') {
			return '/MyAccount/Home?';
		} elseif ($this->searchType == 'list') {
			if (isset($_GET['id'])) {
				return '/MyAccount/MyList/' . urlencode($_GET['id']) . '?';
			}elseif (is_array($this->searchTerms)) {
				$firstSearchTerm = reset($this->searchTerms);
				if ($firstSearchTerm['index'] == 'list_link') {
					return '/MyAccount/MyList/' . urlencode($firstSearchTerm['lookfor']) . '?';
				}
			}
		} elseif ($this->searchType == 'series') {
			if (isset($_GET['id'])) {
				return '/Series/' . urlencode($_GET['id']) . '?';
			}
		}

		// If none of the special cases were met, use the default from the parent:
		return parent::getBaseUrl();
	}

	/**
	 * Get an array of strings to attach to a base URL in order to reproduce the
	 * current search.
	 *
	 * Note: Can't store this for future use because it is rewritten by spelling suggestions, etc.
	 *
	 * @return array Array of URL parameters (key=url_encoded_value format).
	 */
	protected function getSearchParams(): array {
		$params = [];
		switch ($this->searchType) {
			// Author Home screen
			case "author":
				if ($this->searchSubType == 'home') {
					$params[] = "author=" . urlencode($this->searchTerms[0]['lookfor']);
				}
				if ($this->searchSubType == 'search') {
					$params[] = "lookfor=" . urlencode($this->searchTerms[0]['lookfor']);
				}
				$params[] = "basicSearchType=Author";
				break;
			case "list" :
				$selectedResourceTypes = $_REQUEST['resourceTypes'] ?? [];
				foreach ($selectedResourceTypes as $resourceType) {
					$params[] = "resourceTypes[]=$resourceType";
				}
				break;
			// New Items or Reserves modules may have a few extra parameters to preserve:
			default:
				$params = parent::getSearchParams();
				break;
		}

		//Only use the request search index if we don't have a search index set already
		$searchIndexSet = false;
		foreach ($params as $param) {
			if (strpos($param, 'searchIndex') == 0) {
				$searchIndexSet = true;
				break;
			}
		}
		if (!$searchIndexSet) {
			if (isset($_REQUEST['searchIndex'])) {
				if ($_REQUEST['searchIndex'] == 'AllFields') {
					$_REQUEST['searchIndex'] = 'Keyword';
				}
				if (is_array($_REQUEST['searchIndex'])) {
					$_REQUEST['searchIndex'] = reset($_REQUEST['searchIndex']);
				}
				$params[] = 'searchIndex=' . $_REQUEST['searchIndex'];
			}
		}

		return $params;
	}


	/**
	 * Load all recommendation settings from the relevant ini file.  Returns an
	 * associative array where the key is the location of the recommendations (top
	 * or side) and the value is the settings found in the file (which may be either
	 * a single string or an array of strings).
	 *
	 * @access  protected
	 * @return  array           associative: location (top/side) => search settings
	 */
	protected function getRecommendationSettings() : array {
		return parent::getRecommendationSettings();
	}


	/**
	 * Turn our results into an RSS feed
	 *
	 * @access  public
	 * @param null|array $result Existing result set (null to do new search)
	 * @return  string                  XML document
	 */
	public function buildRSS($result = null) : string {
		global $configArray;
		// XML HTTP header
		header('Content-type: text/xml');

		// First, get the search results if none were provided
		// (we'll go for 50 at a time)
		if (is_null($result)) {
			$this->limit = 50;
			$result = $this->processSearch();
		}

		$baseUrl = $configArray['Site']['url'];
		if (!empty($result)){
			for ($i = 0; $i < count($result['response']['docs']); $i++) {
				$id = $result['response']['docs'][$i]['id'];
				$result['response']['docs'][$i]['recordUrl'] = $baseUrl . '/GroupedWork/' . $id;
				require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
				$groupedWorkDriver = new GroupedWorkDriver($result['response']['docs'][$i]);
				if ($groupedWorkDriver->isValid) {
					$image = $groupedWorkDriver->getBookcoverUrl('medium', true);
					$description = "<img alt='Cover Image' src='$image'/> " . $groupedWorkDriver->getDescriptionFast();
					$result['response']['docs'][$i]['rss_description'] = $description;
				}
			}
		}

		global $interface;

		// On-screen display value for our search
		$lookfor = $this->displayQuery();
		if (count($this->filterList) > 0) {
			// TODO : better display of filters
			$interface->assign('lookfor', $lookfor . " (" . translate([
					'text' => 'with filters',
					'isPublicFacing' => true,
				]) . ")");
		} else {
			$interface->assign('lookfor', $lookfor);
		}
		// The full url to recreate this search
		$interface->assign('searchUrl', $configArray['Site']['url'] . $this->renderSearchUrl());
		// Stub of a url for a records screen
		$interface->assign('baseUrl', $configArray['Site']['url'] . "/Record/");

		$interface->assign('result', $result);
		return $interface->fetch('Search/rss.tpl');
	}

	/**
	 * Turn our results into a csv document which is returned to the browser
	 */
	public function buildExcel($result = null) : void {
		global $configArray;
		global $solrScope;
		try {
			// First, get the search results if none were provided
			// (we'll go for 50 at a time)
			if (is_null($result)) {
				$this->limit = 1000;
				$result = $this->processSearch();
			}

			//Output to the browser
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment;filename="SearchResults.csv"');
			$fp = fopen('php://output', 'w');

			$fields = array('Link', 'Title', 'Author', 'ISBN', 'UPC', 'Publisher', 'Publish Date', 'Place of Publication', 'Format', 'Location & Call Number');
			fputcsv($fp, $fields);

			$docs = $result['response']['docs'];

			if ($docs != null){
				for ($i = 0; $i < count($docs); $i++) {
					//Output the row to csv
					$curDoc = $docs[$i];
					//Output the row to csv
					$link = '';
					if ($curDoc['id']) {
						$link = $configArray['Site']['url'] . '/GroupedWork/' . $curDoc['id'];
					}

					$title = '';
					$title = $curDoc['title_display'];

					$author = '';
					$author = $curDoc['author_display'];

					$isbn = '';
					if (isset($curDoc['primary_isbn'])) {
						$isbn = $curDoc['primary_isbn'];
					} elseif (isset($curDoc['isbn'])) {
						if (is_array($curDoc['isbn'])) {
							$isbnArray = array_slice($curDoc['isbn'], 0, 3);
							$isbn = implode(', ', $isbnArray);
						} else {
							$isbn = $curDoc['isbn'];
						}
					}

					$upc = '';
					if (isset($curDoc['primary_upc'])) {
						$upc = $curDoc['primary_upc'];
					} elseif (isset($curDoc['upc'])) {
						if (is_array($curDoc['upc'])) {
							$upcArray = array_slice($curDoc['upc'], 0, 3);
							$upc = implode(', ', $upcArray);
						} else {
							$upc = $curDoc['upc'];
						}
					}

					$publisher = '';
					if (isset($curDoc['publisherStr'])) {
						$publisher = implode('; ', $curDoc['publisherStr']);
					}

					$placeOfPublication = '';
					if (isset($curDoc['placeOfPublication'])) {
						$placeOfPublication = implode('; ', $curDoc['placeOfPublication']);
					}

					// Publish Dates: Min-Max
					$publishDates = [''];
					if (isset($curDoc['publishDate'])) {
						if (!is_array($curDoc['publishDate'])) {
							$publishDates = [$curDoc['publishDate']];
						} else {
							$publishDates = $curDoc['publishDate'];
						}
					}
					$publishDate = '';
					if (count($publishDates) == 1) {
						$publishDate = $publishDates[0];
					} elseif (count($publishDates) > 1) {
						$publishDate = min($publishDates) . ' - ' . max($publishDates);
					}

					// Formats
					$formatField = 'format_' . $solrScope;
					if (array_key_exists($formatField, $curDoc)) {
						if (!is_array($curDoc[$formatField])) {
							$formats = (array)$curDoc[$formatField];
						} else {
							$formats = $curDoc[$formatField];
						}
					} else {
						if (!is_array($curDoc['format'])) {
							$formats = (array)$curDoc['format'];
						} else {
							$formats = $curDoc['format'];
						}
						foreach ($formats as $key => $format) {
							$formats[$key] = substr($format, strpos($format, '#') + 1);
						}
					}
					$uniqueFormats = array_unique($formats);
					$uniqueFormats = implode(';', $uniqueFormats);

					// Format / Location / Call number, max 3 records
					//Get the Grouped Work Driver so we can get information about the formats and locations within the record
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					$groupedWorkDriver = new GroupedWorkDriver($curDoc);
					$output = [];
					foreach ($groupedWorkDriver->getRelatedManifestations() as $relatedManifestation) {
						//Manifestation gives us Format & Format Category
						if (!$relatedManifestation->isHideByDefault()) {
							$format = $relatedManifestation->format;
							//Variation gives us the sort
							foreach ($relatedManifestation->getVariations() as $variation) {
								if (!$variation->isHideByDefault()) {
									//Record will give us the call number, and location
									//Only do up to 3 records per format?
									foreach ($variation->getRecords() as $record) {
										if ($record->isLocallyOwned() || $record->isLibraryOwned()) {
											$copySummary = $record->getItemSummary();
											foreach ($copySummary as $item) {
												$output[] = $format . "::" . $item['description'];
											}
											$output = array_unique($output);
											$output = array_slice($output, 0, 3);
											if (count($output) == 0) {
												$output[] = "No copies currently owned by this library";
											}
										}
										if($record->_eContentSource == "OverDrive"){
											$readerName = new OverDriveDriver();
											$readerName = $readerName->getReaderName();
											$output[] = $format . "::" . $readerName;
											$output = array_unique($output);
											$output = array_slice($output, 0, 3);
										}
										$record->discardDriver();
									}
								}
							}
						}
					}
					$groupedWorkDriver = null;
					$output = implode(',', $output);
					$row = array ($link, $title, $author, $isbn, $upc, $publisher, $publishDate, $placeOfPublication, $uniqueFormats, $output);
					fputcsv($fp, $row);
				}
			}
			exit();
		} catch (Exception $e) {
			global $logger;
			$logger->log("Unable to create csv file " . $e, Logger::LOG_ERROR);
		}
	}

		/**
	 * Turn our results into a RIS document
	 */

	public function buildRisExport($result = null) : void {
		try {
			// First, get the search results if none were provided
			if (is_null($result)) {
				//$this->limit = 1000;
				$result = $this->processSearch();
			}
	
			$risData = '';
	
			$docs = $result['response']['docs'];
	
			if ($docs != null) {
				foreach ($docs as $curDoc) {
					// Build RIS data for each document
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					$groupedWorkDriver = new GroupedWorkDriver($curDoc);
					$risData .= $groupedWorkDriver->getRISData();
					$risData .= PHP_EOL . PHP_EOL; // Add a blank line between records

				}
			}

			// Output the RIS data
			header("Content-Type: application/x-research-info-systems");
			header('Content-Disposition: attachment;filename="SearchResults.ris"');
			echo $risData;
			exit();
		} catch (Exception $e) {
			global $logger;
			$logger->log("Unable to create RIS file: " . $e->getMessage(), Logger::LOG_ERROR);
		}
	}

	/**
	 * Retrieves a document specified by the item barcode.
	 *
	 * @param string $barcode A barcode of an item in the document to retrieve from Solr
	 * @return  ?array               The requested resource
	 * @throws  AspenError
	 */
	function getRecordByBarcode(string $barcode) : ?array {
		if ($this->indexEngine instanceof GroupedWorksSolrConnector || $this->indexEngine instanceof GroupedWorksSolrConnector2) {
			return $this->indexEngine->getRecordByBarcode($barcode);
		}else{
			return null;
		}
	}

	/**
	 * Retrieves a document specified by an isbn.
	 *
	 * @param string[] $isbn An array of isbns to check
	 * @return  ?array              The requested resource
	 * @throws  AspenError
	 */
	function getRecordByIsbn(array $isbn) : ?array {
		if ($this->indexEngine instanceof GroupedWorksSolrConnector || $this->indexEngine instanceof GroupedWorksSolrConnector2) {
			return $this->indexEngine->getRecordByIsbn($isbn, $this->getFieldsToReturn());
		}else{
			return null;
		}
	}

	public function setPrimarySearch($flag) : void {
		parent::setPrimarySearch($flag);
		$this->indexEngine->isPrimarySearch = $flag;
	}

	public function __destruct() {
		if (isset($this->indexEngine)) {
			$this->indexEngine = null;
			unset($this->indexEngine);
		}
	}

	public function getSearchIndexes() : array {
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
			'StartOfTitle' => translate([
				'text' => 'Start of Title',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
			'Series' => translate([
				'text' => 'Series',
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
			'LocalCallNumber' => translate([
				'text' => 'Call Number',
				'isPublicFacing' => true,
				'inAttribute' => true,
			]),
		];
	}

	public function getRecordDriverForResult($record) : GroupedWorkDriver {
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		return new GroupedWorkDriver($record);
	}

	public function getSearchesFile() : string {
		return 'groupedWorksSearches';
	}

	/**
	 * Get records similar to one record
	 * Uses MoreLikeThis Request Handler
	 *
	 * Uses SOLR MLT Query Handler
	 *
	 * @access    public
	 *
	 * @param array $ids
	 * @param ?int $page
	 * @param ?int $limit
	 * @param ?string[] $notInterestedTitles
	 * @return    array                            An array of query results
	 */
	function getMoreLikeThese(array $ids, ?int $page = 1, ?int $limit = 25, ?array $notInterestedTitles = []) : array {
		if ($this->indexEngine instanceof GroupedWorksSolrConnector ||  $this->indexEngine instanceof GroupedWorksSolrConnector2) {
			return $this->indexEngine->getMoreLikeThese($ids, $this->getFieldsToReturn(), $page, $limit, $notInterestedTitles);
		}else{
			return [];
		}
	}


	/**
	 * @return array
	 */
	public function getFacetConfig() : array {
		if ($this->facetConfig == null) {
			$facetConfig = [];
			$searchLibrary = Library::getActiveLibrary();
			global $locationSingleton;
			$searchLocation = $locationSingleton->getActiveLocation();
			if ($searchLocation != null) {
				$facets = $searchLocation->getGroupedWorkDisplaySettings()->getFacets();
			} else {
				$facets = $searchLibrary->getGroupedWorkDisplaySettings()->getFacets();
			}
			foreach ($facets as $facet) {
				//Adjust facet name for local scoping
				$facet->facetName = $this->getScopedFieldName($facet->getFacetName($this->searchVersion));

				global $action;
				if ($action == 'Advanced') {
					if ($facet->showInAdvancedSearch == 1) {
						$facetConfig[$facet->facetName] = $facet;
					}
				} else {
					if ($facet->showInResults == 1) {
						$facetConfig[$facet->facetName] = $facet;
					}
				}
			}
			$this->facetConfig = $facetConfig;
		}

		return $this->facetConfig;
	}

	function getMoreLikeThis($id, $selectedAvailabilityToggle = 'global', $availableOnly = false, $limitFormat = true, $limit = null, $format = null) {
		if ($this->indexEngine instanceof GroupedWorksSolrConnector ||  $this->indexEngine instanceof GroupedWorksSolrConnector2) {
			return $this->indexEngine->getMoreLikeThis($id, $selectedAvailabilityToggle, $availableOnly, $limitFormat, $limit, $format, $this->getFieldsToReturn());
		}else{
			return [];
		}
	}

	public function getEngineName() : string {
		return 'GroupedWork';
	}

	public function getDefaultIndex() : string {
		return 'Keyword';
	}
}