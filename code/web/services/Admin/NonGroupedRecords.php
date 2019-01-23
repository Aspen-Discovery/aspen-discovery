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
require_once ROOT_DIR . '/sys/Grouping/NonGroupedRecord.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_NonGroupedRecords extends ObjectEditor
{
	function getObjectType(){
		return 'NonGroupedRecord';
	}
	function getToolName(){
		return 'NonGroupedRecords';
	}
	function getPageTitle(){
		return 'Records to Not Group';
	}
	function getAllObjects(){
		$object = new NonGroupedRecord();
		$object->orderBy('source, recordId');
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return NonGroupedRecord::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'cataloging');
	}
	function getInstructions(){
		global $interface;
		return $interface->fetch('Admin/ungrouping_work_instructions.tpl');
	}
	function getListInstructions(){
		return 'For more information on how to ungroup works, see the <a href="https://docs.google.com/document/d/1fTjDQ04gctT6GpTmKU8uGZyZRgLW6z09eE4DwNsOxOw">online documentation</a>.';
	}


}