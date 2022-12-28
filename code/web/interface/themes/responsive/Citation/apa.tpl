{if !empty($apaDetails.authors)}{$apaDetails.authors|escape} {/if}
{if !empty($apaDetails.year)}({$apaDetails.year|escape}). {/if}
<span style="font-style:italic;">{$apaDetails.title|escape}</span> 
{if !empty($apaDetails.edition)}({$apaDetails.edition|escape}){/if}.
{if !empty($apaDetails.publisher)}{$apaDetails.publisher|escape}.{/if}
