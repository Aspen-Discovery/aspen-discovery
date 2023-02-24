<div class="row moreFacetPopup">
	{foreach from=$facetSearchResults item=thisFacet name="narrowLoop"}
		{strip}
			{if $isMultiSelect}
				<div class="checkboxFacet col-tn-12">
					<label>
					<input type="checkbox" {if !empty($thisFacet.isApplied)}checked{/if} name="filter[]" value='{$facetName}:{if empty($thisFacet.value)}(""){else}"{$thisFacet.value|escape:url}"{/if}'>
						&nbsp;
						{$thisFacet.display}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if !empty($thisFacet.count)}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}
					</label>
				</div>
			{else}
				<div class="col-tn-12 standardFacet">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display}{if $thisFacet.url !=null}</a>{/if}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if $thisFacet.count != ''}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}</div>
			{/if}
		{/strip}
	{/foreach}
</div>
