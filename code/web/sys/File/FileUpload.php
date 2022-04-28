<?php

class FileUpload extends DataObject
{
	public $__table = 'file_uploads';
	public $id;
	public $title;
	public $fullPath;
	public $thumbFullPath;
	public $type;

	static function getObjectStructure() : array
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'title' => array('property' => 'title', 'type' => 'text', 'label' => 'Title', 'description' => 'The title of the page', 'size' => '40', 'maxLength'=>255),
			'type' => array('property' => 'type', 'type' => 'text', 'label' => 'Type', 'description' => 'The type of file being uploaded', 'maxLength' => 50),
			'fullPath' => array('property'=>'fullPath', 'type'=>'file', 'label'=>'Full Path', 'description'=>'The path of the file on the server'),
			'thumbFullPath' => array('property'=>'thumbFullPath', 'type' => 'text', 'label'=>'Thumbnail Full Path', 'description'=>'The path of the generated thumbnail on the server', 'serverValidation' => 'makeThumbnail', 'readOnly' => true),
		];
	}
	
	public function getFileName(){
		return basename($this->fullPath);
	}

	/** @noinspection PhpUnused */
	function makeThumbnail()
	{
		if($this->type == 'web_builder_pdf'){
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
						$thumb->writeImage($destFullPath . '.jpg');
						$thumb->destroy();
						$thumbFullPath = $destFullPath . '.jpg';
					}

					$this->thumbFullPath = $thumbFullPath;
					$this->update();
				} catch (Exception $e) {
					global $logger;
					$logger->log("Imagick not installed", $e);
				}
			}
			return $thumbFullPath;
		} else {
			die();
		}
	}

	public function okToExport(array $selectedFilters): bool
	{
		return true;
	}
}