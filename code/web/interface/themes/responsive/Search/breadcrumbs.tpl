{if $searchId}
	<li>{translate text="Catalog Search"} <span class="divider">&raquo;</span> <em>{$lookfor|capitalize|escape:"html"}</em> <span class="divider">&raquo;</span></li>
{elseif $pageTemplate!=""}
	<li>{translate text=$pageTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{/if}
{if !empty($recordCount)}
	{if $displayMode == 'covers'}
		There are {$recordCount|number_format} total results.
	{else}
		{translate text="Showing %1% - %2% of %3%" 1=$recordStart 2=$recordEnd 3=$recordCount}
	{/if}
{/if}
