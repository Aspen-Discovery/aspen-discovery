<?php


class LayoutSetting extends DataObject
{
	public $__table = 'layout_settings';
	public $id;
	public $name;

	public $useHomeLinkInBreadcrumbs;
	public $useHomeLinkForLogo;
	public $homeLinkText;
	public $browseLinkText;
	public $showLibraryHoursAndLocationsLink;
	public $useHomeLink;
	public $showBookIcon;
	public $showTopOfPageButton;
	public $dismissPlacardButtonLocation;
	public $dismissPlacardButtonIcon;
	public $contrastRatio;

	static function getObjectStructure() : array {
		$contrastOptions = [
			'4.50' => '4.5 (Default, WCAG 2.0 AA compliance)',
			'7.00' => '7.0 (WCAG 2.1 AAA compliance)'
		];

		return [
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'name' => array('property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The Name of the Settings', 'maxLength' => 50, 'required' => true),
			'useHomeLink' => array('property' => 'useHomeLink', 'type' => 'enum', 'label' => 'Where to use custom Home Link from Library Settings', 'values' => ['0' => 'Do not use', '1' => 'Use Home Link in Breadcrumbs and Menu', '2' => 'Use Home Link for Logo', '3' => 'Use Home Link for Breadcrumbs, Menu and Logo'], 'description' => 'x', 'default' => '0', 'onchange'=>'return AspenDiscovery.Admin.updateLayoutSettingsFields();'),
			'showBookIcon' => array('property'=>'showBookIcon', 'type'=>'checkbox', 'label'=>'Use <i class="fas fa-book-open fa-sm"></i> Book icon instead of <i class="fas fa-home fa-sm"></i> Home icon for catalog home link', 'description'=>'Whether or not the icon link for catalog home shows as a book or house.'),
			'homeLinkText' => array('property'=>'homeLinkText', 'type'=>'text', 'label'=>'Home Breadcrumb Link Text', 'description'=>'The text to show for the Home breadcrumb link', 'size'=>'40', 'default' => 'Home'),
			'browseLinkText' => array('property'=>'browseLinkText', 'type'=>'text', 'label'=>'Catalog Home Breadcrumb Link Text', 'description'=>'The text to show for the Catalog Home breadcrumb link', 'size'=>'40', 'default' => 'Browse'),
			'showLibraryHoursAndLocationsLink' => array('property'=>'showLibraryHoursAndLocationsLink', 'type'=>'checkbox', 'label'=>'Show Library Hours and Locations Link', 'description'=>'Whether or not the library hours and locations link is shown on the home page.', 'default' => true),
			'showTopOfPageButton' => array('property'=>'showTopOfPageButton', 'type'=>'checkbox', 'label'=>'Show Top of Page Button', 'description'=>'Whether or not to show button to go to top of page', 'default' => true),
			'dismissPlacardButtonLocation' => array('property'=>'dismissPlacardButtonLocation', 'type'=>'checkbox', 'label'=>'Show Dismiss Placard Button in Top Right Corner', 'description'=>'Whether or not to show dismiss button in the top right corner instead of the bottom right', 'default' => false),
			'dismissPlacardButtonIcon' => array('property'=>'dismissPlacardButtonIcon', 'type'=>'checkbox', 'label'=>'Show Dismiss Placard Button as <i class="fas fa-times"></i> (Close) Icon', 'description'=>'Whether or not to show icon instead of default dismiss placard text', 'default' => false),
			'contrastRatio' => array('property' => 'contrastRatio', 'type' => 'enum', 'values' => $contrastOptions, 'label' => 'Minimum contrast required for themes', 'default' => 4.50)
		];
	}
}