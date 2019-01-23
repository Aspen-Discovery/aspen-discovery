{strip}
	<div id="main-content" class="col-md-12">
		{if $loggedIn}
			<h1>Student Report</h1>
			<div class="alert alert-info">
				For more information on using student reports, see the <a href="https://docs.google.com/document/d/1ASo7wHL0ADxG8Q8oIRTeXybja7QJq7mW-77e3C1X7f8">online documentation</a>.
			</div>
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
			<form class="form form-inline">
				<label for="selectedReport" class="control-label">Available Reports&nbsp;</label>
				<select name="selectedReport" id="selectedReport" class="form-control input-sm">
					{foreach from=$availableReports item=curReport key=reportLocation}
						<option value="{$reportLocation}" {if $curReport==$selectedReport}selected="selected"{/if}>{$curReport}</option>
					{/foreach}
				</select>
				&nbsp;
				<label for="showOverdueOnly" class="control-label">Include&nbsp;</label>
				<select name="showOverdueOnly" id="showOverdueOnly" class="form-control input-sm">
					<option value="overdue" {if $showOverdueOnly}selected="selected"{/if}>Overdue Items</option>
					<option value="checkedOut" {if !$showOverdueOnly}selected="selected"{/if}>Checked Out Items</option>
				</select>
				&nbsp;
				<input type="submit" name="showData" value="Show Data" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
			</form>

			{if $reportData}
				<br/>
				<p>
					There are a total of <strong>{$reportData|@count}</strong> rows that meet your criteria.
				</p>
				<table id="studentReportTable" class="table table-condensed tablesorter">
					{foreach from=$reportData item=dataRow name=studentData}
						{if $smarty.foreach.studentData.index == 0}
							<thead>
								<tr>
									{foreach from=$dataRow item=dataCell name=dataCol}
										{if in_array($smarty.foreach.dataCol.index, array('0', '1', '3', '5', '6', '11', '13')) }
											<th class="filter-select">{$dataCell}</th>
										{else}
											<th>{$dataCell}</th>
										{/if}
									{/foreach}
								</tr>
							</thead>
						{else}
							{if $smarty.foreach.studentData.index == 1}
								<tbody>
							{/if}
							<tr>
								{foreach from=$dataRow item=dataCell}
									<td>{$dataCell}</td>
								{/foreach}
							</tr>
						{/if}
					{/foreach}
					</tbody>
				</table>
				<script type="text/javascript">
					{literal}
					$(document).ready(function(){
						$('#studentReportTable').tablesorter({
							theme: 'blue',
							width: 'fixed',
							widgets: ["zebra", "filter"],
							widgetOptions: {
								filter_hideFilters : false,
								filter_ignoreCase: true
							}
						});
					});
					{/literal}
				</script>
			{/if}
		{else}
			You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
		{/if}
	</div>
{/strip}