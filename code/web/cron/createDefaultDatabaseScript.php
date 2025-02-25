<?php
/** @noinspection SqlResolve */
require_once __DIR__ . '/../bootstrap.php';

set_time_limit(0);
global $configArray;
global $serverName;

global $aspen_db;

$debug = false;

$localDirectory = $configArray['Site']['local'];
$installDirectory = $localDirectory . '/../../install/';
$dbUser = $configArray['Database']['database_user'];
$dbPassword = $configArray['Database']['database_password'];
$dbName = $configArray['Database']['database_aspen_dbname'];
$dbHost = $configArray['Database']['database_aspen_host'];
$dbPort = $configArray['Database']['database_aspen_dbport'];

//Delete the old aspen.sql file
$exportFile = $installDirectory . 'aspen.sql';
unlink($exportFile);

//Create the export files
$listTablesStmt = $aspen_db->query("SHOW TABLES");
$allTables = $listTablesStmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($allTables as $table) {
	$exportData = false;
	//Ignore
	if ($table == 'bad_words' || $table == 'db_update' || $table == 'modules' || $table == 'permissions' || $table == 'role_permissions' || $table == 'roles') {
		$exportData = true;
	}

	if ($exportData) {
		$dumpCommand = "mysqldump -u$dbUser -p$dbPassword -h$dbHost -P$dbPort --skip-comments $dbName $table >> $exportFile";
		/** @noinspection PhpConditionAlreadyCheckedInspection */
		exec_advanced($dumpCommand, $debug);
	}else{
		$createTableStmt = $aspen_db->query("SHOW CREATE TABLE " . $table);
		$createTablesRS = $createTableStmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($createTablesRS as $createTableSql) {
			$fhnd = fopen($exportFile, 'a+');
			fwrite($fhnd, "DROP TABLE IF EXISTS $table;\n");
			$createTableValue = $createTableSql['Create Table'];
			//Remove the auto increment id
			$createTableValue = preg_replace('/AUTO_INCREMENT=\d+/', '', $createTableValue);
			fwrite($fhnd, $createTableValue . ";\n");
			fclose($fhnd);
		}
		//$dumpCommand = "mariadb-dump -u$dbUser -p$dbPassword --skip-comments --no-data $dbName $table >> $exportFile";
	}
}

