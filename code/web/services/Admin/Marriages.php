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
require_once ROOT_DIR . '/sys/Genealogy/Marriage.php';
require_once 'XML/Unserializer.php';

class Marriages extends ObjectEditor
{
	function getObjectType(){
		return 'Marriage';
	}
	function getToolName(){
		return 'Marriages';
	}
	function getPageTitle(){
		return 'Marriages';
	}
	function getAllObjects(){
		$object = new Marriage();
		$object->orderBy('marriageDate');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->marriageId] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return Marriage::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return array('personId', 'spouseName', 'date');
	}
	function getIdKeyColumn(){
		return 'marriageId';
	}
	function getAllowableRoles(){
		return array('genealogyContributor');
	}
	function getRedirectLocation($objectAction, $curObject){
		global $configArray;
		return $configArray['Site']['path'] . '/Person/' . $curObject->personId;
	}
	function showReturnToList(){
		return false;
	}
}