<?php

/**
 * Catalog Specific Driver Class
 *
 * This interface class is the definition of the required methods for
 * interacting with the local catalog.
 *
 * The parameters are of no major concern as you can define the purpose of the
 * parameters for each method for whatever purpose your driver needs.
 * The most important element here is what the method will return.  In all cases
 * the method can return a PEAR_Error object if an error occurs.
 */
abstract class AbstractCatalogDriver
{
    /** @var  AccountProfile $accountProfile */
    public $accountProfile;

    /**
     * @param AccountProfile $accountProfile
     */
    public function __construct($accountProfile){
        $this->accountProfile = $accountProfile;
    }

	public abstract function patronLogin($username, $password, $validatedViaSSO);
	public abstract function hasNativeReadingHistory();
	public function performsReadingHistoryUpdatesOfILS(){
        return false;
    }
    public function getReadingHistory(
            /** @noinspection PhpUnusedParameterInspection */ $user,
            /** @noinspection PhpUnusedParameterInspection */ $page = 1,
            /** @noinspection PhpUnusedParameterInspection */ $recordsPerPage = -1,
            /** @noinspection PhpUnusedParameterInspection */ $sortOption = "checkedOut") {
        return array('historyActive'=>false, 'titles'=>array(), 'numTitles'=> 0);
    }
    public function doReadingHistoryAction(
            /** @noinspection PhpUnusedParameterInspection */ $user,
            /** @noinspection PhpUnusedParameterInspection */ $action,
            /** @noinspection PhpUnusedParameterInspection */ $selectedTitles){
        return;
    }
	public abstract function getNumHolds($id);

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user    The user to load transactions for
	 *
	 * @return array        Array of the patron's transactions on success
	 * @access public
	 */
	public abstract function getMyCheckouts($user);

	/**
	 * @return boolean true if the driver can renew all titles in a single pass
	 */
	public abstract function hasFastRenewAll();

	/**
	 * Renew all titles currently checked out to the user
	 *
	 * @param $patron  User
	 * @return mixed
	 */
	public abstract function renewAll($patron);

	/**
	 * Renew a single title currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $recordId   string
	 * @param $itemId     string
	 * @param $itemIndex  string
	 * @return mixed
	 */
	public abstract function renewItem($patron, $recordId, $itemId, $itemIndex);

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
	public abstract function getMyHolds($user);

    /**
     * Place Hold
     *
     * This is responsible for both placing holds as well as placing recalls.
     *
     * @param   User        $patron         The User to place a hold for
     * @param   string      $recordId       The id of the bib record
     * @param   string      $pickupBranch   The branch where the user wants to pickup the item when available
     * @param   string|null $cancelDate     The date when the record should be automatically cancelled if not filled
     * @return  array                 An array with the following keys
     *                                result - true/false
     *                                message - the message to display (if item holds are required, this is a form to select the item).
     *                                needsItemLevelHold - An indicator that item level holds are required
     *                                title - the title of the record the user is placing a hold on
     * @access  public
     */
	public abstract function placeHold($patron, $recordId, $pickupBranch, $cancelDate = null);

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
	 *
	 * @param   User    $patron     The User to place a hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $itemId     The id of the item to hold
	 * @param   string  $pickupBranch The branch where the user wants to pickup the item when available
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a PEAR_Error
	 * @access  public
	 */
    abstract function placeItemHold($patron, $recordId, $itemId, $pickupBranch);

	/**
	 * Cancels a hold for a patron
	 *
	 * @param   User    $patron     The User to cancel the hold for
	 * @param   string  $recordId   The id of the bib record
	 * @param   string  $cancelId   Information about the hold to be cancelled
	 * @return  array
	 */
    abstract function cancelHold($patron, $recordId, $cancelId);

    abstract function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate);

    abstract function thawHold($patron, $recordId, $itemToThawId);

    abstract function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation);

    abstract function updatePatronInfo($patron, $canUpdateContactInfo);

    public abstract function getMyFines($patron, $includeMessages = false);

    /**
     * @return IndexingProfile|null
     */
    public function getIndexingProfile(){
        global $indexingProfiles;
        if (array_key_exists($this->accountProfile->recordSource, $indexingProfiles)) {
            /** @var IndexingProfile $indexingProfile */
            return $indexingProfiles[$this->accountProfile->recordSource];
        } else {
            return null;
        }
    }

    public function getWebServiceURL(){
        if (empty($this->webServiceURL)){
            $webServiceURL = null;
            if (!empty($this->accountProfile->patronApiUrl)){
                $webServiceURL = trim($this->accountProfile->patronApiUrl);
            }else{
                global $logger;
                $logger->log('No Web Service URL defined in account profile', PEAR_LOG_CRIT);
            }
            $this->webServiceURL = rtrim($webServiceURL, '/'); // remove any trailing slash because other functions will add it.
        }
        return $this->webServiceURL;
    }
}