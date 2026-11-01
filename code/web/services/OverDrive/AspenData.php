<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class OverDrive_AspenData extends Admin_Admin {
	function launch() : void {
		global $interface;
		$canForceReindex = UserAccount::userHasPermission('Force Reindexing of Records');
		$interface->assign('canForceReindex', $canForceReindex);
		if ($canForceReindex && !empty($_POST['recordsToReindex'])) {
			$this->queueRecordsToReindex($_POST['recordsToReindex']);
		}
		if (!empty($_REQUEST['overDriveId'])) {
			$interface->assign('overDriveId', $_REQUEST['overDriveId']);
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';
			$overDriveProduct = new OverDriveAPIProduct();
			$overDriveProduct->overdriveId = $_REQUEST['overDriveId'];
			$errors = '';
			if ($overDriveProduct->find(true)) {
				$interface->assign('overDriveProduct', $overDriveProduct);

				require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductMetaData.php';
				$overDriveMetadata = new OverDriveAPIProductMetaData();
				$overDriveMetadata->productId = $overDriveProduct->id;
				if ($overDriveMetadata->find(true)) {
					$interface->assign('overDriveMetadata', $overDriveMetadata);
				} else {
					$errors = 'Could not find metadata for the product';
				}

				require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProductAvailability.php';
				$overDriveAvailabilities = [];
				$overDriveAvailability = new OverDriveAPIProductAvailability();
				$overDriveAvailability->productId = $overDriveProduct->id;
				$overDriveAvailability->find();
				while ($overDriveAvailability->fetch()) {
					$overDriveAvailabilities[] = clone $overDriveAvailability;
				}
				$interface->assign('overDriveAvailabilities', $overDriveAvailabilities);
			} else {
				$errors = 'Could not find a product with that identifier';
			}
			$interface->assign('errors', $errors);

			//Get data about what is in the Aspen Database for indexing
			global $aspen_db;
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
			require_once ROOT_DIR . '/sys/Grouping/GroupedWorkVariation.php';
			$groupedWorkRecord = new GroupedWorkRecord();
			$escapedId = $groupedWorkRecord->escape($_REQUEST['overDriveId'] . '%');
			$groupedWorkRecord->whereAdd("recordIdentifier LIKE $escapedId" );
			$groupedWorkRecord->find();
			//Record Information
			$recordResult = $aspen_db->query("SELECT grouped_work_records.id, groupedWorkId, source, subSource, format, formatCategory, edition, publisher, placeOfPublication, publicationDate, language FROM `grouped_work_records` 
				LEFT join indexed_record_source on sourceId = indexed_record_source.id LEFT join indexed_format on formatId = indexed_format.id LEFT join indexed_format_category on formatCategoryId = indexed_format_category.id LEFT join indexed_edition on editionId = indexed_edition.id LEFT join indexed_publisher on publisherId = indexed_publisher.id LEFT join indexed_place_of_publication on placeOfPublicationId = indexed_place_of_publication.id LEFT join indexed_publication_date on publicationDateId = indexed_publication_date.id LEFT join indexed_physical_description on physicalDescriptionId = indexed_physical_description.id LEFT join indexed_language on languageId = indexed_language.id where recordIdentifier like ($escapedId); ", PDO::FETCH_ASSOC);
			$recordInfo = $recordResult->fetchAll();
			$interface->assign('aspenRecords', $recordInfo);
			//Grab the grouped work id
			$groupedWorkId = $recordInfo[0]['groupedWorkId'];
			$interface->assign('groupedWorkId', $groupedWorkId);
			$groupedWork = new GroupedWork();
			$groupedWork->id = $groupedWorkId;
			if ($groupedWork->find(true)) {
				$interface->assign('groupedWork', $groupedWork);
			}

			//Variation Information
			$variationResult = $aspen_db->query("SELECT grouped_work_variation.id, language, eContentSource, format, formatCategory  FROM `grouped_work_variation` 
				LEFT JOIN indexed_language on primaryLanguageId = indexed_language.id 
				LEFT JOIN indexed_econtent_source on eContentSourceId = indexed_econtent_source.id 
				LEFT JOIN indexed_format on formatId = indexed_format.id 
				LEFT JOIN indexed_format_category on formatCategoryId = indexed_format_category.id
				where groupedWorkId = $groupedWorkId;", PDO::FETCH_ASSOC);
			$variationInfo = $variationResult->fetchAll();
			$interface->assign('aspenVariations', $variationInfo);

			//Item Information
			$allRecordIds = [];
			foreach ($recordInfo as $record) {
				$allRecordIds[] = $record['id'];
			}
			$allRecordIdsAsString = implode(',', $allRecordIds);
			$itemResult = $aspen_db->query("SELECT grouped_work_record_items.id, groupedWorkRecordId, groupedWorkVariationId, itemId, shelfLocation, callNumber, numCopies, isOrderItem, status, available, holdable, locationOwnedScopes, libraryOwnedScopes, recordIncludedScopes FROM `grouped_work_record_items` 
				LEFT JOIN indexed_shelf_location ON shelfLocationId = indexed_shelf_location.id 
				LEFT JOIN indexed_call_number ON callNumberId = indexed_call_number.id 
				LEFT JOIN indexed_status ON statusId = indexed_status.id 
				where groupedWorkRecordId in ($allRecordIdsAsString); ");
			$itemInfo = $itemResult->fetchAll();
			$interface->assign('aspenItems', $itemInfo);
		} else {
			$interface->assign('overDriveId', '');
		}

		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();

		$interface->assign('readerName', $readerName);

		$this->display('overdriveAspenData.tpl', 'OverDrive Aspen Data');
	}

	function getBreadcrumbs(): array {
		$readerName = new OverDriveDriver();
		$readerName = $readerName->getReaderName();
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#overdrive', $readerName);
		$breadcrumbs[] = new Breadcrumb('/OverDrive/AspenData', 'Aspen Information');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'overdrive';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['View OverDrive Test Interface', 'Force Reindexing of Records']);
	}

	private function queueRecordsToReindex(string $recordToReindex) : void {
		global $interface;
		require_once ROOT_DIR . '/sys/Indexing/RecordIdentifiersToReload.php';
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveAPIProduct.php';

		$overDriveIds = preg_split('/[\s,]+/', $recordToReindex, -1, PREG_SPLIT_NO_EMPTY);
		$overDriveIds = array_unique(array_map(fn($id) => strtolower(trim($id)), $overDriveIds));

		$titleIdsNotFound = [];
		$alreadyInQueueIds = [];
		$numQueued = 0;

		foreach ($overDriveIds as $overDriveId) {
			if (is_numeric($overDriveId)) {
				//This is a crossRefId, look up the reserve id for it
				$overDriveProduct = new OverDriveAPIProduct();
				$overDriveProduct->crossRefId = $overDriveId;
				if (!$overDriveProduct->find(true)) {
					$titleIdsNotFound[] = $overDriveId;
					continue;
				}
				$overDriveId = strtolower($overDriveProduct->overdriveId);
			}
			$recordToReload = new RecordIdentifiersToReload();
			$recordToReload->type = 'overdrive';
			$recordToReload->identifier = $overDriveId;
			$recordToReload->processed = 0;
			if ($recordToReload->count() > 0) {
				$alreadyInQueueIds[] = $overDriveId;
				continue;
			}
			$recordToReload->insert();
			$numQueued++;
		}
		$interface->assign('titleIdsNotFound', $titleIdsNotFound);
		$interface->assign('alreadyInQueueIds', $alreadyInQueueIds);
		$interface->assign('numQueued', $numQueued);
	}
}