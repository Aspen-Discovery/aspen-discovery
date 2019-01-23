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
require_once 'XML/Unserializer.php';
require_once ROOT_DIR . '/Drivers/marmot_inc/Holiday.php';

class Holidays extends ObjectEditor
{
	
	function getObjectType(){
		return 'Holiday';
	}
	function getToolName(){
		return 'Holidays';
	}
	function getPageTitle(){
		return 'Holidays';
	}
	function getAllObjects(){
		$holiday = new Holiday();
		$holiday->orderBy('date');
		$holiday->find();
		$list = array();
		while ($holiday->fetch()){
			$list[$holiday->id] = clone $holiday;
		}
		return $list;
	}
	function getObjectStructure(){
		return Holiday::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}

}