//Add additional data
$fhnd = fopen($exportFile, 'a');
fwrite($fhnd, "INSERT INTO account_profiles (id, name, driver, loginConfiguration, authenticationMethod, vendorOpacUrl, patronApiUrl, recordSource, weight, ils) VALUES (1,'admin','Library','barcode_pin','db','defaultURL','defaultURL','admin',1,'library');\n");
fwrite($fhnd, "INSERT INTO browse_category (id, textId, userId, sharing, label, description, defaultFilter, defaultSort, searchTerm, numTimesShown, numTitlesClickedOn, sourceListId, source, libraryId, startDate, endDate) VALUES (1,'main_new_fiction',2,'everyone','New Fiction','','literary_form:Fiction','newest_to_oldest','',2,0,-1,'GroupedWork',-1,0,0),(2,'main_new_non_fiction',1,'everyone','New Non Fiction','','literary_form:Non Fiction','newest_to_oldest','',0,0,-1,'GroupedWork',-1,0,0);\n");
fwrite($fhnd, "INSERT INTO browse_category_group (id, name) VALUES (1, 'Main Library');\n");
fwrite($fhnd, "INSERT INTO browse_category_group_entry (browseCategoryGroupId, browseCategoryId) VALUES (1, 1);\n");
fwrite($fhnd, "INSERT INTO browse_category_group_entry (browseCategoryGroupId, browseCategoryId) VALUES (1, 2);\n");
fwrite($fhnd, "INSERT INTO grouped_work_display_settings(id, name, facetGroupId) VALUES (1, 'public', 1);\n");
fwrite($fhnd, "INSERT INTO grouped_work_display_settings(id, name, facetGroupId, applyNumberOfHoldingsBoost, showSearchTools, showInSearchResultsMainDetails, alwaysShowSearchResultsMainDetails) VALUES (2, 'academic', 2, 0, 0, 'a:4:{i:0;s:10:\"showSeries\";i:1;s:13:\"showPublisher\";i:2;s:19:\"showPublicationDate\";i:3;s:13:\"showLanguages\";i:4;s:13:\"showPlaceOfPublication\";}', 1);\n");
fwrite($fhnd, "INSERT INTO grouped_work_display_settings(id, name, facetGroupId, showSearchTools) VALUES (3, 'school_elem', 3, 0);\n");
fwrite($fhnd, "INSERT INTO grouped_work_display_settings(id, name, facetGroupId, showSearchTools) VALUES (4, 'school_upper', 3, 0);\n");
fwrite($fhnd, "INSERT INTO grouped_work_display_settings(id, name, facetGroupId, baseAvailabilityToggleOnLocalHoldingsOnly, includeAllRecordsInShelvingFacets, includeAllRecordsInDateAddedFacets, includeOutOfSystemExternalLinks) VALUES (5, 'consortium', 4, 0, 1, 1, 1);\n");
/** @noinspection SpellCheckingInspection */
fwrite($fhnd, "INSERT INTO grouped_work_facet VALUES (1,1,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(2,1,'Search Within','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(3,1,'Fiction / Non-Fiction','literary_form',3,5,0,'num_results',0,1,1,1,1,0,1,1,'Fiction / Non-Fiction'),(4,1,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Reading Levels'),(5,1,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(6,1,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(7,1,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(8,1,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(9,1,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(10,1,'AR Interest Level','accelerated_reader_interest_level',10,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Interest Levels'),(11,1,'AR Reading Level','accelerated_reader_reading_level',11,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Reading Levels'),(12,1,'AR Point Value','accelerated_reader_point_value',12,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Point Values'),(13,1,'Subject','subject_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(14,1,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(15,1,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(16,1,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(17,1,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(18,1,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(19,1,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(20,1,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings'),(21,2,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(22,2,'Available?','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(23,2,'Literary Form','literary_form',3,5,0,'num_results',0,1,1,1,1,0,0,1,'Literary Forms'),(24,2,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Readling Levels'),(25,2,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(26,2,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(27,2,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(28,2,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(29,2,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(30,2,'Subject','topic_facet',10,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(31,2,'Region','geographic_facet',11,5,0,'num_results',0,0,1,1,1,0,0,0,'Regions'),(32,2,'Era','era',12,5,0,'num_results',0,0,1,1,1,0,0,0,'Eras'),(33,2,'Genre','genre_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Genres'),(34,2,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(35,2,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(36,2,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(37,2,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(38,2,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(39,2,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(40,2,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings'),(41,3,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(42,3,'Available?','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(43,3,'Fiction / Non-Fiction','literary_form',3,5,0,'num_results',0,1,1,1,1,0,1,1,'Fiction / Non-Fiction'),(44,3,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Readling Levels'),(45,3,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(46,3,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(47,3,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(48,3,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(49,3,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(50,3,'AR Interest Level','accelerated_reader_interest_level',10,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Interest Levels'),(51,3,'AR Reading Level','accelerated_reader_reading_level',11,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Reading Levels'),(52,3,'AR Point Value','accelerated_reader_point_value',12,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Point Values'),(53,3,'Subject','subject_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(54,3,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(55,3,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(56,3,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(57,3,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(58,3,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(59,3,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(60,3,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings'),(61,4,'Format Category','format_category',1,0,0,'num_results',1,1,1,1,1,0,0,0,'Format Categories'),(62,4,'Available?','availability_toggle',2,0,0,'num_results',1,1,1,1,1,0,0,0,'Available?'),(63,4,'Fiction / Non-Fiction','literary_form',3,5,0,'num_results',0,1,1,1,1,0,1,1,'Fiction / Non-Fiction'),(64,4,'Reading Level','target_audience',4,8,0,'num_results',0,1,1,1,1,0,1,1,'Readling Levels'),(65,4,'Available Now At','available_at',5,5,0,'num_results',0,1,1,1,1,0,0,0,'Available Now At'),(66,4,'eContent Collection','econtent_source',6,5,0,'num_results',0,1,1,1,1,0,1,0,'eContent Collections'),(67,4,'Format','format',7,5,0,'num_results',0,1,1,1,1,0,1,1,'Formats'),(68,4,'Author','authorStr',8,5,0,'num_results',0,1,1,1,1,0,0,0,'Authors'),(69,4,'Series','series_facet',9,5,0,'num_results',0,1,1,1,1,0,1,0,'Series'),(70,4,'AR Interest Level','accelerated_reader_interest_level',10,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Interest Levels'),(71,4,'AR Reading Level','accelerated_reader_reading_level',11,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Reading Levels'),(72,4,'AR Point Value','accelerated_reader_point_value',12,5,0,'num_results',0,1,1,1,1,0,0,0,'AR Point Values'),(73,4,'Subject','subject_facet',13,5,0,'num_results',0,1,1,1,1,0,1,0,'Subjects'),(74,4,'Added in the Last','time_since_added',14,5,0,'num_results',0,1,1,1,1,0,0,0,'Added in the Last'),(75,4,'Awards','awards_facet',15,5,0,'num_results',0,0,1,1,1,0,0,0,'Awards'),(76,4,'Item Type','itype',16,5,0,'num_results',0,0,1,1,1,0,0,0,'Item Types'),(77,4,'Language','language',17,5,0,'num_results',0,1,1,1,1,0,1,1,'Languages'),(78,4,'Movie Rating','mpaa_rating',18,5,0,'num_results',0,0,1,1,1,0,1,0,'Movie Ratings'),(79,4,'Publication Date','publishDateSort',19,5,0,'num_results',0,1,1,1,1,0,0,0,'Publication Dates'),(80,4,'User Rating','rating_facet',20,5,0,'num_results',0,1,1,1,1,0,0,0,'User Ratings');\n");
fwrite($fhnd, "INSERT INTO grouped_work_facet_groups (id, name) VALUES (1, 'public'), (2, 'academic'), (3, 'schools'), (4, 'consortia');\n");
fwrite($fhnd, "INSERT INTO ip_lookup (id, locationId, location, ip, startIpVal, endIpVal, isOpac, blockAccess, allowAPIAccess, showDebuggingInformation, logTimingInformation, logAllQueries) VALUES (1,-1,'Internal','127.0.0.1',2130706433,2130706433,0,0,1,1,0,0);\n");
fwrite($fhnd, "INSERT INTO languages VALUES (1,0,'en','English','English','English',0,'en-US');\n");
fwrite($fhnd, "INSERT INTO layout_settings (id, name) VALUES (1, 'default');\n");
fwrite($fhnd, "INSERT INTO library (libraryId, subdomain, displayName, finePaymentType, repeatSearchOption, allowPinReset, loginFormUsernameLabel, loginFormPasswordLabel, isDefault, browseCategoryGroupId, groupedWorkDisplaySettingId) VALUES (1,'main','Main Library',0, 'none', 0, 'Library Barcode', 'PIN / Password', 1, 1, 1);\n");
fwrite($fhnd, "INSERT INTO list_indexing_settings VALUES (1,1,0,0);\n");
fwrite($fhnd, "INSERT INTO location (locationId, code, displayName, libraryId, groupedWorkDisplaySettingId, browseCategoryGroupId)VALUES (1,'main','Main Library',1, 1, 1);\n");
fwrite($fhnd, "INSERT INTO `materials_request_status` (`id`, `description`, `isDefault`, `sendEmailToPatron`, `emailTemplate`, `isOpen`, `isPatronCancel`, `libraryId`, `checkForHolds`, `holdPlacedSuccessfully`, `holdFailed`, `holdNotNeeded`) VALUES
	(1, 'Request Pending', 1, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(2, 'Already owned/On order', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The Library already owns this item or it is already on order. Please access our catalog to place this item on hold.  Please check our online catalog periodically to put a hold for this item.', 0, 0, -1, 0, 0, 0, 0),
	(3, 'Item purchased', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Outcome: The library is purchasing the item you requested. Please check our online catalog periodically to put yourself on hold for this item. We anticipate that this item will be available soon for you to place a hold.', 1, 0, -1, 1, 0, 0, 0),
	(4, 'Referred to Collection Development - Adult', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(5, 'Referred to Collection Development - J/YA', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(6, 'Referred to Collection Development - AV', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(7, 'ILL Under Review', 0, 0, '', 1, 0, -1, 0, 0, 0, 0),
	(8, 'Request Referred to ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The library\'s Interlibrary loan department is reviewing your request. We will attempt to borrow this item from another system. This process generally takes about 2 - 6 weeks.', 1, 0, -1, 0, 0, 0, 0),
	(9, 'Request Filled by ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Our Interlibrary Loan Department is set to borrow this item from another library.', 0, 0, -1, 0, 0, 0, 0),
	(10, 'Ineligible ILL', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. Your library account is not eligible for interlibrary loan at this time.', 0, 0, -1, 0, 0, 0, 0),
	(11, 'Not enough info - please contact Collection Development to clarify', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We need more specific information in order to locate the exact item you need. Please re-submit your request with more details.', 1, 0, -1, 0, 0, 0, 0),
	(12, 'Unable to acquire the item - out of print', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is out of print.', 0, 0, -1, 0, 0, 0, 0),
	(13, 'Unable to acquire the item - not available in the US', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available in the US.', 0, 0, -1, 0, 0, 0, 0),
	(14, 'Unable to acquire the item - not available from vendor', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item is not available from a preferred vendor.', 0, 0, -1, 0, 0, 0, 0),
	(15, 'Unable to acquire the item - not published', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested has not yet been published. Please check our catalog when the publication date draws near.', 0, 0, -1, 0, 0, 0, 0),
	(16, 'Unable to acquire the item - price', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1, 0, 0, 0, 0),
	(17, 'Unable to acquire the item - publication date', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. We regret that we are unable to acquire the item you requested. This item does not fit our collection guidelines.', 0, 0, -1, 0, 0, 0, 0),
	(18, 'Unavailable', 0, 1, 'This e-mail is to let you know the status of your recent request for an item that you did not find in our catalog. The item you requested cannot be purchased at this time from any of our regular suppliers and is not available from any of our lending libraries.', 0, 0, -1, 0, 0, 0, 0),
	(19, 'Cancelled by Patron', 0, 0, '', 0, 1, -1, 0, 0, 0, 0),
	(20, 'Cancelled - Duplicate Request', 0, 0, '', 0, 0, -1, 0, 0, 0, 0),
	(21, 'Hold Placed', 0, 1, '{title} has been received by the library and you have been added to the hold queue. 

Thank you for your purchase suggestion!', 0, 0, -1, 0, 1, 0, 0),
	(22, 'Hold Failed', 0, 1, '{title} has been received by the library, however we were not able to add you to the hold queue. Please ensure that your account is in good standing and then visit our catalog to place your hold.

	Thanks', 0, 0, -1, 0, 0, 1, 0),
	(23, 'Hold Not Needed', 0, 0, '', 0, 0, -1, 0, 0, 0, 1);\n");
/** @noinspection SqlWithoutWhere */
fwrite($fhnd, "UPDATE modules set enabled=0;UPDATE modules set enabled=1 where name in ('Side Loads', 'User Lists');\n");
fwrite($fhnd, "INSERT INTO system_variables (currencyCode, storeRecordDetailsInSolr, storeRecordDetailsInDatabase, indexVersion, searchVersion, appScheme, trackIpAddresses) VALUES ('USD', 0, 1, 2, 2, 'aspen-lida', 0); \n");
/** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
fwrite($fhnd, "INSERT INTO themes 
  (id,themeName,logoName,headerBackgroundColor,headerBackgroundColorDefault,headerForegroundColor,headerForegroundColorDefault,
  generatedCss,
  pageBackgroundColor,pageBackgroundColorDefault,primaryBackgroundColor,primaryBackgroundColorDefault,primaryForegroundColor,primaryForegroundColorDefault,
  bodyBackgroundColor,bodyBackgroundColorDefault,bodyTextColor,bodyTextColorDefault,
  secondaryBackgroundColor,secondaryBackgroundColorDefault,secondaryForegroundColor,secondaryForegroundColorDefault,
  tertiaryBackgroundColor,tertiaryBackgroundColorDefault,tertiaryForegroundColor,tertiaryForegroundColorDefault,
  browseCategoryPanelColor,browseCategoryPanelColorDefault,selectedBrowseCategoryBackgroundColor,selectedBrowseCategoryBackgroundColorDefault,
  selectedBrowseCategoryForegroundColor,selectedBrowseCategoryForegroundColorDefault,selectedBrowseCategoryBorderColor,selectedBrowseCategoryBorderColorDefault,
  deselectedBrowseCategoryBackgroundColor,deselectedBrowseCategoryBackgroundColorDefault,deselectedBrowseCategoryForegroundColor,deselectedBrowseCategoryForegroundColorDefault,
  deselectedBrowseCategoryBorderColor,deselectedBrowseCategoryBorderColorDefault,menubarHighlightBackgroundColor,menubarHighlightBackgroundColorDefault,
  menubarHighlightForegroundColor,menubarHighlightForegroundColorDefault,capitalizeBrowseCategories,
  browseCategoryImageSize,browseImageLayout,fullWidth,coverStyle,headerBackgroundImage,headerBackgroundImageSize,headerBackgroundImageRepeat,displayName
 ) VALUES (
  1,'default','logoNameTL_Logo_final.png','#f1f1f1',1,'#303030',1,
  '<style>h1 small, h2 small, h3 small, h4 small, h5 small{color: #6B6B6B;}#header-wrapper{background-color: #f1f1f1;background-image: none;color: #303030;}#library-name-header{color: #303030;}#footer-container{background-color: #f1f1f1;color: #303030;}body {background-color: #ffffff;color: #6B6B6B;}a,a:visited,.result-head,#selected-browse-label a,#selected-browse-label a:visited{color: #3174AF;}a:hover,.result-head:hover,#selected-browse-label a:hover{color: #265a87;}body .container, #home-page-browse-content{background-color: #ffffff;color: #6B6B6B;}#selected-browse-label{background-color: #ffffff;}.table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th{background-color: #fafafa;}.table-sticky thead tr th{background-color: #ffffff;}#home-page-search, #horizontal-search-box,.searchTypeHome,.searchSource,.menu-bar {background-color: #0a7589;color: #ffffff;}#horizontal-menu-bar-container{background-color: #f1f1f1;color: #303030;position: relative;}#horizontal-menu-bar-container, #horizontal-menu-bar-container .menu-icon, #horizontal-menu-bar-container .menu-icon .menu-bar-label,#horizontal-menu-bar-container .menu-icon:visited{background-color: #f1f1f1;color: #303030;}#horizontal-menu-bar-container .menu-icon:hover, #horizontal-menu-bar-container .menu-icon:focus,#horizontal-menu-bar-container .menu-icon:hover .menu-bar-label, #horizontal-menu-bar-container .menu-icon:focus .menu-bar-label,#menuToggleButton.selected{background-color: #f1f1f1;color: #265a87;}#horizontal-search-label,#horizontal-search-box #horizontal-search-label{color: #ffffff;}.dropdownMenu, #account-menu, #header-menu, .dropdown .dropdown-menu.dropdownMenu{background-color: #ededed;color: #404040;}.dropdownMenu a, .dropdownMenu a:visited{color: #404040;}.modal-header, .modal-footer{background-color: #ffffff;color: #333333;}.close, .close:hover, .close:focus{color: #333333;}.modal-header{border-bottom-color: #e5e5e5;}.modal-footer{border-top-color: #e5e5e5;}.modal-content{background-color: #ffffff;color: #333333;}.exploreMoreBar{border-color: #0a7589;background: #0a758907;}.exploreMoreBar .label-top, .exploreMoreBar .label-top img{background-color: #0a7589;color: #ffffff;}.exploreMoreBar .exploreMoreBarLabel{color: #ffffff;}#home-page-search-label,#home-page-advanced-search-link,#keepFiltersSwitchLabel,.menu-bar, #horizontal-menu-bar-container {color: #ffffff}.facetTitle, .exploreMoreTitle, .panel-heading, .panel-heading .panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title{background-color: #e7e7e7;}.facetTitle, .exploreMoreTitle,.panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title, .panel-title > a,.panel-default > .panel-heading{color: #333333;}.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,.active .panel-default > .panel-heading, .sidebar-links .active .panel-heading, #account-link-accordion .panel.active .panel-title, #account-settings-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading, .adminSection .adminPanel .adminSectionLabel{background-color: #de9d03;}.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,#more-details-accordion .active .panel-title,#account-link-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading,.adminSection .adminPanel .adminSectionLabel, .facetLock.pull-right a{color: #303030;}.panel-body,.sidebar-links .panel-body,#more-details-accordion .panel-body,.facetDetails,.sidebar-links .panel-body a:not(.btn), .sidebar-links .panel-body a:visited:not(.btn), .sidebar-links .panel-body a:hover:not(.btn),.adminSection .adminPanel{background-color: #ffffff;color: #404040;}.facetValue, .facetValue a,.adminSection .adminPanel .adminActionLabel,.adminSection .adminPanel .adminActionLabel a{color: #404040;}.breadcrumbs{background-color: #f5f5f5;color: #6B6B6B;}.breadcrumb > li + li::before{color: #6B6B6B;}#footer-container{border-top-color: #de1f0b;}#horizontal-menu-bar-container{border-bottom-color: #de1f0b;}#home-page-browse-header{background-color: #d7dce3;}.browse-category,#browse-sub-category-menu button{background-color: #0087AB !important;border-color: #0087AB !important;color: #ffffff !important;}.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover{border-color: #0087AB !important;background-color: #0087AB !important;color: #ffffff !important;}.btn-default,.btn-default:visited,a.btn-default,a.btn-default:visited{background-color: #ffffff;color: #333333;border-color: #cccccc;}.btn-default:hover, .btn-default:focus, .btn-default:active, .btn-default.active, .open .dropdown-toggle.btn-default{background-color: #eeeeee;color: #333333;border-color: #cccccc;}.btn-primary,.btn-primary:visited,a.btn-primary,a.btn-primary:visited{background-color: #1b6ec2;color: #ffffff;border-color: #1b6ec2;}.btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active, .open .dropdown-toggle.btn-primary{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.btn-action,.btn-action:visited,a.btn-action,a.btn-action:visited{background-color: #1b6ec2;color: #ffffff;border-color: #1b6ec2;}.btn-action:hover, .btn-action:focus, .btn-action:active, .btn-action.active, .open .dropdown-toggle.btn-action{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.btn-info,.btn-info:visited,a.btn-info,a.btn-info:visited{background-color: #8cd2e7;color: #000000;border-color: #999999;}.btn-info:hover, .btn-info:focus, .btn-info:active, .btn-info.active, .open .dropdown-toggle.btn-info{background-color: #ffffff;color: #217e9b;border-color: #217e9b;}.btn-tools,.btn-tools:visited,a.btn-tools,a.btn-tools:visited{background-color: #747474;color: #ffffff;border-color: #636363;}.btn-tools:hover, .btn-tools:focus, .btn-tools:active, .btn-tools.active, .open .dropdown-toggle.btn-tools{background-color: #636363;color: #ffffff;border-color: #636363;}.btn-warning,.btn-warning:visited,a.btn-warning,a.btn-warning:visited{background-color: #f4d03f;color: #000000;border-color: #999999;}.btn-warning:hover, .btn-warning:focus, .btn-warning:active, .btn-warning.active, .open .dropdown-toggle.btn-warning{background-color: #ffffff;color: #8d6708;border-color: #8d6708;}.label-warning{background-color: #f4d03f;color: #000000;}.btn-danger,.btn-danger:visited,a.btn-danger,a.btn-danger:visited{background-color: #D50000;color: #ffffff;border-color: #999999;}.btn-danger:hover, .btn-danger:focus, .btn-danger:active, .btn-danger.active, .open .dropdown-toggle.btn-danger{background-color: #ffffff;color: #D50000;border-color: #D50000;}.label-danger{background-color: #D50000;color: #ffffff;}.btn-editions,.btn-editions:visited{background-color: #f8f9fa;color: #212529;border-color: #999999;}.btn-editions:hover, .btn-editions:focus, .btn-editions:active, .btn-editions.active{background-color: #ffffff;color: #1b6ec2;border-color: #1b6ec2;}.badge{background-color: #666666;color: #ffffff;}#webMenuNavBar{background-color: #0a7589;margin-bottom: 2px;color: #ffffff;.navbar-nav > li > a, .navbar-nav > li > a:visited {color: #ffffff;}}.dropdown-menu{background-color: white;color: #6B6B6B;}.result-label{color: #44484a}.result-value{color: #6B6B6B}.search_tools{background-color: #f5f5f5;color: #6B6B6B;}</style>',
  '#ffffff',1,'#0a7589',1,'#ffffff',1,
  '#ffffff',1,'#6B6B6B',1,
  '#de9d03',1,'#303030',1,
  '#de1f0b',1,'#000000',1,
  '#d7dce3',1,'#0087AB',1,
  '#ffffff',1,'#0087AB',1,
  '#0087AB',1,'#ffffff',1,
  '#0087AB',1,'#f1f1f1',1,
  '#265a87',1,-1,
  0,0,1,'floating','','cover','no-repeat','Default'
);\n");
fwrite($fhnd, "UPDATE themes set headerBackgroundColor = '#ffffff' where id = 1;\n");
fwrite($fhnd, "UPDATE themes set browseCategoryPanelColor = '#ffffff' where id = 1;\n");
fwrite($fhnd, "UPDATE themes set closedPanelBackgroundColor = '#ffffff' where id = 1;\n");
fwrite($fhnd, "UPDATE themes set panelBodyBackgroundColor = '#ffffff' where id = 1;\n");
fwrite($fhnd, "INSERT INTO library_themes (libraryId, themeId, weight) VALUES (1, 1, 1);\n");
fwrite($fhnd, "INSERT INTO user (id, username, password, firstname, lastname,cat_username, cat_password, created, homeLocationId, myLocation1Id, myLocation2Id, displayName, source, email, unique_ils_id) VALUES (1,'nyt_user','nyt_password','New York Times','The New York Times','nyt_user','nyt_password','2019-11-19 01:57:54',1,1,1,'The New York Times','admin', '',''),(2,'aspen_admin','password','Aspen','Administrator','aspen_admin','password','2019-11-19 01:57:54',1,1,1,'A. Administrator','admin', '', '');\n");
fwrite($fhnd, "INSERT INTO user_roles (userId, roleId) VALUES (2,1),(2,2);\n");
/** @noinspection SpellCheckingInspection */
fwrite($fhnd, "INSERT INTO variables VALUES (1,'lastHooplaExport','false'),(2,'validateChecksumsFromDisk','false'),(3,'offline_mode_when_offline_login_allowed','false'),(4,'fullReindexIntervalWarning','86400'),(5,'fullReindexIntervalCritical','129600'),(6,'bypass_export_validation','0'),(7,'last_validatemarcexport_time',NULL),(8,'last_export_valid','1'),(9,'record_grouping_running','false'),(10,'last_grouping_time',NULL),(25,'partial_reindex_running','true'),(26,'last_reindex_time',NULL),(27,'lastPartialReindexFinish',NULL),(29,'full_reindex_running','false'),(37,'lastFullReindexFinish',NULL),(44,'num_title_in_unique_sitemap','20000'),(45,'num_titles_in_most_popular_sitemap','20000'),(46,'lastRbdigitalExport',NULL);\n");
fwrite($fhnd, "INSERT INTO web_builder_audience VALUES (1,'Adults'),(4,'Children'),(7,'Everyone'),(5,'Parents'),(6,'Seniors'),(2,'Teens'),(3,'Tweens');\n");
fwrite($fhnd, "INSERT INTO web_builder_category VALUES (10,'Arts and Music'),(1,'eBooks and Audiobooks'),(9,'Homework Help'),(2,'Languages and Culture'),(11,'Library Documents and Policies'),(3,'Lifelong Learning'),(8,'Local History'),(4,'Newspapers and Magazines'),(5,'Reading Recommendations'),(6,'Reference and Research'),(7,'Video Streaming');\n");
fwrite($fhnd, "INSERT INTO website_facet_groups (id, name) VALUES (1, 'default');\n");
fwrite($fhnd, "INSERT INTO website_facets VALUES (1,1, 'Site Name', 'Site Names', 'website_name', 1, 5, 'num_results', 1, 1, 1, 1, 1),(2,1, 'Website Type', 'Website Types', 'search_category', 2, 5, 'num_results', 1, 1, 1, 1, 1),(3,1, 'Audience', 'Audiences', 'audience_facet', 3, 5, 'num_results', 1, 1, 1, 1, 1),(4,1, 'Category', 'Categories', 'category_facet', 4, 5, 'num_results', 1, 1, 1, 1, 1);\n");
fwrite($fhnd, "INSERT INTO events_facet_groups (id, name) VALUES (1, 'default');\n");
fwrite($fhnd, "INSERT INTO events_facet VALUES (1,1, 'Age Group/Audience', 'Age Groups/Audiences', 'age_group_facet', 1, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(2,1, 'Branch', 'Branches', 'branch', 2, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(3,1, 'Room', 'Rooms', 'room', 3, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(4,1, 'Event Type', 'Event Types', 'event_type', 4, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(5,1, 'Program Type', 'Program Types', 'program_type_facet', 5, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(6,1, 'Registration Required?', 'Registration Required?', 'registration_required', 6, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(7,1, 'Category', 'Categories', 'internal_category', 7, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(8,1, 'Reservation State', 'Reservation State', 'reservation_state', 8, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1),(9,1, 'Event State', 'Event State', 'event_state', 9, 5, 0, 'num_results', 0, 1, 1, 1, 1, 0, 1, 1);\n");
fwrite($fhnd, "INSERT INTO open_archives_facet_groups (id, name) VALUES (1, 'default');\n");
fwrite($fhnd, "INSERT INTO open_archives_facets VALUES (1,1, 'Collection', 'Collections', 'collection_name', 1, 5, 'num_results', 1, 1, 1, 1, 1),(2,1, 'Creator', 'Creators', 'creator_facet', 2, 5, 'num_results', 1, 1, 1, 1, 1),(3,1, 'Contributor', 'Contributors', 'contributor_facet', 3, 5, 'num_results', 1, 1, 1, 1, 1),(4,1, 'Type', 'Types', 'type', 4, 5, 'num_results', 1, 1, 1, 1, 1),(5,1, 'Subject', 'Subjects', 'subject_facet', 5, 5, 'num_results', 1, 1, 1, 1, 1),(6,1, 'Publisher', 'Publishers', 'publisher_facet', 6, 5, 'num_results', 1, 1, 1, 1, 1),(7,1, 'Source', 'Sources', 'source', 7, 5, 'num_results', 1, 1, 1, 1, 1);\n");
fwrite($fhnd, "INSERT INTO grouped_work_format_sort_group (id, name) VALUES (1, 'Default Format Sorting');\n");
fclose($fhnd);

//TODO: Fix which modules are enabled by default
//TODO: Make sure the permissions are all correct


function exec_advanced($command, $log) {
	if ($log) {
		console_log($command, 'RUNNING: ');
	}
	$result = exec($command);
	if ($log) {
		console_log($result, 'RESULT: ');
	}
}
function console_log($message, $prefix = '') {
	$STDERR = fopen("php://stderr", "w");
	fwrite($STDERR, $prefix.$message."\n");
	fclose($STDERR);
}