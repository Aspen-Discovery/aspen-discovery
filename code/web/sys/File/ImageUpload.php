<?php
/** @noinspection PhpMissingFieldTypeInspection */


class ImageUpload extends DataObject {
	public $__table = 'image_uploads';
	public $id;
	public $title;
	public $fullSizePath; //Stores the original file maximum width of 1068px
	public $generateXLargeSize;
	public $xLargeSizePath; //Stores the thumbnail with a maximum size of 350px
	public $generateLargeSize;
	public $largeSizePath; //Stores the thumbnail with a maximum size of 350px
	public $generateMediumSize;
	public $mediumSizePath; //Stores the thumbnail with a maximum size of 350px
	public $generateSmallSize;
	public $smallSizePath; //Stores the thumbnail with a maximum size of 200x200px
	public $type;
	public $owningLibrary;
	public $sharing;
	public $sharedWithLibrary;
	public $deleted;
	public $dateDeleted;
	public $deletedBy;
	// Hero slider specific fields.
	public $aspectRatioWidth;
	public $aspectRatioHeight;
	/** @noinspection PhpUnused */
	public $altText;
	/** @noinspection PhpUnused */
	public $pageLink;
	public $startDate;
	public $endDate;
	// Which storage_settings row this upload's files actually live on.
	// NULL means Local Storage; not part of the admin edit form, set only
	// by the storage write path.
	public $storageSettingId;

	static $xLargeSize = 1100;
	static $largeSize = 600;
	static $mediumSize = 400;
	static $smallSize = 200;

	public function getUniquenessFields(): array {
		return ['id'];
	}

