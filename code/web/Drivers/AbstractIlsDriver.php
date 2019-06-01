<?php

/**
 * Catalog Specific Driver Class
 *
 * This interface class is the definition of the required methods for
 * interacting with the local catalog.
 */
require_once ROOT_DIR . '/Drivers/AbstractDriver.php';

abstract class AbstractIlsDriver extends AbstractDriver
{
    /** @var  AccountProfile $accountProfile */
    public $accountProfile;
    protected $webServiceURL;

    /**
     * @param AccountProfile $accountProfile
     */
    public function __construct($accountProfile){
        $this->accountProfile = $accountProfile;
    }

	public abstract function patronLogin($username, $password, $validatedViaSSO);

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
    abstract function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null);

    /**
     * Cancels a hold for a patron
     *
     * @param   User    $patron     The User to cancel the hold for
     * @param   string  $recordId   The id of the bib record
     * @param   string  $cancelId   Information about the hold to be cancelled
     * @return  array
     */
    abstract function cancelHold($patron, $recordId, $cancelId = null);

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
     *                              If an error occurs, return a AspenError
     * @access  public
     */
    abstract function placeItemHold($patron, $recordId, $itemId, $pickupBranch);

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
                $logger->log('No Web Service URL defined in account profile', Logger::LOG_ALERT);
            }
            $this->webServiceURL = rtrim($webServiceURL, '/'); // remove any trailing slash because other functions will add it.
        }
        return $this->webServiceURL;
    }

    public function getVendorOpacUrl(){
        global $configArray;

        if ($this->accountProfile && $this->accountProfile->vendorOpacUrl ){
            $host = $this->accountProfile->vendorOpacUrl;
        }else{
            $host = $configArray['Catalog']['url'];
        }

        if (substr($host, -1) == '/') {
            $host = substr($host, 0, -1);
        }
        return $host;
    }

    /**
     * Renew a single title currently checked out to the user
     *
     * @param $patron     User
     * @param $recordId   string
     * @param $itemId     string
     * @param $itemIndex  string
     * @return mixed
     */
    abstract function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null);

    function showOutstandingFines()
    {
        return false;
    }

	/**
	 * Returns one of three values
	 * - none - No forgot password functionality exists
	 * - emailResetLink - A link to reset the pin is emailed to the user
	 * - emailPin - The pin itself is emailed to the user
	 * @return string
	 */
    function getForgotPasswordType()
	{
		return 'none';
	}

	function getEmailResetPinTemplate()
	{
		return 'overrideInDriver';
	}

	function processEmailResetPinForm()
	{
		return [
			'success' => false,
			'error' => 'This functionality is not available in the ILS.',
		];
	}

	function selfRegister()
	{
		return [
			'success' => false,
		];
	}

	function getSelfRegistrationFields()
	{
		return [];
	}

	function hasUsernameField()
	{
		return false;
	}
}