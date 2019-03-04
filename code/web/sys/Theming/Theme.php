<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Theme extends DataObject
{
    public $__table = 'themes';
    public $__primaryKey = 'id';
    public $id;
    public $themeName;
    public $extendsTheme;
    public $logoName;

    public function getObjectStructure() {
        $structure = array(
            'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
            'themeName' => array('property'=>'themeName', 'type'=>'text', 'label'=>'Theme Name', 'description'=>'The Name of the Theme', 'maxLength'=>50, 'required' => true),
            'extendsTheme' => array('property'=>'extendsTheme', 'type'=>'text', 'label'=>'Extends Theme', 'description'=>'A theme that this overrides (leave blank if none is overridden)', 'maxLength'=>50, 'required' => false),
            'logoName' => array('property'=>'logoName', 'type'=>'image', 'label'=>'Logo', 'description'=>'The logo for use in the header', 'required' => false, 'thumbWidth' => 200, 'mediumWidth'=> 400, 'hideInLists' => true),
        );
        return $structure;
    }
}