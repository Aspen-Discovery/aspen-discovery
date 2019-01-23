{if $citeDetails.authors}{$citeDetails.authors|escape} {/if}
<span style="font-style:italic;">{$citeDetails.title|escape} </span> 
{if $citeDetails.edition}{$citeDetails.edition|escape} {/if}
{if $citeDetails.publisher}{$citeDetails.publisher|escape}; {/if}
{if $citeDetails.year}{$citeDetails.year|escape}. {/if}
