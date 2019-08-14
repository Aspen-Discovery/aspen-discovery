<?php

class CloudLibraryScope extends DataObject
{
	public $__table = 'cloud_library_scopes';
	public $id;
	public $name;
	public $includeEAudiobook;
	public $includeEBooks;
	public $restrictToChildrensMaterial;

	public static function getObjectStructure()
	{
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'name' => array('property'=>'name', 'type'=>'text', 'label'=>'Name', 'description'=>'The Name of the scope', 'maxLength' => 50),
			'includeEAudiobook' => array('property'=>'includeEAudiobook', 'type'=>'checkbox', 'label'=>'Include eAudio books', 'description'=>'Whether or not EAudiobook are included', 'default'=>1),
			'includeEBooks' => array('property'=>'includeEBooks', 'type'=>'checkbox', 'label'=>'Include eBooks', 'description'=>'Whether or not EBooks are included', 'default'=>1),
			'restrictToChildrensMaterial' => array('property'=>'restrictToChildrensMaterial', 'type'=>'checkbox', 'label'=>'Include Children\'s Materials Only', 'description'=>'If checked only includes titles identified as children by RBdigital', 'default'=>0),
		);
		return $structure;
	}
}
