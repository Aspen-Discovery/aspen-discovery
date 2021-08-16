<h1 class="hiddenTitle">{translate text='Archive Search Results'}</h1>
<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Listing Options *}
	<div class="resultHead">
		{if !empty($solrSearchDebug)}
			<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options"}</div>
			<div id="solrSearchOptions" style="display:none">
				<pre>Search options: {$solrSearchDebug}</pre>
			</div>
		{/if}

		{if !empty($solrLinkDebug)}
			<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link"}</div>
			<div id='solrLink' style='display:none'>
				<pre>{$solrLinkDebug}</pre>
			</div>
		{/if}

		{if !empty($debugTiming)}
			<div id='solrTimingToggle' onclick='$("#solrTiming").toggle()'>{translate text="Show Solr Timing"}</div>
			<div id='solrTiming' style='display:none'>
				<pre>{$debugTiming}</pre>
			</div>
		{/if}

		{if !empty($spellingSuggestions)}
			<br /><br /><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br/>
			{foreach from=$spellingSuggestions item=details key=term name=termLoop}
				{$details.phrase|escape} ($details.freq) &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$data.phrase|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
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
		<div class="search_tools well small">
			<strong>{translate text='Search Tools'}:</strong>
			<a href="{$rssLink|escape}">{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true); ">{translate text='Email this Search'}</a>
			{if $savedSearch}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text='save_search_remove'}</a>
			{else}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text='save_search'}</a>
			{/if}
			<a href="{$excelLink|escape}">{translate text='Export To Excel'}</a>
		</div>
	{/if}
</div>
{* Embedded Javascript For this Page *}
<script type="text/javascript">
	$(function(){ldelim}
		if ($('#horizontal-menu-bar-container').is(':visible')) {ldelim}
			$('#home-page-search').show();  {*// Always show the searchbox for search results in mobile views.*}
			{rdelim}

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