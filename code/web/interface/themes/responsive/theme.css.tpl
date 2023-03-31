{strip}
{if false}
<!--suppress CssUnusedSymbol -->
{/if}
{if !empty($headingFont)}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family={$headingFont}">
{/if}
{if !empty($bodyFont)}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family={$bodyFont}">
{/if}

<style type="text/css">

{if !empty($customHeadingFont) && !empty($customHeadingFontName)}
@font-face {ldelim}
    font-family: '{$customHeadingFontName}';
    src: url('/fonts/{$customHeadingFont}');
{rdelim}
{/if}
{if !empty($customBodyFont) && !empty($customBodyFontName)}
@font-face {ldelim}
    font-family: '{$customBodyFontName}';
    src: url('/fonts/{$customBodyFont}');
{rdelim}
{/if}

{if !empty($headingFont)}
h1, h2, h3, h4, h5, .menu-bar-label, .panel-title, label,.browse-category,#browse-sub-category-menu,button,
.btn,.myAccountLink,.adminMenuLink,.selected-browse-label-search,.result-label,.result-title,.label,#remove-search-label,#narrow-search-label,#library-name-header{ldelim}
    font-family: "{$headingFont}", "Helvetica Neue", Helvetica, Arial, sans-serif;
{rdelim}
{/if}
{if !empty($bodyFont)}
body{ldelim}
    font-family: "{$bodyFont}", "Helvetica Neue", Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    font-smooth: always;
    font-size: 14px;
{rdelim}
{/if}
h1 small, h2 small, h3 small, h4 small, h5 small{ldelim}
    color: {$bodyTextColor};
{rdelim}

#header-wrapper{ldelim}
    background-color: {$headerBackgroundColor};
    {if !empty($headerBackgroundImage)}
        background-image: url('/files/original/{$headerBackgroundImage}');
        background-size: {$headerBackgroundImageSize};
        background-repeat: {$headerBackgroundImageRepeat};
    {else}
        background-image: none;
    {/if}
    color: {$headerForegroundColor};
{rdelim}

#library-name-header{ldelim}
    color: {$headerForegroundColor};
{rdelim}

#footer-container{ldelim}
    background-color: {$footerBackgroundColor};
    color: {$footerForegroundColor};
{rdelim}

body {ldelim}
    background-color: {$pageBackgroundColor};
    color: {$bodyTextColor};
{rdelim}

a,a:visited,.result-head,#selected-browse-label a,#selected-browse-label a:visited{ldelim}
    color: {$linkColor};
{rdelim}
a:hover,.result-head:hover,#selected-browse-label a:hover{ldelim}
    color: {$linkHoverColor};
{rdelim}

body .container, #home-page-browse-content{ldelim}
    background-color: {$bodyBackgroundColor};
    color: {$bodyTextColor};
{rdelim}

#selected-browse-label{ldelim}
    background-color: {$bodyBackgroundColor};
{rdelim}

.table-striped > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th{ldelim}
    background-color: {$tableStripeBackgroundColor};
{rdelim}
.table-sticky thead tr th{ldelim}
    background-color: {$bodyBackgroundColor};
{rdelim}

#home-page-search, #horizontal-search-box,.searchTypeHome,.searchSource,.menu-bar {ldelim}
    background-color: {$pageBackgroundColor};
    color: {$primaryForegroundColor};
{rdelim}

#horizontal-search-box .searchSourceHorizontal, #horizontal-search-box .searchTypeHorizontal{ldelim}
    background-color: {$defaultButtonBackgroundColor};
    color: {$defaultButtonForegroundColor};
    border: 1px solid {$defaultButtonBorderColor};
{rdelim}

#home-search-box .input-group-addon{ldelim}
	background-color: {$secondaryBackgroundColor};
{rdelim}

#horizontal-menu-bar-container{ldelim}
    background-color: {$headerBackgroundColor};
    color: {$headerForegroundColor};
    position: relative;
{rdelim}

#horizontal-menu-bar-container, #horizontal-menu-bar-container .menu-icon, #horizontal-menu-bar-container .menu-icon .menu-bar-label,
 #horizontal-menu-bar-container .menu-icon:visited{ldelim}
    background-color: {$menubarBackgroundColor};
    color: {$menubarForegroundColor};
{rdelim}

