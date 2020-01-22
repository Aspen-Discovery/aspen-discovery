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

	public $pageBackgroundColor;
	public $pageBackgroundColorDefault;
	public $bodyBackgroundColor;
	public $bodyBackgroundColorDefault;
	public $bodyTextColor;
	public $bodyTextColorDefault;

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
	public $buttonRadiusDefault;
	public $smallButtonRadius;
	public $smallButtonRadiusDefault;
	//TODO: Colors for buttons

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

	static function getObjectStructure()
	{
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
		];

		$themesToExtend = [];
		$themesToExtend[''] = 'None';
		$theme = new Theme();
		$theme->find();
		while ($theme->fetch()){
			$themesToExtend[$theme->themeName] = $theme->themeName;
		}

		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'themeName' => array('property' => 'themeName', 'type' => 'text', 'label' => 'Theme Name', 'description' => 'The Name of the Theme', 'maxLength' => 50, 'required' => true),
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
//            'headerBottomBorderColor' => array('property'=>'headerBottomBorderColor', 'type'=>'color', 'label'=>'Header Bottom Border Color', 'description'=>'Header Bottom Border Color', 'required' => false, 'hideInLists' => true, 'default' => '#B7B7B7'),
			'headerBottomBorderWidth' => array('property' => 'headerBottomBorderWidth', 'type' => 'text', 'label' => 'Header Bottom Border Width', 'description' => 'Header Bottom Border Width', 'required' => false, 'hideInLists' => true),
			//Header Buttons
			'headerButtonRadius' => array('property' => 'headerButtonRadius', 'type' => 'text', 'label' => 'Header Button Radius', 'description' => 'Header Button Radius', 'required' => false, 'hideInLists' => true),
			'headerButtonColor' => array('property' => 'headerButtonColor', 'type' => 'color', 'label' => 'Header Button Color', 'description' => 'Header Button Color', 'required' => false, 'hideInLists' => true, 'default' => '#ffffff'),
			'headerButtonBackgroundColor' => array('property' => 'headerButtonBackgroundColor', 'type' => 'color', 'label' => 'Header Button Background Color', 'description' => 'Header Button Background Color', 'required' => false, 'hideInLists' => true, 'default' => '#848484'),

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
			'additionalCssType' => array('property' => 'additionalCssType', 'type' => 'enum', 'values' => ['0' => 'Append to parent css', '1' => 'Override parent css'], 'label' => 'Additional CSS Application', 'description' => 'How to apply css to the theme', 'required' => false, 'default' => 0),

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
			]],


		);
		return $structure;
	}

	public function insert()
	{
		$this->generatedCss = $this->generateCss($this->getAllAppliedThemes());
		$this->clearDefaultCovers();
		$ret = parent::insert();
		return $ret;
	}

	public function update()
	{
		$this->generatedCss = $this->generateCss($this->getAllAppliedThemes());
		$this->clearDefaultCovers();
		$ret = parent::update();

		//Check to see what has been derived from this theme and regenerate CSS for those themes as well
		$childTheme = new Theme();
		$childTheme->extendsTheme = $this->themeName;
		$childTheme->find();
		while ($childTheme->fetch()){
			$childTheme->update();
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

//            if ($interface->getVariable('headerBottomBorderColor') == null && !$theme->headerBottomBorderColorDefault) {
//                $interface->assign('headerBottomBorderColor', $theme->headerBottomBorderColor);
//            }
			if ($interface->getVariable('headerBottomBorderWidth') == null && !empty($theme->headerBottomBorderWidth)) {
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
			if ($interface->getVariable('customHeadingFont') == null) {
				$interface->assign('customHeadingFont', $theme->customHeadingFont);
				//Strip off the extension to get the name of the font
				$customHeadingFontName = substr($theme->customHeadingFont, 0, strrpos($theme->customHeadingFont, '.'));
				$interface->assign('customHeadingFontName', $customHeadingFontName);

				$interface->assign('headingFont', $customHeadingFontName);
			}
			if ($interface->getVariable('customBodyFont') == null) {
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

		$interface->assign('additionalCSS', $additionalCSS);

		$formattedCSS = $interface->fetch('theme.css.tpl');
		return $formattedCSS;
	}

	/**
	 * @return Theme[]
	 */
	public function getAllAppliedThemes()
	{
		$primaryTheme = clone($this);
		$allAppliedThemes[] = $primaryTheme;
		$theme = $primaryTheme;
		while (strlen($theme->extendsTheme) != 0) {
			$extendsName = $theme->extendsTheme;
			$theme = new Theme();
			$theme->themeName = $extendsName;
			if ($theme->find(true)) {
				$allAppliedThemes[] = clone $theme;
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
}