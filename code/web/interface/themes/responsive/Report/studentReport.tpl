{strip}
	<div id="main-content" class="col-md-12">
		<div class="doNotPrint">
			<h1>School Overdue Report</h1>
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
			<form class="form form-inline">

				{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}

				<select name="showOverdueOnly" id="showOverdueOnly" class="form-control input-sm">
					<option value="overdue" {if $showOverdueOnly == "overdue"}selected="selected"{/if}>Overdue Items</option>
					<option value="checkedOut" {if $showOverdueOnly == "checkedOut"}selected="selected"{/if}>All Checked Out</option>
				</select>
				&nbsp;
				<input type="submit" name="showData" value="Show Data" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="button" name="printSlips" value="Print Slips" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.overdueSlipContainer'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'auto'; } window.print(); {/literal}" />
				&nbsp;
				<input type="button" name="printPages" value="Print Pages" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.overdueSlipContainer'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'always'; } window.print(); {/literal}" />
				&nbsp;
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
				&nbsp;
			</form>
			{if $reportData}
				<br/>
				<p>
					There are a total of <strong>{$reportData|@count}</strong> {if $showOverdueOnly == "overdue"}overdue items{else}items out{/if}.
				</p>
		</div>
{literal}
<style type="text/css">
	div.overdueSlip {
		border-top: 1px dashed #ccc !important;
		display: table !important;
		margin: 1em;
		padding-top: 1em;
		page-break-inside: avoid;
		width: 7in;
		margin-left: 0;
		margin-right: auto;
	}
	div.overdueSlip div.patronHeader {
		display: table !important;		
		width: 7in;
		margin-left: auto;
		margin-right: auto;
	}
	div.overdueSlip div.patronHeader div {
		display: table-cell !important;
		font-weight: bold;
	}
	div.P_TYPE {
		width: 1in !important;
	}
	div.HOME_ROOM {
		width: 2in !important;
	}
	div.PATRON_NAME {
		width: 3in !important;
	}
	div.P_BARCODE {
		text-align: right;
		width: 1in !important;
	}
	div.overdueSlip div.overdueRecordTable {
		display: table-row !important;		
		width: 7in;
		margin-left: auto;
		margin-right: auto;
	}
	div.overdueSlip div.overdueRecordTable div.overdueRecordTableMessage {
		width: 7in;
		margin-left: auto;
		margin-right: auto;
		text-align: left;
	}
	div.overdueSlip div.overdueRecordTable div.overdueRecord {
		width: 7in !important;
		margin-left: auto !important;
		margin-right: auto !important;
	}
	div.overdueSlip div.overdueRecordTable div.overdueRecord div {
		display: table-cell !important;
	}
	div.SYSTEM {
		width: .5in !important;
	}
	div.ITEM_ID {
		width: 1.25in !important;
	}
	div.CALL_NUMBER {
		width: 1.25in !important;;
	}
	div.TITLE {
		width: 2.5in !important;
	}
	div.DUE_DATE {
		width: .75in !important;
	}
	div.PRICE {
		text-align: right;
		width: .75in !important;
	}
	table#studentReportTable {
		width: 7in;
		margin-left: 0;
		margin-right: auto;
		font: inherit;
		border: 0;
	}
	table#studentReportTable .hideit {
		display: none;
	}
	table#studentReportTable thead {
		display: table !important;
	}
	table#studentReportTable tbody tr td {
		border: 0;
	}

	@media print {
		div.breadcrumbs {
			display: none !important;
		}
		div.doNotPrint {
			display: none !important;
		}
		div#footer-container {
			display: none !important;
		}
		div#main-content {
			padding: 0in !important;
		}
		div#main-content-with-sidebar {
			padding: 0in !important;
		}
		div#system-message-header {
			display: none !important;
		}
		table#studentReportTable {
			margin: 0in;
		}
		table#studentReportTable .displayScreen {
			display: none;
		}
		table#studentReportTable thead {
			display: none !important;
		}

	}
