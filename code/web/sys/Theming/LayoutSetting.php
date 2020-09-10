<?php


class LayoutSetting extends DataObject
{
	public $__table = 'layout_settings';
	public $id;
	public $name;

	public $sidebarMenuButtonText;
	public $useHomeLinkInBreadcrumbs;
	public $useHomeLinkForLogo;
	public $homeLinkText;
	public $showLibraryHoursAndLocationsLink;

	static function getObjectStructure(){
		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The Name of the Settings', 'maxLength' => 50, 'required' => true),
			'sidebarMenuButtonText' => array('property'=>'sidebarMenuButtonText', 'type'=>'text', 'label'=>'Sidebar Help Button Text', 'description'=>'The text to show for the help/menu button in the sidebar', 'size'=>'40', 'default' => 'Help'),
			'useHomeLinkInBreadcrumbs' => array('property'=>'useHomeLinkInBreadcrumbs', 'type'=>'checkbox', 'label'=>'Use Home Link in Breadcrumbs', 'description'=>'Whether or not the home link should be used in the breadcrumbs.'),
			'useHomeLinkForLogo' => array('property'=>'useHomeLinkForLogo', 'type'=>'checkbox', 'label'=>'Use Home Link for Logo', 'description'=>'Whether or not the home link should be used as the link for the main logo.'),
			'homeLinkText' => array('property'=>'homeLinkText', 'type'=>'text', 'label'=>'Home Link Text', 'description'=>'The text to show for the Home breadcrumb link', 'size'=>'40', 'default' => 'Home'),
			'showLibraryHoursAndLocationsLink' => array('property'=>'showLibraryHoursAndLocationsLink', 'type'=>'checkbox', 'label'=>'Show Library Hours and Locations Link', 'description'=>'Whether or not the library hours and locations link is shown on the home page.', 'default' => true),
		];
	}
}