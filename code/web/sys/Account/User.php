<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class User extends DataObject {
	public $__table = 'user'; // table name
	public $id;
	public $source;
	public $username;
	public $unique_ils_id;
	public $cat_username; //Old field for barcode/username from the ILS deprecated
	public $cat_password; //Old field barcode/username from the ILS deprecated
	public $ils_barcode; //The barcode for the user as stored within the ILS. Will be null for admin users and users not stored in the ils
	public $ils_username; //A custom username for the user as stored within the ILS that can be used for login rather than using the barcode.
	public $ils_password; //The password to use when logging in
	public $displayName;
	public $password;
	public $firstname;
	public $lastname;
	public $email;
	public $phone;
	public $patronType;
	public $created; // datetime(19) not_null binary
	public $homeLocationId; // int(11)
	public $myLocation1Id; // int(11)
	public $myLocation2Id; // int(11)
	public $trackReadingHistory; // tinyint
	public $initialReadingHistoryLoaded;
	public $lastReadingHistoryUpdate;
	public $bypassAutoLogout; //tinyint
	public $disableRecommendations; //tinyint
	public $disableCoverArt; //tinyint
	public $overdriveEmail;
	public $promptForOverdriveEmail; //Semantics of this have changed to not prompting for hold settings
	public $hooplaCheckOutConfirmation;
	public $promptForAxis360Email;
	public $axis360Email;
	public $preferredLibraryInterface;
	public $preferredTheme;
	public $noPromptForUserReviews; //tinyint(1)
	public $lockedFacets;
	public $alternateLibraryCard;
	public $alternateLibraryCardPassword;
	public $hideResearchStarters;
	public $disableAccountLinking;
	public $disableCirculationActions;
	public $oAuthAccessToken;
	public $oAuthRefreshToken;
	public $isLoggedInViaSSO;
	public $userCookiePreferenceEssential;
	public $userCookiePreferenceAnalytics;
	public $userCookiePreferenceLocalAnalytics;
	public $holdInfoLastLoaded;
	public $checkoutInfoLastLoaded;
	public $optInToAllCampaignLeaderboards;
	public $campaignNotificationsByEmail;

	public $onboardAppNotifications;
	public $shouldAskBrightness;

	/** @var Role[] */
	private $_roles;
	private $_permissions;
	private $_masqueradingRoles;
	private $_additionalAdministrationLocations;

	public $interfaceLanguage;
	public $searchPreferenceLanguage;

	public $rememberHoldPickupLocation;
	public $pickupLocationId;
	public $pickupSublocationId;

	public $lastListUsed;
	public $browseAddToHome;

	public $lastLoginValidation;

	public $twoFactorStatus; //Whether the user has enrolled

	public $updateMessage;
	public $updateMessageIsError;

	public $proPayPayerAccountId;

	public $enableCostSavings;
	public $totalCostSavings;
	public $currentCostSavings;

	public $isLocalTestUser;

	/** @var User $parentUser */
	private $parentUser;
	/** @var User[] $linkedUsers */
	private $linkedUsers;
	private $viewers;

	//Data that we load, but don't store in the User table
	public $_fullname;
	public $_preferredName;
	public $_address1;
	public $_address2;
	public $_city;
	public $_state;
	public $_zip;
	public $_workPhone;
	public $_mobileNumber;
	public $_web_note;
	public $_expires;
	public $_expired;
	public $_expireClose;
	public $_isBlockedFromIllRequests;
	public $_fines;
	public $_finesVal;
	public $_homeLibrary;
	public $_homeLocationCode;
	public $_homeLocation;
	public $_myLocation1;
	public $_myLocation2;
	public $_numCheckedOutIls;
	public $_numHoldsIls;
	public $_numHoldsAvailableIls;
	public $_numHoldsRequestedIls;
	private $_numCheckedOutOverDrive = 0;
	private $_numHoldsOverDrive = 0;
	private $_numHoldsAvailableOverDrive = 0;
	private $_numCheckedOutHoopla = 0;
	public $_notices;
	public $_billingNotices = "-";
	public $_noticePreferenceLabel;
	private $_numMaterialsRequests = 0;
	private $_readingHistorySize = 0;
	public $_dateOfBirth;


	// CarlX Option
	public $_emailReceiptFlag;
	public $_availableHoldNotice;
	public $_comingDueNotice;
	public $_phoneType;

	//Staff Settings
	public $materialsRequestEmailSignature;
	public $materialsRequestReplyToAddress;
	public $materialsRequestSendEmailOnAssign;

	function getNumericColumnNames(): array {
		return [
			'id',
			'trackReadingHistory',
			'hooplaCheckOutConfirmation',
			'initialReadingHistoryLoaded',
			'updateMessageIsError',
			'rememberHoldPickupLocation',
			'materialsRequestSendEmailOnAssign',
			'isLocalTestUser'
		];
	}

	function getEncryptedFieldNames(): array {
		return [
			'password',
			'cat_password',
			'ils_password',
			'firstname',
			'lastname',
			'email',
			'displayName',
			'phone',
			'overdriveEmail',
			'alternateLibraryCardPassword',
			'axis360Email',
		];
	}

	public function getUniquenessFields(): array {
		return [
			'source',
			'username',
		];
	}

	function getLists() {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';

		$lists = [];

		$list = new UserList();
		$list->user_id = $this->id;
		$list->orderBy('title');
		if ($list->find()) {
			while ($list->fetch()) {
				$lists[] = clone($list);
			}
		}

		return $lists;
	}

	protected ?CatalogConnection $_catalogDriver = null;

	/**
	 * Get a connection to the catalog for the user
	 *
	 * @return null|CatalogConnection
	 */
	function getCatalogDriver(): ?CatalogConnection {
		if ($this->_catalogDriver == null) {
			//Based off the source of the user, get the AccountProfile
			$accountProfile = $this->getAccountProfile();
			if ($accountProfile) {
				$catalogDriver = trim($accountProfile->driver);
				if (!empty($catalogDriver)) {
					$this->_catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
				}
			}
		}
		return $this->_catalogDriver;
	}

	function hasIlsConnection() {
		$driver = $this->getCatalogDriver();
		if ($driver == null) {
			return false;
		} else {
			if ($driver->driver == null) {
				return false;
			}
		}
		return true;
	}

	/** @var AccountProfile */
	protected $_accountProfile;

	/**
	 * @return AccountProfile
	 */
	function getAccountProfile() {
		if ($this->_accountProfile != null) {
			return $this->_accountProfile;
		}
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->name = $this->source;
		if ($accountProfile->find(true)) {
			$this->_accountProfile = $accountProfile;
		} else {
			$this->_accountProfile = null;
		}
		return $this->_accountProfile;
	}

	function __get($name) {
		if ($name == 'roles') {
			return $this->getRoles();
		} elseif ($name == 'linkedUsers') {
			return $this->getLinkedUsers();
		} elseif ($name == 'additionalAdministrationLocations') {
			return $this->getAdditionalAdministrationLocations();
		} elseif ($name == 'homeLibraryName') {
			return $this->getHomeLibrarySystemName();
		} elseif ($name == 'homeLocation') {
			return $this->getHomeLocationName();
		} else {
			return parent::__get($name);
		}
	}

	function __set($name, $value) {
		if ($name == 'roles') {
			$this->setRoles($value);
		} elseif ($name == 'additionalAdministrationLocations') {
			$this->setAdditionalAdministrationLocations($value);
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete($useWhere = false): int {
		$ret = parent::delete($useWhere);
		if ($ret) {
			// delete browse_category_dismissal
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissals = new BrowseCategoryDismissal();
			$browseCategoryDismissals->userId = $this->id;
			$browseCategoryDismissals->find();
			while ($browseCategoryDismissals->fetch()) {
				$browseCategoryDismissals->delete();
			}

			// delete placard_dismissal
			require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardDismissal.php';
			$placardDismissals = new PlacardDismissal();
			$placardDismissals->userId = $this->id;
			$placardDismissals->find();
			while ($placardDismissals->fetch()) {
				$placardDismissals->delete();
			}

			// delete system_message_dismissal
			require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessageDismissal.php';
			$systemMessageDismissals = new SystemMessageDismissal();
			$systemMessageDismissals->userId = $this->id;
			$systemMessageDismissals->find();
			while ($systemMessageDismissals->fetch()) {
				$systemMessageDismissals->delete();
			}

			// delete user_checkout
			require_once ROOT_DIR . '/sys/User/Checkout.php';
			$userCheckouts = new Checkout();
			$userCheckouts->userId = $this->id;
			$userCheckouts->find();
			while ($userCheckouts->fetch()) {
				$userCheckouts->delete();
			}

			// delete user_events_entry
			require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
			$userEventsEntry = new UserEventsEntry();
			$userEventsEntry->userId = $this->id;
			$userEventsEntry->find();
			while ($userEventsEntry->fetch()) {
				$userEventsEntry->delete();
			}

			// delete user_events_registration
			require_once ROOT_DIR . '/sys/Events/UserEventsRegistrations.php';
			$userEventsRegistration = new UserEventsRegistrations();
			$userEventsRegistration->userId = $this->id;
			$userEventsRegistration->find();
			while ($userEventsRegistration->fetch()) {
				$userEventsRegistration->delete();
			}

			// delete user_hold
			require_once ROOT_DIR . '/sys/User/Hold.php';
			$userHolds = new Hold();
			$userHolds->userId = $this->id;
			$userHolds->find();
			while ($userHolds->fetch()) {
				$userHolds->delete();
			}

			// delete user_ils_message
			require_once ROOT_DIR . '/sys/Account/UserILSMessage.php';
			$userILSMessage = new UserILSMessage();
			$userILSMessage->userId = $this->id;
			$userILSMessage->find();
			while ($userILSMessage->fetch()) {
				$userILSMessage->delete();
			}

			// delete user_link
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->find();
			while ($userLink->fetch()) {
				$userLink->delete();
			}

			$userLink = new UserLink();
			$userLink->linkedAccountId = $this->id;
			$userLink->find();
			while ($userLink->fetch()) {
				$userLink->delete();
			}

			// delete user_list
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$userList = new UserList();
			$userList->user_id = $this->id;
			$userList->find();
			while ($userList->fetch()) {
				// delete user_list_entry
				$userListEntry = new UserListEntry();
				$userListEntry->listId = $userList->id;
				$userListEntry->find();
				while ($userListEntry->fetch()) {
					$userListEntry->delete();
				}
				$userList->delete();
			}

			// delete user_messages
			require_once ROOT_DIR . '/sys/Account/UserMessage.php';
			$userMessage = new UserMessage();
			$userMessage->userId = $this->id;
			$userMessage->find();
			while ($userMessage->fetch()) {
				$userMessage->delete();
			}

			// delete user_not_interested
			require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
			$userNotInterested = new NotInterested();
			$userNotInterested->userId = $this->id;
			$userNotInterested->find();
			while ($userNotInterested->fetch()) {
				$userNotInterested->delete();
			}

			// delete user_notification_tokens
			require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
			$userNotificationToken = new UserNotificationToken();
			$userNotificationToken->userId = $this->id;
			$userNotificationToken->find();
			while ($userNotificationToken->fetch()) {
				$userNotificationToken->delete();
			}

			// delete user_notifications
			require_once ROOT_DIR . '/sys/Account/UserNotification.php';
			$userNotification = new UserNotification();
			$userNotification->userId = $this->id;
			$userNotification->find();
			while ($userNotification->fetch()) {
				$userNotification->delete();
			}

			// delete user_reading_history_work
			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$userReadingHistory = new ReadingHistoryEntry();
			$userReadingHistory->userId = $this->id;
			$userReadingHistory->find();
			while ($userReadingHistory->fetch()) {
				$userReadingHistory->delete();
			}

			// delete user_roles
			require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
			$userRoles = new UserRoles();
			$userRoles->userId = $this->id;
			$userRoles->find();
			while ($userRoles->fetch()) {
				$userRoles->delete();
			}

			// delete materials_request (createdBy)
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
			$userMaterialsRequests = new MaterialsRequest();
			$userMaterialsRequests->createdBy = $this->id;
			$userMaterialsRequests->find();
			while ($userMaterialsRequests->fetch()) {
				$userMaterialsRequests->delete();
			}

		}
		return $ret;
	}

	function setRoles($values) {
		$rolesToAssign = [];
		foreach ($values as $index => $value) {
			if (is_object($value)) {
				$rolesToAssign[$index] = $value;
			} else {
				$role = new Role();
				$role->roleId = $value;
				if ($role->find(true)) {
					$rolesToAssign[$role->roleId] = clone $role;
				}
			}
		}
		$this->_roles = $rolesToAssign;
		//Update the database, first remove existing values
		$this->saveRoles();
	}

	function getRoles(): array {
		if (is_null($this->_roles)) {
			$this->_roles = [];
			//Load roles for the user from the user
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			require_once ROOT_DIR . '/sys/Account/PType.php';
			$role = new Role();
			$canUseTestRoles = false;
			if ($this->id) {
				//Get role based on patron type
				$patronType = $this->getPTypeObj();
				if (!empty($patronType)) {
					if ($patronType->assignedRoleId != -1) {
						$role = new Role();
						$role->roleId = $patronType->assignedRoleId;
						if ($role->find(true)) {
							$role->setAssignedFromPType(true);
							$this->_roles[$role->roleId] = clone $role;
							if ($this->_roles[$role->roleId]->hasPermission('Test Roles')) {
								$canUseTestRoles = true;
							}
						}
					}
				}

				$escapedId = $this->escape($this->id);
				/** @noinspection SqlResolve */
				$role->query("SELECT roles.* FROM roles INNER JOIN user_roles ON roles.roleId = user_roles.roleId WHERE userId = " . $escapedId . " ORDER BY name");
				while ($role->fetch()) {
					$this->_roles[$role->roleId] = clone $role;
					if ($this->_roles[$role->roleId]->hasPermission('Test Roles')) {
						$canUseTestRoles = true;
					}
				}
			}

			//Set up a test role if provided
			$testRole = '';
			if (isset($_REQUEST['test_role'])) {
				$testRole = $_REQUEST['test_role'];
			} elseif (isset($_COOKIE['test_role'])) {
				$testRole = $_COOKIE['test_role'];
			}
			if ($canUseTestRoles && $testRole != '') {
				if (is_array($testRole)) {
					$testRoles = $testRole;
				} else {
					$testRoles = [$testRole];
				}
				foreach ($testRoles as $tmpRole) {
					$role = new Role();
					if (is_numeric($tmpRole)) {
						$role->roleId = $tmpRole;
					} else {
						$role->name = $tmpRole;
					}
					$found = $role->find(true);
					if ($found == true) {
						$this->_roles[$role->roleId] = clone $role;
					}
				}
			}
		}

		return $this->_roles;
	}

	function setAdditionalAdministrationLocations($values): void {
		$this->_additionalAdministrationLocations = $values;

		//Update the database, first remove existing values
		$this->saveAdditionalAdministrationLocations();
	}

	function getAdditionalAdministrationLocations(): array {
		if (is_null($this->_additionalAdministrationLocations)) {
			$this->_additionalAdministrationLocations = [];
			require_once ROOT_DIR . '/sys/Administration/AdministrationLocation.php';

			if (!empty($this->id)) {
				$locationsList = Location::getLocationList(false);
				$administrationLocation = new AdministrationLocation();
				$administrationLocation->userId = $this->id;
				$administrationLocation->find();
				while ($administrationLocation->fetch()) {
					$this->_additionalAdministrationLocations[$administrationLocation->locationId] = $locationsList[$administrationLocation->locationId];
				}
			}
		}

		return $this->_additionalAdministrationLocations;
	}

	function saveAdditionalAdministrationLocations(): void {
		if (!empty($this->id) && isset($this->_additionalAdministrationLocations) && is_array($this->_additionalAdministrationLocations)) {
			require_once ROOT_DIR . '/sys/Administration/AdministrationLocation.php';
			$userAdministrationLocations = new AdministrationLocation();
			$userAdministrationLocations->userId = $this->id;
			$existingLocations = [];
			$userAdministrationLocations->find();
			while ($userAdministrationLocations->fetch()) {
				$existingLocations[$userAdministrationLocations->locationId] = $userAdministrationLocations->locationId;
			}

			if (count($this->_additionalAdministrationLocations) > 0) {
				foreach ($this->_additionalAdministrationLocations as $locationId) {
					if (!array_key_exists($locationId, $existingLocations)) {
						$administrationLocation = new AdministrationLocation();
						$administrationLocation->userId = $this->id;
						$administrationLocation->locationId = $locationId;
						$administrationLocation->insert();
					} else {
						unset($existingLocations[$locationId]);
					}
				}
			}

			//delete any roles that no longer exist.
			foreach ($existingLocations as $existingLocation) {
				$administrationLocation = new AdministrationLocation();
				$administrationLocation->userId = $this->id;
				$administrationLocation->locationId = $existingLocation;
				$administrationLocation->delete(true);
			}
		}
	}

	/**
	 * @return Role[]
	 */
	public function getRolesAssignedByPType(): array {
		$rolesAssignedByPType = [];
		if ($this->id) {
			//Get the user role based on their patron type
			$patronType = $this->getPTypeObj();
			if (!empty($patronType)) {
				if ($patronType->assignedRoleId != -1) {
					$role = new Role();
					$role->roleId = $patronType->assignedRoleId;
					if ($role->find(true)) {
						$role->setAssignedFromPType(true);
						$rolesAssignedByPType[$role->roleId] = clone $role;
					}
				}
			}
		}
		return $rolesAssignedByPType;
	}

	function getBarcode() {
		return $this->ils_barcode;
	}

	function getPasswordOrPin() {
		if ($this->getAccountProfile()->authenticationMethod == 'db') {
			return empty($this->password) ? '' : $this->password;
		}else{
			return empty($this->ils_password) ? '' : $this->ils_password;
		}
	}

	function getPasswordOrPinField() {
		return 'ils_password';
	}

	function getBarcodeField() {
		return 'ils_barcode';
	}

	function getAlternateLibraryCardBarcode() {
		return empty($this->alternateLibraryCard) ? '' : $this->alternateLibraryCard;
	}

	function getAlternateLibraryCardPasswordOrPin() {
		return empty($this->alternateLibraryCardPassword) ? '' : $this->alternateLibraryCardPassword;
	}

	function saveRoles() {
		if (isset($this->id) && isset($this->_roles) && is_array($this->_roles)) {
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
			$userRoles = new UserRoles();
			$userRoles->userId = $this->id;
			$existingRoles = [];
			$userRoles->find();
			while ($userRoles->fetch()) {
				$existingRoles[$userRoles->roleId] = $userRoles->roleId;
			}

			//$userRoles->delete(true);

			$changesMade = false;
			$message = '';
			//Now add the new values.
			if (count($this->_roles) > 0) {
				foreach ($this->_roles as $roleObj) {
					if (!$roleObj->isAssignedFromPType()) {
						if (!array_key_exists($roleObj->roleId, $existingRoles)) {
							$userRoles = new UserRoles();
							$userRoles->userId = $this->id;
							$userRoles->roleId = $roleObj->roleId;
							$userRoles->insert();
							$changesMade = true;
						} else {
							unset($existingRoles[$roleObj->roleId]);
						}
					}
				}
			}

			//delete any roles that no longer exist.
			foreach ($existingRoles as $existingRole) {
				$userRoles = new UserRoles();
				$userRoles->userId = $this->id;
				$userRoles->roleId = $existingRole;
				$userRoles->delete(true);
				$changesMade = true;
			}

			if ($changesMade) {
				//Check to see if we have any roles set by PType and warn the user
				$rolesAssignedByPType = $this->getRolesAssignedByPType();
				if (count($rolesAssignedByPType) > 0) {
					foreach ($rolesAssignedByPType as $role) {
						$message .= "Role {$role->name} is defined by PType <br/>";
					}
					UserAccount::getActiveUserObj()->updateMessage .= $message;
					UserAccount::getActiveUserObj()->update();
				}
				unset ($this->_roles);
			}
		}
	}

	/**
	 * @return User[]
	 */
	function getLinkedUsers() {
		if (is_null($this->linkedUsers)) {
			$this->linkedUsers = [];
			/* var Library $library */ global $library;
			global $memCache;
			global $serverName;
			global $logger;
			if ($this->id && $library->allowLinkedAccounts) {
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->primaryAccountId = $this->id;
				try {
					$userLink->find();
					while ($userLink->fetch()) {
						if (!$this->isBlockedAccount($userLink->linkedAccountId)) {
							$linkedUser = new User();
							$linkedUser->id = $userLink->linkedAccountId;
							if ($linkedUser->find(true)) {
								/** @var User $userData */ //$userData = $memCache->get("user_{$serverName}_{$linkedUser->id}");
								//if ($userData === false || isset($_REQUEST['reload'])) {
								//Load full information from the catalog
								$linkedUser = UserAccount::validateAccount($linkedUser->ils_barcode, $linkedUser->ils_password, $linkedUser->source, $this);
								//} else {
								//	$logger->log("Found cached linked user {$userData->id}", Logger::LOG_DEBUG);
								//	$linkedUser = $userData;
								//}
								if ($linkedUser && !($linkedUser instanceof AspenError)) {
									$this->linkedUsers[] = clone($linkedUser);
								}
							}
						}
					}
				} catch (PDOException $e) {
					//Disabling of linking has not been enabled yet.
				}
			}
		}
		return $this->linkedUsers;
	}

	private $linkedUserObjects;

	function getLinkedUserObjects() {
		if (is_null($this->linkedUserObjects)) {
			$this->linkedUserObjects = [];
			try {
				/* var Library $library */ global $library;
				if ($this->id && $library->allowLinkedAccounts) {
					require_once ROOT_DIR . '/sys/Account/UserLink.php';
					$userLink = new UserLink();
					$userLink->primaryAccountId = $this->id;
					$userLink->find();
					while ($userLink->fetch()) {
						if (!$this->isBlockedAccount($userLink->linkedAccountId)) {
							$linkedUser = new User();
							$linkedUser->id = $userLink->linkedAccountId;
							if ($linkedUser->find(true)) {
								/** @var User $userData */
								$this->linkedUserObjects[] = clone($linkedUser);
							}
						}
					}
				}
			} catch (Exception $e) {
				//Tables are likely not fully updated
				global $logger;
				$logger->log("Error loading linked users $e", Logger::LOG_ERROR);
			}
		}
		return $this->linkedUserObjects;
	}

	public function setParentUser($user) {
		$this->parentUser = $user;
	}

	// Account Blocks //
	private $blockAll = null; // set to null to signal unset, boolean when set
	private array|null $blockedAccounts = null; // set to null to signal unset, array when set

	/**
	 * Checks if there is any settings disallowing the account $accountIdToCheck to be linked to this user.
	 *
	 * @param $accountIdToCheck string linked account Id to check for blocking
	 * @return bool true for blocking, false for no blocking
	 */
	public function isBlockedAccount($accountIdToCheck) {
		if (is_null($this->blockAll)) {
			$this->setAccountBlocks();
		}
		return $this->blockAll || in_array($accountIdToCheck, $this->blockedAccounts);
	}

	private function setAccountBlocks() {
		// default settings
		$this->blockAll = false;
		$this->blockedAccounts = [];

		require_once ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php';
		$accountBlock = new BlockPatronAccountLink();
		$accountBlock->primaryAccountId = $this->id;
		if ($accountBlock->find()) {
			while ($accountBlock->fetch(false)) {
				if ($accountBlock->blockLinking) {
					$this->blockAll = true;
				} // any one row that has block all on will set this setting to true for this account.
				if ($accountBlock->blockedLinkAccountId) {
					$this->blockedAccounts[] = $accountBlock->blockedLinkAccountId;
				}
			}
		}
	}

	/**
	 * @param string $source
	 * @return User[]
	 */
	function getRelatedEcontentUsers($source) {
		$users = [];
		if ($this->isValidForEContentSource($source)) {
			$users[$this->ils_barcode . ':' . $this->ils_password] = $this;
		}
		foreach ($this->getLinkedUsers() as $linkedUser) {
			if ($linkedUser->isValidForEContentSource($source)) {
				if (!array_key_exists($linkedUser->ils_barcode . ':' . $linkedUser->ils_password, $users)) {
					$users[$linkedUser->ils_barcode . ':' . $linkedUser->ils_password] = $linkedUser;
				}
			}
		}

		return $users;
	}

	function isValidForEContentSource($source) {
		global $enabledModules;
		if ($this->parentUser == null || ($this->getBarcode() != $this->parentUser->getBarcode())) {
			$userHomeLibrary = Library::getPatronHomeLibrary($this);
			if ($userHomeLibrary) {
				if ($source == 'overdrive') {
					if (empty($this->getBarcode())) {
						return false;
					} else if (array_key_exists('OverDrive', $enabledModules)) {
						$overDriveSettings = $userHomeLibrary->getLibraryOverdriveSettings();
						foreach ($overDriveSettings as $libraryOverDriveSetting) {
							if ($libraryOverDriveSetting->circulationEnabled) {
								return true;
							}
						}
						return false;
					} else {
						return false;
					}
				} elseif ($source == 'hoopla') {
					return array_key_exists('Hoopla', $enabledModules) && $userHomeLibrary->hooplaLibraryID > 0;
				} elseif ($source == 'cloud_library') {
					return array_key_exists('Cloud Library', $enabledModules) && ($userHomeLibrary->cloudLibraryScope > 0);
				} elseif ($source == 'axis360') {
					return array_key_exists('Axis 360', $enabledModules) && ($userHomeLibrary->axis360ScopeId > 0);
				} elseif ($source == 'palace_project') {
					return array_key_exists('Palace Project', $enabledModules) && ($userHomeLibrary->palaceProjectScopeId > 0);
				}
			}
		}
		return false;
	}

	/**
	 * @return OverDriveSetting[]
	 */
	function getAvailableOverDriveSettings(string $readerName): array {
		$overDriveUsers = $this->getRelatedEcontentUsers('overdrive');

		$relatedLibraries = [];
		foreach ($overDriveUsers as $overDriveUser) {
			$userLibrary = $overDriveUser->getHomeLibrary();
			$relatedLibraries[$userLibrary->libraryId] = $userLibrary;
		}

		$overDriveSettings = [];
		foreach ($relatedLibraries as $library) {
			foreach ($library->getLibraryOverDriveSettings() as $libraryOverDriveSetting) {
				if (!empty($libraryOverDriveSetting->getOverDriveSettings())) {
					if (empty($readerName) || $libraryOverDriveSetting->getOverDriveSettings()->readerName == $readerName) {
						$overDriveSettings[$libraryOverDriveSetting->id] = $libraryOverDriveSetting;
					}
				}
			}
		}

		return $overDriveSettings;
	}

	function hasInterlibraryLoan(): bool {
		try {
			$homeLocation = Location::getDefaultLocationForUser();
			if ($homeLocation != null) {
				//Check to see if local ILL is available
				$parentLibrary = $homeLocation->getParentLibrary();
				if ($parentLibrary != null) {
					if ($parentLibrary->localIllRequestType != 0) {
						if ($homeLocation->localIllFormId > 0) {
							return true;
						}
					}
				}
				//Local ILL is not available, check to see if VDX is available.
				require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
				require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
				$vdxSettings = new VdxSetting();
				if ($vdxSettings->find(true)) {
					//Get configuration for the form.
					if ($homeLocation->vdxFormId != -1) {
						return true;
					}
				}
			}
		} catch (Exception $e) {
			//This happens if the tables aren't setup, ignore
		}
		return false;
	}

	function getInterlibraryLoanType(): string {
		$homeLocation = Location::getDefaultLocationForUser();
		if ($homeLocation != null) {
			return $homeLocation->getInterlibraryLoanType();
		} else {
			return 'none';
		}
	}

	/**
	 * Returns a list of users that can view this account
	 *
	 * @return User[]
	 */
	/** @noinspection PhpUnused */
	function getViewers() {
		if (is_null($this->viewers)) {
			$this->viewers = [];
			/* var Library $library */ global $library;
			if ($this->id && $library->allowLinkedAccounts) {
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->linkedAccountId = $this->id;
				$userLink->find();
				while ($userLink->fetch()) {
					$linkedUser = new User();
					$linkedUser->id = $userLink->primaryAccountId;
					if ($linkedUser->find(true)) {
						if (!$linkedUser->isBlockedAccount($this->id)) {
							$this->viewers[] = clone($linkedUser);
						}
					}
				}
			}
		}
		return $this->viewers;
	}

	/**
	 * @param User $linkedUser
	 *
	 * @return boolean
	 */
	function addLinkedUser(User $linkedUser) : bool {
		/* var Library $library */ global $library;
		if ($library->allowLinkedAccounts && $linkedUser->id != $this->id) { // library allows linked accounts and the account to link is not itself
			$linkedUsers = $this->getLinkedUsers();
			foreach ($linkedUsers as $existingUser) {
				if ($existingUser->id == $linkedUser->id) {
					//We already have a link to this user
					return true;
				}
			}

			// Check for Account Blocks
			if ($this->isBlockedAccount($linkedUser->id)) {
				return false;
			}

			//Check to make sure the account we are linking to allows linking
			$linkLibrary = $linkedUser->getHomeLibrary();
			if (!$linkLibrary->allowLinkedAccounts) {
				return false;
			}

			// Add Account Link
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->linkedAccountId = $linkedUser->id;
			if ($userLink->insert()) {
				$this->linkedUsers[] = clone($linkedUser);

				/* Send all the things to the user who was linked to */
				$linkedUser->newLinkMessage(); // Display alert in Aspen Discovery to the linked user
				$linkedUser->sendNewLinkNotification($this); // Send Aspen LiDA notification to the linked user

				return true;
			}
		}
		return false;
	}

	function removeLinkedUser($userId) : bool {
		/* var Library $library */ global $library;
		if ($library->allowLinkedAccounts) {
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->linkedAccountId = $userId;
			$ret = $userLink->delete(true);

			//Force a reload of data
			$this->linkedUsers = null;
			$this->getLinkedUsers();

			return $ret == 1;
		}
		return false;
	}

	/**
	 * Remove managing account by the linked user
	 **/
	function removeManagingAccount($managingAccount) {
		require_once ROOT_DIR . '/sys/Account/UserLink.php';
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';

		$userLink = new UserLink();
		$userLink->primaryAccountId = $managingAccount;
		$userLink->linkedAccountId = $this->id;
		if ($userLink->delete(true)) {

			$managingUser = new User();
			$managingUser->id = $managingAccount;
			if ($managingUser->find(true)) {
				/* Send all the things to the managing account that the user removed the link from */
				$managingUser->removeManagingAccountMessage($this); // Display alert in Aspen Discovery
				$managingUser->sendRemoveManagingLinkNotification($this); // Send Aspen LiDA notification

				// Force a reload of data
				$managingUser->linkedUsers = null;
				$managingUser->getLinkedUsers();
			}

			// Force a reload of data
			$this->linkedUsers = null;
			$this->getLinkedUsers();

			return true;
		}

		return false;
	}

	//THIS GETS USED BY TOGGLEACCOUNTLINKING AJAX
	function accountLinkingToggle() {
		require_once ROOT_DIR . '/sys/Account/UserLink.php';
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';

		if ($this->disableAccountLinking == 0) {
			$this->__set('disableAccountLinking', 1);
			//Remove Managing Accounts
			$userLink = new UserLink();
			$userLink->linkedAccountId = $this->id;
			$userLink->find();
			while ($userLink->fetch()) {
				$userLink->delete();

				$userMessage = new UserMessage();
				$userMessage->messageType = 'linked_acct_notify_disabled_' . $this->id;
				$userMessage->userId = $userLink->primaryAccountId;
				$userMessage->isDismissed = "0";
				$userMessage->message = "An account you were previously linked to, $this->displayName, has disabled account linking. To learn more about linked accounts, please visit your <a href='/MyAccount/LinkedAccounts'>Linked Accounts</a> page.";
				$userMessage->update();
			}
			$userMessage = new UserMessage();
			$userMessage->messageType = 'confirm_linked_accts';
			$userMessage->userId = $this->id;
			$userMessage->isDismissed = "0";
			$userMessage->find();
			while ($userMessage->fetch()) {
				$userMessage->isDismissed = 1;
				$userMessage->update();
			}
			//Remove Linked Users
			$userLink = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->delete(true);
		} else {
			$this->__set('disableAccountLinking', 0);
		}
		return $this->update();
	}

	/**
	 * @return int|bool
	 */
	function update($context = '') {
		if (empty($this->created)) {
			$this->__set('created', date('Y-m-d'));
		}
		if ($this->pickupLocationId == 0) {
			$this->__set('pickupLocationId', $this->homeLocationId);
		}
		$this->fixFieldLengths();
		$result = parent::update();
		$this->saveRoles();
		$this->clearCache(); // Every update to object requires clearing the Memcached version of the object
		return $result;
	}

	function insert($context = '') : int {
		if ($this->firstname === null) {
			$this->firstname = '';
		}
		if ($context == 'development') {
			$this->source = 'development';
			$this->homeLocationId = 0;
			$this->displayName = $this->firstname . ' ' . substr($this->lastname, 0, 1) . '.';
			$this->unique_ils_id = '';
		} elseif ($context == 'localAdministrator') {
			$this->source = 'admin';
			$this->homeLocationId = 0;
			$this->displayName = $this->firstname . ' ' . substr($this->lastname, 0, 1) . '.';
			$this->unique_ils_id = '';
		} else {
			if (!isset($this->homeLocationId)) {
				$this->homeLocationId = 0;
				global $logger;
				$logger->log('No Home Location ID was set for newly created user.', Logger::LOG_WARNING);
			} else {
				//Get the home library for the user
				$homeLibrary = $this->getHomeLibrary();
				if ($homeLibrary !== null) {
					$this->enableCostSavings = $homeLibrary->enableCostSavings;
				}
			}
			if (empty($this->pickupLocationId)) {
				$this->pickupLocationId = $this->homeLocationId;
			}
		}
		if (!isset($this->myLocation1Id)) {
			$this->myLocation1Id = 0;
		}
		if (!isset($this->myLocation2Id)) {
			$this->myLocation2Id = 0;
		}
		if (!isset($this->bypassAutoLogout)) {
			$this->bypassAutoLogout = 0;
		}
		if (empty($this->created)) {
			$this->created = date('Y-m-d');
		}
		$this->fixFieldLengths();

		//set default values as needed
		$ret = parent::insert();
		if ($context != 'development') {
			$this->saveRoles();
		}
		$this->saveAdditionalAdministrationLocations();

		$this->clearCache();
		return $ret;
	}

	function hasRole($roleName) : bool {
		$myRoles = $this->__get('roles');
		return in_array($roleName, $myRoles);
	}

	static function getObjectStructure($context = ''): array {
		//Lookup the available roles in the system
		require_once ROOT_DIR . '/sys/Administration/Role.php';
		$roleList = Role::getLookup();

		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries') || UserAccount::userHasPermission('Administer Home Library Locations'));

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the in the system',
			],
			'source' => [
				'property' => 'source',
				'type' => 'label',
				'label' => 'Source Account Profile',
				'description' => 'The source of the user in the system',
			],
			'username' => [
				'property' => 'username',
				'type' => 'text',
				'label' => 'Username',
				'description' => 'The username for the user.',
			],
			'password' => [
				'property' => 'password',
				'type' => 'storedPassword',
				'label' => 'Password',
				'description' => 'The password for the user.',
				'hideInLists' => true,
			],
			'firstname' => [
				'property' => 'firstname',
				'type' => 'label',
				'label' => 'First Name',
				'description' => 'The first name for the user.',
			],
			'lastname' => [
				'property' => 'lastname',
				'type' => 'label',
				'label' => 'Last Name',
				'description' => 'The last name of the user.',
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'label',
				'label' => 'Display Name',
				'description' => 'The display name of the user.',
			],
			'email' => [
				'property' => 'email',
				'type' => 'email',
				'label' => 'Email Address',
				'description' => 'The email for the user.',
			],
			'homeLibraryName' => [
				'property' => 'homeLibraryName',
				'type' => 'label',
				'label' => 'Home Library',
				'description' => 'The library the user belongs to.',
			],
			'homeLocation' => [
				'property' => 'homeLocation',
				'type' => 'label',
				'label' => 'Home Location',
				'description' => 'The branch the user belongs to.',
			],
		];

		$structure['additionalAdministrationLocations'] = [
			'property' => 'additionalAdministrationLocations',
			'type' => 'multiSelect',
			'listStyle' => 'checkbox',
			'values' => $locationList,
			'label' => 'Additional Administration Locations',
			'description' => 'A list of locations the user can administer in addition to their home location.',
		];

		$structure['barcode'] = [
			'property' => 'ils_barcode',
			'type' => 'label',
			'label' => 'Barcode',
			'description' => 'The barcode for the user.',
		];

		$structure['roles'] = [
			'property' => 'roles',
			'type' => 'multiSelect',
			'listStyle' => 'checkbox',
			'values' => $roleList,
			'label' => 'Roles',
			'description' => 'A list of roles that the user has.',
		];

		if ($context == 'development') {
			$structure['firstname']['type'] = 'text';
			$structure['lastname']['type'] = 'text';
			unset($structure['homeLibraryName']);
			$structure['homeLocation']['label'] = 'Primary Location';
			unset($structure['barcode']);
			unset($structure['roles']);
			unset($structure['additionalAdministrationLocations']);
		}else if ($context == 'localAdministrator') {
			$structure['username']['required'] = true;
			$structure['firstname']['type'] = 'text';
			$structure['firstname']['required'] = true;
			$structure['lastname']['type'] = 'text';
			$structure['lastname']['required'] = true;
			$structure['email']['required'] = true;
			$structure['password']['minLength'] = 12;
			$structure['password']['maxLength'] = 50;
			$structure['password']['note'] = translate(['text'=>'A strong password from 12 to 50 characters including at least one uppercase, lowercase, number, and special character (-_~!@#$%^&*.+).', 'isAdminFacing' => true]);
			$structure['password']['serverValidation'] = 'validateStrongPassword';

			unset($structure['homeLibraryName']);
			unset($structure['homeLocation']);
			unset($structure['barcode']);
		} else {
			unset($structure['username']);
			unset($structure['password']);
			unset($structure['email']);
		}

		return $structure;
	}

	function hasRatings() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';

		$rating = new UserWorkReview();
		$rating->whereAdd("userId = {$this->id}");
		$rating->whereAdd('rating > 0'); // Some entries are just reviews (and therefore have a default rating of -1)
		$rating->find();
		if ($rating->getNumResults() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function hasSavedSearches() {
		$searchEntry = new SearchEntry();
		$searchEntry->user_id = $this->id;
		$searchEntry->saved = "1";
		$searchEntry->find();
		if ($searchEntry->getNumResults() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function hasLists() {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$userList = new UserList();
		$userList->user_id = $this->id;
		$userList->deleted = 0;
		$userList->find();
		if ($userList->getNumResults() > 0) {
			return true;
		} else {
			return false;
		}
	}

	private $_runtimeInfoUpdated = false;

	function updateRuntimeInformation() {
		if (!$this->_runtimeInfoUpdated) {
			if ($this->getCatalogDriver()) {
				$this->getCatalogDriver()->updateUserWithAdditionalRuntimeInformation($this);
			}
			$this->_runtimeInfoUpdated = true;
		}
	}

	private $_contactInformationLoaded = false;

	function loadContactInformation() {
		if (!$this->_contactInformationLoaded) {
			if ($this->getCatalogDriver()) {
				$this->getCatalogDriver()->loadContactInformation($this);
			}
			$this->_contactInformationLoaded = true;
		}
	}

	public function allowUpdatesOfPreferredName(): bool {
		if ($this->getCatalogDriver()) {
			return $this->getCatalogDriver()->allowUpdatesOfPreferredName($this);
		} else {
			return false;
		}
	}

	function updateOverDriveOptions() {
		if (isset($_REQUEST['promptForOverdriveEmail']) && ($_REQUEST['promptForOverdriveEmail'] == 'yes' || $_REQUEST['promptForOverdriveEmail'] == 'on')) {
			// if set check & on check must be combined because checkboxes/radios don't report 'offs'
			$this->__set('promptForOverdriveEmail', 1);
		} else {
			$this->__set('promptForOverdriveEmail', 0);
		}
		if (isset($_REQUEST['overdriveEmail'])) {
			$this->__set('overdriveEmail', strip_tags($_REQUEST['overdriveEmail']));
		}
		$this->update();

		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		$overDriveDriver->updateOptions($this);
	}

	function updateHooplaOptions() {
		if (isset($_REQUEST['hooplaCheckOutConfirmation']) && ($_REQUEST['hooplaCheckOutConfirmation'] == 'yes' || $_REQUEST['hooplaCheckOutConfirmation'] == 'on')) {
			// if set check & on check must be combined because checkboxes/radios don't report 'offs'
			$this->__set('hooplaCheckOutConfirmation', 1);
		} else {
			$this->__set('hooplaCheckOutConfirmation', 0);
		}
		$this->update();
	}

	function updateAxis360Options() {
		if (isset($_REQUEST['promptForAxis360Email']) && ($_REQUEST['promptForAxis360Email'] == 'yes' || $_REQUEST['promptForAxis360Email'] == 'on')) {
			// if set check & on check must be combined because checkboxes/radios don't report 'offs'
			$this->__set('promptForAxis360Email', 1);
		} else {
			$this->__set('promptForAxis360Email', 0);
		}
		if (isset($_REQUEST['axis360Email'])) {
			$this->__set('axis360Email', strip_tags($_REQUEST['axis360Email']));
		}
		$this->update();
	}

	function updateStaffSettings() {
		if (isset($_REQUEST['bypassAutoLogout']) && ($_REQUEST['bypassAutoLogout'] == 'yes' || $_REQUEST['bypassAutoLogout'] == 'on')) {
			$this->__set('bypassAutoLogout', 1);
		} else {
			$this->__set('bypassAutoLogout', 0);
		}
		if (isset($_REQUEST['materialsRequestEmailSignature'])) {
			$this->setMaterialsRequestEmailSignature($_REQUEST['materialsRequestEmailSignature']);
		}
		if (isset($_REQUEST['materialsRequestReplyToAddress'])) {
			$this->setMaterialsRequestReplyToAddress($_REQUEST['materialsRequestReplyToAddress']);
		}
		if (isset($_REQUEST['materialsRequestSendEmailOnAssign']) && ($_REQUEST['materialsRequestSendEmailOnAssign'] == 'yes' || $_REQUEST['materialsRequestSendEmailOnAssign'] == 'on')) {
			$this->__set('materialsRequestSendEmailOnAssign', 1);
		} else {
			$this->__set('materialsRequestSendEmailOnAssign', 0);
		}
		$this->update();
	}

	function updateUserPreferences() {
		require_once ROOT_DIR . '/sys/CommunityEngagement/UserCampaign.php';

		// Validate that the input data is correct
		if (isset($_POST['pickupLocation']) && !is_array($_POST['pickupLocation']) && preg_match('/^\d{1,3}$/', $_POST['pickupLocation']) == 0) {
			return [
				'success' => false,
				'message' => 'The preferred pickup branch had an incorrect format.',
			];
		}
		if (isset($_POST['myLocation1']) && !is_array($_POST['myLocation1']) && preg_match('/^\d{1,3}$/', $_POST['myLocation1']) == 0) {
			return [
				'success' => false,
				'message' => 'The 1st location had an incorrect format.',
			];
		}
		if (isset($_POST['myLocation2']) && !is_array($_POST['myLocation2']) && preg_match('/^\d{1,3}$/', $_POST['myLocation2']) == 0) {
			return [
				'success' => false,
				'message' => 'The 2nd location had an incorrect format.',
			];
		}
		if (isset($_POST['pickupSublocation']) && !is_array($_POST['pickupSublocation']) && preg_match('/^\d{1,3}$/', $_POST['pickupSublocation']) == 0) {
			return [
				'success' => false,
				'message' => 'The preferred pickup location had an incorrect format.',
			];
		}

		if (isset($_REQUEST['profileLanguage'])) {
			$this->__set('interfaceLanguage', $_REQUEST['profileLanguage']);
		}
		if (isset($_REQUEST['searchPreferenceLanguage'])) {
			$this->__set('searchPreferenceLanguage', $_REQUEST['searchPreferenceLanguage']);
		}
		if (isset($_REQUEST['preferredTheme'])) {
			$this->__set('preferredTheme', $_REQUEST['preferredTheme']);
		}

		//Make sure the selected location codes are in the database.
		if (isset($_POST['pickupLocation'])) {
			if ($_POST['pickupLocation'] == 0) {
				$this->__set('pickupLocationId', $_POST['pickupLocation']);
			} else {
				$location = new Location();
				$location->get('locationId', $_POST['pickupLocation']);
				if ($location->getNumResults() != 1) {
					return [
						'success' => false,
						'message' => 'The pickup branch could not be found in the database.',
					];
				} else {
					$this->__set('pickupLocationId', $_POST['pickupLocation']);
				}
			}
		}
		if (isset($_POST['myLocation1'])) {
			if ($_POST['myLocation1'] == 0) {
				$this->__set('myLocation1Id', $_POST['myLocation1']);
			} else {
				$location = new Location();
				$location->get('locationId', $_POST['myLocation1']);
				if ($location->getNumResults() != 1) {
					return [
						'success' => false,
						'message' => 'The 1st location could not be found in the database.',
					];
				} else {
					$this->__set('myLocation1Id', $_POST['myLocation1']);
				}
			}
		}
		if (isset($_POST['myLocation2'])) {
			if ($_POST['myLocation2'] == 0) {
				$this->__set('myLocation2Id', $_POST['myLocation2']);
			} else {
				$location = new Location();
				$location->get('locationId', $_POST['myLocation2']);
				if ($location->getNumResults() != 1) {
					return [
						'success' => false,
						'message' => 'The 2nd location could not be found in the database.',
					];
				} else {
					$this->__set('myLocation2Id', $_POST['myLocation2']);
				}
			}
		}
		if (isset($_POST['pickupSublocation'])) {
			if ($_POST['pickupSublocation'] == 0) {
				$this->__set('pickupSublocationId', $_POST['pickupSublocation']);
			} else {
				require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
				$sublocation = new Sublocation();
				$sublocation->get('id', $_POST['pickupSublocation']);
				if ($sublocation->getNumResults() != 1) {
					return [
						'success' => false,
						'message' => 'The pickup location could not be found in the database.',
					];
				} else {
					$this->__set('pickupSublocationId', $_POST['pickupSublocation']);
				}
			}
		}

		//update DB values with current checkbox status in myPreferences
		if (isset ($_COOKIE['cookieConsent'])) {
			setcookie("cookieConsent", "", time() - 3600, "/"); //remove old cookie so new one can be generated on next page load
			$this->__set('userCookiePreferenceEssential', 1);
			$this->__set('userCookiePreferenceAnalytics', (isset($_POST['userCookieAnalytics']) && $_POST['userCookieAnalytics'] == 'on') ? 1 : 0);
			$this->__set('userCookiePreferenceLocalAnalytics', (isset($_POST['userCookieUserLocalAnalytics']) && $_POST['userCookieUserLocalAnalytics']) ? 1 : 0);
		}

		$this->__set('noPromptForUserReviews', (isset($_POST['noPromptForUserReviews']) && $_POST['noPromptForUserReviews'] == 'on') ? 1 : 0);
		$this->__set('rememberHoldPickupLocation', (isset($_POST['rememberHoldPickupLocation']) && $_POST['rememberHoldPickupLocation'] == 'on') ? 1 : 0);
		$this->__set('disableCirculationActions', (isset($_POST['disableCirculationActions']) && $_POST['disableCirculationActions'] == 'on') ? 0 : 1);
		$homeLibrary = $this->getHomeLibrary();
		if ($homeLibrary !== null && $homeLibrary->enableCostSavings) {
			$this->__set('enableCostSavings', (isset($_POST['enableCostSavings']) && $_POST['enableCostSavings'] == 'on') ? 1 : 0);
		}

		global $enabledModules;
		global $library;
		if (array_key_exists('EBSCO EDS', $enabledModules) && !empty($library->edsSettingsId)) {
			$this->__set('hideResearchStarters', (isset($_POST['hideResearchStarters']) && $_POST['hideResearchStarters'] == 'on') ? 1 : 0);
		}

		if ($this->hasEditableUsername()) {
			$result = $this->updateEditableUsername($_POST['username']);
			if ($result['success'] == false) {
				return $result;
			}
		}

		if ($this->getShowAutoRenewSwitch()) {
			$allowAutoRenewal = ($_REQUEST['allowAutoRenewal'] == 'on' || $_REQUEST['allowAutoRenewal'] == 'true');
			$result = $this->updateAutoRenewal($allowAutoRenewal);
			if ($result['success'] == false) {
				return $result;
			}
		}

		$this->__set('optInToAllCampaignLeaderboards', (isset($_POST['optInToAllCampaignLeaderboards']) && $_POST['optInToAllCampaignLeaderboards'] == 'on') ? 1 : 0);
		$this->__set('campaignNotificationsByEmail', (isset($_POST['campaignNotificationsByEmail']) && $_POST['campaignNotificationsByEmail'] == 'on') ? 1 : 0);
		$this->clearCache();
		$saveResult = $this->update();
		if ($saveResult === false) {
			return [
				'success' => false,
				'message' => 'Could not save to the database',
			];
		} else {
			return [
				'success' => true,
				'message' => 'Your preferences were updated successfully',
			];
		}
	}

	/**
	 * Clear out the cached version of the patron profile.
	 */
	function clearCache() {
		global $memCache;
		global $serverName;
		$memCache->delete("user_{$serverName}_" . $this->id); // now stored by User object id column
	}

	/**
	 * @param $list UserList object of the user list to check permission for
	 * @return bool true if this user can edit passed list
	 */
	function canEditList($list) {
		if (($this->id == $list->user_id) || $this->hasPermission('Edit All Lists')) {
			return true;
		}
		return false;
	}

	/**
	 * Returns the home library instance for the patron if known. The home library may be null for users not connected to an ILS.
	 *
	 * @param bool $forceReload whether or not a previously loaded home library can be used.
	 *        Useful to set in instance when the library is known to be changing.
	 * @return Library|null
	 */
	function getHomeLibrary(bool $forceReload = false): ?Library {
		if ($this->_homeLibrary == null || $forceReload) {
			$this->_homeLibrary = Library::getPatronHomeLibrary($this);
		}
		return $this->_homeLibrary;
	}

	function getHomeLibrarySystemName(): string {
		$homeLibrary = $this->getHomeLibrary();
		if ($homeLibrary == null) {
			return 'No Home Library';
		} else {
			return $this->getHomeLibrary()->displayName;
		}
	}

	/** @noinspection PhpUnused */
	public function getNumHoldsAvailableTotal($includeLinkedUsers = true) {
		$this->updateRuntimeInformation();
		$myHolds = $this->_numHoldsAvailableIls + $this->_numHoldsAvailableOverDrive;
		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				foreach ($this->linkedUsers as $user) {
					$myHolds += $user->getNumHoldsAvailableTotal(false);
				}
			}
		}

		return $myHolds;
	}

	private $totalFinesForLinkedUsers = -1;

	/** @noinspection PhpUnused */
	public function getTotalFines($includeLinkedUsers = true) {
		$totalFines = $this->_finesVal;
		if ($includeLinkedUsers) {
			if ($this->totalFinesForLinkedUsers == -1) {
				if ($this->getLinkedUsers() != null) {
					foreach ($this->linkedUsers as $user) {
						$totalFines += $user->getTotalFines(false);
					}
				}
				$this->totalFinesForLinkedUsers = $totalFines;
			} else {
				$totalFines = $this->totalFinesForLinkedUsers;
			}

		}
		return $totalFines;
	}

	/**
	 * Return all titles that are currently checked out by the user.
	 *
	 * Will check:
	 * 1) The current ILS for the user
	 * 2) OverDrive
	 * 3) Hoopla
	 * 4) cloudLibrary
	 * 5) Axis360
	 *
	 * @param bool $includeLinkedUsers
	 * @param string $source
	 * @return Checkout[]
	 */
	public function getCheckouts(bool $includeLinkedUsers = true, string $source = 'all'): array {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		//Check to see if we should return cached information, we will reload it if we last fetched data more than
		//15 minutes ago or if the refresh option is selected
		$reloadCheckoutInformation = false;
		if (($this->checkoutInfoLastLoaded < (time() - 5 * 60)) || isset($_REQUEST['refreshCheckouts'])) {
			$reloadCheckoutInformation = true;
		}

		$checkoutsToReturn = [];
		if ($reloadCheckoutInformation) {
			global $timer;
			$allCheckedOut = [];
			//Get checked out titles from the ILS
			global $offlineMode;
			if ($this->hasIlsConnection() && !$offlineMode) {
				$ilsCheckouts = $this->getCatalogDriver()->getCheckouts($this);
				$allCheckedOut = $ilsCheckouts;
				$timer->logTime("Loaded transactions from catalog. {$this->id}");
				if ($source == 'all' || $source == 'ils') {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $ilsCheckouts);
				}
			}

			//Get checked out titles from OverDrive
			//Do not load OverDrive titles if the parent barcode (if any) is the same as the current barcode
			if ($this->isValidForEContentSource('overdrive')) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$overDriveCheckedOutItems = $driver->getCheckouts($this);
				$allCheckedOut = array_merge($allCheckedOut, $overDriveCheckedOutItems);
				$timer->logTime("Loaded transactions from overdrive. {$this->id}");
				if ($source == 'all' || $source == 'overdrive') {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $overDriveCheckedOutItems);
				}
			}

			//Get checked out titles from Hoopla
			//Do not load Hoopla titles if the parent barcode (if any) is the same as the current barcode
			if ($this->isValidForEContentSource('hoopla')) {
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$hooplaDriver = new HooplaDriver();
				$hooplaCheckedOutItems = $hooplaDriver->getCheckouts($this);
				$allCheckedOut = array_merge($allCheckedOut, $hooplaCheckedOutItems);
				$timer->logTime("Loaded transactions from hoopla. {$this->id}");
				if ($source == 'all' || $source == 'hoopla') {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $hooplaCheckedOutItems);
				}
			}

			if ($this->isValidForEContentSource('cloud_library')) {
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$cloudLibraryDriver = new CloudLibraryDriver();
				$cloudLibraryCheckedOutItems = $cloudLibraryDriver->getCheckouts($this);
				$allCheckedOut = array_merge($allCheckedOut, $cloudLibraryCheckedOutItems);
				$timer->logTime("Loaded transactions from cloud_library. {$this->id}");
				if ($source == 'all' || $source == 'cloud_library') {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $cloudLibraryCheckedOutItems);
				}
			}

			if ($this->isValidForEContentSource('axis360')) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$axis360Driver = new Axis360Driver();
				$axis360CheckedOutItems = $axis360Driver->getCheckouts($this);
				$allCheckedOut = array_merge($allCheckedOut, $axis360CheckedOutItems);
				$timer->logTime("Loaded transactions from Boundless. {$this->id}");
				if ($source == 'all' || $source == 'axis360') {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $axis360CheckedOutItems);
				}
			}

			if ($this->isValidForEContentSource('palace_project')) {
				require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
				$palaceProjectDriver = new PalaceProjectDriver();
				$palaceProjectCheckedOutItems = $palaceProjectDriver->getCheckouts($this);
				$allCheckedOut = array_merge($allCheckedOut, $palaceProjectCheckedOutItems);
				$timer->logTime("Loaded transactions from Palace Project. {$this->id}");
				if ($source == 'all' || $source == 'palace_project') {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $palaceProjectCheckedOutItems);
				}
			}

			//Delete all existing checkouts
			$checkout = new Checkout();
			$checkout->userId = $this->id;
			$checkout->delete(true);

			foreach ($allCheckedOut as $checkout) {
				if (is_null($checkout->sourceId)) {
					$checkout->sourceId = '';
				}
				if (is_null($checkout->recordId)) {
					$checkout->recordId = '';
				}
				if ($checkout->insert() == 0) {
					if (IPAddress::showDebuggingInformation()) {
						global $logger;
						$logger->log(Logger::LOG_ERROR, "Could not save checkout to database");
					}
				}
			}

			$this->__set('checkoutInfoLastLoaded', time());
			$this->update();
		} else {
			//fetch cached checkouts
			$checkout = new Checkout();
			$checkout->userId = $this->id;
			if ($source != 'all') {
				$checkout->type = $source;
			}
			$checkout->find();
			while ($checkout->fetch()) {
				$checkoutsToReturn[] = clone $checkout;
			}
		}

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $linkedUser) {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $linkedUser->getCheckouts(false, $source));
				}
			}
		}

		if ($source == 'all') {
			$this->calculateCostSavingsForCurrentCheckouts($checkoutsToReturn);
		}
		return $checkoutsToReturn;
	}

	public function isRecordCheckedOut($source, $recordId) {
		$this->getCheckouts(false, 'all');
		require_once ROOT_DIR . "/sys/User/Checkout.php";
		$checkout = new Checkout();
		$checkout->userId = $this->id;
		$checkout->source = $source;
		$checkout->recordId = $recordId;
		if ($checkout->find(true)) {
			return true;
		} else {
			return false;
		}
	}

	public function isBlockedFromIllRequests() {
		if ($this->_isBlockedFromIllRequests === null) {
			if ($this->hasIlsConnection()) {
				$this->_isBlockedFromIllRequests = $this->getCatalogDriver()->isBlockedFromIllRequests($this);
			} else {
				return true;
			}
		}
		return $this->_isBlockedFromIllRequests;
	}

	public function getHolds($includeLinkedUsers = true, $unavailableSort = 'sortTitle', $availableSort = 'expire', $source = 'all'): array {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		//Check to see if we should return cached information, we will reload it if we last fetched it more than
		//15 minutes ago or if the refresh option is selected
		$reloadHoldInformation = false;
		if (($this->holdInfoLastLoaded < time() - 5 * 60) || isset($_REQUEST['refreshHolds'])) {
			$reloadHoldInformation = true;
		}

		$holdsToReturn = [
			'available' => [],
			'unavailable' => [],
		];
		if ($reloadHoldInformation) {
			//When we reload holds, we will fetch from all sources so they can be cached.

			$allHolds = [
				'available' => [],
				'unavailable' => [],
			];
			global $offlineMode;
			if ($this->hasIlsConnection() && !$offlineMode) {
				$ilsHolds = $this->getCatalogDriver()->getHolds($this);
				$allHolds = $ilsHolds;
				if ($source == 'all' || $source == 'ils') {
					$holdsToReturn = $ilsHolds;
				}
			}

			//Get holds from OverDrive
			if ($source == 'all' || $source == 'overdrive') {
				if ($this->isValidForEContentSource('overdrive')) {
					require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
					$driver = new OverDriveDriver();
					$overDriveHolds = $driver->getHolds($this);
					$allHolds = array_merge_recursive($allHolds, $overDriveHolds);
					$holdsToReturn = array_merge_recursive($holdsToReturn, $overDriveHolds);
				}
			}

			//Get holds from cloudLibrary
			if ($source == 'all' || $source == 'cloud_library') {
				if ($this->isValidForEContentSource('cloud_library')) {
					require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
					$driver = new CloudLibraryDriver();
					$cloudLibraryHolds = $driver->getHolds($this);
					$allHolds = array_merge_recursive($allHolds, $cloudLibraryHolds);
					$holdsToReturn = array_merge_recursive($holdsToReturn, $cloudLibraryHolds);
				}
			}

			//Get holds from Boundless
			if ($source == 'all' || $source == 'axis360') {
				if ($this->isValidForEContentSource('axis360')) {
					require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
					$driver = new Axis360Driver();
					$axis360Holds = $driver->getHolds($this);
					$allHolds = array_merge_recursive($allHolds, $axis360Holds);
					$holdsToReturn = array_merge_recursive($holdsToReturn, $axis360Holds);
				}
			}

			//Get holds from Palace Project
			if ($source == 'all' || $source == 'palace_project') {
				if ($this->isValidForEContentSource('palace_project')) {
					require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
					$driver = new PalaceProjectDriver();
					$palaceProjectHolds = $driver->getHolds($this);
					$allHolds = array_merge_recursive($allHolds, $palaceProjectHolds);
					$holdsToReturn = array_merge_recursive($holdsToReturn, $palaceProjectHolds);
				}
			}

			if ($source == 'all' || $source == 'interlibrary_loan') {
				if ($this->hasInterlibraryLoan()) {
					//For now, this is just VDX
					require_once ROOT_DIR . '/Drivers/VdxDriver.php';
					$driver = new VdxDriver();
					$vdxRequests = $driver->getRequests($this);
					$allHolds = array_merge_recursive($allHolds, $vdxRequests);
					$holdsToReturn = array_merge_recursive($holdsToReturn, $vdxRequests);
				}
			}
			//Delete all existing holds
			$hold = new Hold();
			$hold->userId = $this->id;
			$hold->delete(true);

			foreach ($allHolds['available'] as $holdToSave) {
				if (is_null($holdToSave->sourceId)) {
					$holdToSave->sourceId = '';
				}
				if (is_null($holdToSave->recordId)) {
					$holdToSave->recordId = '';
				}
				if (!$holdToSave->insert()) {
					global $logger;
					$logger->log('Could not save available hold ' . $holdToSave->getLastError(), Logger::LOG_ERROR);
				}
			}
			foreach ($allHolds['unavailable'] as $holdToSave) {
				if (is_null($holdToSave->sourceId)) {
					$holdToSave->sourceId = '';
				}
				if (is_null($holdToSave->recordId)) {
					$holdToSave->recordId = '';
				}
				if (!$holdToSave->insert()) {
					global $logger;
					$logger->log('Could not save unavailable hold ' . $holdToSave->getLastError(), Logger::LOG_ERROR);
				}
			}
			$this->__set('holdInfoLastLoaded', time());
			$this->update();
		} else {
			//fetch cached holds
			$hold = new Hold();
			$hold->userId = $this->id;
			if ($source != 'all') {
				$hold->type = $source;
			}
			/** @var Hold $allHolds */
			$allHolds = $hold->fetchAll();
			foreach ($allHolds as $hold) {
				$key = $hold->source;
				if (!empty($hold->cancelId)) {
					$key .= $hold->cancelId;
				} else {
					$key .= $hold->sourceId;
				}
				$key .= $hold->userId;
				if ($hold->available) {
					$holdsToReturn['available'][$key] = $hold;
				} else {
					$holdsToReturn['unavailable'][$key] = $hold;
				}
			}
		}

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				foreach ($this->getLinkedUsers() as $user) {
					$holdsToReturn = array_merge_recursive($holdsToReturn, $user->getHolds(false, $unavailableSort, $availableSort, $source));
				}
			}
		}

		$indexToSortBy = 'sortTitle';
		$holdSort = function (Hold $a, Hold $b) use (&$indexToSortBy) {
			$titleA = $a->getSortTitle();
			$titleB = $b->getSortTitle();
			if ($indexToSortBy == 'sortTitle') {
				$a = $titleA;
				$b = $titleB;
			} elseif ($indexToSortBy == 'user') {
				$a = $a->getUserName();
				$b = $b->getUserName();
			} else {
				$a = isset($a->$indexToSortBy) ? $a->$indexToSortBy : null;
				$b = isset($b->$indexToSortBy) ? $b->$indexToSortBy : null;
			}

			// Put empty values (except for specified values of zero) at the bottom of the sort
			if (modifiedEmpty($a) && modifiedEmpty($b)) {
				if ($indexToSortBy != 'sortTitle') {
					return strnatcasecmp($titleA, $titleB);
				} else {
					return 0;
				}
			} elseif (!modifiedEmpty($a) && modifiedEmpty($b)) {
				return -1;
			} elseif (modifiedEmpty($a) && !modifiedEmpty($b)) {
				return 1;
			}

			if ($indexToSortBy == 'format') {
				if (is_array($a)) {
					$a = implode(',', $a);
				}
				if (is_array($b)) {
					$b = implode(',', $b);
				}
			}

			$ret = strnatcasecmp($a, $b);
			if ($ret == 0 && $indexToSortBy != 'sortTitle') {
				return strnatcasecmp($titleA, $titleB);
			} else {
				return $ret;
			}
		};

		if (!empty($holdsToReturn['available'])) {
			switch ($availableSort) {
				case 'author' :
				case 'format' :
					//This is used in the sort function
					$indexToSortBy = $availableSort;
					break;
				case 'title' :
					$indexToSortBy = 'sortTitle';
					break;
				case 'libraryAccount' :
					$indexToSortBy = 'user';
					break;
				case 'location' :
					$indexToSortBy = 'pickupLocationName';
					break;
				case 'expire' :
				default :
					$indexToSortBy = 'expirationDate';
			}
			uasort($holdsToReturn['available'], $holdSort);
		}
		if (!empty($holdsToReturn['unavailable'])) {
			switch ($unavailableSort) {
				case 'author' :
				case 'position' :
				case 'status' :
				case 'format' :
					//This is used in the sort function
					$indexToSortBy = $unavailableSort;
					break;
				case 'placed' :
					$indexToSortBy = 'createDate';
					break;
				case 'libraryAccount' :
					$indexToSortBy = 'user';
					break;
				case 'location' :
					$indexToSortBy = 'pickupLocationName';
					break;
				case 'title' :
				default :
					$indexToSortBy = 'sortTitle';
			}
			uasort($holdsToReturn['unavailable'], $holdSort);
		}

		if ($source == 'interlibrary_loan') {
			unset($holdsToReturn['available']);
		}

		return $holdsToReturn;
	}

	public function inUserEvents($id) {
		require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';

		$event = new UserEventsEntry();
		$event->sourceId = $id;
		$event->userId = $this->id;
		if ($event->find()) {
			return true;
		}
		return false;
	}

	public function isRegistered($eventId): bool {
		require_once ROOT_DIR . '/sys/Events/UserEventsRegistrations.php';

		$registration = new UserEventsRegistrations();
		$registration->sourceId = $eventId;
		$registration->userId = $this->id;
		if ($registration->find()) {
			return true;
		}
		return false;
	}

	public function isAllowedToAddEventsToList($vendor): bool {
		$activeLibrary = $this->getHomeLibrary();
		if ($activeLibrary == null) {
			global $library;
			$activeLibrary = $library;
		}

		$eventsSetting = new LibraryEventsSetting();
		$eventsSetting->libraryId = $activeLibrary->libraryId;
		$eventsSetting->settingSource = $vendor;
		if ($eventsSetting->find(true)) {
			if ($vendor == 'communico') {
				require_once ROOT_DIR . '/sys/Events/CommunicoSetting.php';
				$settings = new CommunicoSetting();
				$settings->id = $eventsSetting->settingId;
				if ($settings->find(true)) {
					if ($settings->eventsInLists == 2 || ($settings->eventsInLists == 1 && $this->isStaff())) {
						return true;
					}
				}
			} elseif ($vendor == 'springshare') {
				require_once ROOT_DIR . '/sys/Events/SpringshareLibCalSetting.php';
				$settings = new SpringshareLibCalSetting;
				$settings->id = $eventsSetting->settingId;
				if ($settings->find(true)) {
					if ($settings->eventsInLists == 2 || ($settings->eventsInLists == 1 && $this->isStaff())) {
						return true;
					}
				}
			} elseif ($vendor == 'library_market') {
				require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
				$settings = new LMLibraryCalendarSetting();
				$settings->id = $eventsSetting->settingId;
				if ($settings->find(true)) {
					if ($settings->eventsInLists == 2 || ($settings->eventsInLists == 1 && $this->isStaff())) {
						return true;
					}
				}
			} elseif ($vendor == 'assabet') {
				require_once ROOT_DIR . '/sys/Events/AssabetSetting.php';
				$settings = new AssabetSetting();
				$settings->id = $eventsSetting->settingId;
				if ($settings->find(true)) {
					if ($settings->eventsInLists == 2 || ($settings->eventsInLists == 1 && $this->isStaff())) {
						return true;
					}
				}
			}
		}

		return false;
	}

	public function isRecordOnHold($source, $recordId) {
		$this->getHolds(false, 'all');
		require_once ROOT_DIR . "/sys/User/Hold.php";
		$hold = new Hold();
		$hold->userId = $this->id;
		$hold->source = $source;
		$hold->recordId = $recordId;
		if ($hold->find(true)) {
			return true;
		} else {
			return false;
		}
	}

	public function areCirculationActionsDisabled() {
		if (!$this->hasIlsConnection()) {
			return true;
		} else {
			if ($this->disableCirculationActions) {
				return true;
			} else {
				//TODO: Should we automatically disable the circulation actions if there are a large number of things checked out and/or on hold?
				//$accountSummary = $this->getAccountSummary();
				return false;
			}
		}
	}

	public function getCirculatedRecordActions($source, $recordId, $loadingLinkedUser = false) {
		$actions = [];
		if ($this->areCirculationActionsDisabled() == true) {
			return $actions;
		}
		$showUserName = $loadingLinkedUser;
//		if (!$loadingLinkedUser){
//			$linkedUsers = $this->getLinkedUsers();
//			if (count($linkedUsers) > 0){
//				$showUserName = true;
//			}
//		}
		if ($this->isRecordCheckedOut($source, $recordId)) {
			$actions[] = [
				'title' => translate([
					'text' => 'Checked Out to %1%',
					1 => $showUserName ? $this->displayName : translate([
						'text' => 'You',
						'isPublicFacing' => true,
					]),
					'isPublicFacing' => true,
				]),
				'url' => "/MyAccount/CheckedOut",
				'requireLogin' => false,
				'btnType' => 'btn-info',
			];
		} elseif ($source != 'hoopla' && $this->isRecordOnHold($source, $recordId)) {
			$actions[] = [
				'title' => translate([
					'text' => 'On Hold for %1%',
					1 => $showUserName ? $this->displayName : translate([
						'text' => 'You',
						'isPublicFacing' => true,
					]),
					'isPublicFacing' => true,
				]),
				'url' => "/MyAccount/Holds",
				'requireLogin' => false,
				'btnType' => 'btn-info',
				'id' => 'onHoldAction' . $recordId
			];
		}
		if (!$loadingLinkedUser) {
			$linkedUsers = $this->getLinkedUsers();
			foreach ($linkedUsers as $linkedUser) {
				$actions = array_merge($actions, $linkedUser->getCirculatedRecordActions($source, $recordId, true));
			}
		}
		return $actions;
	}

	private $ilsFinesForUser;

	public function getFines($includeLinkedUsers = true, $APIRequest = false): array {

		if (!isset($this->ilsFinesForUser)) {
			$this->ilsFinesForUser = $this->getCatalogDriver()->getFines($this);
			if ($this->ilsFinesForUser instanceof AspenError) {
				$this->ilsFinesForUser = [];
			}
		}

		if ($APIRequest && !$includeLinkedUsers) {
			$ilsFines = $this->ilsFinesForUser;
		} else {
			$ilsFines[$this->id] = $this->ilsFinesForUser;
		}

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				foreach ($this->getLinkedUsers() as $user) {
					$ilsFines += $user->getFines(false, $APIRequest); // keep keys as userId
				}
			}
		}
		return $ilsFines;
	}

	public function getNameAndLibraryLabel() {
		return $this->getDisplayName() . ' - ' . $this->getHomeLibrarySystemName();
	}

	public function getValidHomeLibraryBranches($recordSource) {
		$pickupLocations = $this->getValidPickupBranches($recordSource);
		$hasHomeLibrary = false;
		foreach ($pickupLocations as $key => $pickupLocation) {
			if (is_object($pickupLocation)) {
				if ($pickupLocation->locationId == $this->homeLocationId) {
					$hasHomeLibrary = true;
				}
			} else {
				unset($pickupLocations[$key]);
			}
		}
		if (!$hasHomeLibrary) {
			$pickupLocations = array_merge([$this->getHomeLocation()], $pickupLocations);
		}
		return $pickupLocations;
	}

	/**
	 * Get a list of locations where a record can be picked up. Handles liked accounts
	 * and filtering to make sure that the user is able to
	 *
	 * @param $recordSource string The source of the record that we are placing a hold on
	 *
	 * @return Location[]
	 */
	public function getValidPickupBranches($recordSource) {
		//Get the list of pickup branch locations for display in the user interface.
		// using $user to be consistent with other code use of getPickupBranches()
		$userLocation = new Location();
		if ($recordSource == $this->getAccountProfile()->recordSource) {
			$locations = $userLocation->getPickupBranches($this);
		} else {
			$locations = [];
		}
		$linkedUsers = $this->getLinkedUsers();
		if (count($linkedUsers) > 0) {
			$accountProfileForSource = UserAccount::getAccountProfileByRecordSource($recordSource);
			if ($accountProfileForSource != null) {
				$accountProfileSource = $accountProfileForSource->name;
			} else {
				$accountProfileSource = '';
			}
//			$accountProfileForSource = new AccountProfile();
//			$accountProfileForSource->recordSource = $recordSource;
//			$accountProfileSource = '';
//			if ($accountProfileForSource->find(true)) {
//				$accountProfileSource = $accountProfileForSource->name;
//			}
			foreach ($linkedUsers as $linkedUser) {
				if ($accountProfileSource == $linkedUser->source) {
					$linkedUserLocation = new Location();
					$linkedUserPickupLocations = $linkedUserLocation->getPickupBranches($linkedUser, true);
					foreach ($linkedUserPickupLocations as $sortingKey => $pickupLocation) {
						if (!is_object($pickupLocation)) {
							continue;
						}
						foreach ($locations as $mainSortingKey => $mainPickupLocation) {
							if (!is_object($mainPickupLocation)) {
								continue;
							}
							// Check For Duplicated Pickup Locations
							if ($mainPickupLocation->libraryId == $pickupLocation->libraryId && $mainPickupLocation->locationId == $pickupLocation->locationId) {
								// Merge Linked Users that all have this pick-up location
								$pickupUsers = array_unique(array_merge($mainPickupLocation->getPickupUsers(), $pickupLocation->getPickupUsers()));
								$mainPickupLocation->setPickupUsers($pickupUsers);
								$pickupLocation->setPickupUsers($pickupUsers);

								// keep location with better sort key, remove the other
								if ($mainSortingKey == $sortingKey || $mainSortingKey[0] < $sortingKey[0]) {
									unset ($linkedUserPickupLocations[$sortingKey]);
								} elseif ($mainSortingKey[0] == $sortingKey[0]) {
									if (strcasecmp($mainSortingKey, $sortingKey) > 0) {
										unset ($locations[$mainSortingKey]);
									} else {
										unset ($linkedUserPickupLocations[$sortingKey]);
									}
								} else {
									unset ($locations[$mainSortingKey]);
								}
							}
						}
					}
					$locations = array_merge($locations, $linkedUserPickupLocations);
				}
			}
		}
		ksort($locations);
		return $locations;
	}

	/**
	 * Get a list of sublocations where a user can pick-up from
	 **
	 * @return Sublocation[]
	 */
	public function getValidSublocations(int $locationId): array {
		require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
		require_once ROOT_DIR . '/sys/LibraryLocation/SublocationPatronType.php';
		$sublocations = [];
		$patronType = $this->getPTypeObj();
		if ($patronType !== null) {
			$object = new Sublocation();
			$object->locationId = $locationId;
			$object->isValidHoldPickupAreaILS = 1;
			$object->isValidHoldPickupAreaAspen = 1;
			$object->orderBy('weight');
			$object->find();
			while ($object->fetch()) {
				$sublocationPType = new SublocationPatronType();
				$sublocationPType->patronTypeId = $patronType->id;
				$sublocationPType->sublocationId = $object->id;
				if ($sublocationPType->find(true)) {
					$sublocations[$object->id] = clone($object);
				}
			}

			ksort($sublocations);
		}

		return $sublocations;
	}

	/**
	 * Place Hold
	 *
	 * Place a hold for the current user within their ILS
	 *
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param null|string $cancelDate When the hold should be automatically cancelled if desired
	 * @return array An array with the following keys
	 * result - true/false
	 * message - the message to display
	 * @access public
	 */
	function placeHold(string $recordId, string $pickupBranch, ?string $cancelDate = null, ?string  $pickupSublocation = null) : array {
		$result = $this->getCatalogDriver()->placeHold($this, $recordId, $pickupBranch, $cancelDate, $pickupSublocation);
		$this->updateAltLocationForHold($pickupBranch);
		$thisUser = translate([
			'text' => 'You',
			'isPublicFacing' => true,
		]);
		if (!empty($this->parentUser)) {
			$thisUser = $this->displayName;
		}
		if ($result['success']) {
			$viewHoldsText = translate([
				'text' => 'On Hold for %1%',
				1 => $thisUser,
				'isPublicFacing' => true,
				'inAttribute' => true
			]);

			$result['viewHoldsAction'] = "<a id='onHoldAction$recordId' href='/MyAccount/Holds' class='btn btn-sm btn-info btn-wrap' title='$viewHoldsText'>$viewHoldsText</a>";

			$this->clearCache();

			$this->forceReloadOfHolds();
		}
		return $result;
	}

	function placeVolumeHold($recordId, $volumeId, $pickupBranch, $pickupSublocation = null) : array {
		$result = $this->getCatalogDriver()->placeVolumeHold($this, $recordId, $volumeId, $pickupBranch, $pickupSublocation);
		$this->updateAltLocationForHold($pickupBranch);
		$thisUser = translate([
			'text' => 'You',
			'isPublicFacing' => true,
		]);
		if (!empty($this->parentUser)) {
			$thisUser = $this->displayName;
		}
		if ($result['success']) {
			$viewHoldsText = translate([
				'text' => 'On Hold for %1%',
				1 => $thisUser,
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);

			$result['viewHoldsAction'] = "<a id='onHoldAction$recordId' href='/MyAccount/Holds' class='btn btn-sm btn-info btn-wrap' title='$viewHoldsText'>$viewHoldsText</a>";

			$this->clearCache();
		}
		return $result;
	}

	function confirmHold($recordId, $confirmationId) {
		$result = $this->getCatalogDriver()->confirmHold($this, $recordId, $confirmationId);
		if ($result['success']) {
			$this->clearCache();
		}
		return $result;
	}

	function updateAltLocationForHold($pickupBranch) {
		if ($this->_homeLocationCode != $pickupBranch) {
			//global $logger;
			//$logger->log("The selected pickup branch is not the user's home location, checking to see if we need to set an alternate branch", Logger::LOG_NOTICE);
			$location = new Location();
			$location->code = $pickupBranch;
			if ($location->find(true)) {
				//$logger->log("Found the location for the pickup branch $pickupBranch {$location->locationId}", Logger::LOG_NOTICE);
				if ($this->myLocation1Id == 0) {
					//$logger->log("Alternate location 1 is blank updating that", Logger::LOG_NOTICE);
					$this->myLocation1Id = $location->locationId;
					$this->update();
				} elseif ($this->myLocation2Id == 0 && $location->locationId != $this->myLocation1Id) {
					//$logger->log("Alternate location 2 is blank updating that", Logger::LOG_NOTICE);
					$this->myLocation2Id = $location->locationId;
					$this->update();
				}
			} else {
				//$logger->log("Could not find location for $pickupBranch", Logger::LOG_ERROR);
			}
		}
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for placing item level holds.
	 *
	 * @param string $recordId The id of the bib record
	 * @param string $itemId The id of the item to hold
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param null|string $cancelDate The date to automatically cancel the hold if not filled
	 * @return array an array with the results of the hold
	 * @access public
	 */
	function placeItemHold(string $recordId, string $itemId, string $pickupBranch, ?string $cancelDate = null, ?string  $pickupSublocation = null) : array {
		$result = $this->getCatalogDriver()->placeItemHold($this, $recordId, $itemId, $pickupBranch, $cancelDate, $pickupSublocation);
		$this->updateAltLocationForHold($pickupBranch);
		$thisUser = translate([
			'text' => 'You',
			'isPublicFacing' => true,
		]);
		if (!empty($this->parentUser)) {
			$thisUser = $this->displayName;
		}
		if ($result['success']) {
			$viewHoldsText = translate([
				'text' => 'On Hold for %1%',
				1 => $thisUser,
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);

			$result['viewHoldsAction'] = "<a id='onHoldAction$recordId' href='/MyAccount/Holds' class='btn btn-sm btn-info btn-wrap' title='{$viewHoldsText}'>{$viewHoldsText}</a>";

			$this->clearCache();
		}
		return $result;
	}

	/**
	 * Get the user referred to by id. Will return false if the specified patron id is not
	 * the id of this user or one of the users that is linked to this user.
	 *
	 * @param int|string $patronId - The ID of the patron to use
	 * @return User|false
	 */
	function getUserReferredTo(int|string $patronId): User|false {
		$patron = false;
		//Get the correct patron based on the information passed in.
		if ($patronId == $this->id) {
			$patron = $this;
		} else {
			foreach ($this->getLinkedUsers() as $tmpUser) {
				if ($tmpUser->id == $patronId) {
					$patron = $tmpUser;
					break;
				}
			}
		}
		return $patron;
	}

	/**
	 * Cancels a hold for the user in their ILS
	 *
	 * @param $recordId string The Id of the record being cancelled
	 * @param $cancelId string The Id of the hold to be cancelled. Structure varies by ILS
	 * @param $isIll boolean If the hold is from the ILL system
	 *
	 * @return array Information about the result of the cancellation process
	 */
	function cancelHold($recordId, $cancelId, $isIll): array {
		$result = $this->getCatalogDriver()->cancelHold($this, $recordId, $cancelId, $isIll);
		if ($result['success']) {
			$this->forceReloadOfHolds();
		}
		$this->clearCache();
		return $result;
	}

	function cancelVdxRequest($requestId, $cancelId) {
		//For now, this is just VDX
		require_once ROOT_DIR . '/Drivers/VdxDriver.php';
		$driver = new VdxDriver();
		$result = $driver->cancelRequest($this, $requestId, $cancelId);

		$this->clearCache();
		return $result;
	}

	function changeHoldPickUpLocation($itemToUpdateId, $newPickupLocation, $newPickupSublocation): array {
		$result = $this->getCatalogDriver()->changeHoldPickupLocation($this, null, $itemToUpdateId, $newPickupLocation, $newPickupSublocation);
		$this->clearCache();
		return $result;
	}

	function freezeHold($recordId, $holdId, $reactivationDate) {
		$result = $this->getCatalogDriver()->freezeHold($this, $recordId, $holdId, $reactivationDate);
		$this->clearCache();
		return $result;
	}

	function freezeAllHolds($reactivationDate = false) {
		$user = UserAccount::getLoggedInUser();
		$result = [ // set default response
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			])
		];

		$allHolds = $user->getHolds();
		$allUnavailableHolds = $allHolds['unavailable'];
		$success = 0;
		$failed = 0;
		$total = count($allHolds['unavailable']);
		$numHoldsAlreadyFrozen = 0;

		if ($total >= 1) {
			foreach ($allUnavailableHolds as $hold) {
				$frozen = $hold->frozen;
				if ($frozen) {
					$numHoldsAlreadyFrozen++;
				}
				$canFreeze = $hold->canFreeze;
				$recordId = $hold->sourceId;
				$holdId = $hold->cancelId;
				$holdType = $hold->source;

				if ($frozen == 0 && $canFreeze == 1) {
					if ($holdType == 'ils') {
						$tmpResult = $user->freezeHold($recordId, $holdId, $reactivationDate);
						if ($tmpResult['success']) {
							$success++;
						} else {
							$failed++;
						}
					} elseif ($holdType == 'axis360') {
						require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
						$driver = new Axis360Driver();
						$tmpResult = $driver->freezeHold($user, $recordId);
						if ($tmpResult['success']) {
							$success++;
						} else {
							$failed++;
						}
					} elseif ($holdType == 'overdrive') {
						require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
						$driver = new OverDriveDriver();
						$tmpResult = $driver->freezeHold($user, $recordId, $reactivationDate);
						if ($tmpResult['success']) {
							$success++;
						} else {
							$failed++;
						}
					} else {
						$failed++;
					}

				} elseif ($canFreeze == 0) {
					$failed++;
				}
			}
		}

		if ($success >= 1) {
			$message = '<div class="alert alert-success">' . translate([
					'text' => '%1% of %2% holds were frozen',
					1 => $success,
					2 => $total,
					'isPublicFacing' => true,
					'inAttribute' => true,
				]) . '</div>';

			if ($failed >= 1) {
				$message .= '<div class="alert alert-warning">' . translate([
						'text' => '%1% hold(s) failed to freeze',
						1 => $failed,
						2 => $total,
						'isPublicFacing' => true,
						'inAttribute' => true,
					]) . '</div>';
			}

			$result['message'] = $message;
			$result['title'] = translate([
				'text' => 'Success',
				'isPublicFacing' => true,
			]);
		} else {
			if ($total == 0) {
				$result['message'] = '<div class="alert alert-warning">' . translate([
						'text' => 'No holds available to freeze',
						'isPublicFacing' => true,
						'inAttribute' => true,
					]) . '</div>';
			} else {
				if ($numHoldsAlreadyFrozen == $total) {
					$result['message'] = '<div class="alert alert-warning">' . translate([
							'text' => 'All holds already frozen',
							'isPublicFacing' => true,
							'inAttribute' => true,
						]) . '</div>';
				} else {
					$result['message'] = '<div class="alert alert-warning">' . translate([
							'text' => '%1% hold(s) could not be frozen',
							1 => $failed,
							2 => $total,
							'isPublicFacing' => true,
							'inAttribute' => true,
						]) . '</div>';
				}

			}

		}

		return $result;
	}

	function thawAllHolds() {
		$user = UserAccount::getLoggedInUser();
		$tmpResult = [ // set default response
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Error modifying hold.',
				'isPublicFacing' => true,
			]),
		];

		$allHolds = $user->getHolds();
		$allUnavailableHolds = $allHolds['unavailable'];
		$success = 0;
		$failed = 0;
		$total = count($allHolds['unavailable']);

		if ($total >= 1) {
			foreach ($allUnavailableHolds as $hold) {
				$frozen = $hold->frozen;
				$canFreeze = $hold->canFreeze;
				$recordId = $hold->sourceId;
				$holdId = $hold->cancelId;
				$holdType = $hold->source;

				if ($frozen == 1 && $canFreeze == 1) {
					if ($holdType == 'ils') {
						$tmpResult = $user->thawHold($recordId, $holdId);
						if ($tmpResult['success']) {
							$success++;
						}
					} elseif ($holdType == 'axis360') {
						require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
						$driver = new Axis360Driver();
						$tmpResult = $driver->thawHold($user, $recordId);
						if ($tmpResult['success']) {
							$success++;
						}
					} elseif ($holdType == 'overdrive') {
						require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
						$driver = new OverDriveDriver();
						$tmpResult = $driver->thawHold($user, $recordId);
						if ($tmpResult['success']) {
							$success++;
						}
					} elseif ($holdType == 'cloud_library') {
						//Cloud library holds cannot be frozen
//						require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
//						$driver = new CloudLibraryDriver();
//						$tmpResult = $driver->thawHold($user, $recordId);
//						if($tmpResult['success']){$success++;}
					} else {
						$failed++;
						//$tmpResult['message'] = '<div class="alert alert-warning">Hold not available</div>';
					}

				} elseif ($canFreeze == 0) {
					$failed++;
				} elseif ($frozen == 1) {
					$failed++;
				}

				if ($success >= 1) {
					$message = '<div class="alert alert-success">' . translate([
							'text' => '%1% of %2% holds were thawed',
							1 => $success,
							2 => $total,
							'isPublicFacing' => true,
							'inAttribute' => true,
						]) . '</div>';

					if ($failed >= 1) {
						$message .= '<div class="alert alert-warning">' . translate([
								'text' => '%1% holds failed to thaw',
								1 => $failed,
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</div>';
					}

					$tmpResult['message'] = $message;
					$tmpResult['title'] = translate([
						'text' => 'Success',
						'isPublicFacing' => true,
					]);
				} else {
					$tmpResult['message'] = '<div class="alert alert-warning">' . translate([
							'text' => 'All holds already thawed',
							'isPublicFacing' => true,
						]) . '</div>';
				}
			}
		} else {
			$tmpResult['message'] = '<div class="alert alert-warning">' . translate([
					'text' => 'No holds available to thaw.',
					'isPublicFacing' => true,
				]) . '</div>';
		}

		return $tmpResult;
	}

	function thawHold($recordId, $holdId): array {
		$result = $this->getCatalogDriver()->thawHold($this, $recordId, $holdId);
		$this->clearCache();
		return $result;
	}

	function freezeOverDriveHold($overDriveId, $reactivationDate): array {
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		return $overDriveDriver->freezeHold($this, $overDriveId, $reactivationDate);
	}

	function thawOverDriveHold($overDriveId): array {
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		return $overDriveDriver->thawHold($this, $overDriveId);
	}

	function freezeAxis360Hold($recordId): array {
		require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
		$axis360Driver = new Axis360Driver();
		return $axis360Driver->freezeHold($this, $recordId);
	}

	function thawAxis360Hold($recordId): array {
		require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
		$axis360Driver = new Axis360Driver();
		return $axis360Driver->thawHold($this, $recordId);
	}

	function renewCheckout($recordId, $itemId = null, $itemIndex = null) {
		$result = $this->getCatalogDriver()->renewCheckout($this, $recordId, $itemId, $itemIndex);
		$this->clearCache();
		return $result;
	}

	function renewAll($renewLinkedUsers = false) {
		$renewAllResults = $this->getCatalogDriver()->renewAll($this);
		//Also renew linked Users if needed
		if ($renewLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				foreach ($this->getLinkedUsers() as $user) {
					$linkedResults = $user->renewAll();
					//Merge results
					$renewAllResults['Renewed'] += $linkedResults['Renewed'];
					$renewAllResults['NotRenewed'] += $linkedResults['NotRenewed'];
					$renewAllResults['Total'] += $linkedResults['Total'];
					if ($renewAllResults['success'] && !$linkedResults['success']) {
						$renewAllResults['success'] = false;
						$renewAllResults['message'] = $linkedResults['message'];
					} elseif (!$renewAllResults['success'] && !$linkedResults['success']) {
						//Append the new message

						$renewAllResults['message'] = array_merge($renewAllResults['message'], $linkedResults['message']);
					}
				}
			}
		}
		$this->clearCache();

		$renewAllResults['title'] = translate([
			'text' => 'Renewing all titles',
			'isPublicFacing' => true,
		]);
		if ($renewAllResults['Renewed'] == 0) {
			$renewAllResults['title'] = translate([
				'text' => 'Unable to renew some titles',
				'isPublicFacing' => true,
			]);
		}

		return $renewAllResults;
	}

	public function isReadingHistoryEnabled(): bool {
		$catalogDriver = $this->getCatalogDriver();
		if ($catalogDriver != null) {
			//Check to see if it's enabled by home library
			$homeLibrary = $this->getHomeLibrary();
			if (!empty($homeLibrary)) {
				if ($homeLibrary->enableReadingHistory) {
					//Check to see if we are masquerading
					if (UserAccount::isUserMasquerading()) {
						if (!$homeLibrary->allowReadingHistoryDisplayInMasqueradeMode) {
							return false;
						}
					}
					//Check to see if it's enabled by PType
					$patronType = $this->getPTypeObj();
					if (!empty($patronType)) {
						return (bool)$patronType->enableReadingHistory;
					} else {
						return true;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function isPaymentHistoryEnabled() {
		//Check to see if it's enabled by home library
		$homeLibrary = $this->getHomeLibrary();
		if (!empty($homeLibrary)) {
			return $homeLibrary->showPaymentHistory && $homeLibrary->finePaymentType > 1;
		} else {
			global $library;
			return $library->showPaymentHistory && $library->finePaymentType > 1;
		}
	}

	public function isCostSavingsEnabled(): bool {
		//Check to see if it's enabled by home library
		$homeLibrary = $this->getHomeLibrary();
		if (!empty($homeLibrary)) {
			return $homeLibrary->enableCostSavings && $this->enableCostSavings;
		} else {
			global $library;
			return $library->enableCostSavings && $this->enableCostSavings;
		}
	}

	public function getPaymentHistory($page = 1, $recordsPerPage = 25) {
		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';

		$userPayment = new UserPayment();
		$userPayment->userId = $this->id;
		$userPayment->whereAdd('completed = 1 OR cancelled = 1 OR error = 1');
		$numPayments = $userPayment->count();
		if ($recordsPerPage > 0) {
			$firstIndex = ($page - 1) * $recordsPerPage;
			$userPayment->limit($firstIndex, $recordsPerPage);
		}
		$userPayment->orderBy('transactionDate desc');
		$userPayment->find();
		$payments = [];
		while ($userPayment->fetch()) {
			if ($userPayment->completed) {
				$completed = 'Yes';
			} else {
				$completed = 'No';
			}
			$payments[] = [
				'id' => $userPayment->id,
				'date' => $userPayment->transactionDate,
				'type' => translate([
					'text' => ucfirst($userPayment->transactionType),
					'isPublicFacing' => true,
					'inAttribute' => true
				]),
				'completed' => translate([
					'text' => $completed,
					'isPublicFacing' => true,
					'inAttribute' => true
				]),
				'totalPaid' => StringUtils::formatCurrency($userPayment->totalPaid),
			];
		}

		$paymentHistory = [
			'numPayments' => $numPayments,
			'payments' => $payments
		];
		return $paymentHistory;
	}

	public function getReadingHistory($page = 1, $recordsPerPage = 20, $sortOption = "checkedOut", $filter = "", $forExport = false) {
		if ($this->isReadingHistoryEnabled()) {
			return $this->getCatalogDriver()->getReadingHistory($this, $page, $recordsPerPage, $sortOption, $filter, $forExport);
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Reading History Functionality is not available',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	/**
	 * Returns the timestamp when reading history was first tracked, or null if reading history is not enabled or is empty
	 *
	 * @return int|null
	 */
	public function getReadingHistoryStartDate(): ?int {
		if ($this->isReadingHistoryEnabled()) {
			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$readingHistoryEntry = new ReadingHistoryEntry();
			$readingHistoryEntry->userId = $this->id;
			$readingHistoryEntry->deleted = 0;
			$readingHistoryEntry->orderBy('checkOutDate ASC');
			$readingHistoryEntry->limit(0, 1);
			if ($readingHistoryEntry->find(true)) {
				return $readingHistoryEntry->checkOutDate;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	public function doReadingHistoryAction(string $readingHistoryAction, array $selectedTitles) {
		if ($this->isReadingHistoryEnabled()) {
			$catalogDriver = $this->getCatalogDriver();
			$results = $catalogDriver->doReadingHistoryAction($this, $readingHistoryAction, $selectedTitles);
			$this->clearCache();
			return $results;
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Reading History Functionality is not available',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	public function deleteReadingHistoryEntryByTitleAuthor($title, $author) {
		if ($this->isReadingHistoryEnabled()) {
			$catalogDriver = $this->getCatalogDriver();
			return $catalogDriver->deleteReadingHistoryEntryByTitleAuthor($this, $title, $author);
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'Reading History Functionality is not available',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	public function updateReadingHistoryBasedOnCurrentCheckouts($isNightlyUpdate) {
		if ($this->isReadingHistoryEnabled()) {
			$catalogDriver = $this->getCatalogDriver();
			return $catalogDriver->updateReadingHistoryBasedOnCurrentCheckouts($this, $isNightlyUpdate);
		} else {
			return [
				'message' => 'Reading history is not enabled',
				'skipped' => true
			];
		}
	}

	/**
	 * Used by Account Profile, to show users any additional Admin roles they may have.
	 * @return bool
	 */
	public function isStaff(): bool {
		if (count($this->getRoles()) > 0) {
			return true;
		} else {
			$patronType = $this->getPTypeObj();
			if (!empty($patronType)) {
				return boolval($patronType->isStaff);
			}
		}
		return false;
	}

	public function getPType(): ?string {
		return $this->patronType;
	}

	private $_pTypeObj = false;

	public function getPTypeObj(): ?PType {
		if ($this->_pTypeObj === false) {
			if (!empty($this->patronType) || $this->patronType == 0) {
				require_once ROOT_DIR . '/sys/Account/PType.php';
				$patronType = new PType();
				$patronType->pType = $this->patronType;
				$patronType->accountProfileId = $this->getAccountProfile()->id;
				if ($patronType->find(true)) {
					$this->_pTypeObj = $patronType;
				} else {
					$this->_pTypeObj = null;
				}
			} else {
				$this->_pTypeObj = null;
			}
		}
		return $this->_pTypeObj;
	}

	/**
	 * Get the user's age based on their date of birth.
	 * 
	 * @return int|null The user's age or null if date of birth is not set.
	*/
	public function getAge(): ?int {
		
		$this->loadContactInformation();
		
		$dob = new DateTime($this->_dateOfBirth);
		$today = new DateTime();

		$age = $dob->diff($today)->y;
		return $age;
	}

	public function updatePatronInfo($canUpdateContactInfo, $fromMasquerade = false) {
		$result = $this->getCatalogDriver()->updatePatronInfo($this, $canUpdateContactInfo, $fromMasquerade);
		$this->clearCache();
		return $result;
	}

	public function updateHomeLibrary($newHomeLocationCode): array {
		$catalogDriver = $this->getCatalogDriver();
		if (empty($catalogDriver)) { // getCatalogDriver() may return null, guard clause required
			return [
				'success' => false,
				'message' => 'This account is not connected to an ILS, cannot connect to home library.'
			];
		}
		$result = $catalogDriver->updateHomeLibrary($this, $newHomeLocationCode);
		$this->clearCache();
		return $result;
	}

	/**
	 * Update the PIN or password for the user
	 *
	 * @return string[] keys are success and errors or message
	 */
	function updatePin() {
		if (isset($_REQUEST['pin'])) {
			$oldPin = $_REQUEST['pin'];
		} else {
			return [
				'success' => false,
				'message' => "Please enter your current pin number",
			];
		}
		if ($this->hasIlsConnection()) {
			if ($this->cat_password != $oldPin) {
				return [
					'success' => false,
					'message' => "The old pin number is incorrect",
				];
			}
		}else{
			if ($this->password != $oldPin) {
				return [
					'success' => false,
					'message' => "The old pin number is incorrect",
				];
			}
		}
		if (!empty($_REQUEST['pin1'])) {
			$newPin = $_REQUEST['pin1'];
		} else {
			return [
				'success' => false,
				'message' => "Please enter the new pin number",
			];
		}
		if (!empty($_REQUEST['pin2'])) {
			$confirmNewPin = $_REQUEST['pin2'];
		} else {
			return [
				'success' => false,
				'message' => "Please enter the new pin number again",
			];
		}
		if ($newPin != $confirmNewPin) {
			return [
				'success' => false,
				'message' => "New PINs do not match. Please try again.",
			];
		}
		if ($this->hasIlsConnection()) {
			$result = $this->getCatalogDriver()->updatePin($this, $oldPin, $newPin);
		}else{
			$this->password = $newPin;
			$passwordValidation = $this->validateStrongPassword();
			if (!$passwordValidation['validatedOk']) {
				$this->password = $oldPin;
				$result = [
					'success' => false,
					'message' => implode('<br/>', $passwordValidation['errors'])
				];
			}else{
				$result = [
					'success' => true,
					'message' => 'Your password was updated successfully.'
				];
			}
		}
		if ($result['success']) {
			if ($this->hasIlsConnection()) {
				$this->__set('cat_password', $newPin);
			}
			$this->__set('password', $newPin);
			$this->update();
			$this->clearCache();
		}

		return $result;
	}

	function getRelatedPTypes($includeLinkedUsers = true) {
		$relatedPTypes = [];
		$relatedPTypes[$this->patronType] = $this->patronType;
		if ($includeLinkedUsers) {
			if ($this->getLinkedUserObjects() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUserObjects() as $user) {
					$relatedPTypes = array_merge($relatedPTypes, $user->getRelatedPTypes(false));
				}
			}
		}
		return $relatedPTypes;
	}

	function importListsFromIls() {
		return $this->getCatalogDriver()->importListsFromIls($this);
	}

	public function canClientIpUseMasquerade(): bool {
		global $library;
		$masqueradeStatus = $library->getMasqueradeStatus();
		if ($masqueradeStatus == 1) {
			return true;
		} else if ($masqueradeStatus == 2) {
			$clientIP = IPAddress::getClientIP();
			$ipInfo = IPAddress::getIPAddressForIP($clientIP);
			if ($ipInfo != false) {
				return $ipInfo->masqueradeMode == 1;
			}
		}
		return false;
	}

	public function canMasquerade() {
		if (self::canClientIpUseMasquerade()) {
			return $this->hasPermission([
				'Masquerade as any user',
				'Masquerade as unrestricted patron types',
				'Masquerade as patrons with same home library',
				'Masquerade as unrestricted patrons with same home library',
				'Masquerade as patrons with same home location',
				'Masquerade as unrestricted patrons with same home location',
			]);
		} else {
			return false;
		}
	}

	/**
	 * @param mixed $materialsRequestReplyToAddress
	 */
	public function setMaterialsRequestReplyToAddress($materialsRequestReplyToAddress) {
		$this->__set('materialsRequestReplyToAddress', $materialsRequestReplyToAddress);
	}

	/**
	 * @param mixed $materialsRequestEmailSignature
	 */
	public function setMaterialsRequestEmailSignature($materialsRequestEmailSignature) {
		$this->__set('materialsRequestEmailSignature', $materialsRequestEmailSignature);
	}

	public function setPickupLocationId(int $pickupLocationId) {
		$this->__set('pickupLocationId', $pickupLocationId);
	}

	public function setPickupSublocationId(int $pickupSublocationId) {
		$this->__set('pickupSublocationId', $pickupSublocationId);
	}

	public function setRememberHoldPickupLocation(bool $rememberPickupLocation) {
		$this->__set('rememberHoldPickupLocation', $rememberPickupLocation);
	}

	function setNumMaterialsRequests($val) {
		$this->_numMaterialsRequests = $val;
	}

	function getNumMaterialsRequests() {
		$this->updateRuntimeInformation();
		return $this->_numMaterialsRequests;
	}

	function getNumSavedEvents($eventsFilter) {
		require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
		$curTime = time();

		$event = new UserEventsEntry();
		if ($eventsFilter == 'past') {
			$event->whereAdd("eventDate < $curTime");
		}
		if ($eventsFilter == 'upcoming') {
			$event->whereAdd("eventDate >= $curTime");
		}
		$event->whereAdd("userId = {$this->id}");
		return $event->count();
	}

	function getNumRatings() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';

		$rating = new UserWorkReview();
		$rating->whereAdd("userId = {$this->id}");
		$rating->whereAdd('rating > 0'); // Some entries are just reviews (and therefore have a default rating of -1)
		return $rating->count();
	}

	function getNumLists() {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';
		$lists = new UserList();
		$lists->user_id = $this->id;
		return $lists->count();
	}

	function getNumNotInterested() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$obj = new NotInterested();
		$obj->whereAdd("userId = {$this->id}");
		return $obj->count();
	}

	function getReadingHistorySizeForYear($year): int {
		if ($this->isReadingHistoryEnabled()) {
			$catalogDriver = $this->getCatalogDriver();
			if ($this->trackReadingHistory && $this->initialReadingHistoryLoaded) {
				require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $this->id;
				$readingHistoryDB->whereAdd('deleted = 0');
				$yearStart = strtotime($year . '-01-01');
				$yearEnd = strtotime(($year + 1) . '-01-01');
				$readingHistoryDB->whereAdd("checkOutDate >= $yearStart");
				$readingHistoryDB->whereAdd("checkOutDate < $yearEnd");
				$readingHistoryDB->groupBy('groupedWorkPermanentId, title, author');
				return $readingHistoryDB->count();
			}
		}
		return 0;
	}

	public function getReadingHistorySummaryForYear($year): ?ReadingHistorySummary {
		if ($this->isReadingHistoryEnabled()) {
			$catalogDriver = $this->getCatalogDriver();
			if ($this->trackReadingHistory && $this->initialReadingHistoryLoaded) {
				require_once ROOT_DIR . '/sys/YearInReview/ReadingHistorySummary.php';
				$summary = new ReadingHistorySummary();

				//Generate Yearly checkouts
				require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $this->id;
				$readingHistoryDB->whereAdd('deleted = 0');
				$yearStart = strtotime($year . '-01-01');
				$yearEnd = strtotime(($year + 1) . '-01-01');
				$readingHistoryDB->whereAdd("checkOutDate >= $yearStart");
				$readingHistoryDB->whereAdd("checkOutDate < $yearEnd");
				$readingHistoryDB->groupBy('groupedWorkPermanentId, title, author');

				$summary->totalYearlyCheckouts = $readingHistoryDB->count();

				// Top author
				$readingHistoryDB->find();
				while ($readingHistoryDB->fetch()) {
					$author = strtolower(preg_replace('/[.|,]/', "", $readingHistoryDB->author));
					if (!empty($author)) {
						$authors[] = $author;
					}
					// Get grouped work IDs for Solr queries for top genres, top series, and recommendations
					$groupedWorkIds[] = $readingHistoryDB->groupedWorkPermanentId;
				}
				$authorTotals = array_count_values($authors);
				arsort($authorTotals);
				// Only choose a top author if patron checked out more than 1 book
				if (reset($authorTotals) > 1) {
					$authorNames = explode(" ", key($authorTotals), 2);
					$summary->topAuthor = ucwords(join(" ", array_reverse($authorNames)));
				}

				// Top Format
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $this->id;
				$readingHistoryDB->whereAdd('deleted = 0');
				$yearStart = strtotime($year . '-01-01');
				$yearEnd = strtotime(($year + 1) . '-01-01');
				$readingHistoryDB->whereAdd("checkOutDate >= $yearStart");
				$readingHistoryDB->whereAdd("checkOutDate < $yearEnd");
				$readingHistoryDB->whereAdd("format IS NOT NULL AND format <> ''");
				$readingHistoryDB->groupBy('format');
				$readingHistoryDB->orderBy('count(format) DESC');
				$readingHistoryDB->limit(0, 3);

				$formatCounts = $readingHistoryDB->fetchAll('count(format)');
				$formatNames = $readingHistoryDB->fetchAll('format');
				$summary->topFormats = $formatNames;

				// Top series and top genres (from facets)
				/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject();
				$searchObject->init();
				$searchObject->disableSpelling();
				$searchObject->disableLogging();
				$searchObject->setQueryIDs(array_slice($groupedWorkIds, 0, 500));
				$searchObject->setPage(1);
				$searchObject->setLimit(10);
				$genreFacet = new LibraryFacetSetting();
				$genreFacet->facetName = 'genre_facet';
				$genreFacet->displayName = 'genre';
				$genreFacet->showAboveResults = false;
				$searchObject->addFacet('genre_facet', $genreFacet);
				$seriesFacet = new LibraryFacetSetting();
				$seriesFacet->facetName = 'series_facet';
				$seriesFacet->displayName = 'series';
				$seriesFacet->showAboveResults = false;
				$searchObject->addFacet('series_facet', $seriesFacet);
				$authorFacet = new LibraryFacetSetting();
				$authorFacet->facetName = 'authorStr';
				$authorFacet->displayName = 'author';
				$searchObject->addFacet('authorStr', $authorFacet);
				$results = $searchObject->processSearch(true, true);
				if ($results['facet_counts'] && $results['facet_counts']['facet_fields'] && $results['facet_counts']['facet_fields']['series_facet']) {
					$seriesFacets = array_slice($results['facet_counts']['facet_fields']['series_facet'], 0, 2);
					foreach ($seriesFacets as $series) {
						$summary->topSeries[] = $series[0];
					}
				}
				if ($results['facet_counts'] && $results['facet_counts']['facet_fields'] && $results['facet_counts']['facet_fields']['genre_facet']) {
					$genreFacets = array_slice($results['facet_counts']['facet_fields']['genre_facet'], 0, 3);
					foreach ($genreFacets as $genres) {
						$summary->topGenres[] = $genres[0];
					}
				}
				$searchObject->close();

				// Recommendations
				/** @var SearchObject_AbstractGroupedWorkSearcher $searchObject */
				$searchObject = SearchObjectFactory::initSearchObject();
				$searchObject->init();
				$searchObject->disableSpelling();
				$searchObject->disableLogging();
				$idsToBaseSuggestionsOn = [];
				foreach ($groupedWorkIds as $groupedWorkId) {
					$idsToBaseSuggestionsOn[] = [
						'workId' => $groupedWorkId,
						'rating' => null
					];
				}
				$recommendations = $searchObject->getMoreLikeThese($idsToBaseSuggestionsOn, 1, 3);
				if ($recommendations['response'] && $recommendations['response']['docs']) {
					foreach ($recommendations['response']['docs'] as $doc) {
						if ($doc['author_display']) {
							$summary->recommendations[] = $doc['title_display'] . " by " . $doc['author_display'];
						} else {
							$summary->recommendations[] = $doc['title_display'];
						}
						$summary->recommendationIds[] = $doc['id'];
					}
				}
				$searchObject->close();

				//Cost Savings By Year
				require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
				$yearlyCostSavings = $this->getCostSavingsByYear($year);
				if ($yearlyCostSavings > 0) {
					$summary->yearlyCostSavings = StringUtils::formatCurrency($yearlyCostSavings);
				}

				$minCheckouts = 10000;
				$maxCheckouts = 0;
				$maxCheckoutMonth = 0;
				for ($month = 1; $month <= 12; $month++) {
					$readingHistoryDB = new ReadingHistoryEntry();
					$readingHistoryDB->userId = $this->id;
					$readingHistoryDB->whereAdd('deleted = 0');
					$monthStart = mktime(0, 0, 0, $month, 1, $year);
					$monthEnd = mktime(0, 0, 0, $month + 1, 1, $year);
					$readingHistoryDB->whereAdd("checkOutDate >= $monthStart");
					$readingHistoryDB->whereAdd("checkOutDate < $monthEnd");
					$readingHistoryDB->groupBy('groupedWorkPermanentId, title, author');

					$numMonthlyCheckouts = $readingHistoryDB->count();
					if ($numMonthlyCheckouts < $minCheckouts) {
						$minCheckouts = $numMonthlyCheckouts;
					}
					if ($numMonthlyCheckouts > $maxCheckouts) {
						$maxCheckouts = $numMonthlyCheckouts;
						$maxCheckoutMonth = $month;
					}
					$summary->monthlyCheckouts[$month] = $numMonthlyCheckouts;
				}

				$summary->topMonth = $maxCheckoutMonth;
				$summary->maxMonthlyCheckouts = $maxCheckouts;
				$summary->averageCheckouts = ceil($summary->totalYearlyCheckouts / 12);


				return $summary;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	function getReadingHistorySize(): int {
		if ($this->_readingHistorySize == null) {
			if ($this->isReadingHistoryEnabled()) {
				$catalogDriver = $this->getCatalogDriver();
				if ($this->trackReadingHistory && $this->initialReadingHistoryLoaded) {
					global $timer;
					require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
					$readingHistoryDB = new ReadingHistoryEntry();
					$readingHistoryDB->userId = $this->id;
					$readingHistoryDB->whereAdd('deleted = 0');
					$readingHistoryDB->groupBy('groupedWorkPermanentId, title, author');
					$this->_readingHistorySize = $readingHistoryDB->count();
					$timer->logTime("Updated reading history size");
				} else {
					$this->_readingHistorySize = 0;
				}
			} else {
				$this->_readingHistorySize = 0;
			}
		}

		return $this->_readingHistorySize;
	}

	function getPatronUpdateForm() {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getPatronUpdateForm($this);
		} else {
			return null;
		}
	}

	/** @noinspection PhpUnused */
	function showMessagingSettings(): bool {
		global $library;
		if ($library->showMessagingSettings) {
			if ($this->hasIlsConnection()) {
				return $this->getCatalogDriver()->showMessagingSettings();
			} else {
				return false;
			}
		}
		return false;
	}

	/** @noinspection PhpUnused */
	function showHoldNotificationPreferences(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showHoldNotificationPreferences();
		} else {
			return false;
		}
	}

	function getMessages(): array {
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		$userMessage = new UserMessage();
		$userMessage->userId = $this->id;
		$userMessage->isDismissed = "0";
		$messages = [];
		$userMessage->find();
		while ($userMessage->fetch()) {
			if (!empty($userMessage->message)) {
				$messages[] = clone $userMessage;
			}
		}
		return $messages;
	}

	function getILSMessages() {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getILSMessages($this);
		} else {
			return false;
		}
	}

	/**
	 * Displays an alert in Aspen Discovery when a user has been linked to.
	 **/
	function newLinkMessage() {
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		require_once ROOT_DIR . '/sys/Account/UserLink.php';

		$userLinks = new UserLink();
		$userLinks->linkedAccountId = $this->id;
		if ($userLinks->find()) {
			//If we already have one of these messages that is unconfirmed, we don't need more.
			$userMessage = new UserMessage();
			$userMessage->userId = $this->id;
			$userMessage->messageType = 'confirm_linked_accts';
			$userMessage->isDismissed = 0;

			if ($userMessage->find() == 0) {
				$userMessage = new UserMessage();
				$userMessage->userId = $this->id;
				$userMessage->messageType = 'confirm_linked_accts';
				$userMessage->message = translate([
					'text' => "Other accounts have linked to your account. Do you want to continue allowing them to link to you?",
					'isPublicFacing' => true,
				]);
				$userMessage->action1Title = translate([
					'text' => "Yes",
					'isPublicFacing' => true,
				]);
				$userMessage->action1 = "return AspenDiscovery.Account.allowAccountLink()";
				$userMessage->action2Title = translate([
					'text' => "Manage Linked Accounts",
					'isPublicFacing' => true,
				]);
				$userMessage->action2 = "return AspenDiscovery.Account.redirectLinkedAccounts()";
				$userMessage->messageLevel = 'warning';
				$userMessage->addendum = translate([
					'text' => "Learn more about linked accounts",
					'isPublicFacing' => true,
				]);
				$userMessage->insert();
			}
		}
	}

	/**
	 * Sends an Aspen LiDA notification when a user has been linked to.
	 **/
	function sendNewLinkNotification(User $initiatingUser): void {
		if ($this->canReceiveNotifications('notifyAccount')) {
			require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';
			require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
			$appScheme = 'aspen-lida';
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			if ($systemVariables && !empty($systemVariables->appScheme)) {
				$appScheme = $systemVariables->appScheme;
			}
			$notificationToken = new UserNotificationToken();
			$notificationToken->userId = $this->id;
			$notificationToken->find();
			while ($notificationToken->fetch()) {
				$body = [
					'to' => $notificationToken->pushToken,
					'title' => 'New account link',
					'body' => 'Your account at ' . $this->getHomeLocation()->displayName . ' was just linked to by ' . $initiatingUser->displayName . ' - ' . $initiatingUser->getHomeLocation()->displayName . '. Review all linked accounts and learn more about account linking at your library.',
					'categoryId' => 'accountAlert',
					'channelId' => 'accountAlert',
					'data' => ['url' => urlencode($appScheme . '://user/linked_accounts')],
				];
				$expoNotification = new ExpoNotification();
				$expoNotification->sendExpoPushNotification($body, $notificationToken->pushToken, $this->id, 'linked_account');
			}
		}
	}

	/**
	 * Displays an alert in Aspen Discovery to the managing account when a user removes the link.
	 **/
	function removeManagingAccountMessage(User $unlinkedUser) {
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		$userMessage = new UserMessage();
		$userMessage->messageType = 'confirm_linked_accts';
		$userMessage->userId = $unlinkedUser->id;
		$userMessage->isDismissed = '0';
		$userMessage->find();
		while ($userMessage->fetch()) {
			$userMessage->isDismissed = 1;
			$userMessage->update();
		}

		$userMessage = new UserMessage();
		$userMessage->messageType = 'linked_acct_notify_removed_' . $unlinkedUser->id;
		$userMessage->userId = $this->id;
		$userMessage->isDismissed = '0';
		$userMessage->message = "An account you were previously linked to, $unlinkedUser->displayName, has removed the link to your account. To learn more about linked accounts, please visit your <a href='/MyAccount/LinkedAccounts'>Linked Accounts</a> page.";
		$userMessage->update();
	}

	/**
	 * Sends an Aspen LiDA notification to the managing account when a user removes the link.
	 **/
	function sendRemoveManagingLinkNotification(User $unlinkedUser): void {
		if ($this->canReceiveNotifications('notifyAccount')) {
			require_once ROOT_DIR . '/sys/Notifications/ExpoNotification.php';
			require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
			$appScheme = 'aspen-lida';
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = SystemVariables::getSystemVariables();
			if ($systemVariables && !empty($systemVariables->appScheme)) {
				$appScheme = $systemVariables->appScheme;
			}
			$notificationToken = new UserNotificationToken();
			$notificationToken->userId = $this->id;
			$notificationToken->find();
			while ($notificationToken->fetch()) {
				$body = [
					'to' => $notificationToken->pushToken,
					'title' => 'Account link removed',
					'body' => 'An account you were previously linked to, ' . $unlinkedUser->displayName . ', has removed the link to your account ' . $this->displayName . '. Learn more about account linking at your library.',
					'categoryId' => 'accountAlert',
					'channelId' => 'accountAlert',
					'data' => ['url' => urlencode($appScheme . '://user/linked_accounts')],
				];
				$expoNotification = new ExpoNotification();
				$expoNotification->sendExpoPushNotification($body, $notificationToken->pushToken, $this->id, 'linked_account');
			}
		}
	}

	function getOverDriveOptions($settingId): array {
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = OverDriveDriver::getOverDriveDriver($settingId);
		return $overDriveDriver->getOptions($this);
	}

	function completeFinePayment(UserPayment $payment) {
		$result = $this->getCatalogDriver()->completeFinePayment($this, $payment);
		if ($result['success']) {
			$payment->completed = 1;
			$payment->update();
		}
		return $result;
	}

	private function fixFieldLengths() {
		if (strlen($this->lastname) > 100) {
			$this->__set('lastname', substr($this->lastname, 0, 100));
		}
		if (strlen($this->firstname) > 50) {
			$this->__set('firstname', substr($this->firstname, 0, 50));
		}
		if (!empty($this->displayName) && strlen($this->displayName) > 60) {
			$this->__set('displayName', substr($this->displayName, 0, 60));
		}
	}

	function eligibleForHolds() {
		if (empty($this->getCatalogDriver())) {
			return false;
		}
		return $this->getCatalogDriver()->patronEligibleForHolds($this);
	}

	function getShowAutoRenewSwitch() {
		if (empty($this->getCatalogDriver())) {
			return false;
		}
		return $this->getCatalogDriver()->getShowAutoRenewSwitch($this);
	}

	function isAutoRenewalEnabledForUser() {
		if (empty($this->getCatalogDriver())) {
			return false;
		}
		return $this->getCatalogDriver()->isAutoRenewalEnabledForUser($this);
	}

	function updateAutoRenewal($allowAutoRenewal) {
		return $this->getCatalogDriver()->updateAutoRenewal($this, $allowAutoRenewal);
	}

	public function getNotInterestedTitles($sinceTime = 0) {
		global $timer;
		$notInterestedTitles = [];
		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterested = new NotInterested();
		$notInterested->userId = $this->id;
		if ($sinceTime > 0) {
			$notInterested->whereAdd("dateMarked >= $sinceTime");
		}
		$notInterested->find();
		while ($notInterested->fetch()) {
			$notInterestedTitles[$notInterested->groupedRecordPermanentId] = $notInterested->groupedRecordPermanentId;
		}
		$timer->logTime("Loaded titles the patron is not interested in");
		return $notInterestedTitles;
	}

	public function getAllIdsNotToSuggest() {
		$idsNotToSuggest = $this->getNotInterestedTitles();
		//Add everything the user has rated
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$ratings = new UserWorkReview();
		$ratings->userId = $this->id;
		$ratings->find();
		while ($ratings->fetch()) {
			$idsNotToSuggest[$ratings->groupedRecordPermanentId] = $ratings->groupedRecordPermanentId;
		}
		//Add everything in the user's reading history
		if ($this->isReadingHistoryEnabled()) {
			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$readingHistoryEntry = new ReadingHistoryEntry();
			$readingHistoryEntry->userId = $this->id;
			$readingHistoryEntry->selectAdd();
			$readingHistoryEntry->selectAdd('groupedWorkPermanentId');
			$readingHistoryEntry->groupBy('groupedWorkPermanentId');
			$readingHistoryEntry->find();
			while ($readingHistoryEntry->fetch()) {
				if (!empty($readingHistoryEntry->groupedWorkPermanentId)) {
					$idsNotToSuggest[$readingHistoryEntry->groupedWorkPermanentId] = $readingHistoryEntry->groupedWorkPermanentId;
				}
			}
		}

		return $idsNotToSuggest;
	}

	/** @noinspection PhpUnused */
	function getHomeLocationName() {
		if ($this->getHomeLocation() != null) {
			return $this->getHomeLocation()->displayName;
		} else {
			return translate([
				'text' => 'N/A',
				'isPublicFacing' => true
			]);
		}
	}

	function getHomeLocation() {
		$location = new Location();
		$location->locationId = $this->homeLocationId;
		if ($location->find(true)) {
			if (empty($this->_homeLocation)) {
				$this->_homeLocation = $location;
			}
			if (empty($this->_homeLocationCode)) {
				$this->_homeLocationCode = $location->code;
			}
			return $location;
		}
		return null;
	}

	function getHomeLocationCode() {
		$homeLocation = $this->getHomeLocation();
		if ($homeLocation != null) {
			return $homeLocation->code;
		} else {
			return null;
		}
	}

	function getPickupLocation() {
		//Check if the library allows patrons to update their pickup locaton, if not, pickup location is always the home location
		$allowCustomPickupLocation = false;
		$homeLocation = $this->getHomeLocation();
		if ($homeLocation != null && $homeLocation->getParentLibrary() != null) {
			$allowCustomPickupLocation = $homeLocation->getParentLibrary()->allowPickupLocationUpdates;
		}
		//Always check if a preferred pickup location has been selected. If not, use the home location
		if ($allowCustomPickupLocation && $this->pickupLocationId > 0 && $this->pickupLocationId != $this->homeLocationId) {
			$pickupBranch = $this->pickupLocationId;
			$locationLookup = new Location();
			$locationLookup->locationId = $pickupBranch;
			//Make sure that the hold location is a valid pickup location just in case it's been hidden since
			if ($locationLookup->find(true) && $locationLookup->validHoldPickupBranch != 2) {
				$pickupBranch = $locationLookup;
			} else {
				$pickupBranch = $homeLocation;
			}
		} else {
			$pickupBranch = $homeLocation;
		}

		return $pickupBranch;
	}

	function getPickupLocationCode() {
		$pickupLocation = $this->getPickupLocation();
		if ($pickupLocation != null) {
			return $pickupLocation->code;
		} else {
			return null;
		}
	}

	function getPickupSublocationCode() {
		$pickupBranch = null;
		if ($this->pickupSublocationId > 0) {
			$pickupBranch = $this->pickupSublocationId;
			$sublocationLookup = new Sublocation();
			$sublocationLookup->locationId = $pickupBranch;
			if ($sublocationLookup->find(true)) {
				$pickupBranch = $sublocationLookup->ilsId;
			}
		}

		return $pickupBranch;
	}

	function getPickupSublocationName() {
		$pickupBranch = null;
		if ($this->pickupSublocationId > 0) {
			$pickupBranch = $this->pickupSublocationId;
			$sublocationLookup = new Sublocation();
			$sublocationLookup->locationId = $pickupBranch;
			if ($sublocationLookup->find(true)) {
				$pickupBranch = $sublocationLookup->name;
			}
		}

		return $pickupBranch;
	}

	function getPickupLocationName() {
		//Always check if a preferred pickup location has been selected. If not, use the home location
		if ($this->pickupLocationId > 0 && $this->pickupLocationId != $this->homeLocationId) {
			$pickupBranch = $this->pickupLocationId;
			$locationLookup = new Location();
			$locationLookup->locationId = $pickupBranch;
			//Make sure that the hold location is a valid pickup location just in case it's been hidden since
			if ($locationLookup->find(true) && $locationLookup->validHoldPickupBranch != 2) {
				$pickupBranch = $locationLookup->displayName;
			} else {
				$pickupBranch = $this->getHomeLocation()->displayName;
			}
		} else {
			$pickupBranch = $this->getHomeLocation()->displayName;
		}

		return $pickupBranch;
	}

	/**
	 * @param string $pickupBranch
	 * @return bool
	 */
	function validatePickupBranch(string &$pickupBranch): bool {
		//Validate the selected pickup branch, we do this in 2 passes, the first looking at the code and the second at the historicCode
		//If the historic code is valid, we replace $pickupBranch with the new code
		$location = new Location();
		$location->code = $pickupBranch;
		$location->find();
		$locationValid = true;
		if ($location->getNumResults() == 1) {
			$location->fetch();
			if ($location->validHoldPickupBranch == 2) {
				//Valid for no one
				$locationValid = false;
			} elseif ($location->validHoldPickupBranch == 0) {
				//Valid for patrons of the branch only
				$locationValid = $location->code == $this->getHomeLocation()->code;
			}
		} else {
			$location = new Location();
			$location->historicCode = $pickupBranch;
			$location->find();
			$locationValid = true;
			if ($location->getNumResults() == 1) {
				$location->fetch();
				if ($location->validHoldPickupBranch == 2) {
					//Valid for no one
					$locationValid = false;
				} elseif ($location->validHoldPickupBranch == 0) {
					//Valid for patrons of the branch only
					$locationValid = $location->code == $this->getHomeLocation()->code;
				}
				if ($locationValid) {
					$pickupBranch = $location->code;
				}
			} else {
				//Location is deleted
				$locationValid = false;
			}
		}
		return $locationValid;
	}

	public function hasEditableUsername() {
		if ($this->hasIlsConnection()) {
			$homeLibrary = $this->getHomeLibrary();
			if ($homeLibrary != null && $homeLibrary->allowUsernameUpdates) {
				return $this->getCatalogDriver()->hasEditableUsername();
			}
		}
		return false;
	}

	public function getEditableUsername() {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getEditableUsername($this);
		} else {
			return null;
		}
	}

	public function updateEditableUsername(string $username): array {
		if ($this->hasIlsConnection()) {
			if (empty($username)) {
				return [
					'success' => false,
					'message' => 'A new username was not provided',
				];
			} else {
				return $this->getCatalogDriver()->updateEditableUsername($this, $username);
			}
		} else {
			return [
				'success' => false,
				'message' => 'This user is not connected to an ILS',
			];
		}
	}

	public function logout() {
		if ($this->hasIlsConnection()) {
			$this->getCatalogDriver()->logout($this);
		}
		$this->__set('lastLoginValidation', 0);
		$this->__set('isLoggedInViaSSO', 0);
		$this->update();
	}

	public function treatVolumeHoldsAsItemHolds() {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->treatVolumeHoldsAsItemHolds();
		}
		return false;
	}

	public function getAdminActions() {
		require_once ROOT_DIR . '/sys/AdminSection.php';
		global $library;
		global $configArray;
		global $enabledModules;
		$sections = [];

		if (count($this->getRoles()) == 0) {
			return $sections;
		}
		$sections['system_admin'] = new AdminSection('System Administration');
		$sections['system_admin']->addAction(new AdminAction('Modules', 'Enable and disable sections of Aspen Discovery.', '/Admin/Modules'), 'Administer Modules');
		$sections['system_admin']->addAction(new AdminAction('Administrators', 'Define users from the ILS who should have administration privileges.', '/Admin/Administrators'), 'Administer Users');
		$sections['system_admin']->addAction(new AdminAction('Local Administrators', 'Define local Aspen users who should have administration privileges.', '/Admin/LocalAdministrators'), 'Manage Local Administrators');
		$sections['system_admin']->addAction(new AdminAction('Permissions', 'Define who what each role in the system can do.', '/Admin/Permissions'), 'Administer Permissions');
		$sections['system_admin']->addAction(new AdminAction('DB Maintenance', 'Update the database when new versions of Aspen Discovery are released.', '/Admin/DBMaintenance'), 'Run Database Maintenance');
		$sections['system_admin']->addAction(new AdminAction('Optional Updates', 'Recommended updates that can be optionally applied when new versions of Aspen Discovery are released.', '/Admin/OptionalUpdates'), 'Run Optional Updates');
		$sections['system_admin']->addAction(new AdminAction('Twilio Settings', 'Settings to allow Aspen Discovery to send texts via Twilio.', '/Admin/TwilioSettings'), 'Administer Twilio');
		$sections['system_admin']->addAction(new AdminAction('USPS Settings', 'Settings to allow Aspen Discovery to validate addresses via USPS API.', '/Admin/USPS'), 'Administer System Variables');
		$sections['system_admin']->addAction(new AdminAction('Variables', 'Variables set by the Aspen Discovery itself as part of background processes.', '/Admin/Variables'), 'Administer System Variables');
		$sections['system_admin']->addAction(new AdminAction('System Variables', 'Settings for Aspen Discovery that apply to all libraries on this installation.', '/Admin/SystemVariables'), 'Administer System Variables');

		$sections['system_reports'] = new AdminSection('System Reports');
		$sections['system_reports']->addAction(new AdminAction('Site Status', 'View Status of Aspen Discovery.', '/Admin/SiteStatus'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Usage Dashboard', 'Usage Report for Aspen Discovery.', '/Admin/UsageDashboard'), [
			'View Dashboards',
			'View System Reports',
		]);
		$sections['system_reports']->addAction(new AdminAction('API Usage Dashboard', 'API Usage Report for Aspen Discovery.', '/API/UsageDashboard'), [
			'View Dashboards',
			'View System Reports',
		]);
		$sections['system_reports']->addAction(new AdminAction('Usage By IP Address', 'Reports which IP addresses have used Aspen Discovery.', '/Admin/UsageByIP'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Usage By User Agent', 'Reports which User Agents have used Aspen Discovery.', '/Admin/UsageByUserAgent'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Nightly Index Log', 'Nightly indexing log for Aspen Discovery. The nightly index updates all records if needed.', '/Admin/ReindexLog'), [
			'View System Reports',
			'View Indexing Logs',
		]);
		$sections['system_reports']->addAction(new AdminAction('Cron Log', 'View Cron Log. The cron process handles periodic cleanup tasks and updates reading history for users.', '/Admin/CronLog'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Background Processes', 'View information about background processes that are being run.', '/Admin/BackgroundProcesses'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Saved Search Notifications Log', 'A log of searches that have been checked for new results to generate notices.', '/Admin/SearchUpdateLog'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Performance Report', 'View Aspen Performance Report.', '/Admin/PerformanceReport'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Error Log', 'View Aspen Error Log.', '/Admin/ErrorReport'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('PHP Information', 'Display configuration information for PHP on the server.', '/Admin/PHPInfo'), 'View System Reports');

		$sections['theme_and_layout'] = new AdminSection('Theme & Layout');
		$sections['theme_and_layout']->addAction(new AdminAction('Themes', 'Define colors, fonts, images etc used within Aspen Discovery.', '/Admin/Themes'), [
			'Administer All Themes',
			'Administer Library Themes',
		]);
		$sections['theme_and_layout']->addAction(new AdminAction('Layout Settings', 'Define basic information about how pages are displayed in Aspen Discovery.', '/Admin/LayoutSettings'), [
			'Administer All Layout Settings',
			'Administer Library Layout Settings',
		]);

		$sections['primary_configuration'] = new AdminSection('Primary Configuration');
		$librarySettingsAction = new AdminAction('Library Systems', 'Configure library settings.', '/Admin/Libraries');
		$locationSettingsAction = new AdminAction('Locations', 'Configure location settings.', '/Admin/Locations');
		$ipAddressesAction = new AdminAction('IP Addresses', 'Configure IP addresses for each location and configure rules to block access to Aspen Discovery.', '/Admin/IPAddresses');
		$administerHostAction = new AdminAction('Host Information', 'Allows configuration of domain names to point to different sections of Aspen Discovery', '/Admin/Hosting');
		if ($sections['primary_configuration']->addAction($librarySettingsAction, [
			'Administer All Libraries',
			'Administer Home Library',
		])) {
			$librarySettingsAction->addSubAction($locationSettingsAction, [
				'Administer All Locations',
				'Administer Home Library Locations',
				'Administer Home Location',
			]);
			$librarySettingsAction->addSubAction($ipAddressesAction, 'Administer IP Addresses');
			$librarySettingsAction->addSubAction($administerHostAction, 'Administer Host Information');
		} else {
			$sections['primary_configuration']->addAction($locationSettingsAction, [
				'Administer All Locations',
				'Administer Home Library Locations',
				'Administer Home Location',
			]);
			$sections['primary_configuration']->addAction($ipAddressesAction, 'Administer IP Addresses');
			$sections['primary_configuration']->addAction($administerHostAction, 'Administer Host Information');
		}
		$sections['primary_configuration']->addAction(new AdminAction('Block Patron Account Linking', 'Prevent accounts from linking to other accounts.', '/Admin/BlockPatronAccountLinks'), 'Block Patron Account Linking');
		$sections['primary_configuration']->addAction(new AdminAction('Patron Types', 'Modify Permissions and limits based on Patron Type.', '/Admin/PTypes'), 'Administer Patron Types');
		$sections['primary_configuration']->addAction(new AdminAction('Account Profiles', 'Define how account information is loaded from the ILS.', '/Admin/AccountProfiles'), 'Administer Account Profiles');
		$sections['primary_configuration']->addAction(new AdminAction('User Agents', 'Configure User Agents and block access to Aspen Discovery by User Agent.', '/Admin/UserAgents'), 'Administer User Agents');
		$sections['primary_configuration']->addAction(new AdminAction('Two-Factor Authentication', 'Administer two-factor authentication settings', '/Admin/TwoFactorAuth'), 'Administer Two-Factor Authentication');

		if (array_key_exists('Single sign-on', $enabledModules)) {
			$sections['primary_configuration']->addAction(new AdminAction('Single Sign-on (SSO)', 'Administer single sign-on settings', '/Admin/SSOSettings'), 'Administer Single Sign-on');
		}

		//Materials Request if enabled
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
		if (MaterialsRequest::enableAspenMaterialsRequest()) {
			if ($library->enableMaterialsRequest == 1) {
				$sections['materials_request'] = new AdminSection('Materials Requests');
				$sections['materials_request']->addAction(new AdminAction('Manage Requests', 'Manage Materials Requests from users.', '/MaterialsRequest/ManageRequests'), 'Manage Library Materials Requests');
				$sections['materials_request']->addAction(new AdminAction('Requests Needing Holds', 'Review and generate holds for requests that have hold candidates.', '/MaterialsRequest/RequestsNeedingHolds'), 'Place Holds For Materials Requests');
				$sections['materials_request']->addAction(new AdminAction('Usage Dashboard', 'View the usage dashboard for Materials Requests.', '/MaterialsRequest/Dashboard'), 'View Materials Requests Reports');
				$sections['materials_request']->addAction(new AdminAction('Summary Report', 'A Summary Report of all requests that have been submitted.', '/MaterialsRequest/SummaryReport'), 'View Materials Requests Reports');
				$sections['materials_request']->addAction(new AdminAction('Report By User', 'A Report of all requests that have been submitted by users who submitted them.', '/MaterialsRequest/UserReport'), 'View Materials Requests Reports');
				$sections['materials_request']->addAction(new AdminAction('Hold Candidate Generation Log', 'A log showing information about the hold candidate generation process for materials requests.', '/MaterialsRequest/HoldCandidateGenerationLog'), 'View Materials Requests Reports');
				$sections['materials_request']->addAction(new AdminAction('Format Mapping', 'Define format mapping between Aspen formats and Materials Request Formats for use when placing holds.', '/MaterialsRequest/FormatMapping'), 'Administer Materials Requests');
				$sections['materials_request']->addAction(new AdminAction('Manage Statuses', 'Define the statuses of Materials Requests for the library.', '/MaterialsRequest/ManageStatuses'), 'Administer Materials Requests');
			}
		}

		if (array_key_exists('Web Builder', $enabledModules)) {
			$sections['web_builder'] = new AdminSection('Web Builder');
			//$sections['web_builder']->addAction(new AdminAction('Menu', 'Define additional options that appear in the menu.', '/WebBuilder/Menus'), ['Administer All Menus', 'Administer Library Menus']);
			$sections['web_builder']->addAction(new AdminAction('Basic Pages', 'Create basic pages with a simple layout.', '/WebBuilder/BasicPages'), [
				'Administer All Basic Pages',
				'Administer Library Basic Pages',
			]);
			if (!empty(SystemVariables::getSystemVariables()->enableGrapesEditor)) {
				$sections['web_builder']->addAction(new AdminAction('Grapes Pages', 'Create pages with the Grapes JS visual editor.', '/WebBuilder/GrapesPages'), [
					'Administer All Grapes Pages',
					'Administer Library Grapes Pages',
				]);
				$sections['web_builder']->addAction(new AdminAction('Grapes Templates', 'Create templates with the Grapes JS visual editor.', '/WebBuilder/Templates'), [
					'Administer All Grapes Pages',
					'Administer Library Grapes Pages',
				]);
			}
			$sections['web_builder']->addAction(new AdminAction('Custom Pages', 'Create custom pages with a more complex cell based layout.', '/WebBuilder/PortalPages'), [
				'Administer All Custom Pages',
				'Administer Library Custom Pages',
			]);
			$sections['web_builder']->addAction(new AdminAction('Custom Forms', 'Create custom forms within Aspen Discovery for patrons to fill out.', '/WebBuilder/CustomForms'), [
				'Administer All Custom Forms',
				'Administer Library Custom Forms',
			]);
			$sections['web_builder']->addAction(new AdminAction('Quick Polls', 'Create quick polls within Aspen Discovery for patrons to respond to.', '/WebBuilder/QuickPolls'), [
				'Administer All Quick Polls',
				'Administer Library Quick Polls',
			]);
			$sections['web_builder']->addAction(new AdminAction('Web Resources', 'Add resources within Aspen Discovery that the library provides.', '/WebBuilder/WebResources'), [
				'Administer All Web Resources',
				'Administer Library Web Resources',
			]);
			$sections['web_builder']->addAction(new AdminAction('Web Resource Settings', 'Settings for Web Resources including which custom pages get indexed.', '/WebBuilder/WebResourceSettings'), [
				'Administer All Web Resources',
			]);
			$sections['web_builder']->addAction(new AdminAction('Custom Web Resource Pages', 'Create custom web resource pages to display web resources belonging to specific categories and audiences.', '/WebBuilder/CustomWebResourcePages'), [
				'Administer All Custom Web Resource Pages',
				'Administer Library Custom Web Resource Pages',
			]);
			$sections['web_builder']->addAction(new AdminAction('Staff Members', 'Add staff members to create a staff directory.', '/WebBuilder/StaffMembers'), [
				'Administer All Staff Members',
				'Administer Library Staff Members',
			]);
			$sections['web_builder']->addAction(new AdminAction('Images', 'Add images to Aspen Discovery.', '/WebBuilder/Images'), ['Administer All Web Content']);
			$sections['web_builder']->addAction(new AdminAction('PDFs', 'Add PDFs to Aspen Discovery.', '/WebBuilder/PDFs'), ['Administer All Web Content']);
			//$sections['web_builder']->addAction(new AdminAction('Videos', 'Add Videos to Aspen Discovery.', '/WebBuilder/Videos'), ['Administer All Web Content']);
			$sections['web_builder']->addAction(new AdminAction('Audiences', 'Define Audiences to categorize content within Aspen Discovery.', '/WebBuilder/Audiences'), ['Administer All Web Categories']);
			$sections['web_builder']->addAction(new AdminAction('Categories', 'Define Categories to categorize content within Aspen Discovery.', '/WebBuilder/Categories'), ['Administer All Web Categories']);
		}

		$sections['translations'] = new AdminSection('Languages and Translations');
		$sections['translations']->addAction(new AdminAction('Languages', 'Define which languages are available within Aspen Discovery.', '/Translation/Languages'), 'Administer Languages');
		$sections['translations']->addAction(new AdminAction('Translations', 'Translate the user interface of Aspen Discovery.', '/Translation/Translations'), 'Translate Aspen');

		if (array_key_exists('Community Engagement', $enabledModules)) {
			$sections['communityEngagement'] = new AdminSection('Community Engagement');
			$sections['communityEngagement']->addAction(new AdminAction('Campaigns', 'Create and view campaigns.', '/CommunityEngagement/Campaigns'), [
				'Administer Community Engagement Module',
			]);
			$sections['communityEngagement']->addAction(new AdminAction('Milestone Criteria', 'Create and view milestones.', '/CommunityEngagement/Milestones'), [
				'Administer Community Engagement Module',
			]);
			$sections['communityEngagement']->addAction(new AdminAction('Rewards', 'Create and view rewards.', '/CommunityEngagement/Rewards'), [
				'Administer Community Engagement Module',
			]);
			$sections['communityEngagement']->addAction(new AdminAction('Admin View', 'View progress and manage rewards.', '/CommunityEngagement/AdminView'), [
				'View Community Engagement Dashboard',
			]);
			$sections['communityEngagement']->addAction(new AdminAction('Dashboard', 'View usage dashboard for Community Engagement.', '/CommunityEngagement/Dashboard'), [
				'View Community Engagement Dashboard',
			]);
		}
		$sections['cataloging'] = new AdminSection('Catalog / Grouped Works');
		$groupedWorkAction = new AdminAction('Grouped Work Display', 'Define information about what is displayed for Grouped Works in search results and full record displays.', '/Admin/GroupedWorkDisplay');
		$groupedWorkAction->addSubAction(new AdminAction('Grouped Work Facets', 'Define information about what facets are displayed for grouped works in search results and Advanced Search.', '/Admin/GroupedWorkFacets'), [
			'Administer All Grouped Work Facets',
			'Administer Library Grouped Work Facets',
		]);
		$groupedWorkAction->addSubAction(new AdminAction('Format Sorting', 'Define how formats are sorted within a Grouped Work.', '/Admin/GroupedWorkFormatSorting'), [
			'Administer All Format Sorting',
			'Administer Library Format Sorting',
		]);
		$sections['cataloging']->addAction($groupedWorkAction, [
			'Administer All Grouped Work Display Settings',
			'Administer Library Grouped Work Display Settings',
		]);
		$sections['cataloging']->addAction(new AdminAction('Manual Grouping Authorities', 'View a list of all title author/authorities that have been added to Aspen to merge works.', '/Admin/AlternateTitles'), 'Manually Group and Ungroup Works');
		$sections['cataloging']->addAction(new AdminAction('Author Authorities', 'Create and edit authorities for authors.', '/Admin/AuthorAuthorities'), 'Manually Group and Ungroup Works');
		$sections['cataloging']->addAction(new AdminAction('Records To Not Group', 'Lists records that should not be grouped.', '/Admin/NonGroupedRecords'), 'Manually Group and Ungroup Works');
		$sections['cataloging']->addAction(new AdminAction('Replacement Costs', 'Define default replacement costs by format.', '/Admin/ReplacementCosts'), 'Administer Replacement Costs');
		$sections['cataloging']->addAction(new AdminAction('Hidden Series', 'Edit series to be excluded from the Series facet and Series Display Information', '/Admin/HideSeriess'), 'Hide Metadata');
		$sections['cataloging']->addAction(new AdminAction('Hidden Subjects', 'Edit subjects to be excluded from the Subjects facet.', '/Admin/HideSubjectFacets'), 'Hide Metadata');
		$sections['cataloging']->addAction(new AdminAction('Search Tests', 'Tests to be run to verify searching is generating optimal results.', '/Admin/GroupedWorkSearchTests'), 'Administer Grouped Work Tests');

		//$sections['cataloging']->addAction(new AdminAction('Print Barcodes', 'Lists records that should not be grouped.', '/Admin/PrintBarcodes'), 'Print Barcodes');

		$sections['local_enrichment'] = new AdminSection('Local Catalog Enrichment');
		$sections['local_enrichment']->addAction(new AdminAction('Bad Words List', 'Define the list of words to be censored.', '/Admin/BadWords'), ['Administer Bad Words']);
		$browseCategoryGroupsAction = new AdminAction('Browse Category Groups', 'Configure the Browse Categories that are shown on the library home page.', '/Admin/BrowseCategoryGroups');
		$browseCategoryGroupsAction->addSubAction(new AdminAction('Browse Categories', 'Define browse categories shown on the library home page.', '/Admin/BrowseCategories'), [
			'Administer All Browse Categories',
			'Administer Library Browse Categories',
			'Administer Selected Browse Category Groups'
		]);
		$sections['local_enrichment']->addAction($browseCategoryGroupsAction, [
			'Administer All Browse Categories',
			'Administer Library Browse Categories',
			'Administer Selected Browse Category Groups'
		]);
		$sections['local_enrichment']->addAction(new AdminAction('Collection Spotlights', 'Define spotlights that can be embedded within Aspen custom pages or other websites.', '/Admin/CollectionSpotlights'), [
			'Administer All Collection Spotlights',
			'Administer Library Collection Spotlights',
		]);
		$sections['local_enrichment']->addAction(new AdminAction('JavaScript Snippets', 'JavaScript Snippets to be added to the site when pages are rendered.', '/Admin/JavaScriptSnippets'), [
			'Administer All JavaScript Snippets',
			'Administer Library JavaScript Snippets',
		]);
		$sections['local_enrichment']->addAction(new AdminAction('Placards', 'Placards allow you to promote services that do not have MARC records or APIs for inclusion in the catalog.', '/Admin/Placards'), [
			'Administer All Placards',
			'Administer Library Placards',
			'Edit Library Placards',
		]);
		$sections['local_enrichment']->addAction(new AdminAction('System Messages', 'System Messages allow you to display messages to your patrons in specific locations.', '/Admin/SystemMessages'), [
			'Administer All System Messages',
			'Administer Library System Messages',
		]);
		$sections['local_enrichment']->addAction(new AdminAction('Year in Review', 'Year in Review allows you to display a summary slideshow to patrons at the start of each year.', '/Admin/YearInReview'), [
			'Administer Year in Review for All Libraries',
			'Administer Year in Review for Home Library',
		]);

		$sections['third_party_enrichment'] = new AdminSection('Third Party Enrichment');
		$sections['third_party_enrichment']->addAction(new AdminAction('Accelerated Reader Settings', 'Define settings to load Accelerated Reader information directly from Renaissance Learning.', '/Enrichment/ARSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Coce Server Settings', 'Define settings to load covers from a Coce server.', '/Enrichment/CoceServerSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('ContentCafe Settings', 'Define settings for ContentCafe integration.', '/Enrichment/ContentCafeSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('DP.LA Settings', 'Define settings for DP.LA integration.', '/Enrichment/DPLASettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Google API Settings', 'Define settings for integrating Google APIs within Aspen Discovery.', '/Enrichment/GoogleApiSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('LibKey Settings', 'Administer LibKey Settings', '/Admin/LibKeySettings'), 'Administer LibKey Settings');
		$nytSettingsAction = new AdminAction('New York Times Settings', 'Define settings for integrating New York Times Content within Aspen Discovery.', '/Enrichment/NewYorkTimesSettings');
		$nytListsAction = new AdminAction('New York Times Lists', 'View Lists from the New York Times and manually refresh content.', '/Enrichment/NYTLists');
		if ($sections['third_party_enrichment']->addAction($nytSettingsAction, 'Administer Third Party Enrichment API Keys')) {
			$nytSettingsAction->addSubAction($nytListsAction, 'View New York Times Lists');
		} else {
			$sections['third_party_enrichment']->addAction($nytListsAction, 'View New York Times Lists');
		}
		$sections['third_party_enrichment']->addAction(new AdminAction('NoveList Settings', 'Define settings for integrating NoveList within Aspen Discovery.', '/Enrichment/NovelistSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('NoveList API Information', 'View API information for NoveList.', '/Enrichment/NovelistAPIData'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('OMDB Settings', 'Define settings for integrating OMDB within Aspen Discovery.', '/Enrichment/OMDBSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Quipu Settings', 'Define settings for integrating Quipu eCARD & eRENEW within Aspen Discovery.', '/Enrichment/QuipuECardSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('reCAPTCHA Settings', 'Define settings for using reCAPTCHA within Aspen Discovery.', '/Enrichment/RecaptchaSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Rosen LevelUP Settings', 'Define settings for allowing students and parents to register for Rosen LevelUP.', '/Rosen/RosenLevelUPSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Syndetics Settings', 'Define settings for Syndetics integration.', '/Enrichment/SyndeticsSettings'), 'Administer Third Party Enrichment API Keys');

		if (array_key_exists('Talpa Search', $enabledModules)) {
			$sections['third_party_enrichment']->addAction(new AdminAction('Talpa Search', 'Define connection information and settings for Talpa.', '/Talpa/TalpaSettings'), 'Administer Third Party Enrichment API Keys');
		}

		$sections['third_party_enrichment']->addAction(new AdminAction('Wikipedia Integration', 'Modify which Wikipedia content is displayed for authors.', '/Admin/AuthorEnrichment'), 'Administer Wikipedia Integration');


		$sections['ecommerce'] = new AdminSection('eCommerce');
		$sections['ecommerce']->addAction(new AdminAction('eCommerce Report', 'View payments initiated and completed within the system', '/Admin/eCommerceReport'), [
			'View eCommerce Reports for All Libraries',
			'View eCommerce Reports for Home Library'
		]);
		$sections['ecommerce']->addAction(new AdminAction('Donations Report', 'View donations initiated and completed within the system', '/Admin/DonationsReport'), [
			'View Donations Reports for All Libraries',
			'View Donations Reports for Home Library'
		]);
		$sections['ecommerce']->addAction(new AdminAction('Comprise Settings', 'Define Settings for Comprise SMARTPAY.', '/Admin/CompriseSettings'), 'Administer Comprise');
		$sections['ecommerce']->addAction(new AdminAction('FIS WorldPay Settings', 'Define Settings for FIS WorldPay.', '/Admin/WorldPaySettings'), 'Administer WorldPay');
		$sections['ecommerce']->addAction(new AdminAction('PayPal Settings', 'Define Settings for PayPal.', '/Admin/PayPalSettings'), 'Administer PayPal');
		//PROPAY $sections['ecommerce']->addAction(new AdminAction('ProPay Settings', 'Define Settings for ProPay.', '/Admin/ProPaySettings'), 'Administer ProPay');
		$sections['ecommerce']->addAction(new AdminAction('Xpress-pay Settings', 'Define Settings for Xpress-pay.', '/Admin/XpressPaySettings'), 'Administer Xpress-pay');
		$sections['ecommerce']->addAction(new AdminAction('ACI Speedpay Settings', 'Define Settings for ACI Speedpay.', '/Admin/ACISpeedpaySettings'), 'Administer ACI Speedpay');
		$sections['ecommerce']->addAction(new AdminAction('InvoiceCloud Settings', 'Define Settings for InvoiceCloud.', '/Admin/InvoiceCloudSettings'), 'Administer InvoiceCloud');
		$sections['ecommerce']->addAction(new AdminAction('Certified Payments by Deluxe Settings', 'Define Settings for Certified Payments by Deluxe.', '/Admin/CertifiedPaymentsByDeluxeSettings'), 'Administer Certified Payments by Deluxe');
		$sections['ecommerce']->addAction(new AdminAction('PayPal Payflow Settings', 'Define Settings for PayPal Payflow.', '/Admin/PayPalPayflowSettings'), 'Administer PayPal Payflow');
		$sections['ecommerce']->addAction(new AdminAction('SnapPay Settings', 'Define Settings for SnapPay.', '/Admin/SnapPaySettings'), 'Administer SnapPay');
		$sections['ecommerce']->addAction(new AdminAction('Square Settings', 'Define Settings for Square.', '/Admin/SquareSettings'), 'Administer Square');
		$sections['ecommerce']->addAction(new AdminAction('Stripe Settings', 'Define Settings for Stripe.', '/Admin/StripeSettings'), 'Administer Stripe');
		$sections['ecommerce']->addAction(new AdminAction('NCR Payments Settings', 'Define Settings for NCR Payments.', '/Admin/NCRPaymentsSettings'), 'Administer NCR');
		$sections['ecommerce']->addAction(new AdminAction('Donations Settings', 'Define Settings for Donations.', '/Admin/DonationsSettings'), 'Administer Donations');

		$sections['email'] = new AdminSection('Email');
		$sections['email']->addAction(new AdminAction('Email Templates', 'Templates for various emails sent from Aspen Discovery.', '/Admin/EmailTemplates'), [
			'Administer All Email Templates',
			'Administer Library Email Templates'
		]);
		$sections['email']->addAction(new AdminAction('Amazon SES Settings', 'Settings to allow Aspen Discovery to send emails via Amazon SES.', '/Admin/AmazonSesSettings'), 'Administer Amazon SES');
		$sections['email']->addAction(new AdminAction('Send Grid Settings', 'Settings to allow Aspen Discovery to send emails via SendGrid.', '/Admin/SendGridSettings'), 'Administer SendGrid');
		$sections['email']->addAction(new AdminAction('SMTP Settings', 'Settings to allow Aspen Discovery to send emails via an SMTP server.', '/Admin/SMTPSettings'), 'Administer SMTP');

		$sections['ils_integration'] = new AdminSection('ILS Integration');
		$indexingProfileAction = new AdminAction('Indexing Profiles', 'Define how records from the ILS are loaded into Aspen Discovery.', '/ILS/IndexingProfiles');
		$translationMapsAction = new AdminAction('Translation Maps', 'Define how field values are mapped between the ILS and Aspen Discovery.', '/ILS/TranslationMaps');
		if ($sections['ils_integration']->addAction($indexingProfileAction, 'Administer Indexing Profiles')) {
			$indexingProfileAction->addSubAction($translationMapsAction, 'Administer Translation Maps');
		} else {
			$sections['ils_integration']->addAction($translationMapsAction, 'Administer Translation Maps');
		}

		$hasCurbside = false;
		$allowILSMessaging = false;
		$customSelfRegForms = false;
		$circulationReports = false;
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'koha') {
				$hasCurbside = true;
				$allowILSMessaging = true;
			}
			if ($accountProfile->ils == 'symphony' || $accountProfile->ils == 'carlx' || $accountProfile->ils == 'sierra') {
				$customSelfRegForms = true;
			}
			if ($accountProfile->driver == 'Nashville') {
				$circulationReports = true;
			}
		}
		if ($hasCurbside) {
			$sections['ils_integration']->addAction(new AdminAction('Curbside Pickup Settings', 'Define Settings for Curbside Pickup, requires Koha Curbside plugin', '/ILS/CurbsidePickupSettings'), ['Administer Curbside Pickup']);
		}
		if ($customSelfRegForms) {
			$sections['ils_integration']->addAction(new AdminAction('Self Registration Forms', 'Create self registration forms.', '/ILS/SelfRegistrationForms'), ['Administer Self Registration Forms']);
			$sections['ils_integration']->addAction(new AdminAction('Self Registration TOS', 'Create self registration Terms of Service pages.', '/ILS/SelfRegistrationTOS'), ['Administer Self Registration Forms']);
		}
		$sections['ils_integration']->addAction(new AdminAction('Indexing Log', 'View the indexing log for ILS records.', '/ILS/IndexingLog'), 'View Indexing Logs');
		$sections['ils_integration']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for ILS integration.', '/ILS/Dashboard'), [
			'View Dashboards',
			'View System Reports',
		]);
		$sections['ils_integration']->addAction(new AdminAction('Test Self Check', 'Test Self Check functionality within Aspen and Aspen / LiDA.', '/ILS/SelfCheckTester'), 'Test Self Check');

		$sections['ill_integration'] = new AdminSection('Interlibrary Loan');
		$sections['ill_integration']->addAction(new AdminAction('Hold Groups', 'Modify Hold Groups for creating interlibrary loan holds.', '/InterLibraryLoan/HoldGroups'), 'Administer Hold Groups');
		$sections['ill_integration']->addAction(new AdminAction('Local ILL Forms', 'Configure Forms for submitting Local ILL requests.', '/InterLibraryLoan/LocalIllForms'), [
			'Administer All Local ILL Forms',
			'Administer Library Local ILL Forms',
		]);
		$sections['ill_integration']->addAction(new AdminAction('VDX Settings', 'Define Settings for VDX Integration', '/VDX/VDXSettings'), ['Administer VDX Settings']);
		$sections['ill_integration']->addAction(new AdminAction('VDX Forms', 'Configure Forms for submitting VDX information.', '/VDX/VDXForms'), [
			'Administer All VDX Forms',
			'Administer Library VDX Forms',
		]);

		$sections['circulation_reports'] = new AdminSection('Circulation Reports');
		$sections['circulation_reports']->addAction(new AdminAction('Barcode Generator', 'Create Code39 barcodes on Avery 5160 labels from a list of numbers.', '/Report/BarcodeGenerator'), [
			'Barcode Generators',
		]);
		$sections['circulation_reports']->addAction(new AdminAction('Barcode Generator - Disc', 'Create hub EAN-8 barcodes for CDs and DVDs.', '/Report/DiscBarcodeGenerator'), [
			'Barcode Generators',
		]);
		if ($circulationReports) {
			$sections['circulation_reports']->addAction(new AdminAction('Collection Report', 'View a report of all items for a branch.', '/Report/CollectionReport'), [
				'View Location Collection Reports',
				'View All Collection Reports',
			]);
			$sections['circulation_reports']->addAction(new AdminAction('Holds Report', 'View a report of holds to be pulled from the shelf for patrons.', '/Report/HoldsReport'), [
				'View Location Holds Reports',
				'View All Holds Reports',
			]);
			$sections['circulation_reports']->addAction(new AdminAction('Student Barcodes', 'View/print a report of all barcodes for a class.', '/Report/StudentBarcodes'), [
				'View Location Student Reports',
				'View All Student Reports',
			]);
			$sections['circulation_reports']->addAction(new AdminAction('Student Checkout Report', 'View a report of all checkouts for a given class with filtering to only show overdue items and lost items.', '/Report/StudentReport'), [
				'View Location Student Reports',
				'View All Student Reports',
			]);
			$sections['circulation_reports']->addAction(new AdminAction('Weeding Report', 'View a collection weeding report for all items for a branch.', '/Report/WeedingReport'), [
				'View Location Collection Reports',
				'View All Collection Reports',
			]);
		}

		if (array_key_exists('Axis 360', $enabledModules)) {
			$sections['boundless'] = new AdminSection('Boundless');
			$axis360SettingsAction = new AdminAction('Settings', 'Define connection information between Boundless and Aspen Discovery.', '/Axis360/Settings');
			$axis360ScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/Axis360/Scopes');
			if ($sections['boundless']->addAction($axis360SettingsAction, 'Administer Boundless')) {
				$axis360SettingsAction->addSubAction($axis360ScopesAction, 'Administer Boundless');
			} else {
				$sections['boundless']->addAction($axis360ScopesAction, 'Administer Boundless');
			}
			$sections['boundless']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Boundless.', '/Axis360/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['boundless']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Boundless integration.', '/Axis360/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('Cloud Library', $enabledModules)) {
			$sections['cloud_library'] = new AdminSection('cloudLibrary');
			$cloudLibrarySettingsAction = new AdminAction('Settings', 'Define connection information between cloudLibrary and Aspen Discovery.', '/CloudLibrary/Settings');
			$cloudLibraryScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/CloudLibrary/Scopes');
			if ($sections['cloud_library']->addAction($cloudLibrarySettingsAction, 'Administer Cloud Library')) {
				$cloudLibrarySettingsAction->addSubAction($cloudLibraryScopesAction, 'Administer Cloud Library');
			} else {
				$sections['cloud_library']->addAction($cloudLibraryScopesAction, 'Administer Cloud Library');
			}
			$sections['cloud_library']->addAction(new AdminAction('Indexing Log', 'View the indexing log for cloudLibrary.', '/CloudLibrary/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['cloud_library']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for cloudLibrary integration.', '/CloudLibrary/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('EBSCO EDS', $enabledModules)) {
			$sections['ebsco'] = new AdminSection('EBSCO EDS');
			$sections['ebsco']->addAction(new AdminAction('Settings', 'Define connection information between EBSCO EDS and Aspen Discovery.', '/EBSCO/EDSSettings'), 'Administer EBSCO EDS');
			$sections['ebsco']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for EBSCO EDS integration.', '/EBSCO/EDSDashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('EBSCOhost', $enabledModules)) {
			$sections['ebscohost'] = new AdminSection('EBSCOhost');
			$sections['ebscohost']->addAction(new AdminAction('Settings', 'Define connection information between EBSCOhost and Aspen Discovery.', '/EBSCO/EBSCOhostSettings'), 'Administer EBSCOhost Settings');
			$sections['ebscohost']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for EBSCOhost integration.', '/EBSCOhost/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('Summon', $enabledModules)) {
			$sections['summon'] = new AdminSection('Summon');
			$sections['summon']->addAction(new AdminAction('Settings', 'Define connection information between Summon and Aspen Discovery.', '/Summon/SummonSettings'), ['View Dashboards']);
			$sections['summon']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Summon integration.', '/Summon/SummonDashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('Hoopla', $enabledModules)) {
			$sections['hoopla'] = new AdminSection('Hoopla');
			$hooplaSettingsAction = new AdminAction('Settings', 'Define connection information between Hoopla and Aspen Discovery.', '/Hoopla/Settings');
			$hooplaScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/Hoopla/Scopes');
			if ($sections['hoopla']->addAction($hooplaSettingsAction, 'Administer Hoopla')) {
				$hooplaSettingsAction->addSubAction($hooplaScopesAction, 'Administer Hoopla');
			} else {
				$sections['hoopla']->addAction($hooplaScopesAction, 'Administer Hoopla');
			}
			$sections['hoopla']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Hoopla.', '/Hoopla/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['hoopla']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Hoopla integration.', '/Hoopla/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('OverDrive', $enabledModules)) {
			$sections['overdrive'] = new AdminSection('OverDrive');
			$overDriveSettingsAction = new AdminAction('Settings', 'Define connection information between OverDrive and Aspen Discovery.', '/OverDrive/Settings');
			$overDriveScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/OverDrive/Scopes');
			if ($sections['overdrive']->addAction($overDriveSettingsAction, 'Administer Libby/Sora')) {
				$overDriveSettingsAction->addSubAction($overDriveScopesAction, 'Administer Libby/Sora');
			} else {
				$sections['overdrive']->addAction($overDriveScopesAction, 'Administer Libby/Sora');
			}
			$sections['overdrive']->addAction(new AdminAction('Indexing Log', 'View the indexing log for OverDrive.', '/OverDrive/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['overdrive']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for OverDrive integration.', '/OverDrive/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
			$sections['overdrive']->addAction(new AdminAction('API Information', 'View API information for OverDrive integration to test connections.', '/OverDrive/APIData'), 'View OverDrive Test Interface');
			$sections['overdrive']->addAction(new AdminAction('Aspen Information', 'View information stored within Aspen about an OverDrive product.', '/OverDrive/AspenData'), 'View OverDrive Test Interface');
		}

		if (array_key_exists('Palace Project', $enabledModules)) {
			$sections['palace_project'] = new AdminSection('Palace Project');
			$palaceProjectSettingsAction = new AdminAction('Settings', 'Define connection information between Palace Project and Aspen Discovery.', '/PalaceProject/Settings');
			$palaceProjectScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/PalaceProject/Scopes');
			//$palaceProjectCollectionsAction = new AdminAction('Collections', 'Defines the collections within a Palace Project Account.', '/PalaceProject/Collections');
			if ($sections['palace_project']->addAction($palaceProjectSettingsAction, 'Administer Palace Project')) {
				$palaceProjectSettingsAction->addSubAction($palaceProjectScopesAction, 'Administer Palace Project');
				//$palaceProjectSettingsAction->addSubAction($palaceProjectCollectionsAction, 'Administer Palace Project');
			} else {
				$sections['palace_project']->addAction($palaceProjectScopesAction, 'Administer Palace Project');
				//$sections['palace_project']->addAction($palaceProjectCollectionsAction, 'Administer Palace Project');
			}
			$sections['palace_project']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Palace Project.', '/PalaceProject/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['palace_project']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Palace Project integration.', '/PalaceProject/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
			$sections['palace_project']->addAction(new AdminAction('CollectionReport', 'View collection report for Palace Project.', '/PalaceProject/CollectionReport'), [
				'Administer Palace Project',
				'View System Reports',
			]);
		}

		if (array_key_exists('RBdigital', $enabledModules)) {
			$sections['rbdigital'] = new AdminSection('RBdigital');
			$sections['rbdigital']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for RBdigital integration.', '/RBdigital/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('Side Loads', $enabledModules)) {
			$sections['side_loads'] = new AdminSection('Side Loads');
			$sideLoadsSettingsAction = new AdminAction('Settings', 'Define connection information between Side Loads and Aspen Discovery.', '/SideLoads/SideLoads');
			$sideLoadsScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/SideLoads/Scopes');
			if ($sections['side_loads']->addAction($sideLoadsSettingsAction, 'Administer Side Loads')) {
				$sideLoadsSettingsAction->addSubAction($sideLoadsScopesAction, 'Administer Side Loads');
			} else {
				$sections['side_loads']->addAction($sideLoadsScopesAction, 'Administer Side Loads');
			}
			$sections['side_loads']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Side Loads.', '/SideLoads/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['side_loads']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Side Loads integration.', '/SideLoads/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('Open Archives', $enabledModules)) {
			$sections['open_archives'] = new AdminSection('Open Archives');
			$sections['open_archives']->addAction(new AdminAction('Collections', 'Define collections to be loaded into Aspen Discovery.', '/OpenArchives/Collections'), 'Administer Open Archives');
			$sections['open_archives']->addAction(new AdminAction('Open Archives Facet Settings', 'Define facets for open archives searches.', '/OpenArchives/OpenArchivesFacets'), [
				'Administer All Open Archives Facet Settings',
				'Administer Library Open Archives Facet Settings',
			]);
			$sections['open_archives']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Open Archives.', '/OpenArchives/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['open_archives']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Open Archives integration.', '/OpenArchives/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('Events', $enabledModules)) {
			$sections['events'] = new AdminSection('Events');
			$aspenEventsAction = new AdminAction('Aspen Events - Manage Events', 'Add and manage Aspen Events.', '/Events/Events');
			if ($sections['events']->addAction($aspenEventsAction, [
				'Administer Events for All Locations',
				'Administer Events for Home Library Locations',
				'Administer Events for Home Location']
			)) {
				$aspenEventsAction->addSubAction(new AdminAction('Configure Event Fields', 'Define event fields for Aspen Events.', '/Events/EventFields'), 'Administer Field Sets');
				$aspenEventsAction->addSubAction(new AdminAction('Configure Event Field Sets', 'Define sets of event fields to use for Aspen Events.', '/Events/EventFieldSets'), 'Administer Field Sets');
				$aspenEventsAction->addSubAction(new AdminAction('Configure Event Types', 'Define event types to use for Aspen Events.', '/Events/EventTypes'), 'Administer Event Types');
				$aspenEventsAction->addSubAction(new AdminAction('Aspen Events Settings', 'Aspen Events Settings including indexing and library scope.', '/Events/IndexingSettings'), 'Administer Events for All Locations');
				$aspenEventsAction->addSubAction(new AdminAction('Event Reports', 'Aspen Events Reporting.', '/Events/EventGraphs'), [
					'View Event Reports for All Libraries',
					'View Event Reports for Home Library'
					]);
			}
			$sections['events']->addAction(new AdminAction('Assabet - Interactive Settings', 'Define collections to be loaded into Aspen Discovery.', '/Events/AssabetSettings'), 'Administer Assabet Settings');
			$sections['events']->addAction(new AdminAction('Communico - Attend Settings', 'Define collections to be loaded into Aspen Discovery.', '/Events/CommunicoSettings'), 'Administer Communico Settings');
			$sections['events']->addAction(new AdminAction('Library Market - Calendar Settings', 'Define collections to be loaded into Aspen Discovery.', '/Events/LMLibraryCalendarSettings'), 'Administer LibraryMarket LibraryCalendar Settings');
			$sections['events']->addAction(new AdminAction('Springshare - LibCal Settings', 'Define collections to be loaded into Aspen Discovery.', '/Events/SpringshareLibCalSettings'), 'Administer Springshare LibCal Settings');
			$sections['events']->addAction(new AdminAction('Calendar Display Settings', 'Define display settings for event calendar.', '/Events/CalendarDisplaySettings'), 'Print Calendars with Header Images');
			$sections['events']->addAction(new AdminAction('Event Facet Settings', 'Define facets for event searches.', '/Events/EventsFacets'), 'Administer Events Facet Settings');
			$sections['events']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Events.', '/Events/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
		}

		if (array_key_exists('Web Indexer', $enabledModules)) {
			$sections['web_indexer'] = new AdminSection('Website Indexing');
			$sections['web_indexer']->addAction(new AdminAction('Settings', 'Define settings for indexing websites within Aspen Discovery.', '/Websites/Settings'), 'Administer Website Indexing Settings');
			$sections['web_indexer']->addAction(new AdminAction('Website Facet Settings', 'Define facets for website searches.', '/Websites/WebsiteFacets'), [
				'Administer All Website Facet Settings',
				'Administer Library Website Facet Settings',
			]);
			$sections['web_indexer']->addAction(new AdminAction('Website Pages', 'A list of pages that have been indexed.', '/Websites/WebsitePages'), 'Administer Website Indexing Settings');
			$sections['web_indexer']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Websites.', '/Websites/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['web_indexer']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for indexed websites.', '/Websites/Dashboard'), [
				'View Dashboards',
				'View System Reports',
			]);
		}

		if (array_key_exists('User Lists', $enabledModules)) {
			$sections['user_lists'] = new AdminSection('User Lists');
			$sections['user_lists']->addAction(new AdminAction('Settings', 'Define settings for indexing user lists within Aspen Discovery.', '/UserLists/Settings'), 'Administer List Indexing Settings');
			$sections['user_lists']->addAction(new AdminAction('Indexing Log', 'View the indexing log for User Lists.', '/UserLists/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			$sections['user_lists']->addAction(new AdminAction('NYT Update Log', 'View the updates log for New York Times Lists.', '/UserLists/NYTUpdatesLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
			//$sections['user_lists']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for indexed User Lists.', '/UserLists/Dashboard'), ['View Dashboards', 'View System Reports']);
		}

		if (array_key_exists('Course Reserves', $enabledModules)) {
			$sections['course_reserves'] = new AdminSection('Course Reserves');
			$sections['course_reserves']->addAction(new AdminAction('Settings', 'Define settings for indexing course reserves within Aspen Discovery.', '/CourseReserves/Settings'), 'Administer Course Reserves');
			$sections['course_reserves']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Course Reserves.', '/CourseReserves/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
		}

		if (array_key_exists('Aspen LiDA', $enabledModules)) {
			$sections['aspen_lida'] = new AdminSection('Aspen LiDA');
			$sections['aspen_lida']->addAction(new AdminAction('General Settings', 'Define general settings for Aspen LiDA.', '/AspenLiDA/GeneralSettings'), 'Administer Aspen LiDA Settings');
			$sections['aspen_lida']->addAction(new AdminAction('Location Settings', 'Define location settings for Aspen LiDA.', '/AspenLiDA/LocationSettings'), 'Administer Aspen LiDA Settings');
			$notificationSettingsAction = new AdminAction('Notification Settings', 'Define settings for notifications in Aspen LiDA.', '/AspenLiDA/NotificationSettings');
			$notificationReportAction = new AdminAction('Notifications Report', 'View all notifications initiated and completed within the system', '/AspenLiDA/NotificationsReport');
			if ($sections['aspen_lida']->addAction($notificationSettingsAction, 'Administer Aspen LiDA Settings')) {
				$notificationSettingsAction->addSubAction($notificationReportAction, 'View Notifications Reports');
			} else {
				$sections['aspen_lida']->addAction($notificationReportAction, 'View Notifications Reports');
			}
			if (false /* $allowILSMessaging*/) {
				/** @noinspection PhpUnreachableStatementInspection */
				$sections['aspen_lida']->addAction(new AdminAction('ILS Notification Settings', 'Define settings for ILS notifications in Aspen LiDA.', '/AspenLiDA/ILSNotificationSettings'), 'Administer Aspen LiDA Settings');
			}
			$sections['aspen_lida']->addAction(new AdminAction('LiDA Notifications', 'LiDA Notifications allow you to send custom alerts to your patrons via the app.', '/Admin/LiDANotifications'), [
				'Send Notifications to All Libraries',
				'Send Notifications to All Locations',
				'Send Notifications to Home Library',
				'Send Notifications to Home Location',
				'Send Notifications to Home Library Locations',
			]);
			if (SystemVariables::getSystemVariables()->enableBrandedApp) {
				$sections['aspen_lida']->addAction(new AdminAction('Branded App Settings', 'Define settings for branded versions of Aspen LiDA.', '/AspenLiDA/BrandedAppSettings'), 'Administer Aspen LiDA Settings');
			}
			$sections['aspen_lida']->addAction(new AdminAction('Self-Check Settings', 'Define settings for self-check in Aspen LiDA.', '/AspenLiDA/SelfCheckSettings'), 'Administer Aspen LiDA Self-Check Settings');
		}
		if (array_key_exists('Series', $enabledModules)) {
			$sections['series'] = new AdminSection("Series Search");
			$sections['series']->addAction(new AdminAction('Administer Series', 'Edit series data for series search', '/Series/AdministerSeries'), 'Administer Series');
			$sections['series']->addAction(new AdminAction('Indexing Settings', 'Administer Series Indexing Settings', '/Series/Settings'), 'Administer Series');
			$sections['series']->addAction(new AdminAction('Indexing Log', 'View Series Indexing Log', '/Series/IndexingLog'), [
				'View System Reports',
				'View Indexing Logs',
			]);
		}


		$sections['support'] = new AdminSection('Aspen Discovery Support');
		$sections['support']->addAction(new AdminAction('Request Tracker Settings', 'Define settings for a Request Tracker support system.', '/Support/RequestTrackerConnections'), 'Administer Request Tracker Connection');
		try {
			require_once ROOT_DIR . '/sys/Support/RequestTrackerConnection.php';
			$supportConnections = new RequestTrackerConnection();
			$hasSupportConnection = false;
			if ($supportConnections->find(true)) {
				$hasSupportConnection = true;
			}
			if ($hasSupportConnection) {
				$sections['support']->addAction(new AdminAction('View Active Tickets', 'View Active Tickets.', '/Support/ViewTickets'), 'View Active Tickets');
			}
			$showSubmitTicket = false;
			try {
				if (!empty(SystemVariables::getSystemVariables()->ticketEmail)) {
					$showSubmitTicket = true;
				}
			} catch (Exception $e) {
				//This happens before the table is setup
			}
			if ($showSubmitTicket) {
				$sections['support']->addAction(new AdminAction('Submit Ticket', 'Submit a support ticket for assistance with Aspen Discovery.', '/Admin/SubmitTicket'), 'Submit Ticket');
			}
			if ($hasSupportConnection) {
				$sections['support']->addAction(new AdminAction('Set Priorities', 'Set Development Priorities.', '/Support/SetDevelopmentPriorities'), 'Set Development Priorities');
			}
		} catch (Exception $e) {
			//This happens before tables are created, ignore
		}
		$sections['support']->addAction(new AdminAction('Help Center', 'View the Help Center for Aspen Discovery.', 'https://help.aspendiscovery.org'), true);
		$sections['support']->addAction(new AdminAction('Release Notes', 'View release notes for Aspen Discovery which contain information about new functionality and fixes for each release.', '/Admin/ReleaseNotes'), true);

		$sorter = function (AdminSection $a, AdminSection $b) {
			return strcasecmp($a->getTranslatedLabel(), $b->getTranslatedLabel());
		};
		uasort($sections, $sorter);

		return $sections;
	}

	public function getPermissions($isGuidingUser = false) {
		if ($this->_permissions == null) {
			$this->_permissions = [];
			$roles = $this->getRoles();
			foreach ($roles as $role) {
				$this->_permissions = array_merge($this->_permissions, $role->getPermissions());
			}

			if (!$isGuidingUser) {
				$masqueradeMode = UserAccount::isUserMasquerading();
				if ($masqueradeMode && !$isGuidingUser) {
					$guidingUser = UserAccount::getGuidingUserObject();
					$guidingUserPermissions = $guidingUser->getPermissions(true);
					$this->_permissions = $this->filterPermissionsForMasquerade($this->_permissions, $guidingUserPermissions);
				}
			}
		}
		return $this->_permissions;
	}

	/**
	 * Filter permissions to make sure that we don't gain escalated permissions by masquerading.
	 * But, we also don't want to have permissions the user doesn't while masquerading.
	 *
	 * @param string[] $userPermissions
	 * @param string[] $guidingUserPermissions
	 * @return array
	 */
	public function filterPermissionsForMasquerade($userPermissions, $guidingUserPermissions) {
		$validPermissions = [];
		foreach ($userPermissions as $permissionName) {
			if (in_array($permissionName, $guidingUserPermissions)) {
				$validPermissions[] = $permissionName;
			} else {
				//Check to see if the guiding user has a permission that is more inclusive than the user we are masquerading as
				if (strpos($permissionName, 'Administer Library') === 0) {
					$tmpPermissionName = str_replace('Administer Library', 'Administer All', $permissionName);
					if (in_array($tmpPermissionName, $guidingUserPermissions)) {
						$validPermissions[] = $permissionName;
					}
				} elseif ($permissionName == 'Administer Home Location' && (in_array('Administer Home Library Locations', $guidingUserPermissions) || in_array('Administer All Locations', $guidingUserPermissions))) {
					$validPermissions[] = $permissionName;
				} elseif ($permissionName == 'Administer Home Library Locations' && (in_array('Administer All Locations', $guidingUserPermissions))) {
					$validPermissions[] = $permissionName;
				} elseif ($permissionName == 'Administer Home Library' && (in_array('Administer All Libraries', $guidingUserPermissions))) {
					$validPermissions[] = $permissionName;
				}
			}
		}
		return $validPermissions;
	}

	/**
	 * @param string[]|string $allowablePermissions
	 * @return bool
	 */
	public function hasPermission($allowablePermissions) {
		$permissions = $this->getPermissions();
		if (is_array($allowablePermissions)) {
			foreach ($allowablePermissions as $allowablePermission) {
				if (in_array($allowablePermission, $permissions)) {
					return true;
				}
			}
		} else {
			if (in_array($allowablePermissions, $permissions)) {
				return true;
			}
		}
		return false;
	}

	public function getAccountSummary() {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getAccountSummary($this);
		} else {
			return [];
		}
	}

	public function getCachedAccountSummary(string $source) {
		//Check to see if we have cached summary information
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $this->id;
		$summary->source = $source;
		$existingId = null;
		if ($summary->find(true)) {
			$existingId = $summary->id;
			if (($summary->lastLoaded < (time() - 5 * 60)) || isset($_REQUEST['refreshCheckouts']) || isset($_REQUEST['refreshHolds']) || isset($_REQUEST['refreshSummary'])) {
				$summary = null;
			}
		} else {
			$summary->insert();
			$existingId = $summary->id;
			$summary = null;
		}
		return [
			$existingId,
			$summary,
		];
	}

	public function clearCachedAccountSummaryForSource(string $source) {
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $this->id;
		$summary->source = $source;
		$summary->delete(true);
	}

	public function forceReloadOfCheckouts() {
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkout = new Checkout();
		$checkout->userId = $this->id;
		$checkout->delete(true);

		$this->__set('checkoutInfoLastLoaded', 0);
		$this->update();
	}

	public function forceReloadOfHolds() {
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$hold = new Hold();
		$hold->userId = $this->id;
		$hold->delete(true);

		$this->__set('holdInfoLastLoaded', 0);
		$this->update();
	}

	public function clearActiveSessions() {
		//Delete any sessions for the patron to ensure they are logged out
		$session = new Session();
		$session->whereAdd("data like '%activeUserId|s:" . strlen($this->id) . ":\"$this->id\"%'");
		$session->whereAdd('session_id != "' . session_id() . '"');
		/** @noinspection PhpUnusedLocalVariableInspection */
		$numDeletions = $session->delete(true);
	}

	public function showHoldPosition(): bool {
		if ($this->hasIlsConnection()) {
			global $library;
			return ($library->showHoldPosition == 1) && $this->getCatalogDriver()->showHoldPosition();
		} else {
			return false;
		}
	}

	public function suspendRequiresReactivationDate(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->suspendRequiresReactivationDate();
		} else {
			return false;
		}
	}

	public function showDateWhenSuspending(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showDateWhenSuspending();
		} else {
			return false;
		}
	}

	public function reactivateDateNotRequired(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->reactivateDateNotRequired();
		} else {
			return false;
		}
	}

	public function showHoldPlacedDate(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showHoldPlacedDate();
		} else {
			return false;
		}
	}

	public function showHoldExpirationTime(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showHoldExpirationTime();
		} else {
			return false;
		}
	}

	public function showOutDateInCheckouts(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showOutDateInCheckouts();
		} else {
			return false;
		}
	}

	public function showTimesRenewed(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showTimesRenewed();
		} else {
			return false;
		}
	}

	public function showRenewalsRemaining(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showRenewalsRemaining();
		} else {
			return false;
		}
	}

	public function showWaitListInCheckouts(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showWaitListInCheckouts();
		} else {
			return false;
		}
	}

	public function showPreferredNameInProfile(): bool {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->showPreferredNameInProfile();
		} else {
			return false;
		}
	}

	public function getUsernameValidationRules(): array {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getUsernameValidationRules();
		} else {
			return [];
		}
	}

	public function getPasswordPinValidationRules() : array {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getPasswordPinValidationRules();
		} else {
			return [
				'minLength' => 12,
				'maxLength' => 50,
				'onlyDigitsAllowed' => false,
				'requireStrongPassword' => true
			];
		}
	}

	public function placeHoldForRequest(MaterialsRequest $materialsRequest): array {
		$selectedRequestCandidate = $materialsRequest->getSelectedHoldCandidate();
		$source = $selectedRequestCandidate->source;
		if ($source == 'ils' || $source == null) {
			//The Materials request stores the id of the pickup location
			$pickupBranchId = $materialsRequest->holdPickupLocation;
			if (empty($pickupBranchId)) {
				$pickupBranchId = $this->homeLocationId;
			}
			$location = new Location();
			$location->locationId = $pickupBranchId;
			if ($location->find(true)) {
				$pickupBranch = $location->code;
				$locationValid = $this->validatePickupBranch($pickupBranch);
			} else {
				$locationValid = false;
			}

			if (!$locationValid) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'This location is no longer available, please select a different pickup location',
						'isPublicFacing' => true,
					]),
				];
			}

			$homeLibrary = $this->getHomeLibrary();

			if ($homeLibrary->defaultNotNeededAfterDays <= 0) {
				$cancelDate = null;
			} else {
				//Default to a date based on the default not needed after days in the library configuration.
				$nnaDate = time() + $homeLibrary->defaultNotNeededAfterDays * 24 * 60 * 60;
				$cancelDate = date('Y-m-d', $nnaDate);
			}

			//We will always try to place bib level holds from materials requests

			//Make sure that there are not volumes available
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver($selectedRequestCandidate->sourceId);
			if ($recordDriver->isValid()) {
				require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
				$volumeDataDB = new IlsVolumeInfo();
				$volumeDataDB->recordId = $recordDriver->getIdWithSource();
				if ($volumeDataDB->find(true)) {
					return [
						'success' => false,
						'message' => translate(['text' => 'You must place a volume hold on this title.']),
					];
				}
			}
			$result = $this->placeHold($selectedRequestCandidate->sourceId, $pickupBranch, $cancelDate);
			$responseMessage = strip_tags($result['api']['message']);
			$responseMessage = trim($responseMessage);
			return [
				'success' => $result['success'],
				'message' => $responseMessage,
			];
		} elseif ($source == 'overdrive') {
			require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
			$driver = new OverDriveDriver();
			$result = $driver->placeHold($this, $selectedRequestCandidate->sourceId);
			return [
				'success' => $result['success'],
				'message' => $result['api']['message'],
			];
		} elseif ($source == 'cloud_library') {
			require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
			$driver = new CloudLibraryDriver();
			$result = $driver->placeHold($this, $selectedRequestCandidate->sourceId);
			return [
				'success' => $result['success'],
				'message' => $result['api']['message'],
			];
		} elseif ($source == 'axis360') {
			require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
			$driver = new Axis360Driver();
			$result = $driver->placeHold($this, $selectedRequestCandidate->sourceId);
			return [
				'success' => $result['success'],
				'message' => $result['api']['message'],
			];
		} elseif ($source == 'palace_project') {
			require_once ROOT_DIR . '/Drivers/PalaceProjectDriver.php';
			$driver = new PalaceProjectDriver();
			$result = $driver->placeHold($this, $selectedRequestCandidate->sourceId);
			$action = $result['api']['action'] ?? null;
			return [
				'success' => $result['success'],
				'message' => $result['api']['message'],
			];
		} else {
			return [
				'success' => false,
				'message' => 'Invalid source',
			];
		}
	}

	/**
	 * @param Checkout[] $checkouts
	 * @return float
	 */
	public function calculateCostSavingsForCurrentCheckouts(array $checkouts): float {
		$costSavings = 0;
		foreach ($checkouts as $checkout) {
			$costSavings += $checkout->getReplacementCost();
		}
		$this->__set('currentCostSavings', $costSavings);
		$this->update();
		return $costSavings;
	}

	public function getCurrentCostSavingsMessage(bool $wrapWithAlert): string {
		//Make sure to get checkouts for the user since this triggers the calculation
		$checkouts = $this->getCheckouts();

		$totalSavings = 0;
		$linkedUsers = $this->getLinkedUsers();
		if (count($linkedUsers)) {
			$costSavingsMessage = "You are saving %1% with what is currently checked out from the library to you and your linked accounts. Learn more in <a href='/MyAccount/LibrarySavings'>Library Savings</a>.";
		} else {
			$costSavingsMessage = "You are saving %1% with what is currently checked out from the library. Learn more in <a href='/MyAccount/LibrarySavings'>Library Savings</a>.";
		}
		$costSavings = $this->currentCostSavings;
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$formattedCostSavings = StringUtils::formatCurrency($costSavings);

		$translatedMessage = translate([
			'text' => $costSavingsMessage,
			'1' => $formattedCostSavings,
			'isPublicFacing' => true
		]);
		if ($wrapWithAlert) {
			$translatedMessage = '<div class="alert alert-info">' . $translatedMessage . '</div>';
		}
		return $translatedMessage;
	}

	public function getTotalCostSavingsMessage(bool $wrapWithAlert): string {
		$totalSavings = 0;
		$costSavingsMessage = "You have saved %1% by checking out the materials in your reading history from the library. Learn more in <a href='/MyAccount/LibrarySavings'>Library Savings</a>.";
		$costSavings = $this->totalCostSavings;
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$formattedCostSavings = StringUtils::formatCurrency($costSavings);
		$translatedMessage = translate([
			'text' => $costSavingsMessage,
			'1' => $formattedCostSavings,
			'isPublicFacing' => true
		]);
		if ($wrapWithAlert) {
			$translatedMessage = '<div class="alert alert-info">' . $translatedMessage . '</div>';
		}
		return $translatedMessage;
	}

	public function getCostSavingsByMonth($month, $year): float {
		$startTimeStamp = strtotime($year . '-' . $month);
		if ($month < 12) {
			$endTimeStamp = strtotime($year . '-' . ($month + 1));
		} else {
			$endTimeStamp = strtotime(($year + 1) . '-01');
		}

		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->userId = $this->id;
		$readingHistoryEntry->whereAdd("checkOutDate >= $startTimeStamp AND checkOutDate < $endTimeStamp", 'AND');
		$readingHistoryEntry->selectAdd('SUM(costSavings) as costSavings');
		if ($readingHistoryEntry->find(true)) {
			return (float)$readingHistoryEntry->costSavings;
		} else {
			return 0;
		}
	}

	public function getCostSavingsByYear($year): float {
		if ($this->enableCostSavings) {
			$startTimeStamp = strtotime($year . '-01-01');
			$endTimeStamp = strtotime(($year + 1) . '-01-01');

			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$readingHistoryEntry = new ReadingHistoryEntry();
			$readingHistoryEntry->userId = $this->id;
			$readingHistoryEntry->whereAdd("checkOutDate >= $startTimeStamp AND checkOutDate < $endTimeStamp", 'AND');
			$readingHistoryEntry->selectAdd('SUM(costSavings) as costSavings');
			if ($readingHistoryEntry->find(true)) {
				return (float)$readingHistoryEntry->costSavings;
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}

	public function hasRemainingLocalIllRequests(): bool {
		$homeLibrary = $this->getHomeLibrary();
		if ($homeLibrary != null) {
			if ($homeLibrary->maximumLocalIllRequests > 0) {
				//Figure out the number of Local ILL requests, we just care about ILS holds and checkouts, so we can get from the driver.
				$numLocalIllRequests = 0;
				$holds = $this->getCatalogDriver()->getHolds($this);
				foreach ($holds as $holdSection) {
					/** @var Hold $hold */
					foreach ($holdSection as $hold) {
						if (!empty($hold->outOfHoldGroupMessage)) {
							$numLocalIllRequests++;
						}
					}
				}
				$checkouts = $this->getCatalogDriver()->getCheckouts($this);
				foreach ($checkouts as $checkout) {
					if (!empty($checkout->outOfHoldGroupMessage)) {
						$numLocalIllRequests++;
					}
				}
				if ($numLocalIllRequests >= $homeLibrary->maximumLocalIllRequests) {
					return false;
				}
			}
		}
		return true;
	}

	protected function clearRuntimeDataVariables() {
		if ($this->_accountProfile != null) {
			$this->_accountProfile->__destruct();
			$this->_accountProfile = null;
		}
		parent::clearRuntimeDataVariables();
	}

	public function getFormattedHoldInfoLastLoaded() {
		if ($this->holdInfoLastLoaded == 0) {
			return translate([
				'text' => "Loading...",
				'isPublicFacing' => true,
			]);
		} else {
			return strftime("%I:%M %p", $this->holdInfoLastLoaded);
		}
	}

	public function getFormattedCheckoutInfoLastLoaded() {
		if ($this->checkoutInfoLastLoaded == 0) {
			return translate([
				'text' => "Loading...",
				'isPublicFacing' => true,
			]);
		} else {
			return strftime("%I:%M %p", $this->checkoutInfoLastLoaded);
		}
	}

	public function getDisplayName() {
		if (empty($this->displayName)) {
			if (empty($this->firstname)) {
				$this->__set('displayName', $this->lastname);
			} else {
				// #PK-979 Make display name configurable firstname, last initial, vs first initial last name
				$homeLibrary = $this->getHomeLibrary();
				if ($homeLibrary == null || ($homeLibrary->patronNameDisplayStyle == 'firstinitial_lastname')) {
					$this->__set('displayName', substr($this->firstname, 0, 1) . '. ' . $this->lastname);
				} elseif ($homeLibrary->patronNameDisplayStyle == 'lastinitial_firstname') {
					$this->__set('displayName', $this->firstname . ' ' . substr($this->lastname, 0, 1) . '.');
				} elseif ($homeLibrary->patronNameDisplayStyle == 'firstinitial_middleinitial_lastname') {
					$firstNames = explode(' ', $this->firstname);
					$displayName = '';
					for ($i = 0; $i < count($firstNames); $i++) {
						$displayName .= ' ' . substr($firstNames[$i], 0, 1) . '.';
					}
					$displayName .= ' ' . $this->lastname;

					$this->__set('displayName', trim($displayName));
				} elseif ($homeLibrary->patronNameDisplayStyle == 'firstname_middleinitial_lastinitial') {
					$firstNames = explode(' ', $this->firstname);
					$displayName = $firstNames[0];
					for ($i = 1; $i < count($firstNames); $i++) {
						$displayName .= ' ' . substr($firstNames[$i], 0, 1) . '.';
					}
					$displayName .= ' ' . substr($this->lastname, 0, 1) . '.';
					$this->__set('displayName', trim($displayName));
				}
			}
			$this->update();
		}
		return $this->displayName;
	}

	public function getPluginStatus(string $pluginName) {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getPluginStatus($pluginName);
		} else {
			return ['enabled' => false];
		}
	}

	function newCurbsidePickup($pickupLocation, $pickupTime, $pickupNote) {
		$result = $this->getCatalogDriver()->newCurbsidePickup($this, $pickupLocation, $pickupTime, $pickupNote);
		$this->clearCache();
		return $result;
	}

	private false|null|TwoFactorAuthSetting $_twoFactorAuthenticationSetting = false;
	public function getTwoFactorAuthenticationSetting() : ?TwoFactorAuthSetting {
		if ($this->_twoFactorAuthenticationSetting === false) {
			if ($this->isAspenAdminUser()) {
				$this->_twoFactorAuthenticationSetting = null;
			}else{
				require_once ROOT_DIR . '/sys/TwoFactorAuthSetting.php';
				$this->_twoFactorAuthenticationSetting = new TwoFactorAuthSetting();
				//If the user has a patron type, we will use that to determine the two factor authentication settings.
				//Otherwise, we can use the account profile.
				$patronType = $this->getPTypeObj();
				if (!empty($patronType)) {
					$this->_twoFactorAuthenticationSetting->id = $patronType->twoFactorAuthSettingId;
				}else{
					$this->_twoFactorAuthenticationSetting->accountProfileId = $this->getAccountProfile()->id;
				}
				if (!$this->_twoFactorAuthenticationSetting->find(true)) {
					$this->_twoFactorAuthenticationSetting = null;
				}
			}
		}
		return $this->_twoFactorAuthenticationSetting;
	}

	public function get2FAStatusForPType() : bool {
		$twoFactorAuthSetting = $this->getTwoFactorAuthenticationSetting();
		if ($twoFactorAuthSetting == null) {
			return false;
		}else{
			return $twoFactorAuthSetting->isEnabled != 'notAvailable';
		}
	}

	public function is2FARequired() : bool {
		$twoFactorAuthSetting = $this->getTwoFactorAuthenticationSetting();
		if ($twoFactorAuthSetting == null) {
			return false;
		}else{
			return $twoFactorAuthSetting->isEnabled == 'mandatory';
		}
	}

	public function get2FAStatus() : bool {
		$status = $this->twoFactorStatus;
		if ($status == '1') {
			//Make sure that 2-factor authentication has not been disabled by ptype even though the user previously opted in
			$twoFactorAuthByPType = $this->get2FAStatusForPType();
			if ($twoFactorAuthByPType) {
				return true;
			}
		}
		return false;
	}

	public function canReceiveNotifications($alertType): bool {
		$userHomeLocation = $this->homeLocationId;
		$userLocation = new Location();
		$userLocation->locationId = $this->homeLocationId;
		if ($userLocation->find(true)) {
			$userLibrary = $userLocation->getParentLibrary();
			if ($userLibrary) {
				require_once ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php';
				$settings = new NotificationSetting();
				$settings->id = $userLibrary->lidaNotificationSettingId;
				if ($settings->find(true)) {
					if ($settings->$alertType == 1 || $settings->$alertType == '1') {
						if ($settings->sendTo == 2 || $settings->sendTo == '2') {
							return true;
						} elseif ($settings->sendTo == 1 || $settings->sendTo == '1') {
							$isStaff = 0;
							$patronType = $this->getPTypeObj();
							if (!empty($patronType)) {
								$isStaff = $patronType->isStaff;
							}
							if ($isStaff == 1 || $isStaff == '1') {
								return true;
							}
						}
					}
				}
			}
		}
		return false;
	}

	public function canReceiveILSNotification($code): bool {
		if ($this->isNotificationHistoryEnabled()) { // check if ils notifications are enabled for the ils
			$userHomeLocation = $this->homeLocationId;
			$userLocation = new Location();
			$userLocation->locationId = $this->homeLocationId;
			if ($userLocation->find(true)) {
				$userLibrary = $userLocation->getParentLibrary();
				if ($userLibrary) {
					require_once ROOT_DIR . '/sys/AspenLiDA/NotificationSetting.php';
					$settings = new NotificationSetting();
					$settings->id = $userLibrary->lidaNotificationSettingId;
					if ($settings->find(true)) {
						require_once ROOT_DIR . '/sys/AspenLiDA/ILSMessageType.php';
						$ilsMessageTypes = new ILSMessageType();
						$ilsMessageTypes->ilsNotificationSettingId = $settings->ilsNotificationSettingId;
						$ilsMessageTypes->code = $code;
						$ilsMessageTypes->isEnabled = 1;
						if ($ilsMessageTypes->find(true)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	public function saveNotificationPushToken($token, $device): bool {
		require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
		$pushToken = new UserNotificationToken();
		$pushToken->userId = $this->id;
		$pushToken->pushToken = $token;
		$pushToken->deviceModel = $device;
		$pushToken->onboardAppNotifications = 0;
		if ($pushToken->find(true)) {
			return true;
		} else {
			if ($pushToken->insert()) {
				return true;
			}
		}
		return false;
	}

	public function getNotificationPushToken(): array {
		require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
		$tokens = [];
		$obj = new UserNotificationToken();
		$obj->userId = $this->id;
		$obj->find();
		while ($obj->fetch()) {
			$token = $obj->pushToken;
			$tokens[] = $token;
		}
		return $tokens;
	}

	public function getNotificationPreferencesByToken($token) {
		$preferences = [];
		require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
		$obj = new UserNotificationToken();
		$obj->userId = $this->id;
		$obj->pushToken = $token;
		if ($obj->find(true)) {
			$preferences = $obj;
		}
		return $preferences;
	}

	public function getNotificationPreferencesByUser() {
		$preferences = [];
		require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
		$obj = new UserNotificationToken();
		$obj->userId = $this->id;
		$obj->find();
		while ($obj->fetch()) {
			$preference['device'] = $obj->deviceModel;
			$preference['token'] = $obj->pushToken;
			$preference['notifySavedSearch'] = $obj->notifySavedSearch;
			$preference['notifyCustom'] = $obj->notifyCustom;
			$preference['notifyAccount'] = $obj->notifyAccount;
			$preference['onboardStatus'] = $obj->onboardAppNotifications ?? 0;

			$preferences[] = $preference;
		}

		if (empty($preferences)) {
			$preference['device'] = 'Unknown';
			$preference['token'] = 'Unknown';
			$preference['notifySavedSearch'] = 0;
			$preference['notifyCustom'] = 0;
			$preference['notifyAccount'] = 0;
			$preference['onboardStatus'] = 1;

			$preferences[] = $preference;
		}

		return $preferences;
	}

	public function getNotificationPreference($option, $token): bool {
		require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
		$obj = new UserNotificationToken();
		$obj->userId = $this->id;
		$obj->pushToken = $token;
		if ($obj->find(true)) {
			if ($obj->$option == 1 || $obj->$option == "1") {
				return true;
			}
		}
		return false;
	}

	public function setNotificationPreference($option, $newValue, $token): bool {
		require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
		$obj = new UserNotificationToken();
		$obj->userId = $this->id;
		$obj->pushToken = $token;
		if ($obj->find(true)) {
			$obj->$option = $newValue;
			if ($obj->update()) {
				return true;
			}
		}
		return false;
	}

	public function deleteNotificationPushToken($token): bool {
		require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
		$pushToken = new UserNotificationToken();
		$pushToken->userId = $this->id;
		$pushToken->pushToken = $token;
		if ($pushToken->find(true)) {
			if ($pushToken->delete()) {
				return true;
			}
		}
		return false;
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if ($this->homeLocationId == 0 || in_array($this->homeLocationId, $selectedFilters['locations'])) {
			$okToExport = true;
		}
		return $okToExport;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset($return['homeLocationId']);
		unset($return['myLocation1Id']);
		unset($return['myLocation2Id']);
		unset($return['pickupLocationId']);
		unset($return['lastListUsed']);
		unset($return['twoFactorAuthSettingId']);
		return $return;
	}

	public function loadObjectPropertiesFromJSON($jsonData, $mappings) {
		$encryptedFields = $this->getEncryptedFieldNames();
		$sourceEncryptionKey = isset($mappings['passkey']) ? $mappings['passkey'] : '';
		foreach ($jsonData as $property => $value) {
			if ($property != $this->getPrimaryKey() && $property != 'links') {
				if (in_array($property, $encryptedFields)) {
					if (!empty($sourceEncryptionKey)) {
						$value = EncryptionUtils::decryptFieldWithProvidedKey($value, $sourceEncryptionKey);
					} else {
						//Source key was not provided, decrypt using our encryption key (assuming the source and destination match.
						$value = EncryptionUtils::decryptField($value);
					}
				}
				if ($property == 'username') {
					if (array_key_exists($value, $mappings['users'])) {
						$value = $mappings['users'][$value];
					}
				}
				$this->$property = $value;
			}
		}
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		$allLocations = Location::getLocationListAsObjects(false);
		$links['homeLocationId'] = '';
		if (array_key_exists($this->homeLocationId, $allLocations)) {
			$links['homeLocationId'] = $allLocations[$this->homeLocationId]->code;
		}
		$links['myLocation1Id'] = '';
		if (array_key_exists($this->myLocation1Id, $allLocations)) {
			$links['myLocation1Id'] = $allLocations[$this->myLocation1Id]->code;
		}
		$links['myLocation2Id'] = '';
		if (array_key_exists($this->myLocation2Id, $allLocations)) {
			$links['myLocation2Id'] = $allLocations[$this->myLocation2Id]->code;
		}
		$links['pickupLocationId'] = '';
		if (array_key_exists($this->pickupLocationId, $allLocations)) {
			$links['pickupLocationId'] = $allLocations[$this->pickupLocationId]->code;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		$allLocations = Location::getLocationListAsObjects(false);
		if (empty($jsonData['homeLocationId'])) {
			$this->homeLocationId = 0;
		} else {
			$code = $jsonData['homeLocationId'];
			if (array_key_exists($code, $mappings['locations'])) {
				$code = $mappings['locations'][$code];
			}
			foreach ($allLocations as $tmpLocation) {
				if ($tmpLocation->code == $code) {
					$this->homeLocationId = $tmpLocation->locationId;
					break;
				}
			}
		}
		if (empty($jsonData['myLocation1Id'])) {
			$this->myLocation1Id = 0;
		} else {
			$code = $jsonData['myLocation1Id'];
			if (array_key_exists($code, $mappings['locations'])) {
				$code = $mappings['locations'][$code];
			}
			foreach ($allLocations as $tmpLocation) {
				if ($tmpLocation->code == $code) {
					$this->myLocation1Id = $tmpLocation->locationId;
					break;
				}
			}
		}
		if (empty($jsonData['myLocation2Id'])) {
			$this->myLocation2Id = 0;
		} else {
			$code = $jsonData['myLocation2Id'];
			if (array_key_exists($code, $mappings['locations'])) {
				$code = $mappings['locations'][$code];
			}
			foreach ($allLocations as $tmpLocation) {
				if ($tmpLocation->code == $code) {
					$this->myLocation2Id = $tmpLocation->locationId;
					break;
				}
			}
		}
		if (empty($jsonData['pickupLocationId'])) {
			$this->pickupLocationId = 0;
		} else {
			$code = $jsonData['pickupLocationId'];
			if (array_key_exists($code, $mappings['locations'])) {
				$code = $mappings['locations'][$code];
			}
			foreach ($allLocations as $tmpLocation) {
				if ($tmpLocation->code == $code) {
					$this->pickupLocationId = $tmpLocation->locationId;
					break;
				}
			}
		}
	}

	function validateUniqueId() {
		if ($this->getCatalogDriver() != null) {
			$this->getCatalogDriver()->validateUniqueId($this);
		}
	}

	/**
	 * Returns true if reset username is a separate page independent of the patron information page
	 *
	 * @return bool
	 */
	public function showResetUsernameLink(): bool {
		if ($this->getCatalogDriver() != null) {
			return $this->getCatalogDriver()->showResetUsernameLink();
		} else {
			return false;
		}
	}

	public function showDateInFines(): bool {
		if ($this->getCatalogDriver() != null) {
			return $this->getCatalogDriver()->showDateInFines();
		} else {
			return false;
		}
	}

	public function getILSName(): string {
		if (empty($this->getAccountProfile())) {
			return 'Unknown';
		} else {
			return $this->getAccountProfile()->ils;
		}
	}

	public function canSuggestMaterials(): int {
		$patronType = $this->getPTypeObj();
		if (!empty($patronType)) {
			if ($patronType->canSuggestMaterials) {
				return $patronType->canSuggestMaterials;
			}
		}
		return 0;
	}

	function checkoutItem($barcode, Location $currentLocation): array {
		if ($this->getCatalogDriver()->hasAPICheckout()) {
			$result = $this->getCatalogDriver()->checkoutByAPI($this, $barcode, $currentLocation);
		} else {
			$result = $this->getCatalogDriver()->checkoutBySip($this, $barcode, $currentLocation->code);
		}

		if ($result['success']) {
			$this->forceReloadOfCheckouts();
		}
		$this->clearCache();
		return $result;
	}

	public function hasAPICheckIn(): bool {
		return $this->driver->hasAPICheckIin();
	}

	function checkInItem($barcode, Location $currentLocation): array {
		if ($this->getCatalogDriver()->hasAPICheckin()) {
			$result = $this->getCatalogDriver()->checkInByAPI($this, $barcode, $currentLocation);
		} else {
			$result = $this->getCatalogDriver()->checkInBySip($this, $barcode, $currentLocation->code);
		}

		if ($result['success']) {
			$this->forceReloadOfCheckouts();
		}
		$this->clearCache();
		return $result;
	}

	public function find($fetchFirst = false, $requireOneMatchToReturn = true): bool {
		return parent::find($fetchFirst, $requireOneMatchToReturn);
	}

	public function isAspenAdminUser(): bool {
		return $this->source == 'admin' && $this->username == 'aspen_admin';
	}

	public function isUserAdmin(): bool {
		require_once ROOT_DIR . '/sys/Administration/UserRoles.php';
		$userRole = new UserRoles();
		$userRole->find();

		$adminList = [];
		while ($userRole->fetch()) {
			$adminList[] = $userRole->userId;
		}
		return in_array($this->id, $adminList);
	}

	public function showRenewalLink(AccountSummary $ilsAccountSummary): bool {
		$showRenewalLink = false;
		if ($ilsAccountSummary->isExpirationClose()) {
			$pType = $this->getPTypeObj();
			if ($pType->canRenewOnline) {
				$userLibrary = $this->getHomeLibrary();
				if ($userLibrary->enableCardRenewal == 2) {
					if (!empty($userLibrary->cardRenewalUrl)) {
						$showRenewalLink = true;
					}
				} elseif ($userLibrary->enableCardRenewal == 3) {
					require_once ROOT_DIR . '/sys/Enrichment/QuipuECardSetting.php';
					$quipuECardSettings = new QuipuECardSetting();
					if ($quipuECardSettings->find(true) && $quipuECardSettings->hasERenew) {
						$showRenewalLink = true;
					}
				}
				if (!$ilsAccountSummary->isExpired() && !$userLibrary->showCardRenewalWhenExpirationIsClose) {
					$showRenewalLink = false;
				}
			}
		}
		return $showRenewalLink;
	}

	public function isNotificationHistoryEnabled() {
		$catalogDriver = $this->getCatalogDriver();
		if ($catalogDriver) {
			return $catalogDriver->hasIlsInbox();
		}
		return false;
	}

	public function getNumMaterialsRequestsMaxActive() {
		$homeLibrary = $this->getHomeLibrary();
		if (is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		return isset($homeLibrary) ? $homeLibrary->maxActiveRequests : 5;
	}

	public function getNumMaterialsRequestsMaxPerYear() {
		$homeLibrary = $this->getHomeLibrary();
		if (is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		return isset($homeLibrary) ? $homeLibrary->maxRequestsPerYear : 60;
	}

	public function getNumMaterialsRequestsRequestsForYear() {
		$homeLibrary = $this->getHomeLibrary();
		if (is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
		$materialsRequests = new MaterialsRequest();
		$materialsRequests->createdBy = $this->id;
		if ($homeLibrary->yearlyRequestLimitType == 0) {
			$materialsRequests->whereAdd('dateCreated >= unix_timestamp(now() - interval 1 year)');
		} else {
			$calendarStartMonthDay = $homeLibrary->requestCalendarStartDate;
			//Figure out if we're after the calendar start date for the year
			$currentMonthDay = date('m-d');
			$requestStartYear = date('Y');
			if ($currentMonthDay <= $calendarStartMonthDay) {
				$requestStartYear = $requestStartYear - 1;
			}
			$requestStartDate = date_create_from_format('m-d-Y', "$calendarStartMonthDay-$requestStartYear");
			$requestStartTime = $requestStartDate->getTimestamp();
			$materialsRequests->whereAdd("dateCreated >= $requestStartTime");
		}

		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
		$statusQueryNotCancelled = new MaterialsRequestStatus();
		$statusQueryNotCancelled->libraryId = $homeLibrary->libraryId;
		$statusQueryNotCancelled->whereAdd('isPatronCancel = 0 OR ISNULL(isPatronCancel)');
		$materialsRequests->joinAdd($statusQueryNotCancelled, 'INNER', 'status', 'status', 'id');

		return $materialsRequests->count();
	}

	public function getNumActiveMaterialsRequests(): int {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
		$homeLibrary = $this->getHomeLibrary();
		if (is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		$statusQuery = new MaterialsRequestStatus();
		$statusQuery->libraryId = $homeLibrary->libraryId;
		$statusQuery->isActive = 1;

		$materialsRequests = new MaterialsRequest();
		$materialsRequests->createdBy = UserAccount::getActiveUserId();
		$materialsRequests->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
		return $materialsRequests->count();
	}

	public function getMaterialsRequests() {
		$homeLibrary = $this->getHomeLibrary();
		if (is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
		$allRequests = [];
		$showOpen = true;
		if (isset($_REQUEST['requestsToShow']) && $_REQUEST['requestsToShow'] == 'allRequests') {
			$showOpen = false;
		}

		$formats = MaterialsRequest::getFormats(true);

		$materialsRequests = new MaterialsRequest();
		$materialsRequests->createdBy = $this->id;
		$materialsRequests->orderBy('title, dateCreated');

		$statusQuery = new MaterialsRequestStatus();
		if ($showOpen) {
			$statusQuery->libraryId = $homeLibrary->libraryId;
			$statusQuery->isOpen = 1;
		}
		$materialsRequests->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
		$materialsRequests->selectAdd();
		$materialsRequests->selectAdd('materials_request.*, description as statusLabel');
		$materialsRequests->find();
		while ($materialsRequests->fetch()) {
			if (array_key_exists($materialsRequests->format, $formats)) {
				$materialsRequests->format = $formats[$materialsRequests->format];
			}
			$allRequests[] = clone $materialsRequests;
		}

		return $allRequests;
	}

	public function submitLocalIllRequest(): array {
		if ($this->hasIlsConnection()) {
			$homeLocation = Location::getDefaultLocationForUser();
			if ($homeLocation != null) {
				//Get configuration for the form.
				require_once ROOT_DIR . '/sys/InterLibraryLoan/LocalIllForm.php';
				$localIllForm = new LocalIllForm();
				$localIllForm->id = $homeLocation->localIllFormId;
				if ($localIllForm->find(true)) {
					$results = $this->getCatalogDriver()->submitLocalIllRequest($this, $localIllForm);
					if ($results['success']) {
						$thisUser = translate([
							'text' => 'You',
							'isPublicFacing' => true,
						]);
						if (!empty($this->parentUser)) {
							$thisUser = $this->displayName;
						}
						$viewHoldsText = translate([
							'text' => 'On Hold for %1%',
							1 => $thisUser,
							'isPublicFacing' => true,
							'inAttribute' => true
						]);
						$recordId = $_REQUEST['catalogKey'] ?? '';
						$results['viewHoldsAction'] = "<a id='onHoldAction$recordId' href='/MyAccount/Holds' class='btn btn-sm btn-info btn-wrap' title='$viewHoldsText'>$viewHoldsText</a>";

						$this->clearCache();

						$this->forceReloadOfHolds();
					}
				} else {
					$results = [
						'title' => translate([
							'text' => 'Invalid Configuration',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => "Local ILL settings do not exist, please contact the library to make a request.",
							'isPublicFacing' => true,
						]),
						'success' => false,
					];
				}
			} else {
				$results = [
					'title' => translate([
						'text' => 'Invalid Configuration',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => "Your account does not hava a valid home library, please contact the library to make a request.",
						'isPublicFacing' => true,
					]),
					'success' => false,
				];
			}
			return $results;
		} else {
			return [
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'success' => false,
				'message' => translate([
					'text' => 'Cannot submit ILL request, not connected to an ILS.',
					'isPublicFacing' => true
				])
			];
		}
	}

	private function loadYearInReviewInfo(): void {
		if ($this->_hasYearInReview == null) {
			$this->_hasYearInReview = false;
			$this->_yearInReviewResults = false;
			$this->_yearInReviewSetting = false;
			try {
				require_once ROOT_DIR . '/sys/YearInReview/UserYearInReview.php';
				require_once ROOT_DIR . '/sys/YearInReview/YearInReviewSetting.php';
				$userYearInReview = new UserYearInReview();
				$userYearInReview->userId = $this->id;
				$userYearInReview->wrappedActive = true;
				if ($userYearInReview->find(true)) {
					$this->_yearInReviewResults = $userYearInReview;
					$yearInReviewSetting = new YearInReviewSetting();
					$yearInReviewSetting->id = $userYearInReview->settingId;
					if ($yearInReviewSetting->find(true)) {
						$this->_yearInReviewSetting = $yearInReviewSetting;
						global $interface;
						$interface->assign('yearInReviewName', $yearInReviewSetting->name);
						$this->_hasYearInReview = true;
					}
				}
			} catch (Exception $e) {
				//We get an exception if the tables are not setup, ignore
			}
		}
	}

	private $_hasYearInReview;
	private $_yearInReviewSetting;
	/** @var UserYearInReview */
	private $_yearInReviewResults;

	public function hasYearInReview(): bool {
		$this->loadYearInReviewInfo();
		return $this->_hasYearInReview;
	}

	public function yearInReviewViewed(): bool {
		$yearInReviewResults = $this->getYearInReviewResult();
		if (!is_null($yearInReviewResults)) {
			return $yearInReviewResults->wrappedViewed;
		} else {
			return false;
		}
	}

	public function getYearInReviewSetting(): YearInReviewSetting|false {
		//Use the side effect of has Year
		$this->loadYearInReviewInfo();
		return $this->_yearInReviewSetting;
	}

	public function getYearInReviewResult(): ?UserYearInReview {
		$this->loadYearInReviewInfo();
		if (empty($this->_yearInReviewResults)) {
			return null;
		} else {
			return $this->_yearInReviewResults;
		}
	}

	public function getYearInReviewResultData(): ?stdClass {
		$this->loadYearInReviewInfo();
		if (empty($this->_yearInReviewResults->wrappedResults)) {
			return null;
		} else {
			return json_decode($this->_yearInReviewResults->wrappedResults);
		}
	}

	public function canActiveUserEdit() : bool {
		if ($this->source == 'admin') {
			if ($this->username == 'aspen_admin') {
				return UserAccount::getActiveUserObj()->isAspenAdminUser();
			}elseif ($this->username == 'nyt_user') {
				return false;
			}else{
				return UserAccount::userHasPermission('Manage Local Administrators');
			}
		}elseif ($this->source == 'development') {
			return UserAccount::getActiveUserObj()->isAspenAdminUser();
		}
		return true;
	}

	public function canActiveUserDelete() : bool {
		if ($this->source == 'admin') {
			if ($this->username == 'aspen_admin' || $this->username == 'nyt_user') {
				return false;
			}
		}
		return $this->canActiveUserEdit();
	}

	public function updateStructureForEditingObject($structure) : array {
		if ($this->source == 'admin' && $this->username == 'aspen_admin') {
			$structure['username']['readOnly'] = true;
			$structure['password']['readOnly'] = true;
			$structure['firstname']['readOnly'] = true;
			$structure['lastname']['readOnly'] = true;
			$structure['email']['readOnly'] = true;
			unset($structure['additionalAdministrationLocations']);
		}

		return $structure;
	}

	public function validateStrongPassword() : array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];
		if (strlen($this->password) < 12) {
			$validationResults['validatedOk'] = false;
			$validationResults['errors'][] = 'The provided password is too short';
		}else if (strlen($this->password) > 50) {
			$validationResults['validatedOk'] = false;
			$validationResults['errors'][] = 'The provided password is too long';
		}

		if (!preg_match("/[A-Z]/", $this->password)){
			$validationResults['validatedOk'] = false;
			$validationResults['errors'][] = 'At least one upper case letter must be included in the password.';
		}
		if (!preg_match("/[a-z]/", $this->password)){
			$validationResults['validatedOk'] = false;
			$validationResults['errors'][] = 'At least one lower case letter must be included in the password.';
		}
		if (!preg_match("/[0-9]/", $this->password)){
			$validationResults['validatedOk'] = false;
			$validationResults['errors'][] = 'At least one number must be included in the password.';
		}
		if (!preg_match("/[-_~!@#$%^&*.()+=]/", $this->password)){
			$validationResults['validatedOk'] = false;
			$validationResults['errors'][] = 'At least one special character (-_~!@#$%^&*.+=) must be included in the password.';
		}

		return $validationResults;
	}
}

function modifiedEmpty($var): bool {
	// specified values of zero will not be considered empty
	return empty($var) && $var !== 0 && $var !== '0';
}
