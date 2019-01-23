{strip}
{* Recommendations *}
{if $topRecommendations}
	{foreach from=$topRecommendations item="recommendations"}
		{include file=$recommendations}
	{/foreach}
{/if}

	<h2>{translate text='nohit_heading'}</h2>

<p class="alert alert-info">{translate text='nohit_prefix'} - <b>{if $lookfor}{$lookfor|escape:"html"}{else}&lt;empty&gt;{/if}</b> - {translate text='nohit_suffix'}</p>

{* Return to Advanced Search Link *}
{if $searchType == 'advanced'}
	<h5>
		<a href="{$path}/Search/Advanced">Edit This Advanced Search</a>
	</h5>
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

{if $numUnscopedResults && $numUnscopedResults != 0}
	<div class="unscopedResultCount">
		There are <b>{$numUnscopedResults}</b> results in the entire {$consortiumName} collection. <span style="font-size:15px"><a href="{$unscopedSearchUrl}">Search the entire collection.</a></span>
	</div>
{/if}
<div>
	{if $parseError}
		<div class="alert alert-danger">
			{$parseError}
		</div>
	{/if}

	{if $spellingSuggestions}
		<div class="correction">
			<h2>Spelling Suggestions</h2>
			<p>Here are some alternative spellings that you can try instead.</p>
			<div class="row">
				{foreach from=$spellingSuggestions item=url key=term name=termLoop}
					<div class="col-xs-6 col-sm-4 col-md-3 text-left">
						<a class='btn btn-xs btn-default btn-block' href="{$url|escape}">{$term|escape|truncate:25:'...'}</a>
					</div>
				{/foreach}
			</div>
		</div>
		<br>
	{/if}

	{if $searchSuggestions}
		<div id="searchSuggestions">
			<h2>Similar Searches</h2>
			<p>These searches are similar to the search you tried. Would you like to try one of these instead?</p>
			<div class="row">
				{foreach from=$searchSuggestions item=suggestion}
					<div class="col-xs-6 col-sm-4 col-md-3 text-left">
						<a class='btn btn-xs btn-default btn-block' href="/Search/Results?lookfor={$suggestion.phrase|escape:url}&basicType={$searchIndex|escape:url}" title="{$suggestion.phrase}">{$suggestion.phrase|truncate:25:'...'}</a>
					</div>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $showExploreMoreBar}
		<div id="explore-more-bar-placeholder"></div>
		<script type="text/javascript">
			$(document).ready(
					function () {ldelim}
						VuFind.Searches.loadExploreMoreBar('{$exploreMoreSection}', '{$exploreMoreSearchTerm|escape:"html"}');
						{rdelim}
			);
		</script>
	{/if}

	{if $unscopedResults}
		<h2>Results from the entire {$consortiumName} Catalog</h2>
		{*{foreach from=$unscopedResults item=record name="recordLoop"}*}
			{*<div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">*}
				{* This is raw HTML -- do not escape it: *}
				{*{$record}*}
			{*</div>*}
		{*{/foreach}*}
		{$unscopedResults}
	{/if}

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

	{* Display Repeat this search links *}
	{if strlen($lookfor) > 0 && count($repeatSearchOptions) > 0}
		<div class='repeatSearchHead'><h4>Try another catalog</h4></div>
			<div class='repeatSearchList'>
			{foreach from=$repeatSearchOptions item=repeatSearchOption}
				<div class='repeatSearchItem'>
					<a href="{$repeatSearchOption.link}" class='repeatSearchName' target='_blank'>{$repeatSearchOption.name}</a>{if $repeatSearchOption.description} - {$repeatSearchOption.description}{/if}
				</div>
			{/foreach}
		</div>
	{/if}

	{if $enableMaterialsRequest}
		<h2>Didn't find it?</h2>
		<p>Can't find what you are looking for? <a href="{$path}/MaterialsRequest/NewRequest?lookfor={$lookfor}&basicType={$searchIndex}" onclick="return VuFind.Account.followLinkIfLoggedIn(this);">{translate text='Suggest a purchase'}</a>.</p>
	{elseif $externalMaterialsRequestUrl}
		<h2>Didn't find it?</h2>
		<p>Can't find what you are looking for? <a href="{$externalMaterialsRequestUrl}">{translate text='Suggest a purchase'}</a>.</p>
	{/if}

	{if $showSearchTools || ($loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles)))}
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
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles))}
				<a href="#" onclick="return VuFind.ListWidgets.createWidgetFromSearch('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Create Widget'}</a>
			{/if}
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles))}
				<a href="#" onclick="return VuFind.Browse.addToHomePage('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Add To Home Page as Browse Category'}</a>
			{/if}
		</div>
	{/if}

</div>

<script type="text/javascript">
	$(function(){ldelim}
		{if $showProspectorLink}
		VuFind.Prospector.getProspectorResults(5, {$prospectorSavedSearchId});
		{/if}
		{if $showDplaLink}
		VuFind.DPLA.getDPLAResults('{$lookfor}');
		{/if}
		{rdelim});
</script>
{/strip}