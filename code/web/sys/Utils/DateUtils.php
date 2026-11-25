<?php


class DateUtils {
	// CLDR pattern field symbols: day period is a (AM/PM), b (noon/midnight) or B (flexible, e.g. "in the morning"); hour is h (1-12), H (0-23), k (1-24) or K (0-11)
	const CLDR_DAY_PERIOD_SYMBOLS 	= 'abB';
	const CLDR_HOUR_SYMBOLS 		= 'hHkK';
	const DAY_PERIOD_REGEX 			= '/[' . self::CLDR_DAY_PERIOD_SYMBOLS . ']/u';
	const TRAILING_DAY_PERIOD_REGEX = '/[' . self::CLDR_HOUR_SYMBOLS . '].*[' . self::CLDR_DAY_PERIOD_SYMBOLS . ']/u';

	// CLDR skeletons for the timeFormat system variable; h is 1-12 and H is 0-23 and the pattern generator places the day period where the locale expects it
	// Any other value (1, the locale-based default) leaves the locale's own time format untouched
	const TIME_FORMAT_SKELETONS = [
		2 => 'hm',
		3 => 'Hm',
	];

	static function addDays($givendate, $day, $newDateFormat = 'Y-m-d H:i:s') {
		$cd = strtotime($givendate);
		$newdate = date($newDateFormat, mktime(date('H', $cd), date('i', $cd), date('s', $cd), date('m', $cd), date('d', $cd) + $day, date('Y', $cd)));
		return $newdate;
	}

	static function isPastDate(?DateTime $date) : bool {
		return $date !== null && $date < new DateTime();
	}

