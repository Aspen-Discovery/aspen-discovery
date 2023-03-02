<?php
/**
 * Return a string with the minimum number of decimals after the decimal point
 * @param $number
 * @return string
 */
function smarty_modifier_format_float_with_min_decimals($number) {
	if (is_numeric($number)) {
		if (strpos($number, '.') !== false) {
			$output = rtrim($number, '0');
			if (substr($output, -1) == '.') {
				$output = rtrim($output, '.');
			}
		} else {
			$output = $number;
		}
	} else {
		$output = $number;
	}
	return $output;
}