#horizontal-menu-bar-container .menu-icon:hover, #horizontal-menu-bar-container .menu-icon:focus,
#horizontal-menu-bar-container .menu-icon:hover .menu-bar-label, #horizontal-menu-bar-container .menu-icon:focus .menu-bar-label,
#menuToggleButton.selected{ldelim}
    background-color: {$menubarHighlightBackgroundColor};
    color: {$menubarHighlightForegroundColor};
{rdelim}
#horizontal-search-label,#horizontal-search-box #horizontal-search-label{ldelim}
    color: {$primaryForegroundColor};
{rdelim}

#lookfor{ldelim}
	border-radius: 0;
	background-color: {$bodyBackgroundColor};
	color: {$bodyTextColor};
	border: 1px solid {$bodyTextColor};
{rdelim}

#lookfor-label{ldelim}
	color: {$bodyTextColor};
{rdelim}

.dropdownMenu, #account-menu, #header-menu, .dropdown .dropdown-menu.dropdownMenu, .dropdown-menu{ldelim}
    background-color: {$menuDropdownBackgroundColor} !important;
    color: {$menuDropdownForegroundColor} !important;
{rdelim}

.dropdownMenu a, .dropdownMenu a:visited, .dropdown-menu li a, .dropdown-menu li a:visited{ldelim}
    color: {$menuDropdownForegroundColor} !important;
    background-color: {$menuDropdownBackgroundColor} !important;
{rdelim}

.dropdownMenu a.btn{ldelim}
    color: {$defaultButtonForegroundColor} !important;
    background-color: {$defaultButtonBackgroundColor} !important;
{rdelim}

.modal-header, .modal-footer{ldelim}
    background-color: {$bodyBackgroundColor};
    color: {$bodyTextColor};
{rdelim}
.close, .close:hover, .close:focus{ldelim}
    color: {$bodyTextColor};
{rdelim}
.modal-header{ldelim}
    border-bottom-color: {$bodyBackgroundColor};
{rdelim}
.modal-footer{ldelim}
    border-top-color: {$bodyBackgroundColor};
{rdelim}
.modal-content{ldelim}
    background-color: {$bodyBackgroundColor};
    color: {$bodyTextColor};
{rdelim}

.exploreMoreBar{ldelim}
    border-color: {$primaryBackgroundColor};
    background: {$primaryBackgroundColor}07;
    border: 0;
{rdelim}
.exploreMoreBar .label-top, #explore-more-bar-placeholder, .exploreMoreBar, .exploreMoreBar .label-top .exploreMoreBarLabel, .exploreMoreBar .label-top, .exploreMoreBar .label-top img{ldelim}
    background-color: {$primaryBackgroundColor};
    color: {$primaryForegroundColor};
{rdelim}
.exploreMoreBar .exploreMoreBarLabel{ldelim}
    color: {$primaryForegroundColor};
{rdelim}


{if !empty($primaryForegroundColor)}
#home-page-search-label,#home-page-advanced-search-link,#keepFiltersSwitchLabel,.menu-bar, #horizontal-menu-bar-container {ldelim}
    color: {$primaryForegroundColor}
{rdelim}
{/if}

.facetTitle, .exploreMoreTitle, .panel-heading, .panel-heading .panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title{ldelim}
    background-color: {$closedPanelBackgroundColor};
{rdelim}
.facetTitle, .exploreMoreTitle,.panel-title,.panel-default > .panel-heading, .sidebar-links .panel-heading, #account-link-accordion .panel .panel-title, #account-settings-accordion .panel .panel-title, .panel-title > a,.panel-default > .panel-heading{ldelim}
    color: {$closedPanelForegroundColor};
{rdelim}
.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,.active .panel-default > .panel-heading, .sidebar-links .active .panel-heading, #account-link-accordion .panel.active .panel-title, #account-settings-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading, .adminSection .adminPanel .adminSectionLabel{ldelim}
    background-color: {$openPanelBackgroundColor};
{rdelim}
.facetTitle.expanded, .exploreMoreTitle.expanded,.active .panel-heading,#more-details-accordion .active .panel-heading,#more-details-accordion .active .panel-title,#account-link-accordion .panel.active .panel-title,.active .panel-title,.active .panel-title > a,.active.panel-default > .panel-heading,.adminSection .adminPanel .adminSectionLabel, .facetLock.pull-right a{ldelim}
    color: {$openPanelForegroundColor};
{rdelim}
.panel-body,.sidebar-links .panel-body,#more-details-accordion .panel-body,.facetDetails,.sidebar-links .panel-body a:not(.btn), .sidebar-links .panel-body a:visited:not(.btn), .sidebar-links .panel-body a:hover:not(.btn),.adminSection .adminPanel{ldelim}
    background-color: {$panelBodyBackgroundColor};
    color: {$panelBodyForegroundColor};
{rdelim}
.facetValue, .facetValue a,.adminSection .adminPanel .adminActionLabel,.adminSection .adminPanel .adminActionLabel a{ldelim}
    color: {$panelBodyForegroundColor};
{rdelim}

