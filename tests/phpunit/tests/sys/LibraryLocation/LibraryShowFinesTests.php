<?php
namespace sys\LibraryLocation;

use Library;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LibraryShowFinesTests extends TestCase {
	private $originalCatalogShowFines = null;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
	}

	protected function setUp(): void {
		global $configArray;
		$this->originalCatalogShowFines = $configArray['Catalog']['showFines'] ?? null;
	}

	protected function tearDown(): void {
		global $configArray;
		$configArray['Catalog']['showFines'] = $this->originalCatalogShowFines;
	}

	public static function showFinesProvider(): array {
		return [
			'config off, library on'    => [false, 1, false],
			'config on, library off'    => [true, 0, false],
			'config on, library on'     => [true, 1, true],
			'config on, library empty'  => [true, null, false],
		];
	}

	#[DataProvider('showFinesProvider')]
	public function testShowFinesInMyAccount(bool $catalogShowFines, ?int $libraryShowFines, bool $expected): void {
		global $configArray;
		$configArray['Catalog']['showFines'] = $catalogShowFines;
		$library = new Library();
		$library->showFines = $libraryShowFines;

		$this->assertSame($expected, $library->showFinesInMyAccount());
	}
}
