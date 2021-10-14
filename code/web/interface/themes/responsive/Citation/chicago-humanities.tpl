{if $citeDetails.authors}{$citeDetails.authors|escape}. {/if}
<span style="font-style:italic;">{$citeDetails.title|escape} </span> 
{if $citeDetails.publisher}{$citeDetails.publisher|escape}{/if}
{if $citeDetails.year}, {$citeDetails.year|escape}{/if}. 
