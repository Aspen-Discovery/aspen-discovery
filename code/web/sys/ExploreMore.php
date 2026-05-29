<?php

require_once ROOT_DIR . '/sys/ExploreMoreSourceEntry.php';
require_once ROOT_DIR . '/sys/ExploreMoreSource.php';
require_once ROOT_DIR . '/sys/ExploreMoreSourceLibrary.php';

class ExploreMore {
	private int $numEntriesToAdd = 3;

	function getExploreMoreQuery() {
		$searchTerm = $_REQUEST['lookfor'] ?? '';
		if (empty($searchTerm)) {
			//No search term found, try to get a search term based on applied filters (just one)
			if (isset($_REQUEST['filter'])) {
				foreach ($_REQUEST['filter'] as $filter) {
					if (!is_array($filter) && strlen($filter) > 0) {
						if (str_contains($filter, ':')) {
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

	function loadExploreMoreBar($activeSection, $searchTerm) : array {
		if (isset($_REQUEST['page']) && $_REQUEST['page'] > 1) {
			return [];
		}

		//Get data from the repository
		global $interface;

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
		}

		if (array_key_exists('Gale', $enabledModules)) {
			$exploreMoreOptions = $this->loadGaleOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		if (array_key_exists('CloudSource', $enabledModules)) {
			$exploreMoreOptions = $this->loadCloudSourceOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

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

		if (array_key_exists('Series', $enabledModules) && $library->useSeriesSearchIndex) {
			$exploreMoreOptions = $this->loadSeriesOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		if ($library->enableGenealogy) {
			$exploreMoreOptions = $this->loadGenealogyOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme);
		}

		//Consolidate explore more options, we'd like to show the search links if possible and then pad with sample records
		$exploreMoreDisplayOptions = [];

		// Filter and sort configured sources in one pass for both search links and sample records.
		$exploreMoreOptions['searchLinks'] = self::filterAndSortExploreMoreEntries($exploreMoreOptions['searchLinks']);
		$exploreMoreOptions['sampleRecords'] = self::filterAndSortExploreMoreEntries($exploreMoreOptions['sampleRecords'], true);

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
		//Add in all the search links
		$exploreMoreDisplayOptions = array_merge($exploreMoreDisplayOptions, $exploreMoreOptions['searchLinks']);

		$interface->assign('exploreMoreOptions', $exploreMoreDisplayOptions);

		return $exploreMoreDisplayOptions;
	}

	protected function loadWebIndexerOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'websites') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['websites'] = [];
				/** @var SearchObject_WebsitesSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Websites');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'WebsiteKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true);

				// @todo: Adds a temporary check for libraries who may not have the show_in_explore_more field added to their Solr index yet.
				// This can be removed once we're confident all libraries have the field.
				// This code can, at that time, probably just be simplified to:
				// $searchObjectSolr->addHiddenFilter('show_in_explore_more', 'true');
				$hasShowInExploreMore = false;
				if ($results && isset($results['response']['docs'])) {
					foreach ($results['response']['docs'] as $doc) {
						if (array_key_exists('show_in_explore_more', $doc)) {
							$hasShowInExploreMore = true;
							break;
						}
					}
				}
				// If field exists, re-run with filter.
				if ($hasShowInExploreMore) {
					/** @var SearchObject_WebsitesSearcher $searchObjectSolr */
					$searchObjectSolr = SearchObjectFactory::initSearchObject('Websites');
					$searchObjectSolr->init();
					$searchObjectSolr->disableSpelling();
					$searchObjectSolr->setSearchTerms([
						'lookfor' => $searchTerm,
						'index' => 'WebsiteKeyword',
					]);
					$searchObjectSolr->addHiddenFilter('show_in_explore_more', 'true');
					$searchObjectSolr->setPage(1);
					$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
					$results = $searchObjectSolr->processSearch(true);
				}
				// End temporary check code.

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
							'source' => 'Web Indexer',
						];
					}
					foreach ($results['response']['docs'] as $doc) {
						/** @var IndexRecordDriver $driver */
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							if ($doc['recordtype'] == 'WebResource') {
								$id = str_replace('WebResource:', '', $driver->getId());
								//Add a link to the actual title
								$exploreMoreOptions['sampleRecords']['websites'][] = [
									'label' => $driver->getTitle(),
									'description' => $driver->getTitle(),
									'image' => $driver->getBookcoverUrl('medium'),
									'link' => 'javascript:;',
									'onclick' => "AspenDiscovery.WebBuilder.getWebResource('$id'); AspenDiscovery.Websites.trackUsage('{$driver->getId()}');",
									'usageCount' => 1,
									'openInNewWindow' => false,
									'source' => 'Web Indexer',
								];
							}else{
								//Add a link to the actual title
								$exploreMoreOptions['sampleRecords']['websites'][] = [
									'label' => $driver->getTitle(),
									'description' => $driver->getTitle(),
									'image' => $driver->getBookcoverUrl('medium'),
									'link' => $driver->getLinkUrl(),
									'onclick' => 'AspenDiscovery.Websites.trackUsage(' . $driver->getId() . ')',
									'usageCount' => 1,
									'openInNewWindow' => false,
									'source' => 'Web Indexer',
								];
							}
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
				/** @var SearchObject_EventsSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Events');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'EventsKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true);

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
							'source' => 'Events',
						];
					}
					foreach ($results['response']['docs'] as $doc) {
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
								'source' => 'Events',
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

				/** @var SearchObject_ListsSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Lists');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'ListsKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true);

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
							'source' => 'Lists',
						];
					}
					foreach ($results['response']['docs'] as $doc) {
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
								'source' => 'Lists',
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	protected function loadSeriesOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) {
		if ($activeSection != 'series') {
			if (strlen($searchTerm) > 0) {
				$exploreMoreOptions['sampleRecords']['series'] = [];

				/** @var SearchObject_SeriesSearcher $searchObjectSolr */
				$searchObjectSolr = SearchObjectFactory::initSearchObject('Series');
				$searchObjectSolr->init();
				$searchObjectSolr->disableSpelling();
				$searchObjectSolr->setSearchTerms([
					'lookfor' => $searchTerm,
					'index' => 'SeriesKeyword',
				]);
				$searchObjectSolr->setPage(1);
				$searchObjectSolr->setLimit($this->numEntriesToAdd + 1);
				$results = $searchObjectSolr->processSearch(true);

				if ($results && isset($results['response'])) {
					$numCatalogResultsAdded = 0;
					$numCatalogResults = $results['response']['numFound'];
					if ($numCatalogResults > 1) {
						//check for custom image
						if ($appliedTheme != null && !empty($appliedTheme->seriesImage)) {
							$image = '/files/original/' . $appliedTheme->seriesImage;
						} else{
							$image = '/interface/themes/responsive/images/library_symbol.png';
						}
						//Add a link to remaining results
						$exploreMoreOptions['searchLinks'][] = [
							'label' => translate([
								'text' => "Series (%1%)",
								1 => $numCatalogResults,
								'isPublicFacing' => true,
							]),
							'description' => translate([
								'text' => "All Results in Series related to %1%",
								1 => $searchTerm,
								'isPublicFacing' => true,
							]),
							'image' => $image,
							'link' => $searchObjectSolr->renderSearchUrl(),
							'usageCount' => 1,
							'openInNewWindow' => false,
							'source' => 'Series',
						];
					}
					foreach ($results['response']['docs'] as $doc) {
						$driver = $searchObjectSolr->getRecordDriverForResult($doc);
						if ($numCatalogResultsAdded < $this->numEntriesToAdd) {
							//Add a link to the actual title
							$exploreMoreOptions['sampleRecords']['series'][] = [
								'label' => "Series: " . $driver->getTitle(),
								'description' => $driver->getTitle(),
								'image' => $driver->getBookcoverUrl('medium'),
								'link' => $driver->getLinkUrl(),
								'usageCount' => 1,
								'openInNewWindow' => false,
								'source' => 'Series',
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

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
				$results = $searchObjectSolr->processSearch(true);

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
							'source' => 'Open Archives',
						];
					}
					foreach ($results['response']['docs'] as $doc) {
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
								'source' => 'Open Archives',
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	protected function loadCatalogOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme) : array {
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
				$results = $searchObjectSolr->processSearch(true);

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
								'source' => 'Catalog',
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
								'source' => 'Catalog',
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	public function loadEbscohostOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme): array {
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
						/** @var SearchObject_EbscohostSearcher $ebscohostSearcher **/
						$ebscohostSearcher = SearchObjectFactory::initSearchObject("Ebscohost");
						//Find related titles
						$ebscohostSearcher->setSearchTerms([
							'lookfor' => $searchTerm,
							'index' => 'TX',
						]);
						$ebscohostSearcher->setLimit($this->numEntriesToAdd + 1);
						$ebscohostSearcher->addFilter("db:$database->shortName");
						$ebscohostSearcher->processSearch(true);

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
								'source' => 'EBSCOhost',
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
					/** @var SearchObject_EbscohostSearcher $ebscohostSearcher */
					$ebscohostSearcher = SearchObjectFactory::initSearchObject("Ebscohost");
					//Find related titles
					$ebscohostSearcher->setSearchTerms([
						'lookfor' => $searchTerm,
						'index' => 'TX',
					]);
					$ebscohostSearcher->processSearch(true);
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
						'source' => 'EBSCOhost',
					];
				}
			}
		}
		return $exploreMoreOptions;
	}

	public function loadEbscoEDSOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme): array {
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
				$edsResults = $edsSearcher->processSearch(true);
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
											'label' => "$facetValueStr ($numFacetMatches)",
											'description' => "$facetValueStr in EBSCO related to $searchTerm",
											'image' => "/interface/themes/responsive/images/$iconName.png",
											'link' => '/EBSCO/Results?lookfor=' . urlencode($searchTerm) . '&filter[]=' . $facetInfo->Id . ':' . $facetValueStr,
											'openInNewWindow' => false,
											'source' => 'EBSCO EDS',
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
								'source' => 'EBSCO EDS',
							];
						}
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	public function loadSummonOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme): array {
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

			try {
				$summonResults = $summonSearcher->sendRequest();
			} catch (Exception $e) {
				global $logger;
				$logger->log("Error searching Summon " . $e->getMessage(), Logger::LOG_ERROR);
				$summonResults = null;
			}
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
						'source' => 'Summon',
					];
				}
			}
		}
		return $exploreMoreOptions;
	}


	public function loadGaleOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme): array {
		global $library;
		global $enabledModules;
		if (!empty($searchTerm) && array_key_exists('Gale', $enabledModules) && $library->galeSettingsId != -1 && $activeSection != 'gale') {
			//Load Gale Options
			/** @var SearchObject_GaleSearcher $galeSearcher */
			$galeSearcher = SearchObjectFactory::initSearchObject('Gale');
			$galeSearcher->setSearchTerms([
				'lookfor' => $searchTerm,
				'index' => 'Keyword',
			]);
			$galeResults = $galeSearcher->processSearch(true);
			if ($galeResults != null) {
				$exploreMoreOptions['sampleRecords']['gale'] = [];
				$numMatches = $galeSearcher->getResultSummary()['resultTotal'];
				if ($numMatches > 1) {
					if ($appliedTheme != null && !empty($appliedTheme->articlesDBImage)) {
						$image = '/files/origional/' . $appliedTheme->articlesDBImage;
					} else {
						$image = '/interface/themes/responsive/images/gale.png';
					}
					$exploreMoreOptions['searchLinks'][] = [
						'label' => translate([
							'text' => "All Gale Results (%1%)",
							1 => $numMatches,
							'isPublicFacing' => true,
						]),
						'description' => translate([
							'text' => "All Results in Gale related to %1%",
							1 => $searchTerm,
							'isPublicFacing' => true,
						]),
						'image' => $image,
						'link' => '/Gale/Results?lookfor=' . urlencode($searchTerm),
						'openInNewWindow' => false,
						'source' => 'Gale',
					];
				}
			}
		}
		return $exploreMoreOptions;
	}

	public function loadCloudSourceOptions($activeSection, $exploreMoreOptions, $searchTerm, $appliedTheme): array {
		global $library;
		global $enabledModules;
		global $locationSingleton;
		$activeLocation = $locationSingleton->getActiveLocation();
		if (!empty($searchTerm) && array_key_exists('CloudSource', $enabledModules) && $activeSection != 'cloudsource') {
			$settingId = -1;
			require_once ROOT_DIR . '/sys/CloudSource/LibraryCloudSourceSetting.php';
			$libraryCloudSourceSetting = new LibraryCloudSourceSetting();
			$libraryCloudSourceSetting->libraryId = $library->libraryId;
			if ($libraryCloudSourceSetting->find(true)) {
				$settingId = $libraryCloudSourceSetting->cloudsourceSettingId;
			} else {
				require_once ROOT_DIR . '/sys/CloudSource/LocationCloudSourceSetting.php';
				$locationCloudSourceSetting = new LocationCloudSourceSetting();
				$locationCloudSourceSetting->locationId = $activeLocation->locationId;
				if ($libraryCloudSourceSetting->find(true)) {
					$settingId = $locationCloudSourceSetting->cloudsourceSettingId;
				}
			}
			if ($settingId != -1) {
				require_once ROOT_DIR . '/sys/CloudSource/CloudSourceSetting.php';
				$cloudSourceSetting = new CloudSourceSetting();
				$cloudSourceSetting->id = $settingId;
				if ($cloudSourceSetting->find(true) && $cloudSourceSetting->showInExploreMore){
					//Load Cloud Source Options
					/** @var SearchObject_CloudSourceSearcher $cloudSourceSearcher */
					$cloudSourceSearcher = SearchObjectFactory::initSearchObject('CloudSource');
					$cloudSourceSearcher->setSearchTerms([
						'lookfor' => $searchTerm,
					]);
					$cloudSourceSearcher->setPage(1);
					$cloudSourceSearcher->setLimit($this->numEntriesToAdd);
					$cloudSourceResults = $cloudSourceSearcher->processSearch();
					if ($cloudSourceResults != null) {
						$exploreMoreOptions['sampleRecords']['cloudsource'] = [];
						$numMatches = $cloudSourceSearcher->getresultsTotal();
						if ($numMatches > 1) {
							if ($appliedTheme != null && !empty($appliedTheme->articlesDBImage)) {
								$image = '/files/original/' . $appliedTheme->articlesDBImage;
							} else {
								$image = '/interface/themes/responsive/images/cloudsource.png';
							}
							$exploreMoreOptions['searchLinks'][] = [
								'label' => translate([
									'text' => "All CloudSource OA Results (%1%)",
									1 => $numMatches,
									'isPublicFacing' => true,
								]),
								'description' => translate([
									'text' => "All Results in CloudSource OA related to %1%",
									1 => $searchTerm,
									'isPublicFacing' => true,
								]),
								'image' => $image,
								'link' => '/CloudSource/Results?lookfor=' . urlencode($searchTerm),
								'openInNewWindow' => false,
								'source' => 'CloudSource',
							];
							$numCloudSourceResultsAdded = 0;
							foreach ($cloudSourceResults as $doc) {
								require_once ROOT_DIR . '/RecordDrivers/CloudSourceRecordDriver.php';
								$driver = new CloudSourceRecordDriver($doc);
								if ($numCloudSourceResultsAdded < $this->numEntriesToAdd) {
									//Add a link to the actual title
									$exploreMoreOptions['sampleRecords']['cloudsource'][] = [
										'label' => $driver->getTitle(),
										'description' => $driver->getTitle(),
										'image' => $driver->getBookcoverUrl('medium'),
										'link' => $driver->getPatronUrl(),
										'onclick' => "AspenDiscovery.CloudSource.trackCloudSourceUsage('{$driver->getId()}')",
										'usageCount' => 1,
										'openInNewWindow' => false,
										'source' => 'CloudSource',
									];
								}

								$numCloudSourceResultsAdded++;
							}
						}
					}
				}
			}
		}
		return $exploreMoreOptions;
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
				$results = $searchObjectSolr->processSearch(true);

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
							'source' => 'Genealogy',
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
								'source' => 'Genealogy',
							];
						}

						$numCatalogResultsAdded++;
					}
				}
			}
		}
		return $exploreMoreOptions;
	}

	private static function filterAndSortExploreMoreEntries(array $entries, bool $preserveKeys = false): array {
		if (count($entries) <= 1) {
			return $entries;
		}

		$sourceSettings = self::getConfiguredSearchLinkSettings();
		if ($sourceSettings === null) {
			return $entries;
		}

		$sortableEntries = [];
		$originalOrder = 0;
		foreach ($entries as $key => $entry) {
			$source = self::getExploreMoreEntrySource($entry);
			if ($source !== null && isset($sourceSettings[$source]) && !$sourceSettings[$source]['enabled']) {
				continue;
			}
			$sortableEntries[$key] = [
				'entry' => $entry,
				'priority' => $sourceSettings[$source]['priority'] ?? (9999 + $originalOrder),
				'originalOrder' => $originalOrder++,
			];
		}

		uasort($sortableEntries, function ($a, $b) {
			$priorityComparison = $a['priority'] <=> $b['priority'];
			if ($priorityComparison !== 0) {
				return $priorityComparison;
			}
			return $a['originalOrder'] <=> $b['originalOrder'];
		});

		$sortedEntries = [];
		foreach ($sortableEntries as $key => $sortableEntry) {
			if ($preserveKeys) {
				$sortedEntries[$key] = $sortableEntry['entry'];
			} else {
				$sortedEntries[] = $sortableEntry['entry'];
			}
		}

		return $sortedEntries;
	}

	private static function getExploreMoreEntrySource($entry): ?string {
		if (!is_array($entry) || empty($entry)) {
			return null;
		}

		if (isset($entry['source'])) {
			return $entry['source'];
		}

		$firstEntry = reset($entry);
		if (is_array($firstEntry) && isset($firstEntry['source'])) {
			return $firstEntry['source'];
		}

		return null;
	}

	private static function getConfiguredSearchLinkSettings(): ?array {
		static $sourceSettings = false;
		if ($sourceSettings !== false) {
			return $sourceSettings;
		}

		global $library;
		global $locationSingleton;

		$currentLibraryId = isset($library->libraryId) ? (string)$library->libraryId : null;
		$activeLocation = $locationSingleton->getActiveLocation();
		$currentLocationId = !empty($activeLocation->locationId) ? (string)$activeLocation->locationId : null;

		$sourceSettings = [];
		$order = 0;
		$hasConfiguredEntries = false;
		$entry = new ExploreMoreSourceEntry();
		$entry->exploreMoreSourceGroupId = 1;
		$entry->orderBy('weight ASC');
		$entry->find();
		while ($entry->fetch()) {
			$hasConfiguredEntries = true;
			$source = new ExploreMoreSource();
			$source->id = $entry->exploreMoreSourceId;
			if (!$source->find(true)) {
				continue;
			}

			$libraries = $source->getLibraries();
			$locations = $source->getLocations();
			$libraryAllowed = empty($libraries) || ($currentLibraryId !== null && in_array($currentLibraryId, $libraries, true));
			$locationAllowed = empty($locations) || $currentLocationId === null || in_array($currentLocationId, $locations, true);

			$sourceSettings[$source->source] = [
				'enabled' => !empty($source->showInExploreMore) && $libraryAllowed && $locationAllowed,
				'priority' => $order++,
			];
		}

		if (!$hasConfiguredEntries) {
			$sourceSettings = null;
		}

		return $sourceSettings;
	}
}
