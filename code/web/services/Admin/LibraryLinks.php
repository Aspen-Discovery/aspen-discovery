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
require_once ROOT_DIR . '/sys/LibraryLink.php';

class Admin_LibraryLinks extends ObjectEditor
{

	function getObjectType(){
		return 'LibraryLink';
	}
	function getToolName(){
		return 'LibraryLinks';
	}
	function getPageTitle(){
		return 'Library Links';
	}
	function getAllObjects(){
		//Look lookup information for display in the user interface
		$user = UserAccount::getLoggedInUser();

		$object = new LibraryLink();
		$location = new Location();
		$location->orderBy('displayName');
		if (!UserAccount::userHasRole('opacAdmin')){
			//Scope to just locations for the user based on home library
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$object->libraryId = $patronLibrary->libraryId;
		}

		$object->orderBy('weight');
		$object->find();
		$list = array();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}
		return $list;
	}
	function getObjectStructure(){
		return LibraryLink::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin');
	}

}