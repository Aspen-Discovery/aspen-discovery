<?php /** @noinspection SpellCheckingInspection */

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';

require_once ROOT_DIR . '/services/Authentication/SAML/_toolkit_loader.php';

class SAMLAuthentication{

	protected OneLogin_Saml2_Auth $_auth;
	protected OneLogin_Saml2_Settings $_settings;
	protected array $_attributes;
	protected array $matchpoints;
	protected SSOSetting $config;
	protected bool $uidAsEmail = false;
	protected string $ilsUniqueAttribute = '';
	protected string $uid;
	protected bool $isStaffUser = false;
	protected bool $isStudentUser = false;

	protected bool $ssoAuthOnly = false;
    protected bool $forceReAuth = false;
	protected bool $updateAccount = false;

	/**
	 * @throws Exception
	 */
	public function __construct() {
		global $configArray;
		global $library;
		global $logger;

		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = "saml";
		if($ssoSettings->find(true)) {
			$this->matchpoints = $ssoSettings->getMatchpoints(); //we will use the previous matchpoint system
			$this->config = clone $ssoSettings;

			if(!empty($ssoSettings->ssoILSUniqueAttribute)) {
				$this->ilsUniqueAttribute = $ssoSettings->ssoILSUniqueAttribute;
			} elseif(strpos($ssoSettings->ssoUniqueAttribute, 'mail') != false ) {
				$this->uidAsEmail = true;
			}

			$this->updateAccount = $ssoSettings->updateAccount ?? false;

			$metadata = [];
			if($ssoSettings->ssoXmlUrl || $ssoSettings->ssoMetadataFilename) {
				$metadataParser = new OneLogin_Saml2_IdPMetadataParser();
				if($ssoSettings->ssoXmlUrl) {
					$metadata = $metadataParser->parseRemoteXML($ssoSettings->ssoXmlUrl, $ssoSettings->ssoEntityId);
				} elseif($ssoSettings->ssoMetadataFilename) {
					global $serverName;
					$xmlPath = $ssoSettings->ssoMetadataFilename;
					//$xmlPath = '/data/aspen-discovery/' . $serverName . '/sso_metadata/' . $ssoSettings->ssoMetadataFilename;
					//$xmlPath = '/usr/local/aspen-discovery-data/sso_metadata/' . $ssoSettings->ssoMetadataFilename;
					$metadata = $metadataParser->parseFileXML($xmlPath, $ssoSettings->ssoEntityId);
				}
			}

			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			$technicalContactEmail = '';
			if($systemVariables && !empty($systemVariables->errorEmail)) {
				$technicalContactEmail = $systemVariables->errorEmail;
			}

            if(isset($ssoSettings->forceReAuth)) {
                if($ssoSettings->forceReAuth == 1 || $ssoSettings->forceReAuth == '1') {
                    $this->forceReAuth = true;
                }
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
					'requestedAuthnContext' => false,
				],
				/*'contactPerson' => [
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
				]*/
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
				$logger->log($e, Logger::LOG_ERROR, true);
				echo($e);
				die();
			}

		} else {
			$logger->log('No single sign-on settings found for library', Logger::LOG_ALERT);
			echo('Single sign-on settings must be configured to use SAML for ssoArray authentication.');
			die();
		}
	}

	/**
	 * @throws OneLogin_Saml2_Error
	 */
	public function login($returnTo = null, $parameters = array(), $forceAuthn = false, $isPassive = false, $stay = false, $setNameIdPolicy = true, $nameIdValueReq = null): ?string {
		return $this->_auth->login($returnTo, $parameters, $this->forceReAuth ?? $forceAuthn, $isPassive, $stay, $setNameIdPolicy, $nameIdValueReq);
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
		// Get the attributes from the SAML Response - so we can use them for futher processing
		$attributes = $this->getAttributes();
		if(count($attributes) == 0) {
			$logger->log("No SAML attributes found for ssoArray", Logger::LOG_ERROR, true);
			return false;
		}
		// Map the attributes from the SAML Response to the sso* attributes - so we can have consistent processing from here out
		$ssoArray = $this->mapSAMLAttributesToSSOArray($attributes);

		if($this->ssoAuthOnly === false) {
			$ilsUserArray = $this->setupILSUser($ssoArray);
			if(!$this->validateWithILS($ssoArray)) {
				if($this->selfRegister($ilsUserArray)) {
					return $this->validateWithILS($ssoArray);
				} else {
					AspenError::raiseError(new AspenError('Unable to register a new account with ILS during SAML authentication.'));
					return false;
				}
			} else {
				return $this->validateWithILS($ssoArray);
			}
		} else {
			if(!$this->validateWithAspen($this->uid)) {
				$newUser = $this->selfRegisterAspenOnly($ssoArray);
				if($newUser instanceof User) {
					return $this->validateWithAspen($this->uid);
				} else {
					AspenError::raiseError(new AspenError('Unable to create account with Aspen for new SAML ssoArray.'));
					return false;
				}
			} else {
				return $this->validateWithAspen($this->uid);
			}
		}
	}

	private function validateWithILS($ssoArray): bool {
		global $logger;
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		if(!empty($this->ilsUniqueAttribute)) {
			$logger->log("Finding user in ILS by field ($this->ilsUniqueAttribute, $this->uid)", Logger::LOG_ERROR);
			$user = $catalogConnection->findUserByField($this->ilsUniqueAttribute, $this->uid);
			if(is_string($user)) {
				global $logger;
				$logger->log($user, Logger::LOG_ERROR);
				AspenError::raiseError(new AspenError('More than one user found in the ILS for '.$this->ilsUniqueAttribute.' of '.$this->uid.'.'));
				return false;
			}
		} elseif($this->uidAsEmail) {
			$logger->log("Finding user in ILS by email ($this->uid)", Logger::LOG_ERROR);
			$user = $catalogConnection->findNewUserByEmail($this->uid);
			if(is_string($user)) {
				global $logger;
				$logger->log($user, Logger::LOG_ERROR);
				AspenError::raiseError(new AspenError('More than one user found in the ILS for email of '.$this->uid.'.'));
				return false;
			}
		} else {
			$logger->log("Finding user in ILS by barcode ($this->uid)", Logger::LOG_ERROR);
			$_REQUEST['username'] = $this->uid;
			$user = $catalogConnection->findNewUser($this->uid, '');
		}

		if(!$user instanceof User) {
			$logger->log("  Could not find an existing user in the ILS with that information", Logger::LOG_ERROR);
			return false;
		}

		$user->update();
		if($this->updateAccount) {
			$user->updatePatronInfo(true);
		}
		if(!empty($this->ilsUniqueAttribute)) {
			$user = $catalogConnection->findUserByField($this->ilsUniqueAttribute, $this->uid);
			if(is_string($user)) {
				global $logger;
				$logger->log($user, Logger::LOG_ERROR);
				return false;
			}
		} elseif($this->uidAsEmail) {
			$user = $catalogConnection->findNewUserByEmail($this->uid);
		} else {
			$user = $catalogConnection->findNewUser($this->uid, '');
		}
		return $this->aspenLogin($user);
	}

	private function validateWithAspen($username): bool {
		$findBy = 'ils_username';
		if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
			$findBy = 'email';
		}
		if($this->ssoAuthOnly) {
			$findBy = 'username';
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
			$_REQUEST['username'] = empty($user->ils_username) ? $user->ils_barcode : $user->ils_username;
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

	private function mapSAMLAttributesToSSOArray($user) {
		$tmpUser = [];
		$tmpUser['isStaffUser'] = false;
		$tmpUser['staffPType'] = null;
		$tmpUser['isStudentUser'] = false;
		$tmpUser['studentPType'] = null;
		foreach ($this->matchpoints as $prop => $content) {
			// check if the user should be classified as staff or a student instead of a standard patron
			if(!empty($this->config->samlStaffPTypeAttr) && !empty($this->config->samlStaffPTypeAttrValue)
			&& ($this->config->samlStaffPType != '-1' || $this->config->samlStaffPType != -1)) {
				$staffAttr = $this->config->samlStaffPTypeAttr;
				$staffAttrValue = $this->config->samlStaffPTypeAttrValue;
				$staffAttrValue = explode(",", $staffAttrValue);
				$attrArray = strlen($staffAttr) > 0 ? $user[$staffAttr] : [];
				if((isset($attrArray) && count($attrArray) == 1) || count(array_intersect($staffAttrValue, $attrArray))) {
					$tmpUser['isStaffUser'] = true;
					$tmpUser['staffPType'] = $this->config->samlStaffPType;
				}
			}

			if (!empty($this->config->samlStudentPTypeAttr) && !empty($this->config->samlStudentPTypeAttrValue)
				&& ($this->config->samlStudentPType != '-1' || $this->config->samlStudentPType != -1)) {
				$studentAttr = $this->config->samlStudentPTypeAttr;
				$studentAttrValue = $this->config->samlStudentPTypeAttrValue;
				$studentAttrValue = explode(",", $studentAttrValue);
				$attrArray = strlen($studentAttr) > 0 ? $user[$studentAttr] : [];
				if((isset($attrArray) && count($attrArray) == 1) || count(array_intersect($studentAttrValue, $attrArray))) {
					$tmpUser['isStudentUser'] = true;
					$tmpUser['studentPType'] = $this->config->samlStudentPType;
				}
			}

			$attrName = $this->config->$prop;
			$attrArray = (strlen($attrName) > 0 && array_key_exists($attrName, $user)) ? $user[$attrName] : [];
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

		global $logger;
		$logger->log("Mapped User attributes: " . print_r($tmpUser, true), Logger::LOG_ERROR, true);

		if($this->uidAsEmail) {
			$this->uid = $tmpUser['ssoEmailAttr'];
		} else {
			$this->uid = $tmpUser['ssoUniqueAttribute'];
		}

		$this->isStaffUser = $tmpUser['isStaffUser'] ?? false;
		$this->isStudentUser = $tmpUser['isStudentUser'] ?? false;

		return $tmpUser;
	}

	/**
	 * Map from the sso attribute array to Aspen/ILS fields
	 *
	 * @param $ssoArray
	 * @return array
	 */
	private function setupILSUser($ssoArray) : array {
		$ilsUser = [];
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		$ilsMapping = $catalogConnection->getLmsToSso($this->isStaffUser, $this->isStudentUser, $this->config->ssoUseGivenUserId, $this->config->ssoUseGivenUsername);

		foreach($ilsMapping as $key => $mappings) {
			$primaryAttr = $mappings['primary'];
			if (array_key_exists($primaryAttr, $ssoArray)) {
				$_REQUEST[$key] = $ssoArray[$primaryAttr];
				$ilsUser[$key] = $ssoArray[$primaryAttr];
				if(isset($mappings['useGivenCardnumber'])) {
					if($this->config->ssoUseGivenUserId == '0' || $this->config->ssoUseGivenUserId == 0) {
						$_REQUEST[$key] = null;
						$ilsUser[$key] = null;
					}
				}
				if(isset($mappings['useGivenUserId'])) {
					$useSecondaryOverPrimary = $mappings['useGivenUserId'];
					if($useSecondaryOverPrimary == '0') {
						if($this->config->ssoUsernameFormat == '1') {
							$_REQUEST[$key] = $ssoArray['ssoEmailAttr'];
							$ilsUser[$key] = $ssoArray['ssoEmailAttr'];
						} elseif($this->config->ssoUsernameFormat == '2') {
							$username = $ssoArray['ssoFirstnameAttr'] . '.' . $ssoArray['ssoLastnameAttr'];
							$username = strtolower($username);
							$_REQUEST[$key] = $username;
							$ilsUser[$key] = $username;
						} elseif($this->config->ssoUsernameFormat == '0') {
							$_REQUEST[$key] = null;
							$ilsUser[$key] = null;
						}
					}
				}
			} elseif (array_key_exists('fallback', $mappings)) {
				if (strlen($mappings['fallback']) > 0) {
					$_REQUEST[$key] = $ssoArray[$mappings['fallback']];
					$ilsUser[$key] = $ssoArray[$mappings['fallback']];
				} else {
					$_REQUEST[$key] = $mappings['fallback'];
					$ilsUser[$key] = $mappings['fallback'];
				}
			}
		}

		if(!empty($this->ilsUniqueAttribute)) {
			if($this->ilsUniqueAttribute == 'sort1') {
				$ilsUser['statistics_1'] = $this->uid;
				$_REQUEST['statistics_1'] = $this->uid;
			}

			if($this->ilsUniqueAttribute == 'sort2') {
				$ilsUser['statistics_2'] = $this->uid;
				$_REQUEST['statistics_2'] = $this->uid;
			}
		}

		$ilsUser = array_merge($ilsUser, $ssoArray);
		return $ilsUser;
	}

	private function selfRegister($user): bool {
		global $logger;
		$logger->log("Performing self registration of user " . print_r($user, true), Logger::LOG_ERROR);
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		$selfReg = $catalogConnection->selfRegister(true, $user);
		if($selfReg['success'] != '1') {
			return false;
		}
		return true;
	}

	private function selfRegisterAspenOnly($user) {
		global $library;
		$tmpUser = new User();
		$tmpUser->email = $this->searchArray($user,'ssoEmailAttr');
		$tmpUser->firstname = $this->searchArray($user, 'ssoFirstnameAttr') ?? '';
		$tmpUser->lastname = $this->searchArray($user, 'ssoLastnameAttr') ?? '';
		$tmpUser->username = $this->searchArray($user, 'ssoEmailAttr');
		$tmpUser->unique_ils_id = $this->searchArray($user, 'ssoUniqueAttribute');
		$tmpUser->phone = '';
		$tmpUser->displayName = $this->searchArray($user, 'ssoDisplayNameAttr') ?? '';

		if($this->searchArray($user, 'ssoPatronTypeAttr')) {
			$patronType = $this->searchArray($user, 'ssoPatronTypeAttr');
		} elseif($this->searchArray($user, 'ssoCategoryIdFallback')) {
			$patronType = $this->searchArray($user, 'ssoCategoryIdFallback');
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
		if($this->ssoAuthOnly) {
			$tmpUser->source = 'admin_sso';
		}

		$tmpUser->created = date('Y-m-d');
		if(!$tmpUser->insert()) {
			global $logger;
			$logger->log(print_r($tmpUser->getLastError(), true), Logger::LOG_ERROR);
			$logger->log('Error creating Aspen ssoArray ' . print_r($this->searchArray($user, 'ssoUniqueAttribute'), true), Logger::LOG_ERROR);
			return false;
		}

		return UserAccount::findNewAspenUser('username', $this->searchArray($user, 'ssoUniqueAttribute'));
	}

	private function newSSOSession($id) {
		global $timer;
		/** SessionInterface $session */
		global $session;
		@session_destroy();
		require_once ROOT_DIR . '/sys/Session/MySQLSession.php';
		session_name('aspen_session');
		$session = new MySQLSession();
		$session->init();

		$timer->logTime('Session initialization MySQLSession');

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
}