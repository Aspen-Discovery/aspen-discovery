{strip}
	<div id="main-content" class="col-sm-12">
		<h2>Slowness Dashboard</h2>
		<h3>Slow Pages</h3>
		<table id="slowPages" class="table table-striped table-condensed tablesorter">
			<thead>
				<th>Module</th>
				<th>Action</th>
				<th>This Month</th>
				<th>Last Month</th>
			</thead>
			<tbody>
				{foreach from=$slowPages item=slowPage}
					<tr>
						<td>{$slowPage.module}</td>
						<td>{$slowPage.action}</td>
						<td>{if empty($slowPage.this_month)}0{else}{$slowPage.this_month}{/if}</td>
						<td>{if empty($slowPage.last_month)}0{else}{$slowPage.last_month}{/if}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>

		<h3>Slow Asynchronous Requests</h3>
		<table id="slowPages" class="table table-striped table-condensed tablesorter">
			<thead>
			<th>Module</th>
			<th>Action</th>
			<th>Method</th>
			<th>This Month</th>
			<th>Last Month</th>
			</thead>
			<tbody>
			{foreach from=$slowAsyncRequests item=slowRequest}
				<tr>
					<td>{$slowRequest.module}</td>
					<td>{$slowRequest.action}</td>
					<td>{$slowRequest.method}</td>
					<td>{if empty($slowRequest.this_month)}0{else}{$slowRequest.this_month}{/if}</td>
					<td>{if empty($slowRequest.last_month)}0{else}{$slowRequest.last_month}{/if}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>

	</div>
	<script type="text/javascript">
		{literal}
        $("#slowPages").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader'});
		{/literal}
	</script>
{/strip}
