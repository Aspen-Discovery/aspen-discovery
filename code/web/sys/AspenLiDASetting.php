<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';
require_once ROOT_DIR . '/sys/AspenLiDAQuickSearch.php';

class AspenLiDASetting extends DataObject
{
	public $__table = 'aspen_lida_settings';
	public $id;
	public $slugName;
	public $logoLogin;
	public $privacyPolicy;

	static function getObjectStructure() : array {
		$quickSearches = AspenLiDAQuickSearch::getObjectStructure();
		unset($quickSearches['aspenLidaSettingId']);

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'slugName' => array('property' => 'slugName', 'type' => 'text', 'label' => 'Slug Name', 'description' => 'The name for the app without spaces', 'maxLength' => 50, 'note' => 'Matches the slug in the app config', 'required' => true),
			'logoLogin' => array('property' => 'logoLogin', 'type' => 'image', 'label' => 'Logo for Login Screen', 'description' => 'The logo used on the login screen of the app', 'note' => '1024x1024 or 512x512 is the recommended image size', 'hideInLists' => true, 'required' => false, 'thumbWidth' => 512),
			'privacyPolicy' => array('property' => 'privacyPolicy', 'type' => 'text', 'label' => 'URL to Privacy Policy', 'description' => 'The web address for users to access the privacy policy for using the app', 'hideInLists' => true, 'required' => false),
			'quickSearches' => array(
				'property'      => 'quickSearches',
				'type'          => 'oneToMany',
				'label'         => 'Quick Searches',
				'description'   => 'Define quick searches for this app',
				'keyThis'       => 'aspenLidaSettingId',
				'keyOther'      => 'aspenLidaSettingId',
				'subObjectType' => 'AspenLiDAQuickSearch',
				'structure'     => $quickSearches,
				'sortable'      => true,
				'storeDb'       => true,
				'allowEdit'     => false,
				'canEdit'       => false,
				'hideInLists'   => true
			),
		);
		if (!UserAccount::userHasPermission('Administer Aspen LiDA Settings')){
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name){
		if ($name == 'quickSearches') {
			return $this->getQuickSearches();
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "quickSearches") {
			$this->_quickSearches = $value;
		}else {
			$this->_data[$name] = $value;
		}
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveQuickSearches();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveQuickSearches();
		}
		return $ret;
	}

	private $_quickSearches;
	public function setQuickSearches($value)
	{
		$this->_quickSearches = $value;
	}

	/**
	 * @return array|null
	 */
	public function getQuickSearches()
	{
		if (!isset($this->_quickSearches) && $this->id) {
			$this->_quickSearches = array();

			$quickSearches = new AspenLiDAQuickSearch();
			$quickSearches->aspenLidaSettingId = $this->id;
			if ($quickSearches->find()) {
				while ($quickSearches->fetch()) {
					$this->_quickSearches[$quickSearches->id] = clone $quickSearches;
				}
			}

		}
		return $this->_quickSearches;
	}

	public function saveQuickSearches(){
		if (isset ($this->_quickSearches) && is_array($this->_quickSearches)){
			$this->saveOneToManyOptions($this->_quickSearches, 'aspenLidaSettingId');
			unset($this->_quickSearches);
		}
	}
}