<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Browse/BaseBrowsable.php';

class CollectionSpotlightList extends BaseBrowsable {
	public $__table = 'collection_spotlight_lists';
	public $id;
	public $collectionSpotlightId;
	public $name;
	public $displayFor;
	public $weight;

	static function getObjectStructure($context = ''): array {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$sourceLists = UserList::getSourceListsForBrowsingAndCarousels();

		require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
		$sourceCourseReserves = CourseReserve::getSourceListsForBrowsingAndCarousels();

		require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
		$spotlightObj = new CollectionSpotlight();
		$spotlightObj->orderBy('name');
		$spotlightObj->find();
		$spotlightOptions = [];
		while ($spotlightObj->fetch()) {
			$spotlightOptions[$spotlightObj->id] = $spotlightObj->name;
		}

		$spotlightSources = BaseBrowsable::getBrowseSources();

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the collection spotlight list.',
			],
			'collectionSpotlightId' => [
				'property' => 'collectionSpotlightId',
				'type' => 'enum',
				'values' => $spotlightOptions,
				'label' => 'Collection Spotlight',
				'description' => 'The spotlight with which this list is associated.',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the list to display in the tab.',
				'required' => true,
			],
			'displayFor' => [
				'property' => 'displayFor',
				'type' => 'enum',
				'values' => [
					'all' => 'Everyone',
					'loggedIn' => 'Only when a user is logged in',
					'notLoggedIn' => 'Only when no one is logged in',
				],
				'label' => 'Display For',
				'description' => 'For whom this list should be displayed.',
				'translateValues' => true,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
			],
			'source' => [
				'property' => 'source',
				'type' => 'enum',
				'values' => $spotlightSources,
				'label' => 'Source',
				'description' => 'The source of the list.',
				'required' => true,
				'onchange' => "return AspenDiscovery.Admin.updateBrowseSearchForSource();",
				'hideInLists' => true,
				'translateValues' => true,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
			],
			'searchTerm' => [
				'property' => 'searchTerm',
				'type' => 'text',
				'label' => 'Search Term',
				'description' => 'A default search term to apply to the category.',
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
				'description' => 'A public list from which to display titles.',
				'hideInLists' => true,
				'translateValues' => false,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
			],
			'sourceCourseReserveId' => [
				'property' => 'sourceCourseReserveId',
				'type' => 'enum',
				'values' => $sourceCourseReserves,
				'label' => 'Source Course Reserve',
				'description' => 'A course from which to to display titles.',
				'hideInLists' => true,
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
				],
				'description' => 'The default sort for the search if none is specified.',
				'default' => 'relevance',
				'hideInLists' => true,
				'translateValues' => true,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
			],
		];
	}

	public function insert($context = ''): bool|int {
		if ($this->source === null) {
			$this->source = '';
		}
		return parent::insert();
	}

	/** @noinspection PhpUnused */
	function getSourceListName(): string {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		if ($this->sourceListId != null && $this->sourceListId > 0) {
			$userList = new UserList();
			$userList->id = $this->sourceListId;
			if ($userList->find(true)) {
				return $userList->title;
			} else {
				return "Invalid List ({$this->sourceListId})";
			}

		} elseif ($this->sourceCourseReserveId != null && $this->sourceCourseReserveId > 0) {
			require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
			$userList = new CourseReserve();
			$userList->id = $this->sourceCourseReserveId;
			if ($userList->find(true)) {
				return $userList->getTitle();
			} else {
				return "Invalid Course Reserve ({$this->sourceCourseReserveId})";
			}

		} else {
			return "";
		}
	}

	/** @noinspection PhpUnused */
	function fullListLink(): string {
		global $configArray;
		if ($this->sourceListId != null && $this->sourceListId > 0) {
			return $configArray['Site']['url'] . '/MyAccount/MyList/' . $this->sourceListId;
		} elseif ($this->sourceCourseReserveId != null && $this->sourceCourseReserveId > 0) {
			return $configArray['Site']['url'] . '/CourseReserves/' . $this->sourceCourseReserveId;
		} else {
			$searchObject = $this->getSearchObject();
			if (!$searchObject) {
				return $configArray['Site']['url'];
			}
			$link = $configArray['Site']['url'] . $searchObject->renderSearchUrl();
			$spotlight = $this->getCollectionSpotlight();
			if ($spotlight->viewMoreLinkMode == 'covers') {
				$link .= '&view=covers';
			}
			return $link;
		}
	}

	function __toString() {
		return "{$this->name} ($this->source)";
	}

	/**
	 * @return SearchObject_BaseSearcher|bool
	 */
	public function getSearchObject(): SearchObject_BaseSearcher|bool {
		/** @var SearchObject_BaseSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject($this->source);
		if ($searchObject) {
			if (!empty($this->defaultFilter)) {
				$defaultFilterInfo = $this->defaultFilter;
				$defaultFilters = preg_split('/[\r\n,;]+/', $defaultFilterInfo);
				foreach ($defaultFilters as $filter) {
					$searchObject->addFilter(trim($filter));
				}
			}
			// Set Sorting, this is actually slightly mangled from the category to Solr.
			$searchObject->setSort($this->getSolrSort());
			if ($this->searchTerm != '') {
				$searchObject->setSearchTerm($this->searchTerm);
			}

			//Get titles for the list
			$searchObject->clearFacets();
			$searchObject->disableSpelling();
			$searchObject->disableLogging();
			$searchObject->setLimit($this->getCollectionSpotlight()->numTitlesToShow);
			$searchObject->setPage(1);
		}

		return $searchObject;
	}

	private ?CollectionSpotlight $_collectionSpotlight = null;

	function getCollectionSpotlight(): CollectionSpotlight {
		if ($this->_collectionSpotlight == null) {
			$this->_collectionSpotlight = new CollectionSpotlight();
			$this->_collectionSpotlight->id = $this->collectionSpotlightId;
			$this->_collectionSpotlight->find(true);
		}
		return $this->_collectionSpotlight;
	}

	function getEditLink(): string {
		return '/Admin/CollectionSpotlightLists?objectAction=edit&id=' . $this->id;
	}
}