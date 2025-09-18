<?php

/** @noinspection PhpUnused */
function getUpdates25_10_00(): array {
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
		'addOptionsForIndexing896To899AsSeries' => [
			'title' => 'Add Options For Indexing 896 To 899 As Series',
			'description' => 'Add Options For Indexing 896 To 899 As Series',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE indexing_profiles ADD COLUMN index896asSeries TINYINT(1) DEFAULT 1',
				'ALTER TABLE indexing_profiles ADD COLUMN index897asSeries TINYINT(1) DEFAULT 1',
				'ALTER TABLE indexing_profiles ADD COLUMN index898asSeries TINYINT(1) DEFAULT 1',
				'ALTER TABLE indexing_profiles ADD COLUMN index899asSeries TINYINT(1) DEFAULT 1'
			]
		], //addOptionsForIndexing896To899AsSeries

		//katherine - Grove

		//kirstien - Grove

		//kodi - Grove

		// Myranda - Grove

		//Yanjun Li - ByWater

		// Leo Stoyanov - BWS

		//alexander - Open Fifth

		//chloe - Open Fifth


		//Jacob - Open Fifth

		//Pedro - Open Fifth


		//James Staub - Nashville Public Library

		//Lucas Montoya - Theke Solutions

		//other

		'migrate_images_to_storage_manager' => [
			'title' => 'Migrate Images to StorageManager',
			'description' => 'Migrate all user-uploaded images from /files/ to StorageManager locations',
			'continueOnError' => true,
			'sql' => [
				'migrateImagesToStorageManager'
			]
		], //migrate_images_to_storage_manager
	];
}

