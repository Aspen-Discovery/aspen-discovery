<?php
class SearchSources{
	static function getSearchSources(){
		return SearchSources::getSearchSourcesDefault();
	}

	/**
	 * @param string $source
	 *
	 * @return SearchObject_BaseSearcher
	 */
	static function getSearcherForSource($source){
		switch ($source)
		{
			case 'ebsco_eds':
				$searchObject = SearchObjectFactory::initSearchObject('EbscoEds');
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
			case 'catalog':
			default:
				/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject();
		}
		$searchObject->init();

		return $searchObject;
	}


	/**
	 * @param SearchObject_BaseSearcher $searchObject
	 * @param string $source
	 * @return array
	 */
	static function getSearchIndexesForSource($searchObject, $source){
		if ($searchObject == null) {
			$searchObject = SearchSources::getSearcherForSource($source);
		}
		return is_object($searchObject) ? $searchObject->getSearchIndexes() : array();
	}

	private static function getSearchSourcesDefault(){
		$searchOptions = array();
		//Check to see if marmot catalog is a valid option
		global $library;
		global $enabledModules;

		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		if ($location != null && $location->useScope && $location->restrictSearchByLocation){
			$repeatSearchSetting = $location->repeatSearchOption;
			$repeatInWorldCat = $location->repeatInWorldCat == 1;
			$repeatInProspector = $location->repeatInProspector == 1;
			if (strlen($location->systemsToRepeatIn) > 0){
				$systemsToRepeatIn = explode('|', $location->systemsToRepeatIn);
			}else{
				$systemsToRepeatIn = explode('|', $library->systemsToRepeatIn);
			}
		}else{
			$repeatSearchSetting = $library->repeatSearchOption;
			$repeatInWorldCat = $library->repeatInWorldCat == 1;
			$repeatInProspector = $library->repeatInProspector == 1;
			$systemsToRepeatIn = explode('|', $library->systemsToRepeatIn);
		}

		$searchGenealogy = array_key_exists('Genealogy', $enabledModules) && $library->enableGenealogy;
		$repeatCourseReserves = $library->enableCourseReserves == 1;
		$searchEbscoEDS = array_key_exists('EBSCO EDS', $enabledModules) && $library->edsSettingsId != -1;
		$searchEbscohost = array_key_exists('EBSCOhost', $enabledModules) && $library->ebscohostSettingId != -1;
		$searchOpenArchives = array_key_exists('Open Archives', $enabledModules) && $library->enableOpenArchives == 1;
		$searchCourseReserves = $library->enableCourseReserves == 2;

		list($enableCombinedResults, $showCombinedResultsFirst, $combinedResultsName) = self::getCombinedSearchSetupParameters($location, $library);

		if ($enableCombinedResults && $showCombinedResultsFirst){
			$searchOptions['combined'] = array(
				'name' => $combinedResultsName,
				'description' => "Combined results from multiple sources.",
				'catalogType' => 'combined',
				'hasAdvancedSearch' => false
			);
		}

		//Local search
		if (!empty($location) && $location->useScope && $location->restrictSearchByLocation){
			$searchOptions['local'] = array(
				'name' => $location->displayName,
				'description' => "The {$location->displayName} catalog.",
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true
			);
		}else{
			$searchOptions['local'] = array(
				'name' => 'Library Catalog',
				'description' => "The {$library->displayName} catalog.",
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true
			);
		}

		if (($location != null) &&
			($repeatSearchSetting == 'marmot' || $repeatSearchSetting == 'librarySystem') &&
			($location->useScope && $location->restrictSearchByLocation)
		){
			$searchOptions[$library->subdomain] = array(
				'name' => $library->displayName,
				'description' => "The entire {$library->displayName} catalog not limited to a particular branch.",
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true
			);
		}

		//Process additional systems to repeat in
		if (count($systemsToRepeatIn) > 0){
			foreach ($systemsToRepeatIn as $system){
				if (strlen($system) > 0){
					$repeatInLibrary = new Library();
					$repeatInLibrary->subdomain = $system;
					$repeatInLibrary->find();
					if ($repeatInLibrary->getNumResults() == 1){
						$repeatInLibrary->fetch();

						$searchOptions[$repeatInLibrary->subdomain] = array(
							'name' => $repeatInLibrary->displayName,
							'description' => '',
							'catalogType' => 'catalog',
							'hasAdvancedSearch' => true
						);
					}else{
						//See if this is a repeat within a location
						$repeatInLocation = new Location();
						$repeatInLocation->code = $system;
						$repeatInLocation->find();
						if ($repeatInLocation->getNumResults() == 1){
							$repeatInLocation->fetch();

							$searchOptions[$repeatInLocation->code] = array(
								'name' => $repeatInLocation->displayName,
								'description' => '',
								'catalogType' => 'catalog',
								'hasAdvancedSearch' => true
							);
						}
					}
				}
			}
		}

		$includeOnlineOption = true;
		if ($location != null && $location->repeatInOnlineCollection == 0) {
			$includeOnlineOption = false;
		}elseif ($library != null && $library->repeatInOnlineCollection == 0) {
			$includeOnlineOption = false;
		}

		if ($includeOnlineOption){
			//eContent Search
			$searchOptions['econtent'] = array(
				'name' => 'Online Collection',
				'description' => 'Digital Media available for use online and with portable devices',
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => true
			);
		}

		if ($searchEbscoEDS){
			$searchOptions['ebsco_eds'] = array(
				'name' => 'Articles & Databases',
				'description' => 'EBSCO EDS - Articles and Database',
				'catalogType' => 'ebsco_eds',
				'hasAdvancedSearch' => false
			);
		}

		if ($searchEbscohost){
			$searchOptions['ebscohost'] = array(
				'name' => 'Articles & Databases',
				'description' => 'EBSCOhost - Articles and Database',
				'catalogType' => 'ebscohost',
				'hasAdvancedSearch' => false
			);
		}

		if (array_key_exists('Events', $enabledModules)){
			require_once ROOT_DIR . '/sys/Events/LibraryEventsSetting.php';
			$libraryEventsSetting = new LibraryEventsSetting();
			$libraryEventsSetting->libraryId = $library->libraryId;
			if ($libraryEventsSetting->find(true)){
				$searchOptions['events'] = array(
					'name' => 'Events',
					'description' => 'Search events at the library',
					'catalogType' => 'events',
					'hasAdvancedSearch' => false
				);
			}
		}

		$searchOptions['lists'] = array(
			'name' => 'Lists',
			'description' => 'User Lists',
			'catalogType' => 'lists',
			'hasAdvancedSearch' => false
		);

		if (array_key_exists('Course Reserves', $enabledModules) && $searchCourseReserves){
			$searchOptions['course_reserves'] = array(
				'name' => 'Course Reserves',
				'description' => 'Course Reserves',
				'catalogType' => 'course_reserves',
				'hasAdvancedSearch' => false
			);
		}

		if (array_key_exists('Web Indexer', $enabledModules)){
			require_once ROOT_DIR . '/sys/WebsiteIndexing/WebsiteIndexSetting.php';
			$websiteSetting = new WebsiteIndexSetting();
			$websiteSetting->find();
			//TODO: Need to deal with searching different collections
			while ($websiteSetting->fetch()) {
				if ($websiteSetting->isValidForSearching()) {
					$searchOptions['websites'] = array(
						'name' => 'Library Website',
						'description' => 'Library Website',
						'catalogType' => 'websites',
						'hasAdvancedSearch' => false
					);
				}
			}
			//Local search, activate if we have at least one page
			if ($library->enableWebBuilder) {
				$searchOptions['websites'] = array(
					'name' => 'Library Website',
					'description' => 'Library Website',
					'catalogType' => 'websites'
				);
			}
		}

		if ($searchOpenArchives){
			$searchOptions['open_archives'] = array(
				'name' => 'History & Archives',
				'description' => 'Local History and Archive Information',
				'catalogType' => 'open_archives',
				'hasAdvancedSearch' => false
			);
		}

		//Genealogy Search
		if ($searchGenealogy){
			$searchOptions['genealogy'] = array(
				'name' => 'Genealogy Records',
				'description' => 'Genealogy Records',
				'catalogType' => 'genealogy',
				'hasAdvancedSearch' => false
			);
		}

		if ($enableCombinedResults && !$showCombinedResultsFirst){
			$searchOptions['combined'] = array(
				'name' => $combinedResultsName,
				'description' => "Combined results from multiple sources.",
				'catalogType' => 'combined',
				'hasAdvancedSearch' => false
			);
		}

		if ($repeatInProspector){
			$searchOptions['prospector'] = array(
				'name' => 'Prospector Catalog',
				'description' => 'A shared catalog of academic, public, and special libraries all over Colorado.',
				'external' => true,
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => false
			);
		}

		//Course reserves for colleges
		if ($repeatCourseReserves){
			//Mesa State
			$searchOptions['course-reserves-course-name'] = array(
				'name' => 'Course Reserves by Name or Number',
				'description' => 'Search course reserves by course name or number',
				'external' => true,
				'catalogType' => 'courseReserves',
				'hasAdvancedSearch' => false
			);
			$searchOptions['course-reserves-instructor'] = array(
				'name' => 'Course Reserves by Instructor',
				'description' => 'Search course reserves by professor, lecturer, or instructor name',
				'external' => true,
				'catalogType' => 'courseReserves',
				'hasAdvancedSearch' => false
			);
		}

		if ($repeatInWorldCat){
			$searchOptions['worldcat'] = array(
				'name' => 'WorldCat',
				'description' => 'A shared catalog of libraries all over the world.',
				'external' => true,
				'catalogType' => 'catalog',
				'hasAdvancedSearch' => false
			);
		}

		return $searchOptions;
	}

