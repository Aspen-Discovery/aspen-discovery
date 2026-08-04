{strip}
<div id="main-content" class="col-tn-12 col-xs-12">
	<h1>{translate text="Collection Reports" isAdminFacing=true}</h1>

	<h2>{translate text="Record Counts by Source" isAdminFacing=true}</h2>

	<table id="sourceCount" class="table table-bordered" aria-label="Record Counds by Source">
		<thead>
		<tr>
			<th>{translate text="Records Indexed" isAdminFacing=true}</th>
			<th>{translate text="Number Active" isAdminFacing=true}</th>
			<th>{translate text="Number Deleted" isAdminFacing=true}</th>
			<th>{translate text="Number Suppressed" isAdminFacing=true}</th>
		</tr>
		</thead>
		<tbody>
		{foreach $tableData as $row}
			<tr>
				<td>{$row.rowName}</td>
				<td>{if isset($row.activeCount)}{$row.activeCount|number_format}{else}n/a{/if}</td>
				<td>{if isset($row.deletedCount)}{$row.deletedCount|number_format}{else}n/a{/if}</td>
				<td>{if isset($row.suppressedCount)}{$row.suppressedCount|number_format}{else}n/a{/if}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	<h2>{translate text="Record Counts by Format" isAdminFacing=true}</h2>
	<table id="formatCount" class="table table-bordered" aria-label="Record Counts by Format">
		<thead>
		<tr>
			<th>{translate text="Format" isAdminFacing=true}</th>
			<th>{translate text="Source" isAdminFacing=true}</th>
			<th>{translate text="Number of Records" isAdminFacing=true}</th>
		</tr>
		</thead>
		<tbody>
		{foreach $formatTableData as $row}
			<tr>
				<td>{$row.format}</td>
				<td>{$row.source}</td>
				<td>{$row.numRecords|number_format}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
</div>
{/strip}
