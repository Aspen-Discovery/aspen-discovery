<?php
require_once ROOT_DIR . '/sys/WebBuilder/PortalCell.php';

class PortalRow extends DataObject
{
	public $__table = 'web_builder_portal_row';
	public $weight;
	public $id;
	public $portalPageId;
	public /** @noinspection PhpUnused */ $rowTitle;

	private $_cells;

	static function getObjectStructure() {
		$portalCellStructure = PortalCell::getObjectStructure();

		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id within the database'),
			'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines how items are sorted.  Lower weights are displayed higher.', 'required'=> true),
			'portalPageId' => array('property'=>'portalPageId', 'type'=>'label', 'label'=>'Portal Page', 'description'=>'The parent page'),
			'rowTitle' => array('property' => 'rowTitle', 'type' => 'text', 'label' => 'Row Title', 'description' => 'The title of the row (blank for none)', 'size' => '40', 'maxLength'=>100),

			'cells' => [
				'property'=>'cells',
				'type'=>'oneToMany',
				'label'=>'Cells',
				'description'=>'Cells to show within the row',
				'keyThis' => 'id',
				'keyOther' => 'portalRowId',
				'subObjectType' => 'PortalCell',
				'structure' => $portalCellStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
			],
		];
	}

	/** @noinspection PhpUnused */
	public function getEditLink(){
		return '/WebBuilder/PortalRows?objectAction=edit&id=' . $this->id;
	}

	public function __get($name)
	{
		if ($name == 'cells') {
			return $this->getCells();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == 'cells') {
			$this->_cells = $value;
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
			$this->saveCells();
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
			$this->saveCells();
		}
		return $ret;
	}

	public function delete($useWhere = false)
	{
		if ($useWhere == false) {
			foreach ($this->getCells() as $cell) {
				$cell->delete();
			}
		}
		return parent::delete($useWhere);
	}

	public function saveCells(){
		if (isset ($this->_cells) && is_array($this->_cells)){
			$this->saveOneToManyOptions($this->_cells, 'portalRowId');
			unset($this->_cells);
		}
	}

	public function getCells(){
		if (!isset($this->_cells) && $this->id){
			$this->_cells = [];
			$obj = new PortalCell();
			$obj->portalRowId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_cells[$obj->id] = clone $obj;
			}
		}
		return $this->_cells;
	}

	public function isLastRow(){
		$myPage = new PortalPage();
		$myPage->id = $this->portalPageId;
		if ($myPage->find(true)){
			return count($myPage->getRows()) -1 == $this->weight;
		}
		return false;
	}
}