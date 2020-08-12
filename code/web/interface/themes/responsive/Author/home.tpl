{strip}
<div>
	<h1>{$authorName}</h1>
	<div class="row">
		<div id="wikipedia_placeholder" class="col-xs-12">
		</div>
	</div>

	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">
		<div>
			{if !empty($replacementTerm)}
				<div id="replacement-search-info">
					<span class="replacement-search-info-text">Showing Results for </span>{$replacementTerm}<span class="replacement-search-info-text">.  Search instead for <span class="replacement-search-info-text"><a href="{$oldSearchUrl}">{$oldTerm}</a>
				</div>
			{/if}

			{if $spellingSuggestions}
				<br><br><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br>
				{foreach from=$spellingSuggestions item=details key=term name=termLoop}
					{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$data.phrase|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br>{/if}
				{/foreach}
			</div>
			{/if}
		</div>

		{* User's viewing mode toggle switch *}
		{include file="Search/results-displayMode-toggle.tpl"}

		<div class="clearer"></div>
	</div>
	{* End Listing Options *}

	{include file=$resultsTemplate}

	{if $displayMode == 'covers'}
		{if $recordEnd < $recordCount}
			<a onclick="return AspenDiscovery.Searches.getMoreResults()" role="button" title="{translate text='Get More Results'}">
				<div class="row" id="more-browse-results">
					<span class="glyphicon glyphicon-chevron-down" aria-label="{translate text='Get More Results'}"></span>
				</div>
			</a>
		{/if}
	{else}
		{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
	{/if}

	{if $showSearchTools}
		<div class="well small">
			<strong>{translate text='Search Tools'}:</strong>
			<a href="{$rssLink|escape}">{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search'}</a>
		</div>
	{/if}
</div>
{/strip}

{* Embedded Javascript For this Page *}
<script type="text/javascript">
	$(document).ready(function (){ldelim}
		{if $showWikipedia}
			AspenDiscovery.Wikipedia.getWikipediaArticle('{$wikipediaAuthorName}');
		{/if}
        AspenDiscovery.Authors.loadEnrichmentInfo('{$firstWorkId}');

		{if !$onInternalIP}
			{* Because content is served on the page, have to set the mode that was used, even if the user didn't chose the mode. *}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
		{else}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
			Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+AspenDiscovery.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}
	{rdelim});
</script>
