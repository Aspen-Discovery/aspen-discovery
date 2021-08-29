<h2 aria-label="Catalog Search Results" style="font-size:0;margin:0;line-height:0;"><span class="hidden">{translate text='Catalog Search Results'}</h2>
<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">
		{if !empty($replacementTerm)}
			<div id="replacement-search-info-block">
				<div id="replacement-search-info"><span class="replacement-search-info-text">{translate text="Showing Results for" isPublicFacing=true}</span> {$replacementTerm}</div>
				<div id="original-search-info"><span class="replacement-search-info-text">{translate text="Search instead for" isPublicFacing=true} </span><a href="{$oldSearchUrl}">{$oldTerm}</a></div>
			</div>
		{/if}

		{if !empty($solrSearchDebug)}
			<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options" isPublicFacing=true}</div>
			<div id="solrSearchOptions" style="display:none">
				<pre>{translate text="Search options" isPublicFacing=true} {$solrSearchDebug}</pre>
			</div>
		{/if}

		{if !empty($solrLinkDebug)}
			<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link" isPublicFacing=true}</div>
			<div id='solrLink' style='display:none'>
				<pre>{$solrLinkDebug}</pre>
			</div>
		{/if}

		{if !empty($debugTiming)}
			<div id='solrTimingToggle' onclick='$("#solrTiming").toggle()'>{translate text="Show Solr Timing" isPublicFacing=true}</div>
			<div id='solrTiming' style='display:none'>
				<pre>{$debugTiming}</pre>
			</div>
		{/if}

		{* User's viewing mode toggle switch *}
		{include file="Search/results-displayMode-toggle.tpl"}

		<div class="clearer"></div>
	</div>
	{* End Listing Options *}

	{if $placard}
		{include file="Search/placard.tpl"}
	{/if}

	{if $subpage}
		{include file=$subpage}
	{else}
		{$pageContent}
	{/if}

	{if $displayMode == 'covers'}
		{if $recordEnd < $recordCount}
			<a onclick="return AspenDiscovery.Searches.getMoreResults()" role="button" title="{translate text='Get More Results' inAttribute=true isPublicFacing=true}">
				<div class="row" id="more-browse-results">
					<span class="glyphicon glyphicon-chevron-down" aria-hidden="true" aria-label="{translate text='Get More Results' inAttribute=true isPublicFacing=true}"></span>
				</div>
			</a>
		{/if}
	{else}
		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	{/if}

	{include file="Search/searchSuggestions.tpl"}

	{include file="Search/spellingSuggestions.tpl"}

	{if $showProspectorLink}
		{* Prospector Results *}
		<div id='prospectorSearchResultsPlaceholder'></div>
		{* javascript call for content at bottom of page*}
	{elseif !empty($interLibraryLoanName) && !empty($interLibraryLoanUrl)}
		{include file="Search/interLibraryLoanSearch.tpl"}
	{/if}

	{if $showDplaLink}
		{* DPLA Results *}
		<div id='dplaSearchResultsPlaceholder'></div>
	{/if}

	{if $materialRequestType == 1 }
		<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
		<p>
			{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequest" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
		</p>
	{elseif $materialRequestType == 2}
		<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
		<p>
			{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="/MaterialsRequest/NewRequestIls" class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Submit Request' isPublicFacing=true}</a>
		</p>
	{elseif $materialRequestType == 3}
		<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
		<p>
			{translate text="Can't find what you are looking for? Try our Materials Request Service." isPublicFacing=true} <a href="{$externalMaterialsRequestUrl}" class="btn btn-sm btn-info">{translate text='Submit Request' isPublicFacing=true}</a>
		</p>
	{/if}

	{if $showSearchTools || ($loggedIn && count($userPermissions) > 0)}
	<div class="search_tools well small">
		<strong>{translate text='Search Tools' isPublicFacing=true} </strong>
		{if $showSearchTools}
			<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a>
			{if $savedSearch}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text="Remove Saved Search" isPublicFacing=true}</a>
			{else}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a>
			{/if}
			<a href="{$excelLink|escape}">{translate text='Export To Excel' isPublicFacing=true}</a>
		{/if}
		{if $loggedIn && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
			<a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
		{/if}
		{if $loggedIn && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
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

		{if $showProspectorLink}
		AspenDiscovery.Prospector.getProspectorResults(5, {$prospectorSavedSearchId});
		{/if}

		{if $showDplaLink}
		AspenDiscovery.DPLA.getDPLAResults('{$lookfor}');
		{/if}

		{if !$onInternalIP}
			{* Because content is served on the page, have to set the mode that was used, even if the user didn't choose the mode. *}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
		{else}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
			Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+AspenDiscovery.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}

		{rdelim});
</script>