<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/AspenLiDA/LiDALoadingMessage.php';

class BrandedAppSetting extends DataObject {
	public $__table = 'aspen_lida_branded_settings';
	public $id;
	public $slugName;
	public $logoSplash;
	public $logoLogin;
	public $logoAppIcon;
	public $logoAppIconAndroid;
	public $privacyPolicy;
	public $privacyPolicyContactAddress;
	public $privacyPolicyContactPhone;
	public $privacyPolicyContactEmail;
	/** @noinspection PhpUnused */
	public $showFavicons;
	public $logoNotification;
	public $appName;
	public $autoPickUserHomeLocation;

	//API Keys that are used instead of Greenhouse Settings if needed.
	/** @noinspection PhpUnused */
	public $apiKey1;
	/** @noinspection PhpUnused */
	public $apiKey2;
	/** @noinspection PhpUnused */
	public $apiKey3;
	/** @noinspection PhpUnused */
	public $apiKey4;
	/** @noinspection PhpUnused */
	public $apiKey5;

	public $notificationAccessToken;

	public $loadingMessageType;
	public $_loadingMessages;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$lidaLoadingMessageStructure = LiDALoadingMessage::getObjectStructure($context);
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'appName' => [
				'property' => 'appName',
				'type' => 'text',
				'label' => 'App Name',
				'description' => 'The name for the app',
				'required' => true,
			],
			'slugName' => [
				'property' => 'slugName',
				'type' => 'text',
				'label' => 'Slug Name',
				'description' => 'The name for the app without spaces',
				'maxLength' => 50,
				'note' => 'Matches the slug in the app config',
				'required' => true,
			],
			'logoSplash' => [
				'property' => 'logoSplash',
				'type' => 'image',
				'label' => 'Logo for Splash/Loading Screen',
				'description' => 'The logo used on the splash screen of the app',
				'note' => '1024x1024 or 512x512 is the recommended image size. Transparency is allowed.',
				'hideInLists' => true,
				'required' => true,
				'thumbWidth' => 128,
			],
			'logoLogin' => [
				'property' => 'logoLogin',
				'type' => 'image',
				'label' => 'Logo for Login Screen',
				'description' => 'The logo used on the login screen of the app',
				'note' => '1024x1024 or 512x512 is the recommended image size. Transparency is allowed.',
				'hideInLists' => true,
				'required' => true,
				'thumbWidth' => 128,
			],
			'logoAppIcon' => [
				'property' => 'logoAppIcon',
				'type' => 'image',
				'label' => 'Icon for iOS App',
				'description' => 'The logo used as the app icon for the iOS application',
				'note' => '1024x1024 is the recommended image size. The icon should be square.',
				'hideInLists' => true,
				'required' => true,
				'thumbWidth' => 1024,
			],
			'logoAppIconAndroid' => [
				'property' => 'logoAppIconAndroid',
				'type' => 'image',
				'label' => 'Icon for Android App',
				'description' => 'The logo used as the app icon for the Android application',
				'note' => '512x512 is the recommended image size. Note this must be manually uploaded to the play store as well. Notify your support company when changing.',
				'hideInLists' => true,
				'required' => true,
				'thumbWidth' => 512,
			],
			'logoNotification' => [
				'property' => 'logoNotification',
				'type' => 'image',
				'label' => 'Logo for Notifications (Android Only)',
				'description' => 'The logo used as the notification icon for Android',
				'note' => 'Must be white on transparency, 96x96 pixels, SVG file type',
				'hideInLists' => true,
				'required' => true,
				'thumbWidth' => 96,
			],
			'privacyPolicyInformationSection' => [
				'property' => 'privacyPolicyInformationSection',
				'type' => 'section',
				'label' => 'Privacy Policy Information',
				'note' => 'By default the contact information is imported in from either the main branch or the first location (if no main branch).',
				'renderAsHeading' => true,
				'showBottomBorder' => true,
				'properties' => [
					'privacyPolicy' => [
						'property' => 'privacyPolicy',
						'type' => 'text',
						'label' => 'URL to Privacy Policy',
						'description' => 'The web address for users to access the privacy policy for using the app',
						'hideInLists' => true,
						'required' => true,
					],
					'privacyPolicyContactAddress' => [
						'property' => 'privacyPolicyContactAddress',
						'type' => 'textarea',
						'label' => 'Address',
						'description' => 'The address to list in the privacy policy',
						'hideInLists' => true,
					],
					'privacyPolicyContactPhone' => [
						'property' => 'privacyPolicyContactPhone',
						'type' => 'text',
						'label' => 'Phone Number',
						'maxLength' => '25',
						'description' => 'The phone number to list in the privacy policy',
						'hideInLists' => true,
					],
					'privacyPolicyContactEmail' => [
						'property' => 'privacyPolicyContactEmail',
						'type' => 'text',
						'label' => 'Email',
						'description' => 'The email address to list in the privacy policy',
						'hideInLists' => true,
					],
				],
			],
			'showFavicons' => [
				'property' => 'showFavicons',
				'type' => 'checkbox',
				'label' => 'Show favicons for each library at login',
				'description' => 'Whether or not to display favicons from the theme for each location on the Select Your Library modal when logging in',
				'hideInLists' => true,
				'required' => false,
			],
			'autoPickUserHomeLocation' => [
				'property' => 'autoPickUserHomeLocation',
				'type' => 'checkbox',
				'label' => 'Use User Home Location When Logging In',
				'description' => 'Whether or not to Aspen LiDA should log in the user based on their home location instead of prompting them to select one',
				'hideInLists' => true,
				'required' => false,
			],
			'notificationAccessToken' => [
				'property' => 'notificationAccessToken',
				'type' => 'storedPassword',
				'label' => 'Notification API Access Token',
				'description' => 'API key for authenticating access to Notification APIs',
				'canBatchUpdate' => false,
				'hideInLists' => true,
			],
			'apiKeySection' => [
				'property' => 'apiKeySection',
				'type' => 'section',
				'label' => 'API Keys (optional)',
				'instructions' => 'API Keys to use instead of API Keys within Greenhouse Settings. If API Keys are not provided, the keys in the greenhouse will be used.',
				'properties' => [
					'apiKey1' => [
						'property' => 'apiKey1',
						'type' => 'storedPassword',
						'label' => 'API Key 1',
						'description' => 'API key for authenticating LiDA access',
						'canBatchUpdate' => false,
						'hideInLists' => true,
					],
					'apiKey2' => [
						'property' => 'apiKey2',
						'type' => 'storedPassword',
						'label' => 'API Key 2',
						'description' => 'API key for authenticating LiDA access',
						'canBatchUpdate' => false,
						'hideInLists' => true,
					],
					'apiKey3' => [
						'property' => 'apiKey3',
						'type' => 'storedPassword',
						'label' => 'API Key 3',
						'description' => 'API key for authenticating LiDA access',
						'canBatchUpdate' => false,
						'hideInLists' => true,
					],
					'apiKey4' => [
						'property' => 'apiKey4',
						'type' => 'storedPassword',
						'label' => 'API Key 4',
						'description' => 'API key for authenticating LiDA access',
						'canBatchUpdate' => false,
						'hideInLists' => true,
					],
					'apiKey5' => [
						'property' => 'apiKey5',
						'type' => 'storedPassword',
						'label' => 'API Key 5',
						'description' => 'API key for authenticating LiDA access',
						'canBatchUpdate' => false,
						'hideInLists' => true,
					],
				]
			],
			'loadingMessagesSection' => [
				'property' => 'loadingMessagesSection',
				'type' => 'section',
				'label' => 'Loading Messages',
				'instructions' => 'How messages should be displayed to the patron while LiDA is loading.',
				'properties' => [
					'loadingMessageType' => [
						'property' => 'loadingMessageType',
						'type' => 'enum',
						'label' => 'Loading Message Type',
						'values' => [
							'0' => 'Show library facts',
							'1' => 'Show step being performed',
							'2' => 'Show random loading message'
						]
					],
					'loadingMessages' => [
						'property' => 'loadingMessages',
						'type' => 'oneToMany',
						'label' => 'Loading Messages',
						'description' => 'Custom Messages that will be shown at random',
						'keyThis' => 'id',
						'keyOther' => 'brandedAppSettingId',
						'subObjectType' => 'LiDALoadingMessage',
						'structure' => $lidaLoadingMessageStructure,
						'sortable' => false,
						'storeDb' => true,
						'allowEdit' => true,
						'canEdit' => false,
						'canAddNew' => true,
						'canDelete' => true,
					],
				]
			]
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "loadingMessages") {
			return $this->getLoadingMessages();
		} else {
			return parent::__get($name);
		}
	}

	public function getLoadingMessages(): ?array {
		if (!isset($this->_loadingMessages) && $this->id) {
			$this->_loadingMessages = [];
			$obj = new LiDALoadingMessage();
			$obj->brandedAppSettingId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_loadingMessages[$obj->id] = clone $obj;
			}
		}
		return $this->_loadingMessages;
	}

	public function __set($name, $value) {
		if ($name == "loadingMessages") {
			$this->_loadingMessages = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLoadingMessages();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLoadingMessages();
		}
		return $ret;
	}

	public function delete(bool $useWhere = false, bool $hardDelete = false) : bool|int {
		$ret = parent::delete($useWhere, $hardDelete);
		if ($ret && !empty($this->id)) {
			$loadingMessage = new LiDALoadingMessage();
			$loadingMessage->brandedAppSettingId = $this->id;
			$loadingMessage->delete(true);
		}
		return $ret;
	}

	public function saveLoadingMessages() : void {
		if (isset ($this->_loadingMessages) && is_array($this->_loadingMessages)) {
			$this->saveOneToManyOptions($this->_loadingMessages, 'brandedAppSettingId');
			unset($this->_loadingMessages);
		}
	}
}