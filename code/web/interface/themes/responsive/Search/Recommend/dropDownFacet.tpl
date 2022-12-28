<select class="facetDropDown form-control" onchange="AspenDiscovery.Searches.changeDropDownFacet('facetDropDown-{$title|escapeCSS}')" id="facetDropDown-{$title|escapeCSS}">
	{if empty($cluster.defaultValue)}
		<option selected="selected">Choose {$cluster.label}</option>
	{else}
		<option {if empty($cluster.hasSelectedOption)}selected="selected"{/if}>{$cluster.defaultValue}</option>
	{/if}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		<option data-destination="{$thisFacet.url}" data-label="{$thisFacet.display|escape}" {if !empty($thisFacet.isApplied)}selected{/if}>{$thisFacet.display|escape}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}{/if}</option>
	{/foreach}
</select>