{* Listing Options *}
<div class="row">
	{if $recordCount}
		{translate text="Showing"}
		<b>{$recordStart}</b> - <b>{$recordEnd}</b>
		{* total record count is not currently reliable due to Solr facet paging
					 limitations -- for now, displaying it is disabled.
				{translate text='of'} <b>{$recordCount}</b>
				 *}
		{translate text='for search'} <b>'{$lookfor|escape}'</b>
	{/if}

	<div class="pull-right">
		{translate text='Sort'}
		<select name="sort" onchange="document.location.href = this.options[this.selectedIndex].value;">
		{foreach from=$sortList item=sortData key=sortLabel}
			<option value="{$sortData.sortUrl|escape}"{if $sortData.selected} selected{/if}>{translate text=$sortData.desc}</option>
		{/foreach}
		</select>
	</div>
</div>

{* End Listing Options *}
{foreach from=$recordSet item=record name="recordLoop"}
	{if ($smarty.foreach.recordLoop.iteration % 2) == 0}
	<div class="result row alt record{$smarty.foreach.recordLoop.iteration}">
	{else}
	<div class="result row record{$smarty.foreach.recordLoop.iteration}">
	{/if}

		<div class="col-md-10">
			<a href='{$path}/Author/Home?author="{$record.0|escape:"url"}"'>{$record.0|escape:"html"}</a>
		</div>
		<div class="col-md-2">
			{$record.1} title{if $record.1 > 1}s{/if}
		</div>
	</div>
{/foreach}

{if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
