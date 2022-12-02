<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/SideLoad.php';

class SideLoads_SideLoads extends ObjectEditor {
	function launch() {
		global $interface;
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'viewMarcFiles') {
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$files = [];
			$sideLoadConfiguration = new SideLoad();
			$sideLoadConfiguration->id = $id;
			if ($sideLoadConfiguration->find(true) && !empty($sideLoadConfiguration->marcPath)) {
				$interface->assign('sideload', $sideLoadConfiguration);
				$marcPath = $sideLoadConfiguration->marcPath;
				if ($handle = opendir($marcPath)) {
					$index = 0;
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$fullName = $marcPath . DIR_SEP . $entry;
							$files[$entry] = [
								'date' => filectime($fullName),
								'size' => filesize($fullName),
								'index' => $index++,
							];
						}
					}
					closedir($handle);
					$interface->assign('files', $files);
					$interface->assign('SideLoadName', $sideLoadConfiguration->name);
					$this->display('marcFiles.tpl', 'Marc Files');
				}
			}
		} else {
			parent::launch();
		}
	}

	function getObjectType(): string {
		return 'SideLoad';
	}

	function getModule(): string {
		return "SideLoads";
	}

	function getToolName(): string {
		return 'SideLoads';
	}

	function getPageTitle(): string {
		return 'Side Loaded eContent Collections';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new SideLoad();
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()) {
			$list[$object->id] = clone $object;
		}

		return $list;
	}

	function getDefaultSort(): string {
		return 'name asc';
	}

	function getObjectStructure(): array {
		return SideLoad::getObjectStructure();
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/help/integration/sideload';
	}

	function getAdditionalObjectActions($existingObject): array {
		$actions = [];
		if ($existingObject && $existingObject->id != '') {
			$actions[] = [
				'text' => 'View MARC files',
				'url' => '/SideLoads/SideLoads?objectAction=viewMarcFiles&id=' . $existingObject->id,
			];
			$actions[] = [
				'text' => 'Upload MARC file',
				'url' => '/SideLoads/UploadMarc?id=' . $existingObject->id,
			];
		}

		return $actions;
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#side_loads', 'Side Load');
		$breadcrumbs[] = new Breadcrumb('/SideLoads/SideLoads', 'Side Load Settings');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'side_loads';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Side Loads');
	}
}