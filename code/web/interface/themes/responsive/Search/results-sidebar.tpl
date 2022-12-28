{strip}
	{if !empty($sideRecommendations)}
		<div id="refineSearch">
			{* Narrow Results *}
			{if !empty($sideRecommendations)}
				<div class="row">
					{foreach from=$sideRecommendations item="recommendations"}
						{include file=$recommendations}
					{/foreach}
				</div>
			{/if}
		</div>
	{/if}
{/strip}