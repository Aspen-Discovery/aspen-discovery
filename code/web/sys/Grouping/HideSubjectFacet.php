<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class HideSubjectFacet extends DataObject {
	public $__table = 'hide_subject_facets';
	public $id;
	public $subjectTerm;
	public $subjectNormalized;
	public $dateAdded;

	static function getObjectStructure($context = ''): array {
		return [
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
	}

	public function insert($context = '') {
		$this->subjectNormalized = $this->normalizeSubject($this->subjectTerm);
		return parent::insert();
	}

	public function update($context = '') {
        $this->__set("subjectNormalized", $this->normalizeSubject($this->subjectTerm));
		return parent::update();
	}

	public function normalizeSubject($subjectTerm): string {
		return rtrim($subjectTerm, '- .,;|\t');
	}
}