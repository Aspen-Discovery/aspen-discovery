{strip}
{if false}
<!--suppress CssUnusedSymbol -->
{/if}
<style type="text/css">

{if !empty($customHeadingFont) && !empty($customHeadingFontName)}
@font-face {ldelim}
    font-family: '{$customHeadingFontName}';
    src: url('/fonts/{$customHeadingFont}');
{rdelim}
{elseif $headingFont}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family={$headingFont}">
{/if}
{if !empty($customBodyFont) && !empty($customBodyFontName)}
@font-face {ldelim}
    font-family: '{$customBodyFontName}';
    src: url('/fonts/{$customBodyFont}');
{rdelim}
{elseif $bodyFont}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family={$bodyFont}">
{/if}

{if $headingFont}
h1, h2, h3, h4, h5, .header-button, .menu-bar-label, .panel-title, label,.browse-category,#browse-sub-category-menu,button,
.btn,.myAccountLink,.adminMenuLink,.selected-browse-label-search,.result-label,.result-title,.label,#remove-search-label,#narrow-search-label,#library-name-header{ldelim}
    font-family: "{$headingFont}", "Helvetica Neue", Helvetica, Arial, sans-serif;
{rdelim}
{/if}
{if $bodyFont}
body{ldelim}
    font-family: "{$bodyFont}", "Helvetica Neue", Helvetica, Arial, sans-serif;
{rdelim}
{/if}

#header-container{ldelim}
    {if $headerBackgroundColor}
    background-color: {$headerBackgroundColor};
    background-image: none;
    {/if}
    {if $headerForegroundColor}
        color: {$headerForegroundColor};
    {/if}
    {if $headerBottomBorderWidth}
        border-bottom-width: {$headerBottomBorderWidth};
    {/if}
{rdelim}

{if $headerForegroundColor}
#library-name-header{ldelim}
    color: {$headerForegroundColor};
{rdelim}
{/if}

{if !empty($footerBackgroundColor) || !empty($footerForegroundColor)}
#footer-container{ldelim}
    {if !empty($footerBackgroundColor)}
    background-color: {$footerBackgroundColor};
    {/if}
    {if !empty($footerForegroundColor)}
    color: {$footerForegroundColor};
    {/if}
{rdelim}
{/if}

.header-button{ldelim}
    {if $headerButtonBackgroundColor}
        background-color: {$headerButtonBackgroundColor};
    {/if}
    {if $headerButtonColor}
        color: {$headerButtonColor};
    {/if}
    {if $headerButtonRadius}
        border-radius: {$headerButtonRadius};
    {/if}
{rdelim}

{if $pageBackgroundColor}
body, #home-page-browse-header {ldelim}
    background-color: {$pageBackgroundColor};
{rdelim}
{/if}

{if $bodyBackgroundColor}
body .container{ldelim}
    background-color: {$bodyBackgroundColor};
{rdelim}
{/if}

{if $primaryBackgroundColor}
#home-page-search, #horizontal-search-box, #explore-more-sidebar,.searchTypeHome,.searchSource,.menu-bar,#vertical-menu-bar{ldelim}
    background-color: {$primaryBackgroundColor};
{rdelim}
#vertical-menu-bar .menu-bar-option.menu-icon-selected,.exploreMoreBar .label-top, .exploreMoreBar .label-top img{ldelim}
    background-color: {$primaryBackgroundColorLightened80};
{rdelim}
.exploreMoreBar{ldelim}
    border-color: {$primaryBackgroundColorLightened80};
{rdelim}
#vertical-menu-bar .menu-bar-option:hover{ldelim}
    background-color: {$primaryBackgroundColorLightened60};
{rdelim}
{/if}

{if $primaryForegroundColor}
#home-page-search-label,#home-page-advanced-search-link,#keepFiltersSwitchLabel, #advancedSearchLink,.menu-bar,#vertical-menu-bar{ldelim}
    color: {$primaryForegroundColor}
{rdelim}
{/if}

{if $bodyTextColor}
.browse-category{ldelim}
    color: {$bodyTextColor}
{rdelim}
{/if}

{if $secondaryBackgroundColor}
.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover,.active .panel-heading, .titleScrollerHeader{ldelim}
    border-color: {$secondaryBackgroundColor} !important;
    background: {$secondaryBackgroundColor} !important;
{rdelim}
{/if}

{if $secondaryForegroundColor}
.browse-category{ldelim}
    background-color: {$secondaryForegroundColor};
    border-color: {$secondaryForegroundColor};
{rdelim}
.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover,.active .panel-heading, .titleScrollerHeader{ldelim}
    color: {$secondaryForegroundColor} !important;
{rdelim}
{/if}

{if $tertiaryBackgroundColor}
#footer-container{ldelim}
    border-top-color: {$tertiaryBackgroundColor};
{rdelim}
#header-container{ldelim}
    border-bottom-color: {$tertiaryBackgroundColor};
{rdelim}
{/if}

