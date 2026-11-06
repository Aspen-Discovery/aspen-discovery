<?php
namespace Drivers;

use Koha;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SimpleXMLElement;

class KohaRenewErrorMessageTests extends TestCase {
	private Koha $driver;
	private ReflectionMethod $renewErrorMessageMethod;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once ROOT_DIR . '/Drivers/Koha.php';
	}

	protected function setUp(): void {
		$driverClass = new ReflectionClass(Koha::class);
		$this->driver = $driverClass->newInstanceWithoutConstructor();
		$this->renewErrorMessageMethod = new ReflectionMethod(Koha::class, 'getRenewErrorMessage');
	}

	private function renewErrorMessage(?SimpleXMLElement $error, ?string $errorAlt): string {
		return $this->renewErrorMessageMethod->invoke($this->driver, $error, $errorAlt);
	}

	public function testOnReserveCodeFromAllowsRenewal(): void {
		$this->assertSame('On hold for another patron', $this->renewErrorMessage(null, 'on_reserve'));
	}

	public function testOnReserveCodeFromIlsDiRenewLoan(): void {
		$error = new SimpleXMLElement('<error>on_reserve</error>');
		$this->assertSame('On hold for another patron', $this->renewErrorMessage($error, null));
	}

	public function testUnknownCodeIsShownInMessage(): void {
		$this->assertSame('Unknown error: not_a_code', $this->renewErrorMessage(null, 'not_a_code'));
	}

	public function testMissingCodeGivesGenericMessage(): void {
		$this->assertSame('Unknown error', $this->renewErrorMessage(null, null));
	}
}
