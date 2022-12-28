{if !empty($citeDetails.authors)}{$citeDetails.authors|escape} {/if}
<span style="font-style:italic;">{$citeDetails.title|escape} </span> 
{if !empty($citeDetails.edition)}{$citeDetails.edition|escape} {/if}
{if !empty($citeDetails.publisher)}{$citeDetails.publisher|escape}; {/if}
{if !empty($citeDetails.year)}{$citeDetails.year|escape}. {/if}
