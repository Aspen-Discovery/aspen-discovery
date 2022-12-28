{strip}
<div>
	<h1>{$authorName}</h1>
	<div class="row">
		<div id="wikipedia_placeholder" class="col-xs-12">
		</div>
	</div>

	{if !empty($topRecommendations)}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Information about the search *}
	<div class="result-head">
		{* User's viewing mode toggle switch *}
		{if !empty($showSearchToolsAtTop)}
			{include file="Search/search-toolbar.tpl"}
		{else}
			{include file="Search/results-displayMode-toggle.tpl"}
		{/if}

		<div class="clearer"></div>
	</div>
	{* End Listing Options *}

	{include file=$resultsTemplate}

	{if $displayMode == 'covers'}
		{if $recordEnd < $recordCount}
			<a onclick="return AspenDiscovery.Searches.getMoreResults()" role="button" title="{translate text='Get More Results' inAttribute=true isPublicFacing=true}">
				<div class="row" id="more-browse-results">
					<span class="glyphicon glyphicon-chevron-down" aria-label="{translate text='Get More Results' inAttribute=true isPublicFacing=true}"></span>
				</div>
			</a>
		{/if}
	{else}
		{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
	{/if}

	{if !empty($showSearchTools) && !$showSearchToolsAtTop}
		<div class="well small">
			<strong>{translate text='Search Tools' isPublicFacing=true} </strong> &nbsp;
			<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a> &nbsp;
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a>
		</div>
	{/if}
</div>
{/strip}

{* Embedded Javascript For this Page *}
<script type="text/javascript">
	$(document).ready(function (){ldelim}
		{if !empty($showWikipedia)}
			AspenDiscovery.Wikipedia.getWikipediaArticle('{$wikipediaAuthorName}');
		{/if}
        AspenDiscovery.Authors.loadEnrichmentInfo('{$firstWorkId}');

		{if empty($onInternalIP)}
			{* Because content is served on the page, have to set the mode that was used, even if the user didn't chose the mode. *}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
		{else}
			AspenDiscovery.Searches.displayMode = '{$displayMode}';
			Globals.opac = 1; {* set to true to keep opac browsers from storing browse mode *}
		{/if}
		$('#'+AspenDiscovery.Searches.displayMode).parent('label').addClass('active'); {* show user which one is selected *}
	{rdelim});
</script>
