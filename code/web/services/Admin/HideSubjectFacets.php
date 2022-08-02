<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class HideSubjectFacets extends DataObject
{
    public $__table = 'hide_subject_facets';
    public $id;
    public $name;
    public $subjects;
    public $subjectFilters;

    static function getObjectStructure(): array
    {
        $libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Hide Subject Facets'));
        $locationList = Location::getLocationList(!UserAccount::userHasPermission('Hide Subject Facets'));

        return [
            'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
            'subjects' => array('property' => 'subjects', 'type' => 'textarea', 'label' => 'Available Subjects', 'description' => 'Subjects that exist within the collection', 'readOnly' => true, 'hideInLists' => true),
            'subjectFilters' => array('property' => 'subjectFilters', 'type' => 'textarea', 'label' => 'Subject Filters (each filter on it\'s own line, regular expressions ok)', 'description' => 'Subjects to filter by', 'hideInLists' => true),
        ];
    }
}