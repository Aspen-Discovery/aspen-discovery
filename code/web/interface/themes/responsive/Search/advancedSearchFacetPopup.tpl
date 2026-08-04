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
		<div class="container-12">
			<div class="row moreFacetPopup">
				{foreach from=$topResults item=thisFacet}
					{strip}
					<div class="checkboxFacet col-tn-12">
						<label>
							<input type="checkbox" class="advFacetCheckbox"
								data-filter="{$thisFacet.filter|escape:'html'}"
								data-display="{$thisFacet.display|escape:'html'}"
								data-facet="{$facetName|escape:'html'}">
							&nbsp;{$thisFacet.display}
						</label>
					</div>
					{/strip}
				{/foreach}
			</div>
		</div>
	</div>
</div>
