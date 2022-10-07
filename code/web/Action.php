<?php

// Abstract Base Class for Actions
require_once ROOT_DIR . '/sys/Breadcrumb.php';
abstract class Action
{
	private $isStandalonePage;
	function __construct($isStandalonePage = false) {
		$this->isStandalonePage = $isStandalonePage;
		global $interface;
		if ($interface) {
			$interface->assign('isStandalonePage', $isStandalonePage);
		}
	}

	abstract function launch();

	/**
	 * @param string $mainContentTemplate Name of the SMARTY template file for the main content of the Full Record View Pages
	 * @param string $pageTitle What to display is the html title tag
	 * @param string $sidebarTemplate Sets the sidebar template, set to false or empty string for no sidebar
	 * @param boolean $translateTitle
	 */
	function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Search/home-sidebar.tpl', $translateTitle = true) {
		global $interface;
		if (!empty($sidebarTemplate)) $interface->assign('sidebar', $sidebarTemplate);
		$interface->assign('breadcrumbs', $this->getBreadcrumbs());
		$interface->setTemplate($mainContentTemplate);
		$interface->setPageTitle($pageTitle, $translateTitle, false, true);
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		global $isAJAX;
		if (!$isAJAX && UserAccount::isLoggedIn()){
			try {
				$messages = UserAccount::getActiveUserObj()->getMessages();
				$interface->assign('messages', $messages);
			}catch (Exception $e){
				//Messages table doesn't exist, ignore
			}
		}
		if ($this->isStandalonePage){
			$interface->display('standalone-layout.tpl');
		}else {
			$interface->display('layout.tpl');
		}
	}

	function loadAccountSidebarVariables(){
		global $interface;
		// Check to see what sidebar sections to display, if any
		$showUserCirculationModules = $interface->getVariable('showUserCirculationModules');
		$showCurbsidePickups = $interface->getVariable('showCurbsidePickups');
		$showFines = $interface->getVariable('showFines');
		$showRatings = $interface->getVariable('showRatings');
		$showFavorites = $interface->getVariable('showFavorites');
		$enableSavedSearches = $interface->getVariable('enableSavedSearches');
		$displayMaterialsRequest = $interface->getVariable('displayMaterialsRequest');
		$enableReadingHistory = $interface->getVariable('enableReadingHistory');
		$allowAccountLinking = $interface->getVariable('allowAccountLinking');
		$showUserPreferences = $interface->getVariable('showUserPreferences');
		$showUserContactInformation = $interface->getVariable('showUserContactInformation');
		$twoFactorEnabled = $interface->getVariable('twoFactorEnabled');
		$allowPinReset = $interface->getVariable('allowPinReset');
		$userIsStaff = $interface->getVariable('userIsStaff');


		$user = UserAccount::getLoggedInUser();
		$showMyAccount = false;
		if ($showUserCirculationModules || $showCurbsidePickups || $showFines || $showRatings || $showFavorites || $enableSavedSearches || $displayMaterialsRequest || $enableReadingHistory) {
			$showMyAccount = true;
		}

		$showAccountSettings = false;
		if ($allowAccountLinking || $showUserPreferences || $showUserContactInformation || $user->showMessagingSettings() || $twoFactorEnabled || $allowPinReset || $userIsStaff || $showUserCirculationModules) {
			$showAccountSettings = true;
		}

		$interface->assign('showMyAccount', $showMyAccount);
		$interface->assign('showAccountSettings', $showAccountSettings);
	}

	function setShowCovers() {
		global $interface;
		// Hide Covers when the user has set that setting on a Search Results Page
		// this is the same setting as used by the MyAccount Pages for now.
		$showCovers = true;
		if (isset($_REQUEST['showCovers'])) {
			$showCovers = ($_REQUEST['showCovers'] == 'on' || $_REQUEST['showCovers'] == 'true');
			if (isset($_SESSION)) $_SESSION['showCovers'] = $showCovers;
		} elseif (isset($_SESSION['showCovers'])) {
			$showCovers = $_SESSION['showCovers'];
		}
		$interface->assign('showCovers', $showCovers);
	}

	protected function forbidAPIAccess()
	{
		global $aspenUsage;
		$aspenUsage->blockedApiRequests++;
		$aspenUsage->update();
		global $usageByIPAddress;
		try{
			$usageByIPAddress->numBlockedApiRequests++;
			$usageByIPAddress->update();
		} catch (Exception $e) {
			//Table does not exist yet
		}

		http_response_code(403);
		$clientIP = IPAddress::getClientIP();
		echo("<h1>Forbidden</h1><p><strong>API requests from {$clientIP} are forbidden.</strong></p>");
		die();
	}

	protected function grantTokenAccess()
	{
		//TODO: store if the authentication token is valid or not for 5 minutes to help improve performance
		global $memCache;

		$postData = http_build_query(
			array(
				'key1' => base64_decode($_SERVER['PHP_AUTH_USER']),
				'key2' => base64_decode($_SERVER['PHP_AUTH_PW'])
			)
		);
		$opts = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => 'Content-Type: application/x-www-form-urlencoded',
				'content' => $postData
			)
		);
		$context  = stream_context_create($opts);
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
			if ($result = file_get_contents($systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=authenticateTokens', false, $context)) {
				$data = json_decode($result, true);
				$isValid = $data['success'];

				if($isValid) {
					return true;
				}
			}
		} else {
			global $configArray;
			if ($result = file_get_contents($configArray['Site']['url'] . '/API/GreenhouseAPI?method=authenticateTokens', false, $context)) {
				$data = json_decode($result, true);
				$isValid = $data['success'];

				if($isValid) {
					return true;
				}
			}
		}
		return false;
	}

	abstract function getBreadcrumbs() : array;
}