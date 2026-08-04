<?php /** @noinspection PhpMissingFieldTypeInspection */


class TwoFactorAuthTOTPSecret extends DataObject {
	public $__table = 'two_factor_auth_totp_secrets';   // table name
	public $id;
	public $userId;
	public $secretKey;
	public $createdDate;
	public $verified;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'userId' => [
				'property' => 'userId',
				'type' => 'text',
				'label' => 'User',
				'description' => 'The user who owns this TOTP secret',
				'readOnly' => true,
			],
			'secretKey' => [
				'property' => 'secretKey',
				'type' => 'text',
				'label' => 'Secret Key',
				'description' => 'The encoded TOTP secret',
				'readOnly' => true,
				'hideInLists' => true,
			],
			'createdDate' => [
				'property' => 'createdDate',
				'type' => 'text',
				'label' => 'Code',
				'description' => 'When the TOTP secret was created',
				'readOnly' => true,
			],
			'verified' => [
				'property' => 'verified',
				'type' => 'checkbox',
				'label' => 'Verified',
				'description' => 'Whether the TOTP secret was verified',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function generateSecret(): string {
		$randomBytes = random_bytes(20);
		return self::base32Encode($randomBytes);
	}

	public static function base32Encode(string $data): string {
		$base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$encoded = '';
		$bitString = '';

		for ($i = 0; $i < strlen($data); $i++) {
			$bitString .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
		}

		$bitString = str_pad($bitString, ceil(strlen($bitString) / 5) * 5, '0', STR_PAD_RIGHT);

		for ($i = 0; $i < strlen($bitString); $i += 5) {
			$chunk = substr($bitString, $i, 5);
			$encoded .= $base32chars[bindec($chunk)];
		}

		return $encoded;
	}

	public static function base32Decode(string $encoded): string {
		$base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$encoded = strtoupper($encoded);
		$bitString = '';

		$encoded = rtrim($encoded, '=');
		for ($i = 0; $i < strlen($encoded); $i++) {
			$char = $encoded[$i];
			$pos = strpos($base32chars, $char);
			if ($pos === false) {
				throw new Exception('Invalid Base32 character: ' . $char);
			}
			$bitString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
		}

		$bitString = substr($bitString, 0, strlen($bitString) - (strlen($bitString) % 8));
		$decoded = '';
		for ($i = 0; $i < strlen($bitString); $i += 8) {
			$byte = substr($bitString, $i, 8);
			$decoded .= chr(bindec($byte));
		}

		return $decoded;
	}

	public static function generateCode(string $secret): string {
		return self::generateCodeForTime($secret, floor(time() / 30));
	}

	public static function generateCodeForTime(string $secret, int $timeCounter): string {
		try {
			$decodedSecret = self::base32Decode($secret);
		} catch (Exception $error) {
			return '000000';
		}
		$timeBytes = pack('N', 0) . pack('N', $timeCounter);
		$hmac = hash_hmac('sha1', $timeBytes, $decodedSecret, true);
		$offset = ord($hmac[19]) & 0x0f;
		$code = (((ord($hmac[$offset]) & 0x7f) << 24) | ((ord($hmac[$offset + 1]) & 0xff) << 16) | ((ord($hmac[$offset + 2]) & 0xff) << 8) | (ord($hmac[$offset + 3]) & 0xff)) % 1000000;
		return str_pad($code, 6, '0', STR_PAD_LEFT);
	}

	public static function verifyCode(string $secret, string $code, int $timeWindow = 1): bool {
		$currentTime = floor(time() / 30);
		for ($i = -$timeWindow; $i <= $timeWindow; $i++) {
			$expectedCode = self::generateCodeForTime($secret, $currentTime + $i);
			if (hash_equals($code, $expectedCode)) {
				return true;
			}
		}
		return false;
	}

	public static function getOrCreateSecret(bool $createNew = false): TwoFactorAuthTOTPSecret {
		$secret = new TwoFactorAuthTOTPSecret();
		$secret->userId = UserAccount::getActiveUserId();
		$secret->verified = $createNew ? 0 : 1;
		if (!$createNew) {
			$secret->verified = 1;
			if ($secret->find(true)) {
				return $secret;
			}
		}

		$secret = new TwoFactorAuthTOTPSecret();
		$secret->userId = UserAccount::getActiveUserId();
		$secret->secretKey = (new TwoFactorAuthTOTPSecret)->generateSecret();
		$secret->createdDate = time();
		$secret->verified = 0;
		$secret->insert();

		return $secret;
	}

	public static function generateQRCodeURI(TwoFactorAuthTOTPSecret $secret, string $issuer, User $user = null): string {
		global $configArray;

		if ($user === null) {
			$user = new User();
			$user->id = UserAccount::getActiveUserId();
			if (!$user->find(true)) {
				return '';
			}
		}

		$label = urlencode($issuer . ':' . $user->ils_barcode);
		$params = [
			'secret=' . $secret->secretKey,
			'issuer=' . urlencode($issuer),
			'algorithm=SHA1',
			'digits=6',
			'period=30',
		];

		if (!empty($library->logoApp)) {
			$params[] = 'image=' . urlencode($configArray['Site']['local'] . '/files/original/' . $library->logoApp);
		} else {
			$params[] = 'image=';
		}

		$otpauthUri = 'otpauth://totp/' . $label . '?' . implode('&', $params);
		return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpauthUri);
	}

	public function verify(): bool {
		$this->verified = 1;
		return $this->update() !== false;
	}

	public static function deleteUserSecrets(int $userId): void {
		$secret = new TwoFactorAuthTOTPSecret();
		$secret->userId = $userId;
		$secret->find();
		while ($secret->fetch()) {
			$secret->delete();
		}
	}

	function canActiveUserEdit(): bool {
		return false;
	}
}
