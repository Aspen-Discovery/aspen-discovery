<?php

require_once ROOT_DIR . "/sys/Donations/Donation.php";
require_once ROOT_DIR . "/sys/ECommerce/DonationsSetting.php";

class Donations_NewDonation extends Action {

	function launch() {
		global $interface;
		global $library;

		$donationSettings = new DonationsSetting();
		$donationSettings->id = $library->donationSettingId;
		$donationSettings->find();


		if ($donationSettings->find(true)) {
			$donation = new Donation();
			$donation->donationSettingId = $donationSettings->id;
			$donation->donateToLocationId = $library->libraryId;

			// if logged in, lets populate some fields from their profile
			if ($user = UserAccount::getActiveUserObj()) {
				$donation->firstName = $user->firstname;
				$donation->lastName = $user->lastname;
				$donation->email = $user->email;
				//Let's populate fields if donation form requires address information
				if ($donationSettings->requiresAddressInfo){
					$donation->address = $user->_address1;
					$donation->address2 = $user->_address2;
					$donation->city = $user->_city;
					$donation->state = $user->_state;
					$donation->zip = $user->_zip;
				}
			}

			$interface->assign('newDonation', $donation);

			// Get the payment processor for the form
			$donationPaymentProcessor = $donation->getPaymentProcessor();
			$interface->assign('userId', $donationPaymentProcessor['userId']);
			$interface->assign('paymentType', $donationPaymentProcessor['paymentType']);
			$interface->assign('currencyCode', $donationPaymentProcessor['currencyCode']);

			if ($donationPaymentProcessor['clientId'] != null) {
				$interface->assign('payPalClientId', $donationPaymentProcessor['clientId']);
			}

			if ($donationPaymentProcessor['showPayLater'] != null) {
				$interface->assign('showPayLater', $donationPaymentProcessor['showPayLater']);
			}
			// FIS WorldPay data
			if ($donationPaymentProcessor['paymentType'] == 7) {
				$interface->assign('aspenUrl', $donationPaymentProcessor['aspenUrl']);
				$interface->assign('settleCode', $donationPaymentProcessor['settleCode']);
				$interface->assign('merchantCode', $donationPaymentProcessor['merchantCode']);
				$interface->assign('paymentSite', $donationPaymentProcessor['paymentSite']);
				$interface->assign('useLineItems', $donationPaymentProcessor['useLineItems']);
			}
			// ACI Speedpay
			if ($donationPaymentProcessor['paymentType'] == 8) {
				$sdkAuthKey = $donationPaymentProcessor['sdkAuthKey'];
				$sdkClientId = $donationPaymentProcessor['sdkClientId'];
				$sdkClientSecret = $donationPaymentProcessor['sdkClientSecret'];
				$billerId = $donationPaymentProcessor['billerId'];
				$billerAccountId = $donationPaymentProcessor['billerAccountId'];
				$baseUrl = $donationPaymentProcessor['baseUrl'];
				$sdkUrl = $donationPaymentProcessor['sdkUrl'];

				$interface->assign('billerId', $billerId);
				$interface->assign('aciHost', $baseUrl);
				$interface->assign('sdkUrl', $sdkUrl);
				$interface->assign('sdkAuthKey', $sdkAuthKey);
				$interface->assign('sdkClientId', $sdkClientId);
				$interface->assign('sdkClientSecret', $sdkClientSecret);
				$interface->assign('billerAccountId', $billerAccountId);

				require_once ROOT_DIR . '/sys/CurlWrapper.php';
				$serviceAccountAuthorization = new CurlWrapper();
				$serviceAccountAuthorization->addCustomHeaders([
					"X-Auth-Key: $sdkAuthKey",
					"Content-Type: application/x-www-form-urlencoded",
					"Accept: application/json",
				], true);

				$postParams = [
					'grant_type' => 'client_credentials',
					'client_id' => $sdkClientId,
					'client_secret' => $sdkClientSecret,
					'scope' => 'token_exchange',
					'biller_id' => $billerId,
					'account_number' => $billerAccountId,
				];

				$url = $baseUrl . "/auth/v1/auth/token";
				$accessTokenResults = $serviceAccountAuthorization->curlPostPage($url, $postParams);
				$accessTokenResults = json_decode($accessTokenResults, true);
				$accessToken = "";
				if (empty($accessTokenResults['access_token'])) {
					$interface->assign('aciError', 'Unable to authenticate with ACI, please try again in a few minutes.');
				} else {
					$accessToken = $accessTokenResults['access_token'];
				}
				$interface->assign('accessToken', $accessToken);

				$aciManifest = "https://cds.officialpayments.com/js-sdk/1.5.0/manifest.json";
				$aciManifest = file_get_contents($aciManifest);
				$aciManifest = json_decode($aciManifest, true);
				$sriHash = "";
				if (empty($aciManifest['speedpay.js']['integrity'])) {
					$interface->assign('aciError', 'Unable to authenticate with ACI, please try again in a few minutes.');
				} else {
					$sriHash = $aciManifest['speedpay.js']['integrity'];
				}
				$interface->assign('sriHash', $sriHash);
			}
			// Certified Payments by Deluxe
			if ($donationPaymentProcessor['paymentType'] == 10) {
				$interface->assign('deluxeAPIConnectionUrl', $donationPaymentProcessor['deluxeAPIConnectionUrl']);
				$interface->assign('deluxeRemittanceId', $donationPaymentProcessor['deluxeRemittanceId']);
				$interface->assign('deluxeApplicationId', $donationPaymentProcessor['deluxeApplicationId']);
			}
			// Certified Payments by Deluxe
			if ($donationPaymentProcessor['paymentType'] == 10) {
				$interface->assign('deluxeAPIConnectionUrl', $donationPaymentProcessor['deluxeAPIConnectionUrl']);
				$interface->assign('deluxeRemittanceId', $donationPaymentProcessor['deluxeRemittanceId']);
				$interface->assign('deluxeApplicationId', $donationPaymentProcessor['deluxeApplicationId']);
			}
			// Square
			if ($donationPaymentProcessor['paymentType'] == 12) {
				$interface->assign('squareCdnUrl', $donationPaymentProcessor['squareCdnUrl']);
				$interface->assign('squareApplicationId', $donationPaymentProcessor['squareApplicationId']);
				$interface->assign('squareAccessToken', $donationPaymentProcessor['squareAccessToken']);
				$interface->assign('squareLocationId', $donationPaymentProcessor['squareLocationId']);
			}
			// Stripe
			if ($donationPaymentProcessor['paymentType'] == 13) {
				$interface->assign('stripePublicKey', $donationPaymentProcessor['stripePublicKey']);
				$interface->assign('stripeSecretKey', $donationPaymentProcessor['stripeSecretKey']);
			}

			// Get the fields to display for the form
			$donationFormFields = $donation->getDonationFormFields($donationSettings->id);
			$interface->assign('donationFormFields', $donationFormFields);

			// Get the value options to display for the form
			$values = Donation::getDonationValues($donationSettings->id);
			$symbol = $donation->getCurrencySymbol();
			$interface->assign('donationValues', $values);
			$interface->assign('currencySymbol', $symbol);

			// Get the location options to display for the form
			$locations = [];
			if ($donationSettings->allowDonationsToBranch) {
				$locations = Donation::getLocations();
			}
			$interface->assign('donationLocations', $locations);

			// Get the earmark options to display for the form
			$earmarks = [];
			if ($donationSettings->allowDonationEarmark) {
				$earmarks = Donation::getEarmarks($donationSettings->id);
			}
			$interface->assign('donationEarmarks', $earmarks);

			// Get the dedication options to display for the form
			$dedications = [];
			if ($donationSettings->allowDonationDedication) {
				$dedications = Donation::getDedications($donationSettings->id);
			}
			$interface->assign('donationDedications', $dedications);

			$this->display('newDonation.tpl', 'Make a Donation', '', false);
		}

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', 'Make a Donation', true);
		return $breadcrumbs;
	}
}