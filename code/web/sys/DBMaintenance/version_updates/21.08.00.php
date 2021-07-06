<?php
/** @noinspection PhpUnused */
function getUpdates21_08_00() : array
{
	return [
		'quipu_ecard_settings' => [
			'title' => 'Quipu eCARD Settings',
			'description' => 'Add the ability to define settings for Quipu eCARD integration',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE quipu_ecard_setting (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					server VARCHAR(50) NOT NULL, 
					clientId INT(11) NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			]
		], //quipu_ecard_settings
		'store_grouped_work_record_item_scope' => [
			'title' => 'Grouped Work Records, Items, Scopes',
			'description' => 'Store more information about grouped works within the database for easier access',
			'sql' => [
				"DROP TABLE IF EXISTS indexed_record_source",
				"DROP TABLE IF EXISTS indexed_format",
				"DROP TABLE IF EXISTS indexed_format_category",
				"DROP TABLE IF EXISTS indexed_language",
				"DROP TABLE IF EXISTS indexed_edition",
				"DROP TABLE IF EXISTS indexed_publisher",
				"DROP TABLE IF EXISTS indexed_publicationDate",
				"DROP TABLE IF EXISTS indexed_physicalDescription",
				"DROP TABLE IF EXISTS indexed_shelfLocation",
				"DROP TABLE IF EXISTS indexed_eContentSource",
				"DROP TABLE IF EXISTS indexed_groupedStatus",
				"DROP TABLE IF EXISTS indexed_status",
				"DROP TABLE IF EXISTS indexed_callNumber",
				"DROP TABLE IF EXISTS indexed_itemType",
				"DROP TABLE IF EXISTS indexed_locationCode",
				"DROP TABLE IF EXISTS indexed_subLocationCode",
				"DROP TABLE IF EXISTS scope",
				"DROP TABLE IF EXISTS grouped_work_records",
				"DROP TABLE IF EXISTS grouped_work_variation",
				"DROP TABLE IF EXISTS grouped_work_record_items",
				"DROP TABLE IF EXISTS grouped_work_record_scope",
				"DROP TRIGGER IF EXISTS after_grouped_work_record_items_delete",
				"DROP TRIGGER IF EXISTS after_grouped_work_records_delete",
				"DROP TRIGGER IF EXISTS after_grouped_work_delete",

				"CREATE TABLE indexed_record_source (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					source VARCHAR(50) collate utf8_bin,
					subSource VARCHAR(255) collate utf8_bin,
					UNIQUE(source, subSource)
				) ENGINE INNODB",
				"CREATE TABLE indexed_format (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					format VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_format_category (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					formatCategory VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_language (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					language VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_edition (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					edition VARCHAR(255) collate utf8_bin UNIQUE
				) ENGINE INNODB",
				"CREATE TABLE indexed_publisher (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					publisher VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_publicationDate (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					publicationDate VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_physicalDescription (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					physicalDescription VARCHAR(1000) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_shelfLocation (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					shelfLocation VARCHAR(600) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_eContentSource (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					eContentSource VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_groupedStatus (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedStatus VARCHAR(75) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_status (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					status VARCHAR(75) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_callNumber (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					callNumber VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_itemType (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					itemType VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_locationCode (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					locationCode VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE indexed_subLocationCode (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					subLocationCode VARCHAR(255) collate utf8_bin UNIQUE 
				) ENGINE INNODB",
				"CREATE TABLE grouped_work_variation (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkId int(11) NOT NULL,
					primaryLanguageId INT(11),
					eContentSourceId INT(11),
					formatId INT(11),
					formatCategoryId INT(11),
					INDEX(groupedWorkId)
				) ENGINE INNODB",
				"CREATE TABLE grouped_work_records (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkId int(11) NOT NULL,
					sourceId INT(11),
					recordIdentifier VARCHAR(50) collate utf8_bin,
					formatId INT(11),
					formatCategoryId INT(11),
					editionId INT(11),
					publisherId INT(11),
					publicationDateId INT(11),
					physicalDescriptionId INT(11),
					languageId INT(11),
					INDEX(groupedWorkId),
					UNIQUE INDEX(sourceId, recordIdentifier)
				)  ENGINE INNODB",
				"CREATE TABLE grouped_work_record_items (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkRecordId INT(11) NOT NULL,
					groupedWorkVariationId INT(11) NOT NULL,
					itemId VARCHAR(255),
					shelfLocationId INT(11), 
					callNumberId INT(11),
					sortableCallNumberId INT(11),
					numCopies INT(11),
					isOrderItem TINYINT DEFAULT 0,
					statusId TINYINT(1),
					dateAdded LONG,
					locationCodeId INT(11),
					subLocationCodeId INT(11),
					lastCheckInDate LONG,
					UNIQUE (itemId, groupedWorkRecordId),
					INDEX (groupedWorkRecordId),
					INDEX (groupedWorkVariationId)
				) ENGINE INNODB",
				"CREATE TABLE scope (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(255) collate utf8_bin UNIQUE,
					isLibraryScope TINYINT (1),
					isLocationScope TINYINT(1),
					INDEX (name, isLibraryScope, isLocationScope)
				) ENGINE INNODB",
				"CREATE TABLE grouped_work_record_scope (
					id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					groupedWorkItemId INT(11) NOT NULL,
					scopeId INT(11),
					groupedStatusId INT(11),
					statusId INT(11),
					available TINYINT(1),
					holdable TINYINT(1),
					inLibraryUseOnly TINYINT(1),
					localUrl VARCHAR(1000),
					locallyOwned TINYINT(1),
					libraryOwned TINYINT(1),
					INDEX (groupedWorkItemId),
					INDEX (scopeId),
					UNIQUE (groupedWorkItemId, scopeId)
				) ENGINE INNODB",
				"CREATE TRIGGER after_grouped_work_record_items_delete 
					AFTER DELETE ON grouped_work_record_items 
					FOR EACH ROW BEGIN
					DELETE FROM grouped_work_record_scope where groupedWorkItemId = old.id;
					END",
				"CREATE TRIGGER after_grouped_work_records_delete 
					AFTER DELETE ON grouped_work_records 
					FOR EACH ROW
					DELETE FROM grouped_work_record_items where groupedWorkRecordId = old.id",
				"CREATE TRIGGER after_grouped_work_delete 
					AFTER DELETE ON grouped_work
					FOR EACH ROW BEGIN
					DELETE FROM grouped_work_records where groupedWorkId = old.id;
					DELETE FROM grouped_work_variation where groupedWorkId = old.id;
					END",
				"CREATE TRIGGER after_scope_delete 
					AFTER DELETE ON scope
					FOR EACH ROW
					DELETE FROM grouped_work_record_scope where scopeId = old.id",
			]
		], //store_grouped_work_record_item_scope
		'storeRecordDetailsInSolr' => [
			'title' => 'Add Store Record Details In Solr to System Variables',
			'description' => 'Provides backwards compatibility to 21.07',
			'sql' => [
				'ALTER TABLE system_variables ADD COLUMN storeRecordDetailsInSolr TINYINT(1) DEFAULT 0'
			]
		], //storeRecordDetailsInSolr
		'comprise_settings' => [
			'title' => 'Add settings for Comprise',
			'description' => 'Add settings for Comprise integration',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS comprise_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					customerName VARCHAR(50) UNIQUE,
					customerId INT(11) UNIQUE,
					username VARCHAR(50),
					password VARCHAR(256)
				) ENGINE INNODB',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('eCommerce', 'Administer Comprise', '', 10, 'Controls if the user can change Comprise settings. <em>This has potential security and cost implications.</em>')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Comprise'))",
			]
		], //comprise_settings
		'comprise_link_to_library' => [
			'title' => 'Link Comprise to library',
			'description' => 'Link comprise settings to the library',
			'sql' => [
				"DROP TABLE if exists library_comprise_setting",
				"ALTER TABLE library ADD COLUMN compriseSettingId INT(11) DEFAULT -1"
			]
		], //comprise_link_to_library
		'force_reload_of_cloud_library_21_08' => [
			'title' => 'Force reload of Cloud Library',
			'description' => 'Force Cloud Library to be reloaded for 21.08',
			'sql' => [
				"UPDATE cloud_library_settings set runFullUpdate = 1",
			]
		], //force_reload_of_cloud_library_21_08
		'indexed_information_length' => [
			'title' => 'Indexed Information Lengths',
			'description' => 'Increase the length of some indexed information',
			'sql' => [
				"ALTER TABLE indexed_edition CHANGE COLUMN edition edition VARCHAR(1000) collate utf8_bin UNIQUE ",
				"ALTER TABLE indexed_physicalDescription CHANGE COLUMN physicalDescription physicalDescription VARCHAR(1000) collate utf8_bin UNIQUE ",
			]
		], //indexed_information_length
		'renew_error' => [
			'title' => 'Add renew error on user checkouts',
			'description' => 'Displays users without auto renew why their hold is not renewable',
			'sql' => [
				'ALTER TABLE user_checkout ADD COLUMN renewError VARCHAR(500)'
			]
		],//store_renew_error_for_checkouts
		'hold_request_confirmations' => [
			'title' => 'Hold Request Confirmations',
			'description' => 'Create a table to store confirmation info for hold requests',
			'sql' => [
				'CREATE TABLE hold_request_confirmation (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
					userId INT(11) NOT NULL,
					requestId VARCHAR(36) NOT NULL,
					additionalParams TEXT
				) ENGINE INNODB'
			]
		], //hold_request_confirmations
		'plural_grouped_work_facet' => [
			'title' => 'Add plural version of grouped work facet column',
			'description' => 'Store the plural version of grouped work facet display names',
			'sql' => [
				'ALTER TABLE grouped_work_facet ADD COLUMN displayNamePlural VARCHAR(50)'
			]
		],//plural_grouped_work_facet
		'create_plural_grouped_work_facets' => [
			'title' => 'Create plural versions of grouped work facets',
			'description' => 'Create the plural versions of existing grouped work facet display names',
			'sql' => [
				'UPDATE grouped_work_facet SET displayNamePlural="Format Categories" WHERE displayName="Format Category"',
				'UPDATE grouped_work_facet SET displayNamePlural="Available?" WHERE displayName="Available?"',
				'UPDATE grouped_work_facet SET displayNamePlural="Fiction / Non-Fiction" WHERE displayName="Fiction / Non-Fiction"',
				'UPDATE grouped_work_facet SET displayNamePlural="Readling Levels" WHERE displayName="Reading Level"',
				'UPDATE grouped_work_facet SET displayNamePlural="Available Now At" WHERE displayName="Available Now At"',
				'UPDATE grouped_work_facet SET displayNamePlural="eContent Collections" WHERE displayName="eContent Collection"',
				'UPDATE grouped_work_facet SET displayNamePlural="Formats" WHERE displayName="Format"',
				'UPDATE grouped_work_facet SET displayNamePlural="Authors" WHERE displayName="Author"',
				'UPDATE grouped_work_facet SET displayNamePlural="Series" WHERE displayName="Series"',
				'UPDATE grouped_work_facet SET displayNamePlural="AR Interest Levels" WHERE displayName="AR Interest Level"',
				'UPDATE grouped_work_facet SET displayNamePlural="AR Reading Levels" WHERE displayName="AR Reading Level"',
				'UPDATE grouped_work_facet SET displayNamePlural="AR Point Values" WHERE displayName="AR Point Value"',
				'UPDATE grouped_work_facet SET displayNamePlural="Subjects" WHERE displayName="Subject"',
				'UPDATE grouped_work_facet SET displayNamePlural="Added in the Last" WHERE displayName="Added in the Last"',
				'UPDATE grouped_work_facet SET displayNamePlural="Awards" WHERE displayName="Awards"',
				'UPDATE grouped_work_facet SET displayNamePlural="Item Types" WHERE displayName="Item Type"',
				'UPDATE grouped_work_facet SET displayNamePlural="Languages" WHERE displayName="Language"',
				'UPDATE grouped_work_facet SET displayNamePlural="Movie Ratings" WHERE displayName="Movie Rating"',
				'UPDATE grouped_work_facet SET displayNamePlural="Publication Dates" WHERE displayName="Publication Date"',
				'UPDATE grouped_work_facet SET displayNamePlural="User Ratings" WHERE displayName="User Rating"',
				'UPDATE grouped_work_facet SET displayNamePlural="Regions" WHERE displayName="Region"',
				'UPDATE grouped_work_facet SET displayNamePlural="Eras" WHERE displayName="Era"',
				'UPDATE grouped_work_facet SET displayNamePlural="Genres" WHERE displayName="Genre"',
				'UPDATE grouped_work_facet SET displayNamePlural="Shelf Locations" WHERE displayName="Shelf Location"',
				'UPDATE grouped_work_facet SET displayNamePlural="Owning Libraries" WHERE displayName="Owning Library"',
				'UPDATE grouped_work_facet SET displayNamePlural="Literary Forms" WHERE displayName="Literary Form"'
			]
		],//create_plural_grouped_work_facets
		'update_plural_grouped_work_facet_label' => [
			'title' => 'Fix plural versions label',
			'description' => 'Fix typo',
			'sql' => [
				'UPDATE grouped_work_facet SET displayNamePlural="Reading Levels" WHERE displayName="Readling Levels"',
			]
		], //update_plural_grouped_work_facet_label
		'treat_unknown_audience_as' => [
			'title' => 'Indexing Profile Treat Unknown Audience As',
			'description' => 'Add the ability to modify how unknown audiences are handled',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN treatUnknownAudienceAs VARCHAR(10) DEFAULT 'Unknown'"
			]
		], //treat_unknown_audience_as
		'force_reload_of_overdrive_21_08' => [
			'title' => 'Force reload of OverDrive',
			'description' => 'Force OverDrive to be reloaded for 21.08',
			'sql' => [
				"UPDATE overdrive_settings set runFullUpdate = 1",
			]
		], //force_reload_of_overdrive_21_08
		'force_reload_of_hoopla_21_08' => [
			'title' => 'Force reload of Hoopla',
			'description' => 'Force Hoopla to be reloaded for 21.08',
			'sql' => [
				"UPDATE hoopla_settings set runFullUpdate = 1",
			]
		], //force_reload_of_hoopla_21_08
	];
}