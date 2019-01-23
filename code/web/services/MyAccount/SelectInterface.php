<?php
/**
 * Allow the user to select an interface to use to access the site.
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/8/13
 * Time: 2:32 PM
 */

class MyAccount_SelectInterface extends Action{
	function launch(){
		global $interface;
		global $logger;

		$libraries = array();
		$library = new Library();
		$library->orderBy('displayName');
		$library->find();
		while ($library->fetch()){
			$libraries[$library->libraryId] = array(
				'id' => $library->libraryId,
				'displayName' => $library->displayName,
				'subdomain' => $library->subdomain,
			);
		}
		$interface->assign('libraries', $libraries);

		global $locationSingleton;
		$physicalLocation = $locationSingleton->getActiveLocation();

		if (isset($_REQUEST['gotoModule'])){
			$gotoModule = $_REQUEST['gotoModule'];
			$interface->assign('gotoModule', $gotoModule);
		}
		if (isset($_REQUEST['gotoAction'])){
			$gotoAction = $_REQUEST['gotoAction'];
			$interface->assign('gotoAction', $gotoAction);
		}

		$redirectLibrary = null;
		$user = UserAccount::getLoggedInUser();
		if (isset($_REQUEST['library'])){
			$redirectLibrary = $_REQUEST['library'];
		}elseif (!is_null($physicalLocation)){
			$redirectLibrary = $physicalLocation->libraryId;
		}elseif ($user && isset($user->preferredLibraryInterface) && is_numeric($user->preferredLibraryInterface)){
			$redirectLibrary = $user->preferredLibraryInterface;
		}elseif (isset($_COOKIE['PreferredLibrarySystem'])){
			$redirectLibrary = $_COOKIE['PreferredLibrarySystem'];
		}
		if ($redirectLibrary != null){
			$logger->log("Selected library $redirectLibrary", PEAR_LOG_DEBUG);
			$selectedLibrary = $libraries[$redirectLibrary];
			global $configArray;
			$baseUrl = $configArray['Site']['url'];
			$urlPortions = explode('://', $baseUrl);
			//Get rid of extra portions of the url
			$subdomain = $selectedLibrary['subdomain'];
			if (strpos($urlPortions[1], 'opac2') !== false){
				$urlPortions[1] = str_replace('opac2.', '', $urlPortions[1]);
				$subdomain .= '2';
			}
			$urlPortions[1] = str_replace('opac.', '', $urlPortions[1]);
			$baseUrl = $urlPortions[0] . '://' . $subdomain . '.' . $urlPortions[1];
			if ($gotoModule){
				$baseUrl .= '/' . $gotoModule;
			}
			if ($gotoAction){
				$baseUrl .= '/' . $gotoAction;
			}
			if (isset($_REQUEST['rememberThis']) && isset($_REQUEST['submit'])){
				if ($user){
					$user->preferredLibraryInterface = $redirectLibrary;
					$user->update();
					$_SESSION['userinfo'] = serialize($user);
				}
				//Set a cookie to remember the location when not logged in
				//Remember for a year
				setcookie('PreferredLibrarySystem', $redirectLibrary, time() + 60*60*24*365, '/');
			}

			header('Location:' . $baseUrl);
			die();
		}

		//Build the actual view
		$interface->setTemplate('selectInterface.tpl');
		$interface->setPageTitle('Select Library Catalog');

		// Display Page
		$interface->display('layout.tpl');
	}
}