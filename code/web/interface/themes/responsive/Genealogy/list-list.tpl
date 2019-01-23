{foreach from=$recordSet item=record name="recordLoop"}
  <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
    {* This is raw HTML -- do not escape it: *}
    {$record}
  </div>
{/foreach}

{if $userIsAdmin}
<a href='{$path}/Admin/People?objectAction=addNew' class='btn btn-sm btn-info'>Add someone new</a>
{/if}
