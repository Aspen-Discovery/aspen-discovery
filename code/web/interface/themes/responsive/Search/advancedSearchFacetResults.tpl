<div class="container-12">
	<div class="row moreFacetPopup">
		{foreach from=$facetSearchResults item=thisFacet}
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
