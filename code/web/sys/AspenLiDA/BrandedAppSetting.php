<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class BrandedAppSetting extends DataObject {
	public $__table = 'aspen_lida_branded_settings';
	public $id;
	public $slugName;
	public $logoSplash;
	public $logoLogin;
	public $logoAppIcon;
	public $privacyPolicy;
	public $privacyPolicyContactAddress;
	public $privacyPolicyContactPhone;
	public $privacyPolicyContactEmail;
	public $showFavicons;
	public $logoNotification;
	public $appName;

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
				'label' => 'Logo for App Icon',
				'description' => 'The logo used as the app icon',
				'note' => '1024x1024 or 512x512 is the recommended image size',
				'hideInLists' => true,
				'required' => true,
				'thumbWidth' => 128,
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