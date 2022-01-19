<?php

class AspenLiDASetting extends DataObject
{
	public $__table = 'aspen_lida_settings';
	public $id;
	public $slugName;

	static function getObjectStructure() : array {
		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'slugName' => array('property' => 'slugName', 'type' => 'text', 'label' => 'Slug Name', 'description' => 'A name for the app without spaces, i.e. "aspen-lida"', 'maxLength' => 50),
		);
		if (!UserAccount::userHasPermission('Administer Aspen LiDA Settings')){
			unset($structure['libraries']);
		}
		return $structure;
	}
}