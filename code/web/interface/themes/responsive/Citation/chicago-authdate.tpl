{if !empty($citeDetails.authors)}{$citeDetails.authors|escape}. {/if}
{if !empty($citeDetails.year)}{$citeDetails.year|escape}. {/if}
<span style="font-style:italic;">{$citeDetails.title|escape}</span>.
{if !empty($citeDetails.publisher)}{$citeDetails.publisher|escape}{/if}.