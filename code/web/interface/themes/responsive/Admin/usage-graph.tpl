{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text=$graphTitle isAdminFacing=true}</h1>
		<div class="chart-container" style="position: relative; height:50%; width:100%">
			<canvas id="chart"></canvas>
		</div>

		<h2>{translate text="Raw Data" isAdminFacing=true}</h2>
		<table class="table table-striped table-bordered">
			<thead>
				<tr>
					<th>{translate text="Date" isAdminFacing=true}</th>
					{foreach from=$dataSeries key=seriesLabel item=seriesData}
						<th>{translate text=$seriesLabel isAdminFacing=true}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach from=$columnLabels item=label}
					<tr>
						<td>{translate text=$label isAdminFacing=true}</td>
						{foreach from=$dataSeries item=seriesData}
							<td>{$seriesData.data.$label|number_format}</td>
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
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