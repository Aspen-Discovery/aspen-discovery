<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', realpath(__DIR__ . '/../../../../code/web'));
}
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';
require_once ROOT_DIR . '/Drivers/Sierra.php';

/**
 * Sierra driver with the API call and system variable lookup replaced, so the pickup location
 * filtering can be exercised without a Sierra server or database.
 */
class SierraPickupLocationTestDriver extends Sierra {
	public $cannedResponse = null;
	public $exactMatch = false;
	public $requestedUrls = [];

	public function __construct() {
		$this->accountProfile = (object)[
			'vendorOpacUrl' => 'https://sierra.example.org',
			'apiVersion' => 6,
		];
	}

	public function _callUrl($requestType, $url) {
		$this->requestedUrls[] = $url;
		return $this->cannedResponse;
	}

	protected function useExactLocationMatching(): bool {
		return $this->exactMatch;
	}
}

class SierraPickupLocationFilterTest extends TestCase {

	private static function location(string $code, int $id): object {
		return (object)['code' => $code, 'locationId' => $id, 'displayName' => "Branch $code"];
	}

	/** Aspen locations keyed by locationId, as getValidPickupBranches() returns them */
	private static function aspenLocations(string ...$codes): array {
		$locations = [];
		foreach ($codes as $i => $code) {
			$locations[100 + $i] = self::location($code, 100 + $i);
		}
		return $locations;
	}

	private static function codesOf(array $locations): array {
		return array_values(array_map(fn($l) => is_object($l) ? $l->code : ($l['code'] ?? $l), $locations));
	}

	private static function holdshelfResponse(array $codes, ?string $selected = null): object {
		$holdshelf = new stdClass();
		$holdshelf->locations = array_map(fn($c) => (object)['code' => $c, 'name' => "Sierra $c"], $codes);
		if ($selected !== null) {
			$holdshelf->selected = (object)['code' => $selected];
		}
		return (object)['holdshelf' => $holdshelf];
	}

	// ---------------------------------------------------------------
	// filterPickupLocationsByIlsCodes
	// ---------------------------------------------------------------

	public static function matchingCases(): array {
		return [
			// The reported bug: ILS '3' should not pull in '30' / '36'. Starts-with keeps the old behaviour.
			'ticket example, starts with' => [['3', '30', '36'], ['3'], false, ['3']],
			'ticket example, exact' => [['3', '30', '36'], ['3'], true, ['3']],
			// ILS '10' starts with both '1' and '10': the false positive starts-with produces
			'1 vs 10, starts with' => [['1', '10', '12'], ['10'], false, ['1', '10']],
			'1 vs 10, exact' => [['1', '10', '12'], ['10'], true, ['10']],
			'CENTRAL vs CENTRALPW, starts with' => [['CENTRAL', 'CENTRALPW'], ['CENTRALPW'], false, ['CENTRAL', 'CENTRALPW']],
			'CENTRAL vs CENTRALPW, exact' => [['CENTRAL', 'CENTRALPW'], ['CENTRALPW'], true, ['CENTRALPW']],
			// The reason starts-with exists: Sierra returning a code with a trailing 'z'
			'trailing z, starts with' => [['abc', 'xyz'], ['abcz'], false, ['abc']],
			'trailing z, exact (documented trade-off)' => [['abc', 'xyz'], ['abcz'], true, []],
			'exact is case-insensitive' => [['Main', 'east'], ['MAIN', 'EAST'], true, ['Main', 'east']],
			'starts with is case-sensitive (unchanged)' => [['main'], ['MAIN'], false, []],
			'no ILS codes removes everything' => [['1', '2'], [], false, []],
			'empty Aspen code never matches' => [['', '1'], ['1'], false, ['1']],
		];
	}

	#[DataProvider('matchingCases')]
	public function testMatching(array $aspenCodes, array $ilsCodes, bool $exact, array $expectedCodes): void {
		$result = Sierra::filterPickupLocationsByIlsCodes(self::aspenLocations(...$aspenCodes), $ilsCodes, $exact);
		$this->assertSame($expectedCodes, self::codesOf($result));
	}

