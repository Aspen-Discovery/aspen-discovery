{strip}
<span class="label {if $ilsSummary->totalFines > 0}label-danger hasFines{else}label-default noFines{/if}">{$ilsSummary->totalFines|formatCurrency}</span>
{/strip}