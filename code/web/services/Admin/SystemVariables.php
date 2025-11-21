<?php

use JetBrains\PhpStorm\NoReturn;

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_SystemVariables extends ObjectEditor {

	function getObjectType(): string {
		return 'SystemVariables';
	}

	function getToolName(): string {
		return 'SystemVariables';
	}

	function getPageTitle(): string {
		return 'System Variables';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$variableList = [];

		$variable = new SystemVariables();
		$variable->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$variable->find();
		while ($variable->fetch()) {
			$variableList[$variable->id] = clone $variable;
		}
		return $variableList;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function canSort(): bool {
		return false;
	}

	function getObjectStructure($context = ''): array {
		return SystemVariables::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'name';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() : bool {
		return $this->getNumObjects() == 0;
	}

	function canDelete() : bool {
		return false;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#system_admin', 'System Administration');
		$breadcrumbs[] = new Breadcrumb('/Admin/SystemVariables', 'System Variables');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer System Variables');
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$objectActions = [];
		if ($existingObject instanceof SystemVariables) {
			$objectActions[] = [
				'text' => 'Clear Cached Values',
				'url' => '/Admin/SystemVariables?objectAction=clearCachedValues',
			];
		}
		return $objectActions;
	}

	/** @noinspection PhpUnused */
	#[NoReturn]
	function clearCachedValues(): void {
		require_once ROOT_DIR . '/sys/MemoryCache/CachedValue.php';
		$user = UserAccount::getActiveUserObj();
		$success = CachedValue::clearAllCachedValues();

		if ($user != null) {
			if ($success) {
				$user->updateMessage = translate([
					'text' => 'Cached values were cleared successfully.',
					'isAdminFacing' => true,
				]);
				$user->updateMessageIsError = 0;
			} else {
				$user->updateMessage = translate([
					'text' => 'Cached values could not be cleared.',
					'isAdminFacing' => true,
				]);
				$user->updateMessageIsError = 1;
			}
			$user->update();
		}

		header('Location: /Admin/SystemVariables');
		exit();
	}
}
