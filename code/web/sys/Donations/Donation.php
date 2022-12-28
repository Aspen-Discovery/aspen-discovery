<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/Donations/DonationValue.php';
require_once ROOT_DIR . '/sys/Donations/DonationFormFields.php';
require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
require_once ROOT_DIR . '/sys/Donations/DonationDedicationType.php';

class Donation extends DataObject {
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

	public static function getObjectStructure($context = '') {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'firstName' => [
				'property' => 'firstName',
				'type' => 'text',
				'label' => 'First Name',
				'description' => 'The first name of the person making the donation',
				'readOnly' => true,
			],
			'lastName' => [
				'property' => 'lastName',
				'type' => 'text',
				'label' => 'Last Name',
				'description' => 'The last name of the person making the donation',
				'readOnly' => true,
			],
			'email' => [
				'property' => 'email',
				'type' => 'email',
				'label' => 'Email',
				'description' => 'The email of the person making the donation',
				'readOnly' => true,
			],
			'anonymous' => [
				'property' => 'anonymous',
				'type' => 'checkbox',
				'label' => 'Anonymous?',
				'description' => 'Whether or not the donor wants to remain anonymous',
				'readOnly' => true,
			],
			'donationLibrary' => [
				'property' => 'donationLibrary',
				'type' => 'text',
				'label' => 'Donate To',
				'description' => 'The location where the user wants to send the donation',
				'readOnly' => true,
			],
			'earmark' => [
				'property' => 'earmark',
				'type' => 'text',
				'label' => 'Earmark',
				'description' => 'An earmark the user would like the donation applied to',
				'readOnly' => true,
			],
			'dedicateType' => [
				'property' => 'dedicateType',
				'type' => 'text',
				'label' => 'Dedication',
				'description' => 'The location where the user wants to send the donation',
				'readOnly' => true,
			],
			'honoreeFirstName' => [
				'property' => 'honoreeFirstName',
				'type' => 'text',
				'label' => 'Honoree First Name',
				'description' => 'The first name of the person being honored',
				'readOnly' => true,
			],
			'honoreeLastName' => [
				'property' => 'honoreeLastName',
				'type' => 'text',
				'label' => 'Honoree Last Name',
				'description' => 'The last name of the person being honored',
				'readOnly' => true,
			],
			'donationValue' => [
				'property' => 'donationValue',
				'type' => 'text',
				'label' => 'Donation Amount',
				'description' => 'The amount donated',
				'readOnly' => true,
			],
			'donationComplete' => [
				'property' => 'donationComplete',
				'type' => 'text',
				'label' => 'Donation Completed',
				'description' => 'Whether or not payment for the donation has been completed',
				'readOnly' => true,
			],
		];
	}

	function __get($name) {
		if ($name == 'donationValue') {
			global $activeLanguage;
			$currencyCode = 'USD';
			$variables = new SystemVariables();
			if ($variables->find(true)) {
				$currencyCode = $variables->currencyCode;
			}

			$currencyFormatter = new NumberFormatter($activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY);

			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->id = $this->paymentId;
			if ($payment->find(true)) {
				return $currencyFormatter->formatCurrency(empty($payment->totalPaid) ? 0 : (int)$payment->totalPaid, $currencyCode);
			} else {
				return $currencyFormatter->formatCurrency(0, $currencyCode);
			}
		} elseif ($name == 'donationComplete') {
			require_once ROOT_DIR . '/sys/Account/UserPayment.php';
			$payment = new UserPayment();
			$payment->id = $this->paymentId;
			if ($payment->find(true)) {
				return $payment->completed ? 'true' : 'false';
			} else {
				return 'false';
			}
		} elseif ($name == 'donationLibrary') {
			if (empty($this->donateToLibraryId)) {
				return 'None';
			} else {
				$location = new Location();
				$location->locationId = $this->donateToLibraryId;
				if ($location->find(true)) {
					return $location->displayName;
				} else {
					return 'Unknown';
				}
			}
		} elseif ($name == 'earmark') {
			if (empty($this->comments)) {
				return 'None';
			} else {
				require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
				$earmark = new DonationEarmark();
				$earmark->donationSettingId = $this->donationSettingId;
				$earmark->id = $this->comments;
				if ($earmark->find(true)) {
					return $earmark->label;
				} else {
					return 'Unknown';
				}

			}
		} else {
			return $this->_data[$name] ?? null;
		}
	}

	function getDonationValue($paymentId) {
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
		if ($currencyCode == 'USD') {
			$currencySymbol = '$';
		} elseif ($currencyCode == 'EUR') {
			$currencySymbol = '€';
		} elseif ($currencyCode == 'CAD') {
			$currencySymbol = '$';
		} elseif ($currencyCode == 'GBP') {
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

		$donationFormFields = [];
		if ($fieldsToSortByCategory) {
			foreach ($fieldsToSortByCategory as $formField) {
				if (!array_key_exists($formField->category, $donationFormFields)) {
					$donationFormFields[$formField->category] = [];
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

		if ($values->count() == 0) {
			// Load up the default values if library has defined none.
			/** @var DonationValue $defaultValues */
			$defaultValues = DonationValue::getDefaults($donationSettingId);
			$availableValues = [];

			global $configArray;
			foreach ($defaultValues as $index => $donationValue) {
				$value = $donationValue->value;
				if (!isset($configArray['donationValues'][$value]) || $configArray['donationValues'][$value] != false) {
					$availableValues[$value] = $donationValue->value;
				}
			}

		} else {
			$values->orderBy('weight');
			$availableValues = $values->fetchAll('value', 'value');
		}

		return $availableValues;
	}

	static function getLocations() {
		global $configArray;
		$availableLocations = [];
		$locations = [];
		require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
		$location = new Location();
		$location->showOnDonationsPage = 1;
		$location->find();
		while ($location->fetch()) {
			$locations[] = clone($location);
		}

		foreach ($locations as $index => $donationLocation) {
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

		if ($earmarks->count() == 0) {
			// Load up the default values if library has defined none.
			/** @var DonationEarmark $defaultValues */
			$defaultEarmarks = DonationEarmark::getDefaults($donationSettingId);
			$availableEarmarks = [];

			global $configArray;
			foreach ($defaultEarmarks as $index => $donationEarmarkId) {
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

		if ($dedicationTypes->count() == 0) {
			// Load up the default values if library has defined none.
			/** @var DonationDedicationType $defaultValues */
			$defaultDedicationTypes = DonationDedicationType::getDefaults($donationSettingId);
			$availableDedicationTypes = [];

			global $configArray;
			foreach ($defaultDedicationTypes as $index => $donationDedicationTypeId) {
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

	function sendReceiptEmail() {
		$donationReceipt = new Donation();
		$donationReceipt->id = $this->id;
		if ($donationReceipt->find(true)) {
			if ($donationReceipt->sendEmailToUser == 1 && $this->email) {
				require_once ROOT_DIR . '/sys/Email/Mailer.php';
				$mail = new Mailer();

				$replyToAddress = '';

				$body = '*****This is an auto-generated email response. Please do not reply.*****';

				require_once ROOT_DIR . '/sys/ECommerce/DonationsSetting.php';
				$donationSettings = new DonationsSetting();
				$donationSettings->id = $donationReceipt->donationSettingId;
				if ($donationSettings->find(true)) {
					$emailTemplate = $donationSettings->donationEmailTemplate;
					$body .= "\r\n\r\n" . $emailTemplate;
				}

				$error = $mail->send($this->email, translate([
					'text' => "Your Donation Receipt",
					'isPublicFacing' => true,
				]), $body, $replyToAddress);
				if (($error instanceof AspenError)) {
					global $interface;
					$interface->assign('error', $error->getMessage());
				}
			}
		}
	}

	function getPaymentProcessor(): array {
		global $library;
		$clientId = null;
		$showPayLater = null;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$userId = $user->id;
			if ($homeLibrary == null) {
				$homeLibrary = $library;
				$paymentType = $library->finePaymentType;
			} else {
				$paymentType = $homeLibrary->finePaymentType;
			}

			if ($paymentType == 2) {
				require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
				$payPalSetting = new PayPalSetting();
				$payPalSetting->id = $homeLibrary->payPalSettingId;
				if ($payPalSetting->find(true)) {
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
				if ($payPalSetting->find(true)) {
					$clientId = $payPalSetting->clientId;
				}
			}
		}
		$currencyCode = "USD";
		$systemVariables = SystemVariables::getSystemVariables();
		if (!empty($systemVariables->currencyCode)) {
			$currencyCode = $systemVariables->currencyCode;
		}
		return [
			'paymentType' => $paymentType,
			'currencyCode' => $currencyCode,
			'userId' => $userId,
			'clientId' => $clientId,
			'showPayLater' => $showPayLater,
		];
	}

	function canActiveUserEdit() {
		return false;
	}
}
