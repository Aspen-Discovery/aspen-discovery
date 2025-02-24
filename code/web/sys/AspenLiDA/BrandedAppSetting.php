<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/DB/DataObject.php';

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
	public $showFavicons;
	public $logoNotification;
	public $appName;
	public $autoPickUserHomeLocation;

	//API Keys that are used instead of Greenhouse Settings if needed.
	public $apiKey1;
	public $apiKey2;
	public $apiKey3;
	public $apiKey4;
	public $apiKey5;

	static function getObjectStructure($context = ''): array {

		return [
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
		];
	}
/*
 * 		$address = '';
		$tel = '';
		$email = '';
		$location = new Location();
		$location->orderBy('isMainBranch desc'); // gets the main branch first or the first location
		$location->libraryId = $library->libraryId;
		if ($location->find(true)) {
			$address = preg_replace('/\r\n|\r|\n/', '<br>', $location->address);
			$tel = $location->phone;
			$email = $location->contactEmail;
		}
 */
}