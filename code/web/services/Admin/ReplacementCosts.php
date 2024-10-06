<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/ReplacementCost.php';

class Admin_ReplacementCosts extends ObjectEditor {

	function getObjectType(): string {
		return 'ReplacementCost';
	}

	function getModule(): string {
		return 'Admin';
	}

	function getToolName(): string {
		return 'ReplacementCosts';
	}

	function getPageTitle(): string {
		return 'Replacement Costs';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new ReplacementCost();

		$this->applyFilters($object);

		$object->orderBy('catalogFormat ASC');
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'catalogFormat asc';
	}

	function canSort(): bool {
		return false;
	}

	function getObjectStructure($context = ''): array {
		return ReplacementCost::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function customListActions() : array {
		return [
			[
				'label' => 'Update Active Formats',
				'action' => 'loadActiveFormats',
			],
			[
				'label' => 'Recalculate Historic Cost Savings',
				'action' => 'recalculateHistoricCostSavings',
				'onclick' => "return confirm('" . translate(['text'=>'Recalculating all costs savings will recalculate all savings so historic price changes will be lost. Proceed?', 'isAdminFacing' => true]). "')",
			],
			[
				'label' => 'Recalculate Zero Cost Savings',
				'action' => 'recalculateZeroCostSavings',
			]
		];
	}

	function recalculateHistoricCostSavings() : void {
		$replacementCosts = ReplacementCost::getReplacementCostsByFormat();

		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryEntry = new ReadingHistoryEntry();
		if (isset($_REQUEST['format'])) {
			$format = $_REQUEST['format'];
			$readingHistoryEntry->format = $format;
		}
		$readingHistoryEntry->find();
		$numUpdated = 0;
		//Recalculate all reading history entries
		while ($readingHistoryEntry->fetch()) {
			$lowerFormat = strtolower($readingHistoryEntry->format);
			if (array_key_exists($lowerFormat, $replacementCosts)) {
				if ($replacementCosts[$lowerFormat] > 0) {
					//Update the costSavings for the reading history entry and update the total cost savings for the user
					$readingHistoryEntryToUpdate = new ReadingHistoryEntry();
					$readingHistoryEntryToUpdate->id = $readingHistoryEntry->id;
					if ($readingHistoryEntryToUpdate->find(true)) {
						$readingHistoryEntryToUpdate->costSavings = $replacementCosts[$lowerFormat];
						$readingHistoryEntryToUpdate->update();
						$numUpdated++;
					}
				}
			}
		}

		//Now get the total cost savings for users that have checked something out in the format
		$readingHistoryEntry = new ReadingHistoryEntry();
		if (isset($_REQUEST['format'])) {
			$format = $_REQUEST['format'];
			$readingHistoryEntry->format = $format;
		}
		$readingHistoryEntry->selectAdd();
		$readingHistoryEntry->selectAdd("DISTINCT userId as userId");

		$numUsersUpdated = 0;
		$readingHistoryEntry->find();
		while ($readingHistoryEntry->fetch()) {
			$userToUpdate = new User();
			$userToUpdate->id = $readingHistoryEntry->userId;
			if ($userToUpdate->find(true)) {
				$tmpReadingHistory = new ReadingHistoryEntry();
				$tmpReadingHistory->userId = $userToUpdate->id;
				$tmpReadingHistory->selectAdd();
				$tmpReadingHistory->selectAdd("SUM(costSavings) as costSavings");
				if ($tmpReadingHistory->costSavings != $userToUpdate->totalCostSavings) {
					if ($tmpReadingHistory->find(true)) {
						$userToUpdate->__set('totalCostSavings', $tmpReadingHistory->costSavings);
					} else {
						$userToUpdate->__set('totalCostSavings', 0);
					}
					$userToUpdate->update();
				}
			}
			$numUsersUpdated++;
		}

		$activeUser = UserAccount::getActiveUserObj();
		$activeUser->__set('updateMessage', translate(['text'=>'Updated %1% historic cost savings for %2% users.', 1=>$numUpdated, 2=>$numUsersUpdated, 'isAdminFacing' => true]));
		$activeUser->update();
		header("Location: /Admin/ReplacementCosts");
	}

	/** @noinspection PhpUnused */
	function recalculateZeroCostSavings() : void {
		$replacementCosts = ReplacementCost::getReplacementCostsByFormat();

		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryEntry = new ReadingHistoryEntry();
		$readingHistoryEntry->costSavings = 0;
		if (isset($_REQUEST['format'])) {
			$format = $_REQUEST['format'];
			$readingHistoryEntry->format = $format;
		}
		$readingHistoryEntry->find();
		$numUpdated = 0;
		while ($readingHistoryEntry->fetch()) {
			$lowerFormat = strtolower($readingHistoryEntry->format);
			if (array_key_exists($lowerFormat, $replacementCosts)) {
				if ($replacementCosts[$lowerFormat] > 0) {
					//Update the costSavings for the reading history entry and update the total cost savings for the user
					$readingHistoryEntryToUpdate = new ReadingHistoryEntry();
					$readingHistoryEntryToUpdate->id = $readingHistoryEntry->id;
					if ($readingHistoryEntryToUpdate->find(true)) {
						$readingHistoryEntryToUpdate->costSavings = $replacementCosts[$lowerFormat];
						$readingHistoryEntryToUpdate->update();
					}

					$userToUpdate = new User();
					$userToUpdate->id = $readingHistoryEntryToUpdate->userId;
					if ($userToUpdate->find(true)) {
						$userToUpdate->totalCostSavings += $replacementCosts[$lowerFormat];
						$userToUpdate->update();
					}
					$numUpdated++;
				}
			}
		}

		$activeUser = UserAccount::getActiveUserObj();
		$activeUser->__set('updateMessage', translate(['text'=>'Updated %1% historic cost savings.', 1=>$numUpdated, 'isAdminFacing' => true]));
		$activeUser->update();
		header("Location: /Admin/ReplacementCosts");
	}

	/** @noinspection PhpUnused */
	function loadActiveFormats() : void {
		ReplacementCost::loadActiveFormats();

		header("Location: /Admin/ReplacementCosts");
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#cataloging', 'Catalog / Grouped Works');
		$breadcrumbs[] = new Breadcrumb('/Admin/ReplacementCosts', 'Replacement Costs');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'cataloging';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Replacement Costs');
	}

	function canAddNew() : bool {
		return false;
	}

	function canCompare() : bool {
		return false;
	}

	function canDelete() : bool {
		return false;
	}

	protected function getDefaultRecordsPerPage() : int {
		return 100;
	}
}