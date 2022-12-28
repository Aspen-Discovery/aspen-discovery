{foreach from=$recordSet item=record name="recordLoop"}
  <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt{/if} record{$smarty.foreach.recordLoop.iteration}">
    {* This is raw HTML -- do not escape it: *}
    {$record}
  </div>
{/foreach}

{if !empty($userIsAdmin)}
<a href='/Admin/People?objectAction=addNew' class='btn btn-sm btn-info'>{translate text="Add someone new" isPublicFacing=true}</a>
{/if}