	/**
	 * @param $location
	 * @param $library
	 * @return array
	 */
	static function getCombinedSearchSetupParameters($location, $library)
	{
		$enableCombinedResults = false;
		$showCombinedResultsFirst = false;
		$combinedResultsName = 'Combined Results';
		if ($location && !$location->useLibraryCombinedResultsSettings) {
			$enableCombinedResults = $location->enableCombinedResults;
			$showCombinedResultsFirst = $location->defaultToCombinedResults;
			$combinedResultsName = $location->combinedResultsLabel;
			return array($enableCombinedResults, $showCombinedResultsFirst, $combinedResultsName);
		} else if ($library) {
			$enableCombinedResults = $library->enableCombinedResults;
			$showCombinedResultsFirst = $library->defaultToCombinedResults;
			$combinedResultsName = $library->combinedResultsLabel;
			return array($enableCombinedResults, $showCombinedResultsFirst, $combinedResultsName);
		}
		return array($enableCombinedResults, $showCombinedResultsFirst, $combinedResultsName);
	}

	public function getWorldCatSearchType($type){
		switch ($type){
			case 'Subject':
				return 'su';
				break;
			case 'Author':
				return 'au';
				break;
			case 'Title':
				return 'ti';
				break;
			case 'ISN':
				return 'bn';
				break;
			case 'Keyword':
			default:
				return 'kw';
				break;
		}
	}

