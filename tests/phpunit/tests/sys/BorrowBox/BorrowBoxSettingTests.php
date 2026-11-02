<?php

use PHPUnit\Framework\TestCase;
define('BORROWBOX_SETTING_PATH_TO_ROOT', __DIR__ . '/../../../../../');

class BorrowBoxSettingTests extends TestCase {

	public function __construct(string $name) {
		parent::__construct($name);
		require_once BORROWBOX_SETTING_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxSetting.php';
		require_once BORROWBOX_SETTING_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxScope.php';
	}

	private function createSetting(string $name, string $apiUrl): BorrowBoxSetting {
		$setting = new BorrowBoxSetting();
		$setting->name = $name;
		$setting->apiUrl = $apiUrl;
		$setting->apiUsername = 'phpunit';
		$setting->apiPassword = 'phpunit';
		$setting->insert();
		return $setting;
	}

	public function testInsertNormalizesApiUrlTrailingSlash(): void {
		$setting = $this->createSetting('PHPUnit Trailing Slash', 'https://api.borrowbox.net/');

		$reloaded = new BorrowBoxSetting();
		$reloaded->id = $setting->id;
		$this->assertTrue($reloaded->find(true));
		$this->assertEquals('https://api.borrowbox.net', $reloaded->apiUrl);
	}

	public function testUpdateNormalizesApiUrlTrailingSlash(): void {
		$setting = $this->createSetting('PHPUnit Update Slash', 'https://api.borrowbox.net');

		$setting->apiUrl = 'https://api.borrowbox.example/';
		$setting->update();

		$reloaded = new BorrowBoxSetting();
		$reloaded->id = $setting->id;
		$this->assertTrue($reloaded->find(true));
		$this->assertEquals('https://api.borrowbox.example', $reloaded->apiUrl);
	}

	public function testInsertCreatesDefaultAllRecordsScope(): void {
		$setting = $this->createSetting('PHPUnit Default Scope', 'https://api.borrowbox.net');

		$scope = new BorrowBoxScope();
		$scope->settingId = $setting->id;
		$this->assertTrue($scope->find(true));
		$this->assertEquals('All Records', $scope->name);
		$this->assertEquals(1, $scope->includeAdult);
		$this->assertEquals(1, $scope->includeTeen);
		$this->assertEquals(1, $scope->includeKids);
	}
}
