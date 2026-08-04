<?php

class SearchSources {
	function getSearchSources() : array {
		return SearchSources::getSearchSourcesDefault();
	}

	/**
	 * @param string $source
	 *
	 * @return SearchObject_BaseSearcher
	 */
	static function getSearcherForSource(string $source) : SearchObject_BaseSearcher {
		$searchObject = match ($source) {
			'ebsco_eds' => SearchObjectFactory::initSearchObject('EbscoEds'),
			'summon' => SearchObjectFactory::initSearchObject('Summon'),
			'cloudsource' => SearchObjectFactory::initSearchObject('CloudSource'),
			'events' => SearchObjectFactory::initSearchObject('Events'),
			'genealogy' => SearchObjectFactory::initSearchObject('Genealogy'),
			'lists' => SearchObjectFactory::initSearchObject('Lists'),
			'course_reserves' => SearchObjectFactory::initSearchObject('CourseReserves'),
			'open_archives' => SearchObjectFactory::initSearchObject('OpenArchives'),
			'websites' => SearchObjectFactory::initSearchObject('Websites'),
			'series' => SearchObjectFactory::initSearchObject('Series'),
			'talpa' => SearchObjectFactory::initSearchObject("Talpa"),
			'gale' => SearchObjectFactory::initSearchObject('Gale'),
			default => SearchObjectFactory::initSearchObject(),
		};
		$searchObject->init();

		return $searchObject;
	}


	/**
	 * @param ?SearchObject_BaseSearcher $searchObject
	 * @param string $source
	 * @return array
	 */
	static function getSearchIndexesForSource(?SearchObject_BaseSearcher $searchObject, string $source) : array {
		if ($searchObject == null) {
			$searchObject = SearchSources::getSearcherForSource($source);
		}
		return is_object($searchObject) ? $searchObject->getSearchIndexes() : [];
	}

	static ?array $searchOptions = null;
	private static function getSearchSourcesDefault() : array {
		if (self::$searchOptions == null) {
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
			$searchGale = array_key_exists('Gale', $enabledModules) && $library->galeSettingsId != -1;
			$searchSummon = array_key_exists('Summon', $enabledModules) && $library->summonSettingsId != -1;
			$searchCloudSource = array_key_exists('CloudSource', $enabledModules);
			if ($searchCloudSource) {
				//Check to see if we have active settings for the library/location
				if ($location != null) {
					$searchCloudSource = $location->getCloudSourceSettingId() > 0;
				}else{
					$searchCloudSource = $library->getCloudSourceSettingId() > 0;
				}
			}
			$searchOpenArchives = array_key_exists('Open Archives', $enabledModules) && $library->enableOpenArchives == 1;
			$searchTalpa = array_key_exists('Talpa Search', $enabledModules) && $library->enableTalpaSearch == 1;
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
					'description' => "The $location->displayName catalog.",
					'catalogType' => 'catalog',
					'hasAdvancedSearch' => $library->showAdvancedSearchbox,
				];
			} else {
				$searchOptions['local'] = [
					'name' => 'Library Catalog',
					'description' => "The $library->displayName catalog.",
					'catalogType' => 'catalog',
					'hasAdvancedSearch' => $library->showAdvancedSearchbox,
				];
			}

