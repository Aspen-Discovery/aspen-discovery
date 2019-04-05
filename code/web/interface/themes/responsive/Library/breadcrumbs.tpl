{if $lastSearch}
	<li><a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span></li>
{/if}
{if $breadcrumbText}
	<li><em>{$breadcrumbText|truncate:30:"..."|escape}</em> <span class="divider">&raquo;</span></li>
{/if}

