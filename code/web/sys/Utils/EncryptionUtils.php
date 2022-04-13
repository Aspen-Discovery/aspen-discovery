<?php


class EncryptionUtils
{
	public static function encryptField($fieldData){
		$key = EncryptionUtils::loadKey();
		if ($key == false){
			return $fieldData;
		}else{
			if (empty($fieldData)){
				return $fieldData;
			}else {
				$initializationVector = openssl_random_pseudo_bytes(openssl_cipher_iv_length($key['cipher']));
				$encryptedTextRaw = openssl_encrypt($fieldData, $key['cipher'], $key['key'], OPENSSL_RAW_DATA, $initializationVector, $tag);
				$hmac = hash_hmac('sha256', $encryptedTextRaw, $key['key'], true);
				return 'AEF~' . base64_encode($initializationVector . $tag . $hmac . $encryptedTextRaw);
			}
		}
	}

	public static function decryptField($fieldData){
		$key = EncryptionUtils::loadKey();
		return EncryptionUtils::doDecryption($fieldData, $key);
	}

	private static function doDecryption($fieldData, $key){
		if ($key == false){
			if (strlen($fieldData) > 4 && substr($fieldData, 0, 4) == 'AEF~'){
				return "Invalid encryption";
			}else{
				return $fieldData;
			}
		}else{
			if (strlen($fieldData) > 4 && substr($fieldData, 0, 4) == 'AEF~'){
				$decodedData = base64_decode(substr($fieldData, 4));
				$initializationVectorLength = openssl_cipher_iv_length($key['cipher']);
				$initializationVector = substr($decodedData, 0, $initializationVectorLength);
				$tag = substr($decodedData, $initializationVectorLength, 16);
				$hmac = substr($decodedData, $initializationVectorLength + 16, 32);
				$rawEncodedData = substr($decodedData, $initializationVectorLength + 32 + 16);
				$decryptedText = openssl_decrypt($rawEncodedData, $key['cipher'], $key['key'], OPENSSL_RAW_DATA, $initializationVector, $tag);
				$calcMac = hash_hmac('sha256', $rawEncodedData, $key['key'], true);
				if (hash_equals($hmac, $calcMac)){
					return $decryptedText;
				}else{
					return false;
				}
			}else{
				//This field is not encoded
				return $fieldData;
			}
		}
	}

	private static $_providedKeys = [];
	public static function decryptFieldWithProvidedKey($fieldData, $key){
		if (!array_key_exists($key, EncryptionUtils::$_providedKeys)){
			list($cipher, $key) = explode(':', $key, 2);
			EncryptionUtils::$_providedKeys[$key] = [
				'cipher' => $cipher,
				'key' => hex2bin($key)
			];
		}
		$keyData =  EncryptionUtils::$_providedKeys[$key];
		return EncryptionUtils::doDecryption($fieldData, $keyData);
	}

	private static $_key = null;
	private static function loadKey(){
		global $memCache;
		if (EncryptionUtils::$_key == null){
			global $serverName;
			$cachedKey = $memCache->get('encryption_key_' . $serverName);
			if ($cachedKey === false) {
				$passkeyFile = ROOT_DIR . "/../../sites/$serverName/conf/passkey";
				if (file_exists($passkeyFile)) {
					$passkeyFhnd = fopen($passkeyFile, 'r');
					$key = trim(fgets($passkeyFhnd));
					fclose($passkeyFhnd);
					if ($key != false) {
						$memCache->set('encryption_key_' . $serverName, $key, 86400);
						list($cipher, $key) = explode(':', $key, 2);
						EncryptionUtils::$_key = [
							'cipher' => $cipher,
							'key' => hex2bin($key)
						];
					} else {
						EncryptionUtils::$_key = false;
					}
				} else {
					EncryptionUtils::$_key = false;
				}
			}else{
				list($cipher, $key) = explode(':', trim($cachedKey), 2);
				EncryptionUtils::$_key = [
					'cipher' => $cipher,
					'key' => hex2bin($key)
				];
			}
		}
		return EncryptionUtils::$_key;
	}
}