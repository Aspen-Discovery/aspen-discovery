<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class OAuth2RefreshToken extends DataObject {
	public $__table = 'oauth2_refresh_tokens';
	protected $id;
	protected $token_id;
	protected $access_token_id;
	protected $revoked;
	protected $expires_at;
	protected $created_at;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'token_id' => [
				'property' => 'token_id',
				'type' => 'text',
				'label' => 'Token ID',
				'description' => 'The unique refresh token identifier',
				'maxLength' => 100,
			],
			'access_token_id' => [
				'property' => 'access_token_id',
				'type' => 'text',
				'label' => 'Access Token ID',
				'description' => 'The access token this refresh token belongs to',
				'maxLength' => 100,
			],
			'revoked' => [
				'property' => 'revoked',
				'type' => 'checkbox',
				'label' => 'Revoked',
				'description' => 'Whether this token has been revoked',
				'default' => false,
			],
			'expires_at' => [
				'property' => 'expires_at',
				'type' => 'timestamp',
				'label' => 'Expires At',
				'description' => 'When this token expires',
			],
			'created_at' => [
				'property' => 'created_at',
				'type' => 'timestamp',
				'label' => 'Created',
				'description' => 'When this token was created',
			],
		];
	}

	function getNumericColumnNames(): array {
		return ['id'];
	}

	public function insert($context = ''): bool|int {
		$this->created_at = date('Y-m-d H:i:s');
		return parent::insert($context);
	}

	public function isExpired(): bool {
		return strtotime($this->expires_at) < time();
	}

	public function isRevoked(): bool {
		return $this->revoked == 1;
	}

	public function setAccessToken(string $id): void {
		$this->access_token_id = $id;
	}
}