<?php

use PHPUnit\Framework\TestCase;
define('BORROWBOX_CIRC_PATH_TO_ROOT', __DIR__ . '/../../../../../');

class BorrowBoxCircEntryTests extends TestCase {

	public function __construct(string $name) {
		parent::__construct($name);
		require_once BORROWBOX_CIRC_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxAPIProduct.php';
		require_once BORROWBOX_CIRC_PATH_TO_ROOT . 'code/web/sys/User/Checkout.php';
	}

	protected function setUp(): void {
		parent::setUp();

		$product = new BorrowBoxAPIProduct();
		$product->borrowboxId = 'PHPUNIT_CIRC_1';
		if (!$product->find(true)) {
			$product->title = 'PHPUnit Circ Title';
			$product->mediaType = 'eAudiobook';
			$product->insert();
		}
	}

	public function testCheckoutResolvesBorrowBoxRecordDriver(): void {
		$checkout = new Checkout();
		$checkout->type = 'borrowbox';
		$checkout->source = 'borrowbox';
		$checkout->recordId = 'PHPUNIT_CIRC_1';

		$recordDriver = $checkout->getRecordDriver();
		$this->assertNotFalse($recordDriver);
		$this->assertInstanceOf('BorrowBoxRecordDriver', $recordDriver);
	}

	public function testCheckoutFormatsResolveFromRecordDriver(): void {
		$checkout = new Checkout();
		$checkout->type = 'borrowbox';
		$checkout->source = 'borrowbox';
		$checkout->recordId = 'PHPUNIT_CIRC_1';

		$this->assertEquals(['eAudiobook' => 'eAudiobook'], $checkout->getFormats());
	}

	public function testCheckoutForUnknownBorrowBoxRecordIsInvalid(): void {
		$checkout = new Checkout();
		$checkout->type = 'borrowbox';
		$checkout->source = 'borrowbox';
		$checkout->recordId = 'PHPUNIT_CIRC_MISSING';

		$this->assertFalse($checkout->getRecordDriver());
	}
}
