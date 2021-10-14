<?php

/**
 * Class ProPaySetting - Store settings for ProPay
 */
class ProPaySetting extends DataObject
{
	public $__table = 'propay_settings';
	public $id;
	public $name;
	public $useTestSystem;
	public $authenticationToken;
	public $billerAccountId;
	public $merchantProfileId;
	public $certStr;
	public $accountNum;
	public $termId;

	private $_libraries;

	static function getObjectStructure() : array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings', 'maxLength' => 50),
			'useTestSystem' => array('property'=>'useTestSystem', 'type'=>'checkbox', 'label'=>'Use Test System', 'description'=>'Whether or not users to use ProPay test system', 'hideInLists' => true,),
			'authenticationToken' => array('property'=>'authenticationToken', 'type'=>'text', 'label'=>'Authentication Token', 'description'=>'The Authentication Token to use when paying fines.', 'hideInLists' => true, 'default' => '', 'maxLength' => 36),
			'billerAccountId' => array('property'=>'billerAccountId', 'type'=>'integer', 'label'=>'Biller Account ID', 'description'=>'The Biller Account ID to use when paying fines.', 'hideInLists' => true),
			'merchantProfileId' => array('property'=>'merchantProfileId', 'type'=>'integer', 'label'=>'Merchant Profile ID', 'description'=>'The Merchant Profile ID to use when paying fines.', 'hideInLists' => true),
			'certStr' => array('property'=>'certStr', 'type'=>'text', 'label'=>'Cert String', 'description'=>'The Cert String Provided by ProPay.', 'hideInLists' => true, 'maxLength' => 32),
			'accountNum' => array('property'=>'accountNum', 'type'=>'text', 'label'=>'Account Num', 'description'=>'The Account Number Provided by ProPay.', 'hideInLists' => true, 'maxLength' => 20),
			'termId' => array('property'=>'termId', 'type'=>'text', 'label'=>'Term Id', 'description'=>'The Terminal ID provided by ProPay.', 'hideInLists' => true, 'maxLength' => 20),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
				'forcesReindex' => true
			),
		);

		if (!UserAccount::userHasPermission('Library eCommerce Options')){
			unset($structure['libraries']);
		}
		return $structure;
	}

	function getNumericColumnNames() : array
	{
		return ['customerId'];
	}

	public function __get($name){
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->proPaySettingId = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value){
		if ($name == "libraries") {
			$this->_libraries = $value;
		}else {
			$this->_data[$name] = $value;
		}
	}

	public function update()
	{
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert()
	{
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
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
					if ($library->proPaySettingId != $this->id){
						$library->finePaymentType = 5;
						$library->proPaySettingId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->proPaySettingId == $this->id){
						if ($library->finePaymentType == 5) {$library->finePaymentType = 0;}
						$library->proPaySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}