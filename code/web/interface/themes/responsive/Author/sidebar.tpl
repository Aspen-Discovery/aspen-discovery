{strip}
	{if $recordCount || $sideRecommendations}
		<div id="refineSearch">
			<div id="similar-authors-placeholder-sidebar"></div>

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