<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Translation/Language.php';

class Translation_Languages extends ObjectEditor
{
    function getObjectType(){
        return 'Language';
    }
    function getToolName(){
        return 'Languages';
    }
    function getModule(){
        return 'Translation';
    }
    function getPageTitle(){
        return 'User Languages';
    }
    function getAllObjects(){
        $object = new Language();
        $object->find();
        $objectList = array();
        while ($object->fetch()){
            $objectList[$object->id] = clone $object;
        }
        return $objectList;
    }
    function getObjectStructure(){
        return Language::getObjectStructure();
    }
    function getPrimaryKeyColumn(){
        return 'id';
    }
    function getIdKeyColumn(){
        return 'id';
    }
    function getAllowableRoles(){
        return array('opacAdmin', 'libraryAdmin', 'translator');
    }
    function canAddNew(){
        return UserAccount::userHasRole('opacAdmin', 'libraryAdmin', 'translator');
    }
    function canDelete(){
        return UserAccount::userHasRole('opacAdmin', 'libraryAdmin', 'translator');
    }
    function getAdditionalObjectActions($existingObject){
        return [];
    }

    function getInstructions(){
        return '';
    }
}