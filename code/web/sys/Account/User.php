<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
class User extends DataObject
{
	###START_AUTOCODE
	/* the code below is auto generated do not remove the above tag */

	public $__table = 'user';                            // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $source;
	public $username;                        // string(30)  not_null unique_key
	public $displayName;                     // string(30)
	public $password;                        // string(32)  not_null
	public $firstname;                       // string(50)  not_null
	public $lastname;                        // string(50)  not_null
	public $email;                           // string(250)  not_null
	public $phone;                           // string(30)
	public $cat_username;                    // string(50)
	public $cat_password;                    // string(50)
	public $patronType;
	public $created;                         // datetime(19)  not_null binary
	public $homeLocationId;					 // int(11)
	public $myLocation1Id;					 // int(11)
	public $myLocation2Id;					 // int(11)
	public $trackReadingHistory; 			 // tinyint
	public $initialReadingHistoryLoaded;
	public $lastReadingHistoryUpdate;
	public $bypassAutoLogout;        //tinyint
	public $disableRecommendations;     //tinyint
	public $disableCoverArt;     //tinyint
	public $overdriveEmail;
	public $promptForOverdriveEmail; //Semantics of this have changed to not prompting for hold settings
	public $hooplaCheckOutConfirmation;
	public $preferredLibraryInterface;
	public $noPromptForUserReviews; //tinyint(1)
    public $lockedFacets;
	public $alternateLibraryCard;
	public $alternateLibraryCardPassword;
	public $hideResearchStarters;

	public $holdInfoLastLoaded;
	public $checkoutInfoLastLoaded;

	/** @var Role[] */
	private $_roles;
	private $_permissions;
	private $_masqueradingRoles;

	public $interfaceLanguage;
	public $searchPreferenceLanguage;

	public $rememberHoldPickupLocation;
	public $pickupLocationId;

	public $lastListUsed;

	public $lastLoginValidation;

	public $updateMessage;
	public $updateMessageIsError;

	public $proPayPayerAccountId;

	/** @var User $parentUser */
	private $parentUser;
	/** @var User[] $linkedUsers */
	private $linkedUsers;
	private $viewers;

	//Data that we load, but don't store in the User table
	public $_fullname;
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
	public $_noticePreferenceLabel;
	private $_numMaterialsRequests = 0;
	private $_readingHistorySize = 0;

	// CarlX Option
	public $_emailReceiptFlag;
	public $_availableHoldNotice;
	public $_comingDueNotice;
	public $_phoneType;

	function getNumericColumnNames() : array
	{
		return ['trackReadingHistory', 'hooplaCheckOutConfirmation', 'initialReadingHistoryLoaded', 'updateMessageIsError'];
	}

	function getEncryptedFieldNames() : array {
		return ['password', 'firstname', 'lastname', 'email', 'displayName', 'phone', 'overdriveEmail', 'alternateLibraryCardPassword', $this->getPasswordOrPinField()];
	}

