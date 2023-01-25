<?php


class webhooks_ExpoEASSubmit extends Action {
	public function launch() {
		global $logger;
		$success = false;
		$message = '';
		$error = '';
		$logger->log('Completing Expo EAS Submit Webhook request...', Logger::LOG_ERROR);

		if($payload = $this->isValidRequest()) {
			$payload = json_decode($payload, true);
			$logger->log(print_r($payload, true), Logger::LOG_ERROR);
			if($payload['status'] != 'canceled') {
				require_once ROOT_DIR . '/sys/Greenhouse/AspenLiDABuild.php';
				$build = new AspenLiDABuild();
				$build->buildId = $payload['turtleBuildId'];
				$build->appId = $payload['appId'];
				$build->platform = $payload['platform'];
				if ($build->find(true)) {
					if ($payload['status'] == 'errored') {
						$build->error = 1;
						$build->errorMessage = $payload['submissionInfo']['error']['errorCode'] . ': ' . $payload['submissionInfo']['error']['message'];
					} else {
						$build->isSubmitted = 1;
					}
					if ($build->update() && $build->isSubmitted) {
						$success = true;
						$message = 'Build data successfully updated.';
						$this->sendSlackAlert($build);
					} else {
						$error = 'Unable to update build data.';
					}
				} else {
					$logger->log('Unable to find existing build.', Logger::LOG_ERROR);
				}
			}
			$logger->log('Finished processing webhook request.', Logger::LOG_ERROR);
		} else {
			$logger->log('Unable to validate request!', Logger::LOG_ERROR);
			$logger->log(print_r(getallheaders(), true), Logger::LOG_ERROR);
		}

		$result = [
			'success' => $success,
			'message' => $success ? $message : $error,
		];


		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		echo json_encode($result);
		die();
	}

	function isValidRequest(): bool|array|string {
		global $logger;
		$logger->log('Validating Expo EAS Submit Webhook request...', Logger::LOG_ERROR);
		require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
		$greenhouseSettings = new GreenhouseSettings();
		$expoEASSubmitWebhook = null;
		$hash = null;
		$payload = [];
		if ($greenhouseSettings->find(true)) {
			$expoEASSubmitWebhook = $greenhouseSettings->expoEASSubmitWebhookKey;
			$payload = file_get_contents('php://input');
			$hash = hash_hmac('sha1', $payload, $expoEASSubmitWebhook);
			$hash = 'sha1=' . $hash;
			$logger->log('Stored key: ' . $hash, Logger::LOG_ERROR);
		}

		if ($expoEASSubmitWebhook && $hash) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'Expo-Signature' || $name == 'expo-signature') {
					$logger->log($value, Logger::LOG_ERROR);
					if (hash_equals($hash, $value)) {
						$logger->log('Keys match. Request validated.', Logger::LOG_ERROR);
						return $payload;
					} else {
						$logger->log('Invalid request. Keys do not match.', Logger::LOG_ERROR);
					}
				}
			}
		} else {
			$logger->log('A webhook key was not setup in settings', Logger::LOG_ERROR);
		}

		return false;
	}

	function sendSlackAlert($build): bool {
		if ($build) {
			require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
			$greenhouseSettings = new GreenhouseSettings();
			$greenhouseAlertSlackHook = null;
			$shouldSendBuildAlert = false;
			if ($greenhouseSettings->find(true)) {
				$greenhouseAlertSlackHook = $greenhouseSettings->greenhouseAlertSlackHook;
				$shouldSendBuildAlert = $greenhouseSettings->sendBuildTrackerAlert;
			}

			if ($greenhouseAlertSlackHook && $shouldSendBuildAlert) {
				global $configArray;
				if($build->platform == 'android') {
					$storeName = "Google Play Store";
				} else {
					$storeName = "Apple App Store";
				}
				$buildTracker = $configArray['Site']['url'] . '/Greenhouse/AspenLiDABuildTracker/';
				$patchNum = $build->patch ?? "0";
				$notification = "- <$buildTracker|Build submitted to $storeName> for version $build->version b[$build->buildVersion] p[$patchNum] c[$build->channel]";
				$alertText = "*$build->name* $notification\n";
				$curlWrapper = new CurlWrapper();
				$headers = [
					'Accept: application/json',
					'Content-Type: application/json',
				];
				$curlWrapper->addCustomHeaders($headers, false);
				$body = new stdClass();
				$body->text = $alertText;
				$curlWrapper->curlPostPage($greenhouseAlertSlackHook, json_encode($body));
				return true;
			}
		}
		return false;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}