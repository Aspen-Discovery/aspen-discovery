<?php

class ExploreMore {
	private $relatedCollections;

	private $numEntriesToAdd = 3;

	/**
	 * @param string $activeSection
	 * @param RecordInterface $recordDriver
	 */
	function loadExploreMoreSidebar($activeSection, $recordDriver) {
		global $interface;
		global $timer;

		$exploreMoreSectionsToShow = [];

		$relatedCatalogContent = [];

		//Get subjects that can be used for searching other systems
		$subjects = $recordDriver->getAllSubjectHeadings();
		$subjectsForSearching = [];
		$quotedSubjectsForSearching = [];
		foreach ($subjects as $subject) {
			if (is_array($subject)) {
				$searchSubject = implode(" ", $subject);
			} else {
				$searchSubject = $subject;
			}
			$separatorPosition = strpos($searchSubject, ' -- ');
			if ($separatorPosition > 0) {
				$searchSubject = substr($searchSubject, 0, $separatorPosition);
			}
			$searchSubject = preg_replace('/\(.*?\)/', "", $searchSubject);
			$searchSubject = trim(preg_replace('/[\/|:.,"]/', "", $searchSubject));
			$subjectsForSearching[] = $searchSubject;
			$quotedSubjectsForSearching[] = '"' . $searchSubject . '"';
		}

		$subjectsForSearching = array_slice($subjectsForSearching, 0, 5);
		$searchTerm = implode(' or ', $subjectsForSearching);
		$quotedSearchTerm = implode(' OR ', $quotedSubjectsForSearching);

		//Get objects from the archive based on search subjects
		if ($activeSection != 'archive') {
			foreach ($subjectsForSearching as $curSubject) {
				$exactEntityMatches = $this->loadExactEntityMatches([], $curSubject);
				if (count($exactEntityMatches) > 0) {
					$exploreMoreSectionsToShow['exactEntityMatches'] = [
						'format' => 'list',
						'values' => usort($exactEntityMatches, 'ExploreMore::sortRelatedEntities'),
					];
				}
			}
			$timer->logTime("Loaded related entities");
		}

		//Always load ebsco even if we are already in that section
		$ebscoMatches = $this->loadEbscoEDSOptions('', [], $searchTerm);
		if (count($ebscoMatches) > 0) {
			$interface->assign('relatedArticles', $ebscoMatches);
		}

		$aummonMatches = $this->loadSummonOptions('', [], $searchTerm);
		if (count($summonMatches) > 0) {
			$interface->assign('relatedArticles', $summonMatches);
		}

		if ($activeSection != 'catalog') {
			$relatedWorks = $this->getRelatedWorks($quotedSubjectsForSearching, $relatedCatalogContent);
			if ($relatedWorks['numFound'] > 0) {
				$exploreMoreSectionsToShow['relatedCatalog'] = [
					'format' => 'scrollerWithLink',
					'values' => $relatedWorks['values'],
					'link' => $relatedWorks['link'],
					'numFound' => $relatedWorks['numFound'],
				];
			}
		}

		$interface->assign('exploreMoreSections', $exploreMoreSectionsToShow);
	}

