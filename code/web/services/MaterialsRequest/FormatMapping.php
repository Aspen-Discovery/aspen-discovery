<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormatMapping.php';

class MaterialsRequest_FormatMapping extends ObjectEditor {

	function getObjectType(): string {
		return 'MaterialsRequestFormatMapping';
	}

	function getModule(): string {
		return 'MaterialsRequest';
	}

	function getToolName(): string {
		return 'FormatMapping';
	}

	function getPageTitle(): string {
		return 'Materials Request Format Mapping';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new MaterialsRequestFormatMapping();

		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}

		$object->libraryId = $homeLibrary->libraryId;
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
		return 'catalogFormat desc';
	}

	function canSort(): bool {
		return false;
	}

	function getObjectStructure($context = ''): array {
		$structure = MaterialsRequestFormatMapping::getObjectStructure($context);
		unset($structure['libraryId']);
		return $structure;
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function customListActions() : array {
		$objectActions = [];

		$objectActions[] = [
			'label' => 'Update Active Formats',
			'action' => 'loadActiveFormats',
		];

		return $objectActions;
	}

	/** @noinspection PhpUnused */
	function loadActiveFormats() : void {
		$homeLibrary = Library::getPatronHomeLibrary();
		if (is_null($homeLibrary)) {
			//User does not have a home library, this is likely an admin account.  Use the active library
			global $library;
			$homeLibrary = $library;
		}
		MaterialsRequestFormatMapping::loadActiveFormats($homeLibrary->libraryId);

		header("Location: /MaterialsRequest/FormatMapping");
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/ManageRequests', 'Manage Materials Requests');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/FormatMapping', 'Materials Request Format Mapping');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'materials_request';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Materials Requests');
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