<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Genealogy/Person.php';
require_once 'XML/Unserializer.php';

class People extends ObjectEditor
{
	function getObjectType(){
		return 'Person';
	}
	function getToolName(){
		return 'People';
	}
	function getPageTitle(){
		return 'People';
	}
	function getAllObjects(){
		$object = new Person();
		$object->orderBy('lastName, firstName');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->personId] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		$person = new Person();
		return $person->getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return array('lastName', 'firstName', 'middleName', 'birthDate');
	}
	function getIdKeyColumn(){
		return 'personId';
	}
	function getAllowableRoles(){
		return array('genealogyContributor');
	}
	function getRedirectLocation($objectAction, $curObject){
		global $configArray;
		if ($objectAction == 'delete'){
			return $configArray['Site']['path'] . '/Union/Search?searchSource=genealogy&lookfor=&genealogyType=GenealogyName&submit=Find';
		}else{
			return $configArray['Site']['path'] . '/Person/' . $curObject->personId;
		}
	}
	function showReturnToList(){
		return false;
	}
}