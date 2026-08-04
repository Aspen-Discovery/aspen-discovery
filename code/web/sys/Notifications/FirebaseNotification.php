<?php
require_once ROOT_DIR . '/sys/Account/UserNotification.php';
require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/sys/AspenPWA/Setting.php';
class FirebaseNotification extends DataObject {

	public function sendPushNotification($message, $deviceToken, $userId, $notificationType) : void {
		$response = $this->sendFCMessage($message['title'], $message['body'], $deviceToken);
		// $notification = new UserNotification();
		// $notification->userId = $userId;
		// $notification->pushToken = $pushToken;
		// $notification->notificationType = $notificationType;
		// $notification->notificationDate = time();
		// $notification->completed = 0;
		// $notification->error = 0;
		// TODO error handling
	}
	public function sendTestPushNotification($title, $body, $deviceToken) : array
	{
		return $this->sendFCMessage($title, $body, $deviceToken);
	}

	public function getNotificationReceipt($reciept) : void
	{
		die("getNotificationReceipt not implemented yet");
	}

	public function getTestPushNotificationReceipt($reciept) : array
	{
		die("getTestPushNotificationReceipt not implemented yet");
		return [];
	}

	function generateJWT($serviceAccount) {
		$header = json_encode([
			'alg' => 'RS256',
			'typ' => 'JWT'
		]);

		$now = time();
		$payload = json_encode([
			'iss' => $serviceAccount['client_email'],
			'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
			'aud' => 'https://oauth2.googleapis.com/token',
			'exp' => $now + 3600,
			'iat' => $now
		]);

		$base64UrlHeader = str_replace(['+', '/', '='], ['-','_', ''], base64_encode($header));
		$base64UrlPayload = str_replace(['+', '/', '='], ['-','_', ''], base64_encode($payload));

		$signature = '';
		$signed = openssl_sign(
			$base64UrlHeader . '.' . $base64UrlPayload,
			$signature,
			$serviceAccount['private_key'],
			OPENSSL_ALGO_SHA256
		);

		$base64UrlSignature = str_replace(['+', '/', '='], ['-','_', ''], base64_encode($signature));

		return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
	}

	function getAccessToken($serviceAccount)
	{
		$jwt = $this->generateJWT($serviceAccount);
		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => 'https://oauth2.googleapis.com/token',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query([
				'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				'assertion' => $jwt
			]),
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/x-www-form-urlencoded'
			]
		]);
		$response = curl_exec($ch);
		$httpCode = curl_getInfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);


		if ($httpCode !== 200) {
			throw new Exception('Failed to get access token: ' . $response);
		}

		$data = json_decode($response, true);
		return $data['access_token'];
	}

	function sendFCMessage($title, $body, $deviceToken)
	{
		$settings = AspenPWASetting::getSettingsForCurrentLibrary();
		if($settings)
		{
			$serviceAccount = $settings->getServiceAccount();
			//get serviceAccount from settings then use that to get account token
			$accessToken = $this->getAccessToken($serviceAccount);
			//get projectId from setting
			$projectId = $settings->firebaseProjectID;
			$url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
			$message = [
				'message' => [
					'token' => $deviceToken,
					'notification' => [
						"title" => $title,
						"body" => $body
					]
				]
			];

			$ch = curl_init();
			$headers = [
					'Authorization: Bearer ' . $accessToken,
					'Content-Type: application/json'
			];
			curl_setopt_array($ch, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode($message),
				CURLOPT_HTTPHEADER => $headers
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			ExternalRequestLogEntry::logRequest('FirebasePushNotification.Send', 'POST', $url, $headers, json_encode($body), $httpCode, $response, []);

			return json_decode($response, true);
		} else {
			// If we don't have settings that logging is handled inside
			// getSettingsForCurrentLibrary
			return [];
		}
	}
}
