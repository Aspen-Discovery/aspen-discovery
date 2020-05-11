{strip}
<h1>{$title}</h1>
{if $showReturnToList}
	<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=list'>{translate text="Return to List"}</a>
{/if}
<a class="btn btn-default" href='/{$module}/{$toolName}?objectAction=edit&id={$id}'>{translate text="Edit"}</a>

{if count($objectHistory) > 0}
	<table class="table table-striped table-sticky">
		<thead>
			<tr>
				<th>Property Name</th>
				<th>Old Value</th>
				<th>New Value</th>
				<th>Changed By</th>
				<th>Change Date</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$objectHistory item=historyEntry}
				<tr>
					<td>{$historyEntry->propertyName}</td>
					<td>{$historyEntry->oldValue}</td>
					<td>{$historyEntry->newValue}</td>
					<td>{$historyEntry->getChangedByName()}</td>
					<td>{$historyEntry->changeDate|date_format:"%D %T"}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{else}
	<div>No changes have been recorded for this object.</div>
{/if}
{/strip}