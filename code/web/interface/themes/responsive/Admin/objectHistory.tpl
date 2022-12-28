{strip}
<h1>{$title}</h1>
{if !empty($showReturnToList)}
	<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list'><i class="fas fa-arrow-alt-circle-left"></i> {translate text="Return to List" isAdminFacing=true}</a>
{/if}
<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=edit&id={$id}'>{translate text="Edit" isAdminFacing=true}</a>

{if count($objectHistory) > 0}
	<table class="table table-striped table-sticky">
		<thead>
			<tr>
				<th>{translate text="Property Name" isAdminFacing=true}</th>
				<th>{translate text="Old Value" isAdminFacing=true}</th>
				<th>{translate text="New Value" isAdminFacing=true}</th>
				<th>{translate text="Changed By" isAdminFacing=true}</th>
				<th>{translate text="Change Date" isAdminFacing=true}</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$objectHistory item=historyEntry}
				<tr>
					<td>{translate text=$historyEntry->propertyName isAdminFacing=true}</td>
					<td>{$historyEntry->oldValue}</td>
					<td>{$historyEntry->newValue}</td>
					<td>{$historyEntry->getChangedByName()}</td>
					<td>{$historyEntry->changeDate|date_format:"%D %T"}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{else}
	<div>{translate text="No changes have been recorded for this object." isAdminFacing=true}</div>
{/if}
{/strip}