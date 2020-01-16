<?php

require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';

class BrowseCategory extends DataObject
{
	public $__table = 'browse_category';
	public $id;
	public $textId;  //A textual id to make it easier to transfer browse categories between systems

	public $userId; //The user who created the browse category
	public $sharing; //Who to share with (Private, Location, Library, Everyone)

	public $label; //A label for the browse category to be shown in the browse category listing
	public $description; //A description of the browse category

	public $searchTerm;
	public $defaultFilter;
	public $sourceListId;
	public $defaultSort;

	public $numTimesShown;
	public $numTitlesClickedOn;

	function getNumericColumnNames()
	{
		return ['id', 'sourceListId', 'userId'];
	}

	public function getSubCategories()
	{
		if (!isset($this->subBrowseCategories) && $this->id) {
			$this->subBrowseCategories = array();
			$subCategory = new SubBrowseCategories();
			$subCategory->browseCategoryId = $this->id;
			$subCategory->orderBy('weight');
			$subCategory->find();
			while ($subCategory->fetch()) {
				$this->subBrowseCategories[$subCategory->id] = clone($subCategory);
			}
		}
		return $this->subBrowseCategories;
	}

	public function __get($name)
	{
		if ($name == 'subBrowseCategories') {
			$this->getSubCategories();
			/** @noinspection PhpUndefinedFieldInspection */
			return $this->subBrowseCategories;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == 'subBrowseCategories') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->subBrowseCategories = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSubBrowseCategories();

			//delete any cached results for browse category
			$this->deleteCachedBrowseCategoryResults();

		}
		return $ret;
	}

	/**
	 * call this method when updating the browse categories views statistics, so that all the other functionality
	 * in update() is avoided (and isn't needed)
	 *
	 * @return int
	 */
	public function update_stats_only()
	{
		$ret = parent::update();
		return $ret;
	}

	/**
	 * Override the update functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveSubBrowseCategories();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
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

	public function deleteCachedBrowseCategoryResults()
	{
		// key structure
		// $key = 'browse_category_' . $this->textId . '_' . $solrScope . '_' . $browseMode;
		$librarySubDomains = Library::getAllSubdomains();
		$locationCodes = Location::getAllCodes();

		$solrScopes = array_merge($librarySubDomains, $locationCodes);

		if (!empty($solrScopes)) { // don't bother if we didn't get any solr scopes
			// Valid Browse Modes (taken from class Browse_AJAX)
			$browseModes = array('covers', 'grid');

			/* @var MemCache $memCache */
			global $memCache;

			$keyFormat = 'browse_category_' . $this->textId; // delete all stored items with beginning with this key format.
			foreach ($solrScopes as $solrScope) {
				foreach ($browseModes as $browseMode) {
					$key = $keyFormat . '_' . $solrScope . '_' . $browseMode;
					if ($memCache->get($key)) {
						$memCache->delete($key);
					}
				}
			}
		}
	}

	public function saveSubBrowseCategories()
	{
		if (isset ($this->subBrowseCategories) && is_array($this->subBrowseCategories)) {
			/** @var SubBrowseCategories[] $subBrowseCategories */
			/** @var SubBrowseCategories $subCategory */
			foreach ($this->subBrowseCategories as $subCategory) {
				if (isset($subCategory->deleteOnSave) && $subCategory->deleteOnSave == true) {
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
			unset($this->subBrowseCategories);
		}
	}

	static function getObjectStructure()
	{
		// Get All User Lists
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
		$userLists = new UserList();
		$userLists->public = 1;
		$userLists->orderBy('title asc');
		$userLists->find();
		$sourceLists = array();
		$sourceLists[-1] = 'Generate from search term and filters';
		while ($userLists->fetch()) {

			$numItems = $userLists->numValidListItems();
			if ($numItems > 0) {
				$sourceLists[$userLists->id] = "($userLists->id) $userLists->title - $numItems entries";
			}
		}

		// Get Structure for Sub-categories
		$browseSubCategoryStructure = SubBrowseCategories::getObjectStructure();
		unset($browseSubCategoryStructure['weight']);
		unset($browseSubCategoryStructure['browseCategoryId']);

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'label' => array('property' => 'label', 'type' => 'text', 'label' => 'Label', 'description' => 'The label to show to the user', 'maxLength' => 50, 'required' => true),
			'textId' => array('property' => 'textId', 'type' => 'text', 'label' => 'textId', 'description' => 'A textual id to identify the category', 'serverValidation' => 'validateTextId', 'maxLength' => 50),
			'userId' => array('property' => 'userId', 'type' => 'label', 'label' => 'userId', 'description' => 'The User Id who created this category', 'default' => UserAccount::getActiveUserId()),
//			'sharing' => array('property'=>'sharing', 'type'=>'enum', 'values' => array('private' => 'Just Me', 'location' => 'My Home Branch', 'library' => 'My Home Library', 'everyone' => 'Everyone'), 'label'=>'Share With', 'description'=>'Who the category should be shared with', 'default' =>'everyone'),
			'description' => array('property' => 'description', 'type' => 'html', 'label' => 'Description', 'description' => 'A description of the category.', 'hideInLists' => true),

			// Define oneToMany interface for choosing and arranging sub-categories
			'subBrowseCategories' => array(
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
			),

			'searchTerm' => array('property' => 'searchTerm', 'type' => 'text', 'label' => 'Search Term', 'description' => 'A default search term to apply to the category', 'default' => '', 'hideInLists' => true, 'maxLength' => 500),
			'defaultFilter' => array('property' => 'defaultFilter', 'type' => 'textarea', 'label' => 'Default Filter(s)', 'description' => 'Filters to apply to the search by default.', 'hideInLists' => true, 'rows' => 3, 'cols' => 80),
			'sourceListId' => array('property' => 'sourceListId', 'type' => 'enum', 'values' => $sourceLists, 'label' => 'Source List', 'description' => 'A public list to display titles from'),
			'defaultSort' => array('property' => 'defaultSort', 'type' => 'enum', 'label' => 'Default Sort', 'values' => array('relevance' => 'Best Match', 'popularity' => 'Popularity', 'newest_to_oldest' => 'Date Added', 'author' => 'Author', 'title' => 'Title', 'user_rating' => 'Rating'), 'description' => 'The default sort for the search if none is specified', 'default' => 'relevance', 'hideInLists' => true),
			'numTimesShown' => array('property' => 'numTimesShown', 'type' => 'label', 'label' => 'Times Shown', 'description' => 'The number of times this category has been shown to users'),
			'numTitlesClickedOn' => array('property' => 'numTitlesClickedOn', 'type' => 'label', 'label' => 'Titles Clicked', 'description' => 'The number of times users have clicked on titles within this category'),
		);

		return $structure;
	}

	function getEditLink(){
		return '/Admin/BrowseCategories?objectAction=edit&id=' . $this->id;
	}

	/** @noinspection PhpUnused */
	function validateTextId()
	{
		//Setup validation return array
		$validationResults = array(
			'validatedOk' => true,
			'errors' => array(),
		);

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

	public function getSolrSort()
	{
		if ($this->defaultSort == 'relevance') {
			return 'relevance';
		} elseif ($this->defaultSort == 'popularity') {
			return 'popularity desc';
		} elseif ($this->defaultSort == 'newest_to_oldest') {
			return 'days_since_added asc';
		} elseif ($this->defaultSort == 'author') {
			return 'author,title';
		} elseif ($this->defaultSort == 'title') {
			return 'title,author';
		} elseif ($this->defaultSort == 'user_rating') {
			return 'rating desc,title';
		} else {
			return 'relevance';
		}
	}

	/**
	 * @param SearchObject_SolrSearcher $searchObj
	 *
	 * @return boolean
	 */
	public function updateFromSearch($searchObj)
	{
		//Search terms
		$searchTerms = $searchObj->getSearchTerms();
		if (is_array($searchTerms)) {
			if (count($searchTerms) > 1) {
				return false;
			} else {
				if (!isset($searchTerms[0]['index'])) {
					$this->searchTerm = $searchObj->displayQuery();
				} else if ($searchTerms[0]['index'] == 'Keyword') {
					$this->searchTerm = $searchTerms[0]['lookfor'];
				} else {
					$this->searchTerm = $searchTerms[0]['index'] . ':' . $searchTerms[0]['lookfor'];
				}
			}
		} else {
			$this->searchTerm = $searchTerms;
		}

		//Default Filter
		$filters = $searchObj->getFilterList();
		$formattedFilters = '';
		foreach ($filters as $filter) {
			if (strlen($formattedFilters) > 0) {
				$formattedFilters .= "\r\n";
			}
			$formattedFilters .= $filter[0]['field'] . ':' . $filter[0]['value'];
		}
		$this->defaultFilter = $formattedFilters;

		//Default sort
		$solrSort = $searchObj->getSort();
		if ($solrSort == 'relevance') {
			$this->defaultSort = 'relevance';
		} elseif ($solrSort == 'popularity desc') {
			$this->defaultSort = 'popularity';
		} elseif ($solrSort == 'days_since_added asc' || $solrSort == 'year desc,title asc') {
			$this->defaultSort = 'newest_to_oldest';
		} elseif ($solrSort == 'days_since_added desc') {
			$this->defaultSort = 'oldest_to_newest';
		} elseif ($solrSort == 'author,title') {
			$this->defaultSort = 'author';
		} elseif ($solrSort == 'title,author') {
			$this->defaultSort = 'title';
		} elseif ($solrSort == 'rating desc,title') {
			$this->defaultSort = 'user_rating';
		} else {
			$this->defaultSort = 'relevance';
		}
		return true;

	}
}