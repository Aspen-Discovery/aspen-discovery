<?php

class ExternalEContent_AccessOnline extends Action {
	/** @var ExternalEContentDriver $recordDriver */
	private $recordDriver;

	function launch() {
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		global $activeRecordProfile;
		if (isset($activeRecordProfile)) {
			$subType = $activeRecordProfile;
		} else {
			$indexingProfile = new IndexingProfile();
			$indexingProfile->name = 'ils';
			if ($indexingProfile->find(true)) {
				$subType = $indexingProfile->name;
			} else {
				$indexingProfile = new IndexingProfile();
				$indexingProfile->id = 1;
				if ($indexingProfile->find(true)) {
					$subType = $indexingProfile->name;
				}
			}
		}

		/** @var ExternalEContentDriver $recordDriver */
		require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';
		$this->recordDriver = new ExternalEContentDriver($subType . ':' . $id);

		if ($this->recordDriver->isValid()) {
			$relatedRecord = $this->recordDriver->getRelatedRecord();
			if ($relatedRecord) {
				$recordActions = $relatedRecord->getActions();
			} else {
				$recordActions = [];
			}

			$actionIndex = $_REQUEST['index'];
			$selectedAction = $recordActions[$actionIndex];
			$redirectUrl = $selectedAction['redirectUrl'];

			//Track Usage
			global $sideLoadSettings;
			$sideLoadId = -1;
			foreach ($sideLoadSettings as $sideLoad) {
				if ($sideLoad->name == $this->recordDriver->getRecordType()) {
					$sideLoadId = $sideLoad->id;
				}
			}

			if ($sideLoadId != -1) {
				$this->trackRecordUsage($sideLoadId, $this->recordDriver->getId());
				$this->trackUserUsageOfSideLoad($sideLoadId);
			}
		} else {
			$redirectUrl = $this->recordDriver->getLinkUrl(true);
		}

		header('Location: ' . $redirectUrl);
		die();
	}

	function trackRecordUsage(int $sideLoadId, string $recordId): void {
		require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';
		$recordUsage = new SideLoadedRecordUsage();
		global $aspenUsage;
		$recordUsage->instance = $aspenUsage->instance;
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
		global $aspenUsage;
		$userUsage->instance = $aspenUsage->instance;
		if (UserAccount::getActiveUserId() == false) {
			//User is not logged in
			$userUsage->userId = -1;
		} else {
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

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb($this->recordDriver->getRecordUrl(), $this->recordDriver->getTitle(), false);
		$breadcrumbs[] = new Breadcrumb('', 'Access Online');
		return $breadcrumbs;
	}
}