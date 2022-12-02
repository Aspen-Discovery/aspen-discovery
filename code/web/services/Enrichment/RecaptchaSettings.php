<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';

class Enrichment_RecaptchaSettings extends ObjectEditor {
	function getObjectType(): string {
		return 'RecaptchaSetting';
	}

	function getToolName(): string {
		return 'RecaptchaSettings';
	}

	function getModule(): string {
		return 'Enrichment';
	}

	function getPageTitle(): string {
		return 'reCAPTCHA Settings';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new RecaptchaSetting();
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'id asc';
	}

	function getObjectStructure(): array {
		return RecaptchaSetting::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions($existingObject): array {
		return [];
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#third_party_enrichment', 'Third Party Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Enrichment/RecaptchaSettings', 'reCaptcha Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'third_party_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Third Party Enrichment API Keys');
	}

	function canAddNew() {
		return $this->getNumObjects() == 0;
	}
}