{if $lastsearch}
&nbsp;<a href="{$lastsearch|escape}#record{$id|escape:"url"}">{translate text="Catalog Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
&nbsp;{if !$lastsearch}Catalog {/if}{if $recordDriver}<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}">{$breadcrumbText|truncate:30:"..."|escape}</a> <span class="divider">&raquo;</span>
&nbsp;<em>{if $recordDriver->getFormats()}{implode subject=$recordDriver->getFormats() glue=", "}{/if}</em> <span class="divider">&raquo;</span>
{/if}
