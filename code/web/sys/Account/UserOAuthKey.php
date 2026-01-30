<?php

class UserOAuthKey extends DataObject {
	public $__table = 'user_oauth_keys';
	public $id;
	public $userId;
	public $keyName;
	public $clientId;
	public $clientSecret;
	public $created;
	public $lastUsed;
	public $isActive;

	static function getObjectStructure(string $context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'hidden',
				'label' => 'User Id',
				'description' => 'The user this key belongs to',
			],
			'keyName' => [
				'property' => 'keyName',
				'type' => 'text',
				'label' => 'Key Name',
				'description' => 'A descriptive name for this API key',
				'maxLength' => 100,
				'required' => true,
			],
			'created' => [
				'property' => 'created',
				'type' => 'timestamp',
				'label' => 'Created',
				'description' => 'When the key was created',
				'readOnly' => true,
			],
			'lastUsed' => [
				'property' => 'lastUsed',
				'type' => 'timestamp',
				'label' => 'Last Used',
				'description' => 'When the key was last used',
				'readOnly' => true,
			],
			'isActive' => [
				'property' => 'isActive',
				'type' => 'checkbox',
				'label' => 'Active',
				'description' => 'Whether this key is active',
				'default' => true,
			],
		];
	}

	public function getNumericColumnNames(): array {
		return ['id', 'userId', 'isActive'];
	}

	public function getEncryptedFieldNames(): array {
		return [];
	}

	public static function generateKeys(int $userId, string $keyName): array {
		$oauthKey = new UserOAuthKey();
		$oauthKey->userId = $userId;
		$oauthKey->keyName = $keyName;
		$oauthKey->clientId = bin2hex(random_bytes(16));
		$plainSecret = bin2hex(random_bytes(32));
		$oauthKey->clientSecret = password_hash($plainSecret, PASSWORD_ARGON2ID);
		$oauthKey->created = time();
		$oauthKey->isActive = 1;

		if ($oauthKey->insert()) {
			global $logger;
			$logger->log("OAuth key '{$keyName}' (ID: {$oauthKey->id}) created for user {$userId}", Logger::LOG_NOTICE);

			return [
				'success' => true,
				'clientId' => $oauthKey->clientId,
				'clientSecret' => $plainSecret,
				'id' => $oauthKey->id,
				'warning' => 'Save this secret now. You will not be able to see it again.',
			];
		} else {
			return [
				'success' => false,
				'message' => 'Failed to create OAuth key',
			];
		}
	}

	private static $failedAttempts = [];
	private static $maxAttempts = 5;
	private static $lockoutTime = 900;

	public static function validateCredentials(string $clientId, string $clientSecret): User|bool {
		$lockoutKey = 'oauth_' . $clientId;

		if (isset(self::$failedAttempts[$lockoutKey])) {
			$attempts = self::$failedAttempts[$lockoutKey];
			if ($attempts['count'] >= self::$maxAttempts) {
				if (time() - $attempts['first_attempt'] < self::$lockoutTime) {
					global $logger;
					$logger->log("OAuth key $clientId is locked out due to too many failed attempts", Logger::LOG_WARNING);
					return false;
				} else {
					unset(self::$failedAttempts[$lockoutKey]);
				}
			}
		}

		$oauthKey = new UserOAuthKey();
		$oauthKey->clientId = $clientId;
		$oauthKey->isActive = 1;

		if ($oauthKey->find(true)) {
			if (password_verify($clientSecret, $oauthKey->clientSecret)) {
				unset(self::$failedAttempts[$lockoutKey]);

				$oauthKey->lastUsed = time();
				$oauthKey->update();

				require_once ROOT_DIR . '/sys/Account/User.php';
				$user = new User();
				$user->id = $oauthKey->userId;
				if ($user->find(true)) {
					return $user;
				}
			}
		}

		if (!isset(self::$failedAttempts[$lockoutKey])) {
			self::$failedAttempts[$lockoutKey] = [
				'count' => 1,
				'first_attempt' => time(),
			];
		} else {
			self::$failedAttempts[$lockoutKey]['count']++;
		}

		usleep(500000);

		return false;
	}

	public static function isOAuthEnabled(): bool {
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		return $systemVariables && $systemVariables->enableUserOAuth == 1;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): int|bool {
		global $logger;
		$logger->log("OAuth key '{$this->keyName}' (ID: {$this->id}) deleted for user {$this->userId}", Logger::LOG_WARNING);

		$result = parent::delete($useWhere, $hardDelete);
		return $result;
	}

	public function update(string $context = ''): int|bool {
		if (isset($this->_data['isActive']) && $this->_data['isActive'] != $this->isActive) {
			global $logger;
			$status = $this->isActive ? 'activated' : 'deactivated';
			$logger->log("OAuth key '{$this->keyName}' (ID: {$this->id}) {$status} for user {$this->userId}", Logger::LOG_NOTICE);
		}

		return parent::update($context);
	}
}
