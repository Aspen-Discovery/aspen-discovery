<?php


class RecaptchaSetting extends DataObject
{
	public $__table = 'recaptcha_settings';
	public $id;
	public $publicKey;
	public $privateKey;

	public static function getObjectStructure() : array
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'publicKey' => array('property' => 'publicKey', 'type' => 'text', 'label' => 'Public Key', 'description' => 'The Public Recaptcha Key'),
			'privateKey' => array('property' => 'privateKey', 'type' => 'storedPassword', 'label' => 'Private Key', 'description' => 'The Private Recaptcha Key', 'hideInLists' => true),
		];
	}

	public static function validateRecaptcha()
	{
		$recaptcha = new RecaptchaSetting();
		if ($recaptcha->find(true) && !empty($recaptcha->publicKey)){
			$resp = recaptcha_check_answer ($recaptcha->privateKey,
				$_SERVER["REMOTE_ADDR"],
				$_POST["g-recaptcha-response"]);
			$recaptchaValid = $resp->is_valid;
		}else{
			$recaptchaValid = true;
		}
		return $recaptchaValid;
	}
}