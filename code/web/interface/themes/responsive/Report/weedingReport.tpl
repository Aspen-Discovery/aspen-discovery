{strip}
	<div id="main-content" class="col-md-12">
		<div class="doNotPrint">
		{if !empty($loggedIn)}
		<h1>{translate text="Weeding Report" isAdminFacing=true}</h1>
			<div class="help-block alert alert-warning">
				<ul>
					<li>Please be patient... this report often takes more than one minute to complete retrieving and formatting the data.</li>
					<li>To print, press Ctrl+P. Please be patient... the report re-composes itself in print preview.</li>
					<li>To print colored rows: In your browser's print dialog box, ensure that More Settings > Print Background Graphics is checked.</li>
					<li>If this report is not working for you, please <a href="https://nashvillepl.libanswers.com/form?queue_id=3576">submit a help desk ticket</a>.</li>
				</ul>
			</div>
		{if isset($errors)}
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
		{/if}
		<form class="form form-inline" id="weedingReportForm">

			{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}
			{literal}
				<script type="text/javascript">
					let form = document.getElementById('weedingReportForm');
					form.addEventListener('submit', function (event) {
						let submitter = event.submitter;
						let handler = submitter.name;
						if (handler == 'showData') {
							$('#showData').prop('disabled', true);
							$('#showData').addClass('disabled');
							$('#showData .fa-spinner').removeClass('hidden');
							return true;
						}
						// if (handler == 'download') {
						// 	$('#download').prop('disabled', true);
						// 	$('#download').addClass('disabled');
						// 	$('#download .fa-spinner').removeClass('hidden');
						// 	return true;
						// }
					});
				</script>
			{/literal}
			<button type="submit" name="showData" id="showData" value="Show Data" class="btn btn-sm btn-primary"><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;{translate text="Show Data" inAttribute=true isAdminFacing=true}</button>
			<button type="submit" name="download" id="download" value="Download CSV" class="btn btn-sm btn-info"><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;{translate text="Download CSV" inAttribute=true isAdminFacing=true}</button>
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
#weedingReportTable tbody tr {
	border: black 1px solid;
	page-break-inside: avoid;
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
		margin: .25in !important;  /* this affects the margin in the printer settings */
	}
	html {
		margin: 0px; /* this affects the margin on the html before sending to printer */
	}
	body {
		margin: 0in !important; /* margin you want for the content */
	}
	.container {
		width: 100% !important;
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
	div#system-messages {
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
				<th class="sorter-false">Creation Date</th>
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
					<td>{$dataRow.CREATIONDATE}</td>
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
