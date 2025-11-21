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

	function getAllObjects(int $page, int $recordsPerPage): array {
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

	/** @noinspection PhpUnused */
	function recalculateHistoricCostSavings() : void {
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$additionalParameters = [];
		if (isset($_REQUEST['format'])) {
			$additionalParameters[] = $_REQUEST['format'];
		}
		$result = SystemUtils::startBackgroundProcess("recalculateHistoricCostSavings", $additionalParameters);

		$activeUser = UserAccount::getActiveUserObj();
		if ($result['success']) {
			$activeUser->__set('updateMessage', translate(['text'=>'Successfully started background process %1% to recalculate historic cost savings.', 1=>$result['backgroundProcessId'], 'isAdminFacing' => true]));
		}else{
			$activeUser->__set('updateMessage', translate(['text'=>'Could not start background process to recalculate historic cost savings.', 'isAdminFacing' => true]) . "<br/> " . $result['message']);
		}
		$activeUser->update();
		header("Location: /Admin/ReplacementCosts");
	}

	/** @noinspection PhpUnused */
	function recalculateZeroCostSavings() : void {
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$additionalParameters = [];
		if (isset($_REQUEST['format'])) {
			$additionalParameters[] = $_REQUEST['format'];
		}
		$result = SystemUtils::startBackgroundProcess("recalculateZeroCostSavings", $additionalParameters);

		$activeUser = UserAccount::getActiveUserObj();
		if ($result['success']) {
			$activeUser->__set('updateMessage', translate(['text'=>'Successfully started background process %1% to recalculate zero cost savings.', 1=>$result['backgroundProcessId'], 'isAdminFacing' => true]));
		}else{
			$activeUser->__set('updateMessage', translate(['text'=>'Could not start background process to recalculate zero cost savings.', 'isAdminFacing' => true]) . "<br/> " . $result['message']);
		}
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