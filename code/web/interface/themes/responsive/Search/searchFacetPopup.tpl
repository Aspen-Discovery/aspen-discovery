{if !$isMultiSelect && !empty($appliedFacetValues)}
	<div id="facetSearchResultsAppliedValues">
	    {translate text="The following values are already applied to your search." isPublicFacing=true}
	</div>
	<div class="container-12" id="existingFacetValues">
		<div class="row moreFacetPopup">
			{foreach from=$appliedFacetValues item=$appliedFacet}
				<div class="col-tn-12 standardFacet" style="font-weight: bold">{$appliedFacet.display}</div>
			{/foreach}
		</div>
	</div>
	<hr/>
{/if}
<form role="form" id="searchFacetValuesForm">
	<input type="hidden" name="searchId" id="searchId" value="{$searchId}">
	<input type="hidden" name="facetName" id="facetName" value="{$facetName}">
	<div class="form-group">
		<label for="facetSearchTerm">{translate text="Search %1%" isPublicFacing=true 1=$facetTitlePlural}</label>
		<div class="input-group input-group-sm">
			<input  type="text" name="facetSearchTerm" id="facetSearchTerm" class="form-control" onkeydown="AspenDiscovery.Searches.searchFacetValuesKeyDown(event)"/>
			<span class="btn btn-sm btn-primary input-group-addon" onclick="return AspenDiscovery.Searches.searchFacetValues();">{translate text="Search" isPublicFacing=true}</span>
		</div>
	</div>
</form>
<div>
	<div id="facetSearchResultsLoading" class="alert alert-info" style="display: none">
		{translate text="Loading results" isPublicFacing=true}
	</div>
	<div id="facetSearchResultsPopularHelp">
		{translate text="Or select from these popular %1%." 1=$facetTitlePlural isPublicFacing=true translateParameters=true}
	</div>
	{if $isMultiSelect}
		<form id="searchFacetPopup" onsubmit="return AspenDiscovery.ResultsList.processMultiSelectMoreFacetForm('#searchFacetPopup', '{$facetName}');">
			<div class="container-12" id="facetSearchResults">
				<div class="row moreFacetPopup">
					{if $isMultiSelect}
						{foreach from=$appliedFacetValues item=$thisFacet}
							{strip}
							<div class="checkboxFacet col-tn-12">
								<label>
								<input type="checkbox" class="facetSearchPopupValue" checked name="filter[]" value='{$facetName}:{if empty($thisFacet.value)}(""){else}"{$thisFacet.value|escape:url}"{/if}'>
									&nbsp;
									{$thisFacet.display}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if !empty($thisFacet.count)}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}
								</label>
								</div>
							{/strip}
						{/foreach}
					{/if}
					{foreach from=$topResults item=thisFacet name="narrowLoop"}
						{strip}
							{if !($thisFacet.isApplied)}
								<div class="checkboxFacet col-tn-12">
									<label>
									<input type="checkbox" class="facetSearchPopupValue" {if !empty($thisFacet.isApplied)}checked{/if} name="filter[]" value='{$facetName}:{if empty($thisFacet.value)}(""){else}"{$thisFacet.value|escape:url}"{/if}'>
										&nbsp;
										{$thisFacet.display}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if !empty($thisFacet.count)}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}
									</label>
								</div>
							{/if}
						{/strip}
					{/foreach}
				</div>
			</div>
		</form>
	{else}
		<div class="container-12" id="facetSearchResults">
			<div class="row moreFacetPopup">
				{foreach from=$topResults item=thisFacet name="narrowLoop"}
					{if !($thisFacet.isApplied)}
						<div class="col-tn-12 standardFacet">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display}{if $thisFacet.url !=null}</a>{/if}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if $thisFacet.count != ''}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}</div>
					{/if}
				{/foreach}
			</div>
		</div>
	{/if}
</div>