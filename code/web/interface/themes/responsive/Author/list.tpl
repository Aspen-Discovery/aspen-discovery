{* Listing Options *}
<div class="row">

	{* User's viewing mode toggle switch *}
	{include file="Search/results-displayMode-toggle.tpl"}

	<div class="clearer"></div>
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
