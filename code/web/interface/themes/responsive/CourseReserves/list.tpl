<h1 class="hiddenTitle">{translate text='List Search Results' isPublicFacing=true}</h1>
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
			<div id="replacement-search-info-block">
				<div id="replacement-search-info"><span class="replacement-search-info-text">{translate text="Showing Results for" isPublicFacing=true}</span> {$replacementTerm}</div>
				<div id="original-search-info"><span class="replacement-search-info-text">{translate text="Search instead for" isPublicFacing=true} </span><a href="{$oldSearchUrl}">{$oldTerm}</a></div>
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

	{if !empty($subpage)}
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
		{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
	{/if}

	{*Additional Suggestions on the last page of search results or no results returned *}

	{if ($showSearchTools || ($loggedIn  && count($userPermissions) > 0)) && !$showSearchToolsAtTop}
	<div class="search_tools well small">
		<strong>{translate text='Search Tools' isPublicFacing=true} </strong>
		{if !empty($showSearchTools)}
			<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a>
			{if !empty($enableSavedSearches)}
				{if !empty($savedSearch)}
					<a href="/MyAccount/SaveSearch?delete={$searchId}">{translate text="Remove Saved Search" isPublicFacing=true}</a>
				{else}
					<a href="#" onclick="return AspenDiscovery.Account.showSaveSearchForm('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a>
				{/if}
			{/if}
			<a href="{$excelLink|escape}">{translate text='Export To Excel' isPublicFacing=true}</a>
		{/if}
		{if !empty($loggedIn) && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
			<a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
		{/if}
		{if !empty($loggedIn) && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
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

		{if empty($onInternalIP)}
		{* Because content is served on the page, have to set the mode that was used, even if the user didn't choose the mode. *}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
		{else}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
			Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+AspenDiscovery.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}

		{rdelim});
</script>