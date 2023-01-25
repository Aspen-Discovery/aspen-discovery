<?php


class webhooks_ExpoEASBuild extends Action {
	public function launch() {
		global $logger;
		$success = false;
		$message = '';
		$error = '';
		$logger->log('Completing Expo EAS Build Webhook request...', Logger::LOG_ERROR);

		if($payload = $this->isValidRequest()) {
			$payload = json_decode($payload, true);
			$logger->log(print_r($payload, true), Logger::LOG_ERROR);
			require_once ROOT_DIR . '/sys/Greenhouse/AspenLiDABuild.php';
			$build = new AspenLiDABuild();
			$build->buildId = $payload['id'];
			$build->status = $payload['status'];
			$build->appId = $payload['appId'];
			$build->platform = $payload['platform'];

			$build->createdAt = $payload['createdAt'];
			$build->completedAt = $payload['completedAt'];
			$build->updatedAt = $payload['updatedAt'];

			$build->name = $payload['metadata']['appName'];
			$build->version = $payload['metadata']['appVersion'];
			$build->buildVersion = $payload['metadata']['appBuildVersion'];
			$build->gitCommitHash = $payload['metadata']['gitCommitHash'];
			$build->storeIdentifier = $payload['metadata']['appIdentifier'];

			if($payload['status'] == 'finished') {
				if($payload['metadata']['channel'] == 'production' || $payload['metadata']['channel'] == 'beta' || $payload['metadata']['channel'] == 'store') {
					if ($build->platform == 'android') {
						$build->storeUrl = 'https://play.google.com/store/apps/details?id=' . $build->storeIdentifier;
					} elseif ($build->platform == 'ios') {
						$build->storeUrl = 'https://apps.apple.com/us/app/id' . $build->storeIdentifier;
					}
				}

				if($build->channel == 'alpha') {
					$build->storeUrl = 'https://play.google.com/store/apps/details?id=' . $build->storeIdentifier;
				}

				if(isset($payload['artifacts'])) {
					$build->artifact = $payload['artifacts']['buildUrl'];
				}
			}

			if(isset($payload['metadata']['channel'])) {
				$build->channel = $payload['metadata']['channel'];
			} else {
				$build->channel = $payload['metadata']['buildProfile'];
			}

			if(isset($payload['metadata']['message'])) {
				$build->buildMessage = $payload['metadata']['message'];
				$build->isEASUpdate = 1;
			}

			if($payload['status'] == 'errored') {
				$build->error = 1;
				$build->errorMessage = $payload['error']['errorCode'] . ": " . $payload['error']['message'];
			}

			if($build->insert()) {
				$success = true;
				$message = 'Build data successfully saved.';
				$this->sendSlackAlert($build);
			} else {
				$error = 'Unable to insert build data.';
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
		$logger->log('Validating Expo EAS Build Webhook request...', Logger::LOG_ERROR);
		require_once ROOT_DIR . '/sys/Greenhouse/GreenhouseSettings.php';
		$greenhouseSettings = new GreenhouseSettings();
		$expoEASBuildWebhook = null;
		$hash = null;
		$payload = [];
		if($greenhouseSettings->find(true)) {
			$expoEASBuildWebhook = $greenhouseSettings->expoEASBuildWebhookKey;
			$payload = file_get_contents('php://input');
			$hash = hash_hmac('sha1', $payload, $expoEASBuildWebhook);
			$hash = 'sha1=' . $hash;
			$logger->log("Stored key: " . $hash, Logger::LOG_ERROR);
		}

		if($expoEASBuildWebhook && $hash) {
			foreach (getallheaders() as $name => $value) {
				if($name == 'Expo-Signature' || $name == 'expo-signature') {
					$logger->log($value, Logger::LOG_ERROR);
					if(hash_equals($hash, $value)) {
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
		if($build) {
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
				$buildTracker = $configArray['Site']['url'] . '/Greenhouse/AspenLiDABuildTracker/';
				$notification = "- <$buildTracker|Build completed> for $build->platform for version $build->version b[$build->buildVersion] p[$build->patch] c[$build->channel]";
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