	function getExploreMoreQuery() {
		if (isset($_REQUEST['lookfor'])) {
			$searchTerm = $_REQUEST['lookfor'];
		} else {
			$searchTerm = '';
		}
		if (!$searchTerm) {
			//No search term found, try to get a search term based on applied filters (just one)
			if (isset($_REQUEST['filter'])) {
				foreach ($_REQUEST['filter'] as $filter) {
					if (!is_array($filter) && strlen($filter) > 0) {
						if (strpos($filter, ':') !== false) {
							$filterVals = explode(':', $filter, 2);
							if ($filterVals[0] != 'mods_genre_s' && $filterVals[0] != 'literary_form' && $filterVals[0] != 'literary_form_full' && $filterVals[0] != 'target_audience' && $filterVals[0] != 'target_audience_full') {
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

	function loadExploreMoreBar($activeSection, $searchTerm) {
		if (isset($_REQUEST['page']) && $_REQUEST['page'] > 1) {
			return [];
		}
		//Get data from the repository
		global $interface;
		global $configArray;

		global $library;
		global $enabledModules;
		$exploreMoreOptions = [
			'sampleRecords' => [],
			'searchLinks' => [],
		];

		$appliedTheme = $interface->getAppliedTheme();
		$exploreMoreOptions = $this->loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);

		if (array_key_exists('EBSCO EDS', $enabledModules) && $library->edsSettingsId != -1) {
			$exploreMoreOptions = $this->loadEbscoEDSOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		} elseif (array_key_exists('EBSCOhost', $enabledModules) && $library->edsSettingsId == -1) {
			$exploreMoreOptions = $this->loadEbscohostOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		if (array_key_exists('Summon', $enabledModules)) {
			$exploreMoreOptions = $this->loadSummonOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		};

		if (array_key_exists('Events', $enabledModules)) {
			$exploreMoreOptions = $this->loadEventOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		if (array_key_exists('Web Indexer', $enabledModules)) {
			$exploreMoreOptions = $this->loadWebIndexerOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		$exploreMoreOptions = $this->loadListOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);

		if (array_key_exists('Open Archives', $enabledModules) && $library->enableOpenArchives) {
			$exploreMoreOptions = $this->loadOpenArchiveOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		if ($library->enableGenealogy) {
			$exploreMoreOptions = $this->loadGenealogyOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		//Consolidate explore more options, we'd like to show the search links if possible and then pad with sample records
		$exploreMoreDisplayOptions = [];

		$minSampleRecordsToAdd = 4 - count($exploreMoreOptions['searchLinks']);
		if ($minSampleRecordsToAdd < 0) {
			$minSampleRecordsToAdd = 0;
		}
		//Get at least one sample record from each source
		for ($sampleIndex = 0; $sampleIndex < 4; $sampleIndex++) {
			foreach ($exploreMoreOptions['sampleRecords'] as $sampleRecords) {
				if (array_key_exists($sampleIndex, $sampleRecords)) {
					$exploreMoreDisplayOptions[] = $sampleRecords[$sampleIndex];
					if (count($exploreMoreDisplayOptions) >= $minSampleRecordsToAdd && $sampleIndex >= 1) {
						break;
					}
				}
			}
			if (count($exploreMoreDisplayOptions) >= $minSampleRecordsToAdd) {
				break;
			}
		}
		//Add in all of the search links
		$exploreMoreDisplayOptions = array_merge($exploreMoreDisplayOptions, $exploreMoreOptions['searchLinks']);

		$interface->assign('exploreMoreOptions', $exploreMoreDisplayOptions);

		return $exploreMoreDisplayOptions;
	}

	protected function loadWebIndexerOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'websites') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['websites'] = [];
				/** @var SearchObject_ListsSearcher $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Websites');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'WebsiteKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//check for custom image
						if ($appliedTheme != null && !empty($appliedTheme->libraryWebsiteImage)) {
							$image = '/files/original/' . $appliedTheme->libraryWebsiteImage;
						} else{
							$image = '/images/webpage.png';
						}
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = [
							'label' => translate([
								'text' => "Web pages (%1%)",
								1 => $numCatalogResults,
								'isPublicFacing' => true,
							]),
							'description' => translate([
								'text' => "All Results in Web pages related to %1%",
								1 => $searchTerm,
								'isPublicFacing' => true,
							]),

							'image' => $image,
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1,
							'openInNewWindow' => false,
						];
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var ListsRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['websites'][] = [
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'onclick' => 'AspenDiscovery.Websites.trackUsage(' . $driver->getId() . ')',
								'usageCount' => 1,
								'openInNewWindow' => false,
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	protected function loadEventOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'events') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['events'] = [];
				/** @var SearchObject_EventsSearcher $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Events');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'EventsKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//check for custom image
						if ($appliedTheme != null && !empty($appliedTheme->eventsImage)) {
							$image = '/files/original/' . $appliedTheme->eventsImage;
						} else{
							$image = '/interface/themes/responsive/images/events.png';
						}
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = [
							'label' => translate([
								'text' => "Events (%1%)",
								1 => $numCatalogResults,
								'isPublicFacing' => true,
							]),
							'description' => translate([
								'text' => "All Results in Events related to %1%",
								1 => $searchTerm,
								'isPublicFacing' => true,
							]),
							'image' => $image,
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1,
							'openInNewWindow' => false,
						];
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var EventRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['events'][] = [
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'usageCount' => 1,
								'openInNewWindow' => true,
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	protected function loadListOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'lists') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['lists'] = [];

				/** @var SearchObject_ListsSearcher $searchObject */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Lists');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'ListsKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//check for custom image
						if ($appliedTheme != null && !empty($appliedTheme->listsImage)) {
							$image = '/files/original/' . $appliedTheme->listsImage;
						} else{
							$image = '/interface/themes/responsive/images/library_symbol.png';
						}
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = [
							'label' => translate([
								'text' => "Lists (%1%)",
								1 => $numCatalogResults,
								'isPublicFacing' => true,
							]),
							'description' => translate([
								'text' => "All Results in Lists related to %1%",
								1 => $searchTerm,
								'isPublicFacing' => true,
							]),
							'image' => $image,
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1,
							'openInNewWindow' => false,
						];
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var ListsRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['lists'][] = [
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'usageCount' => 1,
								'openInNewWindow' => false,
							];
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
	protected function loadOpenArchiveOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'open_archives') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['open_archives'] = [];
				/** @var SearchObject_OpenArchivesSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('OpenArchives');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'OpenArchivesKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//check for custom image
						if ($appliedTheme != null && !empty($appliedTheme->historyArchivesImage)) {
							$image = '/files/original/' . $appliedTheme->historyArchivesImage;
						} else{
							$image = '/interface/themes/responsive/images/library_symbol.png';
						}
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = [
							'label' => translate([
								'text' => "Archive Results (%1%)",
								1 => $numCatalogResults,
								'isPublicFacing' => true,
							]),
							'description' => translate([
								'text' => "All Results in Archives related to %1%",
								1 => $searchTerm,
								'isPublicFacing' => true,
							]),
							'image' => $image,
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1,
							'openInNewWindow' => false,
						];
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var OpenArchivesRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['open_archives'][] = [
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'onclick' => "AspenDiscovery.OpenArchives.trackUsage('{$driver->getId()}')",
								'usageCount' => 1,
								'openInNewWindow' => true,
							];
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
	protected function loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'catalog') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['catalog'] = [];
				/** @var SearchObject_AbstractGroupedWorkSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject();
				$searchObjectSolr->init('local');
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'Keyword',
				]);
				$searchObjectSolr->clearHiddenFilters();
				$searchObjectSolr->clearFilters();
				if ($activeSection == 'open_archives' || $activeSection == 'archive') {
					$facetConfig = $searchObjectSolr->getFacetConfig();
					if (array_key_exists('literary_form', $facetConfig)) {
						$searchObjectSolr->addFilter('literary_form:"Non Fiction"');
					} elseif (array_key_exists('literary_form_full', $facetConfig)) {
						$searchObjectSolr->addFilter('literary_form_full:"Non Fiction"');
					}
					if (array_key_exists('target_audience', $facetConfig)) {
						if ($facetConfig['target_audience']->multiSelect) {
							$searchObjectSolr->addFilter('target_audience:Adult');
							$searchObjectSolr->addFilter('target_audience:Unknown');
							$searchObjectSolr->addFilter('target_audience:General');
						} else {
							$searchObjectSolr->addFilter('target_audience:(Adult OR Unknown OR General)');
						}
					} elseif (array_key_exists('target_audience_full', $facetConfig)) {
						if ($facetConfig['target_audience_full']->multiSelect) {
							$searchObjectSolr->addFilter('target_audience_full:Adult');
							$searchObjectSolr->addFilter('target_audience_full:Unknown');
							$searchObjectSolr->addFilter('target_audience_full:General');
						} else {
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
							//check for custom image
							if ($appliedTheme != null && !empty($appliedTheme->catalogImage)) {
								$image = '/files/original/' . $appliedTheme->catalogImage;
							} else{
								$image = '/interface/themes/responsive/images/library_symbol.png';
							}
							//Add a link to remaining catalog results
							$exploreMoreOptions['searchLinks'][] = [
								'label' => translate([
									'text' => "Catalog Results (%1%)",
									1 => $numCatalogResults,
									'isPublicFacing' => true,
								]),
								'description' => translate([
									'text' => "All Results in Catalog related to %1%",
									1 => $searchTerm,
									'isPublicFacing' => true,
								]),
								'image' => $image,
								'link' => $searchObjectSolr->renderSearchUrl(),
								'usageCount' => 1,
								'openInNewWindow' => false,
							];
						} else {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['catalog'][] = [
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'usageCount' => 1,
								'openInNewWindow' => false,
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	public function loadEbscohostOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		global $library;
		global $enabledModules;
		if (!empty($searchTerm) && array_key_exists('EBSCOhost', $enabledModules) && $library->ebscohostSearchSettingId != -1 && $activeSection != 'ebscohost') {
			//Get a list of databases to sort through

			//Load EDS options
			/** @var SearchObject_EbscohostSearcher $ebscohostSearcher */
			$ebscohostSearcher = SearchObjectFactory::initSearchObject("Ebscohost");

			//Get a list of databases to check
			$searchSettings = $ebscohostSearcher->getSearchSettings();
			if ($searchSettings != null) {
				$databases = $searchSettings->getDatabases();
				$hasMatches = false;
				$exploreMoreOptions['sampleRecords']['ebscohost'] = [];
				foreach ($databases as $database) {
					if ($database->allowSearching && $database->showInExploreMore) {
						$ebscohostSearcher = SearchObjectFactory::initSearchObject("Ebscohost");
						//Find related titles
						$ebscohostSearcher->setSearchTerms([
							'lookfor' => $searchTerm,
							'index' => 'TX',
						]);
						$ebscohostSearcher->setLimit($this->numEntriesToAdd + 1);
						$ebscohostSearcher->addFilter("db:$database->shortName");
						$ebscohostResults = $ebscohostSearcher->processSearch(true, false);

						$numMatches = $ebscohostSearcher->getNumResults();
						if ($numMatches > 0) {
							if (empty($database->logo)) {
								$image = '/interface/themes/responsive/images/ebscohost.png';
							} else {
								$image = '/files/original/' . $database->logo;
							}
							//Add a link to the actual title
							$exploreMoreOptions['searchLinks'][] = [
								'label' => $database->displayName . " ($numMatches)",
								'description' => $database->displayName,
								'image' => $image,
								'link' => '/EBSCOhost/Results?lookfor=' . urlencode($searchTerm) . "&filter[]=db:$database->shortName",
								'usageCount' => 1,
								'openInNewWindow' => false,
							];
							$hasMatches = true;
						}
					}
				}
				if ($hasMatches) {
					//check for custom image
					if ($appliedTheme != null && !empty($appliedTheme->articlesDBImage)) {
						$image = '/files/original/' . $appliedTheme->articlesDBImage;
					} else{
						$image = '/interface/themes/responsive/images/ebscohost.png';
					}
					$ebscohostSearcher = SearchObjectFactory::initSearchObject("Ebscohost");
					//Find related titles
					$ebscohostSearcher->setSearchTerms([
						'lookfor' => $searchTerm,
						'index' => 'TX',
					]);
					$ebscohostSearcher->processSearch(true, false);
					$numMatches = $ebscohostSearcher->getNumResults();
					$exploreMoreOptions['searchLinks'][] = [
						'label' => translate([
							'text' => "All EBSCOhost Results (%1%)",
							1 => $numMatches,
							'isPublicFacing' => true,
						]),
						'description' => translate([
							'text' => "All Results in EBSCOhost related to %1%",
							1 => $searchTerm,
							'isPublicFacing' => true,
						]),
						'image' => $image,
						'link' => '/EBSCOhost/Results?lookfor=' . urlencode($searchTerm),
						'openInNewWindow' => false,
					];
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
	public function loadEbscoEDSOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		global $library;
		global $enabledModules;
		if (!empty($searchTerm) && array_key_exists('EBSCO EDS', $enabledModules) && $library->edsSettingsId != -1 && $activeSection != 'ebsco_eds') {
			//Load EDS options
			/** @var SearchObject_EbscoEdsSearcher $edsSearcher */
			$edsSearcher = SearchObjectFactory::initSearchObject("EbscoEds");
			if ($edsSearcher->authenticate()) {
				//Find related titles
				$edsSearcher->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'TX',
				]);
				$edsResults = $edsSearcher->processSearch(true, false);
				if ($edsResults != null) {
					$exploreMoreOptions['sampleRecords']['ebsco_eds'] = [];
					$numMatches = $edsResults->Statistics->TotalHits;
					if ($numMatches > 0) {
						//Check results based on common facets
						foreach ($edsResults->AvailableFacets as $facetInfo) {
							if ($facetInfo->Id == 'SourceType') {
								foreach ($facetInfo->AvailableFacetValues as $facetValue) {
									$facetValueStr = (string)$facetValue->Value;
									if (in_array($facetValueStr, [
										'Magazines',
										'News',
										'Academic Journals',
										'Primary Source Documents',
									])) {
										$numFacetMatches = (int)$facetValue->Count;
										$iconName = 'ebsco_' . str_replace(' ', '_', strtolower($facetValueStr));
										$exploreMoreOptions['searchLinks'][] = [
											'label' => "$facetValueStr ({$numFacetMatches})",
											'description' => "{$facetValueStr} in EBSCO related to {$searchTerm}",
											'image' => "/interface/themes/responsive/images/{$iconName}.png",
											'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm) . '&filter[]=' . $facetInfo->Id . ':' . $facetValueStr,
											'openInNewWindow' => false,
										];
									}

								}
							}
						}

						if ($numMatches > 1) {
							//check for custom image
							if ($appliedTheme != null && !empty($appliedTheme->articlesDBImage)) {
								$image = '/files/original/' . $appliedTheme->articlesDBImage;
							} else{
								$image = '/interface/themes/responsive/images/ebsco_eds.png';
							}
							$exploreMoreOptions['searchLinks'][] = [
								'label' => translate([
									'text' => "All EBSCO Results (%1%)",
									1 => $numMatches,
									'isPublicFacing' => true,
								]),
								'description' => translate([
									'text' => "All Results in EBSCO related to %1%",
									1 => $searchTerm,
									'isPublicFacing' => true,
								]),
								'image' => $image,
								'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm),
								'openInNewWindow' => false,
							];
						}
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
	public function loadSummonOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		global $library;
		global $enabledModules;
		if (!empty($searchTerm) && array_key_exists('Summon', $enabledModules) && $library->summonSettingsId != -1 && $activeSection != 'summon') {
			//Load Summon Options
			/** @var SearchObject_SummonSearcher $summonSearcher */
			$summonSearcher = SearchObjectFactory::initSearchObject('Summon');
			$summonSearcher->setSearchTerms([
				'lookfor' => $searchTerm,
				'index' => 'Everything',
			]);
			$summonResults = $summonSearcher->sendRequest();
			if ($summonResults != null) {
				$exploreMoreOptions['sampleRecords']['summon'] = [];
				$numMatches = $summonResults['recordCount'];
				if ($numMatches > 1) {
					if ($appliedTheme != null && !empty($appliedTheme->articlesDBImage)) {
						$image = '/files/origional/' . $appliedTheme->articlesDBImage;
					} else {
						$image = '/interface/themes/responsive/images/summon.png';
					}
					$exploreMoreOptions['searchLinks'][] = [
						'label' => translate([
							'text' => "All Summon Results (%1%)",
							1 => $numMatches,
							'isPublicFacing' => true,
						]),
						'description' => translate([
							'text' => "All Results in Summon related to %1%",
							1 => $searchTerm,
							'isPublicFacing' => true,
						]),
						'image' => $image,
						'link' => '/Summon/Results?lookfor=' . urlencode($searchTerm),
						'openInNewWindow' => false,
					];
				}
			}
		}
		return $exploreMoreOptions;
	}

	/**
	 * @param string[] $relatedSubjects
	 * @param array $directlyRelatedRecords
	 *
	 * @return array
	 */
	public function getRelatedWorks($relatedSubjects, $directlyRelatedRecords) {
		//Load related catalog content
		$searchTerm = implode(" OR ", $relatedSubjects);

		$similarTitles = [
			'numFound' => 0,
			'link' => '',
			'values' => [],
		];

		if (strlen($searchTerm) > 0) {
			//Do not include any records that we have specific links to
			$recordsToAvoid = '';
			foreach ($directlyRelatedRecords as $record) {
				if (strlen($recordsToAvoid) > 0) {
					$recordsToAvoid .= ' OR ';
				}
				$recordsToAvoid .= $record['id'];
			}
			/*if (strlen($recordsToAvoid) > 0){
				$searchTerm .= " AND NOT id:($recordsToAvoid)";
			}*/

			/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject();
			$searchObject->init('local', $searchTerm);
			$searchObject->disableSpelling();
			$searchObject->setSearchTerms([
				'lookfor' => $searchTerm,
				'index' => 'Keyword',
			]);
			$searchObject->addFilter('literary_form_full:Non Fiction');
			$searchObject->addFilter('target_audience:(Adult OR Unknown)');
			$searchObject->addHiddenFilter('!id', $recordsToAvoid);

			$searchObject->setPage(1);
			$searchObject->setLimit($this->numEntriesToAdd + 1);
			$results = $searchObject->processSearch(true, false);

			if ($results && isset($results['response'])) {
				$similarTitles = [
					'numFound' => $results['response']['numFound'],
					'link' => $searchObject->renderSearchUrl(),
					'topHits' => [],
				];
				foreach ($results['response']['docs'] as $doc) {
					/** @var GroupedWorkDriver $driver */
					$driver = RecordDriverFactory::initRecordDriver($doc);
					$similarTitle = [
						'label' => $driver->getTitle(),
						'link' => $driver->getLinkUrl(),
						'image' => $driver->getBookcoverUrl('medium'),
					];
					$similarTitles['values'][] = $similarTitle;
				}
			}
		}
		return $similarTitles;
	}

	private function loadGenealogyOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'genealogy') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['genealogy'] = [];
				/** @var SearchObject_GenealogySearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Genealogy');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'GenealogyKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true, false);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//check for custom image
						if ($appliedTheme != null && !empty($appliedTheme->genealogyImage)) {
							$image = '/files/original/' . $appliedTheme->genealogyImage;
						} else{
							$image = '/interface/themes/responsive/images/person.png';
						}
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = [
							'label' => translate([
								'text' => "Genealogy Results (%1%)",
								1 => $numCatalogResults,
								'isPublicFacing' => true,
							]),
							'description' => translate([
								'text' => "All Results in Genealogy related to %1%",
								1 => $searchTerm,
								'isPublicFacing' => true,
							]),
							'image' => $image,
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1,
							'openInNewWindow' => false,
						];
					}
					foreach ($results['response']['docs'] as $doc) {
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['genealogy'][] = [
								'label' => $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'usageCount' => 1,
								'openInNewWindow' => false,
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}
}

function sortBrandingResults($a, $b) {
	if ($a['sortIndex'] == $b['sortIndex']) {
		return strcasecmp($a['label'], $b['label']);
	}
	return ($a['sortIndex'] < $b['sortIndex']) ? -1 : 1;
}