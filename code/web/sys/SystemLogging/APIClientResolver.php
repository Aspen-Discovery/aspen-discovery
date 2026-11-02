<?php

class APIClientResolver {
	public static function getRequestHeaders(): array {
		$headersAvailable = function_exists('getallheaders');
		if (!$headersAvailable) {
			return [];
		}
		return getallheaders();
	}

	public static function getRequestHeader(string $headerToRetrieve): ?string {
		foreach (self::getRequestHeaders() as $name => $value) {
			$headerMatches = strcasecmp($name, $headerToRetrieve) === 0;
			if ($headerMatches) {
				return $value;
			}
		}
		return null;
	}
}
