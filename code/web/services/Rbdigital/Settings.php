<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Rbdigital/RbdigitalSetting.php';

class Rbdigital_Settings extends ObjectEditor
{
    function getObjectType(){
        return 'RbdigitalSetting';
    }
    function getToolName(){
        return 'Settings';
    }
    function getModule(){
        return 'Rbdigital';
    }
    function getPageTitle(){
        return 'Rbdigital Settings';
    }
    function getAllObjects(){
        $object = new RbdigitalSetting();
        $object->find();
        $objectList = array();
        while ($object->fetch()){
            $objectList[$object->id] = clone $object;
        }
        return $objectList;
    }
    function getObjectStructure(){
        return RbdigitalSetting::getObjectStructure();
    }
    function getPrimaryKeyColumn(){
        return 'id';
    }
    function getIdKeyColumn(){
        return 'id';
    }
    function getAllowableRoles(){
        return array('opacAdmin', 'libraryAdmin', 'cataloging');
    }
    function canAddNew(){
        return UserAccount::userHasRole('opacAdmin');
    }
    function canDelete(){
        return UserAccount::userHasRole('opacAdmin');
    }
    function getAdditionalObjectActions($existingObject){
        return [];
    }

    function getInstructions(){
        return '';
    }
}