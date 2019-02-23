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

class Obituaries extends ObjectEditor
{
	function getObjectType(){
		return 'Obituary';
	}
	function getToolName(){
		return 'Obituaries';
	}
	function getPageTitle(){
		return 'Obituaries';
	}
	function getAllObjects(){
		$object = new Obituary();
		$object->orderBy('date');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->obituaryId] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return Obituary::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return array('personId', 'source', 'date');
	}
	function getIdKeyColumn(){
		return 'obituaryId';
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