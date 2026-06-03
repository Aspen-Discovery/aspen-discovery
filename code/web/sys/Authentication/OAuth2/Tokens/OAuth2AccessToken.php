<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class OAuth2AccessToken extends DataObject {
	public $__table = 'oauth2_access_tokens';
	protected $id;
	protected $token_id;
	protected $user_id;
	protected $client_id;
	protected $scopes;
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
				'description' => 'The unique token identifier',
				'maxLength' => 100,
			],
			'user_id' => [
				'property' => 'user_id',
				'type' => 'integer',
				'label' => 'User ID',
				'description' => 'The user this token belongs to',
			],
			'client_id' => [
				'property' => 'client_id',
				'type' => 'text',
				'label' => 'Client ID',
				'description' => 'The OAuth2 client identifier',
				'maxLength' => 255,
			],
			'scopes' => [
				'property' => 'scopes',
				'type' => 'text',
				'label' => 'Scopes',
				'description' => 'The scopes granted to this token',
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
		return [
			'id',
			'user_id'
		];
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
}
