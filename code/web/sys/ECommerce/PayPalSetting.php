<?php


class PayPalSetting extends DataObject
{
	public $__table = 'paypal_settings';
	public $id;
	public $name;
	public $sandboxMode;
	public $showPayLater;
	public $clientId;
	public $clientSecret;

	private $_libraries;

	static function getObjectStructure() : array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'A name for the settings', 'maxLength' => 50),
			'sandboxMode' => array('property'=>'sandboxMode', 'type'=>'checkbox', 'label'=>'Use PayPal Sandbox', 'description'=>'Whether or not to use PayPal in Sandbox mode', 'hideInLists' => false,),
			'showPayLater' => array('property'=>'showPayLater', 'type'=>'checkbox', 'label'=>'Show Pay Later', 'description'=>'Whether or not to allow users to use the Pay Later Option', 'hideInLists' => false, 'default'=>false),
			'clientId' => array('property'=>'clientId', 'type'=>'text', 'label'=>'ClientID', 'description'=>'The Client ID to use when paying fines.', 'hideInLists' => true, 'default' => '', 'size' => 80),
			'clientSecret' => array('property'=>'clientSecret', 'type'=>'storedPassword', 'label'=>'Client Secret', 'description'=>'The Client Secret to use when paying fines.', 'hideInLists' => true, 'default' => '', 'size' => 80),

			'libraries' => array(
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true
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
				$obj->payPalSettingId = $this->id;
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
					if ($library->payPalSettingId != $this->id){
						$library->finePaymentType = 2;
						$library->payPalSettingId = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->payPalSettingId == $this->id){
						if ($library->finePaymentType == 2) {$library->finePaymentType = 0;}
						$library->payPalSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}