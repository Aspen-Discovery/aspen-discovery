<?php
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Theme extends DataObject
{
	public $__table = 'themes';
	public $id;
	public $themeName;
	public $extendsTheme;
	public $logoName;
	public $favicon;

	public $headerBackgroundColor;
	public $headerBackgroundColorDefault;
	public $headerForegroundColor;
	public $headerForegroundColorDefault;
	//TODO: Delete header bottom border color from settings?
//    public $headerBottomBorderColor;
//    public $headerBottomBorderColorDefault;

	public $headerButtonRadius;
	public $headerButtonColor;
	public $headerButtonColorDefault;
	public $headerButtonBackgroundColor;
	public $headerButtonBackgroundColorDefault;
	public $headerBottomBorderWidth;

	public $pageBackgroundColor;
	public $pageBackgroundColorDefault;
	public $bodyBackgroundColor;
	public $bodyBackgroundColorDefault;
	public $bodyTextColor;
	public $bodyTextColorDefault;

	public $footerLogo;
	public $footerLogoLink;
	public $footerBackgroundColor;
	public $footerBackgroundColorDefault;
	public $footerForegroundColor;
	public $footerForegroundColorDefault;

	//Primary color is used for the header bar and menu bar
	public $primaryBackgroundColor;
	public $primaryBackgroundColorDefault;
	public $primaryForegroundColor;
	public $primaryForegroundColorDefault;

	//Secondary color is used for selections like browse category
	public $secondaryBackgroundColor;
	public $secondaryBackgroundColorDefault;
	public $secondaryForegroundColor;
	public $secondaryForegroundColorDefault;

	//Tertiary color is used for selections like browse category
	public $tertiaryBackgroundColor;
	public $tertiaryBackgroundColorDefault;
	public $tertiaryForegroundColor;
	public $tertiaryForegroundColorDefault;

	public $buttonRadius;
	public $smallButtonRadius;
	//Colors for buttons
	public $defaultButtonBackgroundColor;
	public $defaultButtonBackgroundColorDefault;
	public $defaultButtonForegroundColor;
	public $defaultButtonForegroundColorDefault;
	public $defaultButtonBorderColor;
	public $defaultButtonBorderColorDefault;
	public $defaultButtonHoverBackgroundColor;
	public $defaultButtonHoverBackgroundColorDefault;
	public $defaultButtonHoverForegroundColor;
	public $defaultButtonHoverForegroundColorDefault;
	public $defaultButtonHoverBorderColor;
	public $defaultButtonHoverBorderColorDefault;

	public $primaryButtonBackgroundColor;
	public $primaryButtonBackgroundColorDefault;
	public $primaryButtonForegroundColor;
	public $primaryButtonForegroundColorDefault;
	public $primaryButtonBorderColor;
	public $primaryButtonBorderColorDefault;
	public $primaryButtonHoverBackgroundColor;
	public $primaryButtonHoverBackgroundColorDefault;
	public $primaryButtonHoverForegroundColor;
	public $primaryButtonHoverForegroundColorDefault;
	public $primaryButtonHoverBorderColor;
	public $primaryButtonHoverBorderColorDefault;

	public $actionButtonBackgroundColor;
	public $actionButtonBackgroundColorDefault;
	public $actionButtonForegroundColor;
	public $actionButtonForegroundColorDefault;
	public $actionButtonBorderColor;
	public $actionButtonBorderColorDefault;
	public $actionButtonHoverBackgroundColor;
	public $actionButtonHoverBackgroundColorDefault;
	public $actionButtonHoverForegroundColor;
	public $actionButtonHoverForegroundColorDefault;
	public $actionButtonHoverBorderColor;
	public $actionButtonHoverBorderColorDefault;

	public $infoButtonBackgroundColor;
	public $infoButtonBackgroundColorDefault;
	public $infoButtonForegroundColor;
	public $infoButtonForegroundColorDefault;
	public $infoButtonBorderColor;
	public $infoButtonBorderColorDefault;
	public $infoButtonHoverBackgroundColor;
	public $infoButtonHoverBackgroundColorDefault;
	public $infoButtonHoverForegroundColor;
	public $infoButtonHoverForegroundColorDefault;
	public $infoButtonHoverBorderColor;
	public $infoButtonHoverBorderColorDefault;

	public $warningButtonBackgroundColor;
	public $warningButtonBackgroundColorDefault;
	public $warningButtonForegroundColor;
	public $warningButtonForegroundColorDefault;
	public $warningButtonBorderColor;
	public $warningButtonBorderColorDefault;
	public $warningButtonHoverBackgroundColor;
	public $warningButtonHoverBackgroundColorDefault;
	public $warningButtonHoverForegroundColor;
	public $warningButtonHoverForegroundColorDefault;
	public $warningButtonHoverBorderColor;
	public $warningButtonHoverBorderColorDefault;

	public $dangerButtonBackgroundColor;
	public $dangerButtonBackgroundColorDefault;
	public $dangerButtonForegroundColor;
	public $dangerButtonForegroundColorDefault;
	public $dangerButtonBorderColor;
	public $dangerButtonBorderColorDefault;
	public $dangerButtonHoverBackgroundColor;
	public $dangerButtonHoverBackgroundColorDefault;
	public $dangerButtonHoverForegroundColor;
	public $dangerButtonHoverForegroundColorDefault;
	public $dangerButtonHoverBorderColor;
	public $dangerButtonHoverBorderColorDefault;

	//Sidebar Menu
	public $sidebarHighlightBackgroundColor;
	public $sidebarHighlightBackgroundColorDefault;
	public $sidebarHighlightForegroundColor;
	public $sidebarHighlightForegroundColorDefault;

	//Browse Category Colors
	public $browseCategoryPanelColor;
	public $browseCategoryPanelColorDefault;
	public $selectedBrowseCategoryBackgroundColor;
	public $selectedBrowseCategoryBackgroundColorDefault;
	public $selectedBrowseCategoryForegroundColor;
	public $selectedBrowseCategoryForegroundColorDefault;
	public $selectedBrowseCategoryBorderColor;
	public $selectedBrowseCategoryBorderColorDefault;
	public $deselectedBrowseCategoryBackgroundColor;
	public $deselectedBrowseCategoryBackgroundColorDefault;
	public $deselectedBrowseCategoryForegroundColor;
	public $deselectedBrowseCategoryForegroundColorDefault;
	public $deselectedBrowseCategoryBorderColor;
	public $deselectedBrowseCategoryBorderColorDefault;
	public $capitalizeBrowseCategories;

	//Panel Colors
	public $closedPanelBackgroundColor;
	public $closedPanelBackgroundColorDefault;
	public $closedPanelForegroundColor;
	public $closedPanelForegroundColorDefault;
	public $openPanelBackgroundColor;
	public $openPanelBackgroundColorDefault;
	public $openPanelForegroundColor;
	public $openPanelForegroundColorDefault;

	//Fonts
	public $headingFont;
	public $headingFontDefault;
	public $customHeadingFont;
	public $bodyFont;
	public $bodyFontDefault;
	public $customBodyFont;

	public $additionalCssType;
	public $additionalCss;

	public $generatedCss;

	private $_libraries;
	private $_locations;

	static function getObjectStructure()
	{
		$libraryList = Library::getLibraryList();
		$locationList = Location::getLocationList();

		//Load Valid Fonts
		$validHeadingFonts = [
			'Arial',
			'Catamaran',
			'Gothic A1',
			'Gothic A1-Black',
			'Helvetica',
			'Helvetica Neue',
			'Josefin Sans',
			'Lato',
			'Montserrat',
			'Noto Sans',
			'Open Sans',
			'PT Sans',
			'Raleway',
			'Roboto',
			'Source Sans Pro',
			'Ubuntu',
		];
		$validBodyFonts = [
			'Arial',
			'Droid Serif',
			'Gothic A1',
			'Gothic A1-Black',
			'Helvetica',
			'Helvetica Neue',
			'Josefin Sans',
			'Lato',
			'Montserrat',
			'Noto Sans',
			'Open Sans',
			'Open Sans Condensed',
			'Playfair Display',
			'PT Sans',
			'Raleway',
			'Roboto',
			'Roboto Condensed',
			'Roboto Slab',
			'Source Sans Pro',
			'Ubuntu',
		];

		$themesToExtend = [];
		$themesToExtend[''] = 'None';
		$theme = new Theme();
		$theme->find();
		while ($theme->fetch()){
			$themesToExtend[$theme->themeName] = $theme->themeName;
		}

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id', 'uniqueProperty' => true),
			'themeName' => array('property' => 'themeName', 'type' => 'text', 'label' => 'Theme Name', 'description' => 'The Name of the Theme', 'maxLength' => 50, 'required' => true, 'uniqueProperty' => true),
			'extendsTheme' => array('property' => 'extendsTheme', 'type' => 'enum', 'values' => $themesToExtend, 'label' => 'Extends Theme', 'description' => 'A theme that this overrides (leave blank if none is overridden)', 'maxLength' => 50, 'required' => false),
			'logoName' => array('property' => 'logoName', 'type' => 'image', 'label' => 'Logo (500px x 100px max)', 'description' => 'The logo for use in the header', 'required' => false, 'maxWidth' => 500, 'maxHeight' => 100, 'hideInLists' => true),
			'favicon' => array('property' => 'favicon', 'type' => 'image', 'label' => 'favicon (32px x 32px max)', 'description' => 'The icon for use in the tab', 'required' => false, 'maxWidth' => 32, 'maxHeight' => 32, 'hideInLists' => true),
			//Overall page colors
			'pageBackgroundColor' => array('property' => 'pageBackgroundColor', 'type' => 'color', 'label' => 'Page Background Color', 'description' => 'Page Background Color behind all content', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
			'bodyBackgroundColor' => array('property' => 'bodyBackgroundColor', 'type' => 'color', 'label' => 'Body Background Color', 'description' => 'Body Background Color for main content', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
			'bodyTextColor' => array('property' => 'bodyTextColor', 'type' => 'color', 'label' => 'Body Text Color', 'description' => 'Body Text Color for main content', 'required' => false, 'hideInLists' => true, 'default' => '#6B6B6B'),

			//Header Colors
			'headerBackgroundColor' => array('property' => 'headerBackgroundColor', 'type' => 'color', 'label' => 'Header Background Color', 'description' => 'Header Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#f1f1f1'),
			'headerForegroundColor' => array('property' => 'headerForegroundColor', 'type' => 'color', 'label' => 'Header Text Color', 'description' => 'Header Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#8b8b8b'),
			'headerBottomBorderWidth' => array('property' => 'headerBottomBorderWidth', 'type' => 'text', 'label' => 'Header Bottom Border Width', 'description' => 'Header Bottom Border Width', 'required' => false, 'hideInLists' => true),
			//Header Buttons
			'headerButtonRadius' => array('property' => 'headerButtonRadius', 'type' => 'text', 'label' => 'Header Button Radius', 'description' => 'Header Button Radius', 'required' => false, 'hideInLists' => true),
			'headerButtonColor' => array('property' => 'headerButtonColor', 'type' => 'color', 'label' => 'Header Button Color', 'description' => 'Header Button Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
			'headerButtonBackgroundColor' => array('property' => 'headerButtonBackgroundColor', 'type' => 'color', 'label' => 'Header Button Background Color', 'description' => 'Header Button Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#848484'),

			//Footer Colors
			'footerBackgroundColor' => array('property' => 'footerBackgroundColor', 'type' => 'color', 'label' => 'Footer Background Color', 'description' => 'Footer Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#f1f1f1'),
			'footerForegroundColor' => array('property' => 'footerForegroundColor', 'type' => 'color', 'label' => 'Footer Text Color', 'description' => 'Footer Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#8b8b8b'),
			'footerImage' => array('property' => 'footerLogo', 'type' => 'image', 'label' => 'Footer Image (250px x 150px max)', 'description' => 'An image to be displayed in the footer', 'required' => false, 'maxWidth' => 250, 'maxHeight' => 150, 'hideInLists' => true),
			'footerImageLink' => array('property' => 'footerLogoLink', 'type' => 'url', 'label' => 'Footer Image Link', 'description' => 'A link to be added to the footer logo', 'required' => false, 'hideInLists' => true),
			//Primary Color
			'primaryBackgroundColor' => array('property' => 'primaryBackgroundColor', 'type' => 'color', 'label' => 'Primary Background Color', 'description' => 'Primary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#0a7589'),
			'primaryForegroundColor' => array('property' => 'primaryForegroundColor', 'type' => 'color', 'label' => 'Primary Text Color', 'description' => 'Primary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),

			//Secondary Color
			'secondaryBackgroundColor' => array('property' => 'secondaryBackgroundColor', 'type' => 'color', 'label' => 'Secondary Background Color', 'description' => 'Secondary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#de9d03'),
			'secondaryForegroundColor' => array('property' => 'secondaryForegroundColor', 'type' => 'color', 'label' => 'Secondary Text Color', 'description' => 'Secondary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),

			//Tertiary Color
			'tertiaryBackgroundColor' => array('property' => 'tertiaryBackgroundColor', 'type' => 'color', 'label' => 'Tertiary Background Color', 'description' => 'Tertiary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#de1f0b'),
			'tertiaryForegroundColor' => array('property' => 'tertiaryForegroundColor', 'type' => 'color', 'label' => 'Tertiary Text Color', 'description' => 'Tertiary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),

			'headingFont' => array('property' => 'headingFont', 'type' => 'font', 'label' => 'Heading Font', 'description' => 'Heading Font', 'validFonts' => $validHeadingFonts, 'previewFontSize' => '20px', 'required' => false, 'hideInLists' => true, 'default' => 'Ubuntu'),
			'customHeadingFont' => array('property' => 'customHeadingFont', 'type' => 'uploaded_font', 'label' => 'Custom Heading Font', 'description' => 'Upload a custom font to use for headings', 'required' => false, 'hideInLists' => true),
			'bodyFont' => array('property' => 'bodyFont', 'type' => 'font', 'label' => 'Body Font', 'description' => 'Body Font', 'validFonts' => $validBodyFonts, 'previewFontSize' => '14px', 'required' => false, 'hideInLists' => true, 'default' => 'Lato'),
			'customBodyFont' => array('property' => 'customBodyFont', 'type' => 'uploaded_font', 'label' => 'Custom Body Font', 'description' => 'Upload a custom font to use for the body', 'required' => false, 'hideInLists' => true),

			//Additional CSS
			'additionalCss' => array('property' => 'additionalCss', 'type' => 'textarea', 'label' => 'Additional CSS', 'description' => 'Additional CSS to apply to the interface', 'required' => false, 'hideInLists' => true),
			'additionalCssType' => array('property' => 'additionalCssType', 'type' => 'enum', 'values' => ['0' => 'Append to parent css', '1' => 'Override parent css'], 'label' => 'Additional CSS Application', 'description' => 'How to apply css to the theme', 'required' => false, 'default' => 0, 'hideInLists' => true),

			//Menu
			'sidebarHighlightBackgroundColor' => array('property' => 'sidebarHighlightBackgroundColor', 'type' => 'color', 'label' => 'Sidebar Highlight Background Color', 'description' => 'Sidebar Highlight Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#16ceff'),
			'sidebarHighlightForegroundColor' => array('property' => 'sidebarHighlightForegroundColor', 'type' => 'color', 'label' => 'Sidebar Highlight Text Color', 'description' => 'Sidebar Highlight Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),

			//Browse category theming
			'browseCategorySection' =>['property'=>'browseCategorySection', 'type' => 'section', 'label' =>'Browse Categories', 'hideInLists' => true, 'properties' => [
				'browseCategoryPanelColor' => array('property' => 'browseCategoryPanelColor', 'type' => 'color', 'label' => 'Browse Category Panel Color', 'description' => 'Background Color of the Browse Category Panel', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB'),

				'selectedBrowseCategoryBackgroundColor' => array('property' => 'selectedBrowseCategoryBackgroundColor', 'type' => 'color', 'label' => 'Selected Browse Category Background Color', 'description' => 'Selected Browse Category Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB'),
				'selectedBrowseCategoryForegroundColor' => array('property' => 'selectedBrowseCategoryForegroundColor', 'type' => 'color', 'label' => 'Selected Browse Category Text Color', 'description' => 'Selected Browse Category Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
				'selectedBrowseCategoryBorderColor' => array('property' => 'selectedBrowseCategoryBorderColor', 'type' => 'color', 'label' => 'Selected Browse Category Border Color', 'description' => 'Selected Browse Category Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB'),

				'deselectedBrowseCategoryBackgroundColor' => array('property' => 'deselectedBrowseCategoryBackgroundColor', 'type' => 'color', 'label' => 'Deselected Browse Category Background Color', 'description' => 'Deselected Browse Category Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB'),
				'deselectedBrowseCategoryForegroundColor' => array('property' => 'deselectedBrowseCategoryForegroundColor', 'type' => 'color', 'label' => 'Deselected Browse Category Text Color', 'description' => 'Deselected Browse Category Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
				'deselectedBrowseCategoryBorderColor' => array('property' => 'deselectedBrowseCategoryBorderColor', 'type' => 'color', 'label' => 'Deselected Browse Category Border Color', 'description' => 'Deselected Browse Category Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB'),

				'capitalizeBrowseCategories' => array('property' => 'capitalizeBrowseCategories', 'type' => 'enum', 'values'=> [-1 => 'Default', 0 => 'Maintain case', 1 => 'Force Uppercase'], 'label' => 'Capitalize Browse Categories', 'description' => 'How to treat capitalization of browse categories', 'required' => false, 'hideInLists' => true, 'default' => '-1'),
			]],

			'panels' => ['property'=>'panelsSection', 'type' => 'section', 'label' =>'Panels', 'hideInLists' => true, 'properties' => [
				'closedPanelBackgroundColor' => array('property' => 'closedPanelBackgroundColor', 'type' => 'color', 'label' => 'Closed Panel Background Color', 'description' => 'Panel Background Color while closed', 'required' => false, 'hideInLists' => true, 'default' => '#e7e7e7'),
				'closedPanelForegroundColor' => array('property' => 'closedPanelForegroundColor', 'type' => 'color', 'label' => 'Closed Panel Text Color', 'description' => 'Panel Foreground Color while closed', 'required' => false, 'hideInLists' => true, 'default' => '#333333'),
				'openPanelBackgroundColor' => array('property' => 'openPanelBackgroundColor', 'type' => 'color', 'label' => 'Open Panel Background Color', 'description' => 'Panel Category Background Color while open', 'required' => false, 'hideInLists' => true, 'default' => '#4DACDE'),
				'openPanelForegroundColor' => array('property' => 'openPanelForegroundColor', 'type' => 'color', 'label' => 'Open Panel Text Color', 'description' => 'Panel Category Foreground Color while open', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
			]],

			'buttonSection' =>['property'=>'buttonSection', 'type' => 'section', 'label' =>'Buttons', 'hideInLists' => true, 'properties' => [
				'buttonRadius'  => array('property' => 'buttonRadius', 'type' => 'text', 'label' => 'Button Radius', 'description' => 'Button Radius', 'required' => false, 'hideInLists' => true),
				'smallButtonRadius'  => array('property' => 'smallButtonRadius', 'type' => 'text', 'label' => 'Small Button Radius', 'description' => 'Small Button Radius', 'required' => false, 'hideInLists' => true),

				'defaultButtonSection' =>['property'=>'defaultButtonSection', 'type' => 'section', 'label' =>'Default Button', 'hideInLists' => true, 'properties' => [
					'defaultButtonBackgroundColor' => array('property' => 'defaultButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Button Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'defaultButtonForegroundColor' => array('property' => 'defaultButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Button Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#333333'),
					'defaultButtonBorderColor' => array('property' => 'defaultButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Button Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#cccccc'),
					'defaultButtonHoverBackgroundColor' => array('property' => 'defaultButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Button Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#ebebeb'),
					'defaultButtonHoverForegroundColor' => array('property' => 'defaultButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Button Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#333333'),
					'defaultButtonHoverBorderColor' => array('property' => 'defaultButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Button Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#adadad'),
				]],
				'primaryButtonSection' =>['property'=>'primaryButtonSection', 'type' => 'section', 'label' =>'Primary Button', 'hideInLists' => true, 'properties' => [
					'primaryButtonBackgroundColor' => array('property' => 'primaryButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#428bca'),
					'primaryButtonForegroundColor' => array('property' => 'primaryButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'primaryButtonBorderColor' => array('property' => 'primaryButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#357ebd'),
					'primaryButtonHoverBackgroundColor' => array('property' => 'primaryButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#3276b1'),
					'primaryButtonHoverForegroundColor' => array('property' => 'primaryButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'primaryButtonHoverBorderColor' => array('property' => 'primaryButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'primary' => '#285e8e'),
				]],

				'actionButtonSection' =>['property'=>'actionButtonSection', 'type' => 'section', 'label' =>'Action Button (Place hold, checkout, access online, etc)', 'hideInLists' => true, 'properties' => [
					'actionButtonBackgroundColor' => array('property' => 'actionButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'action' => '#428bca'),
					'actionButtonForegroundColor' => array('property' => 'actionButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'actionButtonBorderColor' => array('property' => 'actionButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#357ebd'),
					'actionButtonHoverBackgroundColor' => array('property' => 'actionButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#3276b1'),
					'actionButtonHoverForegroundColor' => array('property' => 'actionButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'actionButtonHoverBorderColor' => array('property' => 'actionButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#285e8e'),
				]],

				'infoButtonSection' =>['property'=>'infoButtonSection', 'type' => 'section', 'label' =>'Info Button', 'hideInLists' => true, 'properties' => [
					'infoButtonBackgroundColor' => array('property' => 'infoButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#5bc0de'),
					'infoButtonForegroundColor' => array('property' => 'infoButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'infoButtonBorderColor' => array('property' => 'infoButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#46b8da'),
					'infoButtonHoverBackgroundColor' => array('property' => 'infoButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#39b3d7'),
					'infoButtonHoverForegroundColor' => array('property' => 'infoButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'infoButtonHoverBorderColor' => array('property' => 'infoButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#269abc'),
				]],

				'warningButtonSection' =>['property'=>'warningButtonSection', 'type' => 'section', 'label' =>'Warning Button', 'hideInLists' => true, 'properties' => [
					'warningButtonBackgroundColor' => array('property' => 'warningButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#f0ad4e'),
					'warningButtonForegroundColor' => array('property' => 'warningButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'warningButtonBorderColor' => array('property' => 'warningButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#eea236'),
					'warningButtonHoverBackgroundColor' => array('property' => 'warningButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#ed9c28'),
					'warningButtonHoverForegroundColor' => array('property' => 'warningButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'warningButtonHoverBorderColor' => array('property' => 'warningButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#d58512'),
				]],

				'dangerButtonSection' =>['property'=>'dangerButtonSection', 'type' => 'section', 'label' =>'Danger Button', 'hideInLists' => true, 'properties' => [
					'dangerButtonBackgroundColor' => array('property' => 'dangerButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#d9534f'),
					'dangerButtonForegroundColor' => array('property' => 'dangerButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'dangerButtonBorderColor' => array('property' => 'dangerButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#d43f3a'),
					'dangerButtonHoverBackgroundColor' => array('property' => 'dangerButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#d2322d'),
					'dangerButtonHoverForegroundColor' => array('property' => 'dangerButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
					'dangerButtonHoverBorderColor' => array('property' => 'dangerButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#ac2925'),
				]],
			]],

			'librariesAndLocationsSettings' =>['property'=>'librariesAndLocationsSettings', 'type' => 'section', 'label' =>'Libraries and Locations', 'hideInLists' => true, 'properties' => [
				'libraries' => array(
					'property' => 'libraries',
					'type' => 'multiSelect',
					'listStyle' => 'checkboxSimple',
					'label' => 'Libraries',
					'description' => 'Define libraries that use this browse category group',
					'values' => $libraryList,
				),

				'locations' => array(
					'property' => 'locations',
					'type' => 'multiSelect',
					'listStyle' => 'checkboxSimple',
					'label' => 'Locations',
					'description' => 'Define locations that use this browse category group',
					'values' => $locationList,
				),
			]]
		);
		return $structure;
	}

	public function insert()
	{
		$this->generatedCss = $this->generateCss($this->getAllAppliedThemes());
		$this->clearDefaultCovers();
		$ret = parent::insert();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function update()
	{
		$this->generatedCss = $this->generateCss($this->getAllAppliedThemes());
		$this->clearDefaultCovers();
		$ret = parent::update();
		if ($ret !== FALSE ){
			$this->saveLibraries();
			$this->saveLocations();
		}

		//Check to see what has been derived from this theme and regenerate CSS for those themes as well
		$childTheme = new Theme();
		$childTheme->extendsTheme = $this->themeName;
		$childTheme->find();
		while ($childTheme->fetch()){
			if ($childTheme->id != $this->id) {
				$childTheme->update();
			}
		}
		return $ret;
	}

	/**
	 * @param Theme[] $allAppliedThemes an array of themes that have been applied in order of inheritance
	 *
	 * @return string the resulting css
	 */
	public function generateCss($allAppliedThemes)
	{
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
		$additionalCSS = '';
		$appendCSS = true;
		foreach ($allAppliedThemes as $theme) {
			if ($interface->getVariable('headerBackgroundColor') == null && !$theme->headerBackgroundColorDefault) {
				$interface->assign('headerBackgroundColor', $theme->headerBackgroundColor);
			}
			if ($interface->getVariable('headerForegroundColor') == null && !$theme->headerForegroundColorDefault) {
				$interface->assign('headerForegroundColor', $theme->headerForegroundColor);
			}

			if ($interface->getVariable('headerBottomBorderWidth') == null && $theme->headerBottomBorderWidth != null) {
				$interface->assign('headerBottomBorderWidth', $theme->headerBottomBorderWidth);
			}

			if ($interface->getVariable('headerButtonRadius') == null && !empty($theme->headerButtonRadius)) {
				$interface->assign('headerButtonRadius', $theme->headerButtonRadius);
			}
			if ($interface->getVariable('headerButtonColor') == null && !$theme->headerButtonColorDefault) {
				$interface->assign('headerButtonColor', $theme->headerButtonColor);
			}
			if ($interface->getVariable('headerButtonBackgroundColor') == null && !$theme->headerButtonBackgroundColorDefault) {
				$interface->assign('headerButtonBackgroundColor', $theme->headerButtonBackgroundColor);
			}
			if ($interface->getVariable('pageBackgroundColor') == null && !$theme->pageBackgroundColorDefault) {
				$interface->assign('pageBackgroundColor', $theme->pageBackgroundColor);
			}

			if ($interface->getVariable('footerBackgroundColor') == null && !$theme->footerBackgroundColorDefault) {
				$interface->assign('footerBackgroundColor', $theme->footerBackgroundColor);
			}
			if ($interface->getVariable('footerForegroundColor') == null && !$theme->footerForegroundColorDefault) {
				$interface->assign('footerForegroundColor', $theme->footerForegroundColor);
			}
			
			if ($interface->getVariable('primaryBackgroundColor') == null && !$theme->primaryBackgroundColorDefault) {
				$interface->assign('primaryBackgroundColor', $theme->primaryBackgroundColor);
				$lightened80 = ColorUtils::lightenColor($theme->primaryBackgroundColor, 1.8);
				$interface->assign('primaryBackgroundColorLightened80', $lightened80);
				$lightened60 = ColorUtils::lightenColor($theme->primaryBackgroundColor, 1.6);
				$interface->assign('primaryBackgroundColorLightened60', $lightened60);
			}
			if ($interface->getVariable('primaryForegroundColor') == null && !$theme->primaryForegroundColorDefault) {
				$interface->assign('primaryForegroundColor', $theme->primaryForegroundColor);
			}
			if ($interface->getVariable('secondaryBackgroundColor') == null && !$theme->secondaryBackgroundColorDefault) {
				$interface->assign('secondaryBackgroundColor', $theme->secondaryBackgroundColor);
			}
			if ($interface->getVariable('secondaryForegroundColor') == null && !$theme->secondaryForegroundColorDefault) {
				$interface->assign('secondaryForegroundColor', $theme->secondaryForegroundColor);
			}
			if ($interface->getVariable('tertiaryBackgroundColor') == null && !$theme->tertiaryBackgroundColorDefault) {
				$interface->assign('tertiaryBackgroundColor', $theme->tertiaryBackgroundColor);
			}
			if ($interface->getVariable('tertiaryForegroundColor') == null && !$theme->tertiaryForegroundColorDefault) {
				$interface->assign('tertiaryForegroundColor', $theme->tertiaryForegroundColor);
			}
			if ($interface->getVariable('bodyBackgroundColor') == null && !$theme->bodyBackgroundColorDefault) {
				$interface->assign('bodyBackgroundColor', $theme->bodyBackgroundColor);
			}
			if ($interface->getVariable('bodyTextColor') == null && !$theme->bodyTextColorDefault) {
				$interface->assign('bodyTextColor', $theme->bodyTextColor);
			}
			if ($interface->getVariable('headingFont') == null && !$theme->headingFontDefault) {
				$interface->assign('headingFont', $theme->headingFont);
			}
			if ($interface->getVariable('bodyFont') == null && !$theme->bodyFontDefault) {
				$interface->assign('bodyFont', $theme->bodyFont);
			}
			if ($interface->getVariable('customHeadingFont') == null && ($theme->customHeadingFont != null)) {
				$interface->assign('customHeadingFont', $theme->customHeadingFont);
				//Strip off the extension to get the name of the font
				$customHeadingFontName = substr($theme->customHeadingFont, 0, strrpos($theme->customHeadingFont, '.'));
				$interface->assign('customHeadingFontName', $customHeadingFontName);

				$interface->assign('headingFont', $customHeadingFontName);
			}
			if ($interface->getVariable('customBodyFont') == null && ($theme->customBodyFont != null)) {
				$interface->assign('customBodyFont', $theme->customBodyFont);
				$customBodyFontName = substr($theme->customBodyFont, 0, strrpos($theme->customBodyFont, '.'));
				$interface->assign('customBodyFontName', $customBodyFontName);

				$interface->assign('bodyFont', $customBodyFontName);
			}

			if ($interface->getVariable('sidebarHighlightBackgroundColor') == null && !$theme->sidebarHighlightBackgroundColorDefault) {
				$interface->assign('sidebarHighlightBackgroundColor', $theme->sidebarHighlightBackgroundColor);
			}
			if ($interface->getVariable('sidebarHighlightForegroundColor') == null && !$theme->sidebarHighlightForegroundColorDefault) {
				$interface->assign('sidebarHighlightForegroundColor', $theme->sidebarHighlightForegroundColor);
			}

			if ($interface->getVariable('browseCategoryPanelColor') == null && !$theme->browseCategoryPanelColorDefault) {
				$interface->assign('browseCategoryPanelColor', $theme->browseCategoryPanelColor);
			}
			if ($interface->getVariable('selectedBrowseCategoryBackgroundColor') == null && !$theme->selectedBrowseCategoryBackgroundColorDefault) {
				$interface->assign('selectedBrowseCategoryBackgroundColor', $theme->selectedBrowseCategoryBackgroundColor);
			}
			if ($interface->getVariable('selectedBrowseCategoryForegroundColor') == null && !$theme->selectedBrowseCategoryForegroundColorDefault) {
				$interface->assign('selectedBrowseCategoryForegroundColor', $theme->selectedBrowseCategoryForegroundColor);
			}
			if ($interface->getVariable('selectedBrowseCategoryBorderColor') == null && !$theme->selectedBrowseCategoryBorderColorDefault) {
				$interface->assign('selectedBrowseCategoryBorderColor', $theme->selectedBrowseCategoryBorderColor);
			}
			if ($interface->getVariable('deselectedBrowseCategoryBackgroundColor') == null && !$theme->deselectedBrowseCategoryBackgroundColorDefault) {
				$interface->assign('deselectedBrowseCategoryBackgroundColor', $theme->deselectedBrowseCategoryBackgroundColor);
			}
			if ($interface->getVariable('deselectedBrowseCategoryForegroundColor') == null && !$theme->deselectedBrowseCategoryForegroundColorDefault) {
				$interface->assign('deselectedBrowseCategoryForegroundColor', $theme->deselectedBrowseCategoryForegroundColor);
			}
			if ($interface->getVariable('deselectedBrowseCategoryBorderColor') == null && !$theme->deselectedBrowseCategoryBorderColorDefault) {
				$interface->assign('deselectedBrowseCategoryBorderColor', $theme->deselectedBrowseCategoryBorderColor);
			}
			if ($interface->getVariable('capitalizeBrowseCategories') == null && $theme->capitalizeBrowseCategories != -1) {
				$interface->assign('capitalizeBrowseCategories', $theme->capitalizeBrowseCategories);
			}

			if ($interface->getVariable('closedPanelBackgroundColor') == null && !$theme->closedPanelBackgroundColorDefault) {
				$interface->assign('closedPanelBackgroundColor', $theme->closedPanelBackgroundColor);
			}
			if ($interface->getVariable('closedPanelForegroundColor') == null && !$theme->closedPanelForegroundColorDefault) {
				$interface->assign('closedPanelForegroundColor', $theme->closedPanelForegroundColor);
			}
			if ($interface->getVariable('openPanelBackgroundColor') == null && !$theme->openPanelBackgroundColorDefault) {
				$interface->assign('openPanelBackgroundColor', $theme->openPanelBackgroundColor);
			}
			if ($interface->getVariable('openPanelForegroundColor') == null && !$theme->openPanelForegroundColorDefault) {
				$interface->assign('openPanelForegroundColor', $theme->openPanelForegroundColor);
			}

			if ($interface->getVariable('defaultButtonBackgroundColor') == null && !$theme->defaultButtonBackgroundColorDefault) {
				$interface->assign('defaultButtonBackgroundColor', $theme->defaultButtonBackgroundColor);
			}
			if ($interface->getVariable('defaultButtonForegroundColor') == null && !$theme->defaultButtonForegroundColorDefault) {
				$interface->assign('defaultButtonForegroundColor', $theme->defaultButtonForegroundColor);
			}
			if ($interface->getVariable('defaultButtonBorderColor') == null && !$theme->defaultButtonBorderColorDefault) {
				$interface->assign('defaultButtonBorderColor', $theme->defaultButtonBorderColor);
			}
			if ($interface->getVariable('defaultButtonHoverBackgroundColor') == null && !$theme->defaultButtonHoverBackgroundColorDefault) {
				$interface->assign('defaultButtonHoverBackgroundColor', $theme->defaultButtonHoverBackgroundColor);
			}
			if ($interface->getVariable('defaultButtonHoverForegroundColor') == null && !$theme->defaultButtonHoverForegroundColorDefault) {
				$interface->assign('defaultButtonHoverForegroundColor', $theme->defaultButtonHoverForegroundColor);
			}
			if ($interface->getVariable('defaultButtonHoverBorderColor') == null && !$theme->defaultButtonHoverBorderColorDefault) {
				$interface->assign('defaultButtonHoverBorderColor', $theme->defaultButtonHoverBorderColor);
			}

			if ($interface->getVariable('primaryButtonBackgroundColor') == null && !$theme->primaryButtonBackgroundColorDefault) {
				$interface->assign('primaryButtonBackgroundColor', $theme->primaryButtonBackgroundColor);
			}
			if ($interface->getVariable('primaryButtonForegroundColor') == null && !$theme->primaryButtonForegroundColorDefault) {
				$interface->assign('primaryButtonForegroundColor', $theme->primaryButtonForegroundColor);
			}
			if ($interface->getVariable('primaryButtonBorderColor') == null && !$theme->primaryButtonBorderColorDefault) {
				$interface->assign('primaryButtonBorderColor', $theme->primaryButtonBorderColor);
			}
			if ($interface->getVariable('primaryButtonHoverBackgroundColor') == null && !$theme->primaryButtonHoverBackgroundColorDefault) {
				$interface->assign('primaryButtonHoverBackgroundColor', $theme->primaryButtonHoverBackgroundColor);
			}
			if ($interface->getVariable('primaryButtonHoverForegroundColor') == null && !$theme->primaryButtonHoverForegroundColorDefault) {
				$interface->assign('primaryButtonHoverForegroundColor', $theme->primaryButtonHoverForegroundColor);
			}
			if ($interface->getVariable('primaryButtonHoverBorderColor') == null && !$theme->primaryButtonHoverBorderColorDefault) {
				$interface->assign('primaryButtonHoverBorderColor', $theme->primaryButtonHoverBorderColor);
			}

			if ($interface->getVariable('actionButtonBackgroundColor') == null && !$theme->actionButtonBackgroundColorDefault) {
				$interface->assign('actionButtonBackgroundColor', $theme->actionButtonBackgroundColor);
			}
			if ($interface->getVariable('actionButtonForegroundColor') == null && !$theme->actionButtonForegroundColorDefault) {
				$interface->assign('actionButtonForegroundColor', $theme->actionButtonForegroundColor);
			}
			if ($interface->getVariable('actionButtonBorderColor') == null && !$theme->actionButtonBorderColorDefault) {
				$interface->assign('actionButtonBorderColor', $theme->actionButtonBorderColor);
			}
			if ($interface->getVariable('actionButtonHoverBackgroundColor') == null && !$theme->actionButtonHoverBackgroundColorDefault) {
				$interface->assign('actionButtonHoverBackgroundColor', $theme->actionButtonHoverBackgroundColor);
			}
			if ($interface->getVariable('actionButtonHoverForegroundColor') == null && !$theme->actionButtonHoverForegroundColorDefault) {
				$interface->assign('actionButtonHoverForegroundColor', $theme->actionButtonHoverForegroundColor);
			}
			if ($interface->getVariable('actionButtonHoverBorderColor') == null && !$theme->actionButtonHoverBorderColorDefault) {
				$interface->assign('actionButtonHoverBorderColor', $theme->actionButtonHoverBorderColor);
			}

			if ($interface->getVariable('infoButtonBackgroundColor') == null && !$theme->infoButtonBackgroundColorDefault) {
				$interface->assign('infoButtonBackgroundColor', $theme->infoButtonBackgroundColor);
			}
			if ($interface->getVariable('infoButtonForegroundColor') == null && !$theme->infoButtonForegroundColorDefault) {
				$interface->assign('infoButtonForegroundColor', $theme->infoButtonForegroundColor);
			}
			if ($interface->getVariable('infoButtonBorderColor') == null && !$theme->infoButtonBorderColorDefault) {
				$interface->assign('infoButtonBorderColor', $theme->infoButtonBorderColor);
			}
			if ($interface->getVariable('infoButtonHoverBackgroundColor') == null && !$theme->infoButtonHoverBackgroundColorDefault) {
				$interface->assign('infoButtonHoverBackgroundColor', $theme->infoButtonHoverBackgroundColor);
			}
			if ($interface->getVariable('infoButtonHoverForegroundColor') == null && !$theme->infoButtonHoverForegroundColorDefault) {
				$interface->assign('infoButtonHoverForegroundColor', $theme->infoButtonHoverForegroundColor);
			}
			if ($interface->getVariable('infoButtonHoverBorderColor') == null && !$theme->infoButtonHoverBorderColorDefault) {
				$interface->assign('infoButtonHoverBorderColor', $theme->infoButtonHoverBorderColor);
			}

			if ($interface->getVariable('warningButtonBackgroundColor') == null && !$theme->warningButtonBackgroundColorDefault) {
				$interface->assign('warningButtonBackgroundColor', $theme->warningButtonBackgroundColor);
			}
			if ($interface->getVariable('warningButtonForegroundColor') == null && !$theme->warningButtonForegroundColorDefault) {
				$interface->assign('warningButtonForegroundColor', $theme->warningButtonForegroundColor);
			}
			if ($interface->getVariable('warningButtonBorderColor') == null && !$theme->warningButtonBorderColorDefault) {
				$interface->assign('warningButtonBorderColor', $theme->warningButtonBorderColor);
			}
			if ($interface->getVariable('warningButtonHoverBackgroundColor') == null && !$theme->warningButtonHoverBackgroundColorDefault) {
				$interface->assign('warningButtonHoverBackgroundColor', $theme->warningButtonHoverBackgroundColor);
			}
			if ($interface->getVariable('warningButtonHoverForegroundColor') == null && !$theme->warningButtonHoverForegroundColorDefault) {
				$interface->assign('warningButtonHoverForegroundColor', $theme->warningButtonHoverForegroundColor);
			}
			if ($interface->getVariable('warningButtonHoverBorderColor') == null && !$theme->warningButtonHoverBorderColorDefault) {
				$interface->assign('warningButtonHoverBorderColor', $theme->warningButtonHoverBorderColor);
			}

			if ($interface->getVariable('dangerButtonBackgroundColor') == null && !$theme->dangerButtonBackgroundColorDefault) {
				$interface->assign('dangerButtonBackgroundColor', $theme->dangerButtonBackgroundColor);
			}
			if ($interface->getVariable('dangerButtonForegroundColor') == null && !$theme->dangerButtonForegroundColorDefault) {
				$interface->assign('dangerButtonForegroundColor', $theme->dangerButtonForegroundColor);
			}
			if ($interface->getVariable('dangerButtonBorderColor') == null && !$theme->dangerButtonBorderColorDefault) {
				$interface->assign('dangerButtonBorderColor', $theme->dangerButtonBorderColor);
			}
			if ($interface->getVariable('dangerButtonHoverBackgroundColor') == null && !$theme->dangerButtonHoverBackgroundColorDefault) {
				$interface->assign('dangerButtonHoverBackgroundColor', $theme->dangerButtonHoverBackgroundColor);
			}
			if ($interface->getVariable('dangerButtonHoverForegroundColor') == null && !$theme->dangerButtonHoverForegroundColorDefault) {
				$interface->assign('dangerButtonHoverForegroundColor', $theme->dangerButtonHoverForegroundColor);
			}
			if ($interface->getVariable('dangerButtonHoverBorderColor') == null && !$theme->dangerButtonHoverBorderColorDefault) {
				$interface->assign('dangerButtonHoverBorderColor', $theme->dangerButtonHoverBorderColor);
			}

			if ($interface->getVariable('buttonRadius') == null && $theme->buttonRadius != null) {
				$buttonRadius = $theme->buttonRadius;
				if (is_numeric($buttonRadius)){
					$buttonRadius = $buttonRadius . 'px';
				}
				$interface->assign('buttonRadius', $buttonRadius);
			}
			if ($interface->getVariable('smallButtonRadius') == null && $theme->buttonRadius != null) {
				$buttonRadius = $theme->smallButtonRadius;
				if (is_numeric($buttonRadius)){
					$buttonRadius = $buttonRadius . 'px';
				}
				$interface->assign('smallButtonRadius', $buttonRadius);
			}
			if ($appendCSS) {
				if ($this->additionalCssType == 1) {
					$additionalCSS = $theme->additionalCss;
					$appendCSS = false;
				} else {
					if (!empty($theme->additionalCss)) {
						if (empty($additionalCSS)) {
							$additionalCSS = $theme->additionalCss;
						} else {
							$additionalCSS = $theme->additionalCss . "\n" . $additionalCSS;
						}
					}
				}
			}
		}

//		if ($interface->getVariable('closedPanelBackgroundColor') == null && $interface->getVariable('secondaryBackgroundColor') != null) {
//			$interface->assign('closedPanelBackgroundColor', $interface->getVariable('secondaryBackgroundColor'));
//		}
//		if ($interface->getVariable('closedPanelForegroundColor') == null && $interface->getVariable('secondaryForegroundColor') != null) {
//			$interface->assign('closedPanelForegroundColor', $interface->getVariable('secondaryForegroundColor'));
//		}
		if ($interface->getVariable('openPanelBackgroundColor') == null && $interface->getVariable('secondaryBackgroundColor') != null) {
			$interface->assign('openPanelBackgroundColor', $interface->getVariable('secondaryBackgroundColor'));
		}
		if ($interface->getVariable('openPanelForegroundColor') == null && $interface->getVariable('secondaryForegroundColor') != null) {
			$interface->assign('openPanelForegroundColor', $interface->getVariable('secondaryForegroundColor'));
		}

		$interface->assign('additionalCSS', $additionalCSS);

		return $interface->fetch('theme.css.tpl');
	}

	/**
	 * @return Theme[]
	 */
	public function getAllAppliedThemes()
	{
		$primaryTheme = clone($this);
		$allAppliedThemes[$primaryTheme->themeName] = $primaryTheme;
		$theme = $primaryTheme;
		while (strlen($theme->extendsTheme) != 0) {
			$extendsName = $theme->extendsTheme;
			if (!array_key_exists($extendsName, $allAppliedThemes)){
				$theme = new Theme();
				$theme->themeName = $extendsName;
				if ($theme->find(true)) {
					$allAppliedThemes[$theme->themeName] = clone $theme;
				}
			}else{
				//We have a recursive situation
				break;
			}
		}
		return $allAppliedThemes;
	}

	private function clearDefaultCovers()
	{
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$covers = new BookCoverInfo();
		$covers->reloadAllDefaultCovers();
	}

	public function __get($name)
	{
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id){
				$this->_libraries = [];
				$obj = new Library();
				$obj->theme = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == "locations") {
			if (!isset($this->_locations) && $this->id){
				$this->_locations = [];
				$obj = new Location();
				$obj->theme = $this->id;
				$obj->find();
				while($obj->fetch()){
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
			return $this->_locations;
		} else {
			return $this->_data[$name];
		}
	}

	public function __set($name, $value)
	{
		if ($name == "libraries") {
			$this->_libraries = $value;
		}elseif ($name == "locations") {
			$this->_locations = $value;
		}else{
			$this->_data[$name] = $value;
		}
	}

	public function saveLibraries(){
		if (isset ($this->_libraries) && is_array($this->_libraries)){
			$libraryList = Library::getLibraryList();
			foreach ($libraryList as $libraryId => $displayName){
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)){
					//We want to apply the scope to this library
					if ($library->theme != $this->id){
						$library->theme = $this->id;
						$library->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->theme == $this->id){
						$library->theme = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	public function saveLocations(){
		if (isset ($this->_locations) && is_array($this->_locations)){
			$locationList = Location::getLocationList();
			/**
			 * @var int $locationId
			 * @var Location $location
			 */
			foreach ($locationList as $locationId => $displayName){
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)){
					//We want to apply the scope to this library
					if ($location->theme != $this->id){
						$location->theme = $this->id;
						$location->update();
					}
				}else{
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($location->theme == $this->id){
						$library = new Library();
						$library->libraryId = $location->libraryId;
						$library->find(true);
						if ($library->theme != -1){
							$location->theme = -1;
						}else{
							$location->theme = -2;
						}
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
	}

	/** @return Library[]
	 * @noinspection PhpUnused
	 */
	public function getLibraries()
	{
		return $this->_libraries;
	}

	/** @return Location[]
	 * @noinspection PhpUnused
	 */
	public function getLocations()
	{
		return $this->_locations;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val)
	{
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val)
	{
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries(){
		$this->clearOneToManyOptions('Library', 'theme');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations(){
		$this->clearOneToManyOptions('Location', 'theme');
		unset($this->_locations);
	}
}