<?php

class CatalogConnection
{
	/**
	 * A boolean value that defines whether a connection has been successfully
	 * made.
	 *
	 * @access public
	 * @var    bool
	 */
	public $status = false;

	public $accountProfile;

	/**
	 * The object of the appropriate driver.
	 *
	 * @access private
	 * @var    AbstractIlsDriver
	 */
	public $driver;

	/**
	 * Constructor
	 *
	 * This is responsible for instantiating the driver that has been specified.
	 *
	 * @param string         $driver         The name of the driver to load.
	 * @param AccountProfile $accountProfile
	 * @throws PDOException error if we cannot connect to the driver.
	 *
	 * @access public
	 */
	public function __construct($driver, $accountProfile)
	{
		$path = ROOT_DIR . "/Drivers/{$driver}.php";
		if (is_readable($path) && $driver != 'AbstractIlsDriver') {
            /** @noinspection PhpIncludeInspection */
            require_once $path;

			try {
				$this->driver = new $driver($accountProfile);
			} catch (PDOException $e) {
				global $logger;
				$logger->log("Unable to create driver $driver for account profile {$accountProfile->name}", Logger::LOG_ERROR);
				throw $e;
			}

			$this->accountProfile = $accountProfile;
			$this->status = true;
		}
	}

	/**
	 * Check Function
	 *
	 * This is responsible for checking the driver configuration to determine
	 * if the system supports a particular function.
	 *
	 * @param string $function The name of the function to check.
	 *
	 * @return mixed On success, an associative array with specific function keys
	 * and values; on failure, false.
	 * @access public
	 */
	public function checkFunction($function)
	{
		// Extract the configuration from the driver if available:
		$functionConfig = method_exists($this->driver, 'getConfig') ? $this->driver->getConfig($function) : false;

		// See if we have a corresponding check method to analyze the response:
		$checkMethod = "_checkMethod".$function;
		if (!method_exists($this, $checkMethod)) {
			//Just see if the method exists on the driver
			return method_exists($this->driver, $function);
		}

		// Send back the settings:
		return $this->$checkMethod($functionConfig);
	}

	/**
	 * Patron Login
	 *
	 * This is responsible for authenticating a patron against the catalog.
	 *
	 * @param string  $username        The patron username
	 * @param string  $password        The patron password
	 * @param User    $parentAccount   A parent account that we are linking from if any
	 * @param boolean $validatedViaSSO True if the patron has already been validated via SSO.  If so we don't need to validation, just retrieve information
	 *
	 * @return User|null     User object or null if the user cannot be logged in
	 * @access public
	 */
	public function patronLogin($username, $password, $parentAccount = null, $validatedViaSSO = false) {
		global $timer;
		global $logger;
		global $offlineMode;

		//Get the barcode property
		if ($this->accountProfile->loginConfiguration == 'barcode_pin'){
			$barcode = $username;
		}else{
			$barcode = $password;
		}

		//Strip any non digit characters from the password
		//Can't do this any longer since some libraries do have characters in their barcode:
		//$password = preg_replace('/[a-or-zA-OR-Z\W]/', '', $password);
		//Remove any spaces from the barcode
		$barcode = preg_replace('/[^a-zA-Z\d\s]/', '', trim($barcode));
		if ($offlineMode){
			//The catalog is offline, check the database to see if the user is valid
			$user = new User();
			if ($this->driver->accountProfile->loginConfiguration == 'barcode_pin') {
				$user->cat_username = $barcode;
			}else{
				$user->cat_password = $barcode;
			}
			if ($user->find(true)){
				if ($this->driver->accountProfile->loginConfiguration = 'barcode_pin') {
					//We load the account based on the barcode make sure the pin matches
					$userValid = $user->cat_password == $password;
				}else{
					//We still load based on barcode, make sure the username is similar
					$userValid = $this->areNamesSimilar($username, $user->cat_username);
				}
				if ($userValid){
					//We have a good user account for additional processing
				} else {
					$timer->logTime("offline patron login failed due to invalid name");
					$logger->log("offline patron login failed due to invalid name", Logger::LOG_NOTICE);
					return null;
				}
			} else {
				$timer->logTime("offline patron login failed because we haven't seen this user before");
				$logger->log("offline patron login failed because we haven't seen this user before", Logger::LOG_NOTICE);
				return null;
			}
		}else {
			$user = $this->driver->patronLogin($username, $password, $validatedViaSSO);
		}

		if ($user && !($user instanceof AspenError)){
			if ($user->displayName == '') {
				if ($user->firstname == ''){
					$user->displayName = $user->lastname;
				}else{
					// #PK-979 Make display name configurable firstname, last initial, vs first initial last name
					$homeLibrary = $user->getHomeLibrary();
					if ($homeLibrary == null || ($homeLibrary->__get('patronNameDisplayStyle') == 'firstinitial_lastname')){
						// #PK-979 Make display name configurable firstname, last initial, vs first initial last name
						$user->displayName = substr($user->firstname, 0, 1) . '. ' . $user->lastname;
					}elseif ($homeLibrary->__get('patronNameDisplayStyle') == 'lastinitial_firstname'){
						$user->displayName = $user->firstname . ' ' . substr($user->lastname, 0, 1) . '.';
					}
				}
				$user->update();
			}
			if ($parentAccount) $user->setParentUser($parentAccount); // only set when the parent account is passed.
		}

		return $user;
	}

