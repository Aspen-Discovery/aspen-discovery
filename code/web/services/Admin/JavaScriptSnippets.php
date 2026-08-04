<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/JavaScriptSnippet.php';

class Admin_JavaScriptSnippets extends ObjectEditor {

	function getObjectType(): string {
		return 'JavaScriptSnippet';
	}

	function getToolName(): string {
		return 'JavaScriptSnippets';
	}

	function getPageTitle(): string {
		return 'JavaScript Snippets';
	}

	function canDelete() : bool {
		return UserAccount::userHasPermission([
			'Administer All JavaScript Snippets',
			'Administer Library JavaScript Snippets',
		]);
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new JavaScriptSnippet();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingSnippets = true;
		if (!UserAccount::userHasPermission('Administer All JavaScript Snippets')) {
			$validLibraries = Library::getLibraryList(true);
			$snippetsForLibrary = [];
			foreach ($validLibraries as $libraryId => $displayName) {
				$libraryJavaScriptSnippet = new JavaScriptSnippetLibrary();
				$libraryJavaScriptSnippet->libraryId = $libraryId;
				$libraryJavaScriptSnippet->find();
				while ($libraryJavaScriptSnippet->fetch()) {
					$snippetsForLibrary[] = $libraryJavaScriptSnippet->javascriptSnippetId;
				}
			}
			$validLocations = Location::getLocationList(true);
			foreach ($validLocations as $locationId => $displayName) {
				$locationJavaScriptSnippet = new JavaScriptSnippetLocation();
				$locationJavaScriptSnippet->locationId = $locationId;
				$locationJavaScriptSnippet->find();
				while ($locationJavaScriptSnippet->fetch()) {
					$snippetsForLibrary[] = $locationJavaScriptSnippet->javascriptSnippetId;
				}
			}
			if (count($snippetsForLibrary) > 0) {
				$object->whereAddIn('id', $snippetsForLibrary, false);
			} else {
				$userHasExistingSnippets = false;
			}
		}
		$object->find();
		$list = [];
		if ($userHasExistingSnippets) {
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
		}
		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return JavaScriptSnippet::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/319815796/CSS+JavaScript+and+Regex';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/JavaScriptSnippets', 'JavaScript Snippets');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All JavaScript Snippets',
			'Administer Library JavaScript Snippets',
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All JavaScript Snippets',
		]);
	}

	public function canCopy() : bool {
		return $this->canAddNew();
	}

	public function hasRecordLocking() : bool {
		return true;
	}
}