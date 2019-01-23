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

class MyAccount_Login extends Action
{
	function __construct()
	{
	}

	function launch($msg = null)
	{
		global $interface;
		global $module;
		global $action;
		global $library;
		global $configArray;

		// We should never access this module directly -- this is called by other
		// actions as a support function.  If accessed directly, just redirect to
		// the MyAccount home page.
		if ($module == 'MyAccount' && $action == 'Login') {
			header('Location: /MyAccount/Home');
			die();
		}

		$returnUrl = isset($_REQUEST['return']) ? $_REQUEST['return'] : '';
		if ($returnUrl != ''){
			header('Location: ' . $returnUrl);
			exit;
		}

		// Assign the followup task to come back to after they login -- note that
		//     we need to check for a pre-existing followup task in case we've
		//     looped back here due to an error (bad username/password, etc.).
		$followup = isset($_REQUEST['followup']) ?  strip_tags($_REQUEST['followup']) : $action;

		// Don't go to the trouble if we're just logging in to the Home action
		if ($followup != 'Home' || (isset($_REQUEST['followupModule']) && isset($_REQUEST['followupAction']))) {
			$interface->assign('followup', $followup);
			$interface->assign('followupModule', isset($_REQUEST['followupModule']) ? strip_tags($_REQUEST['followupModule']) : $module);

			// Special case -- if user is trying to view a private list, we need to
			// attach the list ID to the action:
			$finalAction = $action;
			if ($finalAction == 'MyList') {
				if (isset($_GET['id'])){
					$finalAction .= '/' . $_GET['id'];
				}
			}
			$interface->assign('followupAction', isset($_REQUEST['followupAction']) ? $_REQUEST['followupAction'] : $finalAction);

			// If we have a save or delete action, create the appropriate recordId
			//     parameter.  If we've looped back due to user error and already have
			//     a recordId parameter, remember it for future reference.
			if (isset($_REQUEST['delete'])) {
				$interface->assign('returnUrl', $_SERVER['REQUEST_URI']);
				/*$mode = !isset($_REQUEST['mode']) ?
                    '' : '&mode=' . urlencode($_REQUEST['mode']);
				$interface->assign('recordId', 'delete=' .
				urlencode($_REQUEST['delete']) . $mode);*/
			} else if (isset($_REQUEST['save'])) {
				$interface->assign('returnUrl', $_SERVER['REQUEST_URI']);
				/*$mode = !isset($_REQUEST['mode']) ?
                    '' : '&mode=' . urlencode($_REQUEST['mode']);
				$interface->assign('recordId', 'save=' .
				urlencode($_REQUEST['save']) . $mode);*/
			} else if (isset($_REQUEST['recordId'])) {
				$interface->assign('returnUrl', $_REQUEST['recordId']);
			}

			// comments and tags also need to be preserved if present
			if (isset($_REQUEST['comment'])) {
				$interface->assign('comment', $_REQUEST['comment']);
			}

			// preserve card Number for Masquerading
			if (isset($_REQUEST['cardNumber'])) {
				$interface->assign('cardNumber', $_REQUEST['cardNumber']);
				$interface->assign('followupModule', 'MyAccount');
				$interface->assign('followupAction', 'Masquerade');
			}

		}
		$interface->assign('message', $msg);
		if (isset($_REQUEST['username'])) {
			$interface->assign('username', $_REQUEST['username']);
		}
		if (isset($library)){
			$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
			$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
			$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');
		}else{
			$interface->assign('enableSelfRegistration', 0);
			$interface->assign('usernameLabel', 'Your Name');
			$interface->assign('passwordLabel', 'Library Card Number');
		}
		if ($configArray['Catalog']['ils'] == 'Horizon' || $configArray['Catalog']['ils'] == 'Symphony'){
			$interface->assign('showForgotPinLink', true);
			$catalog = CatalogFactory::getCatalogConnectionInstance();
			$useEmailResetPin = $catalog->checkFunction('emailResetPin');
			$interface->assign('useEmailResetPin', $useEmailResetPin);
		}

		$interface->assign('isLoginPage', true);

		$this->display('../MyAccount/login.tpl', 'Login');
	}
}

