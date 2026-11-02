<?php

use PHPUnit\Framework\TestCase;
define('BORROWBOX_USAGE_PATH_TO_ROOT', __DIR__ . '/../../../../../');

class BorrowBoxUsageTests extends TestCase {

	private BorrowBoxAPIProduct $product;
	private BorrowBoxDriver $driver;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once BORROWBOX_USAGE_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxAPIProduct.php';
		require_once BORROWBOX_USAGE_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxRecordUsage.php';
		require_once BORROWBOX_USAGE_PATH_TO_ROOT . 'code/web/sys/BorrowBox/BorrowBoxStats.php';
		require_once BORROWBOX_USAGE_PATH_TO_ROOT . 'code/web/Drivers/BorrowBoxDriver.php';
	}

	protected function setUp(): void {
		parent::setUp();

		$this->product = new BorrowBoxAPIProduct();
		$this->product->borrowboxId = 'PHPUNIT_USAGE_1';
		if (!$this->product->find(true)) {
			$this->product->title = 'PHPUnit Usage Title';
			$this->product->mediaType = 'eBook';
			$this->product->insert();
		}

		$this->driver = new BorrowBoxDriver();
	}

	public function testRecordCheckoutTrackingCreatesDailyRow(): void {
		$this->driver->trackRecordCheckout('PHPUNIT_USAGE_1');

		$usage = new BorrowBoxRecordUsage();
		$usage->borrowboxId = $this->product->id;
		$this->assertTrue($usage->find(true));
		$this->assertEquals((int)date('Y'), $usage->year);
		$this->assertEquals((int)date('n'), $usage->month);
		$this->assertEquals((int)date('d'), $usage->day);
		$this->assertEquals(1, $usage->timesCheckedOut);
	}

	public function testRepeatedTrackingIncrementsSameDailyRow(): void {
		$this->driver->trackRecordCheckout('PHPUNIT_USAGE_1');
		$this->driver->trackRecordHold('PHPUNIT_USAGE_1');

		$usage = new BorrowBoxRecordUsage();
		$usage->borrowboxId = $this->product->id;
		$usage->find();
		$this->assertEquals(1, $usage->getNumResults());
		$usage->fetch();
		$this->assertEquals(2, $usage->timesCheckedOut);
		$this->assertEquals(1, $usage->timesHeld);
	}

	public function testIncrementStatCreatesDailyRow(): void {
		$this->driver->incrementStat('numRenewals');
		$this->driver->incrementStat('numRenewals');

		$stats = new BorrowBoxStats();
		$stats->year = date('Y');
		$stats->month = date('n');
		$stats->day = date('d');
		$this->assertTrue($stats->find(true));
		$this->assertEquals(2, $stats->numRenewals);
	}

	public function testTrackingUnknownProductDoesNothing(): void {
		$this->driver->trackRecordCheckout('PHPUNIT_DOES_NOT_EXIST');

		$usage = new BorrowBoxRecordUsage();
		$usage->borrowboxId = 'PHPUNIT_DOES_NOT_EXIST';
		$this->assertFalse($usage->find(true));
	}
}