{if $sidebarHighlightBackgroundColor || $sidebarHighlightForegroundColor}
#vertical-menu-bar .menu-bar-option.menu-icon-selected,#vertical-menu-bar .menu-bar-option:hover{ldelim}
    {if $sidebarHighlightBackgroundColor}
        background-color: {$sidebarHighlightBackgroundColor};
    {/if}
    {if $sidebarHighlightForegroundColor}
        color: {$sidebarHighlightForegroundColor};
    {/if}
{rdelim}
{/if}

{* Browse Categories *}
{if $browseCategoryPanelColor}
#home-page-browse-header{ldelim}
    background-color: {$browseCategoryPanelColor};
{rdelim}
{/if}

{if $deselectedBrowseCategoryBackgroundColor || $deselectedBrowseCategoryForegroundColor || $deselectedBrowseCategoryBorderColor}
.browse-category,#browse-sub-category-menu button{ldelim}
    {if $deselectedBrowseCategoryBackgroundColor}
        background-color: {$deselectedBrowseCategoryBackgroundColor} !important;
    {/if}
    {if $deselectedBrowseCategoryBorderColor}
        border-color: {$deselectedBrowseCategoryBorderColor} !important;
    {/if}
    {if $deselectedBrowseCategoryForegroundColor}
        color: {$deselectedBrowseCategoryForegroundColor} !important;
    {/if}
{rdelim}
{/if}

{if $selectedBrowseCategoryBackgroundColor || $selectedBrowseCategoryForegroundColor || $selectedBrowseCategoryBorderColor}
.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover{ldelim}
    {if $selectedBrowseCategoryBorderColor}
        border-color: {$selectedBrowseCategoryBorderColor} !important;
    {/if}
    {if $selectedBrowseCategoryBackgroundColor}
        background-color: {$selectedBrowseCategoryBackgroundColor} !important;
    {/if}
    {if $selectedBrowseCategoryForegroundColor}
        color: {$selectedBrowseCategoryForegroundColor} !important;
    {/if}
{rdelim}
{/if}

{if !empty($capitalizeBrowseCategories)}
.browse-category div{ldelim}
    text-transform: uppercase;
{rdelim}
{/if}

{if !empty($buttonRadius)}
.btn{ldelim}
    border-radius: {$buttonRadius};
{rdelim}
{/if}

{if !empty($smallButtonRadius)}
.btn-sm{ldelim}
    border-radius: {$smallButtonRadius};
{rdelim}
{/if}

{if !empty($defaultButtonBackgroundColor) || !empty($defaultButtonForegroundColor) || !empty($defaultButtonBorderColor)}
.btn-default{ldelim}
    {if !empty($defaultButtonBackgroundColor)}
    background-color: {$defaultButtonBackgroundColor};
    {/if}
    {if !empty($defaultButtonForegroundColor)}
    color: {$defaultButtonForegroundColor};
    {/if}
    {if !empty($defaultButtonBorderColor)}
    border-color: {$defaultButtonBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($defaultButtonHoverBackgroundColor) || !empty($defaultButtonHoverForegroundColor) || !empty($defaultButtonHoverBorderColor)}
.btn-default:hover, .btn-default:focus, .btn-default:active, .btn-default.active, .open .dropdown-toggle.btn-default{ldelim}
    {if !empty($defaultButtonHoverBackgroundColor)}
    background-color: {$defaultButtonHoverBackgroundColor};
    {/if}
    {if !empty($defaultButtonHoverForegroundColor)}
    color: {$defaultButtonHoverForegroundColor};
    {/if}
    {if !empty($defaultButtonHoverBorderColor)}
    border-color: {$defaultButtonHoverBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($primaryButtonBackgroundColor) || !empty($primaryButtonForegroundColor) || !empty($primaryButtonBorderColor)}
.btn-primary{ldelim}
    {if !empty($primaryButtonBackgroundColor)}
    background-color: {$primaryButtonBackgroundColor};
    {/if}
    {if !empty($primaryButtonForegroundColor)}
    color: {$primaryButtonForegroundColor};
    {/if}
    {if !empty($primaryButtonBorderColor)}
    border-color: {$primaryButtonBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($primaryButtonHoverBackgroundColor) || !empty($primaryButtonHoverForegroundColor) || !empty($primaryButtonHoverBorderColor)}
.btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active, .open .dropdown-toggle.btn-primary{ldelim}
    {if !empty($primaryButtonHoverBackgroundColor)}
    background-color: {$primaryButtonHoverBackgroundColor};
    {/if}
    {if !empty($primaryButtonHoverForegroundColor)}
    color: {$primaryButtonHoverForegroundColor};
    {/if}
    {if !empty($primaryButtonHoverBorderColor)}
    border-color: {$primaryButtonHoverBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($actionButtonBackgroundColor) || !empty($actionButtonForegroundColor) || !empty($actionButtonBorderColor)}
.btn-action{ldelim}
    {if !empty($actionButtonBackgroundColor)}
    background-color: {$actionButtonBackgroundColor};
    {/if}
    {if !empty($actionButtonForegroundColor)}
    color: {$actionButtonForegroundColor};
    {/if}
    {if !empty($actionButtonBorderColor)}
    border-color: {$actionButtonBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($actionButtonHoverBackgroundColor) || !empty($actionButtonHoverForegroundColor) || !empty($actionButtonHoverBorderColor)}
