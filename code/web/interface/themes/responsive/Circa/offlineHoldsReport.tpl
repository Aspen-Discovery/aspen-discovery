{strip}
	<div id="page-content" class="content">
		{if !empty($error)}<p class="error">{$error}</p>{/if}
		<div id="sidebar">
			{* Report filters *}
			<div class="sidegroup">
				<h4>Report Filters</h4>
				<div class="sidegroupContents">
					<form id="offlineHoldsFilter">
						<div  class="form-horizontal">
							<div class="form-group">
								<label for="startDate" class="control-label col-sm-2">Start Date</label>
								<input type="text" name="startDate" id="startDate" size="10" value="{$startDate|date_format:'%m/%d/%Y'}" class="form-control col-sm-3" style="width: auto;"/>
							</div>
							<div class="form-group">
								<label for="endDate" class="control-label col-sm-2">End Date</label>
								<input type="text" name="endDate" id="endDate" size="10" value="{$endDate|date_format:'%m/%d/%Y'}" class="form-control col-sm-3" style="width: auto;"/>
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
							<div class="form-group">
								<input type="submit" name="updateFilters" value="Update Filters" class="btn btn-primary"/>
							</div>

						</div>
					</form>
				</div>
			</div>
		</div>

		<div id="main-content">
			<h2>Offline Holds</h2>
			{if count($offlineHolds) > 0}
				<table class="citation tablesorter" id="offlineHoldsReport" >
					<thead>
						<tr><th>Patron Barcode</th><th>Record Id</th><th>Title</th><th>Date Entered</th><th>Status</th><th>Notes</th></tr>
					</thead>
					<tbody>
						{foreach from=$offlineHolds item=offlineHold}
							{* TODO Update this to work with multi-ils installations*}
							<tr><td>{$offlineHold.patronBarcode}</td><td>{$offlineHold.bibId}</td><td><a href="/Record/{$offlineHold.bibId}">{$offlineHold.title}</a></td><td>{$offlineHold.timeEntered|date_format}</td><td>{$offlineHold.status}</td><td>{$offlineHold.notes}</td></tr>
						{/foreach}
					</tbody>
				</table>
			{else}
				<p>There are no offline holds to display.</p>
			{/if}
		</div>
	</div>
	<script	type="text/javascript">
		{literal}
		$(function() {
			$( "#startDate" ).datepicker({ showOn: "button", buttonImage: "/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
			$( "#endDate" ).datepicker({ showOn: "button", buttonImage: "/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
			$("#offlineHoldsReport").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
		});
		{/literal}
	</script>
{/strip}