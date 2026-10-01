<?php /** @noinspection PhpMissingFieldTypeInspection */


class Obituary extends DataObject {
	public $__table = 'obituary'; // table name
	public $__primaryKey = 'obituaryId';
	public $obituaryId;
	public $personId;
	public $source;
	public $date;
	/** @noinspection PhpUnused */
	public $dateDay;
	/** @noinspection PhpUnused */
	public $dateMonth;
	/** @noinspection PhpUnused */
	public $dateYear;
	public $sourcePage;
	public $contents;
	/** @noinspection PhpUnused */
	public $picture;

	function id() {
		return $this->obituaryId;
	}

	function label() : string {
		return $this->source . ' ' . $this->sourcePage . ' ' . $this->date;
	}

	function getNumericColumnNames(): array {
		return [
			'dateDay',
			'dateMonth',
			'dateYear',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			[
				'property' => 'obituaryId',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the obituary in the database',
				'storeDb' => true,
			],
			[
				'property' => 'personId',
				'type' => 'hidden',
				'label' => 'Person Id',
				'description' => 'The id of the person this obituary is for',
				'storeDb' => true,
			],
			//array('property'=>'person', 'type'=>'method', 'label'=>'Person', 'description'=>'The person this obituary is for', 'storeDb' => false),
			[
				'property' => 'source',
				'type' => 'text',
				'maxLength' => 100,
				'label' => 'Source',
				'description' => 'The source of the obituary',
				'storeDb' => true,
			],
			[
				'property' => 'sourcePage',
				'type' => 'text',
				'maxLength' => 25,
				'label' => 'Source Page',
				'description' => 'The page where the obituary was found',
				'storeDb' => true,
			],
			[
				'property' => 'date',
				'type' => 'partialDate',
				'label' => 'Date',
				'description' => 'The date of the obituary.',
				'storeDb' => true,
				'propNameMonth' => 'dateMonth',
				'propNameDay' => 'dateDay',
				'propNameYear' => 'dateYear',
			],
			[
				'property' => 'contents',
				'type' => 'textarea',
				'rows' => 10,
				'cols' => 80,
				'label' => 'Full Text of the Obituary',
				'description' => 'The full text of the obituary.',
				'storeDb' => true,
				'hideInLists' => true,
			],
			[
				'property' => 'picture',
				'type' => 'image',
				'thumbWidth' => 65,
				'mediumWidth' => 250,
				'label' => 'Picture',
				'description' => 'A scanned image of the obituary.',
				'storeDb' => true,
				'storeSolr' => false,
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