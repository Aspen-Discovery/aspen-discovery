<?php

require_once ROOT_DIR . '/Action.php';

class Browse_AJAX extends Action {

	const ITEMS_PER_PAGE = 24;

	function launch()
	{
		header ('Content-type: application/json');
		$method = $_REQUEST['method'];
		$allowed_methods = array(
			'getAddBrowseCategoryForm',
			'getUpdateBrowseCategoryForm',
			'getNewBrowseCategoryForm',
			'updateBrowseCategory',
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

	/** @noinspection PhpUnused */
	function getAddBrowseCategoryForm(){
		global $interface;

		$interface->assign('searchId', strip_tags($_REQUEST['searchId']));

		return array(
			'title' => translate(['text'=>'Add as Browse Category to Home Page', 'isAdminFacing'=>'true']),
			'modalBody' => $interface->fetch('Browse/addBrowseCategory.tpl'),
			'modalButtons' => ""
		);
	}
	/** @noinspection PhpUnused */
	function getUpdateBrowseCategoryForm(){
		global $interface;

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

		$browseCategories = new BrowseCategory();
		$browseCategories->orderBy('label');
		if (!UserAccount::userHasPermission('Administer All Browse Categories')){
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			$libraryId = $library == null ? -1 : $library->libraryId;
			$browseCategories->whereAdd("sharing = 'everyone'");
			if ($libraryId == -1) {
				//For Aspen admin, show all categories
				$browseCategories->whereAdd("sharing = 'library'", 'OR');
			}else{
				$browseCategories->whereAdd("sharing = 'library' AND libraryId = " . $libraryId, 'OR');
			}
			$browseCategories->find();
			$browseCategoryList = [];
			while ($browseCategories->fetch()){
				$browseCategoryList[] = clone $browseCategories;
			}
		} else if(UserAccount::userHasPermission('Administer All Browse Categories')) {
			$browseCategories->find();
			$browseCategoryList = [];
			while ($browseCategories->fetch()) {
				$browseCategoryList[] = clone $browseCategories;
			}
		}

		$interface->assign('browseCategories', $browseCategoryList);

		$interface->assign('searchId', strip_tags($_REQUEST['searchId']));
		return array(
			'title' => translate(['text'=>'Update Existing Browse Category','isAdminFacing'=>true]),
			'modalBody' => $interface->fetch('Browse/updateBrowseCategoryForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#updateBrowseCategory\").submit();'>" . translate(['text'=>'Update Category','isAdminFacing'=>true]) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function getNewBrowseCategoryForm(){
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
		return array(
			'title' => translate(['text'=>'Add as New Browse Category', 'isAdminFacing'=>'true']),
			'modalBody' => $interface->fetch('Browse/newBrowseCategoryForm.tpl'),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#createBrowseCategory\").submit();'>" . translate(['text'=>'Create Category', 'isAdminFacing'=>'true']) . "</button>"
		);
	}

	/** @noinspection PhpUnused */
	function updateBrowseCategory(){
		global $library;
		$textId = isset($_REQUEST['categoryName']) ? $_REQUEST['categoryName'] : '';

		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategory = new BrowseCategory();
		$browseCategory->textId = $textId;
		if ($browseCategory->find(true)){
			if (isset($_REQUEST['searchId']) && strlen($_REQUEST['searchId']) > 0){
				$searchId = $_REQUEST['searchId'];

				/** @var SearchObject_GroupedWorkSearcher $searchObj */
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
				if (isset($_REQUEST['listId'])) {
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$listId = $_REQUEST['listId'];
					$userList = new UserList();
					$userList->id = $listId;
					$userList->deleted = "0";
					if ($userList->find(true)) {
						$browseCategory->sourceListId = $listId;
						$browseCategory->source = 'List';
					}
				}elseif (isset($_REQUEST['reserveId'])) {
					require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
					$listId = $_REQUEST['reserveId'];
					$userList = new CourseReserve();
					$userList->id = $listId;
					$userList->deleted = "0";
					if ($userList->find(true)) {
						$browseCategory->sourceCourseReserveId = $listId;
						$browseCategory->source = 'CourseReserve';
					}
				}

			}

			//update the category
			if (!$browseCategory->update()){
				return array(
					'success' => false,
					'message' => "There was an error updating the category."
				);
			}

			return array(
				'success' => true
			);
		}
	}

	/** @noinspection PhpUnused */
	function createBrowseCategory(){
		global $library;
		global $locationSingleton;
		$searchLocation = $locationSingleton->getSearchLocation();
		$patronHomeLibrary = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
		$libraryId = $patronHomeLibrary == null ? $library->libraryId : $patronHomeLibrary->libraryId;
		$categoryName = isset($_REQUEST['categoryName']) ? $_REQUEST['categoryName'] : '';
		// value of zero means nothing was selected.
		$addAsSubCategoryOf = isset($_REQUEST['addAsSubCategoryOf']) && !empty($_REQUEST['addAsSubCategoryOf']) ? $_REQUEST['addAsSubCategoryOf'] : null;

		//Get the text id for the category
		$textId = str_replace(' ', '_', strtolower(trim($categoryName)));
		$textId = preg_replace('/[^\w\d_]/', '', $textId);
		if (strlen($textId) == 0){
			return array(
				'success' => false,
				'message' => 'Please enter a category name'
			);
		}
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$textIdPrefixed = false;
		if (!empty($addAsSubCategoryOf)){
			$browseCategoryParent = new BrowseCategory();
			$browseCategoryParent->id = $addAsSubCategoryOf;
			if ($browseCategoryParent->find(true)) {
				$textId = $browseCategoryParent->textId . '_' . $textId;
				$textIdPrefixed = true;
			}
		}
		if (!$textIdPrefixed){
			if ($searchLocation) {
				$textId = $searchLocation->code . '_' . $textId;
			} elseif ($patronHomeLibrary) {
				$textId = $patronHomeLibrary->subdomain . '_' . $textId;
			}
		}

		//Check to see if we have an existing browse category
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

				/** @var SearchObject_GroupedWorkSearcher $searchObj */
				$searchObj = SearchObjectFactory::initSearchObject();
				$searchObj->init();
				$searchObj = $searchObj->restoreSavedSearch($searchId, false, true);

				if (!$browseCategory->updateFromSearch($searchObj)){
					return array(
							'success' => false,
							'message' => "Sorry, this search is too complex to create a category from."
					);
				}
			}else if (isset($_REQUEST['listId'])){
				require_once ROOT_DIR . '/sys/UserLists/UserList.php';
				$listId = $_REQUEST['listId'];
				$userList = new UserList();
				$userList->id = $listId;
				$userList->deleted = "0";
				if ($userList->find(true)) {
					$browseCategory->sourceListId = $listId;
					$browseCategory->source = 'List';
				}

			}else{
				require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
				$listId = $_REQUEST['reserveId'];
				$userList = new CourseReserve();
				$userList->id = $listId;
				$userList->deleted = "0";
				if ($userList->find(true)) {
					$browseCategory->sourceCourseReserveId = $listId;
					$browseCategory->source = 'CourseReserve';
				}

			}

			$browseCategory->label = $categoryName;
			$browseCategory->userId = UserAccount::getActiveUserId();
			if ($patronHomeLibrary == null) {
				$browseCategory->sharing = 'everyone';
			}else{
				$browseCategory->sharing = 'library';
			}
			$browseCategory->libraryId = $libraryId;
			$browseCategory->description = '';

			//setup and add the category
			if (!$browseCategory->insert()){
				return array(
					'success' => false,
					'message' => "There was an error saving the category. "
				);
			}elseif ($addAsSubCategoryOf) {
				$id = $browseCategory->id; // get from above insertion operation
				$subCategory = new SubBrowseCategories();
				$subCategory->browseCategoryId = $addAsSubCategoryOf;
				$subCategory->subCategoryId = $id;
				if (!$subCategory->insert()){
					return array(
						'success' => false,
						'message' => "There was an error saving the category as a sub-category."
					);
				}

			}

			if ($searchLocation != null){
				$activeBrowseCategoryGroup = $searchLocation->getBrowseCategoryGroup();
			}else{
				//Always add to the active location
				$activeBrowseCategoryGroup = $library->getBrowseCategoryGroup();
			}

			//Now add to the library/location
			if ($library && !$addAsSubCategoryOf){ // Only add main browse categories to the library carousel
				require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupEntry.php';
				$libraryBrowseCategory = new BrowseCategoryGroupEntry();
				$libraryBrowseCategory->browseCategoryGroupId = $activeBrowseCategoryGroup->id;
				$libraryBrowseCategory->browseCategoryId = $browseCategory->id;
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
		if(strpos($this->textId,"system_saved_searches_") !== false) {
			$label = explode('_', $this->textId);
			$id = $label[3];
			$searchEntry = new SearchEntry();
			$searchEntry->id = $id;
			$result = $searchEntry->find(true);
			if ($result) $this->browseCategory = $searchEntry;
		} elseif(strpos($this->textId,"system_user_lists_") !== false) {
			$label = explode('_', $this->textId);
			$id = $label[3];
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$userList = new UserList();
			$userList->id = $id;
			$result = $userList->find(true);
			if ($result) $this->browseCategory = $userList;
		} else {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = $this->textId;
			$result = $browseCategory->find(true);
			if ($result) $this->browseCategory = $browseCategory;
		}
		return $this->browseCategory;
	}

	private function getSuggestionsBrowseCategoryResults($pageToLoad = 1){
		if (!UserAccount::isLoggedIn()){
			return [
				'success' => false,
				'message' => 'Your session has timed out, please login again to view suggestions'
			];
		}
		//Do not cache browse category results in memory because they are generally too large and because they can be slow to delete
		$browseMode = $this->setBrowseMode();

		global $interface;
		$interface->assign('browseCategoryId', $this->textId);
		$result['success'] = true;
		$result['textId'] = $this->textId;
		$result['label'] = translate(['text' => 'Recommended for you', 'isPublicFacing'=>true]);
		$result['searchUrl'] = '/MyAccount/SuggestedTitles';

		require_once ROOT_DIR . '/sys/Suggestions.php';
		$suggestions = Suggestions::getSuggestions(-1, $pageToLoad,self::ITEMS_PER_PAGE);
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

		return $result;
	}

	private function getBrowseCategoryResults($pageToLoad = 1){
		$lastPage = false;
		if ($this->textId == 'system_recommended_for_you') {
			return $this->getSuggestionsBrowseCategoryResults($pageToLoad);
		} else {
			$browseMode = $this->setBrowseMode();
			//Do not cache browse category results in memory because they are generally too large and because they can be slow to delete
			$result = array('success' => false);
			$browseCategory = $this->getBrowseCategory();
			if ($browseCategory) {
				global $interface;
				$interface->assign('browseCategoryId', $this->textId);
				$result['success'] = true;
				if(isset($browseCategory->textId)) {
					$result['textId']  = $browseCategory->textId;
				} else {
					$result['textId']  = $this->textId;
				}

				if(isset($browseCategory->label)) {
					$result['label']  = $browseCategory->label;
				} else {
					$result['label']  = $browseCategory->title;
				}

				if(strpos($this->textId,"system_user_lists_") !== false){
					$browseCategory->source = "userList";
					$browseCategory->sourceListId = $browseCategory->id;
				}

				if(strpos($this->textId,"system_saved_searches_") !== false){
					$browseCategory->source = "SavedSearch";
					$browseCategory->sourceListId = $browseCategory->id;
				}

				// User List Browse Category //
				if ($browseCategory->source == 'List') {
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$sourceList     = new UserList();
					$sourceList->id = $browseCategory->sourceListId;
					if ($sourceList->find(true)) {
						$records = $sourceList->getBrowseRecords(($pageToLoad - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
						$preloadedRecords = $sourceList->getBrowseRecords(($pageToLoad + 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
						if($preloadedRecords == 0) {
							$lastPage = true;
						}
					} else {
						$records = array();
					}
					$result['searchUrl'] = '/MyAccount/MyList/' . $browseCategory->sourceListId;

					// Search Browse Category //
				} elseif ($browseCategory->source == 'userList') {
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$sourceList     = new UserList();
					$sourceListId = $browseCategory->sourceListId;
					$sourceList->id = $sourceListId;
					if ($sourceList->find(true)) {
						$records = $sourceList->getBrowseRecords(($pageToLoad - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
						$preloadedRecords = $sourceList->getBrowseRecords(($pageToLoad + 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
						if($preloadedRecords == 0) {
							$lastPage = true;
						}
					} else {
						$records = array();
					}
					$result['searchUrl'] = '/MyAccount/MyList/' . $sourceListId;
				}  elseif ($browseCategory->source == 'CourseReserve') {
					require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
					$sourceList     = new CourseReserve();
					$sourceList->id = $browseCategory->sourceCourseReserveId;
					if ($sourceList->find(true)) {
						$records = $sourceList->getBrowseRecords(($pageToLoad - 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
						$preloadedRecords = $sourceList->getBrowseRecords(($pageToLoad + 1) * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);
						if($preloadedRecords == 0) {
							$lastPage = true;
						}
					} else {
						$records = array();
					}
					$result['searchUrl'] = '/CourseReserves/' . $browseCategory->sourceCourseReserveId;

					// Search Browse Category //
				} else {
					if(strpos($this->textId,"system_saved_searches_") !== false) {
						$label = explode('_', $this->textId);
						$id = $label[3];
						require_once ROOT_DIR . '/services/Search/History.php';
						$savedSearch = History::getSavedSearchObject($id);
						SearchObjectFactory::initSearchObject();
						$size = strlen($savedSearch['search_object']);
						$minSO = unserialize($savedSearch['search_object']);
						$searchObject = SearchObjectFactory::deminify($minSO);
						$searchObject->getFilterList();
						$searchObject->displayQuery();

					} else {
						$searchObject = SearchObjectFactory::initSearchObject($browseCategory->source);
						$defaultFilterInfo  = $browseCategory->defaultFilter;
						$defaultFilters     = preg_split('/[\r\n]+/', $defaultFilterInfo);
						foreach ($defaultFilters as $filter) {
							$searchObject->addFilter(trim($filter));
						}
						//Set Sorting, this is actually slightly mangled from the category to Solr
						$searchObject->setSort($browseCategory->getSolrSort());
						if ($browseCategory->searchTerm != '') {
							$searchObject->setSearchTerm($browseCategory->searchTerm);
						}
					}

					//Get titles for the list
					$searchObject->clearFacets();
					$searchObject->disableSpelling();
					$searchObject->disableLogging();
					$searchObject->setLimit(self::ITEMS_PER_PAGE);
					$searchObject->setPage($pageToLoad);
					$searchObject->processSearch();

					$records = $searchObject->getBrowseRecordHTML();

					// Do we need to initialize the ajax ratings?
					if ($this->browseMode == 0) {
						// Rating Settings
						global $library;
						global $locationSingleton;
						$location = $locationSingleton->getActiveLocation();
						if ($location != null){
							$browseCategoryGroup = $location->getBrowseCategoryGroup();
						}else{
							$browseCategoryGroup = $library->getBrowseCategoryGroup();
						}
						$browseCategoryRatingsMode = $browseCategoryGroup->browseCategoryRatingsMode; // Try Location Setting

						// when the Ajax rating is turned on, they have to be initialized with each load of the category.
						if ($browseCategoryRatingsMode == 2) $records[] = '<script type="text/javascript">AspenDiscovery.Ratings.initializeRaters()</script>';
					}

					$result['searchUrl'] = $searchObject->renderSearchUrl();

					//TODO: Check if last page
					$searchObject->setPage($pageToLoad + 1);
					$preloadedRecords = $searchObject->getBrowseRecordHTML();

					// Shutdown the search object
					$searchObject->close();
				}
				if (count($records) == 0) {
					$records[] = $interface->fetch('Browse/noResults.tpl');
				}

				if(isset($preloadedRecords)) {
					if(count($preloadedRecords) == 0) {
						$lastPage = true;
					}
				}

				$result['records']    = implode('', $records);
				$result['numRecords'] = count($records);

			}
			$result['lastPage'] = $lastPage;
			return $result;
		}
	}

	public $browseModes = // Valid Browse Modes
		array(
			'covers', // default Mode
			'grid'
		);
	private $browseMode; // Selected Browse Mode

	function setBrowseMode() {
		// Set Browse Mode //
		if (isset($_REQUEST['browseMode']) && in_array($_REQUEST['browseMode'], $this->browseModes)) { // user is setting mode (will be in most calls)
			$browseMode = $_REQUEST['browseMode'];
			if ($browseMode == 'covers'){
				$browseMode = 0;
			}else{
				$browseMode = 1;
			}
		} elseif (!empty($this->browseMode)) { // mode is already set
			$browseMode = $this->browseMode;
		} else { // check library & location settings
			global $location;
			global $library;
			if ($location != null){
				$browseCategoryGroup = $location->getBrowseCategoryGroup();
			}else{
				$browseCategoryGroup = $library->getBrowseCategoryGroup();
			}
			$browseMode = $browseCategoryGroup->defaultBrowseMode;
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

	/** @noinspection PhpUnused */
	function getBrowseCategoryInfo($textId = null){
		$textId = $this->setTextId($textId);
		if ($textId == null){
			return array('success' => false);
		}
		$response['textId'] = $textId;

		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$response['patronId'] = $user->id;
		}

		$activeCategory = $this->getBrowseCategory(); // load sub-category
		$response['label']  = translate(['text'=>$this->browseCategory->label,'isPublicFacing'=>true]);

		// Get Any Subcategories for the subcategory menu
		$response['subcategories'] = $this->getSubCategories();

		// If this category has subcategories, get the results of a sub-category instead.
		if (!empty($response['subcategories'])) {
			$subCategories = $activeCategory->getSubCategories();
			$subBrowseCategoryLabel = "";
			// passed URL variable, or first sub-category
			if (!empty($_REQUEST['subCategoryTextId'])) {
				$subCategoryTextId = $_REQUEST['subCategoryTextId'];
			} else {
				//Get the first sub category
				foreach ($subCategories as $subCategoryId) {
					if($subCategoryId->_source == "userList") {
						require_once ROOT_DIR . '/sys/UserLists/UserList.php';
						$label = explode('_', $subCategoryId->id);
						$id = $label[3];
						$userList = new UserList();
						$userList->id = $id;
						if ($userList->find(true)) {
							if ($userList->numValidListItems() > 0) {
								$subCategoryTextId = $subCategoryId->id;
								$subBrowseCategoryLabel = $userList->title;
								break;
							}
						}
					} elseif($subCategoryId->_source == "savedSearch") {
						$subCategoryTextId = $subCategoryId->id;
						$subBrowseCategoryLabel = $subCategoryId->title;
						break;
					} else {
						//Get the first sub category that is valid for display
						$subCategory = new BrowseCategory();
						$subCategory->id = $subCategoryId->subCategoryId;
						if ($subCategory->find(true)) {
							if ($subCategory->isValidForDisplay()) {
								$subCategoryTextId = $subCategory->textId;
								$subBrowseCategoryLabel = $subCategory->label;
								break;
							}
						}
					}
				}
			}

			if (!empty($subCategoryTextId)) {
				$response['subCategoryTextId'] = $subCategoryTextId;

				// Set the main category label before we fetch the sub-categories main results
				$response['label']  = translate(['text'=>$this->browseCategory->label,'isPublicFacing'=>true, 'isAdminEnteredData'=>true]);
				$response['subCategoryLabel']  = translate(['text'=>$subBrowseCategoryLabel,'isPublicFacing'=>true, 'isAdminEnteredData'=>true]);

				// Reset Main Category with SubCategory to fetch main results
				$this->setTextId($subCategoryTextId);
				$this->getBrowseCategory(true); // load sub-category
			}
		}

		// Get the Browse Results for the Selected Category
		$result = $this->getBrowseCategoryResults();

		// Update Stats
		if((strpos($this->textId,"system_saved_searches_") !== false) || (strpos($this->textId,"system_user_lists_") !== false) ){
			$this->upParentBrowseCategoryCounter();
		} else {
			$this->upBrowseCategoryCounter();
		}

		return array_merge($result, $response);
	}

	/**
	 *  Updates the displayed Browse Category's Shown Stats. Use near the end of
	 *  your actions.
	 */
	private function upBrowseCategoryCounter(){
		if ($this->browseCategory){
			$this->browseCategory->numTimesShown += 1;
		    $this->browseCategory->update_stats_only();
		}
	}

	private function upParentBrowseCategoryCounter(){
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$parentBrowseCategory = new BrowseCategory();

		if(strpos($this->textId,"system_saved_searches_") !== false) {
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = "system_saved_searches";
			$result = $browseCategory->find(true);
			if ($result) $parentBrowseCategory = $browseCategory;
		}

		if(strpos($this->textId,"system_user_lists_") !== false) {
			$browseCategory = new BrowseCategory();
			$browseCategory->textId = "system_user_lists";
			$result = $browseCategory->find(true);
			if ($result) $parentBrowseCategory = $browseCategory;
		}

		$parentBrowseCategory->numTimesShown += 1;
		$parentBrowseCategory->update_stats_only();

	}

	/** @noinspection PhpUnused */
	function getBrowseSubCategoryInfo(){

		if(isset($_REQUEST['textId'])) {
			if(($_REQUEST['textId'] == "system_saved_searches") || ($_REQUEST['textId'] == "system_user_lists")) {
				$subCategoryTextId = $_REQUEST['textId'] . "_" . $_REQUEST['subCategoryTextId'];
			} else {
				$subCategoryTextId = $_REQUEST['subCategoryTextId'];
			}
		} else {
			$subCategoryTextId = null;
		}
		if ($subCategoryTextId == null){
			return array('success' => false);
		}

		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$result['patronId'] = $user->id;
		}

		// Get Main Category Info
		$this->setTextId();
		$this->getBrowseCategory();
		if ($this->browseCategory) {
			$result['textId'] = $this->browseCategory->textId;
			$result['label']  = translate(['text'=>$this->browseCategory->label,'isPublicFacing'=>true, 'isAdminEnteredData'=>true]);
			$result['subcategories'] = $this->getSubCategories();
		}

		// Reload with sub-category
		$this->setTextId($subCategoryTextId); // Override to fetch sub-category results
		$this->getBrowseCategory(true); // Fetch Selected Sub-Category
		$subCategoryResult = $this->getBrowseCategoryResults(); // Get the Browse Results for the Selected Sub Category

		if (isset($subCategoryResult['label'])) {
			$subCategoryResult['subCategoryLabel'] = translate(['text'=>$subCategoryResult['label'],'isPublicFacing'=>true, 'isAdminEnteredData'=>true]);
//			unset($subCategoryResult['label']);
		}
		if (isset($subCategoryResult['textId'])) {
			$subCategoryResult['subCategoryTextId'] = $subCategoryResult['textId'];
//			unset($subCategoryResult['textId']);
		}

		// Update Stats
		if((strpos($this->textId,"system_saved_searches_") !== false) || (strpos($this->textId,"system_user_lists_") !== false) ){
			$this->upParentBrowseCategoryCounter();
		} else {
			$this->upBrowseCategoryCounter();
		}

		$result = (isset($result)) ? array_merge($subCategoryResult, $result) : $subCategoryResult;
		return $result;
	}

	/** @noinspection PhpUnused */
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
		return $this->getBrowseCategoryResults($pageToLoad);
	}

	/**
	 * @return string
	 */
	function getSubCategories() {
		require_once ROOT_DIR . '/services/API/SearchAPI.php';
		$searchAPI = new SearchAPI();
		$result = $searchAPI->getSubCategories();
		if ($result['success']){
			$subCategories = $result['subCategories'];
			if (!empty($subCategories)) {
				global $interface;
				$interface->assign('subCategories', $subCategories);
				return $interface->fetch('Search/browse-sub-category-menu.tpl');
			}
		}
		return null;

	}

	/**
	 * Return a list of browse categories that are assigned to the home page for the current library.
	 *
	 * This is used in the Drupal module, but not in Aspen itself
	 */
	/** @noinspection PhpUnusedPrivateMethodInspection */
	private function getActiveBrowseCategories(){
		require_once ROOT_DIR . '/services/API/SearchAPI.php';
		$searchAPI = new SearchAPI();
		return $searchAPI->getActiveBrowseCategories();
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}