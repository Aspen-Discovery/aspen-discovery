{foreach from=$ratingLabels item=curLabel}
	{assign var=thisFacet value=$cluster.list.$curLabel}
	{if $thisFacet.isApplied}
		{if $curLabel == 'Unrated'}
			<div class="facetValue">{$thisFacet.value|escape} <img src="/images/silk/tick.png" alt="{translate text=Selected inAttribute=true isPublicFacing=true}"/> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">({translate text=remove})</a></div>
		{else}
			<div class="facetValue"><img src="/images/{$curLabel}.png" alt="{translate text=$curLabel inAttribute=true isPublicFacing=true} &amp; Up" title="{translate text=$curLabel isPublicFacing=true inAttribute=true} &amp; up"/> <img src="/images/silk/tick.png" alt="{translate text=Selected inAttribute=true isPublicFacing=true}" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">({translate text=remove isPublicFacing=true})</a></div>
		{/if}
	{else}
		{if $curLabel == 'Unrated'}
			<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{translate text=$thisFacet.display isPublicFacing=true}{if $thisFacet.url !=null}</a>{/if}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)} ({$thisFacet.count}){/if}</div>
		{else}
			<div class="facetValue">{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}<img src="/images/{$curLabel}.png" alt="{translate text=$curLabel inAttribute=true isPublicFacing=true} &amp; Up" title="{translate text=$curLabel inAttribute=true isPublicFacing=true} &amp; Up"/>{if $thisFacet.url !=null}</a>{/if}{if $facetCountsToShow == 1 || ($facetCountsToShow == 2 && !$thisFacet.countIsApproximate)} ({if $thisFacet.count}{$thisFacet.count}{else}0{/if}){/if}</div>
		{/if}
	{/if}
{/foreach}