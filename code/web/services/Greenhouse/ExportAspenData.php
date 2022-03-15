<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
class Greenhouse_ExportAspenData extends Admin_Admin
{
	function launch(){
		global $interface;
		if (isset($_REQUEST['submit'])){
			$submissionResults = [
				'success' => false,
				'message' => 'Nothing was exported'
			];

			$message = '';
			$success = true;

			//Make sure we have the export directory
			global $serverName;
			$exportPath = '/data/aspen-discovery/' . $serverName . '/export/';
			$exportDirExists = false;
			if (!file_exists($exportPath)){
				if (!mkdir($exportPath, 0774, true)){
					$message = 'Could not create export directory';
					$success = false;
				}else{
					chgrp($exportPath, 'aspen_apache');
					chmod($exportPath, 0774);
					$exportDirExists = true;
				}
			}else{
				$exportDirExists = true;
			}

			if ($exportDirExists) {
				$selectedLibraries = $_REQUEST['libraries'];
				$selectedLocations = $_REQUEST['locations'];
				$selectedFilters = [
					'libraries' => $selectedLibraries,
					'locations' => $selectedLocations
				];
				if (count($selectedLibraries) == 0 && count($selectedLocations) == 0) {
					$message = 'No libraries or locations were selected';
					$success = false;
				} else {
					$success = true;
					foreach ($_REQUEST['dataElement'] as $element) {
						if ($element == 'browse_categories') {
							require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php';
							$browseCategoryFile = $exportPath . 'browse_categories.json';
							$message = $this->exportObjects('BrowseCategoryGroup', 'Browse Category Groups', $browseCategoryFile, $selectedFilters, $message);
						} elseif ($element == 'collection_spotlights') {
							$message .= '<br/>Exporting Collection Spotlights has not been implemented yet';
						} elseif ($element == 'javascript') {
							require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';
							$javascriptSnippetsFile = $exportPath . 'javascript_snippets.json';
							$message = $this->exportObjects('JavaScriptSnippet', 'JavaScript Snippets', $javascriptSnippetsFile, $selectedFilters, $message);
						} elseif ($element == 'ip_addresses') {
							require_once ROOT_DIR . '/sys/IP/IPAddress.php';
							$ipAddressesFile = $exportPath . 'ip_addresses.json';
							$message = $this->exportObjects('IPAddress', 'IP Addresses', $ipAddressesFile, $selectedFilters, $message);

						} elseif ($element == 'placards') {
							require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
							$placardFile = $exportPath . 'placards.json';
							$message = $this->exportObjects('Placard', 'Placards', $placardFile, $selectedFilters, $message);

						} elseif ($element == 'system_messages') {
							require_once  ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';
							$systemMessagesFile = $exportPath . 'system_messages.json';
							$message = $this->exportObjects('SystemMessage', 'System Messages', $systemMessagesFile, $selectedFilters, $message);
						}
					}
				}
			}
			if (!empty($message)){
				$submissionResults['message'] = $message;
				$submissionResults['success'] = $success;
			}

			$interface->assign('submissionResults', $submissionResults);
		}else {
			$dataElements = [
				'browse_categories' => 'Browse Categories w/Groups',
				'collection_spotlights' => 'Collection Spotlights',
				'ip_addresses' => 'IP Addresses',
				'javascript' => 'JavaScript',
				'placards' => 'Placards',
				'system_messages' => 'System Messages'
			];
			$interface->assign('dataElements', $dataElements);

			$libraryList = Library::getLibraryList(false);
			$locationList = Location::getLocationList(false);
			$interface->assign('libraries', $libraryList);
			$interface->assign('locations', $locationList);
		}

		$this->display('exportAspenData.tpl', 'Export Aspen Data',false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Export Local Enrichment');
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

	function exportObjects(string $className, string $pluralExportName, string $exportFile, array $selectedFilters, $message) : string{
		$numObjectsExported = 0;
		$exportFileHnd = fopen($exportFile, 'w');
		/** @var DataObject $exportObject */
		$exportObject = new $className();
		$exportObject->find();
		while ($exportObject->fetch()){
			if ($exportObject->okToExport($selectedFilters)){
				fwrite($exportFileHnd, $exportObject->getJSONString(true, false) . "\n");
				$numObjectsExported++;
			}
		}
		fclose($exportFileHnd);
		chgrp($exportFile, 'aspen_apache');
		chmod($exportFile, 0660);

		if ($numObjectsExported > 0){
			if (strlen($message) > 0) {
				$message .= '<br/>';
			}
			$message .= "Exported $numObjectsExported $pluralExportName";
		}

		return $message;
	}
}