.breadcrumbs{ldelim}
    background-color: {$breadcrumbsBackgroundColor};
    color: {$breadcrumbsForegroundColor};
{rdelim}
.breadcrumb > li + li::before{ldelim}
    color: {$breadcrumbsForegroundColor};
{rdelim}

#footer-container{ldelim}
    border-top-color: {$tertiaryBackgroundColor};
{rdelim}

#horizontal-menu-bar-container{ldelim}
    border-bottom-color: {$tertiaryBackgroundColor};
    {if !empty($headerBottomBorderWidth)}
        border-bottom-width: {$headerBottomBorderWidth};
    {/if}
{rdelim}

{* Browse Categories *}
#home-page-browse-header{ldelim}
    background-color: {$browseCategoryPanelColor};
{rdelim}

.browse-category,#browse-sub-category-menu button{ldelim}
    background-color: {$deselectedBrowseCategoryBackgroundColor} !important;
    border-color: {$deselectedBrowseCategoryBorderColor} !important;
    color: {$deselectedBrowseCategoryForegroundColor} !important;
{rdelim}

.browse-category.selected,.browse-category.selected:hover,#browse-sub-category-menu button.selected,#browse-sub-category-menu button.selected:hover{ldelim}
    border-color: {$selectedBrowseCategoryBorderColor} !important;
    background-color: {$selectedBrowseCategoryBackgroundColor} !important;
    color: {$selectedBrowseCategoryForegroundColor} !important;
{rdelim}

{if !empty($capitalizeBrowseCategories)}
.browse-category div{ldelim}
    text-transform: uppercase;
{rdelim}
{/if}

{if !empty($browseImageLayout)}
#home-page-browse-results .grid-col{ldelim}
    display: inline-grid;
    grid-auto-rows: 1fr;
{rdelim}

@media (max-width: 479px){ldelim}
    #home-page-browse-results .grid-col--3{ldelim}
        display: none;
    {rdelim}
{rdelim}

@media (max-width: 991px){ldelim}
    #home-page-browse-results .grid-col--4{ldelim}
        display: none;
    {rdelim}
{rdelim}

@media (max-width: 1199px){ldelim}
#home-page-browse-results .grid-col--5{ldelim}
    display: none;
    {rdelim}
{rdelim}

@media (max-width: 1199px){ldelim}
#home-page-browse-results .grid-col--6{ldelim}
    display: none;
    {rdelim}
{rdelim}

#home-page-browse-results.HideBorder .browse-thumbnail{ldelim}
    border: none;
    align-items: center;
{rdelim}

#home-page-browse-results .browse-thumbnail{ldelim}
    display: inline-flex;
    flex-wrap:wrap;
    justify-content: center;
    align-items: flex-end;
    {if !empty($browseCategoryImageSize)}
    max-height: 350px;
    {else}
    max-height: 250px;
    {/if}
{rdelim}
{/if}

.browse-thumbnail{ldelim}
	background-color: transparent;
{rdelim}

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

.btn-default,.btn-default:visited,a.btn-default,a.btn-default:visited{ldelim}
    background-color: {$defaultButtonBackgroundColor};
    color: {$defaultButtonForegroundColor};
    border-color: {$defaultButtonBorderColor};
{rdelim}

.btn-default:hover, .btn-default:focus, .btn-default:active, .btn-default.active, .open .dropdown-toggle.btn-default{ldelim}
    background-color: {$defaultButtonHoverBackgroundColor};
    color: {$defaultButtonHoverForegroundColor};
    border-color: {$defaultButtonHoverBorderColor};
{rdelim}

.btn-primary,.btn-primary:visited,a.btn-primary,a.btn-primary:visited{ldelim}
    background-color: {$primaryButtonBackgroundColor};
    color: {$primaryButtonForegroundColor};
    border-color: {$primaryButtonBorderColor};
{rdelim}

.btn-primary:hover, .btn-primary:focus, .btn-primary:active, .btn-primary.active, .open .dropdown-toggle.btn-primary{ldelim}
    background-color: {$primaryButtonHoverBackgroundColor};
    color: {$primaryButtonHoverForegroundColor};
    border-color: {$primaryButtonHoverBorderColor};
{rdelim}

