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
			$bibMappings = [];
			if (file_exists($importPath . 'bib_map.csv')){
				$userMappingsFhnd = fopen($importPath . 'bib_map.csv', 'r');
				$mappingLine = fgetcsv($userMappingsFhnd);
				while ($mappingLine){
					if (!empty($mappingLine) && count($mappingLine) >= 2) {
						$sourceId = $mappingLine[1];
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
				'bibs' => $bibMappings,
				'passkey' => $sourcePassKey,
			];

			foreach ($_REQUEST['enrichmentElement'] as $element){
				if ($element == 'browse') {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
					$className = 'BrowseCategoryGroup'; $pluralImportName = 'Browse Category Groups';

				} elseif ($element == 'ip_addresses') {
					require_once ROOT_DIR . '/sys/IP/IPAddress.php';
					$className = 'IPAddress'; $pluralImportName = 'IP Addresses';

				} elseif ($element == 'javascript') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
					$className = 'JavaScriptSnippet'; $pluralImportName = 'JavaScript Snippets';

				} elseif ($element == 'placards') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
					$className = 'Placard'; $pluralImportName = 'Placards';

				} elseif ($element == 'roles') {
					require_once ROOT_DIR . '/sys/Administration/Role.php';
					$className = 'Role'; $pluralImportName = 'Roles';

				} elseif ($element == 'system_messages') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
					$className = 'SystemMessage'; $pluralImportName = 'System Messages';

				} elseif ($element == 'users') {
					$className = 'User'; $pluralImportName = 'Users';

				} elseif ($element == 'user_browse_category_dismissals') {
					require_once  ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
					$className = 'BrowseCategoryDismissal'; $pluralImportName = 'User Browse Category Dismissals';

				} elseif ($element == 'user_linked_accounts') {
					require_once  ROOT_DIR . '/sys/Account/UserLink.php';
					$className = 'UserLink'; $pluralImportName = 'User Linked Accounts';

				} elseif ($element == 'user_lists') {
					require_once  ROOT_DIR . '/sys/UserLists/UserList.php';
					$className = 'UserList'; $pluralImportName = 'User Lists';

				} elseif ($element == 'user_not_interested') {
					require_once  ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
					$className = 'NotInterested'; $pluralImportName = 'User Not Interested';

				} elseif ($element == 'user_reading_history') {
					require_once  ROOT_DIR . '/sys/ReadingHistoryEntry.php';
					$className = 'ReadingHistoryEntry'; $pluralImportName = 'User Reading History';

				} elseif ($element == 'user_work_reviews') {
					require_once  ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
					$className = 'UserWorkReview'; $pluralImportName = 'User Reviews \ Ratings';

				} elseif ($element == 'user_saved_searches') {
					require_once  ROOT_DIR . '/sys/SearchEntry.php';
					$className = 'SearchEntry'; $pluralImportName = 'User Saved Searches';

				} elseif ($element == 'user_roles') {
					require_once  ROOT_DIR . '/sys/Administration/UserRoles.php';
					$className = 'UserRoles'; $pluralImportName = 'User Role';

				} elseif ($element == 'user_system_message_dismissals') {
					require_once  ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
					$className = 'SystemMessageDismissal'; $pluralImportName = 'User System Message Dismissals';

				} else {
					$message .= "<br/> Unhandled element $element";
					continue;
				}

				$message = $this->importObjects($className, $pluralImportName, $importPath . "$element.json", $mappings, $overrideExisting, $message);
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
				if (file_exists($importPath . 'ip_addresses.json')){
					$validEnrichmentToImport['ip_addresses'] = 'IP Addresses';
				}
				if (file_exists($importPath . 'javascript_snippets.json')){
					$validEnrichmentToImport['javascript'] = 'JavaScript Snippets';
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
				if (file_exists($importPath . 'user_browse_category_dismissals.json')){
					$validEnrichmentToImport['user_browse_category_dismissals'] = 'User Browse Category Dismissals';
				}
				if (file_exists($importPath . 'user_linked_accounts.json')){
					$validEnrichmentToImport['user_linked_accounts'] = 'User Linked Accounts';
				}
				if (file_exists($importPath . 'user_lists.json')){
					$validEnrichmentToImport['user_lists'] = 'User Lists';
				}
				if (file_exists($importPath . 'user_not_interested.json')){
					$validEnrichmentToImport['user_not_interested'] = 'User Not Interested';
				}
				if (file_exists($importPath . 'user_reading_history.json')){
					$validEnrichmentToImport['user_reading_history'] = 'User Reading History';
				}
				if (file_exists($importPath . 'user_work_reviews.json')){
					$validEnrichmentToImport['user_work_reviews'] = 'User Reviews \ Ratings';
				}
				if (file_exists($importPath . 'user_saved_searches.json')){
					$validEnrichmentToImport['user_roles'] = 'User Saved Searches';
				}
				if (file_exists($importPath . 'user_system_message_dismissals.json')){
					$validEnrichmentToImport['user_work_reviews'] = 'User System Message Dismissals';
				}
			}

			if (count($validEnrichmentToImport) == 0){
				$setupErrors[] = translate(['text' => "No valid options to import. Upload files to %1%.", 1=>$importPath, 'isAdminFacing'=>true]);
			}
			//Check mapping between libraries and locations

			$interface->assign('setupErrors', $setupErrors);
			$interface->assign('validEnrichmentToImport', $validEnrichmentToImport);

		}

		$this->display('importAspenData.tpl', 'Import Aspen Data', false);
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