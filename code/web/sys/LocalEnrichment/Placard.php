<?php

require_once ROOT_DIR . '/sys/LocalEnrichment/PlacardTrigger.php';
class Placard extends DataObject
{
	public $__table = 'placards';
	public $id;
	public $title;
	public $body;
	public $image;
	public $css;

	//TODO: Which scopes should the Placard apply to
	//TODO: add additional triggers
	//TODO: Add a url to placards
	//TODO: Make placards dismissable so patrons can ignore specific ones

	static function getObjectStructure($availableFacets = null){
		$placardTriggerStructure = PlacardTrigger::getObjectStructure();
		unset($placardTriggerStructure['browseCategoryId']);

		return [
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id'),
			'title' => array('property'=>'title', 'type'=>'text', 'label'=>'Title', 'description'=>'The title of the placard'),
			'body' => array('property'=>'body', 'type'=>'html', 'label'=>'Body', 'description'=>'The body of the placard', 'allowableTags' => '<a><b><em><div><script><span><p><strong><sub><sup>', 'hideInLists' => true),
			'css' => array('property'=>'css', 'type'=>'textarea', 'label'=>'CSS', 'description'=>'Additional styling to apply to the placard', 'hideInLists' => true),
			'image' => array('property' => 'image', 'type' => 'image', 'label' => 'Image (800px x 150px max)', 'description' => 'The logo for use in the header', 'required' => false, 'maxWidth' => 800, 'maxHeight' => 150, 'hideInLists' => true),
			'triggers' => array(
				'property'=>'triggers',
				'type'=>'oneToMany',
				'label'=>'Triggers',
				'description'=>'Trigger words that will cause the placard to display',
				'keyThis' => 'id',
				'keyOther' => 'placardId',
				'subObjectType' => 'PlacardTrigger',
				'structure' => $placardTriggerStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),
		];
	}

	public function getSubCategories(){
		if (!isset($this->subBrowseCategories) && $this->id) {
			$this->subBrowseCategories     = array();
			$subCategory                   = new SubBrowseCategories();
			$subCategory->browseCategoryId = $this->id;
			$subCategory->orderBy('weight');
			$subCategory->find();
			while ($subCategory->fetch()) {
				$this->subBrowseCategories[$subCategory->id] = clone($subCategory);
			}
		}
		return $this->subBrowseCategories;
	}

	public function __get($name){
		if ($name == 'triggers') {
			$this->getTriggers();
			/** @noinspection PhpUndefinedFieldInspection */
			return $this->triggers;
		}else{
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == 'triggers') {
			/** @noinspection PhpUndefinedFieldInspection */
			$this->triggers = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveTriggers();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$triggers = new PlacardTrigger();
			$triggers->placardId = $this->id;
			$triggers->delete(true);
		}
	}

	public function saveTriggers(){
		if (isset ($this->triggers) && is_array($this->triggers)) {
			/** @var PlacardTrigger $trigger */
			foreach ($this->triggers as $trigger) {
				if (isset($trigger->deleteOnSave) && $trigger->deleteOnSave == true) {
					$trigger->delete();
				} else {
					if (isset($trigger->id) && is_numeric($trigger->id)) {
						$trigger->update();
					} else {
						$trigger->placardId = $this->id;
						$trigger->insert();
					}
				}
			}
			unset($this->triggers);
		}
	}

	public function getTriggers(){
		if (!isset($this->triggers) && $this->id) {
			$this->triggers = [];
			$trigger = new PlacardTrigger();
			$trigger->placardId = $this->id;
			$trigger->orderBy('triggerWord');
			$trigger->find();
			while ($trigger->fetch()) {
				$this->triggers[$trigger->id] = clone($trigger);
			}
		}
		return $this->triggers;
	}
}