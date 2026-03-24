<?php

function mergeItemSummary(array $localCopies, array $itemSummary) : array {
	foreach ($itemSummary as $key => $item) {
		if (isset($localCopies[$key])) {
			$localCopies[$key]['totalCopies'] += $item['totalCopies'];
			$localCopies[$key]['availableCopies'] += $item['availableCopies'];
			$localCopies[$key]['available'] = $localCopies[$key]['availableCopies'] > 0;
			if ($item['displayByDefault']) {
				$localCopies[$key]['displayByDefault'] = true;
			}
		} else {
			$localCopies[$key] = $item;
		}
	}
	return $localCopies;
}

function sortItemsByShelfLocationAndCallNumber($holdings) : array {
	uasort($holdings, 'compareItems');
	return $holdings;
}

function sortPeriodicalItemsByShelfLocationAndCallNumber($holdings) : array {
	uasort($holdings, 'comparePeriodicalItems');
	return $holdings;
}

/**
 *
 * @param $a - an array of item data
 * @param $b - an array of item data
 * @return int
 */
function compareItems($a, $b) :int  {
	$basicComparison = compareItemBasics($a, $b);
	if ($basicComparison == 0) {
		//If this is not a periodical, do not attempt date comparisons
		return strnatcasecmp($a['callNumber'], $b['callNumber']);
	}else{
		return $basicComparison;
	}
}

/**
 *
 * @param $a - array of item data
 * @param $b - array of item data
 * @return int
 */
function comparePeriodicalItems($a, $b) :int  {
	$basicComparison = compareItemBasics($a, $b);
	if ($basicComparison == 0) {
		$dateA = getSortableDate($a['callNumber']);
		$dateB = getSortableDate($b['callNumber']);
		if (is_null($dateA) && is_null($dateB)) {
			//No date found, just compare the call numbers
			return strnatcasecmp($b['callNumber'], $a['callNumber']);
		}elseif (is_null($dateA)) {
			return 1;
		}elseif (is_null($dateB)) {
			return -1;
		}else{
			return $dateB <=> $dateA;
		}
	}else{
		return $basicComparison;
	}
}

function getShelfLocationPriority(string $shelfLocation) : int {
	global $shelfLocationPriorities;
	if ($shelfLocationPriorities == null) {
		$shelfLocationPriorities = [];
	}
	if (!array_key_exists($shelfLocation, $shelfLocationPriorities)) {
		global $library;
		//Sort anything that isn't prioritized to the end
		$shelfLocationPriorities[$shelfLocation] = 999;
		if ($library->getGroupedWorkDisplaySettings()->getPrioritizedShelfLocations() != null) {
			foreach ($library->getGroupedWorkDisplaySettings()->getPrioritizedShelfLocations() as $prioritizedShelfLocation) {
				if (preg_match("~$prioritizedShelfLocation->shelfLocation~i", $shelfLocation)) {
					$shelfLocationPriorities[$shelfLocation] = $prioritizedShelfLocation->weight;
				}
			}
		}
	}
	return $shelfLocationPriorities[$shelfLocation];
}
/**
 *
 * @param $a - an array of item data
 * @param $b - an array of item data
 * @return int
 */
function compareItemBasics($a, $b) :int  {
	//Sort library and location information
	$localComparison = $a['locationKey'] <=> $b['locationKey'];
	if ($localComparison == 0) {
		//Compare the location
		$locationComparison = strnatcasecmp($a['locationName'], $b['locationName']);
		if ($locationComparison == 0) {
			//Compare the priority of the shelf locations
			$shelfLocationAPriority = getShelfLocationPriority($a['shelfLocation']);
			$shelfLocationBPriority = getShelfLocationPriority($b['shelfLocation']);
			$priorityComparison = $shelfLocationAPriority <=> $shelfLocationBPriority;
			if ($priorityComparison == 0) {
				//First sort by shelfLocation
				return strnatcasecmp($a['shelfLocation'], $b['shelfLocation']);
			}
			return $priorityComparison;
		}
		return $locationComparison;
	}
	return  $localComparison;
}

