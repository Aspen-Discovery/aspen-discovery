<?php

class FileUpload extends DataObject {
	public $__table = 'file_uploads';
	public $id;
	public $title;
	public $fullPath;
	public $thumbFullPath;
	public $type;
	public $owningLibrary;
	public $sharing;
	public $sharedWithLibrary;

	public function getUniquenessFields(): array {
		return ['id'];
	}

	static function getObjectStructure($context = ''): array {
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
			unset($allowableSharingOptions[1]);
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
				'description' => 'The title of the page.',
				'size' => '40',
				'maxLength' => 255,
			],
			'type' => [
				'property' => 'type',
				'type' => 'text',
				'label' => 'Type',
				'description' => 'The type of file being uploaded.',
				'maxLength' => 50,
			],
			'owningLibrary' => [
				'property' => 'owningLibrary',
				'type' => 'enum',
				'values' => $libraryList,
				'allValues' => $allLibraryList,
				'label' => 'Owning Library (PDFs Only)',
				'description' => 'Which library owns this side load.',
				'onchange' => "return AspenDiscovery.Admin.toggleLibrarySharingOptions();",
			],
			'sharing' => [
				'property' => 'sharing',
				'type' => 'enum',
				'values' => $allowableSharingOptions,
				'allValues' => $allSharingOptions,
				'label' => 'Share With (PDFs Only)',
				'description' => 'With whom the file should be shared.',
				'onchange' => "return AspenDiscovery.Admin.toggleLibrarySharingOptions();",
			],
			'sharedWithLibrary' => [
				'property' => 'sharedWithLibrary',
				'type' => 'enum',
				'values' => $libraryListForSharing,
				'allValues' => $allLibraryList,
				'label' => 'Library to Share With',
				'description' => 'With which library this file is shared.',
			],
			'fullPath' => [
				'property' => 'fullPath',
				'type' => 'file',
				'label' => 'Full Path',
				'description' => 'The path of the file on the server.',
			],
			'thumbFullPath' => [
				'property' => 'thumbFullPath',
				'type' => 'text',
				'label' => 'Thumbnail Full Path',
				'description' => 'The path of the generated thumbnail on the server.',
				'readOnly' => true,
			],
		];
	}

	public function getFileName() {
		return basename($this->fullPath);
	}

	function insert($context = '') {
		$this->makeThumbnail();
		return parent::insert();
	}

	/**
	 * @return int|bool
	 */
	function update($context = '') {
		$this->makeThumbnail();
		return parent::update();
	}

	/** @noinspection PhpUnused */
	function makeThumbnail(): void {
		if ($this->type == 'web_builder_pdf' && !empty($this->fullPath)) {
			$destFullPath = $this->fullPath;
			if (extension_loaded('imagick')) {
				try {
					$thumb = new Imagick($destFullPath . '[0]');
					$thumb->setResolution(150, 150);
					$thumb->setImageBackgroundColor('white');
					$thumb->setImageAlphaChannel(11);
					$thumb->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
					//$thumb->readImage($destFullPath . '[0]');
					$wroteOk = $thumb->writeImage($destFullPath . '.jpg');
					$thumb->clear();
					if ($wroteOk) {
						$thumbFullPath = $destFullPath . '.jpg';
						$this->thumbFullPath = $thumbFullPath;
					}
				} catch (Exception $e) {
					global $logger;
					$logger->log("Imagick PDF thumbnail generation failed: " . $e, Logger::LOG_ERROR);
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
			$structure['fullPath']['readOnly'] = true;

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
}