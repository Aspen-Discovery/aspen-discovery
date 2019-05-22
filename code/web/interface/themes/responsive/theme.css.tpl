{strip}
<!--suppress CssUnusedSymbol -->
<style type="text/css">
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