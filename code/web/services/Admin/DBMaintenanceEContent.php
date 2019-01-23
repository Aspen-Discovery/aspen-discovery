<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class DBMaintenanceEContent extends Admin_Admin {
	function launch() 	{
		global $configArray;
		global $interface;

		mysql_select_db($configArray['Database']['database_econtent_dbname']);

		//Create updates table if one doesn't exist already
		$this->createUpdatesTable();

		$availableUpdates = $this->getSQLUpdates();

		if (isset($_REQUEST['submit'])){
			$interface->assign('showStatus', true);

			//Process the updates
			foreach ($availableUpdates as $key => $update){
				if (isset($_REQUEST["selected"][$key])){
					$sqlStatements = $update['sql'];
					$updateOk = true;
					foreach ($sqlStatements as $sql){
						//Give enough time for long queries to run
						set_time_limit(120);
						if (method_exists($this, $sql)){
							$update['status'] = $this->$sql();
						}else{
							$result = mysql_query($sql);
							if ($result == 0 || $result == false){
								if (isset($update['continueOnError']) && $update['continueOnError']){
									if (!isset($update['status'])) $update['status'] = '';
									$update['status'] .= 'Warning: ' . mysql_error() . "<br/>";
								}else{
									$update['status'] = 'Update failed ' . mysql_error();
									$updateOk = false;
									break;
								}
							}else{
								if (!isset($update['status'])){
									$update['status'] = 'Update succeeded';
								}
							}

						}
					}
					if ($updateOk){
						$this->markUpdateAsRun($key);
					}
					$availableUpdates[$key] = $update;
				}
			}
		}

		//Check to see which updates have already been performed.
		$availableUpdates = $this->checkWhichUpdatesHaveRun($availableUpdates);

		$interface->assign('sqlUpdates', $availableUpdates);

		$this->display('dbMaintenance.tpl', 'Database Maintenance - EContent');

	}

	private function getSQLUpdates() {
		global $configArray;
		return array(
			'overdrive_api_data' => array(
				'title' => 'OverDrive API Data',
				'description' => 'Build tables to store data loaded fromthe OverDrive API so the reindex process can use cached data and so we can add additional logic for lastupdate time, etc.',
				'sql' => array(
					"CREATE TABLE IF NOT EXISTS overdrive_api_products (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						overdriveId VARCHAR(36) NOT NULL,
						mediaType  VARCHAR(50) NOT NULL,
						title VARCHAR(512) NOT NULL,
						series VARCHAR(215),
						primaryCreatorRole VARCHAR(50),
						primaryCreatorName VARCHAR(215),
						cover VARCHAR(215),
						dateAdded INT(11),
						dateUpdated INT(11),
						lastMetadataCheck INT(11),
						lastMetadataChange INT(11),
	          lastAvailabilityCheck INT(11),
						lastAvailabilityChange INT(11),
						deleted TINYINT(1) DEFAULT 0,
						dateDeleted INT(11) DEFAULT NULL,
						UNIQUE(overdriveId),
						INDEX(dateUpdated),
						INDEX(lastMetadataCheck),
						INDEX(lastAvailabilityCheck),
	                    INDEX(deleted)
					)" ,
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_formats (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						productId INT,
						textId VARCHAR(25),
						numericId INT,
						name VARCHAR(512),
						fileName  VARCHAR(215),
						fileSize INT,
						partCount TINYINT,
	          sampleSource_1 VARCHAR(215),
	          sampleUrl_1 VARCHAR(215),
	          sampleSource_2 VARCHAR(215),
	          sampleUrl_2 VARCHAR(215),
						INDEX(productId),
						INDEX(numericId),
						UNIQUE(productId, textId)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_metadata (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						productId INT,
						checksum BIGINT,
						sortTitle VARCHAR(512),
						publisher VARCHAR(215),
						publishDate INT(11),
						isPublicDomain TINYINT(1),
						isPublicPerformanceAllowed TINYINT(1),
						shortDescription TEXT,
						fullDescription TEXT,
						starRating FLOAT,
						popularity INT,
						UNIQUE(productId)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_creators (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						productId INT,
						role VARCHAR(50),
						name VARCHAR(215),
						fileAs VARCHAR(215),
						INDEX (productId)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_identifiers (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						productId INT,
						type VARCHAR(50),
						value VARCHAR(75),
						INDEX (productId),
	                    INDEX (type)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_languages (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						code VARCHAR(10),
						name VARCHAR(50),
						INDEX (code)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_languages_ref (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						productId INT,
						languageId INT,
						UNIQUE (productId, languageId)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_subjects (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						name VARCHAR(512),
						index(name)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_subjects_ref (
						productId INT,
						subjectId INT,
						UNIQUE (productId, subjectId)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_api_product_availability (
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						productId INT,
						libraryId INT,
						available TINYINT(1),
						copiesOwned INT,
						copiesAvailable INT,
						numberOfHolds INT,
						INDEX (productId),
						INDEX (libraryId),
						UNIQUE(productId, libraryId)
					)",
					"CREATE TABLE IF NOT EXISTS overdrive_extract_log(
						`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
						`startTime` INT(11),
						`endTime` INT(11),
						`lastUpdate` INT(11),
						numProducts INT(11) DEFAULT 0,
						numErrors INT(11) DEFAULT 0,
						numAdded INT(11) DEFAULT 0,
						numDeleted INT(11) DEFAULT 0,
	                    numUpdated INT(11) DEFAULT 0,
	                    numSkipped INT(11) DEFAULT 0,
						numAvailabilityChanges INT(11) DEFAULT 0,
						numMetadataChanges INT(11) DEFAULT 0,
						`notes` TEXT
					)",
				)
			),

			'overdrive_api_data_update_1' => array(
				'title' => 'OverDrive API Data Update 1',
				'description' => 'Update MetaData tables to store thumbnail, cover, and raw metadata.  Also update product to store raw metadata',
				'sql' => array(
					"ALTER TABLE overdrive_api_products ADD COLUMN rawData MEDIUMTEXT",
					"ALTER TABLE overdrive_api_product_metadata ADD COLUMN rawData MEDIUMTEXT",
					"ALTER TABLE overdrive_api_product_metadata ADD COLUMN thumbnail VARCHAR(255)",
					"ALTER TABLE overdrive_api_product_metadata ADD COLUMN cover VARCHAR(255)",
				),
			),

			'overdrive_api_data_update_2' => array(
				'title' => 'OverDrive API Data Update 2',
				'description' => 'Update Product table to add subtitle',
				'sql' => array(
					"ALTER TABLE overdrive_api_products ADD COLUMN subtitle VARCHAR(255)",
				),
			),

				'overdrive_api_data_availability_type' => array(
						'title' => 'Add availability type to OverDrive API',
						'description' => 'Update Availability table to add availability type',
						'sql' => array(
								"ALTER TABLE overdrive_api_product_availability ADD COLUMN availabilityType VARCHAR(35) DEFAULT 'Normal'",
						),
				),

				'overdrive_api_data_metadata_isOwnedByCollections' => array(
						'title' => 'Add isOwnedByCollections to OverDrive Metadata API',
						'description' => 'Update isOwnedByCollections table to add metadata table',
						'sql' => array(
								"ALTER TABLE overdrive_api_product_metadata ADD COLUMN isOwnedByCollections TINYINT(1) DEFAULT '1'",
						),
				),

			'overdrive_api_data_needsUpdate' => array(
					'title' => 'Add needsUpdate to OverDrive Product API',
					'description' => 'Update overdrive_api_product table to add needsUpdate to determine if the record should be reloaded from the API',
					'sql' => array(
							"ALTER TABLE overdrive_api_products ADD COLUMN needsUpdate TINYINT(1) DEFAULT '0'",
					),
			),

				'overdrive_api_data_crossRefId' => array(
						'title' => 'Add crossRefId to OverDrive Product API',
						'description' => 'Update overdrive_api_product table to add crossRefId to allow quering of product data ',
						'sql' => array(
								"ALTER TABLE overdrive_api_products ADD COLUMN crossRefId INT(11) DEFAULT '0'",
						),
				),

			'utf8_update' => array(
				'title' => 'Update to UTF-8',
				'description' => 'Update database to use UTF-8 encoding',
				'dependencies' => array(),
				'sql' => array(
					"ALTER DATABASE " . $configArray['Database']['database_econtent_dbname'] . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;",
					"ALTER TABLE db_update CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;",
				),
			),

		);
	}

	private function checkWhichUpdatesHaveRun($availableUpdates){
		foreach ($availableUpdates as $key=>$update){
			$update['alreadyRun'] = false;
			$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($key) . "'");
			$numRows = mysql_num_rows($result);
			if ($numRows != false){
				$update['alreadyRun'] = true;
			}
			$availableUpdates[$key] = $update;
		}
		return $availableUpdates;
	}

	private function markUpdateAsRun($update_key){
		$result = mysql_query("SELECT * from db_update where update_key = '" . mysql_escape_string($update_key) . "'");
		if (mysql_num_rows($result) != false){
			//Update the existing value
			mysql_query("UPDATE db_update SET date_run = CURRENT_TIMESTAMP WHERE update_key = '" . mysql_escape_string($update_key) . "'");
		}else{
			mysql_query("INSERT INTO db_update (update_key) VALUES ('" . mysql_escape_string($update_key) . "')");
		}
	}

	function getAllowableRoles(){
		return array('userAdmin', 'opacAdmin');
	}

	private function createUpdatesTable(){
		//Check to see if the updates table exists
		$result = mysql_query("SHOW TABLES");
		$tableFound = false;
		if ($result){
			while ($row = mysql_fetch_array($result, MYSQL_NUM)){
				if ($row[0] == 'db_update'){
					$tableFound = true;
					break;
				}
			}
		}
		if (!$tableFound){
			//Create the table to mark which updates have been run.
			mysql_query("CREATE TABLE db_update (" .
										"update_key VARCHAR( 100 ) NOT NULL PRIMARY KEY ," .
										"date_run TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" .
										") ENGINE = InnoDB");
		}
	}
}