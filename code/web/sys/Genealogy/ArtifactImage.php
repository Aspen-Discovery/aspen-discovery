<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
class ArtifactImage extends DataObject
{
    public $__table = 'artifact_image';    // table name
    public $__primaryKey = 'artifactImageId';
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