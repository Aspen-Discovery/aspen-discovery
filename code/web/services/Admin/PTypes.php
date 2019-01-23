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
require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';

class PTypes extends ObjectEditor
{

	function getObjectType(){
		return 'PType';
	}
	function getToolName(){
		return 'PTypes';
	}
	function getPageTitle(){
		return 'PTypes';
	}
	function getAllObjects(){
		$libraryList = array();

		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('opacAdmin')){
			$library = new PType();
			$library->orderBy('pType');
			$library->find();
			while ($library->fetch()){
				$libraryList[$library->id] = clone $library;
			}
		}

		return $libraryList;
	}
	function getObjectStructure(){
		return PType::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'pType';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function canAddNew(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}

}