<ul class="list-unstyled adv-facet-list">
	{foreach from=$facetSearchResults item=thisFacet}
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
