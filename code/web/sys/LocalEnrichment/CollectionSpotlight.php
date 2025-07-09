<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';

class CollectionSpotlight extends DataObject {
	public $__table = 'collection_spotlights';
	public $id;
	public $name;
	public $description;
	public $showTitleDescriptions;
	public /** @noinspection PhpUnused */
		$showTitle;
	public /** @noinspection PhpUnused */
		$showAuthor;
	public $onSelectCallback;
	public $customCss;
	public $listDisplayType;
	public $showMultipleTitles;
	public $style;
	public $autoRotate;
	public $libraryId;
	public /** @noinspection PhpUnused */
		$showRatings;
	public $coverSize;
	public /** @noinspection PhpUnused */
		$showViewMoreLink;
	public $viewMoreLinkMode;
	public /** @noinspection PhpUnused */
		$showSpotlightTitle;
	public /** @noinspection PhpUnused */
		$numTitlesToShow;

	private static array $styles = [
		'horizontal' => 'Horizontal',
		'horizontal-carousel' => 'Horizontal Carousel',
		'vertical' => 'Vertical',
		'single' => 'Single Title',
		'single-with-next' => 'Single Title with a Next Button',
		'text-list' => 'Text-Only List',
	];

	private static array $displayTypes = [
		'tabs' => 'Tabbed Display',
		'dropdown' => 'Drop Down List',
	];

	/** @var  CollectionSpotlightList[] */
	private ?array $spotlightLists = null;

	public function getNumericColumnNames(): array {
		return ['id'];
	}

	/** @noinspection PhpUnused */
	public function getStyle($styleName): string {
		return CollectionSpotlight::$styles[$styleName];
	}

	/** @noinspection PhpUnused */
	public function getDisplayType($typeName): string {
		return CollectionSpotlight::$displayTypes[$typeName];
	}

