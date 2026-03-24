<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/HeroSlider/HeroSliderPlaylist.php';

class HeroSliderLocation extends DataObject {
	public $__table = 'hero_slider_location';
	public $id;
	public $name;
	public $description;
	public $displayStyle;
	public $aspectRatioPreset;
	public $aspectRatioWidth;
	public $aspectRatioHeight;
	public $autoRotate;
	/** @noinspection PhpUnused */
	public $rotationInterval;
	public $playlistId;
	public $libraryId;
	public $deleted;
	public $dateDeleted;
	public $deletedBy;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		// Library list (CollectionSpotlight pattern)
		$libraryList = [];
		if (UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$library = new Library();
			$library->orderBy('displayName');
			$library->find();
			$libraryList[-1] = 'All Libraries';
			while ($library->fetch()) {
				$libraryList[$library->libraryId] = $library->displayName;
			}
		} else {
			$homeLibrary = Library::getPatronHomeLibrary();
			if (!empty($homeLibrary)) {
				$libraryList[$homeLibrary->libraryId] = $homeLibrary->displayName;
			}
		}
		$allLibraryList = Library::getLibraryList(false);
		$allLibraryList[-1] = 'All Libraries';

		// Playlist list
		$playlistList = [];
		$playlistList[-1] = 'Select a playlist';
		$playlist = new HeroSliderPlaylist();
		$playlist->orderBy('name ASC');
		if (!UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if (!empty($homeLibrary)) {
				$playlist->whereAdd("libraryId = $homeLibrary->libraryId OR libraryId = -1");
			}
		}
		$playlist->find();
		while ($playlist->fetch()) {
			$playlistList[$playlist->id] = $playlist->name;
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the slider location.',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Slider Location Name',
				'description' => 'The name of this slider location.',
				'maxLength' => 255,
				'required' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'description' => 'Internal description (not shown publicly).',
				'rows' => 3,
				'cols' => 80,
				'hideInLists' => true,
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'allValues' => $allLibraryList,
				'label' => 'Library',
				'description' => 'On what library catalogs to display this hero slider.',
			],
			'displayStyle' => [
				'property' => 'displayStyle',
				'type' => 'enum',
				'values' => [
					'digital_signage' => 'Digital Signage',
					'website' => 'Website',
				],
				'label' => 'Display Style',
				'description' => 'How this slider will be displayed.',
				'required' => true,
				'default' => 'website',
				'onchange' => 'return AspenDiscovery.Admin.updateHeroSliderFields();',
			],
			'aspectRatioPreset' => [
				'property' => 'aspectRatioPreset',
				'type' => 'enum',
				'values' => [
					'16:9' => '16:9 (Widescreen)',
					'4:3' => '4:3 (Standard)',
					'21:9' => '21:9 (Ultrawide)',
					'1:1' => '1:1 (Square)',
					'9:16' => '9:16 (Portrait)',
					'custom' => 'Custom Aspect Ratio',
				],
				'label' => 'Aspect Ratio',
				'description' => 'Select a common aspect ratio or choose custom.',
				'default' => '16:9',
				'hideInLists' => true,
				'onchange' => 'return AspenDiscovery.Admin.updateHeroSliderFields();',
			],
			'aspectRatioWidth' => [
				'property' => 'aspectRatioWidth',
				'type' => 'integer',
				'label' => 'Custom Width',
				'description' => 'Aspect ratio width (e.g., 16 for 16:9).',
				'default' => 16,
				'min' => 1,
				'max' => 10000,
				'required' => true,
				'hideInLists' => true,
			],
			'aspectRatioHeight' => [
				'property' => 'aspectRatioHeight',
				'type' => 'integer',
				'label' => 'Custom Height',
				'description' => 'Aspect ratio height (e.g., 9 for 16:9).',
				'default' => 9,
				'min' => 1,
				'max' => 10000,
				'required' => true,
				'hideInLists' => true,
			],
			'autoRotate' => [
				'property' => 'autoRotate',
				'type' => 'checkbox',
				'label' => 'Auto-Rotate',
				'description' => 'Whether slides should automatically rotate. For &quot;Digital Signage&quot;, this option is always enabled.',
				'default' => 1,
				'hideInLists' => true,
			],
			'rotationInterval' => [
				'property' => 'rotationInterval',
				'type' => 'integer',
				'label' => 'Default Rotation Interval (Seconds)',
				'description' => 'Default time between slides (overridden by per-slide duration).',
				'default' => 5,
				'min' => 1,
				'max' => 60,
				'hideInLists' => true,
			],
			'playlistId' => [
				'property' => 'playlistId',
				'type' => 'enum',
				'values' => $playlistList,
				'label' => 'Playlist',
				'description' => 'The playlist of images to display.',
				'required' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return $structure;
	}

	public function updateStructureForEditingObject($structure) : array {
		if ($this->id >0 ) {
			$activePlaylist = new HeroSliderPlaylist();
			$activePlaylist->id = $this->playlistId;
			$activeAspectRatios = [];
			if ($activePlaylist->find(true)) {
				$playlistImages = $activePlaylist->getImages();

				foreach ($playlistImages as $playListImage) {
					$image = new ImageUpload();
					$image->id = $playListImage->imageId;
					$image->type = 'hero_slider';
					if ($image->find(true)) {
						$aspectRatio = "$image->aspectRatioWidth:$image->aspectRatioHeight";
						$activeAspectRatios[$aspectRatio] = $aspectRatio;
					}
				}
			}
			if (count($activeAspectRatios) == 1) {
				$firstAspectRatio = reset($activeAspectRatios);
				$structure['aspectRatioPreset']['note'] = "Active Playlist has an aspect ratio of $firstAspectRatio.";
			}else{
				$aspectRatios = implode(', ', $activeAspectRatios);
				$structure['aspectRatioPreset']['note'] = "Active Playlist has aspect ratios of $aspectRatios.";
			}

		}

		return $structure;
	}

	public function getEmbedUrl(): string {
		global $configArray;
		return $configArray['Site']['url'] . '/API/HeroSliderAPI?method=getHeroSlider&id=' . $this->id;
	}

	public function getAdditionalListActions(): array {
		$actions = parent::getAdditionalListActions();
		$actions[] = [
			'url' => '/Admin/HeroSliderLocations?objectAction=view&id=' . $this->id,
			'text' => 'View',
			'onclick' => '',
			'target' => '',
		];
		$actions[] = [
			'url' => $this->getEmbedUrl(),
			'text' => 'Preview',
			'onclick' => '',
			'target' => '_blank',
		];
		return $actions;
	}

	public function supportsSoftDelete(): bool {
		return true;
	}
}