function migrateImagesToStorageManager(&$update) {
	global $configArray;
	require_once ROOT_DIR . '/sys/Storage/StorageManager.php';
	require_once ROOT_DIR . '/sys/Theming/Theme.php';
	require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';
	require_once ROOT_DIR . '/sys/WebBuilder/StaffMember.php';
	require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
	require_once ROOT_DIR . '/sys/Series/Series.php';
	require_once ROOT_DIR . '/sys/Genealogy/Person.php';
	require_once ROOT_DIR . '/sys/Genealogy/Obituary.php';
	require_once ROOT_DIR . '/sys/CommunityEngagement/Reward.php';
	require_once ROOT_DIR . '/sys/Events/Event.php';
	
	$storageManager = StorageManager::getInstance();
	$migratedCount = 0;
	
	try {
		// 1. Migrate Theme Images (logos, favicons, footerLogos)
		$theme = new Theme();
		$theme->find();
		while ($theme->fetch()) {
			// 1a. Theme Logo
			if (!empty($theme->logoName) && strpos($theme->logoName, 'Theme_') !== 0) {
				if (migrateThemeImage($theme->logoName, $theme->id, 'logo', 'logos', $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Theme_logo_" . $theme->id . "." . pathinfo($theme->logoName, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE themes SET logoName = " . $aspen_db->quote($newFileName) . " WHERE id = " . $theme->id);
					$migratedCount++;
				}
			}
			
			// 1b. Theme Favicon
			if (!empty($theme->favicon) && strpos($theme->favicon, 'Theme_') !== 0) {
				if (migrateThemeImage($theme->favicon, $theme->id, 'favicon', 'favicons', $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Theme_favicon_" . $theme->id . "." . pathinfo($theme->favicon, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE themes SET favicon = " . $aspen_db->quote($newFileName) . " WHERE id = " . $theme->id);
					$migratedCount++;
				}
			}
			
			// 1c. Theme App Logo
			if (!empty($theme->logoApp) && strpos($theme->logoApp, 'Theme_') !== 0) {
				if (migrateThemeImage($theme->logoApp, $theme->id, 'logoApp', 'logos', $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Theme_logoApp_" . $theme->id . "." . pathinfo($theme->logoApp, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE themes SET logoApp = " . $aspen_db->quote($newFileName) . " WHERE id = " . $theme->id);
					$migratedCount++;
				}
			}
			
			// 1d. Theme Footer Logo
			if (!empty($theme->footerLogo) && strpos($theme->footerLogo, 'Theme_') !== 0) {
				if (migrateThemeImage($theme->footerLogo, $theme->id, 'footerLogo', 'backgrounds', $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Theme_footerLogo_" . $theme->id . "." . pathinfo($theme->footerLogo, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE themes SET footerLogo = " . $aspen_db->quote($newFileName) . " WHERE id = " . $theme->id);
					$migratedCount++;
				}
			}
		}
		
		// 2. Migrate Placard Images
		$placard = new Placard();
		$placard->find();
		while ($placard->fetch()) {
			if (!empty($placard->image) && strpos($placard->image, 'Placard_') !== 0) {
				if (migrateImage($placard->image, 'placard', null, $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Placard_" . $placard->id . "_" . $placard->image;
					$aspen_db->query("UPDATE placards SET image = " . $aspen_db->quote($newFileName) . " WHERE id = " . $placard->id);
					$migratedCount++;
				}
			}
		}
		
		// 3. Migrate WebBuilder Staff Photos
		$staffMember = new StaffMember();
		$staffMember->find();
		while ($staffMember->fetch()) {
			if (!empty($staffMember->photo) && strpos($staffMember->photo, 'Staff_') !== 0) {
				if (migrateImage($staffMember->photo, 'web_builder', null, $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Staff_photo_" . $staffMember->id . "." . pathinfo($staffMember->photo, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE staff_members SET photo = " . $aspen_db->quote($newFileName) . " WHERE id = " . $staffMember->id);
					$migratedCount++;
				}
			}
		}
		
		// 4. Migrate WebBuilder Resource Logos
		$webResource = new WebResource();
		$webResource->find();
		while ($webResource->fetch()) {
			if (!empty($webResource->logo) && strpos($webResource->logo, 'WebResource_') !== 0) {
				if (migrateImage($webResource->logo, 'web_builder', null, $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "WebResource_logo_" . $webResource->id . "." . pathinfo($webResource->logo, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE web_builder_resource SET logo = " . $aspen_db->quote($newFileName) . " WHERE id = " . $webResource->id);
					$migratedCount++;
				}
			}
		}
		
		// 5. Migrate Series Cover Images
		$series = new Series();
		$series->find();
		while ($series->fetch()) {
			if (!empty($series->cover) && strpos($series->cover, 'Series_') !== 0) {
				if (migrateImage($series->cover, 'series', null, $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Series_cover_" . $series->id . "." . pathinfo($series->cover, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE series SET cover = " . $aspen_db->quote($newFileName) . " WHERE id = " . $series->id);
					$migratedCount++;
				}
			}
		}
		
		// 6. Migrate Genealogy Person Pictures
		global $aspen_db;
		$personQuery = "SELECT personId, picture FROM person WHERE picture IS NOT NULL AND picture != '' AND picture NOT LIKE 'Person_%'";
		$personResult = $aspen_db->query($personQuery);
		if ($personResult) {
			while ($personRow = $personResult->fetch(PDO::FETCH_ASSOC)) {
				if (migrateImage($personRow['picture'], 'genealogy', 'person', $storageManager, $configArray)) {
					$newFileName = "Person_picture_" . $personRow['personId'] . "." . pathinfo($personRow['picture'], PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE person SET picture = " . $aspen_db->quote($newFileName) . " WHERE personId = " . $personRow['personId']);
					$migratedCount++;
				}
			}
		}
		
		// 7. Migrate Genealogy Obituary Pictures
		$obituary = new Obituary();
		$obituary->find();
		while ($obituary->fetch()) {
			if (!empty($obituary->picture) && strpos($obituary->picture, 'Obituary_') !== 0) {
				if (migrateImage($obituary->picture, 'genealogy', 'obituary', $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Obituary_picture_" . $obituary->obituaryId . "." . pathinfo($obituary->picture, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE obituary SET picture = " . $aspen_db->quote($newFileName) . " WHERE obituaryId = " . $obituary->obituaryId);
					$migratedCount++;
				}
			}
		}
		
		// 8. Migrate Community Engagement Reward Badge Images
		$reward = new Reward();
		$reward->find();
		while ($reward->fetch()) {
			if (!empty($reward->badgeImage) && strpos($reward->badgeImage, 'Reward_') !== 0) {
				if (migrateImage($reward->badgeImage, 'reward', null, $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Reward_badge_" . $reward->id . "." . pathinfo($reward->badgeImage, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE community_engagement_rewards SET badgeImage = " . $aspen_db->quote($newFileName) . " WHERE id = " . $reward->id);
					$migratedCount++;
				}
			}
		}
		
		// 9. Migrate Event Images
		$event = new Event();
		$event->find();
		while ($event->fetch()) {
			if (!empty($event->image) && strpos($event->image, 'Event_') !== 0) {
				if (migrateImage($event->image, 'event', null, $storageManager, $configArray)) {
					global $aspen_db;
					$newFileName = "Event_image_" . $event->id . "." . pathinfo($event->image, PATHINFO_EXTENSION);
					$aspen_db->query("UPDATE events SET image = " . $aspen_db->quote($newFileName) . " WHERE id = " . $event->id);
					$migratedCount++;
				}
			}
		}
		
		// 10. Migrate ALL remaining files to LEGACY (since we can't determine their purpose)
		$originalFiles = glob($configArray['Site']['local'] . "/files/original/*");
		$thumbnailFiles = glob($configArray['Site']['local'] . "/files/thumbnail/*");
		
		foreach (array_merge($originalFiles, $thumbnailFiles) as $filePath) {
			if (is_file($filePath)) {
				$fileName = basename($filePath);
				$isOriginal = strpos($filePath, '/original/') !== false;
				$size = $isOriginal ? 'original' : 'thumbnail';
				
				// All remaining files go to legacy - we can't determine their purpose
				$newPath = $storageManager->getImagePath('legacy', null, $size) . "/" . $fileName;
				
				if (!file_exists($newPath)) {
					if (rename($filePath, $newPath)) {
						$migratedCount++;
					}
				}
			}
		}
		
		$update['status'] = "StorageManager migration completed successfully. Migrated $migratedCount images across all categories and file patterns.";
		$update['success'] = true;
		
	} catch (Exception $e) {
		$update['status'] = 'Failed: ' . $e->getMessage();
		$update['success'] = false;
	}
}

function migrateThemeImage($fileName, $themeId, $type, $subtype, $storageManager, $configArray) {
	$oldPath = $configArray['Site']['local'] . "/files/original/" . $fileName;
	$oldThumbnailPath = $configArray['Site']['local'] . "/files/thumbnail/" . $fileName;
	
	if (!file_exists($oldPath) && !file_exists($oldThumbnailPath)) {
		return false;
	}
	
	$fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
	$newFileName = "Theme_{$type}_{$themeId}.{$fileExtension}";
	$newPath = $storageManager->getImagePath('theme', $subtype, 'original') . "/" . $newFileName;
	$newThumbnailPath = $storageManager->getImagePath('theme', $subtype, 'thumbnail') . "/" . $newFileName;
	
	$moved = false;
	if (file_exists($oldPath) && !file_exists($newPath)) {
		$moved = rename($oldPath, $newPath) || $moved;
	}
	if (file_exists($oldThumbnailPath) && !file_exists($newThumbnailPath)) {
		$moved = rename($oldThumbnailPath, $newThumbnailPath) || $moved;
	}
	
	return $moved;
}

function migrateImage($fileName, $type, $subtype, $storageManager, $configArray) {
	$oldPath = $configArray['Site']['local'] . "/files/original/" . $fileName;
	$oldThumbnailPath = $configArray['Site']['local'] . "/files/thumbnail/" . $fileName;
	
	if (!file_exists($oldPath) && !file_exists($oldThumbnailPath)) {
		return false;
	}
	
	$newPath = $storageManager->getImagePath($type, $subtype, 'original') . "/" . $fileName;
	$newThumbnailPath = $storageManager->getImagePath($type, $subtype, 'thumbnail') . "/" . $fileName;
	
	$moved = false;
	if (file_exists($oldPath) && !file_exists($newPath)) {
		$moved = rename($oldPath, $newPath) || $moved;
	}
	if (file_exists($oldThumbnailPath) && !file_exists($newThumbnailPath)) {
		$moved = rename($oldThumbnailPath, $newThumbnailPath) || $moved;
	}
	
	return $moved;
}
