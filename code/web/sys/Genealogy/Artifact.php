<?php
/**
 * Table Definition for an artifact (physical document, picture, man-made object, etc)
 */
require_once ROOT_DIR . '/sys/DB/DataObject.php';
class Artifact extends DataObject
{
    public $__table = 'artifact';    // table name
    public $__primaryKey = 'artifactId';
    public $artifactId;
    public $catalogId;
    public $objectName;
    public $catalogType;
    public $title;
    public $description;
    public $dateOfCreation;
    public $dateOfAcquisition;
    public $physicalDescription;
    public $place;
    public $copyright;
    public $collection;
    public $lexiconCategory;
    public $lexiconSubCategory;
    public $subjects;
    
    function keys() {
        return array('artifactId');
    }
}