{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text=$graphTitle isAdminFacing=true}</h1>

		<div class="chart-container" style="position: relative; height:50%; width:100%">
			<canvas id="chart"></canvas>
		</div>

		<h2>{translate text="Filter Options" isAdminFacing=true}</h2>
		<form>
		<div>
			<div class="form-group">
				<label for="timeframe">Event hours per:</label>
				<select name="timeframe" id="timeframe" class="form-control">
					<option {if $timeframe == 'days'}selected{/if} value="days">Day</option>
					<option {if $timeframe == 'weeks'}selected{/if} value="weeks">Week</option>
					<option {if $timeframe == 'months'}selected{/if} value="months">Month</option>
					<option {if $timeframe == 'years'}selected{/if} value="years">Year</option>
				</select>
			</div>
			<div class="form-group">
				<label for="fromDate">Start date:</label>
				<input class="form-control" type="date" {if !empty($fromDate)}value="{$fromDate}"{/if} id="fromDate" name="fromDate">
			</div>
			<div class="form-group">
				<label for="toDate">End date:</label>
				<input class="form-control" type="date" {if !empty($toDate)}value="{$toDate}"{/if} id="toDate" name="toDate">
			</div>
			<div class="form-group">
				<label for="type">Event Type:</label>
				<select name="type" id="type" class="form-control">
					<option {if $eventTypeValue == ''}selected{/if} value="">All Types</option>
					{foreach $eventTypes as $id => $type}
						<option {if $eventTypeValue == $id}selected{/if} value="{$id}">{$type}</option>
					{/foreach}
				</select>
			</div>
			<div class="form-group">
				<label for="type">Location:</label>
				<select name="location" id="location" class="form-control">
					<option {if $locationValue == ''}selected{/if} value="">All Locations{$libraryRestriction}</option>
					{foreach $locations as $id => $location}
						<option {if $locationValue == $id}selected{/if} value="{$id}">{$location}</option>
					{/foreach}
				</select>
			</div>
			<div class="form-group" {if empty($sublocations)}style="display:none"{/if}>
				<label for="type">Sublocation:</label>
				<select name="sublocation" id="sublocation" class="form-control" >
					<option {if $sublocations && $sublocationValue == ''}selected{/if} value="">All Sublocations</option>
					{if $sublocations}
						{foreach $sublocations as $id => $sublocation}
							<option {if $sublocationValue == $id}selected{/if} value="{$id}">{$sublocation}</option>
						{/foreach}
					{/if}
				</select>
			</div>
			<h3>{translate text="Custom Fields" isAdminFacing=true}</h3>
			<div class="form-inline">
			{foreach $checkboxFields as $id => $checkbox}
				<div class="form-group">
					<label for="field_{$id}">
						<input type="checkbox" {if array_key_exists("field_{$id}", $fields) && $fields["field_{$id}"] == 1}checked{/if}  value="1" id="field_{$id}" name="field_{$id}" class="form-control">
						{$checkbox->name}
					</label>
				</div>
			{/foreach}
			</div>
			<hr>
			{foreach $selectFields as $id => $select}
				<div class="form-group">
					<label for="field_{$id}">{$select->name}: </label>
					<select value="{$id}" id="field_{$id}" name="field_{$id}" class="form-control">
						<option value="">No selection</option>
						{foreach explode("\n", $select->allowableValues) as $index => $option}
							<option {if array_key_exists("field_{$id}", $fields) && $fields["field_{$id}"] == $index}selected{/if} value="{$index}">{$option}</option>
						{/foreach}
					</select>
				</div>
			{/foreach}
			<hr>
			<div class="form-group">
				<label for="query">Search: </label><input type="text" id="query" name="query" class="form-control" value="{$query}"/>
				<span class="help-block" style="margin-top:0"><small class="text-info"><i class="fas fa-info-circle"></i> {translate text="Searches all text fields (title, description, custom text fields)" isAdminFacing=true}</small></span>
			</div>
			<hr>
			<div class="form-group">
				<input type="submit" value="Apply Filters" class="form-control btn btn-primary"/>
			</div>
		</form>
		<hr>

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
		{if !empty($showCSVExportButton)}
			<div>
				<a id="UsageGraphExport" class="btn btn-sm btn-default" href="/{$section}/AJAX?method=exportUsageData&stat={$stat}{if !empty($profileName)}&profileName={$profileName}{/if}&instance={if !empty($instance)}{$instance}{/if}">{translate text='Export To CSV' isAdminFacing=true}</a>
				<div id="exportToCSVHelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Exporting will retrieve the latest data. To see it on screen, refresh this page." isAdminFacing=true}</small></div>
			</div>
		{/if}
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
	$("#location").change(function () {
		var url = Globals.path + '/Events/AJAX';
		var params = {
			method: 'getEventTypesAndSublocationsForLocation',
			locationId: $(this).val()
		};

		$.getJSON(url, params, function (data) {
			if (data.success) {
				var sublocationSelect = $("#sublocation");
				if (data.sublocations && data.sublocations.length > 0) {
					var sublocations = JSON.parse(data.sublocations);
					sublocationSelect.html("");
					$("<option/>", {
						value: "",
						text: "All Sublocations"
					}).appendTo("#sublocation");
					Object.keys(sublocations).forEach(function (key) {
						$("<option/>", {
							value: key,
							text: sublocations[key]
						}).appendTo("#sublocation");
					});
					if (sublocations.length === 0) {
						sublocationSelect.parent().hide();
					} else {
						sublocationSelect.parent().show();
					}

				} else {
					sublocationSelect.html("");
					sublocationSelect.parent().hide();
				}
			}
		});
	});
</script>
{/literal}
