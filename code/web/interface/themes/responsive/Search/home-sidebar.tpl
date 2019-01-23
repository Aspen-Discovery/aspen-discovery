{strip}
{* Search box *}
	{if !$horizontalSearchBar}
		{include file="Search/searchbox-home.tpl"}
	{/if}

	{include file="login-sidebar.tpl"}

	{if $loggedIn}
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{if $showExploreMore}
		<div id="explore-more-header" class="row">Explore More</div>
		<div id="explore-more-body" class="row">
			<div id="loadingExploreMore">
				<img src="{img filename=loading.gif}" alt="loading...">
				Loading...
			</div>
		</div>
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}