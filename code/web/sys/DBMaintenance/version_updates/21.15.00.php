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
		'userPayments_addTransactionType' => [
			'title' => 'Add TransactionType column',
			'description' => 'Add TransactionType column to user_payments table to support multiple use-cases of the payment system. Updates existing entries to value of fine.',
			'sql' => [
				"ALTER TABLE user_payments ADD COLUMN transactionType VARCHAR(75) DEFAULT NULL",
				"UPDATE user_payments SET user_payments.transactionType=('fine') WHERE user_payments.transactionType IS NULL",
			]
		], //userPayments_addTransactionType
		'donations_createInitialTable' => [
			'title' => 'Donations - Create Donations Table',
			'description' => 'Creates table to store donations',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS donations (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							paymentId INT(11),
							firstName VARCHAR(256),
							lastName VARCHAR(256),
							email VARCHAR(256),
							anonymous TINYINT(1) default 0,
							libraryId INT(11),
							comments MEDIUMTEXT default null,
							dedicate TINYINT(1) default 0,
							dedicateType int(11),
							honoreeFirstName VARCHAR(256) default null,
							honoreeLastName VARCHAR(256) default null,
							sendEmailToUser TINYINT(1) default 0
						) ENGINE = INNODB;',
			]
		], //donations_createInitialTable
		'donations_createDonationsValue' => [
			'title' => 'Donations - Create DonationsValue Table',
			'description' => 'Creates table to store donation values',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS donations_value (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							value INT(11),
							isDefault TINYINT(1) default 0,
							libraryId VARCHAR(11)
						) ENGINE = INNODB;'
			]
		], //donations_createDonationsValue
		'donations_createDonationsDedicateType' => [
			'title' => 'Donations - Create DedicateType Table',
			'description' => 'Creates table to store dedication type values',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS donations_dedicate_type (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							label VARCHAR(75),
							libraryId VARCHAR(11)
						) ENGINE = INNODB;'
			]
		], //donations_createDonationsDedicateType
		'donations_createDonationsEarmarks' => [
			'title' => 'Donations - Create Earmark Table',
			'description' => 'Creates table to store earmark values',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS donations_earmark (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							label VARCHAR(75),
							libraryId VARCHAR(11),
							weight SMALLINT(2)
						) ENGINE = INNODB;'
			]
		], //donations_createDonationsEarmarks
		'donations_createDonationsFormFields' => [
			'title' => 'Donations - Create FormFields Table',
			'description' => 'Creates table to store donation form fields',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS donations_form_fields (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							textId VARCHAR(60) NOT NULL DEFAULT -1,
							libraryId VARCHAR(11),
							category VARCHAR(55),
							label VARCHAR(255),
							type VARCHAR(30),
							note VARCHAR(75),
							required TINYINT(1) default 0,
							weight SMALLINT(2),
							UNIQUE (textId)
						) ENGINE = INNODB;'
			]
		], //donations_createDonationsFormFields
		'donations_addLocationSettings' => [
			'title' => 'Donations - Add options to Location table',
			'description' => 'Add columns for options used by the Donations module in the Location config',
			'sql' => [
				'ALTER TABLE location ADD COLUMN showOnDonationsPage TINYINT(1) DEFAULT 1',
			]
		], //donations_addLocationSettings
		'donations_settings' => [
			'title' => 'Add settings for Donations',
			'description' => 'Add table to store settings for Donations',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS donations_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					name VARCHAR(50) UNIQUE,
					allowDonationsToBranch TINYINT(1) DEFAULT 0,
					allowDonationEarmark TINYINT(1) DEFAULT 0,
					allowDonationDedication TINYINT(1) DEFAULT 0,
					donationsContent LONGTEXT DEFAULT NULL,
					donationEmailTemplate TEXT DEFAULT NULL
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer Donations', '', 10, 'Controls if the user can change Donations settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Donations'))",
				"ALTER TABLE library ADD COLUMN donationSettingId INT(11) DEFAULT -1"
			]
		], //donations_settings
		'addNewSystemBrowseCategories' => [
			'title' => 'Add new system browse categories',
			'description' => 'Adds browse categories for user lists and saved searches',
			'sql' => [
				"INSERT INTO browse_category (textId, label, source) VALUES ('system_user_lists', 'Your Lists', 'List')",
				"INSERT INTO browse_category (textId, label, source) VALUES ('system_saved_searches', 'Your Saved Searches', 'GroupedWork')",
			]
		], //addNewSystemBrowseCategories
		'addNumDismissedToBrowseCategory' => [
			'title' => 'Add numTimesDismissed column',
			'description' => 'Adds numTimesDismissed column to Browse Categories to get count of user dismissals',
			'sql' => [
				"ALTER TABLE browse_category ADD COLUMN numTimesDismissed MEDIUMINT(9) NOT NULL DEFAULT 0"
			]
		], //addNumDismissedToBrowseCategory
		'userPaymentTransactionId' => [
			'title' => 'User Payment Transaction Id',
			'description' => 'Add a transaction id to the user payment to capture transaction id when different from order ID (PayPal)',
			'sql' => [
				"ALTER TABLE user_payments ADD COLUMN transactionId VARCHAR(20) DEFAULT ''"
			]
		], //addNumDismissedToBrowseCategory
		'increaseExternalRequestResponseField' => [
			'title' => 'Increase Response field length in External Request Log',
			'description' => 'Increase Response field length in External Request Log',
			'sql' => [
				"ALTER TABLE external_request_log CHANGE COLUMN response response MEDIUMTEXT"
			]
		]
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
					foreach ($payPalSettings as $payPalSetting) {
						if ($payPalSetting->clientId == $payPalInfoRow['payPalClientId'] && $payPalSetting->clientSecret == $payPalInfoRow['payPalClientSecret']) {
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