<?php

use PHPUnit\Framework\TestCase;
define('BORROWBOX_LINK_PATH_TO_ROOT', __DIR__ . '/../../../../../');

class BorrowBoxScopeLinkTests extends TestCase {

	private BorrowBoxSetting $setting;
	private BorrowBoxScope $scope;
	private Library $library;
	private Location $location;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once BORROWBOX_LINK_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxSetting.php';
		require_once BORROWBOX_LINK_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxScope.php';
		require_once BORROWBOX_LINK_PATH_TO_ROOT . 'code/web/sys/BorrowBox/LibraryBorrowBoxScope.php';
		require_once BORROWBOX_LINK_PATH_TO_ROOT . 'code/web/sys/BorrowBox/LocationBorrowBoxScope.php';
		require_once BORROWBOX_LINK_PATH_TO_ROOT . 'code/web/sys/BorrowBox/LibraryBorrowBoxSettings.php';
		require_once BORROWBOX_LINK_PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Library.php';
		require_once BORROWBOX_LINK_PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Location.php';
	}

	protected function setUp(): void {
		parent::setUp();

		$this->setting = new BorrowBoxSetting();
		$this->setting->name = 'PHPUnit Link Setting';
		if (!$this->setting->find(true)) {
			$this->setting->apiUrl = 'https://api.borrowbox.net';
			$this->setting->apiUsername = 'phpunit';
			$this->setting->apiPassword = 'phpunit';
			$this->setting->insert();
		}

		$this->scope = new BorrowBoxScope();
		$this->scope->settingId = $this->setting->id;
		$this->assertTrue($this->scope->find(true));

		$this->library = new Library();
		$this->assertTrue($this->library->find(true));

		$this->location = new Location();
		if (!$this->location->find(true)) {
			$this->location->libraryId = $this->library->libraryId;
			$this->location->displayName = 'PHPUnit Location';
			$this->location->code = 'phpunit';
			$this->location->insert();
		}
	}

	public function testScopeAttachesToLibrary(): void {
		$link = new LibraryBorrowBoxScope();
		$link->scopeId = $this->scope->id;
		$link->libraryId = $this->library->libraryId;
		if (!$link->find(true)) {
			$link->insert();
		}

		$attachedScopes = $this->library->getLibraryBorrowBoxScopes();
		$scopeIds = [];
		foreach ($attachedScopes as $attachedScope) {
			$scopeIds[] = $attachedScope->scopeId;
		}
		$this->assertContains($this->scope->id, $scopeIds);
	}

	public function testScopeAttachesToLocation(): void {
		$link = new LocationBorrowBoxScope();
		$link->scopeId = $this->scope->id;
		$link->locationId = $this->location->locationId;
		if (!$link->find(true)) {
			$link->insert();
		}

		$attachedScopes = $this->location->getLocationBorrowBoxScopes();
		$scopeIds = [];
		foreach ($attachedScopes as $attachedScope) {
			$scopeIds[] = $attachedScope->scopeId;
		}
		$this->assertContains($this->scope->id, $scopeIds);
	}

	public function testDeletingSettingCascadesToScopesAndLinks(): void {
		$setting = new BorrowBoxSetting();
		$setting->name = 'PHPUnit Cascade Setting';
		$setting->apiUrl = 'https://api.borrowbox.net';
		$setting->apiUsername = 'phpunit';
		$setting->apiPassword = 'phpunit';
		$setting->insert();
		$settingId = $setting->id;

		$scope = new BorrowBoxScope();
		$scope->settingId = $settingId;
		$this->assertTrue($scope->find(true));
		$scopeId = $scope->id;

		$link = new LibraryBorrowBoxScope();
		$link->scopeId = $scopeId;
		$link->libraryId = $this->library->libraryId;
		$link->insert();

		$setting->delete();

		$orphanScope = new BorrowBoxScope();
		$orphanScope->id = $scopeId;
		$this->assertFalse($orphanScope->find(true));

		$orphanLink = new LibraryBorrowBoxScope();
		$orphanLink->scopeId = $scopeId;
		$this->assertFalse($orphanLink->find(true));
	}

	public function testLibrarySettingLinksSiteId(): void {
		$librarySetting = new LibraryBorrowBoxSettings();
		$librarySetting->settingId = $this->setting->id;
		$librarySetting->libraryId = $this->library->libraryId;
		if (!$librarySetting->find(true)) {
			$librarySetting->siteId = 'PHPUNIT_SITE';
			$librarySetting->insert();
		}

		$librarySettings = $this->library->getLibraryBorrowBoxSettings();
		$this->assertNotEmpty($librarySettings);
		$found = false;
		foreach ($librarySettings as $attached) {
			if ($attached->settingId == $this->setting->id) {
				$found = true;
				$this->assertEquals('PHPUNIT_SITE', $attached->siteId);
				$this->assertEquals(1, $attached->circulationEnabled);
			}
		}
		$this->assertTrue($found);
	}
}
