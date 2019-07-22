<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalSetting.php';

class RBdigital_Settings extends ObjectEditor
{
    function getObjectType(){
        return 'RBdigitalSetting';
    }
    function getToolName(){
        return 'Settings';
    }
    function getModule(){
        return 'RBdigital';
    }
    function getPageTitle(){
        return 'RBdigital Settings';
    }
    function getAllObjects(){
        $object = new RBdigitalSetting();
        $object->find();
        $objectList = array();
        while ($object->fetch()){
            $objectList[$object->id] = clone $object;
        }
        return $objectList;
    }
    function getObjectStructure(){
        return RBdigitalSetting::getObjectStructure();
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