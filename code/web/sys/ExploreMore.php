<?php

class ExploreMore {
	private $relatedCollections;

	private $numEntriesToAdd = 3;
	/**
	 * @param string $activeSection
	 * @param RecordInterface $recordDriver
	 */
	function loadExploreMoreSidebar($activeSection, $recordDriver){
		global $interface;
		global $timer;

		$exploreMoreSectionsToShow = array();

		$relatedCatalogContent = array();
		if ($activeSection == 'archive'){
			//If this is a book or a page, show a table of contents
			//Check to see if the record is part of a compound object.  If so we will want to link to the parent compound object.
			if ($recordDriver instanceof PageRecordDriver){
				/** @var AbstractFedoraObject $parentObject */
				$parentObject = $recordDriver->getParentObject();

				if ($parentObject != null){
					/** @var CompoundRecordDriver $parentDriver */
					$parentDriver = RecordDriverFactory::initRecordDriver($parentObject);

					//If the parent object is a section then get the parent again
					/** @var AbstractFedoraObject $parentOfParent */
					$parentOfParent = $parentDriver->getParentObject();
					if ($parentOfParent != null ){
						$parentOfParentDriver = RecordDriverFactory::initRecordDriver($parentOfParent);
						if ($parentOfParentDriver){
							$parentObject = $parentOfParent;
							$parentDriver = $parentOfParentDriver;
						}
					}

					$exploreMoreSectionsToShow['parentBook'] = array(
							'format' => 'list',
							'values' => array(
									array(
											'pid' => $parentObject->id,
											'label' => $parentDriver->getTitle(),
											'link' => $parentDriver->getRecordUrl(),
											'image' => $parentDriver->getBookcoverUrl('small'),
											'object' => $parentObject,
									),
							)
					);

					$exploreMoreSectionsToShow = $this->setupTableOfContentsForBook($parentDriver, $exploreMoreSectionsToShow, false);

					$this->relatedCollections = $parentDriver->getRelatedCollections();
					$this->relatedCollections['all'] = array(
						'label' => 'See All Digital Archive Collections',
						'link' => '/Archive/Home'
					);

					if (count($this->relatedCollections) > 1){ //Don't show if the only link is back to the All Collections page
						$displayType = count($this->relatedCollections) > 3 ? 'textOnlyList' : 'list';
						$exploreMoreSectionsToShow['relatedCollections'] = array(
								'format' => $displayType,
								'values' => $this->relatedCollections
						);
					}
				}
				$timer->logTime("Loaded table of contents");
			}elseif ($recordDriver instanceof BookDriver || $recordDriver instanceof CompoundRecordDriver){
				if ($recordDriver->getFormat() != 'Postcard'){
					/** @var CompoundRecordDriver $bookDriver */
					$exploreMoreSectionsToShow = $this->setupTableOfContentsForBook($recordDriver, $exploreMoreSectionsToShow, true);
					$timer->logTime("Loaded table of contents for book");
				}
			}

			/** @var IslandoraRecordDriver $archiveDriver */
			$archiveDriver = $recordDriver;
			if (!isset($this->relatedCollections)){
				$this->relatedCollections = $archiveDriver->getRelatedCollections();
				$this->relatedCollections['all'] = array(
					'label' => 'See All Digital Archive Collections',
					'link' => '/Archive/Home'
				);
				if (count($this->relatedCollections) > 1){ //Don't show if the only link is back to the All Collections page
					$displayType = count($this->relatedCollections) > 3 ? 'textOnlyList' : 'list';
					$exploreMoreSectionsToShow['relatedCollections'] = array(
							'format' => $displayType,
							'values' => $this->relatedCollections
					);
				}
				$timer->logTime("Loaded related collections for archive object");
			}

			//Find content from the catalog that is directly related to the object or collection based on linked data
			$relatedCatalogContent = $archiveDriver->getRelatedCatalogContent();
			if (count($relatedCatalogContent) > 0){
				$exploreMoreSectionsToShow['linkedCatalogRecords'] = array(
						'format' => 'scroller',
						'values' => $relatedCatalogContent
				);
			}
			$timer->logTime("Loaded related Catalog content");

			//Find other entities
		}

		//Get subjects that can be used for searching other systems
		$subjects = $recordDriver->getAllSubjectHeadings();
		$subjectsForSearching = array();
		$quotedSubjectsForSearching = array();
		foreach ($subjects as $subject){
			if (is_array($subject)){
				$searchSubject =  implode(" ", $subject);
			}else{
				$searchSubject = $subject;
			}
			$separatorPosition = strpos($searchSubject, ' -- ');
			if ($separatorPosition > 0){
				$searchSubject = substr($searchSubject, 0, $separatorPosition);
			}
			$searchSubject = preg_replace('/\(.*?\)/',"", $searchSubject);
			$searchSubject = trim(preg_replace('/[\/|:.,"]/',"", $searchSubject));
			$subjectsForSearching[] = $searchSubject;
			$quotedSubjectsForSearching[] = '"' . $searchSubject . '"';
		}

		$subjectsForSearching = array_slice($subjectsForSearching, 0, 5);
		$searchTerm = implode(' or ', $subjectsForSearching);
		$quotedSearchTerm = implode(' OR ', $quotedSubjectsForSearching);

		//Get objects from the archive based on search subjects
		if ($activeSection != 'archive'){
			foreach ($subjectsForSearching as $curSubject){
				$exactEntityMatches = $this->loadExactEntityMatches(array(), $curSubject);
				if (count($exactEntityMatches) > 0){
					$exploreMoreSectionsToShow['exactEntityMatches'] = array(
							'format' => 'list',
							'values' => usort($exactEntityMatches, 'ExploreMore::sortRelatedEntities')
					);
				}
			}
			$timer->logTime("Loaded related entities");
		}

		//Always load ebsco even if we are already in that section
		$ebscoMatches = $this->loadEbscoOptions('', array(), $searchTerm);
		if (count($ebscoMatches) > 0){
			$interface->assign('relatedArticles', $ebscoMatches);
		}

		//Load related content from the archive

		if ($activeSection == 'archive'){
			/** @var IslandoraRecordDriver $archiveDriver */
			$archiveDriver = $recordDriver;
			$relatedArchiveEntities = $this->getRelatedArchiveEntities($archiveDriver);
			if (count($relatedArchiveEntities) > 0){
				if (isset($relatedArchiveEntities['people'])){
					usort($relatedArchiveEntities['people'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedPeople'] = array(
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['people']
					);
				}
				if (isset($relatedArchiveEntities['places'])){
					usort($relatedArchiveEntities['places'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedPlaces'] = array(
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['places']
					);
				}
				if (isset($relatedArchiveEntities['organizations'])){
					usort($relatedArchiveEntities['organizations'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedOrganizations'] = array(
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['organizations']
					);
				}
				if (isset($relatedArchiveEntities['events'])){
					usort($relatedArchiveEntities['events'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedEvents'] = array(
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['events']
					);
				}
			}
		}

		$driver = $activeSection == 'archive' ? $recordDriver : null;
		$relatedArchiveContent = $this->getRelatedArchiveObjects($quotedSearchTerm, $driver);
		if (count($relatedArchiveContent) > 0) {
			$exploreMoreSectionsToShow['relatedArchiveData'] = array(
					'format' => 'subsections',
					'values' => $relatedArchiveContent
			);
		}

		if ($activeSection != 'catalog'){
			$relatedWorks = $this->getRelatedWorks($quotedSubjectsForSearching, $relatedCatalogContent);
			if ($relatedWorks['numFound'] > 0){
				$exploreMoreSectionsToShow['relatedCatalog'] = array(
						'format' => 'scrollerWithLink',
						'values' => $relatedWorks['values'],
						'link' => $relatedWorks['link'],
						'numFound' => $relatedWorks['numFound'],
				);
			}
		}

		if ($activeSection == 'archive'){
			/** @var IslandoraRecordDriver $archiveDriver */
			$archiveDriver = $recordDriver;

			//Load related subjects
			$relatedSubjects = $this->getRelatedArchiveSubjects($archiveDriver);
			if (count($relatedSubjects) > 0){
				usort($relatedSubjects, 'ExploreMore::sortRelatedEntities');
				$exploreMoreSectionsToShow['relatedSubjects'] = array(
						'format' => 'textOnlyList',
						'values' => $relatedSubjects
				);
			}

			//Load DPLA Content
			if ($archiveDriver->isEntity()){
				require_once ROOT_DIR . '/sys/SearchObject/DPLA.php';
				$dpla = new DPLA();
				//Check to see if we get any results from DPLA for this entity
				$dplaResults = $dpla->getDPLAResults('"' . $archiveDriver->getTitle() . '"');
				if (count($dplaResults)){
					$exploreMoreSectionsToShow['dpla'] = array(
							'format' => 'scrollerWithLink',
							'values' => $dplaResults['records'],
							'link' => 'http://dp.la/search?q=' . urlencode('"' . $archiveDriver->getTitle() . '"'),
							'openInNewWindow' => true,
					);
				}
			}else{
				//Display donor and contributor information
				$brandingResults = $archiveDriver->getBrandingInformation();

				if (count($brandingResults) > 0){
					//Sort and filter the acknowledgements
					$foundDuplicatePids = true;
					while ($foundDuplicatePids){
						$foundDuplicatePids = false;
						$indexToRemove = -1;
						$keys = array_keys($brandingResults);
						for ($i = 0; $i < count($brandingResults) - 1; $i++){
							for ($j = $i + 1; $j < count($brandingResults); $j++ ){
								if ($brandingResults[$keys[$i]]['pid'] == $brandingResults[$keys[$j]]['pid']){
									$foundDuplicatePids = true;
									if ($brandingResults[$keys[$i]]['sortIndex'] > $brandingResults[$keys[$j]]['sortIndex']){
										$indexToRemove = $i;
									}else{
										$indexToRemove = $j;
									}
									break;
								}
							}
							if ($foundDuplicatePids) break;
						}
						if ($foundDuplicatePids){
							unset($brandingResults[$keys[$indexToRemove]]);
						}
					}

					usort($brandingResults, 'sortBrandingResults');

					$exploreMoreSectionsToShow['acknowledgements'] = array(
							'format' => 'list',
							'values' => $brandingResults,
							'showTitles' => true,
					);
				}
			}
		}

		$interface->assign('exploreMoreSections', $exploreMoreSectionsToShow);
	}

	function getExploreMoreQuery(){
		if (isset($_REQUEST['lookfor'])){
			$searchTerm = $_REQUEST['lookfor'];
		}else{
			$searchTerm = '';
		}
		if (!$searchTerm){
			//No search term found, try to get a search term based on applied filters (just one)
			if (isset($_REQUEST['filter'])){
				foreach ($_REQUEST['filter'] as $filter){
					if (!is_array($filter) && strlen($filter) > 0) {
						if (strpos($filter, ':') !== false){
							$filterVals = explode(':', $filter, 2);
							if ($filterVals[0] != 'mods_genre_s' &&
									$filterVals[0] != 'literary_form' && $filterVals[0] != 'literary_form_full' &&
									$filterVals[0] != 'target_audience' && $filterVals[0] != 'target_audience_full'
							) {
								$searchTerm = str_replace('"', '', $filterVals[1]);
								break;
							}
						}
					}
				}
			}
		}
		return $searchTerm;
	}

	function loadExploreMoreBar($activeSection, $searchTerm){
		if (isset($_REQUEST['page']) && $_REQUEST['page'] > 1){
			return [];
		}
		//Get data from the repository
		global $interface;
		global $configArray;
		/** @var Library $library */
		global $library;
		global $enabledModules;
		$exploreMoreOptions = [
			'sampleRecords' => [],
			'searchLinks' => []
		];

		$islandoraActive = false;
		$islandoraSearchObject = null;
		if ($library->enableArchive && $activeSection != 'archive'){
			/** @var SearchObject_IslandoraSearcher $islandoraSearchObject */
			$islandoraSearchObject = SearchObjectFactory::initSearchObject('Islandora');
			$islandoraSearchObject->init();
            $islandoraSearchObject->disableSpelling();
			$islandoraActive = $islandoraSearchObject->pingServer(false);
		}

		//Check the archive to see if we match an entity.
		if ($islandoraActive) {
			$exploreMoreOptions = $this->loadExactEntityMatches($exploreMoreOptions, $searchTerm);
		}

		$exploreMoreOptions = $this->loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm);

		if (array_key_exists('Events', $enabledModules)) {
			$exploreMoreOptions = $this->loadEventOptions($activeSection, $exploreMoreOptions, $searchTerm);
		}

		if (array_key_exists('Web Indexer', $enabledModules)) {
			$exploreMoreOptions = $this->loadWebIndexerOptions($activeSection, $exploreMoreOptions, $searchTerm);
		}

		$exploreMoreOptions = $this->loadListOptions($activeSection, $exploreMoreOptions, $searchTerm);

		if (array_key_exists('Open Archives', $enabledModules) && $library->enableOpenArchives) {
			$exploreMoreOptions = $this->loadOpenArchiveOptions($activeSection, $exploreMoreOptions, $searchTerm);
		}

		if (array_key_exists('EBSCO_EDS', $enabledModules)) {
			$exploreMoreOptions = $this->loadEbscoOptions($activeSection, $exploreMoreOptions, $searchTerm);
		}

		if ($islandoraActive){
			$exploreMoreOptions = $this->loadIslandoraOptions($searchTerm, $configArray, $islandoraSearchObject, $exploreMoreOptions);
		}

		//Consolidate explore more options, we'd like to show the search links if possible and then pad with sample records
		$exploreMoreDisplayOptions = [];

		$minSampleRecordsToAdd = 4 - count($exploreMoreOptions['searchLinks']);
		if ($minSampleRecordsToAdd < 0) {
			$minSampleRecordsToAdd = 0;
		}
		//Get at least one sample record from each source
		for ($sampleIndex = 0; $sampleIndex < 4; $sampleIndex++){
			foreach ($exploreMoreOptions['sampleRecords'] as $sampleRecords){
				if (array_key_exists($sampleIndex, $sampleRecords)){
					$exploreMoreDisplayOptions[] = $sampleRecords[$sampleIndex];
					if (count($exploreMoreDisplayOptions) >= $minSampleRecordsToAdd && $sampleIndex >= 1){
						break;
					}
				}
			}
			if (count($exploreMoreDisplayOptions) >= $minSampleRecordsToAdd){
				break;
			}
		}
		//Add in all of the search links
		$exploreMoreDisplayOptions = array_merge($exploreMoreDisplayOptions, $exploreMoreOptions['searchLinks']);

		$interface->assign('exploreMoreOptions', $exploreMoreDisplayOptions);

		return $exploreMoreDisplayOptions;
	}

	/**
	 * @param $exploreMoreOptions
	 * @param string $searchTerm
	 * @return array
	 */
	protected function loadExactEntityMatches($exploreMoreOptions, $searchTerm) {
		global $library;
		global $configArray;
		if ($library->enableArchive) {
			if (!array_key_exists('islandora', $exploreMoreOptions['sampleRecords'])){
				$exploreMoreOptions['sampleRecords']['islandora'] = [];
			}
			if (isset($configArray['Islandora']) && isset($configArray['Islandora']['solrUrl']) && $searchTerm) {
				/** @var SearchObject_IslandoraSearcher $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject->init();
                $searchObject->disableSpelling();
				$searchObject->setDebugging(false, false);

				//First look specifically for
				$searchObject->setSearchTerms(array(
						'lookfor' => $searchTerm,
						'index' => 'IslandoraTitle'
				));
				$searchObject->clearHiddenFilters();
				$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
				//First search for people, places, and things
				$searchObject->addHiddenFilter('RELS_EXT_hasModel_uri_s', "(*placeCModel OR *personCModel OR *eventCModel)");
				$response = $searchObject->processSearch(true, false);
				if ($response && $response['response']['numFound'] > 0) {
					//Check the docs to see if we have a match for a person, place, or event
					$numProcessed = 0;
					foreach ($response['response']['docs'] as $doc) {
						$entityDriver = RecordDriverFactory::initRecordDriver($doc);
						$exploreMoreOptions['sampleRecords']['islandora'][] = array(
								'label' => $entityDriver->getTitle(),
								'image' => $entityDriver->getBookcoverUrl('medium'),
								'link' => $entityDriver->getRecordUrl(),
						);
						$numProcessed++;
						if ($numProcessed >= 3) {
							break;
						}
					}
				}
			}
		}

		return $exploreMoreOptions;
	}

	protected function loadWebIndexerOptions($activeSection, $exploreMoreOptions, $searchTerm)
	{
		if ($activeSection != 'websites') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['websites'] = [];
				/** @var SearchObject_ListsSearcher $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Websites');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms(array(
					'lookfor' => $searchTerm,
					'index' => 'WebsiteKeyword'
				));
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = array(
							'label' => "Web pages ($numCatalogResults)",
							'description' => "Web pages ($numCatalogResults)",
							//TODO: provide a better icon
							'image' => '/images/webpage.png',
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1
						);
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var ListsRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['websites'][] = array(
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'onclick' => 'AspenDiscovery.Websites.trackUsage(' .  $driver->getId() .')',
								'usageCount' => 1
							);
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	protected function loadEventOptions($activeSection, $exploreMoreOptions, $searchTerm) {
		if ($activeSection != 'events') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['events'] = [];
				/** @var SearchObject_EventsSearcher $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Events');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms(array(
					'lookfor' => $searchTerm,
					'index' => 'EventsKeyword'
				));
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = array(
							'label' => "Events ($numCatalogResults)",
							'description' => "Events ($numCatalogResults)",
							'image' => '/interface/themes/responsive/images/events.png',
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1
						);
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var ListsRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						$numCatalogResults = $results['response']['numFound'];
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['events'][] = array(
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'usageCount' => 1
							);
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	protected function loadListOptions($activeSection, $exploreMoreOptions, $searchTerm)
	{
		if ($activeSection != 'lists') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['lists'] = [];

				/** @var SearchObject_ListsSearcher $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Lists');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms(array(
					'lookfor' => $searchTerm,
					'index' => 'ListsKeyword'
				));
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = array(
							'label' => "Lists ($numCatalogResults)",
							'description' => "Lists ($numCatalogResults)",
							//TODO: provide a better icon
							'image' => '/interface/themes/responsive/images/library_symbol.png',
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1
						);
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var ListsRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['lists'][] = array(
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'usageCount' => 1
							);
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	/**
	 * @param $activeSection
	 * @param $exploreMoreOptions
	 * @param $searchTerm
	 * @return array
	 */
	protected function loadOpenArchiveOptions($activeSection, $exploreMoreOptions, $searchTerm)
	{
		if ($activeSection != 'open_archives') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['open_archives'] = [];
				/** @var SearchObject_OpenArchivesSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('OpenArchives');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms(array(
					'lookfor' => $searchTerm,
					'index' => 'OpenArchivesKeyword'
				));
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = array(
							'label' => "Archive Results ($numCatalogResults)",
							'description' => "Archive Results ($numCatalogResults)",
							//TODO: Provide a better title
							'image' => '/interface/themes/responsive/images/library_symbol.png',
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1
						);
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var OpenArchivesRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['open_archives'][] = array(
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'onclick' => "AspenDiscovery.OpenArchives.trackUsage('{$driver->getId()}')",
								'usageCount' => 1
							);
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	/**
	 * @param $activeSection
	 * @param $exploreMoreOptions
	 * @param $searchTerm
	 * @return array
	 */
	protected function loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm) {
		if ($activeSection != 'catalog') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['catalog'] = [];
				/** @var SearchObject_GroupedWorkSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject();
				$searchObjectSolr->init('local');
                $searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms(array(
						'lookfor' => $searchTerm,
						'index' => 'Keyword'
				));
				$searchObjectSolr->clearHiddenFilters();
				$searchObjectSolr->clearFilters();
				if ($activeSection == 'open_archives' || $activeSection == 'archive') {
					$facetConfig = $searchObjectSolr->getFacetConfig();
					if (array_key_exists('literary_form', $facetConfig)){
						$searchObjectSolr->addFilter('literary_form:"Non Fiction"');
					}elseif (array_key_exists('literary_form_full', $facetConfig)){
						$searchObjectSolr->addFilter('literary_form_full:"Non Fiction"');
					}
					if (array_key_exists('target_audience', $facetConfig)){
						if ($facetConfig['target_audience']->multiSelect){
							$searchObjectSolr->addFilter('target_audience:Adult');
							$searchObjectSolr->addFilter('target_audience:Unknown');
							$searchObjectSolr->addFilter('target_audience:General');
						}else{
							$searchObjectSolr->addFilter('target_audience:(Adult OR Unknown OR General)');
						}
					}elseif (array_key_exists('target_audience_full', $facetConfig)){
						if ($facetConfig['target_audience_full']->multiSelect){
							$searchObjectSolr->addFilter('target_audience_full:Adult');
							$searchObjectSolr->addFilter('target_audience_full:Unknown');
							$searchObjectSolr->addFilter('target_audience_full:General');
						}else{
							$searchObjectSolr->addFilter('target_audience_full:(Adult OR Unknown OR General)');
						}
					}
				}
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					foreach ($results['response']['docs'] as $doc) {
						/** @var GroupedWorkDriver $driver */
						$driver = RecordDriverFactory::initRecordDriver($doc);
						$numCatalogResults = $results['response']['numFound'];
						if ($numCatalogResultsAdded == $this->numEntriesToAdd && $numCatalogResults > ($this->numEntriesToAdd + 1)) {
							//Add a link to remaining catalog results
							$exploreMoreOptions['searchLinks'][] = array(
									'label' => "Catalog Results ($numCatalogResults)",
									'description' => "Catalog Results ($numCatalogResults)",
									'image' => '/interface/themes/responsive/images/library_symbol.png',
									'link' => $searchObjectSolr->renderSearchUrl(),
									'usageCount' => 1
							);
						} else {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['catalog'][] = array(
									'label' => $driver->getTitle(),
									'description' => $driver->getTitle(),
									'image' => $driver->getBookcoverUrl('medium'),
									'link' => $driver->getLinkUrl(),
									'usageCount' => 1
							);
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	/**
	 * @param $activeSection
	 * @param $searchTerm
	 * @param $exploreMoreOptions
	 * @return array
	 */
	public function loadEbscoOptions($activeSection, $exploreMoreOptions, $searchTerm) {
		global $library;
		global $enabledModules;
		if (array_key_exists('EBSCO_EDS', $enabledModules) && $library->edsSettingsId != -1 && $activeSection != 'ebsco_eds') {
			//Load EDS options
			require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
			$edsApi = EDS_API::getInstance();
			if ($edsApi->authenticate()) {
				//Find related titles
				$edsResults = $edsApi->getSearchResults($searchTerm);
				if ($edsResults) {
					$exploreMoreOptions['sampleRecords']['ebsco_eds'] = [];
					$numMatches = $edsResults->Statistics->TotalHits;
					if ($numMatches > 0) {
						//Check results based on common facets
						foreach ($edsResults->AvailableFacets->AvailableFacet as $facetInfo) {
							if ($facetInfo->Id == 'SourceType') {
								foreach ($facetInfo->AvailableFacetValues->AvailableFacetValue as $facetValue) {
									$facetValueStr = (string)$facetValue->Value;
									if (in_array($facetValueStr, array('Magazines', 'News', 'Academic Journals', 'Primary Source Documents'))) {
										$numFacetMatches = (int)$facetValue->Count;
										$iconName = 'ebsco_' . str_replace(' ', '_', strtolower($facetValueStr));
										$exploreMoreOptions['sampleRecords']['ebsco_eds'][] = array(
												'label' => "$facetValueStr ({$numFacetMatches})",
												'description' => "{$facetValueStr} in EBSCO related to {$searchTerm}",
												'image' => "/interface/themes/responsive/images/{$iconName}.png",
												'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm) . '&filter[]=' . $facetInfo->Id . ':' . $facetValueStr,
										);
									}

								}
							}
						}

						if ($numMatches > 1) {
							$exploreMoreOptions['searchLinks'][] = array(
								'label' => "All EBSCO Results ({$numMatches})",
								'description' => "All Results in EBSCO related to {$searchTerm}",
								'image' => '/interface/themes/responsive/images/ebsco_eds.png',
								'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm)
							);
						}
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	/**
	 * @param IslandoraRecordDriver $archiveDriver
	 *
	 * @return array
	 */
	public function getRelatedArchiveSubjects($archiveDriver){
		$relatedObjects = $archiveDriver->getDirectlyRelatedArchiveObjects();
		$relatedSubjects = array();

		foreach ($relatedObjects['objects'] as $object){
			/** @var IslandoraRecordDriver $relatedObjectDriver */
			$relatedObjectDriver = $object['driver'];
			foreach ($relatedObjectDriver->getAllSubjectsWithLinks() as $subject){
				if (!isset($relatedSubjects[$subject['label']])){
					$relatedSubjects[$subject['label']] = $subject;
					if (!isset($relatedSubjects[$subject['label']]['linkingReason'])) {
						$relatedSubjects[$subject['label']]['linkingReason'] = "Used in: ";
					}
				}

				if (strpos($relatedSubjects[$subject['label']]['linkingReason'], "\r\n - " . $relatedObjectDriver->getTitle()) === false){
					$relatedSubjects[$subject['label']]['linkingReason'] .= "\r\n - " . $relatedObjectDriver->getTitle();
				}

			}
		}
		return $relatedSubjects;
	}

	/**
	 * @param string $searchTerm
	 * @param IslandoraRecordDriver $archiveDriver
	 * @return array
	 */
	public function getRelatedArchiveObjects($searchTerm, $archiveDriver = null) {
		global $timer;
		$relatedArchiveContent = array();

		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		/** @var SearchObject_IslandoraSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
        $searchObject->disableSpelling();
		$searchObject->setDebugging(false, false);

		//Get a list of objects in the archive related to this search
		$searchObject->setSearchTerms(array(
				'lookfor' => $searchTerm,
				//TODO: do additional testing with this since it was reversed.
				'index' => 'IslandoraKeyword'
				//'index' => $searchSubjectsOnly ? 'IslandoraSubject' : 'IslandoraKeyword'
		));
		$searchObject->clearHiddenFilters();
		$searchObject->clearFilters();
		$searchObject->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
		if ($archiveDriver != null){
			$searchObject->addHiddenFilter('!PID', str_replace(':', '\:', $archiveDriver->getUniqueID()));
		}
		$searchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
		$searchObject->addFacet('mods_genre_s', 'Format');

		$response = $searchObject->processSearch(true, false);
		if ($response && $response['response']['numFound'] > 0) {
			//Using the facets, look for related entities
			foreach ($response['facet_counts']['facet_fields']['mods_genre_s'] as $relatedContentType) {
				/** @var SearchObject_IslandoraSearcher $searchObject2 */
				$searchObject2 = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject2->init();
                $searchObject2->disableSpelling();
				$searchObject2->setDebugging(false, false);
				if ($archiveDriver != null){
					$searchObject2->addHiddenFilter('!PID', str_replace(':', '\:', $archiveDriver->getUniqueID()));
				}
				$searchObject2->setSearchTerms(array(
						'lookfor' => $searchTerm,
						'index' => 'IslandoraKeyword'
						//'index' => $searchSubjectsOnly ? 'IslandoraSubject' : 'IslandoraKeyword'
				));
				$searchObject2->clearFilters();
				$searchObject2->clearHiddenFilters();
				$searchObject2->addHiddenFilter('!RELS_EXT_isViewableByRole_literal_ms', "administrator");
				if ($archiveDriver != null){
					$searchObject2->addHiddenFilter('!PID', str_replace(':', '\:', $archiveDriver->getUniqueID()));
				}
				$searchObject2->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
				$searchObject2->addFilter("mods_genre_s:{$relatedContentType[0]}");
				$response2 = $searchObject2->processSearch(true, false);
				if ($response2 && $response2['response']['numFound'] > 0) {
					$firstObject = reset($response2['response']['docs']);
					$numMatches = $response2['response']['numFound'];
					if ($archiveDriver != null && $firstObject['PID'] == $archiveDriver->getUniqueID()){
						if ($numMatches == 1) {
							continue;
						}else{
							$firstObject = next($response2['response']['docs']);
						}
					}
					/** @var IslandoraRecordDriver $firstObjectDriver */
					$firstObjectDriver = RecordDriverFactory::initRecordDriver($firstObject);
					$contentType = ucwords(translate($relatedContentType[0]));
					if ($numMatches == 1) {
						$relatedArchiveContent[] = array(
								'title' => $firstObjectDriver->getTitle(),
								'description' => $firstObjectDriver->getTitle(),
								'image' => $firstObjectDriver->getBookcoverUrl('medium'),
								'link' => $firstObjectDriver->getRecordUrl(),
						);
					} else {
						$relatedArchiveContent[] = array(
								'title' => "{$contentType}s ({$numMatches})",
								'description' => "{$contentType}s related to this",
								'image' => $firstObjectDriver->getBookcoverUrl('medium'),
								'link' => $searchObject2->renderSearchUrl(),
						);
					}
				}
			}
		}
		$timer->logTime('Loaded related archive objects');
		return $relatedArchiveContent;
	}

	/**
	 * Load entities that are related to this entity but that are not directly related.
	 * I.e. we want to see
	 *
	 * @param IslandoraRecordDriver $archiveDriver
	 * @return array
	 */
	public function getRelatedArchiveEntities($archiveDriver){
		global $timer;
		$directlyRelatedPeople = $archiveDriver->getRelatedPeople();
		$directlyRelatedPlaces = $archiveDriver->getRelatedPlaces();
		$directlyRelatedOrganizations = $archiveDriver->getRelatedOrganizations();
		$directlyRelatedEvents = $archiveDriver->getRelatedEvents();

		$relatedPeople = array();
		$relatedPlaces = array();
		$relatedOrganizations = array();
		$relatedEvents = array();
		$relatedObjects = $archiveDriver->getDirectlyRelatedArchiveObjects();

		foreach ($relatedObjects['objects'] as $object){
			/** @var IslandoraRecordDriver $objectDriver */
			$objectDriver = $object['driver'];

			$peopleRelatedToObject = $objectDriver->getRelatedPeople();
			foreach($peopleRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedPeople)){
					$relatedPeople = $this->addAssociatedEntity($entity, $relatedPeople, $objectDriver);
				}
			}

			$placesRelatedToObject = $objectDriver->getRelatedPlaces();
			foreach($placesRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedPlaces)){
					$relatedPlaces = $this->addAssociatedEntity($entity, $relatedPlaces, $objectDriver);
				}
			}

			$organizationsRelatedToObject = $objectDriver->getRelatedOrganizations();
			foreach($organizationsRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedOrganizations)){
					$relatedOrganizations = $this->addAssociatedEntity($entity, $relatedOrganizations, $objectDriver);
				}
			}

			$eventsRelatedToObject = $objectDriver->getRelatedEvents();
			foreach($eventsRelatedToObject as $entity){
				if ($entity['pid'] != $archiveDriver->getUniqueID() && !array_key_exists($entity['pid'], $directlyRelatedEvents)){
					$relatedEvents = $this->addAssociatedEntity($entity, $relatedEvents, $objectDriver);
				}
			}
		}

		$relatedEntities = array();
		if (count($relatedPeople) > 0){
			$relatedEntities['people'] = $relatedPeople;
		}
		if (count($relatedPlaces) > 0){
			$relatedEntities['places'] = $relatedPlaces;
		}
		if (count($relatedOrganizations) > 0){
			$relatedEntities['organizations'] = $relatedOrganizations;
		}
		if (count($relatedEvents) > 0){
			$relatedEntities['events'] = $relatedEvents;
		}
		$timer->logTime('Loaded related entities');
		return $relatedEntities;
	}

	/**
	 * @param string[] $relatedSubjects
	 * @param array    $directlyRelatedRecords
	 *
	 * @return array
	 */
	public function getRelatedWorks($relatedSubjects, $directlyRelatedRecords) {
		//Load related catalog content
		$searchTerm = implode(" OR ", $relatedSubjects);

		$similarTitles = array(
				'numFound' => 0,
				'link' => '',
				'values' => array()
		);

		if (strlen($searchTerm) > 0) {
			//Do not include any records that we have specific links to
			$recordsToAvoid = '';
			foreach ($directlyRelatedRecords as $record){
				if (strlen($recordsToAvoid) > 0){
					$recordsToAvoid .= ' OR ';
				}
				$recordsToAvoid .= $record['id'];
			}
			/*if (strlen($recordsToAvoid) > 0){
				$searchTerm .= " AND NOT id:($recordsToAvoid)";
			}*/

			/** @var SearchObject_GroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init('local', $searchTerm);
            $searchObject->disableSpelling();
			$searchObject->setSearchTerms(array(
					'lookfor' => $searchTerm,
					'index' => 'Keyword'
			));
			$searchObject->addFilter('literary_form_full:Non Fiction');
			$searchObject->addFilter('target_audience:(Adult OR Unknown)');
			$searchObject->addHiddenFilter('!id', $recordsToAvoid);

			$searchObject->setPage(1);
			$searchObject->setLimit($this->numEntriesToAdd + 1);
			$results = $searchObject->processSearch(true, false);

			if ($results && isset($results['response'])) {
				$similarTitles = array(
						'numFound' => $results['response']['numFound'],
						'link' => $searchObject->renderSearchUrl(),
						'topHits' => array()
				);
				foreach ($results['response']['docs'] as $doc) {
					/** @var GroupedWorkDriver $driver */
					$driver = RecordDriverFactory::initRecordDriver($doc);
					$similarTitle = array(
							'label' => $driver->getTitle(),
							'link' => $driver->getLinkUrl(),
							'image' => $driver->getBookcoverUrl('medium')
					);
					$similarTitles['values'][] = $similarTitle;
				}
			}
		}
		return $similarTitles;
	}

	/**
     * @param array $entity
     * @param array $relatedEntities
     * @param IslandoraRecordDriver $objectDriver
     * @return array
     */
	private function addAssociatedEntity($entity, $relatedEntities, $objectDriver) {
		if (!isset($relatedEntities[$entity['pid']])){
			$relatedEntities[$entity['pid']] = $entity;
			if (!isset($relatedEntities[$entity['pid']]['linkingReason'])) {
				$relatedEntities[$entity['pid']]['linkingReason'] = "Both link to: ";
			}
		}

		if (strpos($relatedEntities[$entity['pid']]['linkingReason'], "\r\n - " . $objectDriver->getTitle()) === false){
			$relatedEntities[$entity['pid']]['linkingReason'] .= "\r\n - " . $objectDriver->getTitle();
		}

		return $relatedEntities;
	}

	static function sortRelatedEntities($a, $b){
		return strcasecmp($a["label"], $b["label"]);
	}

	/**
	 * @param CompoundRecordDriver $bookDriver
	 * @param array $exploreMoreSectionsToShow
	 * @param bool $currentlyShowingBook
	 * @return array
	 */
	private function setupTableOfContentsForBook($bookDriver, $exploreMoreSectionsToShow, $currentlyShowingBook) {
		global $interface;
		$bookContents = $bookDriver->loadBookContents();
		if (count($bookContents) > 1){
			$exploreMoreSectionsToShow['tableOfContents'] = array(
					'title' => 'Table of Contents',
					'format' => 'tableOfContents',
					'values' => array()
			);
			if (!$currentlyShowingBook){
				$exploreMoreSectionsToShow['tableOfContents']['format'] = 'textOnlyList';
			}
			foreach ($bookContents as $section){
				$firstPageInSection = reset($section['pages']);
				$section = array(
						'pid' => $firstPageInSection['pid'],
						'label' => $section['title'],
				);
				if (!$currentlyShowingBook){
					$section['link'] = $bookDriver->getRecordUrl() . '?pagePid=' . $firstPageInSection['pid'];
				}
				$exploreMoreSectionsToShow['tableOfContents']['values'][] = $section;
			}
		}
		$interface->assign('bookPid', $bookDriver->getUniqueId());
		return $exploreMoreSectionsToShow;
	}

	/**
	 * @param $searchTerm
	 * @param array $configArray
	 * @param SearchObject_IslandoraSearcher|null $islandoraSearchObject
	 * @param array $exploreMoreOptions
	 * @return array
	 */
	protected function loadIslandoraOptions($searchTerm, array $configArray, ?SearchObject_IslandoraSearcher $islandoraSearchObject, array $exploreMoreOptions): array
	{
		if (isset($configArray['Islandora']) && isset($configArray['Islandora']['solrUrl']) && !empty($searchTerm)) {
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();


			$islandoraSearchObject->setDebugging(false, false);

			//Get a list of objects in the archive related to this search
			$islandoraSearchObject->setSearchTerms(array(
				'lookfor' => $searchTerm,
				'index' => 'IslandoraKeyword'
			));
			$islandoraSearchObject->addFacet('mods_genre_s', 'Format');
			$islandoraSearchObject->addFacet('RELS_EXT_isMemberOfCollection_uri_ms', 'Collection');
			$islandoraSearchObject->addFacet('mods_extension_marmotLocal_relatedEntity_person_entityPid_ms', 'People');
			$islandoraSearchObject->addFacet('mods_extension_marmotLocal_relatedEntity_place_entityPid_ms', 'Places');
			$islandoraSearchObject->addFacet('mods_extension_marmotLocal_relatedEntity_event_entityPid_ms', 'Events');
			$islandoraSearchObject->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");

			$response = $islandoraSearchObject->processSearch(true, false);
			if ($response && $response['response']['numFound'] > 0) {
				//Related content
				foreach ($response['facet_counts']['facet_fields']['mods_genre_s'] as $relatedContentType) {
					/** @var SearchObject_IslandoraSearcher $searchObject2 */
					$searchObject2 = SearchObjectFactory::initSearchObject('Islandora');
					$searchObject2->init();
					$searchObject2->disableSpelling();
					$searchObject2->setDebugging(false, false);
					$searchObject2->setSearchTerms(array(
						'lookfor' => $searchTerm,
						'index' => 'IslandoraKeyword'
					));
					$searchObject2->addFilter("mods_genre_s:{$relatedContentType[0]}");
					$searchObject2->addHiddenFilter('!mods_extension_marmotLocal_pikaOptions_showInSearchResults_ms', "no");
					$response2 = $searchObject2->processSearch(true, false);
					if ($response2 && $response2['response']['numFound'] > 0) {
						$firstObject = reset($response2['response']['docs']);
						/** @var IslandoraRecordDriver $firstObjectDriver */
						$firstObjectDriver = RecordDriverFactory::initRecordDriver($firstObject);
						$numMatches = $response2['response']['numFound'];
						$contentType = ucwords(translate($relatedContentType[0]));
						if ($numMatches == 1) {
							$exploreMoreOptions['searchLinks'][] = array(
								'label' => "{$contentType}s ({$numMatches})",
								'description' => "{$contentType}s related to {$searchObject2->getQuery()}",
								'image' => $firstObjectDriver->getBookcoverUrl('medium'),
								'link' => $firstObjectDriver->getRecordUrl(),
							);
						} else {
							$exploreMoreOptions['searchLinks'][] = array(
								'label' => "{$contentType}s ({$numMatches})",
								'description' => "{$contentType}s related to {$searchObject2->getQuery()}",
								'image' => $firstObjectDriver->getBookcoverUrl('medium'),
								'link' => $searchObject2->renderSearchUrl(),
							);
						}
					}
				}

				if (!array_key_exists('islandora', $exploreMoreOptions['sampleRecords'])){
					$exploreMoreOptions['sampleRecords']['islandora'] = [];
				}

				//Related collections
				foreach ($response['facet_counts']['facet_fields']['RELS_EXT_isMemberOfCollection_uri_ms'] as $collectionInfo) {
					$archiveObject = $fedoraUtils->getObject($collectionInfo[0]);
					if ($archiveObject != null) {
						$okToAdd = $fedoraUtils->isObjectValidForDisplay($archiveObject);

						if ($okToAdd) {
							$exploreMoreOptions['sampleRecords']['islandora'][] = array(
								'label' => $archiveObject->label,
								'description' => $archiveObject->label,
								'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'medium'),
								'link' => "/Archive/{$archiveObject->id}/Exhibit",
								'usageCount' => $collectionInfo[1]
							);
						}
					}
				}

				//Related Entities
				if (isset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_person_entityPid_ms'])) {
					$personInfo = reset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_person_entityPid_ms']);
					$numPeople = count($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_person_entityPid_ms']);
					if ($numPeople == 100) {
						$numPeople = '100+';
					}
					$archiveObject = $fedoraUtils->getObject($personInfo[0]);
					$islandoraSearchObject->clearFilters();
					$islandoraSearchObject->addFilter('RELS_EXT_hasModel_uri_s:info:fedora/islandora:personCModel');
					if ($archiveObject != null) {
						$exploreMoreOptions['searchLinks'][] = array(
							'label' => "People (" . $numPeople . ")",
							'description' => "People related to {$islandoraSearchObject->getQuery()}",
							'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'medium', 'personCModel'),
							'link' => '/Archive/RelatedEntities?lookfor=' . urlencode($searchTerm) . '&entityType=person',
							'usageCount' => $numPeople
						);
					}
				}
				if (isset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_place_entityPid_ms'])) {
					$placeInfo = reset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_place_entityPid_ms']);
					$numPlaces = count($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_place_entityPid_ms']);
					if ($numPlaces == 100) {
						$numPlaces = '100+';
					}
					$archiveObject = $fedoraUtils->getObject($placeInfo[0]);
					$islandoraSearchObject->clearFilters();
					$islandoraSearchObject->addFilter('RELS_EXT_hasModel_uri_s:info:fedora/islandora:placeCModel');
					if ($archiveObject != null) {
						$exploreMoreOptions['searchLinks'][] = array(
							'label' => "Places (" . $numPlaces . ")",
							'description' => "Places related to {$islandoraSearchObject->getQuery()}",
							'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'medium', 'placeCModel'),
							'link' => '/Archive/RelatedEntities?lookfor=' . urlencode($searchTerm) . '&entityType=place',
							'usageCount' => $numPlaces
						);
					}
				}
				if (isset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_event_entityPid_ms'])) {
					$eventInfo = reset($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_event_entityPid_ms']);
					$numEvents = count($response['facet_counts']['facet_fields']['mods_extension_marmotLocal_relatedEntity_event_entityPid_ms']);
					if ($numEvents == 100) {
						$numEvents = '100+';
					}
					$archiveObject = $fedoraUtils->getObject($eventInfo[0]);
					$islandoraSearchObject->clearFilters();
					$islandoraSearchObject->addFilter('RELS_EXT_hasModel_uri_s:info:fedora/islandora:eventCModel');
					if ($archiveObject != null) {
						$exploreMoreOptions['searchLinks'][] = array(
							'label' => "Events (" . $numEvents . ")",
							'description' => "Places related to {$islandoraSearchObject->getQuery()}",
							'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'medium', 'eventCModel'),
							'link' => '/Archive/RelatedEntities?lookfor=' . urlencode($searchTerm) . '&entityType=event',
							'usageCount' => $numEvents
						);
					}
				}
			}
		}
		return $exploreMoreOptions;
	}
}

function sortBrandingResults($a, $b){
	if ($a['sortIndex'] == $b['sortIndex']){
		return strcasecmp($a['label'], $b['label']);
	}
	return ($a['sortIndex'] < $b['sortIndex']) ? -1 : 1;
}