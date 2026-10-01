<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Grouping/GroupedWorkEContentSort.php';

class GroupedWorkEContentSortingGroup extends DataObject {
	public $__table = 'grouped_work_econtent_sort_group';
	public $id;
	public $name;
	public $sortAvailableSourcesFirst;
	public $sortMethod;
	/** @noinspection PhpUnused */
	public $_sortedEContentSources;

	public function getNumericColumnNames(): array {
		return [
			'sortMethod',
		];
	}

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$eContentSortStructure = GroupedWorkEContentSort::getObjectStructure($context);
		unset($eContentSortStructure['weight']);
		unset($eContentSortStructure['eContentSortingGroupId']);

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
			'sortAvailableSourcesFirst' => [
				'property' => 'sortAvailableSourcesFirst',
				'type' => 'checkbox',
				'label' => 'Sort Available Sources First',
				'description' => 'If on will sort sources that are available before unavailable sources',
			],
			'sortMethod' => [
				'property' => 'sortMethod',
				'type' => 'enum',
				'values' => [
					'1' => 'Sort Alphabetically',
					'2' => 'Custom Sort'
				],
				'label' => 'Sorting Method',
				'description' => 'Determines how eContent sources are sorted for grouped works',
				'onchange' => "return AspenDiscovery.Admin.updateGroupedWorkEContentSortFields();",
			],
			'sortedEContentSources' => [
				'property' => 'sortedEContentSources',
				'type' => 'oneToMany',
				'label' => 'Sorted eContent Sources (any sources not listed will be sorted alphabetically at the end)',
				'description' => 'A list of eContent sources in the order they should be displayed',
				'keyThis' => 'id',
				'keyOther' => 'eContentSortingGroupId',
				'subObjectType' => 'GroupedWorkEContentSort',
				'structure' => $eContentSortStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
		];

		self::$_objectStructure[$context] = $objectStructure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = ''): int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSortedEContentSources();
		}
		return $ret;
	}

	public function insert(string $context = ''): int|bool {
		$this->sortMethod = 1;
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveSortedEContentSources();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret !== false) {
			$sortedEContentSource = new GroupedWorkEContentSort();
			$sortedEContentSource->eContentSortingGroupId = $this->id;
			$sortedEContentSource->delete(true);
		}
		return $ret;
	}

	public function saveSortedEContentSources(): void {
		if (!empty($this->_sortedEContentSources) && is_array($this->_sortedEContentSources)) {
			$this->saveOneToManyOptions($this->_sortedEContentSources, 'eContentSortingGroupId');
			unset($this->_sortedEContentSources);
		}
	}

	public function __get($name) {
		if ($name == 'sortedEContentSources') {
			return $this->getSortedEContentSources();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'sortedEContentSources') {
			$this->_sortedEContentSources = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/** @return GroupedWorkEContentSort[]|null */
	public function getSortedEContentSources(): ?array {
		if ($this->id) {
			$this->_sortedEContentSources = [];
			$sortedEContentSource = new GroupedWorkEContentSort();
			$sortedEContentSource->eContentSortingGroupId = $this->id;
			$sortedEContentSource->orderBy('weight');
			$sortedEContentSource->find();
			while ($sortedEContentSource->fetch()) {
				$this->_sortedEContentSources[$sortedEContentSource->id] = clone($sortedEContentSource);
			}
		}
		return $this->_sortedEContentSources;
	}

	private ?array $_econtentSourceWeights = null;
	public function getEContentSourceWeights(): ?array {
		if ($this->_econtentSourceWeights == null) {
			$this->_econtentSourceWeights = [];
			foreach ($this->getSortedEContentSources() as $sortedEContentSource) {
				$this->_econtentSourceWeights[$sortedEContentSource->eContentSource] = $sortedEContentSource->weight;
			}
		}
		return $this->_econtentSourceWeights;
	}

	public function getLinkedObjectStructure(): array {
		return [
			[
				'object' => 'GroupedWorkDisplaySetting',
				'class' => ROOT_DIR . '/sys/Grouping/GroupedWorkDisplaySetting.php',
				'linkingProperty' => 'eContentSortingGroupId',
				'objectName' => 'Grouped Work Display Setting',
				'objectNamePlural' => 'Grouped Work Display Settings',
			],
		];
	}

	public static function getValidEContentSources(): array {
		require_once ROOT_DIR . '/sys/Indexing/IndexedEContentSource.php';
		$indexedEContentSource = new IndexedEContentSource();
		$indexedEContentSource->orderBy('eContentSource');
		$indexedEContentSource->find();
		$validEContentSources = [];
		while ($indexedEContentSource->fetch()) {
			$validEContentSources[$indexedEContentSource->eContentSource] = $indexedEContentSource->eContentSource;
		}

		return $validEContentSources;
	}

	public function canActiveUserDelete() : bool {
		//Do not allow the default to be deleted
		return $this->id != 1;
	}
}