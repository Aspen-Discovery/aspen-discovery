<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Donations/DonationValue.php';
require_once ROOT_DIR . '/sys/Donations/DonationFormFields.php';
require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
require_once ROOT_DIR . '/sys/Donations/DonationDedicationType.php';

class Donation extends DataObject
{
	public $__table = 'donations';   // table name

	public $id;
	public $paymentId;
	public $firstName;
	public $lastName;
	public $email;
	public $anonymous;
	public $donateToLibraryId;
	public $comments;
	public $dedicate;
	public $dedicateType;
	public $honoreeFirstName;
	public $honoreeLastName;
	public $sendEmailToUser;
	public $donationSettingId;

	function getDonationValue($paymentId){
		require_once ROOT_DIR . '/sys/Account/UserPayment.php';
		$payment = new UserPayment();
		$payment->id = $paymentId;
		$payment->find();
		return $payment->totalPaid;
	}

	function getCurrencySymbol() {
		$currencyCode = 'USD';
		$systemVariables = SystemVariables::getSystemVariables();
		if (!empty($systemVariables->currencyCode)) {
			$currencyCode = $systemVariables->currencyCode;
		}
		if($currencyCode == 'USD') {
			$currencySymbol = '$';
		} elseif($currencyCode == 'EUR') {
			$currencySymbol = '€';
		} elseif($currencyCode == 'CAD') {
			$currencySymbol = '$';
		} elseif($currencyCode == 'GBP') {
			$currencySymbol = '£';
		}
		return $currencySymbol;
	}

	function getDonationFormFields($donationSettingId) {
		require_once ROOT_DIR . '/sys/Donations/DonationFormFields.php';
		$formFields = new DonationFormFields();
		$formFields->donationSettingId = $donationSettingId;

		/** @var DonationFormFields[] $fieldsToSortByCategory */
		$fieldsToSortByCategory = $formFields->fetchAll();

		// If no values set get the defaults.
		if (empty($fieldsToSortByCategory)) {
			$fieldsToSortByCategory = $formFields::getDefaults($donationSettingId);
		}

		$donationFormFields = array();
		if ($fieldsToSortByCategory) {
			foreach ($fieldsToSortByCategory as $formField) {
				if (!array_key_exists($formField->category, $donationFormFields)) {
					$donationFormFields[$formField->category] = array();
				}
				$donationFormFields[$formField->category][] = $formField;
			}
		}
		return $donationFormFields;
	}

	static function getDonationValues($donationSettingId) {
		require_once ROOT_DIR . '/sys/Donations/DonationValue.php';
		$values = new DonationValue();
		$values->donationSettingId = $donationSettingId;

		if($values->count() == 0) {
			// Load up the default values if library has defined none.
			/** @var DonationValue $defaultValues */
			$defaultValues = DonationValue::getDefaults($donationSettingId);
			$availableValues = array();

			global $configArray;
			foreach($defaultValues as $index => $donationValue) {
				$value = $donationValue->value;
				if (!isset($configArray['donationValues'][$value]) || $configArray['donationValues'][$value] != false) {
					$availableValues[$value] = $donationValue->value;
				}
			}

		} else {
			$values->orderBy('value');
			$availableValues = $values->fetchAll('value', 'value');
		}

		return $availableValues;
	}

	static function getLocations() {
		$availableLocations = array();
		$locations = array();
		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		$location = new Location();
		$location->showOnDonationsPage = 1;
		$location->find();
		while ($location->fetch()) {
			$locations[] = clone($location);
		}

		foreach($locations as $index => $donationLocation) {
			$id = $donationLocation->locationId;
			if (!isset($configArray['donationLocations'][$id]) || $configArray['donationLocations'][$id] != false) {
				$availableLocations[$donationLocation->displayName] = $id;
			}
		}

		return $availableLocations;
	}

