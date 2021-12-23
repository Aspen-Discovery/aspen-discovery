<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class TwoFactorAuthCode extends DataObject
{
	public $__table = 'two_factor_auth_codes';   // table name

	public $id;
	public $userId;
	public $sessionId;
	public $code;
	public $dateSent;
	public $status;

	public static function getObjectStructure() {
		return [
			'id' => ['property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'],
			'userId' => ['property' => 'userId', 'type' => 'text', 'label' => 'User', 'description' => 'The user who has requested a code', 'readOnly' => true],
			'sessionId' => ['property' => 'sessionId', 'type' => 'text', 'label' => 'Session', 'description' => 'The session that has been authenticated', 'readOnly' => true],
			'code' => ['property' => 'code', 'type' => 'text', 'label' => 'Code', 'description' => 'The code used for authentication', 'readOnly' => true],
			'dateSent' => ['property' => 'dateSent', 'type' => 'date', 'label' => 'Date Send/Created', 'description' => 'The date the code was created', 'readOnly' => true],
			'status' => ['property' => 'status', 'type' => 'text', 'label' => 'Status', 'description' => 'The status of the code', 'readOnly' => true],
		];
	}

	public function createCode($num = 1, $backup = false) {
		for($i=1; $i<=$num; $i++){
			$twoFactorAuthCode = new TwoFactorAuthCode();
			$twoFactorAuthCode->code = mt_rand(100000,999999);
			$twoFactorAuthCode->userId = UserAccount::getActiveUserId();
			$twoFactorAuthCode->dateSent = time();
			if($backup) {
				$twoFactorAuthCode->status = "backup";
			} else {
				$twoFactorAuthCode->status = "created";
			}
			$twoFactorAuthCode->insert();

			if(!$backup) {
				$this->sendCode();
			}
		}
		return true;
	}

	function sendCode() {
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mail = new Mailer();
		$replyToAddress = "";
		$body = "*****This is an auto-generated email response. Please do not reply.*****";
		$body .= "\r\n\r\n" . "Your code to login is " . $this->code;
		$body .= "\r\n\r\n" . "This code is only valid for the next 15 minutes.";

		$patron = new User();
		$patron->id = $this->userId;
		if($patron->find(true)) {
			if($patron->email) {
				$email = $mail->send($patron->email, translate(['text'=>"Your one-time login code",'isPublicFacing'=>true]), $body, $replyToAddress);
				$this->status = "sent";
				$this->update();
				return true;
			} else {
				// no email setup, probably won't happen?
				return false;
			}
		} else {
			// patron not found
			return false;
		}
	}

	function validateCode($code) {
		$codeToCheck = new TwoFactorAuthCode();
		$codeToCheck->code = $code;
		if($codeToCheck->find(true)) {
			if($codeToCheck->userId == UserAccount::getActiveUserId()){
				if($codeToCheck->status != "used" || $codeToCheck->status != "expired") {
					$codeToCheck->status = "used";
					$codeToCheck->sessionId = session_id();
					$codeToCheck->update();
					$result = array(
						'success' => 'true',
						'message' => 'Code OK'
					);
				} else {
					$result = array(
						'success' => 'false',
						'message' => 'You have already used this code'
					);
				}
			} else {
				// code belongs to another user
				$result = array(
					'success' => 'false',
					'message' => 'Sorry, this code is invalid'
				);
			}
		} else {
			// code not found
			$result = array(
				'success' => 'false',
				'message' => 'Sorry, this code is invalid'
			);
		}

		return $result;
	}

	function createNewBackups() {
		$oldBackupCodes = new TwoFactorAuthCode();
		$oldBackupCodes->userId = UserAccount::getActiveUserId();
		$oldBackupCodes->status = "backup";
		$oldBackupCodes->find();
		while($oldBackupCodes->fetch()) {
			$this->deleteCode($oldBackupCodes->code);
		}

		$this->createCode(5, true);
	}

	function getBackups() {
		$backupCodes = [];
		$backupCode = new TwoFactorAuthCode();
		$backupCode->userId = UserAccount::getActiveUserId();
		$backupCode->status = "backup";
		$backupCode->find();
		while($backupCode->fetch()) {
			$backupCodes[] = $backupCode->code;
		}
		return $backupCodes;
	}

	function deleteCode($code) {
		$codeToCheck = new TwoFactorAuthCode();
		$codeToCheck->code = $code;
		if($codeToCheck->find(true)) {
			$codeToCheck->delete();
			return true;
		}
		return false;
	}

	function deactivate2FA() {

		$user = new User();
		$user->id = UserAccount::getActiveUserId();
		if($user->find(true)){
			$user->twoFactorStatus = 0;
			$user->update();

			$userCodes = new TwoFactorAuthCode();
			$userCodes->userId = UserAccount::getActiveUserId();
			$userCodes->find();
			while($userCodes->fetch()) {
				$userCodes->deleteCode($userCodes->code);
			}
		}
	}

	function canActiveUserEdit(){
		return false;
	}
}
