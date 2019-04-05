{if $lastSearch}
<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
{if $breadcrumbText}
<em>{$breadcrumbText|truncate:30:"..."|escape}</em> <span class="divider">&raquo;</span>
{/if}

