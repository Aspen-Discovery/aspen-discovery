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

	function canDelete() {
		return UserAccount::userHasPermission([
			'Administer All System Messages',
			'Administer Library System Messages',
		]);
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new SystemMessage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingMessages = true;
		if (!UserAccount::userHasPermission('Administer All System Messages')) {
			$librarySystemMessage = new SystemMessageLibrary();
			$library = Library::getPatronHomeLibrary(UserAccount::getActiveUserObj());
			if ($library != null) {
				$librarySystemMessage->libraryId = $library->libraryId;
				$systemMessagesForLibrary = [];
				$librarySystemMessage->find();
				while ($librarySystemMessage->fetch()) {
					$systemMessagesForLibrary[] = $librarySystemMessage->systemMessageId;
				}
				if (count($systemMessagesForLibrary) > 0) {
					$object->whereAddIn('id', $systemMessagesForLibrary, false);
				} else {
					$userHasExistingMessages = false;
				}
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
		return 'https://help.aspendiscovery.org/help/admin/systemmessages';
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

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer All System Messages',
			'Administer Library System Messages',
		]);
	}
}