<?php
/** @noinspection PhpUnused */
function getUpdates23_01_01(): array {
	$curTime = time();
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
        ], //sample*/

		'update_api_usage_uniqueness' => [
			'title' => 'Update API Usage Uniqueness',
			'description' => 'Add instance to uniqueness',
			'sql' => [
				"ALTER TABLE api_usage ADD UNIQUE INDEX uniqueness(year, month, instance, module, method)",
				"ALTER TABLE api_usage DROP INDEX year",
			],
		],
		//update_api_usage_uniqueness
		'externalRequestsRequestMethodLength' => [
			'title' => 'External Requests Request Method Length',
			'description' => 'Increase the length of the request method column',
			'sql' => [
				'ALTER TABLE external_request_log CHANGE COLUMN requestMethod requestMethod VARCHAR(10)',
			],
		],
	];
}