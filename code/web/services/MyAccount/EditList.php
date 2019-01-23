<?php
/**
 * EditList action for MyResearch module
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
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
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
require_once ROOT_DIR . "/Action.php";

require_once 'Home.php';

/**
 * EditList action for MyResearch module
 *
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class EditList extends Action
{
	/**
	 * Save a user's changes.
	 *
	 * @param object $user Logged in user object
	 * @param object $list List to update
	 *
	 * @return boolean true / false
	 * @access private
	 */
	private function _saveChanges($user, $list)
	{
		if ($list->user_id == $user->id) {
			$title = $_POST['title'];
			$desc = $_POST['desc'];
			$public = $_POST['public'];
			return $list->updateList($title, $desc, $public);
		}
		return false;
	}

	/**
	 * Process parameters and display the page.
	 *
	 * @return void
	 * @access public
	 */
	public function launch()
	{
		global $interface;
		global $configArray;

		if (!UserAccount::isLoggedIn()) {
			include_once 'Login.php';
			MyAccount_Login::launch();
			exit();
		}else{
			$user = UserAccount::getLoggedInUser();
		}

		// Fetch List object
		$list = new UserList();
		$list->id = $_GET['id'];
		$list->find(true);

		// Ensure user have privs to view the list
		if ($list->user_id != $user->id) {
			PEAR_Singleton::raiseError(new PEAR_Error(translate('list_access_denied')));
		}

		// Save Data
		if (isset($_POST['submit'])) {
			if (empty($_POST['title'])) {
				$interface->assign('errorMsg', 'list_edit_name_required');
			} else if ($this->_saveChanges($user, $list)) {
				// After changes are saved, send the user back to an appropriate page
				$nextAction = 'MyList/' . $list->id;
				header(
                    'Location: ' . $configArray['Site']['path'] . '/MyResearch/' .
				$nextAction
				);
				exit();
			} else {
				// List was not edited
				$interface->assign('errorMsg', 'edit_list_fail');
			}
		}

		// Send list to template so title/description can be displayed:
		$interface->assign('list', $list);
		$interface->setTemplate('editList.tpl');
		$interface->display('layout.tpl');
	}
}