<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Summon_JSON extends JSON_Action {
    /**@noinspection PhpUnused */
    public function trackSummonUsage(): array {
		global $library;
        if (!isset($_REQUEST['id'])) {
            return [
                'success' => false,
                'message' => 'ID was not provided',
            ];
        }
        $id = $_REQUEST['id'];

        require_once ROOT_DIR . '/sys/Summon/SummonRecordUsage.php';
        $summonRecordUsage = new SummonRecordUsage();
        global $aspenUsage;
        $summonRecordUsage->instance = $aspenUsage->getInstance();
        $summonRecordUsage->summonId = $id;
        $summonRecordUsage->year = date('Y');
        $summonRecordUsage->month =  date('n');
        if ($summonRecordUsage->find(true)) {
            $summonRecordUsage->timesUsed++;
            $ret = $summonRecordUsage->update();
            if ($ret == 0) {
                echo ("Unable to update times used");
            }
        } else {
            $summonRecordUsage->timesViewedInSearch = 0;
            $summonRecordUsage->timesUsed = 1;
            $summonRecordUsage->insert();
        }

		$userObj = UserAccount::getActiveUserObj();
		$userSummonTracking = $userObj->userCookiePreferenceSummon;

		if ($userSummonTracking && $library->cookieStorageConsent) {
			$userId = UserAccount::getActiveUserId();
			if ($userId) {
				//Track usage for the user
				require_once ROOT_DIR . '/sys/Summon/UserSummonUsage.php';
				$userSummonUsage = new UserSummonUsage();
				global $aspenUsage;
				$userSummonUsage->instance = $aspenUsage->getInstance();
				$userSummonUsage->userId = $userId;
				$userSummonUsage->year = date('Y');
				$userSummonUsage->month = date('n');

				if ($userSummonUsage->find(true)) {
					$userSummonUsage->usageCount++;
					$userSummonUsage->update();
				} else {
					$userSummonUsage->usageCount = 1;
					$userSummonUsage->insert();
				}
			}
		}

		return [
			'success' => true,
			'message' => 'Updated usage for Summon record ' . $id,
		];
    }

	function getTitleAuthor(): array {
		$result = [
			'success' => false,
			'title' => 'Unknown',
			'author' => 'Unknown',
		];
		require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';
		$id = $_REQUEST['id'];
		if (!empty($id)) {
			$recordDriver = new SummonRecordDriver($id);
			if ($recordDriver->isValid()) {
				$result['success'] = true;
				$result['title'] = $recordDriver->getTitle();
				$result['author'] = $recordDriver->getAuthor();
			}
		}
		return $result;
	}
}