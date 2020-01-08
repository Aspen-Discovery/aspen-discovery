<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Indexing/UserSideLoadUsage.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoadedRecordUsage.php';

class SideLoads_Dashboard extends Admin_Admin
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

        /** @var SideLoad[] $sideLoadSettings*/
        global $sideLoadSettings;
        $profilesToGetStatsFor = [];
        foreach ($sideLoadSettings as $sideLoad){
            $profilesToGetStatsFor[$sideLoad->id] = $sideLoad->name;
        }
        $interface->assign('profiles', $profilesToGetStatsFor);

        $activeUsersThisMonth = $this->getUserStats($thisMonth, $thisYear, $profilesToGetStatsFor);
        $interface->assign('activeUsersThisMonth', $activeUsersThisMonth);
        $activeUsersLastMonth = $this->getUserStats($lastMonth, $lastMonthYear, $profilesToGetStatsFor);
        $interface->assign('activeUsersLastMonth', $activeUsersLastMonth);
        $activeUsersThisYear = $this->getUserStats(null, $thisYear, $profilesToGetStatsFor);
        $interface->assign('activeUsersThisYear', $activeUsersThisYear);
        $activeUsersLastYear = $this->getUserStats(null, $lastYear, $profilesToGetStatsFor);
        $interface->assign('activeUsersLastYear', $activeUsersLastYear);
        $activeUsersAllTime = $this->getUserStats(null, null, $profilesToGetStatsFor);
        $interface->assign('activeUsersAllTime', $activeUsersAllTime);

        $activeRecordsThisMonth = $this->getRecordStats($thisMonth, $thisYear, $profilesToGetStatsFor);
        $interface->assign('activeRecordsThisMonth', $activeRecordsThisMonth);
        $activeRecordsLastMonth = $this->getRecordStats($lastMonth, $lastMonthYear, $profilesToGetStatsFor);
        $interface->assign('activeRecordsLastMonth', $activeRecordsLastMonth);
        $activeRecordsThisYear = $this->getRecordStats(null, $thisYear, $profilesToGetStatsFor);
        $interface->assign('activeRecordsThisYear', $activeRecordsThisYear);
        $activeRecordsLastYear = $this->getRecordStats(null, $lastYear, $profilesToGetStatsFor);
        $interface->assign('activeRecordsLastYear', $activeRecordsLastYear);
        $activeRecordsAllTime = $this->getRecordStats(null, null, $profilesToGetStatsFor);
        $interface->assign('activeRecordsAllTime', $activeRecordsAllTime);

        $this->display('dashboard.tpl', 'Side Load Dashboard');
    }

    function getAllowableRoles(){
        return array('opacAdmin');
    }

    /**
     * @param string|null $month
     * @param string|null $year
     * @param int[] $profilesToGetStatsFor
     * @return int[]
     */
    public function getUserStats($month, $year, $profilesToGetStatsFor): array
    {
        $userUsage = new UserSideLoadUsage();
        if ($month != null){
            $userUsage->month = $month;
        }
        if ($year != null){
            $userUsage->year = $year;
        }
        $userUsage->groupBy('sideLoadId');
        $userUsage->selectAdd();
        $userUsage->selectAdd('sideLoadId');
        $userUsage->selectAdd('COUNT(id) as numUsers');
        $userUsage->find();
        $usageStats = [];
        foreach ($profilesToGetStatsFor as $id => $name){
            $usageStats[$id] = 0;
        }
        while ($userUsage->fetch()){
            /** @noinspection PhpUndefinedFieldInspection */
            $usageStats[$userUsage->sideLoadId] =  $userUsage->numUsers;
        }
        return $usageStats;
    }

    /**
     * @param string|null $month
     * @param string|null $year
     * @param int[] $profilesToGetStatsFor
     * @return int[]
     */
    public function getRecordStats($month, $year, $profilesToGetStatsFor): array
    {
        $usage = new SideLoadedRecordUsage();
        if ($month != null){
            $usage->month = $month;
        }
        if ($year != null){
            $usage->year = $year;
        }
        $usage->groupBy('sideLoadId');
        $usage->selectAdd(null);
        $usage->selectAdd('sideLoadId');

        $usage->selectAdd('SUM(IF(timesUsed>0,1,0)) as numRecordsUsed');
        $usage->find();

        $usageStats = [];
        foreach ($profilesToGetStatsFor as $id => $name){
            $usageStats[$id] = [
                'numRecordsUsed' => 0
            ];
        }
        while ($usage->fetch()){
            /** @noinspection PhpUndefinedFieldInspection */
            $usageStats[$usage->sideLoadId] = [
                'numRecordsUsed' => $usage->numRecordsUsed
            ];
        }
        return $usageStats;
    }

}