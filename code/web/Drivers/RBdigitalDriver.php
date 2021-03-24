<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';

class RBdigitalDriver extends AbstractEContentDriver
{
	/** @var RBDigitalConnectionSettings[]  */
	private $connectionSettings = [];

	public function __construct()
	{

	}

	public function getUserInterfaceURL($patron)
	{
		return $this->getConnectionSettings($patron)->userInterfaceURL;
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
		if (isset($this->checkouts[$patron->id])) {
			return $this->checkouts[$patron->id];
		}

		//Get the rbdigital id for the patron
		$rbdigitalId = $this->getRBdigitalId($patron);

		$checkouts = array();

		if ($rbdigitalId != false) {
			$patronCheckoutUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/checkouts';

			$patronCheckoutsRaw = $this->getConnectionSettings($patron)->curlWrapper->curlGetPage($patronCheckoutUrl);
			$patronCheckouts = json_decode($patronCheckoutsRaw);
			if (isset($patronCheckouts->message)) {
				//Error in RBdigital APIS
				global $logger;
				$logger->log("Error in RBdigital {$patronCheckouts->message}", Logger::LOG_WARNING);
			} else {
				foreach ($patronCheckouts as $patronCheckout) {
					$checkout = new Checkout();
					$checkout->type = 'rbdigital';
					$checkout->source = 'rbdigital';

					$checkout->sourceId = $patronCheckout->transactionId;
					$checkout->recordId = $patronCheckout->isbn;
					$checkout->title = $patronCheckout->title;
					$checkout->author = $patronCheckout->authors;

					$dateDue = DateTime::createFromFormat('Y-m-d', $patronCheckout->expiration);
					if ($dateDue) {
						$dueTime = $dateDue->getTimestamp();
					} else {
						$dueTime = null;
					}
					$checkout->dueDate = $dueTime;
					$checkout->itemId = $patronCheckout->isbn;
					$checkout->canRenew = $patronCheckout->canRenew;
					$checkout->downloadUrl = $patronCheckout->downloadUrl;
					$checkout['accessOnlineUrl'] = '';

					if ($checkout['id'] && strlen($checkout['id']) > 0) {
						require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
						$recordDriver = new RBdigitalRecordDriver($checkout['recordId']);
						if (!$recordDriver->isValid()) {
							$checkout->groupedWorkId = $recordDriver->getPermanentId();
							$checkout->format = $recordDriver->getPrimaryFormat();
							$checkout->author = $recordDriver->getPrimaryAuthor();
							$checkout->title = $recordDriver->getTitle();
						} else {
							$checkout->format = $patronCheckout->mediaType;
						}
					}

					$checkout->userId = $patron->id;

					$checkouts[$checkout->source . $checkout->sourceId . $checkout->userId] = $checkout;
				}
			}

			//Look for magazines
			$patronMagazinesUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/patron-magazines/history?pageIndex=0&pageSize=100';
			$patronMagazinesRaw = $this->getConnectionSettings($patron)->curlWrapper->curlGetPage($patronMagazinesUrl);
			$patronMagazines = json_decode($patronMagazinesRaw);
			if (!isset($patronMagazines->resultSet)) {
				global $logger;
				$logger->log("Did not get result set when looking for magazines", Logger::LOG_WARNING);
			} else {
				foreach ($patronMagazines->resultSet as $patronMagazine) {
					$patronMagazineDetails = $patronMagazine->item;
					$checkout = new Checkout();
					$checkout->type = 'rbdigital';
					$checkout->source = 'rbdigital_magazine';

					$checkout->sourceId = $patronMagazineDetails->issueId;
					$checkout->recordId = $patronMagazineDetails->magazineId . '_' . $patronMagazineDetails->issueId;
					$checkout->title = $patronMagazineDetails->title;
					$checkout->volume = $patronMagazineDetails->coverDate;
					$checkout->author = $patronMagazineDetails->publisher;
					$checkout->canRenew = false;

					if ($checkout->sourceId && strlen($checkout->sourceId) > 0) {
						require_once ROOT_DIR . '/RecordDrivers/RBdigitalMagazineDriver.php';
						$recordDriver = new RBdigitalMagazineDriver($checkout->recordId);
						if ($recordDriver->isValid()) {
							$checkout->groupedWorkId = $recordDriver->getPermanentId();
							$checkout->format = $recordDriver->getPrimaryFormat();
							$checkout->title = $recordDriver->getTitle();
						} else {
							if (!empty($patronMagazineDetails->images[0])) {
								$checkout->coverUrl = $patronMagazineDetails->images[0]->url;
							}
							$checkout->format = 'eMagazine';
						}
					}

					$checkout->userId = $patron->id;

					$checkouts[$checkout->source . $checkout->sourceId . $checkout->userId] = $checkout;
				}
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
	public function checkOutTitle($patron, $recordId)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$rbdigitalId = $this->getRBdigitalId($patron);
		if ($rbdigitalId == false) {
			$result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
		} else {
			require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
			$actionUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/checkouts/' . $recordId;

			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlPostPage($actionUrl, '');
			$response = json_decode($rawResponse);
			if ($response == false) {
				$result['message'] = "Invalid information returned from API, please retry your checkout after a few minutes.";
				global $logger;
				$logger->log("Invalid information from rbdigital api\r\n$actionUrl\r\n$rawResponse", Logger::LOG_ERROR);
				$logger->log(print_r($this->getConnectionSettings($patron)->curlWrapper->getHeaders(), true), Logger::LOG_ERROR);
				$curl_info = curl_getinfo($this->getConnectionSettings($patron)->curlWrapper->curl_connection);
				$logger->log(print_r($curl_info, true), Logger::LOG_ERROR);
			} else {
				if (!empty($response->output) && $response->output == 'SUCCESS') {
					$this->trackUserUsageOfRBdigital($patron);
					$this->trackRecordCheckout($recordId);
					$patron->lastReadingHistoryUpdate = 0;
					$patron->update();

					$result['success'] = true;
					$result['message'] = translate(['text' => 'rbdigital-checkout-success', 'defaultText' => 'Your title was checked out successfully. You can read or listen to the title from your account.']);

					global $memCache;
					$memCache->delete('rbdigital_summary_' . $patron->id);
				} else {
					$result['message'] = $response->output;
				}

			}
		}
		return $result;
	}

	/**
	 * @param User $patron
	 * @param string $recordId
	 *
	 * @return array results (success, message)
	 */
	public function checkoutMagazine($patron, $recordId)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$rbdigitalId = $this->getRBdigitalId($patron);
		if ($rbdigitalId == false) {
			$result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
		} else {
			//Get the current issue for the magazine
			list($magazineId, $issueId) = explode("_", $recordId);
			require_once ROOT_DIR . '/sys/RBdigital/RBdigitalMagazine.php';
			$product = new RBdigitalMagazine();
			$product->magazineId = $magazineId;
			if ($product->find(true)) {
				require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
				$actionUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/patron-magazines/' . $issueId;
				// /v{version}/libraries/{libraryId}/patrons/{patronId}/patron-magazines/{issueId}

				//RBdigital does not return a status so we assume that it checked out ok
				$return = $this->getConnectionSettings($patron)->curlWrapper->curlPostPage($actionUrl, '');
				$result['success'] = true;
				if (!empty($return)) {
					$jsonResult = json_decode($return);
					if (!empty($jsonResult->message)){
						$result['success'] = false;
						$result['message'] = $jsonResult->message;
					}
				}
				if ($result['success'] == true) {
					$this->trackUserUsageOfRBdigital($patron);
					$this->trackMagazineCheckout($magazineId, $issueId);
					$patron->lastReadingHistoryUpdate = 0;
					$patron->update();

					$result['message'] = 'The magazine was checked out successfully. You can read the magazine from the RBdigital app.';
				}

				global $memCache;
				$memCache->delete('rbdigital_summary_' . $patron->id);
			} else {
				$result['message'] = "Could not find magazine to checkout";
			}
		}
		return $result;
	}

	public function createAccount(User $user)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];

