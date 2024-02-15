<?php

require_once ROOT_DIR . '/RecordDrivers/SummonRecordDriver.php';

class Summon_AccessOnline extends Action {

    private $recordDriver;

    function launch() {
        global $interface;
        $id = urldecode($_REQUEST['id']);

        $this->recordDriver = new SummonRecordDriver($id);

        if ($this->recordDriver->isValid()) {
            $activeIP = IPAddress::getActiveIp();
            $subnet = IPAddress::getIPAddressForIp($activeIP);
            $okToAccess = false;
            if ($subnet != false && $subnet->authenticatedForSummon) {
                $okToAccess = UserAccount::isLoggedIn();
            }

            if ($okToAccess) {
                require_once ROOT_DIR . '/sys/Summon/SummonRecordUsage.php';
                $summonRecordUsage = new SummonRecordUsage();
                global $aspenUsage;
                $summonRecordUsage->instance = $aspenUsage->getInstance();
                $summonRecordUsage->summonId = $id;
                $summonRecordUsage->year = date('Y');
                $summonRecordUsage->month = date('n');
                if ($summonRecordUsage->find(true)) {
                    $summonRecordUsage->timesUsed++;
                    $ret = $summonRecordUsage->update();
                    if ($ret == 0) {
                        echo("Unable to update times used");
                    }
                } else {
                    $summonRecordUsage->timesViewedInSearch = 0;
                    $summonRecordUsage->timesUsed = 1;
                    $summonRecordUsage->insert();
                }

                $userId = UserAccount::getActiveUserId();
                if ($userId) {
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
                header('Location:' . $this->recordDriver->getRecordUrl());
                die();
            } else {
                require_once ROOT_DIR . '/services/MyAccount/Login.php';
                $launchAction = new MyAccount_Login();
                $_REQUEST['followupModule'] = 'Summon';
                $_REQUEST['followUpAction'] = 'AccessOnline';
                $_REQUEST['recordId'] = $id;

                $error_msg = translate([
                    'text' => 'You must be logged in to access content from Summon',
                    'isPublicFacing' => true,
                ]);
                $launchAction->launch($error_msg);
            }
        } else {
            $this->display('../Record/invalidRecord.tpl', 'Invalid Record', '');
            die();
        }
    }

    function getBreadcrumbs(): array {
        $breadcrumbs = [];
        if (!empty($this->lastSearch)) {
            $breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Articles and Databases Search Results');
        }
        $breadcrumbs[] = new Breadcrumb('', $this->recordDriver->getTitle());
        return $breadcrumbs;
    }
}