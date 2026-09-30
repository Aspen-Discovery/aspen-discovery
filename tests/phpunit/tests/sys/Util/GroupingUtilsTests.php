<?php
namespace sys\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GroupingUtilsTests extends TestCase {
	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/Utils/GroupingUtils.php';
	}

	/**
	 * Data provider for date parsing test cases.
	 * @return array
	 */
	public static function dateFormatsProvider() : array
	{
		return [
			// Standard formats
			['MAGAZINE 977.311 CHI SEP 2025', '2025-09-01'],
			['MAGAZINE MAY 2025', '2025-05-01'],
			['PERIODICAL WISCONSIN TRAVEL GUIDE 2022', '2022-01-01'],
			['PERIODICAL 2024 MILWAUKEE TRAVEL GUIDE', '2024-01-01'],
			['PERIODICAL INDIANA TRAVEL GUIDE \'22', '2022-01-01'],
			['PERIODICAL AUGUST 2022', '2022-08-01'],
			['ADULT JAN 13, 2025', '2025-01-13'],
			['MAGAZINE JULY 7, 2025', '2025-07-07'],
			['MAGAZINE JULY 7. 2025', '2025-07-07'],
			['. SEP 2025',  '2025-09-01'],
			['PERIODICAL APR 07, 2025', '2025-04-07'],
			['MAG OCT 23 2023', '2023-10-23'],
			['April 14. 2025', '2025-04-14'],
			["MAG NEW YOR SEP 30 '24", '2024-09-30'],

			// Seasonal formats
			['ADULT FALL 2023', '2023-09-01'],
			['ADULT WINTER 2023', '2023-12-01'],
			['ADULT SUM 2024', '2024-06-01'],
			['ADULT SPRING 2024', '2024-03-01'],

			// Range formats
			['MAGAZINE JUN/JUL 2025', '2025-07-01'],
			['MAGAZINE DEC 2024/JAN 2025', '2025-01-01'],
			['ADULT JUL 13/27, 2024', '2024-07-27'],
			['PERIODICAL DEC 14/DEC 28 2024', '2024-12-28'],
			['MAY 6 & MAY 20, 2023', '2023-05-20'],
			['PERIODICAL V.205 NO.9 MAY 4 & 18, 2024', '2024-05-18'],
			["MAG NEW YOR JUL 8, 15 '24", '2024-07-15'],

			// ISO date formats
			['J PER / RANGER R / 2024-02', '2024-02-01'],
			['J PER / RANGER R / 2024-03', '2024-03-01'],
			['J PER / RANGER R / 2024-04', '2024-04-01'],
			['Adult PERIODICALS: PER / TIME / 2026-08-24', '2026-08-24'],

			//Basic date
			['MAG NEW YOR 8-8-22', '2022-08-08'],
			['MAG NEW YOR 08-12-24', '2024-08-12'],
			['MAG NEW YOR 08-26-2024', '2024-08-26'],

			// Null cases
			['XX(518748.7580)', null],
			['PERIODICAL V.65 NO.4', null],

		];
	}

	#[DataProvider('dateFormatsProvider')] public function testDateParsing($input, $expectedDateStr)
	{
		$result = getSortableDate($input);

		if ($expectedDateStr === null) {
			$this->assertNull($result, "Input: '$input' should return null.");
		} else {
			$this->assertInstanceOf(\DateTime::class, $result);
			$this->assertEquals($expectedDateStr, $result->format('Y-m-d'));
		}
	}
}
