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
			$mappings = [
				'libraries' => $libraryMappings,
				'locations' => $locationMappings
			];

			foreach ($_REQUEST['enrichmentElement'] as $element){
				if ($element == 'browse') {

				} elseif ($element == 'collection_spotlights') {

				} elseif ($element == 'javascript') {
					require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
					$numSnippetsImported = 0;
					$javascriptFileHnd = fopen($importPath . 'javascript_snippets.json', 'r');
					$objectLine = fgets($javascriptFileHnd);
					while ($objectLine){
						$jsonData = json_decode($objectLine, true);
						$snippet = new JavaScriptSnippet();
						$snippet->loadFromJSON($jsonData, $mappings);
						$snippet->update();

						$numSnippetsImported++;
						$objectLine = fgets($javascriptFileHnd);
					}
					if ($numSnippetsImported > 0){
						if (strlen($message) > 0){
							$message .= '<br/>';
						}else{
							$message .= "Imported $numSnippetsImported Javascript Snippets";
						}
					}
				} elseif ($element == 'system_messages') {

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
				if (file_exists($importPath . 'javascript_snippets.json')){
					$validEnrichmentToImport['javascript'] = 'JavaScript';
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
}