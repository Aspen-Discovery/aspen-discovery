<?php

namespace services\API;

use PHPUnit\Framework\TestCase;
use UserAPI;

class UserAPITests extends TestCase {
	public function testIsLoggedIn() {
		require_once __DIR__ . '/../../../../../code/web/services/API/UserAPI.php';
		$userAPI = new UserAPI();

		$isLoggedIn = $userAPI->isLoggedIn();
		$this->assertFalse($isLoggedIn);
	}

	public function testLoginFailure() {
		require_once __DIR__ . '/../../../../../code/web/services/API/UserAPI.php';
		$userAPI = new UserAPI();

		$result = $userAPI->login();
		$this->assertFalse($result['success']);
		$this->assertEquals('This method must be called via POST.', $result['message']);

		$isLoggedIn = $userAPI->isLoggedIn();
		$this->assertFalse($isLoggedIn);
	}

	public function testLogin() {
		require_once __DIR__ . '/../../../../../code/web/services/API/UserAPI.php';
		$userAPI = new UserAPI();

		global $_POST;
		global $_REQUEST;
		$_POST = [];
		$_POST['username'] = 'test_user';
		$_POST['password'] = 'password';
		$_REQUEST = [];
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'password';
		$result = $userAPI->login();
		$this->assertTrue($result['success']);
		$this->assertEquals('Test User', $result['name']);
		$this->assertNotNull($result['session']);

		$isLoggedIn = $userAPI->isLoggedIn();
		$this->assertTrue($isLoggedIn);

		unset($_POST);
	}
}