<?php /** @noinspection SpellCheckingInspection */

require_once 'bootstrap.php';
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';
require_once ROOT_DIR . '/sys/Account/AccountProfile.php';

class LDAPAuthentication extends Action {

	static bool $clientInitialized = false;
	protected mixed $ldap_client;
	protected array $ldap_config = [];
	protected bool $ssoAuthOnly = false;

	public function __construct() {
		parent::__construct();
		if(!$this->intializeClient()) {
			AspenError::raiseError(new AspenError(ldap_error($this->ldap_client)));
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
					}
				}

				$this->ldap_client = ldap_connect($this->ldap_config['host']);
				if($this->ldap_client) {
					if(ldap_bind($this->ldap_client, $this->ldap_config['client'], $this->ldap_config['secret'])) {
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
			if ($user) {
				if (ldap_bind($this->ldap_client, $username, $password)) {
					$attributes = ldap_get_attributes($this->ldap_client, $user);
					if($this->ssoAuthOnly === false) {
						if (!$this->validateWithILS($username)) {
							if ($this->selfRegister($attributes)) {
								return $this->validateWithILS($username);
							} else {
								AspenError::raiseError(new AspenError('Unable to register a new account with ILS.'));
							}
						}
					} else {
						if(!$this->validateWithAspen($username)) {
							if($this->createNewAspenUser($attributes)) {
								return $this->validateWithAspen($username);
							} else {
								AspenError::raiseError(new AspenError('Unable to create account with Aspen for new LDAP user.'));
							}
						}
					}
				} else {
					AspenError::raiseError(new AspenError('Unable to authenticate LDAP user. ' . ldap_error($this->ldap_client)));
				}
			}
			AspenError::raiseError(new AspenError('Unable to find user with that username in LDAP. ' . ldap_error($this->ldap_client)));
		}
		AspenError::raiseError(new AspenError(ldap_error($this->ldap_client)));
		return false;
	}

	private function findUser($username): mixed {
		if($this->ldap_config['ou']) {
			return ldap_search($this->ldap_client, $this->ldap_config['baseDir'], '(&(' . $this->ldap_config['idAttr'] . '=' . $username . ')(ou=' . $this->ldap_config['ou'] . '))');
		}
		return ldap_search($this->ldap_client, $this->ldap_config['baseDir'], $this->ldap_config['idAttr'] . '=' . $username);
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
		$user = UserAccount::findNewAspenUser('user_id', $username);

		if(!$user instanceof User){
			return false;
		}

		return $this->login($user);
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
		$selfReg = $catalogConnection->selfRegister(true, $this->setupUser($attributes));
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
		return [
			'email' => $this->searchArray($user, 'email'),
			'firstname' => $this->searchArray($user, 'firstname'),
			'lastname' => $this->searchArray($user, 'lastname'),
			'cat_username' => $this->searchArray($user, 'username'),
			'category_id' => $this->searchArray($user, 'patronType'),
		];
	}

	private function createNewAspenUser($user) {
		global $library;
		$tmpUser = new User();
		$tmpUser->email = $this->searchArray($user, 'email');
		$tmpUser->firstname = $this->searchArray($user, 'firstname');
		$tmpUser->lastname = $this->searchArray($user, 'lastname') ?? '';
		$tmpUser->username = $this->searchArray($user, 'username');
		$tmpUser->phone = '';
		$tmpUser->displayName = '';
		$tmpUser->patronType = '';
		$tmpUser->trackReadingHistory = false;

		$location = new Location();
		$location->libraryId = $library->libraryId;
		$location->orderBy('isMainBranch desc');
		if (!$location->find(true)) {
			$tmpUser->homeLocationId = 0;
		} else {
			$tmpUser->homeLocationId = $location->code;
		}
		$tmpUser->myLocation1Id = 0;
		$tmpUser->myLocation2Id = 0;
		$tmpUser->created = date('Y-m-d');
		if(!$tmpUser->insert()) {
			global $logger;
			$logger->log('Error creating Aspen user ' . print_r($this->searchArray($user, 'username'), true), Logger::LOG_ERROR);
			return false;
		}

		return UserAccount::findNewAspenUser('user_id', $this->searchArray($user, 'username'));
	}

	public function searchArray($array, $needle) {
		$result = false;
		foreach ($array as $obj) {
			if (is_array($obj)) {
				foreach ($obj as $n) {
					if (array_key_exists($needle, $n)) {
						$result = $n[$needle];
						break;
					}
				}
			} else {
				if (array_key_exists($needle, $obj)) {
					$result = $obj[$needle];
					break;
				}
			}
		}
		return $result;
	}

	function launch() {}

	function getBreadcrumbs(): array {
		return [];
	}
}