	public function testKeysArePreservedForObjectLists(): void {
		// Record/AJAX and MyAccount/AJAX index pickupSublocations by these keys
		$result = Sierra::filterPickupLocationsByIlsCodes(self::aspenLocations('1', '10', '12'), ['12'], true);
		$this->assertSame([102], array_keys($result));
	}

	public function testNonLocationEntriesAreKept(): void {
		$locations = ['header' => 'Select a location', 5 => self::location('5', 5), 6 => self::location('6', 6)];
		$result = Sierra::filterPickupLocationsByIlsCodes($locations, ['6'], true);
		$this->assertSame(['header', 6], array_keys($result));
	}

	public function testArrayShapedLocationsFromUserApi(): void {
		// UserAPI::getValidPickupLocations converts locations to arrays before filtering
		$locations = [
			['locationId' => '1', 'code' => '3', 'locationCode' => '3'],
			['locationId' => '2', 'code' => '30', 'locationCode' => '30'],
			['locationId' => '3', 'code' => '36', 'locationCode' => '36'],
		];
		$this->assertSame(['3'], self::codesOf(Sierra::filterPickupLocationsByIlsCodes($locations, ['3'], true)));
		$this->assertSame(['3', '30'], self::codesOf(Sierra::filterPickupLocationsByIlsCodes($locations, ['30'], false)));
	}

	// ---------------------------------------------------------------
	// getValidPickupLocationsForRecordFromILS
	// ---------------------------------------------------------------

	public function testParsesSierraResponseIncludingSelected(): void {
		$driver = new SierraPickupLocationTestDriver();
		$driver->exactMatch = true;
		$driver->cannedResponse = self::holdshelfResponse([' 30 '], '3');
		$patron = (object)['unique_ils_id' => '12345'];

		$result = $driver->getValidPickupLocationsForRecordFromILS('.b10000012', $patron, self::aspenLocations('3', '30', '36'));

		$this->assertSame(['3', '30'], self::codesOf($result));
		$this->assertStringContainsString('/patrons/12345/holds/requests/form?recordNumber=1000001', $driver->requestedUrls[0]);
	}

	public function testSettingTogglesBehaviour(): void {
		$driver = new SierraPickupLocationTestDriver();
		$driver->cannedResponse = self::holdshelfResponse(['10']);
		$patron = (object)['unique_ils_id' => '1'];
		$locations = self::aspenLocations('1', '10', '12');

		$driver->exactMatch = false;
		$this->assertSame(['1', '10'], self::codesOf($driver->getValidPickupLocationsForRecordFromILS('.b1234', $patron, $locations)));
		$driver->exactMatch = true;
		$this->assertSame(['10'], self::codesOf($driver->getValidPickupLocationsForRecordFromILS('.b1234', $patron, $locations)));
	}

	public static function fallbackResponses(): array {
		return [
			'no response' => [null],
			'error response' => [(object)['code' => 132, 'name' => 'Record not found']],
			'empty holdshelf' => [(object)['holdshelf' => null]],
		];
	}

	#[DataProvider('fallbackResponses')]
	public function testFallsBackToUnfilteredLocations($response): void {
		$driver = new SierraPickupLocationTestDriver();
		$driver->exactMatch = true;
		$driver->cannedResponse = $response;
		$locations = self::aspenLocations('1', '10');

		$this->assertSame($locations, $driver->getValidPickupLocationsForRecordFromILS('.b1234', (object)['unique_ils_id' => '1'], $locations));
	}

	public function testMissingRecordIdReturnsLocationsWithoutCallingSierra(): void {
		$driver = new SierraPickupLocationTestDriver();
		$locations = self::aspenLocations('1', '10');

		$this->assertSame($locations, $driver->getValidPickupLocationsForRecordFromILS(null, (object)['unique_ils_id' => '1'], $locations));
		$this->assertSame([], $driver->requestedUrls);
	}
}
