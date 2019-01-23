{if $searchId}
	<li>{translate text="Search"}: {$lookfor|capitalize|escape:"html"} <span class="divider">&raquo;</span></li>
{elseif $pageTemplate=="newitem.tpl" || $pageTemplate=="newitem-list.tpl"}
	<li>{translate text="New Items"} <span class="divider">&raquo;</span></li>
{elseif $subTemplate}
	<li>{translate text=$subTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{elseif $pageTemplate!=""}
	<li>{translate text=$pageTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{/if}

{* Moved result-head info here from list.tpl - JE 6/18/15 *}
    {if $recordCount}
			{if $displayMode == 'covers'}
				There are {$recordCount|number_format} total results.
			{else}
				{translate text="Showing"}
				{$recordStart} - {$recordEnd}
				{translate text='of'} {$recordCount|number_format}
			{/if}
		{/if}
		{*<span class="hidden-phone">
			 {translate text='query time'}: {$qtime}s
		</span>*}

{if !$productionServer}
<div class="hidden-phone">
	 {translate text='query time'}: {$qtime}s
</div>
{/if}