{if $lastSearch}
<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
{if $pageTitleShort}
	<em>{$pageTitleShort}</em> <span class="divider">&raquo;</span>
{/if}

