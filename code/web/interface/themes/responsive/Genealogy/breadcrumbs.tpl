{if $searchId}
	<li>Genealogy {translate text="Search"} <span class="divider">{if !empty($lookfor)}&raquo;</span> {$lookfor|escape:"html"}{/if} <span class="divider">&raquo;</span></li>
{elseif $pageTemplate!=""}
	<li>{translate text=$pageTemplate|replace:'.tpl':''} <span class="divider">&raquo;</span></li>
{/if}
{if !empty($recordCount)}
	{translate text="Showing"}
	{$recordStart} - {$recordEnd}
	{translate text='of'} {$recordCount|number_format}
{/if}
