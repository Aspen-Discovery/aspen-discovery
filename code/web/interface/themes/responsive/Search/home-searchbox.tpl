{strip}
<div id="home-search-box" class="row row-no-gutters" style="padding: 2em; padding-top: 5em">
	<form method="get" action="/Union/Search" id="searchForm">

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
			<div class="row row-no-gutters" style="padding-bottom: 1em">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<div class="input-group">
						<span class="input-group-addon"><label for="lookfor" class="label" id="lookfor-label"><i class="fas fa-search fa-2x" style="vertical-align: middle"></i><span class="sr-only">{translate text="Look for" isPublicFacing=true}</span></label></span>

						{* Main Search Term Box *}
						<input type="text" class="form-control input-lg"{/strip}

							name="lookfor"
							title="{translate text="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." isPublicFacing=true inAttribute=true}"
							onfocus="$(this).select()"
							autocomplete="off"
							aria-labelledby="lookfor-label"

							{if !empty($lookfor)}value="{$lookfor|escape:"html"}"{/if}
						{strip}>
					</div>
				</div>
			</div>
			<div class="row row-no-gutters">
				{* Search Type *}
				<div class="col-lg-4 col-md-4 col-sm-6 col-xs-6 col-lg-offset-1 col-md-offset-1" style="padding-right: .5em">
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

				{if empty($hiddenSearchSource)}
					<div class="col-lg-4 col-md-3 col-sm-6 col-xs-6" style="padding-right: .5em">
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
				<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                    <button class="form-control btn btn-default" type="submit">
                        <i class="fas fa-search fas-lg"></i>
                        <span id="home-search-box-submit-text">&nbsp;{translate text='Search' isPublicFacing=true}</span>
                    </button>
                </div>
			</div>
		</div>

	</form>
</div>
{/strip}