		$registrationData = [
			'username' => $_REQUEST['username'],
			'password' => $_REQUEST['password'],
			'firstName' => $_REQUEST['firstName'],
			'lastName' => $_REQUEST['lastName'],
			'email' => $_REQUEST['email'],
			'postalCode' => $_REQUEST['postalCode'],
			'libraryCard' => $_REQUEST['libraryCard'],
			'libraryId' => $this->getConnectionSettings($user)->libraryId,
			'tenantId' => $this->getConnectionSettings($user)->libraryId
		];

		//TODO: add pin if the library configuration uses pins

		$actionUrl = $this->getConnectionSettings($user)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($user)->libraryId . '/patrons/';

		$rawResponse = $this->getConnectionSettings($user)->curlWrapper->curlPostPage($actionUrl, json_encode($registrationData));
		$response = json_decode($rawResponse);
		if ($response == false) {
			$result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
			global $logger;
			$logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
		} else {
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

	public function isUserRegistered(User $user)
	{
		if ($this->getRBdigitalId($user) != false) {
			return true;
		} else {
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
	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];

		$rbdigitalId = $this->getRBdigitalId($patron);
		if ($rbdigitalId == false) {
			$result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
		} else {
			$actionUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/checkouts/' . $recordId;

			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlSendPage($actionUrl, 'PUT');
			$response = json_decode($rawResponse);
			if ($response == false) {
				$result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
				global $logger;
				$logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
			} else {
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

		$rbdigitalId = $this->getRBdigitalId($patron);
		if ($rbdigitalId == false) {
			$result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
		} else {
			$actionUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/checkouts/' . $recordId;

			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlSendPage($actionUrl, 'DELETE');
			$response = json_decode($rawResponse);
			if ($response == false) {
				$result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
				global $logger;
				$logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
			} else {
				if (!empty($response->message) && $response->message == 'success') {
					$result['success'] = true;
					$result['message'] = "Your title was returned successfully.";

					global $memCache;
					$memCache->delete('rbdigital_summary_' . $patron->id);
				} else {
					$result['message'] = $response->message;
				}
			}
		}
		return $result;
	}

	/**
	 * Return a magazine currently checked out to the user
	 *
	 * @param $patron     User
	 * @param $magazineId   string
	 * @return array
	 */
	public function returnMagazine($patron, $magazineId, $issueId)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];

		$rbdigitalId = $this->getRBdigitalId($patron);
		if ($rbdigitalId == false) {
			$result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
		} else {
			//RBdigital can return titles that are no longer available or that aren't actually part of the library collection.
			//We can return even if we can't load the record in Aspen.
			$actionUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/patron-magazines/' . $issueId;

			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlSendPage($actionUrl, 'DELETE');
			$response = json_decode($rawResponse);
			if ($response == false) {
				$result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
				global $logger;
				$logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
			} else {
				$result['success'] = true;
				$result['message'] = "The magazine was returned successfully.";

				global $memCache;
				$memCache->delete('rbdigital_summary_' . $patron->id);
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
		if (isset($this->holds[$patron->id])) {
			return $this->holds[$patron->id];
		}

		$rbdigitalId = $this->getRBdigitalId($patron);

		$patronHoldsUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/holds';

		$patronHoldsRaw = $this->getConnectionSettings($patron)->curlWrapper->curlGetPage($patronHoldsUrl);
		$patronHolds = json_decode($patronHoldsRaw);

		$holds = array(
			'available' => array(),
			'unavailable' => array()
		);

		if ($rbdigitalId == false) {
			return $holds;
		}

		if (isset($patronHolds->message)) {
			//Error in RBdigital APIS
			global $logger;
			$logger->log("Error in RBdigital {$patronHolds->message}", Logger::LOG_WARNING);
		} else {
			foreach ($patronHolds as $tmpHold) {
				$hold = array();
				$hold['id'] = $tmpHold->isbn;
				$hold['transactionId'] = $tmpHold->transactionId;
				$hold['holdSource'] = 'RBdigital';

				require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
				$recordDriver = new RBdigitalRecordDriver($hold['id']);
				if ($recordDriver->isValid()) {
					$hold['groupedWorkId'] = $recordDriver->getPermanentId();
					$hold['coverUrl'] = $recordDriver->getBookcoverUrl('medium', true);
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
		}

		return $holds;
	}

	public function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$rbdigitalId = $this->getRBdigitalId($patron);
		if ($rbdigitalId == false) {
			$result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
		} else {
			$actionUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/holds/' . $recordId;

			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlPostPage($actionUrl, "");
			$response = json_decode($rawResponse);
			if ($response == false) {
				$result['message'] = "Invalid information returned from API, please retry your hold after a few minutes.";
				global $logger;
				$logger->log("Invalid information from rbdigital api\r\n$actionUrl\r\n$rawResponse", Logger::LOG_ERROR);
				$logger->log(print_r($this->getConnectionSettings($patron)->curlWrapper->getHeaders(), true), Logger::LOG_ERROR);
				$curl_info = curl_getinfo($this->getConnectionSettings($patron)->curlWrapper->curl_connection);
				$logger->log(print_r($curl_info, true), Logger::LOG_ERROR);
			} else {
				if (is_numeric($response)) {
					$this->trackUserUsageOfRBdigital($patron);
					$this->trackRecordHold($recordId);
					$result['success'] = true;
					$result['message'] = "<p class='alert alert-success'>" . translate(['text'=>"rbdigital_hold_success", 'defaultText'=>"Your hold was placed successfully."]) . "</p>";
					$result['hasWhileYouWait'] = false;

					//Get the grouped work for the record
					global $library;
					if ($library->showWhileYouWait) {
						require_once ROOT_DIR . '/RecordDrivers/RBdigitalRecordDriver.php';
						$recordDriver = new RBdigitalRecordDriver($recordId);
						if ($recordDriver->isValid()) {
							$groupedWorkId = $recordDriver->getPermanentId();
							require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
							$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
							$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

							global $interface;
							if (count($whileYouWaitTitles) > 0) {
								$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);
								$result['message'] .= '<h3>' . translate('While You Wait') . '</h3>';
								$result['message'] .= $interface->fetch('GroupedWork/whileYouWait.tpl');
								$result['hasWhileYouWait'] = true;
							}
						}
					}

					global $memCache;
					$memCache->delete('rbdigital_summary_' . $patron->id);
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
	 * @param User $patron The User to cancel the hold for
	 * @param string $recordId The id of the bib record
	 * @return  array
	 */
	function cancelHold($patron, $recordId, $cancelId = null)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$rbdigitalId = $this->getRBdigitalId($patron);
		if ($rbdigitalId == false) {
			$result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
		} else {
			$actionUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . $rbdigitalId . '/holds/' . $recordId;

			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlSendPage($actionUrl, 'DELETE');
			$response = json_decode($rawResponse);
			if ($response == false) {
				$result['message'] = "Invalid information returned from API, please retry your action after a few minutes.";
				global $logger;
				$logger->log("Invalid information from rbdigital api " . $rawResponse, Logger::LOG_ERROR);
			} else {
				if (!empty($response->message) && $response->message == 'success') {
					$result['success'] = true;
					$result['message'] = "Your hold was cancelled successfully.";
					global $memCache;
					$memCache->delete('rbdigital_summary_' . $patron->id);
				} else {
					$result['message'] = $response->message;
				}
			}
		}
		return $result;
	}

	/**
	 * @param User $user
	 *
	 * @return array
	 */
	public function getAccountSummary(User $user) : AccountSummary
	{
		list($existingId, $summary) = $user->getCachedAccountSummary('rbdigital');

		if ($summary === null) {
			global $timer;
			require_once ROOT_DIR . '/sys/User/AccountSummary.php';
			$summary = new AccountSummary();
			$summary->userId = $user->id;
			$summary->source = 'rbdigital';

			//Get the rbdigital id for the patron
			$rbdigitalId = $this->getRBdigitalId($user);

			if ($rbdigitalId !== false) {
				//Get account information from api
				$patronSummaryUrl = $this->getConnectionSettings($user)->webServiceURL . '/v1/tenants/' . $this->getConnectionSettings($user)->libraryId . '/patrons/' . $rbdigitalId . '/patron-config';

				$responseRaw = $this->getConnectionSettings($user)->curlWrapper->curlGetPage($patronSummaryUrl);
				$response = json_decode($responseRaw);

				$summary->numCheckedOut = empty($response->audioBooks->checkouts) ? 0 : count($response->audioBooks->checkouts);
				$summary->numCheckedOut += empty($response->magazines->checkouts) ? 0 : count($response->magazines->checkouts);

				//RBdigital automatically checks holds out so nothing is available
				$summary->numAvailableHolds = 0;
				$summary->numUnavailableHolds = empty($response->audioBooks->holds) ? 0 : count($response->audioBooks->holds);
			}else{
				$summary->numCheckedOut = 0;
				$summary->numAvailableHolds = 0;
				$summary->numUnavailableHolds = 0;
			}
			$summary->lastLoaded = time();
			if ($existingId != null) {
				$summary->id = $existingId;
				$summary->update();
			} else {
				$summary->insert();
			}
			$timer->logTime("Finished loading titles from rbdigital summary");
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
	public function getRBdigitalId(User $user)
	{
		if (!empty($user->rbdigitalId) && $user->rbdigitalId != -1 && !isset($_REQUEST['reload'])) {
			return $user->rbdigitalId;
		} else {
			//Check to see if we should do a lookup.  Check no more than every 15 minutes
			if (isset($_REQUEST['reload']) || $user->rbdigitalLastAccountCheck < time() - 15 * 60) {
				$lookupPatronUrl = $this->getConnectionSettings($user)->webServiceURL . '/v1/rpc/libraries/' . $this->getConnectionSettings($user)->libraryId . '/patrons/' . urlencode($user->getBarcode());

				$rawResponse = $this->getConnectionSettings($user)->curlWrapper->curlGetPage($lookupPatronUrl);
				$response = json_decode($rawResponse);
				if (is_null($response) || (isset($response->message) && ($response->message == 'Patron not found.'))) {
					//Do lookup by email address if settings allow.
					// Can be disabled because patron's can share email addresses
					if (!empty($user->email) && $this->getConnectionSettings($user)->allowPatronLookupByEmail) {
						$lookupPatronUrl = $this->getConnectionSettings($user)->webServiceURL . '/v1/rpc/libraries/' . $this->getConnectionSettings($user)->libraryId . '/patrons/' . urlencode($user->email);

						$rawResponse = $this->getConnectionSettings($user)->curlWrapper->curlGetPage($lookupPatronUrl);
						$response = json_decode($rawResponse);
						if (is_null($response) || !empty($response->message) && $response->message == 'Patron not found.') {
							$rbdigitalId = -1;
						} else if (!empty($response->message)) {
							global $logger;
							$logger->log("New Message checking if patron exists by email {$response->message}", Logger::LOG_DEBUG);
							$rbdigitalId = -1;
						} else {
							$rbdigitalId = $response->patronId;
						}
					} else {
						$rbdigitalId = -1;
					}
				} else if (!empty($response->message)) {
					global $logger;
					$logger->log("New Message checking if patron exists {$response->message}", Logger::LOG_DEBUG);
					$rbdigitalId = -1;
				} else {
					$rbdigitalId = $response->patronId;
				}
				$user->rbdigitalId = $rbdigitalId;
				$user->rbdigitalLastAccountCheck = time();
				$user->update();
				if ($rbdigitalId == -1) {
					return false;
				} else {
					return $rbdigitalId;
				}
			} else {
				return false;
			}
		}
	}

	public function redirectToRBdigitalMagazine(User $patron, RBdigitalMagazineDriver $recordDriver)
	{
		$token = $this->addBearerTokenHeader($patron);
		$redirectUrl = $recordDriver->getRBdigitalLinkUrl($patron);
		if (!empty($token)) {
			$redirectUrl .= '?bearer=' . $token;
		}
		header('Location:' . $redirectUrl);
		die();
	}

	public function redirectToRBdigital(User $patron, RBdigitalRecordDriver $recordDriver)
	{
		$token = $this->addBearerTokenHeader($patron);
		$redirectUrl = $this->getConnectionSettings($patron)->userInterfaceURL . '/book/' . $recordDriver->getUniqueID();
		if (!empty($token)) {
			$redirectUrl .= '?bearer=' . $token;
		}
		header('Location:' . $redirectUrl);
		die();
//        $result = ['success' => false, 'message' => 'Unknown error'];
//        $rbdigitalId = $this->getRBdigitalId($patron);
//        if ($rbdigitalId == false) {
//            $result['message'] = 'Sorry, you are not registered with RBdigital.  You will need to create an account there before continuing.';
//        } else {
//            //Get the link to redirect to with the proper bearer information
//            /*
//             * POST to api.rbdigital.com/v1/tokens/
//                with values of…
//                libraryId
//                UserName
//                Password
//
//                You should get a bearer token in response along the lines of...
//                {"bearer": "5cc2058bd2b76b28943de9cf","result": true}
//
//                …and should then be able to set an authorization header using…
//                bearer 5cc2063fd2b76b28943deb32
//             */

//        }
//        return $result;

	}

	/**
	 * @param $user
	 */
	public function trackUserUsageOfRBdigital($user): void
	{
		require_once ROOT_DIR . '/sys/RBdigital/UserRBdigitalUsage.php';
		$userUsage = new UserRBdigitalUsage();
		$userUsage->instance = $_SERVER['SERVER_NAME'];
		$userUsage->userId = $user->id;
		$userUsage->year = date('Y');
		$userUsage->month = date('n');

		if ($userUsage->find(true)) {
			$userUsage->usageCount++;
			$userUsage->update();
		} else {
			$userUsage->usageCount = 1;
			$userUsage->insert();
		}
	}

	/**
	 * @param int $rbdigitalId
	 */
	function trackRecordCheckout($rbdigitalId): void
	{
		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalRecordUsage.php';
		$recordUsage = new RBdigitalRecordUsage();
		$product = new RBdigitalProduct();
		$product->rbdigitalId = $rbdigitalId;
		if ($product->find(true)) {
			$recordUsage->instance = $_SERVER['SERVER_NAME'];
			$recordUsage->rbdigitalId = $product->id;
			$recordUsage->year = date('Y');
			$recordUsage->month = date('n');
			if ($recordUsage->find(true)) {
				$recordUsage->timesCheckedOut++;
				$recordUsage->update();
			} else {
				$recordUsage->timesCheckedOut = 1;
				$recordUsage->timesHeld = 0;
				$recordUsage->insert();
			}
		}
	}

	function trackMagazineCheckout($magazineId, $issueId): void
	{
		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalMagazineUsage.php';
		$recordUsage = new RBdigitalMagazineUsage();
		$product = new RBdigitalMagazine();
		$product->magazineId = $magazineId;
		if ($product->find(true)) {
			$recordUsage->instance = $_SERVER['SERVER_NAME'];
			$recordUsage->magazineId = $product->id;
			$recordUsage->issueId = $issueId;
			$recordUsage->year = date('Y');
			$recordUsage->month = date('n');
			if ($recordUsage->find(true)) {
				$recordUsage->timesCheckedOut++;
				$recordUsage->update();
			} else {
				$recordUsage->timesCheckedOut = 1;
				$recordUsage->insert();
			}
		}
	}

	/**
	 * @param int $rbdigitalId
	 */
	function trackRecordHold($rbdigitalId): void
	{
		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalRecordUsage.php';
		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalProduct.php';
		$recordUsage = new RBdigitalRecordUsage();
		$product = new RBdigitalProduct();
		$product->rbdigitalId = $rbdigitalId;
		if ($product->find(true)) {
			$recordUsage->instance = $_SERVER['SERVER_NAME'];
			$recordUsage->rbdigitalId = $product->id;
			$recordUsage->year = date('Y');
			$recordUsage->month = date('n');
			if ($recordUsage->find(true)) {
				$recordUsage->timesHeld++;
				$recordUsage->update();
			} else {
				$recordUsage->timesCheckedOut = 0;
				$recordUsage->timesHeld = 1;
				$recordUsage->insert();
			}
		}

	}

	/**
	 * @param User $patron
	 * @return string|null
	 */
	private function addBearerTokenHeader(User $patron)
	{
		if (!empty($patron->rbdigitalUsername) && !empty($patron->rbdigitalPassword)) {
			$tokenUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/tokens/';
			$userData = [
				'libraryId' => $this->getConnectionSettings($patron)->libraryId,
				'UserName' => $patron->rbdigitalUsername,
				'Password' => $patron->rbdigitalPassword,
			];
			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlPostPage($tokenUrl, $userData);
			$response = json_decode($rawResponse);

			if ($response == false) {
				$result['message'] = "Invalid information returned from API, please retry your hold after a few minutes.";
				global $logger;
				$logger->log("Invalid information from rbdigital api\r\n$tokenUrl\r\n$rawResponse", Logger::LOG_ERROR);
				$logger->log(print_r($this->getConnectionSettings($patron)->curlWrapper->getHeaders(), true), Logger::LOG_ERROR);
				$curl_info = curl_getinfo($this->getConnectionSettings($patron)->curlWrapper->curl_connection);
				$logger->log(print_r($curl_info, true), Logger::LOG_ERROR);
			} else {
				//We should get back a bearer token
				if ($response->result == true) {
					$bearerToken = $response->bearer;
					header('Authorization: bearer ' . $bearerToken);
					return $bearerToken;
				}
			}
		}elseif (!empty($patron->email)){
			$this->getConnectionSettings($patron)->curlWrapper->timeout = 10;
			$patronUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/rpc/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/patrons/' . urlencode($patron->email);
			$rawResponse = $this->getConnectionSettings($patron)->curlWrapper->curlGetPage($patronUrl);
			$response = json_decode($rawResponse);
			if ($response != false) {
				$tokenUrl = $this->getConnectionSettings($patron)->webServiceURL . '/v1/libraries/' . $this->getConnectionSettings($patron)->libraryId . '/tokens';
				$userData = [
					'userId' => $response->userId,
				];
				$rawTokenResponse = $this->getConnectionSettings($patron)->curlWrapper->curlPostBodyData($tokenUrl, $userData);
				$tokenResponse = json_decode($rawTokenResponse);
				if ($tokenResponse != false) {
					if (!empty($tokenResponse->message)){
						return false;
					}
					return $tokenResponse->bearer;
				}
			}
		}
		return null;

//...with that userId GUID value, you can generate a bearer token via...
//POST https://api.rbdigital.com/v1/libraries/[libraryId]/tokens
//{
//	"userId":"[GUID]"
//}
//
//...which will return a bearer token.
//	Assuming you know the ISBN for the title of interest, you can use the bearer token as per...
//https://[subdomain].rbdigital.com/book/[ISBN]?bearer=[bearer token]
	}

	/**
	 * @param User $patron
	 * @return RBDigitalConnectionSettings|null
	 */
	private function getConnectionSettings(User $patron)
	{
		$homeLibrary = $patron->getHomeLibrary();
		if (array_key_exists($homeLibrary->libraryId, $this->connectionSettings)){
			return $this->connectionSettings[$homeLibrary->libraryId];
		}
		$this->connectionSettings[$homeLibrary->libraryId] = new RBDigitalConnectionSettings();
		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalSetting.php';
		require_once ROOT_DIR . '/sys/RBdigital/RBdigitalScope.php';
		try {
			$rbdigitalScope = new RBdigitalScope();
			$rbdigitalScope->id = $homeLibrary->rbdigitalScopeId;
			if ($rbdigitalScope->find(true)){
				$rbdigitalSettings = new RBdigitalSetting();
				$rbdigitalSettings->id = $rbdigitalScope->settingId;
				if ($rbdigitalSettings->find(true)) {
					$this->connectionSettings[$homeLibrary->libraryId]->valid = true;

					$this->connectionSettings[$homeLibrary->libraryId]->webServiceURL = $rbdigitalSettings->apiUrl;
					$this->connectionSettings[$homeLibrary->libraryId]->userInterfaceURL = $rbdigitalSettings->userInterfaceUrl;
					$this->connectionSettings[$homeLibrary->libraryId]->apiToken = $rbdigitalSettings->apiToken;
					$this->connectionSettings[$homeLibrary->libraryId]->libraryId = $rbdigitalSettings->libraryId;
					$this->connectionSettings[$homeLibrary->libraryId]->allowPatronLookupByEmail = $rbdigitalSettings->allowPatronLookupByEmail;

					$this->connectionSettings[$homeLibrary->libraryId]->curlWrapper = new CurlWrapper();
					$headers = [
						'Accept: application/json',
						'Authorization: basic ' . strtolower($this->connectionSettings[$homeLibrary->libraryId]->apiToken),
						'Content-Type: application/json'
					];
					$this->connectionSettings[$homeLibrary->libraryId]->curlWrapper->addCustomHeaders($headers, true);
				}
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Could not load RBdigital settings", Logger::LOG_ALERT);
		}
		return $this->connectionSettings[$homeLibrary->libraryId];
	}
}

class RBDigitalConnectionSettings{
	public $valid = false;
	public $webServiceURL;
	public $userInterfaceURL;
	public $apiToken;
	public $libraryId;
	public $allowPatronLookupByEmail;

	/** @var CurlWrapper */
	public $curlWrapper;
}