<?php /** @noinspection PhpMissingFieldTypeInspection */


class HideSubjectFacet extends DataObject {
	public $__table = 'hide_subject_facets';
	public $id;
	public $subjectTerm;
	public $subjectNormalized;
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
			'subjectTerm' => [
				'property' => 'subjectTerm',
				'type' => 'text',
				'label' => 'Hide Subject Term',
				'description' => 'Subject term to hide',
				'autocomplete' => 'off',
				'forcesReindex' => true,
			],
			'subjectNormalized' => [
				'property' => 'subjectNormalized',
				'type' => 'text',
				'label' => 'Hide Subject Term, normalized',
				'description' => 'Subject term to hide, normalized',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = '') : int|bool {
		$this->subjectNormalized = $this->normalizeSubject($this->subjectTerm);
		return parent::insert();
	}

	public function update(string $context = '') : int|bool {
        $this->__set("subjectNormalized", $this->normalizeSubject($this->subjectTerm));
		return parent::update();
	}

	public function normalizeSubject($subjectTerm): string {
		return rtrim($subjectTerm, '- .,;');
	}
}