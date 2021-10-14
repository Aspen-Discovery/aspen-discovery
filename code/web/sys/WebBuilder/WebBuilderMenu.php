<?php


class WebBuilderMenu extends DataObject
{
	public $__table = 'web_builder_menu';
	public $id;
	public $label;
	public $parentMenuId;
	public $url;
	public /** @noinspection PhpUnused */ $showWhen;
	public $libraryId;
	public $lastUpdate;

	public function getNumericColumnNames() : array
	{
		return ['parentMenuId'];
	}

	public static function getObjectStructure() : array{
		$parentMenuItems = [];
		$parentMenuItems[-1] = 'None';

		$menus = new WebBuilderMenu();
		$menus->parentMenuId = -1;
		$menus->find();
		while ($menus->fetch()){
			$parentMenuItems[$menus->id] = $menus->label;
		}

		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'label' => array('property' => 'label', 'type' => 'text', 'label' => 'Label', 'description' => 'The label of the menu item', 'size' => '40', 'maxLength'=>50),
			'parentMenuId' => array('property' => 'parentMenuId', 'type' => 'enum', 'values' => $parentMenuItems, 'label' => 'Parent Menu Item', 'description' => 'The parent of the menu item'),
			'weight' => array('property' => 'weight', 'type' => 'integer', 'label' => 'Weight', 'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.', 'required'=> true, 'default'=>0),
			'url' => array('property' => 'url', 'type' => 'text', 'label' => 'URL', 'description' => 'The URL to link to', 'maxLength' => 255),
			'showWhen' => ['property' => 'showWhen', 'type' => 'enum', 'values' => [0 => 'Always', 1 => 'When User is Logged In', 2 => 'When User is Logged Out'], 'label' => 'Show', 'description' => 'When the menu should be shown', 'default' => 0]
		];
	}

	/** @noinspection PhpUnused */
	public function getChildMenuItems(){
		$childItems = [];
		$childItem = new WebBuilderMenu();
		$childItem->parentMenuId = $this->id;
		$childItem->orderBy('weight ASC, label');
		$childItem->find();
		while ($childItem->fetch()){
			$childItems[$childItem->id] = clone($childItem);
		}
		return $childItems;
	}

	public function insert()
	{
		global $library;
		$this->libraryId = $library->libraryId;
		return parent::insert();
	}
}