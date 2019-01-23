{strip}
	<h2 id="pageTitle">Archive Usage By Library</h2>
	<div class='adminTableRegion'>
		<table class="adminTable table table-striped table-condensed smallText" id="adminTable">
			<thead>
			<tr>
				<th><label title='Library'>Library</label></th>
				<th><label title='Num Objects'>Num Objects</label></th>
				<th><label title='Num in DPLA'>Num in DPLA</label></th>
				<th><label title='Disk Space'>Disk Space Used</label></th>
			</tr>
			</thead>
			<tbody>
				{foreach from=$usageByNamespace item=libraryData}
					<tr class='{cycle values="odd,even"}'>
						<td>{$libraryData.displayName}</td>
						<td>{$libraryData.numObjects}</td>
						<td>{$libraryData.numDpla}</td>
						<td>{$libraryData.driveSpace}</td>
					</tr>
				{/foreach}
			</tbody>
			<tfoot>
				<tr>
					<td><strong></strong></td>
					<td><strong>{$totalObjects}</strong></td>
					<td><strong>{$totalDpla}</strong></td>
					<td><strong>{$totalDriveSpace} GB</strong></td>
				</tr>
			</tfoot>
		</table>
	</div>
{/strip}

{if isset($usageByNamespace) && is_array($usageByNamespace) && count($usageByNamespace) > 5}
	<script type="text/javascript">
		{literal}
		$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
		{/literal}
	</script>
{/if}