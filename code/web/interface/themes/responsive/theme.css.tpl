{strip}
{if $headingFont}
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family={$headingFont}">
{/if}
{if $bodyFont}
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family={$bodyFont}">
{/if}

{if false}
<!--suppress CssUnusedSymbol -->
{/if}
<style type="text/css">
{if $headingFont}
h1, h2, h3, h4, h5, .header-button, .menu-bar-label, .panel-title, label,.browse-category,#browse-sub-category-menu,button,
.btn,.myAccountLink,.adminMenuLink,.selected-browse-label-search,.result-label,.result-title,.label,#remove-search-label,
#results-sort-label,#narrow-search-label{ldelim}
    font-family: {$headingFont}, "Helvetica Neue", Helvetica, Arial, sans-serif;
{rdelim}
{/if}
{if $bodyFont}
body{ldelim}
    font-family: {$bodyFont}, "Helvetica Neue", Helvetica, Arial, sans-serif;
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
    background-color: {$primaryBackgroundColor}
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

</style>
{/strip}