<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class OAuth2UserConsent extends DataObject {
	public $__table = 'oauth2_user_consents';
	protected $id;
	protected $user_id;
	protected $oauth2_client_id;
	protected $scopes;
	protected $dateCreated;
	protected $lastUpdated;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'user_id' => [
				'property' => 'user_id',
				'type' => 'integer',
				'label' => 'User ID',
				'description' => 'The user who granted consent',
			],
			'oauth2_client_id' => [
				'property' => 'oauth2_client_id',
				'type' => 'integer',
				'label' => 'OAuth2 Client ID',
				'description' => 'The OAuth2 client record this consent applies to',
			],
			'scopes' => [
				'property' => 'scopes',
				'type' => 'text',
				'label' => 'Scopes',
				'description' => 'Comma separated scopes approved by the user',
			],
			'dateCreated' => [
				'property' => 'dateCreated',
				'type' => 'timestamp',
				'label' => 'Date Created',
				'description' => 'When this consent was first created',
			],
			'lastUpdated' => [
				'property' => 'lastUpdated',
				'type' => 'timestamp',
				'label' => 'Last Updated',
				'description' => 'When this consent was last updated',
			],
		];
	}

	function getNumericColumnNames(): array {
		return [
			'id',
			'user_id',
			'oauth2_client_id',
		];
	}

	public function insert($context = ''): bool|int {
		$this->dateCreated = date('Y-m-d H:i:s');
		$this->lastUpdated = date('Y-m-d H:i:s');
		$this->scopes = self::encodeScopes($this->scopes);
		return parent::insert($context);
	}

	public function update($context = ''): bool|int {
		$this->lastUpdated = date('Y-m-d H:i:s');
		$this->scopes = self::encodeScopes($this->scopes);
		return parent::update($context);
	}

	public function getScopesArray(): array {
		return self::normalizeScopes($this->scopes);
	}

	public function hasExactScopes(array $requestedScopes): bool {
		return $this->getScopesArray() === self::normalizeScopes($requestedScopes);
	}

	public static function normalizeScopes(array|string|null $scopes): array {
		if (empty($scopes)) {
			return [];
		}
		if (is_string($scopes)) {
			$scopes = preg_split('/[\s,]+/', trim($scopes)) ?: [];
		}
		$normalizedScopes = [];
		foreach ($scopes as $scope) {
			$scope = trim((string)$scope);
			if ($scope !== '') {
				$normalizedScopes[$scope] = $scope;
			}
		}
		ksort($normalizedScopes);
		return array_values($normalizedScopes);
	}

	public static function encodeScopes(array|string|null $scopes): string {
		return implode(',', self::normalizeScopes($scopes));
	}
}

