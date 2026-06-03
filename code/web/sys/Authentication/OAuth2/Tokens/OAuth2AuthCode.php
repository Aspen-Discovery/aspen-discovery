<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class OAuth2AuthCode extends DataObject {
	public $__table = 'oauth2_auth_codes';
	protected $id;
	protected $code_id;
	protected $user_id;
	protected $client_id;
	protected $scopes;
	protected $redirect_uri;
	protected $code_challenge;
	protected $code_challenge_method;
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
			'code_id' => [
				'property' => 'code_id',
				'type' => 'text',
				'label' => 'Code ID',
				'description' => 'The unique authorization code identifier',
				'maxLength' => 100,
			],
			'user_id' => [
				'property' => 'user_id',
				'type' => 'integer',
				'label' => 'User ID',
				'description' => 'The user this code belongs to',
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
				'description' => 'The scopes granted to this code',
			],
			'redirect_uri' => [
				'property' => 'redirect_uri',
				'type' => 'url',
				'label' => 'Redirect URI',
				'description' => 'The redirect URI for this code',
				'maxLength' => 2000,
			],
			'code_challenge' => [
				'property' => 'code_challenge',
				'type' => 'text',
				'label' => 'Code Challenge',
				'description' => 'PKCE code challenge',
			],
			'code_challenge_method' => [
				'property' => 'code_challenge_method',
				'type' => 'text',
				'label' => 'Code Challenge Method',
				'description' => 'PKCE code challenge method',
				'maxLength' => 10,
			],
			'revoked' => [
				'property' => 'revoked',
				'type' => 'checkbox',
				'label' => 'Revoked',
				'description' => 'Whether this code has been revoked',
				'default' => false,
			],
			'expires_at' => [
				'property' => 'expires_at',
				'type' => 'timestamp',
				'label' => 'Expires At',
				'description' => 'When this code expires',
			],
			'created_at' => [
				'property' => 'created_at',
				'type' => 'timestamp',
				'label' => 'Created',
				'description' => 'When this code was created',
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

	public function setRedirectUri(string $uri): void {
		$this->redirect_uri = $uri;
	}
}