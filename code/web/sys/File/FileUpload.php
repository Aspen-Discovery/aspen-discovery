<?php

class FileUpload extends DataObject
{
	public $__table = 'file_uploads';
	public $id;
	public $title;
	public $fullPath;
	public $type;

	public function getFileName(){
		return basename($this->fullPath);
	}
}