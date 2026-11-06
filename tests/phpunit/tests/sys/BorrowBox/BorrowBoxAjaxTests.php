<?php

use PHPUnit\Framework\TestCase;
define('BORROWBOX_AJAX_PATH_TO_ROOT', __DIR__ . '/../../../../../');

class BorrowBoxAjaxTests extends TestCase {

	private BorrowBox_AJAX $ajax;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once BORROWBOX_AJAX_PATH_TO_ROOT . 'code/web/services/BorrowBox/AJAX.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$_REQUEST = [];
		$_REQUEST['borrowboxId'] = 'PHPUNIT_AJAX_1';
		$_REQUEST['patronId'] = 1;
		$this->ajax = new BorrowBox_AJAX();
	}

	public function testPlaceHoldRequiresLogin(): void {
		$result = $this->ajax->placeHold();
		$this->assertFalse($result['result']);
		$this->assertEquals('You must be logged in to place a hold.', $result['message']);
	}

	public function testCheckOutTitleRequiresLogin(): void {
		$result = $this->ajax->checkOutTitle();
		$this->assertFalse($result['result']);
		$this->assertEquals('You must be logged in to checkout an item.', $result['message']);
	}

	public function testReturnCheckoutRequiresLogin(): void {
		$result = $this->ajax->returnCheckout();
		$this->assertFalse($result['result']);
		$this->assertEquals('You must be logged in to return an item.', $result['message']);
	}

	public function testRenewCheckoutRequiresLogin(): void {
		$result = $this->ajax->renewCheckout();
		$this->assertFalse($result['result']);
		$this->assertEquals('You must be logged in to renew titles.', $result['message']);
	}

	public function testCancelHoldRequiresLogin(): void {
		$result = $this->ajax->cancelHold();
		$this->assertFalse($result['result']);
		$this->assertEquals('You must be logged in to cancel holds.', $result['message']);
	}
}
