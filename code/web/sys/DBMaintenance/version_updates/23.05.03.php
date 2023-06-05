<?php
/** @noinspection PhpUnused */
function getUpdates23_05_03(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'continueOnError' => false,
			'sql' => [
				''
			]
		], //sample*/

		'automatic_update_settings' => [
			'title' => 'Automatic Update Settings',
			'description' => 'Add settings to control automatic updates.',
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN allowScheduledUpdates TINYINT(1) DEFAULT 1",
				"ALTER TABLE system_variables ADD COLUMN doQuickUpdates TINYINT(1) DEFAULT 0",
			]
		],

	];
}