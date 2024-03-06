<?php

namespace services\API;

use SystemAPI;
use PHPUnit\Framework\TestCase;

class SystemAPITest extends TestCase {

	public function testGetCatalogStatusOnline() {
		require_once ROOT_DIR . '/services/API/SystemAPI.php';
		$systemAPI = new SystemAPI();
		$response = $systemAPI->getCatalogStatus();
		$this->assertEquals(true, $response['success']);
		$this->assertEquals(0, $response['catalogStatus']);
		$this->assertNull($response['message']);
		$this->assertNull($response['api']['message']);
	}

	public function testGetLanguages() {}
}
