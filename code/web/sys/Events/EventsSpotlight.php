<?php


class EventsSpotlight extends DataObject
{
	public $__table = 'events_spotlights';
	public $id;
	public $name;
	public $showNameAsTitle;
	public $description;
	public $showDescription;
	public $showEventImages;
	public $showEventDescriptions;
	public $searchTerm;
	public $defaultFilter;
	public $defaultSort;

	static function getObjectStructure() {
		return [
			'id' => ['property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'],
			'name' => ['property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name of the events spotlight', 'maxLength' => 255, 'required' => true],
			'showNameAsTitle' => ['property' => 'showNameAsTitle', 'type'=> 'checkbox', 'label' => 'Show Name as the Spotlight Title', 'description' => 'Whether or not the title is shown', 'default'=> 1],
			'description' => ['property' => 'description', 'type' => 'markdown', 'label' => 'Description', 'description'=>'A description for the spotlight', 'hideInLists' => true],
			'showDescription' => ['property' => 'showDescription', 'type'=> 'checkbox', 'label' => 'Show Spotlight Description', 'description' => 'Whether or not the description is shown', 'default'=> 0],
			'showEventImages' => ['property' => 'showEventImages', 'type'=> 'checkbox', 'label' => 'Show Images for Each Event', 'description' => 'Whether or not the event image is shown', 'default'=> 1],
			'showEventDescriptions' => ['property' => 'showDescription', 'type'=> 'checkbox', 'label' => 'Show Description for Each Event', 'description' => 'Whether or not the event description is shown', 'default'=> 1],
			'searchTerm' => array('property' => 'searchTerm', 'type' => 'text', 'label' => 'Search Term', 'description' => 'A default search term to apply to the category', 'default' => '', 'hideInLists' => true, 'maxLength' => 500),
			'defaultFilter' => array('property' => 'defaultFilter', 'type' => 'textarea', 'label' => 'Default Filter(s)', 'description' => 'Filters to apply to the search by default.', 'hideInLists' => true, 'rows' => 3, 'cols' => 80),
			'defaultSort' => array('property' => 'defaultSort', 'type' => 'enum', 'label' => 'Default Sort', 'values' => array('relevance' => 'Best Match', 'start_date_sort' => 'Start Date', 'title_sort' => 'Title'), 'description' => 'The default sort for the search if none is specified', 'default' => 'relevance', 'hideInLists' => true),
		];
	}
}