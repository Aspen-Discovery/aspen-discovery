{strip}
	{if $lastSearch}
		<a href="{$lastSearch|escape}#record{$id|escape:"url"}">{translate text="Archive Search Results"}</a> <span class="divider">&raquo;</span>
	{else}
		<a href="/Archive/Home">{translate text="Local Digital Archive"}</a> <span class="divider">&raquo;</span>
	{/if}
	{if $pageTitleShort}
		<em>{$pageTitleShort}</em> <span class="divider">&raquo;</span>
	{/if}
	{if !empty($recordCount)}
		{if $displayMode == 'covers'}
			{translate text="There are %1% total results" 1=$recordCount|number_format}.
		{else}
			{translate text="Showing %1% - %2% of %3%" 1=$recordStart 2=$recordEnd 3=$recordCount|number_format}
		{/if}
	{/if}
{/strip}