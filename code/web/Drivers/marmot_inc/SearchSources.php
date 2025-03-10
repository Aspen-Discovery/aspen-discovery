<?php

class SearchSources {
	static function getSearchSources() {
		return SearchSources::getSearchSourcesDefault();
	}

	/**
	 * @param string $source
	 *
	 * @return SearchObject_BaseSearcher
	 */
	static function getSearcherForSource($source) {
		switch ($source) {
			case 'ebsco_eds':
				$searchObject = SearchObjectFactory::initSearchObject('EbscoEds');
				break;
			case 'summon':
				$searchObject = SearchObjectFactory::initSearchObject('Summon');
				break;
			case 'events':
				$searchObject = SearchObjectFactory::initSearchObject('Events');
				break;
			case 'genealogy':
				$searchObject = SearchObjectFactory::initSearchObject('Genealogy');
				break;
			case 'lists':
				$searchObject = SearchObjectFactory::initSearchObject('Lists');
				break;
			case 'course_reserves':
				$searchObject = SearchObjectFactory::initSearchObject('CourseReserves');
				break;
			case 'open_archives':
				$searchObject = SearchObjectFactory::initSearchObject('OpenArchives');
				break;
			case 'websites':
				$searchObject = SearchObjectFactory::initSearchObject('Websites');
				break;
			case 'series':
				$searchObject = SearchObjectFactory::initSearchObject('Series');
				break;
			case 'catalog':
			default:
				/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */ $searchObject = SearchObjectFactory::initSearchObject();
		}
		$searchObject->init();

		return $searchObject;
	}


	/**
	 * @param SearchObject_BaseSearcher $searchObject
	 * @param string $source
	 * @return array
	 */
	static function getSearchIndexesForSource($searchObject, $source) {
		if ($searchObject == null) {
			$searchObject = SearchSources::getSearcherForSource($source);
		}
		return is_object($searchObject) ? $searchObject->getSearchIndexes() : [];
	}

