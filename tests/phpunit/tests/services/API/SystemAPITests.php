<?php

namespace services\API;

use PHPUnit\Framework\TestCase;
use SystemAPI;
use SystemVariables;

class SystemAPITests extends TestCase {
	public function test_getCatalogStatus_Online() {
		require_once __DIR__ . '/../../../../../code/web/services/API/SystemAPI.php';
		$systemAPI = new SystemAPI();
		$response = $systemAPI->getCatalogStatus();
		$this->assertEquals(true, $response['success']);
		$this->assertEquals(0, $response['catalogStatus']);
		$this->assertNull($response['message']);
		$this->assertNull($response['api']['message']);
	}

	public function test_getCatalogStatus_Offline() {
		require_once __DIR__ . '/../../../../../code/web/services/API/SystemAPI.php';
		require_once __DIR__ . '/../../../../../code/web/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$systemVariables->catalogStatus = 1;
		$systemVariables->offlineMessage = 'The system is offline for testing';
		$systemVariables->update();

		$systemAPI = new SystemAPI();
		$response = $systemAPI->getCatalogStatus();
		$this->assertEquals(true, $response['success']);
		$this->assertEquals(1, $response['catalogStatus']);
		$this->assertEquals("The system is offline for testing", $response['message']);
		$this->assertEquals("The system is offline for testing", $response['api']['message']);

		$systemVariables->catalogStatus = 0;
		$systemVariables->update();
	}

	public function test_getCatalogStatus_OfflineWithHtml() {
		require_once __DIR__ . '/../../../../../code/web/services/API/SystemAPI.php';
		require_once __DIR__ . '/../../../../../code/web/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$systemVariables->catalogStatus = 1;
		$systemVariables->offlineMessage = 'The system is <strong>offline</strong> for testing';
		$systemVariables->update();

		$systemAPI = new SystemAPI();
		$response = $systemAPI->getCatalogStatus();
		$this->assertEquals(true, $response['success']);
		$this->assertEquals(1, $response['catalogStatus']);
		$this->assertEquals("The system is <strong>offline</strong> for testing", $response['message']);
		$this->assertEquals("The system is offline for testing", $response['api']['message']);

		$systemVariables->catalogStatus = 0;
		$systemVariables->update();
	}

	public function test_hasPendingDatabaseUpdates() {
		require_once __DIR__ . '/../../../../../code/web/services/API/SystemAPI.php';
		$systemAPI = new SystemAPI();
		$response = $systemAPI->hasPendingDatabaseUpdates();
		$this->assertFalse($response);
	}

	public function test_getCurrentVersion() {
		getAspenVersion();

		require_once __DIR__ . '/../../../../../code/web/services/API/SystemAPI.php';
		$systemAPI = new SystemAPI();
		$response = $systemAPI->getCurrentVersion();
		$this->assertNotNull($response['version']);
		$this->assertMatchesRegularExpression('/\d\d\.\d\d\.\d\d/', $response['version']);
	}

}
