<?php

abstract class AbstractAPI extends Action{
	protected $context;
	protected string $apiName = '';
	protected $authorizedUser = false;
	protected string $authorizedScope = '';
	
	function __construct($context = 'external') {
		parent::__construct(false);
		$this->context = $context;
		if ($this->checkIfLiDA()) {
			$this->context = 'lida';
		}
		if (empty($this->apiName)) {
			$this->apiName = (new ReflectionClass($this))->getShortName();
		}
	}

	function checkIfLiDA(): bool {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'User-Agent' || $name == 'user-agent') {
					if (str_contains($value, "Aspen LiDA")) {
						return true;
					}
				}
			}
		}
		return false;
	}

	function getLiDAVersion() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'version' || $name == 'Version') {
					$version = explode(' ', $value);
					$version = substr($version[0], 1); // remove starting 'v'
					return floatval($version);
				}
			}
		}
		return 0;
	}

	function getLiDASession() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'LiDA-SessionID' || $name == 'lida-sessionid') {
					$sessionId = explode(' ', $value);
					return $sessionId[0];
				}
			}
		}
		return false;
	}

	function getLiDASlug() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if (strcasecmp($name, 'lida-slug') === 0) {
					return $value;
				}
			}
		}
		return false;
	}

	function getLiDAUserAgent() {
		if (function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if ($name == 'User-Agent' || $name == 'user-agent') {
					if (str_contains($value, 'Aspen LiDA') || str_contains($value, 'aspen lida')) {
						return true;
					}
				}
			}
		}
		return false;
	}

	protected function extractBasicAuthCredentials(): void {
		if (!isset($_SERVER['PHP_AUTH_USER']) && (isset($_SERVER['HTTP_AUTHORIZATION']) || isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))) {
			$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
			if (preg_match('/^Basic\s+(.*)$/i', $authHeader, $matches)) {
				$credentials = base64_decode($matches[1]);
				if (strpos($credentials, ':') !== false) {
					list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $credentials, 2);
				}
			}
		}
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	function loadUsernameAndPassword() {
		$username = $_REQUEST['username'] ?? '';
		$password = $_REQUEST['password'] ?? '';

		if (isset($_POST['username']) && isset($_POST['password'])) {
			$username = $_POST['username'];
			$password = $_POST['password'];
		}

		if (is_array($username)) {
			$username = reset($username);
		}
		if (is_array($password)) {
			$password = reset($password);
		}
		return [$username, $password];
	}

	/**
	 * @return bool|User
	 */
	function getUserForApiCall() {
		$this->extractBasicAuthCredentials();

		global $oAuthUser;
		if (isset($oAuthUser) && $oAuthUser !== false) {
			// Note: 'Use All API Endpoints' permission is checked in grantTokenAccess()

			if (empty($_REQUEST['language'])) {
				global $activeLanguage;
				global $translator;
				$userLanguage = new Language();
				$userLanguage->code = $oAuthUser->interfaceLanguage;
				if ($userLanguage->find(true)) {
					if ($userLanguage->code != $activeLanguage->code) {
						$activeLanguage = $userLanguage;
						$translator = new Translator('lang', $userLanguage->code);
					}
				}
			}

			return $oAuthUser;
		}

		$user = false;
		[$username, $password] = $this->loadUsernameAndPassword();
		$user = UserAccount::validateAccount($username, $password);
		if ($user !== false && $user->source == 'admin') {
			//Admin users are not allowed with API calls
			return false;
		}

		//Set translations up based on the active user's desired language
		if (empty($_REQUEST['language']) && $user !== false) {
			global $activeLanguage;
			global $translator;
			$userLanguage = new Language();
			$userLanguage->code = $user->interfaceLanguage;
			if ($userLanguage->find(true)) {
				if ($userLanguage->code != $activeLanguage->code) {
					$activeLanguage = $userLanguage;
					$translator = new Translator('lang', $userLanguage->code);
				}
			}
		}

		return $user;
	}

	protected function setActiveLanguage(): void {
		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}
	}

	/**
	 * Get authentication info for OpenAPI authorization
	 * @return array ['user' => User|false, 'greenhouseAuth' => bool]
	 */
	protected function getAuthInfoForOpenAPI(): array {
		global $oAuthUser;
		
		// Check if already authenticated via OAuth
		if (isset($oAuthUser) && $oAuthUser !== false) {
			return ['user' => $oAuthUser, 'greenhouseAuth' => false];
		}
		
		// Try to authenticate via Basic auth
		// Use validateTokenCredentials() - OpenAPI authorizer handles permissions
		if (isset($_SERVER['PHP_AUTH_USER'])) {
			if ($this->validateTokenCredentials()) {
				global $oAuthUser;
				if (isset($oAuthUser) && $oAuthUser !== false) {
					// OAuth authentication succeeded - we have a user
					return ['user' => $oAuthUser, 'greenhouseAuth' => false];
				} else {
					// Greenhouse/LiDA keys - authorized but no user
					return ['user' => false, 'greenhouseAuth' => true];
				}
			}
		}
		
		return ['user' => false, 'greenhouseAuth' => false];
	}
	
	/**
	 * @deprecated Use getAuthInfoForOpenAPI() instead
	 */
	protected function getAuthenticatedUserForOpenAPI() {
		$authInfo = $this->getAuthInfoForOpenAPI();
		return $authInfo['user'];
	}

	protected function sendErrorResponse(string $error, int $code, ?string $message = null): void {
		http_response_code($code);
		header('Cache-Control: no-cache, must-revalidate');
		
		$response = ['error' => $error];
		if ($message !== null) {
			$response['message'] = $message;
		}
		
		$output = json_encode($response);
		
		$method = $_GET['method'] ?? 'unknown';
		ExternalRequestLogEntry::logRequest(
			$this->apiName . '.' . $method,
			$_SERVER['REQUEST_METHOD'],
			$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			getallheaders(),
			'',
			$code,
			$output,
			[]
		);
		
		echo $output;
	}

	protected function executeAPIMethod(string $method): void {
		require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
		APIUsage::incrementStat($this->apiName, $method);
		
		$output = json_encode(['result' => $this->$method()]);
		
		ExternalRequestLogEntry::logRequest(
			$this->apiName . '.' . $method,
			$_SERVER['REQUEST_METHOD'],
			$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			getallheaders(),
			'',
			$_SERVER['REDIRECT_STATUS'] ?? 200,
			$output,
			[]
		);
		
		echo $output;
	}

	protected function launchWithOpenAPI(): void {
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		
		header('Content-type: application/json');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		
		$this->setActiveLanguage();
		$this->extractOAuthCredentials();
		$this->extractBasicAuthCredentials();
		
		$authInfo = $this->getAuthInfoForOpenAPI();
		$user = $authInfo['user'];
		$greenhouseAuth = $authInfo['greenhouseAuth'];
		$ipAllowed = IPAddress::allowAPIAccessForClientIP();
		
		require_once ROOT_DIR . '/sys/API/OpenAPIAuthorizer.php';
		$authResult = OpenAPIAuthorizer::authorize($this->apiName, $method, $user, $ipAllowed, $greenhouseAuth);
		
		if (!$authResult['allowed']) {
			$this->sendErrorResponse($authResult['error'], $authResult['code'], $authResult['message'] ?? null);
			return;
		}
		
		$this->authorizedUser = $user;
		$this->authorizedScope = $authResult['scope'] ?? 'user';
		
		if (!method_exists($this, $method)) {
			$this->sendErrorResponse('method_not_implemented', 501, "Method '$method' is defined but not implemented");
			return;
		}
		
		$this->executeAPIMethod($method);
	}

	protected function getAuthorizedUser() {
		return $this->authorizedUser;
	}

	protected function getAuthorizedScope(): string {
		return $this->authorizedScope;
	}

	protected function isSuperuserScope(): bool {
		return $this->authorizedScope === 'superuser';
	}
}