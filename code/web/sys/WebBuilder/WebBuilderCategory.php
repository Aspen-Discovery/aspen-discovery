<?php


class WebBuilderCategory extends DataObject
{
	public $__table = 'web_builder_category';
	public $__displayNameColumn = 'name';
	public $id;
	public $name;

	public function getUniquenessFields(): array
	{
		return ['name'];
	}

	public static function getObjectStructure() : array {
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings', 'required' => true, 'maxLength' => 100),
		);
	}

	public static function getCategories()
	{
		$categories = [];
		$category = new WebBuilderCategory();
		$category->orderBy('name');
		$category->find();
		while ($category->fetch()){
			$categories[$category->id] = $category->name;
		}
		return $categories;
	}

	public function okToExport(array $selectedFilters): bool
	{
		return true;
	}
}