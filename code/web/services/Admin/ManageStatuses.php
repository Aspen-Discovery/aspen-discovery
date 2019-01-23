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
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';

class ManageStatuses extends ObjectEditor
{

	function getObjectType(){
		return 'MaterialsRequestStatus';
	}
	function getToolName(){
		return 'ManageStatuses';
	}
	function getPageTitle(){
		return 'Materials Request Statuses';
	}
	function getAllObjects(){
		$user = UserAccount::getLoggedInUser();

		$status = new MaterialsRequestStatus();
		if (UserAccount::userHasRole('library_material_requests')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$status->libraryId = $homeLibrary->libraryId;
		}
		$status->orderBy('isDefault DESC');
		$status->orderBy('isPatronCancel DESC');
		$status->orderBy('isOpen DESC');
		$status->orderBy('description ASC');
		$status->find();
		$objectList = array();
		while ($status->fetch()){
			$objectList[$status->id] = clone $status;
		}
		return $objectList;
	}
	function getObjectStructure(){
		return MaterialsRequestStatus::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'description';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function getAllowableRoles(){
		return array('library_material_requests');
	}
	function customListActions(){
		$objectActions = array();
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('library_material_requests')){
			$objectActions[] = array(
				'label' => 'Reset to Default',
				'action' => 'resetToDefault',
			);
		}

		return $objectActions;
	}

	function resetToDefault(){
		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('library_material_requests')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$materialRequestStatus = new MaterialsRequestStatus();
			$materialRequestStatus->libraryId = $homeLibrary->libraryId;
			$materialRequestStatus->delete();

			$materialRequestStatus = new MaterialsRequestStatus();
			$materialRequestStatus->libraryId = -1;
			$materialRequestStatus->find();
			while ($materialRequestStatus->fetch()){
				$materialRequestStatus->id = null;
				$materialRequestStatus->libraryId = $homeLibrary->libraryId;
				$materialRequestStatus->insert();
			}
		}
		header("Location: /Admin/ManageStatuses");
	}
}