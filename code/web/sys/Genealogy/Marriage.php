<?php /** @noinspection PhpMissingFieldTypeInspection */


class Marriage extends DataObject {
	public $__table = 'marriage';    // table name
	public $__primaryKey = 'marriageId';
	public $marriageId;
	public $personId;
	public $spouseName;
	/** @noinspection PhpUnused */
	public $spouseId;
	public $marriageDate;
	/** @noinspection PhpUnused */
	public $marriageDateDay;
	/** @noinspection PhpUnused */
	public $marriageDateMonth;
	/** @noinspection PhpUnused */
	public $marriageDateYear;
	public $comments;

	function id() {
		return $this->marriageId;
	}

	function getNumericColumnNames(): array {
		return [
			'marriageDateDay',
			'marriageDateMonth',
			'marriageDateYear',
		];
	}

	function label() : string {
		return $this->spouseName . (isset($this->marriageDate) ? (' - ' . $this->marriageDate) : '');
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			[
				'property' => 'marriageId',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the marriage in the database',
				'storeDb' => true,
			],
			[
				'property' => 'personId',
				'type' => 'hidden',
				'label' => 'Person Id',
				'description' => 'The id of the person this marriage is for',
				'storeDb' => true,
			],
			[
				'property' => 'spouseName',
				'type' => 'text',
				'maxLength' => 100,
				'label' => 'Spouse',
				'description' => 'The spouse&apos;s name.',
				'storeDb' => true,
			],
			[
				'property' => 'marriageDate',
				'type' => 'partialDate',
				'label' => 'Date',
				'description' => 'The date of the marriage.',
				'storeDb' => true,
				'propNameMonth' => 'marriageDateMonth',
				'propNameDay' => 'marriageDateDay',
				'propNameYear' => 'marriageDateYear',
			],
			[
				'property' => 'comments',
				'type' => 'textarea',
				'rows' => 10,
				'cols' => 80,
				'label' => 'Comments',
				'description' => 'Information about the marriage.',
				'storeDb' => true,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		//Load the person this is for, and update solr
		if ($this->personId) {
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->personId;
			$person->find(true);
			$person->saveToSolr();
		}
		return $ret;
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		//Load the person this is for, and update solr
		if ($this->personId) {
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->personId;
			$person->find(true);
			$person->saveToSolr();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$personId = $this->personId;
		$ret = parent::delete($useWhere, $hardDelete);
		//Load the person this is for, and update solr
		if ($personId) {
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->personId;
			$person->find(true);
			$person->saveToSolr();
		}
		return $ret;
	}
}