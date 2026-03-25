<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/Placard.php';

class Admin_Placards extends ObjectEditor {

	function getObjectType(): string {
		return 'Placard';
	}

	function getToolName(): string {
		return 'Placards';
	}

	function getPageTitle(): string {
		return 'Placards';
	}

	function canDelete() : bool {
		return UserAccount::userHasPermission([
			'Administer All Placards',
			'Administer Library Placards',
		]);
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new Placard();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingPlacards = true;
		if (!UserAccount::userHasPermission('Administer All Placards')) {
			$libraries = Library::getLibraryList(true);
			$placardsForLibrary = [];
			foreach ($libraries as $libraryId => $displayName) {
				$libraryPlacard = new PlacardLibrary();
				$libraryPlacard->libraryId = $libraryId;
				$libraryPlacard->find();
				while ($libraryPlacard->fetch()) {
					$placardsForLibrary[] = $libraryPlacard->placardId;
				}

			}
			if (count($placardsForLibrary) > 0) {
				$object->whereAddIn('id', $placardsForLibrary, false);
			} else {
				$userHasExistingPlacards = false;
			}
		}
		$object->find();
		$list = [];
		if ($userHasExistingPlacards) {
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'title asc';
	}

	function getObjectStructure($context = ''): array {
		return Placard::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/273350657/Placards';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/Placards', 'Placards');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.WebBuilder.getSourceValuesForPlacard()';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Placards',
			'Administer Library Placards',
			'Edit Library Placards',
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Placards',
		]);
	}

	function canAddNew() : bool {
		return UserAccount::userHasPermission([
			'Administer All Placards',
			'Administer Library Placards',
		]);
	}

	public function canCopy() : bool {
		return $this->canAddNew();
	}

	public function hasRecordLocking() : bool {
		return true;
	}
}