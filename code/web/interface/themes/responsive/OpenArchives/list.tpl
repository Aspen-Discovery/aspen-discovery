<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">

		<div>
			{if $recordCount}
				{if $displayMode == 'covers'}
					There are {$recordCount|number_format} total results.
				{else}
					{translate text="Showing"}
					{$recordStart} - {$recordEnd}
					{translate text='of'} {$recordCount|number_format}
				{/if}
			{else}
				No results found in {$sectionLabel}
			{/if}
			{if !$productionServer}
			<span class="hidden-phone">
			 {translate text='query time'}: {$qtime}s
			</span>
			{/if}
		</div>

		{if $replacementTerm}
			<div id="replacement-search-info-block">
				<div id="replacement-search-info"><span class="replacement-search-info-text">Showing Results for</span> {$replacementTerm}</div>
				<div id="original-search-info"><span class="replacement-search-info-text">Search instead for </span><a href="{$oldSearchUrl}">{$oldTerm}</a></div>
			</div>
		{/if}

		{if $solrSearchDebug}
			<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">Show Search Options</div>
			<div id="solrSearchOptions" style="display:none">
				<pre>Search options: {$solrSearchDebug}</pre>
			</div>
		{/if}

		{if $solrLinkDebug}
			<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>Show Solr Link</div>
			<div id='solrLink' style='display:none'>
				<pre>{$solrLinkDebug}</pre>
			</div>
		{/if}

		{if $debugTiming}
			<div id='solrTimingToggle' onclick='$("#solrTiming").toggle()'>Show Solr Timing</div>
			<div id='solrTiming' style='display:none'>
				<pre>{$debugTiming}</pre>
			</div>
		{/if}

		{* User's viewing mode toggle switch *}
		{include file="Search/results-displayMode-toggle.tpl"}

		<div class="clearer"></div>
	</div>
	{* End Listing Options *}

	{if $subpage}
		{include file=$subpage}
	{else}
		{$pageContent}
	{/if}

	{if $displayMode == 'covers'}
		{if $recordEnd < $recordCount}
			<a onclick="return AspenDiscovery.Searches.getMoreResults()" role="button">
				<div class="row" id="more-browse-results">
					<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
				</div>
			</a>
		{/if}
	{else}
		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	{/if}

	{if $showDplaLink}
		{* DPLA Results *}
		<div id='dplaSearchResultsPlaceholder'></div>
	{/if}

	{if $showSearchTools || ($loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)))}
	<div class="search_tools well small">
		<strong>{translate text='Search Tools'}:</strong>
		{if $showSearchTools}
			<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('{$path}/Search/AJAX?method=getEmailForm', true);"><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
			{if $savedSearch}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>
			{else}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>
			{/if}
			<a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
		{/if}
		{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
			<a href="#" onclick="return AspenDiscovery.ListWidgets.createWidgetFromSearch('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Create Widget'}</a>
		{/if}
		{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
			<a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Add To Home Page as Browse Category'}</a>
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