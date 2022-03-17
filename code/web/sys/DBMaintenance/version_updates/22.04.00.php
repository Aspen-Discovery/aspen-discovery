<?php
/** @noinspection PhpUnused */
function getUpdates22_04_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'restrictLoginToLibraryMembers' => [
			'title' => 'Restrict Login to Library Members',
			'description' => 'Allow restricting login to patrons of a specific home system',
			'sql' => [
				'ALTER TABLE library ADD COLUMN allowLoginToPatronsOfThisLibraryOnly TINYINT(1) DEFAULT 0',
				'ALTER TABLE library ADD COLUMN messageForPatronsOfOtherLibraries TEXT'
			]
		], //restrictLoginToLibraryMembers
		'catalogStatus' => [
			'title' => 'Catalog Status',
			'description' => 'Allow placing Aspen into offline mode via System Variables',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN catalogStatus TINYINT(1) DEFAULT 0',
				"ALTER TABLE system_variables ADD COLUMN offlineMessage TEXT",
				"UPDATE system_variables set offlineMessage = 'The catalog is down for maintenance, please check back later.'",
				"DROP TABLE IF EXISTS offline_holds"
			]
		], //catalogStatus
		'user_hoopla_confirmation_checkout_prompt2' => array(
			'title' => 'Hoopla Checkout Confirmation Prompt - recreate',
			'description' => 'Stores user preference whether or not to prompt for confirmation before checking out a title from Hoopla',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE `user` ADD COLUMN `hooplaCheckOutConfirmation` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;"
			),
		), //user_hoopla_confirmation_checkout_prompt2
		'user_hideResearchStarters' => [
			'title' => 'User Hide Research Starters - recreate',
			'description' => 'Recreates column to hide research starters',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user ADD COLUMN hideResearchStarters TINYINT(1) DEFAULT 0"
			),
		], //user_hideResearchStarters
		'user_role_uniqueness' => [
			'title' => 'User Role Uniqueness',
			'description' => 'Update Uniqueness for User Roles',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE user_roles DROP PRIMARY KEY",
				"ALTER TABLE user_roles ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
			),
		], //user_role_uniqueness
		'browse_category_times_shown' => [
			'title' => 'Browse Category Times Shown',
			'description' => 'Make times shown an int rather than medium int',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE browse_category CHANGE COLUMN numTimesShown numTimesShown INT NOT NULL DEFAULT  0",
			),
		], //browse_category_times_shown
	];
}