{strip}
	<div id="main-content" class="col-sm-12">
		<h2>Slowness Dashboard</h2>
		<h3>Slow Pages</h3>
		<table id="slowPages" class="table table-striped table-condensed tablesorter">
			<thead>
				<tr>
					<th>Module</th>
					<th>Action</th>
					<th>Fast<br>&lt; 0.5 sec</th>
					<th>Acceptable<br>&lt; 1sec</th>
					<th>Slow<br>&lt; 2sec</th>
					<th>Slower<br>&lt; 4sec</th>
					<th>Very Slow<br>&gt;= 4sec</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$slowPages item=slowPage}
					<tr>
						<td>{$slowPage.module}</td>
						<td>{$slowPage.action}</td>
						<td>{if empty($slowPage.this_month_fast)}0{else}{$slowPage.this_month_fast}{/if} / {if empty($slowPage.last_month_fast)}0{else}{$slowPage.last_month_fast}{/if}</td>
						<td>{if empty($slowPage.this_month_acceptable)}0{else}{$slowPage.this_month_acceptable}{/if} / {if empty($slowPage.last_month_acceptable)}0{else}{$slowPage.last_month_acceptable}{/if}</td>
						<td>{if empty($slowPage.this_month_slow)}0{else}{$slowPage.this_month_slow}{/if} / {if empty($slowPage.last_month_slow)}0{else}{$slowPage.last_month_slow}{/if}</td>
						<td>{if empty($slowPage.this_month_slower)}0{else}{$slowPage.this_month_slower}{/if} / {if empty($slowPage.last_month_slower)}0{else}{$slowPage.last_month_slower}{/if}</td>
						<td>{if empty($slowPage.this_month_very_slow)}0{else}{$slowPage.this_month_very_slow}{/if} / {if empty($slowPage.last_month_very_slow)}0{else}{$slowPage.last_month_very_slow}{/if}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>

		<h3>Slow Asynchronous Requests</h3>
		<table id="slowPages" class="table table-striped table-condensed tablesorter">
			<thead>
				<tr>
					<th>Module</th>
					<th>Action</th>
					<th>Method</th>
					<th>Fast<br>&lt; 0.5 sec</th>
					<th>Acceptable<br>&lt; 1sec</th>
					<th>Slow<br>&lt; 2sec</th>
					<th>Slower<br>&lt; 4sec</th>
					<th>Very Slow<br>&gt;= 4sec</th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$slowAsyncRequests item=slowRequest}
				<tr>
					<td>{$slowRequest.module}</td>
					<td>{$slowRequest.action}</td>
					<td>{$slowRequest.method}</td>
					<td>{if empty($slowRequest.this_month_fast)}0{else}{$slowRequest.this_month_fast}{/if} / {if empty($slowRequest.last_month_fast)}0{else}{$slowRequest.last_month_fast}{/if}</td>
					<td>{if empty($slowRequest.this_month_acceptable)}0{else}{$slowRequest.this_month_acceptable}{/if} / {if empty($slowRequest.last_month_acceptable)}0{else}{$slowRequest.last_month_acceptable}{/if}</td>
					<td>{if empty($slowRequest.this_month_slow)}0{else}{$slowRequest.this_month_slow}{/if} / {if empty($slowRequest.last_month_slow)}0{else}{$slowRequest.last_month_slow}{/if}</td>
					<td>{if empty($slowRequest.this_month_slower)}0{else}{$slowRequest.this_month_slower}{/if} / {if empty($slowRequest.last_month_slower)}0{else}{$slowRequest.last_month_slower}{/if}</td>
					<td>{if empty($slowRequest.this_month_very_slow)}0{else}{$slowRequest.this_month_very_slow}{/if} / {if empty($slowRequest.last_month_very_slow)}0{else}{$slowRequest.last_month_very_slow}{/if}</td>
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