</style>
{/literal}

		<table id="studentReportTable">
			<thead>
				<tr>
					<th class="filter-select filter-onlyAvail">Grade</th>
					<th class="filter-select filter-onlyAvail">Homeroom</th>
					<th class="sorter-false">Student ID</th>
					<th class="filter">Student Name</th>
					<th class="sorter-false">Notice</th>
				<tr>
			</thead>
			<tbody>
{assign var=previousPatron value=0}
{foreach from=$reportData item=dataRow name=overdueData}
	{if !$smarty.foreach.overdueData.first}
	{if $dataRow.P_BARCODE != $previousPatron}
		{if $smarty.foreach.overdueData.index > 0}</div></div></td></tr>{/if}
				<tr class="overdueSlipContainer">
					<td class="hideit">{$dataRow.GRD_LVL|replace:' student':''|replace:'MNPS School Librar':'0.0 MNPS School Librar'|replace:'MNPS Staff':'0.1 MNPS Staff'|replace:'Pre-K':'0.2 Pre-K'|replace:'Kindergar':'0.3 Kindergar'|replace:'First':'1 First'|replace:'Second':'2 Second'|replace:'Third':'3 Third'|replace:'Fourth':'4 Fourth'|replace:'Fifth':'5 Fifth'|replace:'Sixth':'6 Sixth'|replace:'Seventh':'7 Seventh'|replace:'Eighth':'8 Eighth'|replace:'Ninth':'9 Ninth'|replace:'Tenth':'10 Tenth'|replace:'Eleventh':'11 Eleventh'|replace:'Twelfth':'12 Twelfth'|regex_replace:'/^.*no LL delivery/':'13 no LL delivery'|replace:'MNPS 18+':'13 MNPS 18+'}</td>
					<td class="hideit">{$dataRow.HOME_ROOM|lower|capitalize:true}</td>
					<td class="hideit">{$dataRow.P_BARCODE}</td>
					<td class="hideit">{$dataRow.PATRON_NAME}</td>
					<td>
						<div class="overdueSlip">
							<div class="patronHeader">
								<div class="P_TYPE">{$dataRow.GRD_LVL|replace:' student':''}</div>
								<div class="HOME_ROOM">{$dataRow.HOME_ROOM|lower|capitalize:true}</div>
								<div class="PATRON_NAME">{$dataRow.PATRON_NAME|upper}</div>
								<div class="P_BARCODE">{$dataRow.P_BARCODE}</div>
							</div>
							<div class="overdueRecordTable">
								<div class="overdueRecordTableMessage">
									The items below are
									{if $showOverdueOnly}&nbsp;overdue{/if}
									{if !$showOverdueOnly}&nbsp;checked out{/if}
									. &nbsp; 
									Please return them to your library. This notice was created {$reportDateTime}<br>
									Check your account online at https://school.library.nashville.org/
								</div>
								<div class="overdueRecord">
                                        				<div class="SYSTEM">SYSTEM</div>
                                        				<div class="ITEM_ID">BARCODE</div>
                                        				<div class="CALL_NUMBER">CALL NUMBER</div>
                                        				<div class="TITLE">TITLE</div>
                                        				<div class="DUE_DATE">DUE DATE</div>
									<div class="PRICE">PRICE</div>
								</div>
		{assign var=previousPatron value=$dataRow.P_BARCODE}
	{/if}
								<div class="overdueRecord">
									<div class="SYSTEM">{$dataRow.SYSTEM|replace:"1":"NPL"|replace:"2":"MNPS"}</div>
					                                <div class="ITEM_ID">{$dataRow.ITEM}</div>
									<div class="CALL_NUMBER">{$dataRow.CALL_NUMBER}</div>
									<div class="TITLE">{$dataRow.TITLE|regex_replace:"/ *\/ *$/":""}</div>
									<div class="DUE_DATE">{$dataRow.DUE_DATE}</div>
									<div class="PRICE">{$dataRow.OWED|regex_replace:"/^ *0\.00$/":"10.00"}</div>
								</div>	
	{/if}
{/foreach}
					</td>
				</tr>
			</tbody>
		</table>
		<script type="text/javascript">
			{literal}
				$(document).ready(function(){
					$('#studentReportTable').tablesorter({
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
