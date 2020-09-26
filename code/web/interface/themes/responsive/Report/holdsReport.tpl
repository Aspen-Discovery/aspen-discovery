{strip}
	<div id="main-content" class="col-md-12">
		<div class="doNotPrint">
		{if $loggedIn}
			<h1>Holds Report</h1>
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
			<form class="form form-inline">

				{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}

				<input type="submit" name="showData" value="Show Data" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="button" name="printSlips" value="Print Slips" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.holdsReportContainer'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'auto'; } window.print(); {/literal}" />
				&nbsp;
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
				&nbsp;
			</form>
			{if $reportData}
				<br/>
				<p>
					There are a total of <strong>{$reportData|@count}</strong> Fill List items{/if}.
				</p>
		</div>
{literal}
<style type="text/css">
	/* TODO : GET 4 slips to fit on a page */
	/* TODO : display as table on screen, slips in print */
	.holdsReportSlipContainer{
		border-bottom: 1px dashed #ccc !important;
		border-top: 1px dashed #ccc !important;
		display: table !important;
		height: 2.3in !important;
		max-height: 2.3in !important;
		max-width: 7in !important;
		min-height: 2.3in !important;
		min-width: 7in !important;
		width: 7in !important;
	}
	.holdsReportSlipContainer td {
		padding: 0in !important;
	}
	div.holdsReportSlip {
		color: #000;
		display: table-row !important;
		font-size: 12pt;
		page-break-inside: avoid;
	}
	div.holdsReportSlip div.patron {
		display: table-cell;
		height: 2in;
		overflow-wrap: break-word;
		padding: .47in !important;
		transform: rotate(-90deg);
		width: 2in;
	}
	div.holdsReportSlip div.patron div {
		font-weight: bold;
	}
	div.GRD_LVL {
	}
	div.HOME_ROOM {
	}
	div.P_BARCODE_SCANNABLE {
		font-family: "3 of 9 Barcode";
		font-size: 20pt !important;
		font-weight: normal !important;
	}
	div.PATRON_NAME {
	}
	div.holdsReportSlip div.placeHolder {
		display: table-cell;
		height: 2in;
		overflow-wrap: break-word;
		transform: rotate(-90deg);
		width: 2in;
	}
	div.holdsReportSlip div.item {
		display: table-cell;
		height: 2in;
		overflow-wrap: break-word;
		transform: rotate(90deg);
		width: 2in;
	}
	div.holdsReportSlip div.item div {
	}
	div.CALL_NUMBER {
	}
	div.ITEM_ID {
	}
	div.TITLE {
		font-style: italic;
	}
	div.DUE_DATE {
	}
	table#holdsReportTable {
/*		width: 7in;
		margin-left: 0;
		margin-right: auto;
		font: inherit;
		border: 0;
*/	}
	table#holdsReportTable .hideit {
		display: none;
	}
	table#holdsReportTable thead {
		display: table !important;
	}
	table#holdsReportTable tbody tr td {
		border: 0;
	}
	@media print {
		@page {
			size:  auto;   /* auto is the initial value */
			margin: .5in !important;  /* this affects the margin in the printer settings */
		}
		html {
			margin: 0px;  /* this affects the margin on the html before sending to printer */
		}
		body {
			margin: 0in !important; /* margin you want for the content */
		}
		div.breadcrumbs {
			display: none !important;
		}
		div.doNotPrint {
			display: none !important;
		}
		div#footer-container {
			display: none !important;
		}
		div#system-message-header {
			display: none !important;
		}
		table#holdsReportTable thead tr {
			display: none !important;
		}
	}
</style>
{/literal}

		<table id="holdsReportTable">
			<thead>
				<tr>
					<th class="filter-select filter-onlyAvail">Shelf Location</th>
					<th class="filter-select filter-onlyAvail">Grade</th>
					<th class="filter-select filter-onlyAvail">Homeroom</th>
					<th class="sorter-false">Student ID</th>
					<th class="filter">Student Name</th>
					<th class="sorter-false">Notice</th>
				<tr>
			</thead>
			<tbody>
{assign var=previousPatron value=0}
{foreach from=$reportData item=dataRow name=holdsReportData}
		{if $smarty.foreach.holdsReportData.index > 0}</div></div></td></tr>{/if}
				<tr class="holdsReportSlipContainer">
					<td class="hideit">{$dataRow.SHELF_LOCATION|replace:'kids ':''|replace:'teen ':''|replace:'adult ':''}</td>
					<td class="hideit">{$dataRow.GRD_LVL|replace:' student':''|replace:'MNPS School Librar':'0.0 MNPS School Librar'|replace:'MNPS Staff':'0.1 MNPS Staff'|replace:'Pre-K':'0.2 Pre-K'|replace:'Kindergar':'0.3 Kindergar'|replace:'First':'1 First'|replace:'Second':'2 Second'|replace:'Third':'3 Third'|replace:'Fourth':'4 Fourth'|replace:'Fifth':'5 Fifth'|replace:'Sixth':'6 Sixth'|replace:'Seventh':'7 Seventh'|replace:'Eighth':'8 Eighth'|replace:'Ninth':'9 Ninth'|replace:'Tenth':'10 Tenth'|replace:'Eleventh':'11 Eleventh'|replace:'Twelfth':'12 Twelfth'|regex_replace:'/^.*no LL delivery/':'13 no LL delivery'|replace:'MNPS 18+':'13 MNPS 18+'}</td>
					<td class="hideit">{$dataRow.HOME_ROOM|lower|capitalize:true}</td>
					<td class="hideit">{$dataRow.P_BARCODE}</td>
					<td class="hideit">{$dataRow.PATRON_NAME}</td>
					<td>
						<div class="holdsReportSlip">
							<div class="patron">
								<div class="PATRON_NAME">{$dataRow.PATRON_NAME|upper}</div>
								<div class="P_BARCODE_SCANNABLE">*{$dataRow.P_BARCODE}*</div>
								<div class="GRD_LVL">{$dataRow.GRD_LVL|replace:' student':''}</div>
								<div class="HOME_ROOM">{$dataRow.HOME_ROOM|lower|capitalize:true}</div>
							</div>
							<div class="placeHolder">
&nbsp;
							</div>
							<div class="item">
								<div class="SHELF_LOCATION">{$dataRow.SHELF_LOCATION|replace:'kids ':''|replace:'teen ':''|replace:'adult ':''|capitalize}</div>
								<div class="CALL_NUMBER">{$dataRow.CALL_NUMBER}</div>
								<div class="TITLE">{$dataRow.TITLE}</div>
								<div class="ITEM_ID">{$dataRow.ITEM_ID}</div>
{/foreach}
					</td>
				</tr>
			</tbody>
		</table>
		<script type="text/javascript">
			{literal}
				$(document).ready(function(){
					$('#holdsReportTable').tablesorter({
						widgets: ["filter"],
						widgetOptions: {
							filter_hideFilters : false,
							filter_ignoreCase: true
						}
					});
				});
			{/literal}
		</script>

{/if}
	</div>
{/strip}
