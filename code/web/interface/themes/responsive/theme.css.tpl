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
.btn,.myAccountLink,.adminMenuLink,.selected-browse-label-search,.result-label,.result-title,.label,#remove-search-label,#narrow-search-label{ldelim}
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
.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover,.active .panel-heading{ldelim}
    border-color: {$secondaryBackgroundColor} !important;
    background: {$secondaryBackgroundColor} !important;
{rdelim}
{/if}

{if $secondaryForegroundColor}
.browse-category{ldelim}
    background-color: {$secondaryForegroundColor};
    border-color: {$secondaryForegroundColor};
{rdelim}
.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover,.active .panel-heading{ldelim}
    color: {$secondaryForegroundColor} !important;
{rdelim}
{/if}

{if $tertiaryBackgroundColor}
#footer-container{ldelim}
    border-top-color: {$tertiaryBackgroundColor};
{rdelim}
#header-container{ldelim}
{if $tertiaryBackgroundColor}
    border-bottom-color: {$tertiaryBackgroundColor};
{/if}
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

{if $capitalizeBrowseCategories}
.browse-category div{ldelim}
    text-transform: uppercase;
{rdelim}
{/if}

{$additionalCSS}
</style>
{/strip}