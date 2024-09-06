<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Grouping/GroupedWorkFormatSort.php';

class GroupedWorkFormatSortingGroup extends DataObject {
	public $__table = 'grouped_work_format_sort_group';
	public $id;
	public $name;
	public $bookSortMethod;
	/** @noinspection PhpUnused */
	public $_sortedBookFormats;
	public $comicSortMethod;
	/** @noinspection PhpUnused */
	public $_sortedComicFormats;
	public $movieSortMethod;
	/** @noinspection PhpUnused */
	public $_sortedMovieFormats;
	public $musicSortMethod;
	/** @noinspection PhpUnused */
	public $_sortedMusicFormats;
	public $otherSortMethod;
	/** @noinspection PhpUnused */
	public $_sortedOtherFormats;

	public function getNumericColumnNames() : array {
		return [
			'bookSortMethod',
			'comicSortMethod',
			'movieSortMethod',
			'musicSortMethod',
			'otherSortMethod',
		];
	}
	static function getObjectStructure($context = ''): array {
		$formatSortStructure = GroupedWorkFormatSort::getObjectStructure($context);
		unset($formatSortStructure['weight']);
		unset($formatSortStructure['formatSortingGroupId']);
		unset($formatSortStructure['groupingCategory']);

		$objectStructure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The name of the settings',
				'size' => '40',
				'maxLength' => 255,
			],
			'booksSection' => [
				'property' => 'booksSection',
				'type' => 'section',
				'label' => 'Books',
				'properties' => [
					'bookSortMethod' => [
						'property' => 'bookSortMethod',
						'type' => 'enum',
						'values' => [
							'1' => 'Sort Alphabetically with Books first',
							'2' => 'Custom Sort'
						],
						'label' => 'Sorting Method for Books',
						'description' => 'Determines how formats are sorted for grouped works with a grouping category of book',
						'onchange' => "return AspenDiscovery.Admin.updateGroupedWorkSortFields('book');",
					],
					'sortedBookFormats' => [
						'property' => 'sortedBookFormats',
						'type' => 'oneToMany',
						'label' => 'Sorted Book Formats',
						'description' => 'A list of formats in the order they should be displayed',
						'keyThis' => 'id',
						'keyOther' => 'formatSortingGroupId',
						'subObjectType' => 'GroupedWorkFormatSort',
						'structure' => $formatSortStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canEdit' => false,
						'canAddNew' => false,
						'canDelete' => false,
					],
				]
			],

