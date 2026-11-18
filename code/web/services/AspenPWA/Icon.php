<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/API/SystemAPI.php';
require_once ROOT_DIR . '/sys/AspenPWA/Setting.php';

/**
 * This class exists because PWABuilder.com did not except
 * dynamic urls for the manifest. Rather then modify how
 * SystemAPI's get logofile works we are going to point
 * /pwa-icon.png to this function
 */
class AspenPWA_Icon extends Action {

	function launch()
	{
		$settings = AspenPWASetting::getSettingsForCurrentLibrary();
		$success = true;
		//TODO we should return an error code instead of 200
		// if we have no settings
		if($settings)
		{
			$_REQUEST['themeId'] = $settings->themeId;
		} else {
			http_response_code(404);
			return;
		}

		//Use the icon uploaded to the PWA settings and fall back to the theme logo
		if (!empty($settings->icon)) {
			global $configArray;
			$fullPath = $configArray['Site']['local'] . '/files/original/' . basename($settings->icon);
			if (is_readable($fullPath)) {
				header('Content-Type: image/png');
				header('Content-Length: ' . filesize($fullPath));
				readfile($fullPath);
				die();
			}
			global $logger;
			$logger->log("PWA icon $fullPath could not be read, using the theme logo", Logger::LOG_ERROR);
		}

		$api = new SystemAPI('internal');
		$_REQUEST['type'] = "logoApp";
		$api->getLogoFile();
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
?>