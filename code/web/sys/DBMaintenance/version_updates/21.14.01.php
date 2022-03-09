<?php
/** @noinspection PhpUnused */
function getUpdates21_14_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'externalRequestsLogMethod' => [
			'title' => 'External Requests Log Method',
			'description' => 'Add method to External Requests Log',
			'sql' => [
				'ALTER TABLE external_request_log ADD COLUMN requestType VARCHAR(50)',
				'ALTER TABLE external_request_log ADD COLUMN requestMethod VARCHAR(5)',
				'ALTER TABLE external_request_log ADD INDEX requestType(requestType)',
			]
		], //externalRequestsLogMethod

	];
}