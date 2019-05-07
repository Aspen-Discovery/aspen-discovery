<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';
class RbdigitalDriver extends AbstractEContentDriver
{
    private $webServiceURL;
    private $userInterfaceURL;
    private $apiToken;
    private $libraryId;

    /** @var CurlWrapper */
    private $curlWrapper;

    public function __construct() {
        //TODO: migrate these settings to the database
        global $configArray;
        $this->webServiceURL = $configArray['Rbdigital']['url'];
        $this->userInterfaceURL = $configArray['Rbdigital']['userInterfaceUrl'];
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
     * @param User $patron The user to load transactions for
     *
     * @return array        Array of the patron's transactions on success
     * @access public
     */
    public function getCheckouts($patron)
    {
        if (isset($this->checkouts[$patron->id])){
            return $this->checkouts[$patron->id];
        }

        //Get the rbdigital id for the patron
        $rbdigitalId = $this->getRbdigitalId($patron);

        $checkouts = array();

        if ($rbdigitalId != false) {
            $patronCheckoutUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/checkouts';

            $patronCheckoutsRaw = $this->curlWrapper->curlGetPage($patronCheckoutUrl);
            $patronCheckouts = json_decode($patronCheckoutsRaw);
            foreach ($patronCheckouts as $patronCheckout){
                $checkout = array();
                $checkout['checkoutSource'] = 'Rbdigital';

                $checkout['id'] = $patronCheckout->transactionId;
                $checkout['recordId'] = $patronCheckout->isbn;
                $checkout['title'] = $patronCheckout->title;
                $checkout['author'] = $patronCheckout->authors;

                $dateDue = DateTime::createFromFormat('Y-m-d', $patronCheckout->expiration);
                if ($dateDue){
                    $dueTime = $dateDue->getTimestamp();
                }else{
                    $dueTime = null;
                }
                $checkout['dueDate'] = $dueTime;
                $checkout['itemId'] = $patronCheckout->isbn;
                $checkout['canRenew'] = $patronCheckout->canRenew;
                $checkout['hasDrm'] = $patronCheckout->hasDrm;
                $checkout['downloadUrl'] = $patronCheckout->downloadUrl;
                if (strlen($checkout['downloadUrl']) == 0){
                    $checkout['output'] = $patronCheckout->output;
                }
                $checkout['accessOnlineUrl'] = '';

                if ($checkout['id'] && strlen($checkout['id']) > 0){
                    require_once ROOT_DIR . '/RecordDrivers/RbdigitalRecordDriver.php';
                    $recordDriver = new RbdigitalRecordDriver($checkout['recordId']);
                    if ($recordDriver->isValid()){
                        $checkout['coverUrl']      = $recordDriver->getBookcoverUrl('medium');
                        $checkout['groupedWorkId'] = $recordDriver->getGroupedWorkId();
                        $checkout['ratingData']    = $recordDriver->getRatingData();
                        $checkout['format']        = $recordDriver->getPrimaryFormat();
                        $checkout['author']        = $recordDriver->getPrimaryAuthor();
                        $checkout['title']         = $recordDriver->getTitle();
                        $curTitle['title_sort']    = $recordDriver->getTitle();
                        $checkout['linkUrl']       = $recordDriver->getLinkUrl();
                        $checkout['accessOnlineUrl'] = $recordDriver->getAccessOnlineLinkUrl($patron);
                    }else{
                        $checkout['coverUrl'] = "";
                        $checkout['groupedWorkId'] = "";
                        $checkout['format'] = $patronCheckout->mediaType;
                    }
                }

                $checkout['user'] = $patron->getNameAndLibraryLabel();
                $checkout['userId'] = $patron->id;

                $checkouts[] = $checkout;
            }

        }
        $this->checkouts[$patron->id] = $checkouts;

        return $checkouts;
    }

    /**
     * @param User $patron
     * @param string $recordId
     *
     * @return array results (success, message)
     */
    public function checkOutTitle($patron, $recordId) {
        $result = ['success' => false, 'message' => 'Unknown error'];
        $rbdigitalId = $this->getRbdigitalId($patron);
        if ($rbdigitalId == false) {
            $result['message'] = 'Sorry, you are not registered with Rbdigital.  You will need to create an account there before continuing.';
        } else {
            require_once ROOT_DIR . '/RecordDrivers/RbdigitalRecordDriver.php';
            $actionUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/checkouts/' . $recordId;

            $rawResponse = $this->curlWrapper->curlPostPage($actionUrl, '');
            $response = json_decode($rawResponse);
            if ($response == false){
                $result['message'] = "Invalid information returned from API, please retry your checkout after a few minutes.";
                global $logger;
                $logger->log("Invalid information from rbdigital api\r\n$actionUrl\r\n$rawResponse", Logger::LOG_ERROR);
                $logger->log(print_r($this->curlWrapper->getHeaders(), true), Logger::LOG_ERROR);
                $curl_info = curl_getinfo($this->curlWrapper->curl_connection);
                $logger->log(print_r($curl_info, true), Logger::LOG_ERROR);
            }else{
                if (!empty($response->output) && $response->output == 'SUCCESS') {
                    $result['success'] = true;
                    $result['message'] = 'Your title was checked out successfully. You can read or listen to the title from your account.';
                } else {
                    $result['message'] = $response->output;
                }

            }
        }
        return $result;
    }

    public function createAccount(User $user) {
        global $configArray;
        $result = ['success' => false, 'message' => 'Unknown error'];

        $registrationData = [
            'username' => $_REQUEST['username'],
            'password' => $_REQUEST['password'],
            'firstName' => $_REQUEST['firstName'],
            'lastName' => $_REQUEST['lastName'],
            'email' => $_REQUEST['email'],
            'postalCode' => $_REQUEST['postalCode'],
            'libraryCard' => $_REQUEST['libraryCard'],
            'libraryId' => $configArray['Rbdigital']['libraryId'],
            'tenantId' => $configArray['Rbdigital']['libraryId']
        ];

        //TODO: add pin if the library configuration uses pins

        $actionUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/';

        $rawResponse = $this->curlWrapper->curlPostPage($actionUrl, json_encode($registrationData));
        $response = json_decode($rawResponse);
        if ($response == false){
            $result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
            global $logger;
            $logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
        }else{
            if (!empty($response->authStatus) && $response->authStatus == 'Success') {
                $user->rbdigitalId = $response->patron->patronId;
                $result['success'] = true;
                $result['message'] = "Your have been registered successfully.";
            } else {
                $result['message'] = $response->message;
            }
        }

        return $result;
    }

    public function isUserRegistered(User $user){
        if ($this->getRbdigitalId($user) != false) {
            return true;
        }else{
            return false;
        }
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
     * @return array
     */
    public function renewCheckout($patron, $recordId)
    {
        $result = ['success' => false, 'message' => 'Unknown error'];

        $rbdigitalId = $this->getRbdigitalId($patron);
        if ($rbdigitalId == false) {
            $result['message'] = 'Sorry, you are not registered with Rbdigital.  You will need to create an account there before continuing.';
        } else {
            $actionUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/checkouts/' . $recordId;

            $rawResponse = $this->curlWrapper->curlSendPage($actionUrl, 'PUT');
            $response = json_decode($rawResponse);
            if ($response == false){
                $result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
                global $logger;
                $logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
            }else{
                if (!empty($response->output) && $response->output == 'success') {
                    $result['success'] = true;
                    $result['message'] = "Your title was renewed successfully.";
                } else {
                    $result['message'] = $response->output;
                }
            }
        }
        return $result;
    }

    /**
     * Return a title currently checked out to the user
     *
     * @param $patron     User
     * @param $recordId   string
     * @return array
     */
    public function returnCheckout($patron, $recordId)
    {
        $result = ['success' => false, 'message' => 'Unknown error'];

        $rbdigitalId = $this->getRbdigitalId($patron);
        if ($rbdigitalId == false) {
            $result['message'] = 'Sorry, you are not registered with Rbdigital.  You will need to create an account there before continuing.';
        } else {
            $actionUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/checkouts/' . $recordId;

            $rawResponse = $this->curlWrapper->curlSendPage($actionUrl, 'DELETE');
            $response = json_decode($rawResponse);
            if ($response == false){
                $result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
                global $logger;
                $logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
            }else{
                if (!empty($response->message) && $response->message == 'success') {
                    $result['success'] = true;
                    $result['message'] = "Your title was returned successfully.";
                } else {
                    $result['message'] = $response->message;
                }
            }
        }
        return $result;
    }

    private $holds = array();
    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds for a specific patron.
     *
     * @param User $patron The user to load transactions for
     *
     * @return array        Array of the patron's holds
     * @access public
     */
    public function getHolds($patron)
    {
        if (isset($this->holds[$patron->id])){
            return $this->holds[$patron->id];
        }

        $rbdigitalId = $this->getRbdigitalId($patron);

        $patronHoldsUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/holds';

        $patronHoldsRaw = $this->curlWrapper->curlGetPage($patronHoldsUrl);
        $patronHolds = json_decode($patronHoldsRaw);

        $holds = array(
            'available' => array(),
            'unavailable' => array()
        );

        if ($rbdigitalId == false) {
            return $holds;
        }

        foreach ($patronHolds as $tmpHold) {
            $hold = array();
            $hold['id'] = $tmpHold->isbn;
            $hold['transactionId'] = $tmpHold->transactionId;
            $hold['holdSource'] = 'Rbdigital';

            require_once ROOT_DIR . '/RecordDrivers/RbdigitalRecordDriver.php';
            $recordDriver = new RbdigitalRecordDriver($hold['id']);
            if ($recordDriver->isValid()) {
                $hold['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
                $hold['title'] = $recordDriver->getTitle();
                $hold['sortTitle'] = $recordDriver->getTitle();
                $hold['author'] = $recordDriver->getPrimaryAuthor();
                $hold['linkUrl'] = $recordDriver->getLinkUrl(false);
                $hold['format'] = $recordDriver->getFormats();
                $hold['ratingData'] = $recordDriver->getRatingData();
            }
            $hold['user'] = $patron->getNameAndLibraryLabel();
            $hold['userId'] = $patron->id;

            $key = $hold['holdSource'] . $hold['id'] . $hold['user'];
            $holds['unavailable'][$key] = $hold;
        }

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
     *                                success - true/false
     *                                message - the message to display (if item holds are required, this is a form to select the item).
     * @access  public
     */
    public function placeHold($patron, $recordId)
    {
        $result = ['success' => false, 'message' => 'Unknown error'];
        $rbdigitalId = $this->getRbdigitalId($patron);
        if ($rbdigitalId == false) {
            $result['message'] = 'Sorry, you are not registered with Rbdigital.  You will need to create an account there before continuing.';
        } else {
            $actionUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/holds/' . $recordId;

            $rawResponse = $this->curlWrapper->curlPostPage($actionUrl, "");
            $response = json_decode($rawResponse);
            if ($response == false){
                $result['message'] = "Invalid information returned from API, please retry your hold after a few minutes.";
                global $logger;
                $logger->log("Invalid information from rbdigital api\r\n$actionUrl\r\n$rawResponse", Logger::LOG_ERROR);
                $logger->log(print_r($this->curlWrapper->getHeaders(), true), Logger::LOG_ERROR);
                $curl_info = curl_getinfo($this->curlWrapper->curl_connection);
                $logger->log(print_r($curl_info, true), Logger::LOG_ERROR);
            }else{
                if (is_numeric($response)) {
                    $result['success'] = true;
                    $result['message'] = "Your hold was placed successfully.";
                } else {
                    $result['message'] = $response->message;
                }
            }
        }
        return $result;
    }

    /**
     * Cancels a hold for a patron
     *
     * @param   User $patron The User to cancel the hold for
     * @param   string $recordId The id of the bib record
     * @return  array
     */
    function cancelHold($patron, $recordId)
    {
        $result = ['success' => false, 'message' => 'Unknown error'];
        $rbdigitalId = $this->getRbdigitalId($patron);
        if ($rbdigitalId == false) {
            $result['message'] = 'Sorry, you are not registered with Rbdigital.  You will need to create an account there before continuing.';
        } else {
            $actionUrl = $this->webServiceURL . '/v1/libraries/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/holds/' . $recordId;

            $rawResponse = $this->curlWrapper->curlSendPage($actionUrl, 'DELETE');
            $response = json_decode($rawResponse);
            if ($response == false){
                $result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
                global $logger;
                $logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
            }else{
                if (!empty($response->message) && $response->message == 'success') {
                    $result['success'] = true;
                    $result['message'] = "Your hold was cancelled successfully.";
                } else {
                    $result['message'] = $response->message;
                }
            }
        }
        return $result;
    }

    /**
     * @param User $patron
     *
     * @return array
     */
    public function getAccountSummary($patron){
        /** @var Memcache $memCache */
        global $memCache;
        global $configArray;
        global $timer;

        if ($patron == false){
            return array(
                'numCheckedOut' => 0,
                'numAvailableHolds' => 0,
                'numUnavailableHolds' => 0,
            );
        }

        $summary = $memCache->get('rbdigital_summary_' . $patron->id);
        if ($summary == false || isset($_REQUEST['reload'])){
            //Get the rbdigital id for the patron
            $rbdigitalId = $this->getRbdigitalId($patron);

            //Get account information from api
            $patronSummaryUrl = $this->webServiceURL . '/v1/tenants/' . $this->libraryId . '/patrons/' . $rbdigitalId. '/patron-config';

            $responseRaw = $this->curlWrapper->curlGetPage($patronSummaryUrl);
            $response = json_decode($responseRaw);

            $summary = array();
            $summary['numCheckedOut'] = empty($response->audioBooks->checkouts) ? 0 : count($response->audioBooks->checkouts);

            //Rbdigital automatically checks holds out so nothing is available
            $summary['numAvailableHolds'] = 0;
            $summary['numUnavailableHolds'] = empty($response->audioBooks->holds) ? 0 : count($response->audioBooks->holds);

            $timer->logTime("Finished loading titles from overdrive summary");
            $memCache->set('rbdigital_summary_' . $patron->id, $summary, 0, $configArray['Caching']['overdrive_summary']);
        }

        return $summary;
    }

    /**
     * Get the user's rbdigital id or return false if the user is not registered.
     * Checks to see if the user is registered no more than every 15 minutes.
     *
     * @param User $user
     * @return int|false
     */
    public function getRbdigitalId(User $user)
    {
        if (!empty($user->rbdigitalId) && $user->rbdigitalId != -1 && !isset($_REQUEST['reload'])){
            return $user->rbdigitalId;
        } else {
            //Check to see if we should do a lookup.  Check no more than every 15 minutes
            if (isset($_REQUEST['reload']) || $user->rbdigitalLastAccountCheck < time() - 15 * 60){
                $lookupPatronUrl = $this->webServiceURL . '/v1/rpc/libraries/' . $this->libraryId . '/patrons/' . urlencode($user->getBarcode());

                $rawResponse = $this->curlWrapper->curlGetPage($lookupPatronUrl);
                $response = json_decode($rawResponse);
                if (is_null($response) || (isset($response->message) && ($response->message == 'Patron not found.'))){
                    if (!empty($user->email)){
                        $lookupPatronUrl = $this->webServiceURL . '/v1/rpc/libraries/' . $this->libraryId . '/patrons/' . urlencode($user->email);

                        $rawResponse = $this->curlWrapper->curlGetPage($lookupPatronUrl);
                        $response = json_decode($rawResponse);
                        if ($response->message == 'Patron not found.'){
                            $rbdigitalId = -1;
                        }else {
                            $rbdigitalId = $response->patronId;
                        }
                    }else{
                        $rbdigitalId = -1;
                    }

                }else {
                    $rbdigitalId = $response->patronId;
                }
                $user->rbdigitalId = $rbdigitalId;
                $user->rbdigitalLastAccountCheck = time();
                $user->update();
                if ($rbdigitalId == -1) {
                    return false;
                }else {
                    return $rbdigitalId;
                }
            } else {
                return false;
            }
        }
    }

    public function registerAccount($user){

    }

    public function redirectToRbdigital(User $patron, RbdigitalRecordDriver $recordDriver)
    {
        header('Location:' . $this->userInterfaceURL . '/book/' . $recordDriver->getUniqueID());
        die();
        $result = ['success' => false, 'message' => 'Unknown error'];
        $rbdigitalId = $this->getRbdigitalId($patron);
        if ($rbdigitalId == false) {
            $result['message'] = 'Sorry, you are not registered with Rbdigital.  You will need to create an account there before continuing.';
        } else {
            //Get the link to redirect to with the proper bearer information
            /*
             * POST to api.rbdigital.com/v1/tokens/
                with values of…
                libraryId
                UserName
                Password

                You should get a bearer token in response along the lines of...
                {"bearer": "5cc2058bd2b76b28943de9cf","result": true}

                …and should then be able to set an authorization header using…
                bearer 5cc2063fd2b76b28943deb32
             */
            $tokenUrl = $this->webServiceURL . '/v1/tokens/';
            $userData = [
                'UserName' => $rbdigitalId,
                'Password' => $_REQUEST['password'],
                'libraryId' => $this->libraryId,
            ];
            $rawResponse = $this->curlWrapper->curlPostPage($tokenUrl, json_encode($userData));
            $response = json_decode($rawResponse);

            if ($response == false){
                $result['message'] = "Invalid information returned from API, please retry your hold after a few minutes.";
                global $logger;
                $logger->log("Invalid information from rbdigital api\r\n$tokenUrl\r\n$rawResponse", Logger::LOG_ERROR);
                $logger->log(print_r($this->curlWrapper->getHeaders(), true), Logger::LOG_ERROR);
                $curl_info = curl_getinfo($this->curlWrapper->curl_connection);
                $logger->log(print_r($curl_info, true), Logger::LOG_ERROR);
            }else{
                //We should get back a bearer token
                if ($response->result == true) {
                    $bearerToken = $response->bearer;
                }else{
                    $result['message'] = "Did not get a bearer token from the API";
                }
            }
        }
        return $result;

    }
}