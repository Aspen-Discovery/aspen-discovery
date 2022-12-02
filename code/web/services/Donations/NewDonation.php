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
			$donation->donateToLibraryId = $library->libraryId;

			// if logged in, lets populate some fields from their profile
			if ($user = UserAccount::getActiveUserObj()) {
				$donation->firstName = $user->firstname;
				$donation->lastName = $user->lastname;
				$donation->email = $user->email;
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