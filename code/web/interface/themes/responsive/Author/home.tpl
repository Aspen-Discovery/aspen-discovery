{strip}
	{if !empty($replacedScope)}
		<div id="replacement-search-info-block" class="alert alert-info" role="alert">
			<div id="replacement-search-info"><span class="replacement-search-info-text">{translate text="Showing Results for %1%" 1=$globalScopeLabel isPublicFacing=true}</span> {$replacedScope}</div>
			<div id="original-search-info"><span class="replacement-search-info-text"><a href='{$oldSearchUrl}'>{translate text="Search %1% instead" 1=$replacedScopeLabel isPublicFacing=true}</a></span></div>
		</div>
	{elseif !empty($hasGlobalResults) && !$defaultSearchResults}
		<h1>{translate text="No \"%1%\" Results Found" 1=$originalScopeLabel isPublicFacing=true}</h1>
		<p class="alert alert-info">
			{if (empty($lookfor))}
				{translate text="Your %1% search did not match any resources." isPublicFacing=true 1=$originalScope}
			{else}
				{translate text="Your %1% search - <b>%2%</b> - did not match any resources." 1=$originalScopeLabel 2=$lookfor|escape:html isPublicFacing=true}

				{/if}
			{if !empty($globalResultsLink)}
				{translate text="There are <strong>%1%</strong> results when searching %2%, would you like to search all libraries?" 1=$globalResultsCount 2=$globalScopeLabel isPublicFacing=true}
				<a class='btn btn-sm btn-primary' href="{$globalResultsLink}">{translate text="Search all libraries" isPublicFacing=true}</a>
			{/if}
		</p>
	{/if}
	{if $recordCount > 0}
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
							<span class="glyphicon glyphicon-chevron-down" aria-label="{translate text='Get More Results' inAttribute=true isPublicFacing=true}" role="button"></span>
						</div>
					</a>
				{/if}
			{else}
				{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}
			{/if}

			{if !empty($showSearchTools) && !$showSearchToolsAtTop && (empty($offline) || $enableEContentWhileOffline)}
				<div class="well small">
					<strong>{translate text='Search Tools' isPublicFacing=true} </strong> &nbsp;
					{if !empty($rssLink)}<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a> &nbsp;{/if}
					<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a>
				</div>
			{/if}
		</div>
	{/if}
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
