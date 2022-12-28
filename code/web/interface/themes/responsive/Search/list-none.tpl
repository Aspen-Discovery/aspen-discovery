{strip}
	{* Recommendations *}
	{if !empty($topRecommendations)}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{if !empty($hasGlobalResults)}
		<h1>{translate text="No \"%1%\" Results Found" 1=$originalScopeLabel isPublicFacing=true}</h1>
	{else}
		{if !empty($hasKeywordResults)}
			<h1>{translate text="No \"%1%\" Results Found" 1=$originalSearchIndexLabel isPublicFacing=true}</h1>
		{else}
			<h1>{translate text="No Results Found" isPublicFacing=true}</h1>
		{/if}
	{/if}

	<p class="alert alert-info">
		{if !empty($hasGlobalResults)}
			{if (empty($lookfor))}
				{translate text="Your %1% search did not match any resources." isPublicFacing=true 1=$originalScope}
			{else}
				{translate text="Your %1% search - <b>%2%</b> - did not match any resources." 1=$originalScopeLabel 2=$lookfor|escape:html isPublicFacing=true}
			{/if}
			{if !empty($globalResultsLink)}
				&nbsp;
				{translate text="There are <strong>%1%</strong> results when searching %2%, would you like to search all libraries?" 1=$globalResultsCount 2=$globalScopeLabel isPublicFacing=true}
				&nbsp;<a class='btn btn-sm btn-primary' href="{$globalResultsLink}">{translate text="Search all libraries" isPublicFacing=true}</a>
			{/if}
		{else}
			{if !empty($hasKeywordResults)}
				{if (empty($lookfor))}
					{translate text="Your %1% search did not match any resources." isPublicFacing=true 1=$originalSearchIndexLabel}
				{else}
					{translate text="Your %1% search - <b>%2%</b> - did not match any resources." 1=$originalSearchIndexLabel 2=$lookfor|escape:html isPublicFacing=true}
				{/if}
				{if !empty($keywordResultsLink)}
					&nbsp;
					{translate text="There are <strong>%1%</strong> results when searching by keyword, would you like to search by keyword?" 1=$keywordResultsCount isPublicFacing=true}
					&nbsp;<a class='btn btn-sm btn-primary' href="{$keywordResultsLink}">{translate text="Search by Keyword" isPublicFacing=true}</a>
				{/if}
			{else}
				{if (empty($lookfor))}
					{translate text="Your search did not match any resources." isPublicFacing=true}
				{else}
					{translate text="Your search - <b>%1%</b> - did not match any resources." 1=$lookfor|escape:html isPublicFacing=true}
				{/if}
			{/if}
		{/if}
	</p>

	{* Return to Advanced Search Link *}
	{if $searchType == 'advanced'}
		<h5>
			<a href="/Search/Advanced">{translate text="Edit This Advanced Search"}</a>
		</h5>
	{/if}

	{if !empty($solrSearchDebug)}
		<div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options" isAdminFacing=true}</div>
		<div id="solrSearchOptions" style="display:none">
			<pre>{translate text="Search options" isPublicFacing=true} {$solrSearchDebug}</pre>
		</div>
	{/if}

	{if !empty($solrLinkDebug)}
		<div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link" isAdminFacing=true}</div>
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

		{if !empty($placard)}
			{include file="Search/placard.tpl"}
		{/if}

		{include file="Search/searchSuggestions.tpl"}

		{include file="Search/spellingSuggestions.tpl"}

		{if !empty($showExploreMoreBar)}
			<div id="explore-more-bar-placeholder"></div>
			<script type="text/javascript">
				$(document).ready(
					function () {ldelim}
						AspenDiscovery.Searches.loadExploreMoreBar('{$exploreMoreSection}', '{$exploreMoreSearchTerm|escape:"html"}');
					{rdelim}
				);
			</script>
		{/if}

		{if !empty($showProspectorLink)}
			{* Prospector Results *}
			<div id='prospectorSearchResultsPlaceholder'></div>
			{* javascript call for content at bottom of page*}
		{elseif !empty($interLibraryLoanName) && !empty($interLibraryLoanUrl)}
			{include file="Search/interLibraryLoanSearch.tpl"}
		{/if}

		{if !empty($showDplaLink)}
			{* DPLA Results *}
			<div id='dplaSearchResultsPlaceholder'></div>
		{/if}

		{if $materialRequestType == 1 && $displayMaterialsRequest}
			<div class="materialsRequestLink">
				<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
				<p>{translate text="Can't find what you are looking for?" isPublicFacing=true} <a href="/MaterialsRequest/NewRequest?lookfor={$lookfor}&searchIndex={$searchIndex}" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);" class="btn btn-info">{translate text='Suggest a purchase' isPublicFacing=true}</a></p>
			</div>
		{elseif $materialRequestType == 2 && $displayMaterialsRequest}
			<div class="materialsRequestLink">
				<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
				<p>{translate text="Can't find what you are looking for?" isPublicFacing=true} <a href="/MaterialsRequest/NewRequestIls?lookfor={$lookfor}&searchIndex={$searchIndex}" onclick="return AspenDiscovery.Account.followLinkIfLoggedIn(this);" class="btn btn-info">{translate text='Suggest a purchase' isPublicFacing=true}</a></p>
			</div>
		{elseif $materialRequestType == 3 && $displayMaterialsRequest}
			<div class="materialsRequestLink">
				<h2>{translate text="Didn't find it?" isPublicFacing=true}</h2>
				<p>{translate text="Can't find what you are looking for?" isPublicFacing=true} <a href="{$externalMaterialsRequestUrl}" class="btn btn-info">{translate text='Suggest a purchase' isPublicFacing=true}</a></p>
			</div>
		{/if}

		{if $showSearchTools || ($loggedIn && count($userPermissions) > 0)}
			<br/>
			<div class="search_tools well small">
				<strong>{translate text='Search Tools' isPublicFacing=true} </strong>
				{if !empty($showSearchTools)}
					<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a>
					<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true);">{translate text='Email this Search' isPublicFacing=true}</a>
					{if !empty($enableSavedSearches)}
						{if !empty($savedSearch)}
							<a href="/MyAccount/SaveSearch?delete={$searchId}">{translate text="Remove Saved Search" isPublicFacing=true}</a>
						{else}
							<a href="#" onclick="return AspenDiscovery.Account.showSaveSearchForm('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a>
						{/if}
					{/if}
					<a href="{$excelLink|escape}">{translate text='Export To Excel' isPublicFacing=true}</a>
				{/if}
			</div>
		{/if}

	</div>

	<script type="text/javascript">
		$(function(){ldelim}
			{if !empty($showProspectorLink)}
			AspenDiscovery.Prospector.getProspectorResults(5, {$prospectorSavedSearchId});
			{/if}
			{if !empty($showDplaLink)}
			AspenDiscovery.DPLA.getDPLAResults("{$lookfor|escapeCSS}");
			{/if}
		{rdelim});
	</script>
{/strip}