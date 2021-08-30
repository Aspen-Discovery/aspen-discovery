{strip}
	<h1>{translate text="No Results Found" isPublicFacing=true}</h1>

	<p class="alert alert-info">
		{if (empty($lookfor))}
			{translate text="Your search - <b>&lt;empty&gt;</b> - did not match any resources." isPublicFacing=true}
		{else}
			{translate text="Your search - <b>%1%</b> - did not match any resources." 1=$lookfor|escape:html isPublicFacing=true}
		{/if}
	</p>


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

        {include file="Search/searchSuggestions.tpl"}

        {include file="Search/spellingSuggestions.tpl"}

		{if $showExploreMoreBar}
			<div id="explore-more-bar-placeholder"></div>
			<script type="text/javascript">
				$(document).ready(
					function () {ldelim}
						AspenDiscovery.Searches.loadExploreMoreBar('lists', '{$exploreMoreSearchTerm|escape:"html"}');
					{rdelim}
				);
			</script>
		{/if}

		{if $showSearchTools || ($loggedIn && count($userPermissions) > 0)}
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
{/strip}