	/**
	 * @param User $user
	 */
	public function updateUserWithAdditionalRuntimeInformation($user){
		global $timer;
        require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
        $overDriveDriver = new OverDriveDriver();
		if ($user->isValidForEContentSource('overdrive') && $overDriveDriver->isUserValidForOverDrive($user)){
			$overDriveSummary = $overDriveDriver->getAccountSummary($user);
			$user->setNumCheckedOutOverDrive($overDriveSummary['numCheckedOut']);
			$user->setNumHoldsAvailableOverDrive($overDriveSummary['numAvailableHolds']);
			$user->setNumHoldsRequestedOverDrive($overDriveSummary['numUnavailableHolds']);
			$timer->logTime("Updated runtime information from OverDrive");
		}

		if ($user->isValidForEContentSource('hoopla')){
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$hooplaSummary = $driver->getAccountSummary($user);
			$hooplaCheckOuts = isset($hooplaSummary->currentlyBorrowed) ? $hooplaSummary->currentlyBorrowed : 0;
			$user->setNumCheckedOutHoopla($hooplaCheckOuts);
		}

		if ($user->isValidForEContentSource('rbdigital')) {
		    require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
		    $driver = new RbdigitalDriver();
            $rbdigitalSummary = $driver->getAccountSummary($user);
            $user->setNumCheckedOutRbdigital($rbdigitalSummary['numCheckedOut']);
            $user->setNumHoldsAvailableRbdigital($rbdigitalSummary['numAvailableHolds']);
            $user->setNumHoldsRequestedRbdigital($rbdigitalSummary['numUnavailableHolds']);
        }

		$materialsRequest = new MaterialsRequest();
		$materialsRequest->createdBy = $user->id;
		$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
		if ($homeLibrary && $homeLibrary->enableMaterialsRequest){
			$statusQuery = new MaterialsRequestStatus();
			$statusQuery->isOpen = 1;
			$statusQuery->libraryId = $homeLibrary->libraryId;
			$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
			$materialsRequest->find();
			$user->setNumMaterialsRequests($materialsRequest->N);
			$timer->logTime("Updated number of active materials requests");
		}


		if ($user->trackReadingHistory && $user->initialReadingHistoryLoaded){
			require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
			$readingHistoryDB = new ReadingHistoryEntry();
            $readingHistoryDB->userId = $user->id;
			$readingHistoryDB->deleted = 0;
			$readingHistoryDB->groupBy('groupedWorkPermanentId');
            $user->setReadingHistorySize($readingHistoryDB->count());
			$timer->logTime("Updated reading history size");
		}
	}

