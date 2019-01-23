{if $cluster.showMoreFacetPopup}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		{if $thisFacet.isApplied}
			<div class="facetValue"><img src="{$path}/images/silk/tick.png" alt="Selected" /> {$thisFacet.display|escape} <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.display|escape}');">(remove)</a></div>
		{else}
			<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count|number_format}){/if}</div>
		{/if}
	{/foreach}
	{* Show more list *}
	<div class="facetValue" id="more{$title}"><a href="#" onclick="VuFind.ResultsList.moreFacetPopup('More {$cluster.label}{if substr($cluster.label, -1) != 's'}s{/if}', '{$title}'); return false;">{translate text='more'} ...</a></div>
	<div id="moreFacetPopup_{$title}" style="display:none">
		<p>Please select one of the items below to narrow your search by {$cluster.label}.</p>
		<div class="container-12">
			<div class="row moreFacetPopup">
				{foreach from=$cluster.sortedList item=thisFacet name="narrowLoop"}
					<div>{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count|number_format}){/if}</div>
				{/foreach}
			</div>
		</div>
	</div>
{else}
	{foreach from=$cluster.list item=thisFacet name="narrowLoop"}
		{if $smarty.foreach.narrowLoop.iteration == ($cluster.valuesToShow + 1)}
		{* Show More link*}
			<div class="facetValue" id="more{$title}"><a href="#" onclick="VuFind.ResultsList.moreFacets('{$title}'); return false;">{translate text='more'} ...</a></div>
		{* Start div for hidden content*}
			<div class="narrowGroupHidden" id="narrowGroupHidden_{$title}" style="display:none">
		{/if}
		{if $thisFacet.isApplied}
			<div class="facetValue"><img src="{$path}/images/silk/tick.png" alt="Selected" /> {$thisFacet.display|escape} <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink" onclick="trackEvent('Remove Facet', '{$cluster.label}', '{$thisFacet.display|escape}');">(remove)</a></div>
		{else}
			<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}{if $thisFacet.count != ''}&nbsp;({$thisFacet.count|number_format}){/if}</div>
		{/if}
	{/foreach}
	{if $smarty.foreach.narrowLoop.total > $cluster.valuesToShow}
		<div class="facetValue">
			<a href="#" onclick="VuFind.ResultsList.lessFacets('{$title}'); return false;">{translate text='less'} ...</a>
		</div>
		</div>{* closes hidden div *}
	{/if}
{/if}