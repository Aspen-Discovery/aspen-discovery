<?php
require_once ROOT_DIR . '/services/Greenhouse/UserMerger.php';

class MergeDuplicateBarcodes extends UserMerger {
	function launch() {
		parent::launch();
		global $interface;

		if ($this->importDirExists && isset($_REQUEST['submit'])) {
			$results = $this->mergeUsersWithDuplicateBarcodes();
			$interface->assign('mergeResults', $results);
		}

		$interface->assign('setupErrors', $this->setupErrors);

		$this->display('mergeDuplicateBarcodes.tpl', 'Merge Duplicate Barcodes', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Merge Duplicate Barcodes');

		return $breadcrumbs;
	}

	function mergeUsersWithDuplicateBarcodes() {
		//Get a list of all barcodes that have more than one user for them
		global $aspen_db;
		global $interface;
		$mergeResults = $this->getBlankResult();
		$result = $aspen_db->query('select cat_username, count(*) as numUsers from user where cat_username != "" group by cat_username having numUsers > 1;');
		$allBarcodesWithDuplicates = $result->fetchAll(PDO::FETCH_ASSOC);
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		foreach ($allBarcodesWithDuplicates as $index => &$row) {
			$user = new User();
			$user->cat_username = $row['cat_username'];
			/** @var User[] $allUsersForBarcode */
			$allUsersForBarcode = $user->fetchAll();
			$oldUser = null;
			$newUser = null;
			foreach ($allUsersForBarcode as $tmpUser) {
				if (isset($row['usernames'])) {
					$row['usernames'] .= ", $tmpUser->username";
				} else {
					$row['usernames'] = $tmpUser->username;
				}

				$oldUser = clone($tmpUser);
				$newUser = null;
				$loginResult = $catalog->patronLogin($tmpUser->cat_username, $tmpUser->cat_password);
				if ($loginResult instanceof User) {
					//We got a good user
					if ($loginResult->username != $tmpUser->username) {
						//The internal ILS ID has changed
						$newUser = $loginResult;
					}
				} else {
					$loginResult = $catalog->findNewUser($tmpUser->cat_username);
					if ($loginResult instanceof User) {
						if ($loginResult->username != $tmpUser->username) {
							//The internal ILS ID has changed
							$newUser = $loginResult;
						}
					}
				}

				if ($oldUser != null && $newUser != null) {
					$row['oldUser'] = $oldUser;
					$row['oldUserId'] = $oldUser->username;
					$row['newUser'] = $newUser;
					$row['newUserId'] = $newUser->username;

					//Merge the records
					$mergeResults['numUsersUpdated']++;

					$this->mergeUsers($oldUser, $newUser, $mergeResults);
				}
			}
		}
		$interface->assign('barcodesWithDuplicates', $allBarcodesWithDuplicates);
		return $mergeResults;
	}
}