.btn-action:hover, .btn-action:focus, .btn-action:active, .btn-action.active, .open .dropdown-toggle.btn-action{ldelim}
    {if !empty($actionButtonHoverBackgroundColor)}
    background-color: {$actionButtonHoverBackgroundColor};
    {/if}
    {if !empty($actionButtonHoverForegroundColor)}
    color: {$actionButtonHoverForegroundColor};
    {/if}
    {if !empty($actionButtonHoverBorderColor)}
    border-color: {$actionButtonHoverBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($infoButtonBackgroundColor) || !empty($infoButtonForegroundColor) || !empty($infoButtonBorderColor)}
.btn-info{ldelim}
    {if !empty($infoButtonBackgroundColor)}
    background-color: {$infoButtonBackgroundColor};
    {/if}
    {if !empty($infoButtonForegroundColor)}
    color: {$infoButtonForegroundColor};
    {/if}
    {if !empty($infoButtonBorderColor)}
    border-color: {$infoButtonBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($infoButtonHoverBackgroundColor) || !empty($infoButtonHoverForegroundColor) || !empty($infoButtonHoverBorderColor)}
.btn-info:hover, .btn-info:focus, .btn-info:active, .btn-info.active, .open .dropdown-toggle.btn-info{ldelim}
    {if !empty($infoButtonHoverBackgroundColor)}
    background-color: {$infoButtonHoverBackgroundColor};
    {/if}
    {if !empty($infoButtonHoverForegroundColor)}
    color: {$infoButtonHoverForegroundColor};
    {/if}
    {if !empty($infoButtonHoverBorderColor)}
    border-color: {$infoButtonHoverBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($warningButtonBackgroundColor) || !empty($warningButtonForegroundColor) || !empty($warningButtonBorderColor)}
.btn-warning{ldelim}
    {if !empty($warningButtonBackgroundColor)}
    background-color: {$warningButtonBackgroundColor};
    {/if}
    {if !empty($warningButtonForegroundColor)}
    color: {$warningButtonForegroundColor};
    {/if}
    {if !empty($warningButtonBorderColor)}
    border-color: {$warningButtonBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($warningButtonHoverBackgroundColor) || !empty($warningButtonHoverForegroundColor) || !empty($warningButtonHoverBorderColor)}
.btn-warning:hover, .btn-warning:focus, .btn-warning:active, .btn-warning.active, .open .dropdown-toggle.btn-warning{ldelim}
    {if !empty($warningButtonHoverBackgroundColor)}
    background-color: {$warningButtonHoverBackgroundColor};
    {/if}
    {if !empty($warningButtonHoverForegroundColor)}
    color: {$warningButtonHoverForegroundColor};
    {/if}
    {if !empty($warningButtonHoverBorderColor)}
    border-color: {$warningButtonHoverBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($dangerButtonBackgroundColor) || !empty($dangerButtonForegroundColor) || !empty($dangerButtonBorderColor)}
.btn-danger{ldelim}
    {if !empty($dangerButtonBackgroundColor)}
    background-color: {$dangerButtonBackgroundColor};
    {/if}
    {if !empty($dangerButtonForegroundColor)}
    color: {$dangerButtonForegroundColor};
    {/if}
    {if !empty($dangerButtonBorderColor)}
    border-color: {$dangerButtonBorderColor};
    {/if}
{rdelim}
{/if}

{if !empty($dangerButtonHoverBackgroundColor) || !empty($dangerButtonHoverForegroundColor) || !empty($dangerButtonHoverBorderColor)}
.btn-danger:hover, .btn-danger:focus, .btn-danger:active, .btn-danger.active, .open .dropdown-toggle.btn-danger{ldelim}
    {if !empty($dangerButtonHoverBackgroundColor)}
    background-color: {$dangerButtonHoverBackgroundColor};
    {/if}
    {if !empty($dangerButtonHoverForegroundColor)}
    color: {$dangerButtonHoverForegroundColor};
    {/if}
    {if !empty($dangerButtonHoverBorderColor)}
    border-color: {$dangerButtonHoverBorderColor};
    {/if}
{rdelim}
{/if}

{* Webbuilder*}
#webMenuNavBar{ldelim}
    {if !empty($primaryBackgroundColor)}
    background-color: {$primaryBackgroundColor};
    {/if}
    margin-bottom: 2px;
    {if !empty($primaryForegroundColor)}
    color: {$primaryForegroundColor};
    .navbar-nav > li > a, .navbar-nav > li > a:visited {ldelim}
        color: {$primaryForegroundColor};
    {rdelim}
    {/if}
{rdelim}

.dropdown-menu{ldelim}
    background-color: white;
    {if !empty($bodyTextColor)}
    color: {$bodyTextColor};
    {else}
    color: black;
    {/if}
{rdelim}

{$additionalCSS}
</style>
{/strip}