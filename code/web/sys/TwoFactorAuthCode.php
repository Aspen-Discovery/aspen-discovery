<?php /** @noinspection PhpMissingFieldTypeInspection */


class TwoFactorAuthCode extends DataObject {
	public $__table = 'two_factor_auth_codes';   // table name

	public $id;
	public $userId;
	public $sessionId;
	public $code;
	public $dateSent;
	public $status;

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
				'description' => 'The user who has requested a code',
				'readOnly' => true,
			],
			'sessionId' => [
				'property' => 'sessionId',
				'type' => 'text',
				'label' => 'Session',
				'description' => 'The session that has been authenticated',
				'readOnly' => true,
			],
			'code' => [
				'property' => 'code',
				'type' => 'text',
				'label' => 'Code',
				'description' => 'The code used for authentication',
				'readOnly' => true,
			],
			'dateSent' => [
				'property' => 'dateSent',
				'type' => 'date',
				'label' => 'Date Send/Created',
				'description' => 'The date the code was created',
				'readOnly' => true,
			],
			'status' => [
				'property' => 'status',
				'type' => 'text',
				'label' => 'Status',
				'description' => 'The status of the code',
				'readOnly' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function createCode($num = 1, $backup = false) : bool {
		for ($i = 1; $i <= $num; $i++) {
			$twoFactorAuthCode = new TwoFactorAuthCode();
			$twoFactorAuthCode->code = mt_rand(100000, 999999);
			$twoFactorAuthCode->userId = UserAccount::getActiveUserId();
			$twoFactorAuthCode->dateSent = time();
			if ($backup) {
				$twoFactorAuthCode->status = "backup";
			} else {
				$twoFactorAuthCode->status = "created";
			}
			$twoFactorAuthCode->insert();

			if (!$backup) {
				if (!$twoFactorAuthCode->sendCode()){
					return false;
				}
			}
		}

		$this->cleanupOldCodes();

		return true;
	}

	public function createRecoveryCode($username) : array {
		$user = new User();
		$user->ils_barcode = $username;
		if ($user->find(true)) {
			if ($user->twoFactorStatus == '1') {
				$twoFactorAuthCode = new TwoFactorAuthCode();
				$twoFactorAuthCode->code = mt_rand(100000, 999999);
				$twoFactorAuthCode->userId = $user->id;
				$twoFactorAuthCode->dateSent = time();
				$twoFactorAuthCode->status = "created";
				$twoFactorAuthCode->insert();
				$result = [
					'success' => true,
					'message' => translate([
						'text' => 'Recovery code: ' . $twoFactorAuthCode->code,
						'isAdminFacing' => true,
					]),
				];
			} else {
				$result = [
					'success' => false,
					'message' => translate([
						'text' => 'User not setup for two-factor authentication',
						'isAdminFacing' => true,
					]),
				];
			}
		} else {
			$result = [
				'success' => false,
				'message' => translate([
					'text' => 'User not found',
					'isAdminFacing' => true,
				]),
			];
		}
		return $result;
	}

	function sendCode() : bool {
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mail = new Mailer();
		$replyToAddress = "";
		$body = "*****" . translate([
				'text' => 'This is an auto-generated email response. Please do not reply.',
				'isPublicFacing' => true,
			]) . "*****";
		$body .= "\r\n\r\n" . translate([
				'text' => 'Your code to login is',
				'isPublicFacing' => true,
			]) . " " . $this->code;
		$body .= "\r\n\r\n" . translate([
				'text' => 'This code is only valid for the next 15 minutes.',
				'isPublicFacing' => true,
			]);

		$patron = new User();
		$patron->id = $this->userId;
		if ($patron->find(true)) {
			if (!empty($patron->email)) {
				/** @noinspection PhpUnusedLocalVariableInspection */
				$emailResult = $mail->send($patron->email, translate([
					'text' => "Your one-time login code",
					'isPublicFacing' => true,
				]), $body, $replyToAddress);
				$this->status = "sent";
				$this->update();
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function validateCode($code, $method, $secretId = null): array {
		global $library;
		require_once ROOT_DIR . '/sys/TwoFactorAuthSetting.php';
		require_once ROOT_DIR . '/sys/TwoFactorAuthTOTPSecret.php';

		$userId = UserAccount::getActiveUserId();
		$user = new User();
		$user->id = $userId;
		if (!$user->find(true)) {
			return [
				'success' => 'false',
				'message' => translate([
					'text' => 'No user found',
					'isPublicFacing' => true,
				]),
			];
		}
		$authSetting = $user->getTwoFactorAuthenticationSetting();
		if ($authSetting) {
			$deniedMessage = $authSetting->deniedMessage;
		} else {
			$deniedMessage = "";
		}

		if ($method === 'totp') {
			return $this->validateTOTPCode($code, $userId, $deniedMessage, $secretId);
		}

		// Otherwise, validate email code
		return $this->validateEmailCode($code, $userId, $deniedMessage);
	}

	function createNewBackups() : void {
		$oldBackupCodes = new TwoFactorAuthCode();
		$oldBackupCodes->userId = UserAccount::getActiveUserId();
		$oldBackupCodes->status = "backup";
		$oldBackupCodes->find();
		while ($oldBackupCodes->fetch()) {
			$this->deleteCode($oldBackupCodes->code);
		}

		$this->createCode(5, true);
	}

	private function validateTOTPCode($code, $userId, $deniedMessage, $secretId = null): array {
		require_once ROOT_DIR . '/sys/TwoFactorAuthTOTPSecret.php';
		$totpSecret = new TwoFactorAuthTOTPSecret();
		$totpSecret->userId = $userId;

		if ($secretId !== null) {
			$totpSecret->id = $secretId;
			if (!$totpSecret->find(true)) {
				return [
					'success' => 'false',
					'message' => translate([
						'text' => 'TOTP secret not found. ' . $deniedMessage,
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			$totpSecret->verified = 1;
			if (!$totpSecret->find(true)) {
				return [
					'success' => 'false',
					'message' => translate([
						'text' => 'TOTP not configured. ' . $deniedMessage,
						'isPublicFacing' => true,
					]),
				];
			}
		}

		// Verify TOTP code
		if (TwoFactorAuthTOTPSecret::verifyCode($totpSecret->secretKey, $code, 1)) {
			return [
				'success' => 'true',
				'message' => translate([
					'text' => 'Code OK',
					'isPublicFacing' => true,
				]),
			];
		}

		// Check if it's a backup code
		$backupCode = new TwoFactorAuthCode();
		$backupCode->code = $code;
		$backupCode->userId = $userId;
		$backupCode->status = 'backup';

		if ($backupCode->find(true)) {
			$backupCode->status = 'used';
			$backupCode->sessionId = session_id();
			$backupCode->update();
			return [
				'success' => 'true',
				'message' => translate([
					'text' => 'Backup code accepted',
					'isPublicFacing' => true,
				]),
			];
		}

		return [
			'success' => 'false',
			'message' => translate([
				'text' => 'Invalid code. ' . $deniedMessage,
				'isPublicFacing' => true,
			]),
		];
	}

	private function validateEmailCode($code, $userId, $deniedMessage): array {
		$codeToCheck = new TwoFactorAuthCode();
		$codeToCheck->code = $code;
		if ($codeToCheck->find(true)) {
			if ($codeToCheck->userId == $userId) {
				if ($codeToCheck->status != "used") {
					$codeToCheck->status = "used";
					$codeToCheck->sessionId = session_id();
					$codeToCheck->update();
					$result = [
						'success' => 'true',
						'message' => translate([
							'text' => 'Code OK',
							'isPublicFacing' => true,
						]),
					];
				} else {
					$result = [
						'success' => 'false',
						'message' => translate([
							'text' => 'You have already used this code or it expired. ' . $deniedMessage,
							'isPublicFacing' => true,
						]),
					];
				}
			} else {
				// code belongs to another user
				$result = [
					'success' => 'false',
					'message' => translate([
						'text' => 'Sorry, this code is invalid. ' . $deniedMessage,
						'isPublicFacing' => true,
					]),
				];
			}
		} else {
			// code not found
			$result = [
				'success' => 'false',
				'message' => translate([
					'text' => 'Sorry, this code is invalid. ' . $deniedMessage,
					'isPublicFacing' => true,
				]),
			];
		}

		return $result;
	}

	function getBackups() : array {
		$backupCodes = [];
		$backupCode = new TwoFactorAuthCode();
		$backupCode->userId = UserAccount::getActiveUserId();
		$backupCode->status = "backup";
		$backupCode->find();
		while ($backupCode->fetch()) {
			$backupCodes[] = $backupCode->code;
		}
		return $backupCodes;
	}

	function deleteCode($code) : bool {
		$codeToCheck = new TwoFactorAuthCode();
		$codeToCheck->code = $code;
		if ($codeToCheck->find(true)) {
			$codeToCheck->delete();
			return true;
		}
		return false;
	}

	function cleanupOldCodes() : void {
		// delete codes with a used status and no longer have a valid session id
		$codesFromOldSessions = new TwoFactorAuthCode();
		$codesFromOldSessions->status = "used";
		$codesFromOldSessions->whereAdd("sessionId != 'null'");
		$codesFromOldSessions->find();
		while ($codesFromOldSessions->fetch()) {
			$session = new Session();
			$session->setSessionId($codesFromOldSessions->sessionId);
			if (!$session->find()) {
				$codeToDelete = clone $codesFromOldSessions;
				$codeToDelete->delete();
			}
		}
		// delete codes with a status of: sent or created codes AND are older than 15 minutes
		$codesToExpire = new TwoFactorAuthCode();
		$codesToExpire->whereAdd("status = 'sent' OR status = 'created'");
		$codesToExpire->whereAdd("dateSent < " . (time() - 60 * 30));
		$codesToExpire->find();
		while ($codesToExpire->fetch()) {
			$codeToDelete = clone $codesToExpire;
			$codeToDelete->delete();
		}
	}

	function deactivate2FA($method): void {
		$user = new User();
		$user->id = UserAccount::getActiveUserId();
		if ($user->find(true)) {
			$methods = array_filter(array_map('trim', explode(',', (string)$user->twoFactorMethod)));
			$methodToRemove = trim((string)$method);
			$methods = array_values(array_filter($methods, function ($m) use ($methodToRemove) {
				return strcasecmp($m, $methodToRemove) !== 0;
			}));

			$user->twoFactorStatus = 0;
			$user->twoFactorMethod = implode(',', $methods);
			$user->update();

			$userCodes = new TwoFactorAuthCode();
			$userCodes->userId = UserAccount::getActiveUserId();
			$userCodes->find();
			while ($userCodes->fetch()) {
				$userCodes->deleteCode($userCodes->code);
			}

			require_once ROOT_DIR . '/sys/TwoFactorAuthTOTPSecret.php';
			TwoFactorAuthTOTPSecret::deleteUserSecrets(UserAccount::getActiveUserId());
		}
	}

	function canActiveUserEdit() : bool {
		return false;
	}
}
