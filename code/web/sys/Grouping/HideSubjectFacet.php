<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class HideSubjectFacet extends DataObject
{
    public $__table = 'hide_subject_facets';
    public $id;
    public $subjectTerm;
    public $subjectNormalized;
    public $dateAdded;

    static function getObjectStructure(): array
    {
        return [
            'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
            'subjectTerm' => array('property' => 'subjectTerm', 'type' => 'text', 'label' => 'Hide Subject Term', 'description' => 'Subject term to hide', 'autocomplete' => 'off', 'forcesReindex' => true),
            'subjectNormalized' => array('property' => 'subjectNormalized', 'type' => 'text', 'label' => 'Hide Subject Term, normalized', 'description' => 'Subject term to hide, normalized', 'readOnly'=> true),
            'dateAdded' => array('property' => 'dateAdded', 'type' => 'timestamp', 'label' => 'Date Added', 'description' => 'The date the record was added', 'readOnly'=> true)
        ];
    }

    public function insert(){
        $this->dateAdded = time();
        $this->subjectNormalized = $this->normalizeSubject($this->subjectTerm);
        return parent::insert();
    }

    public function update()
    {
        $this->subjectNormalized = $this->normalizeSubject($this->subjectTerm);
        return parent::update();
    }

    public function normalizeSubject($subjectTerm): string
    {
        return rtrim($subjectTerm, '- .,;|\t');
    }
}