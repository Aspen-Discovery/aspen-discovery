<?php

require_once 'bootstrap.php';
require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';

class OAuthAuthentication extends Action
{
	/** @var CurlWrapper */
	private $curlWrapper;

	protected $basicAuth;
	protected $state;
	protected $accessToken;
	protected $resourceOwner;
	protected $redirectUri;
	protected $matchpoints;

	public function __construct()
	{
		parent::__construct();

		global $library;
		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = "oauth";
		if ($ssoSettings->find(true)) {
			$this->state = $this->getRandomState();
			$this->basicAuth = $ssoSettings->getBasicAuthToken();
			$this->redirectUri = $ssoSettings->getRedirectUrl();
			$this->matchpoints = $ssoSettings->getMatchpoints();
		} else {
			global $logger;
			$logger->log('No single sign-on settings found for library', Logger::LOG_ALERT);
			echo("Single sign-on settings must be configured to use OAuth 2.0 for user authentication.");
			die();
		}
	}

	public function initCurlWrapper()
	{
		$this->curlWrapper = new CurlWrapper();
		$this->curlWrapper->timeout = 5;
		$this->curlWrapper->addCustomHeaders([
			"Authorization: Basic $this->basicAuth",
			"Cache-Control: no-cache",
			"Content-Type: application/x-www-form-urlencoded"
		], true);
	}

	public function verifyIdToken($payload): array
	{
		$success = false;
		$error = '';
		$message = '';
		$returnTo = '';

		global $library;

		if (isset($payload['code'])) {
			$ssoSettings = new SSOSetting();
			$ssoSettings->id = $library->ssoSettingId;
			$ssoSettings->service = "oauth";
			if ($ssoSettings->find(true)) {
				$requestOptions = [
					'client_id' => $ssoSettings->clientId,
					'client_secret' => $ssoSettings->clientSecret,
					'grant_type' => 'authorization_code',
					'code' => $payload['code'],
					'redirect_uri' => $this->redirectUri,
				];

				$requestToken = $this->getAccessToken($ssoSettings->getAccessTokenUrl(), $requestOptions);
				if (!$requestToken) {
					return [
						'success' => false,
						'message' => "Did not get expected JSON results from OAuth to get a valid Access Token",
					];
				}

				$resourceOwner = $this->getResourceOwner($ssoSettings->getResourceOwnerDetailsUrl());
				if (!$resourceOwner) {
					return [
						'success' => false,
						'message' => "Did not get expected JSON results from OAuth to get Resource Owner details",
					];
				}

				$account = $this->validateAccount();
				if (!$account) {
					return [
						'success' => false,
						'message' => "Unable to find and/or register user with provided credentials",
					];
				}

				$success = true;
				$message = 'Successfully logged in using OAuth';
				$returnTo = '/MyAccount/Home';

			} else {
				$error = 'OAuth is not setup for library.';
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

	private function validateAccount(): bool
	{
		global $logger;
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();

		$user = $catalogConnection->findNewUser($this->getUserId());

		if (!$user instanceof User) {
			$selfReg = $catalogConnection->selfRegister();
			if ($selfReg['success'] != '1') {
				//unable to register the user
				$logger->log("Error self registering user " . $this->getUserId(), Logger::LOG_ERROR);
				return false;
			}
		}

		$user->email = $this->getEmail();
		$user->firstname = $this->getFirstName();
		$user->lastname = $this->getLastName();
		$user->updatePatronInfo(true);

		$user = $catalogConnection->findNewUser($this->getUserId());

		if ($user instanceof User) {
			$_REQUEST['username'] = $this->getUserId();
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

	public function getAuthorizationRequestUrl(SSOSetting $settings)
	{
		$authorizationUrl = $settings->getAuthorizationUrl();
		$requestOptions = [
			'client_id' => $settings->clientId,
			'response_type' => 'code',
			'redirect_uri' => $this->redirectUri,
			'state' => $this->state,
			'scope' => $settings->getScope()
		];

		$queryString = $this->buildQueryString($requestOptions);
		return $this->appendQuery($authorizationUrl, $queryString);
	}

	public function getAccessToken($accessTokenUrl, array $options = [], $returnToken = false)
	{
		$queryString = $this->buildQueryString($options);
		$url = $this->appendQuery($accessTokenUrl, $queryString);
		$this->initCurlWrapper();
		$response = $this->curlWrapper->curlPostPage($url, '');
		$options = json_decode($response, true);
		if (!empty($options['access_token'])) {
			$this->accessToken = $options['access_token'];
			if ($returnToken) {
				return $options['access_token'];
			}
			return true;
		}
		return false;
	}

	private function getResourceOwner($resourceOwnerDetailsUrl)
	{
		$url = $resourceOwnerDetailsUrl . "?access_token=" . $this->accessToken;
		$this->initCurlWrapper();
		$response = $this->curlWrapper->curlGetPage($url);
		$options = json_decode($response, true);
		if ($options[$this->matchpoints['userId']]) {
			$this->resourceOwner = $options;
			return true;
		}
		return false;
	}

	private function getUserId()
	{
		return $this->resourceOwner[$this->matchpoints['userId']];
	}

	private function getFirstName()
	{
		return $this->resourceOwner[$this->matchpoints['firstName']];
	}

	private function getLastName()
	{
		return $this->resourceOwner[$this->matchpoints['lastName']];
	}

	private function getEmail()
	{
		return $this->resourceOwner[$this->matchpoints['email']];
	}

	protected function getRandomState($length = 32): string
	{
		return bin2hex(random_bytes($length / 2));
	}

	protected function buildQueryString(array $params): string
	{
		return http_build_query($params, '', '&', \PHP_QUERY_RFC3986);
	}

	protected function appendQuery($url, $query)
	{
		$query = trim($query, '?&');

		if ($query) {
			$glue = strstr($url, '?') === false ? '?' : '&';
			return $url . $glue . $query;
		}

		return $url;
	}

	function launch()
	{
	}

	function getBreadcrumbs(): array
	{
		return [];
	}
}