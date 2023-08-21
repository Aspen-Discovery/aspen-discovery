<?php
/** @noinspection PhpUnused */
function getUpdates23_08_01(): array {
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

		'change_ecommerceTerms_to_mediumText' => [
			'title' => 'Change eCommerceTerms column to medium text',
			'description' => 'Changes column type for eCommerceTerms from vartext to medium text to allow larger storage',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library MODIFY COLUMN eCommerceTerms MEDIUMTEXT;'
			]
		]
		//change_ecommerceTerms_to_mediumText
	];
}