	public function getNumericColumnNames(): array {
		return [
			'aspectRatioWidth',
			'aspectRatioHeight',
			'startDate',
			'endDate',
			'storageSettingId',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$allSharingOptions = [
			0 => 'Not Shared',
			1 => 'Selected Library',
			2 => 'All Libraries',
			3 => 'All Libraries - Read Only'
		];
		$allowableSharingOptions = $allSharingOptions;
		$libraryListForSharing[-1] = '';
		//need to get restricted list first
		$libraryList = Library::getLibraryList(true);

		$allLibraryList[-1] = 'All Libraries';
		$allLibraryList = $allLibraryList + Library::getLibraryList(false);

		if (!UserAccount::userHasPermission('Administer All Web Content') && (UserAccount::userHasPermission('Administer Web Content for Home Library'))) {
			unset($allowableSharingOptions[2]);
		}else{
			$libraryList = $allLibraryList;
		}

		$libraryListForSharing = $libraryListForSharing + $libraryList;

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database.',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title of the image.',
				'size' => '40',
				'maxLength' => 255,
				'required' => true,
			],
			'type' => [
				'property' => 'type',
				'type' => 'enum',
				'values' => [
					'web_builder_image' => 'Web Builder Image',
					'hero_slider' => 'Hero Slider Image',
				],
				'label' => 'Image Type',
				'description' => 'The type of image being uploaded.',
				'default' => 'web_builder_image',
				'required' => true,
				'onchange' => 'return AspenDiscovery.Admin.toggleHeroSliderFields();',
			],
			'owningLibrary' => [
				'property' => 'owningLibrary',
				'type' => 'enum',
				'values' => $libraryList,
				'allValues' => $allLibraryList,
				'label' => 'Owning Library',
				'description' => 'Which library owns this image.',
				'onchange' => "return AspenDiscovery.Admin.toggleLibrarySharingOptions();",
			],
			'sharing' => [
				'property' => 'sharing',
				'type' => 'enum',
				'values' => $allowableSharingOptions,
				'allValues' => $allSharingOptions,
				'label' => 'Share With',
				'description' => 'With whom the image should be shared.',
				'onchange' => "return AspenDiscovery.Admin.toggleLibrarySharingOptions();",
			],
			'sharedWithLibrary' => [
				'property' => 'sharedWithLibrary',
				'type' => 'enum',
				'values' => $libraryListForSharing,
				'allValues' => $allLibraryList,
				'label' => 'Library to Share With',
				'description' => 'With which library to share this image.',
			],
			'fullSizePath' => [
				'property' => 'fullSizePath',
				'type' => 'image',
				'label' => 'Full Size Image',
				'description' => 'The full size image (max width 1068px for web builder images, 3840px for hero sliders).',
				'maxWidth' => 3840,  // 4K width - suitable for digital signage
				'maxHeight' => 2160, // 4K height
				'storageKey' => 'uploads/web_builder_image/full',
				'displayUrl' => '/WebBuilder/ViewImage?size=full&id=',
				'hideInLists' => true,
				'required' => true,
				'note' => translate(['text' => 'Allowed formats: GIF, JPG, JPEG, PNG, SVG', 'isAdminFacing' => true]),
				'validTypes' => ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
			],
			'generateXLargeSize' => [
				'property' => 'generateXLargeSize',
				'type' => 'checkbox',
				'label' => 'Generate x-large size image',
				'default' => 1,
				'hideInLists' => true,
			],
			'xLargeSizePath' => [
				'property' => 'xLargeSizePath',
				'type' => 'image',
				'label' => 'X-Large Size Image',
				'description' => 'The x-large size image (max width 1100 px).',
				'maxWidth' => ImageUpload::$xLargeSize,
				'maxHeight' => ImageUpload::$xLargeSize,
				'storageKey' => 'uploads/web_builder_image/x-large',
				'displayUrl' => '/WebBuilder/ViewImage?size=x-large&id=',
				'hideInLists' => true,
				'note' => translate(['text' => 'Allowed formats: GIF, JPG, JPEG, PNG, SVG', 'isAdminFacing' => true]),
				'validTypes' => ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
			],
			'generateLargeSize' => [
				'property' => 'generateLargeSize',
				'type' => 'checkbox',
				'label' => 'Generate large size image',
				'default' => 1,
				'hideInLists' => true,
			],
			'largeSizePath' => [
				'property' => 'largeSizePath',
				'type' => 'image',
				'label' => 'Large Size Image',
				'description' => 'The medium size image (max width 600px).',
				'maxWidth' => ImageUpload::$largeSize,
				'maxHeight' => ImageUpload::$largeSize,
				'storageKey' => 'uploads/web_builder_image/large',
				'displayUrl' => '/WebBuilder/ViewImage?size=large&id=',
				'hideInLists' => true,
				'note' => translate(['text' => 'Allowed formats: GIF, JPG, JPEG, PNG, SVG', 'isAdminFacing' => true]),
				'validTypes' => ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
			],
			'generateMediumSize' => [
				'property' => 'generateMediumSize',
				'type' => 'checkbox',
				'label' => 'Generate medium size image',
				'default' => 1,
				'hideInLists' => true,
			],
			'mediumSizePath' => [
				'property' => 'mediumSizePath',
				'type' => 'image',
				'label' => 'Medium Size Image',
				'description' => 'The medium size image (max width 400px).',
				'maxWidth' => ImageUpload::$mediumSize,
				'maxHeight' => ImageUpload::$mediumSize,
				'storageKey' => 'uploads/web_builder_image/medium',
				'displayUrl' => '/WebBuilder/ViewImage?size=medium&id=',
				'hideInLists' => true,
				'note' => translate(['text' => 'Allowed formats: GIF, JPG, JPEG, PNG, SVG', 'isAdminFacing' => true]),
				'validTypes' => ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
			],
			'generateSmallSize' => [
				'property' => 'generateSmallSize',
				'type' => 'checkbox',
				'label' => 'Generate small size image',
				'default' => 1,
				'hideInLists' => true,
			],
			'smallSizePath' => [
				'property' => 'smallSizePath',
				'type' => 'image',
				'label' => 'Small Size Image',
				'description' => 'The small size image (max width 200px).',
				'maxWidth' => ImageUpload::$smallSize,
				'maxHeight' => ImageUpload::$smallSize,
				'storageKey' => 'uploads/web_builder_image/small',
				'displayUrl' => '/WebBuilder/ViewImage?size=small&id=',
				'note' => translate(['text' => 'Allowed formats: GIF, JPG, JPEG, PNG, SVG', 'isAdminFacing' => true]),
				'validTypes' => ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
			],
			'altText' => [
				'property' => 'altText',
				'type' => 'text',
				'label' => 'Alt Text',
				'description' => 'Alternative text for accessibility.',
				'maxLength' => 512,
				'hideInLists' => true,
			],
			'pageLink' => [
				'property' => 'pageLink',
				'type' => 'url',
				'label' => 'Page Link',
				'description' => 'URL to link to when image is clicked.',
				'maxLength' => 512,
				'hideInLists' => true,
			],
			'startDate' => [
				'property' => 'startDate',
				'type' => 'timestamp',
				'label' => 'Start Date',
				'description' => 'Date when this image should start displaying.',
				'hideInLists' => true,
			],
			'endDate' => [
				'property' => 'endDate',
				'type' => 'timestamp',
				'label' => 'End Date',
				'description' => 'Date when this image should stop displaying.',
				'hideInLists' => true,
			],
			'aspectRatioWidth' => [
				'property' => 'aspectRatioWidth',
				'type' => 'hidden',
				'label' => 'Aspect Ratio Width',
				'description' => 'Calculated aspect ratio width (e.g., 16 for 16:9). Auto-calculated on upload.',
				'hideInLists' => true,
			],
			'aspectRatioHeight' => [
				'property' => 'aspectRatioHeight',
				'type' => 'hidden',
				'label' => 'Aspect Ratio Height',
				'description' => 'Calculated aspect ratio height (e.g., 9 for 16:9). Auto-calculated on upload.',
				'hideInLists' => true,
			],
			'calculatedAspectRatio' => [
				'property' => 'calculatedAspectRatio',
				'type' => 'label',
				'label' => 'Calculated Aspect Ratio',
				'description' => 'Aspect ratio as width:height (e.g., 16:9). Auto-calculated on upload.',
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	function getDisplayUrl($property) : string {
		if (empty($this->id)) {
			return '';
		}
		if ($property == 'xLargeSizePath') {
			$size = 'x-large';
		} elseif ($property == 'largeSizePath') {
			$size = 'large';
		} elseif ($property == 'mediumSizePath') {
			$size = 'medium';
		} elseif ($property == 'smallSizePath') {
			$size = 'small';
		} else {
			$size = 'full';
		}
		return '/WebBuilder/ViewImage?size=' . $size . '&id=' . $this->id;
	}

	public function insert(string $context = '') : int|bool {
		$this->calculateAspectRatio();
		$this->generateDerivatives();
		return parent::insert();
	}

	public function update(string $context = '') : int|bool {
		$this->calculateAspectRatio();
		$this->generateDerivatives();
		return parent::update();
	}

	private function calculateAspectRatio() : void {
		if ($this->type === 'hero_slider' && !empty($this->fullSizePath)) {
			$contents = StorageDriverFactory::getById($this->storageSettingId)->read('uploads/web_builder_image/full/' . $this->fullSizePath);
			if ($contents !== false) {
				$imageInfo = getimagesizefromstring($contents);
				if ($imageInfo !== false) {
					[$width, $height] = $imageInfo;
					if ($width && $height) {
						$gcd = $this->gcd($width, $height);
						$this->aspectRatioWidth = $width / $gcd;
						$this->aspectRatioHeight = $height / $gcd;
					}
				}
			}
		}
	}

	/**
	 * Calculate the Greatest Common Divisor (GCD) of two numbers.
	 * Used to simplify aspect ratios (e.g., 1920/1080 = 16/9).
	 *
	 * @param int $a First number
	 * @param int $b Second number
	 * @return int The GCD
	 */
	private function gcd(int $a, int $b): int {
		return $b ? $this->gcd($b, $a % $b) : $a;
	}

	public function __get($name) {
		if ($name === 'calculatedAspectRatio') {
			if (!empty($this->aspectRatioWidth) && !empty($this->aspectRatioHeight)) {
				return $this->aspectRatioWidth . ':' . $this->aspectRatioHeight;
			}
			return '';
		}
		return parent::__get($name);
	}

	private function generateDerivatives() : void {
		global $logger;
		if (!empty($this->fullSizePath) && !empty($this->id)) {
			require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';
			$storage = StorageDriverFactory::getById($this->storageSettingId);
			$logger->log("generateDerivatives: image id=$this->id storageSettingId=" . var_export($this->storageSettingId, true), Logger::LOG_DEBUG);

			$sourceContents = $storage->read('uploads/web_builder_image/full/' . $this->fullSizePath);
			if ($sourceContents === false) {
				$logger->log("generateDerivatives: could not read source for image id=$this->id fullSizePath=$this->fullSizePath", Logger::LOG_ERROR);
				return;
			}
			$srcTmp = tempnam(sys_get_temp_dir(), 'aspen_src_');
			file_put_contents($srcTmp, $sourceContents);

			foreach ([
				'x-large' => ['flag' => 'generateXLargeSize', 'prop' => 'xLargeSizePath', 'size' => ImageUpload::$xLargeSize],
				'large'   => ['flag' => 'generateLargeSize',   'prop' => 'largeSizePath',   'size' => ImageUpload::$largeSize],
				'medium'  => ['flag' => 'generateMediumSize',  'prop' => 'mediumSizePath',  'size' => ImageUpload::$mediumSize],
				'small'   => ['flag' => 'generateSmallSize',   'prop' => 'smallSizePath',   'size' => ImageUpload::$smallSize],
			] as $variant => $cfg) {
				if (!$this->{$cfg['flag']}) {
					continue;
				}
				if (!empty($_FILES['fullSizePath']['full_path'])) {
					$tempKey = 'uploads/web_builder_image/' . $variant . '/Temp_' . $_FILES['fullSizePath']['full_path'];
					if ($storage->exists($tempKey)) {
						$storage->delete($tempKey);
					}
				}
				$destTmp = tempnam(sys_get_temp_dir(), 'aspen_dst_');
				if (resizeImage($srcTmp, $destTmp, $cfg['size'], $cfg['size'])) {
					if ($storage->write('uploads/web_builder_image/' . $variant . '/' . $this->fullSizePath, $destTmp)) {
						$this->{$cfg['prop']} = $this->fullSizePath;
						$logger->log("generateDerivatives: wrote $variant derivative for image id=$this->id", Logger::LOG_DEBUG);
					} else {
						$logger->log('Failed to write ' . $variant . ' derivative image to storage for fullSizePath ' . $this->fullSizePath, Logger::LOG_ERROR);
					}
				}
				unlink($destTmp);
			}
			unlink($srcTmp);
		}
	}

	public function updateStructureForEditingObject($structure) : array {
		if ($this->isReadOnly()) {
			$structure['title']['readOnly'] = true;
			$structure['owningLibrary']['readOnly'] = true;
			$structure['sharing']['readOnly'] = true;
			$structure['sharedWithLibrary']['readOnly'] = true;
			$structure['fullSizePath']['readOnly'] = true;
			$structure['generateXLargeSize']['readOnly'] = true;
			$structure['xLargeSizePath']['readOnly'] = true;
			$structure['generateLargeSize']['readOnly'] = true;
			$structure['largeSizePath']['readOnly'] = true;
			$structure['generateMediumSize']['readOnly'] = true;
			$structure['mediumSizePath']['readOnly'] = true;
			$structure['generateSmallSize']['readOnly'] = true;
			$structure['smallSizePath']['readOnly'] = true;
		}
		return $structure;
	}

	private ?bool $_isReadOnly = null;
	/**
	 * Determine whether the Image can be changed by the active user.
	 * This is slightly different from canActiveUserEdit because we want the user to be able to view
	 * but not change the image and access the image(s) they have access to
	 *
	 * @return bool
	 */
	public function isReadOnly() : bool {
		if ($this->_isReadOnly === null) {
			//Active user can edit if they have permission to edit everything or this is for their home location or sharing allows editing
			if (UserAccount::userHasPermission('Administer All Web Content')) {
				$this->_isReadOnly = false;
			}elseif (UserAccount::userHasPermission( 'Administer Web Content for Home Library')){
				$allowableLibraries = Library::getLibraryList(true);
				if (array_key_exists($this->owningLibrary, $allowableLibraries) || array_key_exists($this->sharedWithLibrary, $allowableLibraries)) {
					$this->_isReadOnly = false;
				}else{
					//Ok if shared by everyone
					if ($this->sharing == 2 || $this->owningLibrary == -1) {
						$this->_isReadOnly = false;
					}else{
						$this->_isReadOnly = true;
					}
				}
			}else{ //Manage images for Home Library Only
				$this->_isReadOnly = true;
			}
		}
		return $this->_isReadOnly;
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		if ($hardDelete) {
			global $logger;
			$storage = StorageDriverFactory::getById($this->storageSettingId);
			$logger->log("ImageUpload::delete: hard deleting image id=$this->id storageSettingId=" . var_export($this->storageSettingId, true), Logger::LOG_DEBUG);
			foreach ([
				'full'    => $this->fullSizePath,
				'x-large' => $this->xLargeSizePath,
				'large'   => $this->largeSizePath,
				'medium'  => $this->mediumSizePath,
				'small'   => $this->smallSizePath,
			] as $size => $filename) {
				if (!empty($filename)) {
					$storage->delete('uploads/web_builder_image/' . $size . '/' . $filename);
				}
			}
		}
		return parent::delete($useWhere, $hardDelete);
	}

	public function supportsSoftDelete(): bool {
		return true;
	}

	/**
	 * Purge expired soft-deleted images: delete disk files then DB rows.
	 *
	 * @param int $olderThanSecs
	 * @return int
	 */
	public static function purgeExpired(int $olderThanSecs = 2592000): int {
		global $logger;
		$cutOff = time() - $olderThanSecs;
		$expiredIds = [];
		$fetchObj = new static();
		$fetchObj->deleted = 1;
		// dateDeleted > 0 = Leave images older than the Object Restorations implementation alone for now.
		$fetchObj->whereAdd("dateDeleted > 0 AND dateDeleted < $cutOff");
		$fetchObj->find();
		while ($fetchObj->fetch()) {
			$storage = StorageDriverFactory::getById($fetchObj->storageSettingId);
			$logger->log("ImageUpload::purgeExpired: purging image id=$fetchObj->id storageSettingId=" . var_export($fetchObj->storageSettingId, true), Logger::LOG_DEBUG);
			foreach ([
				'full'    => $fetchObj->fullSizePath,
				'x-large' => $fetchObj->xLargeSizePath,
				'large'   => $fetchObj->largeSizePath,
				'medium'  => $fetchObj->mediumSizePath,
				'small'   => $fetchObj->smallSizePath,
			] as $size => $filename) {
				if (!empty($filename)) {
					$storage->delete('uploads/web_builder_image/' . $size . '/' . $filename);
				}
			}
			$expiredIds[] = $fetchObj->id;
		}
		if (empty($expiredIds)) {
			return 0;
		}

		$deleteObj = new static();
		$deleteObj->whereAddIn($deleteObj->getPrimaryKey(), $expiredIds, false);
		return $deleteObj->delete(true, true);
	}
}