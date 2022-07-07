{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Site Memory Dashboard" isAdminFacing=true}</h1>
		{include file="Greenhouse/selectSiteForm.tpl"}

		<h2>{translate text="Memory Usage Graph" isAdminFacing=true}</h2>
		<div class="chart-container" style="position: relative; height:50%; width:100%">
			<canvas id="chart"></canvas>
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