	private static function getSearchSourcesDefault() {
		$searchOptions = [];
		//Check to see if marmot catalog is a valid option
		global $library;
		global $enabledModules;

		/** @var Location $locationSingleton */
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		if ($location != null && $location->useScope && $location->restrictSearchByLocation) {
			$repeatSearchSetting = $location->repeatSearchOption;
			$repeatInWorldCat = $location->repeatInWorldCat == 1;
			$repeatInInnReach = $location->repeatInInnReach == 1;
			$repeatInShareIt = $location->repeatInShareIt == 1;
			$repeatInCloudSource = $location->repeatInCloudSource == 1;
			if (strlen($location->systemsToRepeatIn) > 0) {
				$systemsToRepeatIn = explode('|', $location->systemsToRepeatIn);
			} else {
				$systemsToRepeatIn = explode('|', $library->systemsToRepeatIn);
			}
		} else {
			$repeatSearchSetting = $library->repeatSearchOption;
			$repeatInWorldCat = $library->repeatInWorldCat == 1;
			$repeatInInnReach = $library->repeatInInnReach == 1;
			$repeatInShareIt = $library->repeatInShareIt == 1;
			$repeatInCloudSource = $library->repeatInCloudSource == 1;
			$systemsToRepeatIn = explode('|', $library->systemsToRepeatIn);
		}

		$searchGenealogy = array_key_exists('Genealogy', $enabledModules) && $library->enableGenealogy;
		$repeatCourseReserves = $library->enableCourseReserves == 1;
		$searchEbscoEDS = array_key_exists('EBSCO EDS', $enabledModules) && $library->edsSettingsId != -1;
		$searchEbscohost = array_key_exists('EBSCOhost', $enabledModules) && $library->ebscohostSearchSettingId != -1;
		$searchSummon = array_key_exists('Summon', $enabledModules) && $library->summonSettingsId != -1;
		$searchOpenArchives = array_key_exists('Open Archives', $enabledModules) && $library->enableOpenArchives == 1;
		$searchCourseReserves = $library->enableCourseReserves == 2;
		$searchSeries = array_key_exists('Series', $enabledModules) && $library->useSeriesSearchIndex == 1;

		[
			$enableCombinedResults,
			$showCombinedResultsFirst,
			$combinedResultsName,
		] = self::getCombinedSearchSetupParameters($location, $library);

		if ($enableCombinedResults && $showCombinedResultsFirst) {
			$searchOptions['combined'] = [
				'name' => $combinedResultsName,
				'description' => "Combined results from multiple sources.",
				'catalogType' => 'combined',
				'hasAdvancedSearch' => false,
			];
		}

		//Local search
		if (!empty($location) && $location->useScope && $location->restrictSearchByLocation) {
			$searchOptions['local'] = [
				'name' => $location->displayName,
				'description' => "The {$location->displayName} catalog.",
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true,
			];
		} else {
			$searchOptions['local'] = [
				'name' => 'Library Catalog',
				'description' => "The {$library->displayName} catalog.",
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true,
			];
		}

		if (($location != null) && ($repeatSearchSetting == 'marmot' || $repeatSearchSetting == 'librarySystem') && ($location->useScope && $location->restrictSearchByLocation)) {
			$searchOptions[$library->subdomain] = [
				'name' => $library->displayName,
				'description' => "The entire {$library->displayName} catalog not limited to a particular branch.",
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true,
			];
		}

		//Process additional systems to repeat in
		if (count($systemsToRepeatIn) > 0) {
			foreach ($systemsToRepeatIn as $system) {
				if (strlen($system) > 0) {
					$repeatInLibrary = new Library();
					$repeatInLibrary->subdomain = $system;
					$repeatInLibrary->find();
					if ($repeatInLibrary->getNumResults() == 1) {
						$repeatInLibrary->fetch();

						$searchOptions[$repeatInLibrary->subdomain] = [
							'name' => $repeatInLibrary->displayName,
							'description' => '',
							'catalogType' => 'catalog',
							'hasAdvancedSearch' => true,
						];
					} else {
						//See if this is a repeat within a location
						$repeatInLocation = new Location();
						$repeatInLocation->code = $system;
						$repeatInLocation->find();
						if ($repeatInLocation->getNumResults() == 1) {
							$repeatInLocation->fetch();

							$searchOptions[$repeatInLocation->code] = [
								'name' => $repeatInLocation->displayName,
								'description' => '',
								'catalogType' => 'catalog',
								'hasAdvancedSearch' => true,
							];
						}
					}
				}
			}
		}

		$includeOnlineOption = true;
		if ($location != null && $location->repeatInOnlineCollection == 0) {
			$includeOnlineOption = false;
		} elseif ($library != null && $library->repeatInOnlineCollection == 0) {
			$includeOnlineOption = false;
		}

		if ($includeOnlineOption) {
			//eContent Search
			$searchOptions['econtent'] = [
				'name' => 'Online Collection',
				'description' => 'Digital Media available for use online and with portable devices',
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true,
			];
		}

		if ($searchEbscoEDS) {
			$searchOptions['ebsco_eds'] = [
				'name' => 'Articles & Databases',
				'description' => 'EBSCO EDS - Articles and Database',
				'catalogType' => 'ebsco_eds',
				'hasAdvancedSearch' => false,
			];
		} elseif ($searchEbscohost) {
			$searchOptions['ebscohost'] = [
				'name' => 'Articles & Databases',
				'description' => 'EBSCOhost - Articles and Database',
				'catalogType' => 'ebscohost',
				'hasAdvancedSearch' => false,
			];
		}

		if ($searchSummon) {
			$searchOptions['summon'] = [
				'name' => 'Articles & Databases',
				'description' => 'Summon - Articles and Database',
				'catalogType' => 'summon',
				'hasAdvancedSearch' => false,
			];
		}

		// if ($searchSummon) {
		// 	$searchOptions['summon'] = [
		// 		'name' => 'Articles & Databases',
		// 		'description' => 'Summon - Articles and Database',
		// 		'catalogType' => 'summon',
		// 		'hasAdvancedSearch' => false,
		// 	];
		// }

		if (array_key_exists('Events', $enabledModules)) {
			require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
			$libraryEventsSetting = new LibraryEventsSetting();
			$libraryEventsSetting->libraryId = $library->libraryId;
			if ($libraryEventsSetting->find(true)) {
				$searchOptions['events'] = [
					'name' => 'Events',
					'description' => 'Search events at the library',
					'catalogType' => 'events',
					'hasAdvancedSearch' => false,
				];
			}
		}

		$searchOptions['lists'] = [
			'name' => 'Lists',
			'description' => 'User Lists',
			'catalogType' => 'lists',
			'hasAdvancedSearch' => false,
		];

		if (array_key_exists('Course Reserves', $enabledModules) && $searchCourseReserves) {
			$searchOptions['course_reserves'] = [
				'name' => 'Course Reserves',
				'description' => 'Course Reserves',
				'catalogType' => 'course_reserves',
				'hasAdvancedSearch' => false,
			];
		}

		if ($searchSeries) {
			$searchOptions['series'] = [
				'name' => 'Series',
				'description' => 'Series',
				'catalogType' => 'series',
				'hasAdvancedSearch' => false,
			];
		}

		if (array_key_exists('Web Indexer', $enabledModules)) {
			require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';
			$websiteSetting = new WebsiteIndexSetting();
			$websiteSetting->find();
			//TODO: Need to deal with searching different collections
			while ($websiteSetting->fetch()) {
				if ($websiteSetting->isValidForSearching()) {
					$searchOptions['websites'] = [
						'name' => 'Library Website',
						'description' => 'Library Website',
						'catalogType' => 'websites',
						'hasAdvancedSearch' => false,
					];
				}
			}
			//Local search, activate if we have at least one page
			if ($library->enableWebBuilder) {
				$searchOptions['websites'] = [
					'name' => 'Library Website',
					'description' => 'Library Website',
					'catalogType' => 'websites',
					'hasAdvancedSearch' => false,
				];
			}
		}

		if ($searchOpenArchives) {
			$searchOptions['open_archives'] = [
				'name' => 'History & Archives',
				'description' => 'Local History and Archive Information',
				'catalogType' => 'open_archives',
				'hasAdvancedSearch' => false,
			];
		}

		//Genealogy Search
		if ($searchGenealogy) {
			$searchOptions['genealogy'] = [
				'name' => 'Genealogy Records',
				'description' => 'Genealogy Records',
				'catalogType' => 'genealogy',
				'hasAdvancedSearch' => false,
			];
		}

		if ($enableCombinedResults && !$showCombinedResultsFirst) {
			$searchOptions['combined'] = [
				'name' => $combinedResultsName,
				'description' => "Combined results from multiple sources.",
				'catalogType' => 'combined',
				'hasAdvancedSearch' => false,
			];
		}

		if ($repeatInCloudSource) {
			$searchOptions['cloudSource'] = [
				'name' => 'CloudSource',
				'description' => "Open Articles, eBooks, eTextBooks, and more from CloudSource.",
				'external' => true,
				'catalogType' => 'cloudSource',
				'hasAdvancedSearch' => false,
			];
		}

		if ($repeatInInnReach) {
			$searchOptions['innReach'] = [
				'name' => $library->interLibraryLoanName,
				'description' => 'Additional materials from partner libraries available by interlibrary loan.',
				'external' => true,
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => false,
			];
		}

		if ($repeatInShareIt) {
			$searchOptions['shareIt'] = [
				'name' => $library->interLibraryLoanName,
				'description' => 'Additional materials from partner libraries available by interlibrary loan.',
				'external' => true,
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => false,
			];
		}

		//Course reserves for colleges
		if ($repeatCourseReserves) {
			//Mesa State
			$searchOptions['course-reserves-course-name'] = [
				'name' => 'Course Reserves by Name or Number',
				'description' => 'Search course reserves by course name or number',
				'external' => true,
				'catalogType' => 'courseReserves',
				'hasAdvancedSearch' => false,
			];
			$searchOptions['course-reserves-instructor'] = [
				'name' => 'Course Reserves by Instructor',
				'description' => 'Search course reserves by professor, lecturer, or instructor name',
				'external' => true,
				'catalogType' => 'courseReserves',
				'hasAdvancedSearch' => false,
			];
		}

		if ($repeatInWorldCat) {
			$searchOptions['worldcat'] = [
				'name' => 'WorldCat',
				'description' => 'A shared catalog of libraries all over the world.',
				'external' => true,
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => false,
			];
		}

		return $searchOptions;
	}

