<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/InterLibraryLoan/HoldGroup.php';

class InterLibraryLoan_HoldGroups extends ObjectEditor {
	function getObjectType(): string {
		return 'HoldGroup';
	}

	function getToolName(): string {
		return 'HoldGroups';
	}

	function getModule(): string {
		return 'InterLibraryLoan';
	}

	function getPageTitle(): string {
		return 'Hold Groups';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new HoldGroup();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function getObjectStructure($context = ''): array {
		return HoldGroup::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ill_integration', 'Interlibrary Loan');
		$breadcrumbs[] = new Breadcrumb('/InterLibraryLoan/HoldGroups', 'Hold Groups');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ill_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Hold Groups');
	}

	function customListActions() : array {
		$actions = [];
		$symphonyActive = false;
		foreach (UserAccount::getAccountProfiles() as $accountProfileInfo) {
			/** @var AccountProfile $accountProfile */
			$accountProfile = $accountProfileInfo['accountProfile'];
			if ($accountProfile->ils == 'symphony') {
				$symphonyActive = true;
			}
		}
		if ($symphonyActive) {
			$actions[] = [
				'label' => 'Update From Symphony',
				'action' => 'loadHoldGroupsFromSymphony',
			];
		}
		return $actions;
	}

	/** @noinspection PhpUnused */
	function loadHoldGroupsFromSymphony() : void {
		global $library;
		$user = UserAccount::getActiveUserObj();
		$accountProfile = $library->getAccountProfile();
		$catalogDriverName = trim($accountProfile->driver);
		$catalogDriver = null;
		if (!empty($catalogDriverName)) {
			$catalogDriver = CatalogFactory::getCatalogConnectionInstance($catalogDriverName, $accountProfile);
		}
		if ($catalogDriver->driver instanceof SirsiDynixROA) {
			$result = $catalogDriver->driver->loadHoldGroups();
			$user->__set('updateMessage', $result['message']);
			$user->__set('updateMessageIsError', !$result['success']);
		}else{
			$user->__set('updateMessage', translate(['text'=>'This instance is not connected to Symphony, cannot load hold groups.', 'isAdminFacing' => true]));
		}
		$user->update();
		header("Location: /InterLibraryLoan/HoldGroups");
	}
}
