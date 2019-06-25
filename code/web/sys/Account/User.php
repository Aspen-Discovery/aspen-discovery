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
	public $alt_username;                    // An alternate username used by patrons to login.
	public $cat_username;                    // string(50)
	public $cat_password;                    // string(50)
	public $patronType;
	public $created;                         // datetime(19)  not_null binary
	public $homeLocationId;					 // int(11)
	public $myLocation1Id;					 // int(11)
	public $myLocation2Id;					 // int(11)
	public $trackReadingHistory; 			 // tinyint
	public $initialReadingHistoryLoaded;
	public $bypassAutoLogout;        //tinyint
	public $disableRecommendations;     //tinyint
	public $disableCoverArt;     //tinyint
	public $overdriveEmail;
	public $promptForOverdriveEmail;
	public $hooplaCheckOutConfirmation;
	public $preferredLibraryInterface;
	public $noPromptForUserReviews; //tinyint(1)
    public $rbdigitalId;
    public $rbdigitalLastAccountCheck;

	private $roles;
	private $masqueradingRoles;
	private $masqueradeLevel;

	public $interfaceLanguage;
	public $searchPreferenceLanguage;

	/** @var User $parentUser */
	private $parentUser;
	/** @var User[] $linkedUsers */
	private $linkedUsers;
	private $viewers;

	//Data that we load, but don't store in the User table
	public $_fullname;
	public $_dateOfBirth;
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
	public $_homeLibraryName; //Only populated as part of loading administrators
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
	private $_numHoldsRequestedOverDrive = 0;
    private $_numCheckedOutRbdigital = 0;
    private $_numHoldsRbdigital = 0;
    private $_numHoldsAvailableRbdigital = 0;
    private $_numHoldsRequestedRbdigital = 0;
	private $_numCheckedOutHoopla = 0;
	public $_numBookings;
	public $_notices;
	public $_noticePreferenceLabel;
	private $_numMaterialsRequests = 0;
	private $_readingHistorySize = 0;

	private $data = array();

	// CarlX Option
	public $_emailReceiptFlag;
	public $_availableHoldNotice;
	public $_comingDueNotice;
	public $_phoneType;

	function getNumericColumnNames()
	{
		return ['trackReadingHistory'];
	}

	function getLists() {
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';

		$lists = array();

		$escapedId = $this->escape($this->id);
		$sql = "SELECT user_list.* FROM user_list " .
							 "WHERE user_list.user_id = '$escapedId' " .
							 "ORDER BY user_list.title";
		$list = new UserList();
		$list->query($sql);
		if ($list->N) {
			while ($list->fetch()) {
				$lists[] = clone($list);
			}
		}

		return $lists;
	}

	private $catalogDriver;
	/**
	 * Get a connection to the catalog for the user
	 *
	 * @return CatalogConnection
	 */
	function getCatalogDriver(){
		if ($this->catalogDriver == null){
			//Based off the source of the user, get the AccountProfile
			$accountProfile = $this->getAccountProfile();
			if ($accountProfile){
				$catalogDriver = $accountProfile->driver;
				if (!empty($catalogDriver)){
                    $this->catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriver, $accountProfile);
                }
			}
		}
		return $this->catalogDriver;
	}

	/** @var AccountProfile */
	private $accountProfile;

	/**
	 * @return AccountProfile
	 */
	function getAccountProfile(){
		if ($this->accountProfile != null){
			return $this->accountProfile;
		}
		require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
		$accountProfile = new AccountProfile();
		$accountProfile->name = $this->source;
		if ($accountProfile->find(true)){
			$this->accountProfile = $accountProfile;
		}else{
			$this->accountProfile = null;
		}
		return $this->accountProfile;
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
			return $this->data[$name];
		}
	}

	function __set($name, $value){
		if ($name == 'roles') {
			$this->roles = $value;
			//Update the database, first remove existing values
			$this->saveRoles();
		}else{
			$this->data[$name] = $value;
		}
	}

	function getRoles($isGuidingUser = false){
		if (is_null($this->roles)){
			$this->roles = array();
			//Load roles for the user from the user
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$canUseTestRoles = false;
			if ($this->id){
				$escapedId = $this->escape($this->id);
				$role->query("SELECT roles.* FROM roles INNER JOIN user_roles ON roles.roleId = user_roles.roleId WHERE userId = " . $escapedId . " ORDER BY name");
				while ($role->fetch()){
					$this->roles[$role->roleId] = $role->name;
					if ($role->name == 'userAdmin'){
						$canUseTestRoles = true;
					}
				}
			}

			//Setup masquerading as different users
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
						$this->roles[$role->roleId] = $role->name;
					}
				}
			}
		}


		$masqueradeMode = UserAccount::isUserMasquerading();
		if ($masqueradeMode && !$isGuidingUser) {
			if (is_null($this->masqueradingRoles)) {
				global /** @var User $guidingUser */
				$guidingUser;
				$guidingUserRoles = $guidingUser->getRoles(true);
				if (in_array('opacAdmin', $guidingUserRoles)) {
					$this->masqueradingRoles = $this->roles;
				} else {
					$this->masqueradingRoles = array_intersect($this->roles, $guidingUserRoles);
				}
			}
			return $this->masqueradingRoles;
		}
		return $this->roles;
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

	function setStaffSettings(){
		require_once ROOT_DIR . '/sys/Account/UserStaffSettings.php';
		$staffSettings = new UserStaffSettings();
		$staffSettings->userId =  $this->id;
		$doUpdate = $staffSettings->find(true);
		$staffSettings->materialsRequestReplyToAddress = $this->materialsRequestReplyToAddress;
		$staffSettings->materialsRequestEmailSignature = $this->materialsRequestEmailSignature;
		if ($doUpdate) {
			$staffSettings->update();
		} else {
			$staffSettings->insert();
		}
	}

	function getBarcode(){
		global $configArray;
		//TODO: Check the login configuration for the driver
		if ($configArray['Catalog']['barcodeProperty'] == 'cat_username'){
			return trim($this->cat_username);
		}else{
			return trim($this->cat_password);
		}
	}

	function getPasswordOrPin(){
        global $configArray;
        //TODO: Check the login configuration for the driver
        if ($configArray['Catalog']['barcodeProperty'] == 'cat_username'){
            return trim($this->cat_password);
        }else{
            return trim($this->cat_username);
        }
    }

	function saveRoles(){
		if (isset($this->id) && isset($this->roles) && is_array($this->roles)){
			require_once ROOT_DIR . '/sys/Administration/Role.php';
			$role = new Role();
			$escapedId = $this->escape($this->id);
			$role->query("DELETE FROM user_roles WHERE userId = " . $escapedId);
			//Now add the new values.
			if (count($this->roles) > 0){
				$values = array();
				foreach ($this->roles as $roleId => $roleName){
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
			/** @var Memcache $memCache */
			global $memCache;
			global $serverName;
			global $logger;
			if ($this->id && $library->allowLinkedAccounts){
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->primaryAccountId = $this->id;
				$userLink->find();
				while ($userLink->fetch()){
					if (!$this->isBlockedAccount($userLink->linkedAccountId)) {
						$linkedUser = new User();
						$linkedUser->id = $userLink->linkedAccountId;
						if ($linkedUser->find(true)){
							/** @var User $userData */
							$userData = $memCache->get("user_{$serverName}_{$linkedUser->id}");
							if ($userData === false || isset($_REQUEST['reload'])){
								//Load full information from the catalog
                                $linkedUser = UserAccount::validateAccount($linkedUser->cat_username, $linkedUser->cat_password, $linkedUser->source, $this);
                            }else{
								$logger->log("Found cached linked user {$userData->id}", Logger::LOG_DEBUG);
								$linkedUser = $userData;
							}
							if ($linkedUser && !($linkedUser instanceof AspenError)) {
								$this->linkedUsers[] = clone($linkedUser);
							}
						}
					}
				}
			}
		}
		return $this->linkedUsers;
	}

	private $linkedUserObjects;
	function getLinkedUserObjects(){
		if (is_null($this->linkedUserObjects)){
			$this->linkedUserObjects = array();
			/* var Library $library */
			global $library;
			if ($this->id && $library->allowLinkedAccounts){
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->primaryAccountId = $this->id;
				$userLink->find();
				while ($userLink->fetch()){
					if (!$this->isBlockedAccount($userLink->linkedAccountId)) {
						$linkedUser = new User();
						$linkedUser->id = $userLink->linkedAccountId;
						if ($linkedUser->find(true)){
							/** @var User $userData */
							$this->linkedUserObjects[] = clone($linkedUser);
						}
					}
				}
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
	function getRelatedEcontentUsers($source){
		$users = array();
		if ($this->isValidForEContentSource($source)){
            $users[$this->cat_username . ':' . $this->cat_password] = $this;
		}
		foreach ($this->getLinkedUsers() as $linkedUser){
			if ($linkedUser->isValidForEContentSource($source)){
				if (!array_key_exists($linkedUser->cat_username . ':' . $linkedUser->cat_password, $users)){
                    $users[$linkedUser->cat_username . ':' . $linkedUser->cat_password] = $linkedUser;
				}
			}
		}

		return $users;
	}

	function isValidForEContentSource($source){
		if ($this->parentUser == null || ($this->getBarcode() != $this->parentUser->getBarcode())){
			$userHomeLibrary = Library::getPatronHomeLibrary($this);
			if ($userHomeLibrary){
			    if ($source == 'overdrive'){
			        return $userHomeLibrary->enableOverdriveCollection;
                }elseif ($source == 'hoopla'){
			        return $userHomeLibrary->hooplaLibraryID > 0;
                }elseif ($source == 'rbdigital'){
				    require_once ROOT_DIR . '/sys/Rbdigital/RbdigitalSetting.php';
				    try{
					    $rbdigitalSettings = new RbdigitalSetting();
					    $rbdigitalSettings->find();
					    return $rbdigitalSettings->N > 0;
				    }catch (Exception $e){
				    	return false;
				    }
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
	function getViewers(){
		if (is_null($this->viewers)){
			$this->viewers = array();
			/* var Library $library */
			global $library;
			if ($this->id && $library->allowLinkedAccounts){
				require_once ROOT_DIR . '/sys/Account/UserLink.php';
				$userLink = new UserLink();
				$userLink->linkedAccountId = $this->id;
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
			/** @var User $existingUser */
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
        if (empty($this->created)){
            $this->created = date('Y-m-d');
        }
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
		if (!isset($this->myLocation1Id)) $this->myLocation1Id = 0;
		if (!isset($this->myLocation2Id)) $this->myLocation2Id = 0;
		if (!isset($this->bypassAutoLogout)) $this->bypassAutoLogout = 0;

		if (empty($this->created)){
            $this->created = date('Y-m-d');
        }
		parent::insert();
		$this->saveRoles();
		$this->clearCache();
	}

	function hasRole($roleName){
		$myRoles = $this->__get('roles');
		return in_array($roleName, $myRoles);
	}

    static function getObjectStructure(){
		//Lookup available roles in the system
		require_once ROOT_DIR . '/sys/Administration/Role.php';
		$roleList = Role::getLookup();

		$structure = array(
				'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Administrator Id', 'description'=>'The unique id of the in the system'),
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

	function getFilters(){
		require_once ROOT_DIR . '/sys/Administration/Role.php';
		$roleList = Role::getLookup();
		$roleList[-1] = 'Any Role';
		return array(
            array('filter'=>'role', 'type'=>'enum', 'values'=>$roleList, 'label'=>'Role'),
            array('filter'=>'cat_password', 'type'=>'text', 'label'=>'Login'),
            array('filter'=>'cat_username', 'type'=>'text', 'label'=>'Name'),
		);
	}

	function hasRatings(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';

		$rating = new UserWorkReview();
		$rating->whereAdd("userId = {$this->id}");
		$rating->whereAdd('rating > 0'); // Some entries are just reviews (and therefore have a default rating of -1)
		$rating->find();
		if ($rating->N > 0){
			return true;
		}else{
			return false;
		}
	}

	private $_runtimeInfoUpdated = false;
	private $_hasCatalogConnection = null;
	function updateRuntimeInformation(){
		if (!$this->_runtimeInfoUpdated) {
			if ($this->getCatalogDriver()) {
				$this->getCatalogDriver()->updateUserWithAdditionalRuntimeInformation($this);
				$this->_hasCatalogConnection = true;
			} else {
                $this->_hasCatalogConnection = false;
			}
			$this->_runtimeInfoUpdated = true;
		}
	}

	function hasCatalogConnection(){
	    if ($this->_hasCatalogConnection == null) {
            if ($this->getCatalogDriver()) {
                $this->_hasCatalogConnection = true;
            } else {
                $this->_hasCatalogConnection = false;
            }
        }
        return $this->_hasCatalogConnection;
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

	function updateUserPreferences(){
		// Validate that the input data is correct
		if (isset($_POST['myLocation1']) && !is_array($_POST['myLocation1']) && preg_match('/^\d{1,3}$/', $_POST['myLocation1']) == 0){
			AspenError::raiseError('The 1st location had an incorrect format.');
		}
		if (isset($_POST['myLocation2']) && !is_array($_POST['myLocation2']) && preg_match('/^\d{1,3}$/', $_POST['myLocation2']) == 0){
			AspenError::raiseError('The 2nd location had an incorrect format.');
		}
		if (isset($_REQUEST['bypassAutoLogout']) && ($_REQUEST['bypassAutoLogout'] == 'yes' || $_REQUEST['bypassAutoLogout'] == 'on')){
			$this->bypassAutoLogout = 1;
		}else{
			$this->bypassAutoLogout = 0;
		}
		if (isset($_REQUEST['profileLanguage'])){
			$this->interfaceLanguage = $_REQUEST['profileLanguage'];
		}
		if (isset($_REQUEST['searchPreferenceLanguage'])){
			$this->searchPreferenceLanguage = $_REQUEST['searchPreferenceLanguage'];
		}

		//Make sure the selected location codes are in the database.
		if (isset($_POST['myLocation1'])){
			if ($_POST['myLocation1'] == 0){
				$this->myLocation1Id = $_POST['myLocation1'];
			}else{
				$location = new Location();
				$location->get('locationId', $_POST['myLocation1'] );
				if ($location->N != 1) {
					AspenError::raiseError('The 1st location could not be found in the database.');
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
				if ($location->N != 1) {
					AspenError::raiseError('The 2nd location could not be found in the database.');
				} else {
					$this->myLocation2Id = $_POST['myLocation2'];
				}
			}

		}

		$this->noPromptForUserReviews = (isset($_POST['noPromptForUserReviews']) && $_POST['noPromptForUserReviews'] == 'on')? 1 : 0;
		$this->clearCache();
		return $this->update();
	}

	/**
	 * Clear out the cached version of the patron profile.
	 */
	function clearCache(){
		/** @var Memcache $memCache */
		global $memCache, $serverName;
		$memCache->delete("user_{$serverName}_" . $this->id); // now stored by User object id column
	}

	/**
	 * @param $list UserList           object of the user list to check permission for
	 * @return  bool       true if this user can edit passed list
	 */
	function canEditList($list) {
		if ($this->id == $list->user_id){
			return true;
		}elseif ($this->hasRole('opacAdmin')){
			return true;
		}elseif ($this->hasRole('libraryAdmin') || $this->hasRole('contentEditor') || $this->hasRole('libraryManager')){
			$listUser = new User();
			$listUser->id = $list->user_id;
			$listUser->find(true);
			$listLibrary = Library::getLibraryForLocation($listUser->homeLocationId);
			$userLibrary = Library::getLibraryForLocation($this->homeLocationId);
			if ($userLibrary->libraryId == $listLibrary->libraryId){
				return true;
			}elseif(strpos($list->title, 'NYT - ') === 0 && ($this->hasRole('libraryAdmin') || $this->hasRole('contentEditor'))){
				//Allow NYT Times lists to be edited by any library admins and library managers
				return true;
			}
		}elseif ($this->hasRole('locationManager')){
			$listUser = new User();
			$listUser->id = $list->user_id;
			$listUser->find(true);
			if ($this->homeLocationId == $listUser->homeLocationId){
				return true;
			}
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

	public function getNumCheckedOutTotal($includeLinkedUsers = true) {
		$this->updateRuntimeInformation();
		$myCheckouts = $this->_numCheckedOutIls + $this->_numCheckedOutOverDrive + $this->_numCheckedOutHoopla + $this->_numCheckedOutRbdigital;
		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$myCheckouts += $user->getNumCheckedOutTotal(false);
				}
			}
		}
		return $myCheckouts;
	}

	public function getNumHoldsTotal($includeLinkedUsers = true) {
		$this->updateRuntimeInformation();
		$myHolds = $this->_numHoldsIls + $this->_numHoldsOverDrive + $this->_numHoldsRbdigital;
		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->linkedUsers as $user) {
					$myHolds += $user->getNumHoldsTotal(false);
				}
			}
		}
		return $myHolds;
	}

	public function getNumHoldsAvailableTotal($includeLinkedUsers = true){
		$this->updateRuntimeInformation();
		$myHolds = $this->_numHoldsAvailableIls + $this->_numHoldsAvailableOverDrive + $this->_numHoldsAvailableRbdigital;
		if ($includeLinkedUsers){
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->linkedUsers as $user) {
					$myHolds += $user->getNumHoldsAvailableTotal(false);
				}
			}
		}

		return $myHolds;
	}

	public function getNumBookingsTotal($includeLinkedUsers = true){
		$myBookings = $this->_numBookings;
		if ($includeLinkedUsers){
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->linkedUsers as $user) {
					$myBookings += $user->getNumBookingsTotal(false);
				}
			}
		}

		return $myBookings;
	}

	private $totalFinesForLinkedUsers = -1;
	public function getTotalFines($includeLinkedUsers = true){
		$totalFines = $this->_finesVal;
		if ($includeLinkedUsers){
			if ($this->totalFinesForLinkedUsers == -1){
				if ($this->getLinkedUsers() != null) {
					/** @var User $user */
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
	 *
	 * @param bool $includeLinkedUsers
	 * @return array
	 */
	public function getCheckouts($includeLinkedUsers = true){
		global $timer;
		//Get checked out titles from the ILS
		$ilsCheckouts = $this->getCatalogDriver()->getCheckouts($this);
        $allCheckedOut = $ilsCheckouts;
		$timer->logTime("Loaded transactions from catalog.");

		//Get checked out titles from OverDrive
		//Do not load OverDrive titles if the parent barcode (if any) is the same as the current barcode
		if ($this->isValidForEContentSource('overdrive')){
            require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
            $driver = new OverDriveDriver();
			$overDriveCheckedOutItems = $driver->getCheckouts($this);
            $allCheckedOut = array_merge($ilsCheckouts, $overDriveCheckedOutItems);
		}

		//Get checked out titles from Hoopla
		//Do not load Hoopla titles if the parent barcode (if any) is the same as the current barcode
		if ($this->isValidForEContentSource('hoopla')){
            require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
            $hooplaDriver = new HooplaDriver();
            $hooplaCheckedOutItems = $hooplaDriver->getCheckouts($this);
            $allCheckedOut = array_merge($allCheckedOut, $hooplaCheckedOutItems);
        }

        if ($this->isValidForEContentSource('rbdigital')){
            require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
            $rbdigitalDriver = new RbdigitalDriver();
            $rbdigitalCheckedOutItems = $rbdigitalDriver->getCheckouts($this);
            $allCheckedOut = array_merge($allCheckedOut, $rbdigitalCheckedOutItems);
        }

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $linkedUser) {
					$allCheckedOut = array_merge($allCheckedOut, $linkedUser->getCheckouts(false));
				}
			}
		}
		return $allCheckedOut;
	}

	public function getHolds($includeLinkedUsers = true, $unavailableSort = 'sortTitle', $availableSort = 'expire'){
		$ilsHolds = $this->getCatalogDriver()->getHolds($this);
		if ($ilsHolds instanceof AspenError) {
			$ilsHolds = array();
		}
        $allHolds = $ilsHolds;

		//Get holds from OverDrive
		if ($this->isValidForEContentSource('overdrive')){
            require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
            $driver = new OverDriveDriver();
			$overDriveHolds = $driver->getHolds($this);
            $allHolds = array_merge_recursive($allHolds, $overDriveHolds);
		}

        //Get holds from Rbdigital
        if ($this->isValidForEContentSource('rbdigital')){
            require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
            $driver = new RbdigitalDriver();
            $rbdigitalHolds = $driver->getHolds($this);
            $allHolds = array_merge_recursive($allHolds, $rbdigitalHolds);
        }

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$allHolds = array_merge_recursive($allHolds, $user->getHolds(false, $unavailableSort, $availableSort));
				}
			}
		}

		$indexToSortBy='sortTitle';
		$holdSort = function ($a, $b) use (&$indexToSortBy) {
			$a = isset($a[$indexToSortBy]) ? $a[$indexToSortBy] : null;
			$b = isset($b[$indexToSortBy]) ? $b[$indexToSortBy] : null;

			// Put empty values (except for specified values of zero) at the bottom of the sort
			if (modifiedEmpty($a) && modifiedEmpty($b)) {
				return 0;
			} elseif (!modifiedEmpty($a) && modifiedEmpty($b)) {
				return -1;
			} elseif (modifiedEmpty($a) && !modifiedEmpty($b)) {
				return 1;
			}

			if ($indexToSortBy == 'format') {
				$a = implode($a, ',');
				$b = implode($b, ',');
			}

			return strnatcasecmp($a, $b);
			// This will sort numerically correctly as well
		};

		if (count($allHolds['available'])) {
			switch ($availableSort) {
            case 'author' :
            case 'format' :
                //This is used in the sort function
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = $availableSort;
                break;
            case 'title' :
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = 'sortTitle';
                break;
            case 'libraryAccount' :
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = 'user';
                break;
            case 'expire' :
            default :
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = 'expire';
			}
			uasort($allHolds['available'], $holdSort);
		}
		if (count($allHolds['unavailable'])) {
			switch ($unavailableSort) {
            case 'author' :
            case 'location' :
            case 'position' :
            case 'status' :
            case 'format' :
                //This is used in the sort function
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = $unavailableSort;
                break;
            case 'placed' :
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = 'create';
                break;
            case 'libraryAccount' :
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = 'user';
                break;
            case 'title' :
            default :
                /** @noinspection PhpUnusedLocalVariableInspection */
                $indexToSortBy = 'sortTitle';
			}
			uasort($allHolds['unavailable'], $holdSort);
		}

		return $allHolds;
	}

	public function getMyBookings($includeLinkedUsers = true){
		$ilsBookings = $this->getCatalogDriver()->getMyBookings($this);
		if ($ilsBookings instanceof AspenError) {
			$ilsBookings = array();
		}

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$ilsBookings = array_merge_recursive($ilsBookings, $user->getMyBookings(false));
				}
			}
		}
		return $ilsBookings;
	}

	private $ilsFinesForUser;
	public function getMyFines($includeLinkedUsers = true){

		if (!isset($this->ilsFinesForUser)){
			$this->ilsFinesForUser = $this->getCatalogDriver()->getMyFines($this);
			if ($this->ilsFinesForUser instanceof AspenError) {
				$this->ilsFinesForUser = array();
			}
		}
		$ilsFines[$this->id] = $this->ilsFinesForUser;

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {
					$ilsFines += $user->getMyFines(false); // keep keys as userId
				}
			}
		}
		return $ilsFines;
	}

	public function getNameAndLibraryLabel(){
		return $this->displayName . ' - ' . $this->getHomeLibrarySystemName();
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
			$locations = $userLocation->getPickupBranches($this, $this->homeLocationId);
		}else{
			$locations = array();
		}
		$linkedUsers = $this->getLinkedUsers();
		foreach ($linkedUsers as $linkedUser){
			if ($recordSource == $linkedUser->source){
				$linkedUserLocation = new Location();
				$linkedUserPickupLocations = $linkedUserLocation->getPickupBranches($linkedUser, null, true);
				foreach ($linkedUserPickupLocations as $sortingKey => $pickupLocation) {
					foreach ($locations as $mainSortingKey => $mainPickupLocation) {
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

	function bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime){
		$result = $this->getCatalogDriver()->bookMaterial($this, $recordId, $startDate, $startTime, $endDate, $endTime);
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

	function cancelBookedMaterial($cancelId){
		$result = $this->getCatalogDriver()->cancelBookedMaterial($this, $cancelId);
		$this->clearCache();
		return $result;
	}

	function cancelAllBookedMaterial($includeLinkedUsers = true){
		$result = $this->getCatalogDriver()->cancelAllBookedMaterial($this);
		$this->clearCache();

		if ($includeLinkedUsers) {
			if ($this->getLinkedUsers() != null) {
				/** @var User $user */
				foreach ($this->getLinkedUsers() as $user) {

					$additionalResults = $user->cancelAllBookedMaterial(false);
					if (!$additionalResults['success']) { // if we received failures
						if ($result['success']) {
							$result = $additionalResults; // first set of failures, overwrite currently successful results
						} else { // if there were already failures, add the extra failure messages
							$result['message'] = array_merge($result['message'], $additionalResults['message']);
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for placing item level holds.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($recordId, $itemId, $pickupBranch) {
		$result = $this->getCatalogDriver()->placeItemHold($this, $recordId, $itemId, $pickupBranch);
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

	function thawHold($recordId, $holdId){
		$result = $this->getCatalogDriver()->thawHold($this, $recordId, $holdId);
		$this->clearCache();
		return $result;
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
				/** @var User $user */
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

	public function getReadingHistory($page, $recordsPerPage, $selectedSortOption) {
		return $this->getCatalogDriver()->getReadingHistory($this, $page, $recordsPerPage, $selectedSortOption);
	}

	public function doReadingHistoryAction($readingHistoryAction, $selectedTitles){
		$this->getCatalogDriver()->doReadingHistoryAction($this, $readingHistoryAction, $selectedTitles);
		$this->clearCache();
	}

	/**
	 * Used by Account Profile, to show users any additional Admin roles they may have.
	 * @return bool
	 */
	public function isStaff(){
		global $configArray;
		if (count($this->getRoles()) > 0){
			return true;
		}elseif (isset($configArray['Staff P-Types'])){
			$staffPTypes = $configArray['Staff P-Types'];
			$pType = $this->patronType;
			if ($pType && array_key_exists($pType, $staffPTypes)){
				return true;
			}
		}
		return false;
	}

	public function updatePatronInfo($canUpdateContactInfo){
		$result = $this->getCatalogDriver()->updatePatronInfo($this, $canUpdateContactInfo);
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
			return ['success' => false, 'errors' => "Please enter your current pin number"];
		}
		if ($this->cat_password != $oldPin){
			return ['success' => false, 'errors' => "The old pin number is incorrect"];
		}
		if (!empty($_REQUEST['pin1'])){
			$newPin = $_REQUEST['pin1'];
		}else{
			return ['success' => false, 'errors' => "Please enter the new pin number"];
		}
		if (!empty($_REQUEST['pin2'])){
			$confirmNewPin = $_REQUEST['pin2'];
		}else{
			return ['success' => false, 'errors' => "Please enter the new pin number again"];
		}
		if ($newPin != $confirmNewPin){
			return ['success' => false, 'errors' => "New PINs do not match. Please try again."];
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
		$result = $this->getCatalogDriver()->importListsFromIls($this);
		return $result;
	}

	public function getShowUsernameField() {
	    if ($this->getCatalogDriver() != null){
            return $this->getCatalogDriver()->getShowUsernameField();
        }else{
	        return false;
        }

	}

	/**
	 * @return string
	 */
	public function getMasqueradeLevel()
	{
		if (empty($this->masqueradeLevel)) $this->setMasqueradeLevel();
		return $this->masqueradeLevel;
	}

	private function setMasqueradeLevel()
	{
		$this->masqueradeLevel = 'none';
		if (!empty($this->patronType)) {
			require_once ROOT_DIR . '/Drivers/marmot_inc/PType.php';
			$pType = new pType();
			$pType->get('pType', $this->patronType);
			if ($pType->N > 0) {
				$this->masqueradeLevel = $pType->masquerade;
			}
		}
	}

	public function canMasquerade() {
		return $this->getMasqueradeLevel() != 'none';
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

	function setNumCheckedOutHoopla($val){
		$this->_numCheckedOutHoopla = $val;
	}

	function setNumCheckedOutOverDrive($val){
		$this->_numCheckedOutOverDrive = $val;
	}

	function setNumHoldsAvailableOverDrive($val){
		$this->_numHoldsAvailableOverDrive = $val;
		$this->_numHoldsOverDrive += $val;
	}

	function setNumHoldsRequestedOverDrive($val){
		$this->_numHoldsRequestedOverDrive = $val;
		$this->_numHoldsOverDrive += $val;
	}

    function setNumCheckedOutRbdigital($val){
        $this->_numCheckedOutRbdigital = $val;
    }

    function setNumHoldsAvailableRbdigital($val){
        $this->_numHoldsAvailableRbdigital = $val;
        $this->_numHoldsRbdigital += $val;
    }

    function setNumHoldsRequestedRbdigital($val){
        $this->_numHoldsRequestedRbdigital = $val;
        $this->_numHoldsRbdigital += $val;
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

	function hasRecommendations(){
		require_once ROOT_DIR . '/sys/LocalEnrichment/UserWorkReview.php';

		$rating = new UserWorkReview();
		$rating->whereAdd("userId = {$this->id}");
		$rating->whereAdd('rating >= 3'); // Some entries are just reviews (and therefore have a default rating of -1)
		return $rating->count() > 0;
	}

	function setReadingHistorySize($val){
		$this->_readingHistorySize = $val;
	}

	function getReadingHistorySize(){
		$this->updateRuntimeInformation();
		return $this->_readingHistorySize;
	}

	function getPatronUpdateForm()
	{
		if ($this->getCatalogDriver() != null){
			return $this->getCatalogDriver()->getPatronUpdateForm($this);
		}else{
			return null;
		}
	}
}

function modifiedEmpty($var) {
	// specified values of zero will not be considered empty
	return empty($var) && $var !== 0 && $var !== '0';
}