<?php

use PHPUnit\Framework\TestCase;

class CampaignMilestoneTests extends TestCase {

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/CommunityEngagement/CampaignMilestone.php';
	}

    protected function setUp(): void {
        parent::setUp();
        
        $tables = [
            'CampaignMilestone',
            'Milestone',
            'Reward',
            'CampaignMilestoneUsersProgress',
            'Campaign'
        ];

        foreach ($tables as $className) {
            $obj = new $className();
            $obj->whereAdd("1=1");
            $obj->delete(true);
        }
    }

    public function testGetCampaignMilestoneProgress() {
        $userId = 123;
        $goal = 10;
        $completed = 5;

        // 1. Prepare the Database State
        // Insert the Milestone Goal
        $cm = new CampaignMilestone();
        $cm->goal = $goal;
        $cm->campaignId = 1; // Just a dummy value for the FK
        $cm->milestoneId = 1; // Just a dummy value for the FK
        $campaignMilestoneId = $cm->insert(); // This returns the new auto-incremented ID

        // Insert the User Progress (satisfies the static call in the other class)
        $progress = new CampaignMilestoneUsersProgress();
        $progress->ce_campaign_milestone_id = $campaignMilestoneId;
        $progress->userId = $userId;
        $progress->progress = $completed;
        $progress->insert();

        // 2. Execute the logic
        $cmObj = new CampaignMilestone();
        $result = $cmObj->getCampaignMilestoneProgress($campaignMilestoneId, $userId);

        // 3. Assertions
        // Expected: (5 / 10) * 100 = 50%
        $this->assertEquals(50, $result['progress'], "Progress calculation is incorrect.");
        $this->assertEquals(0, $result['extraProgress'], "Extra progress should be 0 when under 100%.");
        $this->assertEquals($completed, $result['completed'], "Completed count mismatch.");
    }

    public function testGetCampaignMilestoneByCampaignFormat() {
        $c = new Campaign();
        $c->name = "My campaign";
        $campaignId = $c->insert();

        $m = new Milestone();
        $m->name = "Read Books";
        $m->description = "Read any books to earn a badge.";
        $m->milestoneType = "user_checkout";
        $milestoneId = $m->insert();

        $r = new Reward();
        $r->name = "Super Reader Badge";
        $r->description = "Earned by reading 5 books";
        $r->rewardType = 1;
        $r->badgeImage = "badge.png";
        $r->displayName = 1;
        $r->awardAutomatically = 0;
        $rewardId = $r->insert();

        $cm = new CampaignMilestone();
        $cm->campaignId = $campaignId;
        $cm->milestoneId = $milestoneId;
        $cm->reward = $rewardId;
        $cm->goal = 5;
        $cm->insert();

        $results = @CampaignMilestone::getCampaignMilestoneByCampaign($campaignId);
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        
        $obj = $results[0];
        $this->assertInstanceOf(CampaignMilestone::class, $obj);
        $this->assertEquals("Read Books", $obj->name);
        $this->assertEquals("user_checkout", $obj->milestoneType);
        $this->assertEquals("Super Reader Badge", $obj->rewardName);
        $this->assertTrue($obj->rewardExists);
        $this->assertEquals($rewardId, $obj->rewardId);
        $this->assertEquals(1, $obj->rewardType);
        $this->assertTrue($obj->rewardExists, "rewardExists should be true when a reward is linked");
        $this->assertEquals(0, $obj->awardAutomatically, "awardAutomatically property was not correctly mapped");
        $this->assertNotEmpty($obj->rewardImage, "Reward image URL should not be empty");
        $this->assertEquals($campaignId, $obj->campaignId);
        $this->assertEquals($milestoneId, $obj->milestoneId);
        $this->assertEquals(5, $obj->goal);
        $this->assertEquals("Read any books to earn a badge.", $obj->description, "Milestone description was not augmented");
        $this->assertEquals(1, $obj->displayName, "displayName setting was not preserved");
        $this->assertNotEquals('', $obj->rewardName);
    }

}