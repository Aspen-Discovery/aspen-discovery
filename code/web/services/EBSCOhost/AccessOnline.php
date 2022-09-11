<?php

require_once ROOT_DIR . '/RecordDrivers/EbscohostRecordDriver.php';
class EBSCOhost_AccessOnline extends Action{

	private $recordDriver;
	function launch() {
		global $interface;
		$id = urldecode($_REQUEST['id']);

		$this->recordDriver = new EbscohostRecordDriver($id);

		if ($this->recordDriver->isValid()){
			//Make sure the user has been validated to view the record based on IP or login
			$activeIP = IPAddress::getActiveIp();
			$subnet = IPAddress::getIPAddressForIP($activeIP);
			$okToAccess = false;
			if ($subnet != false && $subnet->authenticatedForEBSCOhost){
				$okToAccess = true;
			}else{
				$okToAccess = UserAccount::isLoggedIn();
			}

			if ($okToAccess) {
				//Track usage of the record
				require_once ROOT_DIR . '/sys/Ebsco/EbscohostRecordUsage.php';
				$ebscoEdsRecordUsage = new EbscohostRecordUsage();
				global $aspenUsage;
				$ebscoEdsRecordUsage->instance = $aspenUsage->instance;
				$ebscoEdsRecordUsage->ebscohostId = $id;
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

				$userId = UserAccount::getActiveUserId();
				if ($userId) {
					//Track usage for the user
					require_once ROOT_DIR . '/sys/Ebsco/UserEbscohostUsage.php';
					$userEbscohostUsage = new UserEbscohostUsage();
					global $aspenUsage;
					$userEbscohostUsage->instance = $aspenUsage->instance;
					$userEbscohostUsage->userId = $userId;
					$userEbscohostUsage->year = date('Y');
					$userEbscohostUsage->month = date('n');

					if ($userEbscohostUsage->find(true)) {
						$userEbscohostUsage->usageCount++;
						$userEbscohostUsage->update();
					} else {
						$userEbscohostUsage->usageCount = 1;
						$userEbscohostUsage->insert();
					}
				}

				header('Location:' . $this->recordDriver->getRecordUrl());
				die();
			}else{
				require_once ROOT_DIR . '/services/MyAccount/Login.php';
				$launchAction = new MyAccount_Login();
				$_REQUEST['followupModule'] = 'EBSCOhost';
				$_REQUEST['followupAction'] = 'AccessOnline';
				$_REQUEST['recordId'] = $id;

				$error_msg = translate(['text'=>'You must be logged in to access content from EBSCOhost','isPublicFacing'=>true]);
				$launchAction->launch($error_msg);
			}
		}else{
			$this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
			die();
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		if (!empty($this->lastSearch)){
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Article & Database Search Results');
		}
		$breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
		return $breadcrumbs;
	}
}