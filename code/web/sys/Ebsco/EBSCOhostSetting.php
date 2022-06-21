<?php
require_once ROOT_DIR . '/sys/Ebsco/EBSCOhostSearchSetting.php';
/**
 * Class EBSCOhostSetting - Store settings for EBSCOhost
 */
class EBSCOhostSetting extends DataObject
{
	public $__table = 'ebscohost_settings';
	public $id;
	public $name;
	public $authType;
	public $profileId;
	public $profilePwd;
	public $ipProfileId;

	private $_searchSettings;

	static function getObjectStructure() : array {
		$ebscoHostSearchSettingStructure = EBSCOhostSearchSetting::getObjectStructure();

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'maxLength' => 50, 'description' => 'A name for these settings', 'required' => true),
			'authType' => array('property'=>'authType', 'type'=>'enum', 'label'=>'Profile or IP Authentication', 'values' => array('profile' => 'Profile Authentication', 'ip' => 'IP Authentication'), 'description'=>'If using IP Authentication or Profile Authentication'),
			'profileId' => array('property' => 'profileId', 'type' => 'text', 'label' => 'Profile Id', 'description' => 'The profile used for authentication. Required if using profile authentication.', 'hideInLists' => true),
			'profilePwd' => array('property' => 'profilePwd', 'type' => 'text', 'label' => 'Profile Password', 'description' => 'The password used for profile authentication. Required if using profile authentication.', 'hideInLists' => true),
			'ipProfileId' => array('property' => 'ipProfileId', 'type' => 'text', 'label' => 'IP Profile Id', 'description' => 'The IP profile used for authenication. Required if using IP authentication.', 'hideInLists' => true),
			'searchSettings' => array(
				'property' => 'searchSettings',
				'type' => 'oneToMany',
				'label' => 'Search Settings',
				'description' => 'Settings for Searching',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'EBSCOhostSearchSetting',
				'structure' => $ebscoHostSearchSettingStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true
			),
		);

		return $structure;
	}

	public function __get($name){
		if ($name == "searchSettings") {
			return $this->getSearchSettings();
		} else {
			return $this->_data[$name];
		}
	}

	/**
	 * @return EBSCOhostSearchSetting[]
	 */
	public function getSearchSettings() : array{
		if (!isset($this->_searchSettings) && $this->id){
			$this->_searchSettings = [];
			$obj = new EBSCOhostSearchSetting();
			$obj->settingId = $this->id;
			$obj->find();
			while($obj->fetch()){
				$this->_searchSettings[$obj->id] = clone($obj);
			}
		}
		return $this->_searchSettings;
	}

	public function __set($name, $value){
		if ($name == "searchSettings") {
			$this->_searchSettings = $value;
		}else {
			$this->_data[$name] = $value;
		}
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveSearchSettings();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveSearchSettings();
			//Create a default search settings
			$searchSettings = new EBSCOhostSearchSetting();
			$searchSettings->settingId = $this->id;
			$searchSettings->name = 'default';
			$searchSettings->insert();
		}
		return $ret;
	}

	public function saveSearchSettings(){
		if (isset ($this->_searchSettings) && is_array($this->_searchSettings)){
			$this->saveOneToManyOptions($this->_searchSettings, 'settingId');
			unset($this->_searchSettings);
		}
	}

	public function delete($useWhere = false)
	{
		$ret = parent::delete($useWhere);
		if ($ret) {
			$this->clearSearchSettings();
		}
		return $ret;
	}

	public function clearSearchSettings(){
		$this->clearOneToManyOptions('EBSCOhostSearchSetting', 'settingsId');
		$this->_searchSettings = array();
	}
}