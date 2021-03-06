<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class UserContribution extends DataObject
{
    public $__table = 'redwood_user_contribution';
    public $id;
    public $userId;
    public $title;
    public $creator;
    public $dateCreated;
    public $description;
    public $suggestedSubjects;
    public $howAcquired;
    public $filePath;
    public $status;
    public $license;
    public $allowRemixing;
    public $prohibitCommercialUse;
    public $requireShareAlike;
    public $dateContributed;

    public static function getObjectStructure() : array{
        $structure = array(
            array('property'=>'title', 'type'=>'text', 'label'=>'Title', 'description'=>'Title of the file', 'maxLength' => 255, 'required' => true),
            array('property'=>'creator', 'type'=>'text', 'label'=>'Creator', 'description'=>'Creator of the file', 'maxLength' => 255),
            array('property'=>'dateCreated', 'type'=>'date', 'label'=>'Date Created', 'description'=>'When the picture was taken or file created'),
            array('property'=>'description', 'type'=>'textarea', 'label'=>'Description', 'description'=>'Description of the file'),
            array('property'=>'suggestedSubjects', 'type'=>'text', 'label'=>'Subject(s) separated by commas', 'description'=>'Subject(s) that should be applied separated by commas'),
            array('property'=>'howAcquired', 'type'=>'text', 'label'=>'How Acquired', 'description'=>'How the file was acquired', 'maxLength' => 255),
            array('property'=>'filePath', 'type'=>'file', 'label'=>'File to submit', 'description'=>'The file to submit'),
            array('property'=>'license', 'type'=>'enum', 'values'=>['none' => 'Unknown', 'CC0' => 'Creative Commons 0, no rights reserved', 'cc' => 'Creative Commons', 'public' => 'Public Domain'], 'label'=>'License', 'description'=>'The license that applies to the file'),
            array('property'=>'allowRemixing', 'type'=>'checkbox', 'label'=>'Allow Remixing', 'description'=>'Whether or not the file can be changed after downloading'),
            array('property'=>'prohibitCommercialUse', 'type'=>'checkbox', 'label'=>'Prohibit Commercial Usage', 'description'=>'Prohibit commercial use of the file'),
            array('property'=>'requireShareAlike', 'type'=>'checkbox', 'label'=>'Require Share Alike', 'description'=>'If the downloaded file has been changed does the change need the same rights?'),

        );
        return $structure;
    }

    public function insert() {
        global $user;
        $this->dateContributed = time();
        $this->userId = $user->id;
        $this->status = 'submitted';
        return parent::insert(); // TODO: Change the autogenerated stub
    }
}