	/**
	 * @param $nameFromUser  string
	 * @param $nameFromIls   string
	 * @return boolean
	 */
	private function areNamesSimilar($nameFromUser, $nameFromIls) {
		$fullName = str_replace(",", " ", $nameFromIls);
		$fullName = str_replace(";", " ", $fullName);
		$fullName = str_replace(";", "'", $fullName);
		$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
		$allNameComponents = preg_split('^[\s-]^', strtolower($fullName));

		//Get the first name that the user supplies.
		//This expects the user to enter one or two names and only
		//Validates the first name that was entered.
		$enteredNames = preg_split('^[\s-]^', strtolower($nameFromUser));
		$userValid = false;
		foreach ($enteredNames as $name) {
			if (in_array($name, $allNameComponents, false)) {
				$userValid = true;
				break;
			}
		}
		return $userValid;
	}

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user    The user to load transactions for
	 *
	 * @return mixed        Array of the patron's transactions on success,
	 * AspenError otherwise.
	 * @access public
	 */
	public function getCheckouts($user)
	{
		$transactions = $this->driver->getCheckouts($user);
		foreach ($transactions as $key => $curTitle){
			$curTitle['user'] = $user->getNameAndLibraryLabel();
			$curTitle['userId'] = $user->id;
			$curTitle['fullId'] = $this->accountProfile->recordSource . ':' . $curTitle['id'];

			if ($curTitle['dueDate']){
				// use the same time of day to calculate days until due, in order to avoid errors wiht rounding
				$dueDate = strtotime('midnight', $curTitle['dueDate']);
				$today = strtotime('midnight');
				$daysUntilDue = ceil(($dueDate - $today) / (24 * 60 * 60));
				$overdue = $daysUntilDue < 0;
				$curTitle['overdue'] = $overdue;
				$curTitle['daysUntilDue'] = $daysUntilDue;
			}
			//Determine if the record
			$transactions[$key] = $curTitle;
		}
		return $transactions;
	}

	/**
	 * Get Patron Fines
	 *
	 * This is responsible for retrieving all fines by a specific patron.
	 *
	 * @param User $patron The patron from patronLogin
	 *
	 * @return mixed        Array of the patron's fines on success, AspenError
	 * otherwise.
	 * @access public
	 */
	public function getMyFines($patron, $includeMessages = false)
	{
		return $this->driver->getMyFines($patron, $includeMessages);
	}

