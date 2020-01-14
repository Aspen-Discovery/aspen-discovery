<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/ListWidgetListsLinks.php';

class ListWidgetList extends DataObject
{
	public $__table = 'list_widget_lists';    // table name
	public $id;                      //int(25)
	public $listWidgetId;                    //varchar(255)
	public $name;
	public $displayFor;
	public $source;                    //varchar(255)
	public $weight;

	function keys()
	{
		return array('id');
	}

	static function getObjectStructure()
	{
		$structure = array(
			'id' => array(
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the list widget file.'
			),
			'listWidgetId' => array(
				'property' => 'listWidgetId',
				'type' => 'foreignKey',
				'label' => 'List Widget Id',
				'description' => 'The widget this list is associated with.'
			),
			'name' => array(
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the list to display in the tab.',
				'required' => true,
			),
			'displayFor' => array(
				'property' => 'displayFor',
				'type' => 'enum',
				'values' => array('all' => 'Everyone', 'loggedIn' => 'Only when a user is logged in', 'notLoggedIn' => 'Only when no one is logged in'),
				'label' => 'Display For',
				'description' => 'Who this list should be displayed for.'
			),
			'source' => array(
				'property' => 'source',
				'type' => 'text',
				'label' => 'Source',
				'description' => 'The source of the list.',
				'serverValidation' => 'validateSource',
				'required' => true
			),
			'links' => array(
				'property' => 'links',
				'type' => 'oneToMany',
				'keyThis' => 'id',
				'keyOther' => 'listWidgetListsId',
				'subObjectType' => 'ListWidgetListLinks',
				'structure' => ListWidgetListsLinks::getObjectStructure(),
				'label' => 'Links',
				'description' => 'The links to be displayed within the widget.',
				'sortable' => true,
				'storeDb' => true
			),
			/*'weight' => array(
				'property' => 'weight',
				'type' => 'integer',
				'label' => 'Weight',
				'weight' => 'Defines how lists are sorted within the widget.  Lower weights are displayed to the left of the screen.',
				'required'=> true
			),*/

		);
		return $structure;
	}

	public function __get($name)
	{
		if ($name == "links") {
			if (!isset($this->links)) {
				//Get the list of lists that are being displayed for the widget
				$this->links = array();
				$listWidgetListLinks = new ListWidgetListsLinks();
				$listWidgetListLinks->listWidgetListsId = $this->id;
				$listWidgetListLinks->orderBy('weight ASC');
				$listWidgetListLinks->find();
				while ($listWidgetListLinks->fetch()) {
					$this->links[$listWidgetListLinks->id] = clone($listWidgetListLinks);
				}
			}
			return $this->links;
		}
		return null;
	}

	function fullListLink()
	{
		require_once ROOT_DIR . '/services/API/ListAPI.php';
		$listAPI = new ListAPI();
		$cacheInfo = $listAPI->getCacheInfoForListId($this->source);
		//Get the widget for the list
		$widget = new ListWidget();
		$widget->id = $this->listWidgetId;
		if ($widget->find(true)) {
			if ($widget->viewMoreLinkMode == 'covers') {
				$cacheInfo['fullListLink'] .= '&view=covers';
			}
		}
		return $cacheInfo['fullListLink'];
	}


	function validateName()
	{
		//Setup validation return array
		$validationResults = array(
			'validatedOk' => true,
			'errors' => array(),
		);

		//TODO: Check to see if the name is unique

		//Make sure there aren't errors
		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}
		return $validationResults;
	}

	function __toString()
	{
		return "{$this->name} ($this->source)";
	}
}