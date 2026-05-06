{strip}
<div id="home-search-box" class="row row-no-gutters" style="padding: 2em; padding-top: 5em">
	<form method="get" action="/Union/Search" id="searchForm" {if $simplifiedSearchBox}onsubmit="AspenDiscovery.Searches.handleSimplifiedSearchSubmit(this);"{/if}>

		{* Hidden Inputs *}
		<input type="hidden" name="view" id="view" value="{$displayMode}">

		{if isset($showCovers)}
			<input type="hidden" name="showCovers" value="{if !empty($showCovers)}on{else}off{/if}">
		{/if}

		{assign var="hiddenSearchSource" value=false}
		{* Switch sizing when no search source is to be displayed *}
		{if empty($searchSources) || count($searchSources) == 1}
			{assign var="hiddenSearchSource" value=true}
			<input type="hidden" name="searchSource" value="{$searchSource}">
		{/if}

		<div class="col-lg-6 col-lg-offset-3 col-md-6 col-md-offset-3 col-sm-12 col-sm-offset-0 col-xs-12 col-xs-offset-0">
			<div class="row row-no-gutters"{if !$simplifiedSearchBox || empty($hiddenSearchSource)} style="padding-bottom: 1em"{/if}>
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="input-group">
						{if $simplifiedSearchBox}
							<label for="lookfor" class="sr-only" id="lookfor-label">{translate text="Look for" isPublicFacing=true}</label>
						{else}
							<span class="input-group-addon"><label for="lookfor" class="label" id="lookfor-label"><i class="fas fa-search fa-2x" style="vertical-align: middle" role="presentation"></i><span class="sr-only">{translate text="Look for" isPublicFacing=true}</span></label></span>
						{/if}

						{* Main Search Term Box *}
						<input type="text" class="form-control input-lg" id="lookfor"{/strip}

							name="lookfor"
							title="{translate text="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." isPublicFacing=true inAttribute=true}"
							onfocus="$(this).trigger('select')"
							autocomplete="off"
							aria-labelledby="lookfor-label"
							aria-required="true"
							{if !empty($lookfor)}value="{$lookfor|escape:"html"}"{/if}
						{strip}>
						<button type="button" class="input-group-addon clear-search" onclick="AspenDiscovery.resetSearchBox();" title="{translate text="Clear search" isPublicFacing=true inAttribute=true}">
							<span class="sr-only">{translate text="Clear search" isPublicFacing=true}</span>
							<svg focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
							</svg>
						</button>
						{if $simplifiedSearchBox}
							<span class="input-group-btn">
								<button class="btn btn-default" type="submit">
									<i class="fas fa-search fas-lg" role="presentation"></i>
									<span id="home-search-box-submit-text">&nbsp;{translate text='Search' isPublicFacing=true}</span>
								</button>
							</span>
						{/if}
					</div>
				</div>
			</div>
			<div class="row row-no-gutters">
				{* Search Type *}
				{if $simplifiedSearchBox}
					<input type="hidden" name="searchIndex" value="Keyword">
				{else}
					<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6 col-lg-offset-1 col-md-offset-1" style="padding-right: .5em">
						<select name="searchIndex" class="searchTypeHorizontal form-control catalogType" id="searchIndex" title="The method of searching." aria-label="Search Index">
							<script type="text/javascript">
								{literal}
								$(document).ready(function() {
									AspenDiscovery.Searches.loadSearchTypes();
								});
								{/literal}
							</script>
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
				{/if}

				{if empty($hiddenSearchSource)}
					<div class="col-lg-4 col-md-3 col-sm-6 col-xs-6" style="padding-right: .5em">
						<select name="searchSource" id="searchSource" title="{translate text="Select what to search. Items marked with a * will redirect you to one of our partner sites." isPublicFacing=true inAttribute=true}" onchange="{if $simplifiedSearchBox}AspenDiscovery.Searches.handleSimplifiedSourceChange(this);{else}AspenDiscovery.Searches.loadSearchTypes();{/if}" class="searchSourceHorizontal form-control" aria-label="{translate text="Collection to Search" isPublicFacing=true inAttribute=true}">
							{foreach from=$searchSources item=searchOption key=searchKey}
								<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}" title="{$searchOption.description|escape}" data-advanced_search="{$searchOption.hasAdvancedSearch}" data-advanced_search_label="{translate text="Advanced Search" inAttribute=true isPublicFacing=true}"
										{if $searchKey == $searchSource} selected="selected"{/if}
										{if $searchKey == $defaultSearchIndex} id="default_search_type"{/if}
										>
									{translate text="in %1%" 1=$searchOption.name|escape inAttribute=true isPublicFacing=true translateParameters=true}{if !empty($searchOption.external)} *{/if}
								</option>
							{/foreach}
							{if $simplifiedSearchBox && $showAdvancedSearchbox}
								<option id="advancedSearchLink" value="advanced">
									{translate text='Advanced Search' inAttribute=true isPublicFacing=true}
								</option>
							{/if}
						</select>
					</div>
				{/if}
				{if !$simplifiedSearchBox}
					<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
						<button class="form-control btn btn-default" type="submit">
							<i class="fas fa-search fas-lg" role="presentation"></i>
							<span id="home-search-box-submit-text">&nbsp;{translate text='Search' isPublicFacing=true}</span>
						</button>
					</div>
				{/if}
			</div>
		</div>

	</form>
</div>
{/strip}
