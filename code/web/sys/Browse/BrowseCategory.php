<?php

require_once ROOT_DIR . '/sys/Browse/BaseBrowsable.php';
require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';

class BrowseCategory extends BaseBrowsable {
	public $__table = 'browse_category';
	public $id;
	public $textId;  //A textual id to make it easier to transfer browse categories between systems

	public $userId; //The user who created the browse category
	public $sharing; //Who to share with (Private, Location, Library, Everyone)
	public $libraryId;

	public $label; //A label for the browse category to be shown in the browse category listing
	public $description; //A description of the browse category

	public $startDate;
	public $endDate;

	public $numTimesShown;
	public $numTitlesClickedOn;
	public $numTimesDismissed;

	protected $_subBrowseCategories;

	function getNumericColumnNames(): array {
		return [
			'id',
			'sourceListId',
			'sourceCourseReserveId',
			'userId',
		];
	}

	function getUniquenessFields(): array {
		return ['textId'];
	}

	/**
	 * Note, may return invalid categories
	 *
	 * @return SubBrowseCategories[]
	 */
	public function getSubCategories() {
		global $module;
		if (!isset($this->_subBrowseCategories) && $this->id) {
			$this->_subBrowseCategories = [];
			if ($module != "Admin") {
				if ($this->textId == "system_saved_searches") {
					// fetch users saved searches
					$SearchEntry = new SearchEntry();
					$SearchEntry->user_id = UserAccount::getActiveUserId();
					$SearchEntry->saved = "1";
					$SearchEntry->orderBy('created desc');
					$SearchEntry->find();
					$count = 0;
					do {
						if ($SearchEntry->title && $SearchEntry->isValidForDisplay()) {
							$count++;
							$searchId = $SearchEntry->id;
							$this->_subBrowseCategories[$searchId] = clone($SearchEntry);
							$this->_subBrowseCategories[$searchId]->id = $this->textId . '_' . $SearchEntry->id;
							$this->_subBrowseCategories[$searchId]->label = $SearchEntry->title;
							$this->_subBrowseCategories[$searchId]->_source = "savedSearch";
						}

					} while ($SearchEntry->fetch() && $count < 5);
				} elseif ($this->textId == "system_user_lists") {
					// fetch users list
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$lists = new UserList();
					$lists->user_id = UserAccount::getActiveUserId();
					$lists->deleted = "0";
					$lists->orderBy('dateUpdated desc');
					$lists->find();
					$count = 0;
					do {
						if ($lists->isValidForDisplay()) {
							$count++;
							$id = $lists->id;
							$this->_subBrowseCategories[$id] = clone($lists);
							$this->_subBrowseCategories[$id]->id = $this->textId . '_' . $id;
							$this->_subBrowseCategories[$id]->label = $lists->title;
							$this->_subBrowseCategories[$id]->_source = "userList";
						}
					} while ($lists->fetch() && $count < 5);
				} else {
					$subCategory = new SubBrowseCategories();
					$subCategory->browseCategoryId = $this->id;
					$subCategory->orderBy('weight');
					$subCategory->find();
					while ($subCategory->fetch()) {
						if(!$subCategory->isDismissed()) {
							$this->_subBrowseCategories[$subCategory->id] = clone($subCategory);
							$this->_subBrowseCategories[$subCategory->id]->_source = "browseCategory";
						}
					}
				}
			} else {
				$subCategory = new SubBrowseCategories();
				$subCategory->browseCategoryId = $this->id;
				$subCategory->orderBy('weight');
				$subCategory->find();
				while ($subCategory->fetch()) {
					if(!$subCategory->isDismissed()) {
						$this->_subBrowseCategories[$subCategory->id] = clone($subCategory);
						$this->_subBrowseCategories[$subCategory->id]->_source = "browseCategory";
					}
				}
			}
		}
		return $this->_subBrowseCategories;
	}

	public function getNumSubCategories() {
		require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
		$subBrowseCategory = new SubBrowseCategories();
		$subBrowseCategory->browseCategoryId = $this->id;
		return $subBrowseCategory->count();
	}

	public function __get($name) {
		if ($name == 'subBrowseCategories') {
			$this->getSubCategories();
			return $this->_subBrowseCategories;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'subBrowseCategories') {
			$this->_subBrowseCategories = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSubBrowseCategories();
		}
		return $ret;
	}

	/**
	 * call this method when updating the browse categories views statistics, so that all the other functionality
	 * in update() is avoided (and isn't needed)
	 *
	 * @return int
	 */
	public function update_stats_only() {
		return parent::update();
	}

