<?php /** @noinspection PhpMissingFieldTypeInspection */

class ExploreMoreSourceEntry extends DataObject {
	public function __get($name) {
		if ($name === 'sourceName') {
			return $this->getSourceName();
		}
		return parent::__get($name);
	}

	private $_sourceName = null;

	public function getSourceName() : string {
		if (empty($this->_sourceName)) {
			$this->_sourceName = '';
			require_once ROOT_DIR . '/sys/ExploreMoreSource.php';
			$source = new ExploreMoreSource();
			$source->id = $this->exploreMoreSourceId;
			if ($source->find(true)) {
				$this->_sourceName = $source->source;
			}
		}
		return $this->_sourceName;
	}
	public $__table = 'explore_more_source_entry';
	public $id;
	public $weight;
	public $exploreMoreSourceGroupId;
	public $exploreMoreSourceId;

	function getUniquenessFields(): array {
		return [
			'exploreMoreSourceGroupId',
			'exploreMoreSourceId',
		];
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/Admin/ExploreMoreSource?objectAction=edit&id=' . $this->exploreMoreSourceId;
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		// Load Groups for lookup values
		$groups = new ExploreMoreSource();
		$groups->orderBy('source');
		$groups->find();
		$groupList = [];
		while ($groups->fetch()) {
			$groupList[$groups->id] = $groups->source;
		}
		$sourceList = $groupList;
		$structure = [
			'sourceName' => [
				'property' => 'sourceName',
				'type' => 'label',
				'label' => 'Source',
				'description' => 'The Explore More source to display',
			],
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'exploreMoreSourceGroupId' => [
				'property' => 'exploreMoreSourceGroupId',
				'type' => 'enum',
				'values' => $groupList,
				'label' => 'Group',
				'description' => 'The group this source should be added in',
			],
			'exploreMoreSourceId' => [
				'property' => 'exploreMoreSourceId',
				'type' => 'enum',
				'values' => $sourceList,
				'label' => 'Source',
				'description' => 'The Explore More source to display',
				'readOnly' => true,
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Order',
				'description' => 'Order for display',
				'default' => 0,
			],
		];
		self::$_objectStructure[$context] = $structure;
		return $structure;
	}
}
