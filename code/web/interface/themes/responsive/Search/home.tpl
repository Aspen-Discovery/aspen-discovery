{strip}
	<h1 class="hiddenTitle">{translate text='Browse the Catalog' isPublicFacing=true}</h1>
	<div id="home-page-browse-header" class="row">
		<div class="col-sm-12">
			<div class="row text-center" id="browse-category-picker">
				<div class="jcarousel-wrapper">
					<div class="jcarousel" id="browse-category-carousel">
						<ul>
							{foreach from=$browseCategories item=browseCategory name="browseCategoryLoop"}
								<li id="browse-category-{$browseCategory->textId}" class="browse-category {if (!$selectedBrowseCategory && $smarty.foreach.browseCategoryLoop.index == 0) || $selectedBrowseCategory && $selectedBrowseCategory->textId == $browseCategory->textId} selected{/if}" data-category-id="{$browseCategory->textId}">
									<div >
										{translate text=$browseCategory->label isPublicFacing=true}
									</div>
								</li>
							{/foreach}
						</ul>
					</div>

					<a href="#" class="jcarousel-control-prev" aria-label="{translate text="Previous Category" inAttribute=true isPublicFacing=true}"></a>
					<a href="#" class="jcarousel-control-next" aria-label="{translate text="Next Category" inAttribute=true isPublicFacing=true}"></a>

					<p class="jcarousel-pagination hidden-xs"></p>
				</div>
				<div class="clearfix"></div>
			</div>
			<div id="browse-sub-category-menu" class="row text-center">
				{* Initial load of content done by AJAX call on page load, unless sub-category is specified via URL *}
				{if $subCategoryTextId}
					{include file="Search/browse-sub-category-menu.tpl"}
				{/if}
			</div>
		</div>
	</div>
	<div id="home-page-browse-content" class="row">
		<div class="col-sm-12">

			<div class="row" id="selected-browse-label">

				<div class="btn-group btn-group-sm" data-toggle="buttons">
					<label for="covers" title="Covers" class="btn btn-sm btn-default"><input onchange="AspenDiscovery.Browse.toggleBrowseMode(this.id)" type="radio" id="covers">
						<span class="thumbnail-icon"></span><span> {translate text='Covers' isPublicFacing=true}</span>
					</label>
					<label for="grid" title="Grid" class="btn btn-sm btn-default"><input onchange="AspenDiscovery.Browse.toggleBrowseMode(this.id);" type="radio" id="grid">
						<span class="grid-icon"></span><span> {translate text='Grid' isPublicFacing=true}</span>
					</label>
				</div>

				<div class="selected-browse-label-search">
					<a id="selected-browse-search-link" title="See the search results page for this browse category">
						<span class="icon-before"></span> {*space needed for good padding between text and icon *}
						<span class="selected-browse-label-search-text"></span>
						<span class="selected-browse-sub-category-label-search-text"></span>
						<span class="icon-after"></span>
					</a>
				</div>
			</div>

			<div id="home-page-browse-results">
				<div class="grid">
					<!-- columns -->
					<div class="grid-col grid-col--1"></div>
					<div class="grid-col grid-col--2"></div>
					<div class="grid-col grid-col--3"></div>
					<div class="grid-col grid-col--4"></div>
					<div class="grid-col grid-col--5"></div>
					<div class="grid-col grid-col--6"></div>
				</div>
			</div>

			<a onclick="return AspenDiscovery.Browse.getMoreResults()" onkeypress="return AspenDiscovery.Browse.getMoreResults()" role="button" title="{translate text='Get More Results' inAttribute=true isPublicFacing=true}" tabindex="0">
				<div class="row" id="more-browse-results">
					<span class="glyphicon glyphicon-chevron-down" aria-label="{translate text='Get More Results' inAttribute=true isPublicFacing=true}"></span>
				</div>
			</a>
		</div>
	</div>
{/strip}
<script type="text/javascript">
	$(function(){ldelim}
		{if $selectedBrowseCategory}
			AspenDiscovery.Browse.curCategory = '{$selectedBrowseCategory->textId}';
			{if $subCategoryTextId}AspenDiscovery.Browse.curSubCategory = '{$subCategoryTextId}';{/if}
		{/if}
		{if !$onInternalIP}
		if (!Globals.opac && AspenDiscovery.hasLocalStorage()){ldelim}
			var temp = window.localStorage.getItem('browseMode');
			if (AspenDiscovery.Browse.browseModeClasses.hasOwnProperty(temp)) AspenDiscovery.Browse.browseMode = temp; {* if stored value is empty or a bad value, fall back on default setting ("null" returned when not set) *}
			else AspenDiscovery.Browse.browseMode = '{$browseMode}';
		{rdelim}
		else AspenDiscovery.Browse.browseMode = '{$browseMode}';
		{else}
		AspenDiscovery.Browse.browseMode = '{$browseMode}';
		{/if}
		$('#'+AspenDiscovery.Browse.browseMode).parent('label').addClass('active'); {* show user which one is selected *}
		AspenDiscovery.Browse.toggleBrowseMode();
	{rdelim});
</script>
