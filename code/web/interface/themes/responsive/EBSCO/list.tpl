<h1 class="hiddenTitle">{translate text='Articles & Databases Search Results'}</h1>
<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	<div class="result-head">
		{* User's viewing mode toggle switch *}
		{include file="Search/results-displayMode-toggle.tpl"}

		<div class="clearer"></div>
	</div>

	{if $subpage}
		{include file=$subpage}
	{else}
		{$pageContent}
	{/if}

	{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}

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
			{if $loggedIn && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
			{/if}
			{if $loggedIn && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')">{translate text='Add To Browse'}</a>
			{/if}
		</div>
	{/if}
</div>
