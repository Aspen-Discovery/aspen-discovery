<?php /** @noinspection SpellCheckingInspection */

require_once 'bootstrap.php';
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
require_once ROOT_DIR . '/sys/Account/AccountProfile.php';

class LDAPAuthentication extends Action {

	static bool $clientInitialized = false;
	protected mixed $ldap_client = null;
	protected array $ldap_config = [];
	protected int $timeout = 0;
	protected bool $ssoAuthOnly = false;
	protected string $message = "";
	protected $matchpoints = [];

	public function __construct() {
		parent::__construct();
		if(!$this->intializeClient()) {
			AspenError::raiseError(new AspenError(@ldap_error($this->ldap_client) . '. LDAP Error Code: ' . @ldap_errno($this->ldap_client)));
		} else {
			LDAPAuthentication::$clientInitialized = true;
		}
	}

	private function intializeClient(): bool {
		if(!LDAPAuthentication::$clientInitialized) {
			global $logger;
			$logger->log('Initializing LDAP Connection', Logger::LOG_DEBUG);

			global $library;
			$ssoSettings = new SSOSetting();
			$ssoSettings->id = $library->ssoSettingId;
			$ssoSettings->service = 'ldap';
			if($ssoSettings->find(true)) {
				$this->matchpoints = $ssoSettings->getMatchpoints();
				$this->ldap_config['baseDir'] = $ssoSettings->ldapBaseDN;
				$this->ldap_config['client'] = $ssoSettings->ldapUsername;
				$this->ldap_config['secret'] = $ssoSettings->ldapPassword;
				$this->ldap_config['host'] = $ssoSettings->ldapHosts;
				$this->ldap_config['idAttr'] = $ssoSettings->ldapIdAttr;
				$this->ldap_config['ou'] = $ssoSettings->ldapOrgUnit;

				$accountProfile = new AccountProfile();
				$accountProfile->id = $library->accountProfileId;
				if($accountProfile->find(true)) {
					if($accountProfile->authenticationMethod === 'sso') {
						$this->ssoAuthOnly = true;
					} else {
						$this->ssoAuthOnly = $ssoSettings->ssoAuthOnly;
					}
				}

				$this->ldap_client = @ldap_connect($this->ldap_config['host']);
				@ldap_set_option($this->ldap_client, LDAP_OPT_DEBUG_LEVEL, 7);
				@ldap_set_option($this->ldap_client, LDAP_OPT_PROTOCOL_VERSION, 3);
				@ldap_set_option($this->ldap_client, LDAP_OPT_REFERRALS, false);
				@ldap_set_option($this->ldap_client, LDAP_OPT_NETWORK_TIMEOUT, $this->timeout);
				@ldap_set_option($this->ldap_client, LDAP_OPT_TIMELIMIT, $this->timeout);

				if (stripos($this->ldap_config['host'], 'ldaps:')) {
					if(!@ldap_start_tls($this->ldap_client)) {
						AspenError::raiseError(new AspenError("Unable to force TLS"));
					}
				}

				if($this->ldap_client) {
					if(@ldap_bind($this->ldap_client, $this->ldap_config['client'], $this->ldap_config['secret'])) {
						return true;
					}

				}
			}
		}
		return false;
	}

	public function validateAccount($username, $password): mixed {
		if(LDAPAuthentication::$clientInitialized) {
			$user = $this->findUser($username);
			$isAuthenticated = $this->validateWithLDAP($user, $password);
			if ($user && $isAuthenticated) {
				$attributes = $this->getUserAttributes($user);
				if($this->ssoAuthOnly === false) {
					if (!$this->validateWithILS($username)) {
						if ($this->selfRegister($attributes)) {
							return $this->validateWithILS($username);
						} else {
							AspenError::raiseError(new AspenError('Unable to register a new account with ILS.'));
						}
					} return $this->validateWithILS($username);
				} else {
					if(!$this->validateWithAspen($username)) {
						$newUser = $this->createNewAspenUser($attributes);
						if($newUser instanceof User) {
							return $this->validateWithAspen($username);
						} else {
							AspenError::raiseError(new AspenError('Unable to create account with Aspen for new LDAP user.'));
						}
					} return $this->validateWithAspen($username);
				}
			}
			AspenError::raiseError(new AspenError('Unable to find user with that username in LDAP. ' . ldap_error($this->ldap_client)));
		}
		AspenError::raiseError(new AspenError(ldap_error($this->ldap_client)));
		return false;
	}

	private function findUser($username): mixed {
		if($this->ldap_config['ou']) {
			$result = @ldap_search($this->ldap_client, $this->ldap_config['baseDir'], '(&(' . $this->ldap_config['idAttr'] . '=' . $username . ')(ou=' . $this->ldap_config['ou'] . '))');
		} else {
			$result = @ldap_search($this->ldap_client, $this->ldap_config['baseDir'], $this->ldap_config['idAttr'] . '=' . $username);
		}
		return @ldap_first_entry($this->ldap_client, $result);
	}

	private function getUserAttributes($entry) {
		if(!@ldap_get_attributes($this->ldap_client, $entry)) {
			AspenError::raiseError(new AspenError(@ldap_error($this->ldap_client) . ' Code: ' . @ldap_errno($this->ldap_client)));
			die();
		}

		$attributes = @ldap_get_attributes($this->ldap_client, $entry);
		$user = [];
		for ($i = 0; $i < $attributes['count']; $i++) {
			$name = $attributes[$i];
			$attribute = $attributes[$name];

			$values = [];
			for ($j = 0; $j < $attribute['count']; $j++)  {
				$value = $attribute[$j];
				if (strtolower($name) === 'jpegphoto' || strtolower($name) === 'objectguid') {
					$values[] = base64_encode($value);
				} else
					$values[] = $value;
			}

			$user[$name] = $values;
		}

		return $user;
	}

	private function validateWithILS($username): bool {
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		$user = $catalogConnection->findNewUser($username);

		if(!$user instanceof User) {
			return false;
		}

		$user->update();
		$user->updatePatronInfo(true);
		$user = $catalogConnection->findNewUser($username);
		return $this->login($user, $username);
	}

	private function validateWithAspen($username): bool {
		$user = UserAccount::findNewAspenUser('username', $username);

		if(!$user instanceof User){
			return false;
		}

		return $this->login($user);
	}

	private function validateWithLDAP($user, $password): bool {
		$userDN = @ldap_get_dn($this->ldap_client, $user);
		if(@ldap_bind($this->ldap_client, $userDN, $password)) {
			return true;
		} else {
			$this->message = @ldap_error($this->ldap_client);
			AspenError::raiseError(new AspenError('Unable to authenticate user with LDAP Server. ' . @ldap_error($this->ldap_client)));
		}
		return false;
	}

	private function login(User $user, $username = ""): bool {
		if($this->ssoAuthOnly) {
			$login = UserAccount::loginWithAspen($user);
		} else {
			$_REQUEST['username'] = $username;
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

	private function selfRegister($attributes): bool {
		$catalogConnection = CatalogFactory::getCatalogConnectionInstance();
		$userAttributes = $this->setupUser($attributes);
		$selfReg = $catalogConnection->selfRegister(true, $userAttributes);
		if($selfReg['success'] != '1') {
			return false;
		}
		return true;
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

	private function setupUser($user):array {
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

	private function createNewAspenUser($user) {
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

	function launch() {}

	function getBreadcrumbs(): array {
		return [];
	}
}