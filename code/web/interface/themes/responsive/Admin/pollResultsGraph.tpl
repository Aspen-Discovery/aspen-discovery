{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text=$graphTitle isAdminFacing=true}</h1>
		<div class="chart-container">
			<canvas id="chart"></canvas>
		</div>
	</div>

	<h2>{translate text="Data" isAdminFacing=true}</h2>
	<div class="adminTableRegion fixed-height-table">
		<table class="adminTable table table-responsive table-striped table-bordered table-condensed smallText table-sticky">
			<thead>
			<tr>
				<th>{translate text="Option" isAdminFacing=true}</th>
                <th>{translate text="Count" isAdminFacing=true}</th>
			</tr>
			</thead>
			<tbody>
            {foreach from=$dataSeries item=seriesData}
				<tr>
					<td>{if !empty($translateColumnLabels)}{translate text=$seriesData.displayLabel isAdminFacing=true}{else}{$seriesData.displayLabel}{/if}</td>
						<td>{if (empty($seriesData.displayCount))}0{else}{$seriesData.displayCount|number_format}{/if}</td>
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
		type: 'bar',
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
					{
					label: "{translate text="Results" isAdminFacing=true}",
					data: [
                        {foreach from=$dataSeries key=seriesLabel item=seriesData}
                        {foreach from=$seriesData.data item=curValue}
                        {$curValue},
                        {/foreach}
                        {/foreach}
					],
					borderWidth: 1,
					borderColor: [
                        {foreach from=$dataSeries key=seriesLabel item=seriesData}
						'{$seriesData.borderColor}',
						{/foreach}
					],
					backgroundColor: [
                        {foreach from=$dataSeries key=seriesLabel item=seriesData}
						'{$seriesData.backgroundColor}',
                        {/foreach}
					],

                    }
                {literal}
			]
		},
		options: {
			responsive: true,
			legend: false,
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
</script>
{/literal}