<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/RBdigital/UserRBdigitalUsage.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalRecordUsage.php';
require_once ROOT_DIR . '/sys/RBdigital/RBdigitalMagazineUsage.php';

class RBdigital_Dashboard extends Admin_Admin
{
    function launch()
    {
        global $interface;

        $thisMonth = date('n');
        $thisYear = date('Y');
        $lastMonth = $thisMonth - 1;
        $lastMonthYear = $thisYear;
        if ($lastMonth == 0){
            $lastMonth = 12;
            $lastMonthYear--;
        }
        $lastYear = $thisYear -1 ;
        //Generate stats

        $activeUsersThisMonth = $this->getUserStats($thisMonth, $thisYear);
        $interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
        $activeUsersLastMonth = $this->getUserStats($lastMonth, $lastMonthYear);
        $interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
        $activeUsersThisYear = $this->getUserStats(null, $thisYear);
        $interface->assign('activeUsersThisYear', $activeUsersThisYear);
        $activeUsersLastYear = $this->getUserStats(null, $lastYear);
        $interface->assign('activeUsersLastYear', $activeUsersLastYear);
        $activeUsersAllTime = $this->getUserStats(null, null);
        $interface->assign('activeUsersAllTime', $activeUsersAllTime);

        list($activeRecordsThisMonth, $loansThisMonth, $holdsThisMonth, $activeMagazinesThisMonth, $magazineLoansThisMonth) = $this->getRecordStats($thisMonth, $thisYear);
        $interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
        $interface->assign('loansThisMonth', $loansThisMonth);
        $interface->assign('holdsThisMonth', $holdsThisMonth);
	    $interface->assign('activeMagazinesThisMonth', $activeMagazinesThisMonth);
	    $interface->assign('magazineLoansThisMonth', $magazineLoansThisMonth);
        list($activeRecordsLastMonth, $loansLastMonth, $holdsLastMonth, $activeMagazinesLastMonth, $magazineLoansLastMonth) = $this->getRecordStats($lastMonth, $lastMonthYear);
        $interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
        $interface->assign('loansLastMonth', $loansLastMonth);
        $interface->assign('holdsLastMonth', $holdsLastMonth);
	    $interface->assign('activeMagazinesLastMonth', $activeMagazinesLastMonth);
	    $interface->assign('magazineLoansLastMonth', $magazineLoansLastMonth);
        list($activeRecordsThisYear, $loansThisYear, $holdsThisYear, $activeMagazinesThisYear, $magazineLoansThisYear) = $this->getRecordStats(null, $thisYear);
        $interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
        $interface->assign('loansThisYear', $loansThisYear);
        $interface->assign('holdsThisYear', $holdsThisYear);
	    $interface->assign('activeMagazinesThisYear', $activeMagazinesThisYear);
	    $interface->assign('magazineLoansThisYear', $magazineLoansThisYear);
        list($activeRecordsLastYear, $loansLastYear, $holdsLastYear, $activeMagazinesLastYear, $magazineLoansLastYear) = $this->getRecordStats(null, $lastYear);
        $interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
        $interface->assign('loansLastYear', $loansLastYear);
        $interface->assign('holdsLastYear', $holdsLastYear);
	    $interface->assign('activeMagazinesLastYear', $activeMagazinesLastYear);
	    $interface->assign('magazineLoansLastYear', $magazineLoansLastYear);
        list($activeRecordsAllTime, $loansAllTime, $holdsAllTime, $activeMagazinesAllTime, $magazineLoansAllTime) = $this->getRecordStats(null, null);
        $interface->assign('activeRecordsAllTime', $activeRecordsAllTime);
        $interface->assign('loansAllTime', $loansAllTime);
        $interface->assign('holdsAllTime', $holdsAllTime);
	    $interface->assign('activeMagazinesAllTime', $activeMagazinesAllTime);
	    $interface->assign('magazineLoansAllTime', $magazineLoansAllTime);

        $this->display('dashboard.tpl', 'RBdigital Dashboard');
    }

    function getAllowableRoles(){
        return array('opacAdmin', 'libraryAdmin', 'cataloging');
    }

    /**
     * @param string|null $month
     * @param string|null $year
     * @return int
     */
    public function getUserStats($month, $year): int
    {
        $userUsage = new UserRBdigitalUsage();
        if ($month != null){
            $userUsage->month = $month;
        }
        if ($year != null){
            $userUsage->year = $year;
        }
        $activeUsersThisMonth = $userUsage->count();
        return $activeUsersThisMonth;
    }

    /**
     * @param string|null $month
     * @param string|null $year
     * @return array
     */
    public function getRecordStats($month, $year): array
    {
        $usage = new RBdigitalRecordUsage();
        if ($month != null){
            $usage->month = $month;
        }
        if ($year != null){
            $usage->year = $year;
        }
        $usage->selectAdd(null);
        $usage->selectAdd('COUNT(id) as recordsUsed');
        $usage->selectAdd('SUM(timesHeld) as totalHolds');
        $usage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
        $usage->find(true);

	    $magazineUsage = new RBdigitalMagazineUsage();
	    if ($month != null){
		    $magazineUsage->month = $month;
	    }
	    if ($year != null){
		    $magazineUsage->year = $year;
	    }
	    $magazineUsage->selectAdd(null);
	    $magazineUsage->selectAdd('COUNT(id) as recordsUsed');
	    $magazineUsage->selectAdd('SUM(timesCheckedOut) as totalCheckouts');
	    $magazineUsage->find(true);

        /** @noinspection PhpUndefinedFieldInspection */
        return [
        	$usage->recordsUsed,
	        (($usage->totalCheckouts != null) ? $usage->totalCheckouts : 0),
	        (($usage->totalHolds != null) ? $usage->totalHolds : 0),
	        $magazineUsage->recordsUsed,
	        (($magazineUsage->totalCheckouts != null) ? $magazineUsage->totalCheckouts : 0),
        ];
    }

}