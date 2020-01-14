<?php
require_once 'Record.php';

class Record_AccessOnline extends Record_Record
{
	function launch(){
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);
		$recordDriver = $this->recordDriver;

		$relatedRecord = $recordDriver->getRelatedRecord();
		$recordActions = $relatedRecord->getActions();

		$actionIndex = $_REQUEST['index'];
		$selectedAction = $recordActions[$actionIndex];
		$redirectUrl = $selectedAction['redirectUrl'];

		//Track Usage
		global $sideLoadSettings;
		$sideLoadId = -1;
		foreach ($sideLoadSettings as $sideLoad){
			if ($sideLoad->name == $recordDriver->getRecordType()){
				$sideLoadId = $sideLoad->id;
			}
		}

		$this->trackRecordUsage($sideLoadId, $recordDriver->getId());
		$this->trackUserUsageOfSideLoad($sideLoadId);

		header('Location: ' . $redirectUrl);
		die();
	}

	function trackRecordUsage(int $sideLoadId, string $recordId): void
	{
		require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';
		$recordUsage = new SideLoadedRecordUsage();
		$recordUsage->sideloadId = $sideLoadId;
		$recordUsage->recordId = $recordId;
		$recordUsage->year = date('Y');
		$recordUsage->month = date('n');
		if ($recordUsage->find(true)) {
			$recordUsage->timesUsed++;
			$recordUsage->update();
		} else {
			$recordUsage->timesUsed = 1;
			$recordUsage->insert();
		}
	}

	public function trackUserUsageOfSideLoad(int $sideLoadId): void
	{
		require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
		$userUsage = new UserSideLoadUsage();
		if (UserAccount::getActiveUserId() == false){
			//User is not logged in
			$userUsage->userId = -1;
		}else{
			$userUsage->userId = UserAccount::getActiveUserId();
		}
		$userUsage->sideLoadId = $sideLoadId;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');

		if ($userUsage->find(true)) {
			$userUsage->usageCount++;
			$userUsage->update();
		} else {
			$userUsage->usageCount = 1;
			$userUsage->insert();
		}
	}
}