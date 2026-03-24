<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/HeroSlider/HeroSliderLocation.php';
require_once ROOT_DIR . '/sys/HeroSlider/HeroSliderPlaylist.php';

class Admin_HeroSliderLocations extends ObjectEditor {

	function getObjectType(): string {
		return 'HeroSliderLocation';
	}

	function getToolName(): string {
		return 'HeroSliderLocations';
	}

	function getPageTitle(): string {
		return 'Hero Slider Locations';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];
		$object = new HeroSliderLocation();

		if (!UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$libraryList = Library::getLibraryList(true);
			$validIds = array_keys($libraryList);
			$validIds[] = -1;
			$object->whereAddIn('libraryId', $validIds, false);
		}

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
		return HeroSliderLocation::getObjectStructure($context);
	}

	function getPrimaryKeyColumn(): string {
		return 'id';
	}

	function getIdKeyColumn(): string {
		return 'id';
	}

	function canAddNew(): bool {
		return UserAccount::userHasPermission(['Administer All Hero Sliders', 'Administer Library Hero Sliders']);
	}

	function canDelete(): bool {
		return UserAccount::userHasPermission(['Administer All Hero Sliders', 'Administer Library Hero Sliders']);
	}

	function launch(): void {
		global $interface;

		//Get a list of all Playlists that are available
		require_once ROOT_DIR . '/sys/File/ImageUpload.php';
		$playlist = new HeroSliderPlaylist();
		$playlist->orderBy('name ASC');
		if (!UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$libraryList = Library::getLibraryList(true);
			$validIds = array_keys($libraryList);
			$validIds[] = -1;
			$playlist->whereAddIn('libraryId', $validIds, false);
		}
		$numAvailablePlaylists = $playlist->count();
		if ($numAvailablePlaylists == 0) {
			$warningMessage = translate(['text' => '<strong>Warning:</strong> No Hero Slider Playlists have been created. You should <a href="/Admin/HeroSliderPlaylists?objectAction=addNew">create playlists</a> first.', 'isAdminFacing' => true]);
			$interface->assign('propertiesListWarningMessage', $warningMessage);
		}

		$interface->assign('canAddNew', $this->canAddNew());
		$interface->assign('canDelete', $this->canDelete());

		$objectAction = $_REQUEST['objectAction'] ?? 'list';

		if ($objectAction == 'view' && isset($_REQUEST['id'])) {
			$location = new HeroSliderLocation();
			$location->id = $_REQUEST['id'];
			if ($location->find(true)) {
				$interface->assign('object', $location);
				$interface->assign('embedUrl', $location->getEmbedUrl());
				$interface->assign('returnToListUrl', $this->getReturnToListUrl());
				$this->display('heroSliderLocation.tpl', 'Hero Slider Location');
				return;
			}
		}

		parent::launch();
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/HeroSliderLocations', 'Hero Slider Locations');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	public function getViewPermissions() : array {
		return [
			'Administer All Hero Sliders',
			'Administer Library Hero Sliders',
		];
	}

	function getInitializationJs(): string {
		return 'AspenDiscovery.Admin.updateHeroSliderFields();';
	}

	function updateFromUI($object, $structure, $fieldLocks): array {
		if ($object instanceof HeroSliderLocation) {
			if (!empty($object->aspectRatioPreset) && $object->aspectRatioPreset !== 'custom') {
				$parts = explode(':', $object->aspectRatioPreset);
				if (count($parts) === 2) {
					$object->aspectRatioWidth = (int)$parts[0];
					$object->aspectRatioHeight = (int)$parts[1];
				}
			}
		}
		$validationResults = parent::updateFromUI($object, $structure, $fieldLocks);
		if ($object->displayStyle === 'digital_signage') {
			$object->autoRotate = 1;
		}
		return $validationResults;
	}

	function getAdditionalObjectActions(?DataObject $existingObject): array {
		$actions = parent::getAdditionalObjectActions($existingObject);
		if ($existingObject && !empty($existingObject->id)) {
			$actions[] = [
				'url' => '/Admin/HeroSliderLocations?objectAction=view&id=' . $existingObject->id,
				'text' => 'View',
				'onclick' => '',
				'target' => '',
			];
			$actions[] = [
				'url' => $existingObject->getEmbedUrl(),
				'text' => 'Preview',
				'onclick' => '',
				'target' => '_blank',
			];
		}
		return $actions;
	}
}
