<?php


class PlacardTrigger extends DataObject
{
	public $__table = 'placard_trigger';
	public $id;
	public $placardId;
	public $triggerWord;

	static function getObjectStructure(){
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the sub-category row within the database'),
			'triggerWord'    => array('property'=>'triggerWord', 'type'=>'text', 'label'=>'Trigger word', 'description'=>'The trigger used to cause the placard to display', 'maxLength' => 100, 'required' => true),
			'placardId' => array('property'=>'placardId', 'type'=>'label', 'label'=>'Placard', 'description'=>'The placard to display'),
		);
		return $structure;
	}
}