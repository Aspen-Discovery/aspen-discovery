{if $searchId}
	<li>Genealogy {translate text="Search"} <span class="divider">{if !empty($lookfor)}&raquo;</span> {$lookfor|capitalize|escape:"html"}{/if} <span class="divider">&raquo;</span></li>
{elseif $pageTemplate!=""}
	<li>{translate text=$pageTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{/if}
{if $recordCount}
	{translate text="Showing"}
	{$recordStart} - {$recordEnd}
	{translate text='of'} {$recordCount|number_format}
{/if}
