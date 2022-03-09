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

		<div class="col-xs-12 col-sm-10 col-md-10 col-lg-10">
			<div class="row">
				<div class="{if $hiddenSearchSource}col-lg-10 col-md-10{else}col-lg-7 col-md-7{/if} col-sm-12 col-xs-12">
					{* <div class="input-group"> *}
					<label for="lookfor" class="label" id="lookfor-label"><i class="fas fa-search fa-2x" style="vertical-align: middle"></i><span class="sr-only">{translate text="Look for" isPublicFacing=true}</span></label>
					{* Main Search Term Box *}
					<input type="text" class="form-control" style="border-right:0"{/strip}
						id="lookfor"
						name="lookfor"
						title="{translate text="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." isPublicFacing=true inAttribute=true}"
						onfocus="$(this).select()"
						autocomplete="off"
						aria-labelledby="lookfor-label"

						{if !empty($lookfor)}value="{$lookfor|escape:"html"}"{/if}
					{strip}>
						{*<span class="input-group-btn">
							<button class="btn btn-default" type="button" onclick="return AspenDiscovery.resetSearchBox();"><i class="fas fa-times"></i></button>
						</span>*}
					{*</div>*}
				</div>

				{* Search Type *}
				<div class="col-lg-2 col-lg-offset-0 col-md-2 col-md-offset-0 {if $hiddenSearchSource} col-sm-12 col-sm-offset-0 col-xs-12 col-xs-offset-0 {else} col-sm-6 col-sm-offset-0 col-xs-6 col-xs-offset-0{/if}">
					<select name="searchIndex" class="searchTypeHorizontal form-control catalogType" id="searchIndex" title="The method of searching." aria-label="Search Index">
						{foreach from=$searchIndexes item=searchDesc key=searchVal}
							<option value="{$searchVal}"{if !empty($searchIndex) && $searchIndex == $searchVal} selected="selected"{/if}>{translate text="by %1%" 1=$searchDesc inAttribute=true isPublicFacing=true translateParameters=true}</option>
						{/foreach}

						{* Add Advanced Search *}
						{if !empty($searchIndex) && $searchIndex == 'advanced'}*}
							<option id="advancedSearchLink" value="editAdvanced" selected="selected">
								{translate text='Edit Advanced Search' inAttribute=true isPublicFacing=true}
							</option>
						{elseif $showAdvancedSearchbox}
							<option id="advancedSearchLink" value="advanced">
								{translate text='Advanced Search' inAttribute=true isPublicFacing=true}
							</option>
						{/if}
					</select>
				</div>

				{if !$hiddenSearchSource}
					<div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
						<select name="searchSource" id="searchSource" title="{translate text="Select what to search. Items marked with a * will redirect you to one of our partner sites." isPublicFacing=true inAttribute=true}" onchange="AspenDiscovery.Searches.loadSearchTypes();" class="searchSourceHorizontal form-control" aria-label="{translate text="Collection to Search" isPublicFacing=true inAttribute=true}">
							{foreach from=$searchSources item=searchOption key=searchKey}
								<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}" title="{$searchOption.description}" data-advanced_search="{$searchOption.hasAdvancedSearch}" data-advanced_search_label="{translate text="Advanced Search" inAttribute=true isPublicFacing=true}"
										{if $searchKey == $searchSource} selected="selected"{/if}
										{if $searchKey == $defaultSearchIndex} id="default_search_type"{/if}
										>
									{translate text="in %1%" 1=$searchOption.name inAttribute=true isPublicFacing=true translateParameters=true}{if !empty($searchOption.external)} *{/if}
								</option>
							{/foreach}
						</select>
					</div>
				{/if}
			</div>
		</div>

		{* GO Button & Search Links*}
		<div id="horizontal-search-button-container" class="col-xs-12 col-sm-2 col-md-2">
			<div class="row">
				<div class="col-tn-6 col-xs-6 col-sm-12 col-md-12 text-center">
					<button class="btn btn-default" type="submit" style="width: 95%">
						<i class="fas fa-search fas-lg"></i>
						<span id="horizontal-search-box-submit-text">&nbsp;{translate text='Search' isPublicFacing=true}</span>
					</button>
				</div>

				{* Show/Hide Search Facets & Sort Options *}
				{if !empty($recordCount) || !empty($sideRecommendations)}
					<div class="col-tn-6 col-xs-6 visible-xs text-center">
						<a class="btn btn-default" id="refineSearchButton" style="width: 95%" role="button" onclick="$('#side-bar').slideToggle('slow');return false;"><i class="fas fa-filter"></i> {translate text='Filters' isPublicFacing=true}</a>
					</div>
				{/if}
			</div>
		</div>

	</form>
</div>
{/strip}