{if $lastSearch}
	<em><a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span></em>
{/if}
{if $pageTitleShort}
	<em>{$pageTitleShort}</em> <span class="divider">&raquo;</span>
{/if}