			'comicsSection' => [
				'property' => 'comicsSection',
				'type' => 'section',
				'label' => 'Comics',
				'properties' => [
					'comicSortMethod' => [
						'property' => 'comicSortMethod',
						'type' => 'enum',
						'values' => [
							'1' => 'Sort Alphabetically',
							'2' => 'Custom Sort'
						],
						'label' => 'Sorting Method for Comics',
						'description' => 'Determines how formats are sorted for grouped works with a grouping category of comic',
						'onchange' => "return AspenDiscovery.Admin.updateGroupedWorkSortFields('comic');",
					],
					'sortedComicFormats' => [
						'property' => 'sortedComicFormats',
						'type' => 'oneToMany',
						'label' => 'Sorted Comic Formats',
						'description' => 'A list of formats in the order they should be displayed',
						'keyThis' => 'id',
						'keyOther' => 'formatSortingGroupId',
						'subObjectType' => 'GroupedWorkFormatSort',
						'structure' => $formatSortStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canEdit' => false,
						'canAddNew' => false,
						'canDelete' => false,
					],
				],
			],
			'moviesSection' => [
				'property' => 'moviesSection',
				'type' => 'section',
				'label' => 'Movies',
				'properties' => [
					'movieSortMethod' => [
						'property' => 'movieSortMethod',
						'type' => 'enum',
						'values' => [
							'1' => 'Sort Alphabetically',
							'2' => 'Custom Sort'
						],
						'label' => 'Sorting Method for Movies',
						'description' => 'Determines how formats are sorted for grouped works with a grouping category of movie',
						'onchange' => "return AspenDiscovery.Admin.updateGroupedWorkSortFields('movie');",
					],
					'sortedMovieFormats' => [
						'property' => 'sortedMovieFormats',
						'type' => 'oneToMany',
						'label' => 'Sorted Movie Formats',
						'description' => 'A list of formats in the order they should be displayed',
						'keyThis' => 'id',
						'keyOther' => 'formatSortingGroupId',
						'subObjectType' => 'GroupedWorkFormatSort',
						'structure' => $formatSortStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canEdit' => false,
						'canAddNew' => false,
						'canDelete' => false,
					],
				],
			],
			'musicSection' => [
				'property' => 'musicSection',
				'type' => 'section',
				'label' => 'Music',
				'properties' => [
					'musicSortMethod' => [
						'property' => 'musicSortMethod',
						'type' => 'enum',
						'values' => [
							'1' => 'Sort Alphabetically',
							'2' => 'Custom Sort'
						],
						'label' => 'Sorting Method for Music',
						'description' => 'Determines how formats are sorted for grouped works with a grouping category of music',
						'onchange' => "return AspenDiscovery.Admin.updateGroupedWorkSortFields('music');",
					],
					'sortedMusicFormats' => [
						'property' => 'sortedMusicFormats',
						'type' => 'oneToMany',
						'label' => 'Sorted Music Formats',
						'description' => 'A list of formats in the order they should be displayed',
						'keyThis' => 'id',
						'keyOther' => 'formatSortingGroupId',
						'subObjectType' => 'GroupedWorkFormatSort',
						'structure' => $formatSortStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canEdit' => false,
						'canAddNew' => false,
						'canDelete' => false,
					],
				],
			],
			'otherSection' => [
				'property' => 'otherSection',
				'type' => 'section',
				'label' => 'Other',
				'properties' => [
					'otherSortMethod' => [
						'property' => 'otherSortMethod',
						'type' => 'enum',
						'values' => [
							'1' => 'Sort Alphabetically',
							'2' => 'Custom Sort'
						],
						'label' => 'Sorting Method for Other',
						'description' => 'Determines how formats are sorted for grouped works with a grouping category of other',
						'onchange' => "return AspenDiscovery.Admin.updateGroupedWorkSortFields('other');",
					],
					'sortedOtherFormats' => [
						'property' => 'sortedOtherFormats',
						'type' => 'oneToMany',
						'label' => 'Sorted Other Formats',
						'description' => 'A list of formats in the order they should be displayed',
						'keyThis' => 'id',
						'keyOther' => 'formatSortingGroupId',
						'subObjectType' => 'GroupedWorkFormatSort',
						'structure' => $formatSortStructure,
						'sortable' => true,
						'storeDb' => true,
						'allowEdit' => false,
						'canEdit' => false,
						'canAddNew' => false,
						'canDelete' => false,
					],
				],
			],
		];
		if ($context == 'addNew') {
			unset($objectStructure['booksSection']);
			unset($objectStructure['comicsSection']);
			unset($objectStructure['moviesSection']);
			unset($objectStructure['musicSection']);
			unset($objectStructure['otherSection']);
		}
		return $objectStructure;
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSortedFormats('book');
			$this->saveSortedFormats('comic');
			$this->saveSortedFormats('movie');
			$this->saveSortedFormats('music');
			$this->saveSortedFormats('other');
		}
		return $ret;
	}

	public function insert($context = '') {
		$this->bookSortMethod = 1;
		$this->comicSortMethod = 1;
		$this->movieSortMethod = 1;
		$this->musicSortMethod = 1;
		$this->otherSortMethod = 1;
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->loadDefaultFormats();
		}
		return $ret;
	}

	public function delete($useWhere = false): int {
		$ret = parent::delete($useWhere);
		/** @noinspection PhpStrictComparisonWithOperandsOfDifferentTypesInspection */
		if ($ret !== false) {
			$sortedFormat = new GroupedWorkFormatSort();
			$sortedFormat->formatSortingGroupId = $this->id;
			$sortedFormat->delete(true);
		}
		return $ret;
	}

	private function getArrayNameForGroupingCategory($groupingCategory) : string {
		$internalArrayName = null;
		switch ($groupingCategory) {
			case 'book':
				$internalArrayName = '_sortedBookFormats';
				break;
			case 'comic':
				$internalArrayName = '_sortedComicFormats';
				break;
			case 'movie':
				$internalArrayName = '_sortedMovieFormats';
				break;
			case 'music':
				$internalArrayName = '_sortedMusicFormats';
				break;
			case 'other':
				$internalArrayName = '_sortedOtherFormats';
				break;
		}
		return $internalArrayName;
	}

	public function saveSortedFormats($groupingCategory) : void {
		$internalArrayName = $this->getArrayNameForGroupingCategory($groupingCategory);
		if (!empty($internalArrayName) && isset ($this->$internalArrayName) && is_array($this->$internalArrayName)) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			foreach ($this->$internalArrayName as $id => $formatSort) {
				$formatSort->groupingCategory = $groupingCategory;
			}
			$this->saveOneToManyOptions($this->$internalArrayName, 'formatSortingGroupId');
			unset($this->$internalArrayName);
		}
	}

	public function __get($name) {
		if ($name == 'sortedBookFormats') {
			return $this->getSortedFormats('book');
		} elseif ($name == 'sortedComicFormats') {
			return $this->getSortedFormats('comic');
		} elseif ($name == 'sortedMovieFormats') {
			return $this->getSortedFormats('movie');
		} elseif ($name == 'sortedMusicFormats') {
			return $this->getSortedFormats('music');
		} elseif ($name == 'sortedOtherFormats') {
			return $this->getSortedFormats('other');
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'sortedBookFormats') {
			$this->setSortedFormats('book', $value);
		} elseif ($name == 'sortedComicFormats') {
			$this->setSortedFormats('comic', $value);
		} elseif ($name == 'sortedMovieFormats') {
			$this->setSortedFormats('movie', $value);
		} elseif ($name == 'sortedMusicFormats') {
			$this->setSortedFormats('music', $value);
		} elseif ($name == 'sortedOtherFormats') {
			$this->setSortedFormats('other', $value);
		} else {
			parent::__set($name, $value);
		}
	}

	/** @return GroupedWorkFormatSort[]|null */
	public function getSortedFormats($groupingCategory): ?array {
		$internalArrayName = $this->getArrayNameForGroupingCategory($groupingCategory);
		if (!empty($internalArrayName) && !isset($this->$internalArrayName) && $this->id) {
			$this->$internalArrayName = [];
			$sortedFormat = new GroupedWorkFormatSort();
			$sortedFormat->formatSortingGroupId = $this->id;
			$sortedFormat->groupingCategory = $groupingCategory;
			$sortedFormat->orderBy('weight');
			$sortedFormat->find();
			while ($sortedFormat->fetch()) {
				$this->$internalArrayName[$sortedFormat->id] = clone($sortedFormat);
			}
		}
		return $this->$internalArrayName;
	}

	public function loadDefaultFormats() : void {
		//Automatically generate based on the data in the database.
		global $aspen_db;
		$loadDefaultFormatsStmt = "select grouped_work.grouping_category, trim(indexed_format.format) as format, MIN(permanent_id), count(grouped_work.id) as numWorks FROM grouped_work_record_items inner join grouped_work_records on groupedWorkRecordId = grouped_work_records.id join grouped_work_variation on grouped_work_variation.id = grouped_work_record_items.groupedWorkVariationId join indexed_format on grouped_work_variation.formatId = indexed_format.id join grouped_work on grouped_work_variation.groupedWorkId = grouped_work.id group by grouping_category, lower(trim(format));";
		$results = $aspen_db->query($loadDefaultFormatsStmt, PDO::FETCH_ASSOC);

		$bookSort = $this->getSortedFormats('book');
		$comicSort = $this->getSortedFormats('comic');
		$movieSort = $this->getSortedFormats('movie');
		$musicSort = $this->getSortedFormats('music');
		$otherSort = $this->getSortedFormats('other');

		//Store the original formats, so we can remove anything that no longer exists at the end
		$originalBookSort = array_merge([], $bookSort);
		$originalComicSort = array_merge([], $comicSort);
		$originalMovieSort = array_merge([], $movieSort);
		$originalMusicSort = array_merge([], $musicSort);
		$originalOtherSort = array_merge([], $otherSort);

		foreach ($results as $result) {
			//Check to see if we already have this category
			if ($result['grouping_category'] == 'book') {
				$activeFormats = $bookSort;
				$originalFormats = $originalBookSort;
			}elseif ($result['grouping_category'] == 'comic') {
				$activeFormats = $comicSort;
				$originalFormats = $originalComicSort;
			}elseif ($result['grouping_category'] == 'movie') {
				$activeFormats = $movieSort;
				$originalFormats = $originalMovieSort;
			}elseif ($result['grouping_category'] == 'music') {
				$activeFormats = $musicSort;
				$originalFormats = $originalMusicSort;
			}elseif ($result['grouping_category'] == 'other') {
				$activeFormats = $otherSort;
				$originalFormats = $originalOtherSort;
			}else{
				continue;
			}
			$formatExists = false;
			foreach ($activeFormats as $activeFormat) {
				if ($activeFormat->format == trim($result['format'])) {
					$formatExists = true;
					break;
				}
			}
			if (!$formatExists) {
				$groupedWorkFormatSort = new GroupedWorkFormatSort();
				$groupedWorkFormatSort->formatSortingGroupId = $this->id;
				$groupedWorkFormatSort->groupingCategory = $result['grouping_category'];
				$groupedWorkFormatSort->format = trim($result['format']);
				$groupedWorkFormatSort->weight = count($activeFormats) + 1;
				$groupedWorkFormatSort->insert();
				$activeFormats[$groupedWorkFormatSort->id] = $groupedWorkFormatSort;
			}else{
				foreach ($originalFormats as $key => $originalFormat) {
					if ($originalFormat->format == trim($result['format'])) {
						if ($result['grouping_category'] == 'book') {
							unset($originalBookSort[$key]);
						}elseif ($result['grouping_category'] == 'comic') {
							unset($originalComicSort[$key]);
						}elseif ($result['grouping_category'] == 'movie') {
							unset($originalMovieSort[$key]);
						}elseif ($result['grouping_category'] == 'music') {
							unset($originalMusicSort[$key]);
						}elseif ($result['grouping_category'] == 'other') {
							unset($originalOtherSort[$key]);
						}

						break;
					}
				}
			}
		}
		foreach ($originalBookSort as $originalFormatSort) {
			$originalFormatSort->delete();
		}
		foreach ($originalComicSort as $originalFormatSort) {
			$originalFormatSort->delete();
		}
		foreach ($originalMovieSort as $originalFormatSort) {
			$originalFormatSort->delete();
		}
		foreach ($originalMusicSort as $originalFormatSort) {
			$originalFormatSort->delete();
		}
		foreach ($originalOtherSort as $originalFormatSort) {
			$originalFormatSort->delete();
		}
	}

	private function setSortedFormats(string $groupingCategory, ?array $value) : void {
		$internalArrayName = $this->getArrayNameForGroupingCategory($groupingCategory);
		if (!empty($internalArrayName)) {
			$this->$internalArrayName = $value;
		}
	}

	public function getLinkedObjectStructure() : array {
		return [
			[
				'object' => 'GroupedWorkDisplaySetting',
				'class' => ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php',
				'linkingProperty' => 'formatSortingGroupId',
				'objectName' => 'Grouped Work Display Setting',
				'objectNamePlural' => 'Grouped Work Display Settings',
			],
		];
	}


}