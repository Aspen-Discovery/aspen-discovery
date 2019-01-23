<?php
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ArtifactImage extends DB_DataObject
{
    public $__table = 'artifact_image';    // table name
    public $artifactImageId;
    public $artifactId;
    public $webLink;
    public $title;
    public $description;
    public $dateOfCreation;
    public $dateOfAcquisition;
    public $physicalDescription;
    public $copyright;
    
    function keys() {
        return array('artifactImageId');
    }
}