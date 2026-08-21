<?php

use PHPUnit\Framework\TestCase;
define('ROOT_DIR', __DIR__ . '/../../../../code/web');
require_once ROOT_DIR . '/sys/Throttler.php';
require_once ROOT_DIR . '/sys/Logger.php';
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/IP/IPAddress.php';
require_once ROOT_DIR . '/sys/ConfigArray.php';


class ThrottlerTests extends TestCase {
	private $throttler;
	private $defaultThrottler;

	protected function setUp(): void {
		parent::setUp();
		global $logger;
		global $configArray;
		$configArray["System"]["applicationName"] = "phpunit";
		global $servername;
		$servername = "unit-test";
		$logger = new Logger();
		//$configArray = readConfig();
		//require_once __DIR__ . '/../../../../code/web/sys/CurlWrapper.php';
		$this->throttler = new Throttler(200);
		$this->defaultThrottler = new Throttler();
	}

	// protected function tearDown(): void {
		
	// }


	public function testThrottle(): void {
		$starttime = (int) floor(microtime(true) * 1000);
		$this->throttler->throttle("https://www.example.com");
		$endtime = (int) floor(microtime(true) * 1000);
		$this->assertSame($starttime, $endtime, "first call to throttle with a url should be instant");
		
		$this->throttler->throttle("https://www.example.com");
		$endtime = (int) floor(microtime(true) * 1000);
		$this->assertTrue($endtime - $starttime >= 200, "subsequent calls should be delayed by interval");
		

		//with no interval set throttle should be instantaneous
		$starttime = (int) floor(microtime(true) * 1000);
		$this->defaultThrottler->throttle("https://www.example.com");
		
		$this->defaultThrottler->throttle("https://www.example.com");
		$endtime = (int) floor(microtime(true) * 1000);
		$this->assertSame($starttime, $endtime, "with no interval subsequent calls should be instant");
		
		$starttime = (int) floor(microtime(true) * 1000);
		$this->throttler->throttle("malformed-url");

		$this->throttler->throttle("malformed-url");
		$endtime = (int) floor(microtime(true) * 1000);
		$this->assertSame($starttime, $endtime, "Malformed urls should return immediately to let outside code handle the bad call right away");

		//sleep 200ms
		usleep(200 * 1000);
		$starttime = (int) floor(microtime(true) * 1000);
		$this->throttler->throttle("https://www.example.com");
		$endtime = (int) floor(microtime(true) * 1000);
		$this->assertSame($starttime, $endtime, "When there has been a natural delay already throttler should not add any aditional.");

	}

	public function testConfigureRequestInterval() : void {
		try {
			$alphaThrottler = new Throttler("banana");
			$this->fail("requestInterval should be an integer");
		} catch (TypeError $e)
		{
		}

		$wordThrottler = new Throttler("3");
		$this->assertSame($wordThrottler->getInterval(), 3);
		
		$fractionThrottler = new Throttler(3.5);
		$this->assertSame($fractionThrottler->getInterval(), 3);
		
		global $configArray;
		$configArray['CurlWrapper']['requestInterval'] = "banana";
		$throttler = new Throttler();
		$this->assertSame($throttler->getInterval(), -1);
		
		$configArray['CurlWrapper']['requestInterval'] = 36;
		$throttler = new Throttler();
		$this->assertSame($throttler->getInterval(), 36);
		
		$configArray['CurlWrapper']['requestInterval'] = -1000;
		$throttler = new Throttler();
		$this->assertSame($throttler->getInterval(), -1);
	}
}
