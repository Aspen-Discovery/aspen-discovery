<?php

class StringUtils {
	public static function trimStringToLengthAtWordBoundary($string, $maxLength, $addEllipsis) : string {
		if (strlen($string) >= $maxLength) {
			if ($addEllipsis) {
				$maxLength -= 3;
			}
			$lastDelimiter = strrpos(substr($string, 0, $maxLength), ' ');
			$string = substr($string, 0, $lastDelimiter);
			if ($addEllipsis) {
				$string .= '...';
			}
		}
		return $string;
	}

	static function formatCurrency($number) : string {
		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);

		return $currencyFormatter->formatCurrency($number, $currencyCode);
	}

	static function getCurrencySymbol() : string {
		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);

		return $currencyFormatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
	}

	static function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false) : string {
		if ($length == 0) {
			return '';
		}

		if (strlen($string) > $length) {
			$length -= min($length, strlen($etc));
			if (!$break_words && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
			}
			if (!$middle) {
				return substr($string, 0, $length) . $etc;
			} else {
				return substr($string, 0, $length / 2) . $etc . substr($string, -$length / 2);
			}
		} else {
			return $string;
		}
	}

	static function removeTrailingPunctuation($str) : string {
		// We couldn't find the file, return an empty value:
		$str = trim($str);
		$str = preg_replace("~([/:,]+)$~", "", $str);
		return trim($str);
	}

	static function formatBytes($bytes, $precision = 2) : string {
		$units = [
			'B',
			'KB',
			'MB',
			'GB',
			'TB',
		];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	static function unformatBytes($formattedBytes): float|object|int {

		$units = [
			'B' => 0,
			'KB' => 1,
			'MB' => 2,
			'GB' => 3,
			'TB' => 4,
		];

		[
			$value,
			$unit,
		] = explode(' ', $formattedBytes);

		$bytes = (float)$value;
		$bytes *= pow(1024, $units[$unit]);

		return $bytes;
	}

	static function toCamelCase(string $string) : string {
		$string = preg_replace('/[^a-zA-Z0-9 ]/', '', $string);
		$words = explode(' ', $string);
		$words[0] = strtolower($words[0]);
		for ($i = 1; $i < count($words); $i++) {
			$words[$i] = ucfirst(strtolower($words[$i]));
		}
		return implode('', $words);
	}

	static function endsWith($haystack, $needle) : bool {
		$length = strlen($needle);
		if (!$length) {
			return true;
		}
		return substr($haystack, -$length) === $needle;
	}
}
