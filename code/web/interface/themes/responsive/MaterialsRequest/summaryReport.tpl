	<div id="main-content" class="col-md-12">
		<h2>Materials Request Summary Report</h2>
		{if $error}
			<div class="alert alert-warning">{$error}</div>
		{else}


<legend>Filters</legend>

						<form action="{$path}/MaterialsRequest/SummaryReport" method="get" class="form-inline">
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
									<label for="startDate"> From</label> <input type="text" id="startDate" name="startDate" value="{$startDate}" size="8">
									<label for="endDate">To</label> <input type="text" id="endDate" name="endDate" value="{$endDate}" size="8">
							</div>
							<div class="form-group">
								<input type="submit" name="submit" value="Update Filters" class="btn btn-default">
							</div>
						</form>

<br>


			{* Display results as graph *}
			{if $chartPath}

				<legend>Chart</legend>

				<div id="chart">
				<img src="{$chartPath}">
				</div>

				<br>
			{/if}

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

		<form action="{$path}/MaterialsRequest/SummaryReport" method="get">
			<input type="hidden" name="period" value="{$period}"/>
			<input type="hidden" name="startDate" value="{$startDate}"/>
			<input type="hidden" name="endDate" value="{$endDate}"/>
			<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel"  class="btn btn-default">
		</form>

		{* Export to Excel option *}
	</div>

<script type="text/javascript">
{literal}
	$("#startDate").datepicker();
	$("#endDate").datepicker();
	$("#summaryTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: 'date'} } });
{/literal}
</script>