<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">

{* Hid record count b/c moved to breadcrumbs.tpl - JE 6/18/15 *}
{*
		{if $recordCount}
			{if $displayMode == 'covers'}
				There are {$recordCount|number_format} total results.
			{else}
				{translate text="Showing"}
				{$recordStart} - {$recordEnd}
				{translate text='of'} {$recordCount|number_format}
			{/if}
		{/if}
		<span class="hidden-phone">
			 {translate text='query time'}: {$qtime}s
		</span>
*}
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

		{if $numUnscopedResults && $numUnscopedResults != $recordCount}
		{* avoids when both searches are unscoped *}
			<div class="unscopedResultCount">
				There are <b>{$numUnscopedResults}</b> results in the entire {$consortiumName} collection. <a href="{$unscopedSearchUrl}">Search the entire collection.</a>
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
			<a onclick="return VuFind.Searches.getMoreResults()" role="button">
				<div class="row" id="more-browse-results">
					<span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
				</div>
			</a>
		{/if}
	{else}
		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	{/if}

	{*Additional Suggestions on the last page of search results or no results returned *}

	{if $unscopedResults}
		<h2>More results from the {$consortiumName} Catalog</h2>
		<div class="unscopedResultCount">
		There are <b>{$numUnscopedResults}</b> results in the entire {$consortiumName} collection. <a href="{$unscopedSearchUrl}">Search the entire collection.</a>
		</div>
		{$unscopedResults}{* Unscoped Results already set for display *}
	{/if}

	{if $showProspectorLink}
		{* Prospector Results *}
		<div id='prospectorSearchResultsPlaceholder'></div>
		{* javascript call for content at bottom of page*}
	{/if}

	{if $showDplaLink}
		{* DPLA Results *}
		<div id='dplaSearchResultsPlaceholder'></div>
	{/if}

	{if $enableMaterialsRequest}
		<h2>Didn't find it?</h2>
		<p>Can't find what you are looking for? <a href="{$path}/MaterialsRequest/NewRequest?lookfor={$lookfor}&basicType={$searchIndex}" onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text='Suggest a purchase'}</a>.</p>
	{elseif $externalMaterialsRequestUrl}
		<h2>Didn't find it?</h2>
		<p>Can't find what you are looking for? <a href="{$externalMaterialsRequestUrl}">{translate text='Suggest a purchase'}</a>.</p>
	{/if}

	{if $showSearchTools || ($loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles)))}
	<div class="searchtools well small">
		<strong>{translate text='Search Tools'}:</strong>
		{if $showSearchTools}
			<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return VuFind.Account.ajaxLightbox('{$path}/Search/AJAX?method=getEmailForm', true);"><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
			{if $savedSearch}
				<a href="#" onclick="return VuFind.Account.saveSearch('{$searchId}')"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>
			{else}
				<a href="#" onclick="return VuFind.Account.saveSearch('{$searchId}')"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>
			{/if}
			<a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
		{/if}
		{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
			<a href="#" onclick="return VuFind.ListWidgets.createWidgetFromSearch('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Create Widget'}</a>
		{/if}
		{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
			<a href="#" onclick="return VuFind.Browse.addToHomePage('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Add To Home Page as Browse Category'}</a>
		{/if}
	</div>
	{/if}
</div>

{* Embedded Javascript For this Page *}
<script type="text/javascript">
	$(function(){ldelim}
		{if $showProspectorLink}
		VuFind.Prospector.getProspectorResults(5, {$prospectorSavedSearchId});
		{/if}

		{if $showDplaLink}
		VuFind.DPLA.getDPLAResults('{$lookfor}');
		{/if}

		{*{include file="Search/results-displayMode-js.tpl"}*}
		{if !$onInternalIP}
		{*if (!Globals.opac &&VuFind.hasLocalStorage()){ldelim}*}
			{*var temp = window.localStorage.getItem('searchResultsDisplayMode');*}
			{*if (VuFind.Searches.displayModeClasses.hasOwnProperty(temp)) VuFind.Searches.displayMode = temp; *}{* if stored value is empty or a bad value, fall back on default setting ("null" returned when not set) *}
			{*else VuFind.Searches.displayMode = '{$displayMode}';*}
			{*{rdelim}*}
		{*else*}
		{* Because content is served on the page, have to set the mode that was used, even if the user didn't chose the mode. *}
			VuFind.Searches.displayMode = '{$displayMode}';
		{else}
		VuFind.Searches.displayMode = '{$displayMode}';
		Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+VuFind.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}

		{rdelim});
</script>
