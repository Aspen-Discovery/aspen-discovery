<?php

class APIClientResolver {
	private const LIDA_USER_AGENT_MARKER = 'Aspen LiDA';

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

	public static function isLiDAUserAgent(?string $userAgent): bool {
		$userAgentMissing = is_null($userAgent);
		if ($userAgentMissing) {
			return false;
		}
		return str_contains($userAgent, self::LIDA_USER_AGENT_MARKER);
	}

	public static function isLiDARequest(): bool {
		return self::isLiDAUserAgent(self::getRequestHeader('User-Agent'));
	}
}
