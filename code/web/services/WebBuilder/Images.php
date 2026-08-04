<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/File/ImageUpload.php';

class WebBuilder_Images extends ObjectEditor {
	function getObjectType(): string {
		return 'ImageUpload';
	}

	function getToolName(): string {
		return 'Images';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Uploaded Images';
	}

	function getAllObjects(int $page, int $recordsPerPage): array {
		$object = new ImageUpload();

		// Only filter by type if explicitly requested (don't filter by default in list view).
		if (isset($_REQUEST['type'])) {
			$object->type = $_REQUEST['type'];
		}

		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Web Content') && !UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$libraryList = Library::getLibraryList(true);
			$object->whereAddIn("owningLibrary", array_keys($libraryList), false, "OR");
			$object->whereAdd("owningLibrary = -1", "OR");
			$object->whereAdd("sharing = 2 OR sharing = 3", "OR");
			if (Library::getLibraryList(true)){
				$object->whereAdd("sharing = 1 AND sharedWithLibrary IN (" . implode(",", array_keys($libraryList)) . ")", "OR");
			}
		}
		$object->find();
		$objectList = [];
		while ($object->fetch()) {
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	function getDefaultSort(): string {
		return 'title asc';
	}

	function updateFromUI($object, $structure, $fieldLocks): array {
		if (empty($object->type)) {
			$object->type = $_REQUEST['type'] ?? 'web_builder_image';
		}
		return parent::updateFromUI($object, $structure, $fieldLocks);
	}

	function getObjectStructure($context = ''): array {
		return ImageUpload::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$objectActions = [];
		if ($existingObject instanceof ImageUpload && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View Image',
				'url' => '/WebBuilder/ViewImage?id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return 'https://aspen-discovery.atlassian.net/wiki/spaces/Help/pages/188579842/Images+and+PDFs';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Images', 'Images');
		return $breadcrumbs;
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Web Content',
			'Administer Web Content for Home Library',
			'Administer All Hero Sliders',
			'Administer Library Hero Sliders',
		];
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.toggleLibrarySharingOptions(); AspenDiscovery.Admin.toggleHeroSliderFields();';
	}

	public function hasRecordLocking() : bool {
		return true;
	}

	public function getRequiredModule(): ?string {
		return 'Web Builder';
	}
}