	/**
	 * @param Location $location
	 * @param Library $library
	 * @return array
	 */
	static function getCombinedSearchSetupParameters($location, $library) {
		$enableCombinedResults = false;
		$showCombinedResultsFirst = false;
		$combinedResultsName = 'Combined Results';
		if ($location && !$location->useLibraryCombinedResultsSettings) {
			$enableCombinedResults = $location->enableCombinedResults;
			$showCombinedResultsFirst = $location->defaultToCombinedResults;
			$combinedResultsName = $location->combinedResultsLabel;
			return [
				$enableCombinedResults,
				$showCombinedResultsFirst,
				$combinedResultsName,
			];
		} elseif ($library) {
			$enableCombinedResults = $library->enableCombinedResults;
			$showCombinedResultsFirst = $library->defaultToCombinedResults;
			$combinedResultsName = $library->combinedResultsLabel;
			return [
				$enableCombinedResults,
				$showCombinedResultsFirst,
				$combinedResultsName,
			];
		}
		return [
			$enableCombinedResults,
			$showCombinedResultsFirst,
			$combinedResultsName,
		];
	}

	public function getWorldCatSearchType($type) {
		/** @noinspection PhpSwitchCanBeReplacedWithMatchExpressionInspection */
		switch ($type) {
			case 'Subject':
				return 'su';
			case 'Author':
				return 'au';
			case 'Title':
				return 'ti';
			case 'ISN':
				return 'bn';
			case 'Keyword':
			default:
				return 'kw';
		}
	}

