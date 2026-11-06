<?php

use PHPUnit\Framework\TestCase;
define('PATH_TO_ROOT', __DIR__ . '/../../../../../');

class EventSublocationLabelTests extends TestCase {

	private const UPPERCASE_LOCATION_ID = 9001;
	private const LOWERCASE_LOCATION_ID = 9002;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Location.php';
		require_once PATH_TO_ROOT . 'code/web/sys/LibraryLocation/Sublocation.php';
	}

	protected function setUp(): void {
		parent::setUp();
		global $aspen_db;

		$aspen_db->exec("INSERT INTO location (locationId, code, displayName, libraryId, groupedWorkDisplaySettingId, browseCategoryGroupId) VALUES (" . self::UPPERCASE_LOCATION_ID . ", 'phpunit_zeta', 'Zeta Library', 1, 1, 1)");
		$aspen_db->exec("INSERT INTO location (locationId, code, displayName, libraryId, groupedWorkDisplaySettingId, browseCategoryGroupId) VALUES (" . self::LOWERCASE_LOCATION_ID . ", 'phpunit_alpha', 'alpha branch', 1, 1, 1)");
		Location::$_locationList = [];
	}

	protected function tearDown(): void {
		global $aspen_db;

		$locationIds = self::UPPERCASE_LOCATION_ID . ', ' . self::LOWERCASE_LOCATION_ID;
		$aspen_db->exec("DELETE FROM sublocation WHERE locationId IN ($locationIds)");
		$aspen_db->exec("DELETE FROM location WHERE locationId IN ($locationIds)");
		Location::$_locationList = [];

		parent::tearDown();
	}

	private function insertSublocation(int $locationId, string $name, int $isValidEventLocation): int {
		$sublocation = new Sublocation();
		$sublocation->locationId = $locationId;
		$sublocation->name = $name;
		$sublocation->weight = 0;
		$sublocation->isValidEventLocation = $isValidEventLocation;
		$sublocation->insert();
		return (int)$sublocation->id;
	}

	private function getLabelsFor(array $sublocationIds): array {
		$labels = Location::getEventSublocationsWithLocationNames();
		return array_intersect_key($labels, array_flip($sublocationIds));
	}

	public function testLabelsIncludeLocationName(): void {
		$zetaId = $this->insertSublocation(self::UPPERCASE_LOCATION_ID, "Children's Library", 1);
		$alphaId = $this->insertSublocation(self::LOWERCASE_LOCATION_ID, "Children's Library", 1);

		$labels = $this->getLabelsFor([$zetaId, $alphaId]);

		$this->assertSame("Zeta Library - Children's Library", $labels[$zetaId]);
		$this->assertSame("alpha branch - Children's Library", $labels[$alphaId]);
	}

	public function testLabelsSortCaseInsensitively(): void {
		$zetaId = $this->insertSublocation(self::UPPERCASE_LOCATION_ID, "Children's Library", 1);
		$alphaId = $this->insertSublocation(self::LOWERCASE_LOCATION_ID, "Children's Library", 1);

		$labels = $this->getLabelsFor([$zetaId, $alphaId]);

		$this->assertSame([$alphaId, $zetaId], array_keys($labels));
	}

	public function testSublocationsNotValidForEventsAreExcluded(): void {
		$eventSublocationId = $this->insertSublocation(self::UPPERCASE_LOCATION_ID, "Children's Library", 1);
		$storageId = $this->insertSublocation(self::UPPERCASE_LOCATION_ID, 'Storage Room', 0);

		$labels = $this->getLabelsFor([$eventSublocationId, $storageId]);

		$this->assertArrayHasKey($eventSublocationId, $labels);
		$this->assertArrayNotHasKey($storageId, $labels);
	}

	public function testPlainSublocationListKeepsNamesOnly(): void {
		$zetaId = $this->insertSublocation(self::UPPERCASE_LOCATION_ID, "Children's Library", 1);

		$names = Location::getEventSublocations(self::UPPERCASE_LOCATION_ID);

		$this->assertSame([$zetaId => "Children's Library"], $names);
	}
}
