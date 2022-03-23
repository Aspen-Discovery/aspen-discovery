<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';
class MapAndMergeUsers extends Admin_Admin
{
	function launch()
	{
		global $interface;
		global $serverName;
		$importPath = '/data/aspen-discovery/' . $serverName . '/import/';
		$importDirExists = false;
		$setupErrors = [];
		if (!file_exists($importPath)){
			if (!mkdir($importPath, 0774, true)){
				$setupErrors[] = 'Could not create import directory';
			}else{
				chgrp($importPath, 'aspen_apache');
				chmod($importPath, 0774);
				$importDirExists = true;
			}
		}else{
			$importDirExists = true;
		}

		if ($importDirExists) {
			if (!file_exists($importPath . 'users_map.csv')) {
				$setupErrors[] = "users_map.csv file did not exist in $importPath";
			}
		}

		if (isset($_REQUEST['submit'])) {
			$results = $this->remapAndMergeUsers($importPath);
			$interface->assign('mergeResults', $results);
		}

		$interface->assign('setupErrors', $setupErrors);

		$this->display('mapAndMergeUsers.tpl', 'Map and Merge Users',false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Map and Merge Users');

		return $breadcrumbs;
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

	private function remapAndMergeUsers(string $importPath) : array
	{
		$result = [
			'numUsersUpdated' => 0,
			'numUsersMerged' => 0,
			'numUnmappedUsers' => 0,
			'numListsMoved' => 0,
			'numReadingHistoryEntriesMoved' => 0,
			'numRolesMoved' => 0,
			'numNotInterestedMoved' => 0,
		];
		set_time_limit(0);
		ini_set('memory_limit', '4G');
		$allUsers = new User();
		$result['numUsersInAspen'] = $allUsers->count();

		$userMappingsFhnd = fopen($importPath . 'users_map.csv', 'r');
		$mappingLine = fgetcsv($userMappingsFhnd);
		$userMappings = [];
		while ($mappingLine) {
			if (!empty($mappingLine) && count($mappingLine) >= 2) {
				$sourceId = $mappingLine[1];
				if (substr($sourceId, 0, 1) == 'p'){
					//This is a Sierra/Millennium user, remove the p and the check digit
					$sourceId = substr($sourceId, 1, strlen($sourceId) -2);
				}
				$destId = $mappingLine[0];
				$userMappings[trim($sourceId)] = trim($destId);
			}
			$mappingLine = fgetcsv($userMappingsFhnd);
		}
		fclose($userMappingsFhnd);
		$result['numUsersInMap'] = count($userMappings);

		foreach ($userMappings as $originalUsername => $newUsername) {
			$originalUser = new User();
			$originalUser->username = $originalUsername;
			if ($originalUser->find(true)){
				$result['numUnmappedUsers']++;

				$newUser = new User();
				$newUser->username = $newUsername;
				if ($newUser->find(true)){
					//We have both the new and old record in the database, they need to be merged.
					//We will move everything from the new user to the old user, update the old user with the
					// new username and then delete the new username.
					require_once ROOT_DIR . '/sys/UserLists/UserList.php';
					$listsForUser = new UserList();
					$listsForUser->user_id = $newUser->id;
					$listsForUser->find();
					while ($listsForUser->fetch()){
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
					while ($readingHistoryEntries->fetch()){
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
					while ($ratingsAndReviews->fetch()){
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
					while ($userRoles->fetch()){
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
					while ($userNotInterested->fetch()){
						$clonedUserNotInterested = clone $userNotInterested;
						$clonedUserNotInterested->userId = $originalUser->id;
						$clonedUserNotInterested->update();
						$clonedUserNotInterested->__destruct();
						$clonedUserNotInterested = null;
						$result['numNotInterestedMoved']++;
					}

					$result['numUsersMerged']++;
				}else{
					//We just have the old record in the database, we can just update the username and reset
					$originalUser->username = $newUsername;
					$originalUser->update();
					$result['numUsersUpdated']++;
				}

				$newUser->__destruct();
				$newUser = null;
			}else{
				//Skip this user since they never used Aspen
			}
			$originalUser->__destruct();
			$originalUser = null;
		}

		//Now that the updates have been made, clear sessions

		return $result;
	}
}