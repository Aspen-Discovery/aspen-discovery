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

<div>
	{if !empty($parseError)}
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
						<a class='btn btn-xs btn-default btn-block' href="/Search/Results?lookfor={$suggestion.phrase|escape:url}&searchIndex={$searchIndex|escape:url}" title="{$suggestion.phrase}">{$suggestion.phrase|truncate:25:'...'}</a>
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
						VuFind.Searches.loadExploreMoreBar('open_archives', '{$exploreMoreSearchTerm|escape:"html"}');
						{rdelim}
			);
		</script>
	{/if}

	{if $showSearchTools || ($loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles)))}
		<div class="search_tools well small">
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
{/strip}