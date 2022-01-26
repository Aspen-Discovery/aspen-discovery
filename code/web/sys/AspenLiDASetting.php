<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class AspenLiDASetting extends DataObject
{
	public $__table = 'aspen_lida_settings';
	public $id;
	public $slugName;
	public $logoSplash;
	public $logoLogin;
	public $logoAppIcon;
	public $privacyPolicy;

	static function getObjectStructure() : array {

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'slugName' => array('property' => 'slugName', 'type' => 'text', 'label' => 'Slug Name', 'description' => 'The name for the app without spaces', 'maxLength' => 50, 'note' => 'Matches the slug in the app config', 'required' => true),
			'logoSplash' => array('property' => 'logoSplash', 'type' => 'image', 'label' => 'Logo for Splash/Loading Screen', 'description' => 'The logo used on the splash screen of the app', 'note' => '1024x1024 or 512x512 is the recommended image size. Transparency is allowed.', 'hideInLists' => true, 'required' => false, 'thumbWidth' => 512),
			'logoLogin' => array('property' => 'logoLogin', 'type' => 'image', 'label' => 'Logo for Login Screen', 'description' => 'The logo used on the login screen of the app', 'note' => '1024x1024 or 512x512 is the recommended image size. Transparency is allowed.', 'hideInLists' => true, 'required' => false, 'thumbWidth' => 512),
			'logoAppIcon' => array('property' => 'logoAppIcon', 'type' => 'image', 'label' => 'Logo for Login Screen', 'description' => 'The logo used on the login screen of the app', 'note' => '1024x1024 or 512x512 is the recommended image size', 'hideInLists' => true, 'required' => false, 'thumbWidth' => 512),
			'privacyPolicy' => array('property' => 'privacyPolicy', 'type' => 'text', 'label' => 'URL to Privacy Policy', 'description' => 'The web address for users to access the privacy policy for using the app', 'hideInLists' => true, 'required' => false),
		);
		if (!UserAccount::userHasPermission('Administer Aspen LiDA Settings')){
			unset($structure['libraries']);
		}
		return $structure;
	}
}