{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text=$graphTitle isAdminFacing=true}</h1>
		<div class="chart-container" style="position: relative; height:50%; width:100%">
			<canvas id="chart"></canvas>
		</div>

		<h2>{translate text="Raw Data" isAdminFacing=true}</h2>
		<div class="adminTableRegion fixed-height-table">
			<table class="adminTable table table-responsive table-striped table-bordered table-condensed smallText table-sticky">
				<thead>
					<tr>
						<th>{translate text="Date" isAdminFacing=true}</th>
						{foreach from=$dataSeries key=seriesLabel item=seriesData}
							<th>{if !empty($translateDataSeries)}{translate text=$seriesLabel isAdminFacing=true}{else}{$seriesLabel}{/if}</th>
						{/foreach}
					</tr>
				</thead>
				<tbody>
					{foreach from=$columnLabels item=label}
						<tr>
							<td>{if !empty($translateColumnLabels)}{translate text=$label isAdminFacing=true}{else}{$label}{/if}</td>
							{foreach from=$dataSeries item=seriesData}
								<td>{if (empty($seriesData.data.$label))}0{else}{$seriesData.data.$label|number_format}{/if}</td>
							{/foreach}
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		<div>
			<a id="UsageGraphExport" class="btn btn-sm btn-default" href="/ILS/AJAX?method=exportUsageData&stat={$stat}&instance={if !empty($instance)}{$instance}{/if}">{translate text='Export To CSV' isAdminFacing=true}</a>
			<div id="exportToCSVHelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Exporting will retrieve the latest data. To see it on screen, refresh this page." isAdminFacing=true}</small></div>
		</div>
	</div>
	</div>
{/strip}
{literal}
<script>
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
				label: "{translate text=$seriesLabel isAdminFacing=true}",
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
		]
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
</script>
{/literal}