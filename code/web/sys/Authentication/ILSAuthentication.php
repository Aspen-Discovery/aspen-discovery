<?php
require_once 'Authentication.php';
require_once ROOT_DIR . '/CatalogConnection.php';

class ILSAuthentication implements Authentication {
	private $username;
	private $password;
	private $driverName;
	/** @var  AccountProfile */
	private $accountProfile;
	private $catalogConnection;

	public function __construct($additionalInfo) {
		if (array_key_exists('driver', $additionalInfo)) {
			$this->driverName = $additionalInfo['driver'];
			$this->accountProfile = $additionalInfo['accountProfile'];
		} else {
			global $configArray;
			$this->driverName = $configArray['Catalog']['driver'];
		}
		$this->catalogConnection = CatalogFactory::getCatalogConnectionInstance($this->driverName, $this->accountProfile);
	}

	/**
	 * @param bool $validatedViaSSO
	 * @return AspenError|User|false
	 */
	public function authenticate($validatedViaSSO) {
		global $logger;
		//Check to see if the username and password are provided
		if (!array_key_exists('username', $_REQUEST) && !array_key_exists('password', $_REQUEST)) {
			$logger->log("Username and password not provided, returning user if it exists", Logger::LOG_NOTICE);
			//If not, check to see if we have a valid user already authenticated
			if (UserAccount::isLoggedIn()) { //TODO: prevent in case of masquerade??
				return UserAccount::getLoggedInUser();
			}
		}
		$this->username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
		$this->password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';

		if (is_array($this->username)) {
			$this->username = reset($this->username);
		}
		if (is_array($this->password)) {
			$this->password = reset($this->password);
		}

		$logger->log("Authenticating user '{$this->username}' via the ILS", Logger::LOG_DEBUG);
		if (!$validatedViaSSO && ($this->username == '' || $this->password == '')) {
			$user = new AspenError('Login information cannot be blank.');
		} else {
			// Connect to the correct catalog depending on the driver for this account
			$catalog = $this->catalogConnection;

			if ($catalog->status) {
				/** @var User $patron */
				$patron = $catalog->patronLogin($this->username, $this->password, null, $validatedViaSSO);
				if ($patron && !($patron instanceof AspenError)) {
					$user = $patron;
				} elseif (($patron instanceof AspenError)) {
					$user = $patron;
				} else {
					$user = new AspenError('Sorry that login information was not recognized, please try again.');
				}
			} else {
				$user = new AspenError('We cannot log you in at this time.  Please try again later.');
			}
		}
		return $user;
	}

	public function validateAccount($username, $password, $parentAccount, $validatedViaSSO) {
		global $logger;
		$this->username = $username;
		$this->password = $password;

		$logger->log("validating account for user '{$this->username}' via the ILS", Logger::LOG_DEBUG);
		//Password is not required if we have validated via single sign on or if the user is masquerading
		if ($this->username == '' || ($this->password == '' && !$validatedViaSSO && !UserAccount::isUserMasquerading())) {
			$validUser = new AspenError('Login information cannot be blank.');
		} else {
			// Connect to the correct catalog depending on the driver for this account
			$catalog = CatalogFactory::getCatalogConnectionInstance($this->driverName);

			if ($catalog->status) {
				$patron = $catalog->patronLogin($this->username, $this->password, $parentAccount, $validatedViaSSO);
				if ($patron && !($patron instanceof AspenError)) {
					$validUser = $patron;
				} elseif (($patron instanceof AspenError)) {
					$validUser = $patron;
				} else {
					$validUser = new AspenError('Sorry that login information was not recognized, please try again.');
				}
			} else {
				$validUser = new AspenError('We cannot log you in at this time.  Please try again later.');
			}
		}
		return $validUser;
	}
}