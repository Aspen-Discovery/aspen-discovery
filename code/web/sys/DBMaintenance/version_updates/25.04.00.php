<?php

function getUpdates25_04_00(): array {
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

		//mark - Grove

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions
		'enable_payments_debugging' => [
			'title' => 'Enable to Show Debugging Information about Paypal Payments',
			'description' => 'Enable to show debugging information about Paypal payments',
			'sql' => [
				'ALTER TABLE paypal_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
				'ALTER TABLE square_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
				'ALTER TABLE stripe_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
				'ALTER TABLE propay_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
				'ALTER TABLE ncr_payments_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
				'ALTER TABLE paypal_payflow_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
				'ALTER TABLE aci_speedpay_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
				'ALTER TABLE invoice_cloud_settings ADD COLUMN enablePaymentsDebugging TINYINT(1) DEFAULT 1',
			]
		], //enable_payments_debugging

		//other

	];
}