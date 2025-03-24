<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Series/SeriesMember.php';

class Series extends DataObject {
	public $__table = 'series';
	public $id;
	public $displayName;
	public $groupedWorkSeriesTitle;
	public $description;
	public $cover;
	public $audience;
	public $author;
	public $isIndexed;
	public $dateUpdated;
	public $created;

	public $_seriesMembers; // grouped works and placeholders

	public static function getObjectStructure($context = ''): array {

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
				'description' => 'Uncheck to exclude from series searches'
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
		];
		return $structure;
	}

	public function update($context = '') {
		$this->dateUpdated = time();
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSeriesMembers();
			if (in_array('cover', $this->_changedFields)) {
				$this->reloadCover();
			}
		}
		return $ret;
	}

	public function insert($context = '') {
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
				return $this->getSeriesMembers(true);
			}
			return $this->getSeriesMembers(false);
		} else {
			return parent::__get($name);
		}
	}

	public function setSeriesMembers($value) {
		$this->_seriesMembers = $value;
	}

	public function saveSeriesMembers() {
		if (isset ($this->_seriesMembers) && is_array($this->_seriesMembers)) {
			$this->saveOneToManyOptions($this->_seriesMembers, 'seriesId');
			unset($this->_seriesMembers);
		}
	}

	function numTitlesInSeries() {
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		$members = new SeriesMember();
		$members->seriesId = $this->id;
		$members->excluded = 0;
		return $members->count();
	}

	/**
	 * @return array      of list entries
	 */
	function getTitles($sortName = "volume asc", $includePlaceholders = true) {
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		$seriesMember = new SeriesMember();
		$seriesMember->seriesId = $this->id;
		$seriesMember->excluded = 0;
		if (!$includePlaceholders) {
			$seriesMember->isPlaceholder = 0;
		}
		$seriesMember->orderBy($sortName);

		$seriesMembers = [];
		$idsBySource = [];
		$seriesMember->find();
		while ($seriesMember->fetch()) {
			$source = "GroupedWork";  // All series currently come from groupedWorks
			if (!array_key_exists($source, $idsBySource)) {
				$idsBySource[$source] = [];
			}
			$idsBySource[$source][] = $seriesMember->groupedWorkPermanentId;
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

			$seriesMembers[] = $tmpListEntry;
		}
		if ($sortName == "volume asc" || $sortName == "volume desc") {
			// If manually sorted, use weight instead
			if (!in_array(0, array_column($seriesMembers, 'weight'))  && array_column($seriesMembers, 'weight') != array_column($seriesMembers, 'volume')) {
				array_multisort(array_column($seriesMembers, 'weight'), SORT_ASC, $seriesMembers);

			} else {
				// Otherwise
				array_multisort(array_column($seriesMembers, 'volume'), SORT_NATURAL, $seriesMembers);
			}
			if ($sortName == "volume desc") {
				$seriesMembers = array_reverse($seriesMembers);
			}
		}
		$seriesMember->__destruct();
		$seriesMember = null;
		return [
			'seriesMembers' => $seriesMembers,
			'idsBySource' => $idsBySource,
		];
	}

	/**
	 * @return array      of series members
	 */
	function getSeriesMembers($showExcluded = true) {
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		if (empty($this->id)) {
			return [];
		}
		$seriesMember = new SeriesMember();
		$seriesMember->seriesId = $this->id;
		if (!$showExcluded) {
			$seriesMember->excluded = 0;
		}
		$seriesMember->orderBy('weight');
		$this->_seriesMembers = [];
		$seriesMember->find();
		while ($seriesMember->fetch()) {
			$this->_seriesMembers[$seriesMember->id] = clone($seriesMember);
		}
		$seriesMember->__destruct();
		$seriesMember = null;
		return $this->_seriesMembers;
	}

	/**
	 * @param int $start position of first list item to fetch (0 based)
	 * @param int $numItems Number of items to fetch for this result
	 * @param string $format The format of the records, valid values are html, summary, recordDrivers, citation
	 * @param string $sortName How the records should be sorted when pulled from the database
	 * @param boolean $includePlaceholders Default true, whether to include placeholder records
	 * @return array     Array of HTML to display to the user
	 */
	public function getSeriesRecords($start, $numItems, $format, $sortName, $includePlaceholders = true) {
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
			$searchObject = SearchObjectFactory::initSearchObject($sourceType);
			if ($searchObject === false) {
				AspenError::raiseError("Unknown Series Member Source $sourceType");
			} else {
				$records = $searchObject->getRecords($sourceIds);
				if ($format == 'html') {
					$listResults = $listResults + $this->getResultListHTML($records, $filteredSeriesMembers, $start);
				} elseif ($format == 'summary') {
					$listResults = $listResults + $this->getResultListSummary($records, $filteredSeriesMembers);
				} elseif ($format == 'recordDrivers') {
					$listResults = $listResults + $this->getResultListRecordDrivers($records, $filteredSeriesMembers);
				} else {
					AspenError::raiseError("Unknown display format $format in getSeriesRecords");
				}
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
	private function getResultListHTML($records, $allListEntryIds, $startRecord = 0) {
		global $interface;
		$html = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			/** @var GroupedWorkDriver|null $current */
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			foreach ($records as $docIndex => $recordDriver) {
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
				$html[$listPosition] = $interface->fetch($current->getSeriesEntry($this->id));
			}
		}
		return $html;
	}

	private function getResultListSummary($records, $allListEntryIds) {
		$results = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			/** @var CourseReservesRecordDriver|null $current */
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			/**
			 * @var int $docIndex
			 * @var CourseReservesRecordDriver $recordDriver
			 */
			foreach ($records as $docIndex => $recordDriver) {
				if ($recordDriver->getId() == $currentId['sourceId']) {
					$current = $recordDriver;
					break;
				}
			}
			if (!empty($current)) {
				$results[$listPosition] = $current->getSummaryInformation();
			}
		}
		return $results;
	}

	private function getResultListRecordDrivers($records, $allListEntryIds) {
		$results = [];
		//Reorder the documents based on the list of id's
		foreach ($allListEntryIds as $listPosition => $currentId) {
			// use $IDList as the order guide for the html
			$current = null; // empty out in case we don't find the matching record
			reset($records);
			/**
			 * @var int $docIndex
			 * @var IndexRecordDriver $recordDriver
			 */
			foreach ($records as $docIndex => $recordDriver) {
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

	private function reloadCover() {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->recordType = 'series';
		$bookCoverInfo->recordId = $this->id;
		if ($bookCoverInfo->find(true)) {
			$bookCoverInfo->imageSource = 'upload';
			$bookCoverInfo->thumbnailLoaded = 0;
			$bookCoverInfo->mediumLoaded = 0;
			$bookCoverInfo->largeLoaded = 0;
			$bookCoverInfo->update();
		}
	}


}