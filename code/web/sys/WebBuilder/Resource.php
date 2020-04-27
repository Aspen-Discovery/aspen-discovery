<?php

class Resource extends DataObject
{
	public $__table = 'web_builder_resource';
	public $id;
	public $name;
	public $logo;
	public $url;
	public $featured;
	public $category;
	public $requiresLibraryCard;
	public $description;

	static function getObjectStructure()
	{
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name of the resource', 'size' => '40', 'maxLength'=>100),
			'url' => array('property' => 'url', 'type' => 'url', 'label' => 'URL', 'description' => 'The url of the resource', 'size' => '40', 'maxLength'=>255),
			'logo' => array('property' => 'logo', 'type' => 'image', 'label' => 'Logo', 'description' => 'An image to display for the resource', 'thumbWidth' => 200),
			'featured' => array('property' => 'featured', 'type' => 'checkbox', 'label' => 'Featured?', 'description' => 'Whether or not the resource is a featured resource', 'default'=>0),
			'category' => array('property' => 'category', 'type' => 'text', 'label' => 'Category', 'description' => 'The category of the resource', 'size' => '40', 'maxLength'=>100),
			'requiresLibraryCard' => array('property' => 'requiresLibraryCard', 'type' => 'checkbox', 'label' => 'Requires Library Card?', 'description' => 'Whether or not the resource requires a library card to use it', 'default'=>0),
			'description' => array('property' => 'description', 'type' => 'markdown', 'label' => 'Description', 'description' => 'A description of the resource', 'hideInLists' => true),
		];
	}

	public function getFormattedDescription()
	{
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		return $parsedown->parse($this->description);
	}
}