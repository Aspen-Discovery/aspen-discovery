<?php

require_once ROOT_DIR . '/services/MyAccount/MyPrivacySettings.php';

class MyAccount_MyCookiePreferences extends MyAccount_MyPrivacySettings {
	function updateCookiePreferences($patron) {	
		$cookieResult = $this->updateUserCookiePreferences($patron);
		session_write_close();
		return $cookieResult;
	}

	function updateUserCookiePreferences($patron) {
		$success = true;
		$message = ' ';
		$patron->userCookiePreferenceEssential = 1;
		$patron->userCookiePreferenceAnalytics = isset($_POST['userCookieAnalytics']) ? 1 : 0;
		$patron->userCookiePreferenceLocalAnalytics = isset($_POST['userCookieUserLocalAnalytics']) ? 1 : 0;

		if ($patron->userCookiePreferenceLocalAnalytics == 0) {
			$this->removeLocalAnalyticsTrackingForUser($patron->id);
		}
		if (!$patron->update()) {
			$success = false;
			$message = 'Failed to update cookie preferences.';
		}
		return ['success' => $success, 'message' => $message];
	}

	public function removeLocalAnalyticsTrackingForUser($userId) {
		require_once ROOT_DIR . '/sys/Summon/UserSummonUsage.php';
		require_once ROOT_DIR . '/sys/Gale/UserGaleUsage.php';
		require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
		require_once ROOT_DIR . '/sys/BorrowBox/UserBorrowBoxUsage.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php';
		require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
		require_once ROOT_DIR . '/sys/Ebsco/UserEbscohostUsage.php';
		require_once ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php';
		require_once ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php';
		require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
		require_once ROOT_DIR . '/sys/PalaceProject/UserPalaceProjectUsage.php';
		require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
		require_once ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php';
		require_once ROOT_DIR . '/sys/Events/UserEventsUsage.php';

		$userId = UserAccount::getActiveUserId();

		if ($userId) {
			$userSummonUsage = new UserSummonUsage();
			$userSummonUsage->userId = $userId;
			$userSummonUsage->delete(true);

			$userAxis360Usage = new UserAxis360Usage();
			$userAxis360Usage->userId = $userId;
			$userAxis360Usage->delete(true);

			$userCloudLibraryUsage = new UserCloudLibraryUsage();
			$userCloudLibraryUsage->userId = $userId;
			$userCloudLibraryUsage->delete(true);

			$userEbscoEdsUsage = new UserEbscoEdsUsage();
			$userEbscoEdsUsage->userId = $userId;
			$userEbscoEdsUsage->delete(true);

			$userEbscoHostUsage = new UserEbscohostUsage();
			$userEbscoHostUsage->userId = $userId;
			$userEbscoEdsUsage->delete(true);

			$userHooplaUsage = new UserHooplaUsage();
			$userHooplaUsage->userId = $userId;
			$userHooplaUsage->delete(true);

			$userOpenArchivesUsage = new UserOpenArchivesUsage();
			$userOpenArchivesUsage->userId = $userId;
			$userOpenArchivesUsage->delete(true);
			
			$userOverDriveUsage = new UserOverDriveUsage();
			$userOverDriveUsage->userId = $userId;
			$userOverDriveUsage->delete(true);

			$userBorrowBoxUsage = new UserBorrowBoxUsage();
			$userBorrowBoxUsage->userId = $userId;
			$userBorrowBoxUsage->delete(true);

			$userPalaceProjectUsage = new UserPalaceProjectUsage();
			$userPalaceProjectUsage->userId = $userId;
			$userPalaceProjectUsage->delete(true);

			$userSideLoadUsage = new UserSideLoadUsage();
			$userSideLoadUsage->userId = $userId;
			$userSideLoadUsage->delete(true);

			$userWebsiteUsage = new UserWebsiteUsage();
			$userWebsiteUsage->userId = $userId;
			$userWebsiteUsage->delete(true);

			$userEventsUsage = new UserEventsUsage();
			$userEventsUsage->userId = $userId;
			$userEventsUsage->delete(true);

			$userGaleUsage = new UserGaleUsage();
			$userGaleUsage->userId = $userId;
			$userGaleUsage->delete(true);
		}
	}
}