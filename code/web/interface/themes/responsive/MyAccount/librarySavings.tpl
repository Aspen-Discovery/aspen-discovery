{strip}
<div class="col-xs-12">
	{if !empty($loggedIn)}

		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
			</div>
		{/if}
		{if !empty($accountMessages)}
			{include file='systemMessages.tpl' messages=$accountMessages}
		{/if}
		{if !empty($ilsMessages)}
			{include file='ilsMessages.tpl' messages=$ilsMessages}
		{/if}
		{strip}

		<h1>{translate text='My Library Savings' isPublicFacing = true}</h1>

		<div class="row">
			<div id="costSavingsExplanation" class="alert alert-info">
				{* some necessary white space in notice was previously stripped out when needed. *}
				{$costSavingsExplanation}
			</div>
		</div>

		{if !empty($offline)}
			<div class="alert alert-warning">{translate text="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your cost savings." isPublicFacing=true}</div>
		{else}
			<div class="row">
				<div class="col-xs-12">
					{if $profile->enableCostSavings}
						<a class="btn btn-danger" href="/MyAccount/LibrarySavings?disableLibrarySavings" role="button">{translate text="Don't Track Library Savings" isPublicFacing=true}</a>
					{else}
						<a class="btn btn-default" href="/MyAccount/LibrarySavings?enableLibrarySavings" role="button">{translate text="Show My Library Savings" isPublicFacing=true}</a>
					{/if}
				</div>
			</div>
			<div class="row"><div class="col-xs-12">&nbsp;</div> </div>
			<div class="row">
				<div class="col-xs-12">
					{if $profile->trackReadingHistory}
						<p>{translate text="You are saving %1% with what is currently checked out from the library and you have saved %2% by checking out the materials in your reading history from the library." 1=$currentCostSavings 2=$totalCostSavings isPublicFacing=true}</p>
					{else}
						<p>{translate text="You are saving %1% with what is currently checked out from the library." 1=$currentCostSavings isPublicFacing=true}</p>
						<p>{translate text="Start recording your <a href='/MyAccount/ReadingHistory'>reading history</a> to see your cost savings over time." 1=$currentCostSavings isPublicFacing=true}</p>
					{/if}
				</div>
			</div>
			{if $showGraphs}
				<div class="row">
					<div class="col-xs-12">
						<h2>{translate text="Monthly Savings" isPublicFacing=true}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<label for="yearToShow" class="control-label">{translate text="Year to Show" isPublicFacing="true"}</label>
						<select name="yearToShow" id="yearToShow" class="form-control" onchange="document.location.href = this.options[this.selectedIndex].value;">
							{foreach from=$yearsToShow item=year}
								<option value="\MyAccount\LibrarySavings?yearToShow={$year}" {if $year==$yearToShow}selected="selected"{/if}>{$year}</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<div class="chart-container" style="position: relative; height:50%; width:100%">
							<canvas id="monthlySavingsChart"></canvas>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-12">
						<h2>{translate text="Yearly Savings" isPublicFacing=true}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-xs-12">
						<div class="chart-container" style="position: relative; height:50%; width:100%">
							<canvas id="yearlySavingsChart"></canvas>
						</div>
					</div>
				</div>
			{/if}
		{/if}
		{/strip}
	{else}
		<div class="page">
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		</div>
	{/if}
</div>
{/strip}

{literal}
<script>
var monthlySavingsCtx = document.getElementById('monthlySavingsChart');
var monthlySavingsChart = new Chart(monthlySavingsCtx, {
	type: 'line',
	data: {
		labels: [
			{/literal}
			{foreach from=$monthlySavingsColumnLabels item=columnLabel}
				'{$columnLabel}',
			{/foreach}
			{literal}
		],
		datasets: [
			{/literal}
			{foreach from=$monthlyDataSeries key=seriesLabel item=seriesData}
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
				tension: 0
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
					{foreach from=$monthlySavingsColumnLabels item=columnLabel}
						'{$columnLabel}',
					{/foreach}
					{literal}
				]
			}]
		}
	}
});
var yearlySavingsCtx = document.getElementById('yearlySavingsChart');
var yearlySavingsChart = new Chart(yearlySavingsCtx, {
	type: 'bar',
	data: {
		labels: [
			{/literal}
			{foreach from=$yearlySavingsColumnLabels item=columnLabel}
				'{$columnLabel}',
			{/foreach}
			{literal}
		],
		datasets: [
			{/literal}
			{foreach from=$yearlyDataSeries key=seriesLabel item=seriesData}
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
					{foreach from=$yearlySavingsColumnLabels item=columnLabel}
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