			if (($location != null) && ($repeatSearchSetting == 'marmot' || $repeatSearchSetting == 'librarySystem') && ($location->useScope && $location->restrictSearchByLocation)) {
				$searchOptions[$library->subdomain] = [
					'name' => $library->displayName,
					'description' => "The entire $library->displayName catalog not limited to a particular branch.",
					'catalogType' => 'catalog',
					'hasAdvancedSearch' => $library->showAdvancedSearchbox,
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
								'hasAdvancedSearch' => $library->showAdvancedSearchbox,
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
									'hasAdvancedSearch' => $library->showAdvancedSearchbox,
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
					'hasAdvancedSearch' => $library->showAdvancedSearchbox,
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

			if ($searchGale) {
				$searchOptions['gale'] = [
					'name' => 'Articles & Databases',
					'description' => 'Gale - Articles and Database',
					'catalogType' => 'gale',
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

			if ($searchCloudSource) {
				$searchOptions['cloudsource'] = [
					'name' => 'Articles & Databases',
					'description' => 'CloudSource - Articles and Database',
					'catalogType' => 'cloudsource',
					'hasAdvancedSearch' => false,
				];
			}

			if (array_key_exists('Events', $enabledModules)) {
				if (isset($library->aspenEventsToInclude) && $library->aspenEventsToInclude != 0) {
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
				if ($library->showWebsiteSearch) {
					//Local search, activate if we have at least one page
					if ($library->enableWebBuilder) {
						$searchOptions['websites'] = [
							'name' => 'Library Website',
							'description' => 'Library Website',
							'catalogType' => 'websites',
							'hasAdvancedSearch' => false,
						];
					} else {
						//We may still show website searching if there are indexed websites
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
								//We only need the first one
								break;
							}
						}
					}
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
			if ($searchTalpa) {
				require_once ROOT_DIR . '/sys/Talpa/TalpaSettings.php';
				$talpaSettings = new TalpaSettings();
				if (!$talpaSettings->find(true)) {
					$talpaSettings = null;
				}
				$searchOptions['talpa'] = [
					'name' => $talpaSettings->talpaSearchSourceString?:'Talpa Search',
					'description' => $talpaSettings->talpaSearchSourceString?:'Talpa Search',
					'catalogType' => 'talpa',
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
			self::$searchOptions = $searchOptions;
		}

		return self::$searchOptions;
	}

	/**
	 * @param ?Location $location
	 * @param Library $library
	 * @return array
	 */
	static function getCombinedSearchSetupParameters(?Location $location, Library $library) : array {
		if ($location && !$location->useLibraryCombinedResultsSettings) {
			$enableCombinedResults = $location->enableCombinedResults;
			$showCombinedResultsFirst = $location->defaultToCombinedResults;
			$combinedResultsName = $location->combinedResultsLabel;
		} else {
			$enableCombinedResults = $library->enableCombinedResults;
			$showCombinedResultsFirst = $library->defaultToCombinedResults;
			$combinedResultsName = $library->combinedResultsLabel;
		}
		return [
			$enableCombinedResults,
			$showCombinedResultsFirst,
			$combinedResultsName,
		];
	}

	public function getWorldCatSearchType($type) : string {
		return match ($type) {
			'Subject' => 'su',
			'Author' => 'au',
			'Title' => 'ti',
			'ISN' => 'bn',
			default => 'kw',
		};
	}

	public function getExternalLink($searchSource, $type, $lookFor) : string {
		/** Library $library */
		global $library;
		global $configArray;
		if ($searchSource == 'worldcat') {
			$worldCatSearchType = $this->getWorldCatSearchType($type);
			$worldCatLink = "https://www.worldcat.org/search?q=$worldCatSearchType%3A" . urlencode($lookFor);
			if (strlen($library->worldCatUrl) > 0) {
				$worldCatLink = $library->worldCatUrl;
				if (!strpos($worldCatLink, '?')) {
					$worldCatLink .= "?";
				}
				$worldCatLink .= "q=$worldCatSearchType:" . urlencode($lookFor);
				//Repeat the search term with a parameter of queryString since some interfaces use that parameter instead of q
				$worldCatLink .= "&queryString=$worldCatSearchType:" . urlencode($lookFor);
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
			if (str_ends_with($baseUrl, '/')) {
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
			return $shareIt->getSearchLink($searchTerms);
		} elseif ($searchSource == 'cloudSource') {
			return $library->cloudSourceBaseUrl . '/search/results?qu=' . urlencode($lookFor) . '&te=1803299674&dt=list';
		} elseif ($searchSource == 'amazon') {
			return "https://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=" . urlencode($lookFor);
		} elseif ($searchSource == 'course-reserves-course-name') {
			$accountProfile = $library->getAccountProfile();
			if ($accountProfile) {
				$linkingUrl = $accountProfile->vendorOpacUrl;
			}else{
				$linkingUrl = $configArray['Catalog']['linking_url'];
			}
			return "$linkingUrl/search~S$library->scope/r?SEARCH=" . urlencode($lookFor);
		} elseif ($searchSource == 'course-reserves-instructor') {
			$accountProfile = $library->getAccountProfile();
			if ($accountProfile) {
				$linkingUrl = $accountProfile->vendorOpacUrl;
			}else{
				$linkingUrl = $configArray['Catalog']['linking_url'];
			}
			return "$linkingUrl/search~S$library->scope/p?SEARCH=" . urlencode($lookFor);
		}
		return "";
	}

	public function getInnReachSearchType($type) : string {
		return match ($type) {
			'Subject' => 'd',
			'Author' => 'a',
			'Title' => 't',
			'ISN' => 'i',
			default => ' ',
		};
	}
}