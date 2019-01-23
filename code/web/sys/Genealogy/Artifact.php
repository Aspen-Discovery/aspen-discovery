<?php
/**
 * Table Definition for an artifact (physical document, picture, man-made object, etc)
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class Artifact extends DB_DataObject
{
    public $__table = 'artifact';    // table name
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