	static function getEarmarks($donationSettingId) {
		require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
		$earmarks = new DonationEarmark();
		$earmarks->donationSettingId = $donationSettingId;

		if($earmarks->count() == 0) {
			// Load up the default values if library has defined none.
			/** @var DonationEarmark $defaultValues */
			$defaultEarmarks = DonationEarmark::getDefaults($donationSettingId);
			$availableEarmarks = array();

			global $configArray;
			foreach($defaultEarmarks as $index => $donationEarmarkId) {
				$id = $donationEarmarkId->id;
				if (!isset($configArray['donationEarmarks'][$id]) || $configArray['donationEarmarks'][$id] != false) {
					$availableEarmarks[$donationEarmarkId->label] = $id;
				}
			}

		} else {
			$availableEarmarks = $earmarks->fetchAll('label', 'id');

		}

		return $availableEarmarks;
	}

	static function getDedications($donationSettingId) {
		require_once ROOT_DIR . '/sys/Donations/DonationDedicationType.php';
		$dedicationTypes = new DonationDedicationType();
		$dedicationTypes->donationSettingId = $donationSettingId;

		if($dedicationTypes->count() == 0) {
			// Load up the default values if library has defined none.
			/** @var DonationDedicationType $defaultValues */
			$defaultDedicationTypes = DonationDedicationType::getDefaults($donationSettingId);
			$availableDedicationTypes = array();

			global $configArray;
			foreach($defaultDedicationTypes as $index => $donationDedicationTypeId) {
				$id = $donationDedicationTypeId->id;
				if (!isset($configArray['donationDedicationTypes'][$id]) || $configArray['donationDedicationTypes'][$id] != false) {
					$availableDedicationTypes[$donationDedicationTypeId->label] = $id;
				}
			}

		} else {
			$availableDedicationTypes = $dedicationTypes->fetchAll('label', 'id');
		}

		return $availableDedicationTypes;
	}

	function sendReceiptEmail(){
		$donationReceipt = new Donation();
		$donationReceipt->id = $this->id;
		if ($donationReceipt->find(true)){
			if ($donationReceipt->sendEmailToUser == 1 && $this->email){
				require_once ROOT_DIR . '/sys/Email/Mailer.php';
				$mail = new Mailer();

				$replyToAddress = '';

				$body = '*****This is an auto-generated email response. Please do not reply.*****';

				require_once ROOT_DIR . '/sys/ECommerce/DonationsSetting.php';
				$donationSettings = new DonationsSetting();
				$donationSettings->id = $donationReceipt->donationSettingId;
				if($donationSettings->find(true)) {
					$emailTemplate = $donationSettings->donationEmailTemplate;
					$body .= "\r\n\r\n" . $emailTemplate;
				}

				$error = $mail->send($this->email, translate(['text'=>"Your Donation Receipt",'isPublicFacing'=>true]), $body, $replyToAddress);
				if (($error instanceof AspenError)) {
					global $interface;
					$interface->assign('error', $error->getMessage());
				}
			}
		}
	}

	function getPaymentProcessor() : array {
		global $library;
		$clientId = null;
		$showPayLater = null;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$userId = $user->id;
			$paymentType = isset($homeLibrary) ? $homeLibrary->finePaymentType : 0;
			if($user->username == "aspen_admin") {
				$userId = "Guest";
				$homeLibrary = $library->libraryId;
				$paymentType = isset($library) ? $library->finePaymentType : 0;
			}
			if ($paymentType == 2) {
				require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
				$payPalSetting = new PayPalSetting();
				$payPalSetting->id = $homeLibrary->payPalSettingId;
				if($payPalSetting->find(true)){
					$clientId = $payPalSetting->clientId;
					$showPayLater = $payPalSetting->showPayLater;
				}
			}
		} else {
			$userId = "Guest";
			$paymentType = isset($library) ? $library->finePaymentType : 0;
			if ($paymentType == 2) {
				require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
				$payPalSetting = new PayPalSetting();
				$payPalSetting->id = $library->payPalSettingId;
				if($payPalSetting->find(true)){
					$clientId = $payPalSetting->clientId;
				}
			}
		}
		$currencyCode = "USD";
		$systemVariables = SystemVariables::getSystemVariables();
		if (!empty($systemVariables->currencyCode)) {
			$currencyCode = $systemVariables->currencyCode;
		}
		return array('paymentType' => $paymentType, 'currencyCode' => $currencyCode, 'userId' => $userId, 'clientId' => $clientId, 'showPayLater' => $showPayLater);
	}

}
