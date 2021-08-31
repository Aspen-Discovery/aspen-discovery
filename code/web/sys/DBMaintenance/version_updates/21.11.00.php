<?php
/** @noinspection PhpUnused */
function getUpdates21_11_00() : array
{
	return [
		'showCardExpirationDate' => [
			'title' => 'Add app access level options',
			'description' => 'Create app access level for the greenhouse',
			'sql' => [
				'ALTER TABLE library ADD COLUMN showCardExpirationDate TINYINT(1) DEFAULT 1'
			]
		], //showCardExpirationDate
		'materialsRequestStaffComments' => [
			'title' => 'Allow adding staff comments to Materials Requests',
			'description' => 'Allow adding staff comments to Materials Requests',
			'sql' => [
				'ALTER TABLE materials_request ADD COLUMN staffComments TEXT'
			]
		], //materialsRequestStaffComments
		'additionalTranslationTermInfo' => [
			'title' => 'Add categorizing of translation terms',
			'description' =>  'Add additional information to translation terms to be able categorize them',
			'sql' => [
				'ALTER TABLE translation_terms ADD COLUMN isPublicFacing TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN isAdminFacing TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN isMetadata TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN isAdminEnteredData TINYINT(1) DEFAULT 0',
				'ALTER TABLE translation_terms ADD COLUMN lastUpdate INT(11) DEFAULT 0',
			]
		], //additionalTranslationTermInfo
		'addGreenhouseUrl' => [
			'title' => 'Add Greenhouse URL',
			'description' => 'Add a link to the Greenhouse',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN greenhouseUrl VARCHAR(128)'
			]
		], //addGreenhouseUrl
		'removeIslandoraTables' => [
			'title' => 'Remove Islandora Tables',
			'description' => 'Remove unused Islandora Tables',
			'sql' => [
				'DROP TABLE islandora_object_cache',
				'DROP TABLE islandora_samepika_cache',
				'DROP TABLE library_archive_search_facet_setting',
				'DROP TABLE library_archive_more_details',
				'DROP TABLE library_archive_explore_more_bar',
				'DROP TABLE archive_subjects',
				'DROP TABLE archive_private_collections',
				'ALTER TABLE library DROP COLUMN enableArchive',
				'ALTER TABLE library DROP COLUMN archiveNamespace',
				'ALTER TABLE library DROP COLUMN archivePid',
				'ALTER TABLE library DROP COLUMN hideAllCollectionsFromOtherLibraries',
				'ALTER TABLE library DROP COLUMN collectionsToHide',
				'ALTER TABLE library DROP COLUMN objectsToHide',
				'ALTER TABLE library DROP COLUMN defaultArchiveCollectionBrowseMode',
				'ALTER TABLE library DROP COLUMN allowRequestsForArchiveMaterials',
				'ALTER TABLE library DROP COLUMN archiveRequestMaterialsHeader',
				'ALTER TABLE library DROP COLUMN claimAuthorshipHeader',
				'ALTER TABLE library DROP COLUMN archiveRequestEmail',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldName',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldAddress',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldAddress2',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldCity',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldState',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldZip',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldCountry',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldPhone',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldAlternatePhone',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldFormat',
				'ALTER TABLE library DROP COLUMN archiveRequestFieldPurpose',
				'ALTER TABLE library DROP COLUMN archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode',
			]
		], //removeIslandoraTables
		'remove_econtent_support_address' => array(
			'title' => 'Remove eContent Support Address',
			'description' => 'Remove unused support email address for eContent problems.',
			'continueOnError' => true,
			'sql' => array(
				"ALTER TABLE `library` DROP COLUMN eContentSupportAddress",
			),
		), //remove_econtent_support_address
		'enableAppAccess' => [
			'title' => 'Enable app access per location',
			'description' => 'Turn on/off app access per location',
			'sql' => [
				'ALTER TABLE location ADD COLUMN enableAppAccess TINYINT(1) DEFAULT 0'
			]
		], //enableAppAccess
	];
}