	/**
	 * Get Reading History
	 *
	 * This is responsible for retrieving a history of checked out items for the patron.
	 *
	 * @param   User   $patron     The patron array
	 * @param   int     $page
	 * @param   int     $recordsPerPage
	 * @param   string  $sortOption
	 *
	 * @return  array               Array of the patron's reading list
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut"){
		//Get reading history from the database unless we specifically want to load from the driver.
        $result = array('historyActive'=>$patron->trackReadingHistory, 'titles'=>array(), 'numTitles'=> 0);
        if (!$patron->trackReadingHistory){
            return $result;
        }
        if (!$patron->initialReadingHistoryLoaded) {
            if ($this->driver->hasNativeReadingHistory()){
                //Load existing reading history from the ILS
                $moreRecordsToLoad = true;
                while ($moreRecordsToLoad) {
                    $moreRecordsToLoad = false;
                    $result = $this->driver->getReadingHistory($patron, -1, -1, $sortOption);
                    if ($result['numTitles'] > 0){
                        foreach ($result['titles'] as $title){
                            if ($title['permanentId'] != null){
                                $userReadingHistoryEntry = new ReadingHistoryEntry();
                                $userReadingHistoryEntry->userId = $patron->id;
                                $userReadingHistoryEntry->groupedWorkPermanentId = $title['permanentId'];
                                $userReadingHistoryEntry->source = $this->accountProfile->recordSource;
                                $userReadingHistoryEntry->sourceId = $title['recordId'];
                                $userReadingHistoryEntry->title = $title['title'];
                                $userReadingHistoryEntry->author = $title['author'];
                                $userReadingHistoryEntry->format = $title['format'];
                                $userReadingHistoryEntry->checkOutDate = $title['checkout'];
                                $userReadingHistoryEntry->checkInDate = null;
                                $userReadingHistoryEntry->deleted = 0;
                                $userReadingHistoryEntry->insert();
                            }
                        }
                    }
                    //TODO: Check to see if there is more to load
                }
            }
            $patron->initialReadingHistoryLoaded = true;
            $patron->update();
        }
        //Do the
        $this->updateReadingHistoryBasedOnCurrentCheckouts($patron);

        require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
        $readingHistoryDB = new ReadingHistoryEntry();
        $readingHistoryDB->userId = $patron->id;
        $readingHistoryDB->deleted = 0; //Only show titles that have not been deleted
        $readingHistoryDB->selectAdd();
        $readingHistoryDB->selectAdd('groupedWorkPermanentId');
        $readingHistoryDB->selectAdd('title');
        $readingHistoryDB->selectAdd('author');
        $readingHistoryDB->selectAdd('MAX(checkOutDate) as checkOutDate');
        $readingHistoryDB->selectAdd('GROUP_CONCAT(DISTINCT(format)) as format');
        if ($sortOption == "checkedOut"){
            $readingHistoryDB->orderBy('MAX(checkOutDate) DESC, title ASC');
        }else if ($sortOption == "returned"){
            $readingHistoryDB->orderBy('checkInDate DESC, title ASC');
        }else if ($sortOption == "title"){
            $readingHistoryDB->orderBy('title ASC, MAX(checkOutDate) DESC');
        }else if ($sortOption == "author"){
            $readingHistoryDB->orderBy('author ASC, title ASC, MAX(checkOutDate) DESC');
        }else if ($sortOption == "format"){
            $readingHistoryDB->orderBy('format ASC, title ASC, MAX(checkOutDate) DESC');
        }
        $readingHistoryDB->groupBy(['groupedWorkPermanentId', 'title', 'author']);

        $numTitles = $readingHistoryDB->count();

        if ($recordsPerPage != -1){
            $readingHistoryDB->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
        }
        $readingHistoryDB->find();
        $readingHistoryTitles = array();

        while ($readingHistoryDB->fetch()){
            $historyEntry = $this->getHistoryEntryForDatabaseEntry($readingHistoryDB);

            $readingHistoryTitles[] = $historyEntry;
        }

        return array('historyActive'=>$patron->trackReadingHistory, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param   User    $patron         The user to do the reading history action on
	 * @param   string  $action         The action to perform
	 * @param   array   $selectedTitles The titles to do the action on if applicable
	 */
	function doReadingHistoryAction($patron, $action, $selectedTitles){
		if ($action == 'deleteMarked'){
            //Remove titles from database (do not remove from ILS)
            foreach ($selectedTitles as $id => $titleId){
                $readingHistoryDB = new ReadingHistoryEntry();
                $readingHistoryDB->userId = $patron->id;
                $readingHistoryDB->groupedWorkPermanentId = strtolower($id);
                $readingHistoryDB->find();
                if ($id && $readingHistoryDB->N > 0){
                    while ($readingHistoryDB->fetch()){
                        $readingHistoryDB->deleted = 1;
                        $readingHistoryDB->update();
                    }
                }else{
                    $readingHistoryDB = new ReadingHistoryEntry();
                    $readingHistoryDB->userId = $patron->id;
                    $readingHistoryDB->id = str_replace('rsh', '', $titleId);
                    if ($readingHistoryDB->find(true)){
                        $readingHistoryDB->deleted = 1;
                        $readingHistoryDB->update();
                    }
                }
            }
        }elseif ($action == 'deleteAll'){
            //Remove all titles from database (do not remove from ILS)
            $readingHistoryDB = new ReadingHistoryEntry();
            $readingHistoryDB->userId = $patron->id;
            $readingHistoryDB->find();
            while ($readingHistoryDB->fetch()){
                $readingHistoryDB->deleted = 1;
                $readingHistoryDB->update();
            }
        }elseif ($action == 'exportList'){
            //Leave this unimplemented for now.
        }elseif ($action == 'optOut'){
            //Delete the reading history (permanently this time since we are opting out)
            $readingHistoryDB = new ReadingHistoryEntry();
            $readingHistoryDB->userId = $patron->id;
            $readingHistoryDB->delete(true);

            //Opt out within Aspen since the ILS does not seem to implement this functionality
            $patron->trackReadingHistory = false;
            $patron->update();
        }elseif ($action == 'optIn'){
            //Opt in within Aspen since the ILS does not seem to implement this functionality
            $patron->trackReadingHistory = true;
            $patron->update();

            //TODO: Load the reading history from the ILS if available
            if ($this->driver->hasNativeReadingHistory()){

            }
        }
        if ($this->driver->performsReadingHistoryUpdatesOfILS()){
            $this->driver->doReadingHistoryAction($patron, $action, $selectedTitles);
        }
	}


	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $user    The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds($user) {
		$holds = $this->driver->getHolds($user);
		foreach ($holds as $section => $holdsForSection){
			foreach ($holdsForSection as $key => $curTitle){
				$curTitle['user'] = $user->getNameAndLibraryLabel();
				$curTitle['userId'] = $user->id;
				$curTitle['allowFreezeHolds'] = $user->getHomeLibrary()->allowFreezeHolds;
				if (!isset($curTitle['sortTitle'])){
					$curTitle['sortTitle'] = $curTitle['title'];
				}
				$holds[$section][$key] = $curTitle;
			}
		}

		return $holds;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param   User    $patron       The User to place a hold for
	 * @param   string  $recordId     The id of the bib record
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch, $cancelDate = null) {
		$result =  $this->driver->placeHold($patron, $recordId, $pickupBranch, $cancelDate);
		if ($result['success'] == true){
		    $indexingProfileId = $this->driver->getIndexingProfile()->id;
		    //Track usage by the user
            require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
            $userUsage = new UserILSUsage();
            $userUsage->userId = $patron->id;
            $userUsage->indexingProfileId = $indexingProfileId;
            $userUsage->year = date('Y');
            $userUsage->month = date('n');

            if ($userUsage->find(true)) {
                $userUsage->usageCount++;
                $userUsage->update();
            } else {
                $userUsage->usageCount = 1;
                $userUsage->insert();
            }

            //Track usage of the record
            require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';
            $recordUsage = new ILSRecordUsage();
            $recordUsage->indexingProfileId = $indexingProfileId;
            $recordUsage->recordId = $recordId;
            $recordUsage->year = date('Y');
            $recordUsage->month = date('n');
            if ($recordUsage->find(true)) {
                $recordUsage->timesUsed++;
                $recordUsage->update();
            } else {
                $recordUsage->timesUsed = 1;
                $recordUsage->insert();
            }
        }
		return $result;
	}

