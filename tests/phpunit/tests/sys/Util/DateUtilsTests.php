<?php
namespace sys\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateUtilsTests extends TestCase {
	private ?string $originalTimezone = null;
	private $originalActiveLanguage = null;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/Utils/DateUtils.php';
	}

	protected function setUp(): void {
		$this->originalTimezone = date_default_timezone_get();
		date_default_timezone_set('UTC');

		global $activeLanguage;
		$this->originalActiveLanguage = $activeLanguage;
		$activeLanguage = (object)['locale' => 'en_US'];
	}

	protected function tearDown(): void {
		if ($this->originalTimezone !== null) {
			date_default_timezone_set($this->originalTimezone);
		}
		global $activeLanguage;
		$activeLanguage = $this->originalActiveLanguage;
	}

	public static function emptyDateProvider(): array {
		return [
			'empty string'        => [''],
			'null'                => [null],
			'zero date'           => ['0000-00-00'],
			'zero datetime'       => ['0000-00-00 00:00:00'],
			'unparseable string'  => ['not a date'],
		];
	}

	#[DataProvider('emptyDateProvider')]
	public function testFormatDateLocaleReturnsEmptyForInvalidInput($input): void {
		$this->assertSame('', \DateUtils::formatDateLocale($input));
	}

	public static function patternProvider(): array {
		return [
			'iso date'        => ['2025-03-15', 'medium', 'none', 'yyyy-MM-dd', '2025-03-15'],
			'iso datetime'    => ['2025-03-15 14:30:00', 'medium', 'short', 'yyyy-MM-dd HH:mm', '2025-03-15 14:30'],
			'month and year'  => ['2025-03-15', 'medium', 'none', 'MMMM yyyy', 'March 2025'],
		];
	}

	#[DataProvider('patternProvider')]
	public function testFormatDateLocaleHonoursPattern($input, $dateStyle, $timeStyle, $pattern, $expected): void {
		$this->assertSame($expected, \DateUtils::formatDateLocale($input, $dateStyle, $timeStyle, $pattern));
	}

	public function testFormatDateLocaleAcceptsDateTimeObject(): void {
		$date = new \DateTime('2025-03-15 00:00:00', new \DateTimeZone('UTC'));
		$this->assertSame('2025-03-15', \DateUtils::formatDateLocale($date, 'medium', 'none', 'yyyy-MM-dd'));
	}

	public function testFormatDateLocaleAcceptsNumericTimestamp(): void {
		$timestamp = strtotime('2025-03-15 00:00:00');
		$this->assertSame('2025-03-15', \DateUtils::formatDateLocale($timestamp, 'medium', 'none', 'yyyy-MM-dd'));
	}

	public static function emptyTimeRangeProvider(): array {
		return [
			'both empty'        => ['', ''],
			'empty start'       => ['', '2025-01-01 10:00:00'],
			'empty end'         => ['2025-01-01 09:00:00', ''],
			'unparseable start' => ['not a time', '2025-01-01 10:00:00'],
			'unparseable end'   => ['2025-01-01 09:00:00', 'not a time'],
		];
	}

	#[DataProvider('emptyTimeRangeProvider')]
	public function testFormatTimeRangeReturnsEmptyForInvalidInput($start, $end): void {
		$this->assertSame('', \DateUtils::formatTimeRange($start, $end));
	}

	public static function timeRangeProvider(): array {
		return [
			'both am, 12h'             => ['2025-01-01 09:00:00', '2025-01-01 10:30:00', '12', '9:00 - 10:30 AM'],
			'both pm, 12h'             => ['2025-01-01 13:00:00', '2025-01-01 14:30:00', '12', '1:00 - 2:30 PM'],
			'across noon, 12h'         => ['2025-01-01 09:00:00', '2025-01-01 13:00:00', '12', '9:00 AM - 1:00 PM'],
			'noon boundary differs'    => ['2025-01-01 11:00:00', '2025-01-01 12:00:00', '12', '11:00 AM - 12:00 PM'],
			'noon and after same half' => ['2025-01-01 12:00:00', '2025-01-01 13:00:00', '12', '12:00 - 1:00 PM'],
			'24 hour format'           => ['2025-01-01 09:00:00', '2025-01-01 10:30:00', '24', '09:00 - 10:30'],
		];
	}

	#[DataProvider('timeRangeProvider')]
	public function testFormatTimeRange($start, $end, $format, $expected): void {
		$this->assertSame($expected, \DateUtils::formatTimeRange($start, $end, $format));
	}

	public function testFormatTimeRangeAcceptsDateTimeObjects(): void {
		$start = new \DateTime('2025-01-01 09:00:00', new \DateTimeZone('UTC'));
		$end = new \DateTime('2025-01-01 10:30:00', new \DateTimeZone('UTC'));
		$this->assertSame('9:00 - 10:30 AM', \DateUtils::formatTimeRange($start, $end, '12'));
	}

	public function testFormatTimeRangePartsCollapseExposesStartMeridiem(): void {
		$parts = \DateUtils::formatTimeRangeParts('2025-01-01 09:00:00', '2025-01-01 10:30:00', '12');
		$this->assertSame('9:00', $parts['start']);
		$this->assertSame('AM', $parts['startMeridiem']);
		$this->assertSame('10:30 AM', $parts['end']);
	}

	public function testFormatTimeRangePartsAcrossNoonKeepStartMeridiemInline(): void {
		$parts = \DateUtils::formatTimeRangeParts('2025-01-01 09:00:00', '2025-01-01 13:00:00', '12');
		$this->assertSame('9:00 AM', $parts['start']);
		$this->assertSame('', $parts['startMeridiem']);
		$this->assertSame('1:00 PM', $parts['end']);
	}

	public function testFormatTimeRangePartsAreEmptyForInvalidInput(): void {
		$this->assertSame(['start' => '', 'startMeridiem' => '', 'end' => ''], \DateUtils::formatTimeRangeParts('', ''));
	}

	public function testFormatTimeRangeDefaultsTo12Hour(): void {
		$this->assertSame('9:00 - 10:30 AM', \DateUtils::formatTimeRange('2025-01-01 09:00:00', '2025-01-01 10:30:00'));
	}

	public function testFormatTimeRangeForces12HourEvenInA24HourLocale(): void {
		global $activeLanguage;
		$activeLanguage = (object)['locale' => 'en_GB'];
		$default = \DateUtils::formatTimeRange('2025-01-01 11:00:00', '2025-01-01 16:00:00');
		$this->assertMatchesRegularExpression('/\d{1,2}:\d{2}\s*[ap]m/i', $default);
		$this->assertStringNotContainsString('16:00', $default);
		$this->assertSame(\DateUtils::formatTimeRange('2025-01-01 11:00:00', '2025-01-01 16:00:00', '12'), $default);
	}

	public function testFormatTimeRangeForces24HourOnRequest(): void {
		global $activeLanguage;
		$activeLanguage = (object)['locale' => 'en_US'];
		$this->assertSame('09:00 - 16:00', \DateUtils::formatTimeRange('2025-01-01 09:00:00', '2025-01-01 16:00:00', '24'));
	}

	public function testFormatDateTimeLocaleCombinesLocaleDateAnd12HourTime(): void {
		$result = \DateUtils::formatDateTimeLocale('2025-07-07 09:00:00', 'long');
		$this->assertStringContainsString('July 7, 2025', $result);
		$this->assertStringContainsString('9:00 AM', $result);
	}

	public function testFormatDateTimeLocaleForces12HourInA24HourLocale(): void {
		global $activeLanguage;
		$activeLanguage = (object)['locale' => 'en_GB'];
		$result = \DateUtils::formatDateTimeLocale('2025-07-07 16:00:00', 'long');
		$this->assertStringContainsString('7 July 2025', $result);
		$this->assertMatchesRegularExpression('/4:00\s*pm/i', $result);
		$this->assertStringNotContainsString('16:00', $result);
	}

	public function testFormatDateTimeLocaleEmptyForInvalidInput(): void {
		$this->assertSame('', \DateUtils::formatDateTimeLocale(''));
	}

	public static function skeletonProvider(): array {
		return [
			'month and year (en_US)' => ['en_US', '2025-03-15', 'yMMM', 'Mar 2025'],
			'month and year (en_GB)' => ['en_GB', '2025-03-15', 'yMMM', 'Mar 2025'],
			'month and year - short (en_GB)' => ['en_GB', '2025-03-15', 'yMM', '03/2025'],
			'month and year - short (en_US)' => ['en_US', '2025-03-15', 'yMM', '03/2025'],
		];
	}

	#[DataProvider('skeletonProvider')]
	public function testFormatDateLocaleDerivesPatternFromSkeleton($locale, $input, $skeleton, $expected): void {
		global $activeLanguage;
		$activeLanguage->locale = $locale;
		$this->assertSame($expected, \DateUtils::formatDateLocale($input, 'medium', 'none', null, $skeleton));
	}

	public function testFormatDateLocaleSkeletonRespectsLocaleOrdering(): void {
		global $activeLanguage;
		$activeLanguage->locale = 'ja_JP';
		$result = \DateUtils::formatDateLocale('2025-03-15', 'medium', 'none', null, 'yMMM');
		$this->assertStringStartsWith('2025', $result);
	}

	public function testFormatDateLocaleSkeletonOverridesPattern(): void {
		$this->assertSame('Mar 2025', \DateUtils::formatDateLocale('2025-03-15', 'medium', 'none', 'yyyy-MM-dd', 'yMMM'));
	}
}
