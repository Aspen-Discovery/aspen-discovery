{if $lastSearch}
<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
{if $breadcrumbText}
<em>{$breadcrumbText|removeTrailingPunctuation|truncate:30:"..."|escape}</em> <span class="divider">&raquo;</span>
{/if}

