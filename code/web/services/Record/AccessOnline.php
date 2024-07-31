<?php

class Record_AccessOnline extends Action {
	/** @var SideLoadedRecord $recordDriver */
	private $recordDriver;
	private $id;

	function launch() {
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$variationId = isset($_REQUEST['variationId']) ? strip_tags($_REQUEST['variationId']) : '';
		$interface->assign('id', $id);

		if (strpos($id, ':')) {
			[
				$source,
				$id,
			] = explode(":", $id);
			$this->id = $id;
			$interface->assign('id', $this->id);
		} else {
			$source = 'ils';
		}

		//Check to see if the record exists within the resources table
		$this->recordDriver = RecordDriverFactory::initRecordDriverById($source . ':' . $this->id);

		if ($this->recordDriver->isValid()) {

			if ($variationId != 'any'){
				$relatedRecord = $this->recordDriver->getRelatedRecordForVariation($variationId);
			}else{
				$relatedRecord = $this->recordDriver->getRelatedRecord();
			}

			if ($relatedRecord != null) {
				$recordActions = $relatedRecord->getActions($variationId);

				$actionIndex = $_REQUEST['index'];
				if (isset($_REQUEST['itemId'])) {
					foreach ($recordActions as $recordAction) {
						if ($recordAction['itemId'] == $_REQUEST['itemId'] && $recordAction['index'] == $actionIndex) {
							$selectedAction = $recordAction;
							break;
						}
					}
				} else {
					$selectedAction = $recordActions[$actionIndex];
				}
				$redirectUrl = $selectedAction['redirectUrl'];

				//Track Usage
				global $sideLoadSettings;
				$sideLoadId = -1;
				foreach ($sideLoadSettings as $sideLoad) {
					if ($sideLoad->name == $this->recordDriver->getRecordType()) {
						$sideLoadId = $sideLoad->id;
					}
				}

				$this->trackRecordUsage($sideLoadId, $this->recordDriver->getId());
				$this->trackUserUsageOfSideLoad($sideLoadId);
				header('Location: ' . $redirectUrl);
			} else {
				$this->display('invalidRecord.tpl', 'Invalid Record', '');
			}
		} else {
			$this->display('invalidRecord.tpl', 'Invalid Record', '');
		}
		die();
	}

	function trackRecordUsage(int $sideLoadId, string $recordId): void {
		require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';
		$recordUsage = new SideLoadedRecordUsage();
		global $aspenUsage;
		$recordUsage->instance = $aspenUsage->getInstance();
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

	public function trackUserUsageOfSideLoad(int $sideLoadId): void {
		require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
		$userUsage = new UserSideLoadUsage();
		$userObj = UserAccount::getActiveUserObj();
		$userSideLoadTracking = $userObj->userCookiePreferenceSideLoad;
		global $aspenUsage;
		global $library;
		$userUsage->instance = $aspenUsage->getInstance();
		if (UserAccount::getActiveUserId() == false) {
			//User is not logged in
			$userUsage->userId = -1;
		} else {
			$userUsage->userId = UserAccount::getActiveUserId();
		}
		$userUsage->sideLoadId = $sideLoadId;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');

		if ($userSideLoadTracking && $library->cookieStorageConsent) {
			if ($userUsage->find(true)) {
				$userUsage->usageCount++;
				$userUsage->update();
			} else {
				$userUsage->usageCount = 1;
				$userUsage->insert();
			}
		}
	}

	public function getBreadcrumbs(): array {
		return [];
	}
}