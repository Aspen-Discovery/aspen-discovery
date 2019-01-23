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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';

class Browse_AJAX extends Action {

	const ITEMS_PER_PAGE = 24;

	/** @var SearchObject_Solr|SearchObject_Base $searchObject*/
	private $searchObject;

	function launch()
	{
		header ('Content-type: application/json');
		$method = $_REQUEST['method'];
		$allowed_methods = array(
				'getAddBrowseCategoryForm',
				'createBrowseCategory',
				'getMoreBrowseResults',
				'getBrowseCategoryInfo',
				'getBrowseSubCategoryInfo',
				'getActiveBrowseCategories',
				'getSubCategories'
		);
		if (in_array($method, $allowed_methods)) {
			$response = $this->$method();
		} else {
			$response = array('result'=> false);
		}
		echo json_encode($response);
	}

	function getAddBrowseCategoryForm(){
		global $interface;

		// Select List Creation using Object Editor functions
		require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
		$temp = SubBrowseCategories::getObjectStructure();
		$temp['subCategoryId']['values'] = array(0 => 'Select One') + $temp['subCategoryId']['values'];
			// add default option that denotes nothing has been selected to the options list
			// (this preserves the keys' numeric values (which is essential as they are the Id values) as well as the array's order)
			// btw addition of arrays is kinda a cool trick.
		$interface->assign('propName', 'addAsSubCategoryOf');
		$interface->assign('property', $temp['subCategoryId']);

		// Display Page
		$interface->assign('searchId', strip_tags($_REQUEST['searchId']));
		$results = array(
			'title' => 'Add as Browse Category to Home Page',
			'modalBody' => $interface->fetch('Browse/addBrowseCategoryForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#createBrowseCategory\").submit();'>Create Category</button>"
		);
		return $results;
	}

	function createBrowseCategory(){
		global $library;
		global $locationSingleton;
		$searchLocation = $locationSingleton->getSearchLocation();
		$categoryName = isset($_REQUEST['categoryName']) ? $_REQUEST['categoryName'] : '';
		$addAsSubCategoryOf = isset($_REQUEST['addAsSubCategoryOf']) && !empty($_REQUEST['addAsSubCategoryOf']) ? $_REQUEST['addAsSubCategoryOf'] : null;
			// value of zero means nothing was selected.


		//Get the text id for the category
		$textId = str_replace(' ', '_', strtolower(trim($categoryName)));
		$textId = preg_replace('/[^\w\d_]/', '', $textId);
		if (strlen($textId) == 0){
			return array(
				'success' => false,
				'message' => 'Please enter a category name'
			);
		}
		if ($searchLocation){
			$textId = $searchLocation->code . '_' . $textId;
		}elseif ($library){
			$textId = $library->subdomain . '_' . $textId;
		}

		//Check to see if we have an existing browse category
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		$browseCategory->textId = $textId;
		if ($browseCategory->find(true)){
			return array(
				'success' => false,
				'message' => "Sorry the title of the category was not unique.  Please enter a new name."
			);
		}else{
			if (isset($_REQUEST['searchId']) && strlen($_REQUEST['searchId']) > 0){
				$searchId = $_REQUEST['searchId'];

				/** @var SearchObject_Solr|SearchObject_Base $searchObj */
				$searchObj = SearchObjectFactory::initSearchObject();
				$searchObj->init();
				$searchObj = $searchObj->restoreSavedSearch($searchId, false, true);

				if (!$browseCategory->updateFromSearch($searchObj)){
					return array(
							'success' => false,
							'message' => "Sorry, this search is too complex to create a category from."
					);
				}
			}else{
				$listId = $_REQUEST['listId'];
				$browseCategory->sourceListId = $listId;
			}

			$browseCategory->label = $categoryName;
			$browseCategory->userId = UserAccount::getActiveUserId();
			$browseCategory->sharing = 'everyone';
			$browseCategory->catalogScoping = 'unscoped';
			$browseCategory->description = '';


			//setup and add the category
			if (!$browseCategory->insert()){
				return array(
					'success' => false,
					'message' => "There was an error saving the category.  Please contact Marmot."
				);
			}elseif ($addAsSubCategoryOf) {
				$id = $browseCategory->id; // get from above insertion operation
				$subCategory = new SubBrowseCategories();
				$subCategory->browseCategoryId = $addAsSubCategoryOf;
				$subCategory->subCategoryId = $id;
				if (!$subCategory->insert()){
					return array(
						'success' => false,
						'message' => "There was an error saving the category as a sub-category.  Please contact Marmot."
					);
				}

			}

			//Now add to the library/location
			if ($library && !$addAsSubCategoryOf){ // Only add main browse categories to the library carousel
				require_once ROOT_DIR . '/sys/Browse/LibraryBrowseCategory.php';
				$libraryBrowseCategory = new LibraryBrowseCategory();
				$libraryBrowseCategory->libraryId = $library->libraryId;
				$libraryBrowseCategory->browseCategoryTextId = $textId;
				$libraryBrowseCategory->insert();
			}

			return array(
				'success' => true
			);
		}
	}

/** @var  BrowseCategory $browseCategory */
	private $browseCategory;


	/**
	 * @param bool $reload  Reload object's BrowseCategory
	 * @return BrowseCategory
	 */
	private function getBrowseCategory($reload = false) {
		if ($this->browseCategory && !$reload) return $this->browseCategory;

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		$browseCategory->textId = $this->textId;
		$result = $browseCategory->find(true);
		if ($result) $this->browseCategory = $browseCategory;
		return $this->browseCategory;
	}

	private function getSuggestionsBrowseCategoryResults(){
		// Only Fetches one page of results
		$browseMode = $this->setBrowseMode();
		if (!isset($_REQUEST['reload'])) {
			/** @var Memcache $memCache */
			global $memCache, $solrScope;
			$activeUserId = UserAccount::getActiveUserId();
			$key = 'browse_category_' . $this->textId . '_' . $activeUserId . '_' . $solrScope . '_' . $browseMode;
			$browseCategoryInfo = $memCache->get($key);
			if ($browseCategoryInfo != false){
				return $browseCategoryInfo;
			}
		}

		global $interface;
		$interface->assign('browseCategoryId', $this->textId);
		$result['success'] = true;
		$result['textId'] = $this->textId;
		$result['label'] = translate('Recommended for you');
		$result['searchUrl'] = '/MyAccount/SuggestedTitles';

		require_once ROOT_DIR . '/services/MyResearch/lib/Suggestions.php';
		$suggestions = Suggestions::getSuggestions(-1, self::ITEMS_PER_PAGE);
		$records = array();
		foreach ($suggestions as $suggestedItemId => $suggestionData) {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			if (array_key_exists('recordDriver', $suggestionData['titleInfo'])) {
				$groupedWork = $suggestionData['titleInfo']['recordDriver'];
			}else {
				$groupedWork = new GroupedWorkDriver($suggestionData['titleInfo']);
			}
			if ($groupedWork->isValid) {
				if (method_exists($groupedWork, 'getBrowseResult')) {
					$records[] = $interface->fetch($groupedWork->getBrowseResult());
				} else {
					$records[] = 'Browse Result not available';
				}
			}
		}
		if (count($records) == 0){
			$records[] = $interface->fetch('Browse/noResults.tpl');
		}

		$result['records']    = implode('',$records);
		$result['numRecords'] = count($records);

		global $memCache, $configArray, $solrScope;
		$activeUserId = UserAccount::getActiveUserId();
		$key = 'browse_category_' . $this->textId . '_' . $activeUserId . '_' . $solrScope . '_' . $browseMode;
		$memCache->add($key, $result, 0, $configArray['Caching']['browse_category_info']);

		return $result;
	}

	private function getBrowseCategoryResults($pageToLoad = 1){
		if ($this->textId == 'system_recommended_for_you') {
			return $this->getSuggestionsBrowseCategoryResults();
		} else {
			$browseMode = $this->setBrowseMode();
			if ($pageToLoad == 1 && !isset($_REQUEST['reload'])) {
				// only first page is cached
				/** @var Memcache $memCache */
				global $memCache, $solrScope;
				$key                = 'browse_category_' . $this->textId . '_' . $solrScope . '_' . $browseMode;
				$browseCategoryInfo = $memCache->get($key);
				if ($browseCategoryInfo != false) {
					return $browseCategoryInfo;
				}
			}

			$result         = array('success' => false);
			$browseCategory = $this->getBrowseCategory();
			if ($browseCategory) {
				global $interface;
				$interface->assign('browseCategoryId', $this->textId);
				$result['success'] = true;
				$result['textId']  = $browseCategory->textId;
				$result['label']   = $browseCategory->label;
				//$result['description'] = $browseCategory->description; // the description is not used anywhere on front end. plb 1-2-2015

				// User List Browse Category //
				if ($browseCategory->sourceListId != null && $browseCategory->sourceListId > 0) {
					require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
					$sourceList     = new UserList();
					$sourceList->id = $browseCategory->sourceListId;
					if ($sourceList->find(true)) {
						$records = $sourceList->getBrowseRecords(($pageToLoad - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
					} else {
						$records = array();
					}
					$result['searchUrl'] = '/MyAccount/MyList/' . $browseCategory->sourceListId;

					// Search Browse Category //
				} else {
					$this->searchObject = SearchObjectFactory::initSearchObject();
					$defaultFilterInfo  = $browseCategory->defaultFilter;
					$defaultFilters     = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
					foreach ($defaultFilters as $filter) {
						$this->searchObject->addFilter(trim($filter));
					}
					//Set Sorting, this is actually slightly mangled from the category to Solr
					$this->searchObject->setSort($browseCategory->getSolrSort());
					if ($browseCategory->searchTerm != '') {
						$this->searchObject->setSearchTerm($browseCategory->searchTerm);
					}

					//Get titles for the list
					$this->searchObject->clearFacets();
					$this->searchObject->disableSpelling();
					$this->searchObject->disableLogging();
					$this->searchObject->setLimit(self::ITEMS_PER_PAGE);
					$this->searchObject->setPage($pageToLoad);
					$this->searchObject->processSearch();

					$records = $this->searchObject->getBrowseRecordHTML();

					// Do we need to initialize the ajax ratings?
					if ($this->browseMode == 'covers') {
						// Rating Settings
						global $library;
						$location                  = Location::getActiveLocation();
						$browseCategoryRatingsMode = null;
						if ($location) $browseCategoryRatingsMode = $location->browseCategoryRatingsMode; // Try Location Setting
						if (!$browseCategoryRatingsMode) $browseCategoryRatingsMode = $library->browseCategoryRatingsMode;  // Try Library Setting

						// when the Ajax rating is turned on, they have to be initialized with each load of the category.
						if ($browseCategoryRatingsMode == 'stars') $records[] = '<script type="text/javascript">VuFind.Ratings.initializeRaters()</script>';
					}

					$result['searchUrl'] = $this->searchObject->renderSearchUrl();

					//TODO: Check if last page

					// Shutdown the search object
					$this->searchObject->close();
				}
				if (count($records) == 0) {
					$records[] = $interface->fetch('Browse/noResults.tpl');
				}

				$result['records']    = implode('', $records);
				$result['numRecords'] = count($records);
			}

			// Store first page of browse category in the MemCache
			if ($pageToLoad == 1) {
				global $memCache, $configArray, $solrScope;
				$key = 'browse_category_' . $this->textId . '_' . $solrScope . '_' . $browseMode;
				$memCache->add($key, $result, 0, $configArray['Caching']['browse_category_info']);
			}
			return $result;
		}
	}

	public $browseModes = // Valid Browse Modes
		array(
			'covers', // default Mode
			'grid'
		),
	$browseMode; // Selected Browse Mode

	function setBrowseMode() {
		// Set Browse Mode //
		if (isset($_REQUEST['browseMode']) && in_array($_REQUEST['browseMode'], $this->browseModes)) { // user is setting mode (will be in most calls)
			$browseMode = $_REQUEST['browseMode'];
		} elseif (!empty($this->browseMode)) { // mode is already set
			$browseMode = $this->browseMode;
		} else { // check library & location settings
			global $location;
			if (!empty($location->defaultBrowseMode)) { // check location setting
				$browseMode = $location->defaultBrowseMode;
			} else {
				global $library;
				if (!empty($library->defaultBrowseMode)) { // check location setting
					$browseMode = $library->defaultBrowseMode;
				} else $browseMode = $this->browseModes[0]; // default setting
			}
		}

		$this->browseMode = $browseMode;

		global $interface;
		$interface->assign('browseMode', $browseMode); // sets the template switch that is created in GroupedWork object

		return $browseMode;
	}

	private $textId;

	/**
	 * @param null $textId  Optional Id to set the object's textId to
	 * @return null         Return the object's textId value
	 */
	function setTextId($textId = null){
		if ($textId) {
			$this->textId = $textId;
		} elseif ($this->textId == null) { // set Id only once
			$this->textId = isset($_REQUEST['textId']) ? $_REQUEST['textId'] : null;
		}
		return $this->textId;
	}

	function getBrowseCategoryInfo($textId = null){
		$textId = $this->setTextId($textId);
		if ($textId == null){
			return array('success' => false);
		}
		$response['textId'] = $textId;

		// Get Any Subcategories for the subcategory menu
		$response['subcategories'] = $this->getSubCategories();

		// If this category has subcategories, get the results of a sub-category instead.
		if (!empty($this->subCategories)) {
			// passed URL variable, or first sub-category
			if (!empty($_REQUEST['subCategoryTextId'])) {
				$test = array_search($_REQUEST['subCategoryTextId'], $this->subCategories);
				$subCategoryTextId = $_REQUEST['subCategoryTextId'];
			} else {
				$subCategoryTextId = $this->subCategories[0]->textId;
			}
			$response['subCategoryTextId'] = $subCategoryTextId;

			// Set the main category label before we fetch the sub-categories main results
			$response['label']  = $this->browseCategory->label;

			// Reset Main Category with SubCategory to fetch main results
			$this->setTextId($subCategoryTextId);
			$this->getBrowseCategory(true); // load sub-category
		}

		// Get the Browse Results for the Selected Category
		$result = $this->getBrowseCategoryResults();

		// Update Stats
		$this->upBrowseCategoryCounter();

		return array_merge($result, $response);
	}

	/**
	 *  Updates the displayed Browse Category's Shown Stats. Use near the end of
	 *  your actions.
	 */
	private function upBrowseCategoryCounter(){
		if ($this->browseCategory){
			$this->browseCategory->numTimesShown += 1;
//			if ($this->subCategories){ // Avoid unneeded sql update calls of subBrowseCategories
//				unset ($this->browseCategory->subBrowseCategories);
//			}
		$this->browseCategory->update_stats_only();
		}
	}

	function getBrowseSubCategoryInfo(){
		$subCategoryTextId = isset($_REQUEST['subCategoryTextId']) ? $_REQUEST['subCategoryTextId'] : null;
		if ($subCategoryTextId == null){
			return array('success' => false);
		}

		// Get Main Category Info
		$this->setTextId();
		$this->getBrowseCategory();
		if ($this->browseCategory) {
			$result['textId'] = $this->browseCategory->textId;
			$result['label']  = $this->browseCategory->label;
		}

		// Reload with sub-category
		$this->setTextId($subCategoryTextId); // Override to fetch sub-category results
		$this->getBrowseCategory(true); // Fetch Selected Sub-Category
		$subCategoryResult = $this->getBrowseCategoryResults(); // Get the Browse Results for the Selected Sub Category

		if (isset($subCategoryResult['label'])) {
			$subCategoryResult['subCategoryLabel'] = $subCategoryResult['label'];
//			unset($subCategoryResult['label']);
		}
		if (isset($subCategoryResult['textId'])) {
			$subCategoryResult['subCategoryTextId'] = $subCategoryResult['textId'];
//			unset($subCategoryResult['textId']);
		}

		// Update Stats
		$this->upBrowseCategoryCounter();

		$result = (isset($result)) ? array_merge($subCategoryResult, $result) : $subCategoryResult;
		return $result;
	}

	function getMoreBrowseResults($textId = null, $pageToLoad = null) {
		$textId = $this->setTextId($textId);
		if ($textId == null){
			return array('success' => false);
		}

		// Get More Results requires a defined page to load
		if ($pageToLoad == null) {
			$pageToLoad = (int) $_REQUEST['pageToLoad'];
			if (!is_int($pageToLoad)) return array('success' => false);
		}
		$result = $this->getBrowseCategoryResults($pageToLoad);
		return $result;
	}

	/** @var  BrowseCategory $subCategories[]   Browse category info for each sub-category */
	private $subCategories;

	/**
	 * @return string
	 */
	function getSubCategories() {
		$this->setTextId();
		$this->getBrowseCategory();
		if ($this->browseCategory){
			$subCategories = array();
			/** @var SubBrowseCategories $subCategory */
			foreach ($this->browseCategory->getSubCategories() as $subCategory) {

				// Get Needed Info about sub-category
				/** @var BrowseCategory $temp */
				$temp = new BrowseCategory();
				$temp->id = $subCategory->subCategoryId;
				if ($temp->find(true)) {
					$this->subCategories[] = $temp;
					$subCategories[] = array('label' => $temp->label, 'textId' => $temp->textId);
				}else{
					global $logger;
					$logger->log("Did not find subcategory with id {$subCategory->subCategoryId}", PEAR_LOG_WARNING);
				}
			}
			if ($subCategories) {
				global $interface;
				$interface->assign('subCategories', $subCategories);
				$result = $interface->fetch('Search/browse-sub-category-menu.tpl');
				return $result;
			}
		}
	}

	/**
	 * Return a list of browse categories that are assigned to the home page for the current library.
	 *
	 * TODO: Support loading sub categories.
	 */
	private function getActiveBrowseCategories(){
		//Figure out which library or location we are looking at
		global $library;
		/** @var Location $locationSingleton */
		global $locationSingleton;
		global $configArray;
		//Check to see if we have an active location, will be null if we don't have a specific locatoin
		//based off of url, branch parameter, or IP address
		$activeLocation = $locationSingleton->getActiveLocation();

		//Get a list of browse categories for that library / location
		/** @var LibraryBrowseCategory[]|LocationBrowseCategory[] $browseCategories */
		if ($activeLocation == null){
			//We don't have an active location, look at the library
			$browseCategories = $library->browseCategories;
		}else{
			//We have a location get data for that
			$browseCategories = $activeLocation->browseCategories;
		}

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		//Format for return to the user, we want to return
		// - the text id of the category
		// - the display label
		// - Clickable link to load the category
		$formattedCategories = array();
		foreach ($browseCategories as $curCategory){
			$categoryInformation = new BrowseCategory();
			$categoryInformation->textId = $curCategory->browseCategoryTextId;

			if ($categoryInformation->find(true)){
				$formattedCategories[] = array(
						'text_id' => $curCategory->browseCategoryTextId,
						'display_label' => $categoryInformation->label,
						'link' => $configArray['Site']['url'] . '?browseCategory=' . $curCategory->browseCategoryTextId
				);
			}
		}
		return $formattedCategories;
	}
}