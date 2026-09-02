<?php
/** @noinspection PhpMissingFieldTypeInspection */

class HeroSliderPlaylistImage extends DataObject {
	public $__table = 'hero_slider_playlist_image';
	public $id;
	public $playlistId;
	public $imageId;
	public $weight;
	public $duration;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		require_once ROOT_DIR . '/sys/File/ImageUpload.php';
		$imageList = [];
		$image = new ImageUpload();
		$image->type = 'hero_slider';
		$image->orderBy('title ASC');
		if (!UserAccount::userHasPermission('Administer All Hero Sliders')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if (!empty($homeLibrary)) {
				$image->whereAdd("owningLibrary = $homeLibrary->libraryId OR sharing = 2");
			}
		}
		$image->find();
		while ($image->fetch()) {
			$aspectRatio = ($image->aspectRatioWidth && $image->aspectRatioHeight)
				? "$image->aspectRatioWidth:$image->aspectRatioHeight"
				: "Unknown";
			$imageList[$image->id] = "$image->title ($aspectRatio)";
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id.',
			],
			'playlistId' => [
				'property' => 'playlistId',
				'type' => 'label',
				'label' => 'Playlist',
				'description' => 'The parent playlist.',
			],
			'weight' => [
				'property' => 'weight',
				'type' => 'numeric',
				'label' => 'Weight',
				'description' => 'Defines how items are sorted. Lower weights are displayed first.',
				'default' => 0,
				'required' => true,
			],
			'imageId' => [
				'property' => 'imageId',
				'type' => 'enum',
				'values' => $imageList,
				'label' => 'Image',
				'description' => 'The image to display.',
				'required' => true,
			],
			'duration' => [
				'property' => 'duration',
				'type' => 'integer',
				'label' => 'Duration (Seconds)',
				'description' => 'How long to display this slide.',
				'default' => 5,
				'min' => 0,
				'max' => 60,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return $structure;
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/WebBuilder/Images?objectAction=edit&id=' . $this->imageId;
	}
}