	static function addMinutes($givendate, $minutes) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d H:i:s', mktime(date('H', $cd), date('i', $cd) + $minutes, date('s', $cd), date('m', $cd), date('d', $cd), date('Y', $cd)));
		return $newdate;
	}

	static function formatHumanDate(string $date): string {
		$dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
		if (!$dt) {
			return $date;
		}
		return self::formatDate($dt) ?: $date;
	}

	static function formatHumanDateTime(string $dateTime): string {
		$dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);
		if (!$dt) {
			return $dateTime;
		}
		return self::formatDateTime($dt) ?: $dateTime;
	}

	static function formatDate(DateTimeImmutable $date): string|false {
		global $locale;
		$formatter = new IntlDateFormatter(self::formatLocale($locale), IntlDateFormatter::FULL, IntlDateFormatter::NONE);
		return $formatter->format($date);
	}

	static function formatDateTime(DateTimeImmutable $dateTime): string|false {
		global $locale;
		$formatter = new IntlDateFormatter(self::formatLocale($locale), IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
		return $formatter->format($dateTime);
	}

	static function formatLocale(?string $locale): string {
		if (!$locale) {
			return 'en_US';
		}
		return str_replace('-', '_', $locale);
	}

	static function toTimestamp(mixed $value): ?int {
		if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
			return null;
		}
		if ($value instanceof DateTimeInterface) {
			return $value->getTimestamp();
		}
		if (is_numeric($value)) {
			return (int)$value;
		}
		$timestamp = strtotime($value);
		if ($timestamp === false || $timestamp === -1) {
			return null;
		}
		return $timestamp;
	}
	
	private static function getTimeFormat(?int $formatOverride = null): int {
		if ($formatOverride !== null) {
			return $formatOverride;
		}

		$systemVariables = SystemVariables::getSystemVariables();

		return (int)($systemVariables->timeFormat ?? 0);
	}

	static function getTimeFormatter(?int $formatOverride = null): IntlDateFormatter {
		global $activeLanguage;

		$locale 	= $activeLanguage->locale ?? 'en_US';
		$timezone 	= date_default_timezone_get();
		$formatter 	= new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::SHORT, $timezone);

		$skeleton = self::TIME_FORMAT_SKELETONS[self::getTimeFormat($formatOverride)] ?? null;
		if ($skeleton === null) {
			return $formatter;
		}

		$formatter->setPattern((new IntlDatePatternGenerator($locale))->getBestPattern($skeleton));
		return $formatter;
	}

	static function formatTimeLocale(int|DateTimeInterface $timestamp, bool $includeDayPeriod = true, ?int $formatOverride = null):string {
		$formatter = self::getTimeFormatter($formatOverride);

		if (!$includeDayPeriod) {
			$localePattern 			= preg_replace(self::DAY_PERIOD_REGEX, '', $formatter->getPattern());
			// Remove leftover separators if any (en uses U+202F before AM/PM in current CLDR, so trim() is not an option)
			$trimmedLocalePattern 	= preg_replace('/^\p{Z}+|\p{Z}+$/u', '', $localePattern);
			$formatter->setPattern($trimmedLocalePattern);
		}

		return $formatter->format($timestamp);
	}

	static function formatDayPeriodLocale(int|DateTimeInterface $timestamp, ?int $formatOverride = null): string {
		$formatter = self::getTimeFormatter($formatOverride);

		if (!preg_match(self::DAY_PERIOD_REGEX, $formatter->getPattern(), $dayPeriodSymbol)) {
			return '';
		}

		$formatter->setPattern($dayPeriodSymbol[0]);
		return $formatter->format($timestamp);
	}

	static function hasTrailingDayPeriod(?int $formatOverride = null): bool {
		$formatter = self::getTimeFormatter($formatOverride);

		// Locales like ko ("a h:mm") and zh ("ah:mm") lead with the day period, where lifting it onto the start of a range would read as nonsense
		return preg_match(self::TRAILING_DAY_PERIOD_REGEX, $formatter->getPattern()) === 1;
	}

	static function formatDateLocale($string, $dateStyle = 'medium', $timeStyle = 'none', $pattern = null, $skeleton = null): string|false {
		global $activeLanguage;

		$timestamp = self::toTimestamp($string);
		if ($timestamp === null) {
			return '';
		}

		$dateStyleMap = [
			'none'   => IntlDateFormatter::NONE,
			'short'  => IntlDateFormatter::SHORT,
			'medium' => IntlDateFormatter::MEDIUM,
			'long'   => IntlDateFormatter::LONG,
			'full'   => IntlDateFormatter::FULL,
		];

		$timeStyleMap = [
			'none'   => IntlDateFormatter::NONE,
			'short'  => IntlDateFormatter::SHORT,
			'medium' => IntlDateFormatter::MEDIUM,
			'long'   => IntlDateFormatter::LONG,
			'full'   => IntlDateFormatter::FULL,
		];

		$dateStyleConstant = $dateStyleMap[strtolower($dateStyle)] ?? IntlDateFormatter::MEDIUM;
		$timeStyleConstant = $timeStyleMap[strtolower($timeStyle)] ?? IntlDateFormatter::NONE;

		$locale = $activeLanguage->locale ?? 'en_US';
		$timezone = date_default_timezone_get();

		if ($skeleton !== null) {
			$pattern = (new IntlDatePatternGenerator($locale))->getBestPattern($skeleton);
		}

		$formatter = new IntlDateFormatter(
			$locale,
			$dateStyleConstant,
			$timeStyleConstant,
			$timezone
		);

		if ($pattern !== null) {
			$formatter->setPattern($pattern);
		}

		return $formatter->format($timestamp);
	}

	static function formatHour(?string $time, bool $useNoonAndMidnight = false, ?int $formatOverride = null): string {
		if (empty($time)) {
			return '';
		}

		if ($useNoonAndMidnight) {	
			$parsedTime = date_parse($time);
			$isOnTheHour = $parsedTime['minute'] === 0 && $parsedTime['second'] === 0;
			if ($isOnTheHour && $parsedTime['hour'] === 12) {
				return translate([
					'text' => 'Noon',
					'isPublicFacing' => true,
				]);
			}
			if ($isOnTheHour && $parsedTime['hour'] % 24 === 0) {
				return translate([
					'text' => 'Midnight',
					'isPublicFacing' => true,
				]);
			}
		}

		return self::formatTimeLocale(strtotime($time), true, $formatOverride);
	}

	static function formatTimeRange(mixed $startTime, mixed $endTime, ?int $formatOverride = null): string {
		$parts = self::formatTimeRangeParts($startTime, $endTime, $formatOverride);
		if ($parts['start'] === '' && $parts['end'] === '') {
			return '';
		}
		return $parts['start'] . ' - ' . $parts['end'];
	}

	static function formatTimeRangeParts(mixed $startTime, mixed $endTime, ?int $formatOverride = null): array {
		$startTimestamp = self::toTimestamp($startTime);
		$endTimestamp = self::toTimestamp($endTime);

		if ($startTimestamp === null || $endTimestamp === null) {
			return ['start' => '', 'startMeridiem' => '', 'end' => ''];
		}

		// A day period shared by both endpoints is redundant on the start, so compare the rendered values rather than assuming a noon split
		$startDayPeriod = self::hasTrailingDayPeriod($formatOverride) ? self::formatDayPeriodLocale($startTimestamp, $formatOverride) : '';
		$collapseDayPeriod = $startDayPeriod !== '' && $startDayPeriod === self::formatDayPeriodLocale($endTimestamp, $formatOverride);

		return [
			'start'         => self::formatTimeLocale($startTimestamp, !$collapseDayPeriod, $formatOverride),
			'startMeridiem' => $collapseDayPeriod ? $startDayPeriod : '',
			'end'           => self::formatTimeLocale($endTimestamp, true, $formatOverride),
		];
	}

	static function formatDateTimeLocale($value, $dateStyle = 'long', ?int $formatOverride = null): string {
		$timestamp = self::toTimestamp($value);
		if ($timestamp === null) {
			return '';
		}

		return self::formatDateLocale($timestamp, $dateStyle) . ' ' . self::formatTimeLocale($timestamp, true, $formatOverride);
	}
}