<div id="searchInfo">
	{* Recommendations *}
	{if !empty($topRecommendations)}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Listing Options *}
	<div class="resultHead">
		<div>
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

			{if !empty($debugTiming)}
				<div id='solrTimingToggle' onclick='$("#solrTiming").toggle()'>{translate text="Show Solr Timing" isAdminFacing=true}</div>
				<div id='solrTiming' style='display:none'>
					<pre>{$debugTiming}</pre>
				</div>
			{/if}

            {include file="Search/spellingSuggestions.tpl"}
		</div>
	</div>
	{* End Listing Options *}

	{if !empty($subpage)}
		{include file=$subpage}
	{else}
		{$pageContent}
	{/if}

	{if !empty($pageLinks.all)}<div class="pagination">{$pageLinks.all}</div>{/if}

	{if !empty($showSearchTools)}
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