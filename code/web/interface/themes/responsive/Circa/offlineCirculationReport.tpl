{strip}
	<div id="page-content" class="content">
		{if $error}<p class="alert alert-danger">{$error}</p>{/if}
		<div id="sidebar">

			{* Report filters *}
			<div class="sidegroup">
				<h4>Report Filters</h4>
				<div class="sidegroupContents">
					<form id="offlineHoldsFilter">
						<div class="form-horizontal">
							<div class="form-group">
								<label for="startDate" class="control-label col-sm-2">Start Date</label>
								<input type="text" name="startDate" id="startDate" size="10" value="{$startDate|date_format:'%m/%d/%Y'}" class="form-control col-sm-3" style="width: auto;">
							</div>
							<div class="form-group">
								<label for="endDate" class="control-label col-sm-2">End Date</label>
								<input type="text" name="endDate" id="endDate" size="10" value="{$endDate|date_format:'%m/%d/%Y'}" class="form-control col-sm-3" style="width: auto;">
							</div>
							{*
							<div>
								<label for="typesToInclude">Include</label>
								<select name="typesToInclude" id="statiToInclude">
									<option value="everything" {if $typesToInclude=='everything'}selected="selected"{/if}>Everything</option>
									<option value="checkouts" {if $typesToInclude=='checkouts'}selected="selected"{/if}>Check Outs</option>
									<option value="checkins" {if $typesToInclude=='checkins'}selected="selected"{/if}>Check Ins</option>
								</select>
							</div>
							*}
							<div class="form-group">
								<label for="loginsToInclude" class="control-label col-sm-2">Logins To Show</label> <input type="text" name="loginsToInclude" id="startDate" size="60" value="{$loginsToInclude}" title="Separate multiple logins with commas, leave blank to include all" class="form-control col-sm-6" style="width: auto;">
							</div>
						<div class="row">
						<p class="alert alert-info col-sm-8 col-sm-offset-2">Separate multiple logins with commas. Leave blank to include all logins.</p>
						</div>
						<div class="form-group">
							<label class="control-label col-sm-2">Status</label>
							<div class="col-sm-6">
								<div class="checkbox">
									<label for="hideNotProcessed"><input type="checkbox" name="hideNotProcessed" id="hideNotProcessed" {if $hideNotProcessed}checked="checked"{/if}/> Hide Not Processed</label>
								</div>
								<div class="checkbox">
									<label for="hideFailed"><input type="checkbox" name="hideFailed" id="hideFailed" {if $hideFailed}checked="checked"{/if}/> Hide Failed</label>
								</div>
								<div class="checkbox">
									<label for="hideSuccess"><input type="checkbox" name="hideSuccess" id="hideSuccess" {if $hideSuccess}checked="checked"{/if}/> Hide Successful</label>
								</div>
							</div>
						</div>
						<br>
						<div>
							<input type="submit" name="updateFilters" value="Update Filters" class="btn btn-primary">
						</div>
						</div>

					</form>
				</div>
			</div>

		</div>

		<div id="main-content">
			<h2>Offline Circulation Summary</h2>
			<table class="table table-striped">
				<tr><th>Total Records</th><td>{$totalRecords}</td></tr>
				<tr><th>Not Processed</th><td>{$totalNotProcessed}</td></tr>
				<tr><th>Passed</th><td>{$totalPassed}</td></tr>
				<tr><th>Failed</th><td>{$totalFailed}</td></tr>
			</table>

			<h2>Offline Circulation</h2>
			{if count($offlineCirculation) > 0}
				<table class="tablesorter table table-striped" id="offlineCirculationReport">
					<thead>
					<tr><th>#</th><th>Login</th>{*<th>Initials</th><th>Type</th>*}<th>Item Barcode</th><th>Patron Barcode</th><th>Date Entered</th><th>Status</th><th>Notes</th></tr>
					</thead>
					<tbody>
					{foreach from=$offlineCirculation item=offlineCircEntry name='offlinecircs'}
						<tr><td>{$smarty.foreach.offlinecircs.iteration}</td><td>{$offlineCircEntry->login}</td>{*<td>{$offlineCircEntry->initials}</td><td>{$offlineCircEntry->type}</td>*}<td>{$offlineCircEntry->itemBarcode}</td><td>{$offlineCircEntry->patronBarcode}</td><td>{$offlineCircEntry->timeEntered|date_format}</td><td>{$offlineCircEntry->status}</td><td>{$offlineCircEntry->notes}</td></tr>
					{/foreach}
					</tbody>
				</table>
			{else}
				<p>There is no offline circulation information to display.</p>
			{/if}
		</div>
	</div>
	<script	type="text/javascript">
		{literal}
		$(function() {
			$( "#startDate" ).datepicker({ showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
			$( "#endDate" ).datepicker({ showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
			$("#offlineCirculationReport").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
		});
		{/literal}
	</script>
{/strip}