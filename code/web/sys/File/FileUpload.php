<?php

class FileUpload extends DataObject {
	public $__table = 'file_uploads';
	public $id;
	public $title;
	public $fullPath;
	public $thumbFullPath;
	public $type;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title of the page',
				'size' => '40',
				'maxLength' => 255,
			],
			'type' => [
				'property' => 'type',
				'type' => 'text',
				'label' => 'Type',
				'description' => 'The type of file being uploaded',
				'maxLength' => 50,
			],
			'fullPath' => [
				'property' => 'fullPath',
				'type' => 'file',
				'label' => 'Full Path',
				'description' => 'The path of the file on the server',
			],
			'thumbFullPath' => [
				'property' => 'thumbFullPath',
				'type' => 'text',
				'label' => 'Thumbnail Full Path',
				'description' => 'The path of the generated thumbnail on the server',
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
	function makeThumbnail() {
		if ($this->type == 'web_builder_pdf' && !empty($this->fullPath)) {
			$destFullPath = $this->fullPath;
			$thumbFullPath = '';
			if (extension_loaded('imagick')) {
				try {
					$thumb = new Imagick($destFullPath);
					if ($thumb) {
						$thumb->setResolution(150, 150);
						$thumb->setImageBackgroundColor('white');
						$thumb->setImageAlphaChannel(11);
						$thumb->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
						$thumb->readImage($destFullPath . '[0]');
						$wroteOk = $thumb->writeImage($destFullPath . '.jpg');
						$thumb->destroy();
						if ($wroteOk) {
							$thumbFullPath = $destFullPath . '.jpg';
							$this->thumbFullPath = $thumbFullPath;
						}
					}
				} catch (Exception $e) {
					global $logger;
					$logger->log("Imagick not installed", $e);
				}
			}
		}
	}

	public function okToExport(array $selectedFilters): bool {
		return true;
	}
}