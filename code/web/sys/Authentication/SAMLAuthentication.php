<?php /** @noinspection SpellCheckingInspection */

require_once 'bootstrap.php';
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';

require_once ROOT_DIR . '/services/Authentication/SAML/_toolkit_loader.php';

class SAMLAuthentication extends Action {

	protected OneLogin_Saml2_Auth $_auth;
	protected OneLogin_Saml2_Settings $_settings;
	protected array $_attributes;
	protected array $matchpoints;

	protected bool $ssoAuthOnly = false;

	/**
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct();
		global $configArray;
		global $library;
		global $logger;

		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = "saml";
		if($ssoSettings->find(true)) {
			$this->matchpoints = $ssoSettings->getMatchpoints();

			$metadata = [];
			if($ssoSettings->ssoXmlUrl || $ssoSettings->ssoMetadataFilename) {
				$metadataParser = new OneLogin_Saml2_IdPMetadataParser();
				if($ssoSettings->ssoXmlUrl) {
					$metadata = $metadataParser->parseRemoteXML($ssoSettings->ssoXmlUrl, $ssoSettings->ssoEntityId);
				} elseif($ssoSettings->ssoMetadataFilename) {
					global $serverName;
					//$xmlPath = '/data/aspen-discovery/' . $serverName . '/sso_metadata/' . $ssoSettings->ssoMetadataFilename;
					$xmlPath = '/usr/local/aspen-discovery-data/sso_metadata/' . $ssoSettings->ssoMetadataFilename;
					$metadata = $metadataParser->parseFileXML($xmlPath, $ssoSettings->ssoEntityId);
				}
			}

			$settings = [
				'strict' => true,
				'debug' => false,
				'sp' => [
					'entityId' => $configArray['Site']['url'] . '/Authentication/SAML2?metadata',
					'assertionConsumerService' => [
						'url' => $configArray['Site']['url'] . '/Authentication/SAML2?acs',
					],
					'singleLogoutService' => [
						'url' => $configArray['Site']['url'] . '/Authentication/SAML2?sls',
					],
					'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'
				],
				'idp' => $metadata['idp'],
			];

			require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
			$accountProfile = new AccountProfile();
			$accountProfile->id = $library->accountProfileId;
			if($accountProfile->find(true)) {
				if($accountProfile->authenticationMethod === 'sso') {
					$this->ssoAuthOnly = true;
				} else {
					$this->ssoAuthOnly = $ssoSettings->ssoAuthOnly;
				}
			}

			try {
				$this->_auth = new OneLogin_Saml2_Auth($settings);
				$this->_settings = new OneLogin_Saml2_Settings($settings, true);
			} catch(Exception $e) {
				$logger->log($e, Logger::LOG_ERROR);
				echo($e);
				die();
			}

		} else {
			$logger->log('No single sign-on settings found for library', Logger::LOG_ALERT);
			echo('Single sign-on settings must be configured to use SAML for user authentication.');
			die();
		}
	}

	/**
	 * @throws OneLogin_Saml2_Error
	 */
	public function login($returnTo = null, $parameters = array(), $forceAuthn = false, $isPassive = false, $stay = false, $setNameIdPolicy = true, $nameIdValueReq = null): ?string {
		return $this->_auth->login($returnTo, $parameters, $forceAuthn, $isPassive, $stay, $setNameIdPolicy, $nameIdValueReq);
	}

	/**
	 * @throws OneLogin_Saml2_Error
	 */
	public function logout($returnTo = null, $parameters = array(), $nameId = null, $sessionIndex = null, $stay = false, $nameIdFormat = null, $nameIdNameQualifier = null, $nameIdSPNameQualifier = null): ?string {
		return $this->_auth->logout($returnTo, $parameters, $nameId, $sessionIndex, $stay, $nameIdFormat, $nameIdNameQualifier, $nameIdSPNameQualifier);
	}

	public function isAuthenticated(): bool {
		return $this->_auth->isAuthenticated();
	}

	/**
	 * @throws OneLogin_Saml2_Error
	 * @throws OneLogin_Saml2_ValidationError
	 */
	public function processResponse($requestId = null) {
		$this->_auth->processResponse($requestId);
	}

	public function getAttributes(): array {
		$this->_attributes = $this->_auth->getAttributes();
		return $this->_attributes;
	}

	/**
	 * @throws OneLogin_Saml2_Error
	 */
	public function processSLO($keepLocalSession = false, $requestId = null, $retrieveParametersFromServer = false, $cbDeleteSession = null, $stay = false): ?string {
		return $this->_auth->processSLO($keepLocalSession, $requestId, $retrieveParametersFromServer, $cbDeleteSession, $stay);
	}

	public function getErrors(): array {
		return $this->_auth->getErrors();
	}

	public function getSettings(): OneLogin_Saml2_Settings {
		return $this->_settings;
	}

	/**
	 * @throws OneLogin_Saml2_Error
	 */
	public function redirectTo($url = '', $parameters = array(), $stay = false): ?string {
		return $this->_auth->redirectTo($url, $parameters, $stay);
	}

