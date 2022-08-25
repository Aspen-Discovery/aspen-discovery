<?php /** @noinspection PhpUnused */
require_once ROOT_DIR . '/sys/Account/UserNotification.php';
require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
require_once ROOT_DIR . '/sys/CurlWrapper.php';

class ExpoNotification extends DataObject
{
	/** @var CurlWrapper */
	private $expoCurlWrapper;

	public function sendExpoPushNotification($body, $pushToken, $userId, $notificationType){
		//https://docs.expo.dev/push-notifications/sending-notifications
		$bearerAuthToken = $this->getNotificationAccessToken();
		$url = "https://exp.host/--/api/v2/push/send";
		$this->expoCurlWrapper = new CurlWrapper();
		$headers = array(
			'Host: exp.host',
			'Accept: application/json',
			'Accept-Encoding: gzip, deflate',
			'Content-Type: application/json',
			'Authorization: Bearer ' . $bearerAuthToken
		);
		$this->expoCurlWrapper->addCustomHeaders($headers, true);
		$response = $this->expoCurlWrapper->curlPostPage($url, json_encode($body));
		if ($this->expoCurlWrapper->getResponseCode() == 200) {
			$json = json_decode($response, true);
			$data = $json['data'];
			$notification = new UserNotification();
			$notification->userId = $userId;
			$notification->pushToken = $pushToken;
			$notification->notificationType = $notificationType;
			$notification->notificationDate = time();
			$notification->completed = 0;
			$notification->error = 0;
			if($data['id']) {
				$notification->receiptId = $data['id'];
			}
			if(array_key_exists('errors', $json)) {
				$error = $json['errors'][0];
				$notification->error = 1;
				$notification->message = $error['code'] . ": " . $error['message'];
			}
			$notification->insert();
		} else {
			global $logger;
			$logger->log('Error sending notification via Expo ' . $this->expoCurlWrapper->getResponseCode() . ' ' . $response, Logger::LOG_ERROR);
		}
	}

	public function getExpoNotificationReceipt($receiptId){
		//https://docs.expo.dev/push-notifications/sending-notifications/#push-receipt-errors
		$bearerAuthToken = $this->getNotificationAccessToken();
		$url = "https://exp.host/--/api/v2/push/getReceipts";
		$this->expoCurlWrapper = new CurlWrapper();
		$headers = array(
			'Host: exp.host',
			'Accept: application/json',
			'Accept-Encoding: gzip, deflate',
			'Content-Type: application/json',
			'Authorization: Bearer ' . $bearerAuthToken
		);
		$this->expoCurlWrapper->addCustomHeaders($headers, true);
		$body = ['ids' => [$receiptId]];
		$response = $this->expoCurlWrapper->curlPostPage($url, json_encode($body));
		if($this->expoCurlWrapper->getResponseCode() == 200) {
			$json = json_decode($response, true);
			$data = $json['data'];
			$notification = new UserNotification();
			$notification->receiptId = $receiptId;
			if($notification->find(true)) {
				if(!array_key_exists('errors', $data)) {
					$notification->completed = 1;
				} else {
					$error = $json['errors'][0];
					$notification->error = 1;
					$notification->message = $error['code'] . ": " . $error['message'];
					if($error['code'] == "DeviceNotRegistered") {
						UserNotificationToken::deleteToken($notification->pushToken);
					}
				}
				$notification->update();
			}
		} else {
			global $logger;
			$logger->log('Error fetching notification receipt via Expo ' . $this->expoCurlWrapper->getResponseCode() . ' ' . $response, Logger::LOG_ERROR);
		}
	}

	public function getNotificationAccessToken() {
		$token = null;
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables && !empty($systemVariables->greenhouseUrl)) {
			if ($result = file_get_contents($systemVariables->greenhouseUrl . '/API/GreenhouseAPI?method=getNotificationAccessToken')) {
				$data = json_decode($result, true);
				$token = $data['token'];
			}
		} else {
			global $configArray;
			if ($result = file_get_contents($configArray['Site']['url'] . '/API/GreenhouseAPI?method=getNotificationAccessToken')) {
				$data = json_decode($result, true);
				$token = $data['token'];
			}
		}
		return $token;
	}
}