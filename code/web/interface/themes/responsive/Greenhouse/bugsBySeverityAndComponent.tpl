{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Active Bugs by Severity" isAdminFacing=true}</h1>

		<table id="adminTable" class="table table-striped table-bordered">
			<thead>
			<tr>
				<th>{translate text="Component" isAdminFacing=true}</th>
				<th>{translate text="Low" isAdminFacing=true}</th>
				<th>{translate text="Medium" isAdminFacing=true}</th>
				<th>{translate text="High" isAdminFacing=true}</th>
				<th>{translate text="Critical" isAdminFacing=true}</th>
				<th>{translate text="Total" isAdminFacing=true}</th>
			</tr>
			</thead>
			<tbody>
				{foreach from=$ticketsByComponent item=componentTicketInfo}
					<tr>
						<td>{$componentTicketInfo.component}</td>
						<td>{$componentTicketInfo.low}</td>
						<td>{$componentTicketInfo.medium}</td>
						<td>{$componentTicketInfo.high}</td>
						<td>{$componentTicketInfo.critical}</td>
						<td>{$componentTicketInfo.Total}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{/strip}
<script type="text/javascript">
	{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
	{/literal}
</script>