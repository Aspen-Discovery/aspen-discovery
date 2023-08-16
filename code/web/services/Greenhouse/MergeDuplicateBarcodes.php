<?php
require_once ROOT_DIR . '/services/Greenhouse/UserMerger.php';
require_once ROOT_DIR . '/sys/Utils/UserUtils.php';

class MergeDuplicateBarcodes extends UserMerger {
	function launch() {
		parent::launch();
		global $interface;

		$duplicateUsers = $this->getDuplicateUsers();
		$interface->assign('duplicateUsers', $duplicateUsers);

		if ($this->importDirExists && isset($_REQUEST['submit'])) {
			$results = $this->mergeUsersWithDuplicateBarcodes();
			$interface->assign('mergeResults', $results);
		}

		$interface->assign('setupErrors', $this->setupErrors);

		$this->display('mergeDuplicateBarcodes.tpl', 'Merge Duplicate Barcodes', false);
	}

	function getDuplicateUsers() {
		//Get a list of all barcodes that have more than one user for them
		global $aspen_db;
		$result = $aspen_db->query('select ils_barcode, count(*) as numUsers, GROUP_CONCAT(username) as usernames from user where ils_barcode != "" group by ils_barcode having numUsers > 1;');
		return $result->fetchAll(PDO::FETCH_ASSOC);
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
		$result = $aspen_db->query("select ils_barcode, count(*) as numUsers from user where ils_barcode != '' group by ils_barcode having numUsers > 1;");
		$allBarcodesWithDuplicates = $result->fetchAll(PDO::FETCH_ASSOC);
		$catalog = CatalogFactory::getCatalogConnectionInstance();
		foreach ($allBarcodesWithDuplicates as $index => &$row) {
			$user = new User();
			$user->ils_barcode = $row['ils_barcode'];
			/** @var User[] $allUsersForBarcode */
			$allUsersForBarcode = $user->fetchAll();
			$oldUser = null;
			$newUser = null;
			foreach ($allUsersForBarcode as $tmpUser) {
				if (isset($row['usernames'])) {
					$row['usernames'] .= ", $tmpUser->unique_ils_id";
				} else {
					$row['usernames'] = $tmpUser->unique_ils_id;
				}

				$oldUser = clone($tmpUser);
				$newUser = null;
				$loginResult = $catalog->patronLogin($tmpUser->ils_barcode, $tmpUser->cat_password);
				if ($loginResult instanceof User) {
					//We got a good user
					if ($loginResult->unique_ils_id != $tmpUser->unique_ils_id) {
						//The internal ILS ID has changed
						$newUser = $loginResult;
					}
				} else {
					$loginResult = $catalog->findNewUser($tmpUser->ils_barcode);
					if ($loginResult instanceof User) {
						if ($loginResult->unique_ils_id != $tmpUser->unique_ils_id) {
							//The internal ILS ID has changed
							$newUser = $loginResult;
						}
					}
				}

				if ($oldUser != null && $newUser != null) {
					$row['oldUser'] = $oldUser;
					$row['oldUserId'] = $oldUser->unique_ils_id;
					$row['newUser'] = $newUser;
					$row['newUserId'] = $newUser->unique_ils_id;

					//Merge the records
					$mergeResults['numUsersUpdated']++;

					UserUtils::mergeUsers($oldUser, $newUser, $mergeResults);
				}
			}
		}
		$interface->assign('barcodesWithDuplicates', $allBarcodesWithDuplicates);
		return $mergeResults;
	}
}