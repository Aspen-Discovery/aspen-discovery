<?php


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

	static $xLargeSize = 1100;
	static $largeSize = 600;
	static $mediumSize = 400;
	static $smallSize = 200;

	public function getUniquenessFields(): array {
		return ['id'];
	}

	static function getObjectStructure($context = ''): array {
		global $serverName;
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

		return [
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
			],
			'type' => [
				'property' => 'type',
				'type' => 'text',
				'label' => 'Type',
				'description' => 'The type of image being uploaded.',
				'maxLength' => 50,
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
				'description' => 'The full size image (max width 1068px).',
				'maxWidth' => 1068,
				'maxHeight' => 1068,
				'path' => '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/full',
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
				'path' => '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/x-large',
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
				'path' => '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/large',
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
				'path' => '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/medium',
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
				'path' => '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/small',
				'displayUrl' => '/WebBuilder/ViewImage?size=small&id=',
				'note' => translate(['text' => 'Allowed formats: GIF, JPG, JPEG, PNG, SVG', 'isAdminFacing' => true]),
				'validTypes' => ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
			],
		];
	}

	function getDisplayUrl($property) {
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

	function insert($context = '') {
		$this->generateDerivatives();
		return parent::insert();
	}

	function update($context = '') {
		$this->generateDerivatives();
		return parent::update();
	}

	private function generateDerivatives() {
		if (!empty($this->fullSizePath) && !empty($this->id)) {
			global $serverName;
			require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';
			$fullSizeFile = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/full/' . $this->fullSizePath;
			if ($this->generateXLargeSize) {
				$xLargeFilePath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/x-large/';
				if (!file_exists($xLargeFilePath)) {
					mkdir($xLargeFilePath, 0755, true);
				}
				$xLargeFile = $xLargeFilePath . $this->fullSizePath;
				if (!empty($_FILES['fullSizePath']['full_path'])) {
					$prevUpload = $xLargeFilePath . "Temp_" . $_FILES['fullSizePath']['full_path'];
					if (file_exists($prevUpload)) {
						unlink($prevUpload);
					}
				}
				if (resizeImage($fullSizeFile, $xLargeFile, ImageUpload::$xLargeSize, ImageUpload::$xLargeSize)) {
					$this->xLargeSizePath = $this->fullSizePath;
				}
			}
			if ($this->generateLargeSize) {
				$largeFilePath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/large/';
				if (!file_exists($largeFilePath)) {
					mkdir($largeFilePath, 0755, true);
				}
				$largeFile = $largeFilePath . $this->fullSizePath;
				if (!empty($_FILES['fullSizePath']['full_path'])) {
					$prevUpload = $largeFilePath . "Temp_" . $_FILES['fullSizePath']['full_path'];
					if (file_exists($prevUpload)) {
						unlink($prevUpload);
					}
				}
				if (resizeImage($fullSizeFile, $largeFile, ImageUpload::$largeSize, ImageUpload::$largeSize)) {
					$this->largeSizePath = $this->fullSizePath;
				}
			}
			if ($this->generateMediumSize) {
				$mediumFilePath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/medium/';
				if (!file_exists($mediumFilePath)) {
					mkdir($mediumFilePath, 0755, true);
				}
				$mediumFile = $mediumFilePath . $this->fullSizePath;
				if (!empty($_FILES['fullSizePath']['full_path'])) {
					$prevUpload = $mediumFilePath . "Temp_" . $_FILES['fullSizePath']['full_path'];
					if (file_exists($prevUpload)) {
						unlink($prevUpload);
					}
				}
				if (resizeImage($fullSizeFile, $mediumFile, ImageUpload::$mediumSize, ImageUpload::$mediumSize)) {
					$this->mediumSizePath = $this->fullSizePath;
				}
			}
			if ($this->generateSmallSize) {
				$smallFilePath = '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_image/small/';
				if (!file_exists($smallFilePath)) {
					mkdir($smallFilePath, 0755, true);
				}
				$smallFile = $smallFilePath . $this->fullSizePath;
				if (!empty($_FILES['fullSizePath']['full_path'])) {
					$prevUpload = $smallFilePath . "Temp_" . $_FILES['fullSizePath']['full_path'];
					if (file_exists($prevUpload)) {
						unlink($prevUpload);
					}
				}
				if (resizeImage($fullSizeFile, $smallFile, ImageUpload::$smallSize, ImageUpload::$smallSize)) {
					$this->smallSizePath = $this->fullSizePath;
				}
			}
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
				if (array_key_exists($this->owningLibrary, $allowableLibraries)) {
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
}