<?php

class FirebaseSetting extends DataObject {
	public $__table = 'firebase_settings';
	public $id;
	public $name;
	public $firebaseAPIKey;
	public $firebaseAuthDomain;
	public $firebaseProjectID;
	public $firebaseStorageBucket;
	public $firebaseMessagingSenderID;
	public $firebaseAppID;
	public $serviceAccount;
	public $vapidKey;

	private $_libraries;

	function getEncryptedFieldNames(): array {
		return ['serviceAccount'];
	}

	static function getSettingsForCurrentLibrary(): FirebaseSetting | null {
		$library = Library::getActiveLibrary();
		return self::getSettingsForLibrary($library);
	}

	static function getSettingsForLibrary(?Library $library): FirebaseSetting | null {
		if ($library == null || empty($library->firebaseSettingId) || $library->firebaseSettingId == -1) {
			global $logger;
			$logger->log("No Firebase settings found for library or no library found", Logger::LOG_ERROR);
			return null;
		}
		return self::getSettingsById($library->firebaseSettingId);
	}

	static function getSettingsById($id): FirebaseSetting | null {
		if (empty($id) || $id == -1) {
			return null;
		}
		$settings = new FirebaseSetting();
		$settings->id = $id;
		if ($settings->find(true)) {
			return $settings;
		}
		global $logger;
		$logger->log("Firebase settings $id could not be found", Logger::LOG_ERROR);
		return null;
	}

	/**
	 * Push tokens can only be used with the Firebase project that issued them, so use the settings
	 * recorded on the token and fall back to the user's home library for older tokens.
	 */
	static function getSettingsForToken(UserNotificationToken $token): FirebaseSetting | null {
		if (!empty($token->firebaseSettingId) && $token->firebaseSettingId != -1) {
			$settings = self::getSettingsById($token->firebaseSettingId);
			if ($settings != null) {
				return $settings;
			}
		}
		$user = new User();
		$user->id = $token->userId;
		if ($user->find(true)) {
			return self::getSettingsForLibrary($user->getHomeLibrary());
		}
		global $logger;
		$logger->log("Firebase Settings for token & User for token could not be found.", Logger::LOG_ERROR);
		return null;
	}

	static function getObjectStructure($context = ''): array {
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
				'description' => 'A name for these settings',
				'maxLength' => 50,
				'required' => true,
			],
			'firebaseAPIKey' => [
				'property' => 'firebaseAPIKey',
				'type' => 'text',
				'label' => 'Firebase API Key',
				'note' => 'API key generated from within Firebase. (withing your firebase project on google cloud > APIs & Services > Credentials',
				'maxLength' => 50,
				'required' => true,
			],
			'firebaseAuthDomain' => [
				'property' => 'firebaseAuthDomain',
				'type' => 'text',
				'label' => 'Firebase Authorization Domain',
				'note' => 'This value also comes from your firebase config (https://support.google.com/firebase/answer/7015592#zippy=%2Cin-this-article)',
				'maxLength' => 50,
				'required' => true,
			],
			'firebaseProjectID' => [
				'property' => 'firebaseProjectID',
				'type' => 'text',
				'label' => 'Firebase project ID',
				'note' => 'This value also comes from your firebase config (https://support.google.com/firebase/answer/7015592#zippy=%2Cin-this-article)',
				'maxLength' => 50,
				'required' => true,
			],
			'firebaseStorageBucket' => [
				'property' => 'firebaseStorageBucket',
				'type' => 'text',
				'label' => 'Firebase Storage Bucket',
				'note' => 'This value also comes from your firebase config (https://support.google.com/firebase/answer/7015592#zippy=%2Cin-this-article)',
				'maxLength' => 50,
				'required' => true,
			],
			'firebaseMessagingSenderID' => [
				'property' => 'firebaseMessagingSenderID',
				'type' => 'text',
				'label' => 'Firebase Messaging Sender ID',
				'note' => 'This value also comes from your firebase config (https://support.google.com/firebase/answer/7015592#zippy=%2Cin-this-article)',
				'maxLength' => 50,
				'required' => true,
			],
			'firebaseAppID' => [
				'property' => 'firebaseAppID',
				'type' => 'text',
				'label' => 'Firebase application ID',
				'note' => 'This value also comes from your firebase config (https://support.google.com/firebase/answer/7015592#zippy=%2Cin-this-article)',
				'maxLength' => 50,
				'required' => true,
			],
			'vapidKey' => [
				'property' => 'vapidKey',
				'type' => 'text',
				'label' => 'Web Push Certificate (VapidKey)',
				'note' => 'This value is the public key of the key pair in the web configuration section of the cloud messaging tab in your firebase project. You may need to generate a new key pair if you do not already have one. (https://firebase.google.com/docs/cloud-messaging/web/get-started#configure_web_credentials_with_fcm)',
				'maxLength' => 100,
				'required' => true,
			],
			'serviceAccount' => [
				'property' => 'serviceAccount',
				'type' => 'file',
				'readContents' => true,
				'validExtensions' => ['json'],
				'accept' => '.json,application/json',
				'label' => 'Service Account',
				'note' => 'Service Account json file from firebase. Project settings > Service Accounts > Generate new private key, then upload the downloaded file here.',
				'required' => false,
				'hideInLists' => true,
				'serverValidation' => 'validateServiceAccount',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'note' => 'Define libraries that use these settings.',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->firebaseSettingId = $this->id;
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
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					if ($library->firebaseSettingId != $this->id) {
						$library->firebaseSettingId = $this->id;
						$library->update();
					}
				} else {
					if ($library->firebaseSettingId == $this->id) {
						$library->firebaseSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	function getFirebaseSettings(){
		//only the values needed by the front end to get a token
		return [
			'apiKey' => $this->firebaseAPIKey,
			'authDomain' =>$this->firebaseAuthDomain,
			'projectId' => $this->firebaseProjectID,
			'storageBucket' => $this->firebaseStorageBucket,
			'messagingSenderId' => $this->firebaseMessagingSenderID,
			'appId' => $this->firebaseAppID,
			'vapidKey' => $this->vapidKey
		];
	}

	/** @noinspection PhpUnused */
	function validateServiceAccount(): array {
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];
		if (empty($this->serviceAccount)) {
			return $validationResults;
		}
		$serviceAccount = $this->getServiceAccount();
		if (!is_array($serviceAccount) || ($serviceAccount['type'] ?? '') != 'service_account' || empty($serviceAccount['client_email']) || empty($serviceAccount['private_key'])) {
			$validationResults['errors'][] = translate([
				'text' => 'The Service Account file is not a valid service account key. Please upload the json file generated by Firebase.',
				'isAdminFacing' => true,
			]);
		} elseif (!empty($serviceAccount['project_id']) && !empty($this->firebaseProjectID) && $serviceAccount['project_id'] != $this->firebaseProjectID) {
			$validationResults['errors'][] = translate([
				'text' => 'The Service Account file is for project %1%, but the Firebase project ID is %2%.',
				1 => $serviceAccount['project_id'],
				2 => $this->firebaseProjectID,
				'isAdminFacing' => true,
			]);
		}
		$validationResults['validatedOk'] = empty($validationResults['errors']);
		return $validationResults;
	}

	function getServiceAccount() {
		return json_decode($this->serviceAccount, true);
	}
}
