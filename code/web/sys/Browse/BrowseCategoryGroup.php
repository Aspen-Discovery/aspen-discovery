<?php

require_once ROOT_DIR . '/sys/Browse/BrowseCategoryGroupEntry.php';

class BrowseCategoryGroup extends DataObject
{
	public $__table = 'browse_category_group';
	public $id;
	public $name;

	public $defaultBrowseMode;
	public $browseCategoryRatingsMode;

	private $_browseCategories;

	public static function getObjectStructure(){
		$browseCategoryStructure = BrowseCategoryGroupEntry::getObjectStructure();
		unset($browseCategoryStructure['weight']);
		unset($browseCategoryStructure['browseCategoryGroupId']);

		$structure = [
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The name of the group', 'maxLength' => 50, 'required' => true),
			'defaultBrowseMode' => array('property' => 'defaultBrowseMode', 'type' => 'enum', 'label'=>'Default Viewing Mode', 'description' => 'Sets how browse categories will be displayed when users haven\'t chosen themselves.', 'hideInLists' => true,
				'values'=> array('0' => 'Show Covers Only', '1' => 'Show as Grid'),
				'default' => '0'
			),
			'browseCategoryRatingsMode' => array('property' => 'browseCategoryRatingsMode', 'type' => 'enum', 'label' => 'Ratings Mode', 'description' => 'Sets how ratings will be displayed and how user ratings will be enabled when a user is viewing a browse category in the &#34;covers&#34; browse mode. These settings only apply when User Ratings have been enabled. (These settings will also apply to search results viewed in covers mode.)',
				'values' => array(
					'1' => 'Show rating stars and enable user rating via pop-up form.',
					'2' => 'Show rating stars and enable user ratings by clicking the stars.',
					'0' => 'Do not show rating stars.'
				),
				'default' => '1'
			),

			// The specific categories displayed in the carousel
			'browseCategories' => array(
				'property'=>'browseCategories',
				'type'=>'oneToMany',
				'label'=>'Browse Categories',
				'description'=>'Browse Categories To Show on the Home Screen',
				'keyThis' => 'id',
				'keyOther' => 'browseCategoryGroupId',
				'subObjectType' => 'BrowseCategoryGroupEntry',
				'structure' => $browseCategoryStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			),
		];

		return $structure;
	}

	public function __get($name)
	{
		if ($name == 'browseCategories') {
			return $this->getBrowseCategories();
		} else {
			return $this->_data[$name];
		}
	}

	public function getBrowseCategories()
	{
		if (!isset($this->_browseCategories) && $this->id) {
			$this->_browseCategories = array();
			$browseCategory = new BrowseCategoryGroupEntry();
			$browseCategory->browseCategoryGroupId = $this->id;
			$browseCategory->orderBy('weight');
			$browseCategory->find();
			while ($browseCategory->fetch()) {
				$this->_browseCategories[$browseCategory->id] = clone($browseCategory);
			}
		}
		return $this->_browseCategories;
	}

	public function __set($name, $value)
	{
		if ($name == 'browseCategories') {
			$this->_browseCategories = $value;
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
		//Updates to properly update settings based on the ILS
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveBrowseCategories();
		}

		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveBrowseCategories();
		}
		return $ret;
	}

	public function saveBrowseCategories(){
		if (isset ($this->_browseCategories) && is_array($this->_browseCategories)){
			$uniqueBrowseCategories = [];
			/**
			 * @var int $categoryId
			 * @var BrowseCategory $browseCategory
			 */
			foreach ($this->_browseCategories as $categoryId => $browseCategory){
				if (in_array($browseCategory->browseCategoryId, $uniqueBrowseCategories)){
					$browseCategory->delete();
					unset($this->_browseCategories[$categoryId]);
				}else{
					$uniqueBrowseCategories[] = $browseCategory->browseCategoryId;
				}
			}
			$this->saveOneToManyOptions($this->_browseCategories, 'browseCategoryGroupId');
			unset($this->_browseCategories);
		}
	}
}