	/**
	* Place Item Hold
	*
	* This is responsible for placing item level holds.
	*
	* @param   User    $patron     The User to place a hold for
	* @param   string  $recordId   The id of the bib record
	* @param   string  $itemId     The id of the item to hold
	* @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	* @return  mixed               True if successful, false if unsuccessful
	*                              If an error occurs, return a AspenError
	* @access  public
	*/
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch) {
		return $this->driver->placeItemHold($patron, $recordId, $itemId, $pickupBranch);
	}

	function updatePatronInfo($user, $canUpdateContactInfo)
	{
		return $errors = $this->driver->updatePatronInfo($user, $canUpdateContactInfo);
	}

	// TODO Millennium only at this time, set other drivers to return false.
	function bookMaterial($patron, $recordId, $startDate, $startTime = null, $endDate = null, $endTime = null){
		return $this->driver->bookMaterial($patron, $recordId, $startDate, $startTime, $endDate, $endTime);
	}

	// TODO Millennium only at this time, set other drivers to return false.
	function cancelBookedMaterial($patron, $cancelIds){
		return $this->driver->cancelBookedMaterial($patron, $cancelIds);
	}

	// TODO Millennium only at this time, set other drivers to return false.
	function cancelAllBookedMaterial($patron){
		return $this->driver->cancelAllBookedMaterial($patron);
	}

	/**
	 * @param User $patron
     *
     * @return array
	 */
	function getMyBookings($patron){
		$bookings = $this->driver->getMyBookings($patron);
		foreach ($bookings as &$booking) {
			$booking['user'] = $patron->getNameAndLibraryLabel();
			$booking['userId'] = $patron->id;
		}
		return $bookings;
	}

	function selfRegister(){
		return $this->driver->selfRegister();
	}

	/**
	 * Default method -- pass along calls to the driver if available; return
	 * false otherwise.  This allows custom functions to be implemented in
	 * the driver without constant modification to the connection class.
	 *
	 * @param string $methodName The name of the called method.
	 * @param array  $params     Array of passed parameters.
	 *
	 * @return mixed             Varies by method (false if undefined method)
	 * @access public
	 */
	public function __call($methodName, $params)
	{
		$method = array($this->driver, $methodName);
		if (is_callable($method)) {
			return call_user_func_array($method, $params);
		}
		return false;
	}

	public function getSelfRegistrationFields() {
		return $this->driver->getSelfRegistrationFields();
	}

	/**
	 * @param ReadingHistoryEntry $readingHistoryDB
	 * @return mixed
	 */
	public function getHistoryEntryForDatabaseEntry($readingHistoryDB) {
		$historyEntry = array();

        require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
        $recordDriver = new GroupedWorkDriver($readingHistoryDB->groupedWorkPermanentId);

		$historyEntry['deletable'] = true;
		$historyEntry['title'] = $readingHistoryDB->title;
		$historyEntry['author'] = $readingHistoryDB->author;
		$historyEntry['format'] = $readingHistoryDB->format;
		$historyEntry['checkout'] = $readingHistoryDB->checkOutDate;
		$historyEntry['checkin'] = $readingHistoryDB->checkInDate;
		$historyEntry['ratingData'] = $recordDriver->getRatingData();
		$historyEntry['permanentId'] = $readingHistoryDB->groupedWorkPermanentId;
		$historyEntry['linkUrl'] = $recordDriver->getLinkUrl();
		$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('small');

		return $historyEntry;
	}

	/**
	 * @param User $patron
	 */
	private function updateReadingHistoryBasedOnCurrentCheckouts($patron) {
		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		//Note, include deleted titles here so they are not added multiple times.
		$readingHistoryDB = new ReadingHistoryEntry();
		$readingHistoryDB->userId = $patron->id;
		$readingHistoryDB->whereAdd('checkInDate IS NULL');
		$readingHistoryDB->find();

		$activeHistoryTitles = array();
		while ($readingHistoryDB->fetch()){
			$historyEntry = [];
            $historyEntry['source'] = $readingHistoryDB->source;
            $historyEntry['id'] = $readingHistoryDB->sourceId;
			$key = $historyEntry['source'] . ':' . $historyEntry['id'];
			$activeHistoryTitles[$key] = $historyEntry;
		}

		//Update reading history based on current checkouts.  That way it never looks out of date
		$checkouts = $patron->getCheckouts(false);
		foreach ($checkouts as $checkout){
			$sourceId = '?';
			$source = $checkout['checkoutSource'];
			if ($source == 'OverDrive'){
				$sourceId = $checkout['overDriveId'];
			}elseif ($source == 'Hoopla'){
				$sourceId = $checkout['hooplaId'];
			}elseif ($source == 'ILS'){
				$sourceId = $checkout['fullId'];
			}elseif ($source == 'eContent'){
				$source = $checkout['recordType'];
				$sourceId = $checkout['id'];
			}
			$key = $source . ':' . $sourceId;
			if (array_key_exists($key, $activeHistoryTitles)){
				unset($activeHistoryTitles[$key]);
			}else{
				$historyEntryDB = new ReadingHistoryEntry();
				$historyEntryDB->userId = $patron->id;
				if (isset($checkout['groupedWorkId'])){
					$historyEntryDB->groupedWorkPermanentId = $checkout['groupedWorkId'] == null ? '' : $checkout['groupedWorkId'];
				}else{
					$historyEntryDB->groupedWorkPermanentId = "";
				}

				$historyEntryDB->source       = $source;
				$historyEntryDB->sourceId     = $sourceId;
				$historyEntryDB->title        = substr($checkout['title'], 0, 150);
				$historyEntryDB->author       = substr($checkout['author'], 0, 75);
				$historyEntryDB->format       = substr($checkout['format'], 0, 50);
				$historyEntryDB->checkOutDate = time();
				if (!$historyEntryDB->insert()){
					global $logger;
					$logger->log("Could not insert new reading history entry", Logger::LOG_ERROR);
				}
			}
		}

		//Anything that was still active is now checked in
		foreach ($activeHistoryTitles as $historyEntry){
			//Update even if deleted to make sure code is cleaned up correctly
			$historyEntryDB = new ReadingHistoryEntry();
			$historyEntryDB->source = $historyEntry['source'];
			$historyEntryDB->sourceId = $historyEntry['id'];
			$historyEntryDB->checkInDate = null;
			if ($historyEntryDB->find(true)){
				$historyEntryDB->checkInDate = time();
				$numUpdates = $historyEntryDB->update();
				if ($numUpdates != 1){
					global $logger;
					$key = $historyEntry['source'] . ':' . $historyEntry['id'];
					$logger->log("Could not update reading history entry $key", Logger::LOG_ERROR);
				}
			}
		}
	}

	function cancelHold($patron, $recordId, $cancelId = null) {
		return $this->driver->cancelHold($patron, $recordId, $cancelId);
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) {
		return $this->driver->freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate);
	}

	function thawHold($patron, $recordId, $itemToThawId) {
		return $this->driver->thawHold($patron, $recordId, $itemToThawId);
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) {
		return $this->driver->changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation);
	}

	public function getBookingCalendar($recordId) {
		// Graceful degradation -- return null if method not supported by driver.
		return method_exists($this->driver, 'getBookingCalendar') ?
			$this->driver->getBookingCalendar($recordId) : null;
	}

	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null){
		return $this->driver->renewCheckout($patron, $recordId, $itemId, $itemIndex);
	}

	public function renewAll($patron){
		if ($this->driver->hasFastRenewAll()){
			return $this->driver->renewAll($patron);
		}else{
			//Get all list of all transactions
			$currentTransactions = $this->driver->getCheckouts($patron);
			$renewResult = array(
				'success' => true,
				'message' => array(),
				'Renewed' => 0,
				'NotRenewed' => 0
			);
			$renewResult['Total'] = count($currentTransactions);
			$numRenewals = 0;
			$failure_messages = array();
			foreach ($currentTransactions as $transaction){
				$curResult = $this->renewCheckout($patron, $transaction['recordId'], $transaction['renewIndicator'], null);
				if ($curResult['success']){
					$numRenewals++;
				} else {
					$failure_messages[] = $curResult['message'];
				}
			}
			$renewResult['Renewed'] += $numRenewals;
			$renewResult['NotRenewed'] = $renewResult['Total'] - $renewResult['Renewed'];
			if ($renewResult['NotRenewed'] > 0) {
				$renewResult['success'] = false;
				$renewResult['message'] = $failure_messages;
			}else{
				$renewResult['message'][] = "All items were renewed successfully.";
			}
			return $renewResult;
		}
	}

	public function placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch) {
		if ($this->checkFunction('placeVolumeHold')){
			return $this->driver->placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch);
		}else{
			return array(
					'success' => false,
					'message' => 'Volume level holds have not been implemented for this ILS.');
		}
	}

	public function importListsFromIls($patron){
		if ($this->checkFunction('importListsFromIls')){
			return $this->driver->importListsFromIls($patron);
		}else{
			return array(
					'success' => false,
					'errors' => array('Importing Lists has not been implemented for this ILS.'));
		}
	}

	public function getShowUsernameField() {
		if ($this->checkFunction('hasUsernameField')) {
			return $this->driver->hasUsernameField();
		}else{
			return false;
		}
	}

    /**
     * @param User $user
     * @param string $oldPin
     * @param string $newPin
     * @param $confirmNewPin
     * @return string a message to the user letting them know what happened
     */
    function updatePin(/** @noinspection PhpUnusedParameterInspection */ $user, $oldPin, $newPin, $confirmNewPin){
        /* var Logger $logger */
        global $logger;
        $logger->log('Call to updatePin(), function not implemented.', Logger::LOG_WARNING);

        return 'Can not update Pins';
    }

    function requestPinReset($patronBarcode){
        if ($this->checkFunction('requestPinReset')) {
            return $this->driver->requestPinReset($patronBarcode);
        }else{
            return false;
        }
    }

    function showOutstandingFines()
    {
        return $this->driver->showOutstandingFines();
    }
}