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

require_once ROOT_DIR . "/Action.php";

require_once 'Home.php';

class MyAccount_Edit extends Action
{
	function __construct()
	{
	}

	private function saveChanges($user)
	{
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
		$userListEntry = new UserListEntry();
		$userListEntry->id = $_REQUEST['listEntry'];
		if ($userListEntry->find(true)){
			$userListEntry->notes = strip_tags($_REQUEST['notes']);
			$userListEntry->update();
		}
	}

	function launch($msg = null)
	{
		global $interface;
		global $configArray;

		if (!UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			MyAccount_Login::launch();
			exit();
		}else{
			$user = UserAccount::getLoggedInUser();
		}

		// Save Data
		$listId = isset($_REQUEST['list_id']) ? $_REQUEST['list_id'] : null;
		if (is_array($listId)){
			$listId = array_pop($listId);
		}
		if (!empty($listId) && ctype_digit($listId)) {
			if (isset($_POST['submit'])) {
				$this->saveChanges($user);

				// After changes are saved, send the user back to an appropriate page;
				// either the list they were viewing when they started editing, or the
				// overall favorites list.
				if (isset($listId)) {
					$nextAction = 'MyList/' . $listId;
				} else {
					$nextAction = 'Home';
				}
				header('Location: ' . $configArray['Site']['path'] . '/MyAccount/' . $nextAction);
				exit();
			}

			require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
			$userList     = new UserList();
			$userList->id = $listId;
			if ($userList->find(true)) {
				$interface->assign('list', $userList);

				$id = $_GET['id'];
				if (!empty($id)) {
					// Item ID
					$interface->assign('recordId', $id);

					if (strpos($id, ':') === false) {
						// Grouped Works (Catalog Items)
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						$groupedWorkDriver = new GroupedWorkDriver($id);
						if ($groupedWorkDriver->isValid) {
							$interface->assign('recordDriver', $groupedWorkDriver);
						}
					} else {
						// Archive Objects
						require_once ROOT_DIR . './sys/Utils/FedoraUtils.php';
						$fedoraUtils         = FedoraUtils::getInstance();
						$archiveObject       = $fedoraUtils->getObject($id);
						$archiveRecordDriver = RecordDriverFactory::initRecordDriver($archiveObject);
						$interface->assign('recordDriver', $archiveRecordDriver);
					}

					// Retrieve saved information about record
					require_once ROOT_DIR . '/sys/LocalEnrichment/UserListEntry.php';
					$userListEntry                         = new UserListEntry();
					$userListEntry->groupedWorkPermanentId = $id;
					$userListEntry->listId                 = $listId;
					if ($userListEntry->find(true)) {
						$interface->assign('listEntry', $userListEntry);
					} else {
						$interface->assign('error', 'The item you selected is not part of the selected list.');
					}
				} else {
					$interface->assign('error', 'No ID for the list item.');
				}
			} else {
				$interface->assign('error', "List {$listId} was not found.");
			}
		} else {
			$interface->assign('error', 'Invalid List ID.');
		}
		$this->display('editListTitle.tpl', 'Edit List Entry');
	}
}

