{if !empty($mlaDetails.authors)}{$mlaDetails.authors|escape}. {/if}
<span style="font-style: italic;">{$mlaDetails.title|escape}</span> 
{if !empty($mlaDetails.edition)}{$mlaDetails.edition|escape}, {/if}
{if !empty($mlaDetails.publisher)}{$mlaDetails.publisher|escape}, {/if}
{if !empty($mlaDetails.year)}{$mlaDetails.year|escape}. {/if}