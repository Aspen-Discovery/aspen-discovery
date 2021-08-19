	<div id="main-content" class="col-md-12">
		<h1>Materials Request Summary Report</h1>
		{if !empty($error)}
			<div class="alert alert-warning">{$error}</div>
		{else}
			<legend>Filters</legend>
			<form action="/MaterialsRequest/SummaryReport" method="get" class="form-inline">
				<div class="form-group">
					<label for="period" class="control-label">Period</label>
					<select name="period" id="period" onchange="$('#startDate').val('');$('#endDate').val('');">
						<option value="day" {if $period == 'day'}selected="selected"{/if}>Day</option>
						<option value="week" {if $period == 'week'}selected="selected"{/if}>Week</option>
						<option value="month" {if $period == 'month'}selected="selected"{/if}>Month</option>
						<option value="year" {if $period == 'year'}selected="selected"{/if}>Year</option>
					</select>
				</div>
				<div class="form-group">
					Date:
						<label for="startDate"> From</label> <input type="date" id="startDate" name="startDate" value="{$startDate}" size="8" max="{$smarty.now|date_format:"%Y-%m-%d"}">
						<label for="endDate">To</label> <input type="date" id="endDate" name="endDate" value="{$endDate}" size="8" max="{$smarty.now|date_format:"%Y-%m-%d"}">
				</div>
				<div class="form-group">
					<input type="submit" name="submit" value="Update Filters" class="btn btn-default">
				</div>
			</form>

			<br>

			{* Display results as graph *}
			<legend>Chart</legend>

			<div class="chart-container" style="position: relative; height:50%; width:100%">
				<canvas id="chart"></canvas>
			</div>

			<br>

			{* Display results in table*}

			<legend>Table</legend>

			<table id="summaryTable" class="tablesorter table table-bordered">
				<thead>
					<tr>
						<th>Date</th>
						{foreach from=$statuses item=status}
							<th>{$status|translate}</th>
						{/foreach}
					</tr>
				</thead>
				<tbody>
					{foreach from=$periodData item=periodInfo key=periodStart}
						<tr>
							<td>
								{* Properly format the period *}
								{if $period == 'year'}
									{$periodStart|date_format:'%Y'}
								{elseif $period == 'month'}
									{$periodStart|date_format:'%h %Y'}
								{else}
									{$periodStart|date_format}
								{/if}
							</td>
							{foreach from=$statuses key=status item=statusLabel}
								<th>{if $periodInfo.$status}{$periodInfo.$status}{else}0{/if}</th>
							{/foreach}
						</tr>
					{/foreach}
				</tbody>
			</table>
		{/if}

		<form action="/MaterialsRequest/SummaryReport" method="get">
			<input type="hidden" name="period" value="{$period}"/>
			<input type="hidden" name="startDate" value="{$startDate}"/>
			<input type="hidden" name="endDate" value="{$endDate}"/>
			<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel"  class="btn btn-default">
		</form>

		{* Export to Excel option *}
	</div>

<script type="text/javascript">
{literal}
	$("#summaryTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: 'date'} } });

var ctx = document.getElementById('chart');
var myChart = new Chart(ctx, {
	type: 'line',
	data: {
		labels: [
			{/literal}
			{foreach from=$columnLabels item=columnLabel}
				'{$columnLabel}',
			{/foreach}
			{literal}
		],
		datasets: [
			{/literal}
			{foreach from=$dataSeries key=seriesLabel item=seriesData}
				{ldelim}
				label: "{$seriesLabel}",
				data: [
					{foreach from=$seriesData.data item=curValue}
						{$curValue},
					{/foreach}
				],
				borderWidth: 1,
				borderColor: '{$seriesData.borderColor}',
				backgroundColor: '{$seriesData.backgroundColor}',
				{rdelim},
			{/foreach}
			{literal}
		],
	},
	options: {
		scales: {
			yAxes: [{
				ticks: {
					beginAtZero: true
				}
			}],
			xAxes: [{
				type: 'category',
				labels: [
					{/literal}
					{foreach from=$columnLabels item=columnLabel}
						'{$columnLabel}',
					{/foreach}
					{literal}
				]
			}]
		}
	}
});
{/literal}
</script>