<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/YearInReview/YearInReviewSetting.php';

class Admin_YearInReview extends ObjectEditor {
	function getModule() : string {
		return 'Admin';
	}
	function getObjectType(): string {
		return 'YearInReviewSetting';
	}

	function getToolName(): string {
		return 'YearInReview';
	}

	function getPageTitle(): string {
		return 'Year In Review Settings';
	}

	function canDelete() : bool {
		return UserAccount::userHasPermission([
			'Administer Year in Review for All Libraries',
			'Administer Year in Review for Home Library',
		]);
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new YearInReviewSetting();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$userHasExistingMessages = true;
		if (!UserAccount::userHasPermission('Administer Year in Review for All Libraries')) {
			$libraries = Library::getLibraryList(true);
			$yearInReviewSettingsForLibrary = [];
			foreach ($libraries as $libraryId => $displayName) {
				$libraryYearInReview = new LibraryYearInReview();
				$libraryYearInReview->libraryId = $libraryId;
				$libraryYearInReview->find();
				while ($libraryYearInReview->fetch()) {
					$yearInReviewSettingsForLibrary[] = $libraryYearInReview->yearInReviewId;
				}
			}
			if (count($yearInReviewSettingsForLibrary) > 0) {
				$object->whereAddIn('id', $yearInReviewSettingsForLibrary, false);
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
		return 'name asc';
	}

	function getObjectStructure($context = ''): array {
		return YearInReviewSetting::getObjectStructure($context);
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
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/YearInReview', 'Year In Review');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			'Administer Year in Review for All Libraries',
			'Administer Year in Review for Home Library',
		]);
	}

	function canBatchEdit(): bool {
		return UserAccount::userHasPermission([
			'Administer Year in Review for All Libraries',
		]);
	}

	function canCopy() : bool {
		return $this->canAddNew();
	}
}