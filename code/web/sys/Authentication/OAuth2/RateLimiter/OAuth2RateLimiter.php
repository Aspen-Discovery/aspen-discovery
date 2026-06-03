<?php

require_once ROOT_DIR . '/sys/Authentication/OAuth2/RateLimiter/OAuth2RateLimit.php';

/**
 * OAuth2 Rate Limiting Service
 * Implements sliding window rate limiting for OAuth2 endpoints
 */
class OAuth2RateLimiter {
	private static array $rateLimits = [
		'token' => [
			'requests' => 10,
			// 10 requests
			'window' => 60,
			// per 60 seconds (1 minute)
			'description' => 'Token endpoint rate limit'
		],
		'auth' => [
			'requests' => 20,
			// 20 requests
			'window' => 300,
			// per 300 seconds (5 minutes)
			'description' => 'Authorization endpoint rate limit'
		]
	];

	/**
	 * Check if request should be rate limited
	 * @param string $endpoint The endpoint being accessed
	 * @param string $clientId OAuth2 client ID (optional)
	 * @param string $ipAddress Client IP address
	 * @return array ['allowed' => bool, 'headers' => array, 'resetTime' => int]
	 */
	public static function checkRateLimit(string $endpoint, string $clientId = '', string $ipAddress = ''): array {
		global $logger;
		
		// Get rate limit config for endpoint
		$config = self::$rateLimits[$endpoint] ?? [
			'requests' => 100,
			'window' => 3600,
			'description' => 'Default API rate limit'
		];

		$anonIP = self::anonymizeIP($ipAddress);
		$identifier = $clientId ?: $anonIP;
		if (empty($identifier)) {
			$identifier = 'anonymous';
		}

		$rateLimitRecord = OAuth2RateLimit::getRateLimitRecord($identifier, $anonIP, $endpoint);

		if ($rateLimitRecord->isWindowExpired($config['window'])) {
			$logger->log("[OAuth2] RateLimiter::checkRateLimit() - Rate limit window expired for identifier: " . $identifier . ", endpoint: " . $endpoint . ". Resetting window.", Logger::LOG_DEBUG);
			$rateLimitRecord->resetWindow();
		}

		$remaining = max(0, $config['requests'] - $rateLimitRecord->request_count);
		$resetTime = strtotime($rateLimitRecord->window_start) + $config['window'];

		$headers = [
			'X-RateLimit-Limit' => $config['requests'],
			'X-RateLimit-Remaining' => $remaining,
			'X-RateLimit-Reset' => $resetTime,
			'X-RateLimit-Window' => $config['window']
		];

		if ($rateLimitRecord->request_count >= $config['requests']) {
			$retryAfter = $resetTime - time();
			$logger->log("[OAuth2] RateLimiter::checkRateLimit() - RATE LIMIT EXCEEDED for identifier: " . $identifier . ", endpoint: " . $endpoint . ", retry_after: " . $retryAfter . " seconds", Logger::LOG_WARNING);
			$headers['Retry-After'] = $retryAfter;
			return [
				'allowed' => false,
				'headers' => $headers,
				'resetTime' => $resetTime,
				'message' => "Rate limit exceeded. Try again in " . $retryAfter . " seconds."
			];
		}

		$rateLimitRecord->incrementCount();

		$headers['X-RateLimit-Remaining'] = max(0, $config['requests'] - $rateLimitRecord->request_count);

		return [
			'allowed' => true,
			'headers' => $headers,
			'resetTime' => $resetTime,
			'message' => 'Request allowed'
		];
	}

	/**
	 * Send rate limit response headers
	 */
	public static function sendHeaders(array $headers): void {
		foreach ($headers as $name => $value) {
			header("$name: $value");
		}
	}

	/**
	 * Send rate limit exceeded response
	 */
	public static function sendRateLimitResponse(array $rateLimitResult): void {
		http_response_code(429); // Too Many Requests
		header('Content-Type: application/json');

		self::sendHeaders($rateLimitResult['headers']);

		echo json_encode([
			'error' => 'rate_limit_exceeded',
			'error_description' => $rateLimitResult['message'],
			'retry_after' => $rateLimitResult['headers']['Retry-After'] ?? 60
		]);
	}

	/**
	 * Apply rate limiting to an endpoint
	 * @param string $endpoint Endpoint name
	 * @param string $clientId OAuth2 client ID (optional)
	 * @return bool True if request allowed, false if rate limited (response already sent)
	 */
	public static function enforce(string $endpoint, string $clientId = ''): bool {
		global $logger;
		$ipAddress = self::getClientIP();
		
		$logger->log("[OAuth2] RateLimiter::enforce() - Checking rate limit for endpoint: " . $endpoint . ", client_id: " . ($clientId ?: 'not_provided') . ", ip: " . self::anonymizeIP($ipAddress), Logger::LOG_DEBUG);
		
		$rateLimitResult = self::checkRateLimit($endpoint, $clientId, $ipAddress);

		self::sendHeaders($rateLimitResult['headers']);

		if (!$rateLimitResult['allowed']) {
			$logger->log("[OAuth2] RateLimiter::enforce() - Rate limit enforced, sending 429 response", Logger::LOG_WARNING);
			self::sendRateLimitResponse($rateLimitResult);
			return false;
		}

		return true;
	}

	/**
	 * Get client IP address, handling proxies
	 */
	private static function getClientIP(): string {
		$ipKeys = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR'
		];

		foreach ($ipKeys as $key) {
			if (!empty($_SERVER[$key])) {
				$ip = $_SERVER[$key];
				if (strpos($ip, ',') !== false) {
					$ip = trim(explode(',', $ip)[0]);
				}
				if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					return $ip;
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	}

	/**
	 * Anonymize IP addresses for privacy
	 *  IPv4: Replace last octet with 0 (e.g., 203.0.113.45 → 203.0.113.0)
	 *  IPv6: Replace last 64 bits with 0 (e.g., 2001:0db8:85a3:0000:0000:8a2e:1370:7334 → 2001:0db8:85a3:0000::)
	 *  Invalid IPs are returned as 'unknown'
	 */
	private static function anonymizeIP(string $ip): string {
		if (str_contains($ip, '.') && substr_count($ip, '.') === 3) {
			$parts = explode('.', $ip);
			if (count($parts) === 4 && is_numeric($parts[0]) && is_numeric($parts[3])) {
				$parts[3] = '0';
				return implode('.', $parts);
			}
		} elseif (str_contains($ip, ':')) {
			$parts = explode(':', $ip);
			if (count($parts) >= 3) {
				array_splice($parts, 4);
				return implode(':', $parts) . '::';
			}
		}
		return 'unknown';
	}

}