.btn-action,.btn-action:visited,a.btn-action,a.btn-action:visited{ldelim}
    background-color: {$actionButtonBackgroundColor};
    color: {$actionButtonForegroundColor};
    border-color: {$actionButtonBorderColor};
{rdelim}

.btn-action:hover, .btn-action:focus, .btn-action:active, .btn-action.active, .open .dropdown-toggle.btn-action{ldelim}
    background-color: {$actionButtonHoverBackgroundColor};
    color: {$actionButtonHoverForegroundColor};
    border-color: {$actionButtonHoverBorderColor};
{rdelim}

.btn-info,.btn-info:visited,a.btn-info,a.btn-info:visited{ldelim}
    background-color: {$infoButtonBackgroundColor};
    color: {$infoButtonForegroundColor};
    border-color: {$infoButtonBorderColor};
{rdelim}

.btn-info:hover, .btn-info:focus, .btn-info:active, .btn-info.active, .open .dropdown-toggle.btn-info{ldelim}
    background-color: {$infoButtonHoverBackgroundColor};
    color: {$infoButtonHoverForegroundColor};
    border-color: {$infoButtonHoverBorderColor};
{rdelim}

.btn-tools,.btn-tools:visited,a.btn-tools,a.btn-tools:visited{ldelim}
    background-color: {$toolsButtonBackgroundColor};
    color: {$toolsButtonForegroundColor};
    border-color: {$toolsButtonBorderColor};
{rdelim}

.btn-tools:hover, .btn-tools:focus, .btn-tools:active, .btn-tools.active, .open .dropdown-toggle.btn-tools{ldelim}
    background-color: {$toolsButtonHoverBackgroundColor};
    color: {$toolsButtonHoverForegroundColor};
    border-color: {$toolsButtonHoverBorderColor};
{rdelim}

.btn-warning,.btn-warning:visited,a.btn-warning,a.btn-warning:visited{ldelim}
    background-color: {$warningButtonBackgroundColor};
    color: {$warningButtonForegroundColor};
    border-color: {$warningButtonBorderColor};
{rdelim}

.btn-warning:hover, .btn-warning:focus, .btn-warning:active, .btn-warning.active, .open .dropdown-toggle.btn-warning{ldelim}
    background-color: {$warningButtonHoverBackgroundColor};
    color: {$warningButtonHoverForegroundColor};
    border-color: {$warningButtonHoverBorderColor};
{rdelim}

.label-warning{ldelim}
    background-color: {$warningButtonBackgroundColor};
    color: {$warningButtonForegroundColor};
{rdelim}

.btn-danger,.btn-danger:visited,a.btn-danger,a.btn-danger:visited{ldelim}
    background-color: {$dangerButtonBackgroundColor};
    color: {$dangerButtonForegroundColor};
    border-color: {$dangerButtonBorderColor};
{rdelim}

.btn-danger:hover, .btn-danger:focus, .btn-danger:active, .btn-danger.active, .open .dropdown-toggle.btn-danger{ldelim}
    background-color: {$dangerButtonHoverBackgroundColor};
    color: {$dangerButtonHoverForegroundColor};
    border-color: {$dangerButtonHoverBorderColor};
{rdelim}

.label-danger{ldelim}
    background-color: {$dangerButtonBackgroundColor};
    color: {$dangerButtonForegroundColor};
{rdelim}

.btn-editions,.btn-editions:visited{ldelim}
    background-color: {$editionsButtonBackgroundColor};
    color: {$editionsButtonForegroundColor};
    border-color: {$editionsButtonBorderColor};
{rdelim}

.btn-editions:hover, .btn-editions:focus, .btn-editions:active, .btn-editions.active{ldelim}
    background-color: {$editionsButtonHoverBackgroundColor};
    color: {$editionsButtonHoverForegroundColor};
    border-color: {$editionsButtonHoverBorderColor};
{rdelim}

.badge{ldelim}
    background-color: {$badgeBackgroundColor};
    color: {$badgeForegroundColor};
    {if (!empty($badgeBorderRadius))}
    border-radius: {$badgeBorderRadius};
    {/if}
{rdelim}

{* Alerts *}
.alert-success{ldelim}
    background-color: {$successButtonBackgroundColor};
    border-color: {$successButtonBackgroundColor};
    color: {$successButtonForegroundColor};
{rdelim}

