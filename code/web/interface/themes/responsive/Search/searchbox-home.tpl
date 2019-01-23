{strip}
<div id="home-page-search" class="row"{if $displaySidebarMenu} style="display: none"{/if}>
	<div class="col-xs-12">
		<div class="row">
			<div class="hidden-xs-inline col-md-12 text-center" id="home-page-search-label">
				SEARCH <span class="glyphicon glyphicon-search"></span>
			</div>
		</div>
		<form method="get" action="{$path}/Union/Search" id="searchForm" class="form-inline" onsubmit="VuFind.Searches.processSearchForm();">
			<div class="row">
				<div class="{if $displaySidebarMenu}col-sm-12{else}col-sm-10 col-md-10 col-sm-push-1 col-md-push-1{/if}">
					{if $searchIndex == 'Keyword' || $searchIndex == '' || $searchIndex == 'GenealogyKeyword'}
						<input type="hidden" name="basicType" id="basicType" value="">
						<input type="hidden" name="genealogyType" id="genealogyType" value="">
					{/if}
					<input type="hidden" name="view" id="view" value="{$displayMode}">

					{if isset($showCovers)}
					<input type="hidden" name="showCovers"{* id="showCovers"*} value="{if $showCovers}on{else}off{/if}">
					{/if}

					<fieldset>
						<div class="input-group input-group-sm">
							<div class="input-group-sm">
							<textarea class="form-control"{/strip}
							       id="lookfor"
							       placeholder="&#128269; SEARCH" {*experimental for anythink. disabled in css by default, as of now. plb 11-19-2014 *}
							       type="search"
							       name="lookfor"
							       size="30"
							       value=""
							       title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term."
							       onkeyup="return VuFind.Searches.resetSearchType()"
							       onfocus="$(this).select()"
							       autocomplete="off"
							       rows="1"
											{strip}>
								{$lookfor|escape:"html"}
								</textarea>
							</div>
							<div class="input-group-btn" id="search-actions">
								<button class="btn btn-default" type="submit">GO</button>
								<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
									<span class="caret"></span>
								</button>

								<ul id="searchType" class="dropdown-menu text-left">
									{if $searchIndex == 'Keyword' || $searchIndex == '' || $searchIndex == 'GenealogyKeyword'}
										{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
											<li>
												<a class="catalogType" href="#" onclick="return VuFind.Searches.updateSearchTypes('catalog', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
											</li>
										{/foreach}
										<li class="divider catalogType"></li>
										{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
											<li>
												<a class="genealogyType" href="#" onclick="return VuFind.Searches.updateSearchTypes('genealogy', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
											</li>
										{/foreach}
										<li class="divider genealogyType"></li>
										{foreach from=$islandoraSearchTypes item=searchDesc key=searchVal}
											<li>
												<a class="islandoraType" href="#" onclick="return VuFind.Searches.updateSearchTypes('islandora', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
											</li>
										{/foreach}
										<li class="divider islandoraType"></li>
										{foreach from=$ebscoSearchTypes item=searchDesc key=searchVal}
											<li>
												<a class="ebscoType" href="#" onclick="return VuFind.Searches.updateSearchTypes('ebsco', '{$searchVal}', '#searchForm');">{translate text="by"} {translate text=$searchDesc}</a>
											</li>
										{/foreach}
										<li class="divider ebscoType"></li>
									{/if}

									<li class="catalogType">
										{*<a id="advancedSearch" title="{translate text='Advanced Search'}" onclick="VuFind.Account.ajaxLightbox('{$path}/Search/AdvancedPopup', false)">*}
										<a id="advancedSearch" title="{translate text='Advanced Search'}" href="{$path}/Search/Advanced">
											{*<i class="icon-plus-sign"></i>*} {translate text="Advanced"}
										</a>
									</li>

									{* Link to Search Tips Help *}
									<li>
										<a href="{$path}/Help/Home?topic=search" title="{translate text='Search Tips'}" id="searchTips" class="modalDialogTrigger">
											{*<i class="icon-question-sign"></i>*} {translate text='Search Tips'}
										</a>
									</li>
								</ul>
							</div>
						</div>

					</fieldset>
				</div>
			</div>

			{if $searchIndex != 'Keyword' && $searchIndex != '' && $searchIndex != 'GenealogyKeyword'}
				<div class="row text-center">
					<div class="col-sm-10 col-md-10 col-sm-push-1 col-md-push-1">
						<select name="basicType" class="searchTypeHome form-control catalogType" id="basicSearchTypes" title="Search by Keyword to find subjects, titles, authors, etc. Search by Title or Author for more precise results." {if $searchSource == 'genealogy' || $searchSource == 'islandora' || $searchSource == 'ebsco'}style="display:none"{/if}>
							{foreach from=$basicSearchTypes item=searchDesc key=searchVal}
								<option value="{$searchVal}"{if $basicSearchIndex == $searchVal || $searchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
							{/foreach}
						</select>
						<select name="genealogyType" class="searchTypeHome form-control genealogyType" id="genealogySearchTypes" {if $searchSource != 'genealogy'}style="display:none"{/if}>
							{foreach from=$genealogySearchTypes item=searchDesc key=searchVal}
								<option value="{$searchVal}"{if $genealogySearchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
							{/foreach}
						</select>
						<select name="islandoraType" class="searchTypeHome form-control islandoraType" id="islandoraSearchTypes" {if $searchSource != 'islandora'}style="display:none"{/if}>
							{foreach from=$islandoraSearchTypes item=searchDesc key=searchVal}
								<option value="{$searchVal}"{if $islandoraSearchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
							{/foreach}
						</select>
						<select name="ebscoType" class="searchTypeHome form-control ebscoType" id="ebscoSearchTypes" {if $searchSource != 'ebsco'}style="display:none"{/if}>
							{foreach from=$ebscoSearchTypes item=searchDesc key=searchVal}
								<option value="{$searchVal}"{if $ebscoSearchIndex == $searchVal} selected="selected"{/if}>{translate text=$searchDesc}</option>
							{/foreach}
						</select>
					</div>
				</div>
			{/if}
			<div class="row text-center">
				<div class="col-sm-10 col-md-10 col-sm-push-1 col-md-push-1">
					{if $searchSources|@count == 1}
						<input type="hidden" name="searchSource" value="{$searchSource}">
					{else}
					<select name="searchSource" id="searchSource" title="Select what to search.	Items marked with a * will redirect you to one of our partner sites." onchange="VuFind.Searches.enableSearchTypes();" class="searchSource form-control">
						{foreach from=$searchSources item=searchOption key=searchKey}
							<option data-catalog_type="{$searchOption.catalogType}" value="{$searchKey}"
								{if $searchKey == $searchSource && !$filterList} selected="selected"{/if}
								{if $searchKey == $searchSource} id="default_search_type"{/if}
								{*1space needed for clean markup ->*} title="{$searchOption.description}">
								{translate text="in"} {$searchOption.name}{if $searchOption.external} *{/if}
							</option>
						{/foreach}
					</select>
					{/if}
				</div>
			</div>

			<div class="row">

				{if $showAdvancedSearchbox || $searchType == 'advanced' || $filterList}
					<div class="{if $recordCount || $sideRecommendations}col-tn-6 {/if}col-sm-12 text-center">
						{* In Col-sm and above, display each of these as it's own row; In col-xs & below display all items within a single row *}

						{* Keep Filters Switch *}
						{if $filterList}
							<div class="row">
								<label for="keepFiltersSwitch" id="keepFiltersSwitchLabel"><input id="keepFiltersSwitch" type="checkbox" onclick="VuFind.Searches.filterAll(this);"> Keep Applied Filters</label>
							</div>
						{/if}

						{* Advanced Search Links *}
						{if $searchType == 'advanced'}
							<a id="advancedSearchLink" href="{$path}/Search/Advanced">
								{translate text='Edit This Advanced Search'}
							</a>
						{elseif $showAdvancedSearchbox}
							<a id="advancedSearchLink" href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a>
						{/if}

					</div>
				{/if}

				{* Show/Hide Search Facets & Sort Options *}
				{if $recordCount || $sideRecommendations}
					<div class="{if $showAdvancedSearchbox || $searchType == 'advanced'}col-tn-6{else}col-tn-12{/if} text-center visible-xs">
						<a class="btn btn-default" id="refineSearchButton" role="button" onclick="VuFind.Menu.Mobile.showSearchFacets()">{translate text="Refine Search"}</a>
					</div>
				{/if}

			</div>

{* Original *}<!--
			<div class="row text-center">
				{if $filterList}
					<label for="keepFiltersSwitch" id="keepFiltersSwitchLabel"><input id="keepFiltersSwitch" type="checkbox" onclick="VuFind.Searches.filterAll(this);"> Keep Applied Filters</label>
				{/if}
			</div>
			{* Return to Advanced Search Link *}
			{if $searchType == 'advanced'}
				<div class="row text-center">
					<a id="advancedSearchLink" href="{$path}/Search/Advanced">{translate text='Edit This Advanced Search'}</a>
				</div>
			{elseif $showAdvancedSearchbox}
				<div class="row text-center">
					<a id="advancedSearchLink" href="{$path}/Search/Advanced">{translate text='Advanced Search'}</a>
				</div>
			{/if}

			{* Show/Hide Search Facets & Sort Options *}
			{if $recordCount || $sideRecommendations}
				<div class="row text-center visible-xs">
					<a class="btn btn-default" id="refineSearchButton" role="button" onclick="VuFind.Menu.Mobile.showSearchFacets()">{translate text="Refine Search"}</a>
				</div>
			{/if}-->

			{if $filterList}
				{* Data for searching within existing results *}
				<div id="keepFilters" style="display:none;">
					{foreach from=$filterList item=data key=field}
						{foreach from=$data item=value}
							<input class="existingFilter" type="checkbox" name="filter[]" value='{$value.field}:"{$value.value|escape}"'>
						{/foreach}
					{/foreach}
				</div>
			{/if}
		</form>
	</div>
</div>
{/strip}