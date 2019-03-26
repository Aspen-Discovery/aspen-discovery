<?php

require_once ROOT_DIR . '/JSON_Action.php';
class JSON extends JSON_Action
{
    public function trackUsage(){
        if (!isset($_REQUEST['id'])){
            return ['success' => false, 'message' => 'ID was not provided'];
        }
        $id = $_REQUEST['id'];
        require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecord.php';
        $openArchivesRecord = new OpenArchivesRecord();
        $openArchivesRecord->id = $id;
        if (!$openArchivesRecord->find(true)){
            return ['success' => false, 'message' => 'Record was not found in the database'];
        }

        //Track usage of the record
        require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecordUsage.php';
        $openArchivesUsage = new OpenArchivesRecordUsage();
        $openArchivesUsage->openArchivesRecordId = $id;
        $openArchivesUsage->year = date('Y');
        if ($openArchivesUsage->find(true)){
            $openArchivesUsage->timesUsed++;
            $ret = $openArchivesUsage->update();
            if ($ret == 0){
                echo("Unable to update times used");
            }
        }else {
            $openArchivesUsage->timesViewedInSearch = 0;
            $openArchivesUsage->timesUsed = 1;
            $openArchivesUsage->insert();
        }

        $userId = UserAccount::getActiveUserId();
        if ($userId){
            //Track usage for the user
            require_once ROOT_DIR . '/sys/OpenArchives/UserOpenArchivesUsage.php';
            $userOpenArchivesUsage = new UserOpenArchivesUsage();
            $userOpenArchivesUsage->userId = $userId;
            $userOpenArchivesUsage->year = date('Y');
            $userOpenArchivesUsage->openArchivesCollectionId = $openArchivesRecord->sourceCollection;

            if ($userOpenArchivesUsage->find(true)){
                $userOpenArchivesUsage->lastUsed = time();
                $userOpenArchivesUsage->usageCount++;
                $userOpenArchivesUsage->update();
            }else {
                $userOpenArchivesUsage->lastUsed = time();
                $userOpenArchivesUsage->firstUsed = time();
                $userOpenArchivesUsage->usageCount = 1;
                $userOpenArchivesUsage->insert();
            }
        }

        return  ['success' => true, 'message' => 'Updated usage for archive record ' . $id];
    }
}