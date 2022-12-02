<?php

class ArrayUtils {

	/**
	 * Return the last key of the given array
	 * http://stackoverflow.com/questions/2348205/how-to-get-last-key-in-an-array
	 */
	static public function getLastKey($array) {
		end($array);
		return key($array);
	}

	static public function utf8EncodeArray($array) {
		array_walk_recursive($array, 'ArrayUtils::encode_item');
		return $array;
	}

	static function encode_item(&$item, &$key) {
		if (is_array($item)) {
			ArrayUtils::encode_item($item, $key);
		} elseif (is_string($item)) {
			$key = utf8_encode($key);
			$item = utf8_encode($item);
		}

	}
}