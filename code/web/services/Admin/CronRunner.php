<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Admin_CronRunner extends Admin_Admin {

	function launch() : void {
		global $interface;
		//Get a list of cron processes that can be run manually
		$availableCronProcesses = [
			'checkSolrForDeletedWorks' => 'Check Solr for Deleted Works',
			'cleanupSharedSessions' => 'Cleanup Shared Sessions',
			'createSitemaps' => 'Create Sitemaps',
			'dismissYearInReviewMessages' => 'Dismiss Year-in-Review Messages',
			'fetchILSMessages' => 'Fetch ILS Messages',
			'fetchNotificationReceipts' => 'Fetch Notification Receipts',
			'generateMaterialRequestHoldCandidates' => 'Generate Material Request Hold Candidates',
			'loadInitialReadingHistory' => 'Load Initial Reading History',
			'purgeSoftDeleted' => 'Purge Soft-Deleted Objects',
			'sendCampaignEmails' => 'Send Campaign Emails',
			'sendCampaignEndingEmails' => 'Send Campaign Ending Emails',
			'sendILSMessages' => 'Send ILS Messages',
			'sendLiDANotifications' => 'Send LiDA Notifications',
			'talpaRecalculationCron' => 'Talpa Recalculation',
			'talpaWorksCron' => 'Talpa Works',
			'updateCommunityTranslations' => 'Update Community Translations',
			'updateNYTLists' => 'Update New York Times Lists',
			'updateSavedSearches' => 'Update Saved Searches',
			'updateSuggesters' => 'Update Suggesters',
			'updateEventRegistrationInvitations' => 'Update Event Registration Invites',
		];
		$interface->assign('availableCronProcesses', $availableCronProcesses);

		if (isset($_REQUEST['processToRun'])) {
			if (array_key_exists($_REQUEST['processToRun'], $availableCronProcesses)) {
				require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
				$result = SystemUtils::startBackgroundProcess($_REQUEST['processToRun'], []);
				$interface->assign('results', $result);
			}else{
				$interface->assign('error', 'Invalid process to run');
			}
		}

		$this->display('cronRunner.tpl', 'Cron Runner');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('', 'Manually Run Cron Processes');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Manually Run Cron Processes');
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	/**
	 * Get list of cron jobs that are considered "frequent" (e.g., run every few minutes).
	 * These jobs can be controlled by the logFrequentCrons system variable.
	 */
	public static function getFrequentCronJobs(): array {
		return [
			'Cleanup Shared Sessions',
			'Fetch ILS Messages',
			'Load Initial Reading History',
			'Send LiDA Notifications',
			'Update Event Registration Invites',
		];
	}
}