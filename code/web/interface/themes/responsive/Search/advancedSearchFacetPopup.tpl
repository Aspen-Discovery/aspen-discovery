<form role="form" id="advancedSearchFacetValuesForm">
	<input type="hidden" name="facetName" id="advFacetName" value="{$facetName}">
	<div class="form-group">
		<label for="advFacetSearchTerm">{translate text="Search %1%" isPublicFacing=true 1=$facetTitlePlural}</label>
		<div class="input-group input-group-sm">
			<input type="text" name="facetSearchTerm" id="advFacetSearchTerm" class="form-control" onkeydown="AspenDiscovery.Searches.searchAdvancedFacetValuesKeyDown(event)"/>
			<span class="btn btn-sm btn-primary input-group-addon" onclick="return AspenDiscovery.Searches.searchAdvancedFacetValues();">{translate text="Search" isPublicFacing=true}</span>
		</div>
	</div>
</form>
<div>
	<div id="advFacetSearchResultsLoading" class="alert alert-info" style="display: none">
		{translate text="Loading results" isPublicFacing=true}
	</div>
	<div id="advFacetSearchResultsPopularHelp" class="small text-muted" style="margin-bottom: 6px;">
		{translate text="Or select from these popular %1%." 1=$facetTitlePlural isPublicFacing=true translateParameters=true}
	</div>
	<div id="advFacetSearchResults">
		<ul class="list-unstyled adv-facet-list">
			{foreach from=$topResults item=thisFacet}
				<li class="adv-facet-item"
					data-filter="{$thisFacet.filter|escape:'html'}"
					data-display="{$thisFacet.display|escape:'html'}"
					data-facet="{$facetName|escape:'html'}"
					onclick="AspenDiscovery.Searches.setAdvancedSearchFacetValue(this); return false;"
					style="cursor: pointer;">
					{$thisFacet.display}
				</li>
			{/foreach}
		</ul>
	</div>
	<style>
		{literal}
		.adv-facet-list { column-count: 2; column-gap: 0; margin: 0; }
		.adv-facet-item { break-inside: avoid; padding: 4px 8px; }
		.adv-facet-item:nth-child(odd)  { background-color: #f9f9f9; }
		.adv-facet-item:nth-child(even) { background-color: #ffffff; }
		.adv-facet-item:hover { background-color: #e8f0fe; }
		{/literal}
	</style>
</div>
