<?php

/**
 * Class ACISpeedpaySetting - Store settings for ACI Speedpay
 */
class ACISpeedpaySetting extends DataObject
{
	public $__table = 'aci_speedpay_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $clientId;
	public $clientSecret;
	public $apiAuthKey;
	public $sdkClientId;
	public $sdkClientSecret;
	public $sdkApiAuthKey;
	public $billerId;
	public $billerAccountId;


	private $_libraries;

	static function getObjectStructure() : array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings', 'maxLength' => 50),
			'sandboxMode' => array('property'=>'sandboxMode', 'type'=>'checkbox', 'label'=>'Use ACI Sandbox', 'description'=>'Whether or not to use ACI Speedpay in Sandbox mode', 'hideInLists' => false, 'note'=>'This is for testing only! No funds will be received by the library.'),
			'clientId' => array('property' => 'clientId', 'type' => 'text', 'label' => 'Client ID', 'description' => 'Client identifier used for client authentication. This value is provided by ACI.', 'hideInLists' => true),
			'clientSecret' => array('property' => 'clientSecret', 'type' => 'storedPassword', 'label' => 'Client Secret', 'description' => 'Client API token used for client authentication. This value is provided by ACI.', 'hideInLists' => true),
			'apiAuthKey' => array('property' => 'apiAuthKey', 'type' => 'storedPassword', 'label' => 'API Auth Key', 'description' => 'The API key used to access the API. This value is provided by ACI.', 'hideInLists' => true),
			'sdkClientId' => array('property' => 'sdkClientId', 'type' => 'text', 'label' => 'SDK Client ID', 'description' => 'Client identifier used for SDK client authentication. This value is provided by ACI.', 'hideInLists' => true),
			'sdkClientSecret' => array('property' => 'sdkClientSecret', 'type' => 'storedPassword', 'label' => 'SDK Client Secret', 'description' => 'Client token used for SDK client authentication. This value is provided by ACI.', 'hideInLists' => true),
			'sdkApiAuthKey' => array('property' => 'sdkApiAuthKey', 'type' => 'storedPassword', 'label' => 'SDK API Auth Key', 'description' => 'The API key used to access the SDK. This value is provided by ACI.', 'hideInLists' => true),
			'billerId' => array('property' => 'billerId', 'type' => 'text', 'label' => 'Biller ID', 'description' => 'A unique identifier assigned by your ACI project manager.', 'hideInLists' => true),
			'billerAccountId' => array('property' => 'billerAccountId', 'type' => 'text', 'label' => 'Biller Account ID', 'description' => 'A biller account identifier assigned by a biller to an obligor.', 'hideInLists' => true),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
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
				$obj->aciSpeedpaySettingId = $this->id;
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
					if ($library->aciSpeedpaySettingId != $this->id){
						$library->finePaymentType = 8;
						$library->aciSpeedpaySettingId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->aciSpeedpaySettingId == $this->id){
						if ($library->finePaymentType == 8) {$library->finePaymentType = 0;}
						$library->aciSpeedpaySettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}