<?php

require_once ROOT_DIR . '/sys/Browse/BaseBrowsable.php';
require_once ROOT_DIR . '/sys/Browse/SubBrowseCategories.php';

class BrowseCategory extends BaseBrowsable
{
	public $__table = 'browse_category';
	public $id;
	public $textId;  //A textual id to make it easier to transfer browse categories between systems

	public $userId; //The user who created the browse category
	public $sharing; //Who to share with (Private, Location, Library, Everyone)
	public $libraryId;

	public $label; //A label for the browse category to be shown in the browse category listing
	public $description; //A description of the browse category

	public $numTimesShown;
	public $numTitlesClickedOn;

	private $_subBrowseCategories;

	function getNumericColumnNames()
	{
		return ['id', 'sourceListId', 'userId'];
	}

	public function getSubCategories()
	{
		if (!isset($this->_subBrowseCategories) && $this->id) {
			$this->_subBrowseCategories = array();
			$subCategory = new SubBrowseCategories();
			$subCategory->browseCategoryId = $this->id;
			$subCategory->orderBy('weight');
			$subCategory->find();
			while ($subCategory->fetch()) {
				$this->_subBrowseCategories[$subCategory->id] = clone($subCategory);
			}
		}
		return $this->_subBrowseCategories;
	}

	public function __get($name)
	{
		if ($name == 'subBrowseCategories') {
			$this->getSubCategories();
			return $this->_subBrowseCategories;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == 'subBrowseCategories') {
			$this->_subBrowseCategories = $value;
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
		return parent::update();
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


	public function saveSubBrowseCategories()
	{
		if (isset ($this->_subBrowseCategories) && is_array($this->_subBrowseCategories)) {
			/** @var SubBrowseCategories[] $subBrowseCategories */
			/** @var SubBrowseCategories $subCategory */
			foreach ($this->_subBrowseCategories as $subCategory) {
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
			unset($this->_subBrowseCategories);
		}
	}

	static function getObjectStructure()
	{
		// Get All User Lists
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$sourceLists = UserList::getSourceListsForBrowsingAndCarousels();

		// Get Structure for Sub-categories
		$browseSubCategoryStructure = SubBrowseCategories::getObjectStructure();
		unset($browseSubCategoryStructure['weight']);
		unset($browseSubCategoryStructure['browseCategoryId']);
		$browseCategorySources = BaseBrowsable::getBrowseSources();

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Browse Categories'));
		$libraryList[-1] = 'No Library Selected';

		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'label' => array('property' => 'label', 'type' => 'text', 'label' => 'Label', 'description' => 'The label to show to the user', 'maxLength' => 50, 'required' => true),
			'textId' => array('property' => 'textId', 'type' => 'text', 'label' => 'textId', 'description' => 'A textual id to identify the category', 'serverValidation' => 'validateTextId', 'maxLength' => 50),
			'userId' => array('property' => 'userId', 'type' => 'label', 'label' => 'userId', 'description' => 'The User Id who created this category', 'default' => UserAccount::getActiveUserId()),
			'sharing' => array('property'=>'sharing', 'type'=>'enum', 'values' => array('library' => 'My Home Library', 'everyone' => 'Everyone'), 'label'=>'Share With', 'description'=>'Who the category should be shared with', 'default' =>'everyone'),
			'libraryId' => array('property' => 'libraryId', 'type' => 'enum', 'values' => $libraryList, 'label' => 'Library', 'description' => 'A link to the library which the location belongs to'),
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
			'source' => array(
				'property' => 'source',
				'type' => 'enum',
				'values' => $browseCategorySources,
				'label' => 'Source',
				'description' => 'The source of the browse category.',
				'required' => true,
				'onchange' => "return AspenDiscovery.Admin.updateBrowseSearchForSource();"
			),
			'searchTerm' => array('property' => 'searchTerm', 'type' => 'text', 'label' => 'Search Term', 'description' => 'A default search term to apply to the category', 'default' => '', 'hideInLists' => true, 'maxLength' => 500),
			'defaultFilter' => array('property' => 'defaultFilter', 'type' => 'textarea', 'label' => 'Default Filter(s)', 'description' => 'Filters to apply to the search by default.', 'hideInLists' => true, 'rows' => 3, 'cols' => 80),
			'sourceListId' => array('property' => 'sourceListId', 'type' => 'enum', 'values' => $sourceLists, 'label' => 'Source List', 'description' => 'A public list to display titles from'),
			'defaultSort' => array('property' => 'defaultSort', 'type' => 'enum', 'label' => 'Default Sort', 'values' => array('relevance' => 'Best Match', 'popularity' => 'Popularity', 'newest_to_oldest' => 'Date Added', 'author' => 'Author', 'title' => 'Title', 'user_rating' => 'Rating'), 'description' => 'The default sort for the search if none is specified', 'default' => 'relevance', 'hideInLists' => true),
			'numTimesShown' => array('property' => 'numTimesShown', 'type' => 'label', 'label' => 'Times Shown', 'description' => 'The number of times this category has been shown to users'),
			'numTitlesClickedOn' => array('property' => 'numTitlesClickedOn', 'type' => 'label', 'label' => 'Titles Clicked', 'description' => 'The number of times users have clicked on titles within this category'),
		);
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
}