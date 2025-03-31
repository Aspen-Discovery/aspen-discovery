<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Greenhouse_ImportAspenData extends Admin_Admin {
	function launch() {
		global $interface;
		global $serverName;
		$importPath = '/data/aspen-discovery/' . $serverName . '/import/';
		$importDirExists = false;
		if (!file_exists($importPath)) {
			if (!mkdir($importPath, 0777, true)) {
				$setupErrors[] = 'Could not create import directory';
			} else {
				chgrp($importPath, 'aspen_apache');
				chmod($importPath, 0777);
				$importDirExists = true;
			}
		} else {
			$importDirExists = true;
		}

		//All elements in order that they should be processed
		$elements = [
			'roles' => [
				'classFile' => ROOT_DIR . '/sys/Administration/Role.php',
				'className' => 'Role',
				'name' => 'Roles',
			],
			'users' => [
				'classFile' => ROOT_DIR . '/sys/Account/User.php',
				'className' => 'User',
				'name' => 'Users',
			],
			'user_roles' => [
				'classFile' => ROOT_DIR . '/sys/Administration/UserRoles.php',
				'className' => 'UserRoles',
				'name' => 'User Roles',
			],
			'user_messages' => [
				'classFile' => ROOT_DIR . '/sys/Account/UserMessage.php',
				'className' => 'UserMessage',
				'name' => 'User Messages',
			],
			'user_payments' => [
				'classFile' => ROOT_DIR . '/sys/Account/UserPayment.php',
				'className' => 'UserPayment',
				'name' => 'User Payments',
			],
			'user_saved_searches' => [
				'classFile' => ROOT_DIR . '/sys/SearchEntry.php',
				'className' => 'SearchEntry',
				'name' => 'User Saved Searches',
			],
			'user_lists' => [
				'classFile' => ROOT_DIR . '/sys/UserLists/UserList.php',
				'className' => 'UserList',
				'name' => 'User Lists',
			],
			'browse_categories' => [
				'classFile' => ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php',
				'className' => 'BrowseCategoryGroup',
				'name' => 'Browse Category Groups',
			],
			'user_browse_category_dismissals' => [
				'classFile' => ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php',
				'className' => 'BrowseCategoryDismissal',
				'name' => 'User Browse Category Dismissals',
			],
			'user_linked_accounts' => [
				'classFile' => ROOT_DIR . '/sys/Account/UserLink.php',
				'className' => 'UserLink',
				'name' => 'User Linked Accounts',
			],
			'user_not_interested' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php',
				'className' => 'NotInterested',
				'name' => 'User Not Interested',
			],
			'user_reading_history' => [
				'classFile' => ROOT_DIR . '/sys/ReadingHistoryEntry.php',
				'className' => 'ReadingHistoryEntry',
				'name' => 'User Reading History',
			],
			'user_work_reviews' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php',
				'className' => 'UserWorkReview',
				'name' => 'User Reviews \ Ratings',
			],
			'ip_addresses' => [
				'classFile' => ROOT_DIR . '/sys/IP/IPAddress.php',
				'className' => 'IPAddress',
				'name' => 'IP Addresses',
			],
			'javascript' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php',
				'className' => 'JavaScriptSnippet',
				'name' => 'JavaScript Snippets',
			],
			'placards' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/Placard.php',
				'className' => 'Placard',
				'name' => 'Placards',
			],
			'placard_dismissals' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php',
				'className' => 'PlacardDismissal',
				'name' => 'Placard Dismissals',
			],
			'system_messages' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php',
				'className' => 'SystemMessage',
				'name' => 'System Messages',
			],
			'user_system_message_dismissals' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php',
				'className' => 'SystemMessageDismissal',
				'name' => 'User System Message Dismissals',
			],
			'materials_request_statuses' => [
				'classFile' => ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php',
				'className' => 'MaterialsRequestStatus',
				'name' => 'Materials Request Statuses',
			],
			'materials_requests' => [
				'classFile' => ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php',
				'className' => 'MaterialsRequest',
				'name' => 'Materials Requests',
			],
			'block_patron_account_links' => [
				'classFile' => ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php',
				'className' => 'BlockPatronAccountLink',
				'name' => 'Block Patron Account Links',
			],
			'axis_360_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php',
				'className' => 'Axis360RecordUsage',
				'name' => 'Boundless Record Usage',
			],
			'user_axis_360_usage' => [
				'classFile' => ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php',
				'className' => 'UserAxis360Usage',
				'name' => 'User Boundless Usage',
			],
			'cloud_library_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/CloudLibrary/CloudLibraryRecordUsage.php',
				'className' => 'CloudLibraryRecordUsage',
				'name' => 'Cloud Library Record Usage',
			],
			'user_cloud_library_usage' => [
				'classFile' => ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php',
				'className' => 'UserCloudLibraryUsage',
				'name' => 'User Cloud Library Usage',
			],
			'ebsco_eds_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php',
				'className' => 'EbscoEdsRecordUsage',
				'name' => 'EBSCO EDS Record Usage',
			],
			'user_ebsco_eds_usage' => [
				'classFile' => ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php',
				'className' => 'UserEbscoEdsUsage',
				'name' => 'User EBSCO EDS Usage',
			],
			'summon_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Summon/SummonRecordUsage.php',
				'className' => 'SummonRecordUsage',
				'name' => 'Summon Record Usage',
			],
			'user_summon_usage' => [
				'classFile' => ROOT_DIR . '/sys/Summon/UserSummonUsage.php',
				'className' => 'UserSummonUsage',
				'name' => 'User Summon Usage',
			],
			'events_usage' => [
				'classFile' => ROOT_DIR . '/sys/Events/EventsUsage.php',
				'className' => 'EventsUsage',
				'name' => 'Events Usage',
			],
			'user_events_usage' => [
				'classFile' => ROOT_DIR . '/sys/Events/UserEventsUsage.php',
				'className' => 'UserEventsUsage',
				'name' => 'User Events Usage',
			],
			'hoopla_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Hoopla/HooplaRecordUsage.php',
				'className' => 'HooplaRecordUsage',
				'name' => 'Hoopla Record Usage',
			],
			'user_hoopla_usage' => [
				'classFile' => ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php',
				'className' => 'UserHooplaUsage',
				'name' => 'User Hoopla Usage',
			],
			'ils_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/ILS/ILSRecordUsage.php',
				'className' => 'ILSRecordUsage',
				'name' => 'ILS Record Usage',
			],
			'user_ils_usage' => [
				'classFile' => ROOT_DIR . '/sys/ILS/UserILSUsage.php',
				'className' => 'UserILSUsage',
				'name' => 'User ILS Usage',
			],
			'side_load_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php',
				'className' => 'SideLoadedRecordUsage',
				'name' => 'Side Loaded Record Usage',
			],
			'user_side_load_usage' => [
				'classFile' => ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php',
				'className' => 'UserSideLoadUsage',
				'name' => 'User Side Load Usage',
			],
			'open_archives_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecordUsage.php',
				'className' => 'OpenArchivesRecordUsage',
				'name' => 'Open Archives Record Usage',
			],
			'user_open_archives_usage' => [
				'classFile' => ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php',
				'className' => 'UserOpenArchivesUsage',
				'name' => 'User Open Archives Usage',
			],
			'overdrive_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php',
				'className' => 'OverDriveRecordUsage',
				'name' => 'OverDrive Record Usage',
			],
			'user_overdrive_usage' => [
				'classFile' => ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php',
				'className' => 'UserOverDriveUsage',
				'name' => 'User OverDrive Usage',
			],
			'api_usage' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/APIUsage.php',
				'className' => 'APIUsage',
				'name' => 'API Usage',
			],
			'aspen_usage' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/AspenUsage.php',
				'className' => 'AspenUsage',
				'name' => 'Aspen Usage',
			],
			'slow_ajax_request' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/SlowAjaxRequest.php',
				'className' => 'SlowAjaxRequest',
				'name' => 'Slow AJAX Requests',
			],
			'slow_page' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/SlowPage.php',
				'className' => 'SlowPage',
				'name' => 'Page Performance',
			],
			'usage_by_ip' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php',
				'className' => 'UsageByIPAddress',
				'name' => 'Usage by IP Address',
			],
			'web_resource_usage' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebResourceUsage.php',
				'className' => 'WebResourceUsage',
				'name' => 'Web Resource Usage',
			],
			'user_website_usage' => [
				'classFile' => ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php',
				'className' => 'UserWebsiteUsage',
				'name' => 'User Website Usage',
			],
			'web_page_usage' => [
				'classFile' => ROOT_DIR . '/sys/WebsiteIndexing/WebPageUsage.php',
				'className' => 'WebPageUsage',
				'name' => 'Web Page Usage',
			],
			'web_builder_audiences' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php',
				'className' => 'WebBuilderAudience',
				'name' => 'Web Builder Audiences',
			],
			'web_builder_categories' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php',
				'className' => 'WebBuilderCategory',
				'name' => 'Web Builder Categories',
			],
			'web_builder_file_uploads' => [
				'classFile' => ROOT_DIR . '/sys/File/FileUpload.php',
				'className' => 'FileUpload',
				'name' => 'Web Builder File Uploads (PDFS etc)',
			],
			'web_builder_images' => [
				'classFile' => ROOT_DIR . '/sys/File/ImageUpload.php',
				'className' => 'ImageUpload',
				'name' => 'Web Builder Images',
			],
			'web_resources' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebResource.php',
				'className' => 'WebResource',
				'name' => 'Web Builder Resources',
			],
			'web_builder_basic_page' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/BasicPage.php',
				'className' => 'BasicPage',
				'name' => 'Web Builder Basic Pages',
			],
			'web_builder_custom_pages' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/PortalPage.php',
				'className' => 'PortalPage',
				'name' => 'Web Builder Custom Pages',
			],
			'web_builder_custom_forms' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/CustomForm.php',
				'className' => 'CustomForm',
				'name' => 'Web Builder Custom Forms',
			],
			'web_builder_form_submissions' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/CustomFormSubmission.php',
				'className' => 'CustomFormSubmission',
				'name' => 'Web Builder Custom Form Submissions',
			],
			'uploaded_images' => [
				'name' => 'Uploaded Images',
			],
			'side_load_marc_records' => [
				'name' => 'Sideload Files',
			],
		];

		if (isset($_REQUEST['submit'])) {
			set_time_limit(0);
			ini_set('memory_limit', '4G');

			$importResults = [
				'success' => false,
				'message' => 'Nothing was imported',
			];

			$message = '';
			$success = true;

			$overrideExisting = $_REQUEST['overrideExisting'];

			//Look for a mapping between old library names and new library names
			$libraryMappings = [];
			if (file_exists($importPath . 'library_map.csv')) {
				$libraryMappingsFhnd = fopen($importPath . 'library_map.csv', 'r');
				$mappingLine = fgetcsv($libraryMappingsFhnd);
				while ($mappingLine) {
					if (!empty($mappingLine) && count($mappingLine) >= 2) {
						$libraryMappings[trim($mappingLine[0])] = trim($mappingLine[1]);
					}
					$mappingLine = fgetcsv($libraryMappingsFhnd);
				}
				fclose($libraryMappingsFhnd);
			}
			//Look for a mapping between old location names and new location names
			$locationMappings = [];
			if (file_exists($importPath . 'location_map.csv')) {
				$locationMappingsFhnd = fopen($importPath . 'location_map.csv', 'r');
				$mappingLine = fgetcsv($locationMappingsFhnd);
				while ($mappingLine) {
					if (!empty($mappingLine) && count($mappingLine) >= 2) {
						$locationMappings[trim($mappingLine[0])] = trim($mappingLine[1]);
					}
					$mappingLine = fgetcsv($locationMappingsFhnd);
				}
				fclose($locationMappingsFhnd);
			}
			$userMappings = [];
			if (file_exists($importPath . 'users_map.csv')) {
				$userMappingsFhnd = fopen($importPath . 'users_map.csv', 'r');
				$mappingLine = fgetcsv($userMappingsFhnd);
				while ($mappingLine) {
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
			if (file_exists($importPath . 'bib_map.csv')) {
				$userMappingsFhnd = fopen($importPath . 'bib_map.csv', 'r');
				$mappingLine = fgetcsv($userMappingsFhnd);
				while ($mappingLine) {
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
			if (file_exists($importPath . 'source_passkey.txt')) {
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

			foreach ($elements as $element => $elementDefinition) {
				if (in_array($element, $_REQUEST['enrichmentElement'])) {
					if ($element == 'uploaded_images') {
						$message = $this->importImages($message);
					} else if ($element == 'side_load_marc_records') {
						$message = $this->importSideLoadMarcRecords($message);
					} else {
						require_once $elementDefinition['classFile'];
						$message = $this->importObjects($elementDefinition['className'], $elementDefinition['name'], $importPath . "$element.json", $mappings, $overrideExisting, $message);
					}
				}
			}

			if (!empty($message)) {
				$importResults['message'] = $message;
				$importResults['success'] = $success;
			}

			$interface->assign('importResults', $importResults);
		} else {
			//Staring the import process
			//Determine if the necessary files are in place
			$setupErrors = [];
			$validEnrichmentToImport = [];
			//Look for the necessary files
			if ($importDirExists) {
				foreach ($elements as $element => $elementDefinition) {
					if (file_exists($importPath . $element . '.json')) {
						$validEnrichmentToImport[$element] = $elementDefinition['name'];
					} elseif ($element == 'uploaded_images' && file_exists($importPath . 'uploaded_images.tar.gz')) {
						$validEnrichmentToImport[$element] = $elementDefinition['name'];
					} elseif ($element == 'side_load_marc_records') {
						$isValid = false;
						$uploadedSideLoads = scandir($importPath);
						foreach ($uploadedSideLoads as $uploadedSideLoad) {
							if (preg_match('~sideload_.*\.tar\.gz~', $uploadedSideLoad)){
								$isValid = true;
							}
						}
						if ($isValid) {
							$validEnrichmentToImport[$element] = $elementDefinition['name'];
						}
					}
				}
			}

			if (count($validEnrichmentToImport) == 0) {
				$setupErrors[] = translate([
					'text' => "No valid options to import. Upload files to %1%.",
					1 => $importPath,
					'isAdminFacing' => true,
				]);
			}
			//Check mapping between libraries and locations

			$interface->assign('setupErrors', $setupErrors);
			$interface->assign('validEnrichmentToImport', $validEnrichmentToImport);

		}

		$this->display('importAspenData.tpl', 'Import Aspen Data', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Import Local Enrichment');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	function importObjects(string $className, string $pluralImportName, string $importFile, array $mappings, string $overrideExisting, string $message): string {
		$numObjectsImported = 0;

		if ($overrideExisting == 'deleteAllExisting') {
			/** @var DataObject $object */
			$object = new $className();
			$object->whereAdd($object->getPrimaryKey() . " LIKE '%'");
			$object->delete(true);
		}
		$objectHnd = fopen($importFile, 'r');
		$objectLine = fgets($objectHnd);
		while ($objectLine) {
			$jsonData = json_decode($objectLine, true);
			/** @var DataObject $object */
			$object = new $className();
			$importResult = $object->loadFromJSON($jsonData, $mappings, $overrideExisting);
			if ($importResult == false) {
				$message .= "<br/>Error loading $className " . $object . "<br/>&nbsp;&nbsp;{$object->getLastError()}";
			} else {
				$numObjectsImported++;
			}
			$object->__destruct();
			$object = null;

			$objectLine = fgets($objectHnd);
		}
		if ($numObjectsImported > 0) {
			if (strlen($message) > 0) {
				$message .= '<br/>';
			}
			$message .= "Imported $numObjectsImported $pluralImportName";
		}
		return $message;
	}

	private function importSideLoadMarcRecords($message) : string {
		global $configArray;
		global $serverName;
		$sideLoads = new SideLoad();
		$sideLoads->find();
		while ($sideLoads->fetch()) {
			if (!empty($sideLoads->marcPath)) {
				$sideLoadName = preg_replace('~[\W]~', '_', trim($sideLoads->name));
				if (strlen($message) > 0) {
					$message .= '<br/>';
				}

				$message .= "Importing Side Load $sideLoads->name from sideload_$sideLoadName.tar.gz";
				if (!file_exists($sideLoads->marcPath)) {
					mkdir($sideLoads->marcPath, 0774, true);
					chgrp($sideLoads->marcPath, 'aspen_apache');
					chmod($sideLoads->marcPath, 0775);
				}
				if ($configArray['System']['operatingSystem'] == 'windows') {
					$output = [];
					if (file_exists("c:/data/aspen-discovery/$serverName/import/sideload_$sideLoadName.tar.gz")) {
						exec("tar -xzf c:/data/aspen-discovery/$serverName/import/sideload_$sideLoadName.tar.gz -C $sideLoads->marcPath", $output);
					}
				} else {
					$output = [];
					if (file_exists("/data/aspen-discovery/$serverName/import/sideload_$sideLoadName.tar.gz")) {
						exec("tar -xzf /data/aspen-discovery/$serverName/import/sideload_$sideLoadName.tar.gz -C $sideLoads->marcPath", $output);
						//$message .= "<br/><pre>cd $sideLoads->marcPath; tar -czf /data/aspen-discovery/$serverName/import/sideload_$sideLoadName.tar.gz *</pre>";
						//$message .= "<br/>Output" . implode("<br/>", $output);
					}
				}

			}
		}
		if (strlen($message) > 0) {
			$message .= '<br/>';
		}
		$message .= "Imported Side Load MARC records";

		return $message;
	}

	private function importImages($message) : string {
		global $configArray;
		global $serverName;
		if (file_exists("/data/aspen-discovery/$serverName/import/uploaded_images.tar.gz")) {
			if ($configArray['System']['operatingSystem'] == 'windows') {
				exec("tar -xzf /data/aspen-discovery/$serverName/import/uploaded_images.tar.gz -C /web/aspen-discovery/code/web/files");
			} else {
				exec("tar -xzf /data/aspen-discovery/$serverName/import/uploaded_images.tar.gz -C /usr/local/aspen-discovery/code/web/files");
			}
		}
		if (file_exists("/data/aspen-discovery/$serverName/import/uploaded_covers.tar.gz")) {
			exec("tar -xzf /data/aspen-discovery/$serverName/import/uploaded_covers.tar.gz -C /data/aspen-discovery/$serverName/covers");
		}
		if (file_exists("/data/aspen-discovery/$serverName/import/uploaded_files.tar.gz")) {
			exec("tar -xzf /data/aspen-discovery/$serverName/import/uploaded_files.tar.gz -C /data/aspen-discovery/$serverName/uploads");
		}

		$message .= "Imported Uploaded Files";

		return $message;
	}
}