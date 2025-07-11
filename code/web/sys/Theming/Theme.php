<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Theme extends DataObject {
	public $__table = 'themes';
	public $__displayNameColumn = 'displayName';
	public $id;
	public $themeName;
	public $displayName;
	public $extendsTheme;
	public $logoName;
	public $favicon;
	public $defaultCover;
	public $logoApp;
	public $headerLogoApp;
	public $headerLogoAlignmentApp;
	public $headerLogoBackgroundColorApp;
	public static $defaultHeaderLogoBackgroundColorApp = '#FFFFFF';
	public /** @noinspection PhpUnused */
		$headerLogoBackgroundColorAppDefault;

	public $fullWidth;

	//Format Icons
	public $booksImage;
	public $eBooksImage;
	public $audioBooksImage;
	public $musicImage;
	public $moviesImage;
	public $booksImageSelected;
	public $eBooksImageSelected;
	public $audioBooksImageSelected;
	public $musicImageSelected;
	public $moviesImageSelected;

	//Explore More Images
	/** @noinspection PhpUnused */
	public $catalogImage;
	/** @noinspection PhpUnused */
	public $genealogyImage;
	/** @noinspection PhpUnused */
	public $articlesDBImage;
	/** @noinspection PhpUnused */
	public $eventsImage;
	/** @noinspection PhpUnused */
	public $listsImage;
	/** @noinspection PhpUnused */
	public $seriesImage;
	/** @noinspection PhpUnused */
	public $libraryWebsiteImage;
	/** @noinspection PhpUnused */
	public $historyArchivesImage;

	public static $defaultHeaderBackgroundColor = '#ffffff';
	public $headerBackgroundColor;
	public /** @noinspection PhpUnused */
		$headerBackgroundColorDefault;
	public static $defaultHeaderForegroundColor = '#303030';
	public $headerForegroundColor;
	public /** @noinspection PhpUnused */
		$headerForegroundColorDefault;
	public $headerBottomBorderWidth;
	public $headerBackgroundImage;
	public $headerBackgroundImageSize;
	public $headerBackgroundImageRepeat;

	public static $defaultPageBackgroundColor = '#ffffff';
	public $pageBackgroundColor;
	public /** @noinspection PhpUnused */
		$pageBackgroundColorDefault;
	public static $defaultBodyBackgroundColor = '#ffffff';
	public $bodyBackgroundColor;
	public /** @noinspection PhpUnused */
		$bodyBackgroundColorDefault;
	public static $defaultBodyTextColor = '#6B6B6B';
	public $bodyTextColor;
	public /** @noinspection PhpUnused */
		$bodyTextColorDefault;
	public static $defaultLinkColor = '#3174AF';
	public $linkColor;
	public /** @noinspection PhpUnused */
		$linkColorDefault;
	public static $defaultLinkHoverColor = '#265a87';
	public $linkHoverColor;
	public /** @noinspection PhpUnused */
		$linkHoverColorDefault;
	public static $defaultResultLabelColor = '#44484a';
	public $resultLabelColor;
	public /** @noinspection PhpUnused */
		$resultLabelColorDefault;
	public static $defaultResultValueColor = '#6B6B6B';
	public $resultValueColor;
	public /** @noinspection PhpUnused */
		$resultValueColorDefault;

	public static $defaultBreadcrumbsBackgroundColor = '#f5f5f5';
	public $breadcrumbsBackgroundColor;
	public /** @noinspection PhpUnused */
		$breadcrumbsBackgroundColorDefault;
	public static $defaultBreadcrumbsForegroundColor = '#6B6B6B';
	public $breadcrumbsForegroundColor;
	public /** @noinspection PhpUnused */
		$breadcrumbsForegroundColorDefault;

	public static $defaultSearchToolsBackgroundColor = '#f5f5f5';
	public $searchToolsBackgroundColor;
	public /** @noinspection PhpUnused */
		$searchToolsBackgroundColorDefault;
	public static $defaultSearchToolsBorderColor = '#e3e3e3';
	public $searchToolsBorderColor;
	public /** @noinspection PhpUnused */
		$searchToolsBorderColorDefault;
	public static $defaultSearchToolsForegroundColor = '#6B6B6B';
	public $searchToolsForegroundColor;
	public /** @noinspection PhpUnused */
		$searchToolsForegroundColorDefault;

	public $footerLogo;
	public $footerLogoLink;
	public $footerLogoAlt;
	public static $defaultFooterBackgroundColor = '#f1f1f1';
	public $footerBackgroundColor;
	public /** @noinspection PhpUnused */
		$footerBackgroundColorDefault;
	public static $defaultFooterForegroundColor = '#303030';
	public $footerForegroundColor;
	public /** @noinspection PhpUnused */
		$footerForegroundColorDefault;

	//Primary color is used for the search bar
	public static $defaultPrimaryBackgroundColor = '#0a7589';
	public $primaryBackgroundColor;
	public $primaryBackgroundColorDefault;
	public static $defaultPrimaryForegroundColor = '#ffffff';
	public $primaryForegroundColor;
	public /** @noinspection PhpUnused */
		$primaryForegroundColorDefault;

	//Secondary color is used for selections like browse category
	public static $defaultSecondaryBackgroundColor = '#de9d03';
	public $secondaryBackgroundColor;
	public $secondaryBackgroundColorDefault;
	public static $defaultSecondaryForegroundColor = '#303030';
	public $secondaryForegroundColor;
	public /** @noinspection PhpUnused */
		$secondaryForegroundColorDefault;

	//Tertiary color is used for selections like browse category
	public static $defaultTertiaryBackgroundColor = '#F76E5E';
	public $tertiaryBackgroundColor;
	public /** @noinspection PhpUnused */
		$tertiaryBackgroundColorDefault;
	public static $defaultTertiaryForegroundColor = '#000000';
	public $tertiaryForegroundColor;
	public /** @noinspection PhpUnused */
		$tertiaryForegroundColorDefault;
	public $buttonRadius;
	public $smallButtonRadius;

	public static $defaultBadgeBackgroundColor = '#666666';
	public static $defaultBadgeForegroundColor = '#ffffff';
	public $badgeBackgroundColor;
	public /** @noinspection PhpUnused */
		$badgeBackgroundColorDefault;
	public $badgeForegroundColor;
	public /** @noinspection PhpUnused */
		$badgeForegroundColorDefault;
	public $badgeBorderRadius;

	//Colors for buttons
	public static $defaultDefaultButtonBackgroundColor = '#ffffff';
	public static $defaultDefaultButtonForegroundColor = '#333333';
	public static $defaultDefaultButtonBorderColor = '#cccccc';
	public static $defaultDefaultButtonHoverBackgroundColor = '#eeeeee';
	public static $defaultDefaultButtonHoverForegroundColor = '#333333';
	public static $defaultDefaultButtonHoverBorderColor = '#cccccc';
	public $defaultButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$defaultButtonBackgroundColorDefault;
	public $defaultButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$defaultButtonForegroundColorDefault;
	public $defaultButtonBorderColor;
	public /** @noinspection PhpUnused */
		$defaultButtonBorderColorDefault;
	public $defaultButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$defaultButtonHoverBackgroundColorDefault;
	public $defaultButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$defaultButtonHoverForegroundColorDefault;
	public $defaultButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$defaultButtonHoverBorderColorDefault;

	public static $defaultPrimaryButtonBackgroundColor = '#1b6ec2';
	public static $defaultPrimaryButtonForegroundColor = '#ffffff';
	public static $defaultPrimaryButtonBorderColor = '#1b6ec2';
	public static $defaultPrimaryButtonHoverBackgroundColor = '#ffffff';
	public static $defaultPrimaryButtonHoverForegroundColor = '#1b6ec2';
	public static $defaultPrimaryButtonHoverBorderColor = '#1b6ec2';
	public $primaryButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$primaryButtonBackgroundColorDefault;
	public $primaryButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$primaryButtonForegroundColorDefault;
	public $primaryButtonBorderColor;
	public /** @noinspection PhpUnused */
		$primaryButtonBorderColorDefault;
	public $primaryButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$primaryButtonHoverBackgroundColorDefault;
	public $primaryButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$primaryButtonHoverForegroundColorDefault;
	public $primaryButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$primaryButtonHoverBorderColorDefault;

	public static $defaultEditionsButtonBackgroundColor = '#f8f9fa';
	public static $defaultEditionsButtonForegroundColor = '#212529';
	public static $defaultEditionsButtonBorderColor = '#999999';
	public static $defaultEditionsButtonHoverBackgroundColor = '#ffffff';
	public static $defaultEditionsButtonHoverForegroundColor = '#1b6ec2';
	public static $defaultEditionsButtonHoverBorderColor = '#1b6ec2';
	public $editionsButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$editionsButtonBackgroundColorDefault;
	public $editionsButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$editionsButtonForegroundColorDefault;
	public $editionsButtonBorderColor;
	public /** @noinspection PhpUnused */
		$editionsButtonBorderColorDefault;
	public $editionsButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$editionsButtonHoverBackgroundColorDefault;
	public $editionsButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$editionsButtonHoverForegroundColorDefault;
	public $editionsButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$editionsButtonHoverBorderColorDefault;

	public static $defaultToolsButtonBackgroundColor = '#747474';
	public static $defaultToolsButtonForegroundColor = '#ffffff';
	public static $defaultToolsButtonBorderColor = '#636363';
	public static $defaultToolsButtonHoverBackgroundColor = '#636363';
	public static $defaultToolsButtonHoverForegroundColor = '#ffffff';
	public static $defaultToolsButtonHoverBorderColor = '#636363';
	public $toolsButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$toolsButtonBackgroundColorDefault;
	public $toolsButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$toolsButtonForegroundColorDefault;
	public $toolsButtonBorderColor;
	public /** @noinspection PhpUnused */
		$toolsButtonBorderColorDefault;
	public $toolsButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$toolsButtonHoverBackgroundColorDefault;
	public $toolsButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$toolsButtonHoverForegroundColorDefault;
	public $toolsButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$toolsButtonHoverBorderColorDefault;

	public static $defaultActionButtonBackgroundColor = '#1b6ec2';
	public static $defaultActionButtonForegroundColor = '#ffffff';
	public static $defaultActionButtonBorderColor = '#1b6ec2';
	public static $defaultActionButtonHoverBackgroundColor = '#ffffff';
	public static $defaultActionButtonHoverForegroundColor = '#1b6ec2';
	public static $defaultActionButtonHoverBorderColor = '#1b6ec2';
	public $actionButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$actionButtonBackgroundColorDefault;
	public $actionButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$actionButtonForegroundColorDefault;
	public $actionButtonBorderColor;
	public /** @noinspection PhpUnused */
		$actionButtonBorderColorDefault;
	public $actionButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$actionButtonHoverBackgroundColorDefault;
	public $actionButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$actionButtonHoverForegroundColorDefault;
	public $actionButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$actionButtonHoverBorderColorDefault;

	public static $defaultInfoButtonBackgroundColor = '#8cd2e7';
	public static $defaultInfoButtonForegroundColor = '#000000';
	public static $defaultInfoButtonBorderColor = '#999999';
	public static $defaultInfoButtonHoverBackgroundColor = '#ffffff';
	public static $defaultInfoButtonHoverForegroundColor = '#217e9b';
	public static $defaultInfoButtonHoverBorderColor = '#217e9b';
	public $infoButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$infoButtonBackgroundColorDefault;
	public $infoButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$infoButtonForegroundColorDefault;
	public $infoButtonBorderColor;
	public /** @noinspection PhpUnused */
		$infoButtonBorderColorDefault;
	public $infoButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$infoButtonHoverBackgroundColorDefault;
	public $infoButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$infoButtonHoverForegroundColorDefault;
	public $infoButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$infoButtonHoverBorderColorDefault;

	public static $defaultWarningButtonBackgroundColor = '#f4d03f';
	public static $defaultWarningButtonForegroundColor = '#000000';
	public static $defaultWarningButtonBorderColor = '#999999';
	public static $defaultWarningButtonHoverBackgroundColor = '#ffffff';
	public static $defaultWarningButtonHoverForegroundColor = '#8d6708';
	public static $defaultWarningButtonHoverBorderColor = '#8d6708';
	public $warningButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$warningButtonBackgroundColorDefault;
	public $warningButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$warningButtonForegroundColorDefault;
	public $warningButtonBorderColor;
	public /** @noinspection PhpUnused */
		$warningButtonBorderColorDefault;
	public $warningButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$warningButtonHoverBackgroundColorDefault;
	public $warningButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$warningButtonHoverForegroundColorDefault;
	public $warningButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$warningButtonHoverBorderColorDefault;

	public static $defaultDangerButtonBackgroundColor = '#D50000';
	public static $defaultDangerButtonForegroundColor = '#ffffff';
	public static $defaultDangerButtonBorderColor = '#999999';
	public static $defaultDangerButtonHoverBackgroundColor = '#ffffff';
	public static $defaultDangerButtonHoverForegroundColor = '#D50000';
	public static $defaultDangerButtonHoverBorderColor = '#D50000';
	public $dangerButtonBackgroundColor;
	public /** @noinspection PhpUnused */
		$dangerButtonBackgroundColorDefault;
	public $dangerButtonForegroundColor;
	public /** @noinspection PhpUnused */
		$dangerButtonForegroundColorDefault;
	public $dangerButtonBorderColor;
	public /** @noinspection PhpUnused */
		$dangerButtonBorderColorDefault;
	public $dangerButtonHoverBackgroundColor;
	public /** @noinspection PhpUnused */
		$dangerButtonHoverBackgroundColorDefault;
	public $dangerButtonHoverForegroundColor;
	public /** @noinspection PhpUnused */
		$dangerButtonHoverForegroundColorDefault;
	public $dangerButtonHoverBorderColor;
	public /** @noinspection PhpUnused */
		$dangerButtonHoverBorderColorDefault;

	//Top Menu
	public static $defaultMenubarBackgroundColor = '#f1f1f1';
	public $menubarBackgroundColor;
	public /** @noinspection PhpUnused */
		$menubarBackgroundColorDefault;
	public static $defaultMenubarForegroundColor = '#303030';
	public $menubarForegroundColor;
	public /** @noinspection PhpUnused */
		$menubarForegroundColorDefault;
	public static $defaultMenubarHighlightBackgroundColor = '#f1f1f1';
	public $menubarHighlightBackgroundColor;
	public /** @noinspection PhpUnused */
		$menubarHighlightBackgroundColorDefault;
	public static $defaultMenubarHighlightForegroundColor = '#265a87';
	public $menubarHighlightForegroundColor;
	public /** @noinspection PhpUnused */
		$menubarHighlightForegroundColorDefault;
	public static $defaultMenuDropdownBackgroundColor = '#ededed';
	public $menuDropdownBackgroundColor;
	public /** @noinspection PhpUnused */
		$menuDropdownBackgroundColorDefault;
	public static $defaultMenuDropdownForegroundColor = '#404040';
	public $menuDropdownForegroundColor;
	public /** @noinspection PhpUnused */
		$menuDropdownForegroundColorDefault;

	//Modal dialog
	public static $defaultModalDialogHeaderFooterBackgroundColor = '#ffffff';
	public $modalDialogHeaderFooterBackgroundColor;
	public /** @noinspection PhpUnused */
		$modalDialogHeaderFooterBackgroundColorDefault;
	public static $defaultModalDialogHeaderFooterForegroundColor = '#333333';
	public $modalDialogHeaderFooterForegroundColor;
	public /** @noinspection PhpUnused */
		$modalDialogHeaderFooterForegroundColorDefault;
	public static $defaultModalDialogBackgroundColor = '#ffffff';
	public $modalDialogBackgroundColor;
	public /** @noinspection PhpUnused */
		$modalDialogBackgroundColorDefault;
	public static $defaultModalDialogForegroundColor = '#333333';
	public $modalDialogForegroundColor;
	public /** @noinspection PhpUnused */
		$modalDialogForegroundColorDefault;
	public static $defaultModalDialogHeaderFooterBorderColor = '#e5e5e5';
	public $modalDialogHeaderFooterBorderColor;
	public /** @noinspection PhpUnused */
		$modalDialogHeaderFooterBorderColorDefault;

	//Browse Category Colors
	public static $defaultBrowseCategoryPanelColor = '#ffffff';
	public $browseCategoryPanelColor;
	public /** @noinspection PhpUnused */
		$browseCategoryPanelColorDefault;
	public static $defaultSelectedBrowseCategoryBackgroundColor = '#005C75';
	public $selectedBrowseCategoryBackgroundColor;
	public /** @noinspection PhpUnused */
		$selectedBrowseCategoryBackgroundColorDefault;
	public static $defaultSelectedBrowseCategoryForegroundColor = '#ffffff';
	public $selectedBrowseCategoryForegroundColor;
	public /** @noinspection PhpUnused */
		$selectedBrowseCategoryForegroundColorDefault;
	public static $defaultSelectedBrowseCategoryBorderColor = '#0087AB';
	public $selectedBrowseCategoryBorderColor;
	public /** @noinspection PhpUnused */
		$selectedBrowseCategoryBorderColorDefault;
	public static $defaultDeselectedBrowseCategoryBackgroundColor = '#005C75';
	public $deselectedBrowseCategoryBackgroundColor;
	public /** @noinspection PhpUnused */
		$deselectedBrowseCategoryBackgroundColorDefault;
	public static $defaultDeselectedBrowseCategoryForegroundColor = '#ffffff';
	public $deselectedBrowseCategoryForegroundColor;
	public /** @noinspection PhpUnused */
		$deselectedBrowseCategoryForegroundColorDefault;
	public static $defaultDeselectedBrowseCategoryBorderColor = '#0087AB';
	public $deselectedBrowseCategoryBorderColor;
	public /** @noinspection PhpUnused */
		$deselectedBrowseCategoryBorderColorDefault;
	public $capitalizeBrowseCategories;
	public $browseCategoryImageSize;
	public $browseImageLayout;
	public $accessibleBrowseCategories;

	//Panel Colors
	public static $defaultClosedPanelBackgroundColor = '#ffffff';
	public $closedPanelBackgroundColor;
	public /** @noinspection PhpUnused */
		$closedPanelBackgroundColorDefault;
	public static $defaultClosedPanelForegroundColor = '#333333';
	public $closedPanelForegroundColor;
	public /** @noinspection PhpUnused */
		$closedPanelForegroundColorDefault;
	public static $defaultOpenPanelBackgroundColor = '#ffffff';
	public $openPanelBackgroundColor;
	public /** @noinspection PhpUnused */
		$openPanelBackgroundColorDefault;
	public static $defaultOpenPanelForegroundColor = '#404040';
	public $openPanelForegroundColor;
	public /** @noinspection PhpUnused */
		$openPanelForegroundColorDefault;
	public static $defaultPanelBodyBackgroundColor = '#ffffff';
	public $panelBodyBackgroundColor;
	public /** @noinspection PhpUnused */
		$panelBodyBackgroundColorDefault;
	public static $defaultPanelBodyForegroundColor = '#404040';
	public $panelBodyForegroundColor;
	public /** @noinspection PhpUnused */
		$panelBodyForegroundColorDefault;

	//Tab Colors
	public static $defaultInactiveTabBackgroundColor = '#ffffff';
	public $inactiveTabBackgroundColor;
	public /** @noinspection PhpUnused */
		$inactiveTabBackgroundColorDefault;
	public static $defaultInactiveTabForegroundColor = '#6B6B6B';
	public $inactiveTabForegroundColor;
	public /** @noinspection PhpUnused */
		$inactiveTabForegroundColorDefault;
	public static $defaultActiveTabBackgroundColor = '#e7e7e7';
	public $activeTabBackgroundColor;
	public /** @noinspection PhpUnused */
		$activeTabBackgroundColorDefault;
	public static $defaultActiveTabForegroundColor = '#333333';
	public $activeTabForegroundColor;
	public /** @noinspection PhpUnused */
		$activeTabForegroundColorDefault;

	//Theme accessibility options
	public $isHighContrast;

	//Fonts
	public $headingFont;
	public $headingFontDefault;
	public $customHeadingFont;
	public $bodyFont;
	public $bodyFontDefault;
	public $customBodyFont;

	public $coverStyle;

	public $additionalCssType;
	public $additionalCss;

	public $generatedCss;

	//Cookie Consent Themeing Options
	public static $defaultCookieConsentBackgroundColor = '#1D7FF0';
	public $cookieConsentBackgroundColor;
	public /** @noinspection PhpUnused */
		$cookieConsentBackgroundColorDefault;

	public static $defaultCookieConsentButtonColor = '#1D7FF0';
	public $cookieConsentButtonColor;
	public /** @noinspection PhpUnused */
		$cookieConsentButtonColorDefault;

	public static $defaultCookieConsentButtonHoverColor = '#FF0000';
	public $cookieConsentButtonHoverColor;
	public /** @noinspection PhpUnused */
		$cookieConsentButtonHoverColorDefault;

	public static $defaultCookieConsentTextColor = '#FFFFFF';
	public $cookieConsentTextColor;
	public /** @noinspection PhpUnused */
		$cookieConsentTextColorDefault;

	public static $defaultCookieConsentButtonTextColor = '#FFFFFF';
	public $cookieConsentButtonTextColor;
	public /** @noinspection PhpUnused */
		$cookieConsentButtonTextColorDefault;

	public static $defaultCookieConsentButtonHoverTextColor = '#FFFFFF';
	public $cookieConsentButtonHoverTextColor;
	public /** @noinspection PhpUnused */
		$cookieConsentButtonHoverTextColorDefault;

	public static $defaultCookieConsentButtonBorderColor = '#FFFFFF';
	public $cookieConsentButtonBorderColor;
	public /** @noinspection PhpUnused */
		$cookieConsentButtonBorderColorDefault;

	/** @noinspection PhpUnused */
	public static int $defaultPlacardImageMaxHeight = 0;
	public $placardImageMaxHeight;


	protected $_libraries;
	protected $_locations;

	public function getNumericColumnNames(): array {
		return [
			'additionalCssType',
			'capitalizeBrowseCategories',
			'browseCategoryImageSize',
			'browseImageLayout',
			'headerLogoAlignmentApp',
			'placardImageMaxHeight',
		];
	}

	static function getObjectStructure($context = ''): array {
		$libraryThemeStructure = LibraryTheme::getObjectStructure($context);
		unset($libraryThemeStructure['themeId']);
		unset($libraryThemeStructure['weight']);

		$locationThemeStructure = LocationTheme::getObjectStructure($context);
		unset($locationThemeStructure['themeId']);
		unset($locationThemeStructure['weight']);

//		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Themes'));
//		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Themes'));

		//Load Valid Fonts
		$validHeadingFonts = [
			'Arimo',
			'Catamaran',
			'Gothic A1',
			'Gothic A1-Black',
			'Helvetica',
			'Helvetica Neue',
			'Josefin Sans',
			'Lato',
			'Merriweather',
			'Montserrat',
			'Noto Sans',
			'Open Sans',
			'PT Sans',
			'Raleway',
			'Roboto',
			'Rubik',
			'Source Sans Pro',
			'Ubuntu',
		];
		$validBodyFonts = [
			'Arimo',
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
			'Rubik',
			'Source Sans Pro',
			'Ubuntu',
		];

		$coverStyles = [
			"border" => 'Border / Picture Frame',
			"floating" => 'Shadow / Floating',
		];

		$headerBackgroundImageSizes = [
			"cover" => 'Cover',
			"contain" => 'Contain',
		];

		$headerBackgroundImageRepeat = [
			"no-repeat" => 'No Repeat',
			"repeat" => 'Repeat',
			"repeat-x" => 'Repeat X',
			"repeat-y" => 'Repeat Y',
		];

		$themesToExtend = [];
		$themesToExtend[''] = 'None';
		$theme = new Theme();
		$theme->find();
		while ($theme->fetch()) {
			$themesToExtend[$theme->themeName] = $theme->themeName;
		}

		$objectStructure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
				'uniqueProperty' => true,
			],
			'themeName' => [
				'property' => 'themeName',
				'type' => 'text',
				'label' => 'Theme Name',
				'description' => 'The Name of the Theme. This theme name will only display internally within Aspen settings.',
				'maxLength' => 50,
				'required' => true,
				'uniqueProperty' => true,
			],
			'displayName' => [
				'property' => 'displayName',
				'type' => 'text',
				'label' => 'Display Name',
				'description' => 'The Display Name of the Theme. This will display to users when more than one theme option is available for them to choose in their preferences. Examples: Default, High Contrast, Dark Mode, etc.',
				'maxLength' => 50,
				'required' => true,
				'uniqueProperty' => false,
			],
			'extendsTheme' => [
				'property' => 'extendsTheme',
				'type' => 'enum',
				'values' => $themesToExtend,
				'label' => 'Extends Theme',
				'description' => 'A theme that this overrides (leave blank if none is overridden)',
				'maxLength' => 50,
				'required' => false,
			],
			'fullWidth' => [
				'property' => 'fullWidth',
				'type' => 'checkbox',
				'label' => 'Make Header & Footer Full Width',
				'description' => 'Whether or not the header and footer should be full width',
				'required' => false,
				'hideInLists' => true,
			],
			'isHighContrast' => [
				'property' => 'isHighContrast',
				'type' => 'checkbox',
				'label' => 'High Contrast Theme',
				'description' => 'Do not enable this option for your primary/default theme! Enabling this option will add some accessibility styling enhancements.',
                'note' => 'Enabling this option will add accessibility and styling enhancements to a High Contrast theme. Not recommended for your default/primary theme.',
				'required' => false,
			],
			'logoName' => [
				'property' => 'logoName',
				'type' => 'image',
				'label' => 'Logo (1140 x 225px max) - (250 x 100px max if showing library name in header)',
				'description' => 'The logo for use in the header',
				'required' => false,
				'thumbWidth' => 750,
				'maxWidth' => 1170,
				'maxHeight' => 250,
				'hideInLists' => true,
			],
			'favicon' => [
				'property' => 'favicon',
				'type' => 'image',
				'label' => 'favicon (32px x 32px max)',
				'description' => 'The icon for use in the browser tab (.jpg and .png files supported)',
				'required' => false,
				'maxWidth' => 32,
				'maxHeight' => 32,
				'hideInLists' => true,
			],
			'defaultCover' => [
				'property' => 'defaultCover',
				'type' => 'image',
				'label' => 'Background Image for Default Covers (280x280)',
				'description' => 'A background image for default covers (.jpg or .png only)',
				'required' => false,
				'maxWidth' => 280,
				'maxHeight' => 280,
				'hideInLists' => true,
			],
			'coverStyle' => [
				'property' => 'coverStyle',
				'type' => 'enum',
				'values' => $coverStyles,
				'label' => 'Cover Image Style',
				'description' => 'Choose a style for cover images throughout the catalog.',
				'required' => false,
				'hideInLists' => true,
				'default' => 'floating'
			],
			//Overall page colors
			'pageBackgroundColor' => [
				'property' => 'pageBackgroundColor',
				'type' => 'color',
				'label' => 'Page Background Color',
				'description' => 'Page Background Color behind all content',
				'required' => false,
				'hideInLists' => true,
				'default' => '#ffffff',
				'serverValidation' => 'validateColorContrast',
			],
			'bodyBackgroundColor' => [
				'property' => 'bodyBackgroundColor',
				'type' => 'color',
				'label' => 'Body Background Color',
				'description' => 'Body Background Color for main content',
				'required' => false,
				'hideInLists' => true,
				'default' => '#ffffff',
				'checkContrastWith' => 'bodyTextColor',
			],
			'bodyTextColor' => [
				'property' => 'bodyTextColor',
				'type' => 'color',
				'label' => 'Body Text Color',
				'description' => 'Body Text Color for main content',
				'required' => false,
				'hideInLists' => true,
				'default' => '#6B6B6B',
				'checkContrastWith' => 'bodyBackgroundColor',
			],
			'linkColor' => [
				'property' => 'linkColor',
				'type' => 'color',
				'label' => 'Link Color',
				'description' => 'Color of Links',
				'required' => false,
				'hideInLists' => true,
				'default' => '#3174AF',
				'checkContrastWith' => 'bodyBackgroundColor',
				'checkContrastOneWay' => true,
			],
			'linkHoverColor' => [
				'property' => 'linkHoverColor',
				'type' => 'color',
				'label' => 'Link Hover Color',
				'description' => 'Color of Links when being hovered over',
				'required' => false,
				'hideInLists' => true,
				'default' => '#265a87',
				'checkContrastWith' => 'bodyBackgroundColor',
				'checkContrastOneWay' => true,
			],
			'resultLabelColor' => [
				'property' => 'resultLabelColor',
				'type' => 'color',
				'label' => 'Result Label Color',
				'description' => 'Color of Labels within Results',
				'required' => false,
				'hideInLists' => true,
				'default' => '#44484a',
				'checkContrastWith' => 'bodyBackgroundColor',
				'checkContrastOneWay' => true,
			],
			'resultValueColor' => [
				'property' => 'resultValueColor',
				'type' => 'color',
				'label' => 'Result Value Color',
				'description' => 'Color of Values within Results',
				'required' => false,
				'hideInLists' => true,
				'default' => '#6B6B6B',
				'checkContrastWith' => 'bodyBackgroundColor',
				'checkContrastOneWay' => true,
			],

			//Header Colors
			'headerBackgroundColor' => [
				'property' => 'headerBackgroundColor',
				'type' => 'color',
				'label' => 'Header Background Color',
				'description' => 'Header Background Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#ffffff',
				'checkContrastWith' => 'headerForegroundColor',
			],
			'headerForegroundColor' => [
				'property' => 'headerForegroundColor',
				'type' => 'color',
				'label' => 'Header Text Color',
				'description' => 'Header Foreground Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#303030',
				'checkContrastWith' => 'headerBackgroundColor',
			],
			'headerBottomBorderWidth' => [
				'property' => 'headerBottomBorderWidth',
				'type' => 'text',
				'label' => 'Header Bottom Border Width',
				'description' => 'Header Bottom Border Width',
				'required' => false,
				'hideInLists' => true,
			],

			'headerBackgroundImage' => [
				'property' => 'headerBackgroundImage',
				'type' => 'image',
				'label' => 'Header Background Image',
				'description' => 'Use an image as a background for the header.',
				'required' => false,
				'hideInLists' => true,
				'thumbWidth' => 750,
				'maxWidth' => 1170,
			],
			'headerBackgroundImageSize' => [
				'property' => 'headerBackgroundImageSize',
				'type' => 'enum',
				'values' => $headerBackgroundImageSizes,
				'label' => 'Header Background Image Fit',
				'description' => 'Choose how the header background image displays. Cover = image will stretch to fit the entire header space. Contain = image size will not be adjusted. ',
				'required' => false,
				'hideInLists' => true,
				'default' => 'cover',
			],
			'headerBackgroundImageRepeat' => [
				'property' => 'headerBackgroundImageRepeat',
				'type' => 'enum',
				'values' => $headerBackgroundImageRepeat,
				'label' => 'Header Background Image Repeat',
				'description' => 'These options will allow the header background image to repeat horizontally or vertically.',
				'required' => false,
				'hideInLists' => true,
				'default' => 'no-repeat',
			],

			//Breadcrumbs
			'breadcrumbsBackgroundColor' => [
				'property' => 'breadcrumbsBackgroundColor',
				'type' => 'color',
				'label' => 'Breadcrumbs Background Color',
				'description' => 'Breadcrumbs Background Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#f5f5f5',
				'checkContrastWith' => 'breadcrumbsForegroundColor',
			],
			'breadcrumbsForegroundColor' => [
				'property' => 'breadcrumbsForegroundColor',
				'type' => 'color',
				'label' => 'Breadcrumbs Text Color',
				'description' => 'Breadcrumbs Foreground Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#6B6B6B',
				'checkContrastWith' => 'breadcrumbsBackgroundColor',
			],

			//Breadcrumbs
			'searchToolsBackgroundColor' => [
				'property' => 'searchToolsBackgroundColor',
				'type' => 'color',
				'label' => 'Search Tools Background Color',
				'description' => 'Search Tools Background Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#f5f5f5',
				'checkContrastWith' => 'searchToolsForegroundColor',
			],
			'searchToolsForegroundColor' => [
				'property' => 'searchToolsForegroundColor',
				'type' => 'color',
				'label' => 'Search Tools Text Color',
				'description' => 'Search Tools Foreground Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#6B6B6B',
				'checkContrastWith' => 'searchToolsBackgroundColor',
			],
			'searchToolsBorderColor' => [
				'property' => 'searchToolsBorderColor',
				'type' => 'color',
				'label' => 'Search Tools Border Color',
				'description' => 'Search Tools Border Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#e3e3e3',
			],

			//Footer Colors
			'footerBackgroundColor' => [
				'property' => 'footerBackgroundColor',
				'type' => 'color',
				'label' => 'Footer Background Color',
				'description' => 'Footer Background Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#f1f1f1',
				'checkContrastWith' => 'footerForegroundColor',
			],
			'footerForegroundColor' => [
				'property' => 'footerForegroundColor',
				'type' => 'color',
				'label' => 'Footer Text Color',
				'description' => 'Footer Foreground Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#303030',
				'checkContrastWith' => 'footerBackgroundColor',
			],
			'footerImage' => [
				'property' => 'footerLogo',
				'type' => 'image',
				'label' => 'Footer Image (250px x 150px max)',
				'description' => 'An image to be displayed in the footer',
				'required' => false,
				'maxWidth' => 250,
				'maxHeight' => 150,
				'hideInLists' => true,
			],
			'footerImageLink' => [
				'property' => 'footerLogoLink',
				'type' => 'url',
				'label' => 'Footer Image Link',
				'description' => 'A link to be added to the footer logo',
				'required' => false,
				'hideInLists' => true,
			],
			'footerImageAlt' => [
				'property' => 'footerLogoAlt',
				'type' => 'text',
				'label' => 'Footer Image Alternative Text',
				'description' => 'The text to be used for screen readers',
				'required' => false,
				'hideInLists' => true,
			],

			//Primary Color
			'primaryBackgroundColor' => [
				'property' => 'primaryBackgroundColor',
				'type' => 'color',
				'label' => 'Primary Background Color',
				'description' => 'Primary Background Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#0a7589',
				'checkContrastWith' => 'primaryForegroundColor',
			],
			'primaryForegroundColor' => [
				'property' => 'primaryForegroundColor',
				'type' => 'color',
				'label' => 'Primary Text Color',
				'description' => 'Primary Foreground Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#ffffff',
				'checkContrastWith' => 'primaryBackgroundColor',
			],

			//Secondary Color
			'secondaryBackgroundColor' => [
				'property' => 'secondaryBackgroundColor',
				'type' => 'color',
				'label' => 'Secondary Background Color',
				'description' => 'Secondary Background Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#de9d03',
				'checkContrastWith' => 'secondaryForegroundColor',
			],
			'secondaryForegroundColor' => [
				'property' => 'secondaryForegroundColor',
				'type' => 'color',
				'label' => 'Secondary Text Color',
				'description' => 'Secondary Foreground Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#303030',
				'checkContrastWith' => 'secondaryBackgroundColor',
			],

			//Tertiary Color
			'tertiaryBackgroundColor' => [
				'property' => 'tertiaryBackgroundColor',
				'type' => 'color',
				'label' => 'Tertiary Background Color',
				'description' => 'Tertiary Background Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#F76E5E',
				'checkContrastWith' => 'tertiaryForegroundColor',
			],
			'tertiaryForegroundColor' => [
				'property' => 'tertiaryForegroundColor',
				'type' => 'color',
				'label' => 'Tertiary Text Color',
				'description' => 'Tertiary Foreground Color',
				'required' => false,
				'hideInLists' => true,
				'default' => '#000000',
				'checkContrastWith' => 'tertiaryBackgroundColor',
			],

			'headingFont' => [
				'property' => 'headingFont',
				'type' => 'font',
				'label' => 'Heading Font',
				'description' => 'Heading Font',
				'validFonts' => $validHeadingFonts,
				'previewFontSize' => '20px',
				'required' => false,
				'hideInLists' => true,
				'default' => 'Ubuntu',
			],
			'customHeadingFont' => [
				'property' => 'customHeadingFont',
				'type' => 'uploaded_font',
				'label' => 'Custom Heading Font',
				'description' => 'Upload a custom font to use for headings',
				'required' => false,
				'hideInLists' => true,
			],
			'bodyFont' => [
				'property' => 'bodyFont',
				'type' => 'font',
				'label' => 'Body Font',
				'description' => 'Body Font',
				'validFonts' => $validBodyFonts,
				'previewFontSize' => '14px',
				'required' => false,
				'hideInLists' => true,
				'default' => 'Lato',
			],
			'customBodyFont' => [
				'property' => 'customBodyFont',
				'type' => 'uploaded_font',
				'label' => 'Custom Body Font',
				'description' => 'Upload a custom font to use for the body',
				'required' => false,
				'hideInLists' => true,
			],

			//Additional CSS
			'additionalCss' => [
				'property' => 'additionalCss',
				'type' => 'textarea',
				'label' => 'Additional CSS',
				'description' => 'Additional CSS to apply to the interface',
				'required' => false,
				'hideInLists' => true,
			],
			'additionalCssType' => [
				'property' => 'additionalCssType',
				'type' => 'enum',
				'values' => [
					'0' => 'Append to parent css',
					'1' => 'Override parent css',
				],
				'label' => 'Additional CSS Application',
				'description' => 'How to apply css to the theme',
				'required' => false,
				'default' => 0,
				'hideInLists' => true,
			],

			//Aspen LiDA
			'lidaSection' => [
				'property' => 'lidaSection',
				'type' => 'section',
				'label' => 'Aspen LiDA',
				'hideInLists' => true,
				'properties' => [
					'logoApp' => [
						'property' => 'logoApp',
						'type' => 'image',
						'label' => 'Logo for Aspen LiDA (512x512 pixels)',
						'description' => 'The logo for use in Aspen LiDA. If none provided, Aspen will use the favicon.',
						'required' => false,
						'thumbWidth' => 180,
						'maxWidth' => 512,
						'maxHeight' => 512,
						'hideInLists' => true,
					],
					'headerLogoApp' => [
						'property' => 'headerLogoApp',
						'type' => 'image',
						'label' => 'Logo to show above the screen title in LiDA (1536x200 pixels)',
						'description' => 'The logo to display above the title in Aspen LiDA. If none provided, LiDA will only show the screen title.',
						'required' => false,
						'thumbWidth' => 180,
						'maxWidth' => 1536,
						'maxHeight' => 200,
						'hideInLists' => true,
					],
					'headerLogoAlignmentApp' => [
						'property' => 'headerLogoAlignmentApp',
						'type' => 'enum',
						'values' => [
							1 => 'left',
							2 => 'center',
							3 => 'right'
						],
						'label' => 'Header Logo Alignment',
						'description' => 'The alignment of the logo within Aspen LiDA.',
						'default' => 2,
						'hideInLists' => true,
					],
					'headerLogoBackgroundColorApp' => [
						'property' => 'headerLogoBackgroundColorApp',
						'type' => 'color',
						'label' => 'Header Logo Background Color',
						'description' => 'The background color to show behind the header logo',
						'required' => false,
						'hideInLists' => true,
						'default' => '#FFFFFF',
					],
				],
			],

			//Menu
			'menuSection' => [
				'property' => 'menuSection',
				'type' => 'section',
				'label' => 'Menu',
				'hideInLists' => true,
				'properties' => [
					'menubarBackgroundColor' => [
						'property' => 'menubarBackgroundColor',
						'type' => 'color',
						'label' => 'Menubar Background Color',
						'description' => 'Menubar Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#f1f1f1',
						'checkContrastWith' => 'menubarForegroundColor',
					],
					'menubarForegroundColor' => [
						'property' => 'menubarForegroundColor',
						'type' => 'color',
						'label' => 'Menubar Text Color',
						'description' => 'Menubar Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#303030',
						'checkContrastWith' => 'menubarBackgroundColor',
					],
					'menubarHighlightBackgroundColor' => [
						'property' => 'menubarHighlightBackgroundColor',
						'type' => 'color',
						'label' => 'Menubar Highlight Background Color',
						'description' => 'Menubar Highlight Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#f1f1f1',
						'checkContrastWith' => 'menubarHighlightForegroundColor',
					],
					'menubarHighlightForegroundColor' => [
						'property' => 'menubarHighlightForegroundColor',
						'type' => 'color',
						'label' => 'Menubar Highlight Text Color',
						'description' => 'Menubar Highlight Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#265a87',
						'checkContrastWith' => 'menubarHighlightBackgroundColor',
					],
					'menuDropdownBackgroundColor' => [
						'property' => 'menuDropdownBackgroundColor',
						'type' => 'color',
						'label' => 'Menu Dropdown Background Color',
						'description' => 'Menubar Dropdown Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ededed',
						'checkContrastWith' => 'menuDropdownForegroundColor',
					],
					'menuDropdownForegroundColor' => [
						'property' => 'menuDropdownForegroundColor',
						'type' => 'color',
						'label' => 'Menu Dropdown Text Color',
						'description' => 'Menubar Dropdown Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#404040',
						'checkContrastWith' => 'menuDropdownBackgroundColor',
					],
				],
			],

			/*
			'modalDialogSection' => [
				'property' => 'modalDialogSection',
				'type' => 'section',
				'label' => 'Modal Dialog',
				'hideInLists' => true,
				'properties' => [
					'modalDialogBackgroundColor' => [
						'property' => 'modalDialogBackgroundColor',
						'type' => 'color',
						'label' => 'Background Color',
						'description' => 'Modal Dialog Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
						'checkContrastWith' => 'modalDialogForegroundColor',
					],
					'modalDialogForegroundColor' => [
						'property' => 'modalDialogForegroundColor',
						'type' => 'color',
						'label' => 'Text Color',
						'description' => 'Modal Dialog Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#333333',
						'checkContrastWith' => 'modalDialogBackgroundColor',
					],
					'modalDialogHeaderFooterBackgroundColor' => [
						'property' => 'modalDialogHeaderFooterBackgroundColor',
						'type' => 'color',
						'label' => 'Header/Footer Background Color',
						'description' => 'Modal Dialog Header & Footer Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
						'checkContrastWith' => 'modalDialogHeaderFooterForegroundColor',
					],
					'modalDialogHeaderFooterForegroundColor' => [
						'property' => 'modalDialogHeaderFooterForegroundColor',
						'type' => 'color',
						'label' => 'Header/Footer Text Color',
						'description' => 'Modal Dialog Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#333333',
						'checkContrastWith' => 'modalDialogHeaderFooterBackgroundColor',
					],
					'modalDialogHeaderFooterBorderColor' => [
						'property' => 'modalDialogHeaderFooterBorderColor',
						'type' => 'color',
						'label' => 'Header/Footer Border',
						'description' => 'The color of the border between the header and footer and the content',
						'required' => false,
						'hideInLists' => true,
						'default' => '#e5e5e5',
					],
				],
			],*/

			//Format Category Facet Theming
			'formatCategorySection' => [
				'property' => 'formatCategorySection',
				'type' => 'section',
				'label' => 'Format Category Icons',
				'hideInLists' => true,
				'properties' => [
					'booksImage' => [
						'property' => 'booksImage',
						'type' => 'image',
						'label' => 'Book Icon (50x50px max)',
						'description' => 'An image for the book format category icon',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'booksImageSelected' => [
						'property' => 'booksImageSelected',
						'type' => 'image',
						'label' => 'Book Icon Selected (50x50px max)',
						'description' => 'An image for the book format category icon when selected',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'eBooksImage' => [
						'property' => 'eBooksImage',
						'type' => 'image',
						'label' => 'eBook Icon (50x50px max)',
						'description' => 'An image for the eBook format category icon',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'eBooksImageSelected' => [
						'property' => 'eBooksImageSelected',
						'type' => 'image',
						'label' => 'eBook Icon Selected (50x50px max)',
						'description' => 'An image for the eBook format category icon when selected',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'audioBooksImage' => [
						'property' => 'audioBooksImage',
						'type' => 'image',
						'label' => 'Audio Book Icon (50x50px max)',
						'description' => 'An image for the audio book format category icon',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'audioBooksImageSelected' => [
						'property' => 'audioBooksImageSelected',
						'type' => 'image',
						'label' => 'Audio Book Icon Selected (50x50px max)',
						'description' => 'An image for the audio book format category icon when selected',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'musicImage' => [
						'property' => 'musicImage',
						'type' => 'image',
						'label' => 'Music Icon (50x50px max)',
						'description' => 'An image for the music format category icon',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'musicImageSelected' => [
						'property' => 'musicImageSelected',
						'type' => 'image',
						'label' => 'Music Icon Selected (50x50px max)',
						'description' => 'An image for the music format category icon when selected',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'moviesImage' => [
						'property' => 'moviesImage',
						'type' => 'image',
						'label' => 'Movie Icon (50x50px max)',
						'description' => 'An image for the movie format category icon',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
					'moviesImageSelected' => [
						'property' => 'moviesImageSelected',
						'type' => 'image',
						'label' => 'Movie Icon Selected (50x50px max)',
						'description' => 'An image for the movie format category icon when selected',
						'required' => false,
						'maxWidth' => 50,
						'maxHeight' => 50,
						'hideInLists' => true,
					],
				],
			],
			//Format Category Facet Theming
			'exploreMoreImageSection' => [
				'property' => 'exploreMoreImageSection',
				'type' => 'section',
				'label' => 'Explore More Images',
				'hideInLists' => true,
				'properties' => [
					'catalogImage' => [
						'property' => 'catalogImage',
						'type' => 'image',
						'label' => 'Library Catalog (400x400px max)',
						'description' => 'An image for the library catalog in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
					'genealogyImage' => [
						'property' => 'genealogyImage',
						'type' => 'image',
						'label' => 'Genealogy (400x400px max)',
						'description' => 'An image for genealogy results in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
					'articlesDBImage' => [
						'property' => 'articlesDBImage',
						'type' => 'image',
						'label' => 'Articles and Databases (400x400px max)',
						'description' => 'An image for article and database results in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
					'eventsImage' => [
						'property' => 'eventsImage',
						'type' => 'image',
						'label' => 'Events (400x400px max)',
						'description' => 'An image for event results in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
					'listsImage' => [
						'property' => 'listsImage',
						'type' => 'image',
						'label' => 'Lists (400x400px max)',
						'description' => 'An image for list results in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
					'seriesImage' => [
						'property' => 'seriesImage',
						'type' => 'image',
						'label' => 'Series (400x400px max)',
						'description' => 'An image for series results in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
					'libraryWebsiteImage' => [
						'property' => 'libraryWebsiteImage',
						'type' => 'image',
						'label' => 'Library Website (400x400px max)',
						'description' => 'An image for website results in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
					'historyArchivesImage' => [
						'property' => 'historyArchivesImage',
						'type' => 'image',
						'label' => 'History and Archives (400x400px max)',
						'description' => 'An image for history and archive results in Explore More',
						'required' => false,
						'maxWidth' => 400,
						'maxHeight' => 400,
						'hideInLists' => true,
					],
				],
			],
			//Browse category theming
			'browseCategorySection' => [
				'property' => 'browseCategorySection',
				'type' => 'section',
				'label' => 'Browse Categories',
				'hideInLists' => true,
				'properties' => [
					'browseCategoryPanelColor' => [
						'property' => 'browseCategoryPanelColor',
						'type' => 'color',
						'label' => 'Browse Category Panel Color',
						'description' => 'Background Color of the Browse Category Panel',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
					],

					'selectedBrowseCategoryBackgroundColor' => [
						'property' => 'selectedBrowseCategoryBackgroundColor',
						'type' => 'color',
						'label' => 'Selected Browse Category Background Color',
						'description' => 'Selected Browse Category Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#005C75',
						'checkContrastWith' => 'selectedBrowseCategoryForegroundColor',
					],
					'selectedBrowseCategoryForegroundColor' => [
						'property' => 'selectedBrowseCategoryForegroundColor',
						'type' => 'color',
						'label' => 'Selected Browse Category Text Color',
						'description' => 'Selected Browse Category Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
						'checkContrastWith' => 'selectedBrowseCategoryBackgroundColor',
					],
					'selectedBrowseCategoryBorderColor' => [
						'property' => 'selectedBrowseCategoryBorderColor',
						'type' => 'color',
						'label' => 'Selected Browse Category Border Color',
						'description' => 'Selected Browse Category Border Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#0087AB',
					],

					'deselectedBrowseCategoryBackgroundColor' => [
						'property' => 'deselectedBrowseCategoryBackgroundColor',
						'type' => 'color',
						'label' => 'Deselected Browse Category Background Color',
						'description' => 'Deselected Browse Category Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#005C75',
						'checkContrastWith' => 'deselectedBrowseCategoryForegroundColor',
					],
					'deselectedBrowseCategoryForegroundColor' => [
						'property' => 'deselectedBrowseCategoryForegroundColor',
						'type' => 'color',
						'label' => 'Deselected Browse Category Text Color',
						'description' => 'Deselected Browse Category Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
						'checkContrastWith' => 'deselectedBrowseCategoryBackgroundColor',
					],
					'deselectedBrowseCategoryBorderColor' => [
						'property' => 'deselectedBrowseCategoryBorderColor',
						'type' => 'color',
						'label' => 'Deselected Browse Category Border Color',
						'description' => 'Deselected Browse Category Border Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#0087AB',
					],

					'capitalizeBrowseCategories' => [
						'property' => 'capitalizeBrowseCategories',
						'type' => 'enum',
						'values' => [
							-1 => 'Default',
							0 => 'Maintain case',
							1 => 'Force Uppercase',
						],
						'label' => 'Capitalize Browse Categories',
						'description' => 'How to treat capitalization of browse category names',
						'required' => false,
						'hideInLists' => true,
						'default' => '-1',
					],
					'browseCategoryImageSize' => [
						'property' => 'browseCategoryImageSize',
						'type' => 'enum',
						'values' => [
							0 => 'Medium',
							1 => 'Large',
						],
						'label' => 'Browse Category Image Size',
						'description' => 'The size of cover images to be displayed in browse categories',
						'required' => false,
						'hideInLists' => true,
						'default' => '0',
					],
					'browseImageLayout' => [
						'property' => 'browseImageLayout',
						'type' => 'enum',
						'values' => [
							0 => 'Masonry',
							1 => 'Grid',
						],
						'label' => 'Browse Category Image Layout',
						'description' => 'The layout of cover images in browse categories. Masonry has no fixed row heights, so maximizes space by reducing unnecessary vertical gaps between cover images. Grid layout will maintain fixed heights for rows, regardless of cover image dimensions.',
						'required' => false,
						'hidInLists' => true,
						'default' => '0',
					],
					'accessibleBrowseCategories' => [
						'property' => 'accessibleBrowseCategories',
						'type' => 'checkbox',
						'label' => 'Use Accessible Layout',
						'note' => 'Does not apply the Browse Category Image Layout preference',
						'required' => false,
					],
				],
			],

			'badges' => [
				'property' => 'badgesSection',
				'type' => 'section',
				'label' => 'Badges',
				'hideInLists' => true,
				'properties' => [
					'badgeBackgroundColor' => [
						'property' => 'badgeBackgroundColor',
						'type' => 'color',
						'label' => 'Badge Background Color',
						'description' => 'Badge Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => Theme::$defaultBadgeBackgroundColor,
						'checkContrastWith' => 'badgeForegroundColor',
					],
					'badgeForegroundColor' => [
						'property' => 'badgeForegroundColor',
						'type' => 'color',
						'label' => 'Badge Text Color',
						'description' => 'Badge Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => Theme::$defaultBadgeForegroundColor,
						'checkContrastWith' => 'badgeBackgroundColor',
					],
					'badgeBorderRadius' => [
						'property' => 'badgeBorderRadius',
						'type' => 'text',
						'label' => 'Badge Border Radius',
						'description' => 'Badge Border Radius',
						'required' => false,
						'hideInLists' => true,
					],
				],
			],

			'panels' => [
				'property' => 'panelsSection',
				'type' => 'section',
				'label' => 'Panels',
				'hideInLists' => true,
				'properties' => [
					'closedPanelBackgroundColor' => [
						'property' => 'closedPanelBackgroundColor',
						'type' => 'color',
						'label' => 'Closed Panel Background Color',
						'description' => 'Panel Background Color while closed',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
						'checkContrastWith' => 'closedPanelForegroundColor',
					],
					'closedPanelForegroundColor' => [
						'property' => 'closedPanelForegroundColor',
						'type' => 'color',
						'label' => 'Closed Panel Text Color',
						'description' => 'Panel Foreground Color while closed',
						'required' => false,
						'hideInLists' => true,
						'default' => '#333333',
						'checkContrastWith' => 'closedPanelBackgroundColor',
					],
					'openPanelBackgroundColor' => [
						'property' => 'openPanelBackgroundColor',
						'type' => 'color',
						'label' => 'Open Panel Background Color',
						'description' => 'Panel Category Background Color while open',
						'required' => false,
						'hideInLists' => true,
						'default' => '#4DACDE',
						'checkContrastWith' => 'openPanelForegroundColor',
					],
					'openPanelForegroundColor' => [
						'property' => 'openPanelForegroundColor',
						'type' => 'color',
						'label' => 'Open Panel Text Color',
						'description' => 'Panel Category Foreground Color while open',
						'required' => false,
						'hideInLists' => true,
						'default' => '#303030',
						'checkContrastWith' => 'openPanelBackgroundColor',
					],
					'panelBodyBackgroundColor' => [
						'property' => 'panelBodyBackgroundColor',
						'type' => 'color',
						'label' => 'Panel Body Background Color',
						'description' => 'Panel Body Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
						'checkContrastWith' => 'panelBodyForegroundColor',
					],
					'panelBodyForegroundColor' => [
						'property' => 'panelBodyForegroundColor',
						'type' => 'color',
						'label' => 'Panel Body Text Color',
						'description' => 'Panel Body Foreground Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#404040',
						'checkContrastWith' => 'panelBodyBackgroundColor',
					],
				],
			],

			'buttonSection' => [
				'property' => 'buttonSection',
				'type' => 'section',
				'label' => 'Buttons',
				'hideInLists' => true,
				'properties' => [
					'buttonRadius' => [
						'property' => 'buttonRadius',
						'type' => 'text',
						'label' => 'Button Radius',
						'description' => 'Button Radius',
						'required' => false,
						'hideInLists' => true,
					],
					'smallButtonRadius' => [
						'property' => 'smallButtonRadius',
						'type' => 'text',
						'label' => 'Small Button Radius',
						'description' => 'Small Button Radius',
						'required' => false,
						'hideInLists' => true,
					],

					'defaultButtonSection' => [
						'property' => 'defaultButtonSection',
						'type' => 'section',
						'label' => 'Default Button',
						'hideInLists' => true,
						'properties' => [
							'defaultButtonBackgroundColor' => [
								'property' => 'defaultButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Button Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDefaultButtonBackgroundColor,
								'checkContrastWith' => 'defaultButtonForegroundColor',
							],
							'defaultButtonForegroundColor' => [
								'property' => 'defaultButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Button Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDefaultButtonForegroundColor,
								'checkContrastWith' => 'defaultButtonBackgroundColor',
							],
							'defaultButtonBorderColor' => [
								'property' => 'defaultButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Button Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDefaultButtonBorderColor,
							],
							'defaultButtonHoverBackgroundColor' => [
								'property' => 'defaultButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Button Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDefaultButtonHoverBackgroundColor,
								'checkContrastWith' => 'defaultButtonHoverForegroundColor',
							],
							'defaultButtonHoverForegroundColor' => [
								'property' => 'defaultButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Button Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDefaultButtonHoverForegroundColor,
								'checkContrastWith' => 'defaultButtonHoverBackgroundColor',
							],
							'defaultButtonHoverBorderColor' => [
								'property' => 'defaultButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Button Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDefaultButtonHoverBorderColor,
							],
						],
					],
					'primaryButtonSection' => [
						'property' => 'primaryButtonSection',
						'type' => 'section',
						'label' => 'Primary Button',
						'hideInLists' => true,
						'properties' => [
							'primaryButtonBackgroundColor' => [
								'property' => 'primaryButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultPrimaryButtonBackgroundColor,
								'checkContrastWith' => 'primaryButtonForegroundColor',
							],
							'primaryButtonForegroundColor' => [
								'property' => 'primaryButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultPrimaryButtonForegroundColor,
								'checkContrastWith' => 'primaryButtonBackgroundColor',
							],
							'primaryButtonBorderColor' => [
								'property' => 'primaryButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultPrimaryButtonBorderColor,
							],
							'primaryButtonHoverBackgroundColor' => [
								'property' => 'primaryButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultPrimaryButtonHoverBackgroundColor,
								'checkContrastWith' => 'primaryButtonHoverForegroundColor',
							],
							'primaryButtonHoverForegroundColor' => [
								'property' => 'primaryButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultPrimaryButtonHoverForegroundColor,
								'checkContrastWith' => 'primaryButtonHoverBackgroundColor',
							],
							'primaryButtonHoverBorderColor' => [
								'property' => 'primaryButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultPrimaryButtonHoverBorderColor,
							],
						],
					],

					'actionButtonSection' => [
						'property' => 'actionButtonSection',
						'type' => 'section',
						'label' => 'Action Button (Place hold, checkout, access online, etc)',
						'hideInLists' => true,
						'properties' => [
							'actionButtonBackgroundColor' => [
								'property' => 'actionButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultActionButtonBackgroundColor,
								'checkContrastWith' => 'actionButtonForegroundColor',
							],
							'actionButtonForegroundColor' => [
								'property' => 'actionButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultActionButtonForegroundColor,
								'checkContrastWith' => 'actionButtonBackgroundColor',
							],
							'actionButtonBorderColor' => [
								'property' => 'actionButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultActionButtonBorderColor,
							],
							'actionButtonHoverBackgroundColor' => [
								'property' => 'actionButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultActionButtonHoverBackgroundColor,
								'checkContrastWith' => 'actionButtonHoverForegroundColor',
							],
							'actionButtonHoverForegroundColor' => [
								'property' => 'actionButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultActionButtonHoverForegroundColor,
								'checkContrastWith' => 'actionButtonHoverBackgroundColor',
							],
							'actionButtonHoverBorderColor' => [
								'property' => 'actionButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultActionButtonHoverBorderColor,
							],
						],
					],

					'editionsButtonSection' => [
						'property' => 'editionsButtonSection',
						'type' => 'section',
						'label' => 'Editions Button',
						'hideInLists' => true,
						'properties' => [
							'editionsButtonBackgroundColor' => [
								'property' => 'editionsButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultEditionsButtonBackgroundColor,
								'checkContrastWith' => 'editionsButtonForegroundColor',
							],
							'editionsButtonForegroundColor' => [
								'property' => 'editionsButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultEditionsButtonForegroundColor,
								'checkContrastWith' => 'editionsButtonBackgroundColor',
							],
							'editionsButtonBorderColor' => [
								'property' => 'editionsButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultEditionsButtonBorderColor,
							],
							'editionsButtonHoverBackgroundColor' => [
								'property' => 'editionsButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultEditionsButtonHoverBackgroundColor,
								'checkContrastWith' => 'editionsButtonHoverForegroundColor',
							],
							'editionsButtonHoverForegroundColor' => [
								'property' => 'editionsButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultEditionsButtonHoverForegroundColor,
								'checkContrastWith' => 'editionsButtonHoverBackgroundColor',
							],
							'editionsButtonHoverBorderColor' => [
								'property' => 'editionsButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultEditionsButtonHoverBorderColor,
							],
						],
					],

					'toolsButtonSection' => [
						'property' => 'toolsButtonSection',
						'type' => 'section',
						'label' => 'Tools Button',
						'hideInLists' => true,
						'properties' => [
							'toolsButtonBackgroundColor' => [
								'property' => 'toolsButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultToolsButtonBackgroundColor,
								'checkContrastWith' => 'toolsButtonForegroundColor',
							],
							'toolsButtonForegroundColor' => [
								'property' => 'toolsButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultToolsButtonForegroundColor,
								'checkContrastWith' => 'toolsButtonBackgroundColor',
							],
							'toolsButtonBorderColor' => [
								'property' => 'toolsButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultToolsButtonBorderColor,
							],
							'toolsButtonHoverBackgroundColor' => [
								'property' => 'toolsButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultToolsButtonHoverBackgroundColor,
								'checkContrastWith' => 'toolsButtonHoverForegroundColor',
							],
							'toolsButtonHoverForegroundColor' => [
								'property' => 'toolsButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultToolsButtonHoverForegroundColor,
								'checkContrastWith' => 'toolsButtonHoverBackgroundColor',
							],
							'toolsButtonHoverBorderColor' => [
								'property' => 'toolsButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultToolsButtonHoverBorderColor,
							],
						],
					],

					'infoButtonSection' => [
						'property' => 'infoButtonSection',
						'type' => 'section',
						'label' => 'Info Button',
						'hideInLists' => true,
						'properties' => [
							'infoButtonBackgroundColor' => [
								'property' => 'infoButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultInfoButtonBackgroundColor,
								'checkContrastWith' => 'infoButtonForegroundColor',
							],
							'infoButtonForegroundColor' => [
								'property' => 'infoButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultInfoButtonForegroundColor,
								'checkContrastWith' => 'infoButtonBackgroundColor',
							],
							'infoButtonBorderColor' => [
								'property' => 'infoButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultInfoButtonBorderColor,
							],
							'infoButtonHoverBackgroundColor' => [
								'property' => 'infoButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultInfoButtonHoverBackgroundColor,
								'checkContrastWith' => 'infoButtonHoverForegroundColor',
							],
							'infoButtonHoverForegroundColor' => [
								'property' => 'infoButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultInfoButtonHoverForegroundColor,
								'checkContrastWith' => 'infoButtonHoverBackgroundColor',
							],
							'infoButtonHoverBorderColor' => [
								'property' => 'infoButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultInfoButtonHoverBorderColor,
							],
						],
					],

					'warningButtonSection' => [
						'property' => 'warningButtonSection',
						'type' => 'section',
						'label' => 'Warning Button',
						'hideInLists' => true,
						'properties' => [
							'warningButtonBackgroundColor' => [
								'property' => 'warningButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultWarningButtonBackgroundColor,
								'checkContrastWith' => 'warningButtonForegroundColor',
							],
							'warningButtonForegroundColor' => [
								'property' => 'warningButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultWarningButtonForegroundColor,
								'checkContrastWith' => 'warningButtonBackgroundColor',
							],
							'warningButtonBorderColor' => [
								'property' => 'warningButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultWarningButtonBorderColor,
							],
							'warningButtonHoverBackgroundColor' => [
								'property' => 'warningButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultWarningButtonHoverBackgroundColor,
								'checkContrastWith' => 'warningButtonHoverForegroundColor',
							],
							'warningButtonHoverForegroundColor' => [
								'property' => 'warningButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultWarningButtonHoverForegroundColor,
								'checkContrastWith' => 'warningButtonHoverBackgroundColor',
							],
							'warningButtonHoverBorderColor' => [
								'property' => 'warningButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultWarningButtonHoverBorderColor,
							],
						],
					],

					'dangerButtonSection' => [
						'property' => 'dangerButtonSection',
						'type' => 'section',
						'label' => 'Danger Button',
						'hideInLists' => true,
						'properties' => [
							'dangerButtonBackgroundColor' => [
								'property' => 'dangerButtonBackgroundColor',
								'type' => 'color',
								'label' => 'Background Color',
								'description' => 'Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDangerButtonBackgroundColor,
								'checkContrastWith' => 'dangerButtonForegroundColor',
							],
							'dangerButtonForegroundColor' => [
								'property' => 'dangerButtonForegroundColor',
								'type' => 'color',
								'label' => 'Text Color',
								'description' => 'Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDangerButtonForegroundColor,
								'checkContrastWith' => 'dangerButtonBackgroundColor',
							],
							'dangerButtonBorderColor' => [
								'property' => 'dangerButtonBorderColor',
								'type' => 'color',
								'label' => 'Border Color',
								'description' => 'Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDangerButtonBorderColor,
							],
							'dangerButtonHoverBackgroundColor' => [
								'property' => 'dangerButtonHoverBackgroundColor',
								'type' => 'color',
								'label' => 'Hover Background Color',
								'description' => 'Hover Background Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDangerButtonHoverBackgroundColor,
								'checkContrastWith' => 'dangerButtonHoverForegroundColor',
							],
							'dangerButtonHoverForegroundColor' => [
								'property' => 'dangerButtonHoverForegroundColor',
								'type' => 'color',
								'label' => 'Hover Text Color',
								'description' => 'Hover Text Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDangerButtonHoverForegroundColor,
								'checkContrastWith' => 'dangerButtonHoverBackgroundColor',
							],
							'dangerButtonHoverBorderColor' => [
								'property' => 'dangerButtonHoverBorderColor',
								'type' => 'color',
								'label' => 'Hover Border Color',
								'description' => 'Hover Border Color',
								'required' => false,
								'hideInLists' => true,
								'default' => Theme::$defaultDangerButtonHoverBorderColor,
							],
						],
					],
				],
			],

			'tabsSection' => [
				'property' => 'tabsSection',
				'type' => 'section',
				'label' => 'Tabs',
				'hideInLists' => true,
				'properties' => [
					'inactiveTabBackgroundColor' => [
						'property' => 'inactiveTabBackgroundColor',
						'type' => 'color',
						'label' => 'Inactive Tab Background Color',
						'description' => 'Tab Background Color while inactive',
						'required' => false,
						'hideInLists' => true,
						'default' => '#ffffff',
						'checkContrastWith' => 'inactiveTabForegroundColor',
					],
					'inactiveTabForegroundColor' => [
						'property' => 'inactiveTabForegroundColor',
						'type' => 'color',
						'label' => 'Inactive Tab Text Color',
						'description' => 'Tab Foreground Color while inactive',
						'required' => false,
						'hideInLists' => true,
						'default' => '#6B6B6B',
						'checkContrastWith' => 'inactiveTabBackgroundColor',
					],
					'activeTabBackgroundColor' => [
						'property' => 'activeTabBackgroundColor',
						'type' => 'color',
						'label' => 'Active Tab Background Color',
						'description' => 'Tab Background Color while active',
						'required' => false,
						'hideInLists' => true,
						'default' => '#e7e7e7',
						'checkContrastWith' => 'activeTabForegroundColor',
					],
					'activeTabForegroundColor' => [
						'property' => 'activeTabForegroundColor',
						'type' => 'color',
						'label' => 'Active Tab Text Color',
						'description' => 'Tab Foreground Color while open',
						'required' => false,
						'hideInLists' => true,
						'default' => '#333333',
						'checkContrastWith' => 'activeTabBackgroundColor',
					],
				]
			],
			'placardSection' => [
				'property' => 'placardSection',
				'type' => 'section',
				'label' => 'Placards',
				'hideInLists' => true,
				'properties' => [
					'placardImageMaxHeight' => [
						'property' => 'placardImageMaxHeight',
						'type' => 'integer',
						'label' => 'Image Max Height (pixels)',
						'description' => 'Maximum height for placard images (0 = no restriction).',
						'default' => 0,
						'hideInLists' => true,
					],
				],
			],
			'cookieConsentSection' => [
				'property' => 'cookieConsentSection',
				'type' => 'section',
				'label' => 'Cookie Consent',
				'hideInLists' => true,
				'properties' => [
					'cookieConsentBackgroundColor' => [
						'property' => 'cookieConsentBackgroundColor',
						'type' => 'color',
						'label' => 'Cookie Consent Banner Color',
						'description' => 'Cookie Consent Banner Background Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#1D7FF0',
						'checkContrastWith' => 'cookieConsentTextColor',
					],
					'cookieConsentTextColor' => [
						'property' => 'cookieConsentTextColor',
						'type' => 'color',
						'label' => 'Cookie Consent Text Color',
						'description' => 'Color Of Text In Cookie Consent Bar Color',
						'required' => false,
						'hideInLists' => true,
						'default' => '#FFFFFF',
						'checkContrastWith' => 'cookieConsentBackgroundColor',
					],
					'cookieConsentButtonColor' => [
						'property' => 'cookieConsentButtonColor',
						'type' => 'color',
						'label' => 'Cookie Consent Button Color',
						'description' => 'Base Color Of Cookie Consent Buttons',
						'required' => false,
						'hideInLists' => true,
						'default' => '#1D7FF0',
						'checkContrastWith' => 'cookieConsentButtonTextColor',
					],
					'cookieConsentButtonTextColor' => [
						'property' => 'cookieConsentButtonTextColor',
						'type' => 'color',
						'label' => 'Cookie Consent Button Text Color',
						'description' => 'Color Of Text In Cookie Consent Buttons On Hover',
						'required' => false,
						'hideInLists' => true,
						'default' => '#FFFFFF',
						'checkContrastWith' => 'cookieConsentButtonColor',
					],
					'cookieConsentButtonHoverColor' => [
						'property' => 'cookieConsentButtonHoverColor',
						'type' => 'color',
						'label' => 'Cookie Consent Button Hover Color',
						'description' => 'Color of Cookie Consent Buttons on Hover',
						'required' => false,
						'hideInLists' => true,
						'default' => '#FF0000',
						'checkContrastWith' => 'cookieConsentButtonHoverTextColor',
					],
					'cookieConsentButtonHoverTextColor' => [
						'property' => 'cookieConsentButtonHoverTextColor',
						'type' => 'color',
						'label' => 'Cookie Consent Button Hover Text Color',
						'description' => 'Color Of Text In Cookie Consent Buttons On Hover',
						'required' => false,
						'hideInLists' => true,
						'default' => '#FFFFFF',
						'checkContrastWith' => 'cookieConsentButtonHoverColor',
					],
					'cookieConsentButtonBorderColor' => [
						'property' => 'cookieConsentButtonBorderColor',
						'type' => 'color',
						'label' => 'Cookie Consent Button Border Color',
						'description' => 'Color Of Border around Buttons in Cookie Consent Banner',
						'required' => false,
						'hideInLists' => true,
						'default' => '#FFFFFF',
					],
				],
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'oneToMany',
				'label' => 'Libraries',
				'description' => 'Define libraries that use this theme',
				'keyThis' => 'id',
				'keyOther' => 'themeId',
				'subObjectType' => 'LibraryTheme',
				'structure' => $libraryThemeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'permissions' => ['Library Theme Configuration'],
				'additionalOneToManyActions' => [
					'applyToAllLibraries' => [
						'text' => 'Apply To All Libraries',
						'url' => '/Admin/Themes?id=$id&amp;objectAction=addToAllLibraries',
					],
					'clearLibraries' => [
						'text' => 'Clear Libraries',
						'url' => '/Admin/Themes?id=$id&amp;objectAction=clearLibraries',
						'class' => 'btn-warning',
					],
				],
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'oneToMany',
				'label' => 'Locations',
				'description' => 'Define locations that use this theme',
				'keyThis' => 'id',
				'keyOther' => 'themeId',
				'subObjectType' => 'LocationTheme',
				'structure' => $locationThemeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'permissions' => ['Location Theme Configuration'],
				'additionalOneToManyActions' => [
					[
						'text' => 'Apply To All Locations',
						'url' => '/Admin/Themes?id=$id&amp;objectAction=addToAllLocations',
					],
					[
						'text' => 'Clear Locations',
						'url' => '/Admin/Themes?id=$id&amp;objectAction=clearLocations',
						'class' => 'btn-warning',
					],
				],
			],
		];

		if (!UserAccount::userHasPermission('Administer All Libraries')) {
			$objectStructure['libraries']['additionalOneToManyActions'] = [];
		}

		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$objectStructure['locations']['additionalOneToManyActions'] = [];
		}

		return $objectStructure;
	}

	/** @noinspection PhpUnused */
	public function validateColorContrast() {
		global $library;
		//Setup validation return array
		$validationResults = [
			'validatedOk' => true,
			'errors' => [],
		];

		if ($library->getLayoutSettings()->contrastRatio == 7.0) {
			$minContrastRatio = 4.5;
		} else {
			$minContrastRatio = 3.5;
		}

		$this->applyDefaults();

		$prevColors = false;
		if(!empty($this->id) && $this->id !== -1) {
			$theme = new Theme();
			$theme->id = $this->id;
			if ($theme->find(true)) {
				$prevColors = $theme;
			}
		} else if (strlen($this->extendsTheme) != 0) {
			$prevColors = $this->getParentTheme();
		}

		foreach($this as $index => $item) {
			//Properties ending with ColorDefault are checkboxes that indicate the value from the parent should be used.
			if(strpos($index, 'Color') != false && strpos($index, 'ColorDefault') === false) {
				if(is_null($item)) {
					if($prevColors) {
						if ($prevColors->$index) {
							// Locked fields contain null values, so we'll grab the previous value in order to check contrast
							$this->$index = $prevColors->$index;
						}
					} else {
						$defaultColor =  'default' . ucfirst($index);
						$this->$index = Theme::$$defaultColor;
					}
				}
			}
		}
		require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
		$bodyContrast = ColorUtils::calculateColorContrast($this->bodyBackgroundColor, $this->bodyTextColor);
		if ($bodyContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Body contrast does not meet accessibility guidelines, contrast is: ' . $bodyContrast;
		}
		$linkContrast = ColorUtils::calculateColorContrast($this->bodyBackgroundColor, $this->linkColor);
		if ($linkContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Link contrast does not meet accessibility guidelines, contrast is: ' . $linkContrast;
		}
		$linkHoverContrast = ColorUtils::calculateColorContrast($this->bodyBackgroundColor, $this->linkHoverColor);
		if ($linkHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Link hover contrast does not meet accessibility guidelines, contrast is: ' . $linkHoverContrast;
		}
		$resultLabelContrast = ColorUtils::calculateColorContrast($this->bodyBackgroundColor, $this->resultLabelColor);
		if ($resultLabelContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Result Label contrast does not meet accessibility guidelines, contrast is: ' . $resultLabelContrast;
		}
		$resultValueContrast = ColorUtils::calculateColorContrast($this->bodyBackgroundColor, $this->resultValueColor);
		if ($resultValueContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Result Value contrast does not meet accessibility guidelines, contrast is: ' . $resultValueContrast;
		}
		$headerContrast = ColorUtils::calculateColorContrast($this->headerBackgroundColor, $this->headerForegroundColor);
		if ($headerContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Header contrast does not meet accessibility guidelines, contrast is: ' . ($headerContrast);
		}
		$footerContrast = ColorUtils::calculateColorContrast($this->footerBackgroundColor, $this->footerForegroundColor);
		if ($footerContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Footer contrast does not meet accessibility guidelines, contrast is: ' . ($footerContrast);
		}
		$breadcrumbsContrast = ColorUtils::calculateColorContrast($this->breadcrumbsBackgroundColor, $this->breadcrumbsForegroundColor);
		if ($breadcrumbsContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Breadcrumbs contrast does not meet accessibility guidelines, contrast is: ' . ($breadcrumbsContrast);
		}
		$searchToolsContrast = ColorUtils::calculateColorContrast($this->searchToolsBackgroundColor, $this->searchToolsForegroundColor);
		if ($searchToolsContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Search Tools contrast does not meet accessibility guidelines, contrast is: ' . ($searchToolsContrast);
		}
		$primaryContrast = ColorUtils::calculateColorContrast($this->primaryBackgroundColor, $this->primaryForegroundColor);
		if ($primaryContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Primary color contrast does not meet accessibility guidelines, contrast is: ' . ($primaryContrast);
		}
		$secondaryContrast = ColorUtils::calculateColorContrast($this->secondaryBackgroundColor, $this->secondaryForegroundColor);
		if ($secondaryContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Secondary color contrast does not meet accessibility guidelines, contrast is: ' . ($secondaryContrast);
		}
		$tertiaryContrast = ColorUtils::calculateColorContrast($this->tertiaryBackgroundColor, $this->tertiaryForegroundColor);
		if ($tertiaryContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Tertiary color contrast does not meet accessibility guidelines, contrast is: ' . ($tertiaryContrast);
		}
		$menubarContrast = ColorUtils::calculateColorContrast($this->menubarBackgroundColor, $this->menubarForegroundColor);
		if ($menubarContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Menu contrast does not meet accessibility guidelines, contrast is: ' . ($menubarContrast);
		}
		$menubarHighlightContrast = ColorUtils::calculateColorContrast($this->menubarHighlightBackgroundColor, $this->menubarHighlightForegroundColor);
		if ($menubarHighlightContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Menu Highlight contrast does not meet accessibility guidelines, contrast is: ' . ($menubarHighlightContrast);
		}
		$menubarDropdownContrast = ColorUtils::calculateColorContrast($this->menuDropdownBackgroundColor, $this->menuDropdownForegroundColor);
		if ($menubarDropdownContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Menu dropdown contrast does not meet accessibility guidelines, contrast is: ' . ($menubarDropdownContrast);
		}
//		$modalDialogContrast = ColorUtils::calculateColorContrast($this->modalDialogBackgroundColor, $this->modalDialogForegroundColor);
//		if ($modalDialogContrast < $minContrastRatio) {
//			$validationResults['errors'][] = 'Modal Dialog contrast does not meet accessibility guidelines, contrast is: ' . ($modalDialogContrast);
//		}
//		$modalDialogHeaderFooterContrast = ColorUtils::calculateColorContrast($this->modalDialogHeaderFooterBackgroundColor, $this->modalDialogHeaderFooterForegroundColor);
//		if ($modalDialogHeaderFooterContrast < $minContrastRatio) {
//			$validationResults['errors'][] = 'Modal Dialog Header Footer contrast does not meet accessibility guidelines, contrast is: ' . ($modalDialogHeaderFooterContrast);
//		}
		$selectedBrowseCategoryContrast = ColorUtils::calculateColorContrast($this->selectedBrowseCategoryBackgroundColor, $this->selectedBrowseCategoryForegroundColor);
		if ($selectedBrowseCategoryContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Selected Browse Category contrast does not meet accessibility guidelines, contrast is: ' . ($selectedBrowseCategoryContrast);
		}
		$deselectedBrowseCategoryContrast = ColorUtils::calculateColorContrast($this->deselectedBrowseCategoryBackgroundColor, $this->deselectedBrowseCategoryForegroundColor);
		if ($deselectedBrowseCategoryContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Deselected Browse Category contrast does not meet accessibility guidelines, contrast is: ' . ($deselectedBrowseCategoryContrast);
		}
		$badgeContrast = ColorUtils::calculateColorContrast($this->badgeBackgroundColor, $this->badgeForegroundColor);
		if ($badgeContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Badge contrast does not meet accessibility guidelines, contrast is: ' . ($badgeContrast);
		}
		$closedPanelContrast = ColorUtils::calculateColorContrast($this->closedPanelBackgroundColor, $this->closedPanelForegroundColor);
		if ($closedPanelContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Closed Panel contrast does not meet accessibility guidelines, contrast is: ' . ($closedPanelContrast);
		}
		$openPanelContrast = ColorUtils::calculateColorContrast($this->openPanelBackgroundColor, $this->openPanelForegroundColor);
		if ($openPanelContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Open Panel contrast does not meet accessibility guidelines, contrast is: ' . ($openPanelContrast);
		}
		$panelBodyContrast = ColorUtils::calculateColorContrast($this->panelBodyBackgroundColor, $this->panelBodyForegroundColor);
		if ($panelBodyContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Open Panel contrast does not meet accessibility guidelines, contrast is: ' . ($panelBodyContrast);
		}
		$inactiveTabContrast = ColorUtils::calculateColorContrast($this->inactiveTabBackgroundColor, $this->inactiveTabForegroundColor);
		if ($inactiveTabContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Inactive Tab contrast does not meet accessibility guidelines, contrast is: ' . ($inactiveTabContrast);
		}
		$activeTabContrast = ColorUtils::calculateColorContrast($this->activeTabBackgroundColor, $this->activeTabForegroundColor);
		if ($activeTabContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Active Tab contrast does not meet accessibility guidelines, contrast is: ' . ($activeTabContrast);
		}
		$defaultButtonContrast = ColorUtils::calculateColorContrast($this->defaultButtonBackgroundColor, $this->defaultButtonForegroundColor);
		if ($defaultButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Default Button contrast does not meet accessibility guidelines, contrast is: ' . ($defaultButtonContrast);
		}
		$defaultButtonHoverContrast = ColorUtils::calculateColorContrast($this->defaultButtonHoverBackgroundColor, $this->defaultButtonHoverForegroundColor);
		if ($defaultButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Default Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($defaultButtonHoverContrast);
		}
		$primaryButtonContrast = ColorUtils::calculateColorContrast($this->primaryButtonBackgroundColor, $this->primaryButtonForegroundColor);
		if ($primaryButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Primary Button contrast does not meet accessibility guidelines, contrast is: ' . ($primaryButtonContrast);
		}
		$primaryButtonHoverContrast = ColorUtils::calculateColorContrast($this->primaryButtonHoverBackgroundColor, $this->primaryButtonHoverForegroundColor);
		if ($primaryButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Primary Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($primaryButtonHoverContrast);
		}
		$actionButtonContrast = ColorUtils::calculateColorContrast($this->actionButtonBackgroundColor, $this->actionButtonForegroundColor);
		if ($actionButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Action Button contrast does not meet accessibility guidelines, contrast is: ' . ($actionButtonContrast);
		}
		$actionButtonHoverContrast = ColorUtils::calculateColorContrast($this->actionButtonHoverBackgroundColor, $this->actionButtonHoverForegroundColor);
		if ($actionButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Action Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($actionButtonHoverContrast);
		}
		$editionsButtonContrast = ColorUtils::calculateColorContrast($this->editionsButtonBackgroundColor, $this->editionsButtonForegroundColor);
		if ($editionsButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Editions Button contrast does not meet accessibility guidelines, contrast is: ' . ($editionsButtonContrast);
		}
		$editionsButtonHoverContrast = ColorUtils::calculateColorContrast($this->editionsButtonHoverBackgroundColor, $this->editionsButtonHoverForegroundColor);
		if ($editionsButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Editions Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($editionsButtonHoverContrast);
		}
		$toolsButtonContrast = ColorUtils::calculateColorContrast($this->toolsButtonBackgroundColor, $this->toolsButtonForegroundColor);
		if ($toolsButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Tools Button contrast does not meet accessibility guidelines, contrast is: ' . ($toolsButtonContrast);
		}
		$toolsButtonHoverContrast = ColorUtils::calculateColorContrast($this->toolsButtonHoverBackgroundColor, $this->toolsButtonHoverForegroundColor);
		if ($toolsButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Tools Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($toolsButtonHoverContrast);
		}
		$infoButtonContrast = ColorUtils::calculateColorContrast($this->infoButtonBackgroundColor, $this->infoButtonForegroundColor);
		if ($infoButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Info Button contrast does not meet accessibility guidelines, contrast is: ' . ($infoButtonContrast);
		}
		$infoButtonHoverContrast = ColorUtils::calculateColorContrast($this->infoButtonHoverBackgroundColor, $this->infoButtonHoverForegroundColor);
		if ($infoButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Info Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($infoButtonHoverContrast);
		}
		$warningButtonContrast = ColorUtils::calculateColorContrast($this->warningButtonBackgroundColor, $this->warningButtonForegroundColor);
		if ($warningButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Warning Button contrast does not meet accessibility guidelines, contrast is: ' . ($warningButtonContrast);
		}
		$warningButtonHoverContrast = ColorUtils::calculateColorContrast($this->warningButtonHoverBackgroundColor, $this->warningButtonHoverForegroundColor);
		if ($warningButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Warning Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($warningButtonHoverContrast);
		}
		$dangerButtonContrast = ColorUtils::calculateColorContrast($this->dangerButtonBackgroundColor, $this->dangerButtonForegroundColor);
		if ($dangerButtonContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Danger Button contrast does not meet accessibility guidelines, contrast is: ' . ($dangerButtonContrast);
		}
		$dangerButtonHoverContrast = ColorUtils::calculateColorContrast($this->dangerButtonHoverBackgroundColor, $this->dangerButtonHoverForegroundColor);
		if ($dangerButtonHoverContrast < $minContrastRatio) {
			$validationResults['errors'][] = 'Danger Button Hover contrast does not meet accessibility guidelines, contrast is: ' . ($dangerButtonHoverContrast);
		}

		if (count($validationResults['errors']) > 0) {
			$validationResults['validatedOk'] = false;
		}

		return $validationResults;
	}

	public function insert($context = '') {
		$this->__set('generatedCss', $this->generateCss());
		$this->clearDefaultCovers();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveLocations();
		}
		return $ret;
	}

	public function update($context = '') {
		if ($context != 'saveGeneratedCss') {
			$this->generatedCss = $this->generateCss();
		}
		$this->clearDefaultCovers();
		if ($context != 'saveGeneratedCss') {
			$updateDerivedThemes = false;
			$oldThemeName = null;
			if (!empty($this->_changedFields)) {
				if (in_array('themeName', $this->_changedFields)) {
					//Need to update all the themes that extend this theme to make sure they have the new correct name for what they are extending.
					$originalTheme = new Theme();
					$originalTheme->id = $this->id;
					if ($originalTheme->find(true)) {
						$oldThemeName = $originalTheme->themeName;
						$updateDerivedThemes = true;
					}
				}
			}
		}

		$ret = parent::update();
		if ($ret !== FALSE) {
			if ($context != 'saveGeneratedCss') {
				$validationChangedTheme = $this->validateExtendsTheme();
				// If validation changed the extendsTheme, save the object again.
				if ($validationChangedTheme) {
					parent::update();
				}

				// Update any themes that extend this theme to give them the correct name
				if ($updateDerivedThemes) {
					$childTheme = new Theme();
					$childTheme->extendsTheme = $oldThemeName;
					$childThemes = $childTheme->fetchAll();
					foreach ($childThemes as $childTheme) {
						$tmpChildTheme = new Theme();
						$tmpChildTheme->id = $childTheme->id;
						if ($tmpChildTheme->find(true)) {
							$tmpChildTheme->extendsTheme = $this->themeName;
							$tmpChildTheme->update();
						}
					}
				}

				$this->saveLibraries();
				$this->saveLocations();

				//Check to see what has been derived from this theme and regenerate CSS for those themes as well
				$extendedThemeIds = [];
				$childTheme = new Theme();
				$childTheme->extendsTheme = $this->themeName;
				$childTheme->find();
				while ($childTheme->fetch()) {
					if ($childTheme->id != $this->id) {
						$extendedThemeIds[] = $childTheme->id;
					}
				}

				foreach ($extendedThemeIds as $themeId) {
					$child = new Theme();
					$child->id = $themeId;
					if ($child->find(true)) {
						$child->generateCss(true);
					}
				}
			}
		}

		return $ret;
	}

	public function delete($useWhere = false) : int {
		$this->clearLibraries();
		$this->clearLocations();
		$this->clearDefaultCovers();
		return parent::delete($useWhere);
	}

	public function applyDefaults() {
		require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
		$appliedThemes = $this->getAllAppliedThemes();
		$this->getValueForPropertyUsingDefaults('pageBackgroundColor', Theme::$defaultPageBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('bodyBackgroundColor', Theme::$defaultBodyBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('bodyTextColor', Theme::$defaultBodyTextColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('linkColor', Theme::$defaultLinkColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('linkHoverColor', Theme::$defaultLinkHoverColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('resultLabelColor', Theme::$defaultResultLabelColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('resultValueColor', Theme::$defaultResultValueColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('headerBackgroundColor', Theme::$defaultHeaderBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('headerForegroundColor', Theme::$defaultHeaderForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('breadcrumbsBackgroundColor', Theme::$defaultBreadcrumbsBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('breadcrumbsForegroundColor', Theme::$defaultBreadcrumbsForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('searchToolsBackgroundColor', Theme::$defaultSearchToolsBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('searchToolsBorderColor', Theme::$defaultSearchToolsBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('searchToolsForegroundColor', Theme::$defaultSearchToolsForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('footerBackgroundColor', Theme::$defaultFooterBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('footerForegroundColor', Theme::$defaultFooterForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryBackgroundColor', Theme::$defaultPrimaryBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('primaryForegroundColor', Theme::$defaultPrimaryForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('secondaryBackgroundColor', Theme::$defaultSecondaryBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('secondaryForegroundColor', Theme::$defaultSecondaryForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('tertiaryBackgroundColor', Theme::$defaultTertiaryBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('tertiaryForegroundColor', Theme::$defaultTertiaryForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('menubarBackgroundColor', Theme::$defaultMenubarBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('menubarForegroundColor', Theme::$defaultMenubarForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('menubarHighlightBackgroundColor', Theme::$defaultMenubarHighlightBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('menubarHighlightForegroundColor', Theme::$defaultMenubarHighlightForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('menuDropdownBackgroundColor', Theme::$defaultMenuDropdownBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('menuDropdownForegroundColor', Theme::$defaultMenuDropdownForegroundColor, $appliedThemes);
//		$this->getValueForPropertyUsingDefaults('modalDialogBackgroundColor', Theme::$defaultModalDialogHeaderFooterBackgroundColor, $appliedThemes);
//		$this->getValueForPropertyUsingDefaults('modalDialogForegroundColor', Theme::$defaultModalDialogHeaderFooterForegroundColor, $appliedThemes);
//		$this->getValueForPropertyUsingDefaults('modalDialogHeaderFooterBackgroundColor', Theme::$defaultModalDialogBackgroundColor, $appliedThemes);
//		$this->getValueForPropertyUsingDefaults('modalDialogHeaderFooterForegroundColor', Theme::$defaultModalDialogForegroundColor, $appliedThemes);
//		$this->getValueForPropertyUsingDefaults('modalDialogHeaderFooterBorderColor', Theme::$defaultModalDialogHeaderFooterBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('browseCategoryPanelColor', Theme::$defaultBrowseCategoryPanelColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('selectedBrowseCategoryBackgroundColor', Theme::$defaultSelectedBrowseCategoryBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('selectedBrowseCategoryForegroundColor', Theme::$defaultSelectedBrowseCategoryForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('selectedBrowseCategoryBorderColor', Theme::$defaultSelectedBrowseCategoryBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('deselectedBrowseCategoryBackgroundColor', Theme::$defaultDeselectedBrowseCategoryBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('deselectedBrowseCategoryForegroundColor', Theme::$defaultDeselectedBrowseCategoryForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('deselectedBrowseCategoryBorderColor', Theme::$defaultDeselectedBrowseCategoryBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('badgeBackgroundColor', Theme::$defaultBadgeBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('badgeForegroundColor', Theme::$defaultBadgeForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('closedPanelBackgroundColor', Theme::$defaultClosedPanelBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('closedPanelForegroundColor', Theme::$defaultClosedPanelForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('openPanelBackgroundColor', $this->secondaryBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('openPanelForegroundColor', $this->secondaryForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('panelBodyBackgroundColor', Theme::$defaultPanelBodyBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('panelBodyForegroundColor', Theme::$defaultPanelBodyForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('inactiveTabBackgroundColor', Theme::$defaultInactiveTabBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('inactiveTabForegroundColor', Theme::$defaultInactiveTabForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('activeTabBackgroundColor', Theme::$defaultActiveTabBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('activeTabForegroundColor', Theme::$defaultActiveTabForegroundColor, $appliedThemes);
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
		$this->getValueForPropertyUsingDefaults('editionsButtonBackgroundColor', Theme::$defaultEditionsButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('editionsButtonForegroundColor', Theme::$defaultEditionsButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('editionsButtonBorderColor', Theme::$defaultEditionsButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('editionsButtonHoverBackgroundColor', Theme::$defaultEditionsButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('editionsButtonHoverForegroundColor', Theme::$defaultEditionsButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('editionsButtonHoverBorderColor', Theme::$defaultEditionsButtonHoverBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('toolsButtonBackgroundColor', Theme::$defaultToolsButtonBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('toolsButtonForegroundColor', Theme::$defaultToolsButtonForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('toolsButtonBorderColor', Theme::$defaultToolsButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('toolsButtonHoverBackgroundColor', Theme::$defaultToolsButtonHoverBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('toolsButtonHoverForegroundColor', Theme::$defaultToolsButtonHoverForegroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('toolsButtonHoverBorderColor', Theme::$defaultToolsButtonHoverBorderColor, $appliedThemes);
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
		$this->getValueForPropertyUsingDefaults('cookieConsentBackgroundColor', Theme::$defaultCookieConsentBackgroundColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('cookieConsentButtonColor', Theme::$defaultCookieConsentButtonColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('cookieConsentButtonHoverColor', Theme::$defaultCookieConsentButtonHoverColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('cookieConsentTextColor', Theme::$defaultCookieConsentTextColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('cookieConsentButtonTextColor', Theme::$defaultCookieConsentButtonTextColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('cookieConsentButtonHoverTextColor', Theme::$defaultCookieConsentButtonHoverTextColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('cookieConsentButtonBorderColor', Theme::$defaultCookieConsentButtonBorderColor, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('headerLogoBackgroundColorApp', Theme::$defaultHeaderLogoBackgroundColorApp, $appliedThemes);
		$this->getValueForPropertyUsingDefaults('placardImageMaxHeight', Theme::$defaultPlacardImageMaxHeight, $appliedThemes);
	}

	public function getValueForPropertyUsingDefaults($propertyName, $defaultValue, $appliedThemes) {
		foreach ($appliedThemes as $theme) {
			$defaultPropertyName = $propertyName . 'Default';
			if (!$theme->$defaultPropertyName) {
				$this->$propertyName = $theme->$propertyName;
				return;
			}
		}
		$this->$propertyName = $defaultValue;
	}

	/**
	 * @return string the resulting css
	 */
	public function generateCss($saveChanges = false) {
		$allAppliedThemes = $this->getAllAppliedThemes();
		global $interface;
		require_once ROOT_DIR . '/sys/Utils/ColorUtils.php';
		$additionalCSS = '';
		$appendCSS = true;
		$this->applyDefaults();
		$interface->assign('headerBackgroundColor', $this->headerBackgroundColor);
		$interface->assign('headerForegroundColor', $this->headerForegroundColor);
		$interface->assign('headerBackgroundImage', $this->headerBackgroundImage);
		$interface->assign('headerBackgroundImageSize', $this->headerBackgroundImageSize);
		$interface->assign('headerBackgroundImageRepeat', $this->headerBackgroundImageRepeat);
		$interface->assign('pageBackgroundColor', $this->pageBackgroundColor);
		$interface->assign('breadcrumbsBackgroundColor', $this->breadcrumbsBackgroundColor);
		$interface->assign('breadcrumbsForegroundColor', $this->breadcrumbsForegroundColor);
		$interface->assign('searchToolsBackgroundColor', $this->searchToolsBackgroundColor);
		$interface->assign('searchToolsBorderColor', $this->searchToolsBorderColor);
		$interface->assign('searchToolsForegroundColor', $this->searchToolsForegroundColor);
		$interface->assign('footerBackgroundColor', $this->footerBackgroundColor);
		$interface->assign('footerForegroundColor', $this->footerForegroundColor);
		$interface->assign('primaryBackgroundColor', $this->primaryBackgroundColor);
		$interface->assign('primaryForegroundColor', $this->primaryForegroundColor);
		$interface->assign('secondaryBackgroundColor', $this->secondaryBackgroundColor);
		$interface->assign('secondaryForegroundColor', $this->secondaryForegroundColor);
		$interface->assign('tertiaryBackgroundColor', $this->tertiaryBackgroundColor);
		$interface->assign('tertiaryForegroundColor', $this->tertiaryForegroundColor);
		$interface->assign('bodyBackgroundColor', $this->bodyBackgroundColor);
		$interface->assign('bodyTextColor', $this->bodyTextColor);
		$interface->assign('linkColor', $this->linkColor);
		$interface->assign('linkHoverColor', $this->linkHoverColor);
		$tableStripeBackgroundColor = ColorUtils::lightenColor($this->bodyBackgroundColor, 0.50);
		if (ColorUtils::calculateColorContrast($tableStripeBackgroundColor, $this->bodyTextColor) < 4.5 || ColorUtils::calculateColorContrast($tableStripeBackgroundColor, $this->linkColor) < 4.5 || ColorUtils::calculateColorContrast($tableStripeBackgroundColor, $this->linkHoverColor) < 4.5) {

			$tableStripeBackgroundColor = ColorUtils::lightenColor($this->bodyBackgroundColor, 0.98);
		}
		$interface->assign('tableStripeBackgroundColor', $tableStripeBackgroundColor);
		$interface->assign('resultLabelColor', $this->resultLabelColor);
		$interface->assign('resultValueColor', $this->resultValueColor);
		$interface->assign('menubarHighlightBackgroundColor', $this->menubarHighlightBackgroundColor);
		$interface->assign('menubarHighlightForegroundColor', $this->menubarHighlightForegroundColor);
		$interface->assign('menubarBackgroundColor', $this->menubarBackgroundColor);
		$interface->assign('menubarForegroundColor', $this->menubarForegroundColor);
		$interface->assign('menuDropdownBackgroundColor', $this->menuDropdownBackgroundColor);
		$interface->assign('menuDropdownForegroundColor', $this->menuDropdownForegroundColor);
//		$interface->assign('modalDialogBackgroundColor', $this->modalDialogBackgroundColor);
//		$interface->assign('modalDialogForegroundColor', $this->modalDialogForegroundColor);
//		$interface->assign('modalDialogHeaderFooterBackgroundColor', $this->modalDialogHeaderFooterBackgroundColor);
//		$interface->assign('modalDialogHeaderFooterForegroundColor', $this->modalDialogHeaderFooterForegroundColor);
//		$interface->assign('modalDialogHeaderFooterBorderColor', $this->modalDialogHeaderFooterBorderColor);
		$interface->assign('browseCategoryPanelColor', $this->browseCategoryPanelColor);
		$interface->assign('selectedBrowseCategoryBackgroundColor', $this->selectedBrowseCategoryBackgroundColor);
		$interface->assign('selectedBrowseCategoryForegroundColor', $this->selectedBrowseCategoryForegroundColor);
		$interface->assign('selectedBrowseCategoryBorderColor', $this->selectedBrowseCategoryBorderColor);
		$interface->assign('deselectedBrowseCategoryBackgroundColor', $this->deselectedBrowseCategoryBackgroundColor);
		$interface->assign('deselectedBrowseCategoryForegroundColor', $this->deselectedBrowseCategoryForegroundColor);
		$interface->assign('deselectedBrowseCategoryBorderColor', $this->deselectedBrowseCategoryBorderColor);
		$interface->assign('badgeBackgroundColor', $this->badgeBackgroundColor);
		$interface->assign('badgeForegroundColor', $this->badgeForegroundColor);
		$interface->assign('closedPanelBackgroundColor', $this->closedPanelBackgroundColor);
		$interface->assign('closedPanelForegroundColor', $this->closedPanelForegroundColor);
		$interface->assign('openPanelBackgroundColor', $this->openPanelBackgroundColor);
		$interface->assign('openPanelForegroundColor', $this->openPanelForegroundColor);
		$interface->assign('panelBodyBackgroundColor', $this->panelBodyBackgroundColor);
		$interface->assign('panelBodyForegroundColor', $this->panelBodyForegroundColor);
		$interface->assign('inactiveTabBackgroundColor', $this->inactiveTabBackgroundColor);
		$interface->assign('inactiveTabForegroundColor', $this->inactiveTabForegroundColor);
		$interface->assign('activeTabBackgroundColor', $this->activeTabBackgroundColor);
		$interface->assign('activeTabForegroundColor', $this->activeTabForegroundColor);
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
		$interface->assign('editionsButtonBackgroundColor', $this->editionsButtonBackgroundColor);
		$interface->assign('editionsButtonForegroundColor', $this->editionsButtonForegroundColor);
		$interface->assign('editionsButtonBorderColor', $this->editionsButtonBorderColor);
		$interface->assign('editionsButtonHoverBackgroundColor', $this->editionsButtonHoverBackgroundColor);
		$interface->assign('editionsButtonHoverForegroundColor', $this->editionsButtonHoverForegroundColor);
		$interface->assign('editionsButtonHoverBorderColor', $this->editionsButtonHoverBorderColor);
		$interface->assign('toolsButtonBackgroundColor', $this->toolsButtonBackgroundColor);
		$interface->assign('toolsButtonForegroundColor', $this->toolsButtonForegroundColor);
		$interface->assign('toolsButtonBorderColor', $this->toolsButtonBorderColor);
		$interface->assign('toolsButtonHoverBackgroundColor', $this->toolsButtonHoverBackgroundColor);
		$interface->assign('toolsButtonHoverForegroundColor', $this->toolsButtonHoverForegroundColor);
		$interface->assign('toolsButtonHoverBorderColor', $this->toolsButtonHoverBorderColor);
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
		$interface->assign('themeIsHighContrast', $this->isHighContrast);
		$interface->assign('cookieConsentBackgroundColor', $this->cookieConsentBackgroundColor);
		$interface->assign('cookieConsentButtonColor', $this->cookieConsentButtonColor);
		$interface->assign('cookieConsentButtonHoverColor', $this->cookieConsentButtonHoverColor);
		$interface->assign('cookieConsentTextColor', $this->cookieConsentTextColor);
		$interface->assign('cookieConsentButtonTextColor', $this->cookieConsentButtonTextColor);
		$interface->assign('cookieConsentButtonHoverTextColor', $this->cookieConsentButtonHoverTextColor);
		$interface->assign('cookieConsentButtonBorderColor', $this->cookieConsentButtonBorderColor);
		$interface->assign('customHeadingFont', $this->customHeadingFont);
		$interface->assign('customHeadingFontName', '');
		$interface->assign('headingFont', '');
		$interface->assign('customBodyFont', $this->customBodyFont);
		$interface->assign('customBodyFontName', '');
		$interface->assign('bodyFont', '');
		$interface->assign('placardImageMaxHeight', $this->placardImageMaxHeight);
		if ($this->customHeadingFont != null) {
			$customHeadingFontName = substr($this->customHeadingFont, 0, strrpos($this->customHeadingFont, '.'));
			$interface->assign('customHeadingFontName', $customHeadingFontName);

			$interface->assign('headingFont', $customHeadingFontName);
		}
		if ($this->customBodyFont != null) {
			$customBodyFontName = substr($this->customBodyFont, 0, strrpos($this->customBodyFont, '.'));
			$interface->assign('customBodyFontName', $customBodyFontName);

			$interface->assign('bodyFont', $customBodyFontName);
		}

		foreach ($allAppliedThemes as $theme) {
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

			if ($interface->getVariable('browseCategoryImageSize') == null && $theme->browseCategoryImageSize != 0) {
				$interface->assign('browseCategoryImageSize', $theme->browseCategoryImageSize);
			}

			if ($interface->getVariable('browseImageLayout') == null && $theme->browseImageLayout != -1) {
				$interface->assign('browseImageLayout', $theme->browseImageLayout);
			}

			$interface->assign('accessibleBrowseCategories', $theme->accessibleBrowseCategories);

			if ($interface->getVariable('headerBottomBorderWidth') == null && $theme->headerBottomBorderWidth != null) {
				$headerBottomBorderWidth = $theme->headerBottomBorderWidth;
				if (is_numeric($headerBottomBorderWidth)) {
					$headerBottomBorderWidth = $headerBottomBorderWidth . 'px';
				}
				$interface->assign('headerBottomBorderWidth', $headerBottomBorderWidth);
			}
			if ($interface->getVariable('buttonRadius') == null && $theme->buttonRadius != null) {
				$buttonRadius = $theme->buttonRadius;
				if (is_numeric($buttonRadius)) {
					$buttonRadius = $buttonRadius . 'px';
				}
				$interface->assign('buttonRadius', $buttonRadius);
			}
			if ($interface->getVariable('smallButtonRadius') == null && $theme->smallButtonRadius != null) {
				$buttonRadius = $theme->smallButtonRadius;
				if (is_numeric($buttonRadius)) {
					$buttonRadius = $buttonRadius . 'px';
				}
				$interface->assign('smallButtonRadius', $buttonRadius);
			}
			if ($interface->getVariable('badgeBorderRadius') == null && !empty($theme->badgeBorderRadius)) {
				$badgeBorderRadius = $theme->badgeBorderRadius;
				if (is_numeric($badgeBorderRadius)) {
					$badgeBorderRadius = $badgeBorderRadius . 'px';
				}
				$interface->assign('badgeBorderRadius', $badgeBorderRadius);
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

		$previousCss = $this->generatedCss;
		$updatedCss = $interface->fetch('theme.css.tpl');
		if ($updatedCss != $previousCss) {
			$this->__set('generatedCss', $updatedCss);
			if ($saveChanges) {
				$this->update('saveGeneratedCss');
			}
		}

		return $this->generatedCss;
	}

	private $_allAppliedThemes = null;
	/**
	 * @return Theme[]
	 */
	public function getAllAppliedThemes() {
		if ($this->_allAppliedThemes == null) {
			$allAppliedThemes = [];
			$primaryTheme = clone($this);
			$allAppliedThemes[$primaryTheme->themeName] = $primaryTheme;
			$theme = $primaryTheme;
			$extendsName = $theme->extendsTheme;
			while (!empty($extendsName)) {
				if (!array_key_exists($extendsName, $allAppliedThemes)) {
					$theme = new Theme();
					$theme->themeName = $extendsName;
					if ($theme->find(true)) {
						$allAppliedThemes[$theme->themeName] = clone $theme;
						$extendsName = $theme->extendsTheme;
					}
				} else {
					//We have a recursive situation
					break;
				}
			}
			$this->_allAppliedThemes = $allAppliedThemes;
			if ($this->_themeHierarchy == null) {
				$this->_themeHierarchy = array_reverse($this->_allAppliedThemes, true);
			}
		}

		return $this->_allAppliedThemes;
	}

	protected $_parentTheme = null;

	public function getParentTheme() {
		if ($this->_parentTheme == null) {
			$theme = $this;
			if (strlen($theme->extendsTheme) != 0) {
				$this->_parentTheme = null;
				$extendsName = $theme->extendsTheme;
				if ($extendsName != $this->themeName) {
					$theme = new Theme();
					$theme->themeName = $extendsName;
					if ($theme->find(true)) {
						$this->_parentTheme = clone $theme;
					}
				}else{
					$this->_parentTheme = null;
				}
			} else {
				$this->_parentTheme = null;
			}
		}
		return $this->_parentTheme;
	}

	private function clearDefaultCovers() : void {
		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$covers = new BookCoverInfo();
		$covers->reloadAllDefaultCovers();
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "locations") {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "locations") {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function saveLibraries(): void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			// First, check if any libraries would be left without themes if removed.
			require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
			$librariesToDelete = [];
			$librariesWithOnlyThisTheme = [];
			$preventDeletion = false;

			foreach($this->_libraries as $obj) {
				/** @var LibraryTheme $obj */
				if($obj->_deleteOnSave) {
					$librariesToDelete[] = $obj;
					// Check if this is the library's only theme.
					$library = new Library();
					$library->libraryId = $obj->libraryId;
					if ($library->find(true)) {
						if (!$library->hasMultipleThemes()) {
							$preventDeletion = true;
							$librariesWithOnlyThisTheme[] = $library->displayName;
						}
					}
				}
			}

			// If any libraries would be left without themes, show error message and abort.
			if ($preventDeletion) {
				if (count($librariesWithOnlyThisTheme) == 1) {
					$preventionMessage = translate([
						'text' => 'Library %1% cannot be removed from this theme because it is the only theme for the library. Before proceeding, please assign another theme to this library.',
						'isAdminFacing' => true,
						1 => $librariesWithOnlyThisTheme[0]
					]);
				} else {
					$libraryList = implode('", "', $librariesWithOnlyThisTheme);
					$preventionMessage = translate([
						'text' => 'The following libraries cannot be removed from this theme because it is their only theme: %1%. Before proceeding, please assign another theme to these libraries.',
						'isAdminFacing' => true,
						1 => $libraryList
					]);
				}

				$user = UserAccount::getActiveUserObj();
				if ($user) {
					$user->updateMessage = $preventionMessage;
					$user->updateMessageIsError = true;
					$user->update();
				}

				// Remove the libraries marked for deletion from the _deleteOnSave list.
				foreach($librariesToDelete as $obj) {
					$obj->_deleteOnSave = false;
				}
			}

			// Continue with normal save procedure for non-deleted libraries.
			foreach($this->_libraries as $obj) {
				/** @var LibraryTheme $obj */
				if($obj->_deleteOnSave) {
					$obj->delete();
				} else {
					if (isset($obj->{$obj->__primaryKey}) && is_numeric($obj->{$obj->__primaryKey})) {
						if($obj->{$obj->__primaryKey} <= 0) {
							$obj->themeId = $this->{$this->__primaryKey};
							$obj->insert();
						} else {
							if($obj->hasChanges()) {
								$obj->update();
							}
						}
					} else {
						// Set the appropriate weight for the new theme.
						$weight = 0;
						$existingThemesForLibrary = new LibraryTheme();
						$existingThemesForLibrary->libraryId = $obj->libraryId;
						if ($existingThemesForLibrary->find()) {
							while ($existingThemesForLibrary->fetch()) {
								$weight = $weight + 1;
							}
						}

						$obj->themeId = $this->{$this->__primaryKey};
						$obj->weight = $weight;
						$obj->insert();
					}
				}

			}
			unset($this->_libraries);
		}
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			foreach($this->_locations as $obj) {
				/** @var DataObject $obj */
				if($obj->_deleteOnSave) {
					$obj->delete();
				} else {
					if (isset($obj->{$obj->__primaryKey}) && is_numeric($obj->{$obj->__primaryKey})) {
						if($obj->{$obj->__primaryKey} <= 0) {
							$obj->themeId = $this->{$this->__primaryKey};
							$obj->insert();
						} else {
							if($obj->hasChanges()) {
								$obj->update();
							}
						}
					} else {
						// set appropriate weight for new theme
						$weight = 0;
						$existingThemesForLocation = new LocationTheme();
						$existingThemesForLocation->locationId = $obj->locationId;
						if ($existingThemesForLocation->find()) {
							while ($existingThemesForLocation->fetch()) {
								$weight = $weight + 1;
							}
						}

						$obj->themeId = $this->{$this->__primaryKey};
						$obj->weight = $weight;
						$obj->insert();
					}
				}

			}
			unset($this->_locations);
		}
	}

	/**
	 * @return array|null
	 */
	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new LibraryTheme();
			$obj->themeId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->id] = clone $obj;
			}
		}
		return $this->_libraries;
	}

	/**
	 * @return array|null
	 */
	public function getLocations() : ?array {
		if (!isset($this->_locations) && $this->id) {
			$this->_locations = [];
			$obj = new LocationTheme();
			$obj->themeId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_locations[$obj->id] = clone $obj;
			}
		}
		return $this->_locations;
	}

	/** @noinspection PhpUnused */
	public function setLibraries($val) {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function setLocations($val) {
		$this->_libraries = $val;
	}

	/** @noinspection PhpUnused */
	public function clearLibraries() {
		$this->clearOneToManyOptions('LibraryTheme', 'themeId');
		unset($this->_libraries);
	}

	/** @noinspection PhpUnused */
	public function clearLocations() {
		$this->clearOneToManyOptions('LocationTheme', 'themeId');
		unset($this->_locations);
	}

	public function canActiveUserEdit() : bool {
		if (UserAccount::userHasPermission('Administer All Themes')) {
			return true;
		} elseif (UserAccount::userHasPermission('Administer Library Themes')) {
			$libraries = $this->getLibraries();
			$validLibraries = Library::getLibraryList(true);
			$validLibraryIds = array_keys($validLibraries);
			foreach ($libraries as $libraryTheme) {
				if (in_array($libraryTheme->libraryId, $validLibraryIds)) {
					return true;
				}
			}
			return false;
		} else {
			return false;
		}
	}

	public function getApiInfo() : Theme {
		global $configArray;

		$apiInfo = $this;
		$this->logoName = $configArray['Site']['url'] . '/files/original/' . $this->logoName;
		$this->favicon = $configArray['Site']['url'] . '/files/original/' . $this->favicon;
		$this->headerLogoApp = $configArray['Site']['url'] . '/files/original/' . $this->headerLogoApp;
		unset($this->additionalCssType);
		unset($this->additionalCss);
		unset($this->generatedCss);
		unset($this->__table);
		unset($this->__primaryKey);
		unset($this->__displayNameColumn);
		unset($this->_deleteOnSave);

		return $apiInfo;
	}

	public function prepareForSharingToCommunity() {
		parent::prepareForSharingToCommunity();
		unset($this->logoName);
		unset($this->_libraries);
		unset($this->_locations);
		unset($this->_parentTheme);
		unset($this->extendsTheme);
		unset($this->footerLogo);
		unset($this->footerLogoAlt);
		unset($this->footerLogoLink);
		unset($this->favicon);
		unset($this->logoApp);
		unset($this->headerLogoApp);
		unset($this->headerBackgroundImage);
		unset($this->customBodyFont);
		unset($this->customHeadingFont);
		unset($this->generatedCss);
	}

	/**
	 * Check to see if this object should not be deleted because it will cause inconsistencies in other objects.
	 * Prevent deletion of the default theme (ID: 1) and handle relationships with dependent objects.
	 *
	 * @param array $structure The object structure of this object.
	 * @return array
	 */
	public function getDeletionBlockInformation(array $structure) : array {
		return $this->checkDeletionDependencies();
	}

	/**
	 * Checks if the theme can be deleted based on dependencies.
	 * - Prevents deletion of the default theme (ID: 1).
	 * - Prevents deletion if the theme is extended by child themes.
	 * - Prevents deletion if the theme is the only theme for any library.
	 *
	 * @return array ['preventDeletion' => bool, 'message' => string]
	 */
	private function checkDeletionDependencies() : array {
		// Check if this is the default theme (ID: 1) and prevent its deletion.
		if ($this->id == 1) {
			return [
				'preventDeletion' => true,
				'message' => translate(['text' => 'The default theme ' . $this->themeName . ' (ID: 1) cannot be deleted.', 'isAdminFacing'=>true])
			];
		}

		$objectDependencyInfo = [
			'preventDeletion' => false,
			'message' => ''
		];
		$errorMessages = [];

		// Check for child themes to prevent orphaned themes.
		$childThemesWithThisParent = [];
		$childTheme = new Theme();
		$childTheme->extendsTheme = $this->themeName;
		$childTheme->find();
		while ($childTheme->fetch()) {
			if ($childTheme->id != $this->id) {
				$childThemesWithThisParent[] = $childTheme->displayName;
			}
		}
		if (count($childThemesWithThisParent) > 0) {
			$objectDependencyInfo['preventDeletion'] = true;
			if (count($childThemesWithThisParent) == 1) {
				$errorMessages[] = translate([
					'text' => 'It is extended by the child theme: %1%. Before proceeding, please update the child theme to extend a different theme or "None".',
					'isAdminFacing' => true,
					1 => $childThemesWithThisParent[0]
				]);
			} else {
				$childThemesList = implode(', ', $childThemesWithThisParent);
				$errorMessages[] = translate([
					'text' => 'It is extended by the child themes: %1%. Before proceeding, please update the child themes to extend a different theme or "None".',
					'isAdminFacing' => true,
					1 => $childThemesList
				]);
			}
		}

		// Custom handling for library themes to only prevent deletion
		// if removing this theme would leave a library without any themes.
		// There is no need to prevent the deletion of locations that would be left
		// without any themes because their fallback is their respective library's theme.
		require_once ROOT_DIR . '/sys/LibraryLocation/LibraryTheme.php';
		$librariesWithOnlyThisTheme = [];
		$libraryTheme = new LibraryTheme();
		$libraryTheme->themeId = $this->id;
		$libraryTheme->find();
		while ($libraryTheme->fetch()) {
			// For each library using this theme, check if it has other themes.
			$otherThemeCheck = new LibraryTheme();
			$otherThemeCheck->libraryId = $libraryTheme->libraryId;
			$otherThemeCheck->whereAdd("themeId != {$this->id}");
			if ($otherThemeCheck->count() == 0) {
				require_once ROOT_DIR . '/sys/LibraryLocation/Library.php';
				$library = new Library();
				$library->libraryId = $libraryTheme->libraryId;
				if ($library->find(true)) {
					$librariesWithOnlyThisTheme[] = $library->displayName;
				}
			}
		}
		if (count($librariesWithOnlyThisTheme) > 0) {
			$objectDependencyInfo['preventDeletion'] = true;
			if (count($librariesWithOnlyThisTheme) == 1) {
				$errorMessages[] = translate([
					'text' => 'It is the only theme for the library: %1%. Before proceeding, please assign another theme to this library.',
					'isAdminFacing' => true,
					1 => $librariesWithOnlyThisTheme[0]
				]);
			} else {
				$librariesList = implode(', ', $librariesWithOnlyThisTheme);
				$errorMessages[] = translate([
					'text' => 'It is the only theme for the libraries: %1%. Before proceeding, please assign another theme to these libraries.',
					'isAdminFacing' => true,
					1 => $librariesList
				]);
			}
		}

		if ($objectDependencyInfo['preventDeletion']) {
			$objectDependencyInfo['message'] = translate([
				'text' => 'The theme %1% cannot be deleted because:',
				'isAdminFacing' => true,
				1 => $this->themeName
			]) . '<ul>';
			foreach ($errorMessages as $msg) {
				$objectDependencyInfo['message'] .= '<li>' . $msg . '</li>';
			}
			$objectDependencyInfo['message'] .= '</ul>';
		}

		return $objectDependencyInfo;
	}

	/**
	 * Validates the `extendsTheme` property to ensure it references a valid, non-self theme.
	 * - If `extendsTheme` is set but does not point to an existing theme, it resets the value and logs an error.
	 * - If `extendsTheme` references the current theme itself, it removes the self-reference and logs the correction.
	 *
	 * @return bool Returns true if the `extendsTheme` value was invalid and had to be reset (non-existent or self-referential), false otherwise.
	 */
	private function validateExtendsTheme(): bool {
		if (!empty($this->extendsTheme)) {
			$parentTheme = new Theme();
			$parentTheme->themeName = $this->extendsTheme;
			if (!$parentTheme->find(true)) {
				$originalExtends = $this->extendsTheme;
				$this->extendsTheme = '';
				global $logger;
				$logger->log("Theme {$this->themeName} was extending non-existent theme {$originalExtends}, reset to no parent.", Logger::LOG_NOTICE);
				// No need to check for self-reference if the parent didn't exist.
				return true;
			}
		}

		if (!empty($this->extendsTheme) && $this->extendsTheme == $this->themeName) {
			$this->extendsTheme = '';
			global $logger;
			$logger->log("Fixed self-referential theme: {$this->themeName} was extending itself.", Logger::LOG_NOTICE);
			return true;
		}
		return false;
	}

	/**
	 * Modify the structure of the object based on the object currently being edited.
	 * This can be used to change enums or other values based on the object being edited, so we know relationships.
	 *
	 * @param $structure
	 * @return array
	 */
	public function updateStructureForEditingObject($structure) : array {
		// Remove the current theme from the list of available themes to extend.
		if (!empty($this->themeName) && isset($structure['extendsTheme']['values'][$this->themeName])) {
			unset($structure['extendsTheme']['values'][$this->themeName]);
		}
		return $structure;
	}

	protected $_defaultTheme = null;

	/**
	 * Get the default theme or, failing that, get the first theme stored in the database
	 */
	public function getDefaultTheme( bool $resetTheme = false ): Theme {
		if ($this->_defaultTheme != null && !$resetTheme) {
			return $this->_defaultTheme;
		}

		$defaultTheme = new Theme;
		$defaultTheme->themeName = 'default';

		if (!$defaultTheme->find(true)) {
			unset($defaultTheme->themeName);
			$defaultTheme->find(true);
		}

		$this->_defaultTheme = clone $defaultTheme;

		return $this->_defaultTheme;
	}

	public static function updateCssForAllThemes() : void {
		set_time_limit(0);
		//Make sure themes are only updated once
		$themeIdsUpdated = [];
		$allThemesByName = [];
		$theme = new Theme();
		$theme->find();
		while ($theme->fetch()) {
			$allThemesByName[$theme->themeName] = clone $theme;
		}
		//We need to make sure that all parent themes are updated before the child.
		// Will also need to look for recursion
		while (count($themeIdsUpdated) != count($allThemesByName)) {
			foreach ($allThemesByName as $theme) {
				$parentThemes = $theme->getThemeHierarchy($allThemesByName, []);
				/** @var Theme $parentTheme */
				foreach ($parentThemes as $parentTheme) {
					if (!array_key_exists($parentTheme->id, $themeIdsUpdated)) {
						$parentTheme->generateCss(true);
						$themeIdsUpdated[$parentTheme->id] = $parentTheme->id;
					}
				}
			}
		}
	}

	private $_themeHierarchy = null;
	/**
	 * Returns an array of the theme hierarchy from the farthest grandparent to the current theme.
	 * I.e. if C extends B which extends A, it will return an array of C, B, A.
	 * If a theme does not extend anything, an array with only the theme will be returned
	 * If recursion is found, C extends B which extends C, the recursion will be stopped and an array of C, B will be returned
	 *
	 * @param Theme[] $allThemesByName an array of all themes in the system with a key of the name
	 * @param Theme[] $existingHierarchy an array of the hierarchy that has been built so far
	 * @return Theme[]
	 */
	private function getThemeHierarchy(array $allThemesByName, array $existingHierarchy = []) : array {
		if ($this->_themeHierarchy == null) {
			$this->_themeHierarchy = $existingHierarchy;
			if (array_key_exists($this->themeName, $existingHierarchy)) {
				//We have a recursive situation, quit
			}else{
				if (empty($this->extendsTheme)) {
					$this->_themeHierarchy[$this->themeName] = $this;
				}else{
					$directParentName = $this->extendsTheme;
					if (array_key_exists($directParentName, $existingHierarchy)) {
						//We have a recursive situation, quit
					}else{
						$existingHierarchy = array_merge([$this->themeName => $this], $existingHierarchy);
						$parentTheme = $allThemesByName[$directParentName];
						$this->_themeHierarchy = array_merge($parentTheme->getThemeHierarchy($allThemesByName, $existingHierarchy), $existingHierarchy);
					}
				}
			}
			if ($this->_allAppliedThemes == null) {
				$this->_allAppliedThemes = array_reverse($this->_themeHierarchy, true);
			}
		}
		return $this->_themeHierarchy;
	}
}