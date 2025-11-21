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
		$object->type = 'web_builder_image';
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		if (!UserAccount::userHasPermission('Administer All Web Content') && (UserAccount::userHasPermission('Administer Web Content for Home Library'))) {
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
		$object->type = 'web_builder_image';
		return parent::updateFromUI($object, $structure, $fieldLocks);
	}

	function getObjectStructure($context = ''): array {
		$objectStructure = ImageUpload::getObjectStructure($context);
		unset($objectStructure['type']);
		return $objectStructure;
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$objectActions = [];
		if ($existingObject instanceof FileUpload && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'View Image',
				'url' => '/WebBuilder/ViewImage?id=' . $existingObject->id,
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/webbuilder/imagespdfs';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Images', 'Images');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer All Web Content', 'Administer Web Content for Home Library']);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.toggleLibrarySharingOptions();';
	}
}