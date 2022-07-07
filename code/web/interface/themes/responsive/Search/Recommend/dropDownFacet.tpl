<select class="facetDropDown form-control" onchange="AspenDiscovery.Searches.changeDropDownFacet('facetDropDown-{$title|escape:css}')" id="facetDropDown-{$title|escape:css}">
	{if empty($cluster.defaultValue)}
		<option selected="selected">Choose {$cluster.label}</option>
	{else}
		<option {if !$cluster.hasSelectedOption}selected="selected"{/if}>{$cluster.defaultValue}</option>
	{/if}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		<option data-destination="{$thisFacet.url}" data-label="{$thisFacet.display|escape}" {if $thisFacet.isApplied}selected{/if}>{$thisFacet.display|escape}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count}){/if}{/if}</option>
	{/foreach}
</select>