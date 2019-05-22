<?php


class ARSetting extends DataObject
{
    public $__table = 'accelerated_reading_settings';
    public $id;
    public $indexSeries;
    public $indexSubjects;
    public $arExportPath;
    public $ftpServer;
    public $ftpUser;
    public $ftpPassword;
    public $lastFetched;

    public static function getObjectStructure()
    {
        $structure = array(
            'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
            'indexSeries' => array('property' => 'indexSeries', 'type' => 'checkbox', 'label' => 'Index Series', 'description'=>'Whether or not series from the AR data should be indexed', 'default'=>1),
            'indexSubjects' => array('property' => 'indexSubjects', 'type' => 'checkbox', 'label' => 'Index indexSubjects', 'description'=>'Whether or not subjects from the AR data should be indexed', 'default'=>1),
            'arExportPath' => array('property'=>'arExportPath', 'type'=>'text', 'label'=>'AR Export Path', 'description'=>'The local path on the server where the Accelerated Reader data is stored'),
            'ftpServer' => array('property'=>'ftpServer', 'type'=>'text', 'label'=>'FTP Server URL', 'description'=>'The Name of the FTP Server'),
            'ftpUser' => array('property'=>'ftpUser', 'type'=>'text', 'label'=>'FTP Username', 'description'=>'The username to connect to the FTP Server'),
            'ftpPassword' => array('property' => 'ftpPassword', 'type' => 'password', 'label' => 'FTP Password', 'description'=>'The password to connect to the FTP Server', 'hideInLists' => true),
            'lastFetched' => array('property' => 'lastFetched', 'type' => 'integer', 'label' => 'Last Fetch from the FTP Server', 'description'=>'The timestamp when the file was last fetched from the server', 'default'=>0),
        );
        return $structure;
    }
}