<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/HeroSlider/HeroSliderPlaylist.php';

class Admin_HeroSliderPlaylists extends ObjectEditor {
	function launch() : void {
		//Get a list of all Images that are available
		require_once ROOT_DIR . '/sys/File/ImageUpload.php';
		$imageList = [];
		$image = new ImageUpload();
		$image->type = 'hero_slider';
		if (!UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$libraryList = Library::getLibraryList(true);
			$validIds = array_keys($libraryList);
			$validIds[] = -1;
			$libraries = implode(',', $validIds);
			$image->whereAdd("owningLibrary IN ($libraries) OR sharing = 2");
		}
		$numAvailableImages = $image->count();
		if ($numAvailableImages == 0) {
			global $interface;
			$warningMessage = translate(['text' => '<strong>Warning:</strong> No Hero Slider Images have been created. You should <a href="/WebBuilder/Images?objectAction=addNew">create images</a> first.', 'isAdminFacing' => true]);
			$interface->assign('propertiesListWarningMessage', $warningMessage);
		}
		parent::launch();
	}

	function getObjectType(): string {
		return 'HeroSliderPlaylist';
	}

	function getToolName(): string {
		return 'HeroSliderPlaylists';
	}

	function getPageTitle(): string {
		return 'Hero Slider Playlists';
	}

	function getAllObjects($page, $recordsPerPage): array {
		$list = [];
		$object = new HeroSliderPlaylist();

		if (!UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$libraryList = Library::getLibraryList(true);
			$validIds = array_keys($libraryList);
			$validIds[] = -1;
			$object->whereAddIn("libraryId", $validIds, false);
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
		return HeroSliderPlaylist::getObjectStructure($context);
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

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/HeroSliderPlaylists', 'Hero Slider Playlists');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'local_enrichment';
	}

	public function getViewPermissions() : array {
		return ['Administer All Hero Sliders', 'Administer Library Hero Sliders'];
	}
}
