{strip}
	{if !empty($showBrowseContent)}
	<h1 class="hiddenTitle">{translate text='Browse the Catalog' isPublicFacing=true}</h1>
	<div id="home-page-browse-header" class="row">
	{if $accessibleBrowseCategories == '1'}
		<div class="col-sm-12" id="browse-category-feed" role="feed">
			<!-- Slider main container -->
			{foreach from=$browseCategories item=browseCategory name="browseCategoryLoop"}

			<div class="browse-category-feed-item" id="browse-category-{$browseCategory.textId}" role="article">
				<div class="browse-category-group-heading">
					<a id="browse-search-link-{$browseCategory.textId}" href="{if $browseCategory.searchUrl}{$browseCategory.searchUrl}{/if}" title="View all results for {translate text=$browseCategory.label isPublicFacing=true}"> <h2 id="tablist-browse-category-{$browseCategory.textId}"><i class="fas fa-search" role="presentation"></i> {translate text=$browseCategory.label isPublicFacing=true}</h2></a>
					{if !empty($isLoggedIn)}
						<button id="browse-dismiss-{$browseCategory.textId}" class="btn btn-default browse-dismiss" onclick="AspenDiscovery.Account.dismissBrowseCategory(null, '{$browseCategory.textId}')" title="{translate text='Hide Category %1%' 1={$browseCategory.label} inAttribute=true isPublicFacing=true}">
							<i class="fas fa-times" role="presentation"></i> {translate text='Hide' isPublicFacing=true}
						</button>
					{/if}
				</div>
				{if $browseCategory.hasSubcategories}
					<div class="tabs" id="tabs-{$browseCategory.textId}">
						<div class="tabs-loading"
							 style="height:200px;display:flex;align-items:center;justify-content:center;">
							<i class="fas fa-lg fa-spinner fa-spin"></i>
						</div>
					</div>
				{else}
					<div class="swiper swiper-first swiper-browse-category-{$browseCategory.textId}" id="swiper-{$browseCategory.textId}">
						<div class="swiper-navigation-container">
							<div class="swiper-button-prev"></div>
						</div>
						<div class="swiper-wrapper" id="swiper-browse-category-{$browseCategory.textId}">
							<div class="swiper-slide" id="swiper-loading-{$browseCategory.textId}" style="height: 200px">
								<i class="fas fa-lg fa-spinner fa-spin"></i>
							</div>
							<script type="text/javascript">
								$(function() {ldelim}
									setTimeout(function() {ldelim}
										AspenDiscovery.Browse.initializeBrowseCategorySwiper("{$browseCategory.textId}");
									{rdelim}, 1000);
								{rdelim})
							</script>
						</div>
						<div class="swiper-navigation-container">
							<div class="swiper-button-next"></div>
						</div>
					</div>
				{/if}
			</div>
			{/foreach}
		</div>

		{if !empty($isLoggedIn)}
			<div class="row text-center" style="margin-top: 2em">
				<div class="col-xs-12">
					<hr/>
					<a class="btn {if !empty($showBrowseContent)}btn-default{else}btn-primary{/if}"  href="#" role="button" title="{translate text='Show Hidden Browse Categories' inAttribute=true isPublicFacing=true}" onclick="return AspenDiscovery.Account.showHiddenBrowseCategories('{$loggedInUser}')">
						<i class="fas fa-eye"></i> {translate text='Show Hidden Browse Categories' isPublicFacing=true}
					</a>
				</div>
			</div>
		{/if}

		{else}
		<div class="col-sm-12">
			<div class="row text-center" id="browse-category-picker">
				<div class="jcarousel-wrapper">
					<div class="jcarousel" id="browse-category-carousel">
						<ul>
							{foreach from=$browseCategories item=browseCategory name="browseCategoryLoop"}
								<li tabindex="0" id="browse-category-{$browseCategory->textId}" class="browse-category {if (empty($selectedBrowseCategory) && $smarty.foreach.browseCategoryLoop.index == 0) || (!empty($selectedBrowseCategory) && $selectedBrowseCategory->textId == $browseCategory->textId)} selected{/if}" data-category-id="{$browseCategory->textId}">
									<div role="button">
										{translate text=$browseCategory->label isPublicFacing=true}
									</div>
								</li>
							{/foreach}
						</ul>
					</div>

					<a role="button" href="#" class="jcarousel-control-prev" aria-label="{translate text="Previous Category" inAttribute=true isPublicFacing=true}"></a>
					<a role="button" href="#" class="jcarousel-control-next" aria-label="{translate text="Next Category" inAttribute=true isPublicFacing=true}"></a>

					<p class="jcarousel-pagination hidden-xs"></p>
				</div>
				<div class="clearfix"></div>
			</div>
			<div id="browse-sub-category-menu" class="row text-center">
				{* Initial load of content done by AJAX call on page load, unless sub-category is specified via URL *}
				{if !empty($subCategoryTextId)}
					{include file="Search/browse-sub-category-menu.tpl"}
				{/if}
			</div>
		</div>
		{/if}
	</div>
	{/if}
	{if $accessibleBrowseCategories == '0'}
	<div id="home-page-browse-content" class="row">
		<div class="col-sm-12">

			{if !empty($showBrowseContent)}
			<div class="row" id="selected-browse-label">
					<div class="btn-toolbar pull-right" style="padding: 0 8px; margin-right: 20px">
						<div class="btn-group btn-group-sm" data-toggle="buttons">
							<button type="button" class="btn btn-sm btn-default" id="covers" onclick="AspenDiscovery.Browse.toggleBrowseMode(this.id)"><i class="fas fa-th" role="presentation"></i><span> {translate text='Covers' isPublicFacing=true}</span></button>
							<button type="button" class="btn btn-sm btn-default" id="grid" onclick="AspenDiscovery.Browse.toggleBrowseMode(this.id)"><i class="fas fa-th-list" role="presentation"></i> {translate text='Grid' isPublicFacing=true}</span></button>
						</div>
						{if !empty($isLoggedIn)}
						<div class="btn-group" data-toggle="buttons" style="margin-top: -.15em; margin-left: 1em;">
							<button class="btn btn-default selected-browse-dismiss" onclick="" title="{translate text='Hide Category %1%' 1={$browseCategory->label} inAttribute=true isPublicFacing=true}">
							<i class="fas fa-times" role="presentation"></i> {translate text='Hide' isPublicFacing=true}</button>
						</div>
						{/if}
					</div>


				<div class="selected-browse-label-search">
					<a id="selected-browse-search-link" title="See the search results page for this browse category">
						<span class="icon-before" role="presentation"></span> {*space needed for good padding between text and icon *}
						<span class="selected-browse-label-search-text" role="presentation"></span>
						<span class="selected-browse-sub-category-label-search-text" role="presentation"></span>
						<span class="icon-after" role="presentation"></span>
					</a>
				</div>
			</div>

			<div id="home-page-browse-results" class="{if empty($browseCategoryRatingsMode)}HideBorder{/if} home-page-browse-results-grid {if $browseStyle == 'grid'}home-page-browse-results-grid-grid{else}home-page-browse-results-grid-masonry{/if}">
				{if $browseStyle == 'masonry'}
					<div class="masonry grid">
						<!-- columns -->
						<div class="grid-col grid-col--1"></div>
						<div class="grid-col grid-col--2"></div>
						<div class="grid-col grid-col--3"></div>
						<div class="grid-col grid-col--4"></div>
						<div class="grid-col grid-col--5"></div>
						<div class="grid-col grid-col--6"></div>
					</div>
				{/if}
			</div>
			<div class="clearfix"></div>
			<a onclick="return AspenDiscovery.Browse.getMoreResults()" onkeypress="return AspenDiscovery.Browse.getMoreResults()" role="button" title="{translate text='Get More Results' inAttribute=true isPublicFacing=true}" tabindex="0">
				<div class="row" id="more-browse-results">
					<span tabindex="0" class="glyphicon glyphicon-chevron-down" aria-label="{translate text='Get More Results' inAttribute=true isPublicFacing=true}" role="button"></span>
				</div>
			</a>
			{else}
				{if $allBrowseCategoriesAreHidden}
					<div class="row text-center" style="padding-bottom:1em">
						<div class="col-md-6 col-md-offset-3">
							<p class="lead">{translate text='It looks like you\'ve hidden everything' isPublicFacing=true}</p>
							<p>{translate text='<strong>Start by making a search.</strong> Or, if you want to see curated content from the library again, update your hidden browse categories.' isPublicFacing=true}</p>
						</div>
					</div>
				{/if}
			{/if}

			{* add link to restore hidden browse categories if user has any hidden *}
			{if !empty($isLoggedIn) && $numHiddenCategory > 0}
			<div class="row text-center" {if !empty($showBrowseContent)}style="margin-top: 2em"{/if}>
				<div class="col-xs-12">
					<a  class="btn {if !empty($showBrowseContent)}btn-default{else}btn-primary{/if}"  href="#" role="button" title="{translate text='Show Hidden Browse Categories' inAttribute=true isPublicFacing=true}" onclick="return AspenDiscovery.Account.showHiddenBrowseCategories('{$loggedInUser}')">
						<i class="fas fa-eye"></i> {translate text='Show Hidden Browse Categories' isPublicFacing=true}
					</a>
				</div>
			</div>
			{/if}
		</div>
	</div>
	{/if}
{/strip}
<script type="text/javascript">
	$(function(){ldelim}
		{if !empty($selectedBrowseCategory)}
			AspenDiscovery.Browse.curCategory = '{$selectedBrowseCategory->textId}';
			{if !empty($subCategoryTextId)}AspenDiscovery.Browse.curSubCategory = '{$subCategoryTextId}';{/if}
		{/if}
		$('#'+AspenDiscovery.Browse.browseMode).removeClass('active');
		{if empty($onInternalIP)}
		if (!Globals.opac && AspenDiscovery.hasLocalStorage()){ldelim}
			var temp = window.localStorage.getItem('browseMode');
			if (AspenDiscovery.Browse.browseModeClasses.hasOwnProperty(temp)) AspenDiscovery.Browse.browseMode = temp; {* if stored value is empty or a bad value, fall back on default setting ("null" returned when not set) *}
			else AspenDiscovery.Browse.browseMode = '{$browseMode}';
		{rdelim}
		else AspenDiscovery.Browse.browseMode = '{$browseMode}';
		{else}
		AspenDiscovery.Browse.browseMode = '{$browseMode}';
		{/if}
		$('#'+AspenDiscovery.Browse.browseMode).addClass('active'); {* show user which one is selected *}

		AspenDiscovery.Browse.toggleBrowseMode();

		{if $accessibleBrowseCategories == '1'}
			AspenDiscovery.Browse.loadAllBrowseCategoryTabsSequential();
		{/if}
	{rdelim});
</script>
