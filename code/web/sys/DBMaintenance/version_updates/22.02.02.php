<?php
/** @noinspection PhpUnused */
function getUpdates22_02_02() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'overdrive_handle_ise' => [
			'title' => 'OverDrive ISE Updates',
			'description' => 'Updates to improve handling of internal server errors (500 errors) from OverDrive',
			'sql' => [
				'ALTER TABLE overdrive_settings ADD COLUMN numRetriesOnError INT DEFAULT 1',
				'ALTER TABLE overdrive_settings ADD COLUMN productsToUpdate TEXT'
			]
		], //overdrive_handle_ise
		'overdrive_encrypt_client_secret' => [
			'title' => 'OverDrive Client Secret Encryption',
			'description' => 'Encrypt OverDrive Client Secret at rest',
			'sql' => [
				'ALTER TABLE overdrive_settings CHANGE COLUMN clientSecret clientSecret VARCHAR(256)  COLLATE utf8mb4_general_ci DEFAULT NULL',
			]
		], //overdrive_encrypt_client_secret
		'overdrive_encrypt_client_secret_in_scope' => [
			'title' => 'OverDrive Client Secret Encryption in Scope',
			'description' => 'Encrypt OverDrive Client Secret for Scope  at rest',
			'sql' => [
				'ALTER TABLE overdrive_scopes CHANGE COLUMN clientSecret clientSecret VARCHAR(256)  COLLATE utf8mb4_general_ci DEFAULT NULL',
			]
		], //overdrive_encrypt_client_secret_in_scope
		'grouped_work_record_items_itemId2_index' => [
			'title' => 'Grouped Work Record Items Item Id 2 Index',
			'description' => 'Grouped Work Record Items Item Id 2 Index',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE grouped_work_record_items add INDEX itemId2(itemId);'
			]
		], //grouped_work_record_items_itemId2_index
	];
}
