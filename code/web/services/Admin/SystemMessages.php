<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/SystemMessage.php';

class Admin_SystemMessages extends ObjectEditor {

	function getObjectType(): string {
		return 'SystemMessage';
	}

	function getToolName(): string {
		return 'SystemMessages';
	}

	function getPageTitle(): string {
		return 'System Messages';
	}

	function canDelete() : bool {
		return UserAccount::userHasPermission([
			'Administer All System Messages',
			'Administer Library System Messages',
		]);
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new SystemMessage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingMessages = true;
		if (!UserAccount::userHasPermission('Administer All System Messages')) {
			$libraries = Library::getLibraryList(true);
			$systemMessagesForLibrary = [];
			foreach ($libraries as $libraryId => $displayName) {
				$librarySystemMessage = new SystemMessageLibrary();
				$librarySystemMessage->libraryId = $libraryId;
				$librarySystemMessage->find();
				while ($librarySystemMessage->fetch()) {
					$systemMessagesForLibrary[] = $librarySystemMessage->systemMessageId;
				}
			}
			if (count($systemMessagesForLibrary) > 0) {
				$object->whereAddIn('id', $systemMessagesForLibrary, false);
			} else {
				$userHasExistingMessages = false;
			}
		}
		$list = [];
		if ($userHasExistingMessages) {
			$object->find();
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
		return SystemMessage::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/275185665/System+Messages';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/SystemMessages', 'SystemMessages');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All System Messages',
			'Administer Library System Messages',
		];
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer All System Messages',
		]);
	}

	function canCopy() : bool {
		return $this->canAddNew();
	}

	public function hasRecordLocking() : bool {
		return true;
	}
}