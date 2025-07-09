<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Theming/Theme.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';

class Admin_CollectionSpotlightLists extends ObjectEditor {

	function getObjectType(): string {
		return 'CollectionSpotlightList';
	}

	function getToolName(): string {
		return 'CollectionSpotlightLists';
	}

	function getPageTitle(): string {
		return 'Collection Spotlight Lists';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new CollectionSpotlightList();
		if (!UserAccount::userHasPermission('Administer All Collection Spotlights')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$object->whereAdd("collectionSpotlightId IN (SELECT id FROM collection_spotlights WHERE libraryId = {$homeLibrary->libraryId} OR libraryId = -1)");
		}
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$list = [];
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'weight asc';
	}

	function getObjectStructure($context = ''): array {
		return CollectionSpotlightList::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/promote/spotlights';
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.Admin.updateBrowseSearchForSource();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/CollectionSpotlights', 'Collection Spotlights');
		$breadcrumbs[] = new Breadcrumb('', 'Collection Spotlight Lists');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	function canAddNew(): bool {
		// Collection Spotlight Lists are an extension of Collection Spotlights,
		// which should only be added from search results.
		return false;
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All Collection Spotlights',
			'Administer Library Collection Spotlights',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All Collection Spotlights',
		]);
	}

	function getAdditionalObjectActions($existingObject): array {
		$actions = parent::getAdditionalObjectActions($existingObject);
		/** @var CollectionSpotlightList $existingObject */
		$parentSpotlightId = $existingObject->collectionSpotlightId;
		$actions[] = [
			'url' => '/Admin/CollectionSpotlights?objectAction=edit&id=' . $parentSpotlightId,
			'text' => 'Edit Spotlight',
			'onclick' => '',
			'target' => '',
		];
		return $actions;
	}

	public function getSortableFields($structure): array {
		$fields = parent::getSortableFields($structure);
		foreach ($fields as $label => $field) {
			if (!empty($field['hideInLists'])) {
				unset($fields[$label]);
			}
		}
		return $fields;
	}

	public function getFilterFields($structure): array {
		$fields = parent::getFilterFields($structure);
		foreach ($fields as $prop => $field) {
			if (!empty($field['hideInLists'])) {
				unset($fields[$prop]);
			}
		}
		return $fields;
	}
}