<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';

class CollectionSpotlight extends DataObject {
	public $__table = 'collection_spotlights';    // table name
	public $id;                      //int(25)
	public $name;                    //varchar(255)
	public $description;                    //varchar(255)
	public $showTitleDescriptions;
	public /** @noinspection PhpUnused */
		$showTitle;
	public /** @noinspection PhpUnused */
		$showAuthor;
	public $onSelectCallback;
	public $customCss;
	public $listDisplayType;
	public $showMultipleTitles;
	public $style; //'vertical', 'horizontal', 'single', 'single-with-next', 'text-list', 'horizontal-carousel'
	public $autoRotate;
	public $libraryId;
	public /** @noinspection PhpUnused */
		$showRatings;
	public $coverSize; //'small', 'medium'
	public /** @noinspection PhpUnused */
		$showViewMoreLink;
	public $viewMoreLinkMode;
	public /** @noinspection PhpUnused */
		$showSpotlightTitle; // whether or not the title bar is shown
	public /** @noinspection PhpUnused */
		$numTitlesToShow;

	// Spotlight Styles and their labels
	private static $_styles = [
		'horizontal' => 'Horizontal',
		'horizontal-carousel' => 'Horizontal Carousel',
		'vertical' => 'Vertical',
		'single' => 'Single Title',
		'single-with-next' => 'Single Title with a Next Button',
		'text-list' => 'Text Only List',
	];

	// Spotlight Display Types and their labels
	private static $_displayTypes = [
		'tabs' => 'Tabbed Display',
		'dropdown' => 'Drop Down List',
	];

	/** @var  CollectionSpotlightList[] */
	private $_lists = null;

	public function getNumericColumnNames(): array {
		return ['id'];
	}

	public function getStyle($styleName) {
		return CollectionSpotlight::$_styles[$styleName];
	}

	/** @noinspection PhpUnused */
	public function getDisplayType($typeName) {
		return CollectionSpotlight::$_displayTypes[$typeName];
	}

