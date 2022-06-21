{strip}
	{if $recordCount || $limitList}
		<div id="refineSearch">
			{* Narrow Results *}
			<div class="row">
				{include file="Search/Recommend/limits.tpl"}
			</div>
		</div>
	{/if}

	{if $sideFacetSet}
		<div id="refineSearch">
			{* Narrow Results *}
			<div class="row">
				{include file="Search/Recommend/SideFacets.tpl"}
			</div>
		</div>
	{/if}
{/strip}