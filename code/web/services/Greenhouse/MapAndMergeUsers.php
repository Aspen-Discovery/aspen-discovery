<?php
require_once ROOT_DIR . '/services/Greenhouse/UserMerger.php';
class MapAndMergeUsers extends UserMerger
{
	function launch()
	{
		parent::launch();
		global $interface;

		if ($this->importDirExists) {
			if (!file_exists($this->importPath . 'users_map.csv')) {
				$this->setupErrors[] = "users_map.csv file did not exist in $this->importPath";
			}
		}

		if ($this->importDirExists && isset($_REQUEST['submit'])) {
			$results = $this->remapAndMergeUsers($this->importPath);
			$interface->assign('mergeResults', $results);
		}

		$interface->assign('setupErrors', $this->setupErrors);

		$this->display('mapAndMergeUsers.tpl', 'Map and Merge Users',false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Map and Merge Users');

		return $breadcrumbs;
	}

	function remapAndMergeUsers(string $importPath) : array
	{
		$result = $this->getBlankResult();
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
					$this->mergeUsers($originalUser, $newUser, $result);
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
		if ($result['numUsersUpdated'] > 0 || $result['numUsersMerged'] > 0){
			$session = new Session();
			$session->deleteAll();
		}

		return $result;
	}
}