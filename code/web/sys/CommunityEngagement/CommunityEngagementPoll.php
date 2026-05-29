<?php /** @noinspection PhpMissingFieldTypeInspection */

// Load necessary files
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestoneUsersProgress.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/CampaignMilestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Campaign.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/Milestone.php';
require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';

class CommunityEngagementPoll {

    private bool $debug = false;

    public function __construct(bool $debug = false) {
        $this->debug = $debug;
    }

    /**
     * Returns a JSON polling response
     * Returns 'status' => 'stop' if:
     * - User is not logged in
     * - User is not enrolled in an active campaign
     */
    public function CommunityEngagementPoll() {
        if (!UserAccount::isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'stop']);
            exit();
        }
        $patron = UserAccount::getActiveUserObj();

        $campaign = new Campaign();
        if (!$campaign->userIsEnrolledInActiveCampaign($patron->id)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'stop']);
            exit();
        }

        $interval = 10; 
        $notifications = $this->fetchLatestNotifications($patron, $interval);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'notifications' => $notifications
        ]);
        exit();
    }

    /**
     * Returns an array of notification payloads based on priority:
     * 1. Campaign Completion (Highest - suppresses all others)
     * 2. Milestone Completion (Suppresses progress updates)
     * 3. Progress Updates (Lowest)
     */
    public function fetchLatestNotifications($patron, $interval) {
        $campaignCompletions = [];
        $milestoneCompletions = [];
        $progressUpdates = [];

        // Trackers to prevent duplicate notifications for the same ID in this batch
        $campaignsNotified = [];
        $milestonesNotified = [];
        $progressNotified = [];

        $entry = new CampaignMilestoneProgressEntry();
        $entry->userId = $patron->id;

        if (!$this->debug) {
            $entry->whereAdd("timestamp >= DATE_SUB(NOW(), INTERVAL " . $interval . " SECOND)");
        }

        if ($entry->find()) {
            while ($entry->fetch()) {
                $campaignMilestone = new CampaignMilestone();
                $campaignMilestone->id = $entry->ce_campaign_milestone_id;
                $campaignMilestone->find(true);

                $campaign = new Campaign();
                $campaign->id = $campaignMilestone->campaignId;
                $campaign->find(true);

                $milestone = new Milestone();
                $milestone->id = $campaignMilestone->milestoneId;
                $milestone->find(true);

                $usersProgress = new CampaignMilestoneUsersProgress();
                $usersProgress->id = $entry->ce_campaign_milestone_users_progress_id;
                $usersProgress->find(true);

                $userCampaign = new UserCampaign();
                $userCampaign->userId = $patron->id;
                $userCampaign->campaignId = $campaignMilestone->campaignId;
                $userCampaign->find(true);

                $unwantedOverflow = $usersProgress->progress > $campaignMilestone->goal && !$milestone->progressBeyondOneHundredPercent;
                $wantedOverflow = $usersProgress->progress > $campaignMilestone->goal && $milestone->progressBeyondOneHundredPercent;

                if ($unwantedOverflow) {
                    continue;
                }

                // 1. Categorize: Campaign Completion
                if ($userCampaign->completed && !$wantedOverflow) {
                    if (in_array($campaign->id, $campaignsNotified)) continue;
                    $campaignsNotified[] = $campaign->id;
                    
                    $campaignCompletions[] = [
                        'id' => $entry->id . '_ce_campaign_completed',
                        'title' => translate(['text' => 'Campaign completed! Awesome!', 'isPublicFacing' => true]),
                        'body' => translate([
                            'text' => '%1% campaign complete!',
                            1 => $campaign->name,
                            'isPublicFacing' => true
                        ]),
                        'icon' => "fa-medal",
                        'link' => ['href' => '/MyAccount/MyCampaigns', 'text' => translate(['text' => 'View all campaigns', 'isPublicFacing' => true])]
                    ];

                // 2. Categorize: Milestone Completion
                } elseif ($usersProgress->progress >= $campaignMilestone->goal && !$wantedOverflow) {
                    if (in_array($campaignMilestone->id, $milestonesNotified)) continue;
                    $milestonesNotified[] = $campaignMilestone->id;

                    $milestoneCompletions[] = [
                        'id' => $entry->id . '_ce_milestone_completed',
                        'title' => translate(['text' => 'Milestone completed! Well done!', 'isPublicFacing' => true]),
                        'body' => translate([
                            'text' => '%1% of %2% complete.',
                            1 => $milestone->name,
                            2 => $campaign->name,
                            'isPublicFacing' => true,
                        ]),
                        'icon' => "fa-clipboard-check",
                        'link' => ['href' => '/MyAccount/MyCampaigns', 'text' => translate(['text' => 'View all campaigns', 'isPublicFacing' => true])]
                    ];

                // 3. Categorize: General milestone progress
                } else {
                    if (in_array($campaignMilestone->id, $progressNotified)) continue;
                    $progressNotified[] = $campaignMilestone->id;

                    $progressUpdates[] = [
                        'id' => $entry->id . '_ce_milestone_progress',
                        'title' => translate(['text' => 'Milestone progress! Good job!', 'isPublicFacing' => true]),
                        'body' => translate([
                            'text' => '%1% of %2% progressed!',
                            1=> $milestone->name,
                            2=> $campaign->name,
                            'isPublicFacing' => true,
                        ]),
                        'icon' => "fa-chart-line",
                        'link' => ['href' => '/MyAccount/MyCampaigns', 'text' => translate(['text' => 'View all campaigns', 'isPublicFacing' => true])]
                    ];
                }
            }
        }

        // Priority 1: If any campaigns were completed, only return those.
        if (!empty($campaignCompletions)) {
            return $campaignCompletions;
        }

        // Priority 2: If any milestones were completed, return those and ignore progress.
        if (!empty($milestoneCompletions)) {
            return $milestoneCompletions;
        }

        // Priority 3: Otherwise, return whatever progress updates we found.
        return $progressUpdates;
    }
}