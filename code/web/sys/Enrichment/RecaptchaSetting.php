<?php /** @noinspection PhpMissingFieldTypeInspection */


class RecaptchaSetting extends DataObject {
	public $__table = 'recaptcha_settings';
	public $id;
	public $publicKey;
	public $privateKey;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'publicKey' => [
				'property' => 'publicKey',
				'type' => 'text',
				'label' => 'Public Key',
				'description' => 'The Public Recaptcha Key',
			],
			'privateKey' => [
				'property' => 'privateKey',
				'type' => 'storedPassword',
				'label' => 'Private Key',
				'description' => 'The Private Recaptcha Key',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public static function validateRecaptcha() : bool {
		$recaptcha = new RecaptchaSetting();
		if ($recaptcha->find(true) && !empty($recaptcha->publicKey)) {
			$resp = recaptcha_check_answer($recaptcha->privateKey, $_SERVER["REMOTE_ADDR"], $_POST["g-recaptcha-response"]);
			$recaptchaValid = $resp->is_valid;
		} else {
			$recaptchaValid = true;
		}
		return $recaptchaValid;
	}
}