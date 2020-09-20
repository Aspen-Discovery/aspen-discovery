{strip}
<div id="horizontal-search-box" class="row">
	<form method="get" action="/Union/Search" id="searchForm" class="form-inline">

		{* Hidden Inputs *}
		<input type="hidden" name="view" id="view" value="{$displayMode}">

		{if isset($showCovers)}
			<input type="hidden" name="showCovers" value="{if $showCovers}on{else}off{/if}">
		{/if}

		{assign var="hiddenSearchSource" value=false}
		{* Switch sizing when no search source is to be displayed *}
		{if empty($searchSources) || count($searchSources) == 1}
			{assign var="hiddenSearchSource" value=true}
			<input type="hidden" name="searchSource" value="{$searchSource}">
		{/if}

		<div class="col-sm-9 col-xs-12">
			<div class="row">
				<div class="col-lg-1 col-md-1 col-sm-2 col-xs-12 hidden-xs">
					<label id="horizontal-search-label" for="lookfor" class="">{translate text="Search"} </label>
				</div>
				<div class="{if $hiddenSearchSource}col-lg-9 col-md-9{else}col-lg-6 col-md-6{/if} col-sm-10 col-xs-12">
					{* Main Search Term Box *}
					<textarea class="form-control"{/strip}
						id="lookfor"
						name="lookfor"
						title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."
						onfocus="$(this).select()"
						autocomplete="off"
						rows="1"
						aria-labelledby="horizontal-search-label"
						{strip}>
						{if !empty($lookfor)}{$lookfor|escape:"html"}{/if}
					</textarea>
				</div>

				{* Search Type *}
				<div class="col-lg-2 col-lg-offset-0 col-md-2 col-md-offset-0 {if $hiddenSearchSource} col-sm-10 col-sm-offset-2 col-xs-12 col-xs-offset-0 {else} col-sm-5 col-sm-offset-2 col-xs-5 col-xs-offset-0{/if}">
					<select name="searchIndex" class="searchTypeHorizontal form-control catalogType" id="searchIndex" title="The method of searching." aria-label="Search Index">
						{foreach from=$searchIndexes item=searchDesc key=searchVal}
							<option value="{$searchVal}"{if !empty($searchIndex) && $searchIndex == $searchVal} selected="selected"{/if}>{translate text="by"} {translate text=$searchDesc}</option>
						{/foreach}
					</select>
				</div>

				{if !$hiddenSearchSource}
					<div class="col-lg-3 col-md-3 col-sm-5 col-xs-7">
						<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange="AspenDiscovery.Searches.loadSearchTypes();" class="searchSourceHorizontal form-control" aria-label="Collection to Search">
							{foreach from=$searchSources item=searchOption key=searchKey}
								<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}" title="{$searchOption.description}" data-advanced_search="{$searchOption.hasAdvancedSearch}"
										{if $searchKey == $searchSource} selected="selected"{/if}
										{if $searchKey == $defaultSearchIndex} id="default_search_type"{/if}
										>
									{translate text="in"} {$searchOption.name}{if !empty($searchOption.external)} *{/if}
								</option>
							{/foreach}
						</select>
					</div>
				{/if}
			</div>
		</div>

		{* GO Button & Search Links*}
		<div id="horizontal-search-button-container" class="col-sm-3 col-xs-12">
			<div class="row">
				<div class="col-tn-3 col-xs-3 col-sm-4 col-md-4">
					<button class="btn btn-default" type="submit">
						<i class="fas fa-search fas-lg"></i>
						<span id="horizontal-search-box-submit-text">&nbsp;{translate text='GO'}</span>
					</button>
				</div>

				<div id="horizontal-search-additional" class="col-tn-5 col-xs-5 col-sm-12 col-md-8">
					{* Return to Advanced Search Link *}
					{if !empty($searchType) && $searchType == 'advanced'}
						<a id="advancedSearchLink" href="/Search/Advanced" class="btn btn-default" {if !$searchSources.$searchSource.hasAdvancedSearch}style="display: none"{/if}>
							{translate text='Edit Advanced Search'}
						</a>

					{* Show Advanced Search Link *}
					{elseif $showAdvancedSearchbox}
						<a id="advancedSearchLink" href="/Search/Advanced"  class="btn btn-default" {if !$searchSources.$searchSource.hasAdvancedSearch}style="display: none"{/if}>
							{translate text='Advanced Search'}
						</a>
					{/if}
				</div>

				{* Show/Hide Search Facets & Sort Options *}
				{if !empty($recordCount) || !empty($sideRecommendations)}
					<div class="col-tn-3 col-xs-3 visible-xs text-right">
						<a class="btn btn-default" id="refineSearchButton" role="button" onclick="$('#side-bar').slideToggle('slow');return false;"><i class="fas fa-filter"></i> {translate text='Filters'}</a>
					</div>
				{/if}
			</div>
		</div>

	</form>
</div>
{/strip}