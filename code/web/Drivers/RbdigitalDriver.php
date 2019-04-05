<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';
class RbdigitalDriver extends AbstractEContentDriver
{
    private $webServiceURL;
    private $apiToken;
    private $libraryId;

    /** @var CurlWrapper */
    private $curlWrapper;

    public function __construct() {
        //TODO: migrate these settings to the database
        global $configArray;
        $this->webServiceURL = $configArray['Rbdigital']['url'];
        $this->apiToken = $configArray['Rbdigital']['apiToken'];
        $this->libraryId = $configArray['Rbdigital']['libraryId'];

        $this->curlWrapper = new CurlWrapper();
        $headers = [
            'Accept: application/json',
            'Authorization: basic ' . strtolower($this->apiToken),
            'Content-Type: application/json'
        ];
        $this->curlWrapper->addCustomHeaders($headers, true);
    }

    public function hasNativeReadingHistory()
    {
        return false;
    }

    private $checkouts = array();
    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param User $user The user to load transactions for
     *
     * @return array        Array of the patron's transactions on success
     * @access public
     */
    public function getCheckouts($user)
    {
        if (isset($this->checkouts[$user->id])){
            return $this->checkouts[$user->id];
        }
        $patronCheckoutUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $user->getBarcode() . '/checkout';

        $patronCheckoutsRaw = $this->curlWrapper->curlGetPage($patronCheckoutUrl);
        $patronCheckouts = json_decode($patronCheckoutsRaw);

        $checkedOutTitles = array();

        return $checkedOutTitles;
    }

    /**
     * @return boolean true if the driver can renew all titles in a single pass
     */
    public function hasFastRenewAll()
    {
        return false;
    }

    /**
     * Renew all titles currently checked out to the user
     *
     * @param $patron  User
     * @return mixed
     */
    public function renewAll($patron)
    {
        return false;
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
    public function renewCheckout($patron, $recordId, $itemId, $itemIndex)
    {
        // TODO: Implement renewCheckout() method.
    }

    private $holds = array();
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
        if (isset($this->holds[$user->id])){
            return $this->holds[$user->id];
        }
        $patronHoldsUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $user->getBarcode() . '/hold';

        $patronHoldsRaw = $this->curlWrapper->curlGetPage($patronHoldsUrl);
        $patronHolds = json_decode($patronHoldsRaw);

        $holds = array(
            'available' => array(),
            'unavailable' => array()
        );

        return $holds;
    }

    /**
     * Place Hold
     *
     * This is responsible for both placing holds as well as placing recalls.
     *
     * @param   User $patron The User to place a hold for
     * @param   string $recordId The id of the bib record
     * @return  array                 An array with the following keys
     *                                result - true/false
     *                                message - the message to display (if item holds are required, this is a form to select the item).
     *                                needsItemLevelHold - An indicator that item level holds are required
     *                                title - the title of the record the user is placing a hold on
     * @access  public
     */
    public function placeHold($patron, $recordId)
    {
        // TODO: Implement placeHold() method.
    }

    /**
     * Cancels a hold for a patron
     *
     * @param   User $patron The User to cancel the hold for
     * @param   string $recordId The id of the bib record
     * @param   string $cancelId Information about the hold to be cancelled
     * @return  array
     */
    function cancelHold($patron, $recordId, $cancelId)
    {
        // TODO: Implement cancelHold() method.
    }

    /**
     * @param User $patron
     */
    public function getAccountSummary($patron){

    }
}