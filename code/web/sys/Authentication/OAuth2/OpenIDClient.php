<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/UserAccount.php';

class OpenIDClient extends DataObject {
	public $__table = 'oauth2_openid_clients';
	protected $id;
	protected $name;
	protected $client_id;
	protected $client_secret;
	protected $redirect_uri;
	protected $is_active;
	protected $created_by;
	protected $created_date;
	protected $last_modified;
	protected $allowed_claims;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$claimsOptions = self::getClaimsOptions();

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Client Name',
				'description' => 'A unique descriptive name for this OpenID client',
				'maxLength' => 255,
				'required' => true,
				'readOnly' => ($context !== 'addNew'),
			],
			'client_id' => [
				'property' => 'client_id',
				'type' => 'text',
				'label' => 'Client ID',
				'description' => 'The OpenID client identifier',
				'maxLength' => 255,
				'required' => true,
				'readOnly' => ($context !== 'addNew'),
			],
			'client_secret' => [
				'property' => 'client_secret',
				'type' => 'password',
				'label' => 'Client Secret',
				'description' => 'The OpenID client secret',
				'maxLength' => 255,
				'hideInLists' => true,
				'readOnly' => ($context !== 'addNew'),
			],
			'allowed_claims' => [
				'property' => 'allowed_claims',
				'type' => 'multiSelect',
				'label' => 'Allowed Claims',
				'description' => 'Which OpenID user claims can this client request',
				'listStyle' => 'checkboxSimple',
				'values' => $claimsOptions,
			],
			'redirect_uri' => [
				'property' => 'redirect_uri',
				'type' => 'url',
				'label' => 'Redirect URI',
				'description' => 'Valid redirect URI for this client',
				'maxLength' => 2000,
				'required' => true
			],
			'is_active' => [
				'property' => 'is_active',
				'type' => 'checkbox',
				'label' => 'Active',
				'description' => 'Whether this client is currently active',
				'default' => true,
			],
			'created_by' => [
				'property' => 'created_by',
				'type' => 'label',
				'label' => 'Created By',
				'description' => 'The user who created this client',
				'default' => UserAccount::getActiveUserId(),
				'readonly' => true,
			],
			'created_date' => [
				'property' => 'created_date',
				'type' => 'date',
				'label' => 'Created',
				'description' => 'When this client was created',
				'readOnly' => ($context !== 'addNew'),
			],
			'last_modified' => [
				'property' => 'last_modified',
				'type' => 'date',
				'label' => 'Last Modified',
				'description' => 'When this client was last modified',
				'readOnly' => ($context !== 'addNew'),
			]
		];

		if ($context == 'addNew') {
			unset($structure['client_id']);
			unset($structure['client_secret']);
			unset($structure['created_by']);
			unset($structure['created_date']);
			unset($structure['last_modified']);
		} elseif ($context !== '') {
			// For existing clients, make client_secret read-only instead of password field
			$structure['client_id']['type'] = 'label';
			$structure['client_secret']['type'] = 'label';
			$structure['client_secret']['description'] = 'The OAuth2 client secret (hidden for security)';
		}

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	function getNumericColumnNames(): array {
		return [
			'id',
			'created_by'
		];
	}

	function getEncryptedFieldNames(): array {
		return ['client_secret'];
	}

	static function getClaimsOptions(): array {
		return [
			'profile' => 'Profile (name, library information)',
			'email' => 'Email',
			'phone' => 'Phone Number',
			'address' => 'Physical Address',
		];
	}

	public function fetch(): bool|DataObject|null {
		$result = parent::fetch();
		if ($result && !empty($this->allowed_claims)) {
			if (is_string($this->allowed_claims)) {
				$claimsArray = array_filter(array_map('trim', explode(',', $this->allowed_claims)));
				$validClaims = self::getClaimsOptions();
				$selectedClaims = array_filter($validClaims, function ($claimKey) use ($claimsArray) {
					return in_array($claimKey, $claimsArray);
				}, ARRAY_FILTER_USE_KEY);
				$this->allowed_claims = $selectedClaims;
			}
		}

		return $result;
	}

	public function insert($context = ''): bool|int {
		$this->created_date = date('Y-m-d H:i:s');
		$this->last_modified = date('Y-m-d H:i:s');
		$this->created_by = UserAccount::getActiveUserId();
		$this->processClaims();

		$this->generateClientSecret();
		$this->generateClientId();

		$ret = parent::insert();
		return $ret;
	}

	public function update($context = ''): bool|int {
		$this->last_modified = date('Y-m-d H:i:s');
		$this->processClaims();
		return parent::update($context);
	}

	public function processClaims(): void {
		if (is_array($this->allowed_claims)) {
			$this->allowed_claims = implode(',', $this->allowed_claims);
		}
	}

	private function generateClientId(): void {
		$this->__set('client_id', 'aspen_' . bin2hex(random_bytes(16)));
	}

	private function generateClientSecret(): void {
		$this->__set('client_secret', bin2hex(random_bytes(32)));
	}

	public function getClaimsArray(): array {
		if (empty($this->allowed_claims)) {
			return [];
		}
		if (is_string($this->allowed_claims)) {
			return array_filter(array_map('trim', explode(',', $this->allowed_claims)));
		}
		if (is_array($this->allowed_claims)) {
			return array_filter(array_map('trim', $this->allowed_claims));
		}
		return [];
	}

	public function setClientId(string $clientId): void {
		$this->client_id = $clientId;
	}

	public function getClientId(): string {
		return $this->client_id;
	}

	public function getClientSecret(): string {
		return $this->client_secret;
	}

	public function setIsActive(bool $isActive): void {
		$this->is_active = $isActive;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getRedirectUri(): string {
		return $this->redirect_uri;
	}
}
