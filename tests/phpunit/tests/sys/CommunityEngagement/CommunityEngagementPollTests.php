<?php

require_once __DIR__ . '/../../../../../code/web/bootstrap.php';

use PHPUnit\Framework\TestCase;

class CommunityEngagementPollTests extends TestCase {
    private $poll;
    private $patron;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/CommunityEngagement/CommunityEngagementPoll.php';
		require_once __DIR__ . '/../../../../../code/web/sys/CommunityEngagement/Campaign.php';
		require_once __DIR__ . '/../../../../../code/web/sys/CommunityEngagement/CampaignMilestoneProgressEntry.php';
	}

    protected function setUp(): void {
        parent::setUp();
        
        // 1. Instantiate the class in debug mode 
        // This is crucial: it ignores the 10-second DB time constraint
        $this->poll = new CommunityEngagementPoll(true);

        $this->patron = (object)['id' => 999];

        $tables = [
            'CampaignMilestone',
            'Milestone',
            'Reward',
            'UserCampaign',
            'CampaignMilestoneProgressEntry',
            'CampaignMilestoneUsersProgress',
            'Campaign'
        ];

        foreach ($tables as $className) {
            $obj = new $className();
            $obj->whereAdd("1=1");
            $obj->delete(true);
        }

    }

    /**
     * TEST: Milestone Progress Notification
     * Scenario: User has made progress but hasn't reached the goal yet.
     */
    public function testMilestoneProgressNotification() {
        // 1. Setup Campaign
        $campaign = new Campaign();
        $campaign->name = "Test Campaign";
        $campaign->insert();

        // 2. Setup Milestone
        $milestone = new Milestone();
        $milestone->name = "Checkout books";
        $milestone->description = "Read any books to earn a badge.";
        $milestone->milestoneType = "user_checkout";
        $milestone->insert();
        
        // 3. Link them via CampaignMilestone
        $cm = new CampaignMilestone();
        $cm->campaignId = $campaign->id;
        $cm->milestoneId = $milestone->id;
        $cm->goal = 100;
        $cm->insert();

        // 4. Create Progress
        $progress = new CampaignMilestoneUsersProgress();
        $progress->userId = $this->patron->id;
        $progress->ce_campaign_milestone_id = $cm->id;
        $progress->progress = 50; // Halfway there
        $progress->insert();

        // 5. Create the Entry (The trigger for the Poll)
        $entry = new CampaignMilestoneProgressEntry();
        $entry->userId = $this->patron->id;
        $entry->ce_campaign_milestone_id = $cm->id;
        $entry->ce_campaign_milestone_users_progress_id = $progress->id;
        $entry->timestamp = date('Y-m-d H:i:s');
        $entry->insert();

        // EXECUTE
        $notifications = $this->poll->fetchLatestNotifications($this->patron, 10);

        // ASSERT
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ce_milestone_progress', $notifications[0]['id']);
        $this->assertEquals($milestone->name.' of '.$campaign->name.' progressed!', $notifications[0]['body']);
        $this->assertEquals('fa-chart-line', $notifications[0]['icon']);
    }

    /**
     * TEST: Milestone Completion Notification
     * Scenario: User finishes one milestone, but the campaign has others remaining.
     */
    public function testMilestoneCompletionNotification() {
        // 1. Setup Campaign
        $campaign = new Campaign();
        $campaign->name = "Multi-Step Challenge";
        $campaign->insert();

        // 2. Setup Milestone A (The one we will complete)
        $milestoneA = new Milestone();
        $milestoneA->name = "Step One";
        $milestoneA->insert();

        // 3. Setup Milestone B (The "Buffer" to keep campaign open)
        $milestoneB = new Milestone();
        $milestoneB->name = "Step Two";
        $milestoneB->insert();

        // 4. Link both to the Campaign
        $cmA = new CampaignMilestone();
        $cmA->campaignId = $campaign->id;
        $cmA->milestoneId = $milestoneA->id;
        $cmA->goal = 5;
        $cmA->insert();

        $cmB = new CampaignMilestone();
        $cmB->campaignId = $campaign->id;
        $cmB->milestoneId = $milestoneB->id;
        $cmB->goal = 500; // Far out of reach
        $cmB->insert();

        // 5. UserCampaign is NOT completed
        $uc = new UserCampaign();
        $uc->userId = $this->patron->id;
        $uc->campaignId = $campaign->id;
        $uc->completed = 0; 
        $uc->insert();

        // 6. Create Progress matching Milestone A's goal
        $progressA = new CampaignMilestoneUsersProgress();
        $progressA->userId = $this->patron->id;
        $progressA->ce_campaign_milestone_id = $cmA->id;
        $progressA->progress = 5; 
        $progressA->insert();

        $progressB = new CampaignMilestoneUsersProgress();
        $progressB->userId = $this->patron->id;
        $progressB->ce_campaign_milestone_id = $cmB->id;
        $progressB->progress = 200; 
        $progressB->insert();

        // 7. Create the Entry trigger for Milestone A
        $entryA = new CampaignMilestoneProgressEntry();
        $entryA->userId = $this->patron->id;
        $entryA->ce_campaign_milestone_id = $cmA->id;
        $entryA->ce_campaign_milestone_users_progress_id = $progressA->id;
        $entryA->insert();

        $entryB = new CampaignMilestoneProgressEntry();
        $entryB->userId = $this->patron->id;
        $entryB->ce_campaign_milestone_id = $cmB->id;
        $entryB->ce_campaign_milestone_users_progress_id = $progressB->id;
        $entryB->insert();

        // EXECUTE
        $notifications = $this->poll->fetchLatestNotifications($this->patron, 10);

        // ASSERT
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ce_milestone_completed', $notifications[0]['id']);
        $this->assertEquals('Step One of Multi-Step Challenge complete.', $notifications[0]['body']);
        $this->assertEquals('fa-clipboard-check', $notifications[0]['icon']);
    }


    /**
     * TEST: Campaign Completion Notification
     * Scenario: User finishes the last milestone in a campaign.
     */
    public function testCampaignCompletionNotification() {
        
        $campaign = new Campaign();
        $campaign->name = "Grand Tour";
        $campaign->insert();

        $cm = new CampaignMilestone();
        $cm->campaignId = $campaign->id;
        $cm->goal = 10;
        $cm->insert();

        $uc = new UserCampaign();
        $uc->userId = $this->patron->id;
        $uc->campaignId = $campaign->id;
        $uc->completed = true;
        $uc->insert();

        $progress = new CampaignMilestoneUsersProgress();
        $progress->progress = 10;
        $progress->insert();

        $entry = new CampaignMilestoneProgressEntry();
        $entry->userId = $this->patron->id;
        $entry->ce_campaign_milestone_id = $cm->id;
        $entry->ce_campaign_milestone_users_progress_id = $progress->id;
        $entry->insert();

        $notifications = $this->poll->fetchLatestNotifications($this->patron, 10);

        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ce_campaign_completed', $notifications[0]['id']);
        $this->assertEquals('Grand Tour campaign complete!', $notifications[0]['body']);
    }

    /**
     * TEST: Unwanted Overflow
     * Scenario: Milestone is at 110/100, and 'progressBeyondOneHundredPercent' is FALSE.
     * Expected: 0 notifications (Logic should 'continue' and skip this entry).
     */
    public function testUnwantedOverflow() {
        $campaign = new Campaign();
        $campaign->insert();

        $milestone = new Milestone();
        $milestone->name = "Limited Goal";
        $milestone->progressBeyondOneHundredPercent = 0; // Standard behavior
        $milestone->insert();

        $cm = new CampaignMilestone();
        $cm->campaignId = $campaign->id;
        $cm->milestoneId = $milestone->id;
        $cm->goal = 100;
        $cm->insert();

        // Progress is already past the goal
        $progress = new CampaignMilestoneUsersProgress();
        $progress->userId = $this->patron->id;
        $progress->ce_campaign_milestone_id = $cm->id;
        $progress->progress = 110; 
        $progress->insert();

        $entry = new CampaignMilestoneProgressEntry();
        $entry->userId = $this->patron->id;
        $entry->ce_campaign_milestone_id = $cm->id;
        $entry->ce_campaign_milestone_users_progress_id = $progress->id;
        $entry->insert();

        $notifications = $this->poll->fetchLatestNotifications($this->patron, 10);

        // ASSERT: Logic should hit the 'unwantedOverflow' continue statement
        $this->assertCount(0, $notifications);
    }

    /**
     * TEST: Wanted Overflow
     * Scenario: Milestone is at 110/100, and 'progressBeyondOneHundredPercent' is TRUE.
     * Expected: 1 notification of type 'ce_milestone_progress'.
     */
    public function testWantedOverflow() {
        $campaign = new Campaign();
        $campaign->name = "Another Campaign";
        $campaign->insert();

        $milestone = new Milestone();
        $milestone->name = "Infinite Goal";
        $milestone->progressBeyondOneHundredPercent = 1; // Explicitly allow overflow
        $milestone->insert();

        $cm = new CampaignMilestone();
        $cm->campaignId = $campaign->id;
        $cm->milestoneId = $milestone->id;
        $cm->goal = 100;
        $cm->insert();

        $progress = new CampaignMilestoneUsersProgress();
        $progress->userId = $this->patron->id;
        $progress->ce_campaign_milestone_id = $cm->id;
        $progress->progress = 110; 
        $progress->insert();

        $entry = new CampaignMilestoneProgressEntry();
        $entry->userId = $this->patron->id;
        $entry->ce_campaign_milestone_id = $cm->id;
        $entry->ce_campaign_milestone_users_progress_id = $progress->id;
        $entry->insert();

        $notifications = $this->poll->fetchLatestNotifications($this->patron, 10);

        // ASSERT: Logic should identify this as 'wantedOverflow' and allow the progress branch
        $this->assertCount(1, $notifications);
        $this->assertStringContainsString('ce_milestone_progress', $notifications[0]['id']);
        $this->assertEquals($milestone->name.' of '.$campaign->name.' progressed!', $notifications[0]['body']);
    }
}