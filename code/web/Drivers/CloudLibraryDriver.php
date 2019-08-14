<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';
class CloudLibraryDriver extends AbstractEContentDriver
{
	/** @var CurlWrapper */
	private $curlWrapper;

	public function __construct()
	{
		$this->curlWrapper = new CurlWrapper();
	}

	public function hasNativeReadingHistory()
	{
		return false;
	}

	private $checkouts = [];
	/**
	 * Get Patron Checkouts
	 *
	 * This is responsible for retrieving all checkouts (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 * @return array        Array of the patron's transactions on success
	 * @access public
	 */
	public function getCheckouts(User $user)
	{
		if (isset($this->checkouts[$user->id])){
			return $this->checkouts[$user->id];
		}

		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';

		$circulation = $this->getPatronCirculation($user);
		$checkouts = [];

		if (isset($circulation->Checkouts->Item)) {
			foreach ($circulation->Checkouts->Item as $checkoutFromCloudLibrary) {
				$checkout = [];
				$checkout['checkoutSource'] = 'CloudLibrary';

				$checkout['id'] = (string)$checkoutFromCloudLibrary->ItemId;
				$checkout['recordId'] = (string)$checkoutFromCloudLibrary->ItemId;
				$checkout['dueDate'] = (string)$checkoutFromCloudLibrary->EventEndDateInUTC;

				//Checkouts cannot be renewed in CloudLibrary
				$checkout['canrenew'] = false;

				$recordDriver = new CloudLibraryRecordDriver((string)$checkoutFromCloudLibrary->ItemId);
				if ($recordDriver->isValid()) {
					$checkout['title'] = $recordDriver->getTitle();
					$curTitle['title_sort'] = $recordDriver->getTitle();
					$checkout['author'] = $recordDriver->getPrimaryAuthor();
					$checkout['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
					$checkout['ratingData'] = $recordDriver->getRatingData();
					$checkout['groupedWorkId'] = $recordDriver->getGroupedWorkId();
					$checkout['format'] = $recordDriver->getPrimaryFormat();
					$checkout['linkUrl'] = $recordDriver->getLinkUrl();
				} else {
					$checkout['title'] = 'Unknown Cloud Library Title';
					$checkout['author'] = '';
					$checkout['format'] = 'Unknown - Cloud Library';
				}

				$checkout['user'] = $user->getNameAndLibraryLabel();
				$checkout['userId'] = $user->id;

				$checkouts[] = $checkout;
			}
		}

		return $checkouts;
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
	 * @return mixed
	 */
	public function renewCheckout($patron, $recordId)
	{
		return false;
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
		$settings = $this->getSettings();
		$patronId = $patron->getBarcode();
		$patronId = "tltech02";
		$apiPath = "/cirrus/library/{$settings->libraryId}/checkin";
		$requestBody =
			"<CheckinRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</CheckinRequest>";
		$this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		$responseCode = $this->curlWrapper->getResponseCode();
		if ($responseCode == '200'){
			$result['success'] = true;
			$result['message'] = translate("Your title was returned successfully.");

			/** @var Memcache $memCache */
			global $memCache;
			$memCache->delete('cloud_library_summary_' . $patron->id);
		}else if ($responseCode == '400'){
			$result['message'] = translate("Bad Request.");
		}else if ($responseCode == '403'){
			$result['message'] = translate("Unable to authenticate.");
		}else if ($responseCode == '404'){
			$result['message'] = translate("Checkout was not found.");
		}
		return $result;
	}

	private $holds = [];
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

		require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';

		$circulation = $this->getPatronCirculation($user);
		$holds = array(
			'available' => array(),
			'unavailable' => array()
		);

		if (isset($circulation->Holds->Item)) {
			foreach ($circulation->Holds->Item as $holdFromCloudLibrary) {
				$hold = [];
				$hold['holdSource'] = 'CloudLibrary';

				$hold['id'] = (string)$holdFromCloudLibrary->ItemId;
				$hold['transactionId'] = (string)$holdFromCloudLibrary->ItemId;

				$recordDriver = new CloudLibraryRecordDriver((string)$holdFromCloudLibrary->ItemId);
				if ($recordDriver->isValid()) {
					$hold['title'] = $recordDriver->getTitle();
					$curTitle['sortTitle'] = $recordDriver->getTitle();
					$hold['author'] = $recordDriver->getPrimaryAuthor();
					$hold['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
					$hold['ratingData'] = $recordDriver->getRatingData();
					$hold['format'] = $recordDriver->getPrimaryFormat();
					$hold['linkUrl'] = $recordDriver->getLinkUrl();
				} else {
					$hold['title'] = 'Unknown';
					$hold['author'] = 'Unknown';
				}

				$hold['user'] = $user->getNameAndLibraryLabel();
				$hold['userId'] = $user->id;

				$key = $hold['holdSource'] . $hold['id'] . $hold['user'];
				$holds['unavailable'][$key] = $hold;
			}
		}

		return $holds;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds as well as placing recalls.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @return  array                 An array with the following keys
	 *                                result - true/false
	 *                                message - the message to display (if item holds are required, this is a form to select the item).
	 *                                needsItemLevelHold - An indicator that item level holds are required
	 *                                title - the title of the record the user is placing a hold on
	 * @access  public
	 */
	public function placeHold($patron, $recordId)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$settings = $this->getSettings();
		$patronId = $patron->getBarcode();
		$patronId = "tltech02";
		$apiPath = "/cirrus/library/{$settings->libraryId}/placehold";
		$requestBody =
			"<PlaceHoldRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</PlaceHoldRequest>";
		$placeHoldResponse = $this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		$responseCode = $this->curlWrapper->getResponseCode();
		if ($responseCode == '201'){
			$this->trackUserUsageOfCloudLibrary($patron);
			$this->trackRecordHold($recordId);

			$result['success'] = true;
			$result['message'] = translate("Your hold was placed successfully.");

			/** @var Memcache $memCache */
			global $memCache;
			$memCache->delete('cloud_library_summary_' . $patron->id);
		}else if ($responseCode == '405'){
			$result['message'] = translate("Bad Request.");
		}else if ($responseCode == '403'){
			$result['message'] = translate("Unable to authenticate.");
		}else if ($responseCode == '404'){
			$result['message'] = translate("Item was not found.");
		}else if ($responseCode == '404'){
			$result['message'] = translate(['text'=>'cloud_library_already_checked_out', 'defaultText'=>'Could not place hold.  Already on hold or the item can be checked out']);
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
	function cancelHold($patron, $recordId)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$settings = $this->getSettings();
		$patronId = $patron->getBarcode();
		$patronId = "tltech02";
		$apiPath = "/cirrus/library/{$settings->libraryId}/cancelhold";
		$requestBody =
			"<CancelHoldRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</CancelHoldRequest>";
		$cancelHoldResponse = $this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		$responseCode = $this->curlWrapper->getResponseCode();
		if ($responseCode == '200'){
			$result['success'] = true;
			$result['message'] = translate("Your hold was cancelled successfully.");

			/** @var Memcache $memCache */
			global $memCache;
			$memCache->delete('cloud_library_summary_' . $patron->id);
		}else if ($responseCode == '400'){
			$result['message'] = translate("Bad Request.");
		}else if ($responseCode == '403'){
			$result['message'] = translate("Unable to authenticate.");
		}else if ($responseCode == '404'){
			$result['message'] = translate("Item was not found.");
		}
		return $result;
	}

	public function getAccountSummary($patron)
	{
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

		$summary = $memCache->get('cloud_library_summary_' . $patron->id);
		if ($summary == false || isset($_REQUEST['reload'])){
			//Get the rbdigital id for the patron
			$patronId = $patron->getBarcode();
			$patronId = "tltech02";

			//Get account information from api
			$circulation = $this->getPatronCirculation($patron);

			$summary = array();
			$summary['numCheckedOut'] = empty($circulation->Checkouts->Item) ? 0 : count($circulation->Checkouts->Item);

			//RBdigital automatically checks holds out so nothing is available
			$summary['numAvailableHolds'] = 0;
			$summary['numUnavailableHolds'] = empty($circulation->Holds->Item) ? 0 : count($circulation->Holds->Item);

			$timer->logTime("Finished loading titles from Cloud Library summary");
			$memCache->set('cloud_library_summary_' . $patron->id, $summary, $configArray['Caching']['account_summary']);
		}

		return $summary;
	}

	/**
	 * @param User $user
	 * @param string $titleId
	 *
	 * @return array
	 */
	public function checkOutTitle($user, $titleId)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$settings = $this->getSettings();
		$patronId = $user->getBarcode();
		$patronId = "tltech02";
		$apiPath = "/cirrus/library/{$settings->libraryId}/checkout";
		$requestBody =
			"<CheckoutRequest>
				<ItemId>{$titleId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</CheckoutRequest>";
		$checkoutResponse = $this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		if ($checkoutResponse != null){
			$checkoutXml = simplexml_load_string($checkoutResponse);
			if (isset($checkoutXml->Error)){
			$result['message'] = $checkoutXml->Error->Message;
			}else {
				$this->trackUserUsageOfCloudLibrary($user);
				$this->trackRecordCheckout($titleId);

				$result['success'] = true;
				$result['message'] = translate(['text' => 'cloud_library-checkout-success', 'defaultText' => 'Your title was checked out successfully. You can read or listen to the title from your account.']);

				/** @var Memcache $memCache */
				global $memCache;
				$memCache->delete('cloud_library_summary_' . $user->id);
			}
		}
		return $result;
	}

	private $circulationInfo = [];
	private function getPatronCirculation(User $user)
	{
		if (!isset($this->circulationInfo[$user->id])){
			$settings = $this->getSettings();
			$patronId = $user->getBarcode();
			$patronId = "tltech02";
			$apiPath = "/cirrus/library/{$settings->libraryId}/circulation/patron/$patronId";
			$circulationInfo = $this->callCloudLibraryUrl($settings, $apiPath);
			$this->circulationInfo[$user->id] = simplexml_load_string($circulationInfo);
		}
		return $this->circulationInfo[$user->id];
	}

	private function getSettings(){
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
		$settings = new CloudLibrarySetting();
		$settings->find(true);
		return $settings;
	}

	private function callCloudLibraryUrl(CloudLibrarySetting $settings, string $apiPath, $method = 'GET', $requestBody = null)
	{
		$nowUtcDate = gmdate('D, d M Y H:i:s T');
		$dataToSign = $nowUtcDate . "\n" . $method . "\n" . $apiPath;
		$signature = base64_encode(hash_hmac("sha256", $dataToSign, $settings->accountKey, true));

		$headers = [
			"3mcl-Datetime: $nowUtcDate",
			"3mcl-Authorization: 3MCLAUTH {$settings->accountId}:$signature",
			'3mcl-APIVersion: 3.0',
			'Content-Type: application/xml',
			'Accept: application/xml'
		];

		$this->curlWrapper->addCustomHeaders($headers, true);
		$response = $this->curlWrapper->curlSendPage($settings->apiUrl . $apiPath, $method, $requestBody);

		return $response;
	}

	/**
	 * @param $user
	 */
	public function trackUserUsageOfCloudLibrary($user): void
	{
		require_once ROOT_DIR . '/sys/CloudLibrary/UserCloudLibraryUsage.php';
		$userUsage = new UserCloudLibraryUsage();
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
	 * @param string $recordId
	 */
	function trackRecordCheckout($recordId): void
	{
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryRecordUsage.php';
		$recordUsage = new CloudLibraryRecordUsage();
		$product = new CloudLibraryProduct();
		$product->cloudLibraryId = $recordId;
		if ($product->find(true)) {
			$recordUsage->cloudLibraryId = $product->id;
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

	/**
	 * @param string $recordId
	 */
	function trackRecordHold($recordId): void
	{
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryRecordUsage.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryProduct.php';
		$recordUsage = new CloudLibraryRecordUsage();
		$product = new CloudLibraryProduct();
		$product->cloudLibraryId = $recordId;
		if ($product->find(true)){
			$recordUsage->cloudLibraryId = $product->id;
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
}