.alert-info{ldelim}
    background-color: {$infoButtonBackgroundColor};
    border-color: {$infoButtonBackgroundColor};
    color: {$infoButtonForegroundColor};
{rdelim}

.alert-warning{ldelim}
    background-color: {$warningButtonBackgroundColor};
    border-color: {$warningButtonBackgroundColor};
    color: {$warningButtonForegroundColor};
{rdelim}

.alert-danger{ldelim}
    background-color: {$dangerButtonBackgroundColor};
    border-color: {$dangerButtonBackgroundColor};
    color: {$dangerButtonForegroundColor};
{rdelim}

#system-message-header {ldelim}
	background-color: {$pageBackgroundColor};
	color: {$primaryForegroundColor};
	max-width: 1080px;
	margin: 0 auto;
{rdelim}

{* Forms/Inputs *}
.form-control {ldelim}
	background-color: {$bodyBackgroundColor};
	color: {$bodyTextColor};
	border: 1px solid {$bodyTextColor};
	border-radius: {$smallButtonRadius}
{rdelim}

.form-control:focus, #horizontal-search-box #lookfor:focus, #horizontal-search-box .searchSourceHorizontal:focus, #horizontal-search-box .searchTypeHorizontal:focus{ldelim}
	color: {$linkColor};
	border-color: {$linkColor};
	--webkit-box-shadow: inset 0 1px 1px {$linkColor}, 0 0 8px {$linkColor}
	box-shadow: inset 0 1px 1px {$linkColor}, 0 0 8px {$linkColor}
{rdelim}

.input-group-addon{ldelim}
	background-color: {$bodyBackgroundColor};
	color: {$bodyTextColor};
	border: 1px solid {$bodyTextColor};
	border-radius: {$smallButtonRadius}
{rdelim}

legend{ldelim}
	color: {$bodyTextColor};
	border-color: {$bodyTextColor};
{rdelim}

label{ldelim}
	font-family: "{$bodyFont}", "Helvetica Neue", Helvetica, Arial, sans-serif;
{rdelim}

.bootstrap-switch{ldelim}
	border: 1px solid {$bodyTextColor};
	border-radius: {$smallButtonRadius}
{rdelim}

.bootstrap-switch > div > label{ldelim}
	background-color: {$bodyBackgroundColor};
	color: {$bodyTextColor};
{rdelim}

.bootstrap-switch > div > span.bootstrap-switch-default{ldelim}
	background-color: {$bodyBackgroundColor} !important;
    color: {$bodyTextColor} !important;
{rdelim}

.bootstrap-switch > div > span.bootstrap-switch-primary{ldelim}
	background-color: {$tertiaryBackgroundColor} !important;
    color: {$tertiaryForegroundColor} !important;
{rdelim}

.input-group-btn{ldelim}
	position: relative;
	font-size: 0;
	white-space: nowrap;
{rdelim}

.input-group-btn > .btn {ldelim}
	border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    z-index: 2;
    margin-left: -1px;
    border-color: {$bodyTextColor}
{rdelim}

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
    background-color: {$menuDropdownBackgroundColor};
    color: {$menuDropdownForegroundColor};
{rdelim}

.dropdown-menu > li > a:hover, .dropdown-menu > li > a:focus{ldelim}
    background-color: {$menuDropdownBackgroundColor} !important;
    color: {$menuDropdownForegroundColor} !important;
    text-decoration: underline;
{rdelim}

{* Search Results *}
.result-label{ldelim}
    color: {$resultLabelColor}
{rdelim}
.result-value{ldelim}
    color: {$resultValueColor}
{rdelim}
.search_tools{ldelim}
    background-color: {$searchToolsBackgroundColor};
    color: {$searchToolsForegroundColor};
{rdelim}

.top-link{ldelim}
    background-color: {$tertiaryBackgroundColor};
    color: {$tertiaryForegroundColor};
{rdelim}

.top-link:hover{ldelim}
    background-color: {$tertiaryBackgroundColor};
    color: {$tertiaryForegroundColor};
{rdelim}

.top-link i{ldelim}
    color: {$tertiaryForegroundColor};
{rdelim}

.top-link i:hover{ldelim}
    color: {$tertiaryForegroundColor};
{rdelim}

#results-sort{ldelim}
    background-color: {$defaultButtonBackgroundColor};
    color: {$defaultButtonForegroundColor};
    border: 1px solid {$defaultButtonBorderColor};
{rdelim}

