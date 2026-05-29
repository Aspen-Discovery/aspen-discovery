<?php

require_once ROOT_DIR . '/JSON_Action.php';

class AspenPWA_AJAX extends JSON_Action {
	function saveNotificationPushToken()
	{
		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$api = new UserAPI('internal');
		return $api->saveNotificationPushToken();
	}

	function deleteNotificationPushToken()
	{
		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$api = new UserAPI('internal');
		// The method is already checking that the token
		// belongs to the current logged in user
		return $api->deleteNotificationPushToken();
	}

	function setNotificationPreference()
	{
		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$api = new UserAPI('internal');
		return $api->setNotificationPreference();
	}
}