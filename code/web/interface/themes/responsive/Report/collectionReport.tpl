{strip}
	<div id="main-content" class="col-md-12">
		<div class="doNotPrint">
			<h1>Collection Report</h1>
			{if isset($errors)}
				{foreach from=$errors item=error}
					<div class="error">{$error}</div>
				{/foreach}
			{/if}
			<form class="form form-inline">

				{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm"}
				&nbsp;
				<input type="submit" name="showData" value="Show Data" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="button" name="printPages" value="Print Pages" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.overdueSlipContainer'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'always'; } window.print(); {/literal}" />
				&nbsp;
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
				&nbsp;
			</form>
			{if !empty($reportData)}

		</div>
{literal}
<style>
	table#reportTable {
		width: 7in;
		margin-left: 0;
		margin-right: auto;
		font: inherit;
		border: 0;
	}
	table#reportTable .hideit {
		display: none;
	}
	table#reportTable thead {
		/*display: table !important;*/
	}
	table#reportTable tbody tr td {
		/*border: 0;*/
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
		table#reportTable {
			margin: 0in;
		}
		table#reportTable .displayScreen {
			display: none;
		}
		table#reportTable thead {
			display: none !important;
		}
		/* Chromium + Safari */
		/* fix for "Print Pages" */
		@supports (not (-moz-appearance: none)) {
			tr.recordContainer {
				display: block;
			}
		}
	}
</style>
{/literal}

		<table id="reportTable">
			<thead>
				<tr>
					<th class="filter">Item</th>
					<th class="filter">BID</th>
					<th class="filter">Title</th>
					<th class="filter">Author</th>
					<th class="filter">PubDate</th>
					<th class="filter">Price</th>
					<th class="filter-select filter-onlyAvail">Location</th>
					<th class="filter-select filter-onlyAvail">Media</th>
					<th class="filter">Call Number</th>
					<th class="filter">Last Returned</th>
					<th class="filter">Cumulative Circ</th>
					<th class="filter">Barcode</th>
					<th class="filter">Created</th>
					<th class="filter-select filter-onlyAvail">Status</th>
					<th class="filter">Status Date</th>
				</tr>
			</thead>
			<tbody>
{assign var=previousPatron value=0}
{foreach from=$reportData item=dataRow name=data}
				<tr class="recordContainer">
					<td class="ITEM">{$dataRow.ITEM}</td>
					<td class="BID">{$dataRow.BID}</td>
					<td class="TITLE">{$dataRow.TITLE}</td>
					<td class="AUTHOR">{$dataRow.AUTHOR}</td>
					<td class="PUBDATE">{$dataRow.PUBDATE}</td>
					<td class="PRICE">{$dataRow.PRICE}</td>
					<td class="LOCATION">{$dataRow.LOCATION}</td>
					<td class="MEDIA">{$dataRow.MEDIA}</td>
					<td class="CALLNUMBER">{$dataRow.CALLNUMBER}</td>
					<td class="LASTRETURNED">{$dataRow.LASTRETURNED}</td>
					<td class="CIRC">{$dataRow.CIRC}</td>
					<td class="BARCODE">{$dataRow.BARCODE}</td>
					<td class="CREATED">{$dataRow.CREATED}</td>
					<td class="STATUS">{$dataRow.STATUS}</td>
					<td class="STATUSDATE">{$dataRow.STATUSDATE}</td>
				</tr>
{/foreach}
			</tbody>
		</table>
		<script type="text/javascript">
			{literal}
				$(document).ready(function(){
					$('#reportTable').tablesorter({
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
