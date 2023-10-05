<?php
/** @noinspection PhpUnused */
function getUpdates23_09_03(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //name*/

		'updateCatUsername' => [
			'title' => 'Update Cat Username',
			'description' => 'Update Cat Username',
			'continueOnError' => false,
			'sql' => [
				"UPDATE user set cat_username = ils_barcode where ils_barcode is NOT NULL and ils_barcode <> '' and source NOT IN ('admin', 'admin_sso')"
			]
		], //name
	];
}
