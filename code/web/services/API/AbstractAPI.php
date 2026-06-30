<?php

global $composerActive;
if ($composerActive) {
	require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Middleware.php';
}
require_once ROOT_DIR . '/sys/API/APIMethodConfiguration.php';

abstract class AbstractAPI extends Action{
	use APIMethodConfiguration;
	
	protected string $context;
	function __construct($context = 'external') {
		parent::__construct(false);
		$this->context = $context;
		if ($this->checkIfLiDA()) {
			$this->context = 'lida';
		}
	}

	protected function getHeaders() : array {
		if (function_exists('getallheaders')) {
			return getallheaders();
		} else {
			return [];
		}
	}

	protected function getHeader(string $headerToRetrieve) : ?string {
		$headers = $this->getHeaders();
		foreach ($headers as $name => $value) {
			if (strcasecmp($name, $headerToRetrieve) === 0) {
				return $value;
			}
		}
		return null;
	}

	protected function checkIfLiDA(): bool {
		$userAgent = $this->getHeader('User-Agent');
		if (!is_null($userAgent) && str_contains($userAgent, "Aspen LiDA")) {
			return true;
		}
		return false;
	}

	protected function getLiDAVersion() : float {
		$versionHeader = $this->getHeader('Version');
		if (!is_null($versionHeader)) {
			$version = explode(' ', $versionHeader);
			$version = substr($version[0], 1); // remove starting 'v'
			return floatval($version);
		}
		return 0.0;
	}

	protected function getLiDASession() : string|false {
		$lidaSessionHeader = $this->getHeader('LiDA-SessionID');
		if (!is_null($lidaSessionHeader)) {
			$sessionId = explode(' ', $lidaSessionHeader);
			return $sessionId[0];
		}
		return false;
	}

	protected function getLiDASlug() : string|false {
		$lidaSlugHeader = $this->getHeader('lida-slug');
		if (!is_null($lidaSlugHeader)) {
			return $lidaSlugHeader;
		}
		return false;
	}

