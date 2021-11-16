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
	];
}