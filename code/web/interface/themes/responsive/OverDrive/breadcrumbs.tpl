{if $lastSearch}
&nbsp;<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
&nbsp;{if $recordDriver}
	<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}">{$recordDriver->getTitle()|removeTrailingPunctuation|truncate:30:"..."|escape}</a> <span class="divider">&raquo;</span>
&nbsp;<em>{implode subject=$recordDriver->getFormatCategory() glue=", "}</em>
	<span class="divider">&raquo;</span>
{else}
	{if $pageTitleShort}
		<em>{$pageTitleShort}</em> <span class="divider">&raquo;</span>
	{/if}
{/if}