	public function getExternalLink($searchSource, $type, $lookFor){
		global $library;
		global $configArray;
		if ($searchSource == 'worldcat'){
			$worldCatSearchType = $this->getWorldCatSearchType($type);
			$worldCatLink = "http://www.worldcat.org/search?q={$worldCatSearchType}%3A".urlencode($lookFor);
			if (strlen($library->worldCatUrl) > 0){
				$worldCatLink = $library->worldCatUrl;
				if (strpos($worldCatLink, '?') == false){
					$worldCatLink .= "?";
				}
				$worldCatLink .= "q={$worldCatSearchType}:".urlencode($lookFor);
				//Repeat the search term with a parameter of queryString since some interfaces use that parameter instead of q
				$worldCatLink .= "&queryString={$worldCatSearchType}:".urlencode($lookFor);
				if (strlen($library->worldCatQt) > 0){
					$worldCatLink .= "&qt=" . $library->worldCatQt;
				}
			}
			return $worldCatLink;
		}else if ($searchSource == 'overdrive'){
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
			$overDriveScope = new OverDriveScope();
			$overDriveScope->id = $library->overDriveScopeId;
			if ($overDriveScope->find(true)){
				require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
				$overDriveSettings = new OverDriveSetting();
				$overDriveSettings->id = $overDriveScope->settingId;
				if ($overDriveSettings->find(true)) {
					$overDriveUrl = $overDriveSettings->url;
					return "$overDriveUrl/search?query=" . urlencode($lookFor);
				}
			}
		}else if ($searchSource == 'prospector'){
			$prospectorSearchType = $this->getProspectorSearchType($type);
			$lookFor = str_replace('+', '%20', rawurlencode($lookFor));
			// Handle special exception: ? character in the search must be encoded specially
			$lookFor  = str_replace('%3F', 'Pw%3D%3D',$lookFor);
			if ($prospectorSearchType != ' '){
				$lookFor = "$prospectorSearchType:(" . $lookFor . ")";
			}
			return "http://encore.coalliance.org/iii/encore/search/C|S" . $lookFor ."|Orightresult|U1?lang=eng&amp;suite=def";
		}else if ($searchSource == 'amazon'){
			return "http://www.amazon.com/s/ref=nb_sb_noss?url=search-alias%3Daps&field-keywords=" . urlencode($lookFor);
		}else if ($searchSource == 'course-reserves-course-name'){
			$linkingUrl = $configArray['Catalog']['linking_url'];
			return "$linkingUrl/search~S{$library->scope}/r?SEARCH=" . urlencode($lookFor);
		}else if ($searchSource == 'course-reserves-instructor'){
			$linkingUrl = $configArray['Catalog']['linking_url'];
			return "$linkingUrl/search~S{$library->scope}/p?SEARCH=" . urlencode($lookFor);
		}else{
			return "";
		}
	}

	public function getProspectorSearchType($type){
		switch ($type){
			case 'Subject':
				return 'd';
				break;
			case 'Author':
				return 'a';
				break;
			case 'Title':
				return 't';
				break;
			case 'ISN':
				return 'i';
				break;
			case 'Keyword':
				return ' ';
				break;
		}
		return ' ';
	}
}