	protected function getLiDAUserAgent() : bool {
		$userAgent = $this->getHeader('User-Agent');
		if (!is_null($userAgent) && (str_contains($userAgent, "Aspen LiDA") || str_contains($userAgent, 'aspen lida'))) {
			return true;
		}
		return false;
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	protected function loadUsernameAndPassword() : array {
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

	protected function logPatronRequest($userId): void {
		if ($this->context == 'lida') {
			require_once ROOT_DIR . '/sys/SystemLogging/UserAppRequestLogEntry.php';
			UserAppRequestLogEntry::logRequest($userId, $_GET['action'], $_GET['method'], json_encode($_REQUEST), $this->getLiDAVersion());
		}
	}

	/**
	 * Add debug output if user has enabled App Request Logging in Discovery
	 * This forces responses to be logged into Aspen LiDA instead of just errors
	 * @param mixed $result
	 * @return mixed
	 */
	protected function logPatronRequestExternal(mixed $result): mixed {
		$user = $this->getUserForApiCall();
		if ($user !== false && $user->allowAppRequestLogging) {
			if (is_array($result)) {
				$result['debug'] = true;
			} elseif (is_object($result)) {
				$result->debug = true;
			}
		}
		return $result;
	}

	private $_userForAPICall = null;
	/**
	 * Get user for API call - supports both OAuth2 and traditional authentication
	 * Not for use with direct call
	 *
	 * @oauth false
	 * @token false
	 * @public false
	 *
	 * @return bool|User|null
	 */
	protected function getUserForApiCall(): User|bool|null {
		if ($this->_userForAPICall != null) {
			return $this->_userForAPICall;
		}
		global $composerActive;
		if ($composerActive) {
			// Check if this is an OAuth2 authenticated request first
			$oauthUser = OAuth2Middleware::getAuthenticatedUser();
			if ($oauthUser) {
				if (empty($_REQUEST['language'])) {
					global $activeLanguage;
					global $translator;
					$userLanguage = new Language();
					$userLanguage->code = $oauthUser->interfaceLanguage;
					if ($userLanguage->find(true)) {
						if ($userLanguage->code != $activeLanguage->code) {
							$activeLanguage = $userLanguage;
							$translator = new Translator('lang', $userLanguage->code);
						}
					}
				}
				$this->_userForAPICall = $oauthUser;
				return $oauthUser;
			}
		}

		// Fall back to previous authentication
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

		if ($user !== false && $user->allowAppRequestLogging) {
			$this->logPatronRequest($user->id);
		}
		$this->_userForAPICall = $user;

		return $this->_userForAPICall;
	}

	/**
	 * Returns valid sources for Aspen LiDA to return when making API requests for searching, browse categories, lists, etc.
	 * <ul>
	 *     <li><b>Adding new items here without proper testing can result in the app crashing and should only be updated when a source is confirmed to be working with LiDA.</b></li>
	 * </ul>
	 * @param string $context
	 * @return array
	 */
	public static function getValidSourcesForLiDA($context = 'browseCategory'): array {
		if ($context == 'search') {
			return [
				'event_assabet',
				'event_communico',
				'event_libcal',
				'library_calendar_event',
				'event_aspenEvent',
				'event_localhop',
				'grouped_work'
			];
		} elseif ($context == 'list') {
			return [
				'GroupedWork',
				'Events',
				'Lists'
			];
		} else {
			return [
				'GroupedWork',
				'List',
				'Events'
			];
		}
	}

	/**
	 * Generic OAuth2 authentication with scope validation
	 * Subclasses should override getRequiredScopes() to define method-specific scope requirements
	 */
	protected function authenticateWithOAuth2($method): bool {
		$requiredScopes = $this->getRequiredScopes($method);

		if (!OAuth2Middleware::authenticate($requiredScopes)) {
			// Error response already sent by middleware
			return false;
		}

		return true;
	}

	/**
	 * Generic OAuth2 request handler with rate limiting and common response formatting
	 *
	 * @param string $method The API method being called
	 * @param array $allowedMethods Array of methods allowed for OAuth2 access
	 * @param string $rateLimitEndpoint The endpoint name for rate limiting (e.g., 'list_api', 'user_api')
	 */
	protected function handleOAuth2Request($method, $allowedMethods = [], $rateLimitEndpoint = 'api'): void {
		// Apply rate limiting for OAuth2 requests
		require_once ROOT_DIR . '/sys/Authentication/OAuth2/RateLimiter/OAuth2RateLimiter.php';
		$clientId = $this->getOAuth2ClientId();

		if (!OAuth2RateLimiter::enforce($rateLimitEndpoint, $clientId)) {
			// Rate limit response already sent
			return;
		}

		if (in_array($method, $allowedMethods) && method_exists($this, $method)) {
			$result = ['result' => $this->$method()];
			$output = json_encode($result);

			header('Content-type: application/json');
			header("Cache-Control: max-age=300");

			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			$apiName = get_class($this);
			APIUsage::incrementStat($apiName, $method);

			ExternalRequestLogEntry::logRequest($apiName . '.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
			echo $output;
		} else {
			$output = json_encode(['error' => 'invalid_method']);
			echo $output;
		}
	}

	/**
	 * Generic traditional token authentication handler
	 *
	 * @param string $method The API method being called
	 * @param array $allowedMethods Array of methods allowed for traditional token access
	 */
	protected function handleTraditionalTokenAuth(string $method, array $allowedMethods = []): void {
		if ($this->grantTokenAccess()) {
			if (in_array($method, $allowedMethods)) {
				$result = ['result' => $this->$method()];
				$output = json_encode($result);
				header('Content-type: application/json');
				header("Cache-Control: max-age=300");
				require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
				$apiName = get_class($this);
				APIUsage::incrementStat($apiName, $method);
			} else {
				$output = json_encode(['error' => 'invalid_method']);
			}
		} else {
			header('HTTP/1.0 401 Unauthorized');
			$output = json_encode(['error' => 'unauthorized_access']);
		}

		$apiName = get_class($this);
		ExternalRequestLogEntry::logRequest($apiName . '.' . $method, $_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], getallheaders(), '', $_SERVER['REDIRECT_STATUS'], $output, []);
		echo $output;
	}

	/**
	 * Generic public API request handler for API endpoints that are only behind IP whitelisting and do not require authentication
	 * Probably most likely server requests
	 *
	 * @param string $method The API method being called
	 * @param array $allowedMethods Array of methods allowed for public access
	 * @param bool $requireIPWhitelisting Whether to require IP whitelisting (default: true)
	 */
	protected function handlePublicRequest(string $method, array $allowedMethods = [], bool $requireIPWhitelisting = true): void {
		if ($requireIPWhitelisting && !IPAddress::allowAPIAccessForClientIP()) {
			$this->forbidAPIAccess();
		}

		if (in_array($method, $allowedMethods) && method_exists($this, $method)) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			$output = json_encode(['result' => $this->$method()]);

			echo $output;

			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			$apiName = get_class($this);
			APIUsage::incrementStat($apiName, $method);
		} else {
			echo json_encode(['error' => 'invalid_method']);
		}
	}

	/**
	 * Get OAuth2 client ID from token for rate limiting
	 * This is a simplified implementation - in production you'd extract this from the validated JWT token
	 */
	protected function getOAuth2ClientId(): string {
		// For now, we'll use a simplified approach
		// In practice, this would be extracted from the validated JWT token
		$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

		// Extract client information from token if needed
		// This is a placeholder - in production you'd decode the JWT token
		$apiName = strtolower(str_replace('API', '', get_class($this)));
		return $apiName . '_oauth2_client';
	}

	/**
	 * Generic launch method that handles OAuth2, traditional auth, and public access
	 * Subclasses can override this or use the individual handler methods
	 *
	 * @param string $method The API method being called
	 * @param array $oauthMethods Methods that support OAuth2 authentication
	 * @param array $tokenMethods Methods that support traditional token authentication
	 * @param array $publicMethods Methods that allow public access
	 * @param string $rateLimitEndpoint Rate limiting endpoint name
	 */
	protected function handleAPIRequest(string $method, array $oauthMethods = [], array $tokenMethods = [], array $publicMethods = [], string $rateLimitEndpoint = 'api'): void {
		$oauthAuthenticated = false;
		$authHeader = $this->getHeader('Authorization');
		if (!is_null($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
			if ($this->authenticateWithOAuth2($method)) {
				$oauthAuthenticated = true;
			} else {
				return;
			}
		}

		if ($oauthAuthenticated) {
			// If OAuth authenticated, try OAuth methods first, then fall back to public methods
			if (in_array($method, $oauthMethods) && method_exists($this, $method)) {
				$this->handleOAuth2Request($method, $oauthMethods, $rateLimitEndpoint);
			} elseif (in_array($method, $publicMethods) && method_exists($this, $method)) {
				// Allow public methods even with OAuth token
				$this->handlePublicRequest($method, $publicMethods, false);
			} else {
				header('Content-type: application/json');
				echo json_encode(['error' => 'invalid_method']);
			}
		} elseif (isset($_SERVER['PHP_AUTH_USER'])) {
			$this->handleTraditionalTokenAuth($method, $tokenMethods);
		} else {
			$this->handlePublicRequest($method, $publicMethods);
		}
	}

	/**
	 * Enhanced API request handler that automatically discovers method permissions
	 * No need to manually specify method arrays - uses docblock annotations
	 */
	protected function handleAPIRequestAuto($method, $rateLimitEndpoint = 'api'): void {
		// Automatically discover method permissions from docblock annotations
		$oauthMethods = $this->getOAuthMethods();
		$tokenMethods = $this->getTokenMethods();
		$publicMethods = $this->getPublicMethods();

		// Use the existing handleAPIRequest method
		$this->handleAPIRequest($method, $oauthMethods, $tokenMethods, $publicMethods, $rateLimitEndpoint);
	}

	/**
	 * Enhanced getRequiredScopes that uses docblock annotations as fallback
	 */
	protected function getRequiredScopes($method): array {
		// Try to get scopes from annotations first
		$annotationScopes = $this->getRequiredScopesFromAnnotations($method);
		if (!empty($annotationScopes)) {
			return $annotationScopes;
		}

		// Fallback to manual implementation (for backwards compatibility)
		return [];
	}

	/**
	 * Set language for API based on request parameters
	 */
	protected function setLanguage(): void {
		global $activeLanguage;
		if (isset($_GET['language'])) {
			$language = new Language();
			$language->code = $_GET['language'];
			if ($language->find(true)) {
				$activeLanguage = $language;
			}
		}
	}
}