	public function validateAccount() {
		$attributes = $this->getAttributes();
		$username = $this->getUsername();
		if($this->ssoAuthOnly === false) {
			if(!$this->validateWithILS($username)) {
				if($this->selfRegister($attributes)) {
					return $this->validateWithILS($username);
				} else {
					AspenError::raiseError(new AspenError('Unable to register a new account with ILS.'));
				}
			} return $this->validateWithILS($username);
		} else {
			if(!$this->validateWithAspen($username)) {
				$newUser = $this->selfRegisterAspenOnly($attributes);
				if($newUser instanceof User) {
					return $this->validateWithAspen($username);
				} else {
					AspenError::raiseError(new AspenError('Unable to create account with Aspen for new SAML user.'));
				}
			} return $this->validateWithAspen($username);
		}
	}

	private function validateWithILS($username): bool {
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
			$user = $catalogConnection->findNewUserByEmail($username);
		} else {
			$user = $catalogConnection->findNewUser($username);
		}

		if(!$user instanceof User) {
			return false;
		}

		$user->update();
		$user->updatePatronInfo(true);
		if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
			$user = $catalogConnection->findNewUserByEmail($username);
		} else {
			$user = $catalogConnection->findNewUser($username);
		}
		return $this->aspenLogin($user);
	}

	private function validateWithAspen($username): bool {
		$findBy = 'username';
		if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
			$findBy = 'email';
		}
		$user = UserAccount::findNewAspenUser($findBy, $username);

		if(!$user instanceof User){
			return false;
		}

		return $this->aspenLogin($user);
	}

	private function aspenLogin(User $user) {
		if($this->ssoAuthOnly) {
			$login = UserAccount::loginWithAspen($user);
		} else {
			$_REQUEST['username'] = $user->cat_username;
			$login = UserAccount::login(true);
		}

		if($login) {
			$user->isLoggedInViaSSO = 1;
			$user->update();
			$this->newSSOSession($login->id);
			return true;
		}

		return false;
	}

	private function setupUser($user) {
		if($this->searchArray($user, $this->matchpoints['patronType'])) {
			$patronType = $this->searchArray($user, $this->matchpoints['patronType']);
		} elseif($this->matchpoints['patronType_fallback']) {
			$patronType = $this->matchpoints['patronType_fallback'];
		} else {
			$patronType = null;
		}
		return [
			'email' => $this->searchArray($user, $this->matchpoints['email']),
			'firstname' => $this->searchArray($user, $this->matchpoints['firstName']),
			'lastname' => $this->searchArray($user, $this->matchpoints['lastName']),
			'username' => $this->searchArray($user, $this->matchpoints['userId']),
			'displayName' => $this->searchArray($user, $this->matchpoints['displayName']),
			'cat_username' => $this->searchArray($user, $this->matchpoints['username']),
			'category_id' => $patronType,
		];
	}

	private function selfRegister($attributes): bool {
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		$userAttributes = $this->setupUser($attributes);
		$selfReg = $catalogConnection->selfRegister(true, $userAttributes);
		if($selfReg['success'] != '1') {
			return false;
		}
		return true;
	}

	private function selfRegisterAspenOnly($user) {
		global $library;
		$tmpUser = new User();
		$tmpUser->email = $this->searchArray($user, $this->matchpoints['email']);
		$tmpUser->firstname = $this->searchArray($user, $this->matchpoints['firstName']) ?? '';
		$tmpUser->lastname = $this->searchArray($user, $this->matchpoints['lastName']) ?? '';
		$tmpUser->username = $this->searchArray($user, $this->matchpoints['userId']);
		$tmpUser->phone = '';
		$tmpUser->displayName = $this->searchArray($user, $this->matchpoints['displayName']) ?? '';

		if($this->searchArray($user, $this->matchpoints['patronType'])) {
			$patronType = $this->searchArray($user, $this->matchpoints['patronType']);
		} elseif($this->matchpoints['patronType_fallback']) {
			$patronType = $this->matchpoints['patronType_fallback'];
		} else {
			$patronType = null;
		}
		$tmpUser->patronType = $patronType;
		$tmpUser->trackReadingHistory = false;

		$location = new Location();
		$location->libraryId = $library->libraryId;
		$location->orderBy('isMainBranch desc');
		if (!$location->find(true)) {
			$tmpUser->homeLocationId = 0;
		} else {
			$tmpUser->homeLocationId = $location->locationId;
		}
		$tmpUser->myLocation1Id = 0;
		$tmpUser->myLocation2Id = 0;
		$tmpUser->created = date('Y-m-d');
		if(!$tmpUser->insert()) {
			global $logger;
			$logger->log('Error creating Aspen user ' . print_r($this->searchArray($user, $this->matchpoints['userId']), true), Logger::LOG_ERROR);
			return false;
		}

		return UserAccount::findNewAspenUser('username', $this->searchArray($user, $this->matchpoints['userId']));
	}

	private function newSSOSession($id) {
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

	public function searchArray($array, $needle) {
		if (array_key_exists($needle, $array)) {
			if(is_array($array[$needle])) {
				return $array[$needle][0];
			} else {
				return $array[$needle];
			}
		}
		return false;
	}

	private function getUsername() {
		return $this->searchArray($this->_attributes, $this->matchpoints['username']);
	}

	function launch() {}

	function getBreadcrumbs(): array {
		return [];
	}
}