	/**
	 * Override the update functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		// Set userId for manually created browse categories (i.e., not from searching).
		if (empty($this->userId)) {
			$this->userId = UserAccount::getActiveUserId();
		}
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveSubBrowseCategories();
		}
		return $ret;
	}

	public function delete($useWhere = false) : int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->textId)) {
			//Remove from any libraries that use it.
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupEntry.php';
			$libraryBrowseCategory = new BrowseCategoryGroupEntry();
			$libraryBrowseCategory->browseCategoryId = $this->id;
			$libraryBrowseCategory->delete(true);

			//Delete from parent sub categories as needed
			require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';
			$subBrowseCategory = new SubBrowseCategories();
			$subBrowseCategory->subCategoryId = $this->id;
			$subBrowseCategory->delete(true);

			//Remove links to anything that is a subcategory of this
			$subBrowseCategory = new SubBrowseCategories();
			$subBrowseCategory->browseCategoryId = $this->id;
			$subBrowseCategory->delete(true);
		}

		return $ret;
	}


	public function saveSubBrowseCategories() {
		if (isset ($this->_subBrowseCategories) && is_array($this->_subBrowseCategories)) {
			/** @var SubBrowseCategories[] $subBrowseCategories */
			/** @var SubBrowseCategories $subCategory */
			foreach ($this->_subBrowseCategories as $subCategory) {
				if ($subCategory->_deleteOnSave == true) {
					$subCategory->delete();
				} else {
					if (isset($subCategory->id) && is_numeric($subCategory->id)) {
						$subCategory->update();
					} else {
						$subCategory->browseCategoryId = $this->id;
						$subCategory->insert();
					}
				}
			}
			unset($this->_subBrowseCategories);
		}
	}

	static function getObjectStructure($context = ''): array {
		// Get All User Lists
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$sourceLists = UserList::getSourceListsForBrowsingAndCarousels();

		require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
		$sourceCourseReserves = CourseReserve::getSourceListsForBrowsingAndCarousels();

		// Get Structure for Sub-categories
		$browseSubCategoryStructure = SubBrowseCategories::getObjectStructure($context);
		unset($browseSubCategoryStructure['weight']);
		unset($browseSubCategoryStructure['browseCategoryId']);
		$browseCategorySources = BaseBrowsable::getBrowseSources();

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Browse Categories'));
		$libraryList[-1] = 'No Library Selected';

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'label' => [
				'property' => 'label',
				'type' => 'text',
				'label' => 'Label',
				'description' => 'The label to show to the user',
				'maxLength' => 50,
				'required' => true,
			],
			'textId' => [
				'property' => 'textId',
				'type' => 'text',
				'label' => 'textId',
				'description' => 'A textual id to identify the category',
				'serverValidation' => 'validateTextId',
				'maxLength' => 50,
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'label',
				'label' => 'userId',
				'description' => 'The User Id who created this category',
				'default' => UserAccount::getActiveUserId(),
			],
			'sharing' => [
				'property' => 'sharing',
				'type' => 'enum',
				'values' => [
					'library' => 'Selected Library',
					'everyone' => 'Everyone',
				],
				'label' => 'Share With',
				'description' => 'Who the category should be shared with',
				'default' => 'library',
				'onchange' => 'return AspenDiscovery.Admin.updateBrowseCategoryFields();',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the location belongs to',
			],
			'description' => [
				'property' => 'description',
				'type' => 'html',
				'label' => 'Description',
				'description' => 'A description of the category.',
				'hideInLists' => true,
			],
			'startDate' => [
				'property' => 'startDate',
				'type' => 'timestamp',
				'label' => 'Start Date to Show',
				'description' => 'The first date the category should be shown, leave blank to always show',
				'unsetLabel' => 'No start date',
			],
			'endDate' => [
				'property' => 'endDate',
				'type' => 'timestamp',
				'label' => 'End Date to Show',
				'description' => 'The end date the category should be shown, leave blank to always show',
				'unsetLabel' => 'No end date',
			],

			// Define oneToMany interface for choosing and arranging sub-categories
			'subBrowseCategories' => [
				'property' => 'subBrowseCategories',
				'type' => 'oneToMany',
				'label' => 'Browse Sub-Categories',
				'description' => 'Browse Categories that will be displayed as sub-categories of this Browse Category',
				'keyThis' => 'id',
				'keyOther' => 'browseCategoryId',
				'subObjectType' => 'SubBrowseCategories',
				'structure' => $browseSubCategoryStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'source' => [
				'property' => 'source',
				'type' => 'enum',
				'values' => $browseCategorySources,
				'label' => 'Source',
				'description' => 'The source of the browse category.',
				'required' => true,
				'onchange' => "return AspenDiscovery.Admin.updateBrowseSearchForSource();",
			],
			'searchTerm' => [
				'property' => 'searchTerm',
				'type' => 'text',
				'label' => 'Search Term',
				'description' => 'A default search term to apply to the category',
				'default' => '',
				'hideInLists' => true,
				'maxLength' => 500,
			],
			'defaultFilter' => [
				'property' => 'defaultFilter',
				'type' => 'textarea',
				'label' => 'Default Filter(s)',
				'description' => 'Filters to apply to the search by default.',
				'hideInLists' => true,
				'rows' => 3,
				'cols' => 80,
			],
			'sourceListId' => [
				'property' => 'sourceListId',
				'type' => 'enum',
				'values' => $sourceLists,
				'label' => 'Source List',
				'description' => 'A public list to display titles from',
			],
			'sourceCourseReserveId' => [
				'property' => 'sourceCourseReserveId',
				'type' => 'enum',
				'values' => $sourceCourseReserves,
				'label' => 'Source Course Reserve',
				'description' => 'A course to display titles from',
			],
			'defaultSort' => [
				'property' => 'defaultSort',
				'type' => 'enum',
				'label' => 'Default Sort',
				'values' => [
					'relevance' => 'Best Match',
					'popularity' => 'Popularity',
					'newest_to_oldest' => 'Date Added',
					'author' => 'Author',
					'title' => 'Title',
					'user_rating' => 'Rating',
					'publication_year_desc' => 'Publication Year Desc',
					'publication_year_asc' => 'Publication Year Asc',
					'holds' => 'Number of Holds',
				],
				'description' => 'The default sort for the search if none is specified',
				'default' => 'relevance',
				'hideInLists' => true,
			],
			'numTimesShown' => [
				'property' => 'numTimesShown',
				'type' => 'label',
				'label' => 'Times Shown',
				'description' => 'The number of times this category has been shown to users',
			],
			'numTitlesClickedOn' => [
				'property' => 'numTitlesClickedOn',
				'type' => 'label',
				'label' => 'Titles Clicked',
				'description' => 'The number of times users have clicked on titles within this category',
			],
			'numTimesDismissed' => [
				'property' => 'numTimesDismissed',
				'type' => 'label',
				'label' => 'Dismissed',
				'description' => 'The number of times users have dismissed this category',
			],
		];
	}

	function getEditLink($context): string {
		return '/Admin/BrowseCategories?objectAction=edit&id=' . $this->id;
	}

	/** @noinspection PhpUnused */
	function validateTextId() {
		//Setup validation return array
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		if (!$this->textId || strlen($this->textId) == 0) {
			$this->textId = $this->label . ' ' . $this->sharing;
			if ($this->sharing == 'private') {
				$this->textId .= '_' . $this->userId;
			} elseif ($this->sharing == 'location') {
				$location = Location::getUserHomeLocation();
				$this->textId .= '_' . $location->code;
			} elseif ($this->sharing == 'library') {
				$this->textId .= '_' . Library::getPatronHomeLibrary()->subdomain;
			}
		}

		//First convert the text id to all lower case
		$this->textId = strtolower($this->textId);

		//Next convert any non word characters to an underscore
		$this->textId = preg_replace('/\W/', '_', $this->textId);

		//Make sure the length is less than 50 characters
		if (strlen($this->textId) > 50) {
			$this->textId = substr($this->textId, 0, 50);
		}

		return $validationResults;
	}

	public function isValidForDisplay($appUser = null, $checkDismiss = true) {
		$curTime = time();
		if ($this->startDate != 0 && $this->startDate > $curTime) {
			return false;
		}
		if ($this->endDate != 0 && $this->endDate < $curTime) {
			return false;
		}
		if (!empty($appUser)) {
			$user = $appUser;
		} else {
			if (UserAccount::isLoggedIn()) {
				$user = UserAccount::getActiveUserObj();
			} else {
				$user = null;
			}
		}
		if ($this->textId == 'system_user_lists' || $this->textId == 'system_saved_searches' || $this->textId == 'system_recommended_for_you') {
			if (UserAccount::isLoggedIn() || !empty($appUser)) {
				if ($this->textId == 'system_saved_searches' && $user->hasSavedSearches()) {
					if ($checkDismiss) {
						if ($this->isDismissed($user)) {
							return false;
						}
					}
					return true;
				}
				if ($this->textId == 'system_user_lists' && $user->hasLists()) {
					if ($checkDismiss) {
						if ($this->isDismissed($user)) {
							return false;
						}
						if($this->allUserListsDismissed()) {
							return false;
						}
					}
					return true;
				}
				if ($this->textId == 'system_recommended_for_you' && $user->hasRatings()) {
					if ($checkDismiss) {
						if ($this->isDismissed($user)) {
							return false;
						}
					}
					return true;
				}

			}
			return false;
		}

		if ($checkDismiss) {
			if (!empty($user)) {
				if ($this->isDismissed($user)) {
					return false;
				}

				if($this->allSubCategoriesDismissed($user)) {
					return false;
				}
			}
		}
		return true;
	}

	public function isValidForDisplayInApp($user, $checkDismiss = false): bool {
		$curTime = time();
		if ($this->startDate != 0 && $this->startDate > $curTime) {
			return false;
		}
		if ($this->endDate != 0 && $this->endDate < $curTime) {
			return false;
		}

		if($checkDismiss && ($user && !($user instanceof AspenError))) {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissal = new BrowseCategoryDismissal();
			$browseCategoryDismissal->browseCategoryId = $this->textId;
			$browseCategoryDismissal->userId = $user->id;
			if ($browseCategoryDismissal->find(true)) {
				return false;
			}
		}

		if ($this->textId == 'system_user_lists' || $this->textId == 'system_saved_searches' || $this->textId == 'system_recommended_for_you') {
			if(!$user || ($user instanceof AspenError)) {
				return false;
			}
		}

		return true;
	}

	function isDismissed($user) {
		if (!empty($user)) {
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissal = new BrowseCategoryDismissal();
			$browseCategoryDismissal->browseCategoryId = $this->textId;
			$browseCategoryDismissal->userId = $user->id;
			if ($browseCategoryDismissal->find(true)) {
				return true;
			}
		}
		return false;
	}

	function allSubCategoriesDismissed($user) {
		$count = 0;
		if (!empty($user)) {
			$subCategories = $this->getSubCategories();
			foreach($subCategories as $subCategory) {
				$subBrowseCategory = new BrowseCategory();
				$subBrowseCategory->id = $subCategory->subCategoryId;
				if($subBrowseCategory->find(true)) {
					if($subBrowseCategory->isDismissed($user)) {
						$count++;
					}
				}
			}

			if($this->getNumSubCategories() > 0) {
				if ($count == $this->getNumSubCategories()) {
					return true;
				}
			}
		}

		return false;
	}

	function allUserListsDismissed() {
		$count = 0;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$allUserLists = $user->getLists();
			foreach($allUserLists as $userList) {
				if($userList->isDismissed()) {
					$count++;
				}
			}

			if($count > 0) {
				if($count == $user->getNumLists()) {
					return true;
				}
			}
		}

		return false;
	}

	public function canActiveUserEdit() : bool {
		if ($this->sharing == 'everyone') {
			return UserAccount::userHasPermission('Administer All Browse Categories') || ($this->userId == UserAccount::getActiveUserId());
		}
		if (UserAccount::userHasPermission('Administer Selected Browse Category Groups')) {
			//only allow editing the ones the user created
			return $this->userId == UserAccount::getActiveUserId();
		} else {
			//Don't need to limit for the library since the user will need Administer Library Browse Categories to even view them.
			return true;
		}
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset ($return['libraryId']);
		unset ($return['userId']);

		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//library
		$allLibraries = Library::getLibraryListAsObjects(false);
		if (array_key_exists($this->libraryId, $allLibraries)) {
			$library = $allLibraries[$this->libraryId];
			$links['library'] = empty($library->subdomain) ? $library->ilsCode : $library->subdomain;
		}
		//user
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->ils_barcode;
		}
		//sub browse categories
		$subCategories = $this->getSubCategories();
		if (count($subCategories) > 0) {
			$links['subCategories'] = [];
			foreach ($subCategories as $subCategory) {
				$subCategoryArray = $subCategory->toArray();
				$subCategoryArray['links'] = $subCategory->getLinksForJSON();
				$links['subCategories'][] = $subCategoryArray;
			}
		}

		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting');

		if (isset($jsonData['library'])) {
			$allLibraries = Library::getLibraryListAsObjects(false);
			$subdomain = $jsonData['library'];
			if (array_key_exists($subdomain, $mappings['libraries'])) {
				$subdomain = $mappings['libraries'][$subdomain];
			}
			foreach ($allLibraries as $tmpLibrary) {
				if ($tmpLibrary->subdomain == $subdomain || $tmpLibrary->ilsCode == $subdomain) {
					$this->libraryId = $tmpLibrary->libraryId;
					break;
				}
			}
		}
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->userId = $user->id;
			}
		}
	}

	public function loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		if (isset($jsonData['subCategories'])) {
			$subCategories = [];
			foreach ($jsonData['subCategories'] as $subCategory) {
				$subCategoryObj = new SubBrowseCategories();
				$subCategoryObj->browseCategoryId = $this->id;
				$subCategoryObj->loadFromJSON($subCategory, $mappings, $overrideExisting);
				$subCategories[$subCategoryObj->id] = $subCategoryObj;
			}
			$this->_subBrowseCategories = $subCategories;
			$result = true;
		}
		return $result;
	}

}