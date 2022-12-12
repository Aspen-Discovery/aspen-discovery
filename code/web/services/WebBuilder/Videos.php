<?php
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/File/FileUpload.php';

class WebBuilder_Videos extends ObjectEditor {
	function getObjectType(): string {
		return 'FileUpload';
	}

	function getToolName(): string {
		return 'Videos';
	}

	function getModule(): string {
		return 'WebBuilder';
	}

	function getPageTitle(): string {
		return 'Uploaded Videos';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$object = new FileUpload();
		$object->type = 'web_builder_video';
		$this->applyFilters($object);
		$object->orderBy($this->getSort());
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
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

	function updateFromUI($object, $structure) {
		$object->type = 'web_builder_video';
		return parent::updateFromUI($object, $structure);
	}

	function getObjectStructure($context = ''): array {
		$objectStructure = FileUpload::getObjectStructure($context);
		unset($objectStructure['type']);
		$fileProperty = $objectStructure['fullPath'];
		global $serverName;
		$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_video/';
		$fileProperty['path'] = $dataPath;
		$fileProperty['validTypes'] = ['video/mp4'];
		$objectStructure['fullPath'] = $fileProperty;
		return $objectStructure;
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	/**
	 * @param FileUpload $existingObject
	 * @return array
	 */
	function getAdditionalObjectActions($existingObject): array {
		$objectActions = [];
		if (!empty($existingObject) && !empty($existingObject->id)) {
			$objectActions[] = [
				'text' => 'Watch Video',
				'url' => '/Files/' . $existingObject->id . '/WatchVideo',
			];
		}
		return $objectActions;
	}

	function getInstructions(): string {
		return '';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#web_builder', 'Web Builder');
		$breadcrumbs[] = new Breadcrumb('/WebBuilder/Videos', 'Videos');
		return $breadcrumbs;
	}

	function canView(): bool {
		return UserAccount::userHasPermission(['Administer All Web Content']);
	}

	function getActiveAdminSection(): string {
		return 'web_builder';
	}
}