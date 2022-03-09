<?php

require_once ROOT_DIR . '/sys/CourseReserves/CourseReserveLibraryMapValue.php';
class CourseReservesIndexingSettings extends DataObject
{
	public $__table = 'course_reserves_indexing_settings';    // table name
	public $id;
	public $runFullUpdate;
	public $lastUpdateOfChangedCourseReserves;
	public $lastUpdateOfAllCourseReserves;
	/** @var CourseReserveLibraryMapValue[] */
	public $_libraryMappings;

	public static function getObjectStructure() : array
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'runFullUpdate' => array('property' => 'runFullUpdate', 'type' => 'checkbox', 'label' => 'Run Full Update', 'description' => 'Whether or not a full update of all records should be done on the next pass of indexing', 'default' => 0),
			'lastUpdateOfChangedCourseReserves' => array('property' => 'lastUpdateOfChangedCourseReserves', 'type' => 'timestamp', 'label' => 'Last Update of Changed Course Reserves', 'description' => 'The timestamp when just changes were loaded', 'default' => 0),
			'lastUpdateOfAllCourseReserves' => array('property' => 'lastUpdateOfAllCourseReserves', 'type' => 'timestamp', 'label' => 'Last Update of All Course Reserves', 'description' => 'The timestamp when all course reserves were loaded', 'default' => 0),

			'libraryMappings' => array(
				'property' => 'libraryMappings',
				'type'=> 'oneToMany',
				'label' => 'Library Mappings',
				'description' => 'Translation for library names.',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'CourseReserveLibraryMapValue',
				'structure' => CourseReserveLibraryMapValue::getObjectStructure(),
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false
			),
		);
	}

	public function __get($name){
		if ($name == "libraryMappings") {
			return $this->getLibraryMappings();
		}
		return null;
	}

	public function getLibraryMappings() : ?array{
		if (!isset($this->_libraryMappings)){
			//Get the list of translation maps
			if ($this->id){
				$this->_libraryMappings = array();
				$value = new CourseReserveLibraryMapValue();
				$value->settingId = $this->id;
				$value->orderBy('value ASC');
				$value->find();
				while($value->fetch()){
					$this->_libraryMappings[$value->id] = clone($value);
				}
			}
		}
		return $this->_libraryMappings;
	}

	public function __set($name, $value){
		if ($name == "libraryMappings") {
			$this->_libraryMappings = $value;
		}
	}

	/**
	 * Override the update functionality to save the associated translation maps
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(){
		$ret = parent::update();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveLibraryMappings();
		}
		return true;
	}

	/**
	 * Override the update functionality to save the associated translation maps
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(){
		$ret = parent::insert();
		if ($ret === FALSE ){
			return $ret;
		}else{
			$this->saveLibraryMappings();
		}
		return true;
	}

	public function saveLibraryMappings(){
		if (isset ($this->_libraryMappings)){
			foreach ($this->_libraryMappings as $value){
				if ($value->_deleteOnSave == true){
					$value->delete();
				}else{
					if (isset($value->id) && is_numeric($value->id)){
						$value->update();
					}else{
						$value->settingId = $this->id;
						$value->insert();
					}
				}
			}
			//Clear the translation maps so they are reloaded the next time
			unset($this->_libraryMappings);
		}
	}

}