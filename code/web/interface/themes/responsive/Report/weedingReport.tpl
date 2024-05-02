{strip}
	<div id="main-content" class="col-md-12">
		<div class="doNotPrint">
		{if !empty($loggedIn)}
		<h1>{translate text="Weeding Report" isAdminFacing=true}</h1>
		{if isset($errors)}
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
		{/if}
		<form class="form form-inline">

			{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}

			<input type="submit" name="showData" value="{translate text="Show Data" inAttribute=true isAdminFacing=true}" class="btn btn-sm btn-primary"/>
			&nbsp;
			<input type="submit" name="download" value="{translate text="Download CSV" inAttribute=true isAdminFacing=true}" class="btn btn-sm btn-info"/>
			&nbsp;
		</form>
		{if !empty($reportData)}
			<br/>
			<p>
				There are a total of <strong>{$reportData|@count}</strong> items.
			</p>
		{/if}
		</div>
	</div>
{literal}
<style>
table#weedingReportTable tbody tr {
	border: black 1px solid;
	page-break-inside: avoid;
}
table#weedingReportTable tbody tr:nth-child(even) {
	background: #EEEEEE;
}
.weedingReport-discard {
	background-color: #FFCCCC !important;
}
.weedingReport-evaluate {
	background-color: #FFFFCC !important;
}
.weedingReport-fixPubDate {
	background-color: #FFCCFF !important;
}
.weedingReport-grubby {
	background-color: #FFCC99 !important;
}
@media print {
	@page {
		size: letter landscape;
		margin: 0in !important;  /* this affects the margin in the printer settings */
	}
	html {
		margin: 0px; /* this affects the margin on the html before sending to printer */
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
	table#weedingReportTable {
		margin: 0in;
	}
	/*table#weedingReportTable thead tr {*/
	/*	display: none !important;*/
	/*}*/

}
</style>
{/literal}
	<table id="weedingReportTable">
		<thead>
			<tr>
				<th class="filter-select filter-onlyAvail">Collection</th>
				<th class="sorter-false">Item Call Number</th>
				<th class="sorter-false">Item Barcode</th>
				<th class="filter-select filter-onlyAvail">Item Status</th>
{*				<th class="sorter-false">BID</th>*}
				<th class="sorter-false">Title</th>
				<th class="sorter-false">Author</th>
				<th class="sorter-false">Publish Date</th>
				<th class="sorter-false">Cumulative Circulation</th>
				<th class="sorter-false">Last Return Date</th>
				<th class="filter-select filter-onlyAvail">ACTION</th>
				<th class="filter-select filter-onlyAvail">GRUBBY?</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$reportData item=dataRow name=weedingReportData}
				<tr {if $dataRow.ACTION == 'FIX PUB DATE'}class="weedingReport-fixPubDate"{elseif $dataRow.ACTION == 'DISCARD'}class="weedingReport-discard"{elseif $dataRow.ACTION == 'EVALUATE'}class="weedingReport-evaluate"{elseif $dataRow.GRUBBY == 'EVALUATE FOR WEAR AND TEAR'}class="weedingReport-grubby"{/if}>
					<td>{$dataRow.COLLECTION}</td>
					<td>{$dataRow.ITEM_CALLNUMBER}</td>
					<td>{$dataRow.ITEM}</td>
					<td>{$dataRow.STATUS}</td>
{*					<td>{$dataRow.BID}</td>*}
					<td>{$dataRow.TITLE}</td>
					<td>{$dataRow.AUTHOR}</td>
					<td>{$dataRow.PUBLISHINGDATE}</td>
					<td>{$dataRow.CUMULATIVEHISTORY}</td>
					<td>{$dataRow.RETURNDATE}</td>
					<td>{$dataRow.ACTION}</td>
					<td>{$dataRow.GRUBBY}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
	<script type="text/javascript">
		{literal}
		$(document).ready(function(){
			$('#weedingReportTable').tablesorter({
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
