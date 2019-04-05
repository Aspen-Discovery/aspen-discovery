{if $lastSearch}
&nbsp;<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
&nbsp;{if !$lastSearch}Catalog {/if}{if $recordDriver}<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}">{$recordDriver->getTitle()|truncate:30:"..."|escape}</a> <span class="divider">&raquo;</span>
&nbsp;<em>{$groupedWorkDriver->getFormatCategory()}</em>{/if} <span class="divider">&raquo;</span>