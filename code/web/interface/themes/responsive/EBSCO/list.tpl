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
			<a href="{$rssLink|escape}"><span class="silk feed">&nbsp;</span>{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true); "><span class="silk email">&nbsp;</span>{translate text='Email this Search'}</a>
			{if $savedSearch}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')"><span class="silk delete">&nbsp;</span>{translate text='save_search_remove'}</a>
			{else}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')"><span class="silk add">&nbsp;</span>{translate text='save_search'}</a>
			{/if}
			<a href="{$excelLink|escape}"><span class="silk table_go">&nbsp;</span>{translate text='Export To Excel'}</a>
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
				<a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Create Spotlight'}</a>
			{/if}
			{if $loggedIn && (array_key_exists('opacAdmin', $userRoles) || array_key_exists('libraryAdmin', $userRoles) || array_key_exists('contentEditor', $userRoles) || array_key_exists('libraryManager', $userRoles) || array_key_exists('locationManager', $userRoles))}
				<a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')"><span class="silk cog_go">&nbsp;</span>{translate text='Add To Browse'}</a>
			{/if}
		</div>
	{/if}
</div>
