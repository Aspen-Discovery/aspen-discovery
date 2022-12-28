<h1 class="hiddenTitle">{translate text='Articles & Databases Search Results'}</h1>
<div id="searchInfo">
	{* Recommendations *}
	{if !empty($topRecommendations)}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	<div class="result-head">
		{* User's viewing mode toggle switch *}
		{if !empty($showSearchToolsAtTop)}
			{include file="Search/search-toolbar.tpl"}
		{else}
			{include file="Search/results-displayMode-toggle.tpl"}
		{/if}

		<div class="clearer"></div>
	</div>

	{if !empty($subpage)}
		{include file=$subpage}
	{else}
		{$pageContent}
	{/if}

	{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}

	{if !empty($showSearchTools) && !$showSearchToolsAtTop}
		<div class="search_tools well small">
			<strong>{translate text='Search Tools' isPublicFacing=true} </strong>
			<a href="{$rssLink|escape}">{translate text='Get RSS Feed' isPublicFacing=true}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true); ">{translate text='Email this Search' isPublicFacing=true}</a>
			{if !empty($enableSavedSearches)}
				{if !empty($savedSearch)}
					<a href="/MyAccount/SaveSearch?delete={$searchId}">{translate text="Remove Saved Search" isPublicFacing=true}</a>
				{else}
					<a href="#" onclick="return AspenDiscovery.Account.showSaveSearchForm('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a>
				{/if}
			{/if}
			<a href="{$excelLink|escape}">{translate text='Export To Excel' isPublicFacing=true}</a>
			{if !empty($loggedIn) && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
			{/if}
			{if !empty($loggedIn) && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')">{translate text='Add To Browse' isPublicFacing=true}</a>
			{/if}
		</div>
	{/if}
</div>
