{strip}
	<div id="main-content" class="col-sm-12">
		<h1>Slowness Dashboard</h1>
		<h2>Slow Pages</h2>
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
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$slowPages item=slowPage}
					<tr>
						<td>{$slowPage.module}</td>
						<td>{$slowPage.action}</td>
						<td {if $slowPage.average == 1}style="background-color: lightseagreen"{/if}>
							{if $slowPage.average == 1}<strong>{/if}
								{if empty($slowPage.this_month_fast)}0{else}{$slowPage.this_month_fast}{/if} / {if empty($slowPage.last_month_fast)}0{else}{$slowPage.last_month_fast}{/if}
							{if $slowPage.average == 1}</strong>{/if}
						</td>
						<td {if $slowPage.average == 2}style="background-color: lightgreen"{/if}>
							{if $slowPage.average == 2}<strong>{/if}
								{if empty($slowPage.this_month_acceptable)}0{else}{$slowPage.this_month_acceptable}{/if} / {if empty($slowPage.last_month_acceptable)}0{else}{$slowPage.last_month_acceptable}{/if}
							{if $slowPage.average == 2}</strong>{/if}
						</td>
						<td {if $slowPage.average == 3}style="background-color: lightgoldenrodyellow"{/if}>
							{if $slowPage.average == 3}<strong>{/if}
								{if empty($slowPage.this_month_slow)}0{else}{$slowPage.this_month_slow}{/if} / {if empty($slowPage.last_month_slow)}0{else}{$slowPage.last_month_slow}{/if}
							{if $slowPage.average == 3}</strong>{/if}
						</td>
						<td {if $slowPage.average == 4}style="background-color: lightpink"{/if}>
							{if $slowPage.average == 4}<strong>{/if}
								{if empty($slowPage.this_month_slower)}0{else}{$slowPage.this_month_slower}{/if} / {if empty($slowPage.last_month_slower)}0{else}{$slowPage.last_month_slower}{/if}
							{if $slowPage.average == 4}</strong>{/if}
						</td>
						<td {if $slowPage.average == 5}style="background-color: lightcoral"{/if}>
							{if $slowPage.average == 5}<strong>{/if}
								{if empty($slowPage.this_month_very_slow)}0{else}{$slowPage.this_month_very_slow}{/if} / {if empty($slowPage.last_month_very_slow)}0{else}{$slowPage.last_month_very_slow}{/if}
							{if $slowPage.average == 5}</strong>{/if}
						</td>
						<td>{if empty($slowPage.total)}0{else}{$slowPage.total}{/if}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>

		<h2>Slow Asynchronous Requests</h2>
		<table id="slowRequests" class="table table-striped table-condensed tablesorter">
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
					<th>Total</th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$slowAsyncRequests item=slowRequest}
				<tr>
					<td>{$slowRequest.module}</td>
					<td>{$slowRequest.action}</td>
					<td>{$slowRequest.method}</td>
					<td {if $slowRequest.average == 1}style="background-color: lightseagreen"{/if}>
						{if $slowRequest.average == 1}<strong>{/if}
						{if empty($slowRequest.this_month_fast)}0{else}{$slowRequest.this_month_fast}{/if} / {if empty($slowRequest.last_month_fast)}0{else}{$slowRequest.last_month_fast}{/if}
						{if $slowRequest.average == 1}</strong>{/if}
					</td>
					<td {if $slowRequest.average == 2}style="background-color: lightgreen"{/if}>
						{if $slowRequest.average == 2}<strong>{/if}
						{if empty($slowRequest.this_month_acceptable)}0{else}{$slowRequest.this_month_acceptable}{/if} / {if empty($slowRequest.last_month_acceptable)}0{else}{$slowRequest.last_month_acceptable}{/if}
						{if $slowRequest.average == 2}</strong>{/if}
					</td>
					<td {if $slowRequest.average == 3}style="background-color: lightgoldenrodyellow"{/if}>
						{if $slowRequest.average == 3}<strong>{/if}
						{if empty($slowRequest.this_month_slow)}0{else}{$slowRequest.this_month_slow}{/if} / {if empty($slowRequest.last_month_slow)}0{else}{$slowRequest.last_month_slow}{/if}
						{if $slowRequest.average == 3}</strong>{/if}
					</td>
					<td {if $slowRequest.average == 4}style="background-color: lightpink"{/if}>
						{if $slowRequest.average == 4}<strong>{/if}
						{if empty($slowRequest.this_month_slower)}0{else}{$slowRequest.this_month_slower}{/if} / {if empty($slowRequest.last_month_slower)}0{else}{$slowRequest.last_month_slower}{/if}
						{if $slowRequest.average == 4}</strong>{/if}
					</td>
					<td {if $slowRequest.average == 5}style="background-color: lightcoral"{/if}>
						{if $slowRequest.average == 5}<strong>{/if}
						{if empty($slowRequest.this_month_very_slow)}0{else}{$slowRequest.this_month_very_slow}{/if} / {if empty($slowRequest.last_month_very_slow)}0{else}{$slowRequest.last_month_very_slow}{/if}
						{if $slowRequest.average == 5}</strong>{/if}
					</td>
					<td>{if empty($slowRequest.total)}0{else}{$slowRequest.total}{/if}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>

	</div>
	<script type="text/javascript">
		{literal}
        $("#slowPages").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader'});
        $("#slowRequests").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader'});
		{/literal}
	</script>
{/strip}
