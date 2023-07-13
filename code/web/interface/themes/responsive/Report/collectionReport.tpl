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
				<input type="submit" name="download" value="Download CSV" class="btn btn-sm btn-info"/>
				&nbsp;
			</form>
			{if !empty($reportData)}

		</div>
{literal}
<style>
	table#reportTable {
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
		border: 1px black solid !important;
		overflow: hidden;
	}
	td,CIRC, td.MEDIA, td.PRICE, td.PUBDATE {
		/*min-width: 6ex !important;*/
		max-width: 6ex !important;
    }
	td.BARCODE, td.CALLNUMBER, td.CREATED, td.LASTRETURNED, td.LOCATION, td.STATUS, td.STATUSDATE {
		min-width: 12ex !important;
		max-width: 12ex !important;
    }
	td.AUTHOR {
		min-width: 20ex !important;
    }
	td.TITLE {
		min-width: 30ex !important;
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
{*					<th>Item</th>*}
{*					<th>BID</th>*}
					<th>Title</th>
					<th>Author</th>
					<th>PubDate</th>
					<th>Price</th>
					<th>Location</th>
					<th>Media</th>
					<th>Call Number</th>
					<th>Last Returned</th>
					<th>Cumulative Circ</th>
					<th>Barcode</th>
					<th>Created</th>
					<th>Status</th>
					<th>Status Date</th>
				</tr>
			</thead>
			<tbody>
{assign var=previousPatron value=0}
{foreach from=$reportData item=dataRow name=data}
				<tr class="recordContainer">
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
	{/if}
	</div>
{/strip}
