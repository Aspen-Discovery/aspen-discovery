<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Listing Options *}
	<div class="resulthead">
		{if $recordCount}
			{translate text="Showing"}
			<b> {$recordStart}</b> - <b>{$recordEnd} </b>
			{translate text='of'} <b>{$recordCount} </b>
			{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
		{/if}
		<span class="hidden-phone">
			,&nbsp;{translate text='query time'}: {$qtime}s
		</span>

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

		{if $spellingSuggestions}
			<br /><br /><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br/>
			{foreach from=$spellingSuggestions item=details key=term name=termLoop}
				{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
			{/foreach}
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

	{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}

	{if $showSearchTools}
		<div class="searchtools well small">
			<strong>{translate text='Search Tools'}:</strong>
			<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return VuFind.Account.ajaxLightbox('{$path}/Search/AJAX?method=getEmailForm', true); "><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
			{if $savedSearch}
				<a href="#" onclick="return VuFind.Account.saveSearch('{$searchId}')"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>
			{else}
				<a href="#" onclick="return VuFind.Account.saveSearch('{$searchId}')"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>
			{/if}
			<a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
		</div>
	{/if}
</div>
{* Embedded Javascript For this Page *}
<script type="text/javascript">
	$(function(){ldelim}
		if ($('#horizontal-menu-bar-container').is(':visible')) {ldelim}
			$('#home-page-search').show();  {*// Always show the searchbox for search results in mobile views.*}
			{rdelim}

{*  TODO: Show DPLA results on archive page?
		{if $showDplaLink}
		VuFind.DPLA.getDPLAResults('{$lookfor}');
		{/if}
*}

		{*{include file="Search/results-displayMode-js.tpl"}*}
		{if !$onInternalIP}
		{*if (!Globals.opac &&VuFind.hasLocalStorage()){ldelim}*}
		{*var temp = window.localStorage.getItem('searchResultsDisplayMode');*}
		{*if (VuFind.Searches.displayModeClasses.hasOwnProperty(temp)) VuFind.Searches.displayMode = temp; *}{* if stored value is empty or a bad value, fall back on default setting ("null" returned when not set) *}
		{*else VuFind.Searches.displayMode = '{$displayMode}';*}
		{*{rdelim}*}
		{*else*}
		{* Because content is served on the page, have to set the mode that was used, even if the user didn't choose the mode. *}
		VuFind.Searches.displayMode = '{$displayMode}';
		{else}
		VuFind.Searches.displayMode = '{$displayMode}';
		Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+VuFind.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}

		{rdelim});
</script>