	static function getObjectStructure($context = ''): array {
		//Load Libraries for lookup values
		$libraryList = [];
		if (UserAccount::userHasPermission('Administer All Collection Spotlights')) {
			$library = new Library();
			$library->orderBy('displayName');
			$library->find();
			$libraryList[-1] = 'All Libraries';
			while ($library->fetch()) {
				$libraryList[$library->libraryId] = $library->displayName;
			}
		} else {
			$homeLibrary = Library::getPatronHomeLibrary();
			$libraryList[$homeLibrary->libraryId] = $homeLibrary->displayName;
		}

		$spotlightListStructure = CollectionSpotlightList::getObjectStructure($context);
		unset($spotlightListStructure['searchTerm']);
		unset($spotlightListStructure['defaultFilter']);
		unset($spotlightListStructure['sourceListId']);
		unset($spotlightListStructure['sourceCourseReserveId']);
		unset($spotlightListStructure['defaultSort']);
		return [
			'id' => [
				'property' => 'id',
				'type' => 'hidden',
				'label' => 'Id',
				'description' => 'The unique id of the collection spotlight.',
				'storeDb' => true,
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'A link to the library which the location belongs to',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the collection spotlight.',
				'maxLength' => 255,
				'size' => 100,
				'serverValidation' => 'validateName',
				'storeDb' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'rows' => 3,
				'cols' => 80,
				'label' => 'Description',
				'description' => 'A description for the spotlight (shown internally only)',
				'storeDb' => true,
				'hideInLists' => true,
			],
			'numTitlesToShow' => [
				'property' => 'numTitlesToShow',
				'type' => 'integer',
				'label' => 'The number of titles that should be shown',
				'storeDb' => true,
				'default' => 25,
				'hideInLists' => true,
			],
			'showTitle' => [
				'property' => 'showTitle',
				'type' => 'checkbox',
				'label' => 'Should the title for the currently selected item be shown?',
				'storeDb' => true,
				'default' => true,
				'hideInLists' => true,
			],
			'showAuthor' => [
				'property' => 'showAuthor',
				'type' => 'checkbox',
				'label' => 'Should the author (catalog items) /format (archive items) for the currently selected item be shown?',
				'storeDb' => true,
				'default' => false,
				'hideInLists' => true,
			],
			'showRatings' => [
				'property' => 'showRatings',
				'type' => 'checkbox',
				'label' => 'Should ratings be shown under each cover?',
				'storeDb' => true,
				'default' => false,
				'hideInLists' => true,
			],
			'style' => [
				'property' => 'style',
				'type' => 'enum',
				'label' => 'The style to use when displaying the featured titles',
				'values' => CollectionSpotlight::$_styles,
				'storeDb' => true,
				'default' => 'horizontal',
				'hideInLists' => true,
				'translateValues' => true,
				'isAdminFacing' => true,
			],
			'autoRotate' => [
				'property' => 'autoRotate',
				'type' => 'checkbox',
				'label' => 'Should the display automatically rotate between titles?',
				'storeDb' => true,
				'hideInLists' => true,
			],
			'coverSize' => [
				'property' => 'coverSize',
				'type' => 'enum',
				'label' => 'The cover size to use when showing the display',
				'values' => [
					'small' => 'Small',
					'medium' => 'Medium',
				],
				'storeDb' => true,
				'default' => 'medium',
				'hideInLists' => true,
				'translateValues' => true,
				'isAdminFacing' => true,
			],
			'customCss' => [
				'property' => 'customCss',
				'type' => 'url',
				'label' => 'Custom CSS File',
				'maxLength' => 255,
				'size' => 100,
				'description' => 'The URL to an external css file to be included when rendering as an iFrame.',
				'storeDb' => true,
				'required' => false,
				'hideInLists' => true,
			],
			'listDisplayType' => [
				'property' => 'listDisplayType',
				'type' => 'enum',
				'values' => CollectionSpotlight::$_displayTypes,
				'label' => 'Display lists as',
				'description' => 'The method used to show the user the multiple lists associated with the display.',
				'storeDb' => true,
				'hideInLists' => true,
				'translateValues' => true,
				'isAdminFacing' => true,
			],
			'showSpotlightTitle' => [
				'property' => 'showSpotlightTitle',
				'type' => 'checkbox',
				'label' => 'Show the display\'s title bar',
				'description' => 'Whether or not the display\'s title bar is shown. (Enabling the Show More Link will force the title bar to be shown as well.)',
				'storeDb' => true,
				'hideInLists' => true,
				'default' => true,
			],
			'showViewMoreLink' => [
				'property' => 'showViewMoreLink',
				'type' => 'checkbox',
				'label' => 'Show the View More link',
				'storeDb' => true,
				'hideInLists' => true,
				'default' => false,
			],
			'viewMoreLinkMode' => [
				'property' => 'viewMoreLinkMode',
				'type' => 'enum',
				'values' => [
					'list' => 'List',
					'covers' => 'Covers',
				],
				'label' => 'Display mode for view more link',
				'description' => 'The mode to show full results in when the View More link is clicked.',
				'storeDb' => true,
				'hideInLists' => true,
				'translateValues' => true,
				'isAdminFacing' => true,
			],
			'lists' => [
				'property' => 'lists',
				'type' => 'oneToMany',
				'keyThis' => 'id',
				'keyOther' => 'collectionSpotlightId',
				'subObjectType' => 'CollectionSpotlightList',
				'structure' => $spotlightListStructure,
				'label' => 'Lists',
				'description' => 'The lists to be displayed.',
				'sortable' => true,
				'storeDb' => true,
				'serverValidation' => 'validateLists',
				'hideInLists' => false,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
			],
		];
	}

