<?php

require_once 'bootstrap.php';
require_once ROOT_DIR . '/services/Authentication/vendor/autoload.php';

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';

require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';

class OAuthAuthentication extends DataObject
{
	/**
	 * @throws UnknownAuthenticationMethodException
	 */
	public function verifyIdToken($payload): array
	{
		$success = false;
		$error = '';
		$message = '';
		$returnTo = '';

		global $library;
		global $logger;

		if (isset($payload['code'])) {
			$id_token = $payload['code'];
			$ssoSettings = new SSOSetting();
			$ssoSettings->id = $library->ssoSettingId;
			$ssoSettings->service = "oauth";
			if ($ssoSettings->find(true)) {
				$gateway = $ssoSettings->oAuthGateway;
				$provider = require_once ROOT_DIR . '/sys/Authentication/OAuthProvider.php';
				$token = $provider->getAccessToken('authorization_code', [
					'code' => $payload['code']
				]);
				$user = $provider->getResourceOwner($token);
				if ($user) {
					$account = $this->validateAccount($user);
					if ($account) {
						$success = true;
						$message = 'Login successful.';
						$returnTo = '/MyAccount/Home';
					} else {
						$error = 'Unable to match provided credentials within the system.';
					}
				} else {
					$error = 'Unable to verify id token OAuth Client';
				}
			} else {
				$error = 'oAuth is not setup for library.';
			}
		} else {
			$error = 'No data from OAuth provided, unable to log into system.';
		}

		return [
			'success' => $success,
			'message' => $success ? $message : $error,
			'returnTo' => $returnTo
		];
	}

	private function validateAccount($response): bool
	{
		global $logger;
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();

		$resourceOwner = [
			'id' => $response->getId(),
			'email' => $response->getEmail(),
			'firstname' => $response->getFirstName(),
			'lastname' => $response->getLastName(),
		];

		$user = $catalogConnection->findNewUser($resourceOwner['id']);

		if (!$user instanceof User) {
			$selfReg = $catalogConnection->selfRegister();
			if ($selfReg['success'] != '1') {
				//unable to register the user
				$logger->log("Error self registering user " . $resourceOwner['id'], Logger::LOG_ERROR);
				return false;
			}
		}

		$user->email = $resourceOwner['email'];
		$user->firstname = $resourceOwner['firstname'];
		$user->lastname = $resourceOwner['lastname'];
		$user->updatePatronInfo(true);

		$user = $catalogConnection->findNewUser($resourceOwner['id']);

		if ($user instanceof User) {
			$_REQUEST['username'] = $resourceOwner['id'];
			$_REQUEST['password'] = $user->password;
			$login = UserAccount::login(true);
			$this->newSSOSession($login->id);
			return true;
		}

		return false;
	}

	private function newSSOSession($id)
	{
		global $configArray;
		global $timer;
		$session_type = $configArray['Session']['type'];
		$session_lifetime = $configArray['Session']['lifetime'];
		$session_rememberMeLifetime = $configArray['Session']['rememberMeLifetime'];
		$sessionClass = ROOT_DIR . '/sys/Session/' . $session_type . '.php';
		require_once $sessionClass;

		if (class_exists($session_type)) {
			session_destroy();
			session_name('aspen_session'); // must also be set in index.php, in initializeSession()
			/** @var SessionInterface $session */
			$session = new $session_type();
			$session->init($session_lifetime, $session_rememberMeLifetime);
		}

		$_SESSION['activeUserId'] = $id;
		$_SESSION['rememberMe'] = false;
		$_SESSION['loggedInViaSSO'] = true;
	}

	private function getResponseValue($key)
	{
		return $this->resourceOwner[$key] ?? null;
	}

	private function setMatchpoints()
	{
		global $library;
		$settings = new SSOSetting();
		$settings->id = $library->ssoSettingId;
		$settings->service = "oauth";
		if ($settings->find(true)) {
			$mappings = new SSOMapping();
			$mappings->ssoSettingId = $settings->id;
			$mappings->find();
			while ($mappings->fetch()) {
				if ($mappings->aspenField == "email") {
					$this->matchpoint_email = $mappings->responseField;
				} elseif ($mappings->aspenField == "user_id") {
					$this->matchpoint_id = $mappings->responseField;
				} elseif ($mappings->aspenField == "first_name") {
					$this->matchpoint_firstname = $mappings->responseField;
				} elseif ($mappings->aspenField == "last_name") {
					$this->matchpoint_lastname = $mappings->responseField;
				}
			}
		}
	}

}