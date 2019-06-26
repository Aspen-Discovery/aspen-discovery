
{if $lastSearch}<li><a href="{$lastSearch|escape}">{translate text="Search"}</a> <span class="divider">&raquo;</span></li>{/if}
 
{if $pageTemplate=="home.tpl"}<li><em>{$author.0|escape}, {$author.1|escape}</em> <span class="divider">&raquo;</span></li>{/if}

{if $pageTemplate=="list.tpl"}<li><em>{translate text="Author Results for"} {$lookfor|escape}</em> <span class="divider">&raquo;</span></li>{/if}

{if !empty($recordCount)}
	{if $displayMode == 'covers'}
		{translate text="There are %1% total results." 1=$recordCount|number_format}
	{else}
		{translate text="Showing %1% - %2% of %3%" 1=$recordStart 2=$recordEnd 3=$recordCount|number_format}
	{/if}
{/if}