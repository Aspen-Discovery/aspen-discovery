<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/ExploreMoreSourceEntry.php';

class ExploreMoreSourceGroup extends DataObject {
	public $__table = 'explore_more_source_group';
	public $id;
	public $name;

	protected $_exploreMoreSources = null;
	
	public function __get($name) {
		if ($name === 'exploreMoreSources') {
			return $this->getExploreMoreSources();
		}
		return parent::__get($name);
	}

	public function getExploreMoreSources() : ?array {
		if (!isset($this->_exploreMoreSources) && !empty($this->id)) {
			$this->_exploreMoreSources = [];
			$entry = new ExploreMoreSourceEntry();
			$entry->exploreMoreSourceGroupId = $this->id;
			$entry->orderBy('weight ASC');
			$entry->find();
			while ($entry->fetch()) {
				$clonedEntry = clone($entry);
				$clonedEntry->sourceName = $clonedEntry->getSourceName();
				$this->_exploreMoreSources[$entry->id] = $clonedEntry;
			}
		}
		return $this->_exploreMoreSources;
	}

	/**
	 * Ensure all ExploreMoreSource records are present as entries for this group
	 */
	public static function ensureDefaultEntries($groupId) : array {
		require_once ROOT_DIR . '/sys/ExploreMoreSourceEntry.php';
		require_once ROOT_DIR . '/sys/ExploreMoreSource.php';
		$group = new ExploreMoreSourceGroup();
		$group->id = $groupId;
		if (!$group->find(true)) {
			// Group not found
			return [];
		}
		// Only ensure entries exist, do not assign to $group->_exploreMoreSources
		$entries = [];
		$entry = new ExploreMoreSourceEntry();
		$entry->exploreMoreSourceGroupId = $group->id;
		$entry->orderBy('weight ASC');
		$entry->find();
		$hasEntries = false;
		while ($entry->fetch()) {
			$entries[$entry->id] = clone($entry);
			$hasEntries = true;
		}
		// If no entries exist, auto-populate with all sources
		if (!$hasEntries) {
			$source = new ExploreMoreSource();
			$source->find();
			$weight = 1;
			while ($source->fetch()) {
				$newEntry = new ExploreMoreSourceEntry();
				$newEntry->exploreMoreSourceGroupId = $group->id;
				$newEntry->exploreMoreSourceId = $source->id;
				$newEntry->weight = $weight++;
				$newEntry->insert();
				$entries[$newEntry->id] = clone($newEntry);
			}
		}
		return $entries;
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		require_once ROOT_DIR . '/sys/ExploreMoreSourceEntry.php';
		$entryStructure = ExploreMoreSourceEntry::getObjectStructure($context);
		unset($entryStructure['exploreMoreSourceGroupId']);
		unset($entryStructure['weight']);
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Group Name',
				'description' => 'The name of this Explore More group',
				'required' => true,
				'readOnly' => true,
			],
			'exploreMoreSources' => [
				'property' => 'exploreMoreSources',
				'type' => 'oneToMany',
				'label' => 'Explore More Sources',
				'description' => 'Drag and drop to reorder sources. Only sources that are active based on the current modules and settings will be shown.',
				'keyThis' => 'id',
				'keyOther' => 'exploreMoreSourceGroupId',
				'subObjectType' => 'ExploreMoreSourceEntry',
				'structure' => $entryStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => false,
				'displayField' => 'sourceName',
			],
		];
		self::$_objectStructure[$context] = $structure;
		return $structure;
	}

	public function update(string $context = '') : int|bool {
		$result = parent::update($context);
		if (isset($this->_exploreMoreSources) && is_array($this->_exploreMoreSources)) {
			foreach ($this->_exploreMoreSources as $entry) {
				if (isset($entry->_deleteOnSave) && $entry->_deleteOnSave) {
					$entry->delete();
				} else {
					$entry->update();
				}
			}
		}
		return $result;
	}
}
