<?php

class ISBNConverter {
	public static function convertISBN10to13($isbn10) {
		if (strlen($isbn10) != 10) {
			return '';
		}
		$isbn = '978' . substr($isbn10, 0, 9);
		//Calculate the 13 digit checksum
		$sumOfDigits = 0;
		for ($i = 0; $i < 12; $i++) {
			$multiplier = 1;
			if ($i % 2 == 1) {
				$multiplier = 3;
			}
			$sumOfDigits += $multiplier * (int)($isbn[$i]);
		}
		$modValue = $sumOfDigits % 10;
		if ($modValue == 0) {
			$checksumDigit = 0;
		} else {
			$checksumDigit = 10 - $modValue;
		}
		return $isbn . $checksumDigit;
	}

	public static function convertISBN13to10($isbn13) {
		if (substr($isbn13, 0, 3) == '978') {
			$isbn = substr($isbn13, 3, 9);
			$sumOfDigits = 0;
			for ($i = 0; $i < 9; $i++) {
				$sumOfDigits += ($i + 1) * (int)($isbn[$i]);
			}
			$modValue = $sumOfDigits % 11;
			if ($modValue == 10) {
				$checksumDigit = 'X';
			} else {
				$checksumDigit = $modValue;
			}
			return $isbn . $checksumDigit;
		} else {
			//Can't convert to 10 digit
			return '';
		}
	}
}