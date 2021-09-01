{strip}
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	<h1>{translate text="No Results Found" isPublicFacing=true}</h1>

	<p class="alert alert-info">
		{if (empty($lookfor))}
			{translate text="Your search - <b>&lt;empty&gt;</b> - did not match any resources." isPublicFacing=true}
		{else}
			{translate text="Your search - <b>%1%</b> - did not match any resources." 1=$lookfor|escape:html isPublicFacing=true}
		{/if}
	</p>

	{* Return to Advanced Search Link *}
	{if $searchType == 'advanced'}
		<h5>
			<a href="/Search/Advanced">{translate text="Edit This Advanced Search"}</a>
		</h5>
	{/if}

	{if !empty($solrSearchDebug)}
		<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options" isPublicFacing=true}</div>
		<div id="solrSearchOptions" style="display:none">
			<pre>{translate text="Search options" isPublicFacing=true} {$solrSearchDebug}</pre>
		</div>
	{/if}

	{if !empty($solrLinkDebug)}
		<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link" isPublicFacing=true}</div>
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

		{if !empty($keywordResultsLink)}
			<div class="correction">
			<h3>{translate text="Try a Keyword Search?" isPublicFacing=true}</h3>
                {translate text="Your search type is not set to Keyword.  There are <strong>%1%</strong> results when searching by keyword." 1= keywordResultsCount isPublicFacing=true}
				<a class='btn btn-primary' href="{$keywordResultsLink}">{translate text="Search by Keyword" isPublicFacing=true}</a>.
			</div>
		{/if}

		{if $placard}
			{include file="Search/placard.tpl"}
		{/if}

		{include file="Search/searchSuggestions.tpl"}

		{include file="Search/spellingSuggestions.tpl"}

		{if $showExploreMoreBar}
			<div id="explore-more-bar-placeholder"></div>
			<script type="text/javascript">
				$(document).ready(
					function () {ldelim}
						AspenDiscovery.Searches.loadExploreMoreBar('{$exploreMoreSection}', '{$exploreMoreSearchTerm|escape:"html"}');
					{rdelim}
				);
			</script>
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

		{if $materialRequestType == 1}
			<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
			<p>{translate text="Can't find what you are looking for?" isPublicFacing=true} <a href="/MaterialsRequest/NewRequest?lookfor={$lookfor}&searchIndex={$searchIndex}" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Suggest a purchase' isPublicFacing=true}</a>.</p>
		{elseif $materialRequestType == 2}
			<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
			<p>{translate text="Can't find what you are looking for?" isPublicFacing=true} <a href="/MaterialsRequest/NewRequestIls?lookfor={$lookfor}&searchIndex={$searchIndex}" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);">{translate text='Suggest a purchase' isPublicFacing=true}</a>.</p>
		{elseif $materialRequestType == 3}
			<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
			<p>{translate text="Can't find what you are looking for?" isPublicFacing=true} <a href="{$externalMaterialsRequestUrl}">{translate text='Suggest a purchase' isPublicFacing=true}</a>.</p>
		{/if}

		{if $showSearchTools || ($loggedIn && count($userPermissions) > 0)}
			<br/>
			<div class="search_tools well small">
				<strong>{translate text='Search Tools' isPublicFacing=true} </strong>
				{if $showSearchTools}
					<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a>
					<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a>
					{if $savedSearch}
						<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text="Remove Saved Search" isPublicFacing=true}</a>
					{else}
						<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a>
					{/if}
					<a href="{$excelLink|escape}">{translate text='Export To Excel' isPublicFacing=true}</a>
				{/if}
			</div>
		{/if}

	</div>

	<script type="text/javascript">
		$(function(){ldelim}
			{if $showProspectorLink}
			AspenDiscovery.Prospector.getProspectorResults(5, {$prospectorSavedSearchId});
			{/if}
			{if $showDplaLink}
			AspenDiscovery.DPLA.getDPLAResults('{$lookfor}');
			{/if}
		{rdelim});
	</script>
{/strip}