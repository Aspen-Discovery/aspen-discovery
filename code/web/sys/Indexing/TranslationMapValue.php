<?php

class TranslationMapValue extends DataObject{
	public $__table = 'translation_map_values';    // table name
	public $id;
	public $translationMapId;
	public $value;
	public $translation;

	public function __toString()
	{
		return "$this->value => $this->translation";
	}

	static function getObjectStructure() : array {
		return array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id within the database'),
			'translationMapId' => array('property' => 'translationMapId', 'type' => 'foreignKey', 'label' => 'Translation Map Id', 'description' => 'The Translation Map this is associated with'),
			'value' => array('property'=>'value', 'type'=>'text', 'label'=>'Value', 'description'=>'The value to be translated', 'maxLength' => '50', 'required' => true, 'forcesReindex' => true),
			'translation' => array('property'=>'translation', 'type'=>'text', 'label'=>'Translation', 'description'=>'The translated value', 'maxLength' => '255', 'required' => false, 'forcesReindex' => true),
		);
	}
}