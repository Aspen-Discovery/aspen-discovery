<?php

namespace services\API;

use PHPUnit\Framework\TestCase;
use UserAPI;

class UserAPITests extends TestCase {
	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/services/API/UserAPI.php';
	}

	public function testIsLoggedIn() {
		$userAPI = new UserAPI();

		$isLoggedIn = $userAPI->isLoggedIn();
		$this->assertFalse($isLoggedIn);
	}

	public function testLoginFailure() {
		$userAPI = new UserAPI();

		$result = $userAPI->login();
		$this->assertFalse($result['success']);
		$this->assertEquals('This method must be called via POST.', $result['message']);

		$isLoggedIn = $userAPI->isLoggedIn();
		$this->assertFalse($isLoggedIn);
	}

	public function testLogin() {
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

		$userAPI->logout();

		unset($_POST);
		unset($_REQUEST);
	}

	public function testFailedLogin() {
		$userAPI = new UserAPI();

		global $_POST;
		global $_REQUEST;
		$_POST = [];
		$_POST['username'] = 'test_user';
		$_POST['password'] = 'wrong_password';
		$_REQUEST = [];
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'wrong_password';
		$result = $userAPI->login();
		$this->assertFalse($result['success']);

		$isLoggedIn = $userAPI->isLoggedIn();
		$this->assertFalse($isLoggedIn);

		$userAPI->logout();

		unset($_POST);
		unset($_REQUEST);
	}

	public function testRepeatedLogins() {
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

		$_POST = [];
		$_POST['username'] = 'test_user';
		$_POST['password'] = 'password';
		$_REQUEST = [];
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'password';
		$result = $userAPI->login();
		$this->assertTrue($result['success']);

		$isLoggedIn = $userAPI->isLoggedIn();
		$this->assertTrue($isLoggedIn);

		$userAPI->logout();

		unset($_POST);
		unset($_REQUEST);
	}

	public function testValidateAccount() {
		$userAPI = new UserAPI();

		global $_POST;
		global $_REQUEST;
		$_POST = [];
		$_POST['username'] = 'test_user';
		$_POST['password'] = 'password';
		$_REQUEST = [];
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'password';
		$result = $userAPI->validateAccount();
		$this->assertIsObject($result['success']);
		$resultingUser = $result['success'];
		$this->assertEquals('Test', $resultingUser->firstname);
		$this->assertEquals('User', $resultingUser->lastname);

		$userAPI->logout();

		unset($_POST);
		unset($_REQUEST);
	}

	public function testValidateUserCredentialsOneTime() {
		$userAPI = new UserAPI();

		global $_POST;
		global $_REQUEST;
		$_POST = [];
		$_POST['username'] = 'test_user';
		$_POST['password'] = 'password';
		$_REQUEST = [];
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'password';
		$result = $userAPI->validateUserCredentials();
		$this->assertTrue($result['valid']);

		$userAPI->logout();

		unset($_POST);
		unset($_REQUEST);
	}

	public function testValidateUserCredentialsTwoTimes() {
		$userAPI = new UserAPI();

		global $_POST;
		global $_REQUEST;
		$_POST = [];
		$_POST['username'] = 'test_user';
		$_POST['password'] = 'password';
		$_REQUEST = [];
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'password';
		$result = $userAPI->validateUserCredentials();
		$this->assertTrue($result['valid']);
		sleep(1);
		$result = $userAPI->validateUserCredentials();
		$this->assertTrue($result['valid']);

		$userAPI->logout();

		unset($_POST);
		unset($_REQUEST);
	}

	public function testValidateUserCredentialsWithIncorrectInfo() {
		$userAPI = new UserAPI();

		global $_POST;
		global $_REQUEST;
		$_POST = [];
		$_POST['username'] = 'test_user';
		$_POST['password'] = 'password';
		$_REQUEST = [];
		$_REQUEST['username'] = 'test_user';
		$_REQUEST['password'] = 'password';
		$result = $userAPI->validateUserCredentials();
		$this->assertTrue($result['valid']);
		$_POST['password'] = 'wrong_password';
		$_REQUEST['password'] = 'wrong_password';
		$result = $userAPI->validateUserCredentials();
		$this->assertFalse($result['valid']);

		$userAPI->logout();

		unset($_POST);
		unset($_REQUEST);
	}
}