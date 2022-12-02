<?php
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Greenhouse_ClearAspenData extends Admin_Admin {
	function launch() {
		global $interface;

		if (isset($_REQUEST['submit'])) {
			$submissionResults = [
				'success' => false,
				'message' => 'Nothing was selected to be cleaned',
			];

			$success = false;
			global $serverName;
			if (!isset($_REQUEST['confirmation']) || ($_REQUEST['confirmation'] != $serverName)) {
				$message = 'Confirmation did not match, not deleting data!';
			} else {
				set_time_limit(0);
				$message = '';
				//Get a list of all admin users so we can preserve data for them.
				$adminLists = new User();
				$adminListIds = $adminLists->fetchAll('id');
				$adminListIdsString = implode(',', $adminListIds);

				foreach ($_REQUEST['dataElement'] as $element) {
					if ($element == 'bibData') {
						require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
						$message .= $this->deleteAll('IlsVolumeInfo');

						//Get the source for our indexing profile
						require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
						$indexingProfile = new IndexingProfile();
						$indexingProfileNames = $indexingProfile->fetchAll('id', 'name');

						require_once ROOT_DIR . '/sys/Indexing/IndexedRecordSource.php';
						$indexedRecordSource = new IndexedRecordSource();
						$indexedRecordSource->whereAddIn("source", $indexingProfileNames, true);
						$indexedSourceIds = $indexedRecordSource->fetchAll('id');
						$indexedSourceIdsStr = implode(', ', $indexedSourceIds);

						require_once ROOT_DIR . '/sys/Grouping/GroupedWorkItemUrl.php';
						$objectToDelete = new GroupedWorkItemUrl();
						$objectToDelete->whereAdd("groupedWorkItemId IN (SELECT id from grouped_work_record_items WHERE groupedWorkRecordId IN (SELECT id from grouped_work_records where sourceId IN ($indexedSourceIdsStr)))");
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						require_once ROOT_DIR . '/sys/Grouping/GroupedWorkItem.php';
						$objectToDelete = new GroupedWorkItem();
						$objectToDelete->whereAdd("groupedWorkRecordId IN (SELECT id from grouped_work_records where sourceId IN ($indexedSourceIdsStr))");
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
						$objectToDelete = new GroupedWorkRecord();
						$objectToDelete->whereAddIn("sourceId", $indexedSourceIds, false);
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
						$objectToDelete = new GroupedWorkPrimaryIdentifier();
						$objectToDelete->whereAddIn("type", $indexingProfileNames, true);
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						require_once ROOT_DIR . '/sys/Indexing/IlsRecord.php';
						$objectToDelete = new IlsRecord();
						$objectToDelete->whereAddIn("source", $indexingProfileNames, true);
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						//Finally clear solr data
						$solrSearcher = SearchObjectFactory::initSearchObject('GroupedWork');
						/** @var Solr $index */
						$index = $solrSearcher->getIndexEngine();
						if ($index->deleteAllRecords()) {
							$message .= translate([
									'text' => 'Cleared Solr Index',
									'isAdminFacing' => true,
								]) . '<br/>';
						}

						//Also force a nightly index
						require_once ROOT_DIR . '/sys/SystemVariables.php';
						SystemVariables::forceNightlyIndex();

						$indexingProfile = new IndexingProfile();
						$allIndexingProfiles = $indexingProfile->fetchAll();
						foreach ($allIndexingProfiles as $indexingProfile) {
							$indexingProfile->runFullUpdate = true;
							$indexingProfile->update();
						}

						$success = true;
					} elseif ($element == 'userData') {
						require_once ROOT_DIR . '/sys/Account/User.php';
						$objectToDelete = new User();
						$objectToDelete->whereAdd("source <> 'admin'");
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						//Get a list of all admin users so we can preserve data for them.
						$adminUsers = new User();
						$adminUsers->source = 'admin';
						$adminUserIds = $adminUsers->fetchAll('id');
						$adminUserIdsString = implode(',', $adminUserIds);

						require_once ROOT_DIR . '/sys/Account/UserLink.php';
						$message .= $this->deleteAll('UserLink');

						require_once ROOT_DIR . '/sys/Account/UserMessage.php';
						$message .= $this->deleteAll('UserMessage');

						require_once ROOT_DIR . '/sys/Account/UserPayment.php';
						$message .= $this->deleteAll('UserPayment');

						require_once ROOT_DIR . '/sys/Account/UserStaffSettings.php';
						$message .= $this->deleteAll('UserStaffSettings');

						require_once ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php';
						$message .= $this->deleteAll('BlockPatronAccountLink');

						require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
						$objectToDelete = new UserRoles();
						$objectToDelete->whereAdd("userId NOT IN ($adminUserIdsString)");
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
						$message .= $this->deleteAll('BrowseCategoryDismissal');

						require_once ROOT_DIR . '/sys/Donations/Donation.php';
						$message .= $this->deleteAll('Donation');

						require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
						$message .= $this->deleteAll('NotInterested');

						require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php';
						$message .= $this->deleteAll('PlacardDismissal');

						require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
						$message .= $this->deleteAll('SystemMessageDismissal');

						require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
						$message .= $this->deleteAll('UserWorkReview');

						require_once ROOT_DIR . '/sys/User/AccountSummary.php';
						$message .= $this->deleteAll('AccountSummary');

						require_once ROOT_DIR . '/sys/User/Checkout.php';
						$message .= $this->deleteAll('Checkout');

						require_once ROOT_DIR . '/sys/User/Hold.php';
						$message .= $this->deleteAll('Hold');

						require_once ROOT_DIR . '/sys/MaterialsRequest.php';
						$message .= $this->deleteAll('MaterialsRequest');

						require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
						$message .= $this->deleteAll('ReadingHistoryEntry');

						require_once ROOT_DIR . '/sys/SearchEntry.php';
						$objectToDelete = new SearchEntry();
						$objectToDelete->whereAdd("user_id NOT IN ($adminUserIdsString)");
						$numDeleted = $objectToDelete->delete(true);
						$message .= translate([
								'text' => 'Deleted %1% %2% objects',
								1 => $numDeleted,
								2 => get_class($objectToDelete),
								'isAdminFacing' => true,
							]) . '<br/>';

						$success = true;
					} elseif ($element == 'userLists') {
						require_once ROOT_DIR . '/sys/UserLists/UserList.php';
						$message .= $this->deleteAll('UserList');

						require_once ROOT_DIR . '/sys/UserLists/UserListEntry.php';
						$message .= $this->deleteAll('UserListEntry');
					} elseif ($element == 'statistics') {
						require_once ROOT_DIR . '/sys/Axis360/Axis360RecordUsage.php';
						$message .= $this->deleteAll('Axis360RecordUsage');

						require_once ROOT_DIR . '/sys/Axis360/UserAxis360Usage.php';
						$message .= $this->deleteAll('UserAxis360Usage');

						require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryRecordUsage.php';
						$message .= $this->deleteAll('CloudLibraryRecordUsage');

						require_once ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php';
						$message .= $this->deleteAll('UserCloudLibraryUsage');

						require_once ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php';
						$message .= $this->deleteAll('EbscoEdsRecordUsage');

						require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
						$message .= $this->deleteAll('UserEbscoEdsUsage');

						require_once ROOT_DIR . '/sys/Events/EventsUsage.php';
						$message .= $this->deleteAll('EventsUsage');

						require_once ROOT_DIR . '/sys/Events/UserEventsUsage.php';
						$message .= $this->deleteAll('UserEventsUsage');

						require_once ROOT_DIR . '/sys/Hoopla/HooplaRecordUsage.php';
						$message .= $this->deleteAll('HooplaRecordUsage');

						require_once ROOT_DIR . '/sys/Hoopla/UserHooplaUsage.php';
						$message .= $this->deleteAll('UserHooplaUsage');

						require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';
						$message .= $this->deleteAll('ILSRecordUsage');

						require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
						$message .= $this->deleteAll('UserILSUsage');

						require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';
						$message .= $this->deleteAll('SideLoadedRecordUsage');

						require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
						$message .= $this->deleteAll('UserSideLoadUsage');

						require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecordUsage.php';
						$message .= $this->deleteAll('OpenArchivesRecordUsage');

						require_once ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php';
						$message .= $this->deleteAll('UserOpenArchivesUsage');

						require_once ROOT_DIR . '/sys/OverDrive/OverDriveRecordUsage.php';
						$message .= $this->deleteAll('OverDriveRecordUsage');

						require_once ROOT_DIR . '/sys/OverDrive/UserOverDriveUsage.php';
						$message .= $this->deleteAll('UserOverDriveUsage');

						require_once ROOT_DIR . '/sys/OverDrive/OverDriveStats.php';
						$message .= $this->deleteAll('OverDriveStats');

						require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
						$message .= $this->deleteAll('APIUsage');

						require_once ROOT_DIR . '/sys/SystemLogging/AspenUsage.php';
						$message .= $this->deleteAll('AspenUsage');

						require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';
						$message .= $this->deleteAll('ExternalRequestLogEntry');

						require_once ROOT_DIR . '/sys/SystemLogging/SlowAjaxRequest.php';
						$message .= $this->deleteAll('SlowAjaxRequest');

						require_once ROOT_DIR . '/sys/SystemLogging/SlowPage.php';
						$message .= $this->deleteAll('SlowPage');

						require_once ROOT_DIR . '/sys/SystemLogging/UsageByIPAddress.php';
						$message .= $this->deleteAll('UsageByIPAddress');

						require_once ROOT_DIR . '/sys/WebBuilder/WebResourceUsage.php';
						$message .= $this->deleteAll('WebResourceUsage');

						require_once ROOT_DIR . '/sys/WebsiteIndexing/UserWebsiteUsage.php';
						$message .= $this->deleteAll('UserWebsiteUsage');

						require_once ROOT_DIR . '/sys/WebsiteIndexing/WebPageUsage.php';
						$message .= $this->deleteAll('WebPageUsage');

						$success = true;
					} elseif ($element == 'webBuilderContent') {
						require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
						$message .= $this->deleteAll('BasicPage');

						require_once ROOT_DIR . '/sys/WebBuilder/BasicPageAccess.php';
						$message .= $this->deleteAll('BasicPageAccess');

						require_once ROOT_DIR . '/sys/WebBuilder/BasicPageAudience.php';
						$message .= $this->deleteAll('BasicPageAudience');

						require_once ROOT_DIR . '/sys/WebBuilder/BasicPageCategory.php';
						$message .= $this->deleteAll('BasicPageCategory');

						require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
						$message .= $this->deleteAll('CustomForm');

						require_once ROOT_DIR . '/sys/WebBuilder/CustomFormField.php';
						$message .= $this->deleteAll('CustomFormField');

						require_once ROOT_DIR . '/sys/WebBuilder/CustomFormSubmission.php';
						$message .= $this->deleteAll('CustomFormSubmission');

						require_once ROOT_DIR . '/sys/WebBuilder/LibraryBasicPage.php';
						$message .= $this->deleteAll('LibraryBasicPage');

						require_once ROOT_DIR . '/sys/WebBuilder/LibraryCustomForm.php';
						$message .= $this->deleteAll('LibraryCustomForm');

						require_once ROOT_DIR . '/sys/WebBuilder/LibraryPortalPage.php';
						$message .= $this->deleteAll('LibraryPortalPage');

						require_once ROOT_DIR . '/sys/WebBuilder/LibraryWebResource.php';
						$message .= $this->deleteAll('LibraryWebResource');

						require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';
						$message .= $this->deleteAll('PortalCell');

						require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
						$message .= $this->deleteAll('PortalPage');

						require_once ROOT_DIR . '/sys/WebBuilder/PortalPageAccess.php';
						$message .= $this->deleteAll('PortalPageAccess');

						require_once ROOT_DIR . '/sys/WebBuilder/PortalPageAudience.php';
						$message .= $this->deleteAll('PortalPageAudience');

						require_once ROOT_DIR . '/sys/WebBuilder/PortalPageCategory.php';
						$message .= $this->deleteAll('PortalPageCategory');

						require_once ROOT_DIR . '/sys/WebBuilder/PortalRow.php';
						$message .= $this->deleteAll('PortalRow');

						require_once ROOT_DIR . '/sys/WebBuilder/StaffMember.php';
						$message .= $this->deleteAll('StaffMember');

						require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderAudience.php';
						$message .= $this->deleteAll('WebBuilderAudience');

						require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderCategory.php';
						$message .= $this->deleteAll('WebBuilderCategory');

						require_once ROOT_DIR . '/sys/WebBuilder/WebBuilderMenu.php';
						$message .= $this->deleteAll('WebBuilderMenu');

						require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
						$message .= $this->deleteAll('WebResource');

						require_once ROOT_DIR . '/sys/WebBuilder/WebResourceAudience.php';
						$message .= $this->deleteAll('WebResourceAudience');

						require_once ROOT_DIR . '/sys/WebBuilder/WebResourceCategory.php';
						$message .= $this->deleteAll('WebResourceCategory');

						require_once ROOT_DIR . '/sys/WebBuilder/WebResourceUsage.php';
						$message .= $this->deleteAll('WebResourceUsage');

						$success = true;
					}
				}
			}

			if (!empty($message)) {
				$submissionResults['message'] = $message;
				$submissionResults['success'] = $success;
			}

			$interface->assign('submissionResults', $submissionResults);
		}
		$this->display('clearAspenData.tpl', 'Clear Aspen Data', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('', 'Clear Aspen Data');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}

	function canView(): bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->source == 'admin' && UserAccount::getActiveUserObj()->cat_username == 'aspen_admin') {
				return true;
			}
		}
		return false;
	}

	private function deleteAll(string $className): string {
		/** @var DataObject $objectToDelete */
		$objectToDelete = new $className();
		$numDeleted = $objectToDelete->deleteAll();
		return translate([
				'text' => 'Deleted %1% %2% objects',
				1 => $numDeleted,
				2 => get_class($objectToDelete),
				'isAdminFacing' => true,
			]) . '<br/>';
	}
}