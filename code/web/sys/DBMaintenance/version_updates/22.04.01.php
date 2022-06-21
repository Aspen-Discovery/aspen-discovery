<?php
/** @noinspection PhpUnused */
function getUpdates22_04_01() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'restrictLoginOfLibraryMembers' => [
			'title' => 'Restrict Login of Library Members',
			'description' => 'Allow restricting login by patrons of a specific home system',
			'sql' => [
				'ALTER TABLE library ADD COLUMN preventLogin TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN preventLoginMessage TEXT'
			]
		], //restrictLoginOfLibraryMembers
		'addUseLineItems_FISWorldPay' => [
			'title' => 'Add option to FIS WorldPay for Line Items',
			'description' => 'Add option to FIS WorldPay to turn on/off sending Line Items',
			'sql' => [
				'ALTER TABLE worldpay_settings ADD COLUMN useLineItems TINYINT(1) DEFAULT 0',
			]
		], //addUseLineItems_FISWorldPay
	];
}
