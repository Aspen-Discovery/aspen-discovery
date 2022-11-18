{if isset($cluster.showMoreFacetPopup) && $cluster.showMoreFacetPopup}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		<div class="facetValue">
			<label for="{$title}_{$thisFacet.value|escapeCSS}">
				<input type="checkbox" {if $thisFacet.isApplied}checked{/if} name="{$title}_{$thisFacet.value|escapeCSS}" id="{$title}_{$thisFacet.value|escapeCSS}" onclick="document.location = '{if $thisFacet.isApplied}{$thisFacet.removalUrl|escape}{else}{$thisFacet.url|escape}{/if}';">
				{$thisFacet.display}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if $thisFacet.count != ''}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}
			</label>
		</div>
	{/foreach}
	{* Show more facet popup list *}
	<div class="facetValue" id="more{$title}"><a href="#" onclick="AspenDiscovery.ResultsList.multiSelectMoreFacetPopup('{translate text='More %1%' isPublicFacing=true 1=$cluster.displayNamePlural translateParameters=true}', '{$title}', '{translate text='Apply Filters' isPublicFacing=true}'); return false;">{translate text='more' isPublicFacing=true} ...</a></div>
	<div id="moreFacetPopup_{$title}" style="display:none">
		<p>{translate text="Please select one of the items below to narrow your search by %1%." 1=$cluster.label isPublicFacing=true}</p>
		<form id="facetPopup_{$title|escapeCSS}" onsubmit="return AspenDiscovery.ResultsList.processMultiSelectMoreFacetForm('#facetPopup_{$title|escapeCSS}', '{$cluster.field_name}');">
			<div class="container-12">
				<div class="row moreFacetPopup">
					{foreach from=$cluster.sortedList item=thisFacet name="narrowLoop"}
						{strip}
						<div class="checkboxFacet col-tn-12">
							<label>
							<input type="checkbox" {if $thisFacet.isApplied}checked{/if} name="filter[]" value='{$cluster.field_name}:{if empty($thisFacet.value)}(""){else}"{$thisFacet.value|escape:url}"{/if}'>
								&nbsp;
								{$thisFacet.display}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if $thisFacet.count != ''}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}
							</label>
						</div>
						{/strip}
					{/foreach}
				</div>
			</div>
		</form>
	</div>
{else}
	{* Simple list with more link to show remaining values (if any) *}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		{if $smarty.foreach.narrowLoop.iteration == ($cluster.valuesToShow + 1)}
		{* Show More link*}
			<div class="facetValue" id="more{$title}"><a href="#" onclick="AspenDiscovery.ResultsList.moreFacets('{$title}'); return false;">{translate text='more' isPublicFacing=true} ...</a></div>
		{* Start div for hidden content*}
			<div class="narrowGroupHidden" id="narrowGroupHidden_{$title}" style="display:none">
		{/if}
		<div class="facetValue">
			<label for="{$title}_{$thisFacet.value|escapeCSS}">
				<input type="checkbox" {if $thisFacet.isApplied}checked{/if} name="{$title}_{$thisFacet.value|escapeCSS}" id="{$title}_{$thisFacet.value|escapeCSS}" onclick="document.location = '{if $thisFacet.isApplied}{$thisFacet.removalUrl|escape}{else}{$thisFacet.url|escape}{/if}';" onkeypress="document.location = '{if $thisFacet.isApplied}{$thisFacet.removalUrl|escape}{else}{$thisFacet.url|escape}{/if}';">
				{$thisFacet.display}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)}{if $thisFacet.count != ''}&nbsp;({if !empty($thisFacet.countIsApproximate)}{/if}{$thisFacet.count|number_format}){/if}{/if}
			</label>
		</div>
	{/foreach}
	{if $smarty.foreach.narrowLoop.total > $cluster.valuesToShow}
		<div class="facetValue">
			<a href="#" onclick="AspenDiscovery.ResultsList.lessFacets('{$title}'); return false;">{translate text='less' isPublicFacing=true} ...</a>
		</div>
		</div>{* closes hidden div *}
	{/if}
{/if}