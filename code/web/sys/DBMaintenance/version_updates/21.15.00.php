<?php
/** @noinspection PhpUnused */
function getUpdates21_15_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'omdb_disableCoversWithNoDates' => [
			'title' => 'OMDB - Disable Covers With No Dates',
			'description' => 'Allow loading covers with no dates to be disabled',
			'sql' => [
				'ALTER TABLE omdb_settings ADD COLUMN fetchCoversWithoutDates TINYINT(1) DEFAULT 1',
			]
		], //omdb_disableCoversWithNoDates
		'checkoutFormatLength' => [
			'title' => 'Increase Format Length for Checkout',
			'description' => 'Increase Format Length for Checkouts',
			'sql' => [
				'alter table user_checkout change column format format VARCHAR(75) DEFAULT NULL;'
			]
		], //checkoutFormatLength
		'overdrive_useFulfillmentInterface' => [
			'title' => 'OverDrive - Enable updated checkout fulfillment interface',
			'description' => 'Enable updated checkout fulfillment interface',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN useFulfillmentInterface TINYINT(1) DEFAULT 0',
			]
		], //overdrive_useFulfillmentInterface
		'account_profile_increaseDatabaseNameLength' => [
			'title' => 'Account Profile - Increase Database Name Length',
			'description' => 'Increase datbase name length for Account Profiles',
			'sql' => [
				"ALTER TABLE account_profiles CHANGE COLUMN databaseName databaseName VARCHAR(75)",
			]
		], //account_profile_increaseDatabaseNameLength
		'payment_paidFrom' => [
			'title' => 'Add paidFromInstance to payments',
			'description' => 'Add paidFromInstance to payments',
			'sql' => [
				'ALTER TABLE user_payments ADD COLUMN paidFromInstance VARCHAR(100)'
			]
		], //payment_paidFrom
		'paypal_showPayLater' => [
			'title' => 'PayPal - Show Pay Later',
			'description' => 'Allow users to control if the Pay Later option is available',
			'sql' => [
				'ALTER TABLE paypal_settings ADD COLUMN showPayLater TINYINT(1) DEFAULT 0'
			]
		], //paypal_showPayLater
		'paypal_moveSettingsFromLibrary' => [
			'title' => 'PayPal - Move Settings From Library',
			'description' => 'Move settings from library settings to PayPal Settings',
			'sql' => [
				'movePayPalSettings',
				'ALTER TABLE library DROP COLUMN payPalClientId',
				'ALTER TABLE library DROP COLUMN payPalClientSecret',
				'ALTER TABLE library DROP COLUMN payPalSandboxMode',
			]
		], //paypal_moveSettingsFromLibrary
		'library_validPickupSystemLength' => [
			'title' => 'Library validPickupSystem Length',
			'description' => 'Increase length of validPickupSystems for libraries',
			'sql' => [
				"alter table library CHANGE COLUMN validPickupSystems validPickupSystems VARCHAR(500) DEFAULT ''"
			]
		], //library_validPickupSystemLength
		'systemVariables_libraryToUseForPayments' => [
			'title' => 'System Variables - Library To Use For Payments',
			'description' => 'Allow configuration of which library settings are used when making payments',
			'sql' => [
				"alter table system_variables ADD COLUMN libraryToUseForPayments TINYINT(1) DEFAULT 0"
			]
		], //systemVariables_libraryToUseForPayments
		'browseCategoryDismissal' => [
			'title' => 'Add browse_category_dismissal table',
			'description' => 'Enables the ability to hide browse categories by the user',
			'sql' => [
				'CREATE TABLE browse_category_dismissal (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							browseCategoryId INT,
							userId INT,
							UNIQUE INDEX userBrowseCategory(userId, browseCategoryId)
						) ENGINE = INNODB;',
			]
		], //browseCategoryDismissal
		'overdrive_showLibbyPromo' => [
			'title' => 'OverDrive - Enable show/hide Libby promo',
			'description' => 'Enable show/hide option for Libby promo in OverDrive fulfillment interface',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN showLibbyPromo TINYINT(1) DEFAULT 1',
			]
		], //overdrive_showLibbyPromo
		'search_increaseTitleLength' => [
			'title' => 'Saved Search - Increase Title Length',
			'description' => 'Increase title length for Saved Searches',
			'sql' => [
				"ALTER TABLE search CHANGE COLUMN title title VARCHAR(225)",
			]
		], //search_increaseTitleLength
	];
}

function movePayPalSettings(){
	require_once ROOT_DIR . '/sys/ECommerce/PayPalSetting.php';
	$payPalSetting = new PayPalSetting();
	$payPalSettings = $payPalSetting->fetchAll();

	//Get distinct PayPal information
	global $aspen_db;
	$payPalInfoStmt = "SELECT libraryId, displayName, payPalClientId, payPalClientSecret, payPalSandboxMode FROM library ORDER BY isDefault desc, displayName asc";

	$payPalInfoRS = $aspen_db->query($payPalInfoStmt, PDO::FETCH_ASSOC);
	$payPalInfoRow = $payPalInfoRS->fetch();
	require_once ROOT_DIR . '/sys/Theming/LayoutSetting.php';
	while ($payPalInfoRow != null){
		if (!empty($payPalInfoRow['payPalClientId']) && !empty($payPalInfoRow['payPalClientSecret'])){
			$library = new Library();
			$library->libraryId = $payPalInfoRow['libraryId'];
			if ($library->find(true)) {
				if (count($payPalSettings) == 0) {
					$createSetting = true;
				} else {
					$createSetting = true;
					foreach ($payPalSetting as $payPalSettings) {
						if ($payPalSetting->clientId == $library->payPalClientId && $payPalSetting->clientSecret == $library->payPalClientSecret) {
							$createSetting = false;
						}
					}
				}
				if ($createSetting) {
					$payPalSetting = new PayPalSetting();
					if (count($payPalSettings) == 0) {
						$payPalSetting->name = 'default';
					} else {
						$payPalSetting->name = $library->displayName;
					}
					$payPalSetting->clientId = $payPalInfoRow['payPalClientId'];
					$payPalSetting->clientSecret = $payPalInfoRow['payPalClientSecret'];
					$payPalSetting->sandboxMode = $payPalInfoRow['payPalSandboxMode'];
					$payPalSetting->insert();
					$payPalSettings[] = clone $payPalSetting;
				}
				$library->payPalSettingId = $payPalSetting->id;
				$library->update();
			}
		}
		$payPalInfoRow = $payPalInfoRS->fetch();
	}
}