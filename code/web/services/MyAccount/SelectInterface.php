<?php

class MyAccount_SelectInterface extends Action {
	function launch() {
		global $interface;
		global $logger;

		$libraries = [];
		$library = new Library();
		$library->createSearchInterface = 1;
		$library->showInSelectInterface = 1;
		$library->orderBy('displayName');
		$library->find();
		while ($library->fetch()) {
			$libraries[$library->libraryId] = [
				'id' => $library->libraryId,
				'displayName' => $library->displayName,
				'library' => clone $library,
				'isLibrary' => true,
			];
		}
		$location = new Location();
		$location->createSearchInterface = 1;
		$location->showInSelectInterface = 1;
		$location->orderBy('displayName');
		$location->find();
		while ($location->fetch()) {
			$libraries['location' . $location->locationId] = [
				'id' => $location->locationId,
				'displayName' => $location->displayName,
				'location' => clone $location,
				'isLibrary' => false,
			];
		}
		$sortLibraries = function ($library1, $library2) {
			return strcasecmp($library1['displayName'], $library2['displayName']);
		};
		usort($libraries, $sortLibraries);
		$interface->assign('libraries', $libraries);

		/** @var Location $locationSingleton */ //global $locationSingleton;
		//$physicalLocation = $locationSingleton->getIPLocation();

		if (isset($_REQUEST['gotoModule'])) {
			$gotoModule = $_REQUEST['gotoModule'];
			$interface->assign('gotoModule', $gotoModule);
		}
		if (isset($_REQUEST['gotoAction'])) {
			$gotoAction = $_REQUEST['gotoAction'];
			$interface->assign('gotoAction', $gotoAction);
		}

		$redirectLibrary = null;
		if (!array_key_exists('noRememberThis', $_REQUEST) || ($_REQUEST['noRememberThis'] === false)) {
			$user = UserAccount::getLoggedInUser();
			if (isset($_REQUEST['library'])) {
				$redirectLibrary = $_REQUEST['library'];
				//}elseif (!is_null($physicalLocation)){
				//	$redirectLibrary = $physicalLocation->libraryId;
			} elseif ($user && isset($user->preferredLibraryInterface) && is_numeric($user->preferredLibraryInterface)) {
				$redirectLibrary = $user->preferredLibraryInterface;
			} elseif (isset($_COOKIE['PreferredLibrarySystem'])) {
				$redirectLibrary = $_COOKIE['PreferredLibrarySystem'];
			}
			$interface->assign('noRememberThis', false);
		} else {
			$interface->assign('noRememberThis', true);
		}

		if ($redirectLibrary != null) {
			$logger->log("Selected library $redirectLibrary", Logger::LOG_DEBUG);

			if ($libraries[$redirectLibrary]['isLibrary']) {
				/** @var Library $selectedLibrary */
				$selectedLibrary = $libraries[$redirectLibrary]['library'];
				if (!empty($selectedLibrary->baseUrl)) {
					$baseUrl = $selectedLibrary->baseUrl;
				} else {
					global $configArray;
					$baseUrl = $configArray['Site']['url'];
					$urlPortions = explode('://', $baseUrl);
					//Get rid of extra portions of the url
					$subdomain = $selectedLibrary->subdomain;
					if (strpos($urlPortions[1], 'opac2') !== false) {
						$urlPortions[1] = str_replace('opac2.', '', $urlPortions[1]);
						$subdomain .= '2';
					}
					$urlPortions[1] = str_replace('opac.', '', $urlPortions[1]);
					$baseUrl = $urlPortions[0] . '://' . $subdomain . '.' . $urlPortions[1];
				}
				$baseUrl .= '?branch=';
			} else {
				/** @var Location $selectedLocation */
				$selectedLocation = $libraries[$redirectLibrary]['location'];
				global $configArray;
				$baseUrl = $configArray['Site']['url'];
				$branch = '';
				$urlPortions = explode('://', $baseUrl);
				//Get rid of extra portions of the url
				$subdomain = $selectedLocation->subdomain;
				$buildUrl = true;
				if (empty($subdomain)) {
					$library = $selectedLocation->getParentLibrary();
					if (!empty($library->baseUrl)) {
						$baseUrl = $library->baseUrl;
						$branch = $selectedLocation->code;
						$buildUrl = false;
					} else {
						$subdomain = $selectedLocation->getParentLibrary()->subdomain;
						$branch = $selectedLocation->code;
					}
				}
				if ($buildUrl) {
					if (strpos($urlPortions[1], 'opac2') !== false) {
						$urlPortions[1] = str_replace('opac2.', '', $urlPortions[1]);
						$subdomain .= '2';
					}
					$urlPortions[1] = str_replace('opac.', '', $urlPortions[1]);
					$baseUrl = $urlPortions[0] . '://' . $subdomain . '.' . $urlPortions[1];
				}
				if (!empty($branch)) {
					$baseUrl .= '?branch=' . $branch;
				} else {
					$baseUrl .= '?branch=';
				}
			}
			if ($gotoModule) {
				$baseUrl .= '/' . $gotoModule;
			}
			if ($gotoAction) {
				$baseUrl .= '/' . $gotoAction;
			}
			if (isset($_REQUEST['rememberThis']) && isset($_REQUEST['submit'])) {
				if ($user) {
					$user->preferredLibraryInterface = $redirectLibrary;
					$user->update();
					$_SESSION['userinfo'] = serialize($user);
				}
				//Set a cookie to remember the location when not logged in
				//Remember for a year
				setcookie('PreferredLibrarySystem', $redirectLibrary, time() + 60 * 60 * 24 * 365, '/');
			}

			header('Location:' . $baseUrl);
			die();
		}

		// Display Page
		$this->display('selectInterface.tpl', 'Select Library Catalog', '');
	}

	function getBreadcrumbs(): array {
		return [];
	}
}