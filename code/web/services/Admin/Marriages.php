<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Genealogy/Marriage.php';

class Admin_Marriages extends ObjectEditor {
	function getObjectType(): string {
		return 'Marriage';
	}

	function getToolName(): string {
		return 'Marriages';
	}

	function getPageTitle(): string {
		return 'Marriages';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new Marriage();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->marriageId] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'marriageDate asc';
	}

	function getObjectStructure($context = ''): array {
		return Marriage::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'marriageId';
	}

	function getIdKeyColumn(): string {
		return 'marriageId';
	}

	function getRedirectLocation(string $objectAction, DataObject $curObject): ?string {
		if ($curObject instanceof Marriage) {
			return '/Person/' . $curObject->personId;
		} else {
			return '/Union/Search?searchSource=genealogy&lookfor=&searchIndex=GenealogyName&submit=Find';
		}
	}

	function showReturnToList(): bool {
		return false;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->activeObject) && $this->activeObject instanceof Marriage) {
			require_once ROOT_DIR . '/sys/Genealogy/Person.php';
			$person = new Person();
			$person->personId = $this->activeObject->personId;
			if ($person->find(true)) {
				$breadcrumbs[] = new Breadcrumb('/Person/' . $person->personId, $person->displayName());
			}
		}
		$breadcrumbs[] = new Breadcrumb('', 'Marriage');
		return $breadcrumbs;
	}

	function display($mainContentTemplate, $pageTitle, $sidebarTemplate = 'Admin/admin-sidebar.tpl', $translateTitle = true) : void {
		parent::display($mainContentTemplate, $pageTitle, '', false);
	}

	function getActiveAdminSection(): string {
		return '';
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer Genealogy']);
	}
}