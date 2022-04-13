<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
abstract class UserMerger extends Admin_Admin
{
	protected $importPath;
	protected $importDirExists;
	protected $setupErrors;
	function launch()
	{
		global $serverName;
		$this->importPath = '/data/aspen-discovery/' . $serverName . '/import/';
		$this->importDirExists = false;
		$this->setupErrors = [];
		if (!file_exists($this->importPath)){
			if (!mkdir($this->importPath, 0774, true)){
				$this->setupErrors[] = 'Could not create import directory';
			}else{
				chgrp($this->importPath, 'aspen_apache');
				chmod($this->importPath, 0774);
				$this->importDirExists = true;
			}
		}else{
			$this->importDirExists = true;
		}

		if ($this->importDirExists) {
			if (!file_exists($this->importPath . 'users_map.csv')) {
				$this->setupErrors[] = "users_map.csv file did not exist in $this->importPath";
			}
		}
	}

	function getActiveAdminSection() : string
	{
		return 'greenhouse';
	}

	function canView() : bool
	{
		if (UserAccount::isLoggedIn()){
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin'){
				return true;
			}
		}
		return false;
	}

	function getBlankResult() : array{
		return [
			'numUsersUpdated' => 0,
			'numUsersMerged' => 0,
			'numUnmappedUsers' => 0,
			'numListsMoved' => 0,
			'numReadingHistoryEntriesMoved' => 0,
			'numRolesMoved' => 0,
			'numNotInterestedMoved' => 0,
			'numLinkedPrimaryUsersMoved' => 0,
			'numLinkedUsersMoved' => 0,
			'numSavedSearchesMoved' => 0,
			'numSystemMessageDismissalsMoved' => 0,
			'numPlacardDismissalsMoved' => 0,
			'numMaterialsRequestsMoved' => 0,
			'numMaterialsRequestsAssignmentsMoved' => 0,
			'numUserMessagesMoved' => 0,
			'numUserPaymentsMoved' => 0,
			'numUserStaffSettingsMoved' => 0,
			'numRatingsReviewsMoved' => 0,
			'errors' => []
		];
	}
	function mergeUsers(User $originalUser, User $newUser, &$result){
		try {
			//We have both the new and old record in the database, they need to be merged.
			//We will move everything from the new user to the old user, update the old user with the
			// new username and then delete the new username.
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$listsForUser = new UserList();
			$listsForUser->user_id = $newUser->id;
			$listsForUser->find();
			while ($listsForUser->fetch()) {
				$clonedList = clone $listsForUser;
				$clonedList->user_id = $originalUser->id;
				$clonedList->update();
				$clonedList->__destruct();
				$clonedList = null;
				$result['numListsMoved']++;
			}
			$listsForUser->__destruct();
			$listsForUser = null;

			//Reading History
			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$readingHistoryEntries = new ReadingHistoryEntry();
			$readingHistoryEntries->userId = $newUser->id;
			$readingHistoryEntries->find();
			while ($readingHistoryEntries->fetch()) {
				$clonedReadingHistoryEntry = clone $readingHistoryEntries;
				$clonedReadingHistoryEntry->userId = $originalUser->id;
				$clonedReadingHistoryEntry->update();
				$clonedReadingHistoryEntry->__destruct();
				$clonedReadingHistoryEntry = null;
				$result['numReadingHistoryEntriesMoved']++;
			}

			//Ratings & Reviews
			require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
			$ratingsAndReviews = new UserWorkReview();
			$ratingsAndReviews->userId = $newUser->id;
			$ratingsAndReviews->find();
			while ($ratingsAndReviews->fetch()) {
				$clonedRatingsAndReviews = clone $ratingsAndReviews;
				$clonedRatingsAndReviews->userId = $originalUser->id;
				$clonedRatingsAndReviews->update();
				$clonedRatingsAndReviews->__destruct();
				$clonedRatingsAndReviews = null;
				$result['numRatingsReviewsMoved']++;
			}

			//Roles
			require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
			$userRoles = new UserRoles();
			$userRoles->userId = $newUser->id;
			$userRoles->find();
			while ($userRoles->fetch()) {
				$clonedUserRoles = clone $userRoles;
				$clonedUserRoles->userId = $originalUser->id;
				$clonedUserRoles->update();
				$clonedUserRoles->__destruct();
				$clonedUserRoles = null;
				$result['numRolesMoved']++;
			}

			require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
			$userNotInterested = new NotInterested();
			$userNotInterested->userId = $newUser->id;
			$userNotInterested->find();
			while ($userNotInterested->fetch()) {
				$clonedUserNotInterested = clone $userNotInterested;
				$clonedUserNotInterested->userId = $originalUser->id;
				$clonedUserNotInterested->update();
				$clonedUserNotInterested->__destruct();
				$clonedUserNotInterested = null;
				$result['numNotInterestedMoved']++;
			}

			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink = new UserLink();
			$userLink->primaryAccountId = $newUser->id;
			$userLink->find();
			while ($userLink->fetch()) {
				try {
					$clonedUserLink = clone $userLink;
					$clonedUserLink->primaryAccountId = $originalUser->id;
					$clonedUserLink->update();
					$clonedUserLink->__destruct();
					$clonedUserLink = null;
					$result['numLinkedPrimaryUsersMoved']++;
				} catch (Exception $e) {
					$result['errors'][] = "Error updating user link $e";
				}
			}

			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink = new UserLink();
			$userLink->linkedAccountId = $newUser->id;
			$userLink->find();
			while ($userLink->fetch()) {
				try {
					$clonedUserLink = clone $userLink;
					$clonedUserLink->linkedAccountId = $originalUser->id;
					$clonedUserLink->update();
					$clonedUserLink->__destruct();
					$clonedUserLink = null;
					$result['numLinkedUsersMoved']++;
				} catch (Exception $e) {
					$result['errors'][] = "Error updating user link $e";
				}
			}

			require_once ROOT_DIR . '/sys/SearchEntry.php';
			$savedSearches = new SearchEntry();
			$savedSearches->user_id = $newUser->id;
			$savedSearches->find();
			while ($savedSearches->fetch()) {
				$clonedSavedSearch = clone $savedSearches;
				$clonedSavedSearch->user_id = $originalUser->id;
				$clonedSavedSearch->update();
				$clonedSavedSearch->__destruct();
				$clonedSavedSearch = null;
				$result['numSavedSearchesMoved']++;
			}

			require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
			$systemMessageDismissals = new SystemMessageDismissal();
			$systemMessageDismissals->userId = $newUser->id;
			$systemMessageDismissals->find();
			while ($systemMessageDismissals->fetch()) {
				try {
					$clonedSystemMessageDismissals = clone $systemMessageDismissals;
					$clonedSystemMessageDismissals->userId = $originalUser->id;
					$clonedSystemMessageDismissals->update();
					$clonedSystemMessageDismissals->__destruct();
					$clonedSystemMessageDismissals = null;
					$result['numSystemMessageDismissalsMoved']++;
				}catch (Exception $e){
					$result['errors'][] = "Error moving system message dismissal from new User {$newUser->id} to original User {$originalUser->id} $e";
				}
			}

			require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php';
			$placardDismissals = new PlacardDismissal();
			$placardDismissals->userId = $newUser->id;
			$placardDismissals->find();
			while ($placardDismissals->fetch()) {
				try {
					$clonedPlacardDismissals = clone $placardDismissals;
					$clonedPlacardDismissals->userId = $originalUser->id;
					$clonedPlacardDismissals->update();
					$clonedPlacardDismissals->__destruct();
					$clonedPlacardDismissals = null;
					$result['numPlacardDismissalsMoved']++;
				}catch (Exception $e){
					$result['errors'][] = "Error moving placard dismissal from new User {$newUser->id} to original User {$originalUser->id} $e";
				}
			}

			require_once ROOT_DIR . '/sys/MaterialsRequest.php';
			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = $newUser->id;
			$materialsRequests->find();
			while ($materialsRequests->fetch()) {
				$clonedMaterialsRequests = clone $materialsRequests;
				$clonedMaterialsRequests->createdBy = $originalUser->id;
				$clonedMaterialsRequests->update();
				$clonedMaterialsRequests->__destruct();
				$clonedMaterialsRequests = null;
				$result['numMaterialsRequestsMoved']++;
			}

			require_once ROOT_DIR . '/sys/MaterialsRequest.php';
			$materialsRequests = new MaterialsRequest();
			$materialsRequests->assignedTo = $newUser->id;
			$materialsRequests->find();
			while ($materialsRequests->fetch()) {
				$clonedMaterialsRequests = clone $materialsRequests;
				$clonedMaterialsRequests->assignedTo = $originalUser->id;
				$clonedMaterialsRequests->update();
				$clonedMaterialsRequests->__destruct();
				$clonedMaterialsRequests = null;
				$result['numMaterialsRequestsAssignmentsMoved']++;
			}

			require_once ROOT_DIR . '/sys/Account/UserMessage.php';
			$userMessages = new UserMessage();
			$userMessages->userId = $newUser->id;
			$userMessages->find();
			while ($userMessages->fetch()) {
				$clonedUserMessages = clone $userMessages;
				$clonedUserMessages->userId = $originalUser->id;
				$clonedUserMessages->update();
				$clonedUserMessages->__destruct();
				$clonedUserMessages = null;
				$result['numUserMessagesMoved']++;
			}

			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$userPayments = new UserPayment();
			$userPayments->userId = $newUser->id;
			$userPayments->find();
			while ($userPayments->fetch()) {
				$clonedUserPayments = clone $userPayments;
				$clonedUserPayments->userId = $originalUser->id;
				$clonedUserPayments->update();
				$clonedUserPayments->__destruct();
				$clonedUserPayments = null;
				$result['numUserPaymentsMoved']++;
			}

			require_once ROOT_DIR . '/sys/Account/UserStaffSettings.php';
			$userStaffSettings = new UserStaffSettings();
			$userStaffSettings->userId = $newUser->id;
			$userStaffSettings->find();
			while ($userStaffSettings->fetch()) {
				$clonedUserStaffSettings = clone $userStaffSettings;
				$clonedUserStaffSettings->userId = $originalUser->id;
				$clonedUserStaffSettings->update();
				$clonedUserStaffSettings->__destruct();
				$clonedUserStaffSettings = null;
				$result['numUserStaffSettingsMoved']++;
			}

			$newUsername = $newUser->username;
			$newUser->delete();
			$originalUser->username = $newUsername;
			$originalUser->update();

			$result['numUsersMerged']++;
		}catch (Exception $e){
			$result['errors'][] = "Error moving user data from new User {$newUser->id} to original User {$originalUser->id} $e";
		}
	}
}