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
				'classFile' => ROOT_DIR . '/sys/Administration/UserMessage.php',
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
				'classFile' => ROOT_DIR . '/sys/Administration/BlockPatronAccountLink',
				'className' => 'BlockPatronAccountLink',
				'name' => 'Block Patron Account Links'
			],
		];

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
					foreach ($elements as $element => $elementDefinition){
						if (in_array($element, $_REQUEST['dataElement'])){
							require_once $elementDefinition['classFile'];
							$message = $this->exportObjects($elementDefinition['className'], $elementDefinition['name'], $exportPath .  $element . '.json', $selectedFilters, $message);
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