	function getLists() {
		require_once ROOT_DIR . '/sys/UserLists/UserList.php';

		$lists = array();

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

	protected $_catalogDriver = null;

	/**
	 * Get a connection to the catalog for the user
	 *
	 * @return CatalogConnection
	 */
	function getCatalogDriver()
	{
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

	function hasIlsConnection()
	{
		$driver = $this->getCatalogDriver();
		if ($driver == null){
			return false;
		}else{
			if ($driver->driver == null){
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
	function getAccountProfile(){
		if ($this->_accountProfile != null){
			return $this->_accountProfile;
		}
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->name = $this->source;
		if ($accountProfile->find(true)){
			$this->_accountProfile = $accountProfile;
		}else{
			$this->_accountProfile = null;
		}
		return $this->_accountProfile;
	}

	function __get($name){
		if ($name == 'roles') {
			return $this->getRoles();
		}elseif ($name == 'linkedUsers'){
			return $this->getLinkedUsers();
		}elseif ($name == 'materialsRequestReplyToAddress'){
			if (!isset($this->materialsRequestReplyToAddress)) {
				$this->getStaffSettings();
			}
			return $this->materialsRequestReplyToAddress;
		}elseif ($name == 'materialsRequestEmailSignature'){
			if (!isset($this->materialsRequestEmailSignature)) {
				$this->getStaffSettings();
			}
			return $this->materialsRequestEmailSignature;
		}else{
			return $this->_data[$name];
		}
	}

	function __set($name, $value){
		if ($name == 'roles') {
			$this->setRoles($value);
		}else{
			$this->_data[$name] = $value;
		}
	}

	function setRoles($value){
		$this->_roles = $value;
		//Update the database, first remove existing values
		$this->saveRoles();
	}

	function getRoles(){
		if (is_null($this->_roles)){
			$this->_roles = array();
			//Load roles for the user from the user
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			require_once ROOT_DIR . '/sys/Account/PType.php';
			$role = new Role();
			$canUseTestRoles = false;
			if ($this->id){
				//Get role based on patron type
				$patronType = new PType();
				$patronType->pType = $this->patronType;
				if ($patronType->find(true)){
					if ($patronType->assignedRoleId != -1) {
						$role = new Role();
						$role->roleId = $patronType->assignedRoleId;
						if ($role->find(true)){
							$this->_roles[$role->roleId] = clone $role;
							if ($this->_roles[$role->roleId]->hasPermission('Test Roles')){
								$canUseTestRoles = true;
							}
						}
					}
				}

				$escapedId = $this->escape($this->id);
				$role->query("SELECT roles.* FROM roles INNER JOIN user_roles ON roles.roleId = user_roles.roleId WHERE userId = " . $escapedId . " ORDER BY name");
				while ($role->fetch()){
					$this->_roles[$role->roleId] = clone $role;
					if ($this->_roles[$role->roleId]->hasPermission('Test Roles')){
						$canUseTestRoles = true;
					}
				}
			}

			//Setup a test role if provided
			$testRole = '';
			if (isset($_REQUEST['test_role'])){
				$testRole = $_REQUEST['test_role'];
			}elseif (isset($_COOKIE['test_role'])){
				$testRole = $_COOKIE['test_role'];
			}
			if ($canUseTestRoles && $testRole != ''){
				if (is_array($testRole)){
					$testRoles = $testRole;
				}else{
					$testRoles = array($testRole);
				}
				foreach ($testRoles as $tmpRole){
					$role = new Role();
					if (is_numeric($tmpRole)){
						$role->roleId = $tmpRole;
					}else{
						$role->name = $tmpRole;
					}
					$found = $role->find(true);
					if ($found == true){
						$this->_roles[$role->roleId] = clone $role;
					}
				}
			}
		}

		return $this->_roles;
	}

	private $materialsRequestReplyToAddress;
	private $materialsRequestEmailSignature;

	function getStaffSettings(){
		require_once ROOT_DIR . '/sys/Account/UserStaffSettings.php';
		$staffSettings = new UserStaffSettings();
		$staffSettings->get('userId', $this->id);
		$this->materialsRequestReplyToAddress = $staffSettings->materialsRequestReplyToAddress;
		$this->materialsRequestEmailSignature = $staffSettings->materialsRequestEmailSignature;
	}

	function getBarcode()
	{
		if ($this->getAccountProfile() == null){
			return trim($this->cat_username);
		}else {
			if ($this->getAccountProfile()->loginConfiguration == 'barcode_pin') {
				return trim($this->cat_username);
			} else {
				return trim($this->cat_password);
			}
		}
	}

	function getPasswordOrPin()
	{
		if ($this->getAccountProfile() == null) {
			return trim($this->cat_password);
		}else{
			if ($this->getAccountProfile()->loginConfiguration == 'barcode_pin') {
				return trim($this->cat_password);
			} else {
				return trim($this->cat_username);
			}
		}
	}

	function getPasswordOrPinField(){
		if ($this->getAccountProfile() == null) {
			return 'cat_password';
		}else{
			if ($this->getAccountProfile()->loginConfiguration == 'barcode_pin') {
				return 'cat_password';
			} else {
				return 'cat_username';
			}
		}
	}

	function saveRoles(){
		if (isset($this->id) && isset($this->_roles) && is_array($this->_roles)){
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$escapedId = $this->escape($this->id);
			$role->query("DELETE FROM user_roles WHERE userId = " . $escapedId);
			//Now add the new values.
			if (count($this->_roles) > 0){
				$values = array();
				foreach ($this->_roles as $roleId => $roleName){
					$values[] = "({$this->id},{$roleId})";
				}
				$values = join(', ', $values);
				$role->query("INSERT INTO user_roles ( `userId` , `roleId` ) VALUES $values");
			}
		}
	}

	/**
	 * @return User[]
	 */
	function getLinkedUsers(){
		if (is_null($this->linkedUsers)){
 			$this->linkedUsers = array();
			/* var Library $library */
			global $library;
			global $memCache;
			global $serverName;
			global $logger;
			if ($this->id && $library->allowLinkedAccounts){
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->primaryAccountId = $this->id;
				$userLink->linkingDisabled = "0";
				try {
					$userLink->find();
					while ($userLink->fetch()) {
						if (!$this->isBlockedAccount($userLink->linkedAccountId)) {
							$linkedUser = new User();
							$linkedUser->id = $userLink->linkedAccountId;
							if ($linkedUser->find(true)) {
								/** @var User $userData */
								$userData = $memCache->get("user_{$serverName}_{$linkedUser->id}");
								if ($userData === false || isset($_REQUEST['reload'])) {
									//Load full information from the catalog
									$linkedUser = UserAccount::validateAccount($linkedUser->cat_username, $linkedUser->cat_password, $linkedUser->source, $this);
								} else {
									$logger->log("Found cached linked user {$userData->id}", Logger::LOG_DEBUG);
									$linkedUser = $userData;
								}
								if ($linkedUser && !($linkedUser instanceof AspenError)) {
									$this->linkedUsers[] = clone($linkedUser);
								}
							}
						}
					}
				}catch (PDOException $e){
					//Disabling of linking has not been enabled yet. 
				}
			}
		}
		return $this->linkedUsers;
	}

	private $linkedUserObjects;
	function getLinkedUserObjects(){
		if (is_null($this->linkedUserObjects)){
			$this->linkedUserObjects = array();
			try {
				/* var Library $library */
				global $library;
				if ($this->id && $library->allowLinkedAccounts) {
					require_once ROOT_DIR . '/sys/Account/UserLink.php';
					$userLink = new UserLink();
					$userLink->primaryAccountId = $this->id;
					$userLink->linkingDisabled = "0";
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
			}catch (Exception $e){
				//Tables are likely not fully updated
				global $logger;
				$logger->log("Error loading linked users $e", Logger::LOG_ERROR);
			}
		}
		return $this->linkedUserObjects;
	}

	public function setParentUser($user){
		$this->parentUser =  $user;
	}

	// Account Blocks //
	private $blockAll = null; // set to null to signal unset, boolean when set
	private $blockedAccounts = null; // set to null to signal unset, array when set

	/**
	 * Checks if there is any settings disallowing the account $accountIdToCheck to be linked to this user.
	 *
	 * @param  $accountIdToCheck string   linked account Id to check for blocking
	 * @return bool                       true for blocking, false for no blocking
	 */
	public function isBlockedAccount($accountIdToCheck) {
		if (is_null($this->blockAll)) $this->setAccountBlocks();
		return $this->blockAll || in_array($accountIdToCheck, $this->blockedAccounts);
	}

	private function setAccountBlocks() {
		// default settings
		$this->blockAll = false;
		$this->blockedAccounts = array();

		require_once ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php';
		$accountBlock = new BlockPatronAccountLink();
		$accountBlock->primaryAccountId = $this->id;
		if ($accountBlock->find()) {
			while ($accountBlock->fetch(false)) {
				if ($accountBlock->blockLinking) $this->blockAll = true; // any one row that has block all on will set this setting to true for this account.
				if ($accountBlock->blockedLinkAccountId) $this->blockedAccounts[] = $accountBlock->blockedLinkAccountId;
			}
		}
	}

	/**
	 * @param string $source
	 * @return User[]
	 */
	function getRelatedEcontentUsers($source)
	{
		$users = array();
		if ($this->isValidForEContentSource($source)) {
			$users[$this->cat_username . ':' . $this->cat_password] = $this;
		}
		foreach ($this->getLinkedUsers() as $linkedUser) {
			if ($linkedUser->isValidForEContentSource($source)) {
				if (!array_key_exists($linkedUser->cat_username . ':' . $linkedUser->cat_password, $users)) {
					$users[$linkedUser->cat_username . ':' . $linkedUser->cat_password] = $linkedUser;
				}
			}
		}

		return $users;
	}

	function isValidForEContentSource($source)
	{
		global $enabledModules;
		if ($this->parentUser == null || ($this->getBarcode() != $this->parentUser->getBarcode())) {
			$userHomeLibrary = Library::getPatronHomeLibrary($this);
			if ($userHomeLibrary) {
				if ($source == 'overdrive') {
					if (array_key_exists('OverDrive', $enabledModules) && $userHomeLibrary->overDriveScopeId > 0){
						$driver = OverDriveDriver::getOverDriveDriver();
						return $driver->isCirculationEnabled();
					}else {
						return false;
					}
				} elseif ($source == 'hoopla') {
					return array_key_exists('Hoopla', $enabledModules) && $userHomeLibrary->hooplaLibraryID > 0;
				} elseif ($source == 'cloud_library') {
					return array_key_exists('Cloud Library', $enabledModules) && (count($userHomeLibrary->cloudLibraryScopes) > 0);
				} elseif ($source == 'axis360') {
					return array_key_exists('Axis 360', $enabledModules) && ($userHomeLibrary->axis360ScopeId > 0);
				}
			}
		}
		return false;
	}

	/**
	 * Returns a list of users that can view this account
	 *
	 * @return User[]
	 */
	/** @noinspection PhpUnused */
	function getViewers(){
		if (is_null($this->viewers)){
			$this->viewers = array();
			/* var Library $library */
			global $library;
			if ($this->id && $library->allowLinkedAccounts){
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->linkedAccountId = $this->id;
				$userLink->linkingDisabled = "0";
				$userLink->find();
				while ($userLink->fetch()){
					$linkedUser = new User();
					$linkedUser->id = $userLink->primaryAccountId;
					if ($linkedUser->find(true)){
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
	 * @param User $user
	 *
	 * @return boolean
	 */
	function addLinkedUser($user){
		/* var Library $library */
		global $library;
		if ($library->allowLinkedAccounts && $user->id != $this->id) { // library allows linked accounts and the account to link is not itself
			$linkedUsers = $this->getLinkedUsers();
			foreach ($linkedUsers as $existingUser) {
				if ($existingUser->id == $user->id) {
					//We already have a link to this user
					return true;
				}
			}

			// Check for Account Blocks
			if ($this->isBlockedAccount($user->id)) return false;

			//Check to make sure the account we are linking to allows linking
			$linkLibrary = $user->getHomeLibrary();
			if (!$linkLibrary->allowLinkedAccounts){
				return false;
			}

			// Add Account Link
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink                   = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->linkedAccountId  = $user->id;
			$result = $userLink->insert();
			if (true == $result) {
				$this->linkedUsers[] = clone($user);
				return true;
			}
		}
		return false;
	}

	function removeLinkedUser($userId){
		/* var Library $library */
		global $library;
		if ($library->allowLinkedAccounts) {
			require_once ROOT_DIR . '/sys/Account/UserLink.php';
			$userLink                   = new UserLink();
			$userLink->primaryAccountId = $this->id;
			$userLink->linkedAccountId  = $userId;
			$ret                        = $userLink->delete(true);

			//Force a reload of data
			$this->linkedUsers = null;
			$this->getLinkedUsers();

			return $ret == 1;
		}
		return false;
	}


	function update(){
		if (empty($this->created)) {
			$this->created = date('Y-m-d');
		}
		$this->fixFieldLengths();
		$result = parent::update();
		$this->saveRoles();
		$this->clearCache(); // Every update to object requires clearing the Memcached version of the object
		return $result;
	}

	function insert(){
		//set default values as needed
		if (!isset($this->homeLocationId)) {
			$this->homeLocationId = 0;
			global $logger;
			$logger->log('No Home Location ID was set for newly created user.', Logger::LOG_WARNING);
		}
		$this->pickupLocationId = $this->homeLocationId;
		if (!isset($this->myLocation1Id)) $this->myLocation1Id = 0;
		if (!isset($this->myLocation2Id)) $this->myLocation2Id = 0;
		if (!isset($this->bypassAutoLogout)) $this->bypassAutoLogout = 0;

		if (empty($this->created)){
			$this->created = date('Y-m-d');
		}
		$this->fixFieldLengths();
		parent::insert();
		$this->saveRoles();
		$this->clearCache();
	}

	function hasRole($roleName){
		$myRoles = $this->__get('roles');
		return in_array($roleName, $myRoles);
	}

    static function getObjectStructure() : array {
		//Lookup available roles in the system
		require_once ROOT_DIR . '/sys/Administration/Role.php';
		$roleList = Role::getLookup();

		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the in the system'),
				'firstname' => array('property'=>'firstname', 'type'=>'label', 'label'=>'First Name', 'description'=>'The first name for the user.'),
				'lastname' => array('property'=>'lastname', 'type'=>'label', 'label'=>'Last Name', 'description'=>'The last name of the user.'),
				'homeLibraryName' => array('property'=>'homeLibraryName', 'type'=>'label', 'label'=>'Home Library', 'description'=>'The library the user belongs to.'),
				'homeLocation' => array('property'=>'homeLocation', 'type'=>'label', 'label'=>'Home Location', 'description'=>'The branch the user belongs to.'),
		);

		global $configArray;
		$barcodeProperty = $configArray['Catalog']['barcodeProperty'];
		$structure['barcode'] = array('property'=>$barcodeProperty, 'type'=>'label', 'label'=>'Barcode', 'description'=>'The barcode for the user.');

		$structure['roles'] = array('property'=>'roles', 'type'=>'multiSelect', 'listStyle' =>'checkbox', 'values'=>$roleList, 'label'=>'Roles', 'description'=>'A list of roles that the user has.');

		return $structure;
	}

	function hasRatings(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';

		$rating = new UserWorkReview();
		$rating->whereAdd("userId = {$this->id}");
		$rating->whereAdd('rating > 0'); // Some entries are just reviews (and therefore have a default rating of -1)
		$rating->find();
		if ($rating->getNumResults() > 0){
			return true;
		}else{
			return false;
		}
	}

	private $_runtimeInfoUpdated = false;
	function updateRuntimeInformation(){
		if (!$this->_runtimeInfoUpdated) {
			if ($this->getCatalogDriver()) {
				$this->getCatalogDriver()->updateUserWithAdditionalRuntimeInformation($this);
			}
			$this->_runtimeInfoUpdated = true;
		}
	}

	private $_contactInformationLoaded = false;
	function loadContactInformation(){
		if (!$this->_contactInformationLoaded) {
			if ($this->getCatalogDriver()) {
				$this->getCatalogDriver()->loadContactInformation($this);
			}
			$this->_contactInformationLoaded = true;
		}
	}

	function updateOverDriveOptions(){
		if (isset($_REQUEST['promptForOverdriveEmail']) && ($_REQUEST['promptForOverdriveEmail'] == 'yes' || $_REQUEST['promptForOverdriveEmail'] == 'on')){
			// if set check & on check must be combined because checkboxes/radios don't report 'offs'
			$this->promptForOverdriveEmail = 1;
		}else{
			$this->promptForOverdriveEmail = 0;
		}
		if (isset($_REQUEST['overdriveEmail'])){
			$this->overdriveEmail = strip_tags($_REQUEST['overdriveEmail']);
		}
		$this->update();

		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		$overDriveDriver->updateOptions($this);
	}

	function updateHooplaOptions(){
		if (isset($_REQUEST['hooplaCheckOutConfirmation']) && ($_REQUEST['hooplaCheckOutConfirmation'] == 'yes' || $_REQUEST['hooplaCheckOutConfirmation'] == 'on')) {
			// if set check & on check must be combined because checkboxes/radios don't report 'offs'
			$this->hooplaCheckOutConfirmation = 1;
		}else{
			$this->hooplaCheckOutConfirmation = 0;
		}
		$this->update();
	}

	function updateStaffSettings(){
		if (isset($_REQUEST['bypassAutoLogout']) && ($_REQUEST['bypassAutoLogout'] == 'yes' || $_REQUEST['bypassAutoLogout'] == 'on')){
			$this->bypassAutoLogout = 1;
		}else{
			$this->bypassAutoLogout = 0;
		}
		if (isset($_REQUEST['materialsRequestEmailSignature'])) {
			$this->setMaterialsRequestEmailSignature($_REQUEST['materialsRequestEmailSignature']);
		}
		if (isset($_REQUEST['materialsRequestReplyToAddress'])) {
			$this->setMaterialsRequestReplyToAddress($_REQUEST['materialsRequestReplyToAddress']);
		}
		$this->update();
	}

	function updateUserPreferences(){
		// Validate that the input data is correct
		if (isset($_POST['pickupLocation']) && !is_array($_POST['pickupLocation']) && preg_match('/^\d{1,3}$/', $_POST['pickupLocation']) == 0){
			return ['success' => false, 'message' => 'The preferred pickup location had an incorrect format.'];
		}
		if (isset($_POST['myLocation1']) && !is_array($_POST['myLocation1']) && preg_match('/^\d{1,3}$/', $_POST['myLocation1']) == 0){
			return ['success' => false, 'message' => 'The 1st location had an incorrect format.'];
		}
		if (isset($_POST['myLocation2']) && !is_array($_POST['myLocation2']) && preg_match('/^\d{1,3}$/', $_POST['myLocation2']) == 0){
			return ['success' => false, 'message' => 'The 2nd location had an incorrect format.'];
		}

		if (isset($_REQUEST['profileLanguage'])){
			$this->interfaceLanguage = $_REQUEST['profileLanguage'];
		}
		if (isset($_REQUEST['searchPreferenceLanguage'])){
			$this->searchPreferenceLanguage = $_REQUEST['searchPreferenceLanguage'];
		}

		//Make sure the selected location codes are in the database.
		if (isset($_POST['pickupLocation'])){
			if ($_POST['pickupLocation'] == 0){
				$this->pickupLocationId = $_POST['pickupLocation'];
			}else{
				$location = new Location();
				$location->get('locationId', $_POST['pickupLocation'] );
				if ($location->getNumResults() != 1) {
					return ['success' => false, 'message' => 'The pickup location could not be found in the database.'];
				} else {
					$this->pickupLocationId = $_POST['pickupLocation'];
				}
			}
		}
		if (isset($_POST['myLocation1'])){
			if ($_POST['myLocation1'] == 0){
				$this->myLocation1Id = $_POST['myLocation1'];
			}else{
				$location = new Location();
				$location->get('locationId', $_POST['myLocation1'] );
				if ($location->getNumResults() != 1) {
					return ['success' => false, 'message' => 'The 1st location could not be found in the database.'];
				} else {
					$this->myLocation1Id = $_POST['myLocation1'];
				}
			}
		}
		if (isset($_POST['myLocation2'])){
			if ($_POST['myLocation2'] == 0){
				$this->myLocation2Id = $_POST['myLocation2'];
			}else{
				$location = new Location();
				$location->get('locationId', $_POST['myLocation2'] );
				if ($location->getNumResults() != 1) {
					return ['success' => false, 'message' => 'The 2nd location could not be found in the database.'];
				} else {
					$this->myLocation2Id = $_POST['myLocation2'];
				}
			}
		}

		$this->noPromptForUserReviews = (isset($_POST['noPromptForUserReviews']) && $_POST['noPromptForUserReviews'] == 'on')? 1 : 0;
		$this->rememberHoldPickupLocation = (isset($_POST['rememberHoldPickupLocation']) && $_POST['rememberHoldPickupLocation'] == 'on')? 1 : 0;
		global $enabledModules;
		global $library;
		if (array_key_exists('EBSCO EDS', $enabledModules) && !empty($library->edsSettingsId)){
			$this->hideResearchStarters = (isset($_POST['hideResearchStarters']) && $_POST['hideResearchStarters'] == 'on')? 1 : 0;
		}

		if ($this->hasEditableUsername()){
			$result = $this->updateEditableUsername($_POST['username']);
			if ($result['success'] == false){
				return $result;
			}
		}

		if ($this->getShowAutoRenewSwitch()){
			$allowAutoRenewal = ($_REQUEST['allowAutoRenewal'] == 'on' || $_REQUEST['allowAutoRenewal'] == 'true');
			$result = $this->updateAutoRenewal($allowAutoRenewal);
			if ($result['success'] == false){
				return $result;
			}
		}
		$this->clearCache();
		$saveResult = $this->update();
		if ($saveResult === false){
			return ['success' => false, 'message' => 'Could not save to the database'];
		}else{
			return ['success' => true, 'message' => 'Your preferences were updated successfully'];
		}
	}

	/**
	 * Clear out the cached version of the patron profile.
	 */
	function clearCache(){
		global $memCache;
		global $serverName;
		$memCache->delete("user_{$serverName}_" . $this->id); // now stored by User object id column
	}

	/**
	 * @param $list UserList           object of the user list to check permission for
	 * @return  bool       true if this user can edit passed list
	 */
	function canEditList($list) {
		if (($this->id == $list->user_id) || $this->hasPermission('Edit All Lists')){
			return true;
		}
		return false;
	}

	/**
	 * @return Library|null
	 */
	function getHomeLibrary(){
		if ($this->_homeLibrary == null){
			$this->_homeLibrary = Library::getPatronHomeLibrary($this);
		}
		return $this->_homeLibrary;
	}

	function getHomeLibrarySystemName(){
		return $this->getHomeLibrary()->displayName;
	}

	/** @noinspection PhpUnused */
	public function getNumHoldsAvailableTotal($includeLinkedUsers = true){
		$this->updateRuntimeInformation();
		$myHolds = $this->_numHoldsAvailableIls + $this->_numHoldsAvailableOverDrive;
		if ($includeLinkedUsers){
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
	public function getTotalFines($includeLinkedUsers = true){
		$totalFines = $this->_finesVal;
		if ($includeLinkedUsers){
			if ($this->totalFinesForLinkedUsers == -1){
				if ($this->getLinkedUsers() != null) {
					foreach ($this->linkedUsers as $user) {
						$totalFines += $user->getTotalFines(false);
					}
				}
				$this->totalFinesForLinkedUsers = $totalFines;
			}else{
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
	public function getCheckouts($includeLinkedUsers = true, $source = 'all'){
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		//Check to see if we should return cached information, we will reload it if we last fetched it more than
		//15 minutes ago or if the refresh option is selected
		$reloadCheckoutInformation = false;
		if (($this->checkoutInfoLastLoaded < (time() - 15 * 60)) || isset($_REQUEST['refreshCheckouts'])){
			$reloadCheckoutInformation = true;
		}

		$checkoutsToReturn = [];
		if ($reloadCheckoutInformation) {
			global $timer;
			$allCheckedOut = [];
			//Get checked out titles from the ILS
			if ($this->hasIlsConnection()) {
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
				$overDriveCheckedOutItems = $driver->getCheckouts($this, false);
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
				$timer->logTime("Loaded transactions from axis 360. {$this->id}");
				if ($source == 'all' || $source == 'axis360') {
					$checkoutsToReturn = array_merge($checkoutsToReturn, $axis360CheckedOutItems);
				}
			}

			//Delete all existing checkouts
			$checkout = new Checkout();
			$checkout->userId = $this->id;
			$checkout->delete(true);

			foreach ($allCheckedOut as $checkout){
				$checkout->insert();
			}

			$this->checkoutInfoLastLoaded = time();
			$this->update();
		}else{
			//fetch cached checkouts
			$checkout = new Checkout();
			$checkout->userId = $this->id;
			if ($source != 'all'){
				$checkout->type = $source;
			}
			$checkout->find();
			while ($checkout->fetch()){
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
		return $checkoutsToReturn;
	}

	public function isRecordCheckedOut($source, $recordId){
		require_once ROOT_DIR . "/sys/User/Checkout.php";
		$checkout = new Checkout();
		$checkout->userId = $this->id;
		$checkout->source = $source;
		$checkout->recordId = $recordId;
		if ($checkout->find(true)){
			return true;
		}else{
			return false;
		}
	}

	public function getHolds($includeLinkedUsers = true, $unavailableSort = 'sortTitle', $availableSort = 'expire', $source = 'all')
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		//Check to see if we should return cached information, we will reload it if we last fetched it more than
		//15 minutes ago or if the refresh option is selected
		$reloadHoldInformation = false;
		if (($this->holdInfoLastLoaded < time() - 15 * 60) || isset($_REQUEST['refreshHolds'])){
			$reloadHoldInformation = true;
		}

		$holdsToReturn = array(
			'available' => [],
			'unavailable' => []
		);;
		if ($reloadHoldInformation) {
			//When we reload holds, we will fetch from all sources so they can be cached.

			$allHolds = array(
				'available' => [],
				'unavailable' => []
			);;
			if ($this->hasIlsConnection()) {
				$ilsHolds = $this->getCatalogDriver()->getHolds($this);
				if ($ilsHolds instanceof AspenError) {
					$ilsHolds = array();
				}
				$allHolds = $ilsHolds;
				if ($source == 'all' || $source == 'ils') {
					$holdsToReturn = $ilsHolds;
				}
			}

			//Get holds from OverDrive
			if ($this->isValidForEContentSource('overdrive')) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
				$driver = new OverDriveDriver();
				$overDriveHolds = $driver->getHolds($this);
				$allHolds = array_merge_recursive($allHolds, $overDriveHolds);
				if ($source == 'all' || $source == 'overdrive') {
					$holdsToReturn = array_merge_recursive($holdsToReturn, $overDriveHolds);
				}
			}

			//Get holds from cloudLibrary
			if ($this->isValidForEContentSource('cloud_library')) {
				require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
				$driver = new CloudLibraryDriver();
				$cloudLibraryHolds = $driver->getHolds($this);
				$allHolds = array_merge_recursive($allHolds, $cloudLibraryHolds);
				if ($source == 'all' || $source == 'cloud_library') {
					$holdsToReturn = array_merge_recursive($holdsToReturn, $cloudLibraryHolds);
				}
			}

			//Get holds from Axis 360
			if ($this->isValidForEContentSource('axis360')) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				$axis360Holds = $driver->getHolds($this);
				$allHolds = array_merge_recursive($allHolds, $axis360Holds);
				if ($source == 'all' || $source == 'axis360') {
					$holdsToReturn = array_merge_recursive($holdsToReturn, $axis360Holds);
				}
			}

			//Delete all existing holds
			$hold = new Hold();
			$hold->userId = $this->id;
			$hold->delete(true);

			foreach ($allHolds['available'] as $holdToSave){
				$holdToSave->insert();
			}
			foreach ($allHolds['unavailable'] as $holdToSave){
				$holdToSave->insert();
			}
			$this->holdInfoLastLoaded = time();
			$this->update();
		}else{
			//fetch cached holds
			$hold = new Hold();
			$hold->userId = $this->id;
			if ($source != 'all'){
				$hold->type = $source;
			}
			$hold->find();
			while ($hold->fetch()){
				$key = $hold->source;
				if (!empty($hold->cancelId)){
					$key .= $hold->cancelId;
				}else{
					$key .= $hold->sourceId;
				}
				$key .= $hold->userId;
				if ($hold->available){
					$holdsToReturn['available'][$key] = clone $hold;
				}else{
					$holdsToReturn['unavailable'][$key] = clone $hold;
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
		$holdSort = function ($a, $b) use (&$indexToSortBy) {
			$titleA = $a->getSortTitle();
			$titleB = $b->getSortTitle();
			if ($indexToSortBy == 'sortTitle'){
				$a = $titleA;
				$b = $titleB;
			}elseif ($indexToSortBy == 'user'){
				$a = $a->getUserName();
				$b = $b->getUserName();
			}else {
				$a = isset($a->$indexToSortBy) ? $a->$indexToSortBy : null;
				$b = isset($b->$indexToSortBy) ? $b->$indexToSortBy : null;
			}

			// Put empty values (except for specified values of zero) at the bottom of the sort
			if (modifiedEmpty($a) && modifiedEmpty($b)) {
				if ($indexToSortBy != 'sortTitle'){
					return strnatcasecmp($titleA, $titleB);
				}else{
					return 0;
				}
			} elseif (!modifiedEmpty($a) && modifiedEmpty($b)) {
				return -1;
			} elseif (modifiedEmpty($a) && !modifiedEmpty($b)) {
				return 1;
			}

			if ($indexToSortBy == 'format') {
				if (is_array($a)){
					$a = implode($a, ',');
				}
				if (is_array($b)){
					$b = implode($b, ',');
				}
			}

			$ret = strnatcasecmp($a, $b);
			if ($ret == 0 && $indexToSortBy != 'sortTitle'){
				return strnatcasecmp($titleA, $titleB);
			}else{
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
					$indexToSortBy = 'create';
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

		return $holdsToReturn;
	}

	public function isRecordOnHold($source, $recordId){
		require_once ROOT_DIR . "/sys/User/Hold.php";
		$hold = new Hold();
		$hold->userId = $this->id;
		$hold->source = $source;
		$hold->recordId = $recordId;
		if ($hold->find(true)){
			return true;
		}else{
			return false;
		}
	}

	public function getCirculatedRecordActions($source, $recordId, $loadingLinkedUser = false)
	{
		$actions = [];
		$showUserName = $loadingLinkedUser;
//		if (!$loadingLinkedUser){
//			$linkedUsers = $this->getLinkedUsers();
//			if (count($linkedUsers) > 0){
//				$showUserName = true;
//			}
//		}
		if ($this->isRecordCheckedOut($source, $recordId)) {
			$actions[] = array(
				'title' => translate(['text' => 'Checked Out to %1%', 1 => $showUserName ? $this->displayName : 'You', 'isPublicFacing' => true]),
				'url' => "/MyAccount/CheckedOut",
				'requireLogin' => false,
				'btnType' => 'btn-info'
			);
		} elseif ($source != 'hoopla' && $this->isRecordOnHold($source, $recordId)) {
			$actions[] = array(
				'title' => translate(['text' => 'On Hold for %1%', 1 => $showUserName ? $this->displayName : 'You', 'isPublicFacing' => true]),
				'url' => "/MyAccount/Holds",
				'requireLogin' => false,
				'btnType' => 'btn-info'
			);
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
	public function getFines($includeLinkedUsers = true){

		if (!isset($this->ilsFinesForUser)){
			$this->ilsFinesForUser = $this->getCatalogDriver()->getFines($this);
			if ($this->ilsFinesForUser instanceof AspenError) {
				$this->ilsFinesForUser = array();
			}
		}
		$ilsFines[$this->id] = $this->ilsFinesForUser;

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				foreach ($this->getLinkedUsers() as $user) {
					$ilsFines += $user->getFines(false); // keep keys as userId
				}
			}
		}
		return $ilsFines;
	}

	public function getNameAndLibraryLabel(){
		return $this->getDisplayName() . ' - ' . $this->getHomeLibrarySystemName();
	}

	public function getValidHomeLibraryBranches($recordSource){
		$pickupLocations = $this->getValidPickupBranches($recordSource);
		$hasHomeLibrary = false;
		foreach ($pickupLocations as $key => $pickupLocation){
			if (is_object($pickupLocation)){
				if ($pickupLocation->locationId == $this->homeLocationId) {
					$hasHomeLibrary = true;
				}
			}else{
				unset($pickupLocations[$key]);
			}
		}
		if (!$hasHomeLibrary){
			$pickupLocations = array_merge([$this->getHomeLocation()], $pickupLocations);
		}
		return $pickupLocations;
	}

	/**
	 * Get a list of locations where a record can be picked up.  Handles liked accounts
	 * and filtering to make sure that the user is able to
	 *
	 * @param $recordSource string   The source of the record that we are placing a hold on
	 *
	 * @return Location[]
	 */
	public function getValidPickupBranches($recordSource){
		//Get the list of pickup branch locations for display in the user interface.
		// using $user to be consistent with other code use of getPickupBranches()
		$userLocation = new Location();
		if ($recordSource == $this->getAccountProfile()->recordSource){
			$locations = $userLocation->getPickupBranches($this);
		}else{
			$locations = array();
		}
		$linkedUsers = $this->getLinkedUsers();
		if (count($linkedUsers) > 0){
			$accountProfileForSource = new AccountProfile();
			$accountProfileForSource->recordSource = $recordSource;
			$accountProfileSource = '';
			if ($accountProfileForSource->find(true)){
				$accountProfileSource = $accountProfileForSource->name;
			}
			foreach ($linkedUsers as $linkedUser){
				if ($accountProfileSource == $linkedUser->source){
					$linkedUserLocation = new Location();
					$linkedUserPickupLocations = $linkedUserLocation->getPickupBranches($linkedUser, true);
					foreach ($linkedUserPickupLocations as $sortingKey => $pickupLocation) {
						if (!is_object($pickupLocation)){
							continue;
						}
						foreach ($locations as $mainSortingKey => $mainPickupLocation) {
							if (!is_object($mainPickupLocation)){
								continue;
							}
							// Check For Duplicated Pickup Locations
							if ($mainPickupLocation->libraryId == $pickupLocation->libraryId && $mainPickupLocation->locationId == $pickupLocation->locationId) {
								// Merge Linked Users that all have this pick-up location
								$pickupUsers = array_unique(array_merge($mainPickupLocation->pickupUsers, $pickupLocation->pickupUsers));
								$mainPickupLocation->pickupUsers = $pickupUsers;
								$pickupLocation->pickupUsers = $pickupUsers;

								// keep location with better sort key, remove the other
								if ($mainSortingKey == $sortingKey || $mainSortingKey[0] < $sortingKey[0] ) {
									unset ($linkedUserPickupLocations[$sortingKey]);
								} elseif ($mainSortingKey[0] == $sortingKey[0]) {
									if (strcasecmp($mainSortingKey, $sortingKey) > 0) unset ($locations[$mainSortingKey]);
									else unset ($linkedUserPickupLocations[$sortingKey]);
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
     * Place Hold
     *
     * Place a hold for the current user within their ILS
     *
     * @param   string $recordId            The id of the bib record
     * @param   string $pickupBranch        The branch where the user wants to pickup the item when available
     * @param   null|string $cancelDate     When the hold should be automatically cancelled if desired
     * @return  array                 An array with the following keys
     *                                result - true/false
     *                                message - the message to display
     * @access  public
     */
	function placeHold($recordId, $pickupBranch, $cancelDate = null) {
		$result = $this->getCatalogDriver()->placeHold($this, $recordId, $pickupBranch, $cancelDate);
		$this->updateAltLocationForHold($pickupBranch);
		if ($result['success']){
			$this->clearCache();
		}
		return $result;
	}

	function placeVolumeHold($recordId, $volumeId, $pickupBranch){
		$result = $this->getCatalogDriver()->placeVolumeHold($this, $recordId, $volumeId, $pickupBranch);
		$this->updateAltLocationForHold($pickupBranch);
		if ($result['success']){
			$this->clearCache();
		}
		return $result;
	}

	function confirmHold($recordId, $confirmationId) {
		$result = $this->getCatalogDriver()->confirmHold($this, $recordId, $confirmationId);
		if ($result['success']){
			$this->clearCache();
		}
		return $result;
	}

	function updateAltLocationForHold($pickupBranch){
		if ($this->_homeLocationCode != $pickupBranch) {
			global $logger;
			$logger->log("The selected pickup branch is not the user's home location, checking to see if we need to set an alternate branch", Logger::LOG_NOTICE);
			$location = new Location();
			$location->code = $pickupBranch;
			if ($location->find(true)) {
				$logger->log("Found the location for the pickup branch $pickupBranch {$location->locationId}", Logger::LOG_NOTICE);
				if ($this->myLocation1Id == 0) {
					$logger->log("Alternate location 1 is blank updating that", Logger::LOG_NOTICE);
					$this->myLocation1Id = $location->locationId;
					$this->update();
				} else if ($this->myLocation2Id == 0 && $location->locationId != $this->myLocation1Id) {
					$logger->log("Alternate location 2 is blank updating that", Logger::LOG_NOTICE);
					$this->myLocation2Id = $location->locationId;
					$this->update();
				}
			}else{
				$logger->log("Could not find location for $pickupBranch", Logger::LOG_ERROR);
			}
		}
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for placing item level holds.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @param null|string $cancelDate The date to automatically cancel the hold if not filled
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($recordId, $itemId, $pickupBranch, $cancelDate = null) {
		$result = $this->getCatalogDriver()->placeItemHold($this, $recordId, $itemId, $pickupBranch, $cancelDate);
		$this->updateAltLocationForHold($pickupBranch);
		if ($result['success']){
			$this->clearCache();
		}
		return $result;
	}

	/**
	 * Get the user referred to by id.  Will return false if the specified patron id is not
	 * the id of this user or one of the users that is linked to this user.
	 *
	 * @param $patronId     int  The patron to check
	 * @return User|false
	 */
	function getUserReferredTo($patronId){
		$patron = false;
		//Get the correct patron based on the information passed in.
		if ($patronId == $this->id){
			$patron = $this;
		}else{
			foreach ($this->getLinkedUsers() as $tmpUser){
				if ($tmpUser->id == $patronId){
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
	 * @param $recordId string  The Id of the record being cancelled
	 * @param $cancelId string  The Id of the hold to be cancelled.  Structure varies by ILS
	 *
	 * @return array            Information about the result of the cancellation process
	 */
	function cancelHold($recordId, $cancelId){
		$result = $this->getCatalogDriver()->cancelHold($this, $recordId, $cancelId);
		$this->clearCache();
		return $result;
	}

//		function changeHoldPickUpLocation($recordId, $itemToUpdateId, $newPickupLocation){
			//$recordId is not used to update change hold pick up location in driver
	function changeHoldPickUpLocation($itemToUpdateId, $newPickupLocation){
		$result = $this->getCatalogDriver()->changeHoldPickupLocation($this, null, $itemToUpdateId, $newPickupLocation);
		$this->clearCache();
		return $result;
	}

	function freezeHold($recordId, $holdId, $reactivationDate){
		$result = $this->getCatalogDriver()->freezeHold($this, $recordId, $holdId, $reactivationDate);
		$this->clearCache();
		return $result;
	}

	function freezeAllHolds() {
		$user = UserAccount::getLoggedInUser();
		$tmpResult = array( // set default response
			'success' => false,
			'message' => 'Error modifying hold.'
		);

		$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
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

				if ($frozen == 0 && $canFreeze == 1) {
					if ($holdType == 'ils') {
						$tmpResult = $user->freezeHold($recordId, $holdId, false);
						if ($tmpResult['success']) {
							$success++;
						}
					} else if ($holdType == 'axis360') {
						require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
						$driver = new Axis360Driver();
						$tmpResult = $driver->freezeHold($user, $recordId);
						if ($tmpResult['success']) {
							$success++;
						}
					} else if ($holdType == 'overdrive') {
						require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
						$driver = new OverDriveDriver();
						$tmpResult = $driver->freezeHold($user, $recordId);
						if ($tmpResult['success']) {
							$success++;
						}
					} else if ($holdType == 'cloud_library') {
						require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
						$driver = new CloudLibraryDriver();
						$tmpResult = $driver->freezeHold($user, $recordId);
						if ($tmpResult['success']) {
							$success++;
						}
					} else {
						$failed++;
						$tmpResult['message'] = '<div class="alert alert-warning">Hold not available</div>';
					}

				} else if ($canFreeze == 0) {
					$failed++;
				} else {
					$tmpResult['message'] = '<div class="alert alert-warning">All holds already frozen</div>';
				}
			}
		} else {
			$tmpResult['message'] = 'No holds available to freeze.';
		}

		if ($success >= 1) {
			$message = '<div class="alert alert-success">' . $success . ' of ' . $total . ' holds were frozen.</div>';

			if ($failed >= 1) {
				$message .= '<div class="alert alert-warning">' . $failed . ' holds failed to freeze.</div>';
			}

			$tmpResult['message'] = $message;
		} else {
			$tmpResult['message'] = '<div class="alert alert-warning">All holds already frozen</div>';
		}

		return $tmpResult;
	}
	function thawAllHolds(){
		$user = UserAccount::getLoggedInUser();
		$tmpResult = array( // set default response
			'success' => false,
			'message' => 'Error modifying hold.'
		);

		$allHolds = $user->getHolds(true, 'sortTitle', 'expire', 'all');
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
						if($tmpResult['success']){$success++;}
					} else if ($holdType == 'axis360') {
						require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
						$driver = new Axis360Driver();
						$tmpResult = $driver->thawHold($user, $recordId);
						if($tmpResult['success']){$success++;}
					} else if ($holdType == 'overdrive') {
						require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
						$driver = new OverDriveDriver();
						$tmpResult = $driver->thawHold($user, $recordId);
						if($tmpResult['success']){$success++;}
					} else if ($holdType == 'cloud_library') {
						require_once ROOT_DIR . '/Drivers/CloudLibraryDriver.php';
						$driver = new CloudLibraryDriver();
						$tmpResult = $driver->thawHold($user, $recordId);
						if($tmpResult['success']){$success++;}
					} else {
						$failed++;
						$tmpResult['message'] = '<div class="alert alert-warning">Hold not available</div>';
					}

				} else if ($canFreeze == 0) {
					$failed++;
				} else if ($frozen == 1) {
					$failed++;
				}

				if ($success >= 1 ){
					$message = '<div class="alert alert-success">' . $success . ' of ' . $total . ' holds were thawed.</div>';

					if ($failed >= 1) {
						$message .= '<div class="alert alert-warning">' . $failed . ' holds failed to thaw.</div>';
					}

					$tmpResult['message'] = $message;
				} else {
					$tmpResult['message'] = '<div class="alert alert-warning">All holds already thawed</div>';
				}
			}
		} else {
			$tmpResult['message'] = 'No holds available to thaw.';
		}

		return $tmpResult;
	}

	function thawHold($recordId, $holdId){
		$result = $this->getCatalogDriver()->thawHold($this, $recordId, $holdId);
		$this->clearCache();
		return $result;
	}

	function freezeOverDriveHold($overDriveId, $reactivationDate){
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		return $overDriveDriver->freezeHold($this, $overDriveId, $reactivationDate);
	}

	function thawOverDriveHold($overDriveId){
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		return $overDriveDriver->thawHold($this, $overDriveId);
	}

	function freezeAxis360Hold($recordId){
		require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
		$axis360Driver = new Axis360Driver();
		return $axis360Driver->freezeHold($this,$recordId);
	}

	function thawAxis360Hold($recordId){
		require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
		$axis360Driver = new Axis360Driver();
		return $axis360Driver->thawHold($this, $recordId);
	}

	function renewCheckout($recordId, $itemId = null, $itemIndex = null){
		$result = $this->getCatalogDriver()->renewCheckout($this, $recordId, $itemId, $itemIndex);
		$this->clearCache();
		return $result;
	}

	function renewAll($renewLinkedUsers = false){
		$renewAllResults = $this->getCatalogDriver()->renewAll($this);
		//Also renew linked Users if needed
		if ($renewLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				foreach ($this->getLinkedUsers() as $user) {
					$linkedResults = $user->renewAll(false);
					//Merge results
					$renewAllResults['Renewed'] += $linkedResults['Renewed'];
					$renewAllResults['NotRenewed'] += $linkedResults['NotRenewed'];
					$renewAllResults['Total'] += $linkedResults['Total'];
					if ($renewAllResults['success'] && !$linkedResults['success']){
						$renewAllResults['success'] = false;
						$renewAllResults['message'] = $linkedResults['message'];
					}else if (!$renewAllResults['success'] && !$linkedResults['success']){
						//Append the new message

						array_merge($renewAllResults['message'], $linkedResults['message']);
					}
				}
			}
		}
		$this->clearCache();
		return $renewAllResults;
	}

	public function getReadingHistory($page = 1, $recordsPerPage = 20, $sortOption = "checkedOut", $filter = "", $forExport = false) {
		return $this->getCatalogDriver()->getReadingHistory($this, $page, $recordsPerPage, $sortOption, $filter, $forExport);
	}

	public function doReadingHistoryAction($readingHistoryAction, $selectedTitles){
		$results = $this->getCatalogDriver()->doReadingHistoryAction($this, $readingHistoryAction, $selectedTitles);
		$this->clearCache();
		return $results;
	}

	public function deleteReadingHistoryEntryByTitleAuthor($title, $author) {
		return $this->getCatalogDriver()->deleteReadingHistoryEntryByTitleAuthor($this, $title, $author);
	}

	public function updateReadingHistoryBasedOnCurrentCheckouts() {
		$this->getCatalogDriver()->updateReadingHistoryBasedOnCurrentCheckouts($this);
	}

	/**
	 * Used by Account Profile, to show users any additional Admin roles they may have.
	 * @return bool
	 */
	public function isStaff(){
		if (count($this->getRoles()) > 0){
			return true;
		}else{
			require_once ROOT_DIR . '/sys/Account/PType.php';
			$pType = new PType();
			$pType->pType = $this->patronType;
			if ($pType->find(true)){
				return $pType->isStaff;
			}
		}
		return false;
	}

	public function updatePatronInfo($canUpdateContactInfo){
		$result = $this->getCatalogDriver()->updatePatronInfo($this, $canUpdateContactInfo);
		$this->clearCache();
		return $result;
	}

	public function updateHomeLibrary($newHomeLocationCode){
		$result = $this->getCatalogDriver()->updateHomeLibrary($this, $newHomeLocationCode);
		$this->clearCache();
		return $result;
	}

	/**
	 * Update the PIN or password for the user
	 *
	 * @return string[] keys are success and errors or message
	 */
	function updatePin(){
		if (isset($_REQUEST['pin'])){
			$oldPin = $_REQUEST['pin'];
		}else{
			return ['success' => false, 'message' => "Please enter your current pin number"];
		}
		if ($this->cat_password != $oldPin){
			return ['success' => false, 'message' => "The old pin number is incorrect"];
		}
		if (!empty($_REQUEST['pin1'])){
			$newPin = $_REQUEST['pin1'];
		}else{
			return ['success' => false, 'message' => "Please enter the new pin number"];
		}
		if (!empty($_REQUEST['pin2'])){
			$confirmNewPin = $_REQUEST['pin2'];
		}else{
			return ['success' => false, 'message' => "Please enter the new pin number again"];
		}
		if ($newPin != $confirmNewPin){
			return ['success' => false, 'message' => "New PINs do not match. Please try again."];
		}
		$result = $this->getCatalogDriver()->updatePin($this, $oldPin, $newPin);
		if ($result['success']){
			$this->cat_password = $newPin;
			$this->password = $newPin;
			$this->update();
			$this->clearCache();
		}

		return $result;
	}

	function getRelatedPTypes($includeLinkedUsers = true){
		$relatedPTypes = array();
		$relatedPTypes[$this->patronType] = $this->patronType;
		if ($includeLinkedUsers){
			if ($this->getLinkedUserObjects() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUserObjects() as $user) {
					$relatedPTypes = array_merge($relatedPTypes, $user->getRelatedPTypes(false));
				}
			}
		}
		return $relatedPTypes;
	}

	function importListsFromIls(){
		return $this->getCatalogDriver()->importListsFromIls($this);
	}

	public function canMasquerade() {
		return $this->hasPermission(['Masquerade as any user', 'Masquerade as unrestricted patron types',
			'Masquerade as patrons with same home library', 'Masquerade as unrestricted patrons with same home library',
			'Masquerade as patrons with same home location', 'Masquerade as unrestricted patrons with same home location']);
	}

	/**
	 * @param mixed $materialsRequestReplyToAddress
	 */
	public function setMaterialsRequestReplyToAddress($materialsRequestReplyToAddress)
	{
		$this->materialsRequestReplyToAddress = $materialsRequestReplyToAddress;
	}

	/**
	 * @param mixed $materialsRequestEmailSignature
	 */
	public function setMaterialsRequestEmailSignature($materialsRequestEmailSignature)
	{
		$this->materialsRequestEmailSignature = $materialsRequestEmailSignature;
	}

	function setNumMaterialsRequests($val){
		$this->_numMaterialsRequests = $val;
	}

	function getNumMaterialsRequests(){
		$this->updateRuntimeInformation();
		return $this->_numMaterialsRequests;
	}

	function getNumRatings(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';

		$rating = new UserWorkReview();
		$rating->whereAdd("userId = {$this->id}");
		$rating->whereAdd('rating > 0'); // Some entries are just reviews (and therefore have a default rating of -1)
		return $rating->count();
	}

	function getReadingHistorySize(){
		if ($this->_readingHistorySize == null){
			if ($this->trackReadingHistory && $this->initialReadingHistoryLoaded){
				global $timer;
				require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $this->id;
				$readingHistoryDB->whereAdd('deleted = 0');
				$readingHistoryDB->groupBy('groupedWorkPermanentId');
				$this->_readingHistorySize = $readingHistoryDB->count();
				$timer->logTime("Updated reading history size");
			}else{
				$this->_readingHistorySize = 0;
			}
		}

		return $this->_readingHistorySize;
	}

	function getPatronUpdateForm()
	{
		if ($this->hasIlsConnection()){
			return $this->getCatalogDriver()->getPatronUpdateForm($this);
		}else{
			return null;
		}
	}

	/** @noinspection PhpUnused */
	function showMessagingSettings(){
		global $library;
		if ($library->showMessagingSettings) {
			if ($this->hasIlsConnection()) {
				return $this->getCatalogDriver()->showMessagingSettings();
			} else {
				return false;
			}
		}
	}

	function getMessages(){
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		$userMessage = new UserMessage();
		$userMessage->userId = $this->id;
		$userMessage->isDismissed = "0";
		$messages = [];
		$userMessage->find();
		while ($userMessage->fetch()){
			$messages[] = clone $userMessage;
		}
		return $messages;
	}

	function getILSMessages(){
		if ($this->hasIlsConnection()){
			return $this->getCatalogDriver()->getILSMessages($this);
		}else{
			return false;
		}
	}

	function disableLinkingDueToPasswordChange()
	{
		require_once ROOT_DIR . '/sys/Account/UserMessage.php';
		require_once ROOT_DIR . '/sys/Account/UserLink.php';

		$userLinks = new UserLink();
		$userLinks->linkedAccountId = $this->id;
		if ($userLinks->find()){
			$userMessage = new UserMessage();
			$userMessage->userId = $this->id;
			$userMessage->messageType = 'confirm_linked_accts';
			$userMessage->message = "Other accounts have linked to your account.  Do you want to continue allowing them to link to you?";
			$userMessage->action1Title = "Yes";
			$userMessage->action1 = "return AspenDiscovery.Account.enableAccountLinking()";
			$userMessage->action2Title = "No";
			$userMessage->action2 = "return AspenDiscovery.Account.stopAccountLinking()";
			$userMessage->messageLevel = 'warning';
			$userMessage->insert();
			while ($userLinks->fetch()){
				$userMessage = new UserMessage();
				$userMessage->userId = $userLinks->primaryAccountId;
				$userMessage->messageType = 'linked_acct_notify_pause_' . $this->id;
				$userMessage->messageLevel = 'info';
				$userMessage->message = "An account you are linking to changed their login. Account linking with them has been temporarily disabled.";
				$userMessage->insert();
				$userLinks->linkingDisabled = 1;
				$userLinks->update();
			}
		}
	}

	function getOverDriveOptions() {
		require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
		$overDriveDriver = new OverDriveDriver();
		return $overDriveDriver->getOptions($this);
	}

	function completeFinePayment(UserPayment $payment){
		$result = $this->getCatalogDriver()->completeFinePayment($this, $payment);
		if ($result['success']){
			$payment->completed = 1;
			$payment->update();
		}
		return $result;
	}

	private function fixFieldLengths()
	{
		if (strlen($this->lastname) > 100){
			$this->lastname = substr($this->lastname, 0, 100);
		}
		if (strlen($this->firstname) > 50){
			$this->firstname = substr($this->firstname, 0, 50);
		}
		if (strlen($this->displayName) > 60){
			$this->displayName = substr($this->displayName, 0, 60);
		}
	}

	function eligibleForHolds()
	{
		if (empty($this->getCatalogDriver())){
			return false;
		}
		return $this->getCatalogDriver()->patronEligibleForHolds($this);
	}

	function getShowAutoRenewSwitch()
	{
		if (empty($this->getCatalogDriver())){
			return false;
		}
		return $this->getCatalogDriver()->getShowAutoRenewSwitch($this);
	}

	function isAutoRenewalEnabledForUser(){
		if (empty($this->getCatalogDriver())){
			return false;
		}
		return $this->getCatalogDriver()->isAutoRenewalEnabledForUser($this);
	}

	function updateAutoRenewal($allowAutoRenewal){
		return $this->getCatalogDriver()->updateAutoRenewal($this, $allowAutoRenewal);
	}

	public function getNotInterestedTitles($sinceTime = 0){
		global $timer;
		$notInterestedTitles = [];
		require_once ROOT_DIR . '/sys/LocalEnrichment/NotInterested.php';
		$notInterested = new NotInterested();
		$notInterested->userId = $this->id;
		if ($sinceTime > 0){
			$notInterested->whereAdd("dateMarked >= $sinceTime" );
		}
		$notInterested->find();
		while ($notInterested->fetch()){
			$notInterestedTitles[$notInterested->groupedRecordPermanentId] = $notInterested->groupedRecordPermanentId;
		}
		$timer->logTime("Loaded titles the patron is not interested in");
		return $notInterestedTitles;
	}

	public function getAllIdsNotToSuggest(){
		$idsNotToSuggest = $this->getNotInterestedTitles();
		//Add everything the user has rated
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';
		$ratings = new UserWorkReview();
		$ratings->userId = $this->id;
		$ratings->find();
		while ($ratings->fetch()){
			$idsNotToSuggest[$ratings->groupedRecordPermanentId] = $ratings->groupedRecordPermanentId;
		}
		//Add everything in the user's reading history
		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->userId = $this->id;
		$readingHistoryEntry->selectAdd();
		$readingHistoryEntry->selectAdd('groupedWorkPermanentId');
		$readingHistoryEntry->groupBy('groupedWorkPermanentId');
		$readingHistoryEntry->find();
		while ($readingHistoryEntry->fetch()){
			if (!empty($readingHistoryEntry->groupedWorkPermanentId)) {
				$idsNotToSuggest[$readingHistoryEntry->groupedWorkPermanentId] = $readingHistoryEntry->groupedWorkPermanentId;
			}
		}

		return $idsNotToSuggest;
	}

	/** @noinspection PhpUnused */
	function getHomeLocationName(){
		return $this->getHomeLocation()->displayName;
	}

	function getHomeLocation(){
		$location = new Location();
		$location->locationId = $this->homeLocationId;
		if ($location->find(true)){
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

	function getHomeLocationCode(){
		return $this->getHomeLocation()->code;
	}

	function getPickupLocationCode(){
		//Always check if a preferred pickup location has been selected.  If not, use the home location
		if ($this->pickupLocationId > 0 && $this->pickupLocationId != $this->homeLocationId) {
			$pickupBranch = $this->pickupLocationId;
			$locationLookup = new Location();
			$locationLookup->locationId = $pickupBranch;
			//Make sure that the hold location is a valid pickup location just in case it's been hidden since
			if ($locationLookup->find(true) && $locationLookup->validHoldPickupBranch != 2) {
				$pickupBranch = $locationLookup->code;
			} else {
				$pickupBranch = $this->getHomeLocation()->code;
			}
		} else {
			$pickupBranch = $this->getHomeLocation()->code;
		}

		return $pickupBranch;
	}

	/**
	 * @param string $pickupBranch
	 * @return bool
	 */
	function validatePickupBranch(string &$pickupBranch): bool
	{
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
				if ($locationValid){
					$pickupBranch = $location->code;
				}
			} else {
				//Location is deleted
				$locationValid = false;
			}
		}
		return $locationValid;
	}

	public function hasEditableUsername()
	{
		if ($this->hasIlsConnection()) {
			$homeLibrary = $this->getHomeLibrary();
			if ($homeLibrary->allowUsernameUpdates) {
				return $this->getCatalogDriver()->hasEditableUsername();
			}
		}
		return false;
	}

	public function getEditableUsername()
	{
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->getEditableUsername($this);
		}else{
			return null;
		}
	}

	private function updateEditableUsername($username)
	{
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->updateEditableUsername($this, $username);
		}else{
			return [
				'success' => false,
				'message' => 'This user is not connected to an ILS'
			];
		}
	}

	public function logout(){
		if ($this->hasIlsConnection()) {
			$this->getCatalogDriver()->logout($this);
		}
		$this->lastLoginValidation = 0;
		$this->update();
	}

	public function treatVolumeHoldsAsItemHolds() {
		if ($this->hasIlsConnection()) {
			return $this->getCatalogDriver()->treatVolumeHoldsAsItemHolds();
		}
		return false;
	}

	public function getAdminActions(){
		require_once ROOT_DIR . '/sys/AdminSection.php';
		global $library;
		global $configArray;
		global $enabledModules;
		$sections = [];

		if (count($this->getRoles()) == 0){
			return $sections;
		}
		$sections['system_admin'] = new AdminSection('System Administration');
		$sections['system_admin']->addAction(new AdminAction('Modules', 'Enable and disable sections of Aspen Discovery.', '/Admin/Modules'), 'Administer Modules');
		$sections['system_admin']->addAction(new AdminAction('Administration Users', 'Define who should have administration privileges.', '/Admin/Administrators'), 'Administer Users');
		$sections['system_admin']->addAction(new AdminAction('Permissions', 'Define who what each role in the system can do.', '/Admin/Permissions'), 'Administer Permissions');
		$sections['system_admin']->addAction(new AdminAction('DB Maintenance', 'Update the database when new versions of Aspen Discovery are released.', '/Admin/DBMaintenance'), 'Run Database Maintenance');
		$sections['system_admin']->addAction(new AdminAction('Amazon SES Settings', 'Settings to allow Aspen Discovery to send emails via Amazon SES.', '/Admin/AmazonSesSettings'), 'Administer Amazon SES');
		$sections['system_admin']->addAction(new AdminAction('Send Grid Settings', 'Settings to allow Aspen Discovery to send emails via SendGrid.', '/Admin/SendGridSettings'), 'Administer SendGrid');
		$sections['system_admin']->addAction(new AdminAction('Variables', 'Variables set by the Aspen Discovery itself as part of background processes.', '/Admin/Variables'), 'Administer System Variables');
		$sections['system_admin']->addAction(new AdminAction('System Variables', 'Settings for Aspen Discovery that apply to all libraries on this installation.', '/Admin/SystemVariables'), 'Administer System Variables');

		$sections['system_reports'] = new AdminSection('System Reports');
		$sections['system_reports']->addAction(new AdminAction('Site Status', 'View Status of Aspen Discovery.', '/Admin/SiteStatus'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Usage Dashboard', 'Usage Report for Aspen Discovery.', '/Admin/UsageDashboard'), ['View Dashboards', 'View System Reports']);
		$sections['system_reports']->addAction(new AdminAction('API Usage Dashboard', 'API Usage Report for Aspen Discovery.', '/Admin/APIUsageDashboard'), ['View Dashboards', 'View System Reports']);
		$sections['system_reports']->addAction(new AdminAction('Usage By IP Address', 'Reports which IP addresses have used Aspen Discovery.', '/Admin/UsageByIP'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Nightly Index Log', 'Nightly indexing log for Aspen Discovery.  The nightly index updates all records if needed.', '/Admin/ReindexLog'), ['View System Reports', 'View Indexing Logs']);
		$sections['system_reports']->addAction(new AdminAction('Cron Log', 'View Cron Log. The cron process handles periodic cleanup tasks and updates reading history for users.', '/Admin/CronLog'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Performance Report', 'View Aspen Performance Report.', '/Admin/PerformanceReport'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('Error Log', 'View Aspen Error Log.', '/Admin/ErrorReport'), 'View System Reports');
		$sections['system_reports']->addAction(new AdminAction('PHP Information', 'Display configuration information for PHP on the server.', '/Admin/PHPInfo'), 'View System Reports');

		$sections['theme_and_layout'] = new AdminSection('Theme & Layout');
		$sections['theme_and_layout']->addAction(new AdminAction('Themes', 'Define colors, fonts, images etc used within Aspen Discovery.', '/Admin/Themes'), ['Administer All Themes', 'Administer Library Themes']);
		$sections['theme_and_layout']->addAction(new AdminAction('Layout Settings', 'Define basic information about how pages are displayed in Aspen Discovery.', '/Admin/LayoutSettings'), ['Administer All Layout Settings', 'Administer Library Layout Settings']);

		$sections['primary_configuration'] = new AdminSection('Primary Configuration');
		$librarySettingsAction = new AdminAction('Library Systems', 'Configure library settings.', '/Admin/Libraries');
		$locationSettingsAction = new AdminAction('Locations', 'Configure location settings.', '/Admin/Locations');
		$ipAddressesAction = new AdminAction('IP Addresses', 'Configure IP addresses for each location and configure rules to block access to Aspen Discovery.', '/Admin/IPAddresses');
		$administerHostAction = new AdminAction('Host Information', 'Allows configuration of domain names to point to different sections of Aspen Discovery', '/Admin/Hosting');
		if ($sections['primary_configuration']->addAction($librarySettingsAction, ['Administer All Libraries', 'Administer Home Library'])){
			$librarySettingsAction->addSubAction($locationSettingsAction, ['Administer All Locations', 'Administer Home Library Locations', 'Administer Home Location']);
			$librarySettingsAction->addSubAction($ipAddressesAction, 'Administer IP Addresses');
			$librarySettingsAction->addSubAction($administerHostAction, 'Administer Host Information');
		}else{
			$sections['primary_configuration']->addAction($locationSettingsAction, ['Administer All Locations', 'Administer Home Library Locations', 'Administer Home Location']);
			$sections['primary_configuration']->addAction($ipAddressesAction, 'Administer IP Addresses');
			$sections['primary_configuration']->addAction($administerHostAction, 'Administer Host Information');
		}
		$sections['primary_configuration']->addAction(new AdminAction('Block Patron Account Linking', 'Prevent accounts from linking to other accounts.', '/Admin/BlockPatronAccountLinks'), 'Block Patron Account Linking');
		$sections['primary_configuration']->addAction(new AdminAction('Patron Types', 'Modify Permissions and limits based on Patron Type.', '/Admin/PTypes'), 'Administer Patron Types');
		$sections['primary_configuration']->addAction(new AdminAction('Account Profiles', 'Define how account information is loaded from the ILS.', '/Admin/AccountProfiles'), 'Administer Account Profiles');

		//Materials Request if enabled
		if (MaterialsRequest::enableAspenMaterialsRequest()){
			if ($library->enableMaterialsRequest == 1) {
				$sections['materials_request'] = new AdminSection('Materials Requests');
				$sections['materials_request']->addAction(new AdminAction('Manage Requests', 'Manage Materials Requests from users.', '/MaterialsRequest/ManageRequests'), 'Manage Library Materials Requests');
				$sections['materials_request']->addAction(new AdminAction('Summary Report', 'A Summary Report of all requests that have been submitted.', '/MaterialsRequest/SummaryReport'), 'View Materials Requests Reports');
				$sections['materials_request']->addAction(new AdminAction('Report By User', 'A Report of all requests that have been submitted by users who submitted them.', '/MaterialsRequest/UserReport'), 'View Materials Requests Reports');
				$sections['materials_request']->addAction(new AdminAction('Manage Statuses', 'Define the statuses of Materials Requests for the library.', '/MaterialsRequest/ManageStatuses'), 'Administer Materials Requests');
			}
		}

		if (array_key_exists('Web Builder', $enabledModules)) {
			$sections['web_builder'] = new AdminSection('Web Builder');
			//$sections['web_builder']->addAction(new AdminAction('Menu', 'Define additional options that appear in the menu.', '/WebBuilder/Menus'), ['Administer All Menus', 'Administer Library Menus']);
			$sections['web_builder']->addAction(new AdminAction('Basic Pages', 'Create basic pages with a simple layout.', '/WebBuilder/BasicPages'), ['Administer All Basic Pages', 'Administer Library Basic Pages']);
			$sections['web_builder']->addAction(new AdminAction('Custom Pages', 'Create custom pages with a more complex cell based layout.', '/WebBuilder/PortalPages'), ['Administer All Custom Pages', 'Administer Library Custom Pages']);
			$sections['web_builder']->addAction(new AdminAction('Custom Forms', 'Create custom forms within Aspen Discovery for patrons to fill out.', '/WebBuilder/CustomForms'), ['Administer All Custom Forms', 'Administer Library Custom Forms']);
			$sections['web_builder']->addAction(new AdminAction('Web Resources', 'Add resources within Aspen Discovery that the library provides.', '/WebBuilder/WebResources'), ['Administer All Web Resources', 'Administer Library Web Resources']);
			$sections['web_builder']->addAction(new AdminAction('Staff Members', 'Add staff members to create a staff directory.', '/WebBuilder/StaffMembers'), ['Administer All Staff Members', 'Administer Library Staff Members']);
			$sections['web_builder']->addAction(new AdminAction('Images', 'Add images to Aspen Discovery.', '/WebBuilder/Images'), ['Administer All Web Content']);
			$sections['web_builder']->addAction(new AdminAction('PDFs', 'Add PDFs to Aspen Discovery.', '/WebBuilder/PDFs'), ['Administer All Web Content']);
			//$sections['web_builder']->addAction(new AdminAction('Videos', 'Add Videos to Aspen Discovery.', '/WebBuilder/Videos'), ['Administer All Web Content']);
			$sections['web_builder']->addAction(new AdminAction('Audiences', 'Define Audiences to categorize content within Aspen Discovery.', '/WebBuilder/Audiences'), ['Administer All Web Categories']);
			$sections['web_builder']->addAction(new AdminAction('Categories', 'Define Categories to categorize content within Aspen Discovery.', '/WebBuilder/Categories'), ['Administer All Web Categories']);
		}

		$sections['translations'] = new AdminSection('Languages and Translations');
		$sections['translations']->addAction(new AdminAction('Languages', 'Define which languages are available within Aspen Discovery.', '/Translation/Languages'), 'Administer Languages');
		$sections['translations']->addAction(new AdminAction('Translations', 'Translate the user interface of Aspen Discovery.', '/Translation/Translations'), 'Translate Aspen');

		$sections['cataloging'] = new AdminSection('Catalog / Grouped Works');
		$groupedWorkAction = new AdminAction('Grouped Work Display', 'Define information about what is displayed for Grouped Works in search results and full record displays.', '/Admin/GroupedWorkDisplay');
		$groupedWorkAction->addSubAction(new AdminAction('Grouped Work Facets', 'Define information about what facets are displayed for grouped works in search results and Advanced Search.', '/Admin/GroupedWorkFacets'), ['Administer All Grouped Work Facets', 'Administer Library Grouped Work Facets']);
		$sections['cataloging']->addAction($groupedWorkAction, ['Administer All Grouped Work Display Settings', 'Administer Library Grouped Work Display Settings']);
		$sections['cataloging']->addAction(new AdminAction('Manual Grouping Authorities', 'View a list of all title author/authorities that have been added to Aspen to merge works.', '/Admin/AlternateTitles'), 'Manually Group and Ungroup Works');
		$sections['cataloging']->addAction(new AdminAction('Author Authorities', 'Create and edit authorities for authors.', '/Admin/AuthorAuthorities'), 'Manually Group and Ungroup Works');
		$sections['cataloging']->addAction(new AdminAction('Records To Not Group', 'Lists records that should not be grouped.', '/Admin/NonGroupedRecords'), 'Manually Group and Ungroup Works');
		//$sections['cataloging']->addAction(new AdminAction('Print Barcodes', 'Lists records that should not be grouped.', '/Admin/PrintBarcodes'), 'Print Barcodes');

		$sections['local_enrichment'] = new AdminSection('Local Catalog Enrichment');
		$browseCategoryGroupsAction = new AdminAction('Browse Category Groups', 'Define information about what is displayed for Grouped Works in search results and full record displays.', '/Admin/BrowseCategoryGroups');
		$browseCategoryGroupsAction->addSubAction(new AdminAction('Browse Categories', 'Define browse categories shown on the library home page.', '/Admin/BrowseCategories'), ['Administer All Browse Categories', 'Administer Library Browse Categories']);
		$sections['local_enrichment']->addAction($browseCategoryGroupsAction, ['Administer All Browse Categories', 'Administer Library Browse Categories']);
		$sections['local_enrichment']->addAction(new AdminAction('Collection Spotlights', 'Define basic information about how pages are displayed in Aspen Discovery.', '/Admin/CollectionSpotlights'), ['Administer All Collection Spotlights', 'Administer Library Collection Spotlights']);
		$sections['local_enrichment']->addAction(new AdminAction('JavaScript Snippets', 'JavaScript Snippets to be added to the site when pages are rendered.', '/Admin/JavaScriptSnippets'), ['Administer All JavaScript Snippets', 'Administer Library JavaScript Snippets']);
		$sections['local_enrichment']->addAction(new AdminAction('Placards', 'Placards allow you to promote services that do not have MARC records or APIs for inclusion in the catalog.', '/Admin/Placards'), ['Administer All Placards', 'Administer Library Placards', 'Edit Library Placards']);
		$sections['local_enrichment']->addAction(new AdminAction('System Messages', 'System Messages allow you to display messages to your patrons in specific locations.', '/Admin/SystemMessages'), ['Administer All System Messages', 'Administer Library System Messages']);

		$sections['third_party_enrichment'] = new AdminSection('Third Party Enrichment');
		$sections['third_party_enrichment']->addAction(new AdminAction('Accelerated Reader Settings', 'Define settings to load Accelerated Reader information directly from Renaissance Learning.', '/Enrichment/ARSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Coce Server Settings', 'Define settings to load covers from a Coce server.', '/Enrichment/CoceServerSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('ContentCafe Settings', 'Define settings for ContentCafe integration.', '/Enrichment/ContentCafeSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('DP.LA Settings', 'Define settings for DP.LA integration.', '/Enrichment/DPLASettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Google API Settings', 'Define settings for integrating Google APIs within Aspen Discovery.', '/Enrichment/GoogleApiSettings'), 'Administer Third Party Enrichment API Keys');
		$nytSettingsAction = new AdminAction('New York Times Settings', 'Define settings for integrating New York Times Content within Aspen Discovery.', '/Enrichment/NewYorkTimesSettings');
		$nytListsAction = new AdminAction('New York Times Lists', 'View Lists from the New York Times and manually refresh content.', '/Enrichment/NYTLists');
		if ($sections['third_party_enrichment']->addAction($nytSettingsAction, 'Administer Third Party Enrichment API Keys')){
			$nytSettingsAction->addSubAction($nytListsAction, 'View New York Times Lists');
		}else{
			$sections['third_party_enrichment']->addAction($nytListsAction, 'View New York Times Lists');
		}
		$sections['third_party_enrichment']->addAction(new AdminAction('Novelist Settings', 'Define settings for integrating Novelist within Aspen Discovery.', '/Enrichment/NovelistSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('OMDB Settings', 'Define settings for integrating OMDB within Aspen Discovery.', '/Enrichment/OMDBSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Quipu eCARD Settings', 'Define settings for integrating Quipu eCARD within Aspen Discovery.', '/Enrichment/QuipuECardSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('reCAPTCHA Settings', 'Define settings for using reCAPTCHA within Aspen Discovery.', '/Enrichment/RecaptchaSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Rosen LevelUP Settings', 'Define settings for allowing students and parents to register for Rosen LevelUP.', '/Rosen/RosenLevelUPSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Syndetics Settings', 'Define settings for Syndetics integration.', '/Enrichment/SyndeticsSettings'), 'Administer Third Party Enrichment API Keys');
		$sections['third_party_enrichment']->addAction(new AdminAction('Wikipedia Integration', 'Modify which Wikipedia content is displayed for authors.', '/Admin/AuthorEnrichment'), 'Administer Wikipedia Integration');

		$sections['ecommerce'] = new AdminSection('eCommerce');
		$sections['ecommerce']->addAction(new AdminAction('eCommerce Report', 'View all payments initialted and completed within the sytem', '/Admin/eCommerceReport'), 'View eCommerce Reports');
		$sections['ecommerce']->addAction(new AdminAction('Comprise Settings', 'Define Settings for Comprise SMARTPAY.', '/Admin/CompriseSettings'), 'Administer Comprise');
		$sections['ecommerce']->addAction(new AdminAction('FIS WorldPay Settings', 'Define Settings for FIS WorldPay.', '/Admin/WorldPaySettings'), 'Administer WorldPay');
		$sections['ecommerce']->addAction(new AdminAction('PayPal Settings', 'Define Settings for PayPal.', '/Admin/PayPalSettings'), 'Administer PayPal');
		$sections['ecommerce']->addAction(new AdminAction('ProPay Settings', 'Define Settings for ProPay.', '/Admin/ProPaySettings'), 'Administer ProPay');

		$sections['ils_integration'] = new AdminSection('ILS Integration');
		$indexingProfileAction = new AdminAction('Indexing Profiles', 'Define how records from the ILS are loaded into Aspen Discovery.', '/ILS/IndexingProfiles');
		$translationMapsAction = new AdminAction('Translation Maps', 'Define how field values are mapped between the ILS and Aspen Discovery.', '/ILS/TranslationMaps');
		if ($sections['ils_integration']->addAction($indexingProfileAction, 'Administer Indexing Profiles')){
			$indexingProfileAction->addSubAction($translationMapsAction, 'Administer Translation Maps');
		}else{
			$sections['ils_integration']->addAction($translationMapsAction, 'Administer Translation Maps');
		}
		$sections['ils_integration']->addAction(new AdminAction('Indexing Log', 'View the indexing log for ILS records.', '/ILS/IndexingLog'), 'View Indexing Logs');
		$sections['ils_integration']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for ILS integration.', '/ILS/Dashboard'), ['View Dashboards', 'View System Reports']);

		$sections['circulation_reports'] = new AdminSection('Circulation Reports');
		$sections['circulation_reports']->addAction(new AdminAction('Holds Report', 'View a report of holds to be pulled from the shelf for patrons.', '/Report/HoldsReport'), ['View Location Holds Reports', 'View All Holds Reports']);
		$sections['circulation_reports']->addAction(new AdminAction('Student Barcodes', 'View/print a report of all barcodes for a class.', '/Report/StudentBarcodes'), ['View Location Student Reports', 'View All Student Reports']);
		$sections['circulation_reports']->addAction(new AdminAction('Student Checkout Report', 'View a report of all checkouts for a given class with filtering to only show overdue items and lost items.', '/Report/StudentReport'), ['View Location Student Reports', 'View All Student Reports']);

		if (array_key_exists('Axis 360', $enabledModules)) {
			$sections['axis360'] = new AdminSection('Axis 360');
			$axis360SettingsAction = new AdminAction('Settings', 'Define connection information between Axis 360 and Aspen Discovery.', '/Axis360/Settings');
			$axis360ScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/Axis360/Scopes');
			if ($sections['axis360']->addAction($axis360SettingsAction, 'Administer Axis 360')) {
				$axis360SettingsAction->addSubAction($axis360ScopesAction, 'Administer Axis 360');
			} else {
				$sections['axis360']->addAction($axis360ScopesAction, 'Administer Axis 360');
			}
			$sections['axis360']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Axis 360.', '/Axis360/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['axis360']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Axis 360 integration.', '/Axis360/Dashboard'), ['View Dashboards', 'View System Reports']);
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
			$sections['cloud_library']->addAction(new AdminAction('Indexing Log', 'View the indexing log for cloudLibrary.', '/CloudLibrary/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['cloud_library']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for cloudLibrary integration.', '/CloudLibrary/Dashboard'), ['View Dashboards', 'View System Reports']);
		}

		if (array_key_exists('EBSCO EDS', $enabledModules)) {
			$sections['ebsco'] = new AdminSection('EBSCO');
			$sections['ebsco']->addAction(new AdminAction('Settings', 'Define connection information between EBSCO EDS and Aspen Discovery.', '/EBSCO/EDSSettings'), 'Administer EBSCO EDS');
			$sections['ebsco']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for EBSCO EDS integration.', '/EBSCO/EDSDashboard'), ['View Dashboards', 'View System Reports']);
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
			$sections['hoopla']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Hoopla.', '/Hoopla/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['hoopla']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Hoopla integration.', '/Hoopla/Dashboard'), ['View Dashboards', 'View System Reports']);
		}

		if (array_key_exists('OverDrive', $enabledModules)) {
			$sections['overdrive'] = new AdminSection('OverDrive');
			$overDriveSettingsAction = new AdminAction('Settings', 'Define connection information between OverDrive and Aspen Discovery.', '/OverDrive/Settings');
			$overDriveScopesAction = new AdminAction('Scopes', 'Define which records are loaded for each library and location.', '/OverDrive/Scopes');
			if ($sections['overdrive']->addAction($overDriveSettingsAction, 'Administer OverDrive')) {
				$overDriveSettingsAction->addSubAction($overDriveScopesAction, 'Administer OverDrive');
			} else {
				$sections['overdrive']->addAction($overDriveScopesAction, 'Administer OverDrive');
			}
			$sections['overdrive']->addAction(new AdminAction('Indexing Log', 'View the indexing log for OverDrive.', '/OverDrive/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['overdrive']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for OverDrive integration.', '/OverDrive/Dashboard'), ['View Dashboards', 'View System Reports']);
			$sections['overdrive']->addAction(new AdminAction('API Information', 'View API information for OverDrive integration to test connections.', '/OverDrive/APIData'), 'View OverDrive Test Interface');
			$sections['overdrive']->addAction(new AdminAction('Aspen Information', 'View information stored within Aspen about an OverDrive product.', '/OverDrive/AspenData'), 'View OverDrive Test Interface');
		}

		if (array_key_exists('RBdigital', $enabledModules)) {
			$sections['rbdigital'] = new AdminSection('RBdigital');
			$sections['rbdigital']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for RBdigital integration.', '/RBdigital/Dashboard'), ['View Dashboards', 'View System Reports']);
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
			$sections['side_loads']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Side Loads.', '/SideLoads/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['side_loads']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Side Loads integration.', '/SideLoads/Dashboard'), ['View Dashboards', 'View System Reports']);
		}

		if (array_key_exists('Open Archives', $enabledModules)){
			$sections['open_archives'] = new AdminSection('Open Archives');
			$sections['open_archives']->addAction(new AdminAction('Collections', 'Define collections to be loaded into Aspen Discovery.', '/OpenArchives/Collections'), 'Administer Open Archives');
			$sections['open_archives']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Open Archives.', '/OpenArchives/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['open_archives']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for Open Archives integration.', '/OpenArchives/Dashboard'), ['View Dashboards', 'View System Reports']);
		}

		if (array_key_exists('Events', $enabledModules)){
			$sections['events'] = new AdminSection('Events');
			$sections['events']->addAction(new AdminAction('Library Market - Calendar Settings', 'Define collections to be loaded into Aspen Discovery.', '/Events/LMLibraryCalendarSettings'), 'Administer Library Calendar Settings');
			$sections['events']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Events.', '/Events/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
		}

		if (array_key_exists('Web Indexer', $enabledModules)){
			$sections['web_indexer'] = new AdminSection('Website Indexing');
			$sections['web_indexer']->addAction(new AdminAction('Settings', 'Define settings for indexing websites within Aspen Discovery.', '/Websites/Settings'), 'Administer Website Indexing Settings');
			$sections['web_indexer']->addAction(new AdminAction('Indexing Log', 'View the indexing log for Websites.', '/Websites/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['web_indexer']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for indexed websites.', '/Websites/Dashboard'), ['View Dashboards', 'View System Reports']);
		}

		if (array_key_exists('User Lists', $enabledModules)){
			$sections['user_lists'] = new AdminSection('User Lists');
			$sections['user_lists']->addAction(new AdminAction('Settings', 'Define settings for indexing user lists within Aspen Discovery.', '/UserLists/Settings'), 'Administer List Indexing Settings');
			$sections['user_lists']->addAction(new AdminAction('Indexing Log', 'View the indexing log for User Lists.', '/UserLists/IndexingLog'), ['View System Reports', 'View Indexing Logs']);
			$sections['user_lists']->addAction(new AdminAction('NYT Update Log', 'View the updates log for New York Times Lists.', '/UserLists/NYTUpdatesLog'), ['View System Reports', 'View Indexing Logs']);
			//$sections['user_lists']->addAction(new AdminAction('Dashboard', 'View the usage dashboard for indexed User Lists.', '/UserLists/Dashboard'), ['View Dashboards', 'View System Reports']);
		}

		$sections['aspen_help'] = new AdminSection('Aspen Discovery Help');
		$sections['aspen_help']->addAction(new AdminAction('Help Manual', 'View Help Manual for Aspen Discovery.', '/Admin/HelpManual?page=table_of_contents'), true);
		$sections['aspen_help']->addAction(new AdminAction('Release Notes', 'View release notes for Aspen Discovery which contain information about new functionality and fixes for each release.', '/Admin/ReleaseNotes'), true);
		$showSubmitTicket = false;
		try {
			require_once ROOT_DIR . '/sys/SystemVariables.php';
			$systemVariables = new SystemVariables();
			if ($systemVariables->find(true) && !empty($systemVariables->ticketEmail)) {
				$showSubmitTicket = true;
			}
		}catch (Exception $e) {
			//This happens before the table is setup
		}
		if ($showSubmitTicket) {
			$sections['aspen_help']->addAction(new AdminAction('Submit Ticket', 'Submit a support ticket for assistance with Aspen Discovery.', '/Admin/SubmitTicket'), 'Submit Ticket');
		}

		return $sections;
	}

	public function getPermissions($isGuidingUser = false){
		if ($this->_permissions == null){
			$this->_permissions = [];
			$roles = $this->getRoles();
			foreach ($roles as $role){
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
	public function filterPermissionsForMasquerade($userPermissions, $guidingUserPermissions){
		$validPermissions = [];
		foreach ($userPermissions as $permissionName){
			if (in_array($permissionName, $guidingUserPermissions)){
				$validPermissions[] = $permissionName;
			}else{
				//Check to see if the guiding user has a permission that is more inclusive than the user we are masquerading as
				if (strpos($permissionName, 'Administer Library') === 0){
					$tmpPermissionName = str_replace('Administer Library', 'Administer All', $permissionName);
					if (in_array($tmpPermissionName, $guidingUserPermissions)){
						$validPermissions[] = $permissionName;
					}
				}elseif ($permissionName == 'Administer Home Location' && (in_array('Administer Home Library Locations', $guidingUserPermissions) ||in_array('Administer All Locations', $guidingUserPermissions) )){
					$validPermissions[] = $permissionName;
				}elseif ($permissionName == 'Administer Home Library Locations' && (in_array('Administer All Locations', $guidingUserPermissions) )){
					$validPermissions[] = $permissionName;
				}elseif ($permissionName == 'Administer Home Library' && (in_array('Administer All Libraries', $guidingUserPermissions) )){
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
	public function hasPermission($allowablePermissions){
		$permissions = $this->getPermissions();
		if (is_array($allowablePermissions)){
			foreach ($allowablePermissions as $allowablePermission){
				if (in_array($allowablePermission, $permissions)){
					return true;
				}
			}
		}else{
			if (in_array($allowablePermissions, $permissions)){
				return true;
			}
		}
		return false;
	}

	public function getAccountSummary()
	{
		if ($this->hasIlsConnection()){
			return $this->getCatalogDriver()->getAccountSummary($this);
		}else {
			return [];
		}
	}

	public function getCachedAccountSummary(string $source)
	{
		//Check to see if we have cached summary information
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $this->id;
		$summary->source = $source;
		$existingId = null;
		if ($summary->find(true)){
			$existingId = $summary->id;
			if (($summary->lastLoaded < (time() - 15 * 60)) || isset($_REQUEST['refreshCheckouts']) || isset($_REQUEST['refreshHolds']) || isset($_REQUEST['refreshSummary'])){
				$summary = null;
			}
		}else{
			$summary->insert();
			$existingId = $summary->id;
			$summary = null;
		}
		return [$existingId, $summary];
	}

	public function clearCachedAccountSummaryForSource(string $source){
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $this->id;
		$summary->source = $source;
		$summary->delete(true);
	}

	public function forceReloadOfCheckouts(){
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$checkout = new Checkout();
		$checkout->userId = $this->id;
		$checkout->delete(true);

		$this->checkoutInfoLastLoaded = 0;
		$this->update();
	}

	public function forceReloadOfHolds(){
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$hold = new Hold();
		$hold->userId = $this->id;
		$hold->delete(true);

		$this->holdInfoLastLoaded = 0;
		$this->update();
	}

	protected function clearRuntimeDataVariables(){
		if ($this->_accountProfile != null){
			$this->_accountProfile->__destruct();
			$this->_accountProfile = null;
		}
		parent::clearRuntimeDataVariables();
	}

	public function getFormattedHoldInfoLastLoaded(){
		if ($this->holdInfoLastLoaded == 0){
			return translate(['text' => "Loading...", 'isPublicFacing'=>true]);
		}else{
			return strftime("%I:%M %p", $this->holdInfoLastLoaded);
		}
	}

	public function getFormattedCheckoutInfoLastLoaded(){
		if ($this->checkoutInfoLastLoaded == 0){
			return translate(['text' => "Loading...", 'isPublicFacing'=>true]);
		}else{
			return strftime("%I:%M %p", $this->checkoutInfoLastLoaded);
		}
	}

	public function getDisplayName(){
		if (empty($this->displayName)) {
			if ($this->firstname == '') {
				$this->displayName = $this->lastname;
			} else {
				// #PK-979 Make display name configurable firstname, last initial, vs first initial last name
				$homeLibrary = $this->getHomeLibrary();
				if ($homeLibrary == null || ($homeLibrary->patronNameDisplayStyle == 'firstinitial_lastname')) {
					$this->displayName = substr($this->firstname, 0, 1) . '. ' . $this->lastname;
				} else {
					$this->displayName = $this->firstname . ' ' . substr($this->lastname, 0, 1) . '.';
				}
			}
			$this->update();
		}
		return $this->displayName;
	}
}

function modifiedEmpty($var) {
	// specified values of zero will not be considered empty
	return empty($var) && $var !== 0 && $var !== '0';
}