{if $lastSearch}
&nbsp;<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
{if $pageTitleShort}
	<em>{$pageTitleShort}</em> <span class="divider">&raquo;</span>
{/if}
{if !empty($recordCount)}
    {translate text="Showing %1% - %2% of %3%" 1=$recordStart 2=$recordEnd 3=$recordCount}
{/if}
