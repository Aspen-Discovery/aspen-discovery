<?php

require_once ROOT_DIR . '/Drivers/AbstractEContentDriver.php';
class CloudLibraryDriver extends AbstractEContentDriver
{
	/** @var CurlWrapper */
	private $curlWrapper;

	public function initCurlWrapper()
	{
		$this->curlWrapper = new CurlWrapper();
		$this->curlWrapper->timeout = 20;
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

		$checkouts = [];
		$settings = $this->getSettings($user);
		if ($settings == false){
			return $checkouts;
		}

		$circulation = $this->getPatronCirculation($user);

		if (isset($circulation->Checkouts->Item)) {
			foreach ($circulation->Checkouts->Item as $checkoutFromCloudLibrary) {
				$checkout = [];
				$checkout['checkoutSource'] = 'CloudLibrary';

				$checkout['id'] = (string)$checkoutFromCloudLibrary->ItemId;
				$checkout['recordId'] = (string)$checkoutFromCloudLibrary->ItemId;
				$checkout['dueDate'] = (string)$checkoutFromCloudLibrary->EventEndDateInUTC;

				try {
					$dueDate = new DateTime($checkout['dueDate'], new DateTimeZone('UTC'));
					$timeDiff = $dueDate->getTimestamp() - time();
					//Checkouts cannot be renewed 3 days before the title is due
					if ($timeDiff < (3*24*60*60)){
						$checkout['canRenew'] = true;
					}else{
						$checkout['canRenew'] = false;
					}
				} catch (Exception $e) {
					$checkout['canRenew'] = false;
				}

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
					$checkout['accessOnlineUrl'] = $recordDriver->getAccessOnlineLink($user);
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
	function renewCheckout($patron, $recordId, $itemId = null, $itemIndex = null)
	{
		return $this->checkOutTitle($patron, $recordId, true);
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
		$settings = $this->getSettings($patron);
		$patronId = str_replace(' ', '', $patron->getBarcode());
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
			$memCache->delete('cloud_library_circulation_info_' . $patron->id);
		}else if ($responseCode == '400'){
			$result['message'] = translate("Bad Request returning checkout.");
			global $configArray;
			if (IPAddress::showDebuggingInformation()){
				$result['message'] .= "\r\n" . $requestBody;
			}
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

		$holds = array(
			'available' => array(),
			'unavailable' => array()
		);

		$settings = $this->getSettings($user);
		if ($settings == false){
			return $holds;
		}
		$circulation = $this->getPatronCirculation($user);

		if (isset($circulation->Holds->Item)) {
			$index = 0;
			foreach ($circulation->Holds->Item as $holdFromCloudLibrary) {
				$hold = $this->loadCloudLibraryHoldInfo($user, $holdFromCloudLibrary);

				$key = $hold['holdSource'] . $hold['id'] . $hold['user'];
				$hold['position'] = (string)$holdFromCloudLibrary->Position;
				$holds['unavailable'][$key] = $hold;
				$index++;
			}
		}

		if (isset($circulation->Reserves->Item)) {
			$index = 0;
			foreach ($circulation->Reserves->Item as $holdFromCloudLibrary) {
				$hold = $this->loadCloudLibraryHoldInfo($user, $holdFromCloudLibrary);

				$key = $hold['holdSource'] . $hold['id'] . $hold['user'];
				$holds['available'][$key] = $hold;
				$index++;
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
	function placeHold($patron, $recordId, $pickupBranch = null, $cancelDate = null)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$settings = $this->getSettings($patron);
		$patronId = str_replace(' ', '', $patron->getBarcode());
		$password = $patron->getPasswordOrPin();
		$patronEligibleForHolds = $patron->eligibleForHolds();
		if ($patronEligibleForHolds['fineLimitReached']){
			$result['message'] = translate(['text' => 'cl_outstanding_fine_limit', 'defaultText' => 'Sorry, your account has too many outstanding fines to use Cloud Library.']);
			return $result;
		}

		$apiPath = "/cirrus/library/{$settings->libraryId}/placehold?password=$password";
		$requestBody =
			"<PlaceHoldRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</PlaceHoldRequest>";
		$this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		$responseCode = $this->curlWrapper->getResponseCode();
		if ($responseCode == '201'){
			$this->trackUserUsageOfCloudLibrary($patron);
			$this->trackRecordHold($recordId);

			$result['success'] = true;
			$result['message'] = "<p class='alert alert-success'>" . translate(['text'=>"cloud_library_hold_success", 'defaultText'=>"Your hold was placed successfully."]) . "</p>";
			$result['hasWhileYouWait'] = false;

			//Get the grouped work for the record
			global $library;
			if ($library->showWhileYouWait) {
				require_once ROOT_DIR . '/RecordDrivers/CloudLibraryRecordDriver.php';
				$recordDriver = new CloudLibraryRecordDriver($recordId);
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
			$memCache->delete('cloud_library_summary_' . $patron->id);
			$memCache->delete('cloud_library_circulation_info_' . $patron->id);
		}else if ($responseCode == '405'){
			$result['message'] = translate("Bad Request placing hold.");
			global $configArray;
			if (IPAddress::showDebuggingInformation()){
				$result['message'] .= "\r\n" . $requestBody;
			}
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
	function cancelHold($patron, $recordId, $cancelId = null)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];
		$settings = $this->getSettings($patron);
		$patronId = str_replace(' ', '', $patron->getBarcode());
		$apiPath = "/cirrus/library/{$settings->libraryId}/cancelhold";
		$requestBody =
			"<CancelHoldRequest>
				<ItemId>{$recordId}</ItemId>
				<PatronId>{$patronId}</PatronId>
			</CancelHoldRequest>";
		$this->callCloudLibraryUrl($settings, $apiPath, 'POST', $requestBody);
		$responseCode = $this->curlWrapper->getResponseCode();
		if ($responseCode == '200'){
			$result['success'] = true;
			$result['message'] = translate("Your hold was cancelled successfully.");

			/** @var Memcache $memCache */
			global $memCache;
			$memCache->delete('cloud_library_summary_' . $patron->id);
			$memCache->delete('cloud_library_circulation_info_' . $patron->id);
		}else if ($responseCode == '400'){
			$result['message'] = translate("Bad Request cancelling hold.");
			global $configArray;
			if (IPAddress::showDebuggingInformation()){
				$result['message'] .= "\r\n" . $requestBody;
			}
		}else if ($responseCode == '403'){
			$result['message'] = translate("Unable to authenticate.");
		}else if ($responseCode == '404'){
			$result['message'] = translate("Item was not found.");
		}
		return $result;
	}

	public function getAccountSummary($patron)
	{
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
			//Get account information from api
			$circulation = $this->getPatronCirculation($patron);

			$summary = array();
			$summary['numCheckedOut'] = empty($circulation->Checkouts->Item) ? 0 : count($circulation->Checkouts->Item);

			//RBdigital automatically checks holds out so nothing is available
			$summary['numAvailableHolds'] = empty($circulation->Reserves->Item) ? 0 : count($circulation->Reserves->Item);
			$summary['numUnavailableHolds'] = empty($circulation->Holds->Item) ? 0 : count($circulation->Holds->Item);
			$summary['numHolds'] = $summary['numAvailableHolds'] + $summary['numUnavailableHolds'];

			$timer->logTime("Finished loading titles from Cloud Library summary");
			$memCache->set('cloud_library_summary_' . $patron->id, $summary, $configArray['Caching']['account_summary']);
		}

		return $summary;
	}

	/**
	 * @param User $user
	 * @param string $titleId
	 *
	 * @param bool $fromRenew
	 * @return array
	 */
	public function checkOutTitle($user, $titleId, $fromRenew = false)
	{
		$result = ['success' => false, 'message' => 'Unknown error'];

		$settings = $this->getSettings($user);
		$patronId = str_replace(' ', '', $user->getBarcode());
		$password = $user->getPasswordOrPin();
		if (!$user->eligibleForHolds()){
			$result['message'] = translate(['text' => 'cl_outstanding_fine_limit', 'defaultText' => 'Sorry, your account has too many outstanding fines to use Cloud Library.']);
			return $result;
		}

		$apiPath = "/cirrus/library/{$settings->libraryId}/checkout?password=$password";
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
				$user->lastReadingHistoryUpdate = 0;
				$user->update();

				$result['success'] = true;
				if ($fromRenew){
					$result['message'] = translate(['text' => 'cloud_library-renew-success', 'defaultText' => 'Your title was renewed successfully.']);
				}else {
					$result['message'] = translate(['text' => 'cloud_library-checkout-success', 'defaultText' => 'Your title was checked out successfully. You can read or listen to the title from your account.']);
				}

				global $memCache;
				$memCache->delete('cloud_library_summary_' . $user->id);
				$memCache->delete('cloud_library_circulation_info_' . $user->id);
			}
		}
		return $result;
	}

	private function getPatronCirculation(User $user)
	{
		$settings = $this->getSettings($user);
		if ($settings != false) {
			global $memCache;
			$circulationInfo = $memCache->get('cloud_library_circulation_info_' . $user->id);
			if ($circulationInfo == false || isset($_REQUEST['reload'])) {
				$patronId = str_replace(' ', '', $user->getBarcode());
				$password = $user->getPasswordOrPin();
				$apiPath = "/cirrus/library/{$settings->libraryId}/circulation/patron/$patronId?password=$password";
				$circulationInfo = $this->callCloudLibraryUrl($settings, $apiPath);
				global $configArray;
				$memCache->set('cloud_library_circulation_info_' . $user->id, $circulationInfo, $configArray['Caching']['account_summary']);
			}
			return simplexml_load_string($circulationInfo);
		}else{
			return null;
		}
	}

	private function getSettings(User $user = null){
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryScope.php';
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibrarySetting.php';
		$activeLibrary = null;
		if ($user != null){
			$activeLibrary = $user->getHomeLibrary();
		}
		if ($activeLibrary == null){
			global $library;
			$activeLibrary = $library;
		}
		$scope = new CloudLibraryScope();
		$scope->id = $activeLibrary->cloudLibraryScopeId;
		if ($activeLibrary->cloudLibraryScopeId > 0){
			if ($scope->find(true)) {
				$settings = new CloudLibrarySetting();
				$settings->id = $scope->settingId;
				if ($settings->find(true)) {
					return $settings;
				} else {
					return false;
				}
			}else{
				return false;
			}
		}else {
			return false;
		}
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

		//Can't reuse the curl wrapper so make sure it is initialized on each call
		$this->initCurlWrapper();
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
		/** @noinspection DuplicatedCode */
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
		require_once ROOT_DIR . '/sys/CloudLibrary/CloudLibraryProduct.php';
		$recordUsage = new CloudLibraryRecordUsage();
		$product = new CloudLibraryProduct();
		$product->cloudLibraryId = $recordId;
		if ($product->find(true)) {
			$recordUsage->instance = $_SERVER['SERVER_NAME'];
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
			$recordUsage->instance = $_SERVER['SERVER_NAME'];
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

	function checkAuthentication(User $user){
		$settings = $this->getSettings($user);
		if ($settings == false){
			return false;
		}
		$patronId = str_replace(' ', '', $user->getBarcode());
		$password = $user->getPasswordOrPin();
		$apiPath = "/cirrus/library/{$settings->libraryId}/patron/$patronId";
		if (false){
			$apiPath .= "?password=$password";
		}
		$authenticationResponse = $this->callCloudLibraryUrl($settings, $apiPath);
		/** @var SimpleXMLElement $authentication */
		$authentication = simplexml_load_string($authenticationResponse);
		/** @noinspection PhpUndefinedFieldInspection */
		if ($authentication->result == 'SUCCESS'){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @param $user
	 * @param $holdFromCloudLibrary
	 * @return array
	 */
	private function loadCloudLibraryHoldInfo(User $user, $holdFromCloudLibrary): array
	{
		$hold = [];
		$hold['holdSource'] = 'CloudLibrary';

		$hold['id'] = (string)$holdFromCloudLibrary->ItemId;
		$hold['transactionId'] = (string)$holdFromCloudLibrary->ItemId;

		$recordDriver = new CloudLibraryRecordDriver((string)$holdFromCloudLibrary->ItemId);
		if ($recordDriver->isValid()) {
			$hold['groupedWorkId'] = $recordDriver->getPermanentId();
			$hold['title'] = $recordDriver->getTitle();
			$hold['sortTitle'] = $recordDriver->getTitle();
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
		return $hold;
	}

	/**
	 * @param string $itemId
	 * @param User $patron
	 *
	 * @return null|string
	 */
	public function getItemStatus($itemId, $patron){
		$settings = $this->getSettings($patron);
		$patronId = str_replace(' ', '', $patron->getBarcode());
		$apiPath = "/cirrus/library/{$settings->libraryId}/item/status/$patronId/$itemId";
		$itemStatusInfo = $this->callCloudLibraryUrl($settings, $apiPath);
		if ($this->curlWrapper->getResponseCode() == 200){
			$itemStatus = simplexml_load_string($itemStatusInfo);
			$this->curlWrapper = new CurlWrapper();
			return (string)$itemStatus->DocumentStatus->status;
		}else if ($this->curlWrapper->getResponseCode() == 403){
			$errorMessage = simplexml_load_string($itemStatusInfo);
			return (string)$errorMessage->Message;
		}else{
			return false;
		}
	}

	public function redirectToCloudLibrary(User $patron, CloudLibraryRecordDriver $recordDriver)
	{
		$settings = $this->getSettings($patron);
		$userInterfaceUrl = $settings->userInterfaceUrl;
		if (substr($userInterfaceUrl, -1) == '/'){
			$userInterfaceUrl = substr($userInterfaceUrl, 0, -1);
		}

		//Setup the default redirection paths
		if ($recordDriver->getPrimaryFormat() == 'MP3'){
			$redirectUrl = $userInterfaceUrl . '/AudioPlayer/' . $recordDriver->getId();
		}else{
			$redirectUrl = $userInterfaceUrl . '/EPubRead/' . $recordDriver->getId();
		}

		//Login the user to CloudLibrary
		$loginUrl = "{$userInterfaceUrl}/login";
		$postParams = [
			'username' => $patron->getBarcode(),
			'password' => $patron->getPasswordOrPin(),
		];
		$curlWrapper = new CurlWrapper();
		$headers  = array(
			'Content-Type: application/x-www-form-urlencoded',
		);
		$curlWrapper->addCustomHeaders($headers, false);
		$response = $curlWrapper->curlPostPage($loginUrl, $postParams, [CURLOPT_HEADER => true]);
		if ($response){
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
			$cookies = array();
			foreach($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}
			foreach ($cookies as $name => $value){
				if (strpos($name, 'sessionid_') === 0){
					if ($recordDriver->getPrimaryFormat() == 'MP3'){
						//TODO: Need a new URL from CloudLibrary for audio books
						$redirectUrl = "$userInterfaceUrl/audiobooks/{$recordDriver->getId()}?auth_cookie={$value}";
					}else{
						$redirectUrl = "$userInterfaceUrl/ebooks/{$recordDriver->getId()}?auth_cookie={$value}";
					}

					break;
				}
			}
		}
		header('Location:' . $redirectUrl);
		die();
	}
}