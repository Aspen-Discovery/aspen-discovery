<?php

function getUpdates25_03_00(): array {
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

		 //Yanjun Li - ByWater	
		'sierra_self_reg_form_updates' => [
			'title' => 'Sierra Self Reg updates',
			'description' => 'Add new fields to Sierra self registration forms',
			'sql' => [
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegNoticePref CHAR(1) DEFAULT "-"',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegTelephoneField VARCHAR(5) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegExpirationDays INT DEFAULT 30',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode1 VARCHAR(25) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode2 VARCHAR(25) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode3 INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPcode4 INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegPatronMessage VARCHAR(35) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegAgency INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra CHANGE COLUMN selfRegPatronCode selfRegPatronType INT DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegBarcodePrefix VARCHAR(10) DEFAULT NULL',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegBarcodeSuffixLength INT DEFAULT 7',
				'ALTER TABLE self_registration_form_sierra ADD COLUMN selfRegGuardianField VARCHAR(10) DEFAULT NULL',
			],
		], //sierra_self_reg_form_updates

		//mark - Grove

		//katherine - Grove

		//kirstien - Grove

		// Leo Stoyanov - BWS

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe

		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

	];
}