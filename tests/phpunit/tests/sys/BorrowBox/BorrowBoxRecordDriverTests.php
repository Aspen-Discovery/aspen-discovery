<?php

use PHPUnit\Framework\TestCase;
define('BORROWBOX_RECORD_PATH_TO_ROOT', __DIR__ . '/../../../../../');

class BorrowBoxRecordDriverTests extends TestCase {

	private BorrowBoxSetting $setting;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once BORROWBOX_RECORD_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxSetting.php';
		require_once BORROWBOX_RECORD_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxAPIProduct.php';
		require_once BORROWBOX_RECORD_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxAPIProductAvailability.php';
		require_once BORROWBOX_RECORD_PATH_TO_ROOT . 'code/web/RecordDrivers/BorrowBoxRecordDriver.php';
	}

	protected function setUp(): void {
		parent::setUp();

		$this->setting = new BorrowBoxSetting();
		$this->setting->name = 'PHPUnit Record Setting';
		if (!$this->setting->find(true)) {
			$this->setting->apiUrl = 'https://api.borrowbox.net';
			$this->setting->apiUsername = 'phpunit';
			$this->setting->apiPassword = 'phpunit';
			$this->setting->insert();
		}
	}

	private function createProduct(string $borrowboxId, string $mediaType, string $availabilityStatus): BorrowBoxAPIProduct {
		$product = new BorrowBoxAPIProduct();
		$product->borrowboxId = $borrowboxId;
		if (!$product->find(true)) {
			$product->title = "PHPUnit $borrowboxId";
			$product->mediaType = $mediaType;
			$product->insert();

			$availability = new BorrowBoxAPIProductAvailability();
			$availability->productId = $product->id;
			$availability->settingId = $this->setting->id;
			$availability->borrowboxId = $borrowboxId;
			$availability->siteId = 'PHPUNIT_SITE';
			$availability->availabilityStatus = $availabilityStatus;
			$availability->insert();
		}
		return $product;
	}

	public function testInvalidRecordId(): void {
		$driver = new BorrowBoxRecordDriver('PHPUNIT_MISSING');
		$this->assertFalse($driver->isValid());
	}

	public function testAvailableTitleShowsCheckout(): void {
		$this->createProduct('PHPUNIT_REC_AVAIL', 'eBook', 'AVAILABLE');

		$driver = new BorrowBoxRecordDriver('PHPUNIT_REC_AVAIL');
		$this->assertTrue($driver->isValid());
		$statusSummary = $driver->getStatusSummary();
		$this->assertEquals('Available from BorrowBox', $statusSummary['status']);
		$this->assertTrue($statusSummary['available']);
		$this->assertTrue($statusSummary['showCheckout']);
		$this->assertFalse($statusSummary['showPlaceHold']);
	}

	public function testOnLoanTitleShowsPlaceHold(): void {
		$this->createProduct('PHPUNIT_REC_LOANED', 'eAudiobook', 'ON_LOAN');

		$driver = new BorrowBoxRecordDriver('PHPUNIT_REC_LOANED');
		$this->assertTrue($driver->isValid());
		$statusSummary = $driver->getStatusSummary();
		$this->assertEquals('Checked Out', $statusSummary['status']);
		$this->assertFalse($statusSummary['available']);
		$this->assertFalse($statusSummary['showCheckout']);
		$this->assertTrue($statusSummary['showPlaceHold']);
	}

	public function testFormatsComeFromMediaType(): void {
		$this->createProduct('PHPUNIT_REC_FORMAT', 'eAudiobook', 'AVAILABLE');

		$driver = new BorrowBoxRecordDriver('PHPUNIT_REC_FORMAT');
		$this->assertEquals(['eAudiobook' => 'eAudiobook'], $driver->getFormats());
	}
}