/**
 * Helper function to extract a sortable date from a string.
 * This function handles various date formats, including month abbreviations,
 * full month names, month ranges, and year-only values.
 * @param string $str The string to parse.
 * @return ?DateTime Returns a DateTime object for sorting, or null if no date is found.
 */
function getSortableDate(?string $str) : ?DateTime {
	//Cache results so we don't need to constantly parse dates from call numbers during sorts.
	global $sortableDateCache;
	if ($sortableDateCache == null) {
		$sortableDateCache = [];
	}
	if (array_key_exists($str, $sortableDateCache)) {
		return $sortableDateCache[$str];
	}
	// Month/Month Range (e.g., JUN/JUL 2025)
	if (preg_match('/([A-Z]{3})\/([A-Z]{3})\s+(\d{4})/i', $str, $matches)) {
		$monthAbbr = $matches[2];
		$year = $matches[3];
		$dateString = "$monthAbbr 01 $year";
		$sortableDateCache[$str] = DateTime::createFromFormat('M j Y', $dateString);
		if ($sortableDateCache[$str] === false) $sortableDateCache[$str] = null;
		return $sortableDateCache[$str];
	}

	// Month/Month Range Spanning Years (e.g., DEC 2024/JAN 2025)
	if (preg_match('/([A-Z]{3})\s+(\d{4})\/([A-Z]{3})\s+(\d{4})/i', $str, $matches)) {
		$monthAbbr = $matches[3];
		$year = $matches[4];
		$dateString = "$monthAbbr 01 $year";
		$sortableDateCache[$str] = DateTime::createFromFormat('M j Y', $dateString);
		if ($sortableDateCache[$str] === false) $sortableDateCache[$str] = null;
		return $sortableDateCache[$str];
	}

	// Day/Day & Day Range (e.g., MAY 6 & MAY 20, 2023, or MAY 6 & 18 2024)
	if (preg_match('/([A-Z]{3,9})\s+(\d{1,2})\s+&\s+([A-Z]{3,9})?\s*(\d{1,2}),\s+(\d{4})/i', $str, $matches)) {
		$year = end($matches);
		$month2 = empty($matches[3]) ? $matches[1] :  $matches[3];
		$date1 = DateTime::createFromFormat('M j Y', "$matches[1] $matches[2] $year");
		$date2 = DateTime::createFromFormat('M j Y', "$month2 $matches[4] $year");
		$sortableDateCache[$str] = max($date1, $date2);
		if ($sortableDateCache[$str] === false) $sortableDateCache[$str] = null;
		return $sortableDateCache[$str];
	}

	// Day/Day Range (e.g., JUL 13/27, 2024)
	if (preg_match('/([A-Z]{3})\s+(\d{1,2})\/(\d{1,2}),\s+(\d{4})/i', $str, $matches)) {
		$monthAbbr = $matches[1];
		$day = $matches[3];
		$dateString = "$monthAbbr $day $matches[4]";
		$sortableDateCache[$str] = DateTime::createFromFormat('M j Y', $dateString);
		if ($sortableDateCache[$str] === false) $sortableDateCache[$str] = null;
		return $sortableDateCache[$str];
	}

	// Day/Day Range (e.g., JUL 8, 15 '24)
	if (preg_match("/([A-Z]{3})\s+(\d{1,2}),\s(\d{1,2})\s+'(\d{2})/i", $str, $matches)) {
		$monthAbbr = $matches[1];
		$day = $matches[3];
		$dateString = "$monthAbbr $day $matches[4]";
		$sortableDateCache[$str] = DateTime::createFromFormat('M j y', $dateString);
		if ($sortableDateCache[$str] === false) $sortableDateCache[$str] = null;
		return $sortableDateCache[$str];
	}

	// Day/Day Range (e.g., DEC 14/DEC 28 2024)
	if (preg_match('/([A-Z]{3})\s+(\d{1,2})\/([A-Z]{3})\s+(\d{1,2})\s+(\d{4})/i', $str, $matches)) {
		$monthAbbr = $matches[3];
		$day = $matches[4];
		$year = $matches[5];
		$dateString = "$monthAbbr $day $year";
		$sortableDateCache[$str] = DateTime::createFromFormat('M j Y', $dateString);
		if ($sortableDateCache[$str] === false) $sortableDateCache[$str] = null;
		return $sortableDateCache[$str];
	}

	// Standard Month Day, Year (e.g., MARCH 15, 2025, MAR 15, 2025, MARCH 15. 2025, MAR 15. 2025, MARCH 15 2025, or MAR 15 2025)
	if (preg_match('/([A-Z]{3,9}\s+\d{1,2})[,.]?\s+(\d{4})/i', $str, $matches)) {
		$date = DateTime::createFromFormat('F j Y', "$matches[1] $matches[2]") ?: DateTime::createFromFormat('M j Y', "$matches[1] $matches[2]");
		if ($date) {
			$sortableDateCache[$str] = $date;
			return $sortableDateCache[$str];
		}
	}

	// Standard Month Day, 'Year (e.g., MARCH 15, '25, MAR 15, '25, MARCH 15. '25, MAR 15. '25, MARCH 15 '25, or MAR 15 '25)
	if (preg_match("/([A-Z]{3,9}\s+\d{1,2})[,.]?\s+'(\d{2})/i", $str, $matches)) {
		$date = DateTime::createFromFormat('M j y', "$matches[1] $matches[2]") ?: DateTime::createFromFormat('F j Y', "$matches[1] $matches[2]");
		if ($date) {
			$sortableDateCache[$str] = $date;
			return $sortableDateCache[$str];
		}
	}

	// Full Month/Abbreviated Month Year (e.g., MARCH 2025 or SEP 2025)
	if (preg_match('/(?:JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER|[A-Z]{3})\s+(\d{4})/i', $str, $matches)) {
		$date = DateTime::createFromFormat('F Y', $matches[0]) ?: DateTime::createFromFormat('M Y', $matches[0]);
		if ($date) {
			$date->setDate($date->format('Y'), $date->format('m'), 1);
			$sortableDateCache[$str] = $date;
			return $sortableDateCache[$str];
		}
	}

	// Seasonal Names (e.g., FALL 2023)
	$seasonal_mapping = [
		'FALL' => 'September', 'AUTUMN' => 'September',
		'SUMMER' => 'June', 'SUM' => 'June',
		'SPRING' => 'March', 'SPR' => 'March',
		'WINTER' => 'December', 'WIN' => 'December'
	];
	if (preg_match('/(FALL|WINTER|SPRING|SUMMER|SPR|WIN|SUM|AUTUMN)\s+(\d{4})/i', $str, $matches)) {
		$monthName = $seasonal_mapping[strtoupper($matches[1])];
		$dateString = "$monthName 01 $matches[2]";
		$sortableDateCache[$str] = DateTime::createFromFormat('F j Y', $dateString);
		if ($sortableDateCache[$str] === false) $sortableDateCache[$str] = null;
		return $sortableDateCache[$str];
	}

	// Standard Month-Day-Year
	if (preg_match("/(\d{1,2}-\d{1,2}-\d{2,4})/i", $str, $matches)) {
		$date = DateTime::createFromFormat('m-d-y',$matches[0]) ?: DateTime::createFromFormat('m-d-Y', $matches[0]);
		if ($date) {
			$sortableDateCache[$str] = $date;
			return $sortableDateCache[$str];
		}
	}

	// Year-only (e.g., 2022 or '22)
	if (preg_match('/(\b(?:19|20)\d{2}\b)|(\'(\d{2}))/', $str, $matches)) {
		$year = end($matches);
		if (strlen($year) === 2) {
			$year = (int)$year < 50 ? '20' . $year : '19' . $year;
		}
		$date = DateTime::createFromFormat('Y', $year);
		if ($date) {
			$date->setDate($date->format('Y'), 1, 1);
			$sortableDateCache[$str] = $date;
			return $sortableDateCache[$str];
		}
	}

	// Return null if no date is found, so we can do different handling
	$sortableDateCache[$str] = null;
	return null;
}
