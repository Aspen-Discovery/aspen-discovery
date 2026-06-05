<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Series/SeriesMember.php';

class Series extends DataObject {
	public $__table = 'series';
	//ID of the series in the database
	public $id;
	//Permanent ID for cross site compatibility
	public $seriesPermanentId;
	//Version
	public $version;
	public $displayName;
	/** @noinspection PhpUnused */
	public $groupedWorkSeriesTitle;
	public $author;
	public $seriesLanguage;


	public $description;
	public $cover;
	public $audience;
	public $sortMethod;
	/** @noinspection PhpUnused */
	public $isIndexed;
	public $dateUpdated;
	public $created;
	public $deleted;

	public $_seriesMembers; // grouped works and placeholders

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$seriesMemberStructure = SeriesMember::getObjectStructure($context);
		global $configArray;
		$coverPath = $configArray['Site']['coverPath'];

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'version' => [
				'property' => 'version',
				'type' => 'label',
				'label' => 'Version',
				'description' => 'The version of the series',
			],
			'seriesPermanentId' => [
				'property' => 'seriesPermanentId',
				'type' => 'label',
				'label' => 'Series Permanent Id',
				'description' => 'The unique, permanent id for the series',
			],
			'seriesLanguage' => [
				'property' => 'seriesLanguage',
				'type' => 'label',
				'label' => 'Series Language',
				'description' => 'The language of the series',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Series Title',
				'description' => 'The title of the series',
			],
			'author' => [
				'property' => 'author',
				'type' => 'text',
				'label' => 'Author',
				'description' => 'Up to three authors with titles in this series',
				'note' => $context == 'addNew' ? '' : "This field may be automatically updated during indexing"
			],
			'audience' => [
				'property' => 'audience',
				'type' => 'text',
				'label' => 'Audience',
				'description' => 'The target audience for the series',
			],
			'description' => [
				'property' => 'description',
				'type' => 'html',
				'label' => 'Description',
				'description' => 'Series description',
				'hideInLists' => true,
			],
			'cover' => [
				'property' => 'cover',
				'type' => 'image',
				'label' => 'Cover',
				'description' => 'Image to replace the automatically generated series cover',
				'maxWidth' => 280,
				'maxHeight' => 280,
				'path' => "$coverPath/original/series",
				'hideInLists' => true,
			],
			'sortMethod' => [
				'property' => 'sortMethod',
				'type' => 'enum',
				'values' => [
					1 => 'By Volume',
					2 => 'By Title',
					3 => 'By Publication Date',
					4 => 'By Publication Date Descending',
					5 => 'Custom'
				],
				'label' => 'Default Sort Method for Titles',
				'description' => 'How the titles within the series should be sorted by default',
				'note' => 'Save the series to see the updated list'
			],
			'dateUpdated' => [
				'property' => 'dateUpdated',
				'type' => 'timestamp',
				'label' => 'Date Updated',
				'readOnly' => true,
			],
			'isIndexed' => [
				'property' => 'isIndexed',
				'type' => 'checkbox',
				'label' => 'Include in search',
				'default' => true,
				'description' => 'Uncheck to exclude from series searches, facets, and display in records'
			],
			'seriesMembers' => [
				'property' => 'seriesMembers',
				'type' => 'oneToMany',
				'label' => 'Titles in Series',
				'description' => 'A list of all the titles in this series',
				'keyThis' => 'seriesId',
				'keyOther' => 'id',
				'subObjectType' => 'SeriesMember',
				'structure' => $seriesMemberStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'hideInLists' => true,
				'canAddNew' => true,
				'canEdit' => true,
				'canDelete' => true,
				'additionalOneToManyActions' => [
					'showExcluded' => [
						'text' => 'Show Excluded Series Titles',
						'url' => '/Series/AdministerSeries?id=$id&amp;objectAction=edit&amp;showExcluded=true',
					],
				],
			],
			'numTitlesInSeries' => [
				'property' => 'numTitlesInSeries',
				'type' => 'calculatedInteger',
				'label' => 'Number of Titles',
				'description' => 'The number of titles in the series (unscoped, does not include excluded titles)',
				'readOnly' => true,
				'canFilter' => true
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function updateStructureForEditingObject($structure) : array {
		if ($this->sortMethod != 5) {
			$structure['seriesMembers']['sortable'] = false;
		}
		return $structure;
	}


	public function update(string $context = '') : int|bool {

		if (!empty($this->_changedFields)) {
			$this->reloadCover();
			$this->__set('dateUpdated', time());
		}
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->reindexMembers();
			$this->saveSeriesMembers();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		if (empty($this->dateUpdated)) {
			$this->dateUpdated = time();
		}
		if (empty($this->created)) {
			$this->created = time();
		}
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveSeriesMembers();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		if (!$useWhere) {
			$this->deleted = 1;
			$this->dateUpdated = time();
			$ret = parent::update();

			if ($ret) {
				$member = new SeriesMember();
				$member->seriesId = $this->id;
				$member->find();
				while ($member->fetch()) {
					$member->delete();
				}
				return true;
			}
			return false;
		} else {
			return parent::delete($useWhere, $hardDelete);
		}
	}

	public function __set($name, $value) {
		if ($name == 'seriesMembers') {
			$this->setSeriesMembers($value);
		} else {
			parent::__set($name, $value);
		}
	}

	public function __get($name) {
		if ($name == 'seriesMembers') {
			if (!empty($_REQUEST['showExcluded'])) {
				return $this->getSeriesMembers();
			}
			return $this->getSeriesMembers(null,false);
		} else {
			return parent::__get($name);
		}
	}

	public function setSeriesMembers($value) : void {
		$this->_seriesMembers = $value;
	}

	public function saveSeriesMembers() : void {
		if (isset ($this->_seriesMembers) && is_array($this->_seriesMembers)) {
			$this->saveOneToManyOptions($this->_seriesMembers, 'seriesId');
			unset($this->_seriesMembers);
		}
	}

	public function reindexMembers() : void {
		$seriesMembers = $this->_seriesMembers;
		foreach ($seriesMembers as $seriesMember) {
			if (!empty($seriesMember->groupedWorkPermanentId)) {
				require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
				$groupedWork = new GroupedWork();
				$groupedWork->permanent_id = $seriesMember->groupedWorkPermanentId;
				if ($groupedWork->find(true)) {
					$groupedWork->forceReindex();
				}
			}
		}
	}

	function numScopedTitlesInSeries() : int {
		$allTitles = $this->getTitles();
		return count($allTitles['seriesMembers']);
	}

	private ?array $_seriesTitles = null;
	/**
	 * Returns all members of the series as a custom array, used when loading members for display to the Series Page
	 * Also returns a list of unique grouped work ids for the series
	 *
	 * @return array      of list entries
	 */
	function getTitles($sortName = "volume asc", $includePlaceholders = true) : array {
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		if ($this->_seriesTitles === null) {
			$originalSeriesMembers = $this->getSeriesMembers($sortName, false, $includePlaceholders);
			$seriesTitles = [];
			$idsBySource = [
				'GroupedWork' => []
			];
			$source = "GroupedWork";  // All series currently come from groupedWorks
			foreach ($originalSeriesMembers as $seriesMember) {
				if (!empty($seriesMember->groupedWorkPermanentId)) {
					$idsBySource[$source][$seriesMember->groupedWorkPermanentId] = $seriesMember->groupedWorkPermanentId;
				}
				$tmpListEntry = [
					'source' => $source,
					'sourceId' => $seriesMember->groupedWorkPermanentId,
					'title' => $seriesMember->displayName,
					'author' => $seriesMember->author,
					'description' => $seriesMember->description,
					'volume' => $seriesMember->volume,
					'pubDate' => $seriesMember->pubDate,
					'seriesMemberId' => $seriesMember->id,
					'weight' => $seriesMember->weight,
					'seriesMember' => clone($seriesMember),
				];

				$seriesTitles[] = $tmpListEntry;
			}

			//Filter to remove anything that is not part of this scope.
			/** @var SearchObject_GroupedWorkSearcher2|false $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject($source);
			if ($searchObject === false) {
				AspenError::raiseError("Unknown Series Member Source $source");
			}
			$allSeriesMemberIds = $idsBySource[$source];
			$scopedRecords = $searchObject->getScopedRecordIds($allSeriesMemberIds);

			//Remove anything that isn't in the scope from the series
			$missingWorks = array_diff_key($allSeriesMemberIds, $scopedRecords);

			$changeMade = true;
			while ($changeMade) {
				$changeMade = false;
				foreach ($seriesTitles as $key => $seriesMember) {
					if ($seriesMember['source'] == $source && in_array($seriesMember['sourceId'], $missingWorks)) {
						unset($seriesTitles[$key]);
						unset($idsBySource[$source][$key]);
						$changeMade = true;
						break;
					}
				}
			}
			$this->_seriesTitles = $seriesTitles;
		}

		//Sort the series members
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		$sortMethod = $this->getSortMethodIdByName($sortName);

		//Sort the titles based on the active sort method
		uasort($this->_seriesTitles, function (array $a, array $b) use ($sortMethod) {
			$seriesMemberA = $a['seriesMember'];
			$seriesMemberB = $b['seriesMember'];
			return $this->compareSeriesMembers($sortMethod, $seriesMemberA, $seriesMemberB);

		});

		return [
			'seriesMembers' => $this->_seriesTitles
		];
	}

	/**
	 * Return all members of the series as an array of Series Member objects.
	 * Used as back end for getting series data for the admin page as well as loading members for display to Series Page
	 *
	 * @return SeriesMember[]      array of series members
	 */
	function getSeriesMembers($sortName = null, $showExcluded = true, $includePlaceholders = true) : array {
		if (empty($this->id)) {
			return [];
		}

		if ($this->_seriesMembers === null) {
			$this->_seriesMembers = [];

			$seriesMember = new SeriesMember();
			$seriesMember->seriesId = $this->id;
			$seriesMember->deleted = 0;

			$this->_seriesMembers = $seriesMember->fetchAll(null, null, false, true);

			$seriesMember->__destruct();
			$seriesMember = null;
		}

		$filteredSeriesMembers = [];
		if ($showExcluded && $includePlaceholders) {
			$filteredSeriesMembers = $this->_seriesMembers;
		}else{
			foreach ($this->_seriesMembers as $seriesId => $seriesMember) {
				$okToInclude = true;
				if (!$showExcluded && $seriesMember->excluded) {
					$okToInclude = false;
				}
				if (!$includePlaceholders && $seriesMember->isPlaceholder) {
					$okToInclude = false;
				}
				if ($okToInclude) {
					$filteredSeriesMembers[$seriesId] = $seriesMember;
				}
			}
		}

		//Sort the series members
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		$sortMethod = $this->getSortMethodIdByName($sortName);

		//Sort the titles based on the active sort method
		uasort($filteredSeriesMembers, function (SeriesMember $a, SeriesMember $b) use ($sortMethod) {
			return $this->compareSeriesMembers($sortMethod, $a, $b);

		});

		return $filteredSeriesMembers;
	}

	/**
	 * @param int $start position of first list item to fetch (0 based)
	 * @param int $numItems Number of items to fetch for this result
	 * @param string $format The format of the records, valid values are html, recordDrivers
	 * @param string $sortName How the records should be sorted when pulled from the database
	 * @param boolean $includePlaceholders Default true, whether to include placeholder records
	 * @return array     Array of HTML to display to the user
	 */
	public function getSeriesRecords(int $start, int $numItems, string $format, string $sortName, bool $includePlaceholders = true) : array {
		//Get all entries for the list
		$seriesMemberInfo = $this->getTitles($sortName, $includePlaceholders);

		//Trim to the number of records we want to return
		if ($numItems > 0) {
			$filteredSeriesMembers = array_slice($seriesMemberInfo['seriesMembers'], $start, $numItems);
		} else {
			$filteredSeriesMembers = $seriesMemberInfo['seriesMembers'];
		}

		$filteredIdsBySource = [];
		foreach ($filteredSeriesMembers as $seriesMember) {
			$source = "GroupedWork";
			if (!array_key_exists($source, $filteredIdsBySource)) {
				$filteredIdsBySource[$source] = [];
			}
			$filteredIdsBySource[$source][] = $seriesMember['sourceId'] != "" ? $seriesMember['sourceId'] : "noId";
		}

		//Load the actual items from each source
		$listResults = [];
		foreach ($filteredIdsBySource as $sourceType => $sourceIds) {
			/** @var SearchObject_GroupedWorkSearcher2|false $searchObject */
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false) {
				AspenError::raiseError("Unknown Series Member Source $sourceType");
			}

			$records = $searchObject->getRecords($sourceIds);
			if ($format == 'html') {
				$listResults = $listResults + $this->getResultListHTML($records, $filteredSeriesMembers, $start);
			} elseif ($format == 'recordDrivers') {
				$listResults = $listResults + $this->getResultListRecordDrivers($records, $filteredSeriesMembers);
			} else {
				AspenError::raiseError("Unknown display format $format in getSeriesRecords");
			}
		}

		if ($format == 'html') {
			//Add in non-owned results for anything that is left
			global $interface;
			foreach ($filteredSeriesMembers as $listPosition => $seriesMemberInfo) {
				if (!array_key_exists($listPosition, $listResults)) {
					$interface->assign('recordIndex', $listPosition + 1);
					$interface->assign('resultIndex', $listPosition + $start + 1);
					$interface->assign('listEntrySource', "Series");
					$interface->assign('seriesMemberId',$seriesMemberInfo['seriesMemberId']);
					$interface->assign('placeholder', $seriesMemberInfo);
					$seriesRecordDriver = new SeriesRecordDriver($seriesMemberInfo['seriesMemberId']);
					$interface->assign('bookCoverUrl', $seriesRecordDriver->getBookcoverUrl('medium', false, true, $seriesMemberInfo['seriesMemberId']));
					$listResults[$listPosition] = $interface->fetch('Series/placeholderListEntry.tpl');
				}
			}
		}

		ksort($listResults);
		return $listResults;
	}

	/**
	 * Use the record driver to build an array of HTML displays from the search
	 * results suitable for use while displaying lists
	 *
	 * @access  public
	 * @param RecordInterface[] $records Records retrieved from the getRecords method of a SolrSearcher
	 * @param array $allListEntryIds optional list of IDs to re-order the records by (ie User List sorts)
	 * @param int $startRecord The first record being displayed
	 * @return array Array of HTML chunks for individual records.
	 */
	private function getResultListHTML(array $records, array $allListEntryIds, int $startRecord = 0) : array {
		global $interface;
		$html = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			/** @var ?GroupedWorkDriver $current */
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$recordDriver->setListEntryId($currentId['seriesMemberId']);
					$current = $recordDriver;
					break;
				}
			}
			$interface->assign('recordIndex', $listPosition + 1);
			$interface->assign('resultIndex', $listPosition + $startRecord + 1);


			if (!empty($current)) {
				//Get information from list entry
				$interface->assign('seriesMemberId', $current->getListEntryId());

				$interface->assign('recordDriver', $current);
				$html[$listPosition] = $interface->fetch($current->getSeriesEntry($this->id, $currentId));
			}
		}
		return $html;
	}

	private function getResultListRecordDrivers($records, $allListEntryIds) : array {
		$results = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			/**
			 * @var IndexRecordDriver $recordDriver
			 */
			foreach ($records as $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$current = $recordDriver;
					break;
				}
			}
			if (!empty($current)) {
				$results[$listPosition] = $current;
			}
		}
		return $results;
	}

	private function reloadCover() : void {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->setRecordType('series');
		$bookCoverInfo->setRecordId($this->id);
		if ($bookCoverInfo->find(true)) {
			$bookCoverInfo->setImageSource('upload');
			$bookCoverInfo->setThumbnailLoaded(0);
			$bookCoverInfo->setMediumLoaded(0);
			$bookCoverInfo->setLargeLoaded(0);
			$bookCoverInfo->update();
		}
	}


	function getDefaultSortMethodName() : string {
		return match ($this->sortMethod) {
			2 => 'title',
			3 => 'pubDate',
			4 => 'pubDate desc',
			5 => 'custom',
			default => 'volume',
		};
	}

	/**
	 * @param int $sortMethod
	 * @param SeriesMember $a
	 * @param SeriesMember $b
	 * @return int
	 */
	function compareSeriesMembers(int $sortMethod, SeriesMember $a, SeriesMember $b): int {
		if ($sortMethod == 1) {
			$volumeComparison = 0;
			if (!empty($a->volume) && !empty($b->volume)) {
				$volumeComparison = strnatcasecmp($a->volume, $b->volume);
			} else if (!empty($a->volume) && empty($b->volume)) {
				//Sort things with volumes before things without
				$volumeComparison = -1;
			} else if (empty($a->volume) && !empty($b->volume)) {
				//Sort things with volumes before things without
				$volumeComparison = 1;
			}
			if ($volumeComparison == 0) {
				return strnatcasecmp($a->displayName, $b->displayName);
			} else {
				return $volumeComparison;
			}
		} else if ($sortMethod == 2) {
			return strnatcasecmp($a->displayName, $b->displayName);
		} else if ($sortMethod == 3) {
			$pubDateComparison = strcasecmp($a->pubDate, $b->pubDate);
			if ($pubDateComparison == 0) {
				return strnatcasecmp($a->displayName, $b->displayName);
			} else {
				return $pubDateComparison;
			}
		} else if ($sortMethod == 4) {
			$pubDateComparison = strcasecmp($b->pubDate, $a->pubDate);
			if ($pubDateComparison == 0) {
				return strnatcasecmp($a->displayName, $b->displayName);
			} else {
				return $pubDateComparison;
			}
		} else {
			return $a->weight <=> $b->weight;
		}
	}

	/**
	 * @param mixed $sortName
	 * @return int
	 */
	public function getSortMethodIdByName(mixed $sortName): int {
		$sortMethod = $this->sortMethod;
		if ($sortName != null) {
			if ($sortName == 'volume' || $sortName == 'volume asc') {
				$sortMethod = 1;
			} elseif ($sortName == 'displayName' || $sortName == 'title') {
				$sortMethod = 2;
			} elseif ($sortName == 'pubDate') {
				$sortMethod = 3;
			} elseif ($sortName == 'pubDate desc') {
				$sortMethod = 4;
			} else {
				$sortMethod = 5;
			}
		}
		return $sortMethod;
	}
}