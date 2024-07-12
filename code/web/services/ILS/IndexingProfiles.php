<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';

class ILS_IndexingProfiles extends ObjectEditor {
	function launch() {
		global $interface;
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'viewMarcFiles') {
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$files = [];
			$indexProfile = new IndexingProfile();
			if ($indexProfile->get($id) && !empty($indexProfile->marcPath)) {

				$marcPath = $indexProfile->marcPath;
				if ($handle = opendir($marcPath)) {
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$files[$entry] = filectime($marcPath . DIRECTORY_SEPARATOR . $entry);
						}
					}
					closedir($handle);
					$interface->assign('files', $files);
					$interface->assign('IndexProfileName', $indexProfile->name);
					$this->display('marcFiles.tpl', 'Marc Files');
				}
			}
		} elseif ($objectAction == 'loadDefaultBibFormatMappings') {
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$indexProfile = new IndexingProfile();
			if ($indexProfile->get($id) && !empty($indexProfile->marcPath)) {
				global $serverName;
				if (file_exists("../../sites/$serverName/translation_maps/format_map.csv")) {
					// Return the file path (note that all ini files are in the conf/ directory)
					$mapFilename = "../../sites/$serverName/translation_maps/format_map.csv";
				} elseif (file_exists("../../sites/default/translation_maps/format_map.csv")) {
					// Return the file path (note that all ini files are in the conf/ directory)
					$mapFilename = "../../sites/default/translation_maps/format_map.csv";
				}
				if (!empty($mapFilename)) {
					$fHnd = fopen($mapFilename, 'r');
					//Skip the first line
					fgets($fHnd);
					while ($formatMapRow = fgetcsv($fHnd)) {
						$formatMapValue = new FormatMapValue();
						$formatMapValue->value = trim($formatMapRow[0]);
						$formatMapValue->indexingProfileId = $indexProfile->id;
						if (!$formatMapValue->find(true)) {
							$formatMapValue->format = trim($formatMapRow[1]);
							$formatMapValue->formatCategory = trim($formatMapRow[2]);
							$formatMapValue->formatBoost = trim($formatMapRow[3]);
							$formatMapValue->appliesToMatType = 0;
							$formatMapValue->appliesToItemShelvingLocation = 0;
							$formatMapValue->appliesToItemSublocation = 0;
							$formatMapValue->appliesToItemCollection = 0;
							$formatMapValue->appliesToItemType = 0;
							$formatMapValue->appliesToItemFormat = 0;
							$formatMapValue->appliesToBibLevel = 1;
							$formatMapValue->appliesToFallbackFormat = 0;
							$formatMapValue->insert();
						}
					}
					fclose($fHnd);
				}
			}
			parent::launch();
		} else {
			parent::launch();
		}
	}

	function getObjectType(): string {
		return 'IndexingProfile';
	}

	function getModule(): string {
		return "ILS";
	}

	function getToolName(): string {
		return 'IndexingProfiles';
	}

	function getPageTitle(): string {
		return 'ILS Indexing Information';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];

		$object = new IndexingProfile();
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

	function getObjectStructure($context = ''): array {
		return IndexingProfile::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew() {
		return true;
	}

	function canDelete() {
		return true;
	}

	function getInstructions(): string {
		return 'https://help.aspendiscovery.org/ilsintegration';
	}

	function getAdditionalObjectActions($existingObject): array {
		$actions = [];
		if ($existingObject && $existingObject->id != '') {
			$actions[] = [
				'text' => 'View MARC files',
				'url' => '/ILS/IndexingProfiles?objectAction=viewMarcFiles&id=' . $existingObject->id,
			];
			$actions[] = [
				'text' => 'Load Default Bib Format Mappings',
				'url' => '/ILS/IndexingProfiles?objectAction=loadDefaultBibFormatMappings&id=' . $existingObject->id,
			];
		}

		return $actions;
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.Admin.updateIndexingProfileFields();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#ils_integration', 'ILS Integration');
		$breadcrumbs[] = new Breadcrumb('/ILS/IndexingProfiles', 'Indexing Profiles');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'ils_integration';
	}

	function canView(): bool {
		return UserAccount::userHasPermission('Administer Indexing Profiles');
	}
}