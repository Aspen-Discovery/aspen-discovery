<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/Drivers/AbstractIlsDriver.php';

class FOLIO extends AbstractIlsDriver
{
	private $apiCurlWrapper;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile)
	{
		parent::__construct($accountProfile);
		$this->apiCurlWrapper = new CurlWrapper();
	}

	function __destruct()
	{
		$this->apiCurlWrapper = null;
	}

	// 'Borrowed' from VuFind
	public function patronLogin($username, $password, $validatedViaSSO)
	{
		// Step 1 - authenticate credentials
		// return error if bad credentials
		// note username if good
		// Step 2 - switch to Admin account and lookup user profile
		// check token exists
		// no, authenticate and save it
		// yes, use
		// lookup user via query to /users endpoint
		// return important values

		global $configArray;

		$doOkapiLogin = $configArray['Catalog']['okapi_login'] ?? true;
		$usernameField = $configArray['Catalog']['username_field'] ?? 'username';


		global $logger;

		if ($doOkapiLogin) {
			try {
				// If we fetched the profile earlier, we want to use the username
				// from there; otherwise, we'll use the passed-in version.
				$query = $this->patronLoginWithOkapi(
					$profile->username ?? $username,
					$password
				);
			} catch (Exception $e) {
				return null;
			}

			$blah = $this->patronLoginWithOkapi($this->accountProfile->staffUsername, $this->accountProfile->staffPassword);

			// If we didn't load a profile earlier, we should do so now:
			if (!isset($profile)) {
				$query = $this->getUserWithCql($username);
				$profile = $this->fetchUserWithCql(array('query' => $query));

				if ($profile === null) {
					return null;
				}
			}
		}

		$user = new User();
//		$user->id = $profile->externalSystemId;
		$user->username = $profile->id;
		// Look up user by username
		if ($user->find(true)) {
			$logger->log("user found!", Logger::LOG_ERROR);
		} else {
			$logger->log("user not found!", Logger::LOG_ERROR);
		}
		$user->cat_username = $username;
		$user->cat_password = $password;
		$user->alternateLibraryCard = $profile->barcode;
		$user->firstname = $profile->personal->preferredFirstName ?? $profile->personal->firstName;
		$user->lastname = $profile->personal->lastName;
		$user->displayName = $user->firstname . " " . $user->lastname;
		$user->email = $profile->personal->email;
		$user->patronType = 1;
		$user->homeLocationId = 1;
		$user->myLocation1Id = 1;
		$user->myLocation2Id = 1;
		$user->interfaceLanguage = 'en';

		return $user;
	}


	/**
	 * Support method for patronLogin(): authenticate the patron with an Okapi
	 * login attempt. Returns a CQL query for retrieving more information about
	 * the authenticated user.
	 *
	 * @param string $username The patron username
	 * @param string $password The patron password
	 *
	 * @return string
	 */
	protected function patronLoginWithOkapi($username, $password)
	{
		global $logger;
		$credentials = compact('username', 'password');
		// Get token
		$response = $this->makeRequest(
			'POST',
			'/authn/login',
			json_encode($credentials)
		);
		if ($this->apiCurlWrapper->getResponseCode() == '201') {
			$debugMsg = 'User ' . $username . ' successfully authenticated';
		} else {
			$errorMsg = json_decode($response);
			$debugMsg = $errorMsg->errors[0]->message;
		}

		$logger->log($debugMsg, Logger::LOG_ERROR);
		return $response;
	}

	/**
	 * Support method for patronLogin(): authenticate the patron with a CQL lookup.
	 * Returns the CQL query for retrieving more information about the user.
	 *
	 * @param string $username The patron username
	 *
	 * @return string
	 */
	protected function getUserWithCql($username)
	{
		// Construct user query using barcode, username, etc.
		$usernameField = 'username';
		$cql = '%%username_field%% == "%%username%%"';
		$placeholders = [
			'%%username_field%%',
			'%%username%%',
		];
		$values = [
			$usernameField,
			$this->escapeCql($username)
		];
		return str_replace($placeholders, $values, $cql);
	}

	/**
	 * Given a CQL query, fetch a single user; if we get an unexpected count, treat
	 * that as an unsuccessful login by returning null.
	 *
	 * @param string $query CQL query
	 *
	 * @return object
	 */
	protected function fetchUserWithCql($query)
	{
		global $logger;
		$logger->log("Query: " . json_encode($query), Logger::LOG_ERROR);
		$response = $this->makeRequest('GET', '/users', $query);
		$json = json_decode($response);
		return count($json->users) === 1 ? $json->users[0] : null;
	}


	public function getCheckouts(User $user) : array
	{
		require_once ROOT_DIR . '/sys/User/Checkout.php';
		$query = ['query' => 'userId==' . $user->username . ' and status.name==Open'];
		$transactions = [];
		foreach ($this->getPagedResults('loans', '/circulation/loans', $query) as $trans) {
			$checkout = new Checkout();
			$checkout->type = 'ils';
			$checkout->source = 'ils';
			$checkout->sourceId = $trans->id;
			$checkout->format = $trans->item->materialType->name;

			$checkout->userId = $user->id;

			$loandate = date_create($trans->loanDate);
			$checkout->checkoutDate = $loandate->getTimestamp();

			$duedate = date_create($trans->dueDate);
			$checkout->dueDate = $duedate->getTimestamp();

			$checkout->recordId = $trans->id;
			$checkout->itemId = $trans->item->id;
			$checkout->barcode = $trans->item->barcode;
			$checkout->renewCount = $trans->renewalCount ?? 0;
			$checkout->canRenew = 1;  // okay, we don't actually know this, but figuring out the answer is a lot of extra API calls
			$checkout->title = $trans->item->title;
			$checkout->author = $trans->item->contributors[0]->name;
			$checkout->callNumber = $trans->item->callNumber;
			$transactions[] = $checkout;
		}
		return $transactions;
	}

	public function hasFastRenewAll() : bool
	{
		return false;
	}

	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		global $logger;
		if ($itemId == null) {
			require_once ROOT_DIR . '/sys/User/Checkout.php';
			$checkout = new Checkout();
			$checkout->recordId = $recordId;
			$checkout->find(true);
			$itemId = $checkout->itemId;
		}

		$requestbody = [
			'itemId' => $itemId,
			'userId' => $patron->username
		];
		$response = $this->makeRequest('POST', '/circulation/renew-by-id', json_encode($requestbody));

		$responseCode = $this->apiCurlWrapper->getResponseCode();
		if ($responseCode == '200' || $responseCode == '201') {
			$message = 'Renewal successful!';
			$success = true;
		} else {
			$errorMsg = json_decode($response);
			$message = $errorMsg->errors[0]->message;
			$success = false;
		}

		$logger->log($message, Logger::LOG_ERROR);
		return array(
			'itemId' => $itemId,
			'success' => $success,
			'message' => $message
		);
	}

	public function hasHolds()
	{
		return true;
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
	public function getHolds(User $user) : array
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$query = array(
			'query' => 'requesterId==' . $user->username . ' and status>Open'
		);

		$availableHolds = array();
		$unavailableHolds = array();
		$holds = array(
			'available' => $availableHolds,
			'unavailable' => $unavailableHolds
		);

		foreach ($this->getPagedResults('requests', '/circulation/requests', $query) as $trans) {
			$hold = new Hold();
			$hold->recordId = $trans->id;
			$hold->type = 'ils';
			$hold->source = 'ils';
			$hold->sourceId = $trans->id;

			$hold->status = $trans->status;
			if ($hold->status == 'Open - Awaiting pickup') {
				$hold->available = 1;
				$hold->cancelable = 0;
			} else {
				$hold->available = 0;
				$hold->cancelable = 1;
			}
			$hold->position = $trans->position;
			$hold->pickupLocationId = $trans->pickupServicePointId;
			$hold->pickupLocationName = $trans->pickupServicePoint->discoveryDisplayName;

			$hold->userId = $user->id;

			$createdate = date_create($trans->requestDate);
			$hold->createDate = $createdate->getTimestamp();

			$expirydate = date_create($trans->requestExpirationDate);
			$hold->expirationDate = $expirydate->getTimestamp();

			$hold->title = $trans->item->title;
			$hold->itemId = $trans->item->id;
			$hold->author = $trans->item->contributorNames[0]->name;
			$hold->callNumber = $trans->item->callNumber;

			if ($hold->available) {
				$holds['unavailable'][$hold->source . $hold->recordId . $hold->userId] = $hold;
			} else {
				$holds['available'][$hold->source . $hold->recordId . $hold->userId] = $hold;
			}

		}
		return $holds;
	}

	/**
	 * Cancels a hold for a patron
	 *
	 * @param User $patron The User to cancel the hold for
	 * @param string $recordId The id of the bib record
	 * @param string $cancelId Information about the hold to be cancelled
	 * @return  array
	 */
	function cancelHold($patron, $recordId, $cancelId = null, $isIll = false) : array
	{
		global $logger;
		$hold = $this->makeRequest('GET', '/circulation/requests/' . $recordId);
		$hold = json_decode($hold, true);

		// FOLIO default cancellation reason for patron-initiated cancel
		$hold['cancellationReasonId'] = '75187e8d-e25a-47a7-89ad-23ba612338de';
		$hold['cancelledByUserId'] = $patron->username;
		$hold['cancelledDate'] = date('c');
		$hold['status'] = 'Closed - Cancelled';

		$response = $this->makeRequest('PUT', '/circulation/requests/' . $recordId, json_encode($hold));

		$responseCode = $this->apiCurlWrapper->getResponseCode();
		if ($responseCode == '204') {
			$message = 'Hold Cancelled!';
			$success = true;
		} else {
			$errorMsg = json_decode($response);
			$message = $errorMsg->errors[0]->message;
			$success = false;
		}

		$logger->log($message, Logger::LOG_ERROR);
		return array(
			'itemId' => $itemId,
			'success' => $success,
			'message' => $message
		);
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for placing holds
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pickup the item when available
	 * @param string $cancelDate When the hold should be automatically cancelled
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		// title-level holds are currently implemented through the same API
		// as item-level holds, just without an item Id.
		return placeItemHold($patron, $recordId, '', $pickupBranch, $cancelDate);
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for both placing item level holds.
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

	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch)
	{
		return array(
			'success' => false,
			'message' => 'Volume level holds have not been implemented for this ILS.');
	}


	function freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate) : array
	{
		return array(
			'success' => false,
			'message' => 'Freezing holds not implemented for this ILS');
	}

	function thawHold($patron, $recordId, $itemToThawId) : array
	{
		return array(
			'success' => false,
			'message' => 'Thawing holds not implemented for this ILS');
	}

	function changeHoldPickupLocation($patron, $recordId, $itemToUpdateId, $newPickupLocation) : array
	{
		global $logger;
		$hold = $this->makeRequest('GET', '/circulation/requests/' . $recordId);
		$hold = json_decode($hold, true);

		$hold['pickupServicePointId'] = $newPickupLocation;

		$response = $this->makeRequest('PUT', '/circulation/requests/' . $recordId, json_encode($hold));

		$responseCode = $this->apiCurlWrapper->getResponseCode();
		if ($responseCode == '204') {
			$message = 'Pickup Location Updated!';
			$success = true;
		} else {
			$errorMsg = json_decode($response);
			$message = $errorMsg->errors[0]->message;
			$success = false;
		}

		$logger->log($message, Logger::LOG_ERROR);
		return array(
			'itemId' => $itemToUpdateId,
			'success' => $success,
			'message' => $message
		);

	}

	function updatePatronInfo($patron, $canUpdateContactInfo, $fromMasquerade)
	{
		return [
			'success' => false,
			'messages' => ['Cannot update patron information with this ILS.']
		];
	}

	function updateHomeLibrary(User $patron, string $homeLibraryCode)
	{
		return [
			'success' => false,
			'messages' => ['Cannot update home library with this ILS.']
		];
	}

	public function getFines($patron, $includeMessages = false) : array
	{
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)) {
			$currencyCode = $variables->currencyCode;
		}
		$currencyFormatter = new NumberFormatter('en' . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);

		$query = ['query' => 'userId==' . $patron->username . ' and status.name==Open'];
		$fines = [];
		foreach ($this->getPagedResults('accounts', '/accounts', $query) as $fine) {
			$date = date_create($fine->metadata->createdDate);
			$title = $fine->title ?? null;
			$fines[] = [
				'fineId' => $fine->id,
				'amountVal' => $fine->amount,
				'amountOutstandingVal' => $fine->remaining,
				'amount' => $currencyFormatter->formatCurrency($fine->amount, $currencyCode),
				'amountOutstanding' => $currencyFormatter->formatCurrency($fine->remaining, $currencyCode),
				'status' => $fine->paymentStatus->name,
				'type' => $fine->feeFineType,
				'reason' => $fine->feeFineType,
				'message' => $title,
				'date' => date_format($date, "j M Y")
			];
		}
		return $fines;
	}


	public function getWebServiceURL()
	{
		if (empty($this->webServiceURL)) {
			$webServiceURL = null;
			if (!empty($this->accountProfile->patronApiUrl)) {
				$webServiceURL = trim($this->accountProfile->patronApiUrl);
			} else {
				global $logger;
				$logger->log('No Web Service URL defined in account profile', Logger::LOG_ALERT);
			}
			$this->webServiceURL = rtrim($webServiceURL, '/'); // remove any trailing slash because other functions will add it.
		}
		return $this->webServiceURL;
	}

	public function getAccountSummary(User $user): AccountSummary
	{
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $user->id;
		$summary->source = 'ils';

		// checkouts
		$checkouts = $this->getCheckouts($user);
		$totaloverdue = 0;
		foreach ($checkouts as $checkout) {
			$now = date_create()->getTimeStamp();
			if ($checkout->dueDate < $now) {
				$totaloverdue++;
			}

		}
		$summary->numCheckedOut = sizeof($checkouts);
		$summary->numOverdue = $totaloverdue;

		// holds
		$holds = $this->getHolds($user);
		$summary->numAvailableHolds = sizeof($holds['available']);
		$summary->numUnavailableHolds = sizeof($holds['unavailable']);

		// fines
		$fines = $this->getFines($user);
		$totalfines = 0;
		foreach ($fines as $fine) {
			$totalfines += $fine['amountOutstandingVal'];
		}
		$summary->totalFines = $totalfines;

		// expiry
		// TO DO: get expirationDate from user object if it exists... otherwise provide default
		$summary->expirationDate = strtotime('+5 years');
		return $summary;
	}

	public function completeFinePayment(User $patron, UserPayment $payment)
	{
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS'
		];
	}

	public function patronEligibleForHolds(User $patron)
	{
		return [
			'isEligible' => true,
			'message' => '',
			'fineLimitReached' => false,
			'maxPhysicalCheckoutsReached' => false,
			'expiredPatronWhoCannotPlaceHolds' => false
		];
	}


	public function logout(User $user)
	{
	}


	public function hasNativeReadingHistory() : bool
	{
		return false;
	}

	public function renewAll($patron)
	{
		return false;
	}

	function makeRequest($method, $endpoint, $request_body = null)
	{
		global $logger;
		$apiUrl = $this->getWebServiceUrl() . $endpoint;
		$this->apiCurlWrapper->addCustomHeaders([
			'User-Agent: Aspen Discovery',
			'Content-Type: application/json',
			'x-okapi-tenant: ' . $this->accountProfile->oAuthClientId
		], true);

		if (isset($_SESSION['okapi_token'])) {
			$this->apiCurlWrapper->addCustomHeaders([
				'x-okapi-token: ' . $_SESSION['okapi_token']
			], false);
		} else {
			$headers = [];
			// No token?  Well, we need one!  Enable headers
			curl_setopt($this->apiCurlWrapper->curl_connection, CURLOPT_HEADER, 1);
			curl_setopt($this->apiCurlWrapper->curl_connection, CURLOPT_RETURNTRANSFER, 1);
			// this function is called by curl for each header received
			curl_setopt($this->apiCurlWrapper->curl_connection, CURLOPT_HEADERFUNCTION,
				function ($curl, $header) use (&$headers) {
					$len = strlen($header);
					$header = explode(':', $header, 2);
					if (count($header) < 2) // ignore invalid headers
						return $len;

					$headers[strtolower(trim($header[0]))][] = trim($header[1]);

					return $len;
				}
			);
		}
		if ($method == 'GET') {
			$query_string = http_build_query($request_body);
			$apiUrl .= '?' . $query_string;
			$request_body = null;
		}

		$logger->log($method . " to " . $apiUrl, Logger::LOG_ERROR);
		$logger->log("request body: " . $request_body, Logger::LOG_ERROR);
		$response = $this->apiCurlWrapper->curlSendPage($apiUrl, $method, $request_body);
		$header_size = $this->apiCurlWrapper->getHeaderSize();
		//$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

		if (isset($headers["x-okapi-token"])) {
			$_SESSION['okapi_token'] = $headers["x-okapi-token"][0];
			$logger->log("Token returned! " . $_SESSION['okapi_token'], Logger::LOG_ERROR);
			$response = $body;
		}

		$logger->log("Raw response: " . $response, Logger::LOG_ERROR);
//		$logger->log("Headers: " . json_encode($headers), Logger::LOG_ERROR);
		return $response;
	}

	protected function escapeCql($in)
	{
		return str_replace('"', '\"', str_replace('&', '%26', $in));
	}

	/**
	 * Helper function to retrieve paged results from FOLIO APIy
	 *
	 * @param string $responseKey Key containing values to collect in response
	 * @param string $interface FOLIO api interface to call
	 * @param array $query CQL query
	 *
	 * @return array
	 */
	protected function getPagedResults($responseKey, $endpoint, $query = [])
	{
		global $logger;
		$count = 0;
		$limit = 1000;
		$offset = 0;

		do {
			$combinedQuery = array_merge($query, compact('offset', 'limit'));
			$response = $this->makeRequest(
				'GET',
				$endpoint,
				$combinedQuery
			);
			$logger->log("response: " . $response, Logger::LOG_ERROR);
			if ($this->apiCurlWrapper->getResponseCode() == '200') {
				$logger->log("got paged results", Logger::LOG_ERROR);
			} else {
				$errorMsg = json_decode($response);
				$debugMsg = $errorMsg->errors[0]->message;
				$logger->log($debugMsg, Logger::LOG_ERROR);
			}


			$json = json_decode($response);
			$total = $json->totalRecords ?? 0;
			$previousCount = $count;
			foreach ($json->$responseKey ?? [] as $item) {
				$count++;
				if ($count % $limit == 0) {
					$offset += $limit;
				}
				yield $item ?? '';
			}
			// Continue until the count reaches the total records
			// found, if count does not increase, something has gone
			// wrong. Stop so we don't loop forever.
		} while ($count < $total && $previousCount != $count);

	}
}

