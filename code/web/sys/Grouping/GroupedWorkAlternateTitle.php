<?php /** @noinspection PhpMissingFieldTypeInspection */


class GroupedWorkAlternateTitle extends DataObject {
	public $__table = 'grouped_work_alternate_titles';
	public $id;
	public $permanent_id;
	public $alternateGroupingCategory;
	public $alternateTitle;
	public $alternateAuthor;
	public $addedBy;
	public $dateAdded;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'permanent_id' => [
				'property' => 'permanent_id',
				'type' => 'text',
				'label' => 'Grouped Work ID',
				'description' => 'The grouped work id with the alternate title',
				'readOnly' => true,
			],
			'alternateGroupingCategory' => [
				'property' => 'alternateGroupingCategory',
				'type' => 'text',
				'label' => 'Grouping Category',
				'description' => 'The grouping category of the original work'
			],
			'alternateTitle' => [
				'property' => 'alternateTitle',
				'type' => 'text',
				'label' => 'Title Variant',
				'description' => 'The alternate title from the original work',
			],
			'alternateAuthor' => [
				'property' => 'alternateAuthor',
				'type' => 'text',
				'label' => 'Author Variant',
				'description' => 'An alternate author from the original work',
			],
			'addedByName' => [
				'property' => 'addedByName',
				'type' => 'text',
				'label' => 'Added By',
				'description' => 'Who added the record',
				'readOnly' => true,
			],
			'dateAdded' => [
				'property' => 'dateAdded',
				'type' => 'timestamp',
				'label' => 'Date Added',
				'description' => 'The date the record was added',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	private static $usersById = [];

	function __get($name) {
		if ($name == 'addedByName') {
			if (empty($this->_data['addedByName'])) {
				if (array_key_exists($this->addedBy, GroupedWorkAlternateTitle::$usersById)) {
					$this->_data['addedByName'] = GroupedWorkAlternateTitle::$usersById[$this->addedBy];
				} else {
					$user = new User();
					$user->id = $this->addedBy;
					$user->find(true);
					if (!empty($user->displayName)) {
						$this->_data['addedByName'] = $user->displayName;
					} else {
						$this->_data['addedByName'] = $user->firstname . ' ' . $user->lastname;
					}
					GroupedWorkAlternateTitle::$usersById[$this->addedBy] = $this->_data['addedByName'];
				}
			}
		}
		return $this->_data[$name] ?? null;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$relatedWork = new GroupedWork();
		$relatedWork->permanent_id = $this->permanent_id;
		if ($relatedWork->find(true)) {
			$relatedWork->forceReindex(true);
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$relatedWork = new GroupedWork();
		$relatedWork->permanent_id = $this->permanent_id;
		if ($relatedWork->find(true)) {
			$relatedWork->forceReindex(true);
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
		$relatedWork = new GroupedWork();
		$relatedWork->permanent_id = $this->permanent_id;
		if ($relatedWork->find(true)) {
			$relatedWork->forceReindex(true);
		}
		return parent::delete($useWhere, $hardDelete);
	}
}