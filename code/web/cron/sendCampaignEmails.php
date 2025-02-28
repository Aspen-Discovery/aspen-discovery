<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';
require_once ROOT_DIR . '/sys/Account/User.php';
require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';

global $logger;
$logger->log("RUNNING SEND CAMPAIGN EMAIL", Logger::LOG_ERROR);
$today = date('Y-m-d');

$campaign = new Campaign();
$campaign->startDate = $today;

if ($campaign->find()) {
    while ($campaign->fetch()) {
        $campaignId = $campaign->id;
        $campaignName = $campaign->name;

        $userCampaign = new UserCampaign();
        $userCampaign->campaignId = $campaignId;

        if ($userCampaign->find()) {
            while($userCampaign->fetch()) {
                if ($userCampaign->optInToCampaignEmailNotifications == 1) {
                    $user = new User();
                    $user->id = $userCampaign->userId;

                    if ($user->find(true) && !empty($user->email)) {
                        sendCampaignEmail($user, $campaignName);
                    }

                }

            }
        }
    }
}

function sendCampaignEmail($user, $campaignName) {
    $emailTemplate = EmailTemplate::getActiveTemplate('campaignStart');
    if ($emailTemplate) {
        $parameters = [
            'user' => $user,
            'campaignName' => $campaignName,
            'library' => $user->getHomeLibrary(),
        ];
        $emailTemplate->sendEmail($user->email, $parameters);
    }
}
  
