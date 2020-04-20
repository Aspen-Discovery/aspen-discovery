<?php

class FileUpload extends DataObject
{
	public $__table = 'file_uploads';
	public $id;
	public $title;
	public $fullPath;
	public $type;

	static function getObjectStructure()
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'title' => array('property' => 'title', 'type' => 'text', 'label' => 'Title', 'description' => 'The title of the page', 'size' => '40', 'maxLength'=>255),
			'type' => array('property' => 'type', 'type' => 'text', 'label' => 'Type', 'description' => 'The type of file being uploaded', 'maxLength' => 50),
			'fullPath' => array('property'=>'fullPath', 'type'=>'file', 'label'=>'Full Path', 'description'=>'The path of the file on the server'),

		];
	}
}