	public function getExternalLink($searchSource, $type, $lookFor) : string {
		/** Library $library */
		global $library;
		global $configArray;
		if ($searchSource == 'worldcat') {
			$worldCatSearchType = $this->getWorldCatSearchType($type);
			$worldCatLink = "https://www.worldcat.org/search?q={$worldCatSearchType}%3A" . urlencode($lookFor);
			if (strlen($library->worldCatUrl) > 0) {
				$worldCatLink = $library->worldCatUrl;
				if (strpos($worldCatLink, '?') == false) {
					$worldCatLink .= "?";
				}
				$worldCatLink .= "q={$worldCatSearchType}:" . urlencode($lookFor);
				//Repeat the search term with a parameter of queryString since some interfaces use that parameter instead of q
				$worldCatLink .= "&queryString={$worldCatSearchType}:" . urlencode($lookFor);
				if (strlen($library->worldCatQt) > 0) {
					$worldCatLink .= "&qt=" . $library->worldCatQt;
				}
			}
			return $worldCatLink;
		} elseif ($searchSource == 'overdrive') {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
			$overDriveScope = new OverDriveScope();
			$overDriveScope->id = $library->overDriveScopeId;
			if ($overDriveScope->find(true)) {
				require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
				$overDriveSettings = new OverDriveSetting();
				$overDriveSettings->id = $overDriveScope->settingId;
				if ($overDriveSettings->find(true)) {
					$overDriveUrl = $overDriveSettings->url;
					return "$overDriveUrl/search?query=" . urlencode($lookFor);
				}
			}
		} elseif ($searchSource == 'innReach') {
			$innReachSearchType = $this->getInnReachSearchType($type);
			$lookFor = str_replace('+', '%20', rawurlencode($lookFor));
			// Handle special exception: ? character in the search must be encoded specially
			$lookFor = str_replace('%3F', 'Pw%3D%3D', $lookFor);
			if ($innReachSearchType != ' ') {
				$lookFor = "$innReachSearchType:(" . $lookFor . ")";
			}
			$baseUrl = $library->interLibraryLoanUrl;
			if (substr($baseUrl, -1, 1) == '/') {
				$baseUrl = substr($baseUrl, 0, strlen($baseUrl) -1);
			}
			return "$baseUrl/iii/encore/search/C|S" . $lookFor . "|Orightresult|U1?lang=eng&amp;suite=def";
		} elseif ($searchSource == 'shareIt') {
			require_once ROOT_DIR . '/sys/InterLibraryLoan/ShareIt.php';
			$shareIt = new ShareIt();
			$searchTerms = [
				[
					'index' => $type,
					'lookfor' => $lookFor,
				],
			];
			$link = $shareIt->getSearchLink($searchTerms);
			return $link;
		} elseif ($searchSource == 'cloudSource') {
			return $library->cloudSourceBaseUrl . '/search/results?qu=' . urlencode($lookFor) . '&te=1803299674&dt=list';
		} elseif ($searchSource == 'amazon') {
			return "http://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=" . urlencode($lookFor);
		} elseif ($searchSource == 'course-reserves-course-name') {
			$accountProfile = $library->getAccountProfile();
			if ($accountProfile != false) {
				$linkingUrl = $accountProfile->vendorOpacUrl;
			}else{
				$linkingUrl = $configArray['Catalog']['linking_url'];
			}
			return "$linkingUrl/search~S{$library->scope}/r?SEARCH=" . urlencode($lookFor);
		} elseif ($searchSource == 'course-reserves-instructor') {
			$accountProfile = $library->getAccountProfile();
			if ($accountProfile != false) {
				$linkingUrl = $accountProfile->vendorOpacUrl;
			}else{
				$linkingUrl = $configArray['Catalog']['linking_url'];
			}
			return "$linkingUrl/search~S{$library->scope}/p?SEARCH=" . urlencode($lookFor);
		}
		return "";
	}

	public function getInnReachSearchType($type) {
		switch ($type) {
			case 'Subject':
				return 'd';
			case 'Author':
				return 'a';
			case 'Title':
				return 't';
			case 'ISN':
				return 'i';
			case 'Keyword':
			default:
				return ' ';
		}
	}
}