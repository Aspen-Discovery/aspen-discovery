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
	public /** @noinspection PhpUnused */ $headerBackgroundColorDefault;
	public $headerForegroundColor;
	public /** @noinspection PhpUnused */ $headerForegroundColorDefault;
	//TODO: Delete header bottom border color from settings?
//    public $headerBottomBorderColor;
//    public $headerBottomBorderColorDefault;

	public $headerButtonRadius;
	public $headerButtonColor;
	public /** @noinspection PhpUnused */ $headerButtonColorDefault;
	public $headerButtonBackgroundColor;
	public /** @noinspection PhpUnused */ $headerButtonBackgroundColorDefault;
	public $headerBottomBorderWidth;

	public $pageBackgroundColor;
	public /** @noinspection PhpUnused */ $pageBackgroundColorDefault;
	public $bodyBackgroundColor;
	public /** @noinspection PhpUnused */ $bodyBackgroundColorDefault;
	public $bodyTextColor;
	public /** @noinspection PhpUnused */ $bodyTextColorDefault;

	public $footerLogo;
	public $footerLogoLink;
	public $footerBackgroundColor;
	public /** @noinspection PhpUnused */ $footerBackgroundColorDefault;
	public $footerForegroundColor;
	public /** @noinspection PhpUnused */ $footerForegroundColorDefault;

	//Primary color is used for the header bar and menu bar
	public $primaryBackgroundColor;
	public $primaryBackgroundColorDefault;
	public $primaryForegroundColor;
	public /** @noinspection PhpUnused */ $primaryForegroundColorDefault;

	//Secondary color is used for selections like browse category
	public $secondaryBackgroundColor;
	public $secondaryBackgroundColorDefault;
	public $secondaryForegroundColor;
	public /** @noinspection PhpUnused */ $secondaryForegroundColorDefault;

	//Tertiary color is used for selections like browse category
	public $tertiaryBackgroundColor;
	public /** @noinspection PhpUnused */ $tertiaryBackgroundColorDefault;
	public $tertiaryForegroundColor;
	public /** @noinspection PhpUnused */ $tertiaryForegroundColorDefault;
	public $buttonRadius;
	public $smallButtonRadius;
	//Colors for buttons
	public static $defaultDefaultButtonBackgroundColor = '#ffffff';
	public static $defaultDefaultButtonForegroundColor = '#333333';
	public static $defaultDefaultButtonBorderColor = '#cccccc';
	public static $defaultDefaultButtonHoverBackgroundColor = '#eeeeee';
	public static $defaultDefaultButtonHoverForegroundColor = '#333333';
	public static $defaultDefaultButtonHoverBorderColor = '#cccccc';
	public $defaultButtonBackgroundColor;
	public /** @noinspection PhpUnused */ $defaultButtonBackgroundColorDefault;
	public $defaultButtonForegroundColor;
	public /** @noinspection PhpUnused */ $defaultButtonForegroundColorDefault;
	public $defaultButtonBorderColor;
	public /** @noinspection PhpUnused */ $defaultButtonBorderColorDefault;
	public $defaultButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */ $defaultButtonHoverBackgroundColorDefault;
	public $defaultButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */ $defaultButtonHoverForegroundColorDefault;
	public $defaultButtonHoverBorderColor;
	public /** @noinspection PhpUnused */ $defaultButtonHoverBorderColorDefault;

	public static $defaultPrimaryButtonBackgroundColor = '#1b6ec2';
	public static $defaultPrimaryButtonForegroundColor = '#ffffff';
	public static $defaultPrimaryButtonBorderColor = '#1b6ec2';
	public static $defaultPrimaryButtonHoverBackgroundColor = '#ffffff';
	public static $defaultPrimaryButtonHoverForegroundColor = '#1b6ec2';
	public static $defaultPrimaryButtonHoverBorderColor = '#1b6ec2';
	public $primaryButtonBackgroundColor;
	public /** @noinspection PhpUnused */ $primaryButtonBackgroundColorDefault;
	public $primaryButtonForegroundColor;
	public /** @noinspection PhpUnused */ $primaryButtonForegroundColorDefault;
	public $primaryButtonBorderColor;
	public /** @noinspection PhpUnused */ $primaryButtonBorderColorDefault;
	public $primaryButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */ $primaryButtonHoverBackgroundColorDefault;
	public $primaryButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */ $primaryButtonHoverForegroundColorDefault;
	public $primaryButtonHoverBorderColor;
	public /** @noinspection PhpUnused */ $primaryButtonHoverBorderColorDefault;

	public static $defaultActionButtonBackgroundColor = '#1b6ec2';
	public static $defaultActionButtonForegroundColor = '#ffffff';
	public static $defaultActionButtonBorderColor = '#1b6ec2';
	public static $defaultActionButtonHoverBackgroundColor = '#ffffff';
	public static $defaultActionButtonHoverForegroundColor = '#1b6ec2';
	public static $defaultActionButtonHoverBorderColor = '#1b6ec2';
	public $actionButtonBackgroundColor;
	public /** @noinspection PhpUnused */ $actionButtonBackgroundColorDefault;
	public $actionButtonForegroundColor;
	public /** @noinspection PhpUnused */ $actionButtonForegroundColorDefault;
	public $actionButtonBorderColor;
	public /** @noinspection PhpUnused */ $actionButtonBorderColorDefault;
	public $actionButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */ $actionButtonHoverBackgroundColorDefault;
	public $actionButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */ $actionButtonHoverForegroundColorDefault;
	public $actionButtonHoverBorderColor;
	public /** @noinspection PhpUnused */ $actionButtonHoverBorderColorDefault;

	public static $defaultInfoButtonBackgroundColor = '#8cd2e7';
	public static $defaultInfoButtonForegroundColor = '#000000';
	public static $defaultInfoButtonBorderColor = '#999999';
	public static $defaultInfoButtonHoverBackgroundColor = '#ffffff';
	public static $defaultInfoButtonHoverForegroundColor = '#217e9b';
	public static $defaultInfoButtonHoverBorderColor = '#217e9b';
	public $infoButtonBackgroundColor;
	public /** @noinspection PhpUnused */ $infoButtonBackgroundColorDefault;
	public $infoButtonForegroundColor;
	public /** @noinspection PhpUnused */ $infoButtonForegroundColorDefault;
	public $infoButtonBorderColor;
	public /** @noinspection PhpUnused */ $infoButtonBorderColorDefault;
	public $infoButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */ $infoButtonHoverBackgroundColorDefault;
	public $infoButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */ $infoButtonHoverForegroundColorDefault;
	public $infoButtonHoverBorderColor;
	public /** @noinspection PhpUnused */ $infoButtonHoverBorderColorDefault;

	public static $defaultWarningButtonBackgroundColor = '#f4d03f';
	public static $defaultWarningButtonForegroundColor = '#000000';
	public static $defaultWarningButtonBorderColor = '#999999';
	public static $defaultWarningButtonHoverBackgroundColor = '#ffffff';
	public static $defaultWarningButtonHoverForegroundColor = '#8d6708';
	public static $defaultWarningButtonHoverBorderColor = '#8d6708';
	public $warningButtonBackgroundColor;
	public /** @noinspection PhpUnused */ $warningButtonBackgroundColorDefault;
	public $warningButtonForegroundColor;
	public /** @noinspection PhpUnused */ $warningButtonForegroundColorDefault;
	public $warningButtonBorderColor;
	public /** @noinspection PhpUnused */ $warningButtonBorderColorDefault;
	public $warningButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */ $warningButtonHoverBackgroundColorDefault;
	public $warningButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */ $warningButtonHoverForegroundColorDefault;
	public $warningButtonHoverBorderColor;
	public /** @noinspection PhpUnused */ $warningButtonHoverBorderColorDefault;

	public static $defaultDangerButtonBackgroundColor = '#D50000';
	public static $defaultDangerButtonForegroundColor = '#ffffff';
	public static $defaultDangerButtonBorderColor = '#999999';
	public static $defaultDangerButtonHoverBackgroundColor = '#ffffff';
	public static $defaultDangerButtonHoverForegroundColor = '#D50000';
	public static $defaultDangerButtonHoverBorderColor = '#D50000';
	public $dangerButtonBackgroundColor;
	public /** @noinspection PhpUnused */ $dangerButtonBackgroundColorDefault;
	public $dangerButtonForegroundColor;
	public /** @noinspection PhpUnused */ $dangerButtonForegroundColorDefault;
	public $dangerButtonBorderColor;
	public /** @noinspection PhpUnused */ $dangerButtonBorderColorDefault;
	public $dangerButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */ $dangerButtonHoverBackgroundColorDefault;
	public $dangerButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */ $dangerButtonHoverForegroundColorDefault;
	public $dangerButtonHoverBorderColor;
	public /** @noinspection PhpUnused */ $dangerButtonHoverBorderColorDefault;

	//Sidebar Menu
	public $sidebarHighlightBackgroundColor;
	public /** @noinspection PhpUnused */ $sidebarHighlightBackgroundColorDefault;
	public $sidebarHighlightForegroundColor;
	public /** @noinspection PhpUnused */ $sidebarHighlightForegroundColorDefault;

	//Browse Category Colors
	public $browseCategoryPanelColor;
	public /** @noinspection PhpUnused */ $browseCategoryPanelColorDefault;
	public $selectedBrowseCategoryBackgroundColor;
	public /** @noinspection PhpUnused */ $selectedBrowseCategoryBackgroundColorDefault;
	public $selectedBrowseCategoryForegroundColor;
	public /** @noinspection PhpUnused */ $selectedBrowseCategoryForegroundColorDefault;
	public $selectedBrowseCategoryBorderColor;
	public /** @noinspection PhpUnused */ $selectedBrowseCategoryBorderColorDefault;
	public $deselectedBrowseCategoryBackgroundColor;
	public /** @noinspection PhpUnused */ $deselectedBrowseCategoryBackgroundColorDefault;
	public $deselectedBrowseCategoryForegroundColor;
	public /** @noinspection PhpUnused */ $deselectedBrowseCategoryForegroundColorDefault;
	public $deselectedBrowseCategoryBorderColor;
	public /** @noinspection PhpUnused */ $deselectedBrowseCategoryBorderColorDefault;
	public $capitalizeBrowseCategories;

	//Panel Colors
	public $closedPanelBackgroundColor;
	public /** @noinspection PhpUnused */ $closedPanelBackgroundColorDefault;
	public $closedPanelForegroundColor;
	public /** @noinspection PhpUnused */ $closedPanelForegroundColorDefault;
	public $openPanelBackgroundColor;
	public /** @noinspection PhpUnused */ $openPanelBackgroundColorDefault;
	public $openPanelForegroundColor;
	public /** @noinspection PhpUnused */ $openPanelForegroundColorDefault;

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

		return [
			'id' => ['property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id', 'uniqueProperty' => true],
			'themeName' => ['property' => 'themeName', 'type' => 'text', 'label' => 'Theme Name', 'description' => 'The Name of the Theme', 'maxLength' => 50, 'required' => true, 'uniqueProperty' => true],
			'extendsTheme' => ['property' => 'extendsTheme', 'type' => 'enum', 'values' => $themesToExtend, 'label' => 'Extends Theme', 'description' => 'A theme that this overrides (leave blank if none is overridden)', 'maxLength' => 50, 'required' => false],
			'logoName' => ['property' => 'logoName', 'type' => 'image', 'label' => 'Logo (500px x 100px max)', 'description' => 'The logo for use in the header', 'required' => false, 'maxWidth' => 500, 'maxHeight' => 100, 'hideInLists' => true],
			'favicon' => ['property' => 'favicon', 'type' => 'image', 'label' => 'favicon (32px x 32px max)', 'description' => 'The icon for use in the tab', 'required' => false, 'maxWidth' => 32, 'maxHeight' => 32, 'hideInLists' => true],
			//Overall page colors
			'pageBackgroundColor' => ['property' => 'pageBackgroundColor', 'type' => 'color', 'label' => 'Page Background Color', 'description' => 'Page Background Color behind all content', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'serverValidation' => 'validateColorContrast'],
			'bodyBackgroundColor' => ['property' => 'bodyBackgroundColor', 'type' => 'color', 'label' => 'Body Background Color', 'description' => 'Body Background Color for main content', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'bodyTextColor'],
			'bodyTextColor' => ['property' => 'bodyTextColor', 'type' => 'color', 'label' => 'Body Text Color', 'description' => 'Body Text Color for main content', 'required' => false, 'hideInLists' => true, 'default' => '#6B6B6B', 'checkContrastWith'=>'bodyBackgroundColor'],

			//Header Colors
			'headerBackgroundColor' => ['property' => 'headerBackgroundColor', 'type' => 'color', 'label' => 'Header Background Color', 'description' => 'Header Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#f1f1f1', 'checkContrastWith'=>'headerForegroundColor'],
			'headerForegroundColor' => ['property' => 'headerForegroundColor', 'type' => 'color', 'label' => 'Header Text Color', 'description' => 'Header Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#8b8b8b', 'checkContrastWith'=>'headerBackgroundColor'],
			'headerBottomBorderWidth' => ['property' => 'headerBottomBorderWidth', 'type' => 'text', 'label' => 'Header Bottom Border Width', 'description' => 'Header Bottom Border Width', 'required' => false, 'hideInLists' => true],
			//Header Buttons
			'headerButtonRadius' => ['property' => 'headerButtonRadius', 'type' => 'text', 'label' => 'Header Button Radius', 'description' => 'Header Button Radius', 'required' => false, 'hideInLists' => true],
			'headerButtonColor' => ['property' => 'headerButtonColor', 'type' => 'color', 'label' => 'Header Button Color', 'description' => 'Header Button Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'headerButtonBackgroundColor'],
			'headerButtonBackgroundColor' => ['property' => 'headerButtonBackgroundColor', 'type' => 'color', 'label' => 'Header Button Background Color', 'description' => 'Header Button Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#848484', 'checkContrastWith'=>'headerButtonColor'],

			//Footer Colors
			'footerBackgroundColor' => ['property' => 'footerBackgroundColor', 'type' => 'color', 'label' => 'Footer Background Color', 'description' => 'Footer Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#f1f1f1', 'checkContrastWith'=>'footerForegroundColor'],
			'footerForegroundColor' => ['property' => 'footerForegroundColor', 'type' => 'color', 'label' => 'Footer Text Color', 'description' => 'Footer Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#8b8b8b', 'checkContrastWith'=>'footerBackgroundColor'],
			'footerImage' => ['property' => 'footerLogo', 'type' => 'image', 'label' => 'Footer Image (250px x 150px max)', 'description' => 'An image to be displayed in the footer', 'required' => false, 'maxWidth' => 250, 'maxHeight' => 150, 'hideInLists' => true],
			'footerImageLink' => ['property' => 'footerLogoLink', 'type' => 'url', 'label' => 'Footer Image Link', 'description' => 'A link to be added to the footer logo', 'required' => false, 'hideInLists' => true],
			//Primary Color
			'primaryBackgroundColor' => ['property' => 'primaryBackgroundColor', 'type' => 'color', 'label' => 'Primary Background Color', 'description' => 'Primary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#0a7589', 'checkContrastWith'=>'primaryForegroundColor'],
			'primaryForegroundColor' => ['property' => 'primaryForegroundColor', 'type' => 'color', 'label' => 'Primary Text Color', 'description' => 'Primary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'primaryBackgroundColor'],

			//Secondary Color
			'secondaryBackgroundColor' => ['property' => 'secondaryBackgroundColor', 'type' => 'color', 'label' => 'Secondary Background Color', 'description' => 'Secondary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#de9d03', 'checkContrastWith'=>'secondaryForegroundColor'],
			'secondaryForegroundColor' => ['property' => 'secondaryForegroundColor', 'type' => 'color', 'label' => 'Secondary Text Color', 'description' => 'Secondary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'secondaryBackgroundColor'],

			//Tertiary Color
			'tertiaryBackgroundColor' => ['property' => 'tertiaryBackgroundColor', 'type' => 'color', 'label' => 'Tertiary Background Color', 'description' => 'Tertiary Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#de1f0b', 'checkContrastWith'=>'tertiaryForegroundColor'],
			'tertiaryForegroundColor' => ['property' => 'tertiaryForegroundColor', 'type' => 'color', 'label' => 'Tertiary Text Color', 'description' => 'Tertiary Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'tertiaryBackgroundColor'],

			'headingFont' => ['property' => 'headingFont', 'type' => 'font', 'label' => 'Heading Font', 'description' => 'Heading Font', 'validFonts' => $validHeadingFonts, 'previewFontSize' => '20px', 'required' => false, 'hideInLists' => true, 'default' => 'Ubuntu'],
			'customHeadingFont' => ['property' => 'customHeadingFont', 'type' => 'uploaded_font', 'label' => 'Custom Heading Font', 'description' => 'Upload a custom font to use for headings', 'required' => false, 'hideInLists' => true],
			'bodyFont' => ['property' => 'bodyFont', 'type' => 'font', 'label' => 'Body Font', 'description' => 'Body Font', 'validFonts' => $validBodyFonts, 'previewFontSize' => '14px', 'required' => false, 'hideInLists' => true, 'default' => 'Lato'],
			'customBodyFont' => ['property' => 'customBodyFont', 'type' => 'uploaded_font', 'label' => 'Custom Body Font', 'description' => 'Upload a custom font to use for the body', 'required' => false, 'hideInLists' => true],

			//Additional CSS
			'additionalCss' => ['property' => 'additionalCss', 'type' => 'textarea', 'label' => 'Additional CSS', 'description' => 'Additional CSS to apply to the interface', 'required' => false, 'hideInLists' => true],
			'additionalCssType' => ['property' => 'additionalCssType', 'type' => 'enum', 'values' => ['0' => 'Append to parent css', '1' => 'Override parent css'], 'label' => 'Additional CSS Application', 'description' => 'How to apply css to the theme', 'required' => false, 'default' => 0, 'hideInLists' => true],

			//Menu
			'sidebarHighlightBackgroundColor' => ['property' => 'sidebarHighlightBackgroundColor', 'type' => 'color', 'label' => 'Sidebar Highlight Background Color', 'description' => 'Sidebar Highlight Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#16ceff', 'checkContrastWith'=>'sidebarHighlightForegroundColor'],
			'sidebarHighlightForegroundColor' => ['property' => 'sidebarHighlightForegroundColor', 'type' => 'color', 'label' => 'Sidebar Highlight Text Color', 'description' => 'Sidebar Highlight Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'sidebarHighlightBackgroundColor'],

			//Browse category theming
			'browseCategorySection' =>['property'=>'browseCategorySection', 'type' => 'section', 'label' =>'Browse Categories', 'hideInLists' => true, 'properties' => [
				'browseCategoryPanelColor' => ['property' => 'browseCategoryPanelColor', 'type' => 'color', 'label' => 'Browse Category Panel Color', 'description' => 'Background Color of the Browse Category Panel', 'required' => false, 'hideInLists' => true, 'default' => '#d7dce3'],

				'selectedBrowseCategoryBackgroundColor' => ['property' => 'selectedBrowseCategoryBackgroundColor', 'type' => 'color', 'label' => 'Selected Browse Category Background Color', 'description' => 'Selected Browse Category Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB', 'checkContrastWith'=>'selectedBrowseCategoryForegroundColor'],
				'selectedBrowseCategoryForegroundColor' => ['property' => 'selectedBrowseCategoryForegroundColor', 'type' => 'color', 'label' => 'Selected Browse Category Text Color', 'description' => 'Selected Browse Category Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'selectedBrowseCategoryBackgroundColor'],
				'selectedBrowseCategoryBorderColor' => ['property' => 'selectedBrowseCategoryBorderColor', 'type' => 'color', 'label' => 'Selected Browse Category Border Color', 'description' => 'Selected Browse Category Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB'],

				'deselectedBrowseCategoryBackgroundColor' => ['property' => 'deselectedBrowseCategoryBackgroundColor', 'type' => 'color', 'label' => 'Deselected Browse Category Background Color', 'description' => 'Deselected Browse Category Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB', 'checkContrastWith'=>'deselectedBrowseCategoryForegroundColor'],
				'deselectedBrowseCategoryForegroundColor' => ['property' => 'deselectedBrowseCategoryForegroundColor', 'type' => 'color', 'label' => 'Deselected Browse Category Text Color', 'description' => 'Deselected Browse Category Foreground Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'deselectedBrowseCategoryBackgroundColor'],
				'deselectedBrowseCategoryBorderColor' => ['property' => 'deselectedBrowseCategoryBorderColor', 'type' => 'color', 'label' => 'Deselected Browse Category Border Color', 'description' => 'Deselected Browse Category Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#0087AB'],

				'capitalizeBrowseCategories' => ['property' => 'capitalizeBrowseCategories', 'type' => 'enum', 'values'=> [-1 => 'Default', 0 => 'Maintain case', 1 => 'Force Uppercase'], 'label' => 'Capitalize Browse Categories', 'description' => 'How to treat capitalization of browse categories', 'required' => false, 'hideInLists' => true, 'default' => '-1'],
			]],

			'panels' => ['property'=>'panelsSection', 'type' => 'section', 'label' =>'Panels', 'hideInLists' => true, 'properties' => [
				'closedPanelBackgroundColor' => ['property' => 'closedPanelBackgroundColor', 'type' => 'color', 'label' => 'Closed Panel Background Color', 'description' => 'Panel Background Color while closed', 'required' => false, 'hideInLists' => true, 'default' => '#e7e7e7', 'checkContrastWith'=>'closedPanelForegroundColor'],
				'closedPanelForegroundColor' => ['property' => 'closedPanelForegroundColor', 'type' => 'color', 'label' => 'Closed Panel Text Color', 'description' => 'Panel Foreground Color while closed', 'required' => false, 'hideInLists' => true, 'default' => '#333333', 'checkContrastWith'=>'closedPanelBackgroundColor'],
				'openPanelBackgroundColor' => ['property' => 'openPanelBackgroundColor', 'type' => 'color', 'label' => 'Open Panel Background Color', 'description' => 'Panel Category Background Color while open', 'required' => false, 'hideInLists' => true, 'default' => '#4DACDE', 'checkContrastWith'=>'openPanelForegroundColor'],
				'openPanelForegroundColor' => ['property' => 'openPanelForegroundColor', 'type' => 'color', 'label' => 'Open Panel Text Color', 'description' => 'Panel Category Foreground Color while open', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff', 'checkContrastWith'=>'openPanelBackgroundColor'],
			]],

			'buttonSection' =>['property'=>'buttonSection', 'type' => 'section', 'label' =>'Buttons', 'hideInLists' => true, 'properties' => [
				'buttonRadius'  => ['property' => 'buttonRadius', 'type' => 'text', 'label' => 'Button Radius', 'description' => 'Button Radius', 'required' => false, 'hideInLists' => true],
				'smallButtonRadius'  => ['property' => 'smallButtonRadius', 'type' => 'text', 'label' => 'Small Button Radius', 'description' => 'Small Button Radius', 'required' => false, 'hideInLists' => true],

				'defaultButtonSection' =>['property'=>'defaultButtonSection', 'type' => 'section', 'label' =>'Default Button', 'hideInLists' => true, 'properties' => [
					'defaultButtonBackgroundColor' => ['property' => 'defaultButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Button Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDefaultButtonBackgroundColor, 'checkContrastWith'=>'defaultButtonForegroundColor'],
					'defaultButtonForegroundColor' => ['property' => 'defaultButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Button Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDefaultButtonForegroundColor, 'checkContrastWith'=>'defaultButtonBackgroundColor'],
					'defaultButtonBorderColor' => ['property' => 'defaultButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Button Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDefaultButtonBorderColor],
					'defaultButtonHoverBackgroundColor' => ['property' => 'defaultButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Button Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDefaultButtonHoverBackgroundColor, 'checkContrastWith'=>'defaultButtonHoverForegroundColor'],
					'defaultButtonHoverForegroundColor' => ['property' => 'defaultButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Button Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDefaultButtonHoverForegroundColor, 'checkContrastWith'=>'defaultButtonHoverBackgroundColor'],
					'defaultButtonHoverBorderColor' => ['property' => 'defaultButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Button Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDefaultButtonHoverBorderColor],
				]],
				'primaryButtonSection' =>['property'=>'primaryButtonSection', 'type' => 'section', 'label' =>'Primary Button', 'hideInLists' => true, 'properties' => [
					'primaryButtonBackgroundColor' => ['property' => 'primaryButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultPrimaryButtonBackgroundColor, 'checkContrastWith'=>'primaryButtonForegroundColor'],
					'primaryButtonForegroundColor' => ['property' => 'primaryButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultPrimaryButtonForegroundColor, 'checkContrastWith'=>'primaryButtonBackgroundColor'],
					'primaryButtonBorderColor' => ['property' => 'primaryButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultPrimaryButtonBorderColor],
					'primaryButtonHoverBackgroundColor' => ['property' => 'primaryButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultPrimaryButtonHoverBackgroundColor, 'checkContrastWith'=>'primaryButtonHoverForegroundColor'],
					'primaryButtonHoverForegroundColor' => ['property' => 'primaryButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultPrimaryButtonHoverForegroundColor, 'checkContrastWith'=>'primaryButtonHoverBackgroundColor'],
					'primaryButtonHoverBorderColor' => ['property' => 'primaryButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'primary' => Theme::$defaultPrimaryButtonHoverBorderColor],
				]],

				'actionButtonSection' =>['property'=>'actionButtonSection', 'type' => 'section', 'label' =>'Action Button (Place hold, checkout, access online, etc)', 'hideInLists' => true, 'properties' => [
					'actionButtonBackgroundColor' => ['property' => 'actionButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultActionButtonBackgroundColor, 'checkContrastWith'=>'actionButtonForegroundColor'],
					'actionButtonForegroundColor' => ['property' => 'actionButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultActionButtonForegroundColor, 'checkContrastWith'=>'actionButtonBackgroundColor'],
					'actionButtonBorderColor' => ['property' => 'actionButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultActionButtonBorderColor],
					'actionButtonHoverBackgroundColor' => ['property' => 'actionButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultActionButtonHoverBackgroundColor, 'checkContrastWith'=>'actionButtonHoverForegroundColor'],
					'actionButtonHoverForegroundColor' => ['property' => 'actionButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultActionButtonHoverForegroundColor, 'checkContrastWith'=>'actionButtonHoverBackgroundColor'],
					'actionButtonHoverBorderColor' => ['property' => 'actionButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultActionButtonHoverBorderColor],
				]],

				'infoButtonSection' =>['property'=>'infoButtonSection', 'type' => 'section', 'label' =>'Info Button', 'hideInLists' => true, 'properties' => [
					'infoButtonBackgroundColor' => ['property' => 'infoButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultInfoButtonBackgroundColor, 'checkContrastWith'=>'infoButtonHoverBackgroundColor'],
					'infoButtonForegroundColor' => ['property' => 'infoButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultInfoButtonForegroundColor, 'checkContrastWith'=>'infoButtonBackgroundColor'],
					'infoButtonBorderColor' => ['property' => 'infoButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultInfoButtonBorderColor],
					'infoButtonHoverBackgroundColor' => ['property' => 'infoButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultInfoButtonHoverBackgroundColor, 'checkContrastWith'=>'infoButtonHoverForegroundColor'],
					'infoButtonHoverForegroundColor' => ['property' => 'infoButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultInfoButtonHoverForegroundColor, 'checkContrastWith'=>'infoButtonHoverBackgroundColor'],
					'infoButtonHoverBorderColor' => ['property' => 'infoButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultInfoButtonHoverBorderColor],
				]],

				'warningButtonSection' =>['property'=>'warningButtonSection', 'type' => 'section', 'label' =>'Warning Button', 'hideInLists' => true, 'properties' => [
					'warningButtonBackgroundColor' => ['property' => 'warningButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultWarningButtonBackgroundColor, 'checkContrastWith'=>'warningButtonHoverBackgroundColor'],
					'warningButtonForegroundColor' => ['property' => 'warningButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultWarningButtonForegroundColor, 'checkContrastWith'=>'warningButtonBackgroundColor'],
					'warningButtonBorderColor' => ['property' => 'warningButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultWarningButtonBorderColor],
					'warningButtonHoverBackgroundColor' => ['property' => 'warningButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultWarningButtonHoverBackgroundColor, 'checkContrastWith'=>'warningButtonHoverForegroundColor'],
					'warningButtonHoverForegroundColor' => ['property' => 'warningButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultWarningButtonHoverForegroundColor, 'checkContrastWith'=>'warningButtonHoverBackgroundColor'],
					'warningButtonHoverBorderColor' => ['property' => 'warningButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultWarningButtonHoverBorderColor],
				]],

				'dangerButtonSection' =>['property'=>'dangerButtonSection', 'type' => 'section', 'label' =>'Danger Button', 'hideInLists' => true, 'properties' => [
					'dangerButtonBackgroundColor' => ['property' => 'dangerButtonBackgroundColor', 'type' => 'color', 'label' => 'Background Color', 'description' => 'Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDangerButtonBackgroundColor, 'checkContrastWith'=>'dangerButtonHoverBackgroundColor'],
					'dangerButtonForegroundColor' => ['property' => 'dangerButtonForegroundColor', 'type' => 'color', 'label' => 'Text Color', 'description' => 'Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDangerButtonForegroundColor, 'checkContrastWith'=>'dangerButtonBackgroundColor'],
					'dangerButtonBorderColor' => ['property' => 'dangerButtonBorderColor', 'type' => 'color', 'label' => 'Border Color', 'description' => 'Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDangerButtonBorderColor],
					'dangerButtonHoverBackgroundColor' => ['property' => 'dangerButtonHoverBackgroundColor', 'type' => 'color', 'label' => 'Hover Background Color', 'description' => 'Hover Background Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDangerButtonHoverBackgroundColor, 'checkContrastWith'=>'dangerButtonHoverForegroundColor'],
					'dangerButtonHoverForegroundColor' => ['property' => 'dangerButtonHoverForegroundColor', 'type' => 'color', 'label' => 'Hover Text Color', 'description' => 'Hover Text Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDangerButtonHoverForegroundColor, 'checkContrastWith'=>'dangerButtonHoverBackgroundColor'],
					'dangerButtonHoverBorderColor' => ['property' => 'dangerButtonHoverBorderColor', 'type' => 'color', 'label' => 'Hover Border Color', 'description' => 'Hover Border Color', 'required' => false, 'hideInLists' => true, 'default' => Theme::$defaultDangerButtonHoverBorderColor],
				]],
			]],

			'librariesAndLocationsSettings' =>['property'=>'librariesAndLocationsSettings', 'type' => 'section', 'label' =>'Libraries and Locations', 'hideInLists' => true, 'properties' => [
				'libraries' => [
					'property' => 'libraries',
					'type' => 'multiSelect',
					'listStyle' => 'checkboxSimple',
					'label' => 'Libraries',
					'description' => 'Define libraries that use this browse category group',
					'values' => $libraryList,
				],

				'locations' => [
					'property' => 'locations',
					'type' => 'multiSelect',
					'listStyle' => 'checkboxSimple',
					'label' => 'Locations',
					'description' => 'Define locations that use this browse category group',
					'values' => $locationList,
				],
			]]
		];
	}

	/** @noinspection PhpUnused */
	public function validateColorContrast(){
		//Setup validation return array
		$validationResults = array(
			'validatedOk' => true,
			'errors' => [],
		);

		$this->applyDefaults();

		require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
		$bodyContrast = ColorUtils::calculateColorContrast($this->bodyBackgroundColor, $this->bodyTextColor);
		if ($bodyContrast < 3.5){
			$validationResults['errors'][] = 'Body contrast does not meet accessibility guidelines, contrast is: ' . $bodyContrast;
		}
		$headerContrast = ColorUtils::calculateColorContrast($this->headerBackgroundColor, $this->headerForegroundColor);
		if ($headerContrast < 3.5){
			$validationResults['errors'][] = 'Header contrast does not meet accessibility guidelines, contrast is: ' . ($headerContrast);
		}
		$headerButtonContrast = ColorUtils::calculateColorContrast($this->headerButtonColor, $this->headerButtonBackgroundColor);
		if ($headerButtonContrast < 3.5){
			$validationResults['errors'][] = 'Header Button contrast does not meet accessibility guidelines, contrast is: ' . ($headerButtonContrast);
		}
		$footerContrast = ColorUtils::calculateColorContrast($this->footerBackgroundColor, $this->footerForegroundColor);
		if ($footerContrast < 3.5){
			$validationResults['errors'][] = 'Footer contrast does not meet accessibility guidelines, contrast is: ' . ($footerContrast);
		}
		$primaryContrast = ColorUtils::calculateColorContrast($this->primaryBackgroundColor, $this->primaryForegroundColor);
		if ($primaryContrast < 3.5){
			$validationResults['errors'][] = 'Primary color contrast does not meet accessibility guidelines, contrast is: ' . ($primaryContrast);
		}
		$secondaryContrast = ColorUtils::calculateColorContrast($this->secondaryBackgroundColor, $this->secondaryForegroundColor);
		if ($secondaryContrast < 3.5){
			$validationResults['errors'][] = 'Secondary color contrast does not meet accessibility guidelines, contrast is: ' . ($secondaryContrast);
		}
		$tertiaryContrast = ColorUtils::calculateColorContrast($this->tertiaryBackgroundColor, $this->tertiaryForegroundColor);
		if ($tertiaryContrast < 3.5){
			$validationResults['errors'][] = 'Tertiary color contrast does not meet accessibility guidelines, contrast is: ' . ($tertiaryContrast);
		}
		$sidebarHighlightContrast = ColorUtils::calculateColorContrast($this->sidebarHighlightBackgroundColor, $this->sidebarHighlightForegroundColor);
		if ($sidebarHighlightContrast < 3.5){
			$validationResults['errors'][] = 'Sidebar highlight contrast does not meet accessibility guidelines, contrast is: ' . ($sidebarHighlightContrast);
		}
		$selectedBrowseCategoryContrast = ColorUtils::calculateColorContrast($this->selectedBrowseCategoryBackgroundColor, $this->selectedBrowseCategoryForegroundColor);
		if ($selectedBrowseCategoryContrast < 3.5){
			$validationResults['errors'][] = 'Selected Browse Category contrast does not meet accessibility guidelines, contrast is: ' . ($selectedBrowseCategoryContrast);
		}
		$deselectedBrowseCategoryContrast = ColorUtils::calculateColorContrast($this->deselectedBrowseCategoryBackgroundColor, $this->deselectedBrowseCategoryForegroundColor);
		if ($deselectedBrowseCategoryContrast < 3.5){
			$validationResults['errors'][] = 'Deselected Browse Category contrast does not meet accessibility guidelines, contrast is: ' . ($deselectedBrowseCategoryContrast);
		}
		$closedPanelContrast = ColorUtils::calculateColorContrast($this->closedPanelBackgroundColor, $this->closedPanelForegroundColor);
		if ($closedPanelContrast < 3.5){
			$validationResults['errors'][] = 'Closed Panel contrast does not meet accessibility guidelines, contrast is: ' . ($closedPanelContrast);
		}
		$openPanelContrast = ColorUtils::calculateColorContrast($this->openPanelBackgroundColor, $this->openPanelForegroundColor);
		if ($openPanelContrast < 3.5){
			$validationResults['errors'][] = 'Open Panel contrast does not meet accessibility guidelines, contrast is: ' . ($openPanelContrast);
		}
		$defaultButtonContrast = ColorUtils::calculateColorContrast($this->defaultButtonBackgroundColor, $this->defaultButtonForegroundColor);
		if ($defaultButtonContrast < 3.5){
			$validationResults['errors'][] = 'Default Button contrast does not meet accessibility guidelines, contrast is: ' . ($defaultButtonContrast);
		}
		$defaultButtonHoverContrast = ColorUtils::calculateColorContrast($this->defaultButtonHoverBackgroundColor, $this->defaultButtonHoverForegroundColor);
		if ($defaultButtonHoverContrast < 3.5){
			$validationResults['errors'][] = 'Default Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($defaultButtonHoverContrast);
		}
		$primaryButtonContrast = ColorUtils::calculateColorContrast($this->primaryButtonBackgroundColor, $this->primaryButtonForegroundColor);
		if ($primaryButtonContrast < 3.5){
			$validationResults['errors'][] = 'Primary Button contrast does not meet accessibility guidelines, contrast is: ' . ($primaryButtonContrast);
		}
		$primaryButtonHoverContrast = ColorUtils::calculateColorContrast($this->primaryButtonHoverBackgroundColor, $this->primaryButtonHoverForegroundColor);
		if ($primaryButtonHoverContrast < 3.5){
			$validationResults['errors'][] = 'Primary Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($primaryButtonHoverContrast);
		}
		$actionButtonContrast = ColorUtils::calculateColorContrast($this->actionButtonBackgroundColor, $this->actionButtonForegroundColor);
		if ($actionButtonContrast < 3.5){
			$validationResults['errors'][] = 'Action Button contrast does not meet accessibility guidelines, contrast is: ' . ($actionButtonContrast);
		}
		$actionButtonHoverContrast = ColorUtils::calculateColorContrast($this->actionButtonHoverBackgroundColor, $this->actionButtonHoverForegroundColor);
		if ($actionButtonHoverContrast < 3.5){
			$validationResults['errors'][] = 'Action Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($actionButtonHoverContrast);
		}
		$infoButtonContrast = ColorUtils::calculateColorContrast($this->infoButtonBackgroundColor, $this->infoButtonForegroundColor);
		if ($infoButtonContrast < 3.5){
			$validationResults['errors'][] = 'Info Button contrast does not meet accessibility guidelines, contrast is: ' . ($infoButtonContrast);
		}
		$infoButtonHoverContrast = ColorUtils::calculateColorContrast($this->infoButtonHoverBackgroundColor, $this->infoButtonHoverForegroundColor);
		if ($infoButtonHoverContrast < 3.5){
			$validationResults['errors'][] = 'Info Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($infoButtonHoverContrast);
		}
		$warningButtonContrast = ColorUtils::calculateColorContrast($this->warningButtonBackgroundColor, $this->warningButtonForegroundColor);
		if ($warningButtonContrast < 3.5){
			$validationResults['errors'][] = 'Warning Button contrast does not meet accessibility guidelines, contrast is: ' . ($warningButtonContrast);
		}
		$warningButtonHoverContrast = ColorUtils::calculateColorContrast($this->warningButtonHoverBackgroundColor, $this->warningButtonHoverForegroundColor);
		if ($warningButtonHoverContrast < 3.5){
			$validationResults['errors'][] = 'Warning Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($warningButtonHoverContrast);
		}
		$dangerButtonContrast = ColorUtils::calculateColorContrast($this->dangerButtonBackgroundColor, $this->dangerButtonForegroundColor);
		if ($dangerButtonContrast < 3.5){
			$validationResults['errors'][] = 'Danger Button contrast does not meet accessibility guidelines, contrast is: ' . ($dangerButtonContrast);
		}
		$dangerButtonHoverContrast = ColorUtils::calculateColorContrast($this->dangerButtonHoverBackgroundColor, $this->dangerButtonHoverForegroundColor);
		if ($dangerButtonHoverContrast < 3.5){
			$validationResults['errors'][] = 'Danger Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($dangerButtonHoverContrast);
		}

		if (count($validationResults['errors']) > 0){
			$validationResults['validatedOk'] = false;
		}

		return $validationResults;
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

	public function applyDefaults(){
		require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
		$appliedThemes = $this->getAllAppliedThemes();
		$this->getValueForPropertyUsingDefaults('pageBackgroundColor', '#ffffff', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('bodyBackgroundColor', '#ffffff', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('bodyTextColor', '#6B6B6B', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('headerBackgroundColor', '#f1f1f1', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('headerForegroundColor', '#8b8b8b', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('headerButtonColor', '#ffffff', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('headerButtonBackgroundColor', '#848484', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('footerBackgroundColor', '#f1f1f1', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('footerForegroundColor', '#8b8b8b', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryBackgroundColor', '#0a7589', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryForegroundColor', '#ffffff', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('secondaryBackgroundColor', '#de9d03', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('secondaryForegroundColor', '#ffffff', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('tertiaryBackgroundColor', '#de1f0b', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('tertiaryForegroundColor', '#ffffff', $appliedThemes);
		$primaryColorLightened80 = ColorUtils::lightenColor($this->primaryBackgroundColor, 1.8);
		$this->getValueForPropertyUsingDefaults('sidebarHighlightBackgroundColor', $primaryColorLightened80, $appliedThemes);
		$defaultSidebarHighlight = '#ffffff';
		if (ColorUtils::calculateColorContrast($primaryColorLightened80, $defaultSidebarHighlight) < 3.5){
			$defaultSidebarHighlight = '#000000';
		}
		$this->getValueForPropertyUsingDefaults('sidebarHighlightForegroundColor', $defaultSidebarHighlight, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('browseCategoryPanelColor', '#d7dce3', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('selectedBrowseCategoryBackgroundColor', '#0087AB', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('selectedBrowseCategoryForegroundColor', '#ffffff', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('selectedBrowseCategoryBorderColor', '#0087AB', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('deselectedBrowseCategoryBackgroundColor', '#0087AB', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('deselectedBrowseCategoryForegroundColor', '#ffffff', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('deselectedBrowseCategoryBorderColor', '#0087AB', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('closedPanelBackgroundColor', '#e7e7e7', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('closedPanelForegroundColor', '#333333', $appliedThemes);
		$this->getValueForPropertyUsingDefaults('openPanelBackgroundColor', $this->secondaryBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('openPanelForegroundColor', $this->secondaryForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('defaultButtonBackgroundColor', Theme::$defaultDefaultButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('defaultButtonForegroundColor', Theme::$defaultDefaultButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('defaultButtonBorderColor', Theme::$defaultDefaultButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('defaultButtonHoverBackgroundColor', Theme::$defaultDefaultButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('defaultButtonHoverForegroundColor', Theme::$defaultDefaultButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('defaultButtonHoverBorderColor', Theme::$defaultDefaultButtonHoverBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryButtonBackgroundColor', Theme::$defaultPrimaryButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryButtonForegroundColor', Theme::$defaultPrimaryButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryButtonBorderColor', Theme::$defaultPrimaryButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryButtonHoverBackgroundColor', Theme::$defaultPrimaryButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryButtonHoverForegroundColor', Theme::$defaultPrimaryButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryButtonHoverBorderColor', Theme::$defaultPrimaryButtonHoverBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('actionButtonBackgroundColor', Theme::$defaultActionButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('actionButtonForegroundColor', Theme::$defaultActionButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('actionButtonBorderColor', Theme::$defaultActionButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('actionButtonHoverBackgroundColor', Theme::$defaultActionButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('actionButtonHoverForegroundColor', Theme::$defaultActionButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('actionButtonHoverBorderColor', Theme::$defaultActionButtonHoverBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('infoButtonBackgroundColor', Theme::$defaultInfoButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('infoButtonForegroundColor', Theme::$defaultInfoButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('infoButtonBorderColor', Theme::$defaultInfoButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('infoButtonHoverBackgroundColor', Theme::$defaultInfoButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('infoButtonHoverForegroundColor', Theme::$defaultInfoButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('infoButtonHoverBorderColor', Theme::$defaultInfoButtonHoverBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('warningButtonBackgroundColor', Theme::$defaultWarningButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('warningButtonForegroundColor', Theme::$defaultWarningButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('warningButtonBorderColor', Theme::$defaultWarningButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('warningButtonHoverBackgroundColor', Theme::$defaultWarningButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('warningButtonHoverForegroundColor', Theme::$defaultWarningButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('warningButtonHoverBorderColor', Theme::$defaultWarningButtonHoverBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('dangerButtonBackgroundColor', Theme::$defaultDangerButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('dangerButtonForegroundColor', Theme::$defaultDangerButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('dangerButtonBorderColor', Theme::$defaultDangerButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('dangerButtonHoverBackgroundColor', Theme::$defaultDangerButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('dangerButtonHoverForegroundColor', Theme::$defaultDangerButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('dangerButtonHoverBorderColor', Theme::$defaultDangerButtonHoverBorderColor, $appliedThemes);
	}

	public function getValueForPropertyUsingDefaults($propertyName, $defaultValue, $appliedThemes){
		foreach ($appliedThemes as $theme) {
			$defaultPropertyName = $propertyName . 'Default';
			if (!$theme->$defaultPropertyName){
				$this->$propertyName = $theme->$propertyName;
				return;
			}
		}
		$this->$propertyName = $defaultValue;
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
		$this->applyDefaults();
		$interface->assign('headerBackgroundColor', $this->headerBackgroundColor);
		$interface->assign('headerForegroundColor', $this->headerForegroundColor);
		$interface->assign('headerButtonColor', $this->headerButtonColor);
		$interface->assign('headerButtonBackgroundColor', $this->headerButtonBackgroundColor);
		$interface->assign('pageBackgroundColor', $this->pageBackgroundColor);
		$interface->assign('footerBackgroundColor', $this->footerBackgroundColor);
		$interface->assign('footerForegroundColor', $this->footerForegroundColor);
		$interface->assign('primaryBackgroundColor', $this->primaryBackgroundColor);
		$interface->assign('primaryForegroundColor', $this->primaryForegroundColor);
		$lightened80 = ColorUtils::lightenColor($this->primaryBackgroundColor, 1.8);
		$interface->assign('primaryBackgroundColorLightened80', $lightened80);
		$lightened60 = ColorUtils::lightenColor($this->primaryBackgroundColor, 1.6);
		$interface->assign('primaryBackgroundColorLightened60', $lightened60);
		$interface->assign('secondaryBackgroundColor', $this->secondaryBackgroundColor);
		$interface->assign('secondaryForegroundColor', $this->secondaryForegroundColor);
		$interface->assign('tertiaryBackgroundColor', $this->tertiaryBackgroundColor);
		$interface->assign('tertiaryForegroundColor', $this->tertiaryForegroundColor);
		$interface->assign('bodyBackgroundColor', $this->bodyBackgroundColor);
		$interface->assign('bodyTextColor', $this->bodyTextColor);
		$interface->assign('sidebarHighlightBackgroundColor', $this->sidebarHighlightBackgroundColor);
		$interface->assign('sidebarHighlightForegroundColor', $this->sidebarHighlightForegroundColor);
		$interface->assign('browseCategoryPanelColor', $this->browseCategoryPanelColor);
		$interface->assign('selectedBrowseCategoryBackgroundColor', $this->selectedBrowseCategoryBackgroundColor);
		$interface->assign('selectedBrowseCategoryForegroundColor', $this->selectedBrowseCategoryForegroundColor);
		$interface->assign('selectedBrowseCategoryBorderColor', $this->selectedBrowseCategoryBorderColor);
		$interface->assign('deselectedBrowseCategoryBackgroundColor', $this->deselectedBrowseCategoryBackgroundColor);
		$interface->assign('deselectedBrowseCategoryForegroundColor', $this->deselectedBrowseCategoryForegroundColor);
		$interface->assign('deselectedBrowseCategoryBorderColor', $this->deselectedBrowseCategoryBorderColor);
		$interface->assign('closedPanelBackgroundColor', $this->closedPanelBackgroundColor);
		$interface->assign('closedPanelForegroundColor', $this->closedPanelForegroundColor);
		$interface->assign('openPanelBackgroundColor', $this->openPanelBackgroundColor);
		$interface->assign('openPanelForegroundColor', $this->openPanelForegroundColor);
		$interface->assign('defaultButtonBackgroundColor', $this->defaultButtonBackgroundColor);
		$interface->assign('defaultButtonForegroundColor', $this->defaultButtonForegroundColor);
		$interface->assign('defaultButtonBorderColor', $this->defaultButtonBorderColor);
		$interface->assign('defaultButtonHoverBackgroundColor', $this->defaultButtonHoverBackgroundColor);
		$interface->assign('defaultButtonHoverForegroundColor', $this->defaultButtonHoverForegroundColor);
		$interface->assign('defaultButtonHoverBorderColor', $this->defaultButtonHoverBorderColor);
		$interface->assign('primaryButtonBackgroundColor', $this->primaryButtonBackgroundColor);
		$interface->assign('primaryButtonForegroundColor', $this->primaryButtonForegroundColor);
		$interface->assign('primaryButtonBorderColor', $this->primaryButtonBorderColor);
		$interface->assign('primaryButtonHoverBackgroundColor', $this->primaryButtonHoverBackgroundColor);
		$interface->assign('primaryButtonHoverForegroundColor', $this->primaryButtonHoverForegroundColor);
		$interface->assign('primaryButtonHoverBorderColor', $this->primaryButtonHoverBorderColor);
		$interface->assign('actionButtonBackgroundColor', $this->actionButtonBackgroundColor);
		$interface->assign('actionButtonForegroundColor', $this->actionButtonForegroundColor);
		$interface->assign('actionButtonBorderColor', $this->actionButtonBorderColor);
		$interface->assign('actionButtonHoverBackgroundColor', $this->actionButtonHoverBackgroundColor);
		$interface->assign('actionButtonHoverForegroundColor', $this->actionButtonHoverForegroundColor);
		$interface->assign('actionButtonHoverBorderColor', $this->actionButtonHoverBorderColor);
		$interface->assign('infoButtonBackgroundColor', $this->infoButtonBackgroundColor);
		$interface->assign('infoButtonForegroundColor', $this->infoButtonForegroundColor);
		$interface->assign('infoButtonBorderColor', $this->infoButtonBorderColor);
		$interface->assign('infoButtonHoverBackgroundColor', $this->infoButtonHoverBackgroundColor);
		$interface->assign('infoButtonHoverForegroundColor', $this->infoButtonHoverForegroundColor);
		$interface->assign('infoButtonHoverBorderColor', $this->infoButtonHoverBorderColor);
		$interface->assign('warningButtonBackgroundColor', $this->warningButtonBackgroundColor);
		$interface->assign('warningButtonForegroundColor', $this->warningButtonForegroundColor);
		$interface->assign('warningButtonBorderColor', $this->warningButtonBorderColor);
		$interface->assign('warningButtonHoverBackgroundColor', $this->warningButtonHoverBackgroundColor);
		$interface->assign('warningButtonHoverForegroundColor', $this->warningButtonHoverForegroundColor);
		$interface->assign('warningButtonHoverBorderColor', $this->warningButtonHoverBorderColor);
		$interface->assign('dangerButtonBackgroundColor', $this->dangerButtonBackgroundColor);
		$interface->assign('dangerButtonForegroundColor', $this->dangerButtonForegroundColor);
		$interface->assign('dangerButtonBorderColor', $this->dangerButtonBorderColor);
		$interface->assign('dangerButtonHoverBackgroundColor', $this->dangerButtonHoverBackgroundColor);
		$interface->assign('dangerButtonHoverForegroundColor', $this->dangerButtonHoverForegroundColor);
		$interface->assign('dangerButtonHoverBorderColor', $this->dangerButtonHoverBorderColor);

		foreach ($allAppliedThemes as $theme) {
			if ($interface->getVariable('headerBottomBorderWidth') == null && $theme->headerBottomBorderWidth != null) {
				$interface->assign('headerBottomBorderWidth', $theme->headerBottomBorderWidth);
			}
			if ($interface->getVariable('headerButtonRadius') == null && !empty($theme->headerButtonRadius)) {
				$interface->assign('headerButtonRadius', $theme->headerButtonRadius);
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
			if ($interface->getVariable('capitalizeBrowseCategories') == null && $theme->capitalizeBrowseCategories != -1) {
				$interface->assign('capitalizeBrowseCategories', $theme->capitalizeBrowseCategories);
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