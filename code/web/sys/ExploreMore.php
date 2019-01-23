<?php

/**
 * Contains functionality to load content related to a search or to another object
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/20/2016
 * Time: 8:06 PM
 */
class ExploreMore {
	private $relatedCollections;

	/**
	 * @param string $activeSection
	 * @param IndexRecord $recordDriver
	 */
	function loadExploreMoreSidebar($activeSection, $recordDriver){
		//TODO: remove title from $exploreMoreSectionsToShow array
		global $interface;
		global $configArray;
		global $timer;

		if (isset($configArray['Islandora']) && isset($configArray['Islandora']['solrUrl'])) {
			require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
			$fedoraUtils = FedoraUtils::getInstance();
		}
		$exploreMoreSectionsToShow = array();

		$relatedPikaContent = array();
		if ($activeSection == 'archive'){
			//If this is a book or a page, show a table of contents
			//Check to see if the record is part of a compound object.  If so we will want to link to the parent compound object.
			if ($recordDriver instanceof PageDriver){
				/** @var IslandoraDriver $parentObject */
				$parentObject = $recordDriver->getParentObject();

				if ($parentObject != null){
					/** @var IslandoraDriver $parentDriver */
					$parentDriver = RecordDriverFactory::initRecordDriver($parentObject);

					//If the parent object is a section then get the parent again
					/** @var IslandoraDriver $parentOfParent */
					$parentOfParent = $parentDriver->getParentObject();
					if ($parentOfParent != null ){
						$parentOfParentDriver = RecordDriverFactory::initRecordDriver($parentOfParent);
						if ($parentOfParentDriver){
							$parentObject = $parentOfParent;
							$parentDriver = $parentOfParentDriver;
						}
					}

					$exploreMoreSectionsToShow['parentBook'] = array(
//							'title' => 'Entire Book',
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
//								'title' => 'Related Archive Collections',
								'format' => $displayType,
								'values' => $this->relatedCollections
						);
					}
				}
				$timer->logTime("Loaded table of contents");
			}elseif ($recordDriver instanceof BookDriver || $recordDriver instanceof CompoundDriver){
				if ($recordDriver->getFormat() != 'Postcard'){
					/** @var CompoundDriver $bookDriver */
					$exploreMoreSectionsToShow = $this->setupTableOfContentsForBook($recordDriver, $exploreMoreSectionsToShow, true);
					$timer->logTime("Loaded table of contents for book");
				}
			}

			/** @var IslandoraDriver $archiveDriver */
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
//							'title' => 'Related Archive Collections',
							'format' => $displayType,
							'values' => $this->relatedCollections
					);
				}
				$timer->logTime("Loaded related collections for archive object");
			}

			//Find content from the catalog that is directly related to the object or collection based on linked data
			$relatedPikaContent = $archiveDriver->getRelatedPikaContent();
			if (count($relatedPikaContent) > 0){
				$exploreMoreSectionsToShow['linkedCatalogRecords'] = array(
//						'title' => 'Librarian Picks',
						'format' => 'scroller',
						'values' => $relatedPikaContent
				);
			}
			$timer->logTime("Loaded related Pika content");

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
//							'title' => 'Related People, Places &amp; Events',
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
			/** @var IslandoraDriver $archiveDriver */
			$archiveDriver = $recordDriver;
			$relatedArchiveEntities = $this->getRelatedArchiveEntities($archiveDriver);
			if (count($relatedArchiveEntities) > 0){
				if (isset($relatedArchiveEntities['people'])){
					usort($relatedArchiveEntities['people'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedPeople'] = array(
//							'title' => 'Associated People',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['people']
					);
				}
				if (isset($relatedArchiveEntities['places'])){
					usort($relatedArchiveEntities['places'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedPlaces'] = array(
//							'title' => 'Associated Places',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['places']
					);
				}
				if (isset($relatedArchiveEntities['organizations'])){
					usort($relatedArchiveEntities['organizations'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedOrganizations'] = array(
//							'title' => 'Associated Organizations',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['organizations']
					);
				}
				if (isset($relatedArchiveEntities['events'])){
					usort($relatedArchiveEntities['events'], 'ExploreMore::sortRelatedEntities');
					$exploreMoreSectionsToShow['relatedEvents'] = array(
//							'title' => 'Associated Events',
							'format' => 'textOnlyList',
							'values' => $relatedArchiveEntities['events']
					);
				}
			}
		}

		$searchSubjectsOnly = $activeSection == 'archive';
		$driver = $activeSection == 'archive' ? $recordDriver : null;
		$relatedArchiveContent = $this->getRelatedArchiveObjects($quotedSearchTerm, $searchSubjectsOnly, $driver);
		if (count($relatedArchiveContent) > 0) {
			$exploreMoreSectionsToShow['relatedArchiveData'] = array(
//					'title' => 'From the Archive',
					'format' => 'subsections',
					'values' => $relatedArchiveContent
			);
		}

		if ($activeSection != 'catalog'){
			$relatedWorks = $this->getRelatedWorks($quotedSubjectsForSearching, $relatedPikaContent);
			if ($relatedWorks['numFound'] > 0){
				$exploreMoreSectionsToShow['relatedCatalog'] = array(
//						'title' => 'More From the Catalog',
						'format' => 'scrollerWithLink',
						'values' => $relatedWorks['values'],
						'link' => $relatedWorks['link'],
						'numFound' => $relatedWorks['numFound'],
				);
			}
		}

		if ($activeSection == 'archive'){
			/** @var IslandoraDriver $archiveDriver */
			$archiveDriver = $recordDriver;

			//Load related subjects
			$relatedSubjects = $this->getRelatedArchiveSubjects($archiveDriver);
			if (count($relatedSubjects) > 0){
				usort($relatedSubjects, 'ExploreMore::sortRelatedEntities');
				$exploreMoreSectionsToShow['relatedSubjects'] = array(
//						'title' => 'Related Subjects',
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
//							'title' => 'Digital Public Library of America',
							'format' => 'scrollerWithLink',
							'values' => $dplaResults['records'],
							'link' => 'http://dp.la/search?q=' . urlencode('"' . $archiveDriver->getTitle() . '"'),
							'openInNewWindow' => true,
					);
				}
			}else{
				//Display donor and contributor information
				$brandingResults = $archiveDriver->getBrandingInformation(false);

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
//							'title' => 'Acknowledgements',
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
			return;
		}
		//Get data from the repository
		global $interface;
		global $configArray;
		global $library;
		$exploreMoreOptions = array();

		$islandoraActive = false;
		$islandoraSearchObject = null;
		if ($library->enableArchive && $activeSection != 'archive'){
			/** @var SearchObject_Islandora $islandoraSearchObject */
			$islandoraSearchObject = SearchObjectFactory::initSearchObject('Islandora');
			$islandoraSearchObject->init();
			$islandoraActive = $islandoraSearchObject->pingServer(false);
		}

		//Check the archive to see if we match an entity.
		if ($islandoraActive) {
			$exploreMoreOptions = $this->loadExactEntityMatches($exploreMoreOptions, $searchTerm);
		}

		$exploreMoreOptions = $this->loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm);

		$exploreMoreOptions = $this->loadEbscoOptions($activeSection, $exploreMoreOptions, $searchTerm);

		if ($islandoraActive){
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
						/** @var SearchObject_Islandora $searchObject2 */
						$searchObject2 = SearchObjectFactory::initSearchObject('Islandora');
						$searchObject2->init();
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
							/** @var IslandoraDriver $firstObjectDriver */
							$firstObjectDriver = RecordDriverFactory::initRecordDriver($firstObject);
							$numMatches = $response2['response']['numFound'];
							$contentType = ucwords(translate($relatedContentType[0]));
							if ($numMatches == 1) {
								$exploreMoreOptions[] = array(
										'label' => "{$contentType}s ({$numMatches})",
										'description' => "{$contentType}s related to {$searchObject2->getQuery()}",
										'image' => $firstObjectDriver->getBookcoverUrl('medium'),
										'link' => $firstObjectDriver->getRecordUrl(),
								);
							} else {
								$exploreMoreOptions[] = array(
										'label' => "{$contentType}s ({$numMatches})",
										'description' => "{$contentType}s related to {$searchObject2->getQuery()}",
										'image' => $firstObjectDriver->getBookcoverUrl('medium'),
										'link' => $searchObject2->renderSearchUrl(),
								);
							}
						}
					}

					//Related collections
					foreach ($response['facet_counts']['facet_fields']['RELS_EXT_isMemberOfCollection_uri_ms'] as $collectionInfo) {
						$archiveObject = $fedoraUtils->getObject($collectionInfo[0]);
						if ($archiveObject != null) {
							$okToAdd = $fedoraUtils->isObjectValidForPika($archiveObject);

							if ($okToAdd){
								$exploreMoreOptions[] = array(
									'label' => $archiveObject->label,
									'description' => $archiveObject->label,
									'image' => $fedoraUtils->getObjectImageUrl($archiveObject, 'medium'),
									'link' => $configArray['Site']['path'] . "/Archive/{$archiveObject->id}/Exhibit",
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
							$exploreMoreOptions[] = array(
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
							$exploreMoreOptions[] = array(
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
							$exploreMoreOptions[] = array(
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

		} else {
			global $logger;
			$logger->log('Islandora Search Failed.', PEAR_LOG_WARNING);
		}

		/*if (count($exploreMoreOptions) > 0 && count($exploreMoreOptions) < 3){
			$exploreMoreOptions[] = array(
					'label' => "",
					'description' => "Explore the archive",
					'image' => $configArray['Site']['path'] . '/images/archive_banner_1.png',
					'link' => '/Archive/Results',
					'placeholder' => true,
			);
		}*/

		$interface->assign('exploreMoreOptions', $exploreMoreOptions);

		return $exploreMoreOptions;
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
			if (isset($configArray['Islandora']) && isset($configArray['Islandora']['solrUrl']) && $searchTerm) {
				/** @var SearchObject_Islandora $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject->init();
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
						$exploreMoreOptions[] = array(
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

	/**
	 * @param $activeSection
	 * @param $exploreMoreOptions
	 * @param $searchTerm
	 * @return array
	 */
	protected function loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm) {
		global $configArray;
		if ($activeSection != 'catalog') {
			if (strlen($searchTerm) > 0) {
				/** @var SearchObject_Solr $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject();
				$searchObjectSolr->init('local');
				$searchObjectSolr->setSearchTerms(array(
						'lookfor' => $searchTerm,
						'index' => 'Keyword'
				));
				$searchObjectSolr->clearHiddenFilters();
				$searchObjectSolr->clearFilters();
				$searchObjectSolr->addFilter('literary_form_full:Non Fiction');
				$searchObjectSolr->addFilter('target_audience:Adult');
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit(5);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					foreach ($results['response']['docs'] as $doc) {
						/** @var GroupedWorkDriver $driver */
						$driver = RecordDriverFactory::initRecordDriver($doc);
						$numCatalogResults = $results['response']['numFound'];
						if ($numCatalogResultsAdded == 4 && $numCatalogResults > 5) {
							//Add a link to remaining catalog results
							$exploreMoreOptions[] = array(
									'label' => "Catalog Results ($numCatalogResults)",
									'description' => "Catalog Results ($numCatalogResults)",
									'image' => $configArray['Site']['path'] . '/interface/themes/responsive/images/library_symbol.png',
									'link' => $searchObjectSolr->renderSearchUrl(),
									'usageCount' => 1
							);
						} else {
							//Add a link to the actual title
							$exploreMoreOptions[] = array(
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
		global $configArray;
		//TODO: Reenable once we do full EDS integration
		if (false && $library->edsApiProfile && $activeSection != 'ebsco') {
			//Load EDS options
			require_once ROOT_DIR . '/sys/Ebsco/EDS_API.php';
			$edsApi = EDS_API::getInstance();
			if ($edsApi->authenticate()) {
				//Find related titles
				$edsResults = $edsApi->getSearchResults($searchTerm);
				if ($edsResults) {
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
										$exploreMoreOptions[] = array(
												'label' => "$facetValueStr ({$numFacetMatches})",
												'description' => "{$facetValueStr} in EBSCO related to {$searchTerm}",
												'image' => $configArray['Site']['path'] . "/interface/themes/responsive/images/{$iconName}.png",
												'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm) . '&filter[]=' . $facetInfo->Id . ':' . $facetValueStr,
										);
									}

								}
							}
						}

						$exploreMoreOptions[] = array(
								'label' => "All EBSCO Results ({$numMatches})",
								'description' => "All Results in EBSCO related to {$searchTerm}",
								'image' => $configArray['Site']['path'] . '/interface/themes/responsive/images/ebsco_eds.png',
								'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm)
						);
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	function loadExploreMoreContent(){
		global $timer;
		require_once ROOT_DIR . '/sys/ArchiveSubject.php';
		$archiveSubjects = new ArchiveSubject();
		$subjectsToIgnore = array();
		$subjectsToRestrict = array();
		if ($archiveSubjects->find(true)){
			$subjectsToIgnore = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToIgnore)));
			$subjectsToRestrict = array_flip(explode("\r\n", strtolower($archiveSubjects->subjectsToRestrict)));
		}
		$this->getRelatedCollections();
		$timer->logTime("Loaded related collections");
		$relatedSubjects = array();
		$numSubjectsAdded = 0;
		if (strlen($this->archiveObject->label) > 0) {
			$relatedSubjects[$this->archiveObject->label] = '"' . $this->archiveObject->label . '"';
		}
		for ($i = 0; $i < 2; $i++){
			foreach ($this->formattedSubjects as $subject) {
				$lowerSubject = strtolower($subject['label']);
				//Ignore anything after a -- if it exists
				if (strpos($lowerSubject, ' -- ') >= 0){
					$lowerSubject = substr($lowerSubject, 0, strpos($lowerSubject, ' -- '));
				}
				if (!array_key_exists($lowerSubject, $subjectsToIgnore)) {
					if ($i == 0){
						//First pass, just add primary subjects
						if (!array_key_exists($lowerSubject, $subjectsToRestrict)) {
							$relatedSubjects[$lowerSubject] = '"' . $subject['label'] . '"';
						}
					}else{
						//Second pass, add restricted subjects, but only if we don't have 5 subjects already
						if (array_key_exists($lowerSubject, $subjectsToRestrict) && count($relatedSubjects) <= 5) {
							$relatedSubjects[$lowerSubject] = '"' . $subject['label'] . '"';
						}
					}
				}
			}
		}
		$relatedSubjects = array_slice($relatedSubjects, 0, 5);
		foreach ($this->relatedPeople as $person) {
			$label = (string)$person['label'];
			$relatedSubjects[$label] = '"' . $label . '"';
			$numSubjectsAdded++;
		}
		$relatedSubjects = array_slice($relatedSubjects, 0, 8);
		$timer->logTime("Loaded subjects");

		$exploreMore = new ExploreMore();

		$exploreMore->loadEbscoOptions('archive', array(), implode($relatedSubjects, " or "));
		$timer->logTime("Loaded EBSCO options");

		$searchTerm = implode(" OR ", $relatedSubjects);
		$exploreMore->getRelatedArchiveObjects($searchTerm);
		$timer->logTime("Loaded related archive objects");
	}

	/**
	 * @param IslandoraDriver $archiveDriver
	 *
	 * @return array
	 */
	public function getRelatedArchiveSubjects($archiveDriver){
		$relatedObjects = $archiveDriver->getDirectlyRelatedArchiveObjects();
		$relatedSubjects = array();

		foreach ($relatedObjects['objects'] as $object){
			/** @var IslandoraDriver $relatedObjectDriver */
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
	 * @param bool   $searchSubjectsOnly
	 * @param IslandoraDriver $archiveDriver
	 * @return array
	 */
	public function getRelatedArchiveObjects($searchTerm, $searchSubjectsOnly, $archiveDriver = null) {
		global $timer;
		$relatedArchiveContent = array();

		require_once ROOT_DIR . '/sys/Utils/FedoraUtils.php';
		/** @var SearchObject_Islandora $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Islandora');
		$searchObject->init();
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
				/** @var SearchObject_Islandora $searchObject2 */
				$searchObject2 = SearchObjectFactory::initSearchObject('Islandora');
				$searchObject2->init();
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
					/** @var IslandoraDriver $firstObjectDriver */
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
	 * @param IslandoraDriver $archiveDriver
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
			/** @var IslandoraDriver $objectDriver */
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
			//Blacklist any records that we have specific links to
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

			/** @var SearchObject_Solr $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init('local', $searchTerm);
			$searchObject->setSearchTerms(array(
					'lookfor' => $searchTerm,
					'index' => 'Keyword'
			));
			$searchObject->addFilter('literary_form_full:Non Fiction');
			$searchObject->addFilter('target_audience:(Adult OR Unknown)');
			$searchObject->addHiddenFilter('!id', $recordsToAvoid);

			$searchObject->setPage(1);
			$searchObject->setLimit(5);
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
	 * @param CompoundDriver $bookDriver
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
					$section['link'] = $bookDriver->getRecordUrl(false) . '?pagePid=' . $firstPageInSection['pid'];
				}
				$exploreMoreSectionsToShow['tableOfContents']['values'][] = $section;
			}
		}
		$interface->assign('bookPid', $bookDriver->getUniqueId());
		return $exploreMoreSectionsToShow;
	}
}

function sortBrandingResults($a, $b){
	if ($a['sortIndex'] == $b['sortIndex']){
		return strcasecmp($a['label'], $b['label']);
	}
	return ($a['sortIndex'] < $b['sortIndex']) ? -1 : 1;
}