{if $lastSearch}
&nbsp;<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Return to Search Results"}</a> <span class="divider">&raquo;</span>
{/if}
{if $pageTitleShort}
    <em>{$pageTitleShort}</em> <span class="divider">&raquo;</span>
{/if}
{if !empty($recordCount)}
    {if $displayMode == 'covers'}
        There are {$recordCount|number_format} total results.
    {else}
        {translate text="Showing"}
        {$recordStart} - {$recordEnd}
        {translate text='of'} {$recordCount|number_format}
    {/if}
{/if}