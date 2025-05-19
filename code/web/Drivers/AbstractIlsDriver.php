<?php

/**
 * Catalog Specific Driver Class
 *
 * This interface class is the definition of the required methods for
 * interacting with the local catalog.
 */
require_once ROOT_DIR . '/Drivers/AbstractDriver.php';
require_once ROOT_DIR . '/sys/SIP2.php';

abstract class AbstractIlsDriver extends AbstractDriver {
	/** @var  AccountProfile $accountProfile */
	public $accountProfile;
	protected $webServiceURL;

	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile) {
		$this->accountProfile = $accountProfile;
	}

	/**
	 * @param $username
	 * @param $password
	 * @param $validatedViaSSO
	 * @return User|AspenError
	 */
	public abstract function patronLogin($username, $password, $validatedViaSSO);

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
	abstract function placeItemHold(User $patron, $recordId, $itemId, $pickupBranch, $cancelDate = null, $pickupSublocation = null);

	abstract function freezeHold(User $patron, $recordId, $itemToFreezeId, $dateToReactivate): array;

	abstract function thawHold(User $patron, $recordId, $itemToThawId): array;

	abstract function changeHoldPickupLocation(User $patron, $recordId, $itemToUpdateId, $newPickupLocation, $newPickupSublocation = null): array;

	abstract function updatePatronInfo(User $patron, $canUpdateContactInfo, $fromMasquerade);

	function updateHomeLibrary(User $patron, string $homeLibraryCode) {
		return [
			'success' => false,
			'messages' => ['Cannot update home library with this ILS.'],
		];
	}

	public abstract function getFines(User $patron, $includeMessages = false): array;

	/**
	 * @return IndexingProfile|null
	 */
	public function getIndexingProfile(): ?IndexingProfile {
		global $indexingProfiles;
		if (array_key_exists($this->accountProfile->recordSource, $indexingProfiles)) {
			/** @var IndexingProfile $indexingProfile */
			return $indexingProfiles[$this->accountProfile->recordSource];
		} else {
			return null;
		}
	}

	public function getWebServiceURL() {
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

	public function getVendorOpacUrl() {
		global $configArray;

		if ($this->accountProfile && $this->accountProfile->vendorOpacUrl) {
			$host = $this->accountProfile->vendorOpacUrl;
		} else {
			$host = $configArray['Catalog']['url'];
		}

		if (substr($host, -1) == '/') {
			$host = substr($host, 0, -1);
		}
		return $host;
	}

	function showOutstandingFines() {
		return false;
	}

	/**
	 * Returns one of four values
	 * - none - No forgot password functionality exists
	 * - emailResetLink - A link to reset the pin is emailed to the user
	 * - emailPin - The pin itself is emailed to the user
	 * - emailAspenResetLink - A link to reset the pin is emailed to the user.  Reset happens within Aspen.
	 * @return string
	 */
	function getForgotPasswordType() {
		return 'none';
	}

	function getEmailResetPinTemplate() {
		return 'overrideInDriver';
	}

	function processEmailResetPinForm() : array {
		return [
			'success' => false,
			'error' => 'This functionality is not available in the ILS.',
		];
	}

	function selfRegisterViaSSO($ssoUser): array {
		return [
			'success' => false,
		];
	}

	function selfRegister(): array {
		return [
			'success' => false,
		];
	}

	function getSelfRegistrationTerms() {
		return [];
	}

	function getSelfRegistrationFields() {
		return [];
	}

	function updatePin(User $patron, ?string $oldPin, string $newPin) {
		return [
			'success' => false,
			'message' => 'Can not update PINs, this ILS does not support updating PINs',
		];
	}

	function hasMaterialsRequestSupport() {
		return false;
	}

	function getNewMaterialsRequestForm(User $user) {
		return 'not supported';
	}

	/**
	 * @param User $user
	 * @return string[]
	 */
	function processMaterialsRequestForm(User $user) {
		return [
			'success' => false,
			'message' => 'Not Implemented',
		];
	}

	function getMaterialsRequests(User $user) {
		return [];
	}

	function getMaterialsRequestsPage(User $user) {
		return 'not supported';
	}

	function deleteMaterialsRequests(User $patron) {
		return [
			'success' => false,
			'message' => 'Not Implemented',
		];
	}

	/**
	 * Gets form to update contact information within the ILS.
	 *
	 * @param User $user
	 * @return string|null
	 */
	function getPatronUpdateForm(User $user) {
		return null;
	}

	function getNumMaterialsRequests(User $user) {
		return 0;
	}

	function importListsFromIls(User $patron) {
		return [
			'success' => false,
			'errors' => ['Importing Lists has not been implemented for this ILS.'],
		];
	}

	public function getAccountSummary(User $patron): AccountSummary {
		require_once ROOT_DIR . '/sys/User/AccountSummary.php';
		$summary = new AccountSummary();
		$summary->userId = $patron->id;
		$summary->source = 'ils';
		$summary->resetCounters();
		return $summary;
	}

	public function getExpirationInformation(User $patron) : ExpirationInformation {
		return new ExpirationInformation();
	}

	public function getDebarmentStatus(User $patron) {
		return false;
	}

	/**
	 * @return bool
	 */
	public function showMessagingSettings(): bool {
		return false;
	}

	/**
	 * @param User $patron
	 * @return string|null
	 */
	public function getMessagingSettingsTemplate(User $patron): ?string {
		return null;
	}

	public function processMessagingSettingsForm(User $patron): array {
		return [
			'success' => false,
			'message' => 'Notification Settings are not implemented for this ILS',
		];
	}

	public function placeVolumeHold(User $patron, $recordId, $volumeId, $pickupBranch, $pickupSublocation = null) {
		return [
			'success' => false,
			'message' => 'Volume level holds have not been implemented for this ILS.',
		];
	}

	public function completeFinePayment(User $patron, UserPayment $payment) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function patronEligibleForHolds(User $patron) {
		return [
			'isEligible' => true,
			'message' => '',
			'fineLimitReached' => false,
			'maxPhysicalCheckoutsReached' => false,
			'expiredPatronWhoCannotPlaceHolds' => false,
		];
	}

	public function getShowAutoRenewSwitch(User $patron) {
		return false;
	}

	public function isAutoRenewalEnabledForUser(User $patron) {
		return false;
	}

	public function updateAutoRenewal(User $patron, bool $allowAutoRenewal) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	function getPasswordRecoveryTemplate() {
		return null;
	}

	function processPasswordRecovery() {
		return null;
	}

	function getEmailResetPinResultsTemplate() {
		return null;
	}

	function getPasswordPinValidationRules() : array {
		global $library;
		return [
			'minLength' => $library->minPinLength,
			'maxLength' => $library->maxPinLength,
			'onlyDigitsAllowed' => $library->onlyDigitsAllowedInPin,
			'requireStrongPassword' => false
		];
	}

	public function supportsLoginWithUsername() : bool {
		return false;
	}

	public function hasEditableUsername() {
		return false;
	}

	public function getEditableUsername(User $user) {
		return null;
	}

	public function updateEditableUsername(User $patron, string $username): array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function logout(User $user) {
		//Nothing by default
	}

    public function getCollectionReportData($location, $date) {
        return null;
    }

    public function getHoldsReportData($location) {
		return null;
	}

	public function getStudentReportData($location, $showOverdueOnly, $date) {
		return null;
	}

    public function getWeedingReportData($location) {
        return null;
    }

	/**
	 * Loads any contact information that is not stored by Aspen Discovery from the ILS. Updates the user object.
	 *
	 * @param User $user
	 */
	public function loadContactInformation(User $user) {
		return;
	}

	public function getILSMessages(User $user) {
		return [];
	}


	public function confirmHold(User $patron, $recordId, $confirmationId) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function treatVolumeHoldsAsItemHolds() {
		return false;
	}

	public function getPluginStatus(string $pluginName) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function getCurbsidePickupSettings($locationCode) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function hasCurbsidePickups($patron) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function getPatronCurbsidePickups($patron) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function newCurbsidePickup($patron, $location, $time, $note) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function cancelCurbsidePickup($patron, $pickupId) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function checkInCurbsidePickup($patron, $pickupId) {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function getAllCurbsidePickups() {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	/**
	 * @param string $patronBarcode
	 * @param string $patronUsername
	 * @return bool|User
	 */
	public function findNewUser($patronBarcode, $patronUsername) {
		return false;
	}

	public function findNewUserByEmail(string $patronEmail): mixed {
		return false;
	}

	public function findUserByField(string $field, string $value) {
		return false;
	}

	public function hasIssueSummaries() {
		return false;
	}

	public function isPromptForHoldNotifications(): bool {
		return false;
	}

	public function getHoldNotificationTemplate(User $user): ?string {
		return null;
	}

	public function loadHoldNotificationInfo(User $user): ?array {
		return [
			'success' => false,
			'message' => 'Hold Notification Preferences are not implemented for this ILS',
		];
	}

	function validateUniqueId(User $user) {
		//By default, do nothing, this should be overridden for ILSs that use masquerade
	}

	/**
	 * Map from the property names required for self registration to
	 * the IdP property names returned from SAML2Authentication
	 *
	 * @return array|bool
	 */
	public function lmsToSso($isStaffUser, $isStudentUser, $useGivenUserId, $useGivenCardnumber) {
		return false;
	}

	public function getPatronIDChanges($searchPatronID): ?array {
		return null;
	}

	public function showHoldNotificationPreferences(): bool {
		return false;
	}

	public function getHoldNotificationPreferencesTemplate(User $user): ?string {
		return null;
	}

	public function processHoldNotificationPreferencesForm(User $user): array {
		return [
			'success' => false,
			'message' => 'Hold Notification Preferences are not implemented for this ILS',
		];
	}

	public function showHoldPosition(): bool {
		return false;
	}

	public function suspendRequiresReactivationDate(): bool {
		return false;
	}

	public function showDateWhenSuspending(): bool {
		return false;
	}

	public function reactivateDateNotRequired(): bool {
		return false;
	}

	public function showHoldPlacedDate(): bool {
		return false;
	}

	public function showHoldExpirationTime(): bool {
		return false;
	}

	public function showOutDateInCheckouts(): bool {
		return false;
	}

	public function showTimesRenewed(): bool {
		return false;
	}

	public function showRenewalsRemaining(): bool {
		return false;
	}

	public function showWaitListInCheckouts(): bool {
		return false;
	}

	/**
	 * Determine if volume level holds are always done when volumes are present.
	 * When this is on, items without volumes will present a blank volume for the user to choose from.
	 *
	 * @return false
	 */
	public function alwaysPlaceVolumeHoldWhenVolumesArePresent(): bool {
		return false;
	}

	/**
	 * Returns true if reset username is a separate page independent of the patron information page
	 *
	 * @return bool
	 */
	public function showResetUsernameLink(): bool {
		return false;
	}

	/**
	 * Returns an array of validation rules that should be applied when editing
	 *
	 * @return array
	 */
	public function getUsernameValidationRules(): array {
		return [
			'minLength' => 4,
			'maxLength' => 50,
			'additionalRequirements' => '',
		];
	}

	public function showPreferredNameInProfile(): bool {
		return false;
	}

	public function showDateInFines(): bool {
		return false;
	}

	public function getRegistrationCapabilities() : array {
		$forgotPasswordType = $this->getForgotPasswordType();
		//This can change if both email and name are required to initiate
		$canInitiatePasswordType = $forgotPasswordType != 'none';

		$enableSelfRegistrationInApp = false;
		global $library;
		require_once ROOT_DIR . '/sys/AspenLiDA/GeneralSetting.php';
		$appGeneralSetting = new GeneralSetting();
		$appGeneralSetting->id = $library->lidaGeneralSettingId;
		if($appGeneralSetting->find(true)) {
			$enableSelfRegistrationInApp = $appGeneralSetting->enableSelfRegistration;
		}

		return [
			'lookupAccountByEmail' => false,
			'lookupAccountByPhone' => false,
			'basicRegistration' => false,
			'forgottenPassword' => $forgotPasswordType != 'none',
			'initiatePasswordResetByEmail' => false,
			'initiatePasswordResetByBarcode' => $canInitiatePasswordType,
			'enableSelfRegistration' => $library->enableSelfRegistration,
			'enableSelfRegistrationInApp' => $enableSelfRegistrationInApp
		];
	}

	public function lookupAccountByEmail(string $email) : array {
		return [
			'success' => false,
			'message' => translate(['text' => 'This ILS does not support looking up accounts by email.', 'isPublicFacing' => true])
		];
	}

	public function lookupAccountByPhoneNumber(string $phone) : array {
		return [
			'success' => false,
			'message' => translate(['text' => 'This ILS does not support looking up accounts by phone number.', 'isPublicFacing' => true])
		];
	}

	public function getBasicRegistrationForm() : array {
		global $library;

		if (empty($library->validSelfRegistrationStates)) {
			$stateField = [
				'property' => 'state',
				'type' => 'text',
				'label' => translate(['text'=>'State', 'isPublicFacing'=>true, 'inAttribute'=>true]),
				'maxLength' => 32,
				'required' => true,
			];
		} else {
			$validStates = explode('|', $library->validSelfRegistrationStates);
			$validStates = array_combine($validStates, $validStates);
			$stateField = [
				'property' => 'state',
				'type' => 'enum',
				'values' => $validStates,
				'label' => translate(['text'=>'State', 'isPublicFacing'=>true, 'inAttribute'=>true]),
				'maxLength' => 32,
			];
		}

		if ($library->requireNumericPhoneNumbersWhenUpdatingProfile) {
			$phoneFormat = '';
		} else {
			$phoneFormat = ' (xxx-xxx-xxxx)';
		}

		return [
			'success' => true,
			'basicFormDefinition' => [
				'firstname' => [
					'property' => 'firstname',
					'type' => 'text',
					'label' => translate(['text'=>'First Name', 'isPublicFacing'=>true, 'inAttribute'=>true]),
					'maxLength' => 25,
					'required' => true,
				],
				'lastname' => [
					'property' => 'lastname',
					'type' => 'text',
					'label' => translate(['text'=>'Last Name', 'isPublicFacing'=>true, 'inAttribute'=>true]),
					'maxLength' => 60,
				],
				'address' => [
					'property' => 'address',
					'type' => 'text',
					'label' => translate(['text'=>'Address', 'isPublicFacing'=>true, 'inAttribute'=>true]),
					'maxLength' => 128,
				],
				'address2' => [
					'property' => 'address2',
					'type' => 'text',
					'label' => translate(['text'=>'Address 2', 'isPublicFacing'=>true, 'inAttribute'=>true]),

					'maxLength' => 128,
					'required' => false,
				],
				'city' => [
					'property' => 'city',
					'type' => 'text',
					'label' => translate(['text'=>'City', 'isPublicFacing'=>true, 'inAttribute'=>true]),

					'maxLength' => 48,
					'required' => true,
				],
				'state' => $stateField,
				'zipcode' => [
					'property' => 'zipcode',
					'type' => 'text',
					'label' => translate(['text'=>'Zip Code', 'isPublicFacing'=>true, 'inAttribute'=>true]),
					'maxLength' => 32,
					'required' => true,
				],
				'phone' => [
					'property' => 'phone',
					'type' => 'text',
					'label' => translate(['text'=>'Phone' . $phoneFormat, 'isPublicFacing'=>true, 'inAttribute'=>true]),
					'maxLength' => 128,
					'required' => false,

				],
				'email' => [
					'property' => 'email',
					'type' => 'email',
					'label' => translate(['text'=>'Email', 'isPublicFacing'=>true, 'inAttribute'=>true]),
					'maxLength' => 128,
					'required' => false,
				],
			]
		];
	}

	public function processBasicRegistrationForm(bool $addressValidated) : array {
		return [
			'success' => false,
			'messages' => ['Cannot process basic registration forms with this ILS.'],
		];
	}

	public function initiatePasswordResetByEmail() : array {
		return [
			'success' => false,
			'message' => ['Cannot initiate Password Reset by Email for this ILS.'],
		];
	}

	public function initiatePasswordResetByBarcode() : array {
		return [
			'success' => false,
			'message' => ['Cannot initiate Password Reset by Barcode for this ILS.'],
		];
	}

	public function bypassReadingHistoryUpdate($patron, $isNightlyUpdate) : bool {
		//By default, always update
		return false;
	}

	public function hasAPICheckout() : bool {
		return false;
	}

	public function checkoutBySip(User $patron, $barcode, $currentLocationId) {
		$checkout_result = [];
		$success = false;
		$title = translate([
			'text' => 'Unable to checkout title',
			'isPublicFacing' => true,
		]);
		$message = translate([
			'text' => 'Failed to connect to complete requested action.',
			'isPublicFacing' => true,
		]);
		$apiResult = [
			'title' => translate([
				'text' => 'Unable to checkout title',
				'isPublicFacing' => true,
			]),
		];

		require_once ROOT_DIR . '/sys/AspenLiDA/SelfCheckSetting.php';
		$scoSettings = new AspenLiDASelfCheckSetting();
		$checkoutLocationSetting = $scoSettings->getCheckoutLocationSetting($currentLocationId);
		$checkoutLocation = $currentLocationId; // assign checkout to current location logged into (default)
		if($checkoutLocationSetting == 1) {
			// assign checkout to user home location
			$checkoutLocation = $patron->getHomeLocationCode();
		} /* else if ($checkoutLocationSetting == 2) {
			// not yet supported with SIP since we can't easily find the item off barcode only
		} */

		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;
		if (empty($mySip->hostname) || empty($mySip->port)){
			$success = false;
			$message = 'The SIP server is not properly configured for this account profile.';
			$title = 'An Error Occurred';
		} else{
			if ($mySip->connect($this->accountProfile->sipUser, $this->accountProfile->sipPassword)) {
				//send self check status message
				$in = $mySip->msgSCStatus();
				$msg_result = $mySip->get_message($in);
				// Make sure the response is 98 as expected
				if (preg_match('/^98/', $msg_result)) {
					$result = $mySip->parseACSStatusResponse($msg_result);

					//  Use result to populate SIP2 settings
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
					if (!empty($result['variable']['AN'])) {
						$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
					}

					$mySip->patron = $patron->getBarcode();
					$mySip->patronpwd = $patron->getPasswordOrPin();

					$in = $mySip->msgCheckout($barcode, '', 'N', '', 'N', 'N', 'N', $checkoutLocation);
					$msg_result = $mySip->get_message($in);

					$checkoutResponse = null;
					$item = [];
					if (str_starts_with($msg_result, '64') || str_starts_with($msg_result, '12')) {
						$checkoutResponse = $mySip->parseCheckoutResponse($msg_result);
						if($checkoutResponse['fixed']['Ok'][0]) {
							$success = true;
							$title = translate(['text' => 'Checkout successful', 'isPublicFacing' => true]);
							$message = translate(['text' => 'You have successfully checked out this title.', 'isPublicFacing' => true]);
							if(isset($checkoutResponse['variable']['AF'][0])) {
								$message .= ' ' . $checkoutResponse['variable']['AF'][0];
							}
							$dueDate = explode(" ", $checkoutResponse['variable']['AH'][0]);
							if($this->accountProfile->ils == 'sierra') {
								$dueDate = str_replace('-', '/', $dueDate[0]);
								$dueDate = date_create($dueDate);
							} else {
								$dueDate = date_create($dueDate[0]);
							}
							$dueDate = date_format($dueDate, 'm/d/Y');
							$item['due'] = $dueDate;
						} else {
							$message .= ' ' . $checkoutResponse['variable']['AF'][0];
							$item['due'] = null;
						}
						$item['title'] = $checkoutResponse['variable']['AJ'][0] ?? 'Unknown title';
						$item['barcode'] = $barcode;
					} else {
						$message = $checkoutResponse;
					}
				}
			}
		}

		$apiResult['message'] = $message;
		$apiResult['title'] = $title;
		return [
			'title' => $title,
			'success' => $success,
			'message' => $message,
			'api' => $apiResult,
			'itemData' => $item ?? [],
		];
	}

	public function checkoutByAPI(User $patron, $barcode, Location $currentLocation): array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function hasAPICheckIn() {
		return false;
	}

	public function checkInByAPI(User $patron, $barcode, Location $currentLocation): array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function checkInBySIP(User $patron, $barcode, Location $currentLocation): array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function allowUpdatesOfPreferredName(User $patron) : bool {
		return false;
	}

	public function hasIlsInbox() : bool {
		return false;
	}

	public function getMessageTypes(): array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function updateMessageQueue(): array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function updateUserMessageQueue(User $patron): array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function supportsLocalIllRequests() : bool {
		return false;
	}

	public function submitLocalIllRequest(User $patron, LocalIllForm $localIllForm) : array {
		return [
			'success' => false,
			'title' => 'An error occurred',
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function loadLocations() : array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function loadLibraries() : array {
		return [
			'success' => false,
			'message' => 'This functionality has not been implemented for this ILS',
		];
	}

	public function hasIlsConsentSupport(): bool {
		return false;
	}

	public function hasAdditionalFineFields(): bool {
		return false;
	}
}
