{strip}
	{include file="login-sidebar.tpl"}

	{if $recordCount || $sideRecommendations}
		<div id="refineSearch">
			<div id="similar-authors-placeholder-sidebar"></div>

			{* Narrow Results *}
			{if $sideRecommendations}
				<div class="row">
					{foreach from=$sideRecommendations item="recommendations"}
						{include file=$recommendations}
					{/foreach}
				</div>
			{/if}
		</div>
	{/if}

	{if $loggedIn}
		{* Account Menu *}
		{include file="MyAccount/menu.tpl"}
	{/if}

	{include file="library-sidebar.tpl"}
{/strip}