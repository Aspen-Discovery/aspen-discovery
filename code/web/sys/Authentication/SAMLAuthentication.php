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
	protected SSOSetting $config;
	protected bool $uidAsEmail = false;
	protected string $uid;
	protected bool $isStaffUser = false;

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
			$this->matchpoints = $ssoSettings->getMatchpoints(); //we will use the previous matchpoint system
			$this->config = clone $ssoSettings;

			if(str_contains($ssoSettings->ssoUniqueAttribute, 'mail')) {
				$this->uidAsEmail = true;
			}

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

			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			$technicalContactEmail = '';
			if($systemVariables && !empty($systemVariables->errorEmail)) {
				$technicalContactEmail = $systemVariables->errorEmail;
			}

			$settings = [
				// Strict should always be set to true in production
				'strict' => true,
				'debug' => false,
				'sp' => [
					// Identifier of the SP entity  (must be a URI)
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
				'security' => [
					'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
					'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
				],
				'contactPerson' => [
					'technical' => [
						'givenName' => 'Aspen Discovery',
						'emailAddress' => $technicalContactEmail
					],
					'support' => [
						'givenName' => $library->displayName,
						'emailAddress' => $library->contactEmail ?? $technicalContactEmail
					],
				],
				'organization' => [
					'en-US' => [
						'name' => $library->displayName,
						'displayname' => $library->displayName,
						'url' => $configArray['Site']['url']
					],
				]
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
		global $logger;
		$attributes = $this->getAttributes();
		if(count($attributes) == 0) {
			$logger->log("No SAML attributes found for user", Logger::LOG_ERROR);
			return false;
		}
		$user = $this->setupUser($attributes);
		if($this->ssoAuthOnly === false) {
			if(!$this->validateWithILS($user)) {
				if($this->selfRegister($user)) {
					return $this->validateWithILS($user);
				} else {
					AspenError::raiseError(new AspenError('Unable to register a new account with ILS.'));
				}
			} return $this->validateWithILS($user);
		} else {
			if(!$this->validateWithAspen($this->uid)) {
				$newUser = $this->selfRegisterAspenOnly($user);
				if($newUser instanceof User) {
					return $this->validateWithAspen($this->uid);
				} else {
					AspenError::raiseError(new AspenError('Unable to create account with Aspen for new SAML user.'));
				}
			} return $this->validateWithAspen($this->uid);
		}
	}

	private function validateWithILS($attributes): bool {
		$this->setupILSUser($attributes);
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		if($this->uidAsEmail) {
			$user = $catalogConnection->findNewUserByEmail($this->uid);
			if(is_string($user)) {
				global $logger;
				$logger->log($user, Logger::LOG_ERROR);
				return false;
			}
		} else {
			$_REQUEST['username'] = $this->uid;
			$user = $catalogConnection->findNewUser($this->uid);
		}

		if(!$user instanceof User) {
			return false;
		}

		$user->update();
		$user->updatePatronInfo(true);
		if ($this->uidAsEmail) {
			$user = $catalogConnection->findNewUserByEmail($this->uid);
		} else {
			$user = $catalogConnection->findNewUser($this->uid);
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
		$tmpUser = [];
		$tmpUser['isStaffUser'] = false;
		$tmpUser['staffPType'] = null;
		foreach ($this->matchpoints as $prop => $content) {
			if(!empty($this->config->samlStaffPTypeAttr) && !empty($this->config->samlStaffPTypeAttrValue)
			&& ($this->config->samlStaffPType != '-1' || $this->config->samlStaffPType != -1)) {
				$staffAttr = $this->config->samlStaffPTypeAttr;
				$staffAttrValue = $this->config->samlStaffPTypeAttrValue;
				$attrArray = strlen($staffAttr) > 0 ? $user[$staffAttr] : [];
				if(isset($attrArray) && count($attrArray) == 1) {
					if(strlen($attrArray[0]) > 0) {
						if($attrArray[0] == $staffAttrValue) {
							$tmpUser['isStaffUser'] = true;
							$tmpUser['staffPType'] = $this->config->samlStaffPType;
						}
					}
				}
			}
			$attrName = $this->config->$prop;
			$attrArray = strlen($attrName) > 0 ? $user[$attrName] : [];
			if(isset($attrArray) && count($attrArray) == 1) {
				if(strlen($attrArray[0]) > 0) {
					$tmpUser[$prop] = $attrArray[0];
				}
			} elseif (array_key_exists('fallback', $content)) {
				$fallback = $content['fallback'];
				$propertyName = $fallback['propertyName'];
				$tmpUser[$propertyName] = (array_key_exists('func', $fallback)) ? $fallback['func']($user, $this->config) : $this->config->$propertyName;
			}
		}

		if($this->uidAsEmail) {
			$this->uid = $tmpUser['ssoEmailAttr'];
		} else {
			$this->uid = $tmpUser['ssoUniqueAttribute'];
		}

		$this->isStaffUser = $tmpUser['isStaffUser'] ?? false;

		return $tmpUser;
	}

	private function setupILSUser($user) {
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		$ilsMapping = $catalogConnection->getLmsToSso($this->isStaffUser, $this->config->ssoUseGivenUserId, $this->config->ssoUseGivenUsername);

		foreach($ilsMapping as $key => $mappings) {
			$primaryAttr = $mappings['primary'];
			if (array_key_exists($primaryAttr, $user)) {
				$_REQUEST[$key] = $user[$primaryAttr];
				if(isset($mappings['useGivenCardnumber'])) {
					$useSecondaryOverPrimary = $mappings['useGivenCardnumber'];
					if($useSecondaryOverPrimary == '0') {
						$_REQUEST[$key] = null;
					}
				}
				if(isset($mappings['useGivenUserId'])) {
					$useSecondaryOverPrimary = $mappings['useGivenUserId'];
					if($useSecondaryOverPrimary == '0') {
						if($this->config->ssoUsernameFormat == '1') {
							$_REQUEST[$key] = $user['ssoEmailAttr'];
						} elseif($this->config->ssoUsernameFormat == '2') {
							$username = $user['ssoFirstnameAttr'] . '.' . $user['ssoLastnameAttr'];
							$username = strtolower($username);
							$_REQUEST[$key] = $username;
						} elseif($this->config->ssoUsernameFormat == '0') {
							$_REQUEST[$key] = null;
						}
					}
				}
			} elseif (array_key_exists('fallback', $mappings)) {
				if (strlen($mappings['fallback']) > 0) {
					$_REQUEST[$key] = $user[$mappings['fallback']];
				} else {
					$_REQUEST[$key] = $mappings['fallback'];
				}
			}
		}
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