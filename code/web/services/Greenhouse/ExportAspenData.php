<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
class Greenhouse_ExportAspenData extends Admin_Admin
{
	function launch(){
		global $interface;

		//All elements in order that they should be processed
		$elements = [
			'roles' => [
				'classFile' => ROOT_DIR . '/sys/Administration/Role.php',
				'className' => 'Role',
				'name' => 'Roles'
			],
			'users' => [
				'classFile' => ROOT_DIR . '/sys/Account/User.php',
				'className' => 'User',
				'name' => 'Users'
			],
			'user_roles' => [
				'classFile' => ROOT_DIR . '/sys/Administration/UserRoles.php',
				'className' => 'UserRoles',
				'name' => 'User Roles'
			],
			'user_messages' => [
				'classFile' => ROOT_DIR . '/sys/Account/UserMessage.php',
				'className' => 'UserMessage',
				'name' => 'User Messages'
			],
			'user_payments' => [
				'classFile' => ROOT_DIR . '/sys/Account/UserPayment.php',
				'className' => 'UserPayment',
				'name' => 'User Payments'
			],
			'user_staff_settings' => [
				'classFile' => ROOT_DIR . '/sys/Account/UserStaffSettings.php',
				'className' => 'UserStaffSettings',
				'name' => 'User Staff Settings'
			],
			'user_saved_searches' => [
				'classFile' => ROOT_DIR . '/sys/SearchEntry.php',
				'className' => 'SearchEntry', 'name' => 'User Saved Searches'
			],
			'user_lists' => [
				'classFile' => ROOT_DIR . '/sys/UserLists/UserList.php',
				'className' => 'UserList',
				'name' => 'User Lists'
			],
			'browse_categories' => [
				'classFile' => ROOT_DIR . '/sys/Browse/BrowseCategoryGroup.php',
				'className' => 'BrowseCategoryGroup',
				'name' => 'Browse Category Groups'
			],
			'user_browse_category_dismissals' => [
				'classFile' => ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php',
				'className' => 'BrowseCategoryDismissal',
				'name' => 'User Browse Category Dismissals'
			],
			'user_linked_accounts' => [
				'classFile' => ROOT_DIR . '/sys/Account/UserLink.php',
				'className' => 'UserLink',
				'name' => 'User Linked Accounts'
			],
			'user_not_interested' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php',
				'className' => 'NotInterested',
				'name' => 'User Not Interested'
			],
			'user_reading_history' => [
				'classFile' => ROOT_DIR . '/sys/ReadingHistoryEntry.php',
				'className' => 'ReadingHistoryEntry',
				'name' => 'User Reading History'
			],
			'user_work_reviews' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php',
				'className' => 'UserWorkReview',
				'name' => 'User Reviews \ Ratings'
			],
			'ip_addresses' => [
				'classFile' => ROOT_DIR . '/sys/IP/IPAddress.php',
				'className' => 'IPAddress',
				'name' => 'IP Addresses'
			],
			'javascript' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php',
				'className' => 'JavaScriptSnippet',
				'name' => 'JavaScript Snippets'
			],
			'placards' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/Placard.php',
				'className' => 'Placard',
				'name' => 'Placards'
			],
			'placard_dismissals' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php',
				'className' => 'PlacardDismissal',
				'name' => 'Placard Dismissals'
			],
			'system_messages' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php',
				'className' => 'SystemMessage',
				'name' => 'System Messages'
			],
			'user_system_message_dismissals' => [
				'classFile' => ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php',
				'className' => 'SystemMessageDismissal',
				'name' => 'User System Message Dismissals'
			],
			'materials_request_statuses' => [
				'classFile' => ROOT_DIR . '/sys/MaterialsRequestStatus.php',
				'className' => 'MaterialsRequestStatus',
				'name' => 'Materials Request Statuses'
			],
			'materials_requests' => [
				'classFile' => ROOT_DIR . '/sys/MaterialsRequest.php',
				'className' => 'MaterialsRequest',
				'name' => 'Materials Requests'
			],
			'block_patron_account_links' => [
				'classFile' => ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php',
				'className' => 'BlockPatronAccountLink',
				'name' => 'Block Patron Account Links'
			],
			'axis_360_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php',
				'className' => 'Axis360RecordUsage',
				'name' => 'Axis 360 Record Usage'
			],
			'user_axis_360_usage' => [
				'classFile' => ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php',
				'className' => 'UserAxis360Usage',
				'name' => 'User Axis 360 Usage'
			],
			'cloud_library_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/CloudLibrary/CloudLibraryRecordUsage.php',
				'className' => 'CloudLibraryRecordUsage',
				'name' => 'Cloud Library Record Usage'
			],
			'user_cloud_library_usage' => [
				'classFile' => ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php',
				'className' => 'UserCloudLibraryUsage',
				'name' => 'User Cloud Library Usage'
			],
			'ebsco_eds_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php',
				'className' => 'EbscoEdsRecordUsage',
				'name' => 'EBSCO EDS Record Usage'
			],
			'user_ebsco_eds_usage' => [
				'classFile' => ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php',
				'className' => 'UserEbscoEdsUsage',
				'name' => 'User EBSCO EDS Usage'
			],
			'events_usage' => [
				'classFile' => ROOT_DIR . '/sys/Events/EventsUsage.php',
				'className' => 'EventsUsage',
				'name' => 'Events Usage'
			],
			'user_events_usage' => [
				'classFile' => ROOT_DIR . '/sys/Events/UserEventsUsage.php',
				'className' => 'UserEventsUsage',
				'name' => 'User Events Usage'
			],
			'hoopla_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Hoopla/HooplaRecordUsage.php',
				'className' => 'HooplaRecordUsage',
				'name' => 'Hoopla Record Usage'
			],
			'user_hoopla_usage' => [
				'classFile' => ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php',
				'className' => 'UserHooplaUsage',
				'name' => 'User Hoopla Usage'
			],
			'ils_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/ILS/ILSRecordUsage.php',
				'className' => 'ILSRecordUsage',
				'name' => 'ILS Record Usage'
			],
			'user_ils_usage' => [
				'classFile' => ROOT_DIR . '/sys/ILS/UserILSUsage.php',
				'className' => 'UserILSUsage',
				'name' => 'User ILS Usage'
			],
			'side_load_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php',
				'className' => 'SideLoadedRecordUsage',
				'name' => 'Side Loaded Record Usage'
			],
			'user_side_load_usage' => [
				'classFile' => ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php',
				'className' => 'UserSideLoadUsage',
				'name' => 'User Side Load Usage'
			],
			'open_archives_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecordUsage.php',
				'className' => 'OpenArchivesRecordUsage',
				'name' => 'Open Archives Record Usage'
			],
			'user_open_archives_usage' => [
				'classFile' => ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php',
				'className' => 'UserOpenArchivesUsage',
				'name' => 'User Open Archives Usage'
			],
			'overdrive_record_usage' => [
				'classFile' => ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php',
				'className' => 'OverDriveRecordUsage',
				'name' => 'OverDrive Record Usage'
			],
			'user_overdrive_usage' => [
				'classFile' => ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php',
				'className' => 'UserOverDriveUsage',
				'name' => 'User OverDrive Usage'
			],
			'api_usage' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/APIUsage.php',
				'className' => 'APIUsage',
				'name' => 'API Usage'
			],
			'aspen_usage' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/AspenUsage.php',
				'className' => 'AspenUsage',
				'name' => 'Aspen Usage'
			],
			'slow_ajax_request' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/SlowAjaxRequest.php',
				'className' => 'SlowAjaxRequest',
				'name' => 'Slow AJAX Requests'
			],
			'slow_page' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/SlowPage.php',
				'className' => 'SlowPage',
				'name' => 'Page Performance'
			],
			'usage_by_ip' => [
				'classFile' => ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php',
				'className' => 'UsageByIPAddress',
				'name' => 'Usage by IP Address'
			],
			'web_resource_usage' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebResourceUsage.php',
				'className' => 'WebResourceUsage',
				'name' => 'Web Resource Usage'
			],
			'user_website_usage' => [
				'classFile' => ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php',
				'className' => 'UserWebsiteUsage',
				'name' => 'User Website Usage'
			],
			'web_page_usage' => [
				'classFile' => ROOT_DIR . '/sys/WebsiteIndexing/WebPageUsage.php',
				'className' => 'WebPageUsage',
				'name' => 'Web Page Usage'
			],
			'web_builder_audiences' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php',
				'className' => 'WebBuilderAudience',
				'name' => 'Web Builder Audiences'
			],
			'web_builder_categories' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php',
				'className' => 'WebBuilderCategory',
				'name' => 'Web Builder Categories'
			],
			'web_resources' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/WebResource.php',
				'className' => 'WebResource',
				'name' => 'Web Builder Resources'
			],
			'web_builder_basic_page' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/BasicPage.php',
				'className' => 'BasicPage',
				'name' => 'Web Builder Basic Pages'
			],
			'web_builder_custom_pages' => [
				'classFile' => ROOT_DIR . '/sys/WebBuilder/PortalPage.php',
				'className' => 'PortalPage',
				'name' => 'Web Builder Custom Pages'
			],
			'uploaded_images' => [
				'name' => 'Uploaded Images'
			],
		];

		if (isset($_REQUEST['submit'])){
			set_time_limit(0);
			ini_set('memory_limit','8G');
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
				$selectedLibraries = $_REQUEST['libraries'] ?? [];
				$selectedLocations = $_REQUEST['locations'] ?? [];
				$selectedInstances = $_REQUEST['instances'] ?? [];
				$selectedFilters = [
					'libraries' => $selectedLibraries,
					'locations' => $selectedLocations,
					'instances' => $selectedInstances,
				];
				if (count($selectedLibraries) == 0 && count($selectedLocations) == 0) {
					$message = 'No libraries or locations were selected';
					$success = false;
				} else {
					//If no locations are selected, export all data for the selected libraries
					if (count($selectedLocations) == 0){
						foreach ($selectedLibraries as $libraryId){
							$location = new Location();
							$location->libraryId = $libraryId;
							$location->find();
							while ($location->fetch()){
								$selectedLocations[] = $location->locationId;
							}
						}
						$selectedFilters['locations'] = $selectedLocations;
					}
					$success = true;
					foreach ($elements as $element => $elementDefinition){
						if (in_array($element, $_REQUEST['dataElement'])){
							if ($element == 'uploaded_images'){
								$message = $this->exportImages($message);
							}else {
								require_once $elementDefinition['classFile'];
								$message = $this->exportObjects($elementDefinition['className'], $elementDefinition['name'], $exportPath . $element . '.json', $selectedFilters, $message);
							}
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
			$interface->assign('dataElements', $elements);

			$libraryList = Library::getLibraryList(false);
			$locationList = Location::getLocationList(false);
			$interface->assign('libraries', $libraryList);
			$interface->assign('locations', $locationList);

			//Get a list of all instances
			$statsInstance = new AspenUsage();
			$statsInstance->selectAdd(null);
			$statsInstance->selectAdd("DISTINCT(instance) as instance");
			$statsInstance->orderBy('instance');
			$statsInstance->find();
			$allInstances = [];
			if ($statsInstance->getNumResults() > 1) {
				while ($statsInstance->fetch()) {
					if (!empty($statsInstance->instance)) {
						$allInstances[$statsInstance->instance] = $statsInstance->instance;
					}
				}
			}
			$interface->assign('instances', $allInstances);
		}

		$this->display('exportAspenData.tpl', 'Export Aspen Data',false);
	}

	function getBreadcrumbs(): array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Export Aspen Data');
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
				$prettyPrint = false;
				if (isset($_REQUEST['prettyPrint']) && ($_REQUEST['prettyPrint'] == 'on')){
					$prettyPrint = true;
				}
				fwrite($exportFileHnd, $exportObject->getJSONString(true, $prettyPrint) . "\n");
				$numObjectsExported++;
			}
		}
		$exportObject->__destruct();
		$exportObject = null;
		fclose($exportFileHnd);
		chgrp($exportFile, 'aspen_apache');
		chmod($exportFile, 0666);

		if ($numObjectsExported > 0){
			if (strlen($message) > 0) {
				$message .= '<br/>';
			}
			$message .= "Exported $numObjectsExported $pluralExportName";
		}

		return $message;
	}

	private function exportImages($message)
	{
		global $configArray;
		global $serverName;
		//files directory
		if ($configArray['System']['operatingSystem'] == 'windows'){
			$output = [];
			exec("cd c:/web/aspen-discovery/code/web/files/; tar -czf c:/data/aspen-discovery/$serverName/export/uploaded_images.tar.gz c:/web/aspen-discovery/code/web/files/*", $output);
		}else{
			$output = [];
			exec("cd /usr/local/aspen-discovery/code/web/files; tar -czf /data/aspen-discovery/$serverName/export/uploaded_images.tar.gz *", $output);
		}

		//uploaded covers
		if ($configArray['System']['operatingSystem'] == 'windows') {
			$output = [];
			exec("cd c:/data/aspen-discovery/$serverName/images/original/; tar -czf c:/data/aspen-discovery/$serverName/export/uploaded_covers.tar.gz cd c:/data/aspen-discovery/$serverName/images/original/*", $output);
		}else{
			exec("cd /data/aspen-discovery/$serverName/images/original; tar -czf /data/aspen-discovery/$serverName/export/uploaded_covers.tar.gz *", $output);
		}
		$message .= "Exported Uploaded Files";

		return $message;
	}
}