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
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$interface->assign('readerName', $readerName);

		$minimalInterface = $_REQUEST['minimalInterface'] ?? false;
		$interface->assign('minimalInterface', $minimalInterface);

		$printInterface = isset($_REQUEST['print']) ? filter_var($_REQUEST['print'], FILTER_VALIDATE_BOOLEAN) : false;
		$interface->assign('printInterface', $printInterface);
		$printLibraryName = isset($_REQUEST['printLibraryName']) ? filter_var($_REQUEST['printLibraryName'], FILTER_VALIDATE_BOOLEAN) : false;
		$interface->assign('printLibraryName', $printLibraryName);
		$printLibraryLogo = isset($_REQUEST['printLibraryLogo']) ? filter_var($_REQUEST['printLibraryLogo'], FILTER_VALIDATE_BOOLEAN) : false;
		$interface->assign('printLibraryLogo', $printLibraryLogo);

		global $isAJAX;
		if (!$isAJAX && UserAccount::isLoggedIn()){
			$this->loadAccountSidebarVariables();
			try {
				$messages = UserAccount::getActiveUserObj()->getMessages();
				$interface->assign('messages', $messages);
			}catch (Exception $e){
				//Messages table doesn't exist, ignore
			}
		}
		if ($this->isStandalonePage) {
			$interface->display('standalone-layout.tpl');
		} elseif ($printInterface) {
			$interface->display('print-layout.tpl');
		} else {
			$interface->display('layout.tpl');
		}
	}

	function loadAccountSidebarVariables(){
		global $interface;
		$twoFactor = UserAccount::has2FAEnabledForPType();
		$interface->assign('twoFactorEnabled', $twoFactor);

		// Check to see what sidebar sections to display, if any
		$showUserCirculationModules = $interface->getVariable('showUserCirculationModules');
		$showCurbsidePickups = $interface->getVariable('showCurbsidePickups');
		$showFines = $interface->getVariable('showFines');
		$showRatings = $interface->getVariable('showRatings');
		$showFavorites = $interface->getVariable('showFavorites');
		$enableSavedSearches = $interface->getVariable('enableSavedSearches');
		$displayMaterialsRequest = $interface->getVariable('displayMaterialsRequest');
		$enableReadingHistory = $interface->getVariable('enableReadingHistory');
		$enableCostSavings = $interface->getVariable('enableCostSavings');
		$enablePaymentHistory = $interface->getVariable('enablePaymentHistory');
		$allowAccountLinking = $interface->getVariable('allowAccountLinking');
		$showUserPreferences = $interface->getVariable('showUserPreferences');
		$showUserContactInformation = $interface->getVariable('showUserContactInformation');
		$twoFactorEnabled = $interface->getVariable('twoFactorEnabled');
		$allowPinReset = $interface->getVariable('allowPinReset');
		$userIsStaff = $interface->getVariable('userIsStaff');
		$showSaveEvents = $interface->getVariable('hasEventSettings');

		$user = UserAccount::getLoggedInUser();

		$interface->assign('showResetUsernameLink', $user->showResetUsernameLink());

		$showMyAccount = false;
		if ($showUserCirculationModules || $showCurbsidePickups || $showFines || $showRatings || $showFavorites || $enableSavedSearches || $displayMaterialsRequest || $enableReadingHistory || $enablePaymentHistory || $enableCostSavings) {
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
		$usageByIPAddress->incrementNumBlockedApiRequests();

		http_response_code(403);
		$clientIP = IPAddress::getClientIP();
		echo("<h1>Forbidden</h1><p><strong>API requests from {$clientIP} are forbidden.</strong></p>");
		die();
	}

	protected function grantTokenAccess() : bool {
		$key1 = base64_decode($_SERVER['PHP_AUTH_USER']);
		$key2 = base64_decode($_SERVER['PHP_AUTH_PW']);
		if (empty($key1) || empty($key2)) {
			return false;
		}

		if (method_exists($this, 'getLiDASlug')){
			$lidaSlug = $this->getLiDASlug();
			if (!empty($lidaSlug)) {
				require_once ROOT_DIR . '/sys/AspenLiDA/BrandedAppSetting.php';
				$brandedSettings = new BrandedAppSetting();
				$brandedSettings->slugName = $lidaSlug;
				if ($brandedSettings->find(true)) {
					$allKeysFilledOut = true;
					$keychain = [
						'1' => false,
						'2' => false
					];
					for ($key = 1; $key <= 5; $key += 1) {
						$currentKey = "apiKey" . $key;
						if (empty($brandedSettings->$currentKey)) {
							$allKeysFilledOut = false;
							break;
						}else{
							if ($key1 == $brandedSettings->$currentKey) {
								$keychain['1'] = true;
							}

							if ($key2 == $brandedSettings->$currentKey) {
								$keychain['2'] = true;
							}
						}
					}
					if ($allKeysFilledOut) {
						/** @noinspection PhpConditionAlreadyCheckedInspection */
						if ($keychain['1'] && $keychain['2']) {
							return true;
						}else{
							return false;
						}
					}
				}
			}
		}

		$postData = http_build_query(
			array(
				'key1' => $key1,
				'key2' => $key2
			)
		);
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$curlWrapper = new CurlWrapper();
		if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
			$result = $curlWrapper->curlPostPage($systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=authenticateTokens', $postData);
			if (!empty($result)) {
				$data = json_decode($result, true);
				$isValid = $data['success'];

				if($isValid) {
					return true;
				}
			}

		} else {
			global $configArray;
			$result = $curlWrapper->curlPostPage($configArray['Site']['url'] . '/API/GreenhouseAPI?method=authenticateTokens', $postData);
			if (!empty($result)) {
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