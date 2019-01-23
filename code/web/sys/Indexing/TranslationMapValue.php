<?php
/**
 * The actual values for a Translation Map
 *
 * @category VuFind-Plus-2014 
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/30/2015
 * Time: 1:44 PM
 */

class TranslationMapValue extends DB_DataObject{
	public $__table = 'translation_map_values';    // table name
	public $id;
	public $translationMapId;
	public $value;
	public $translation;

	function getObjectStructure(){
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id within the database'),
			'translationMapId' => array('property' => 'translationMapId', 'type' => 'foreignKey', 'label' => 'Translation Map Id', 'description' => 'The Translation Map this is associated with'),
			'value' => array('property'=>'value', 'type'=>'text', 'label'=>'Value', 'description'=>'The value to be translated', 'maxLength' => '50', 'required' => true),
			'translation' => array('property'=>'translation', 'type'=>'text', 'label'=>'Translation', 'description'=>'The translated value', 'maxLength' => '255', 'required' => false),
		);
		return $structure;
	}
}