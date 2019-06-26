{if $searchId}
	<li>{translate text="Catalog Search"} <span class="divider">&raquo;</span> <em>{$lookfor|escape:"html"}</em> <span class="divider">&raquo;</span></li>
{elseif $pageTitleShort!=""}
	<li>{$pageTitleShort} <span class="divider">&raquo;</span></li>
{/if}
{if !empty($recordCount)}
	{if $displayMode == 'covers'}
		There are {$recordCount|number_format} total results.
	{else}
		{translate text="Showing %1% - %2% of %3%" 1=$recordStart 2=$recordEnd 3=$recordCount}
	{/if}
{/if}
