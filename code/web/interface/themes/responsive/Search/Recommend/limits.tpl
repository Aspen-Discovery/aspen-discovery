{if $limitList}
	<div id="searchLimitContainer">
		<div id="remove-search-label" class="sidebar-label">{translate text='Limit Your Results' isPublicFacing=true}</div>
		{foreach from=$limitList item=limit}
			<div class="facetValue">
				<label for="limit_{$limit.value|escapeCSS}">
					<input type="checkbox" {if $limit.isApplied}checked{/if} name="limit[{$limit.value|escapeCSS}]" id="limit_{$limit.value|escapeCSS}" onclick="document.location = '{if $limit.isApplied}{$limit.removalUrl|escape}{else}{$limit.url|escape}{/if}';">
					{translate text=$limit.display}
				</label>
			</div>
		{/foreach}
	</div>
{/if}