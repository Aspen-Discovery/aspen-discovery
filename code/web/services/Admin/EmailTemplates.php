<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';

class Admin_EmailTemplates extends ObjectEditor {
	function getObjectType(): string {
		return 'EmailTemplate';
	}

	function getToolName(): string {
		return 'EmailTemplates';
	}

	function getPageTitle(): string {
		return 'Email Templates';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new EmailTemplate();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$userHasExistingTemplates = true;
		if (!UserAccount::userHasPermission('Administer All Email Templates')) {
			$libraries = Library::getLibraryList(true);
			$templatesForLibrary = [];
			foreach ($libraries as $libraryId => $displayName) {
				$libraryEmailTemplate = new LibraryEmailTemplate();
				$libraryEmailTemplate->libraryId = $libraryId;
				$libraryEmailTemplate->find();
				while ($libraryEmailTemplate->fetch()) {
					$templatesForLibrary[] = $libraryEmailTemplate->emailTemplateId;
				}
			}
			if (count($templatesForLibrary) > 0) {
				$object->whereAddIn('id', $templatesForLibrary, false);
			} else {
				$userHasExistingTemplates = false;
			}
		}
		if ($userHasExistingTemplates) {
			$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
			$object->find();
			while ($object->fetch()) {
				$list[$object->id] = clone $object;
			}
		}

		return $list;
	}

    function getInstructions() : string{
        return 'https://help.aspendiscovery.org/email';
    }

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return EmailTemplate::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#email', 'Email');
		$breadcrumbs[] = new Breadcrumb('', 'Email Templates');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'email';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer All Email Templates') || UserAccount::userHasPermission('Administer Library Email Templates');
	}
}