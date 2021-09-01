{strip}
	<div id="main-content" class="col-md-12">
		<div class="doNotPrint">
		{if $loggedIn}
			<h1>{translate text="Holds Report" isAdminFacing=true}</h1>
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
			<form class="form form-inline">

				{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}

				<input type="submit" name="showData" value="{translate text="Show Data" inAttribute=true isAdminFacing=true}" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="button" name="printSlips" value="{translate text="Print Slips" inAttribute=true isAdminFacing=true}" class="btn btn-sm btn-primary" onclick="{literal} JsBarcode('.barcode').init(); var x = document.querySelectorAll('.holdsReportSlipContainer'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'auto'; } window.print(); {/literal}" />
				&nbsp;
				<input type="submit" name="download" value="{translate text="Download CSV" inAttribute=true isAdminFacing=true}" class="btn btn-sm btn-info"/>
				&nbsp;
			</form>
			{if $reportData}
				<br/>
				<p>
					There are a total of <strong>{$reportData|@count}</strong> Fill List items.
				</p>
            {/if}
		</div>
{literal}
<style type="text/css">
    @media screen {
        .displayPrint {
            display: none !important;
        }
    }
	@media print {
		@page {
			size: letter;
			margin: 0in !important;  /* this affects the margin in the printer settings */
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
		div#main-content {
			padding: 0in !important;
		}
		div#main-content-with-sidebar {
			padding: 0in !important;
		}
		div#system-message-header {
			display: none !important;
		}
		table#holdsReportTable {
			margin: 0in;
		}
		table#holdsReportTable thead tr {
			display: none !important;
		}
        .holdsReportSlipContainer{
            border-bottom: 1px dashed #ccc !important;
            border-top: 1px dashed #ccc !important;
            display: table !important;
            height: 2.75in !important;
            max-height: 2.75in !important;
            max-width: 8in !important;
            min-height: 2.75in !important;
            min-width: 8in !important;
            width: 8in !important;
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
            height: 2.6in !important;
            padding: .25in !important;
            transform: rotate(-90deg);
            width: 2.75in;
        }
        div.holdsReportSlip div.patron div {
            font-weight: bold;
        }
        div.GRD_LVL {
        }
        div.HOME_ROOM {
        }
/*
        div.P_BARCODE_SCANNABLE {
            font-family: "3 of 9 Barcode";
            font-size: 20pt !important;
            font-weight: normal !important;
        }
*/
        div.PATRON_NAME {
        }
        div.holdsReportSlip div.placeHolder {
            display: table-cell;
            height: 2.6in;
            padding: .25in !important;
            transform: rotate(-90deg);
            width: 2.75in;
        }
        div.holdsReportSlip div.item {
            display: table-cell;
            height: 2.6in !important;
            padding: .25in !important;
            transform: rotate(90deg);
            width: 2.75in;
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
        }
        table#holdsReportTable .displayScreen {
            display: none;
        }
        table#holdsReportTable thead {
            display: table !important;
        }
        table#holdsReportTable tbody tr td {
            border: 0;
        }
	}
</style>
{/literal}

		<table id="holdsReportTable">
			<thead>
				<tr>
					<th class="filter-select filter-onlyAvail">Grade</th>
					<th class="filter-select filter-onlyAvail">Homeroom</th>
					<th class="sorter-false">Student ID</th>
					<th class="filter">Student Name</th>
                    <th class="filter-select filter-onlyAvail">Shelf Location</th>
                    <th class="filter">Call Number</th>
                    <th class="filter">Title</th>
                    <th class="sorter-false">Notice</th>
				<tr>
			</thead>
			<tbody>
{assign var=previousPatron value=0}
{foreach from=$reportData item=dataRow name=holdsReportData}
		{if $smarty.foreach.holdsReportData.index > 0}</div></div></td></tr>{/if}
				<tr class="holdsReportSlipContainer">
					<td class="displayScreen">{$dataRow.GRD_LVL|replace:' student':''|replace:'MNPS School Librar':'0.0 MNPS School Librar'|replace:'MNPS Staff':'0.1 MNPS Staff'|replace:'Pre-K':'0.2 Pre-K'|replace:'Kindergar':'0.3 Kindergar'|replace:'First':'1 First'|replace:'Second':'2 Second'|replace:'Third':'3 Third'|replace:'Fourth':'4 Fourth'|replace:'Fifth':'5 Fifth'|replace:'Sixth':'6 Sixth'|replace:'Seventh':'7 Seventh'|replace:'Eighth':'8 Eighth'|replace:'Ninth':'9 Ninth'|replace:'Tenth':'10 Tenth'|replace:'Eleventh':'11 Eleventh'|replace:'Twelfth':'12 Twelfth'|regex_replace:'/^.*no LL delivery/':'13 no LL delivery'|replace:'MNPS 18+':'13 MNPS 18+'}</td>
					<td class="displayScreen">{$dataRow.HOME_ROOM|lower|capitalize:true}</td>
					<td class="displayScreen">{$dataRow.P_BARCODE}</td>
					<td class="displayScreen">{$dataRow.PATRON_NAME}</td>
                    <td class="displayScreen">{$dataRow.SHELF_LOCATION|replace:'kids ':''|replace:'teen ':''|replace:'adult ':''}</td>
                    <td class="displayScreen">{$dataRow.CALL_NUMBER}</td>
                    <td class="displayScreen">{$dataRow.TITLE}</td>
                    <td class="displayPrint">
						<div class="holdsReportSlip">
							<div class="patron">
								<div class="PATRON_NAME">{$dataRow.PATRON_NAME|upper}</div>
								<div class="P_BARCODE_SCANNABLE"><svg class="barcode" jsbarcode-format="CODE39" jsbarcode-value="{$dataRow.P_BARCODE}" jsbarcode-fontsize="1" jsbarcode-height="20" jsbarcode-text=" " jsbarcode-width="1"></svg></div>
								<div class="GRD_LVL">{$dataRow.GRD_LVL|replace:' student':''}</div>
								<div class="HOME_ROOM">{$dataRow.HOME_ROOM|lower|capitalize:true}</div>
							</div>
							<div class="placeHolder">
                                <p>Thank you for checking out from your school library.</p>
                                <p>Books are due in 2 weeks.</p>
                                <p>DVDs are due in 1 week.</p>
                                <p>Check your account online at https://limitlesslibraries.org</p>
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
		<script src="/js/jsBarcode/JsBarcode.all.min.js"></script>

{/if}
	</div>
{/strip}
