<?php

require_once ROOT_DIR . '/JSON_Action.php';

class EBSCO_JSON extends JSON_Action {
	/** @noinspection PhpUnused */
	public function dismissResearchStarter(): array {
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'ID was not provided',
			];
		}
		$result = [
			'success' => false,
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		$id = $_REQUEST['id'];

		require_once ROOT_DIR . '/sys/Ebsco/ResearchStarter.php';
		$researchStarter = new ResearchStarter();
		$researchStarter->id = $id;
		if ($researchStarter->find(true)) {
			require_once ROOT_DIR . '/sys/Ebsco/ResearchStarterDismissal.php';
			$dismissal = new ResearchStarterDismissal();
			$dismissal->researchStarterId = $id;
			$dismissal->userId = UserAccount::getActiveUserId();
			$dismissal->insert();
			$result = [
				'success' => true,
				'title' => 'Research Starter Dismissed',
				'message' => "This research starter will not be shown again.  You can hide all research starters by editing <a href='/MyAccount/MyPreferences'>your preferences</a>.",
			];
		} else {
			$result['message'] = 'Could not find that Research Starter';
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	public function trackEdsUsage(): array {
		global $library;
		if (!isset($_REQUEST['id'])) {
			return [
				'success' => false,
				'message' => 'ID was not provided',
			];
		}
		$id = $_REQUEST['id'];

		//Track usage of the record
		require_once ROOT_DIR . '/sys/Ebsco/EbscoEdsRecordUsage.php';
		$ebscoEdsRecordUsage = new EbscoEdsRecordUsage();
		global $aspenUsage;
		$ebscoEdsRecordUsage->instance = $aspenUsage->getInstance();
		$ebscoEdsRecordUsage->ebscoId = $id;
		$ebscoEdsRecordUsage->year = date('Y');
		$ebscoEdsRecordUsage->month = date('n');
		if ($ebscoEdsRecordUsage->find(true)) {
			$ebscoEdsRecordUsage->timesUsed++;
			$ret = $ebscoEdsRecordUsage->update();
			if ($ret == 0) {
				echo("Unable to update times used");
			}
		} else {
			$ebscoEdsRecordUsage->timesViewedInSearch = 0;
			$ebscoEdsRecordUsage->timesUsed = 1;
			$ebscoEdsRecordUsage->insert();
		}

		$userObj = UserAccount::getActiveUserObj();
		$userEbscoTracking = $userObj->userCookiePreferenceEbscoEds;

		if ($userEbscoTracking && $library->cookieStorageConsent) {
			$userId = UserAccount::getActiveUserId();
			if ($userId) {
				//Track usage for the user
				require_once ROOT_DIR . '/sys/Ebsco/UserEbscoEdsUsage.php';
				$userEbscoEdsUsage = new UserEbscoEdsUsage();
				global $aspenUsage;
				$userEbscoEdsUsage->instance = $aspenUsage->getInstance();
				$userEbscoEdsUsage->userId = $userId;
				$userEbscoEdsUsage->year = date('Y');
				$userEbscoEdsUsage->month = date('n');

				if ($userEbscoEdsUsage->find(true)) {
					$userEbscoEdsUsage->usageCount++;
					$userEbscoEdsUsage->update();
				} else {
					$userEbscoEdsUsage->usageCount = 1;
					$userEbscoEdsUsage->insert();
				}
			}
		} 

		return [
			'success' => true,
			'message' => 'Updated usage for EBSCO EDS record ' . $id,
		];
	}

	/** @noinspection PhpUnused */
	function getResearchStarters(): array {
		global $enabledModules;
		if (array_key_exists('EBSCO EDS', $enabledModules)) {
			require_once ROOT_DIR . '/sys/SearchObject/EbscoEdsSearcher.php';
			$edsSearcher = new SearchObject_EbscoEdsSearcher();
			$researchStarters = $edsSearcher->getResearchStarters($_REQUEST['lookfor']);
			$result = [
				'success' => true,
				'researchStarters' => '',
			];
			foreach ($researchStarters as $researchStarter) {
				$result['researchStarters'] .= $researchStarter->getDisplayHtml();
			}
			return $result;
		} else {
			return [
				'success' => true,
				'researchStarters' => '',
			];
		}
	}

	/** @noinspection PhpUnused */
	function getTitleAuthor(): array {
		$result = [
			'success' => false,
			'title' => 'Unknown',
			'author' => 'Unknown',
		];
		require_once ROOT_DIR . '/RecordDrivers/EbscoRecordDriver.php';
		$id = $_REQUEST['id'];
		if (!empty($id)) {
			$recordDriver = new EbscohostRecordDriver($id);
			if ($recordDriver->isValid()) {
				$result['success'] = true;
				$result['title'] = $recordDriver->getTitle();
				$result['author'] = $recordDriver->getAuthor();
			}
		}
		return $result;
	}
}