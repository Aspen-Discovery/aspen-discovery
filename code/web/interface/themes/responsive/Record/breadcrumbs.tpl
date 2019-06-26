{if $lastSearch}
&nbsp;<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Catalog Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
&nbsp;
{if !$lastSearch}Catalog {/if}
{if $recordDriver}<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}">{$recordDriver->getShortTitle()|removeTrailingPunctuation|truncate:30:"..."|escape}</a> <span class="divider">&raquo;</span>
&nbsp;<em>{if $recordDriver->getFormats()}{implode subject=$recordDriver->getFormats() glue=", "}{/if}</em> <span class="divider">&raquo;</span>
{/if}