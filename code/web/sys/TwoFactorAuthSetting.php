<?php

class TwoFactorAuthSetting extends DataObject
{
	public $__table = 'two_factor_auth_settings';
	public $id;
	public $name;
	public $isEnabled;
	public $authMethod;
	public $deniedMessage;

	private $_libraries;
	private $_ptypes;

	static function getObjectStructure() : array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$ptypeList = PType::getPatronTypeList();

		$requiredList = array(
			'notAvailable' => 'No',
			'optional' => 'Yes, but optional',
			'mandatory' => 'Yes, and mandatory'
		);

		$authMethods = array(
			'email' => 'Email'
		);

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings', 'maxLength' => 50),
			'isEnabled' => array('property' => 'isEnabled', 'type' => 'enum', 'label' => 'Is Enabled', 'values' => $requiredList),
			'deniedMessage' => array('property'=>'deniedMessage', 'type'=>'textarea', 'label'=>'Denied access message', 'note' => 'Instructions for accessing their account if the user is unable to authenticate', 'hideInLists' => true),
			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
			),

			'ptypes' => array(
				'property' => 'ptypes',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Patron Types',
				'values' => $ptypeList,
				'description' => 'Define patron types that use these settings',
			),
		);

		if (!UserAccount::userHasPermission('Administer Two-Factor Authentication')){
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->twoFactorAuthSettingId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == 'ptypes') {
			if (!isset($this->_ptypes) && $this->id){
				$this->_ptypes = [];
				$obj = new PType();
				$obj->twoFactorAuthSettingId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_ptypes[$obj->id] = $obj->id;
				}
			}
			return $this->_ptypes;
		}else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == 'ptypes') {
			$this->_ptypes = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->savePatrons();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->savePatrons();
		}
		return $ret;
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName){
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)){
					//We want to apply the scope to this library
					if ($library->twoFactorAuthSettingId != $this->id){
						$library->twoFactorAuthSettingId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->twoFactorAuthSettingId == $this->id){
						$library->twoFactorAuthSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function savePatrons(){
		if (isset ($this->_ptypes) && is_array($this->_ptypes)){
			$ptypeList = PType::getPatronTypeList();
			foreach ($ptypeList as $ptype){
				$patron = new PType();
				$patron->pType = $ptype;
				$patron->find(true);
				if (in_array($patron->id, $this->_ptypes)){
					//We want to apply the scope to this patron
					if ($patron->twoFactorAuthSettingId != $this->id){
						$patron->twoFactorAuthSettingId = $this->id;
						$patron->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($patron->twoFactorAuthSettingId == $this->id){
						$patron->twoFactorAuthSettingId = -1;
						$patron->update();
					}
				}
			}
			unset($this->_ptypes);
		}
	}
}