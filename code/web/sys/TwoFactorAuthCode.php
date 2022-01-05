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
				$twoFactorAuthCode->sendCode();
			}
		}
		return true;
	}

	public function createRecoveryCode($username) {
		$user = new User();
		$user->cat_username = $username;
		if($user->find(true)){
			if($user->twoFactorStatus == '1') {
				$twoFactorAuthCode = new TwoFactorAuthCode();
				$twoFactorAuthCode->code = mt_rand(100000,999999);
				$twoFactorAuthCode->userId = $user->id;
				$twoFactorAuthCode->dateSent = time();
				$twoFactorAuthCode->status = "recovery";
				$twoFactorAuthCode->insert();
				$result = array(
					'success' => true,
					'message' => translate(['text' => 'Recovery code: ' . $twoFactorAuthCode->code, 'isAdminFacing' => true])
				);
			} else {
				$result = array(
					'success' => false,
					'message' => translate(['text' => 'User not setup for two-factor authentication', 'isAdminFacing' => true])
				);
			}
		} else {
			$result = array(
				'success' => false,
				'message' => translate(['text' => 'User not found', 'isAdminFacing' => true])
			);
		}
		return $result;
	}

	function sendCode() {
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mail = new Mailer();
		$replyToAddress = "";
		$body = "*****". translate(['text' => 'This is an auto-generated email response. Please do not reply.', 'isPublicFacing' => true]) ."*****";
		$body .= "\r\n\r\n" . translate(['text' => 'Your code to login is', 'isPublicFacing' => true]) . " " . $this->code;
		$body .= "\r\n\r\n" . translate(['text' => 'This code is only valid for the next 15 minutes.', 'isPublicFacing' => true]);

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
		global $library;
		require_once ROOT_DIR . '/sys/TwoFactorAuthSetting.php';
		$authSetting = new TwoFactorAuthSetting();
		$authSetting->id = $library->twoFactorAuthSettingId;
		if($authSetting->find(true)) {
			$deniedMessage = $authSetting->deniedMessage;
		} else {
			$deniedMessage = "";
		}

		//TODO: Set anything that is sent and dateSent is > 15 min to expired

		//TODO: Delete anything where expired or used AND dateSent is > 60 min ago

		$codeToCheck = new TwoFactorAuthCode();
		$codeToCheck->code = $code;
		if($codeToCheck->find(true)) {
			if($codeToCheck->userId == UserAccount::getActiveUserId()){
				if($codeToCheck->status != "used" && $codeToCheck->status != "expired") {
					$codeToCheck->status = "used";
					$codeToCheck->sessionId = session_id();
					$codeToCheck->update();
					$result = array(
						'success' => 'true',
						'message' => translate(['text' => 'Code OK', 'isPublicFacing' => true])
					);
				} elseif($codeToCheck->status == "expired") {
					$result = array(
						'success' => 'false',
						'message' => translate(['text' => 'This code has expired. ' . $deniedMessage, 'isPublicFacing' => true]),
					);
				} else {
					$result = array(
						'success' => 'false',
						'message' => translate(['text' => 'You have already used this code. ' . $deniedMessage, 'isPublicFacing' => true]),
					);
				}
			} else {
				// code belongs to another user
				$result = array(
					'success' => 'false',
					'message' => translate(['text' => 'Sorry, this code is invalid. ' . $deniedMessage, 'isPublicFacing' => true]),
				);
			}
		} else {
			// code not found
			$result = array(
				'success' => 'false',
				'message' => translate(['text' => 'Sorry, this code is invalid. ' . $deniedMessage, 'isPublicFacing' => true]),
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
