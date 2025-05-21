<h2 aria-label="Catalog Search Results" style="font-size:0;margin:0;line-height:0;"><span class="hidden">{translate text='Catalog Search Results' isPublicFacing=true}</h2>
<div id="searchInfo">
	{* Recommendations *}
	{if !empty($topRecommendations)}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">
		{if !empty($replacementTerm)}
			<div id="replacement-search-info-block" class="alert alert-info" role="alert">
				<p>{translate text="No results were found for \"%1%\". Showing results for \"%2%\" instead." isPublicFacing=true 1=$oldTerm 2=$replacementTerm}</p>
				<p><a href="{$oldSearchUrl}">{translate text="Search for \"%1%\" instead." isPublicFacing=true 1=$oldTerm}</a></p>
			</div>
		{/if}

		{if !empty($replacedIndex)}
			<div id="replacement-search-info-block">
				<div id="replacement-search-info"><span class="replacement-search-info-text">{translate text="Showing Results using Keyword index" isPublicFacing=true}</span></div>
				<div id="original-search-info"><span class="replacement-search-info-text"><a href='{$oldSearchUrl}'>{translate text="Search instead using %1% index" 1=$replacedIndexLabel isPublicFacing=true}</a></span></div>
			</div>
		{/if}

		{if !empty($replacedScope)}
			<div id="replacement-search-info-block">
				<div id="replacement-search-info"><span class="replacement-search-info-text">{translate text="Showing Results for %1%" 1=$globalScopeLabel isPublicFacing=true}</span> {$replacedScope}</div>
				<div id="original-search-info"><span class="replacement-search-info-text"><a href='{$oldSearchUrl}'>{translate text="Search %1% instead" 1=$replacedScopeLabel isPublicFacing=true}</a></span></div>
			</div>
		{/if}

		{if !empty($solrSearchDebug)}
			<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options" isAdminFacing=true}</div>
			<div id="solrSearchOptions" style="display:none">
				<pre>{translate text="Search options" isPublicFacing=true} {$solrSearchDebug}</pre>
			</div>
		{/if}

		{if !empty($solrLinkDebug)}
			<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link" isAdminFacing=true}</div>
			<div id='solrLink' style='display:none'>
				<pre>{$solrLinkDebug}</pre>
			</div>
		{/if}

		{if !empty($debugTiming)}
			<div id='solrTimingToggle' onclick='$("#solrTiming").toggle()'>{translate text="Show Solr Timing" isAdminFacing=true}</div>
			<div id='solrTiming' style='display:none'>
				<pre>{$debugTiming}</pre>
			</div>
		{/if}

		{* User's viewing mode toggle switch *}
		{if !empty($showSearchToolsAtTop)}
			{include file="Search/search-toolbar.tpl"}
		{else}
			{include file="Search/results-displayMode-toggle.tpl"}
		{/if}

		<div class="clearer"></div>
	</div>
	{* End Listing Options *}
	{if !empty($placard)}
		{include file="Search/placard.tpl"}
	{/if}

	{if !empty($subpage)}
		{include file=$subpage}
	{else}
		{$pageContent}
	{/if}

	{if $displayMode == 'covers'}
		{if $recordEnd < $recordCount}
			<a onclick="return AspenDiscovery.Searches.getMoreResults()" role="button" title="{translate text='Get More Results' inAttribute=true isPublicFacing=true}">
				<div class="row" id="more-browse-results">
					<span class="glyphicon glyphicon-chevron-down" aria-hidden="true" aria-label="{translate text='Get More Results' inAttribute=true isPublicFacing=true}" role="button"></span>
				</div>
			</a>
		{/if}
	{else}
		{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
	{/if}

	{include file="Search/searchSuggestions.tpl"}

	{include file="Search/spellingSuggestions.tpl"}

	{if !empty($showInnReachLink)}
		{* INN-Reach Results *}
		<div id='innReachSearchResultsPlaceholder'></div>
		{* javascript call for content at bottom of page*}
	{elseif !empty($showShareItLink)}
		{* SHAREit Results *}
		<div id='shareItSearchResultsPlaceholder'></div>
		{* javascript call for content at bottom of page*}
	{elseif !empty($interLibraryLoanName) && !empty($interLibraryLoanUrl)}
		{include file="Search/interLibraryLoanSearch.tpl"}
	{/if}

	{if !empty($showDplaLink)}
		{* DPLA Results *}
		<div id='dplaSearchResultsPlaceholder'></div>
	{/if}

	{if $displayMaterialsRequest && empty($offline)}
		{if $materialRequestType == 1}
			<div class="materialsRequestLink">
				<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
				<p>
					{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequest" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
				</p>
			</div>
		{elseif $materialRequestType == 2}
			<div class="materialsRequestLink">
				<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
				<p>
					{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequestIls" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
				</p>
			</div>
		{elseif $materialRequestType == 3}
			<div class="materialsRequestLink">
				<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
				<p>
					{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="{$externalMaterialsRequestUrl}" class="btn btn-sm btn-info">{translate text='Submit Request' isPublicFacing=true}</a>
				</p>
			</div>
		{/if}
	{/if}

	{if ($showSearchTools || ($loggedIn && count($userPermissions) > 0)) && !$showSearchToolsAtTop}
		<div class="search_tools well small">
			<strong>{translate text='Search Tools' isPublicFacing=true} </strong>
			{if !empty($showSearchTools)}
				<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a>
				{if empty($offline) || $enableEContentWhileOffline}
					<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a>
					{if !empty($enableSavedSearches)}
						{if !empty($savedSearch)}
							<a href="/MyAccount/SaveSearch?delete={$searchId}">{translate text="Remove Saved Search" isPublicFacing=true}</a>
						{else}
							<a href="#" onclick="return AspenDiscovery.Account.showSaveSearchForm('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a>
						{/if}
					{/if}
				{/if}
				{if !empty($excelLink)}<a href="{$excelLink|escape}">{translate text='Export To CSV' isPublicFacing=true}</a>{/if}
				{if !empty($risLink)}<a href="{$risLink|escape}">{translate text="Export To RIS" isPublicFacing=true}</a>{/if}

			{/if}
			{if !empty($loggedIn) && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
			{/if}
			{if !empty($loggedIn) && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions) || in_array('Administer Selected Browse Category Groups', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')">{translate text='Add To Browse' isPublicFacing=true}</a>
			{/if}
		</div>
	{/if}
</div>

{* Embedded Javascript For this Page *}
<script type="text/javascript">
	$(function(){ldelim}
		if ($('#horizontal-menu-bar-container').is(':visible')) {ldelim}
			$('#home-page-search').show();  {*// Always show the searchbox for search results in mobile views.*}
		{rdelim}

		{if !empty($showInnReachLink)}
		AspenDiscovery.InterLibraryLoan.getInnReachResults(5, {$innReachSavedSearchId});
		{/if}

		{if !empty($showShareItLink)}
		AspenDiscovery.InterLibraryLoan.getShareItResults(5, {$shareItSavedSearchId});
		{/if}

		{if !empty($showDplaLink)}
		AspenDiscovery.DPLA.getDPLAResults("{$lookfor|escapeCSS}");
		{/if}

		{if empty($onInternalIP)}
			{* Because content is served on the page, have to set the mode that was used, even if the user didn't choose the mode. *}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
		{else}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
			Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+AspenDiscovery.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}
		$('#'+AspenDiscovery.Searches.displayMode+'Modal').parent('label').addClass('active'); {* show user which one is selected *}

		{rdelim});
</script>