	static function getObjectStructure($context = ''): array {
		// Load Libraries for lookup values.
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
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the collection spotlight.',
				'storeDb' => true,
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Display On',
				'description' => 'On what library catalogs to display this spotlight.',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the collection spotlight.',
				'maxLength' => 50,
				'serverValidation' => 'validateName',
				'storeDb' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'rows' => 3,
				'cols' => 80,
				'label' => 'Description',
				'description' => 'A description for the spotlight (shown internally only).',
				'storeDb' => true,
				'hideInLists' => true,
			],
			'numTitlesToShow' => [
				'property' => 'numTitlesToShow',
				'type' => 'integer',
				'label' => 'Number of Items to Display',
				'description' => 'The number of items that should be shown in the spotlight.',
				'storeDb' => true,
				'default' => 25,
				'hideInLists' => true,
				'min' => 1,
				'max' => 100
			],
			'style' => [
				'property' => 'style',
				'type' => 'enum',
				'label' => 'Spotlight Style',
				'description' => 'The style to use when displaying the items in the spotlight.',
				'values' => CollectionSpotlight::$styles,
				'storeDb' => true,
				'default' => 'horizontal',
				'translateValues' => true,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
				'onchange' => 'return AspenDiscovery.Admin.updateCollectionSpotlightFields();',
			],
			'showTitle' => [
				'property' => 'showTitle',
				'type' => 'checkbox',
				'label' => 'Show Titles of Items',
				'description' => 'Whether the title should be shown for the items in the spotlight.',
				'storeDb' => true,
				'default' => true,
				'hideInLists' => true,
			],
			'showAuthor' => [
				'property' => 'showAuthor',
				'type' => 'checkbox',
				'label' => 'Show Authors of Items',
				'description' => 'Whether the author should be shown for the items in the spotlight.',
				'note' => 'If "Show Titles of Items" is enabled, the author\'s name will be appended to the title.',
				'storeDb' => true,
				'default' => false,
				'hideInLists' => true,
			],
			'showRatings' => [
				'property' => 'showRatings',
				'type' => 'checkbox',
				'label' => 'Show Ratings of Items',
				'description' => 'Whether the user ratings of each item in the spotlight should be shown.',
				'storeDb' => true,
				'default' => false,
				'hideInLists' => true,
			],
			'autoRotate' => [
				'property' => 'autoRotate',
				'type' => 'checkbox',
				'label' => 'Automatically Rotate',
				'description' => 'Whether the display should automatically rotate between items in the spotlight.',
				'storeDb' => true,
				'hideInLists' => true,
			],
			'coverSize' => [
				'property' => 'coverSize',
				'type' => 'enum',
				'label' => 'Item Cover Size',
				'description' => 'The cover size of each item in the spotlight.',
				'values' => [
					'small' => 'Small',
					'medium' => 'Medium',
				],
				'storeDb' => true,
				'default' => 'medium',
				'hideInLists' => true,
				'translateValues' => true,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
			],
			'customCss' => [
				'property' => 'customCss',
				'type' => 'url',
				'label' => 'Custom CSS File',
				'maxLength' => 500,
				'description' => 'The URL to an external css file to be included when rendering as an iFrame.',
				'storeDb' => true,
				'required' => false,
				'hideInLists' => true,
			],
			'listDisplayType' => [
				'property' => 'listDisplayType',
				'type' => 'enum',
				'values' => CollectionSpotlight::$displayTypes,
				'label' => 'Display Lists As',
				'description' => 'The method used to show the user the multiple lists associated with the display.',
				'storeDb' => true,
				'translateValues' => true,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
			],
			'showSpotlightTitle' => [
				'property' => 'showSpotlightTitle',
				'type' => 'checkbox',
				'label' => 'Show the Spotlight\'s Title Bar',
				'description' => 'Whether or not the spotlight\'s title bar is shown at the top.',
				'storeDb' => true,
				'hideInLists' => true,
				'default' => true,
			],
			'showViewMoreLink' => [
				'property' => 'showViewMoreLink',
				'type' => 'checkbox',
				'label' => 'Show the View More Link',
				'description' => 'Whether to show the &quot;View More&quot; hyperlinked text at the bottom right of the spotlight.',
				'storeDb' => true,
				'hideInLists' => true,
				'default' => false,
				'onchange' => 'return AspenDiscovery.Admin.updateCollectionSpotlightFields();',
			],
			'viewMoreLinkMode' => [
				'property' => 'viewMoreLinkMode',
				'type' => 'enum',
				'values' => [
					'list' => 'List',
					'covers' => 'Covers',
				],
				'label' => 'Display Mode for View More Link',
				'description' => 'How the full results are displayed when the &quot;View More&quot; hyperlink is clicked.',
				'storeDb' => true,
				'hideInLists' => true,
				'translateValues' => true,
				'isPublicFacing' => false,
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
				'note' => 'If a row is deleted, the associated Collection Spotlight List will be deleted.',
				'sortable' => true,
				'storeDb' => true,
				'serverValidation' => 'validateLists',
				'hideInLists' => false,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
		];
	}

	/** @noinspection PhpUnused */
	function validateName(): array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		$spotlight = new CollectionSpotlight();
		$spotlight->name = $this->name;
		if ($this->id) {
			$spotlight->whereAdd("id != " . $this->id);
		}
		$spotlight->libraryId = $this->libraryId;
		$spotlight->find();
		if ($spotlight->getNumResults() > 0) {
			$validationResults['errors'][] = "A collection spotlight with that name already exists. Please provide a unique name.";
		}
		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	public function __get($name) {
		if ($name == "lists") {
			if ($this->spotlightLists == null) {
				// Get the list of lists that are being displayed for the spotlight.
				$this->spotlightLists = [];
				$collectionSpotlightList = new CollectionSpotlightList();
				$collectionSpotlightList->collectionSpotlightId = $this->id;
				$collectionSpotlightList->orderBy('weight ASC');
				$collectionSpotlightList->find();
				while ($collectionSpotlightList->fetch()) {
					$this->spotlightLists[$collectionSpotlightList->id] = clone($collectionSpotlightList);
				}
			}
			return $this->spotlightLists;
		}
		return parent::__get($name);
	}

	public function getNumLists() {
		$collectionSpotlightList = new CollectionSpotlightList();
		$collectionSpotlightList->collectionSpotlightId = $this->id;
		return $collectionSpotlightList->count();
	}

	/** @noinspection PhpUnused */
	public function getListNames(): string {
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
			$this->spotlightLists = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/** @noinspection PhpUnused */
	public function getLibraryName(): string {
		if ($this->libraryId == -1) {
			return 'All libraries';
		} else {
			$library = new Library();
			$library->libraryId = $this->libraryId;
			$library->find(true);
			return $library->displayName;
		}
	}

	public function update($context = ''): bool {
		$ret = parent::update();
		if ($ret === false) {
			return false;
		}

		$this->saveLists();
		return true;
	}

	public function insert($context = ''): bool {
		$ret = parent::insert();
		if ($ret === false) {
			return false;
		}

		$this->saveLists();
		return true;
	}

	public function saveLists(): void {
		if ($this->spotlightLists != null) {
			foreach ($this->spotlightLists as $list) {
				if ($list->_deleteOnSave) {
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
			// Clear the lists so they are reloaded the next time.
			$this->spotlightLists = null;
		}
	}

	/** @noinspection PhpUnused */
	public function validateLists(): array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		$listNames = [];
		if ($this->spotlightLists != null) {
			foreach ($this->spotlightLists as $list) {
				if (!$list->_deleteOnSave) {
					// Check to make sure that all list names are unique.
					if (in_array($list->name, $listNames)) {
						$validationResults['errors'][] = "This name {$list->name} was used multiple times. Please make sure that each name is unique.";
					}
					$listNames[] = $list->name;
				}
			}
		}

		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	public function getAdditionalListActions(): array {
		$actions = parent::getAdditionalListActions();
		// Add View action (setting's view mode) and Preview action (opens spotlight preview).
		$actions[] = [
			'url' => '/Admin/CollectionSpotlights?objectAction=view&id=' . $this->id,
			'text' => 'View',
			'onclick' => '',
			'target' => '',
		];
		$actions[] = [
			'url' => '/API/SearchAPI?method=getCollectionSpotlight&id=' . $this->id,
			'text' => 'Preview',
			'onclick' => '',
			'target' => '_blank',
		];
		return $actions;
	}
}