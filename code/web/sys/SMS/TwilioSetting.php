<?php

class TwilioSetting extends DataObject {
	public $__table = 'twilio_settings';
	public $id;
	public $name;
	public $phone;
	public $accountSid;
	public $authToken;

	private $_libraries;

	/**
	 * @return string[]
	 */
	function getEncryptedFieldNames(): array {
		return ['authToken'];
	}

	public static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings',
				'maxLength' => 50,
				'required' => true,
			],
			'phone' => [
				'property' => 'phone',
				'type' => 'text',
				'label' => 'Phone (no punctuation)',
				'maxLength' => 15,
				'required' => true,

			],
			'accountSid' => [
				'property' => 'accountSid',
				'type' => 'text',
				'label' => 'Account SID',
				'maxLength' => 50,
				'required' => true,

			],
			'authToken' => [
				'property' => 'authToken',
				'type' => 'storedPassword',
				'label' => 'Auth Token',
				'maxLength' => 50,
				'required' => false,
				'hideInLists' => true,

			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
			],
		];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->twilioSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->twilioSettingId != $this->id) {
						$library->twilioSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->twilioSettingId == $this->id) {
						$library->twilioSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function sendMessage($messageBody, $phoneNumber){
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$curlWrapper = new CurlWrapper();
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
			"Authorization: Basic " . base64_encode($this->accountSid . ':' . $this->authToken),
		];
		$curlWrapper->addCustomHeaders($headers, false);
		$postVariables = [
			'Body' => $messageBody,
			'From' => $this->phone,
			'To' => $phoneNumber
		];
		$result = $curlWrapper->curlPostPage("https://api.twilio.com/2010-04-01/Accounts/$this->accountSid/Messages.json", $postVariables);
		$jsonResponse = json_decode($result);
		if ($curlWrapper->getResponseCode() == 201) {
			return [
				'success' => true,
				'message' => 'The message was sent successfully'
			];
		} else {
			return [
				'success' => false,
				'message' => $jsonResponse->message
			];
		}
	}
}