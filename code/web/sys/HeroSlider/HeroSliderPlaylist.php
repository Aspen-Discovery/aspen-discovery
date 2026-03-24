<?php
/** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/HeroSlider/HeroSliderPlaylistImage.php';

class HeroSliderPlaylist extends DataObject {
	public $__table = 'hero_slider_playlist';
	public $id;
	public $name;
	public $libraryId;
	public $deleted;
	public $dateDeleted;
	public $deletedBy;

	private ?array $playlistImages = null;

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

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

		$playlistImageStructure = HeroSliderPlaylistImage::getObjectStructure($context);

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the playlist.',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Playlist Name',
				'description' => 'The name of this playlist.',
				'maxLength' => 255,
				'required' => true,
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'allValues' => $allLibraryList,
				'label' => 'Library',
				'description' => 'The library this playlist belongs to.',
			],
			'images' => [
				'property' => 'images',
				'type' => 'oneToMany',
				'keyThis' => 'id',
				'keyOther' => 'playlistId',
				'subObjectType' => 'HeroSliderPlaylistImage',
				'structure' => $playlistImageStructure,
				'label' => 'Images',
				'description' => 'Images in this playlist.',
				'noteBullets' => ['For images to display, they must match the Aspect Ratio chosen for this playlist\'s Hero Slider Location(s).',
								 'Images with a Duration of "0" are not displayed.',
								 '<a href="/WebBuilder/Images?objectAction=addNew">Add Image</a>'],
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return $structure;
	}

	public function __get($name) {
		if ($name == "images") {
			return $this->getImages();
		}
		return parent::__get($name);
	}

	public function __set($name, $value) {
		if ($name == "images") {
			$this->playlistImages = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = ''): int|bool {
		$ret = parent::update($context);
		if ($ret === false) {
			return false;
		}
		$this->saveImages();
		$this->validateAspectRatios();
		return true;
	}

	public function insert($context = ''): int|bool {
		$ret = parent::insert($context);
		if ($ret === false) {
			return false;
		}
		$this->saveImages();
		$this->validateAspectRatios();
		return true;
	}

	private function validateAspectRatios(): void {
		require_once ROOT_DIR . '/sys/File/ImageUpload.php';
		$aspectRatios = [];
		$playlistImage = new HeroSliderPlaylistImage();
		$playlistImage->playlistId = $this->id;
		$playlistImage->find();
		while ($playlistImage->fetch()) {
			$image = new ImageUpload();
			$image->id = $playlistImage->imageId;
			if ($image->find(true)) {
				if (!empty($image->aspectRatioWidth) && !empty($image->aspectRatioHeight)) {
					$ratio = $image->aspectRatioWidth . ':' . $image->aspectRatioHeight;
					$aspectRatios[$ratio] = true;
				}
			}
		}

		if (count($aspectRatios) > 1) {
			$ratioList = implode(', ', array_keys($aspectRatios));
			$user = UserAccount::getActiveUserObj();
			if ($user) {
				$user->updateMessage = "Warning: This playlist contains images with different aspect ratios ($ratioList). Images will only display in locations with matching aspect ratios.";
				$user->updateMessageIsError = true;
				$user->update();
			}
		}
	}

	public function saveImages(): void {
		if ($this->playlistImages != null) {
			foreach ($this->playlistImages as $image) {
				if ($image->_deleteOnSave) {
					$image->delete();
				} else {
					if (isset($image->id) && is_numeric($image->id)) {
						$image->update();
					} else {
						$image->playlistId = $this->id;
						$image->insert();
					}
				}
			}
			$this->playlistImages = null;
		}
	}

	public function getActiveImages(int $aspectRatioWidth, int $aspectRatioHeight): array {
		$activeImages = [];
		require_once ROOT_DIR . '/sys/File/ImageUpload.php';
		$playlistImage = new HeroSliderPlaylistImage();
		$playlistImage->playlistId = $this->id;
		$playlistImage->orderBy('weight ASC');
		$playlistImage->find();

		while ($playlistImage->fetch()) {
			$image = new ImageUpload();
			$image->id = $playlistImage->imageId;
			$image->type = 'hero_slider';
			if ($image->find(true)) {
				if ($image->aspectRatioWidth == $aspectRatioWidth &&
					$image->aspectRatioHeight == $aspectRatioHeight) {
					$curTime = time();
					$isValid = true;
					if ($image->startDate != 0 && $image->startDate > $curTime) {
						$isValid = false;
					}
					if ($image->endDate != 0 && $image->endDate < $curTime) {
						$isValid = false;
					}
					if ($playlistImage->duration == 0) {
						$isValid = false;
					}

					if ($isValid) {
						$activeImages[] = [
							'image' => clone $image,
							'duration' => $playlistImage->duration,
							'weight' => $playlistImage->weight,
						];
					}
				}
			}
		}

		return $activeImages;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false): bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && $hardDelete && !empty($this->id)) {
			$playlistImage = new HeroSliderPlaylistImage();
			$playlistImage->playlistId = $this->id;
			$playlistImage->delete(true);
		}
		return $ret;
	}

	public function supportsSoftDelete(): bool {
		return true;
	}

	/**
	 * @return HeroSliderPlaylistImage[]
	 */
	public function getImages() : array{
		if ($this->playlistImages == null) {
			$this->playlistImages = [];
			if ($this->id) {
				$playlistImage = new HeroSliderPlaylistImage();
				$playlistImage->playlistId = $this->id;
				$playlistImage->orderBy('weight ASC');
				$playlistImage->find();
				while ($playlistImage->fetch()) {
					$this->playlistImages[$playlistImage->id] = clone($playlistImage);
				}
			}
		}
		return $this->playlistImages;
	}
}
