<?php
/**
 * Table Definition for library
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class ListWidgetListsLinks extends DB_DataObject
{
	public $__table = 'list_widget_lists_links';    // table name
	public $id; //int(11)
	public $listWidgetListsId;//int(11)
	public $name; //varchar(255)
	public $link; //text
	
	
	function keys() {
		return array('id');
	}
	
	static function getObjectStructure(){
		$structure = array(
				'id' => array(
						'property'=>'id',
						'type'=>'hidden',
						'label'=>'Id',
						'description'=>'The unique id of the list widget file.',
						'primaryKey' => true,
						'storeDb' => true
				),
				'weight' => array(
						'property' => 'weight',
						'type' => 'text',
						'label' => 'Weight',
						'description' => '',
						'required' => true,
						'storeDb' => true
				),
				'listWidgetId' => array(
						'property' => 'listWidgetListsId',
						'type' => 'text',
						'label' => 'List Widget List Id',
						'description' => 'The widget this list is associated with.',
						'required' => true,
						'storeDb' => true
				),
				'name' => array(
						'property'=>'name',
						'type'=>'text',
						'label'=>'Name',
						'description'=>'The name of the list to display in the tab.',
						'required' => true,
						'storeDb' => true
				),
				'link' => array(
						'property'=>'link',
						'type'=>'text',
						'label'=>'Link',
						'description'=>'The link of the list to display in the tab.',
						'required' => true,
						'storeDb' => true
				)
	
		);
		return $structure;
	}
	
}