<?php

class APIClientResolver {
	private const LIDA_USER_AGENT_MARKER = 'Aspen LiDA';
	private const LIDA_CLIENT_NAME = 'Aspen LiDA';
	private const UNIDENTIFIED_CLIENT_NAME = 'Other';

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

	public static function getClientName(): string {
		$oauth2ClientId = self::getAuthenticatedOAuth2ClientId();
		$hasOAuth2Client = !empty($oauth2ClientId);
		if ($hasOAuth2Client) {
			return self::getOAuth2ClientName($oauth2ClientId);
		}
		if (self::isLiDARequest()) {
			return self::LIDA_CLIENT_NAME;
		}
		return self::UNIDENTIFIED_CLIENT_NAME;
	}

	private static function getAuthenticatedOAuth2ClientId(): ?string {
		global $composerActive;
		$composerUnavailable = empty($composerActive);
		if ($composerUnavailable) {
			return null;
		}
		require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Middleware.php';
		return OAuth2Middleware::getAuthenticatedClientId();
	}

	private static function getOAuth2ClientName(string $oauth2ClientId): string {
		require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Client.php';
		$client = new OAuth2Client();
		$client->setClientId($oauth2ClientId);
		$clientFound = $client->find(true);
		if (!$clientFound) {
			return $oauth2ClientId;
		}
		$clientName = $client->getName();
		$clientHasName = !empty($clientName);
		if (!$clientHasName) {
			return $oauth2ClientId;
		}
		return $clientName;
	}
}
