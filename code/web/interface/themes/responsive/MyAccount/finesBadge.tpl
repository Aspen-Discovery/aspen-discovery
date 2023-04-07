{strip}
<span class="{if $ilsSummary->totalFines > 0}label label-danger hasFines{else}badge noFines{/if}">{$ilsSummary->totalFines|formatCurrency}</span>
{/strip}