.related-manifestation.grouped{ldelim}
	border-color: {$secondaryForegroundColor};
{rdelim}

{* Browse Category Carousel *}
.jcarousel-pagination a{ldelim}
	border-radius: {$smallButtonRadius};
	background: {$deselectedBrowseCategoryBackgroundColor};
	color: {$bodyTextColor};
	box-shadow: none;
{rdelim}

.jcarousel-pagination a.active{ldelim}
	background: {$selectedBrowseCategoryBackgroundColor};
	color: {$selectedBrowseCategoryForegroundColor};
	box-shadow: none;
{rdelim}

#browse-category-picker .jcarousel-control-prev, #browse-category-picker .jcarousel-control-next,.jcarousel-control-prev, .jcarousel-control-next{ldelim}
	background: {$browseCategoryPanelColor};
	color: {$bodyTextColor};
	box-shadow: none;
	text-shadow: none;
{rdelim}

#more-browse-results {ldelim}
	background: {$pageBackgroundColor};
	color: {$bodyTextColor};
	box-shadow: none;
	text-shadow: none;
{rdelim}

{* Panels / Accordions *}
.panel{ldelim}
	border: 1px solid {$openPanelBackgroundColor};
	background-color: transparent !important;
	box-shadow: none;
	margin-bottom: 1em !important;
{rdelim}

.accordion .facetList{ldelim}
	padding-bottom: 1em;
{rdelim}

.panel-heading{ldelim}
	border: 0 !important;
{rdelim}

.panel-body{ldelim}
	border-width: 0 !important;
{rdelim}

.facetTitle{ldelim}
	border: 0 !important;
{rdelim}


{* Tables *}
.striped-odd{ldelim}
	background-color: transparent !important;
{rdelim}

div.striped > div:nth-child(odd), div.striped > div:nth-child(odd){ldelim}
	background-color: {$primaryBackgroundColor} !important;
{rdelim}

{* Tabs *}
.nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus, .nav-tabs > li > a:hover{ldelim}
	background-color: {$primaryBackgroundColor};
    border-color: {$primaryBackgroundColor} !important;
    color: {$bodyTextColor}
{rdelim}

.nav-tabs{ldelim}
	border-bottom: 1px solid {$primaryBackgroundColor}
{rdelim}

.nav-tabs{ldelim}
	border-left: 1px solid {$primaryBackgroundColor};
	border-right: 1px solid {$primaryBackgroundColor};
{rdelim}

.tab-content{ldelim}
	border-left: 1px solid {$primaryBackgroundColor};
	border-right: 1px solid {$primaryBackgroundColor};
{rdelim}

{* Syndetics Unbound *}
.unbound_mega_header, .unbound_header, .unbound_subhead, .unbound_author_name, .unbound_reviews_reviewdby, .unbound_review_by_text, .unbound_reviews_source, .unbound_infotext{ldelim}
	color: {$bodyTextColor} !important;
{rdelim}

.unbound_tagblocktable .level2, .unbound_tagblocktable .level1, .unbound_tagblocktable td {ldelim}
	background-color: {$tertiaryBackgroundColor} !important;
    border-color: {$tertiaryBackgroundColor} !important;
    color: {$tertiaryForegroundColor} !important;
{rdelim}

{* Misc *}
.well{ldelim}
	background-color: {$primaryBackgroundColor};
	border: 1px solid {$secondaryBackgroundColor};
{rdelim}

.sidebar-label{ldelim}
	background-color: {$primaryBackgroundColor};
	color: {$primaryForegroundColor};
	margin-bottom: .75em;
{rdelim}

pre{ldelim}
	background-color: {$primaryBackgroundColor};
	color: {$primaryForegroundColor};
{rdelim}

.formatCategoryLabel, .share-tools-label{ldelim}
color: {$bodyTextColor};
{rdelim}

{* Accessiblity *}
{if $themeIsHighContrast}
	body{ldelim}
		filter: contrast(1.50);
	{rdelim}

	*{ldelim}
        font-size: 10pt;
        font-size: max(12pt, min(10pt, 22pt));
        font-size: clamp(10pt, 12pt, 22pt);
    {rdelim}

	a{ldelim}
		text-decoration: underline !important;
		cursor: pointer;
	{rdelim}

	.modal{ldelim}
		filter: invert(1);
	{rdelim}

	#more-details-accordion .panel-body, .itemSummary{ldelim}
        font-size: 85%;
    {rdelim}
{/if}

{$additionalCSS}
</style>
{/strip}