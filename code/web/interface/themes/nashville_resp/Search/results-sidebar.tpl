{strip}
	{include file="login-sidebar.tpl"}

	{*{if $recordCount || $sideRecommendations}*}
	{if $sideRecommendations} {* Since Nashville sorting is moved out of sidebar, don't check recordCount. plb 1-6-2016 *}
		<div id="refineSearch">
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
{/strip}