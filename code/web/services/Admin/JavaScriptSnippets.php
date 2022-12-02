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

	function canDelete() {
		return UserAccount::userHasPermission([
			'Administer All JavaScript Snippets',
			'Administer Library JavaScript Snippets',
		]);
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new JavaScriptSnippet();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingSnippets = true;
		if (!UserAccount::userHasPermission('Administer All JavaScript Snippets')) {
			$libraryJavaScriptSnippet = new JavaScriptSnippetLibrary();
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			if ($library != null) {
				$libraryJavaScriptSnippet->libraryId = $library->libraryId;
				$snippetsForLibrary = [];
				$libraryJavaScriptSnippet->find();
				while ($libraryJavaScriptSnippet->fetch()) {
					$snippetsForLibrary[] = $libraryJavaScriptSnippet->javascriptSnippetId;
				}
				if (count($snippetsForLibrary) > 0) {
					$object->whereAddIn('id', $snippetsForLibrary, false);
				} else {
					$userHasExistingSnippets = false;
				}
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

	function getObjectStructure(): array {
		return JavaScriptSnippet::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return '';
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

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All JavaScript Snippets',
			'Administer Library JavaScript Snippets',
		]);
	}
}