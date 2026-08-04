<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

/**
 * OAuth2 Rate Limiting Data Model
 * Tracks API usage per client/IP for rate limiting enforcement
 */
class OAuth2RateLimit extends DataObject {
	public $__table = 'oauth2_rate_limits';
	protected $id;
	protected $client_id;
	protected $ip_address;
	protected $endpoint;
	protected $request_count;
	protected $window_start;
	protected $last_request;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'client_id' => [
				'property' => 'client_id',
				'type' => 'text',
				'label' => 'Client ID',
				'description' => 'The OAuth2 client identifier',
				'maxLength' => 255,
			],
			'ip_address' => [
				'property' => 'ip_address',
				'type' => 'text',
				'label' => 'IP Address',
				'description' => 'Client IP address',
				'maxLength' => 45,
			],
			'endpoint' => [
				'property' => 'endpoint',
				'type' => 'text',
				'label' => 'Endpoint',
				'description' => 'API endpoint being rate limited',
				'maxLength' => 100,
			],
			'request_count' => [
				'property' => 'request_count',
				'type' => 'integer',
				'label' => 'Request Count',
				'description' => 'Number of requests in current window',
			],
			'window_start' => [
				'property' => 'window_start',
				'type' => 'timestamp',
				'label' => 'Window Start',
				'description' => 'Start of current rate limit window',
			],
			'last_request' => [
				'property' => 'last_request',
				'type' => 'timestamp',
				'label' => 'Last Request',
				'description' => 'Timestamp of last request',
			],
		];
	}

	function getNumericColumnNames(): array {
		return [
			'id',
			'request_count'
		];
	}

	public function insert($context = ''): bool|int {
		$this->window_start = date('Y-m-d H:i:s');
		$this->last_request = date('Y-m-d H:i:s');
		return parent::insert($context);
	}

	public function update($context = ''): bool|int {
		$this->last_request = date('Y-m-d H:i:s');
		return parent::update($context);
	}

	/**
	 * Get current rate limit record or create new one
	 */
	public static function getRateLimitRecord(string $clientId, string $ipAddress, string $endpoint): OAuth2RateLimit {
		$rateLimitRecord = new OAuth2RateLimit();
		$rateLimitRecord->client_id = $clientId;
		$rateLimitRecord->ip_address = $ipAddress;
		$rateLimitRecord->endpoint = $endpoint;

		if (!$rateLimitRecord->find(true)) {
			// Create new record
			$rateLimitRecord->request_count = 0;
			$rateLimitRecord->insert();
		}

		return $rateLimitRecord;
	}

	/**
	 * Check if current window has expired
	 */
	public function isWindowExpired(int $windowSizeSeconds): bool {
		$windowStart = strtotime($this->window_start);
		$now = time();
		return ($now - $windowStart) >= $windowSizeSeconds;
	}

	/**
	 * Reset the rate limit window
	 */
	public function resetWindow(): void {
		$this->request_count = 0;
		$this->window_start = date('Y-m-d H:i:s');
		$this->update();
	}

	/**
	 * Increment request count
	 */
	public function incrementCount(): void {
		$this->request_count++;
		$this->update();
	}
}
