<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Obituary extends DataObject {
	public $__table = 'obituary'; // table name
	public $__primaryKey = 'obituaryId';
	public $obituaryId;
	public $personId;
	public $source;
	public $date;
	public $dateDay;
	public $dateMonth;
	public $dateYear;
	public $sourcePage;
	public $contents;
	public $picture;

	function id() {
		return $this->obituaryId;
	}

	function label() {
		return $this->source . ' ' . $this->sourcePage . ' ' . $this->date;
	}

	function getNumericColumnNames(): array {
		return [
			'dateDay',
			'dateMonth',
			'dateYear',
		];
	}

	static function getObjectStructure($context = ''): array {
		return [
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
	}

	function insert($context = '') {
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

	function update($context = '') {
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

	function delete($useWhere = false) {
		$personId = $this->personId;
		$ret = parent::delete($useWhere);
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