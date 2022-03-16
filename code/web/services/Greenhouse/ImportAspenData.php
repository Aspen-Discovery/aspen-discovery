<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
class Greenhouse_ImportAspenData extends Admin_Admin
{
	function launch(){
		global $interface;
		global $serverName;
		$importPath = '/data/aspen-discovery/' . $serverName . '/import/';
		$importDirExists = false;
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

		if (isset($_REQUEST['submit'])){
			set_time_limit(0);

			$importResults = [
				'success' => false,
				'message' => 'Nothing was imported'
			];

			$message = '';
			$success = true;

			$overrideExisting = $_REQUEST['overrideExisting'];

			//Look for a mapping between old library names and new library names
			$libraryMappings = [];
			if (file_exists($importPath . 'library_map.csv')){
				$libraryMappingsFhnd = fopen($importPath . 'library_map.csv', 'r');
				$mappingLine = fgetcsv($libraryMappingsFhnd);
				while ($mappingLine){
					if (!empty($mappingLine) && count($mappingLine) >= 2) {
						$libraryMappings[trim($mappingLine[0])] = trim($mappingLine[1]);
					}
					$mappingLine = fgetcsv($libraryMappingsFhnd);
				}
				fclose($libraryMappingsFhnd);
			}
			//Look for a mapping between old location names and new location names
			$locationMappings = [];
			if (file_exists($importPath . 'location_map.csv')){
				$locationMappingsFhnd = fopen($importPath . 'location_map.csv', 'r');
				$mappingLine = fgetcsv($locationMappingsFhnd);
				while ($mappingLine){
					if (!empty($mappingLine) && count($mappingLine) >= 2) {
						$locationMappings[trim($mappingLine[0])] = trim($mappingLine[1]);
					}
					$mappingLine = fgetcsv($locationMappingsFhnd);
				}
				fclose($locationMappingsFhnd);
			}
			$userMappings = [];
			if (file_exists($importPath . 'users_map.csv')){
				$userMappingsFhnd = fopen($importPath . 'users_map.csv', 'r');
				$mappingLine = fgetcsv($userMappingsFhnd);
				while ($mappingLine){
					if (!empty($mappingLine) && count($mappingLine) >= 2) {
						$sourceId = $mappingLine[1];
						$sourceId = str_replace('p', '', $sourceId);
						$destId = $mappingLine[0];
						$userMappings[trim($sourceId)] = trim($destId);
					}
					$mappingLine = fgetcsv($userMappingsFhnd);
				}
				fclose($userMappingsFhnd);
			}
			$sourcePassKey = '';
			if (file_exists($importPath . 'source_passkey.txt')){
				$sourcePassKeyFhnd = fopen($importPath . 'source_passkey.txt', 'r');
				$sourcePassKey = trim(fgets($sourcePassKeyFhnd));
				fclose($sourcePassKeyFhnd);
			}
			$mappings = [
				'libraries' => $libraryMappings,
				'locations' => $locationMappings,
				'users' => $userMappings,
				'passkey' => $sourcePassKey,
			];

			foreach ($_REQUEST['enrichmentElement'] as $element){
				if ($element == 'browse') {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
					$message = $this->importObjects('BrowseCategoryGroup', 'Browse Category Groups', $importPath . 'browse_categories.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'collection_spotlights') {

				} elseif ($element == 'ip_addresses') {
					require_once ROOT_DIR . '/sys/IP/IPAddress.php';
					$message = $this->importObjects('IPAddress', 'IP Addresses', $importPath . 'ip_addresses.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'javascript') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
					$message = $this->importObjects('JavaScriptSnippet', 'JavaScript Snippets', $importPath . 'javascript_snippets.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'placards') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
					$message = $this->importObjects('Placard', 'Placards', $importPath . 'placards.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'roles') {
					require_once ROOT_DIR . '/sys/Administration/Role.php';
					$message = $this->importObjects('Role', 'Roles', $importPath . 'roles.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'system_messages') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
					$message = $this->importObjects('SystemMessage', 'System Messages', $importPath . 'system_messages.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'users') {
					$message = $this->importObjects('User', 'Users', $importPath . 'users.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'user_roles') {
					require_once  ROOT_DIR . '/sys/Administration/UserRoles.php';
					$message = $this->importObjects('UserRoles', 'User Roles', $importPath . 'user_roles.json', $mappings, $overrideExisting, $message);
				}
			}

			if (!empty($message)){
				$importResults['message'] = $message;
				$importResults['success'] = $success;
			}

			$interface->assign('importResults', $importResults);
		}else{
			//Staring the import process
			//Determine if the necessary files are in place
			$setupErrors = [];
			$validEnrichmentToImport = [];
			//Look for the necessary files
			if ($importDirExists){
				if (file_exists($importPath . 'browse_categories.json')){
					$validEnrichmentToImport['browse'] = 'Browse Categories';
				}
				if (file_exists($importPath . 'javascript_snippets.json')){
					$validEnrichmentToImport['javascript'] = 'JavaScript Snippets';
				}
				if (file_exists($importPath . 'ip_addresses.json')){
					$validEnrichmentToImport['ip_addresses'] = 'IP Addresses';
				}
				if (file_exists($importPath . 'placards.json')){
					$validEnrichmentToImport['placards'] = 'Placards';
				}
				if (file_exists($importPath . 'roles.json')){
					$validEnrichmentToImport['roles'] = 'Roles';
				}
				if (file_exists($importPath . 'system_messages.json')){
					$validEnrichmentToImport['system_messages'] = 'System Messages';
				}
				if (file_exists($importPath . 'users.json')){
					$validEnrichmentToImport['users'] = 'Users';
				}
				if (file_exists($importPath . 'user_roles.json')){
					$validEnrichmentToImport['user_roles'] = 'User Roles';
				}
			}

			if (count($validEnrichmentToImport) == 0){
				$setupErrors[] = translate(['text' => "No valid options to import. Upload files to %1%.", 1=>$importPath, 'isAdminFacing'=>true]);
			}
			//Check mapping between libraries and locations

			$interface->assign('setupErrors', $setupErrors);
			$interface->assign('validEnrichmentToImport', $validEnrichmentToImport);

		}

		$this->display('importAspenData.tpl', 'Import Aspen Data');
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Import Local Enrichment');
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

	function importObjects(string $className, string $pluralImportName, string $importFile, array $mappings, string $overrideExisting, string $message) : string{
		$numObjectsImported = 0;

		if ($overrideExisting == 'deleteAllExisting'){
			/** @var DataObject $object */
			$object = new $className();
			$object->whereAdd($object->getPrimaryKey() . " LIKE '%'");
			$object->delete(true);
		}
		$objectHnd = fopen($importFile, 'r');
		$objectLine = fgets($objectHnd);
		while ($objectLine){
			$jsonData = json_decode($objectLine, true);
			$object = new $className();
			$object->loadFromJSON($jsonData, $mappings, $overrideExisting);

			$numObjectsImported++;
			$objectLine = fgets($objectHnd);
		}
		if ($numObjectsImported > 0){
			if (strlen($message) > 0){
				$message .= '<br/>';
			}
			$message .= "Imported $numObjectsImported $pluralImportName";
		}
		return $message;
	}
}