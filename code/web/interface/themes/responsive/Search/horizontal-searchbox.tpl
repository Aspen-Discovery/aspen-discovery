{strip}
<div id="horizontal-search-box" class="row {if !empty($fullWidthTheme)}row-no-gutters{/if}">
	<form method="get" action="/Union/Search" id="searchForm" class="form-inline">

		{* Hidden Inputs *}
		<input type="hidden" name="view" id="view" value="{if !empty($displayMode)}{$displayMode}{/if}">

		{if isset($showCovers)}
			<input type="hidden" name="showCovers" value="{if !empty($showCovers)}on{else}off{/if}">
		{/if}

		{assign var="hiddenSearchSource" value=false}
		{* Switch sizing when no search source is to be displayed *}
		{if empty($searchSources) || count($searchSources) == 1}
			{assign var="hiddenSearchSource" value=true}
			<input type="hidden" name="searchSource" value="{if !empty($searchSource)}{$searchSource}{/if}">
		{/if}

		<div class="col-xs-12 col-sm-10 col-md-10 col-lg-10">
			<div class="row">
				<div class="{if !empty($hiddenSearchSource)}col-lg-10 col-md-10{else}col-lg-7 col-md-7{/if} col-sm-12 col-xs-12">
					<div class="input-group">
						<span class="input-group-addon"><label for="lookfor" class="label" id="lookfor-label"><i class="fas fa-search fa-lg" role="presentation"></i><span class="sr-only" aria-label="{translate text="Look for" isPublicFacing=true inAttribute=true}" role="presentation">{translate text="Look for" isPublicFacing=true}</span></label></span>
						{* Main Search Term Box *}
						<input type="text" class="form-control"{/strip}
							id="lookfor"
							name="lookfor"
							title="{translate text="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." isPublicFacing=true inAttribute=true}"
							onfocus="$(this).select()"
							autocomplete="off"
							aria-labelledby="lookfor-label"
							aria-required="true"
							{if !empty($lookfor)}value="{$lookfor|escape:"html"}"{/if}
						{strip}>
						<span class="input-group-addon clear-search" onclick="AspenDiscovery.resetSearchBox();" title="{translate text="Clear search" isPublicFacing=true inAttribute=true}" aria-label="{translate text="Clear search" isPublicFacing=true}" style="display:none;cursor:pointer;">
							<svg focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
							</svg>
						</span>
					</div>
				</div>

				{* Search Type *}
				<div class="col-lg-2 col-lg-offset-0 col-md-2 col-md-offset-0 {if !empty($hiddenSearchSource)} col-sm-12 col-sm-offset-0 col-xs-12 col-xs-offset-0 {else} col-sm-6 col-sm-offset-0 col-xs-6 col-xs-offset-0{/if}">
					<select name="searchIndex" class="searchTypeHorizontal form-control catalogType" id="searchIndex" title="The method of searching." aria-label="Search Index">
						<script type="text/javascript">
							{literal}
							$(document).ready(function() {
								AspenDiscovery.Searches.loadSearchTypes();
							});
							{/literal}
						</script>
						{foreach from=$searchIndexes item=searchDesc key=searchVal}
							<option value="{$searchVal}"{if !empty($searchIndex) && $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc inAttribute=true isPublicFacing=true}</option>
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

				{if empty($hiddenSearchSource)}
					<div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
						<select name="searchSource" id="searchSource" title="{translate text="Select what to search. Items marked with a * will redirect you to one of our partner sites." isPublicFacing=true inAttribute=true}" onchange="AspenDiscovery.Searches.loadSearchTypes();" class="searchSourceHorizontal form-control" aria-label="{translate text="Collection to Search" isPublicFacing=true inAttribute=true}">
							{foreach from=$searchSources item=searchOption key=searchKey}
								<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}" title="{$searchOption.description|escape}" data-advanced_search="{$searchOption.hasAdvancedSearch}" data-advanced_search_label="{translate text="Advanced Search" inAttribute=true isPublicFacing=true}"
										{if $searchKey == $searchSource} selected="selected"{/if}
										{if $searchKey == $defaultSearchIndex} id="default_search_type"{/if}
										>
									{translate text="in %1%" 1=$searchOption.name|escape inAttribute=true isPublicFacing=true translateParameters=true}{if !empty($searchOption.external)} *{/if}
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
						<i class="fas fa-search fas-lg" role="presentation"></i>
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
