<?php

use PHPUnit\Framework\TestCase;

class CampaignTests extends TestCase {
    private $campaign;
    private $lib1_id;
    private $lib2_id;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/CommunityEngagement/Campaign.php';
	}

    protected function setUp(): void {
        parent::setUp();
        $this->campaign = new Campaign();

        // Create Library 1
        $lib1 = new Library();
        $lib1->libraryId = 'LIB1';
        $lib1->insert(); 
        $this->lib1_id = $lib1->libraryId;

        // Create Library 2
        $lib2 = new Library();
        $lib2->libraryId = 'LIB2';
        $lib2->insert();
        $this->lib2_id = $lib2->libraryId;
    }

    /**
     * Tests the private/protected rank suffix logic via public methods.
     * Logic: 1 -> 1st, 2 -> 2nd, 3 -> 3rd, 4 -> 4th, 11 -> 11th
     */
    public function testGetRankDisplayed() {
        // Since getRankDisplayed is private, we test it through a public method 
        // like getOverallLeaderboard or use Reflection. 
        // For this example, we assume we made it public or are testing the result.
        
        $reflection = new ReflectionClass('Campaign');
        $method = $reflection->getMethod('getRankDisplayed');
        $method->setAccessible(true);

        $this->assertEquals('1st', $method->invoke($this->campaign, 1));
        $this->assertEquals('2nd', $method->invoke($this->campaign, 2));
        $this->assertEquals('3rd', $method->invoke($this->campaign, 3));
        $this->assertEquals('4th', $method->invoke($this->campaign, 4));
        $this->assertEquals('11th', $method->invoke($this->campaign, 11));
        $this->assertEquals('21st', $method->invoke($this->campaign, 21));
    }

    /**
     * Tests the logic that determines if a user can enroll based on dates.
     */
    public function testFilterByCanEnroll() {
        $futureDate = date('Y-m-d', strtotime('+10 days'));
        $userId = 123;

        // --- Scenario 1: User is NOT enrolled ---
        $notEnrolledCampaign = $this->getMockBuilder(Campaign::class)
            ->onlyMethods(['isUserEnrolled'])
            ->getMock();
        
        $notEnrolledCampaign->id = 1;
        $notEnrolledCampaign->endDate = $futureDate;
        $notEnrolledCampaign->isActive = true;
        $notEnrolledCampaign->isUpcoming = false;
        
        // Explicitly return false
        $notEnrolledCampaign->method('isUserEnrolled')->willReturn(false);

        $result1 = Campaign::filterByCanEnroll([$notEnrolledCampaign], $userId);
        $this->assertCount(1, $result1, "Should include campaign when user is NOT enrolled");


        // --- Scenario 2: User IS enrolled ---
        $enrolledCampaign = $this->getMockBuilder(Campaign::class)
            ->onlyMethods(['isUserEnrolled'])
            ->getMock();
        
        $enrolledCampaign->id = 2;
        $enrolledCampaign->endDate = $futureDate;
        $enrolledCampaign->isActive = true;
        $enrolledCampaign->isUpcoming = false;
        
        // Explicitly return true (truthy)
        $enrolledCampaign->method('isUserEnrolled')->willReturn(true);

        $result2 = Campaign::filterByCanEnroll([$enrolledCampaign], $userId);
        $this->assertCount(0, $result2, "Should EXCLUDE campaign when user IS enrolled");
    }

    /**
     * Tests the image display logic
     */
    public function testSetDisplayImageForArray() {
        $item = [
            'id' => 1,
            'badgeImage' => 'original_image.jpg'
        ];
        $settings = [
            'displayPlaceholderImage' => true,
            'placeholderImage' => 'placeholder.jpg'
        ];
        
        // rewardGiven = false, awardAutomatically = false, isComplete = false
        // Should trigger placeholder
        Campaign::setDisplayImageForArray($item, $settings, false, false, false);
        
        $this->assertTrue($item['isPlaceholderImage']);
        $this->assertStringContainsString('placeholder.jpg', $item['badgeImage']);
        
        // rewardGiven = true
        // Should show actual image
        $item['badgeImage'] = 'actual.jpg';
        Campaign::setDisplayImageForArray($item, $settings, true, false, false);
        $this->assertFalse($item['isPlaceholderImage']);
        $this->assertEquals('actual.jpg', $item['badgeImage']);
    }

    /**
     * TEST: Database CRUD and Related Saves
     * Covers: insert, saveLibraryAccess, getLibraryAccess
     */
    public function testDatabaseInsertAndAccessSaves() {
        $this->campaign->name = "PHPUnit Test Campaign";
        $this->campaign->startDate = date('Y-m-d');
        $this->campaign->endDate = date('Y-m-d', strtotime('+1 month'));
        
        // Pass the integer IDs we got from the DB in setUp
        $this->campaign->allowLibraryAccess = [$this->lib1_id, $this->lib2_id];
        
        $newId = $this->campaign->insert();
        $this->assertNotFalse($newId, "Campaign insert failed");

        // Reload and verify
        $reloaded = Campaign::getCampaignById($newId);
        $libAccess = $reloaded->getLibraryAccess();
        
        // Check using the integer keys
        $this->assertArrayHasKey($this->lib1_id, $libAccess);
        $this->assertArrayHasKey($this->lib2_id, $libAccess);
    }

    /**
     * TEST: Age Range Filtering Logic
     * Covers: applyUserFiltering (via protected method access)
     */
    public function testAgeRangeFiltering() {

        $c = new Campaign();
        $c->name = "Adults Only";
        $c->userAgeRange = "Over 18";
        $c->insert();

        $adult = $this->createMock(User::class);
        $adult->method('getAge')->willReturn(25);
        $adult->method('getHomeLibrary')->willReturn(new Library());
        $adult->method('getHomeLocation')->willReturn(new Location());
        $adult->method('getPTypeObj')->willReturn(new PType());

        $ref = new ReflectionClass('Campaign');
        $applyUserFiltering = $ref->getMethod('applyUserFiltering');
        $applyUserFiltering->setAccessible(true);

        $search = new Campaign();
        $search->id = $c->id;
        
        $applyUserFiltering->invoke($search, $adult); 

        $this->assertTrue($search->find(true), "Adult should see campaign");

        $child = $this->createMock(User::class);
        $child->method('getAge')->willReturn(12);
        $child->method('getHomeLibrary')->willReturn(new Library());
        $child->method('getHomeLocation')->willReturn(new Location());
        $child->method('getPTypeObj')->willReturn(new PType());

        $applyUserFiltering->invoke($search, $child); 

        $this->assertFalse($search->find(true), "Child should not see campaign");
    }
}