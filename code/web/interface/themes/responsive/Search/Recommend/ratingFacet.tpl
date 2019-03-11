{foreach from=$ratingLabels item=curLabel}
	{assign var=thisFacet value=$cluster.list.$curLabel}
	{if $thisFacet.isApplied}
		{if $curLabel == 'Unrated'}
			<div class="facetValue">{$thisFacet.value|escape} <img src="{$path}/images/silk/tick.png" alt="Selected"/> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></div>
		{else}
			<div class="facetValue"><img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate} &amp; Up" title="{$curLabel|translate} &amp; up"/> <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></div>
		{/if}
	{else}
		{if $curLabel == 'Unrated'}
			<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if} ({$thisFacet.count})</div>
		{else}
			<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}<img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate} &amp; Up" title="{$curLabel|translate} &amp; Up"/>{if $thisFacet.url !=null}</a>{/if} ({if $thisFacet.count}{$thisFacet.count}{else}0{/if})</div>
		{/if}
	{/if}
{/foreach}