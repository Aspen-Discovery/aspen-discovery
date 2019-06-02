<?php

require_once ROOT_DIR . "/Action.php";

class MyAccount_Login extends Action
{
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

		//TODO: Determine if these are used
		$returnUrl = isset($_REQUEST['return']) ? $_REQUEST['return'] : '';
		if ($returnUrl != ''){
			header('Location: ' . $returnUrl);
			exit;
		}

		// Assign the followup task to come back to after they login -- note that
		//     we need to check for a pre-existing followup task in case we've
		//     looped back here due to an error (bad username/password, etc.).
		//TODO: Determine if these are used
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
			//TODO: Determine if these are used
			if (isset($_REQUEST['delete'])) {
				$interface->assign('returnUrl', $_SERVER['REQUEST_URI']);
			} else if (isset($_REQUEST['save'])) {
				$interface->assign('returnUrl', $_SERVER['REQUEST_URI']);
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
		$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');

		$catalog = CatalogFactory::getCatalogConnectionInstance();
		$interface->assign('forgotPasswordType', $catalog->getForgotPasswordType());

		$interface->assign('isLoginPage', true);

		$this->display('../MyAccount/login.tpl', 'Login');
	}
}

