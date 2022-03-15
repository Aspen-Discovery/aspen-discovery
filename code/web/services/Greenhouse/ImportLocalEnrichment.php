<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
class Greenhouse_ImportLocalEnrichment extends Admin_Admin
{
	function launch(){
		global $interface;
		global $serverName;
		$importPath = '/data/aspen-discovery/' . $serverName . '/import/';
		$importDirExists = false;
		if (!file_exists($importPath)){
			if (!mkdir($importPath, '0770', true)){
				$setupErrors[] = 'Could not create import directory';
			}else{
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
						$userMappings[trim($mappingLine[0])] = trim($mappingLine[1]);
					}
					$mappingLine = fgetcsv($userMappingsFhnd);
				}
				fclose($userMappingsFhnd);
			}
			$mappings = [
				'libraries' => $libraryMappings,
				'locations' => $locationMappings,
				'users' => $userMappings,
			];

			foreach ($_REQUEST['enrichmentElement'] as $element){
				if ($element == 'browse') {
					require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
					$message = $this->importObjects('BrowseCategoryGroup', 'Browse Category Groups', $importPath . 'browse_categories.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'collection_spotlights') {

				} elseif ($element == 'javascript') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
					$message = $this->importObjects('JavaScriptSnippet', 'JavaScript Snippets', $importPath . 'javascript_snippets.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'placards') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
					$message = $this->importObjects('Placard', 'Placards', $importPath . 'placards.json', $mappings, $overrideExisting, $message);
				} elseif ($element == 'system_messages') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
					$message = $this->importObjects('SystemMessage', 'System Messages', $importPath . 'system_messages.json', $mappings, $overrideExisting, $message);
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
				if (file_exists($importPath . 'placards.json')){
					$validEnrichmentToImport['placards'] = 'Placards';
				}
				if (file_exists($importPath . 'system_messages.json')){
					$validEnrichmentToImport['system_messages'] = 'System Messages';
				}
			}

			if (count($validEnrichmentToImport) == 0){
				$setupErrors[] = translate(['text' => "No valid options to import. Upload files to %1%.", 1=>$importPath, 'isAdminFacing'=>true]);
			}
			//Check mapping between libraries and locations

			$interface->assign('setupErrors', $setupErrors);
			$interface->assign('validEnrichmentToImport', $validEnrichmentToImport);

		}

		$this->display('importLocalEnrichment.tpl', 'Import Local Enrichment');
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