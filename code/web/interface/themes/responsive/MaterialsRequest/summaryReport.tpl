	<div id="main-content" class="col-md-12">
		<h1>{translate text="Materials Request Summary Report" isAdminFacing=true}</h1>
		{if !empty($error)}
			<div class="alert alert-warning">{$error}</div>
		{else}
			<legend>{translate text="Filters" isAdminFacing=true}</legend>
			<form action="/MaterialsRequest/SummaryReport" method="get" class="form-inline">
				<div class="form-group">
					<label for="period" class="control-label">{translate text="Period" isAdminFacing=true}</label>
					<select name="period" id="period" onchange="$('#startDate').val('');$('#endDate').val('');">
						<option value="day" {if $period == 'day'}selected="selected"{/if}>{translate text="Day" isAdminFacing=true}</option>
						<option value="week" {if $period == 'week'}selected="selected"{/if}>{translate text="Week" isAdminFacing=true}</option>
						<option value="month" {if $period == 'month'}selected="selected"{/if}>{translate text="Month" isAdminFacing=true}</option>
						<option value="year" {if $period == 'year'}selected="selected"{/if}>{translate text="Year" isAdminFacing=true}</option>
					</select>
				</div>
				<div class="form-group">
                    {translate text="Date" isAdminFacing=true}
					<label for="startDate"> {translate text="From" isAdminFacing=true}</label> <input type="date" id="startDate" name="startDate" value="{$startDate}" size="8" max="{$smarty.now|date_format:"%Y-%m-%d"}">
					<label for="endDate">{translate text="To" isAdminFacing=true}</label> <input type="date" id="endDate" name="endDate" value="{$endDate}" size="8" max="{$smarty.now|date_format:"%Y-%m-%d"}">
				</div>
				<div class="form-group">
					<input type="submit" name="submit" value="{translate text="Update Filters" isAdminFacing=true}" class="btn btn-default">
				</div>
			</form>

			<br>

			{* Display results as graph *}
			<legend>{translate text="Chart" isAdminFacing=true}</legend>

			<div class="chart-container" style="position: relative; height:50%; width:100%">
				<canvas id="chart"></canvas>
			</div>

			<br>

			{* Display results in table*}

			<legend>{translate text="Table" isAdminFacing=true}</legend>

			<table id="summaryTable" class="tablesorter table table-bordered">
				<thead>
					<tr>
						<th>{translate text="Date" isAdminFacing=true}</th>
						{foreach from=$statuses item=status}
							<th>{translate text=$status isAdminFacing=true}</th>
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
			<input type="submit" id="exportToExcel" name="exportToExcel" value="{translate text="Export to Excel" isAdminFacing=true}"  class="btn btn-default">
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