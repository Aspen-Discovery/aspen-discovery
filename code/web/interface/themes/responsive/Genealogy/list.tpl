<div id="searchInfo">
	{* Recommendations *}
	{if $topRecommendations}
		{foreach from=$topRecommendations item="recommendations"}
			{include file=$recommendations}
		{/foreach}
	{/if}

	{* Listing Options *}
	<div class="resultHead">
		<div>
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

			{if !empty($debugTiming)}
				<div id='solrTimingToggle' onclick='$("#solrTiming").toggle()'>{translate text="Show Solr Timing"}</div>
				<div id='solrTiming' style='display:none'>
					<pre>{$debugTiming}</pre>
				</div>
			{/if}

			{if $spellingSuggestions}
				<br /><br /><div class="correction"><strong>{translate text='spell_suggest'}</strong>:<br/>
				{foreach from=$spellingSuggestions item=details key=term name=termLoop}
					{$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$data.phrase|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
				{/foreach}
				</div>
			{/if}
		</div>
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
			<strong>{translate text='Search Tools'} </strong>
			<a href="{$rssLink|escape}">{translate text='Get RSS Feed'}</a>
			<a href="#" onclick="return AspenDiscovery.Account.ajaxLightbox('/Search/AJAX?method=getEmailForm', true); ">{translate text='Email this Search' isPublicFacing=true}</a>
			{if $savedSearch}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text="Remove Saved Search" isPublicFacing=true}</a>
			{else}
				<a href="#" onclick="return AspenDiscovery.Account.saveSearch('{$searchId}')">{translate text='Save Search' isPublicFacing=true}</a>
			{/if}
			<a href="{$excelLink|escape}">{translate text='Export To Excel' isPublicFacing=true}</a>
			{if $loggedIn && (in_array('Administer All Collection Spotlights', $userPermissions) || in_array('Administer Library Collection Spotlights', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.CollectionSpotlights.createSpotlightFromSearch('{$searchId}')">{translate text='Create Spotlight' isAdminFacing=true}</a>
			{/if}
			{if $loggedIn && (in_array('Administer All Browse Categories', $userPermissions) || in_array('Administer Library Browse Categories', $userPermissions))}
				<a href="#" onclick="return AspenDiscovery.Browse.addToHomePage('{$searchId}')">{translate text='Add To Browse' isPublicFacing=true}</a>
			{/if}
		</div>
	{/if}
</div>