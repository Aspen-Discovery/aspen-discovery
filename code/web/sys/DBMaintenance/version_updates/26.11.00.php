<?php
/** @noinspection SqlDialectInspection */

/** @noinspection PhpUnused */
function getUpdates26_11_00(): array {
	$now = time();

	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark n

		//kirstien

		//kodi

		//yanjun
		'paypal_client_credentials_length' => [
			'title' => 'Expand PayPal Client Credential Lengths',
			'description' => 'Increase the maximum length of PayPal Client ID and Client Secret to 100 characters.',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE paypal_settings MODIFY COLUMN clientId VARCHAR(100) DEFAULT NULL',
				'ALTER TABLE paypal_settings MODIFY COLUMN clientSecret VARCHAR(100) DEFAULT NULL',
			]
		], //paypal_client_credentials_length

		//imani
		'separate_firebase_settings' => [
			'title' => 'Separate Firebase Settings',
			'description' => 'Move Firebase settings out of Aspen Progressive Web Application(PWA) settings into their own table',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS firebase_settings (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name varchar(50) NOT NULL,
					firebaseAPIKey varchar(50) NOT NULL,
					firebaseAuthDomain varchar(50) NOT NULL,
					firebaseProjectID varchar(50) NOT NULL,
					firebaseStorageBucket varchar(50) NOT NULL,
					firebaseMessagingSenderID varchar(50) NOT NULL,
					firebaseAppID varchar(50) NOT NULL,
					vapidKey varchar(100) NOT NULL,
					serviceAccount TEXT
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
				"ALTER TABLE library ADD COLUMN firebaseSettingId int(11) DEFAULT -1",
				"ALTER TABLE user_notification_tokens ADD COLUMN firebaseSettingId int(11) DEFAULT -1",
				"INSERT IGNORE into `permissions` (name, sectionName, requiredModule, weight, description) VALUES ('Administer Firebase Settings','Aspen Progressive Web Application(PWA)', 'Aspen Progressive Web Application(PWA)', 11, 'Controls if the user can change Firebase Settings used for Aspen Progressive Web Application(PWA) notifications.');",
				"INSERT IGNORE INTO role_permissions (roleId, permissionId)
					SELECT rp.roleId, (SELECT id FROM permissions WHERE name = 'Administer Firebase Settings')
					FROM role_permissions rp
					INNER JOIN permissions p ON rp.permissionId = p.id
					WHERE p.name = 'Administer Aspen Progressive Web Application(PWA) Settings'",
				'migrateFirebaseSettingsFromPWASettings',
			],
		], //separate_firebase_settings
		'aspen_pwa_settings_icon' => [
			'title' => 'Add icon to Aspen PWA Settings',
			'description' => 'Add an icon to Aspen Progressive Web Application(PWA) settings to use instead of the theme logo',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE aspen_pwa_settings ADD COLUMN icon varchar(100) DEFAULT ''",
			],
		], //aspen_pwa_settings_icon

		//galen

		//chloe

		//pedro

		//mark j

		//lucas

		//tomas

		// stephen

		//jacob - OpenFifth


	];
}

function migrateFirebaseSettingsFromPWASettings(&$update): void {
	global $aspen_db;
	$firebaseColumns = [
		'firebaseAPIKey',
		'firebaseAuthDomain',
		'firebaseProjectID',
		'firebaseStorageBucket',
		'firebaseMessagingSenderID',
		'firebaseAppID',
		'vapidKey',
		'serviceAccount',
	];

	$existingColumns = $aspen_db->query("SHOW COLUMNS FROM aspen_pwa_settings LIKE 'firebaseProjectID'");
	if ($existingColumns === false || $existingColumns->rowCount() == 0) {
		$update['success'] = true;
		$update['status'] = 'Firebase settings were already moved out of Aspen PWA settings';
		return;
	}

	$columnList = implode(', ', $firebaseColumns);
	$numCopied = 0;
	$errors = [];
	//Copy with SQL so the encrypted service account is moved as is
	$copyStmt = $aspen_db->prepare("INSERT INTO firebase_settings (name, $columnList) SELECT name, $columnList FROM aspen_pwa_settings WHERE id = :id");
	$linkLibrariesStmt = $aspen_db->prepare("UPDATE library SET firebaseSettingId = :firebaseSettingId WHERE AspenPWASettingId = :pwaSettingId");
	$pwaSettings = $aspen_db->query("SELECT id, name, firebaseProjectID, serviceAccount FROM aspen_pwa_settings");
	while ($pwaSetting = $pwaSettings->fetch(PDO::FETCH_ASSOC)) {
		if (empty(trim($pwaSetting['firebaseProjectID'] ?? '')) && empty($pwaSetting['serviceAccount'])) {
			continue;
		}
		try {
			$copyStmt->execute([':id' => $pwaSetting['id']]);
			$firebaseSettingId = $aspen_db->lastInsertId();
			$linkLibrariesStmt->execute([
				':firebaseSettingId' => $firebaseSettingId,
				':pwaSettingId' => $pwaSetting['id'],
			]);
			$numCopied++;
		} catch (PDOException $e) {
			$errors[] = "Could not copy Firebase settings for {$pwaSetting['name']}: " . $e->getMessage();
		}
	}

	//Record which Firebase project issued each existing token using the user's home library
	try {
		$aspen_db->exec("UPDATE user_notification_tokens t
			INNER JOIN user u ON t.userId = u.id
			INNER JOIN location l ON u.homeLocationId = l.locationId
			INNER JOIN library lib ON l.libraryId = lib.libraryId
			SET t.firebaseSettingId = lib.firebaseSettingId
			WHERE t.tokenType = 'firebase'");
	} catch (PDOException $e) {
		$errors[] = 'Could not link existing push tokens to Firebase settings: ' . $e->getMessage();
	}

	try {
		if (empty($errors)) {
			$dropColumns = implode(', ', array_map(fn($column) => "DROP COLUMN $column", $firebaseColumns));
			$aspen_db->exec("ALTER TABLE aspen_pwa_settings $dropColumns");
			$update['success'] = true;
			$update['status'] = "Copied $numCopied Firebase settings and removed them from Aspen PWA settings";
		} else {
			//Leave the original values in place, but stop them from blocking new PWA settings
			$modifyColumns = implode(', ', array_map(fn($column) => "MODIFY COLUMN $column " . ($column == 'serviceAccount' ? 'varchar(5000)' : ($column == 'vapidKey' ? 'varchar(100)' : 'varchar(50)')) . ' DEFAULT NULL', $firebaseColumns));
			$aspen_db->exec("ALTER TABLE aspen_pwa_settings $modifyColumns");
			$update['success'] = false;
			$update['status'] = implode('<br/>', $errors);
		}
	} catch (PDOException $e) {
		$errors[] = 'Could not update Aspen PWA settings columns: ' . $e->getMessage();
		$update['success'] = false;
		$update['status'] = implode('<br/>', $errors);
	}
}