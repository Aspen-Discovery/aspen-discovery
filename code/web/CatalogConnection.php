<?php
/**
 * Catalog Connection Class
 *
 * This wrapper works with a driver class to pass information from the ILS to
 * VuFind.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */

/**
 * Catalog Connection Class
 *
 * This wrapper works with a driver class to pass information from the ILS to
 * VuFind.
 *
 * @category VuFind
 * @package  ILS_Drivers
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 */
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
	 * @var    Millennium|DriverInterface
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
		if (is_readable($path) && $driver != 'DriverInterface') {
			require_once $path;

			try {
				$this->driver = new $driver($accountProfile);
			} catch (PDOException $e) {
				global $logger;
				$logger->log("Unable to create driver $driver for account profile {$accountProfile->name}", PEAR_LOG_ERR);
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
	 * Get Holding
	 *
	 * This is responsible for retrieving the holding information of a certain
	 * record.
	 *
	 * @param string $recordId The record id to retrieve the holdings for
	 * @param array  $patron   Optional Patron details to determine if a user can
	 * place a hold or recall on an item
	 *
	 * @return mixed     On success, an associative array with the following keys:
	 * id, availability (boolean), status, location, reserve, callnumber, dueDate,
	 * number, barcode; on failure, a PEAR_Error.
	 * @access public
	 */
	public function getHolding($recordId, $patron = false)
	{
		$holding = $this->driver->getHolding($recordId, $patron);

		// Validate return from driver's getHolding method -- should be an array or
		// an error.  Anything else is unexpected and should become an error.
		if (!is_array($holding) && !PEAR_Singleton::isError($holding)) {
			return new PEAR_Error('Unexpected return from getHolding: ' . $holding);
		}

		return $holding;
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
					$logger->log("offline patron login failed due to invalid name", PEAR_LOG_INFO);
					return null;
				}
			} else {
				$timer->logTime("offline patron login failed because we haven't seen this user before");
				$logger->log("offline patron login failed because we haven't seen this user before", PEAR_LOG_INFO);
				return null;
			}
		}else {
			$user = $this->driver->patronLogin($username, $password, $validatedViaSSO);
		}

		if ($user && !PEAR_Singleton::isError($user)){
			if ($user->displayName == '') {
				if ($user->firstname == ''){
					$user->displayName = $user->lastname;
				}else{
					// #PK-979 Make display name configurable firstname, last initial, vs first initial last name
					$homeLibrary = $user->getHomeLibrary();
					if ($homeLibrary == null || ($homeLibrary->patronNameDisplayStyle == 'firstinitial_lastname')){
						// #PK-979 Make display name configurable firstname, last initial, vs first initial last name
						$user->displayName = substr($user->firstname, 0, 1) . '. ' . $user->lastname;
					}elseif ($homeLibrary->patronNameDisplayStyle == 'lastinitial_firstname'){
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
		require_once(ROOT_DIR . '/Drivers/OverDriveDriverFactory.php');
		$overDriveDriver = OverDriveDriverFactory::getDriver();
		if ($user->isValidForOverDrive() && $overDriveDriver->isUserValidForOverDrive($user)){
			$overDriveSummary = $overDriveDriver->getOverDriveSummary($user);
			$user->setNumCheckedOutOverDrive($overDriveSummary['numCheckedOut']);
			$user->setNumHoldsAvailableOverDrive($overDriveSummary['numAvailableHolds']);
			$user->setNumHoldsRequestedOverDrive($overDriveSummary['numUnavailableHolds']);
			$timer->logTime("Updated runtime information from OverDrive");
		}

		if ($user->isValidForHoopla()){
			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();
			$hooplaSummary = $driver->getHooplaPatronStatus($user);
			$hooplaCheckOuts = isset($hooplaSummary->currentlyBorrowed) ? $hooplaSummary->currentlyBorrowed : 0;
			$user->setNumCheckedOutHoopla($hooplaCheckOuts);
		}

		$materialsRequest = new MaterialsRequest();
		$materialsRequest->createdBy = $user->id;
		$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
		if ($homeLibrary && $homeLibrary->enableMaterialsRequest){
			$statusQuery = new MaterialsRequestStatus();
			$statusQuery->isOpen = 1;
			$statusQuery->libraryId = $homeLibrary->libraryId;
			$materialsRequest->joinAdd($statusQuery);
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
			$readingHistoryDB->find();
			$user->setReadingHistorySize($readingHistoryDB->N);
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
	 * PEAR_Error otherwise.
	 * @access public
	 */
	public function getMyCheckouts($user)
	{
		$transactions = $this->driver->getMyCheckouts($user);
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
	 * @param array $patron The patron array from patronLogin
	 *
	 * @return mixed        Array of the patron's fines on success, PEAR_Error
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
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function getReadingHistory($patron, $page = 1, $recordsPerPage = -1, $sortOption = "checkedOut"){
		//Get reading history from the database unless we specifically want to load from the driver.
		if (($patron->trackReadingHistory && $patron->initialReadingHistoryLoaded) || !$this->driver->hasNativeReadingHistory()){
			if ($patron->trackReadingHistory){
				//Make sure initial reading history loaded is set to true if we are here since
				//The only way it wouldn't be here is if the user has elected to start tracking reading history
				//And they don't have reading history currently specified.  We get what is checked out below though
				//So that takes care of the initial load
				if (!$patron->initialReadingHistoryLoaded){
					//Load the initial reading history
					$patron->initialReadingHistoryLoaded = 1;
					$patron->update();
				}

				$this->updateReadingHistoryBasedOnCurrentCheckouts($patron);

				require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $patron->id;
				$readingHistoryDB->deleted = 0; //Only show titles that have not been deleted
				$readingHistoryDB->selectAdd('MAX(checkOutDate) as checkOutDate');
				$readingHistoryDB->selectAdd('GROUP_CONCAT(DISTINCT(format)) as format');
				if ($sortOption == "checkedOut"){
					$readingHistoryDB->orderBy('checkOutDate DESC, title ASC');
				}else if ($sortOption == "returned"){
					$readingHistoryDB->orderBy('checkInDate DESC, title ASC');
				}else if ($sortOption == "title"){
					$readingHistoryDB->orderBy('title ASC, checkOutDate DESC');
				}else if ($sortOption == "author"){
					$readingHistoryDB->orderBy('author ASC, title ASC, checkOutDate DESC');
				}else if ($sortOption == "format"){
					$readingHistoryDB->orderBy('format ASC, title ASC, checkOutDate DESC');
				}
				$readingHistoryDB->groupBy('groupedWorkPermanentId');
				if ($recordsPerPage != -1){
					$readingHistoryDB->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
				}
				$readingHistoryDB->find();
				$readingHistoryTitles = array();

				while ($readingHistoryDB->fetch()){
					$historyEntry = $this->getHistoryEntryForDatabaseEntry($readingHistoryDB);

					$readingHistoryTitles[] = $historyEntry;
				}

				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $patron->id;
				$readingHistoryDB->deleted = 0;
				$readingHistoryDB->groupBy('groupedWorkPermanentId');
				$readingHistoryDB->find();
				$numTitles = $readingHistoryDB->N;

				return array('historyActive'=>$patron->trackReadingHistory, 'titles'=>$readingHistoryTitles, 'numTitles'=> $numTitles);
			}else{
				//Reading history disabled
				return array('historyActive'=>$patron->trackReadingHistory, 'titles'=>array(), 'numTitles'=> 0);
			}

		}else{
			//Don't know enough to load internally, check the ILS.
			$result = $this->driver->getReadingHistory($patron, $page, $recordsPerPage, $sortOption);

			//Do not try to mark that the initial load has been done since we only load a subset of the reading history above.

			//Sort the records
			$count = 0;
			foreach ($result['titles'] as $key => $historyEntry){
				$count++;
				if (!isset($historyEntry['title_sort'])){
					$historyEntry['title_sort'] = preg_replace('/[^a-z\s]/', '', strtolower($historyEntry['title']));
				}
				if ($sortOption == "title"){
					$titleKey = $historyEntry['title_sort'];
				}elseif ($sortOption == "author"){
					$titleKey = $historyEntry['author'] . "_" . $historyEntry['title_sort'];
				}elseif ($sortOption == "checkedOut" || $sortOption == "returned"){
					$checkoutTime = DateTime::createFromFormat('m-d-Y', $historyEntry['checkout']) ;
					if ($checkoutTime){
						$titleKey = $checkoutTime->getTimestamp() . "_" . $historyEntry['title_sort'];
					}else{
						//print_r($historyEntry);
						$titleKey = $historyEntry['title_sort'];
					}
				}elseif ($sortOption == "format"){
					$titleKey = $historyEntry['format'] . "_" . $historyEntry['title_sort'];
				}else{
					$titleKey = $historyEntry['title_sort'];
				}
				$titleKey .= '_' . ($count);
				$result['titles'][$titleKey] = $historyEntry;
				unset($result['titles'][$key]);
			}
			if ($sortOption == "checkedOut" || $sortOption == "returned"){
				krsort($result['titles']);
			}else{
				ksort($result['titles']);
			}

			return $result;
		}
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
		if (($patron->trackReadingHistory && $patron->initialReadingHistoryLoaded) || ! $this->driver->hasNativeReadingHistory()){
			if ($action == 'deleteMarked'){
				//Remove titles from database (do not remove from ILS)
				foreach ($selectedTitles as $id => $titleId){
					list($source, $sourceId) = explode('_', $titleId);
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
				$driverHasReadingHistory = $this->driver->hasNativeReadingHistory();

				//Opt out within the ILS if possible
				if ($driverHasReadingHistory && $this->checkFunction('doReadingHistoryAction')){
					//First run delete all
					$result = $this->driver->doReadingHistoryAction($patron, 'deleteAll', $selectedTitles);

					$result = $this->driver->doReadingHistoryAction($patron, $action, $selectedTitles);
				}

				//Delete the reading history (permanently this time sine we are opting out)
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $patron->id;
				$readingHistoryDB->delete();

				//Opt out within Pika since the ILS does not seem to implement this functionality
				$patron->trackReadingHistory = false;
				$patron->update();
			}elseif ($action == 'optIn'){
				$driverHasReadingHistory = $this->driver->hasNativeReadingHistory();
				//Opt in within the ILS if possible
				if ($driverHasReadingHistory){
					$result = $this->driver->doReadingHistoryAction($patron, $action, $selectedTitles);
				}

				//Opt in within Pika since the ILS does not seem to implement this functionality
				$patron->trackReadingHistory = true;
				$patron->update();
			}
		}else{
			return $this->driver->doReadingHistoryAction($patron, $action, $selectedTitles);
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
	public function getMyHolds($user) {
		$holds = $this->driver->getMyHolds($user);
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
	 *                                If an error occurs, return a PEAR_Error
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch, $cancelDate = null) {
		$result =  $this->driver->placeHold($patron, $recordId, $pickupBranch, $cancelDate);
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
	*                              If an error occurs, return a PEAR_Error
	* @access  public
	*/
	function placeItemHold($patron, $recordId, $itemId, $pickupBranch) {
		return $this->driver->placeItemHold($patron, $recordId, $itemId, $pickupBranch);
	}

	/**
	 * Get Hold Link
	 *
	 * The goal for this method is to return a URL to a "place hold" web page on
	 * the ILS OPAC. This is used for ILSs that do not support an API or method
	 * to place Holds.
	 *
	 * @param   string  $recordId   The id of the bib record
	 * @return  mixed               True if successful, otherwise return a PEAR_Error
	 * @access  public
	 */
	function getHoldLink($recordId)
	{
		return $this->driver->getHoldLink($recordId);
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
	 * Get New Items
	 *
	 * Retrieve the IDs of items recently added to the catalog.
	 *
	 * @param int $page    Page number of results to retrieve (counting starts at 1)
	 * @param int $limit   The size of each page of results to retrieve
	 * @param int $daysOld The maximum age of records to retrieve in days (max. 30)
	 * @param int $fundId  optional fund ID to use for limiting results (use a value
	 * returned by getFunds, or exclude for no limit); note that "fund" may be a
	 * misnomer - if funds are not an appropriate way to limit your new item
	 * results, you can return a different set of values from getFunds. The
	 * important thing is that this parameter supports an ID returned by getFunds,
	 * whatever that may mean.
	 *
	 * @return array       Associative array with 'count' and 'results' keys
	 * @access public
	 */
	public function getNewItems($page = 1, $limit = 20, $daysOld = 30,
	$fundId = null
	) {
		return $this->driver->getNewItems($page, $limit, $daysOld, $fundId);
	}

	/**
	 * Get Funds
	 *
	 * Return a list of funds which may be used to limit the getNewItems list.
	 *
	 * @return array An associative array with key = fund ID, value = fund name.
	 * @access public
	 */
	public function getFunds()
	{
		// Graceful degradation -- return empty fund list if no method supported.
		return method_exists($this->driver, 'getFunds') ?
		$this->driver->getFunds() : array();
	}

	/**
	 * Get Departments
	 *
	 * Obtain a list of departments for use in limiting the reserves list.
	 *
	 * @return array An associative array with key = dept. ID, value = dept. name.
	 * @access public
	 */
	public function getDepartments()
	{
		// Graceful degradation -- return empty list if no method supported.
		return method_exists($this->driver, 'getDepartments') ?
		$this->driver->getDepartments() : array();
	}

	/**
	 * Get Instructors
	 *
	 * Obtain a list of instructors for use in limiting the reserves list.
	 *
	 * @return array An associative array with key = ID, value = name.
	 * @access public
	 */
	public function getInstructors()
	{
		// Graceful degradation -- return empty list if no method supported.
		return method_exists($this->driver, 'getInstructors') ?
		$this->driver->getInstructors() : array();
	}

	/**
	 * Get Courses
	 *
	 * Obtain a list of courses for use in limiting the reserves list.
	 *
	 * @return array An associative array with key = ID, value = name.
	 * @access public
	 */
	public function getCourses()
	{
		// Graceful degradation -- return empty list if no method supported.
		return method_exists($this->driver, 'getCourses') ?
		$this->driver->getCourses() : array();
	}

	/**
	 * Find Reserves
	 *
	 * Obtain information on course reserves.
	 *
	 * @param string $course ID from getCourses (empty string to match all)
	 * @param string $inst   ID from getInstructors (empty string to match all)
	 * @param string $dept   ID from getDepartments (empty string to match all)
	 *
	 * @return mixed An array of associative arrays representing reserve items (or a
	 * PEAR_Error object if there is a problem)
	 * @access public
	 */
	public function findReserves($course, $inst, $dept)
	{
		return $this->driver->findReserves($course, $inst, $dept);
	}

	/**
	 * Process inventory for a particular item in the catalog
	 *
	 * @param string $login     Login for the user doing the inventory
	 * @param string $password1 Password for the user doing the inventory
	 * @param string $initials
	 * @param string $password2
	 * @param string[] $barcodes
	 * @param boolean $updateIncorrectStatuses
	 *
	 * @return array
	 */
	function doInventory($login, $password1, $initials, $password2, $barcodes, $updateIncorrectStatuses){
		return $this->driver->doInventory($login, $password1, $initials, $password2, $barcodes, $updateIncorrectStatuses);
	}

	/**
	 * Get suppressed records.
	 *
	 * @return array ID numbers of suppressed records in the system.
	 * @access public
	 */
	public function getSuppressedRecords()
	{
		return $this->driver->getSuppressedRecords();
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

		$historyEntry['itemindex'] = $readingHistoryDB->id;
		$historyEntry['deletable'] = true;
		$historyEntry['source'] = $readingHistoryDB->source;
		$historyEntry['id'] = $readingHistoryDB->sourceId;
		$historyEntry['recordId'] = $readingHistoryDB->sourceId;
		$historyEntry['shortId'] = $readingHistoryDB->sourceId;
		$historyEntry['title'] = $readingHistoryDB->title;
		$historyEntry['author'] = $readingHistoryDB->author;
		$historyEntry['format'] = $readingHistoryDB->format;
		$historyEntry['checkout'] = $readingHistoryDB->checkOutDate;
		$historyEntry['checkin'] = $readingHistoryDB->checkInDate;
		$historyEntry['ratingData'] = null;
		$historyEntry['permanentId'] = null;
		$historyEntry['linkUrl'] = null;
		$historyEntry['coverUrl'] = null;
		$recordDriver = null;
		/*if ($readingHistoryDB->source == 'ILS') {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			$recordDriver = new MarcRecord($historyEntry['id']);
		} elseif ($readingHistoryDB->source == 'OverDrive') {
			require_once ROOT_DIR . '/RecordDrivers/OverDriveRecordDriver.php';
			$recordDriver = new OverDriveRecordDriver($historyEntry['id']);
		}*/
		require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
		$recordDriver = new GroupedWorkDriver($readingHistoryDB->groupedWorkPermanentId);
		if ($recordDriver != null && $recordDriver->isValid) {
			$historyEntry['ratingData'] = $recordDriver->getRatingData();
			$historyEntry['permanentId'] = $recordDriver->getPermanentId();
			$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
			if ($recordDriver->isValid){
				$historyEntry['linkUrl'] = $recordDriver->getLinkUrl();
			}
			if ($historyEntry['title'] == ''){
				$historyEntry['title']  = $recordDriver->getTitle();
			}
		}
		$recordDriver = null;
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
			$historyEntry = $this->getHistoryEntryForDatabaseEntry($readingHistoryDB);

			$key = $historyEntry['source'] . ':' . $historyEntry['id'];
			$activeHistoryTitles[$key] = $historyEntry;
		}

		//Update reading history based on current checkouts.  That way it never looks out of date
		$checkouts = $patron->getMyCheckouts(false);
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
					$logger->log("Could not insert new reading history entry", PEAR_LOG_ERR);
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
					$logger->log("Could not update reading history entry $key", PEAR_LOG_ERR);
				}
			}
		}
	}

	public function getNumHolds($id) {
		/** @var Memcache $memCache */
		global $memCache;
		$key = 'num_holds_' . $id ;
		$cachedValue = $memCache->get($key);
		if ($cachedValue == false || isset($_REQUEST['reload'])){
			$cachedValue = $this->driver->getNumHolds($id);
			global $configArray;
			$memCache->add($key, $cachedValue, 0, $configArray['Caching']['item_data']);
		}

		return $cachedValue;
	}

	function cancelHold($patron, $recordId, $cancelId) {
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

	public function renewItem($patron, $recordId, $itemId, $itemIndex){
		return $this->driver->renewItem($patron, $recordId, $itemId, $itemIndex);
	}

	public function renewAll($patron){
		if ($this->driver->hasFastRenewAll()){
			return $this->driver->renewAll($patron);
		}else{
			//Get all list of all transactions
			$currentTransactions = $this->driver->getMyCheckouts($patron);
			$renewResult = array(
				'success' => true,
				'message' => array(),
				'Renewed' => 0,
				'Unrenewed' => 0
			);
			$renewResult['Total'] = count($currentTransactions);
			$numRenewals = 0;
			$failure_messages = array();
			foreach ($currentTransactions as $transaction){
				$curResult = $this->renewItem($patron, $transaction['recordId'], $transaction['renewIndicator'], null);
				if ($curResult['success']){
					$numRenewals++;
				} else {
					$failure_messages[] = $curResult['message'];
				}
			}
			$renewResult['Renewed'] += $numRenewals;
			$renewResult['Unrenewed'] = $renewResult['Total'] - $renewResult['Renewed'];
			if ($renewResult['Unrenewed'] > 0) {
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
}