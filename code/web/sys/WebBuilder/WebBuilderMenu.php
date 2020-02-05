<?php


class WebBuilderMenu extends DataObject
{
	public $__table = 'web_builder_menu';
	public $id;
	public $label;
	public $parentMenuId;
	public $url;

	public static function getObjectStructure(){
		$parentMenuItems = [];
		$parentMenuItems[-1] = 'None';

		$menus = new WebBuilderMenu();
		$menus->parentMenuId = -1;
		$menus->find();
		while ($menus->fetch()){
			$parentMenuItems[$menus->id] = $menus->label;
		}

		$structure = [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'label' => array('property' => 'label', 'type' => 'text', 'label' => 'Label', 'description' => 'The label of the menu item', 'size' => '40', 'maxLength'=>50),
			'parentMenuId' => array('property' => 'parentMenuId', 'type' => 'enum', 'values' => $parentMenuItems, 'label' => 'Parent Menu Item', 'description' => 'The parent of the menu item'),
			'url' => array('property' => 'url', 'type' => 'text', 'label' => 'URL', 'description' => 'The URL to link to', 'maxLength' => 255),
		];
		return $structure;
	}
}