<?php

class FileUpload extends DataObject {
	public $__table = 'file_uploads';
	public $id;
	public $title;
	public $fullPath;
	public $uploadedFileData;
	public $thumbFullPath;
	public $thumbnailFileData;
	public $type;

	public function getUniquenessFields(): array {
		return ['id'];
	}

	static function getObjectStructure($context = ''): array {
		global $serverName;
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
				'type' => 'db_file',
				'label' => 'Full Path',
				'description' => 'The path of the file on the server',
			],
			'thumbFullPath' => [
				'property' => 'thumbFullPath',
				'type' => 'text',
				'label' => 'Thumbnail Full Path',
				'description' => 'The path of the generated thumbnail on the server',
				'dirPath' => '/data/aspen-discovery/' . $serverName . '/uploads/web_builder_pdf/thumbnail',
				'readOnly' => true,
			],
		];
	}

	public function getFileName() {
		return $this->getFormatTitle($this->title) . ".pdf";
	}

	function insert($context = '') {
		$this->genPdfThumbnail();
		return parent::insert();
	}

	/**
	 * @return int|bool
	 */
	function update($context = '') {
		$this->genPdfThumbnail();
		return parent::update();
	}

	function genPdfThumbnail() {
		if ($this->type == 'web_builder_pdf'){
			$isWrote = 0;
			if (isset($this->uploadedFileData)){
				//Create tmpfile where will be store the blob temporarily
				$tmpDir = $this->tempdir();
				$tmpFullPath = $tmpDir . '/' . $this->getFormatTitle($this->title);
				//Store the blob into the tmpfile
				$isWrote = file_put_contents($tmpFullPath,$this->uploadedFileData);
			}
			if (extension_loaded('imagick') && $isWrote > 0) {
				$dirPath = self::getObjectStructure()['thumbFullPath']['dirPath'];
				if (!file_exists($dirPath)) {
					mkdir($dirPath, 0775, true);
					chgrp($dirPath, 'aspen_apache');
					chmod($dirPath, 0775);
				}
				if (substr($dirPath, -1) == '/') {
					$dirPath = substr($dirPath, 0, -1);
				}
				$target = $dirPath . '/' . $this->getFormatTitle($this->title);
				$im = new Imagick($tmpFullPath."[0]"); // 0-first page, 1-second page
				$im->setImageColorspace(255); // prevent image colors from inverting
				$im->setimageformat("jpg");
				$im->thumbnailimage(150, 150); // width and height
				$wroteOk = $im->writeimage($target);
				$im->clear();
				$im->destroy();
				if ($wroteOk) {
					$thumbFullPath = $target . '.jpg';
					$this->thumbFullPath = $thumbFullPath;
					$this->thumbnailFileData = file_get_contents($thumbFullPath);
					error_log('LGM SIZE : ' . print_r(strlen(file_get_contents($thumbFullPath)),true));
				}
			}
		}
	}



	public function okToExport(array $selectedFilters): bool {
		return true;
	}
}