	/** @noinspection PhpUnused */
	function validateName() {
		//Setup validation return array
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		//Check to see if the name is unique
		$spotlight = new CollectionSpotlight();
		$spotlight->name = $this->name;
		if ($this->id) {
			$spotlight->whereAdd("id != " . $this->id);
		}
		$spotlight->libraryId = $this->libraryId;
		$spotlight->find();
		if ($spotlight->getNumResults() > 0) {
			//The title is not unique
			$validationResults['errors'][] = "This collection spotlight has already been created.  Please select another name.";
		}
		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	public function __get($name) {
		if ($name == "lists") {
			if ($this->_lists == null) {
				//Get the list of lists that are being displayed for the spotlight
				$this->_lists = [];
				$collectionSpotlightList = new CollectionSpotlightList();
				$collectionSpotlightList->collectionSpotlightId = $this->id;
				$collectionSpotlightList->orderBy('weight ASC');
				$collectionSpotlightList->find();
				while ($collectionSpotlightList->fetch()) {
					$this->_lists[$collectionSpotlightList->id] = clone($collectionSpotlightList);
				}
			}
			return $this->_lists;
		}
		return null;
	}

	public function getNumLists() {
		$collectionSpotlightList = new CollectionSpotlightList();
		$collectionSpotlightList->collectionSpotlightId = $this->id;
		return $collectionSpotlightList->count();
	}

	public function getListNames() {
		$listNames = [];
		$collectionSpotlightList = new CollectionSpotlightList();
		$collectionSpotlightList->collectionSpotlightId = $this->id;
		$collectionSpotlightList->orderBy('weight ASC');
		$collectionSpotlightList->find();
		while ($collectionSpotlightList->fetch()) {
			$listNames[] = $collectionSpotlightList->name;
		}
		return implode(", ", $listNames);
	}

	public function __set($name, $value) {
		if ($name == "lists") {
			$this->_lists = $value;
		}
	}


	public function getLibraryName() {
		if ($this->libraryId == -1) {
			return 'All libraries';
		} else {
			$library = new Library();
			$library->libraryId = $this->libraryId;
			$library->find(true);
			return $library->displayName;
		}
	}

	/**
	 * Override the update functionality to save the associated lists
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret === FALSE) {
			return $ret;
		} else {
			$this->saveLists();
		}
		return true;
	}

	/**
	 * Override the update functionality to save the associated lists
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret === FALSE) {
			return $ret;
		} else {
			$this->saveLists();
		}
		return true;
	}

	public function saveLists() {
		if ($this->_lists != null) {
			foreach ($this->_lists as $list) {
				if ($list->_deleteOnSave == true) {
					$list->delete();
				} else {
					if (isset($list->id) && is_numeric($list->id)) {
						$list->update();
					} else {
						$list->collectionSpotlightId = $this->id;
						$list->insert();
					}
				}
			}
			//Clear the lists so they are reloaded the next time
			$this->_lists = null;
		}
	}

	public function validateLists() {
		//Setup validation return array
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		$listNames = [];
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listAPI = new ListAPI();
		$allListIds = $listAPI->getAllListIds();

		if ($this->_lists != null) {
			foreach ($this->_lists as $list) {
				if ($list->_deleteOnSave == true) {
					//Don't validate
				} else {
					//Check to make sure that all list names are unique
					if (in_array($list->name, $listNames)) {
						$validationResults['errors'][] = "This name {$list->name} was used multiple times.  Please make sure that each name is unique.";
					}
					$listNames[] = $list->name;

					//Check to make sure that each list source is valid
					$source = $list->source;
					//The source is valid if it is in the all lists array or if it is a search
					if (preg_match('/^(search:).*/', $source)) {
						//source is valid
					} elseif (in_array($source, $allListIds)) {
						//source is valid
					} else {
						//source is not valid
//					if (preg_match('/^(list:).*/', $source)) {
//						$validationResults['errors'][] = "This source {$list->source} is not valid.  Please make sure that the list id exists and is public.";
//					} else {
//						$validationResults['errors'][] = "This source {$list->source} is not valid.  Please enter a valid list source.";
//					}
					}
				}
			}
		}

		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}
}