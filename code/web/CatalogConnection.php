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

	/** @var AccountProfile  */
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
	 * @param string $driver The name of the driver to load.
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
		$checkMethod = "_checkMethod" . $function;
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
	 * @param string $username The patron username
	 * @param string $password The patron password
	 * @param User $parentAccount A parent account that we are linking from if any
	 * @param boolean $validatedViaSSO True if the patron has already been validated via SSO.  If so we don't need to validation, just retrieve information
	 *
	 * @return User|null     User object or null if the user cannot be logged in
	 * @access public
	 */
	public function patronLogin($username, $password, $parentAccount = null, $validatedViaSSO = false)
	{
		global $offlineMode;

		$barcodesToTest = array();
		$barcodesToTest[$username] = $username;
		$barcodesToTest[preg_replace('/[^a-zA-Z\d]/', '', trim($username))] = preg_replace('/[^a-zA-Z\d]/', '', trim($username));
		//Special processing to allow users to login with short barcodes
		global $library;
		if ($library) {
			if ($library->barcodePrefix) {
				if (strpos($username, $library->barcodePrefix) !== 0) {
					//Add the barcode prefix to the barcode
					$barcodesToTest[$library->barcodePrefix . $username] = $library->barcodePrefix . $username;
				}
			}
		}

		//Get the existing user from the database.  This validates that the username and password on record are correct
		$user = null;
		foreach ($barcodesToTest as $barcode) {
			$user = $this->getUserFromDatabase($barcode, $password, $username);
			if ($user != null){
				break;
			}
		}

		if ($offlineMode) {
			if ($user == null){
				return null;
			}
		} else {
			if ($user != null) {
				//If we have a valid patron, only revalidate every 15 minutes
				if ($user->lastLoginValidation < (time() - 15 * 60)) {
					$doPatronLogin = true;
				}else{
					$doPatronLogin = false;
				}
			}else{
				$doPatronLogin = true;
			}
			if ($doPatronLogin || isset($_REQUEST['reload'])) {
				//Catalog is online, do the login
				$user = $this->driver->patronLogin($username, $password, $validatedViaSSO);
				if ($user && !($user instanceof AspenError)) {
					try {
						$user->lastLoginValidation = time();
						$user->update();
					}catch (Exception $e){
						//This happens before database update
					}
				}
			}
		}

		if ($user && !($user instanceof AspenError)) {
			if ($parentAccount) $user->setParentUser($parentAccount); // only set when the parent account is passed.

			//Record stats to show the user logged in
			$indexingProfile = $this->accountProfile->getIndexingProfile();
			if ($indexingProfile != null){
				require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
				$userUsage = new UserILSUsage();
				$userUsage->userId = $user->id;
				$userUsage->indexingProfileId = $this->accountProfile->getIndexingProfile()->id;
				$userUsage->year = date('Y');
				$userUsage->month = date('n');
				if (!$userUsage->find(true)) {
					$userUsage->insert();
				}
			}

		}

		return $user;
	}

	/**
	 * @param string $patronBarcode
	 * @return bool|User
	 */
	public function findNewUser(string $patronBarcode){
		return $this->driver->findNewUser($patronBarcode);
	}

	/**
	 * @param User $user
	 */
	public function updateUserWithAdditionalRuntimeInformation($user)
	{
		global $timer;
		$timer->logTime("Starting to Update Additional Runtime information for user " . $user->id);

		$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
		if ($homeLibrary) {
			if ($homeLibrary->enableMaterialsRequest == 1) {
				$materialsRequest = new MaterialsRequest();
				$materialsRequest->createdBy = $user->id;
				$statusQuery = new MaterialsRequestStatus();
				$statusQuery->isOpen = 1;
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
				$materialsRequest->find();
				$user->setNumMaterialsRequests($materialsRequest->getNumResults());
				$timer->logTime("Updated number of active materials requests");
			} elseif ($homeLibrary->enableMaterialsRequest == 2) {
				$user->setNumMaterialsRequests($this->getNumMaterialsRequests($user));
			}
		}

		$timer->logTime("Updated Additional Runtime information for user " . $user->id);
	}

	/**
	 * @param $nameFromUser  string
	 * @param $nameFromIls   string
	 * @return boolean
	 */
	private function areNamesSimilar($nameFromUser, $nameFromIls)
	{
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
	 * @param User $user The user to load transactions for
	 * @return Checkout[]            Array of the patron's transactions on success,
	 * AspenError otherwise.
	 * @access public
	 */
	public function getCheckouts(User $user)
	{
		return $this->driver->getCheckouts($user);
	}

	/**
	 * Get Patron Fines
	 *
	 * This is responsible for retrieving all fines by a specific patron.
	 *
	 * @param User $patron The patron from patronLogin
	 * @param bool $includeMessages Whether or not messages should be included in the output
	 *
	 * @return mixed        Array of the patron's fines on success, AspenError
	 * otherwise.
	 * @access public
	 */
	public function getFines($patron, $includeMessages = false)
	{
		$fines = $this->driver->getFines($patron, $includeMessages);
		foreach ($fines as &$fine){
			if (!array_key_exists('canPayFine', $fine)){
				$fine['canPayFine'] = true;
			}
		}
		return $fines;
	}

	/**
	 * Get Reading History
	 *
	 * This is responsible for retrieving a history of checked out items for the patron.
	 *
	 * @param User $patron The patron array
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @param string $filter
	 * @param bool $forExport
	 * @return  array               Array of the patron's reading list
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function getReadingHistory($patron, $page = 1, $recordsPerPage = 20, $sortOption = "checkedOut", $filter = "", $forExport = false)
	{
		global $timer;
		global $offlineMode;
		$timer->logTime("Starting to load reading history");

		//Get reading history from the database unless we specifically want to load from the driver.
		$result = array('historyActive' => $patron->trackReadingHistory, 'titles' => array(), 'numTitles' => 0);
		if (!$patron->trackReadingHistory) {
			return $result;
		}
		if (!$offlineMode) {
			if (!$patron->initialReadingHistoryLoaded) {
				if ($this->driver->hasNativeReadingHistory()) {
					//Load existing reading history from the ILS
					$result = $this->driver->getReadingHistory($patron, -1, -1, $sortOption);
					if ($result['numTitles'] > 0) {
						foreach ($result['titles'] as $title) {
							if ($title['permanentId'] != null) {
								$userReadingHistoryEntry = new ReadingHistoryEntry();
								$userReadingHistoryEntry->userId = $patron->id;
								$userReadingHistoryEntry->groupedWorkPermanentId = $title['permanentId'];
								$userReadingHistoryEntry->source = $this->accountProfile->recordSource;
								$userReadingHistoryEntry->sourceId = $title['recordId'];
								$userReadingHistoryEntry->title = substr($title['title'], 0, 150);
								$userReadingHistoryEntry->author = substr($title['author'], 0, 75);
								$userReadingHistoryEntry->format = $title['format'];
								$userReadingHistoryEntry->checkOutDate = $title['checkout'];
								if (!empty($title['checkin'])) {
									$userReadingHistoryEntry->checkInDate = $title['checkin'];
								} else {
									$userReadingHistoryEntry->checkInDate = null;
								}
								$userReadingHistoryEntry->deleted = 0;
								$userReadingHistoryEntry->insert();
							}
						}
					}
					$timer->logTime("Finished loading native reading history");
				}
				$patron->initialReadingHistoryLoaded = true;
				$patron->update();
			}
			//Update the reading history based on titles that the patron currently has checked out if we are on the first page.
			if ($page == 1 && empty($filter)) {
				$this->updateReadingHistoryBasedOnCurrentCheckouts($patron);
				$timer->logTime("Finished updating reading history based on current checkouts");
			}
		}

		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryDB = new ReadingHistoryEntry();
		$readingHistoryDB->userId = $patron->id;
		$readingHistoryDB->whereAdd('deleted =  0'); //Only show titles that have not been deleted
		if (!empty($filter)) {
			$escapedFilter = $readingHistoryDB->escape('%' . $filter . '%');
			$readingHistoryDB->whereAdd("title LIKE $escapedFilter OR author LIKE $escapedFilter OR format LIKE $escapedFilter");
		}
		$readingHistoryDB->selectAdd();
		$readingHistoryDB->selectAdd('groupedWorkPermanentId');
		$readingHistoryDB->selectAdd('MAX(title) as title');
		$readingHistoryDB->selectAdd('MAX(author) as author');
		$readingHistoryDB->selectAdd('MAX(checkInDate) as checkInDate');
		$readingHistoryDB->selectAdd('MAX(checkOutDate) as checkOutDate');
		$readingHistoryDB->selectAdd('SUM(CASE WHEN checkInDate IS NULL THEN 1 END) as checkedOut');
		$readingHistoryDB->selectAdd('COUNT(id) as timesUsed');
		$readingHistoryDB->selectAdd('GROUP_CONCAT(DISTINCT(format)) as format');
		if ($sortOption == "checkedOut") {
			$readingHistoryDB->orderBy('checkedOut DESC, MAX(checkOutDate) DESC, title ASC');
		} else if ($sortOption == "returned") {
			$readingHistoryDB->orderBy('checkInDate DESC, title ASC');
		} else if ($sortOption == "title") {
			$readingHistoryDB->orderBy('title ASC, MAX(checkOutDate) DESC');
		} else if ($sortOption == "author") {
			$readingHistoryDB->orderBy('author ASC, title ASC, MAX(checkOutDate) DESC');
		} else if ($sortOption == "format") {
			$readingHistoryDB->orderBy('format ASC, title ASC, MAX(checkOutDate) DESC');
		}
		$readingHistoryDB->groupBy(['groupedWorkPermanentId']);

		$numTitles = $readingHistoryDB->count();

		if ($recordsPerPage != -1) {
			$firstIndex = ($page - 1) * $recordsPerPage;
			$readingHistoryDB->limit($firstIndex, $recordsPerPage);
		} else {
			$firstIndex = 0;
		}
		$readingHistoryDB->find();
		$readingHistoryTitles = array();

		while ($readingHistoryDB->fetch()) {
			$historyEntry = $this->getHistoryEntryForDatabaseEntry($readingHistoryDB, $forExport);
			$historyEntry['index'] = ++$firstIndex;
			$readingHistoryTitles[] = $historyEntry;
		}
		$timer->logTime("Loaded " . count($readingHistoryTitles) . " titles from the reading history");

		return array('historyActive' => $patron->trackReadingHistory, 'titles' => $readingHistoryTitles, 'numTitles' => $numTitles);
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param User $patron The user to do the reading history action on
	 * @param string $action The action to perform
	 * @param array $selectedTitles The titles to do the action on if applicable
	 * @return array success and message are the array keys
	 */
	function doReadingHistoryAction($patron, $action, $selectedTitles)
	{
		$result = [
			'success' => false,
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
		];
		if ($action == 'deleteMarked') {
			//Remove titles from database (do not remove from ILS)
			$numDeleted = 0;
			foreach ($selectedTitles as $id => $titleId) {
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $patron->id;
				$readingHistoryDB->groupedWorkPermanentId = strtolower($id);
				$readingHistoryDB->find();
				if ($id && $readingHistoryDB->getNumResults() > 0) {
					while ($readingHistoryDB->fetch()) {
						$readingHistoryDB->deleted = 1;
						$readingHistoryDB->update();
						$numDeleted++;
					}
				} else {
					$readingHistoryDB = new ReadingHistoryEntry();
					$readingHistoryDB->userId = $patron->id;
					$readingHistoryDB->id = str_replace('rsh', '', $titleId);
					if ($readingHistoryDB->find(true)) {
						$readingHistoryDB->deleted = 1;
						$readingHistoryDB->update();
						$numDeleted++;
					}
				}
			}
			$result['success'] = true;
			$result['message'] = translate(['text' => 'Deleted %1% entries from Reading History.', 1 => $numDeleted, 'isPublicFacing'=>true]);
		} elseif ($action == 'deleteAll') {
			//Remove all titles from database (do not remove from ILS)
			$readingHistoryDB = new ReadingHistoryEntry();
			$readingHistoryDB->userId = $patron->id;
			$readingHistoryDB->find();
			while ($readingHistoryDB->fetch()) {
				$readingHistoryDB->deleted = 1;
				$readingHistoryDB->update();
			}
			$result['success'] = true;
			$result['message'] = translate(['text' => 'Deleted all entries from Reading History.', 'isPublicFacing'=>true]);
		} elseif ($action == 'optOut') {
			//Delete the reading history (permanently this time since we are opting out)
			$readingHistoryDB = new ReadingHistoryEntry();
			$readingHistoryDB->userId = $patron->id;
			$readingHistoryDB->delete(true);

			//Opt out within Aspen since the ILS does not seem to implement this functionality
			$patron->trackReadingHistory = false;

			//Do not unmark that the initial reading history was loaded to avoid reloading if the ILS does track it.
			//TODO: Remove everything from the ILS when available.
			//$patron->initialReadingHistoryLoaded = false;
			$patron->update();
			$result['success'] = true;
			$result['message'] = translate(['text' => 'You have been opted out of tracking Reading History', 'isPublicFacing'=>true]);
		} elseif ($action == 'optIn') {
			//Opt in within Aspen since the ILS does not seem to implement this functionality
			$patron->trackReadingHistory = true;
			$patron->update();

			$result['success'] = true;
			$result['message'] = translate(['text' => 'You have been opted out in to tracking Reading History', 'isPublicFacing'=>true]);
		}
		if ($this->driver->performsReadingHistoryUpdatesOfILS()) {
			$this->driver->doReadingHistoryAction($patron, $action, $selectedTitles);
		}
		return $result;
	}

	/**
	 * @param User $patron
	 * @param string $title
	 * @param string $author
	 *
	 * @return array
	 */
	function deleteReadingHistoryEntryByTitleAuthor($patron, $title, $author){
		$numDeleted = 0;

		$readingHistoryDB = new ReadingHistoryEntry();
		$readingHistoryDB->userId = $patron->id;
		$readingHistoryDB->title = $title;
		$readingHistoryDB->author = $author;
		$readingHistoryDB->find();
		if ($readingHistoryDB->getNumResults() > 0) {
			while ($readingHistoryDB->fetch()) {
				$readingHistoryDB->deleted = 1;
				$readingHistoryDB->update();
				$numDeleted++;
			}
		}

		$result['success'] = true;
		$result['message'] = translate(['text' => 'Deleted %1% entries from Reading History.', 1 => $numDeleted, 'isPublicFacing'=>true]);

		return $result;
	}


	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds($user)
	{
		return $this->driver->getHolds($user);
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param string $cancelDate
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch, $cancelDate = null)
	{
		$result = $this->driver->placeHold($patron, $recordId, $pickupBranch, $cancelDate);
		if ($result['success'] == true) {
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
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $itemId The id of the item to hold
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param null|string $cancelDate The date to automatically cancel the hold if not filled
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate = null)
	{
		return $this->driver->placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate);
	}

	function updatePatronInfo($user, $canUpdateContactInfo, $fromMasquerade = false)
	{
		return $this->driver->updatePatronInfo($user, $canUpdateContactInfo, $fromMasquerade);
	}

	/**
	 * @param User $user
	 * @param string $homeLibraryCode
	 * @return array
	 */
	function updateHomeLibrary($user, $homeLibraryCode)
	{
		$result = $this->driver->updateHomeLibrary($user, $homeLibraryCode);
		if ($result['success']){
			$location = new Location();
			$location->code = $homeLibraryCode;
			if ($location->find(true)){
				$user->homeLocationId = $location->locationId;
				$user->_homeLocationCode = $homeLibraryCode;
				$user->_homeLocation = $location;
				$user->update();
			}
		}
		return $result;
	}

	function selfRegister()
	{
		$result = $this->driver->selfRegister();
		if ($result['success'] == true){
			//Track usage by the user
			require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
			$userUsage = new UserILSUsage();
			$userUsage->userId = -1;
			$userUsage->indexingProfileId = $this->driver->getIndexingProfile()->id;
			$userUsage->year = date('Y');
			$userUsage->month = date('n');

			if ($userUsage->find(true)) {
				$userUsage->selfRegistrationCount++;
				$userUsage->update();
			} else {
				$userUsage->selfRegistrationCount = 1;
				$userUsage->insert();
			}
		}
		return $result;
	}

	/**
	 * Default method -- pass along calls to the driver if available; return
	 * false otherwise.  This allows custom functions to be implemented in
	 * the driver without constant modification to the connection class.
	 *
	 * @param string $methodName The name of the called method.
	 * @param array $params Array of passed parameters.
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

	public function getSelfRegistrationFields()
	{
		return $this->driver->getSelfRegistrationFields();
	}

	/**
	 * @param ReadingHistoryEntry $readingHistoryDB
	 * @param bool $forExport True if this is being ysed while exporting to Excel
	 * @return mixed
	 */
	public function getHistoryEntryForDatabaseEntry($readingHistoryDB, $forExport = false)
	{
		$historyEntry = array();

		$historyEntry['title'] = $readingHistoryDB->title;
		$historyEntry['author'] = $readingHistoryDB->author;
		$historyEntry['format'] = $readingHistoryDB->format;
		$historyEntry['checkout'] = $readingHistoryDB->checkOutDate;
		$historyEntry['checkin'] = $readingHistoryDB->checkInDate;
		/** @noinspection PhpUndefinedFieldInspection */
		$historyEntry['timesUsed'] = $readingHistoryDB->timesUsed;
		/** @noinspection PhpUndefinedFieldInspection */
		$historyEntry['checkedOut'] = $readingHistoryDB->checkedOut == null ? false : true;
		$historyEntry['permanentId'] = $readingHistoryDB->groupedWorkPermanentId;
		if (!$forExport) {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$recordDriver = new GroupedWorkDriver($readingHistoryDB->groupedWorkPermanentId);

			if ($recordDriver->isValid()) {
				$historyEntry['recordDriver'] = $recordDriver;
				$historyEntry['ratingData'] = $recordDriver->getRatingData();
				$historyEntry['linkUrl'] = $recordDriver->getLinkUrl();
				$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('small');
				$historyEntry['existsInCatalog'] = true;
			} else {
				$historyEntry['existsInCatalog'] = false;
				$historyEntry['ratingData'] = '';
				$historyEntry['linkUrl'] = '';
				$historyEntry['coverUrl'] = '';
			}
		}

		return $historyEntry;
	}

	/**
	 * @param User $patron
	 */
	public function updateReadingHistoryBasedOnCurrentCheckouts($patron)
	{
		//Check to see if we need to update the reading history.  Only update every 5 minutes in normal situations.
		$curTime = time();
		if ((($curTime - $patron->lastReadingHistoryUpdate) < 60 * 5) && !isset($_REQUEST['reload'])){
			return;
		}

		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		//Note, include deleted titles here so they are not added multiple times.
		$readingHistoryDB = new ReadingHistoryEntry();
		$readingHistoryDB->userId = $patron->id;
		$readingHistoryDB->whereAdd('checkInDate IS NULL');
		$readingHistoryDB->find();

		$activeHistoryTitles = array();
		global $logger;
		while ($readingHistoryDB->fetch()) {
			$historyEntry = [];
			$historyEntry['source'] = $readingHistoryDB->source;
			$historyEntry['sourceId'] = $readingHistoryDB->sourceId;
			$historyEntry['ids'] = [];
			$historyEntry['ids'][] = $readingHistoryDB->id;
			$key = strtolower($historyEntry['source'] . ':' . $historyEntry['sourceId']);
			if (array_key_exists($key, $activeHistoryTitles)){
				if (IPAddress::showDebuggingInformation()){
					$logger->log("Adding {$readingHistoryDB->id} to active history entry $key.", Logger::LOG_ERROR);
				}
				$activeHistoryTitles[$key]['ids'][] = $readingHistoryDB->id;
			}else{
				if (IPAddress::showDebuggingInformation()){
					$logger->log("Adding new record $key, {$readingHistoryDB->id} to active history entries.", Logger::LOG_ERROR);
				}
				$activeHistoryTitles[$key] = $historyEntry;
			}
		}

		//Update reading history based on current checkouts.  That way it never looks out of date
		$checkouts = $patron->getCheckouts(false, 'all');
		foreach ($checkouts as $checkout) {
			$source = $checkout->source;
			$sourceId = $checkout->sourceId;
			$key = strtolower($source . ':' . $sourceId);
			if (array_key_exists($key, $activeHistoryTitles)) {
				unset($activeHistoryTitles[$key]);
			} else {
				$historyEntryDB = new ReadingHistoryEntry();
				$historyEntryDB->userId = $patron->id;
				if (!empty($checkout->groupedWorkId)) {
					$historyEntryDB->groupedWorkPermanentId = $checkout->groupedWorkId;
				} else {
					$historyEntryDB->groupedWorkPermanentId = "";
				}

				$historyEntryDB->source = $source;
				$historyEntryDB->sourceId = $sourceId;
				$historyEntryDB->title = StringUtils::trimStringToLengthAtWordBoundary($checkout->title, 150, true);
				$historyEntryDB->author = isset($checkout->author) ? StringUtils::trimStringToLengthAtWordBoundary($checkout->author, 75, true) : "";
				$historyEntryDB->format = substr($checkout->format, 0, 50);
				$historyEntryDB->checkOutDate = time();
				if (!$historyEntryDB->insert()) {
					global $logger;
					$logger->log("Could not insert new reading history entry", Logger::LOG_ERROR);
				}
			}
		}

		//Anything that was still active is now checked in
		global $logger;
		if (IPAddress::showDebuggingInformation()){
			$logger->log("There are " . count($activeHistoryTitles) . " titles that have been checked in.", Logger::LOG_ERROR);
		}
		foreach ($activeHistoryTitles as $historyEntry) {
			if (IPAddress::showDebuggingInformation()){
				$logger->log("There are " . count($historyEntry['ids']) . " ids for this history entry.", Logger::LOG_ERROR);
			}
			//Update even if deleted to make sure code is cleaned up correctly
			foreach ($historyEntry['ids'] as $id) {
				$historyEntryDB = new ReadingHistoryEntry();
				$historyEntryDB->id = $id;
				if ($historyEntryDB->find(true)) {
					$historyEntryDB->checkInDate = time();
					$numUpdates = $historyEntryDB->update();
					if (IPAddress::showDebuggingInformation()){
						if ($numUpdates != 1) {
							$logger->log("Could not update reading history entry $id", Logger::LOG_ERROR);
						}else{
							$logger->log("Marked $id as checked in.", Logger::LOG_ERROR);
						}
					}
				}
			}
		}

		//Set the last update time
		$patron->lastReadingHistoryUpdate = $curTime;
		$patron->update();
	}

	function cancelHold($patron, $recordId, $cancelId = null)
	{
		return $this->driver->cancelHold($patron, $recordId, $cancelId);
	}

	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate)
	{
		return $this->driver->freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate);
	}

	function thawHold($patron, $recordId, $itemToThawId)
	{
		return $this->driver->thawHold($patron, $recordId, $itemToThawId);
	}

	function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation)
	{
		return $this->driver->changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation);
	}

	public function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		return $this->driver->renewCheckout($patron, $recordId, $itemId, $itemIndex);
	}

	public function renewAll(User $patron)
	{
		if ($this->driver->hasFastRenewAll()) {
			return $this->driver->renewAll($patron);
		} else {
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
			foreach ($currentTransactions as $transaction) {
				$curResult = $this->renewCheckout($patron, $transaction->recordId, $transaction->renewIndicator, null);
				if ($curResult['success']) {
					$numRenewals++;
				} else {
					if ($curResult['message'] == 'The item could not be renewed'){
						require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
						$failure_messages[] = StringUtils::removeTrailingPunctuation($transaction->title) . ' could not be renewed';
					}
				}
			}
			$renewResult['Renewed'] += $numRenewals;
			$renewResult['NotRenewed'] = $renewResult['Total'] - $renewResult['Renewed'];
			if ($renewResult['NotRenewed'] > 0) {
				$renewResult['success'] = false;
				$renewResult['message'] = $failure_messages;
			} else {
				$renewResult['message'][] = "All items were renewed successfully.";
			}
			return $renewResult;
		}
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch)
	{
		return $this->driver->placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch);
	}

	public function importListsFromIls($patron)
	{
		return $this->driver->importListsFromIls($patron);
	}

	/**
	 * Resets the PIN/Password.  At this point, the confirmation matches the new pin so no need to reconfirm
	 * @param User $user
	 * @param string $oldPin
	 * @param string $newPin
	 * @return string[] a message to the user letting them know what happened
	 */
	function updatePin(User $user, string $oldPin, string $newPin)
	{
		$result = $this->driver->updatePin($user, $oldPin, $newPin);
		if ($result['success']) {
			$user->disableLinkingDueToPasswordChange();
		}
		return $result;
	}

	function showOutstandingFines()
	{
		return $this->driver->showOutstandingFines();
	}

	/**
	 * Returns one of four values
	 * - none - No forgot password functionality exists
	 * - emailResetLink - A link to reset the pin is emailed to the user
	 * - emailPin - The pin itself is emailed to the user
	 * - emailAspenResetLink - A link to reset the pin is emailed to the user.  Reset happens within Aspen.
	 * @return string
	 */
	function getForgotPasswordType()
	{
		if ($this->driver == null) {
			return null;
		}
		return $this->driver->getForgotPasswordType();
	}

	public function getEmailResetPinTemplate()
	{
		global $interface;
		global $library;

		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));

		if (isset($_REQUEST['email'])){
			$interface->assign('email', $_REQUEST['email']);
		}
		if (isset($_REQUEST['barcode'])){
			$interface->assign('barcode', $_REQUEST['barcode']);
		}
		if (isset($_REQUEST['username'])){
			$interface->assign('username', $_REQUEST['username']);
		}
		if (isset($_REQUEST['resendEmail'])){
			$interface->assign('resendEmail', $_REQUEST['resendEmail']);
		}

		if ($this->getForgotPasswordType() == 'emailAspenResetLink'){
			return 'aspenEmailResetPinLink.tpl';
		}else{
			return $this->driver->getEmailResetPinTemplate();
		}
	}

	function processEmailResetPinForm()
	{
		if ($this->getForgotPasswordType() == 'emailAspenResetLink') {
			$result = array(
				'success' => false,
				'error' => translate(['text' => "Unknown error sending password reset.", 'isPublicFacing'=>true])
			);

			//Get the user from the driver
			if (empty($_REQUEST['reset_username'])){
				$result['error'] = translate(['text' => "Barcode not provided. You must provide a barcode to use password reset.", 'isPublicFacing'=>true]);
			}else{
				$barcode = $_REQUEST['reset_username'];
				$userToResetPin = new User();
				$barcodeProperty = $this->accountProfile->loginConfiguration == 'barcode_pin' ? 'cat_username' : 'cat_password';
				$userToResetPin->$barcodeProperty = $barcode;
				if (!$userToResetPin->find(true)){
					$userToResetPin = $this->driver->findNewUser($barcode);
				}
				if ($userToResetPin == false){
					$result['error'] = translate(['text' => "Could not find a patron with that barcode, please contact the library.", 'isPublicFacing'=>true]);
				}else{
					if (empty($userToResetPin->email)){
						$result['error'] = translate(['text' => "That account does not have an email associated with it, please contact the library.", 'isPublicFacing'=>true]);
					}else{
						require_once ROOT_DIR . '/sys/Account/PinResetToken.php';
						$pinResetToken = new PinResetToken();
						$pinResetToken->userId = $userToResetPin->id;
						$pinResetToken->generateToken();
						$pinResetToken->dateIssued = time();
						if ($pinResetToken->insert()){
							require_once ROOT_DIR . '/sys/Email/Mailer.php';
							$mailer = new Mailer();

							global $configArray;
							$resetUrl = $configArray['Site']['url'] . '/MyAccount/CompletePinReset?token=' . $pinResetToken->token;
							$subject = translate(['text' => 'Reset PIN', 'isPublicFacing'=> true]);
							$body = translate(['text' => 'Hi %1%,', 1 => $userToResetPin->firstname, 'isPublicFacing'=> true]);
							$body .= "\r\n" . translate(['text' => 'It looks like you forgot your PIN. Click on the link or copy/paste the URL below into a browser to reset your password. This link will only work for 60 minutes, after that you’ll have to request a new link.', 'isPublicFacing'=> true]);
							$body .= "\r\n\r\n" . $resetUrl;
							$body .= "\r\n" . translate(['text' => 'You can also paste the following code into the page where you generated the reset.', 'isPublicFacing'=> true]);
							$body .= "\r\n\r\n" . $pinResetToken->token;

							$htmlBody = "<html></html><table><tr><td>" . translate(['text' => 'Hi %1%,', 1 => $userToResetPin->firstname, 'isPublicFacing'=> true]) . '<br/>';
							$htmlBody .= translate(['text' => 'It looks like you forgot your PIN. Click on the button or copy/paste the URL below into a browser to reset your password. This link will only work for 60 minutes, after that you’ll have to request a new link', 'isPublicFacing'=> true, 1 => $userToResetPin->firstname]) . "</td>";
							$htmlBody .= "<tr><td style='text-align: center'><a href='{$resetUrl}'>" . translate(['text' => 'CREATE NEW PIN', 'isPublicFacing'=> true]) . '</a></td></tr>';
							$htmlBody .= '<tr><td></td></tr>';
							$htmlBody .= "<tr><td style='text-align: center'>" . translate(['text' => 'Reset Token', 'isPublicFacing'=> true]) . '</tr>';
							$htmlBody .= "<tr><td style='text-align: center'>" . $pinResetToken->token . '</td></tr>';
							$htmlBody .= '</table></html>';


							if ($mailer->send($userToResetPin->email, $subject, $body, null, $htmlBody)){
								$result['success'] = true;
								$result['message'] = translate(['text' => "The email with your PIN reset link was sent. Please click on the link within that email or enter the code below.", 'isPublicFacing'=>true]);
							}else{
								$result['error'] = translate(['text' => "The email with your PIN reset link could not be sent, please contact the library.", 'isPublicFacing'=>true]);
							}
						}else{
							$result['error'] = translate(['text' => "Could not generate PIN reset token.", 'isPublicFacing'=>true]);
						}
					}
				}
			}

			return $result;
		}else{
			return $this->driver->processEmailResetPinForm();
		}
	}

	function hasMaterialsRequestSupport()
	{
		return $this->driver->hasMaterialsRequestSupport();
	}

	function getNewMaterialsRequestForm(User $user)
	{
		return $this->driver->getNewMaterialsRequestForm($user);
	}

	function processMaterialsRequestForm($user)
	{
		return $this->driver->processMaterialsRequestForm($user);
	}

	function getNumMaterialsRequests(User $user): int
	{
		return $this->driver->getNumMaterialsRequests($user);
	}

	/** @noinspection PhpUnused */
	function getMaterialsRequests(User $user)
	{
		return $this->driver->getMaterialsRequests($user);
	}

	function getMaterialsRequestsPage(User $user)
	{
		return $this->driver->getMaterialsRequestsPage($user);
	}

	function deleteMaterialsRequests(User $user)
	{
		return $this->driver->deleteMaterialsRequests($user);
	}

	function getPatronUpdateForm(User $user)
	{
		return $this->driver->getPatronUpdateForm($user);
	}

	public function getAccountSummary(User $user)
	{
		list($existingId, $summary) = $user->getCachedAccountSummary('ils');

		if ($summary === null || isset($_REQUEST['reload'])) {
			$summary = $this->driver->getAccountSummary($user);
			$summary->lastLoaded = time();
			$summary->id = $existingId;
			$summary->update();
		}
		return $summary;
	}

	/**
	 * @return bool
	 */
	public function showMessagingSettings() : bool
	{
		if ($this->driver == null) {
			return false;
		}
		return $this->driver->showMessagingSettings();
	}

	/**
	 * @param User $user
	 * @return string
	 */
	public function getMessagingSettingsTemplate(User $user) : ?string
	{
		return $this->driver->getMessagingSettingsTemplate($user);
	}

	public function processMessagingSettingsForm(User $user) : array
	{
		return $this->driver->processMessagingSettingsForm($user);
	}

	public function completeFinePayment(User $patron, UserPayment $payment)
	{
		$result = $this->driver->completeFinePayment($patron, $payment);
		$patron->clearCachedAccountSummaryForSource($this->driver->getIndexingProfile()->name);
		return $result;
	}

	public function patronEligibleForHolds(User $patron)
	{
		if (empty($this->driver)){
			return false;
		}
		return $this->driver->patronEligibleForHolds($patron);
	}

	public function getShowAutoRenewSwitch(User $patron)
	{
		if (empty($this->driver)){
			return false;
		}
		return $this->driver->getShowAutoRenewSwitch($patron);
	}

	public function isAutoRenewalEnabledForUser(User $patron)
	{
		if (empty($this->driver)){
			return false;
		}
		return $this->driver->isAutoRenewalEnabledForUser($patron);
	}

	public function updateAutoRenewal(User $patron, bool $allowAutoRenewal)
	{
		return $this->driver->updateAutoRenewal($patron, $allowAutoRenewal);
	}

	public function getPasswordRecoveryTemplate()
	{
		return $this->driver->getPasswordRecoveryTemplate();
	}

	public function processPasswordRecovery()
	{
		return $this->driver->processPasswordRecovery();
	}

	public function getEmailResetPinResultsTemplate()
	{
		if ($this->getForgotPasswordType() == 'emailAspenResetLink') {
			return 'aspenEmailResetPinResults.tpl';
		}else{
			return $this->driver->getEmailResetPinResultsTemplate();
		}
	}

	function getPasswordPinValidationRules(){
		return $this->driver->getPasswordPinValidationRules();
	}

	/**
	 * @param $barcode
	 * @param string|null $password
	 * @param string|null $username
	 * @return User|null
	 */
	protected function getUserFromDatabase($barcode, $password, $username)
	{
		global $timer;
		global $logger;
		$user = new User();
		if ($this->driver->accountProfile->loginConfiguration == 'barcode_pin') {
			$user->cat_username = $barcode;
		} else {
			$user->cat_password = $barcode;
		}
		if ($user->find(true)) {
			if ($this->driver->accountProfile->loginConfiguration = 'barcode_pin') {
				//We load the account based on the barcode make sure the pin matches
				$userValid = $password != null && $user->cat_password == $password;
			} else {
				//We still load based on barcode, make sure the username is similar
				$userValid = $this->areNamesSimilar($username, $user->cat_username);
			}
			if (!$userValid) {
				$timer->logTime("offline patron login failed due to invalid name");
				$logger->log("offline patron login failed due to invalid name", Logger::LOG_NOTICE);
				$user = null;
			}
		} else {
			$timer->logTime("offline patron login failed because we haven't seen this user before");
			$logger->log("offline patron login failed because we haven't seen this user before", Logger::LOG_NOTICE);
			$user = null;
		}
		return $user;
	}

	public function hasEditableUsername()
	{
		return $this->driver->hasEditableUsername();
	}

	public function getEditableUsername(User $user)
	{
		return $this->driver->getEditableUsername($user);
	}

	public function updateEditableUsername(User $user, $username)
	{
		return $this->driver->updateEditableUsername($user, $username);
	}

	public function logout(User $user)
	{
		$this->driver->logout($user);
		$user->lastLoginValidation = 0;
		$user->update();
	}

	public function getHoldsReportData($location) {
		return $this->driver->getHoldsReportData($location);
	}

	public function getStudentReportData($location,$showOverdueOnly,$date) {
		return $this->driver->getStudentReportData($location,$showOverdueOnly,$date);
	}

	/**
	 * Loads any contact information that is not stored by Aspen Discovery from the ILS. Updates the user object.
	 *
	 * @param User $user
	 */
	public function loadContactInformation(User $user)
	{
		$this->driver->loadContactInformation($user);
	}

	public function getILSMessages(User $user)
	{
		return $this->driver->getILSMessages($user);
	}

	public function confirmHold(User $user, $recordId, $confirmationId)
	{
		return $this->driver->confirmHold($user, $recordId, $confirmationId);
	}

	public function treatVolumeHoldsAsItemHolds() {
		return $this->driver->treatVolumeHoldsAsItemHolds();
	}

	public function getPluginStatus(string $pluginName) {
		return $this->driver->getPluginStatus($pluginName);
	}

	public function getCurbsidePickupSettings($locationCode)
	{
		return $this->driver->getCurbsidePickupSettings($locationCode);
	}

	public function hasCurbsidePickups($user)
	{
		return $this->driver->hasCurbsidePickups($user);
	}

	public function getPatronCurbsidePickups($user)
	{
		return $this->driver->getPatronCurbsidePickups($user);
	}

	public function newCurbsidePickup($user, $pickupLocation, $pickupTime, $pickupNote)
	{
		return $this->driver->newCurbsidePickup($user, $pickupLocation, $pickupTime, $pickupNote);
	}

	public function cancelCurbsidePickup($user, $pickupId)
	{
		return $this->driver->cancelCurbsidePickup($user, $pickupId);
	}

	public function checkInCurbsidePickup($user, $pickupId)
	{
		return $this->driver->checkInCurbsidePickup($user, $pickupId);
	}

	public function getAllCurbsidePickups()
	{
		return $this->driver->getAllCurbsidePickups();
	}

	public function isPromptForHoldNotifications() : bool
	{
		return $this->driver->isPromptForHoldNotifications();
	}

	public function getHoldNotificationTemplate() : ?string
	{
		return $this->driver->getHoldNotificationTemplate();
	}

	public function validateUniqueId(User $user){
		$this->driver->validateUniqueId($user);
	}
}